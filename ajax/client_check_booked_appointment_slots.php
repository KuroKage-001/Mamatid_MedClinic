<?php
/**
 * Client Appointment Booked Slots Checker
 * 
 * This script retrieves information about booked appointment slots for a specific schedule.
 * It provides data about which slots are available, booked, or in the past,
 * and checks if the client already has appointments at specific times.
 */

include '../config/db_connection.php';
header('Content-Type: application/json');

// Set default response
$response = [
    'booked_slots' => [],
    'slot_statuses' => [],
    'max_patients' => 1,
    'client_appointments' => [],
    'error' => null
];

// Validate input parameters
if (!isset($_POST['schedule_id'])) {
    $response['error'] = "Schedule ID is required";
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

$clientId = isset($_POST['client_id']) ? filter_var($_POST['client_id'], FILTER_VALIDATE_INT) : 0;
$scheduleType = isset($_POST['schedule_type']) ? 
    (in_array($_POST['schedule_type'], ['doctor', 'staff']) ? $_POST['schedule_type'] : 'doctor') : 
    'doctor';

try {
    // Determine which table to check based on schedule type
    $schedulesTable = ($scheduleType === 'staff') ? 'staff_schedules' : 'doctor_schedules';
    $providerIdColumn = ($scheduleType === 'staff') ? 'staff_id' : 'doctor_id';
    
    // Get schedule details to check max patients and date
    $scheduleQuery = "SELECT * FROM {$schedulesTable} WHERE id = ?";
    $scheduleStmt = $con->prepare($scheduleQuery);
    $scheduleStmt->execute([$scheduleId]);
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        $response['error'] = "Invalid schedule ID";
        echo json_encode($response);
        exit;
    }
    
    $maxPatients = $schedule['max_patients'];
    $response['max_patients'] = $maxPatients;
    
    // Get all appointments for this schedule
    $appointmentsQuery = "SELECT appointment_time, COUNT(*) as count 
                         FROM appointments 
                         WHERE schedule_id = ? AND status != 'cancelled' 
                         GROUP BY appointment_time";
    $appointmentsStmt = $con->prepare($appointmentsQuery);
    $appointmentsStmt->execute([$scheduleId]);
    
    $bookedSlots = [];
    
    // Process each time slot
    while ($row = $appointmentsStmt->fetch(PDO::FETCH_ASSOC)) {
        $timeSlot = $row['appointment_time'];
        $count = intval($row['count']);
        
        // Check if past (for UI display)
        $dateTime = $schedule['schedule_date'] . ' ' . $timeSlot;
        $isPast = strtotime($dateTime) < time();
        
        $bookedSlots[$timeSlot] = [
            'count' => $count,
            'is_full' => ($count >= $maxPatients),
            'is_past' => $isPast
        ];
    }
    
    $response['booked_slots'] = $bookedSlots;
    
    // Get slot statuses from the appropriate slots table based on schedule type
    $slotStatuses = [];
    if ($scheduleType === 'staff') {
        $slotStatusesQuery = "SELECT slot_time, is_booked FROM staff_appointment_slots WHERE schedule_id = ?";
    } else {
        $slotStatusesQuery = "SELECT slot_time, is_booked FROM appointment_slots WHERE schedule_id = ?";
    }
    
    $slotStatusesStmt = $con->prepare($slotStatusesQuery);
    $slotStatusesStmt->execute([$scheduleId]);
    
    while ($statusRow = $slotStatusesStmt->fetch(PDO::FETCH_ASSOC)) {
        $slotStatuses[$statusRow['slot_time']] = [
            'is_booked' => $statusRow['is_booked']
        ];
    }
    
    $response['slot_statuses'] = $slotStatuses;
    
    // If client ID provided, get their existing appointments
    if ($clientId > 0) {
        $clientQuery = "SELECT * FROM clients WHERE id = ?";
        $clientStmt = $con->prepare($clientQuery);
        $clientStmt->execute([$clientId]);
        $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            $clientName = $client['full_name'];
            
            // Get all appointments for this client on this date
            $clientAptsQuery = "SELECT appointment_time FROM appointments 
                             WHERE appointment_date = ? 
                               AND patient_name = ? 
                               AND status != 'cancelled'";
            $clientAptsStmt = $con->prepare($clientAptsQuery);
            $clientAptsStmt->execute([$schedule['schedule_date'], $clientName]);
            
            $clientAppointments = [];
            while ($apt = $clientAptsStmt->fetch(PDO::FETCH_ASSOC)) {
                $clientAppointments[] = $apt['appointment_time'];
            }
            
            $response['client_appointments'] = $clientAppointments;
        }
    }
    
    echo json_encode($response);
    
} catch (PDOException $ex) {
    // Log the error for administrators
    error_log("Database error in client_check_booked_appointment_slots.php: " . $ex->getMessage());
    
    // Return a generic error message to the client
    $response['error'] = "A database error occurred. Please try again later.";
    echo json_encode($response);
}
?> 