<?php
// Include admin-client session isolation functions
require_once './system/security/admin_client_session_isolation.php';

// Initialize secure session
if (!initializeSecureSession()) {
    die('Failed to initialize session');
}

// Check if client is logged in using session isolation functions
$client_id = function_exists('getClientSessionVar') ? getClientSessionVar('client_id') : ($_SESSION['client_id'] ?? null);
if (!$client_id) {
    header("location: client_login.php");
    exit;
}

// Store session ID for logging (optional)
$session_id = session_id();
$client_name = function_exists('getClientSessionVar') ? getClientSessionVar('client_name') : ($_SESSION['client_name'] ?? 'unknown');

// Log the logout action with session state
logSessionOperation('client_logout', [
    'client_id' => $client_id,
    'client_name' => $client_name,
    'has_admin_session' => isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])
]);

// Use safe client logout to preserve any admin/staff session that might be active
safeClientLogout();

// Redirect to client login page with logout message
header("location: client_login.php?message=logged_out");
exit; 