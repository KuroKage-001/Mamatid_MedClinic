<?php
/**
 * Secure Client Authentication Check
 * Uses session isolation to prevent conflicts with admin sessions
 */

// Include admin-client session isolation functions if not already included
if (!defined('SESSION_ISOLATION_INCLUDED')) {
    require_once __DIR__ . '/../security/admin_client_session_isolation.php';
}

// Initialize secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    if (!initializeSecureSession()) {
        // Session initialization failed
        logSessionOperation('client_auth_session_init_failed', [
            'script' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        header("Location: " . getClientBasePath() . "/client_login.php?error=session_init_failed");
        exit;
    }
}

// Validate session integrity
if (!validateSessionIntegrity()) {
    // Session compromised, redirect to login
    logSessionOperation('client_auth_integrity_failed', [
        'script' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'session_id' => session_id(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    header("Location: " . getClientBasePath() . "/client_login.php?error=session_invalid");
    exit;
}

// Check if client is logged in using safe session getter
$clientId = getClientSessionVar('client_id');
if (!$clientId || empty($clientId)) {
    // Log unauthorized access attempt
    logSessionOperation('client_auth_failed', [
        'script' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'has_admin_session' => isset($_SESSION['user_id']) && !empty($_SESSION['user_id']),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Redirect to client login
    header("Location: " . getClientBasePath() . "/client_login.php");
    exit;
}

// Update client activity timestamp using safe session setter
setClientSessionVar('client_last_activity', time());

// Log successful client authentication
logSessionOperation('client_auth_success', [
    'client_id' => $clientId,
    'script' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
    'has_concurrent_admin' => isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])
]);

/**
 * Get base path for client redirects
 */
function getClientBasePath() {
    // Simple approach: check if client_login.php exists in current directory
    if (file_exists('client_login.php')) {
        return '.';
    }
    
    // If not, check parent directories
    if (file_exists('../client_login.php')) {
        return '..';
    }
    
    if (file_exists('../../client_login.php')) {
        return '../..';
    }
    
    if (file_exists('../../../client_login.php')) {
        return '../../..';
    }
    
    // Default fallback
    return '.';
}

/**
 * Check if client session is still valid
 */
function isClientSessionValid() {
    // Check if client session exists using safe getter
    $clientId = getClientSessionVar('client_id');
    if (!$clientId || empty($clientId)) {
        return false;
    }
    
    // Check session timeout (optional - implement if needed)
    $lastActivity = getClientSessionVar('client_last_activity');
    if ($lastActivity) {
        $timeout = 3600; // 1 hour timeout for clients
        if ((time() - $lastActivity) > $timeout) {
            // Session timed out
            logSessionOperation('client_session_timeout', [
                'client_id' => $clientId,
                'last_activity' => $lastActivity,
                'timeout_duration' => $timeout
            ]);
            
            // Clear client session using safe logout
            safeClientLogout();
            return false;
        }
    }
    
    return true;
}

/**
 * Refresh client session activity
 */
function refreshClientActivity() {
    $clientId = getClientSessionVar('client_id');
    if ($clientId) {
        setClientSessionVar('client_last_activity', time());
    }
}

// Perform additional session validation
if (!isClientSessionValid()) {
    header("Location: " . getClientBasePath() . "/client_login.php?error=session_expired");
    exit;
}

// Refresh activity timestamp
refreshClientActivity();
?> 