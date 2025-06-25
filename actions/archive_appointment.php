<?php
include '../config/connection.php';
require_once '../common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:../index.php");
    exit;
}

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $appointmentId = $_GET['id'];
    
    try {
        // Start transaction
        $con->beginTransaction();
        
        // Update the appointment to archived status
        $query = "UPDATE appointments SET is_archived = 1, updated_at = NOW() WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$appointmentId]);
        
        // Commit the transaction
        $con->commit();
        
        // Redirect with success message
        header("Location: ../manage_appointments.php?message=" . urlencode("Appointment #" . $appointmentId . " has been archived successfully"));
        exit();
    } catch (PDOException $ex) {
        // Rollback transaction on error
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        
        // Redirect with error message
        header("Location: ../manage_appointments.php?error=" . urlencode("Error: " . $ex->getMessage()));
        exit();
    }
} else {
    // Invalid ID provided
    header("Location: ../manage_appointments.php?error=" . urlencode("Invalid appointment ID"));
    exit();
}
?> 