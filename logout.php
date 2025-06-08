<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only clear admin-specific session variables
if (isset($_SESSION['user_id'])) {
    unset($_SESSION['user_id']);
    unset($_SESSION['display_name']);
    unset($_SESSION['user_name']);
    unset($_SESSION['profile_picture']);
}

// Do not destroy the entire session as it may contain client data
// session_destroy(); -- removed this line

// Redirect to login page
header("Location:index.php");
exit;
?>