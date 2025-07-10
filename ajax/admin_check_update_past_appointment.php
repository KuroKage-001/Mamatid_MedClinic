<?php
/**
 * Admin Past Appointment Status Updater
 * 
 * This script automatically updates the status of past appointments to 'completed'.
 * It can be filtered by doctor_id and uses transactions to ensure data consistency.
 * Called by both doctor and health worker schedule plotters.
 */

include '../config/db_connection.php';
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'updated' => 0,
    'message' => '',
    'error' => null
];

// Validate and sanitize doctor_id if provided
$doctorId = isset($_POST['doctor_id']) ? filter_var($_POST['doctor_id'], FILTER_VALIDATE_INT) : null;

try {
    // Start a transaction
    $con->beginTransaction();
    
    $totalUpdated = 0;
    
    // Update past client appointments to completed status
    $clientQuery = "UPDATE admin_clients_appointments 
                    SET status = 'completed', 
                        updated_at = NOW() 
                    WHERE CONCAT(appointment_date, ' ', appointment_time) < NOW() 
                    AND status = 'approved'
                    AND is_archived = 0";
    
    // Add doctor_id filter if provided
    if ($doctorId !== null && $doctorId > 0) {
        $clientQuery .= " AND doctor_id = :doctor_id";
    }
    
    $clientStmt = $con->prepare($clientQuery);
    
    // Bind doctor_id if provided
    if ($doctorId !== null && $doctorId > 0) {
        $clientStmt->bindParam(':doctor_id', $doctorId, PDO::PARAM_INT);
    }
    
    $clientStmt->execute();
    $clientUpdated = $clientStmt->rowCount();
    $totalUpdated += $clientUpdated;
    
    // Update past walk-in appointments to completed status
    $walkinQuery = "UPDATE admin_walkin_appointments 
                    SET status = 'completed', 
                        updated_at = NOW() 
                    WHERE CONCAT(appointment_date, ' ', appointment_time) < NOW() 
                    AND status = 'approved'";
    
    // Add provider filter if provided (for walk-ins, we check both doctor and staff)
    if ($doctorId !== null && $doctorId > 0) {
        $walkinQuery .= " AND provider_id = :provider_id";
    }
    
    $walkinStmt = $con->prepare($walkinQuery);
    
    // Bind provider_id if provided
    if ($doctorId !== null && $doctorId > 0) {
        $walkinStmt->bindParam(':provider_id', $doctorId, PDO::PARAM_INT);
    }
    
    $walkinStmt->execute();
    $walkinUpdated = $walkinStmt->rowCount();
    $totalUpdated += $walkinUpdated;
    
    // Commit the transaction
    $con->commit();
    
    // Set success response
    $response['success'] = true;
    $response['updated'] = $totalUpdated;
    $response['message'] = 'Past appointments updated successfully';
    
} catch(PDOException $ex) {
    // Rollback the transaction in case of error
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    
    // Log the error for administrators
    error_log("Database error in admin_check_update_past_appointment.php: " . $ex->getMessage());
    
    // Set error response without exposing database details
    $response['error'] = "A database error occurred while updating appointments";
}

// Send response
echo json_encode($response);
?> 