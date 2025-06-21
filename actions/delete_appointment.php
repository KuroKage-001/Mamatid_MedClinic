<?php
session_start();
include '../config/connection.php';
include '../actions/manage_appointment_slots.php';

// Check if user is logged in
if (!isset($_SESSION['client_id'])) {
    header('location: ../login.php');
    exit;
}

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    header('location: ../client_dashboard.php?error=Invalid request');
    exit;
}

$appointmentId = $_GET['id'];
$clientId = $_SESSION['client_id'];

try {
    // Start transaction
    $con->beginTransaction();
    
    // Verify that the appointment belongs to the logged-in client
    $verifyQuery = "SELECT a.id, a.schedule_id, a.appointment_time FROM appointments a
                   JOIN clients c ON a.patient_name = c.full_name
                   WHERE a.id = ? AND c.id = ?";
    $verifyStmt = $con->prepare($verifyQuery);
    $verifyStmt->execute([$appointmentId, $clientId]);
    $appointment = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        $con->rollback();
        header('location: ../client_dashboard.php?error=You are not authorized to cancel this appointment');
        exit;
    }
    
    // Update appointment status to cancelled
    $updateQuery = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->execute([$appointmentId]);
    
    // Free up the appointment slot in the appointment_slots table
    cancelAppointmentSlot($appointmentId);
    
    $con->commit();
    header('location: ../client_dashboard.php?message=Appointment cancelled successfully');
} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollback();
    }
    header('location: ../client_dashboard.php?error=' . urlencode($e->getMessage()));
}
?> 