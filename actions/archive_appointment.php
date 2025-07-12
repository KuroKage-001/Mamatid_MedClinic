<?php
// Include authentication check
require_once '../system/utilities/check_auth.php';

include '../config/db_connection.php';
include '../system/utilities/admin_client_common_functions_services.php';
require_once '../system/utilities/admin_client_role_functions_services.php';

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

if (isset($_POST['archive_id']) && isset($_POST['archive_reason'])) {
    $id = (int)$_POST['archive_id'];
    $reason = trim($_POST['archive_reason']);
    $user_id = $_SESSION['user_id'];
    
    try {
        $con->beginTransaction();
        
        // First, try to archive from admin_clients_appointments table
        $query1 = "UPDATE `admin_clients_appointments` 
                   SET `is_archived` = 1, 
                       `archived_at` = NOW(), 
                       `archived_by` = :user_id,
                       `archive_reason` = :reason,
                       `updated_at` = NOW()
                   WHERE `id` = :id AND `is_archived` = 0";
        
        $stmt1 = $con->prepare($query1);
        $stmt1->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt1->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt1->bindParam(':reason', $reason, PDO::PARAM_STR);
        $stmt1->execute();
        
        $rowsAffected1 = $stmt1->rowCount();
        
        // If no rows affected, try admin_walkin_appointments table
        if ($rowsAffected1 == 0) {
            $query2 = "UPDATE `admin_walkin_appointments` 
                       SET `is_archived` = 1, 
                           `archived_at` = NOW(), 
                           `archived_by` = :user_id,
                           `archive_reason` = :reason,
                           `updated_at` = NOW()
                       WHERE `id` = :id AND `is_archived` = 0";
            
            $stmt2 = $con->prepare($query2);
            $stmt2->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt2->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt2->bindParam(':reason', $reason, PDO::PARAM_STR);
            $stmt2->execute();
            
            $rowsAffected2 = $stmt2->rowCount();
            
            if ($rowsAffected2 > 0) {
                $con->commit();
                $message = 'Walk-in appointment archived successfully.';
            } else {
                $con->rollback();
                $message = 'Appointment not found or already archived.';
            }
        } else {
            $con->commit();
            $message = 'Regular appointment archived successfully.';
        }
        
    } catch (PDOException $ex) {
        $con->rollback();
        $message = 'Error: ' . $ex->getMessage();
    }
}

header("Location: ../admin_appointment_management.php?message=" . urlencode($message));
exit;
?> 