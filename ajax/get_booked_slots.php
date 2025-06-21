<?php
include '../config/connection.php';
include '../actions/manage_appointment_slots.php';

// Check if schedule_id is provided
if (!isset($_POST['schedule_id'])) {
    echo json_encode(['error' => 'Schedule ID is required']);
    exit;
}

$scheduleId = $_POST['schedule_id'];

try {
    // First get the schedule details including max_patients
    $scheduleQuery = "SELECT max_patients FROM doctor_schedules WHERE id = ?";
    $scheduleStmt = $con->prepare($scheduleQuery);
    $scheduleStmt->execute([$scheduleId]);
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode(['error' => 'Invalid schedule ID']);
        exit;
    }
    
    $maxPatients = $schedule['max_patients'];
    
    // Get booked slots using the new function
    $bookedSlots = getBookedSlots($scheduleId);
    
    // Return both booked slots and max_patients
    echo json_encode([
        'booked_slots' => $bookedSlots,
        'max_patients' => $maxPatients
    ]);
} catch(PDOException $ex) {
    echo json_encode(['error' => $ex->getMessage()]);
}
?> 