<?php
include '../config/connection.php';
require_once '../common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:../index.php");
    exit;
}

// Check permission - only admins, health workers, and doctors can delete their schedules
requireRole(['admin', 'health_worker', 'doctor']);

$staffId = $_SESSION['user_id'];
$scheduleId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userRole = $_SESSION['role'];
$redirectPage = ($userRole == 'doctor') ? '../admin_doctor_schedule_plotter.php' : '../admin_hw_schedule_plotter.php';

if ($scheduleId > 0) {
    try {
        // Determine which table to use based on role
        if ($userRole == 'doctor') {
            $tableName = 'doctor_schedules';
            $idColumn = 'doctor_id';
        } else {
            $tableName = 'staff_schedules';
            $idColumn = 'staff_id';
        }

        // First check if the schedule belongs to this staff member
        $query = "SELECT {$idColumn} FROM {$tableName} WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$scheduleId]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($schedule && $schedule[$idColumn] == $staffId) {
            // Check if there are any appointments for this schedule
            $apptQuery = "SELECT COUNT(*) as count FROM appointments WHERE schedule_id = ?";
            $apptStmt = $con->prepare($apptQuery);
            $apptStmt->execute([$scheduleId]);
            $appointmentCount = $apptStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($appointmentCount > 0) {
                header("location:{$redirectPage}?error=" . urlencode("Cannot delete: Schedule has booked appointments"));
                exit;
            }
            
            // Delete the schedule
            $query = "DELETE FROM {$tableName} WHERE id = ?";
            $stmt = $con->prepare($query);
            $stmt->execute([$scheduleId]);
            
            header("location:{$redirectPage}?message=Schedule deleted successfully");
        } else {
            header("location:{$redirectPage}?error=You can only delete your own schedules");
        }
    } catch(PDOException $ex) {
        header("location:{$redirectPage}?error=" . urlencode("Error: " . $ex->getMessage()));
    }
} else {
    header("location:{$redirectPage}?error=Invalid schedule ID");
}
exit;
?> 