<?php
/**
 * Client Appointment Time Slot Availability Checker
 * 
 * This script checks if a specific appointment time slot is available for booking.
 * It validates schedule existence, client appointment conflicts, and slot availability.
 */

include '../config/db_connection.php';
header('Content-Type: application/json');

// Set default response
$response = [
    'is_available' => false,
    'error' => null,
    'client_has_appointment' => false,
    'max_patients' => 1
];

// Validate CSRF token if needed
// if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
//     $response['error'] = "Invalid request";
//     echo json_encode($response);
//     exit;
// }

// Check input parameters
if (!isset($_POST['schedule_id']) || !isset($_POST['appointment_time'])) {
    $response['error'] = "Missing required parameters";
    echo json_encode($response);
    exit;
}

// Sanitize and validate inputs
$scheduleId = filter_var($_POST['schedule_id'], FILTER_VALIDATE_INT);
if ($scheduleId === false) {
    $response['error'] = "Invalid schedule ID";
    echo json_encode($response);
    exit;
}

$appointmentTime = trim($_POST['appointment_time']);
if (empty($appointmentTime)) {
    $response['error'] = "Invalid appointment time";
    echo json_encode($response);
    exit;
}

$clientId = isset($_POST['client_id']) ? filter_var($_POST['client_id'], FILTER_VALIDATE_INT) : 0;
$scheduleType = isset($_POST['schedule_type']) ? 
    (in_array($_POST['schedule_type'], ['doctor', 'staff']) ? $_POST['schedule_type'] : 'doctor') : 
    'doctor';

try {
    // Determine which table to check based on schedule type
    $schedulesTable = ($scheduleType === 'staff') ? 'admin_hw_schedules' : 'admin_doctor_schedules';
    $staffIdColumn = ($scheduleType === 'staff') ? 'staff_id' : 'doctor_id';
    
    // First, validate that the schedule exists and is approved
    $scheduleQuery = "SELECT s.*, u.display_name as provider_name
                    FROM {$schedulesTable} s
                    JOIN admin_user_accounts u ON s.{$staffIdColumn} = u.id
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
        $clientQuery = "SELECT * FROM clients_user_accounts WHERE id = ?";
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
    // Log the error for administrators
    error_log("Database error in client_check_timeslot_availability.php: " . $ex->getMessage());
    
    // Return a generic error message to the client
    $response['error'] = "A database error occurred. Please try again later.";
    echo json_encode($response);
}
?> 