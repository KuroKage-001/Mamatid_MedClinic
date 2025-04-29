<?php
include './config/connection.php';

// Check if ID is provided
$id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($id)) {
    header("Location: family_members.php?message=" . urlencode("Invalid request"));
    exit;
}

try {
    // Start transaction
    $con->beginTransaction();

    // Check if record exists
    $check_query = "SELECT id FROM family_members WHERE id = :id";
    $check_stmt = $con->prepare($check_query);
    $check_stmt->execute([':id' => $id]);
    
    if ($check_stmt->rowCount() === 0) {
        throw new Exception("Record not found");
    }

    // Prepare and execute delete query
    $delete_query = "DELETE FROM family_members WHERE id = :id";
    $delete_stmt = $con->prepare($delete_query);
    $result = $delete_stmt->execute([':id' => $id]);

    if ($result) {
        $con->commit();
        header("Location: family_members.php?message=" . urlencode("Record deleted successfully"));
        exit;
    } else {
        throw new Exception("Failed to delete record");
    }
} catch (Exception $e) {
    // Rollback transaction on error
    if ($con->inTransaction()) {
        $con->rollback();
    }
    header("Location: family_members.php?message=" . urlencode("Error: " . $e->getMessage()));
    exit;
}
?> 