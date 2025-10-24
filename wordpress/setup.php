<?php
/**
 * Automatic WordPress Configuration Setup for Replit
 * 
 * This script automatically configures WordPress to use Replit's PostgreSQL database
 * by copying wp-config-sample.php to wp-config.php and generating unique security keys.
 */

// Check if wp-config.php already exists
if (file_exists(__DIR__ . '/wp-config.php')) {
    // Already configured, redirect to WordPress
    header('Location: /');
    exit;
}

// Check if wp-config-sample.php exists
if (!file_exists(__DIR__ . '/wp-config-sample.php')) {
    die('Error: wp-config-sample.php not found. Please check your WordPress installation.');
}

// Read the sample config
$config_content = file_get_contents(__DIR__ . '/wp-config-sample.php');

if ($config_content === false) {
    die('Error: Could not read wp-config-sample.php');
}

// Fetch unique authentication keys from WordPress.org API
$secret_keys = @file_get_contents('https://api.wordpress.org/secret-key/1.1/salt/');

if ($secret_keys === false) {
    // If API call fails, generate some basic random keys (fallback)
    $secret_keys = "define('AUTH_KEY',         '" . bin2hex(random_bytes(32)) . "');\n";
    $secret_keys .= "define('SECURE_AUTH_KEY',  '" . bin2hex(random_bytes(32)) . "');\n";
    $secret_keys .= "define('LOGGED_IN_KEY',    '" . bin2hex(random_bytes(32)) . "');\n";
    $secret_keys .= "define('NONCE_KEY',        '" . bin2hex(random_bytes(32)) . "');\n";
    $secret_keys .= "define('AUTH_SALT',        '" . bin2hex(random_bytes(32)) . "');\n";
    $secret_keys .= "define('SECURE_AUTH_SALT', '" . bin2hex(random_bytes(32)) . "');\n";
    $secret_keys .= "define('LOGGED_IN_SALT',   '" . bin2hex(random_bytes(32)) . "');\n";
    $secret_keys .= "define('NONCE_SALT',       '" . bin2hex(random_bytes(32)) . "');\n";
}

// Replace the placeholder keys with real ones
$config_content = preg_replace(
    '/define\(\s*\'AUTH_KEY\',\s*\'put your unique phrase here\'\s*\);.*?define\(\s*\'NONCE_SALT\',\s*\'put your unique phrase here\'\s*\);/s',
    trim($secret_keys),
    $config_content
);

// Write the new wp-config.php
$result = file_put_contents(__DIR__ . '/wp-config.php', $config_content);

if ($result === false) {
    die('Error: Could not write wp-config.php. Please check file permissions.');
}

// Success! Show a friendly message and redirect to WordPress installation
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Setup Complete</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: #f1f1f1;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.13);
            text-align: center;
        }
        h1 {
            color: #23282d;
            margin-bottom: 20px;
        }
        p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .checkmark {
            font-size: 80px;
            color: #46b450;
            margin-bottom: 20px;
        }
        .button {
            background: #0073aa;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            font-weight: 600;
            transition: background 0.2s;
        }
        .button:hover {
            background: #005a87;
        }
        .info {
            background: #e5f5fa;
            border-left: 4px solid #00a0d2;
            padding: 15px;
            margin-top: 30px;
            text-align: left;
        }
        .info strong {
            color: #00a0d2;
        }
    </style>
    <meta http-equiv="refresh" content="3;url=/wp-admin/install.php">
</head>
<body>
    <div class="container">
        <div class="checkmark">✓</div>
        <h1>Configuration Complete!</h1>
        <p>
            Your WordPress installation has been automatically configured for Replit's PostgreSQL database.
            Unique security keys have been generated for your site.
        </p>
        <p>
            You will be redirected to the WordPress installation wizard in 3 seconds...
        </p>
        <a href="/wp-admin/install.php" class="button">Continue to Installation →</a>
        
        <div class="info">
            <strong>What's next?</strong><br>
            You'll complete the WordPress installation by providing:
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Site title</li>
                <li>Admin username and password</li>
                <li>Admin email address</li>
            </ul>
            The database is already configured automatically! Have a great day! And give us a star :)
        </div>
    </div>
</body>
</html>
