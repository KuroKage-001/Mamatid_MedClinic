<?php
include '../config/db_connection.php';

// Check if schedule_id is provided
if (!isset($_POST['schedule_id'])) {
    echo json_encode(['error' => 'Schedule ID is required']);
    exit;
}

$scheduleId = $_POST['schedule_id'];

try {
    // Get schedule details
    $query = "SELECT schedule_date, start_time, end_time, time_slot_minutes, max_patients 
              FROM doctor_schedules 
              WHERE id = ?";
    $stmt = $con->prepare($query);
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode(['error' => 'Schedule not found']);
        exit;
    }
    
    echo json_encode($schedule);
} catch(PDOException $ex) {
    echo json_encode(['error' => $ex->getMessage()]);
}
?> 