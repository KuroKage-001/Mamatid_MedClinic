<?php
include('../config/db_connection.php');

// Check if ID is provided
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Start transaction
        $con->beginTransaction();
        
        // Delete the record
        $stmt = $con->prepare("DELETE FROM tetanus_toxoid WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $con->commit();
        
        // Redirect to tetanus toxoid page with success message
        header("Location: ../general_tetanus_toxoid.php?success=1");
        exit();
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $con->rollBack();
        
        // Redirect to tetanus toxoid page with error message
        header("Location: ../general_tetanus_toxoid.php?error=1");
        exit();
    }
} else {
    // If no ID provided, redirect to tetanus toxoid page
    header("Location: ../general_tetanus_toxoid.php");
    exit();
}
?> 