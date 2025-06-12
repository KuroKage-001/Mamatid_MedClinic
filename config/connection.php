<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "db_mamatid01";

// Configure session settings for concurrent users (only if session not started)
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session configuration
    ini_set('session.cookie_httponly', 1);           // Prevent XSS attacks
    ini_set('session.cookie_secure', 0);             // Set to 1 for HTTPS in production
    ini_set('session.use_only_cookies', 1);          // Only use cookies for sessions
    ini_set('session.cookie_lifetime', 0);           // Session expires when browser closes
    ini_set('session.gc_maxlifetime', 1800);         // 30 minutes idle timeout
    ini_set('session.gc_probability', 1);            // Garbage collection probability
    ini_set('session.gc_divisor', 100);              // Garbage collection divisor
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically for security (every 10 minutes)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 600) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

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

// Session timeout check for inactive sessions
function checkSessionTimeout($session) {
    $timeout = 1800; // 30 minutes
    
    if (isset($session['user_id']) || isset($session['client_id'])) {
        if (isset($session['last_activity'])) {
            if (time() - $session['last_activity'] > $timeout) {
                // Session has expired - clear only this session
                $_SESSION = array();
                session_destroy();
                return false;
            }
        }
        $_SESSION['last_activity'] = time();
    }
    return true;
}

// Check session timeout only if user is logged in
if ((isset($_SESSION['user_id']) || isset($_SESSION['client_id'])) && !checkSessionTimeout($_SESSION)) {
    if (isset($_SESSION['client_id'])) {
        header("location: client_login.php?message=session_expired");
    } else {
        header("location: index.php?message=session_expired");
    }
    exit;
}

?>