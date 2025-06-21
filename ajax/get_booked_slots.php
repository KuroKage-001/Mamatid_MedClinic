<?php
include '../config/connection.php';

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
    
    // Get booked slots for this schedule
    $query = "SELECT appointment_time, COUNT(*) as slot_count 
              FROM appointments 
              WHERE schedule_id = ? AND status != 'cancelled'
              GROUP BY appointment_time";
    $stmt = $con->prepare($query);
    $stmt->execute([$scheduleId]);
    
    $bookedSlots = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bookedSlots[$row['appointment_time']] = [
            'count' => $row['slot_count'],
            'is_full' => ($row['slot_count'] >= $maxPatients)
        ];
    }
    
    // Return both booked slots and max_patients
    echo json_encode([
        'booked_slots' => $bookedSlots,
        'max_patients' => $maxPatients
    ]);
} catch(PDOException $ex) {
    echo json_encode(['error' => $ex->getMessage()]);
}
?> 