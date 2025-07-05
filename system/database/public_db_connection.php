<?php
/**
 * Public Database Connection Configuration
 * 
 * This file is used for public pages that don't require authentication
 * like password reset, registration, etc.
 * 
 * @package    Mamatid Health Center System
 * @subpackage Database
 * @version    1.0
 */

$host = "localhost";
$user = "root";
$password = "";
$db = "db_mamatid01";

// Configure session settings (only if session not started)
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session configuration
    ini_set('session.cookie_httponly', 1);           // Prevent XSS attacks
    ini_set('session.cookie_secure', 0);             // Set to 1 for HTTPS in production
    ini_set('session.use_only_cookies', 1);          // Only use cookies for sessions
    ini_set('session.cookie_lifetime', 0);           // Session expires when browser closes
    ini_set('session.gc_maxlifetime', 3600);         // 1 hour idle timeout
    ini_set('session.gc_probability', 1);            // Garbage collection probability
    ini_set('session.gc_divisor', 100);              // Garbage collection divisor
    
    // Start session
    session_start();
}

try {
    // Create PDO connection with optimized settings
    $con = new PDO("mysql:dbname=$db;port=3306;host=$host;charset=utf8mb4",
        $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,        // Use native prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // Set timezone for database operations
    $con->exec("SET time_zone = '+00:00'");
    
} catch(PDOException $e) {
    // Log the error instead of displaying it to users in production
    error_log("Database connection failed: " . $e->getMessage());
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?> 