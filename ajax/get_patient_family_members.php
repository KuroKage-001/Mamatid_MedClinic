<?php
// Include database connection
include '../config/connection.php';

header('Content-Type: application/json');

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
    
    // Log the incoming request for debugging
    file_put_contents('../logs/family_members_debug.log', date('Y-m-d H:i:s') . " - Searching for: " . $patientName . PHP_EOL, FILE_APPEND);
    
    // First try an exact match
    $query = "SELECT 
                id,
                name,
                DATE_FORMAT(date, '%m/%d/%Y') as formatted_date,
                date,
                created_at
              FROM family_members 
              WHERE name = ?
              ORDER BY date DESC, created_at DESC";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param('s', $patientName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $counter = 1;
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'sno' => $counter++,
            'id' => $row['id'],
            'name' => $row['name'],
            'date' => $row['formatted_date'],
            'raw_date' => $row['date'],
            'created_at' => $row['created_at']
        ];
    }
    
    // If no exact match, try a partial match
    if (count($data) == 0) {
        // Clean up the name for better matching
        $searchName = preg_replace('/\s+/', '%', trim($patientName));
        
        $query = "SELECT 
                    id,
                    name,
                    DATE_FORMAT(date, '%m/%d/%Y') as formatted_date,
                    date,
                    created_at
                  FROM family_members 
                  WHERE LOWER(name) LIKE LOWER(?)
                  ORDER BY date DESC, created_at DESC";
        
        $stmt = $con->prepare($query);
        $likeParam = "%" . $searchName . "%";
        $stmt->bind_param('s', $likeParam);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'sno' => $counter++,
                'id' => $row['id'],
                'name' => $row['name'],
                'date' => $row['formatted_date'],
                'raw_date' => $row['date'],
                'created_at' => $row['created_at']
            ];
        }
    }
    
    // Log the results count
    file_put_contents('../logs/family_members_debug.log', date('Y-m-d H:i:s') . " - Found " . count($data) . " results\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => count($data) > 0 ? 'Family members history retrieved successfully' : 'No family members history found',
        'data' => $data
    ]);
    
} catch (Exception $e) {
    // Log any errors
    file_put_contents('../logs/family_members_debug.log', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving family members history: ' . $e->getMessage(),
        'data' => []
    ]);
}

$con->close(); 