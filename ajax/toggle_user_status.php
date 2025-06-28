<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/db_connection.php';

// Get POST data
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Validate inputs
if ($user_id <= 0 || !in_array($status, ['active', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Don't allow admin to deactivate themselves
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot deactivate your own account']);
    exit;
}

// Check if target user is an admin - don't allow deactivating other admins
$checkSql = "SELECT role FROM users WHERE id = :id";
$checkStmt = $con->prepare($checkSql);
$checkStmt->bindParam(':id', $user_id);
$checkStmt->execute();
$targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($targetUser && $targetUser['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot deactivate administrator accounts']);
    exit;
}

try {
    // Update user status
    $sql = "UPDATE users SET status = :status WHERE id = :id";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 