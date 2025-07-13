<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: admin_login.php");
    exit;
}

// Store session ID for logging (optional)
$session_id = session_id();
$user_id = $_SESSION['user_id'] ?? 'unknown';
$user_name = $_SESSION['user_name'] ?? 'unknown';

// Log the logout action (optional - for audit trail)
error_log("User logout: ID=$user_id, Username=$user_name, Session=$session_id, Time=" . date('Y-m-d H:i:s'));

// Only unset admin/staff session variables instead of clearing the entire session
// This preserves any client session that might be active
$admin_session_vars = ['user_id', 'display_name', 'user_name', 'profile_picture', 'role'];
foreach ($admin_session_vars as $var) {
    if (isset($_SESSION[$var])) {
        unset($_SESSION[$var]);
    }
}

// No need to destroy the entire session or delete cookies
// Just regenerate the session ID for security
session_regenerate_id(true);

// Redirect to login page with logout message
header("location: admin_login.php?message=logged_out");
exit;
?>