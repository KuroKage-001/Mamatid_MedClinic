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
        name,
        DATE_FORMAT(date, '%Y-%m-%d') as date,
        age,
        result
    FROM random_blood_sugar 
    WHERE name = :patient_name 
    ORDER BY date DESC";

    $stmt = $con->prepare($query);
    $stmt->bindParam(':patient_name', $patientName);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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