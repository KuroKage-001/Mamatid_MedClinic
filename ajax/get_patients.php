<?php
include '../config/connection.php';

header('Content-Type: application/json');

try {
    $query = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name, 
                     DATE_FORMAT(birthdate, '%d %b %Y') as birthdate
              FROM patients 
              ORDER BY first_name, last_name ASC";
    $stmt = $con->prepare($query);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $options = '<option value="">Select Patient</option>';
    foreach ($patients as $patient) {
        $options .= sprintf(
            '<option value="%d">%s (DOB: %s)</option>',
            $patient['id'],
            htmlspecialchars($patient['full_name']),
            $patient['birthdate']
        );
    }
    
    echo $options;
} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching patients: ' . $ex->getMessage()]);
}