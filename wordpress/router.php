<?php
// Router for PHP built-in server with WordPress
// This helps the built-in server handle WordPress routing correctly

// Set proper server variables for Replit environment
// Force HTTPS for all Replit environments (dev and production)
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = 443;

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Check if WordPress needs to be configured
if (!file_exists(__DIR__ . '/wp-config.php') && $uri !== '/setup.php') {
    // No wp-config.php yet - redirect to automatic setup
    header('Location: /setup.php');
    exit;
}

// If the request is for a file that exists, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise, pass the request to WordPress index.php
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require __DIR__ . '/index.php';
