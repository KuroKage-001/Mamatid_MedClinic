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
    
    // Update past appointments to completed status
    $query = "UPDATE appointments 
              SET status = 'completed', 
                  updated_at = NOW() 
              WHERE CONCAT(appointment_date, ' ', appointment_time) < NOW() 
              AND status = 'approved'";
    
    // Add doctor_id filter if provided
    if ($doctorId !== null && $doctorId > 0) {
        $query .= " AND doctor_id = :doctor_id";
    }
    
    $stmt = $con->prepare($query);
    
    // Bind doctor_id if provided
    if ($doctorId !== null && $doctorId > 0) {
        $stmt->bindParam(':doctor_id', $doctorId, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $updatedCount = $stmt->rowCount();
    
    // Commit the transaction
    $con->commit();
    
    // Set success response
    $response['success'] = true;
    $response['updated'] = $updatedCount;
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