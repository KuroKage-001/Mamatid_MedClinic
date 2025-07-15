<?php
include '../config/db_connection.php';
include '../system/utilities/admin_client_common_functions_services.php';
require_once '../system/utilities/admin_client_role_functions_services.php';

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
        
        // First, try to unarchive from admin_clients_appointments table
        $query1 = "UPDATE `admin_clients_appointments` 
                   SET `is_archived` = 0, 
                       `archived_at` = NULL, 
                       `archived_by` = NULL,
                       `archive_reason` = NULL,
                       `updated_at` = NOW()
                   WHERE `id` = :id AND `is_archived` = 1";
        
        $stmt1 = $con->prepare($query1);
        $stmt1->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt1->execute();
        
        $rowsAffected1 = $stmt1->rowCount();
        
        // If no rows affected, try admin_walkin_appointments table
        if ($rowsAffected1 == 0) {
            $query2 = "UPDATE `admin_walkin_appointments` 
                       SET `is_archived` = 0, 
                           `archived_at` = NULL, 
                           `archived_by` = NULL,
                           `archive_reason` = NULL,
                           `updated_at` = NOW()
                       WHERE `id` = :id AND `is_archived` = 1";
            
            $stmt2 = $con->prepare($query2);
            $stmt2->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt2->execute();
            
            $rowsAffected2 = $stmt2->rowCount();
            
            if ($rowsAffected2 > 0) {
                $con->commit();
                $message = 'Walk-in appointment unarchived successfully.';
            } else {
                $con->rollback();
                $message = 'Appointment not found or not archived.';
            }
        } else {
            $con->commit();
            $message = 'Regular appointment unarchived successfully.';
        }
        
    } catch (PDOException $ex) {
        $con->rollback();
        $message = 'Error: ' . $ex->getMessage();
    }
}

header("Location: ../admin_appointment_management.php?message=" . urlencode($message));
exit;
?> 