<?php
include('../config/connection.php');

// Check if ID is provided
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Start transaction
        $con->beginTransaction();
        
        // Delete the family member
        $stmt = $con->prepare("DELETE FROM family_members WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $con->commit();
        
        // Redirect to family members page with success message
        header("Location: ../general_family_members.php?success=1");
        exit();
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $con->rollBack();
        
        // Redirect to family members page with error message
        header("Location: ../general_family_members.php?error=1");
        exit();
    }
} else {
    // If no ID provided, redirect to family members page
    header("Location: ../general_family_members.php");
    exit();
}
?> 