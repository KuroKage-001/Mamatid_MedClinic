<?php
// Start session
session_start();

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header("location: client_login.php");
    exit;
}

// Store session ID for logging (optional)
$session_id = session_id();
$client_id = $_SESSION['client_id'] ?? 'unknown';
$client_name = $_SESSION['client_name'] ?? 'unknown';

// Log the logout action (optional - for audit trail)
error_log("Client logout: ID=$client_id, Name=$client_name, Session=$session_id, Time=" . date('Y-m-d H:i:s'));

// Only unset client session variables instead of clearing the entire session
// This preserves any admin/staff session that might be active
$client_session_vars = ['client_id', 'client_name', 'client_email'];
foreach ($client_session_vars as $var) {
    if (isset($_SESSION[$var])) {
        unset($_SESSION[$var]);
    }
}

// No need to destroy the entire session or delete cookies
// Just regenerate the session ID for security
session_regenerate_id(true);

// Redirect to client login page with logout message
header("location: client_login.php?message=logged_out");
exit; 