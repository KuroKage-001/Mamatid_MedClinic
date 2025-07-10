<?php
/**
 * Security Protection - Directory Access Prevention
 * Logs unauthorized access attempts and redirects to main page
 * 
 * @package    Mamatid Health Center System
 * @subpackage Security
 * @version    2.0
 */

// Start output buffering to prevent header issues
ob_start();

// Log unauthorized access attempt
$log_message = sprintf(
    "[%s] Unauthorized access attempt to %s from IP: %s, User Agent: %s",
    date('Y-m-d H:i:s'),
    $_SERVER['REQUEST_URI'] ?? 'unknown',
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
);

// Log to error log
error_log($log_message);

// Optional: Log to custom security log file
$security_log_file = __DIR__ . '/../../system/security_access.log';
if (is_writable(dirname($security_log_file))) {
    file_put_contents($security_log_file, $log_message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Set comprehensive security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Security-Policy: default-src \'none\'; script-src \'unsafe-inline\'; style-src \'unsafe-inline\';');

// Send HTTP 403 Forbidden status
http_response_code(403);

// Immediate redirect with PHP
header('Location: ../../index.php');

// Clean output buffer and exit
ob_end_clean();
exit;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="0;url=../../index.php">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="no-referrer">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Content Security Policy -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src 'none';">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #f8f9fa;
            color: #dc3545;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚫</div>
        <h2>403 - Access Forbidden</h2>
        <p>You do not have permission to access this directory.</p>
        <p>This incident has been logged.</p>
        <p>Redirecting to main page...</p>
        
        <!-- Fallback JavaScript redirect -->
        <script>
            setTimeout(function() {
                window.location.href = '../../index.php';
            }, 1000);
        </script>
        
        <!-- Noscript fallback -->
        <noscript>
            <p><a href="../../index.php">Click here if you are not redirected automatically</a></p>
        </noscript>
    </div>
</body>
</html> 