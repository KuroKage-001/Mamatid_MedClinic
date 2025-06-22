<?php
/**
 * Redirect Handler
 * 
 * This utility file handles redirects with messages across the application.
 * It receives a destination page and message parameter, then redirects
 * the user to that page with the message attached as a query parameter.
 * 
 * @package    Mamatid Health Center System
 * @subpackage Utilities
 * @version    1.0
 */

// Include database connection
include '../../config/connection.php';

// Get redirect parameters
$gotoPage = $_GET['goto_page'];
$message = $_GET['message'];

// Perform the redirect
header("Location:../../$gotoPage?message=$message");
exit; // Ensure no further code execution after redirect
?> 