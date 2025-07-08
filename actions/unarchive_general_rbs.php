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
    $id = isset($_POST['unarchive_id']) ? intval($_POST['unarchive_id']) : 0;
    
    if ($id <= 0) {
        header("Location: ../general_rbs.php?message=" . urlencode("Invalid record ID"));
        exit;
    }
    
    try {
        // Check if record exists and is archived
        $checkQuery = "SELECT id, name FROM general_rbs WHERE id = ? AND is_archived = 1";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->execute([$id]);
        
        if ($checkStmt->rowCount() === 0) {
            header("Location: ../general_rbs.php?show_archived=1&message=" . urlencode("Record not found or not archived"));
            exit;
        }
        
        $record = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        // Unarchive the record
        $unarchiveQuery = "UPDATE general_rbs 
                           SET is_archived = 0, archived_at = NULL, archived_by = NULL, archive_reason = NULL 
                           WHERE id = ?";
        $unarchiveStmt = $con->prepare($unarchiveQuery);
        $unarchiveStmt->execute([$id]);
        
        header("Location: ../general_rbs.php?message=" . urlencode("Random blood sugar record for \"" . $record['name'] . "\" has been unarchived successfully"));
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