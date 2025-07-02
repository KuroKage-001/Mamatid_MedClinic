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

// Check if archive_id is provided
if (!isset($_POST['archive_id']) || empty($_POST['archive_id'])) {
    header("Location: ../general_tetanus_toxoid.php?message=" . urlencode("Invalid record ID"));
    exit;
}

$archive_id = (int)$_POST['archive_id'];
$archive_reason = trim($_POST['archive_reason'] ?? '');
$archived_by = $_SESSION['user_id'];

try {
    // Start transaction
    $con->beginTransaction();
    
    // Update the record to set it as archived
    $query = "UPDATE general_tetanus_toxoid 
              SET is_archived = 1, 
                  archived_at = NOW(), 
                  archived_by = :archived_by, 
                  archive_reason = :archive_reason 
              WHERE id = :id AND is_archived = 0";
    
    $stmt = $con->prepare($query);
    $result = $stmt->execute([
        ':id' => $archive_id,
        ':archived_by' => $archived_by,
        ':archive_reason' => $archive_reason
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        $con->commit();
        $message = "Tetanus toxoid record archived successfully.";
    } else {
        throw new Exception("Record not found or already archived.");
    }
    
} catch (Exception $e) {
    $con->rollback();
    $message = "Error archiving record: " . $e->getMessage();
}

// Redirect back to the main page with message
header("Location: ../general_tetanus_toxoid.php?message=" . urlencode($message));
exit;
?> 