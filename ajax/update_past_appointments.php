<?php
include '../config/connection.php';

// Check if doctor_id is provided
$doctorId = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null;

try {
    // Start a transaction
    $con->beginTransaction();
    
    // Update past appointments to completed status
    $query = "UPDATE appointments 
              SET status = 'completed', updated_at = NOW() 
              WHERE CONCAT(appointment_date, ' ', appointment_time) < NOW() 
              AND status = 'approved'";
    
    // Add doctor_id filter if provided
    if ($doctorId) {
        $query .= " AND doctor_id = :doctor_id";
    }
    
    $stmt = $con->prepare($query);
    
    // Bind doctor_id if provided
    if ($doctorId) {
        $stmt->bindParam(':doctor_id', $doctorId);
    }
    
    $stmt->execute();
    $updatedCount = $stmt->rowCount();
    
    // Commit the transaction
    $con->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'updated' => $updatedCount,
        'message' => 'Past appointments updated successfully'
    ]);
    
} catch(PDOException $ex) {
    // Rollback the transaction in case of error
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $ex->getMessage()
    ]);
}
?> 