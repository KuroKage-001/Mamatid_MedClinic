<?php
/**
 * Admin User Status Management
 * 
 * This script handles the toggling of user status (active/inactive) by administrators.
 * It includes security checks to prevent admins from deactivating their own accounts
 * or other administrator accounts.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../config/db_connection.php';

// Get POST data and sanitize
$user_id = isset($_POST['user_id']) ? filter_var($_POST['user_id'], FILTER_VALIDATE_INT) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validate inputs
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

if (!in_array($status, ['active', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

// Don't allow admin to deactivate themselves
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot deactivate your own account']);
    exit;
}

try {
    // Check if target user is an admin - don't allow deactivating other admins
    $checkSql = "SELECT role FROM admin_user_accounts WHERE id = :id";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->bindParam(':id', $user_id);
    $checkStmt->execute();
    $targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    if ($targetUser['role'] === 'admin') {
        echo json_encode(['success' => false, 'message' => 'Cannot deactivate administrator accounts']);
        exit;
    }
    
    // Update user status
    $sql = "UPDATE admin_user_accounts SET status = :status WHERE id = :id";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $user_id);
    
    if ($stmt->execute()) {
        // Log the action
        $action = $status === 'active' ? 'activated' : 'deactivated';
        $adminId = $_SESSION['user_id'];
        $logSql = "INSERT INTO admin_logs (admin_id, action, target_id, target_type, timestamp) 
                  VALUES (:admin_id, :action, :target_id, 'user', NOW())";
        
        // Uncomment if you have an admin_logs table
        // $logStmt = $con->prepare($logSql);
        // $logStmt->bindParam(':admin_id', $adminId);
        // $logStmt->bindParam(':action', $action);
        // $logStmt->bindParam(':target_id', $user_id);
        // $logStmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
    }
} catch (PDOException $e) {
    // Log the error for administrators
    error_log("Database error in admin_check_user_status.php: " . $e->getMessage());
    
    // Return a generic error message to the client
    echo json_encode(['success' => false, 'message' => 'A database error occurred']);
}
?> 