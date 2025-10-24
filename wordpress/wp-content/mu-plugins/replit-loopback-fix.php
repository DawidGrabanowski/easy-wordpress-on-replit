<?php
/**
 * Plugin Name: Replit Loopback Request Handler
 * Description: Handles all WordPress loopback requests internally to avoid deadlock on PHP built-in server
 * Version: 0.1
 * Author: Dawid Grabanowski from https://www.mtzn.pl
 */

/**
 * Intercept all HTTP loopback requests and execute them directly without making actual HTTP calls.
 * This solves the deadlock issue on PHP built-in server where the server can't handle 
 * concurrent requests (it blocks waiting for itself).
 */
add_filter('pre_http_request', function($preempt, $parsed_args, $url) {
    // Only intercept requests to our own domain
    if (!isset($_SERVER['HTTP_HOST'])) {
        return $preempt;
    }
    
    $current_host = $_SERVER['HTTP_HOST'];
    $request_host = parse_url($url, PHP_URL_HOST);
    
    // Check if this is a loopback request (request to our own domain)
    if ($request_host !== $current_host && $request_host !== parse_url('https://' . $current_host, PHP_URL_HOST)) {
        return $preempt; // Allow external requests to pass through
    }
    
    // This is a loopback request - handle it internally
    $path = parse_url($url, PHP_URL_PATH);
    $query = parse_url($url, PHP_URL_QUERY);
    $method = isset($parsed_args['method']) ? strtoupper($parsed_args['method']) : 'GET';
    
    // Parse query parameters
    $query_params = array();
    if ($query) {
        parse_str($query, $query_params);
    }
    
    // ========================================
    // 1. Handle REST API requests
    // ========================================
    if (strpos($path, '/wp-json/') !== false || (isset($query_params['rest_route']))) {
        $rest_route = '';
        
        if (strpos($path, '/wp-json/') !== false) {
            // Extract route from path (e.g., /wp-json/wp/v2/posts -> /wp/v2/posts)
            $rest_route = str_replace('/wp-json', '', $path);
        } elseif (isset($query_params['rest_route'])) {
            // Extract route from query string
            $rest_route = $query_params['rest_route'];
        }
        
        if ($rest_route) {
            // Build REST request object
            $request = new WP_REST_Request($method, $rest_route);
            
            // Add query parameters
            foreach ($query_params as $key => $value) {
                if ($key !== 'rest_route') {
                    $request->set_param($key, $value);
                }
            }
            
            // Add body parameters for POST/PUT/PATCH requests
            if (isset($parsed_args['body'])) {
                if (is_string($parsed_args['body'])) {
                    $body = json_decode($parsed_args['body'], true);
                    if ($body) {
                        $request->set_body_params($body);
                    } else {
                        // If not JSON, parse as form data
                        parse_str($parsed_args['body'], $body_params);
                        $request->set_body_params($body_params);
                    }
                } elseif (is_array($parsed_args['body'])) {
                    $request->set_body_params($parsed_args['body']);
                }
            }
            
            // Add headers
            if (isset($parsed_args['headers'])) {
                foreach ($parsed_args['headers'] as $key => $value) {
                    $request->set_header($key, $value);
                }
            }
            
            // Execute the REST API request directly (no HTTP!)
            $response = rest_do_request($request);
            
            // Handle WP_REST_Response
            if ($response instanceof WP_REST_Response) {
                return array(
                    'response' => array(
                        'code' => $response->get_status(),
                        'message' => 'OK'
                    ),
                    'body' => wp_json_encode($response->get_data()),
                    'headers' => $response->get_headers(),
                    'cookies' => array()
                );
            }
            
            // Handle WP_Error (REST API errors)
            if (is_wp_error($response)) {
                $error_data = $response->get_error_data();
                $status_code = 500; // Default error code
                
                // Extract status code if available
                if (is_array($error_data) && isset($error_data['status'])) {
                    $status_code = (int) $error_data['status'];
                } elseif (is_int($error_data)) {
                    $status_code = $error_data;
                }
                
                return array(
                    'response' => array(
                        'code' => $status_code,
                        'message' => 'Error'
                    ),
                    'body' => wp_json_encode(array(
                        'code' => $response->get_error_code(),
                        'message' => $response->get_error_message(),
                        'data' => $error_data
                    )),
                    'headers' => array('content-type' => 'application/json'),
                    'cookies' => array()
                );
            }
        }
    }
    
    // ========================================
    // 2. Handle WP-Cron requests
    // ========================================
    if (strpos($path, '/wp-cron.php') !== false) {
        // Execute WP-Cron directly without HTTP request
        ob_start();
        
        // Set up $_GET parameters for wp-cron.php
        $old_get = $_GET;
        $_GET = $query_params;
        
        // Define DOING_CRON constant
        if (!defined('DOING_CRON')) {
            define('DOING_CRON', true);
        }
        
        // Run cron directly (wp_cron() executes tasks, spawn_cron() makes HTTP request)
        wp_cron();
        
        // Restore $_GET
        $_GET = $old_get;
        
        $output = ob_get_clean();
        
        return array(
            'response' => array(
                'code' => 200,
                'message' => 'OK'
            ),
            'body' => $output,
            'headers' => array(),
            'cookies' => array()
        );
    }
    
    // ========================================
    // 3. Handle admin-ajax.php requests (including Site Health)
    // ========================================
    if (strpos($path, '/wp-admin/admin-ajax.php') !== false) {
        // Save current state
        $old_get = $_GET;
        $old_post = $_POST;
        $old_request = $_REQUEST;
        
        // Set up request parameters
        $_GET = $query_params;
        $_POST = array();
        
        // Handle POST body
        if ($method === 'POST' && isset($parsed_args['body'])) {
            if (is_string($parsed_args['body'])) {
                parse_str($parsed_args['body'], $_POST);
            } elseif (is_array($parsed_args['body'])) {
                $_POST = $parsed_args['body'];
            }
        }
        
        $_REQUEST = array_merge($_GET, $_POST);
        
        // Set required constants
        if (!defined('DOING_AJAX')) {
            define('DOING_AJAX', true);
        }
        
        // Custom wp_die handler that captures output instead of exiting
        $captured_output = '';
        $captured_status = 200;
        $die_handler_triggered = false;
        
        $custom_die_handler = function($message, $title = '', $args = array()) use (&$captured_output, &$captured_status, &$die_handler_triggered) {
            $die_handler_triggered = true;
            
            // Extract status code if provided
            if (is_array($args) && isset($args['response'])) {
                $captured_status = (int) $args['response'];
            } elseif (is_int($args)) {
                $captured_status = $args;
            }
            
            // Capture the message
            if (is_wp_error($message)) {
                $captured_output = $message->get_error_message();
            } else {
                $captured_output = (string) $message;
            }
            
            // Throw exception to stop execution without killing the whole process
            throw new Exception('wp_die_called');
        };
        
        // Override wp_die handler
        add_filter('wp_die_ajax_handler', function() use ($custom_die_handler) {
            return $custom_die_handler;
        }, 1);
        
        // Capture all output
        ob_start();
        
        try {
            // Execute admin-ajax.php logic by triggering the appropriate action
            if (isset($_REQUEST['action'])) {
                $action = sanitize_text_field($_REQUEST['action']);
                
                // Fire the WordPress AJAX hooks
                if (is_user_logged_in()) {
                    do_action('wp_ajax_' . $action);
                } else {
                    do_action('wp_ajax_nopriv_' . $action);
                }
            }
            
            $output = ob_get_clean();
            
        } catch (Exception $e) {
            // wp_die was called - get captured output
            $output = ob_get_clean();
            if ($die_handler_triggered) {
                $output = $captured_output ?: $output;
            }
        }
        
        // Remove our custom handler
        remove_all_filters('wp_die_ajax_handler', 1);
        
        // Restore original state
        $_GET = $old_get;
        $_POST = $old_post;
        $_REQUEST = $old_request;
        
        return array(
            'response' => array(
                'code' => $die_handler_triggered ? $captured_status : 200,
                'message' => 'OK'
            ),
            'body' => $output ?: '0', // WordPress AJAX returns '0' if no handler
            'headers' => array('content-type' => 'text/html; charset=UTF-8'),
            'cookies' => array()
        );
    }
    
    // ========================================
    // 4. Handle all other loopback requests
    // ========================================
    // For any other loopback request, try to simulate it by including the target file
    
    // Extract the PHP file being requested
    $wordpress_root = ABSPATH;
    $relative_path = ltrim($path, '/');
    $target_file = $wordpress_root . $relative_path;
    
    // Security check - ensure file exists and is within WordPress directory
    if (file_exists($target_file) && strpos(realpath($target_file), realpath($wordpress_root)) === 0) {
        // Save current state
        $old_get = $_GET;
        $old_post = $_POST;
        $old_request = $_REQUEST;
        $old_server = $_SERVER;
        
        // Set up request environment
        $_GET = $query_params;
        $_POST = array();
        
        if ($method === 'POST' && isset($parsed_args['body'])) {
            if (is_string($parsed_args['body'])) {
                parse_str($parsed_args['body'], $_POST);
            } elseif (is_array($parsed_args['body'])) {
                $_POST = $parsed_args['body'];
            }
        }
        
        $_REQUEST = array_merge($_GET, $_POST);
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path . ($query ? '?' . $query : '');
        
        // Custom wp_die handler for generic requests
        $captured_output = '';
        $captured_status = 200;
        $die_handler_triggered = false;
        
        $custom_die_handler = function($message, $title = '', $args = array()) use (&$captured_output, &$captured_status, &$die_handler_triggered) {
            $die_handler_triggered = true;
            
            if (is_array($args) && isset($args['response'])) {
                $captured_status = (int) $args['response'];
            } elseif (is_int($args)) {
                $captured_status = $args;
            }
            
            if (is_wp_error($message)) {
                $captured_output = $message->get_error_message();
            } else {
                $captured_output = (string) $message;
            }
            
            throw new Exception('wp_die_called');
        };
        
        // Override wp_die handler
        add_filter('wp_die_handler', function() use ($custom_die_handler) {
            return $custom_die_handler;
        }, 1);
        
        // Capture output
        ob_start();
        
        try {
            include $target_file;
            $output = ob_get_clean();
            $status_code = 200;
            
        } catch (Exception $e) {
            $output = ob_get_clean();
            if ($die_handler_triggered) {
                $output = $captured_output ?: $output;
                $status_code = $captured_status;
            } else {
                $output = $e->getMessage();
                $status_code = 500;
            }
        }
        
        // Remove our custom handler
        remove_all_filters('wp_die_handler', 1);
        
        // Restore original state
        $_GET = $old_get;
        $_POST = $old_post;
        $_REQUEST = $old_request;
        $_SERVER = $old_server;
        
        return array(
            'response' => array(
                'code' => $status_code,
                'message' => $status_code === 200 ? 'OK' : 'Error'
            ),
            'body' => $output,
            'headers' => array('content-type' => 'text/html; charset=UTF-8'),
            'cookies' => array()
        );
    }
    
    // If we couldn't handle the request specifically, return a success response
    // This prevents blocking and allows WordPress to continue functioning
    return array(
        'response' => array(
            'code' => 200,
            'message' => 'OK'
        ),
        'body' => '',
        'headers' => array('content-type' => 'text/html; charset=UTF-8'),
        'cookies' => array()
    );
}, 10, 3);

/**
 * Alternative WP-Cron implementation that doesn't rely on HTTP requests
 * This ensures cron jobs run even if loopback requests would normally fail
 */
add_action('init', function() {
    // Check if cron needs to run
    if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
        return; // Respect the DISABLE_WP_CRON constant if set
    }
    
    // Only run cron on normal page loads (not AJAX, not admin)
    if (defined('DOING_AJAX') || defined('DOING_CRON') || is_admin()) {
        return;
    }
    
    // Check if cron is due (with low probability to avoid overhead)
    if (rand(1, 100) <= 5) { // 5% chance on each page load
        if (!defined('DOING_CRON')) {
            define('DOING_CRON', true);
        }
        // Use wp_cron() to execute tasks directly, not spawn_cron() which makes HTTP request
        wp_cron();
    }
}, 1);
