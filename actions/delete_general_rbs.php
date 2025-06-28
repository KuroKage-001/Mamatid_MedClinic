<?php
include('../config/connection.php');

// Check if ID is provided
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Start transaction
        $con->beginTransaction();
        
        // Delete the record
        $stmt = $con->prepare("DELETE FROM random_blood_sugar WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $con->commit();
        
        // Redirect to random blood sugar page with success message
        header("Location: ../general_rbs.php?success=1");
        exit();
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $con->rollBack();
        
        // Redirect to random blood sugar page with error message
        header("Location: ../general_rbs.php?error=1");
        exit();
    }
} else {
    // If no ID provided, redirect to random blood sugar page
    header("Location: ../general_rbs.php");
    exit();
}
?> 