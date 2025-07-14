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
    // Only fix session variables if user is actually logged in
    // Don't redirect here - let the page handle authentication
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
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
    
    return true;
}

// Fix admin session variables if admin is logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    fixAdminSessionVariables();
}

/**
 * Check session timeout for inactive sessions
 * Enhanced with session isolation logging
 */
function checkSessionTimeout() {
    $timeout = 7200; // 2 hours - increased from 1 hour to be less aggressive
    
    // Check admin/staff session timeout
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        // Initialize last_activity if not set
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        if (time() - $_SESSION['last_activity'] > $timeout) {
            // Log admin session timeout
            logSessionOperation('admin_session_timeout', [
                'user_id' => $_SESSION['user_id'],
                'last_activity' => $_SESSION['last_activity'],
                'timeout_duration' => $timeout,
                'has_client_session' => isset($_SESSION['client_id']) && !empty($_SESSION['client_id'])
            ]);
            
            return false; // Don't auto-logout here, let the calling function handle it
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
    
    // Check client session timeout
    if (isset($_SESSION['client_id']) && !empty($_SESSION['client_id'])) {
        // Initialize client_last_activity if not set
        if (!isset($_SESSION['client_last_activity'])) {
            $_SESSION['client_last_activity'] = time();
            return true;
        }
        
        if (time() - $_SESSION['client_last_activity'] > $timeout) {
            // Log client session timeout
            logSessionOperation('client_session_timeout', [
                'client_id' => $_SESSION['client_id'],
                'last_activity' => $_SESSION['client_last_activity'],
                'timeout_duration' => $timeout,
                'has_admin_session' => isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])
            ]);
            
            return false; // Don't auto-logout here, let the calling function handle it
        }
        
        // Update client last activity
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
    
    // Skip checking for login pages, assets, and AJAX calls
    $skip_pages = ['index.php', 'client_login.php', 'client_register.php', 'logout.php', 'client_logout.php'];
    if (in_array($current_script, $skip_pages)) {
        return;
    }
    
    // Skip AJAX requests and API calls
    if (strpos($current_script, 'ajax/') === 0 || strpos($current_script, 'actions/') === 0 || 
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        return;
    }
    
    // Only check session expiry if we have an active session and the user should be logged in
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        // Check admin session expiry but be more lenient
        if (!checkSessionTimeout()) {
            // Clear session variables but don't redirect aggressively
            clearAdminSession();
            $_SESSION['alert_message'] = "Your session has expired. Please login again.";
            $_SESSION['alert_type'] = "warning";
            header("location: " . getBasePath() . "/index.php");
            exit;
        }
    }
    
    // Only check client session expiry if client is actually supposed to be logged in
    if (isset($_SESSION['client_id']) && !empty($_SESSION['client_id'])) {
        if (!checkSessionTimeout()) {
            clearClientSession();
            $_SESSION['alert_message'] = "Your session has expired. Please login again.";
            $_SESSION['alert_type'] = "warning";
            header("location: " . getBasePath() . "/client_login.php");
            exit;
        }
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

/**
 * Get base path for redirects
 */
function getBasePath() {
    $script_path = $_SERVER['SCRIPT_NAME'];
    $path_parts = explode('/', $script_path);
    
    // Count how many directories deep we are
    $depth = count($path_parts) - 2; // -2 for script name and empty first element
    
    // If we're in a subdirectory, go up
    if ($depth > 1) {
        return str_repeat('../', $depth - 1);
    }
    
    return '.';
}

// Check session timeout on every request
handleSessionExpiry();
?> 