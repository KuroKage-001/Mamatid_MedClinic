<?php
include './config/connection.php';

// Check if ID is provided
$id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($id)) {
    header("Location: tetanus_toxoid.php");
    exit;
}

try {
    // Start transaction
    $con->beginTransaction();

    // Prepare and execute delete query
    $query = "DELETE FROM tetanus_toxoid WHERE id = :id";
    $stmt = $con->prepare($query);
    $result = $stmt->execute([':id' => $id]);

    if ($result) {
        $con->commit();
        header("Location: tetanus_toxoid.php?message=" . urlencode("Record deleted successfully"));
    } else {
        throw new Exception("Failed to delete record");
    }
} catch (Exception $e) {
    $con->rollback();
    header("Location: tetanus_toxoid.php?message=" . urlencode($e->getMessage()));
}
exit;
?> 