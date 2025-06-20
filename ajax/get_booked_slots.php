<?php
include '../config/connection.php';

// Check if schedule_id is provided
if (!isset($_POST['schedule_id'])) {
    echo json_encode(['error' => 'Schedule ID is required']);
    exit;
}

$scheduleId = $_POST['schedule_id'];

try {
    // Get booked slots for this schedule
    $query = "SELECT appointment_time, COUNT(*) as slot_count 
              FROM appointments 
              WHERE schedule_id = ? AND status != 'cancelled'
              GROUP BY appointment_time";
    $stmt = $con->prepare($query);
    $stmt->execute([$scheduleId]);
    
    $bookedSlots = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bookedSlots[$row['appointment_time']] = $row['slot_count'];
    }
    
    echo json_encode($bookedSlots);
} catch(PDOException $ex) {
    echo json_encode(['error' => $ex->getMessage()]);
}
?> 