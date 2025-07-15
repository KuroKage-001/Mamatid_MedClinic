<?php
/**
 * Database Connection Configuration
 * 
 * @package    Mamatid Health Center System
 * @subpackage Database
 * @version    1.0
 */

$host = "localhost";
$user = "root";
$password = "";
$db = "db_mamatid01";

try {
    // Create PDO connection with optimized settings for concurrent users
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
    
    //echo "Connected successfully";
} catch(PDOException $e) {
    // Log the error instead of displaying it to users in production
    error_log("Database connection failed: " . $e->getMessage());
    echo "Connection failed: " . $e->getMessage();
    echo $e->getTraceAsString();
    exit;
}
?> 