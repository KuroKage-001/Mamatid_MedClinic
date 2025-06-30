<?php
/**
 * Client Authentication check file
 * Include this at the top of any page that requires client authentication
 * 
 * @package    Mamatid Health Center System
 * @subpackage Security
 * @version    1.0
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    // Get the current page URL
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Redirect to client login page
    header("location: client_login.php?redirect=" . urlencode($current_page));
    exit;
}
?> 