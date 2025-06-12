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

// Unset all session variables for this client
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

// Redirect to client login page with logout message
header("location: client_login.php?message=logged_out");
exit; 