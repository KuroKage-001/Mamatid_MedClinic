<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/connection.php';

// Get POST data
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

// Validate inputs
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Don't allow admin to delete themselves
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit;
}

// Check if target user exists and get their role
$checkSql = "SELECT role, display_name FROM users WHERE id = :id";
$checkStmt = $con->prepare($checkSql);
$checkStmt->bindParam(':id', $user_id);
$checkStmt->execute();
$targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$targetUser) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Don't allow deleting other admin accounts
if ($targetUser['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete administrator accounts']);
    exit;
}

try {
    // Begin transaction
    $con->beginTransaction();
    
    // Delete user record
    $sql = "DELETE FROM users WHERE id = :id";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':id', $user_id);
    
    if ($stmt->execute()) {
        // Commit transaction
        $con->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'User "' . $targetUser['display_name'] . '" has been deleted successfully'
        ]);
    } else {
        // Rollback transaction
        $con->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
} catch (Exception $e) {
    // Rollback transaction on error
    $con->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 