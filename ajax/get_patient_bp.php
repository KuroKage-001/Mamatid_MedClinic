<?php
include '../config/db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['patient_name'])) {
    echo json_encode(['success' => false, 'message' => 'Patient name is required']);
    exit;
}

$patientName = $_GET['patient_name'];

try {
    $query = "SELECT 
        ROW_NUMBER() OVER (ORDER BY date DESC) as sno,
        id,
        name,
        DATE_FORMAT(date, '%Y-%m-%d') as date,
        address,
        sex,
        bp,
        alcohol,
        smoke,
        obese,
        cp_number
    FROM general_bp_monitoring 
    WHERE name = :patient_name 
    ORDER BY date DESC";

    $stmt = $con->prepare($query);
    $stmt->bindParam(':patient_name', $patientName);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format boolean values
    foreach ($data as &$row) {
        $row['alcohol'] = $row['alcohol'] ? 'Yes' : 'No';
        $row['smoke'] = $row['smoke'] ? 'Yes' : 'No';
        $row['obese'] = $row['obese'] ? 'Yes' : 'No';
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch(PDOException $ex) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $ex->getMessage()
    ]);
}
?> 