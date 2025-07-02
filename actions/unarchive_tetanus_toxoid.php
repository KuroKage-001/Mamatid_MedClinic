<?php
session_start();
include '../config/db_connection.php';
require_once '../common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

// Check if unarchive_id is provided
if (!isset($_POST['unarchive_id']) || empty($_POST['unarchive_id'])) {
    header("Location: ../general_tetanus_toxoid.php?show_archived=1&message=" . urlencode("Invalid record ID"));
    exit;
}

$unarchive_id = (int)$_POST['unarchive_id'];

try {
    // Start transaction
    $con->beginTransaction();
    
    // Update the record to set it as active (unarchived)
    $query = "UPDATE general_tetanus_toxoid 
              SET is_archived = 0, 
                  archived_at = NULL, 
                  archived_by = NULL, 
                  archive_reason = NULL 
              WHERE id = :id AND is_archived = 1";
    
    $stmt = $con->prepare($query);
    $result = $stmt->execute([':id' => $unarchive_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        $con->commit();
        $message = "Tetanus toxoid record unarchived successfully.";
        // Redirect to active records view
        header("Location: ../general_tetanus_toxoid.php?message=" . urlencode($message));
    } else {
        throw new Exception("Record not found or already active.");
    }
    
} catch (Exception $e) {
    $con->rollback();
    $message = "Error unarchiving record: " . $e->getMessage();
    // Redirect back to archived records view
    header("Location: ../general_tetanus_toxoid.php?show_archived=1&message=" . urlencode($message));
}

exit;
?> 