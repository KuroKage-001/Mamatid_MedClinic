<?php
/**
 * Session Configuration for Concurrent Users
 * This file ensures multiple users can login simultaneously without conflicts
 * 
 * @package    Mamatid Health Center System
 * @subpackage Security
 * @version    1.0
 */

// Only configure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    
    // Set secure session configuration for production
    ini_set('session.cookie_httponly', 1);           // Prevent XSS attacks
    ini_set('session.cookie_secure', 0);             // Set to 1 for HTTPS in production
    ini_set('session.use_only_cookies', 1);          // Only use cookies for sessions
    ini_set('session.entropy_length', 32);           // Better session ID generation
    ini_set('session.hash_function', 'sha256');      // Stronger hash function
    ini_set('session.cookie_lifetime', 0);           // Session expires when browser closes
    ini_set('session.gc_maxlifetime', 1800);         // 30 minutes idle timeout
    ini_set('session.gc_probability', 1);            // Garbage collection probability
    ini_set('session.gc_divisor', 100);              // Garbage collection divisor
    
    // For production with many concurrent users, consider using database sessions
    // ini_set('session.save_handler', 'user');
    
    // Start session with secure settings
    session_start();
    
    // Regenerate session ID periodically for security (every 10 minutes)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 600) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Check session timeout for inactive sessions
 */
function checkSessionTimeout() {
    $timeout = 1800; // 30 minutes
    
    if (isset($_SESSION['user_id']) || isset($_SESSION['client_id'])) {
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $timeout) {
                // Session has expired
                session_unset();
                session_destroy();
                return false;
            }
        }
        $_SESSION['last_activity'] = time();
    }
    return true;
}

/**
 * Check if session has expired and redirect appropriately
 */
function handleSessionExpiry() {
    if (!checkSessionTimeout()) {
        if (isset($_SESSION['client_id'])) {
            header("location: ../../client_login.php?message=session_expired");
        } else {
            header("location: ../../index.php?message=session_expired");
        }
        exit;
    }
}

/**
 * Safely destroy user session without affecting other concurrent sessions
 */
function safeLogout($userType = 'admin') {
    // Store session info for logging
    $session_id = session_id();
    
    if ($userType === 'client') {
        $user_id = $_SESSION['client_id'] ?? 'unknown';
        $user_name = $_SESSION['client_name'] ?? 'unknown';
        $redirect_url = '../../client_login.php?message=logged_out';
    } else {
        $user_id = $_SESSION['user_id'] ?? 'unknown';
        $user_name = $_SESSION['user_name'] ?? 'unknown';
        $redirect_url = '../../index.php?message=logged_out';
    }
    
    // Log the logout action
    error_log("$userType logout: ID=$user_id, Name=$user_name, Session=$session_id, Time=" . date('Y-m-d H:i:s'));
    
    // Clear all session data
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Start fresh session for security
    session_start();
    session_regenerate_id(true);
    
    // Redirect
    header("location: $redirect_url");
    exit;
}

// Check session timeout on every request
handleSessionExpiry();
?> 