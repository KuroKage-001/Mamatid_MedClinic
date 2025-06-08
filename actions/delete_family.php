<?php
include('../config/connection.php');

// Check if ID is provided
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Start transaction
        $con->beginTransaction();
        
        // Delete the record
        $stmt = $con->prepare("DELETE FROM family_planning WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $con->commit();
        
        // Redirect to family planning page with success message
        header("Location: ../family_planning.php?success=1");
        exit();
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $con->rollBack();
        
        // Redirect to family planning page with error message
        header("Location: ../family_planning.php?error=1");
        exit();
    }
} else {
    // If no ID provided, redirect to family planning page
    header("Location: ../family_planning.php");
    exit();
}
?> 