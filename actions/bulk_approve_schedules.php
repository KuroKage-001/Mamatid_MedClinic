<?php
session_start();
require_once '../config/admin_session_config.php';
require_once '../system/database/db_connection.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Check if user is logged in and has admin rights
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        throw new Exception('Access denied. Please log in.');
    }

    // Check if this is a bulk approval request
    if (!isset($_POST['bulk_approve']) || $_POST['bulk_approve'] !== '1') {
        throw new Exception('Invalid request.');
    }

    // Get the approval type
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    
    if (!in_array($type, ['all', 'selected'])) {
        throw new Exception('Invalid approval type.');
    }

    // Start transaction
    $con->beginTransaction();

    $approvedCount = 0;
    $totalProcessed = 0;

    if ($type === 'all') {
        // Approve all pending doctor schedules
        $query = "UPDATE admin_doctor_schedules 
                  SET is_approved = 1, 
                      approved_by = ?, 
                      approved_at = NOW(),
                      updated_at = NOW()
                  WHERE is_approved = 0 OR is_approved IS NULL";
        
        $stmt = $con->prepare($query);
        $stmt->execute([$_SESSION['admin_id']]);
        
        $approvedCount = $stmt->rowCount();
        $totalProcessed = $approvedCount;

        if ($approvedCount === 0) {
            throw new Exception('No pending doctor schedules found to approve.');
        }

    } elseif ($type === 'selected') {
        // Get selected schedule IDs
        $scheduleIds = isset($_POST['schedule_ids']) ? $_POST['schedule_ids'] : [];
        
        if (empty($scheduleIds)) {
            throw new Exception('No schedules selected for approval.');
        }

        // Validate schedule IDs
        $scheduleIds = array_filter($scheduleIds, function($id) {
            return is_numeric($id) && $id > 0;
        });

        if (empty($scheduleIds)) {
            throw new Exception('Invalid schedule IDs provided.');
        }

        $totalProcessed = count($scheduleIds);

        // Create placeholders for IN clause
        $placeholders = str_repeat('?,', count($scheduleIds) - 1) . '?';
        
        // Approve selected schedules (only if they're pending)
        $query = "UPDATE admin_doctor_schedules 
                  SET is_approved = 1, 
                      approved_by = ?, 
                      approved_at = NOW(),
                      updated_at = NOW()
                  WHERE id IN ($placeholders) 
                  AND (is_approved = 0 OR is_approved IS NULL)";
        
        $params = array_merge([$_SESSION['admin_id']], $scheduleIds);
        $stmt = $con->prepare($query);
        $stmt->execute($params);
        
        $approvedCount = $stmt->rowCount();

        if ($approvedCount === 0) {
            throw new Exception('No pending schedules found among the selected items, or schedules are already approved.');
        }
    }

    // Log the bulk approval action
    $logQuery = "INSERT INTO admin_activity_logs (admin_id, action_type, action_details, ip_address, created_at) 
                 VALUES (?, 'bulk_approval', ?, ?, NOW())";
    
    $actionDetails = json_encode([
        'type' => $type,
        'approved_count' => $approvedCount,
        'total_processed' => $totalProcessed,
        'schedule_ids' => $type === 'selected' ? $scheduleIds : 'all_pending'
    ]);
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logStmt = $con->prepare($logQuery);
    $logStmt->execute([$_SESSION['admin_id'], $actionDetails, $ipAddress]);

    // Commit transaction
    $con->commit();

    // Prepare success message
    $message = '';
    if ($type === 'all') {
        $message = "Successfully approved all {$approvedCount} pending doctor schedule" . ($approvedCount > 1 ? 's' : '') . ".";
    } else {
        $message = "Successfully approved {$approvedCount} out of {$totalProcessed} selected schedule" . ($approvedCount > 1 ? 's' : '') . ".";
        if ($approvedCount < $totalProcessed) {
            $skipped = $totalProcessed - $approvedCount;
            $message .= " ({$skipped} schedule" . ($skipped > 1 ? 's were' : ' was') . " already approved)";
        }
    }

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