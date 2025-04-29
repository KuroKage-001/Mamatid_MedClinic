<?php
include './config/connection.php';

$message = '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

if ($id == '') {
    header("Location:family_planning.php");
    exit;
}

try {
    // Begin transaction
    $con->beginTransaction();

    // Delete the family planning record
    $query = "DELETE FROM `family_planning` WHERE `id` = :id";
    $stmt = $con->prepare($query);
    $stmt->execute([':id' => $id]);

    // Commit transaction
    $con->commit();
    $message = 'Family planning record deleted successfully.';
} catch (PDOException $ex) {
    // Rollback transaction on error
    $con->rollback();
    $message = 'Error deleting record: ' . $ex->getMessage();
}

// Redirect with message
header("Location:family_planning.php?message=" . urlencode($message));
exit;
?> 