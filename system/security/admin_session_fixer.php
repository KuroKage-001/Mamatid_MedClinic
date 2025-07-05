<?php
/**
 * Admin Session Fixer to prevent undefined variable errors
 * 
 * @package    Mamatid Health Center System
 * @subpackage Security
 * @version    1.0
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:../../index.php");
    exit;
}

// Set default session variables if not set
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'Unknown User';
}

if (!isset($_SESSION['display_name'])) {
    $_SESSION['display_name'] = $_SESSION['user_name'];
}

if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'user';
}

if (!isset($_SESSION['profile_picture'])) {
    $_SESSION['profile_picture'] = 'default_profile.jpg';
}
?> 