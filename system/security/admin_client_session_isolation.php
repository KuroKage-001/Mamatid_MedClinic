<?php
/**
 * Admin-Client Session Isolation Functions
 * Prevents admin session operations from affecting client sessions
 * Enhanced with better security and concurrent user support
 * Author: System Administrator
 * Version: 2.0
 */

// Prevent direct access
if (!defined('SESSION_ISOLATION_INCLUDED')) {
    define('SESSION_ISOLATION_INCLUDED', true);
}

/**
 * Get all admin/staff session variables
 */
function getAdminSessionVars() {
    return [
        'user_id',
        'user_name', 
        'display_name',
        'role',
        'status',
        'profile_picture',
        'profile_picture_timestamp',
        'last_activity',
        'session_token',
        'login_time',
        'user_agent',
        'ip_address'
    ];
}

/**
 * Get all client session variables
 */
function getClientSessionVars() {
    return [
        'client_id',
        'client_name',
        'client_email',
        'client_profile_picture',
        'client_last_activity',
        'client_session_token',
        'client_login_time',
        'client_ip_address',
        'client_user_agent'
    ];
}

/**
 * Store client session temporarily during admin operations
 */
function preserveClientSession() {
    $clientVars = getClientSessionVars();
    $preserved = [];
    
    foreach ($clientVars as $var) {
        if (isset($_SESSION[$var])) {
            $preserved[$var] = $_SESSION[$var];
        }
    }
    
    return $preserved;
}

/**
 * Restore client session after admin operations
 */
function restoreClientSession($preserved) {
    if (!is_array($preserved)) {
        return;
    }
    
    foreach ($preserved as $var => $value) {
        $_SESSION[$var] = $value;
    }
}

/**
 * Safely clear admin session without affecting client session
 */
function clearAdminSession() {
    $adminVars = getAdminSessionVars();
    
    foreach ($adminVars as $var) {
        if (isset($_SESSION[$var])) {
            unset($_SESSION[$var]);
        }
    }
}

/**
 * Safely clear client session without affecting admin session
 */
function clearClientSession() {
    $clientVars = getClientSessionVars();
    
    foreach ($clientVars as $var) {
        if (isset($_SESSION[$var])) {
            unset($_SESSION[$var]);
        }
    }
}

/**
 * Check if current session has both admin and client active
 */
function hasConcurrentSessions() {
    $hasAdmin = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    $hasClient = isset($_SESSION['client_id']) && !empty($_SESSION['client_id']);
    return $hasAdmin && $hasClient;
}

/**
 * Safely set a client session variable
 */
function setClientSessionVar($key, $value) {
    $_SESSION[$key] = $value;
}

/**
 * Safely get a client session variable
 */
function getClientSessionVar($key, $default = null) {
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
}

/**
 * Safely set an admin session variable
 */
function setAdminSessionVar($key, $value) {
    $_SESSION[$key] = $value;
}

/**
 * Safely get an admin session variable
 */
function getAdminSessionVar($key, $default = null) {
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
}

/**
 * Safely switch admin account without affecting client session
 */
function safeSwitchAdminAccount($newAdminData) {
    // Preserve client session if exists
    $preservedClientSession = null;
    if (isset($_SESSION['client_id']) && !empty($_SESSION['client_id'])) {
        $preservedClientSession = preserveClientSession();
        error_log("Preserving client session during admin switch: client_id=" . $_SESSION['client_id']);
    }
    
    // Clear only admin session
    clearAdminSession();
    
    // Set new admin session data
    foreach ($newAdminData as $key => $value) {
        $_SESSION[$key] = $value;
    }
    
    // Restore client session if it existed
    if ($preservedClientSession) {
        restoreClientSession($preservedClientSession);
        error_log("Restored client session after admin switch: client_id=" . ($_SESSION['client_id'] ?? 'none'));
    }
    
    // Regenerate session ID safely without destroying data
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(false); // Don't delete old session file immediately
    }
}

/**
 * Safely logout admin without affecting client session
 */
function safeAdminLogout() {
    // Preserve client session if exists
    $preservedClientSession = null;
    if (isset($_SESSION['client_id']) && !empty($_SESSION['client_id'])) {
        $preservedClientSession = preserveClientSession();
        error_log("Preserving client session during admin logout: client_id=" . $_SESSION['client_id']);
    }
    
    // Clear only admin session
    clearAdminSession();
    
    // Restore client session if it existed
    if ($preservedClientSession) {
        restoreClientSession($preservedClientSession);
        error_log("Restored client session after admin logout: client_id=" . ($_SESSION['client_id'] ?? 'none'));
    }
    
    // Only regenerate session ID if no client session exists
    if (!$preservedClientSession && session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Safely logout client without affecting admin session
 */
function safeClientLogout() {
    // Just clear client session variables
    clearClientSession();
    
    // Don't regenerate session ID if admin is still logged in
    $hasAdmin = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    if (!$hasAdmin && session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    
    error_log("Client logged out safely, admin session preserved: " . ($hasAdmin ? 'yes' : 'no'));
}

/**
 * Initialize session with proper security
 */
function initializeSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        // Set session name to avoid conflicts
        session_name('MAMATID_SESSION');
        
        // Start session with error handling
        if (!session_start()) {
            error_log("Failed to start session in initializeSecureSession()");
            return false;
        }
        
        // Set session security headers
        if (!isset($_SESSION['session_created'])) {
            $_SESSION['session_created'] = time();
            $_SESSION['session_token'] = bin2hex(random_bytes(32));
        }
    }
    return true;
}

/**
 * Validate session integrity
 */
function validateSessionIntegrity() {
    // Check for session hijacking via user agent
    if (isset($_SESSION['user_agent'])) {
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            error_log("Session hijacking detected: user agent mismatch");
            session_destroy();
            return false;
        }
    } else if (isset($_SESSION['user_id']) || isset($_SESSION['client_id'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
    
    // Check for session hijacking via IP address
    if (isset($_SESSION['ip_address'])) {
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            error_log("Session hijacking detected: IP address mismatch");
            session_destroy();
            return false;
        }
    } else if (isset($_SESSION['user_id']) || isset($_SESSION['client_id'])) {
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    
    return true;
}

/**
 * Check session timeout for clients
 */
function checkClientSessionTimeout() {
    $timeout = 3600; // 1 hour timeout for clients
    
    if (isset($_SESSION['client_id']) && !empty($_SESSION['client_id'])) {
        $lastActivity = getClientSessionVar('client_last_activity');
        if ($lastActivity && (time() - $lastActivity) > $timeout) {
            // Session timed out
            logSessionOperation('client_session_timeout', [
                'client_id' => $_SESSION['client_id'],
                'last_activity' => $lastActivity,
                'timeout_duration' => $timeout
            ]);
            
            // Clear client session
            safeClientLogout();
            return false;
        }
        
        // Update last activity
        setClientSessionVar('client_last_activity', time());
    }
    
    return true;
}

/**
 * Check session timeout for admins
 */
function checkAdminSessionTimeout() {
    $timeout = 7200; // 2 hours timeout for admins
    
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $lastActivity = getAdminSessionVar('last_activity');
        if ($lastActivity && (time() - $lastActivity) > $timeout) {
            // Session timed out
            logSessionOperation('admin_session_timeout', [
                'user_id' => $_SESSION['user_id'],
                'last_activity' => $lastActivity,
                'timeout_duration' => $timeout
            ]);
            
            // Clear admin session
            safeAdminLogout();
            return false;
        }
        
        // Update last activity
        setAdminSessionVar('last_activity', time());
    }
    
    return true;
}

/**
 * Debug session state
 */
function debugSessionState() {
    $adminVars = getAdminSessionVars();
    $clientVars = getClientSessionVars();
    
    $debug = [
        'session_id' => session_id(),
        'admin_logged_in' => isset($_SESSION['user_id']) && !empty($_SESSION['user_id']),
        'client_logged_in' => isset($_SESSION['client_id']) && !empty($_SESSION['client_id']),
        'admin_vars' => [],
        'client_vars' => [],
        'concurrent_sessions' => hasConcurrentSessions()
    ];
    
    foreach ($adminVars as $var) {
        if (isset($_SESSION[$var])) {
            $debug['admin_vars'][$var] = is_string($_SESSION[$var]) ? $_SESSION[$var] : gettype($_SESSION[$var]);
        }
    }
    
    foreach ($clientVars as $var) {
        if (isset($_SESSION[$var])) {
            $debug['client_vars'][$var] = is_string($_SESSION[$var]) ? $_SESSION[$var] : gettype($_SESSION[$var]);
        }
    }
    
    return $debug;
}

/**
 * Log session operation for debugging
 */
function logSessionOperation($operation, $details = []) {
    $logData = [
        'operation' => $operation,
        'timestamp' => date('Y-m-d H:i:s'),
        'session_id' => session_id(),
        'script' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details,
        'session_state' => debugSessionState()
    ];
    
    error_log("Session Operation: " . json_encode($logData));
}

/**
 * Generate secure session token
 */
function generateSecureToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Validate session token
 */
function validateSessionToken($token) {
    return isset($_SESSION['session_token']) && hash_equals($_SESSION['session_token'], $token);
}
?> 