<?php
include '../config/connection.php';
include '../actions/manage_appointment_slots.php';

// Check if required parameters are provided
if (!isset($_POST['schedule_id']) || !isset($_POST['appointment_time'])) {
    echo json_encode(['error' => 'Schedule ID and appointment time are required']);
    exit;
}

$scheduleId = $_POST['schedule_id'];
$appointmentTime = $_POST['appointment_time'];

try {
    // Get the max patients allowed for this schedule
    $scheduleQuery = "SELECT max_patients FROM doctor_schedules WHERE id = ?";
    $scheduleStmt = $con->prepare($scheduleQuery);
    $scheduleStmt->execute([$scheduleId]);
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode(['error' => 'Invalid schedule ID']);
        exit;
    }
    
    $maxPatients = $schedule['max_patients'];
    
    // Check slot availability using the new function
    $isAvailable = isSlotAvailable($scheduleId, $appointmentTime);
    
    // Get current booking count
    $slotQuery = "SELECT COUNT(*) as slot_count FROM appointments 
                WHERE schedule_id = ? AND appointment_time = ? AND status != 'cancelled'";
    $slotStmt = $con->prepare($slotQuery);
    $slotStmt->execute([$scheduleId, $appointmentTime]);
    $slotCount = $slotStmt->fetch(PDO::FETCH_ASSOC)['slot_count'];
    
    $remainingSlots = $maxPatients - $slotCount;
    
    echo json_encode([
        'is_available' => $isAvailable,
        'remaining_slots' => $remainingSlots,
        'max_patients' => $maxPatients,
        'booked_count' => $slotCount
    ]);
} catch(PDOException $ex) {
    echo json_encode(['error' => $ex->getMessage()]);
}
?> 