<?php
// Define helper as included
define('HISTORY_HELPER_INCLUDED', true);

include '../config/db_connection.php';
include 'history_helper.php';

// Apply cache prevention headers
prevent_cache();

// Get days parameter
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;

// Force fresh query execution
$cacheBuster = uniqid();

// Get the maximum date from the database
$maxDate = get_max_date($con, 'bp_monitoring');
$endDate = $maxDate;

// Calculate start date based on the end date
if ($days > 0) {
    $startDate = date('Y-m-d', strtotime("$endDate -$days days"));
} else {
    // Get minimum date if showing all
    try {
        $minQuery = "SELECT MIN(date) as min_date FROM bp_monitoring";
        $minStmt = $con->prepare($minQuery);
        $minStmt->execute();
        $minResult = $minStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($minResult && $minResult['min_date']) {
            $startDate = $minResult['min_date'];
        } else {
            $startDate = date('Y-m-d', strtotime("$endDate -30 days"));
        }
    } catch(PDOException $ex) {
        $startDate = date('Y-m-d', strtotime("$endDate -30 days"));
        error_log("Error getting min date: " . $ex->getMessage());
    }
}

error_log("BP History Query - Start Date: $startDate, End Date: $endDate");

// Get standard history data
$response = get_standard_history_data($con, 'general_bp_monitoring', 'general_bp_monitoring', $days);

// Log response for debugging
error_log("BP History Response: " . json_encode($response));

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 