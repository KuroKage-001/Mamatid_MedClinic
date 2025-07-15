<?php
include '../config/db_connection.php';
include '../system/utilities/admin_client_common_functions_services.php';
require_once '../system/utilities/admin_client_role_functions_services.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

// Check if request is POST and has required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || empty($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'BP monitoring record ID is required']);
    exit;
}

$record_id = intval($input['id']);

try {
    // Start transaction
    $con->beginTransaction();
    
    // Check if record exists and is archived
    $check_query = "SELECT id, name FROM general_bp_monitoring WHERE id = ? AND is_archived = 1";
    $check_stmt = $con->prepare($check_query);
    $check_stmt->execute([$record_id]);
    $record = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('BP monitoring record not found or not archived');
    }
    
    // Unarchive the record by clearing archive data
    $unarchive_query = "UPDATE general_bp_monitoring 
                       SET is_archived = 0, 
                           archived_at = NULL, 
                           archived_by = NULL, 
                           archive_reason = NULL 
                       WHERE id = ?";
    
    $unarchive_stmt = $con->prepare($unarchive_query);
    $result = $unarchive_stmt->execute([$record_id]);
    
    if (!$result) {
        throw new Exception('Failed to unarchive BP monitoring record');
    }
    
    // Commit transaction
    $con->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'BP monitoring record unarchived successfully',
        'record_name' => $record['name']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $con->rollback();
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error unarchiving BP monitoring record: ' . $e->getMessage()
    ]);
} catch (PDOException $e) {
    // Rollback transaction on database error
    $con->rollback();
    
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 