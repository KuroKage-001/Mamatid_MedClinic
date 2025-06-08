<?php
// Define helper as included
define('HISTORY_HELPER_INCLUDED', true);

include '../config/connection.php';
include 'history_helper.php';

// Apply cache prevention headers
prevent_cache();

// Get days parameter
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;

// Force fresh query execution by adding a cache buster
$cacheBuster = uniqid();

// Get standard history data
$response = get_standard_history_data($con, 'blood_sugar', 'random_blood_sugar', $days);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 