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
$archive_reason = isset($input['reason']) ? trim($input['reason']) : null;
$archived_by = $_SESSION['user_id'];

try {
    // Start transaction
    $con->beginTransaction();
    
    // Check if record exists and is not already archived
    $check_query = "SELECT id, name FROM general_bp_monitoring WHERE id = ? AND is_archived = 0";
    $check_stmt = $con->prepare($check_query);
    $check_stmt->execute([$record_id]);
    $record = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('BP monitoring record not found or already archived');
    }
    
    // Archive the record
    $archive_query = "UPDATE general_bp_monitoring 
                     SET is_archived = 1, 
                         archived_at = NOW(), 
                         archived_by = ?, 
                         archive_reason = ? 
                     WHERE id = ?";
    
    $archive_stmt = $con->prepare($archive_query);
    $result = $archive_stmt->execute([$archived_by, $archive_reason, $record_id]);
    
    if (!$result) {
        throw new Exception('Failed to archive BP monitoring record');
    }
    
    // Commit transaction
    $con->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'BP monitoring record archived successfully',
        'record_name' => $record['name']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $con->rollback();
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error archiving BP monitoring record: ' . $e->getMessage()
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