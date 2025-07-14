<?php
// Include admin-client session isolation functions
require_once '../system/security/admin_client_session_isolation.php';

// Initialize secure session
if (!initializeSecureSession()) {
    header("location: client_login.php");
    exit;
}

// Check if client is logged in using safe getter
$clientId = getClientSessionVar('client_id');
if (!$clientId) {
    header("location: client_login.php");
    exit;
}

// Log the logout for debugging
logSessionOperation('client_logout_request', [
    'client_id' => $clientId,
    'client_name' => getClientSessionVar('client_name') ?? 'unknown'
]);

// Use safe client logout to preserve any admin session
safeClientLogout();

// Redirect to client login
header("location: client_login.php?message=logged_out");
exit;
?> 