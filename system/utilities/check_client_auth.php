<?php
/**
 * Secure Client Authentication Check - Enhanced Version
 * Uses session isolation to prevent conflicts with admin sessions
 * Version 2.0 - Fixed security issues
 */

// Start session immediately if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include admin-client session isolation functions if not already included
if (!defined('SESSION_ISOLATION_INCLUDED')) {
    require_once __DIR__ . '/../security/admin_client_session_isolation.php';
}

// Get base path for client redirects - improved version
function getClientBasePath() {
    // Check current directory first
    if (file_exists('./client_login.php')) {
        return './client_login.php';
    }
    
    // Check parent directory
    if (file_exists('../client_login.php')) {
        return '../client_login.php';
    }
    
    // Check two levels up
    if (file_exists('../../client_login.php')) {
        return '../../client_login.php';
    }
    
    // Default fallback
    return './client_login.php';
}

// Enhanced session initialization - compatible with session isolation system
function initializeClientSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Use the same session initialization as the session isolation system
        if (function_exists('initializeSecureSession')) {
            return initializeSecureSession();
        } else {
            // Fallback initialization
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_lifetime', 0);
            
            // Use the same session name as the isolation system
            session_name('MAMATID_SESSION');
            
            // Start session
            if (!session_start()) {
                error_log("Failed to start client session");
                return false;
            }
        }
    }
    return true;
}

// Initialize session
if (!initializeClientSession()) {
    $login_url = getClientBasePath() . "?error=session_init_failed";
    header("Location: $login_url");
    exit;
}

// Log the authentication attempt
$script_name = $_SERVER['SCRIPT_NAME'] ?? 'unknown';
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

error_log("Client authentication check for script: $script_name from IP: $client_ip");

// Check if client is logged in - use session isolation functions if available
$client_id = null;

if (function_exists('getClientSessionVar')) {
    $client_id = getClientSessionVar('client_id');
} else {
    // Direct session access fallback
    $client_id = $_SESSION['client_id'] ?? null;
}

// Validate client ID
if (!$client_id || empty($client_id) || !is_numeric($client_id)) {
    // Log unauthorized access attempt
    error_log("Unauthorized client access attempt to $script_name from $client_ip");
    error_log("Session data: " . print_r($_SESSION, true));
    
    // Clear any potentially corrupted session data using safe functions
    if (function_exists('clearClientSession')) {
        clearClientSession();
    } else {
        // Fallback cleanup
        if (isset($_SESSION['client_id'])) {
            unset($_SESSION['client_id']);
        }
        if (isset($_SESSION['client_name'])) {
            unset($_SESSION['client_name']);
        }
        if (isset($_SESSION['client_email'])) {
            unset($_SESSION['client_email']);
        }
    }
    
    // Redirect to login page
    $login_url = getClientBasePath();
    header("Location: $login_url");
    exit;
}

// Update client activity timestamp
if (function_exists('setClientSessionVar')) {
    setClientSessionVar('client_last_activity', time());
} else {
    $_SESSION['client_last_activity'] = time();
}

// Validate session integrity
$stored_user_agent = null;
if (function_exists('getClientSessionVar')) {
    $stored_user_agent = getClientSessionVar('client_user_agent');
} else {
    $stored_user_agent = $_SESSION['client_user_agent'] ?? null;
}

if ($stored_user_agent) {
    if ($stored_user_agent !== $user_agent) {
        error_log("Client session hijacking detected for client_id: $client_id");
        
        // Clear session and redirect to login
        if (function_exists('safeClientLogout')) {
            safeClientLogout();
        } else {
            session_destroy();
        }
        $login_url = getClientBasePath() . "?error=session_invalid";
        header("Location: $login_url");
        exit;
    }
} else {
    // Set user agent for future validation
    if (function_exists('setClientSessionVar')) {
        setClientSessionVar('client_user_agent', $user_agent);
    } else {
        $_SESSION['client_user_agent'] = $user_agent;
    }
}

// Check session timeout (1 hour)
$last_activity = null;
if (function_exists('getClientSessionVar')) {
    $last_activity = getClientSessionVar('client_last_activity');
} else {
    $last_activity = $_SESSION['client_last_activity'] ?? null;
}

if (!$last_activity) {
    $last_activity = time(); // Set current time if not set
}

$timeout = 3600; // 1 hour

if ((time() - $last_activity) > $timeout) {
    error_log("Client session timeout for client_id: $client_id");
    
    // Clear session and redirect to login
    if (function_exists('safeClientLogout')) {
        safeClientLogout();
    } else {
        session_destroy();
    }
    $login_url = getClientBasePath() . "?error=session_expired";
    header("Location: $login_url");
    exit;
}

// Log successful authentication
error_log("Client authentication successful for client_id: $client_id accessing $script_name");

// Set a flag to indicate successful authentication and update activity
if (function_exists('setClientSessionVar')) {
    setClientSessionVar('client_authenticated', true);
    setClientSessionVar('client_last_activity', time());
} else {
    $_SESSION['client_authenticated'] = true;
    $_SESSION['client_last_activity'] = time();
}

?> 