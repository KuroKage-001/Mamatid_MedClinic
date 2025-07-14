<?php
// Include client authentication check (this handles session isolation automatically)
require_once '../system/utilities/check_client_auth.php';

include '../config/db_connection.php';

$message = '';
$appointmentId = isset($_GET['id']) ? $_GET['id'] : 0;

// Get client ID from session using safe getter
$clientId = getClientSessionVar('client_id');

try {
    // Check if appointment exists and belongs to the client
    $query = "SELECT id FROM admin_clients_appointments WHERE id = ? AND patient_name = (SELECT full_name FROM clients_user_accounts WHERE id = ?)";
    $stmt = $con->prepare($query);
    $stmt->execute([$appointmentId, $clientId]);
    
    if ($stmt->rowCount() > 0) {
        $con->beginTransaction();
        
        // Delete the appointment
        $query = "DELETE FROM admin_clients_appointments WHERE id = ?";
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
header("location:../client_portal/client_dashboard.php?message=" . urlencode($message));
exit;
?> 