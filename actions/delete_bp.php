<?php
include('../config/db_connection.php');

// Check if ID is provided
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Start transaction
        $con->beginTransaction();
        
        // Delete the record
        $stmt = $con->prepare("DELETE FROM general_bp_monitoring WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $con->commit();
        
        // Redirect to BP monitoring page with success message
        header("Location: ../general_bp_monitoring.php?success=1");
        exit();
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $con->rollBack();
        
        // Redirect to BP monitoring page with error message
        header("Location: ../general_bp_monitoring.php?error=1");
        exit();
    }
} else {
    // If no ID provided, redirect to BP monitoring page
    header("Location: ../general_bp_monitoring.php");
    exit();
} 