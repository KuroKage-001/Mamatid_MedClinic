<?php
session_start();
include '../config/connection.php';
include '../actions/manage_appointment_slots.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('location: ../login.php');
    exit;
}

// Check if schedule ID is provided
if (!isset($_GET['id'])) {
    header('location: ../admin/doctor_schedules.php?error=Invalid request');
    exit;
}

$scheduleId = $_GET['id'];

try {
    // Start transaction
    $con->beginTransaction();
    
    // Approve the schedule
    $updateQuery = "UPDATE doctor_schedules SET is_approved = 1 WHERE id = ?";
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->execute([$scheduleId]);
    
    // Create appointment slots for this schedule
    createAppointmentSlots($scheduleId);
    
    $con->commit();
    header('location: ../admin/doctor_schedules.php?message=Schedule approved and slots created successfully');
} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollback();
    }
    header('location: ../admin/doctor_schedules.php?error=' . urlencode($e->getMessage()));
}
?>
