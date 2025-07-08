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
        
        // Update the record to unarchived status
        $query = "UPDATE `general_family_members` 
                  SET `is_archived` = 0, 
                      `archived_at` = NULL, 
                      `archived_by` = NULL,
                      `archive_reason` = NULL 
                  WHERE `id` = :id AND `is_archived` = 1";
        
        $stmt = $con->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $con->commit();
            $message = 'Family member unarchived successfully.';
        } else {
            $con->rollback();
            $message = 'Failed to unarchive family member.';
        }
        
    } catch (PDOException $ex) {
        $con->rollback();
        $message = 'Error: ' . $ex->getMessage();
    }
}

header("Location: ../general_family_members.php?message=" . urlencode($message));
exit;
?> 