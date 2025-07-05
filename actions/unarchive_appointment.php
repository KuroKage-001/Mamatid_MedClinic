<?php
include '../config/db_connection.php';
include '../common_service/common_functions.php';
require_once '../common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:../index.php");
    exit;
}

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

if (isset($_POST['unarchive_id'])) {
    $id = (int)$_POST['unarchive_id'];
    
    try {
        $con->beginTransaction();
        
        // Update the appointment to unarchived status
        $query = "UPDATE `appointments` 
                  SET `is_archived` = 0, 
                      `archived_at` = NULL, 
                      `archived_by` = NULL,
                      `archive_reason` = NULL,
                      `updated_at` = NOW()
                  WHERE `id` = :id AND `is_archived` = 1";
        
        $stmt = $con->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $con->commit();
            $message = 'Appointment unarchived successfully.';
        } else {
            $con->rollback();
            $message = 'Failed to unarchive appointment.';
        }
        
    } catch (PDOException $ex) {
        $con->rollback();
        $message = 'Error: ' . $ex->getMessage();
    }
}

header("Location: ../admin_appointment_management.php?message=" . urlencode($message));
exit;
?> 