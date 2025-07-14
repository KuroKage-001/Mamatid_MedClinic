<?php
// Start session
session_start();

// Include admin-client session isolation functions
require_once '../system/security/admin_client_session_isolation.php';

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header("location: client_login.php");
    exit;
}

// Log the logout for debugging
logSessionOperation('client_logout_request', [
    'client_id' => $_SESSION['client_id'],
    'client_name' => $_SESSION['client_name'] ?? 'unknown'
]);

// Use safe client logout to preserve any admin session
safeClientLogout();

// Redirect to client login
header("location: client_login.php?message=logged_out");
exit;
?> 