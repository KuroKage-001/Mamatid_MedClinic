<?php
// Include database connection and common functions
require_once '../config/connection.php';
require_once '../common_service/common_functions.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'data' => array()
);

// Check if patient_name is provided
if (!isset($_GET['patient_name']) || empty($_GET['patient_name'])) {
    $response['message'] = 'Patient name is required';
    echo json_encode($response);
    exit;
}

try {
    // Get patient history data
    $patientName = $_GET['patient_name'];
    $historyData = getPatientHistory($con, $patientName);
    
    // Set success response
    $response['success'] = true;
    $response['data'] = $historyData;
    
} catch (Exception $e) {
    $response['message'] = 'An error occurred while retrieving patient history: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
