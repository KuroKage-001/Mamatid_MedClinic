<?php
// Include client authentication check
require_once '../system/utilities/check_client_auth.php';

include '../config/db_connection.php';

// Check if schedule_id is provided
if (!isset($_POST['schedule_id'])) {
    echo json_encode(['error' => 'Schedule ID is required']);
    exit;
}

$scheduleId = $_POST['schedule_id'];
$scheduleType = isset($_POST['schedule_type']) ? $_POST['schedule_type'] : 'doctor';

try {
    // Determine which table to query based on schedule type
    if ($scheduleType === 'staff') {
        $query = "SELECT schedule_date, start_time, end_time, time_slot_minutes, max_patients 
                  FROM admin_hw_schedules 
                  WHERE id = ?";
    } else {
        $query = "SELECT schedule_date, start_time, end_time, time_slot_minutes, max_patients 
                  FROM admin_doctor_schedules 
                  WHERE id = ? AND is_approved = 1";
    }
    
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