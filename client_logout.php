<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only clear client-specific session variables
if (isset($_SESSION['client_id'])) {
    unset($_SESSION['client_id']);
    unset($_SESSION['client_name']);
    unset($_SESSION['client_email']);
}

// Do not destroy the entire session as it may contain admin data
// session_destroy(); -- removed this line

// Redirect to login page
header("location:client_login.php");
exit; 