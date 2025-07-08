<?php
/**
 * Admin Session Configuration for Concurrent Users
 * This file ensures multiple users can login simultaneously without conflicts
 * Now includes enhanced session isolation to prevent admin/client conflicts
 * Also includes session variable fixing to prevent undefined variable errors
 * 
 * @package    Mamatid Health Center System
 * @subpackage Security
 * @version    2.1
 */

// Include admin-client session isolation functions
require_once __DIR__ . '/admin_client_session_isolation.php';

// Only configure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    
    // Set secure session configuration for production
    ini_set('session.cookie_httponly', 1);           // Prevent XSS attacks
    ini_set('session.cookie_secure', 0);             // Set to 1 for HTTPS in production
    ini_set('session.use_only_cookies', 1);          // Only use cookies for sessions
    ini_set('session.entropy_length', 32);           // Better session ID generation
    ini_set('session.hash_function', 'sha256');      // Stronger hash function
    ini_set('session.cookie_lifetime', 0);           // Session expires when browser closes
    ini_set('session.gc_maxlifetime', 3600);         // 1 hour idle timeout
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
 * Fix and set default session variables to prevent undefined variable errors
 * This replaces the functionality from admin_session_fixer.php
 */
function fixAdminSessionVariables() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("location:../../index.php");
        exit;
    }

    // Set default session variables if not set
    if (!isset($_SESSION['user_name'])) {
        $_SESSION['user_name'] = 'Unknown User';
    }

    if (!isset($_SESSION['display_name'])) {
        $_SESSION['display_name'] = $_SESSION['user_name'];
    }

    if (!isset($_SESSION['role'])) {
        $_SESSION['role'] = 'user';
    }

    if (!isset($_SESSION['profile_picture'])) {
        $_SESSION['profile_picture'] = 'default_profile.jpg';
    }
}

// Fix admin session variables if admin is logged in
if (isset($_SESSION['user_id'])) {
    fixAdminSessionVariables();
}

/**
 * Check session timeout for inactive sessions
 * Enhanced with session isolation logging
 */
function checkSessionTimeout() {
    $timeout = 3600; // 1 hour
    
    // Check admin/staff session timeout
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $timeout) {
                // Log admin session timeout
                logSessionOperation('admin_session_timeout', [
                    'user_id' => $_SESSION['user_id'],
                    'last_activity' => $_SESSION['last_activity'],
                    'timeout_duration' => $timeout,
                    'has_client_session' => isset($_SESSION['client_id']) && !empty($_SESSION['client_id'])
                ]);
                
                // Admin session has expired - use safe admin logout
                safeAdminLogout();
                return false;
            }
        }
        $_SESSION['last_activity'] = time();
    }
    
    // Check client session timeout
    if (isset($_SESSION['client_id'])) {
        if (isset($_SESSION['client_last_activity'])) {
            if (time() - $_SESSION['client_last_activity'] > $timeout) {
                // Log client session timeout
                logSessionOperation('client_session_timeout', [
                    'client_id' => $_SESSION['client_id'],
                    'last_activity' => $_SESSION['client_last_activity'],
                    'timeout_duration' => $timeout,
                    'has_admin_session' => isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])
                ]);
                
                // Client session has expired - use safe client logout
                safeClientLogout();
                return false;
            }
        }
        $_SESSION['client_last_activity'] = time();
    }
    
    return true;
}

/**
 * Check if session has expired and redirect appropriately
 */
function handleSessionExpiry() {
    // Get the current script name
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    
    // Skip checking for login pages and assets
    $skip_pages = ['index.php', 'client_login.php', 'client_register.php'];
    if (in_array($current_script, $skip_pages)) {
        return;
    }
    
    // Check admin session expiry
    if (isset($_SESSION['user_id']) && !checkSessionTimeout()) {
        $_SESSION['alert_message'] = "Your session has expired. Please login again.";
        $_SESSION['alert_type'] = "warning";
        header("location: ../../index.php");
        exit;
    }
    
    // Check client session expiry
    if (isset($_SESSION['client_id']) && !checkSessionTimeout()) {
        $_SESSION['alert_message'] = "Your session has expired. Please login again.";
        $_SESSION['alert_type'] = "warning";
        header("location: ../../client_login.php");
        exit;
    }
}

/**
 * Safely destroy user session without affecting other concurrent sessions
 * Now uses enhanced session isolation functions
 */
function safeLogout($userType = 'admin') {
    // Store session info for logging
    $session_id = session_id();
    
    if ($userType === 'client') {
        $user_id = $_SESSION['client_id'] ?? 'unknown';
        $user_name = $_SESSION['client_name'] ?? 'unknown';
        $redirect_url = '../../client_login.php?message=logged_out';
        
        // Log the logout attempt
        logSessionOperation('legacy_client_logout', [
            'user_id' => $user_id,
            'user_name' => $user_name,
            'has_admin_session' => isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])
        ]);
        
        // Use enhanced safe client logout
        safeClientLogout();
        
    } else {
        $user_id = $_SESSION['user_id'] ?? 'unknown';
        $user_name = $_SESSION['user_name'] ?? 'unknown';
        $redirect_url = '../../index.php?message=logged_out';
        
        // Log the logout attempt
        logSessionOperation('legacy_admin_logout', [
            'user_id' => $user_id,
            'user_name' => $user_name,
            'has_client_session' => isset($_SESSION['client_id']) && !empty($_SESSION['client_id'])
        ]);
        
        // Use enhanced safe admin logout
        safeAdminLogout();
    }
    
    // Redirect
    header("location: $redirect_url");
    exit;
}

// Check session timeout on every request
handleSessionExpiry();
?> 