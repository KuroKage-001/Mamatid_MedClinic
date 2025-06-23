<?php
include '../config/connection.php';
require_once '../common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:../index.php");
    exit;
}

// Check permission - only doctors can delete their schedules
requireRole(['doctor']);

$doctorId = $_SESSION['user_id'];
$scheduleId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($scheduleId > 0) {
    try {
        // First check if the schedule belongs to the doctor
        $query = "SELECT doctor_id FROM doctor_schedules WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$scheduleId]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($schedule && $schedule['doctor_id'] == $doctorId) {
            // Delete the schedule
            $query = "DELETE FROM doctor_schedules WHERE id = ?";
            $stmt = $con->prepare($query);
            $stmt->execute([$scheduleId]);
            
            header("location:../doctor_schedule.php?message=Schedule deleted successfully");
        } else {
            header("location:../doctor_schedule.php?error=You can only delete your own schedules");
        }
    } catch(PDOException $ex) {
        header("location:../doctor_schedule.php?error=" . urlencode("Error: " . $ex->getMessage()));
    }
} else {
    header("location:../doctor_schedule.php?error=Invalid schedule ID");
}
exit;
?> 