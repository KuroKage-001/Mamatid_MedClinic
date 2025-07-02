<?php
// Define helper as included
define('HISTORY_HELPER_INCLUDED', true);

include '../config/db_connection.php';
include 'history_helper.php';

// Apply cache prevention headers
prevent_cache();

// Get days parameter
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;

// Force fresh query execution by adding a cache buster
$cacheBuster = uniqid();

// Get standard history data
$response = get_standard_history_data($con, 'tetanus', 'general_tetanus_toxoid', $days);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 
