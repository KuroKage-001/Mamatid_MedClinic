<?php
include('../config/connection.php');

// Check if ID is provided
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Start transaction
        $con->beginTransaction();
        
        // Delete the record
        $stmt = $con->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $con->commit();
        
        // Redirect to users page with success message
        header("Location: ../users.php?success=1");
        exit();
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $con->rollBack();
        
        // Redirect to users page with error message
        header("Location: ../users.php?error=1");
        exit();
    }
} else {
    // If no ID provided, redirect to users page
    header("Location: ../users.php");
    exit();
}
?> 