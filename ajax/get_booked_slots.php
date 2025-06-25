<?php
include '../config/connection.php';

// Check if schedule_id is provided
if (!isset($_POST['schedule_id'])) {
    echo json_encode(['error' => 'Schedule ID is required']);
    exit;
}

$scheduleId = $_POST['schedule_id'];

try {
    // Start a transaction
    $con->beginTransaction();
    
    // First get the schedule details with a lock
    $scheduleQuery = "SELECT schedule_date, start_time, end_time, time_slot_minutes 
                     FROM doctor_schedules 
                     WHERE id = ? 
                     FOR UPDATE";
    $scheduleStmt = $con->prepare($scheduleQuery);
    $scheduleStmt->execute([$scheduleId]);
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        $con->rollBack();
        echo json_encode(['error' => 'Invalid schedule ID']);
        exit;
    }
    
    // Set max_patients to 1 to enforce one appointment per slot
    $maxPatients = 1;
    
    // First, check the appointment_slots table for accurate booking information
    $slotsQuery = "SELECT slot_time, is_booked, appointment_id 
                  FROM appointment_slots
                  WHERE schedule_id = ?
                  FOR UPDATE";
    $slotsStmt = $con->prepare($slotsQuery);
    $slotsStmt->execute([$scheduleId]);
    
    $slotStatuses = [];
    while ($slotRow = $slotsStmt->fetch(PDO::FETCH_ASSOC)) {
        $slotStatuses[$slotRow['slot_time']] = [
            'is_booked' => $slotRow['is_booked'],
            'appointment_id' => $slotRow['appointment_id']
        ];
    }
    
    // Get booked slots for this schedule with a lock to prevent race conditions
    $query = "SELECT appointment_time, COUNT(*) as slot_count, 
              CASE WHEN DATE(CONCAT(?, ' ', appointment_time)) < CURDATE() THEN 1 ELSE 0 END as is_past
              FROM appointments 
              WHERE schedule_id = ? AND status != 'cancelled'
              GROUP BY appointment_time, is_past
              FOR UPDATE";
    $stmt = $con->prepare($query);
    $stmt->execute([$schedule['schedule_date'], $scheduleId]);
    
    $bookedSlots = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bookedSlots[$row['appointment_time']] = [
            'count' => $row['slot_count'],
            'is_full' => ($row['slot_count'] >= 1), // Consider slot full if there's at least one appointment
            'is_past' => ($row['is_past'] == 1)
        ];
    }
    
    // Check if this client already has appointments in this schedule
    $clientAppointments = [];
    if (isset($_POST['client_id']) && $_POST['client_id']) {
        $clientId = $_POST['client_id'];
        
        // Get client info
        $clientQuery = "SELECT full_name FROM clients WHERE id = ?";
        $clientStmt = $con->prepare($clientQuery);
        $clientStmt->execute([$clientId]);
        $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            $clientName = $client['full_name'];
            
            // Get this client's appointments for this schedule
            $clientApptsQuery = "SELECT appointment_time 
                               FROM appointments 
                               WHERE schedule_id = ? 
                               AND patient_name = ? 
                               AND status != 'cancelled'";
            $clientApptsStmt = $con->prepare($clientApptsQuery);
            $clientApptsStmt->execute([$scheduleId, $clientName]);
            
            while ($row = $clientApptsStmt->fetch(PDO::FETCH_ASSOC)) {
                $clientAppointments[] = $row['appointment_time'];
            }
        }
    }
    
    // Make sure all appointment slots are properly represented in the appointment_slots table
    // This ensures we have a record for each time slot, even if no appointments exist yet
    
    // First, generate all possible time slots for this schedule
    $startTime = strtotime($schedule['schedule_date'] . ' ' . $schedule['start_time']);
    $endTime = strtotime($schedule['schedule_date'] . ' ' . $schedule['end_time']);
    $timeSlotMinutes = $schedule['time_slot_minutes'];
    
    $currentTime = $startTime;
    $allTimeSlots = [];
    
    while ($currentTime < $endTime) {
        $timeString = date('H:i:s', $currentTime);
        $allTimeSlots[] = $timeString;
        $currentTime += ($timeSlotMinutes * 60);
    }
    
    // Now make sure all these slots exist in the appointment_slots table
    // And update the is_booked flag based on actual appointments
    foreach ($allTimeSlots as $timeSlot) {
        $slotExists = false;
        $isBooked = 0;
        $appointmentId = null;
        
        // Check if this time slot is in the past (for today's date)
        $slotDateTime = strtotime($schedule['schedule_date'] . ' ' . $timeSlot);
        $isPast = $slotDateTime < time();
        
        // Check if this slot exists in the appointment_slots table
        $checkSlotQuery = "SELECT id, is_booked, appointment_id FROM appointment_slots 
                         WHERE schedule_id = ? AND slot_time = ?";
        $checkSlotStmt = $con->prepare($checkSlotQuery);
        $checkSlotStmt->execute([$scheduleId, $timeSlot]);
        $slotData = $checkSlotStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($slotData) {
            $slotExists = true;
            $slotId = $slotData['id'];
            
            // Check if there are actual appointments for this slot
            if (isset($bookedSlots[$timeSlot])) {
                $isBooked = ($bookedSlots[$timeSlot]['count'] > 0) ? 1 : 0;
                
                // If the is_booked status doesn't match the actual appointments, update it
                if ($slotData['is_booked'] != $isBooked) {
                    $updateSlotQuery = "UPDATE appointment_slots 
                                      SET is_booked = ? 
                                      WHERE id = ?";
                    $updateSlotStmt = $con->prepare($updateSlotQuery);
                    $updateSlotStmt->execute([$isBooked, $slotId]);
                }
            }
        } else {
            // Slot doesn't exist, create it
            // Check if there are actual appointments for this slot
            if (isset($bookedSlots[$timeSlot])) {
                $isBooked = ($bookedSlots[$timeSlot]['count'] > 0) ? 1 : 0;
            }
            
            $createSlotQuery = "INSERT INTO appointment_slots 
                              (schedule_id, slot_time, is_booked) 
                              VALUES (?, ?, ?)";
            $createSlotStmt = $con->prepare($createSlotQuery);
            $createSlotStmt->execute([$scheduleId, $timeSlot, $isBooked]);
        }
        
        // Update the bookedSlots array if it doesn't have this time slot
        if (!isset($bookedSlots[$timeSlot])) {
            $bookedSlots[$timeSlot] = [
                'count' => 0,
                'is_full' => false,
                'is_past' => $isPast
            ];
        } else {
            // Add is_past flag if it doesn't exist
            if (!isset($bookedSlots[$timeSlot]['is_past'])) {
                $bookedSlots[$timeSlot]['is_past'] = $isPast;
            }
        }
    }
    
    // Commit the transaction
    $con->commit();
    
    // Return both booked slots and max_patients
    echo json_encode([
        'booked_slots' => $bookedSlots,
        'max_patients' => $maxPatients,
        'client_appointments' => $clientAppointments,
        'schedule' => $schedule,
        'slot_statuses' => $slotStatuses
    ]);
} catch(PDOException $ex) {
    // Rollback the transaction in case of error
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(['error' => $ex->getMessage()]);
}
?> 