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

if (isset($_POST['archive_id']) && isset($_POST['archive_reason'])) {
    $id = (int)$_POST['archive_id'];
    $reason = trim($_POST['archive_reason']);
    $user_id = $_SESSION['user_id'];
    
    try {
        $con->beginTransaction();
        
        // Update the record to archived status
        $query = "UPDATE `general_family_planning` 
                  SET `is_archived` = 1, 
                      `archived_at` = NOW(), 
                      `archived_by` = :user_id,
                      `archive_reason` = :reason 
                  WHERE `id` = :id AND `is_archived` = 0";
        
        $stmt = $con->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            $con->commit();
            $message = 'Family planning record archived successfully.';
        } else {
            $con->rollback();
            $message = 'Failed to archive family planning record.';
        }
        
    } catch (PDOException $ex) {
        $con->rollback();
        $message = 'Error: ' . $ex->getMessage();
    }
}

header("Location: ../general_family_planning.php?message=" . urlencode($message));
exit;
?> 