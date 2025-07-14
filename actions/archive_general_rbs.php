<?php
include '../config/db_connection.php';
include '../system/utilities/admin_client_common_functions_services.php';
require_once '../system/utilities/admin_client_role_functions_services.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../general_rbs.php?message=" . urlencode("Access denied"));
    exit;
}

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['archive_id']) ? intval($_POST['archive_id']) : 0;
    $reason = isset($_POST['archive_reason']) ? trim($_POST['archive_reason']) : '';
    $user_id = $_SESSION['user_id'];
    
    if ($id <= 0) {
        header("Location: ../general_rbs.php?message=" . urlencode("Invalid record ID"));
        exit;
    }
    
    try {
        // Check if record exists and is not already archived
        $checkQuery = "SELECT id, name FROM general_rbs WHERE id = ? AND is_archived = 0";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->execute([$id]);
        
        if ($checkStmt->rowCount() === 0) {
            header("Location: ../general_rbs.php?message=" . urlencode("Record not found or already archived"));
            exit;
        }
        
        $record = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        // Archive the record
        $archiveQuery = "UPDATE general_rbs 
                         SET is_archived = 1, archived_at = NOW(), archived_by = ?, archive_reason = ? 
                         WHERE id = ?";
        $archiveStmt = $con->prepare($archiveQuery);
        $archiveStmt->execute([$user_id, $reason, $id]);
        
        header("Location: ../general_rbs.php?message=" . urlencode("Random blood sugar record for \"" . $record['name'] . "\" has been archived successfully"));
        exit;
        
    } catch (PDOException $ex) {
        header("Location: ../general_rbs.php?message=" . urlencode("Database error: " . $ex->getMessage()));
        exit;
    }
} else {
    header("Location: ../general_rbs.php?message=" . urlencode("Invalid request method"));
    exit;
}
?> 