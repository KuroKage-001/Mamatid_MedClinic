<?php
include './config/db_connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header("location:client_login.php");
    exit;
}

$message = '';
$appointmentId = isset($_GET['id']) ? $_GET['id'] : 0;

try {
    // Check if appointment exists and belongs to the client
    $query = "SELECT id FROM appointments WHERE id = ? AND patient_name = (SELECT full_name FROM clients_user_accounts WHERE id = ?)";
    $stmt = $con->prepare($query);
    $stmt->execute([$appointmentId, $_SESSION['client_id']]);
    
    if ($stmt->rowCount() > 0) {
        $con->beginTransaction();
        
        // Delete the appointment
        $query = "DELETE FROM appointments WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$appointmentId]);
        
        $con->commit();
        $message = "Appointment deleted successfully!";
    } else {
        $message = "Invalid appointment or unauthorized access.";
    }
} catch(PDOException $ex) {
    $con->rollback();
    $message = "An error occurred while deleting the appointment.";
}

// Redirect back to dashboard with message
header("location:client_dashboard.php?message=" . urlencode($message));
exit;
?> 