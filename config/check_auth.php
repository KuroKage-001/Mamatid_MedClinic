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
    // Get the current page URL
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Redirect to unauthorized access page
    header("location: system/security/unauthorized_access.php?page=" . urlencode($current_page));
    exit;
}
?> 