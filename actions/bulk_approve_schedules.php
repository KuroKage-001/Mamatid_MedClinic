<?php
session_start();
include '../config/db_connection.php';
require_once '../system/utilities/admin_client_role_functions_services.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Check if user is logged in and has admin rights
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Access denied. Please log in.');
    }

    // Check user role - only admin and health workers can approve schedules
    if (!in_array($_SESSION['role'], ['admin', 'health_worker'])) {
        throw new Exception('Access denied. Insufficient permissions.');
    }

    // Check if this is a bulk approval request
    if (!isset($_POST['bulk_approve']) || $_POST['bulk_approve'] !== '1') {
        throw new Exception('Invalid request.');
    }

    // Only allow "all" type approval
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    
    if ($type !== 'all') {
        throw new Exception('Only bulk approval of all pending schedules is allowed.');
    }

    // Start transaction
    $con->beginTransaction();

    // Approve all pending doctor schedules
    $query = "UPDATE admin_doctor_schedules 
              SET is_approved = 1, 
                  approval_notes = CONCAT(COALESCE(approval_notes, ''), 'Bulk approved by " . $_SESSION['display_name'] . " on " . date('Y-m-d H:i:s') . "'),
                  updated_at = NOW()
              WHERE is_approved = 0 OR is_approved IS NULL";
    
    $stmt = $con->prepare($query);
    $stmt->execute();
    
    $approvedCount = $stmt->rowCount();
    $totalProcessed = $approvedCount;

    if ($approvedCount === 0) {
        throw new Exception('No pending doctor schedules found to approve.');
    }

    // Log the bulk approval action if the activity logs table exists
    try {
        $logQuery = "INSERT INTO admin_activity_logs (user_id, action_type, action_details, ip_address, created_at) 
                     VALUES (?, 'bulk_approval', ?, ?, NOW())";
        
        $actionDetails = json_encode([
            'type' => 'all',
            'approved_count' => $approvedCount,
            'total_processed' => $totalProcessed,
            'approved_by' => $_SESSION['display_name']
        ]);
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $logStmt = $con->prepare($logQuery);
        $logStmt->execute([$_SESSION['user_id'], $actionDetails, $ipAddress]);
    } catch (PDOException $logEx) {
        // Continue even if logging fails - this is not critical
        error_log("Activity log error: " . $logEx->getMessage());
    }

    // Commit transaction
    $con->commit();

    // Prepare success message
    $message = "Successfully approved all {$approvedCount} pending doctor schedule" . ($approvedCount > 1 ? 's' : '') . ".";

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => [
            'approved_count' => $approvedCount,
            'total_processed' => $totalProcessed,
            'type' => $type
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction if it was started
    if ($con->inTransaction()) {
        $con->rollback();
    }
    
    // Log the error
    error_log("Bulk Approval Error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction if it was started
    if ($con->inTransaction()) {
        $con->rollback();
    }
    
    // Log the database error
    error_log("Bulk Approval Database Error: " . $e->getMessage());
    
    // Return generic error response
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred. Please try again.'
    ]);
}
?> 