<?php
include '../config/db_connection.php';
header('Content-Type: application/json');

// Set default response
$response = [
    'is_available' => false,
    'error' => null,
    'client_has_appointment' => false,
    'max_patients' => 1
];

// Check input parameters
if (!isset($_POST['schedule_id']) || !isset($_POST['appointment_time'])) {
    $response['error'] = "Missing required parameters";
    echo json_encode($response);
    exit;
}

$scheduleId = intval($_POST['schedule_id']);
$appointmentTime = $_POST['appointment_time'];
$clientId = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
$scheduleType = isset($_POST['schedule_type']) ? $_POST['schedule_type'] : 'doctor';

try {
    // Determine which table to check based on schedule type
    $schedulesTable = ($scheduleType === 'staff') ? 'staff_schedules' : 'doctor_schedules';
    $staffIdColumn = ($scheduleType === 'staff') ? 'staff_id' : 'doctor_id';
    
    // First, validate that the schedule exists and is approved
    $scheduleQuery = "SELECT s.*, u.display_name as provider_name
                    FROM {$schedulesTable} s
                    JOIN users u ON s.{$staffIdColumn} = u.id
                    WHERE s.id = ?";
    
    // Add approval check only for doctor schedules
    if ($scheduleType === 'doctor') {
        $scheduleQuery .= " AND s.is_approved = 1";
    }
    
    $scheduleStmt = $con->prepare($scheduleQuery);
    $scheduleStmt->execute([$scheduleId]);
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        $response['error'] = "Invalid or unapproved schedule";
        echo json_encode($response);
        exit;
    }
    
    $maxPatients = $schedule['max_patients'];
    $response['max_patients'] = $maxPatients;
    
    // Check if the client already has an appointment at this time
    if ($clientId > 0) {
        $clientQuery = "SELECT * FROM clients WHERE id = ?";
        $clientStmt = $con->prepare($clientQuery);
        $clientStmt->execute([$clientId]);
        $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            $checkClientQuery = "SELECT COUNT(*) as count FROM appointments 
                              WHERE appointment_date = ? 
                              AND appointment_time = ? 
                              AND patient_name = ? 
                              AND status != 'cancelled'";
            $checkClientStmt = $con->prepare($checkClientQuery);
            $checkClientStmt->execute([$schedule['schedule_date'], $appointmentTime, $client['full_name']]);
            $clientAppointmentCount = $checkClientStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($clientAppointmentCount > 0) {
                $response['client_has_appointment'] = true;
                echo json_encode($response);
                exit;
            }
        }
    }
    
    // Check if the slot is already booked
    $slotQuery = "SELECT COUNT(*) as count FROM appointments 
                WHERE schedule_id = ? AND appointment_time = ? AND status != 'cancelled'";
    $slotStmt = $con->prepare($slotQuery);
    $slotStmt->execute([$scheduleId, $appointmentTime]);
    $bookedCount = $slotStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // If no appointments or less than max_patients, the slot is available
    $response['is_available'] = ($bookedCount < $maxPatients);
    
    // Send response
    echo json_encode($response);
    
} catch(PDOException $ex) {
    $response['error'] = "Database error: " . $ex->getMessage();
    echo json_encode($response);
}
?> 