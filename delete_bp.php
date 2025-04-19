<?php
include './config/connection.php';

// Check if ID is provided
$id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($id)) {
    header("Location: bp_monitoring.php?message=" . urlencode("No record specified for deletion"));
    exit;
}

try {
    // Start transaction
    $con->beginTransaction();

    // Prepare and execute delete query with parameter binding
    $query = "DELETE FROM bp_monitoring WHERE id = :id";
    $stmt = $con->prepare($query);
    $result = $stmt->execute([':id' => $id]);

    if ($result) {
        $con->commit();
        header("Location: bp_monitoring.php?message=" . urlencode("Record deleted successfully"));
    } else {
        throw new Exception("Failed to delete record");
    }
} catch (Exception $e) {
    // Rollback transaction on error
    if ($con->inTransaction()) {
        $con->rollback();
    }
    header("Location: bp_monitoring.php?message=" . urlencode("Error: " . $e->getMessage()));
} finally {
    exit;
} 