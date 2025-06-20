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
    
    // Check admin/staff session timeout
    if (isset($session['user_id'])) {
        if (isset($session['admin_last_activity'])) {
            if (time() - $session['admin_last_activity'] > $timeout) {
                // Admin session has expired - only clear admin session variables
                $admin_session_vars = ['user_id', 'display_name', 'user_name', 'profile_picture', 'role', 'admin_last_activity'];
                foreach ($admin_session_vars as $var) {
                    if (isset($_SESSION[$var])) {
                        unset($_SESSION[$var]);
                    }
                }
                return false;
            }
        }
        $_SESSION['admin_last_activity'] = time();
    }
    
    // Check client session timeout
    if (isset($session['client_id'])) {
        if (isset($session['client_last_activity'])) {
            if (time() - $session['client_last_activity'] > $timeout) {
                // Client session has expired - only clear client session variables
                $client_session_vars = ['client_id', 'client_name', 'client_email', 'client_last_activity'];
                foreach ($client_session_vars as $var) {
                    if (isset($_SESSION[$var])) {
                        unset($_SESSION[$var]);
                    }
                }
                return false;
            }
        }
        $_SESSION['client_last_activity'] = time();
    }
    
    return true;
}

// Check session timeout only if user is logged in
if (isset($_SESSION['user_id']) && !checkSessionTimeout($_SESSION)) {
    header("location: ../../index.php?message=session_expired");
    exit;
}

if (isset($_SESSION['client_id']) && !checkSessionTimeout($_SESSION)) {
    header("location: ../../client_login.php?message=session_expired");
    exit;
}
?> 