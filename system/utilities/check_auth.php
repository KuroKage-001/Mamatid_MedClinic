<?php
/**
 * Authentication check file
 * Include this at the top of any page that requires authentication
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
    // Redirect to login page (maintaining same behavior as manual checks)
    header("location:index.php");
    exit;
}
?> 