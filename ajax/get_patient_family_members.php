<?php
// Include database connection
include '../config/db_connection.php';

header('Content-Type: application/json');

// Enable error reporting for debugging but capture it instead of outputting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Check if patient name is provided
if (!isset($_GET['patient_name']) || empty($_GET['patient_name'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Patient name is required',
        'data' => []
    ]);
    exit;
}

try {
    $patientName = $_GET['patient_name'];
    
    // Log the search parameter
    error_log("Searching for patient: " . $patientName);
    
    // First try an exact match
    $query = "SELECT 
                id,
                name,
                DATE_FORMAT(date, '%m/%d/%Y') as formatted_date,
                date as raw_date,
                created_at
              FROM family_members 
              WHERE name = :name
              ORDER BY date DESC, created_at DESC";
    
    $stmt = $con->prepare($query);
    $stmt->execute(['name' => $patientName]);
    
    $data = [];
    $counter = 1;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = [
            'sno' => $counter++,
            'id' => $row['id'],
            'name' => $row['name'],
            'date' => $row['formatted_date'],
            'raw_date' => $row['raw_date'],
            'created_at' => $row['created_at']
        ];
    }
    
    // If no exact match, try a partial match
    if (count($data) == 0) {
        error_log("No exact match found, trying partial match");
        
        $query = "SELECT 
                    id,
                    name,
                    DATE_FORMAT(date, '%m/%d/%Y') as formatted_date,
                    date as raw_date,
                    created_at
                  FROM family_members 
                  WHERE name LIKE :name
                  ORDER BY date DESC, created_at DESC";
        
        $stmt = $con->prepare($query);
        $stmt->execute(['name' => '%' . $patientName . '%']);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                'sno' => $counter++,
                'id' => $row['id'],
                'name' => $row['name'],
                'date' => $row['formatted_date'],
                'raw_date' => $row['raw_date'],
                'created_at' => $row['created_at']
            ];
        }
    }
    
    error_log("Found " . count($data) . " records");
    
    // Debug: Print the SQL query and parameters
    error_log("SQL Query: " . $query);
    error_log("Parameters: " . json_encode(['patientName' => $patientName]));
    
    $response = [
        'success' => true,
        'message' => count($data) > 0 ? 'Family members records retrieved successfully' : 'No family members records found',
        'data' => $data
    ];
    
    // Debug: Print the response
    error_log("Response: " . json_encode($response));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error in get_patient_family_members.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving family members records: ' . $e->getMessage(),
        'data' => []
    ]);
}

// No need to close PDO connection - it closes automatically when the script ends 