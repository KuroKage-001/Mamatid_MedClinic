<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}

// Store session ID for logging (optional)
$session_id = session_id();
$user_id = $_SESSION['user_id'] ?? 'unknown';
$user_name = $_SESSION['user_name'] ?? 'unknown';

// Log the logout action (optional - for audit trail)
error_log("User logout: ID=$user_id, Username=$user_name, Session=$session_id, Time=" . date('Y-m-d H:i:s'));

// Unset all session variables for this user
$_SESSION = array();

// Delete the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Regenerate session ID for security (start fresh session)
session_start();
session_regenerate_id(true);

// Redirect to login page with logout message
header("location: index.php?message=logged_out");
exit;
?>