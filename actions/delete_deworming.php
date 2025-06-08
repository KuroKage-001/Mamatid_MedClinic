<?php
include('../config/connection.php');

// Check if ID is provided
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Start transaction
        $con->beginTransaction();
        
        // Delete the record
        $stmt = $con->prepare("DELETE FROM deworming WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $con->commit();
        
        // Redirect to deworming page with success message
        header("Location: ../deworming.php?success=1");
        exit();
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $con->rollBack();
        
        // Redirect to deworming page with error message
        header("Location: ../deworming.php?error=1");
        exit();
    }
} else {
    // If no ID provided, redirect to deworming page
    header("Location: ../deworming.php");
    exit();
}
?> 