<?php
include '../config/connection.php';

// Check if required parameters are provided
if (!isset($_POST['schedule_id']) || !isset($_POST['appointment_time'])) {
    echo json_encode(['error' => 'Schedule ID and appointment time are required']);
    exit;
}

$scheduleId = $_POST['schedule_id'];
$appointmentTime = $_POST['appointment_time'];

try {
    // Start a transaction to ensure consistency
    $con->beginTransaction();
    
    // First check if this slot exists in appointment_slots table and if it's marked as booked
    $slotExistsQuery = "SELECT id, is_booked, appointment_id FROM appointment_slots 
                      WHERE schedule_id = ? AND slot_time = ? 
                      FOR UPDATE";
    $slotExistsStmt = $con->prepare($slotExistsQuery);
    $slotExistsStmt->execute([$scheduleId, $appointmentTime]);
    $slotExists = $slotExistsStmt->fetch(PDO::FETCH_ASSOC);
    
    // If the slot exists and is marked as booked, it's not available
    if ($slotExists && $slotExists['is_booked'] == 1) {
        $con->rollBack();
        echo json_encode([
            'is_available' => false,
            'max_patients' => 1,
            'error' => 'This time slot is already booked.'
        ]);
        exit;
    }
    
    // Check if there's any appointment for this slot
    $slotQuery = "SELECT COUNT(*) as slot_count FROM appointments 
                WHERE schedule_id = ? AND appointment_time = ? AND status != 'cancelled'
                FOR UPDATE";
    $slotStmt = $con->prepare($slotQuery);
    $slotStmt->execute([$scheduleId, $appointmentTime]);
    $slotCount = $slotStmt->fetch(PDO::FETCH_ASSOC)['slot_count'];
    
    // If there's already an appointment for this slot, it's not available
    if ($slotCount > 0) {
        $con->rollBack();
        echo json_encode([
            'is_available' => false,
            'max_patients' => 1,
            'booked_count' => $slotCount,
            'error' => 'This time slot is already booked.'
        ]);
        exit;
    }
    
    // If the client is logged in, check if they already have an appointment at this time slot
    $clientHasAppointment = false;
    if (isset($_POST['client_id']) && $_POST['client_id']) {
        $clientId = $_POST['client_id'];
        
        // Get client info
        $clientQuery = "SELECT full_name FROM clients WHERE id = ?";
        $clientStmt = $con->prepare($clientQuery);
        $clientStmt->execute([$clientId]);
        $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            $clientName = $client['full_name'];
            
            // Check if this client already has an appointment at this time slot
            $existingQuery = "SELECT COUNT(*) as existing_count FROM appointments 
                            WHERE schedule_id = ? AND appointment_time = ? 
                            AND patient_name = ? AND status != 'cancelled'
                            FOR UPDATE";
            $existingStmt = $con->prepare($existingQuery);
            $existingStmt->execute([$scheduleId, $appointmentTime, $clientName]);
            $clientHasAppointment = ($existingStmt->fetch(PDO::FETCH_ASSOC)['existing_count'] > 0);
        }
    }
    
    // Slot is available if there are no appointments and the client doesn't already have one
    $isAvailable = ($slotCount == 0) && !$clientHasAppointment;
    
    // Update the appointment_slots table to reflect the current status
    if ($slotExists) {
        // Only update if the status has changed
        if ($slotExists['is_booked'] != ($slotCount > 0 ? 1 : 0)) {
            $updateSlotQuery = "UPDATE appointment_slots 
                              SET is_booked = ? 
                              WHERE id = ?";
            $updateSlotStmt = $con->prepare($updateSlotQuery);
            $updateSlotStmt->execute([($slotCount > 0 ? 1 : 0), $slotExists['id']]);
        }
    } else {
        // Create the slot if it doesn't exist
        $createSlotQuery = "INSERT INTO appointment_slots 
                          (schedule_id, slot_time, is_booked) 
                          VALUES (?, ?, ?)";
        $createSlotStmt = $con->prepare($createSlotQuery);
        $createSlotStmt->execute([$scheduleId, $appointmentTime, ($slotCount > 0 ? 1 : 0)]);
    }
    
    // Commit the transaction
    $con->commit();
    
    echo json_encode([
        'is_available' => $isAvailable,
        'max_patients' => 1,
        'booked_count' => $slotCount,
        'client_has_appointment' => $clientHasAppointment,
        'error' => $clientHasAppointment ? 'You already have an appointment booked for this time slot.' : null
    ]);
} catch(PDOException $ex) {
    // Rollback the transaction in case of error
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(['error' => $ex->getMessage()]);
}
?> 