<?php
/**
 * Appointment Slots Manager
 * 
 * This file contains functions to manage appointment slots for the booking system
 */

// Determine the correct path to connection.php
if (file_exists('../config/connection.php')) {
    include_once '../config/connection.php';
} else if (file_exists('config/connection.php')) {
    include_once 'config/connection.php';
} else {
    die("Could not find connection.php");
}

/**
 * Create appointment slots for a doctor schedule
 * 
 * @param int $scheduleId The schedule ID
 * @return bool True if successful, false otherwise
 */
function createAppointmentSlots($scheduleId) {
    global $con;
    
    try {
        // Get schedule details
        $query = "SELECT * FROM doctor_schedules WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$scheduleId]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$schedule) {
            return false;
        }
        
        // Start transaction
        $con->beginTransaction();
        
        // Delete any existing slots for this schedule
        $deleteQuery = "DELETE FROM appointment_slots WHERE schedule_id = ?";
        $deleteStmt = $con->prepare($deleteQuery);
        $deleteStmt->execute([$scheduleId]);
        
        // Generate time slots
        $startTime = strtotime($schedule['start_time']);
        $endTime = strtotime($schedule['end_time']);
        $timeSlotMinutes = $schedule['time_slot_minutes'];
        
        $currentTime = $startTime;
        
        while ($currentTime < $endTime) {
            $slotTime = date('H:i:s', $currentTime);
            
            // Insert slot
            $insertQuery = "INSERT INTO appointment_slots (schedule_id, slot_time, is_booked) 
                          VALUES (?, ?, 0)";
            $insertStmt = $con->prepare($insertQuery);
            $insertStmt->execute([$scheduleId, $slotTime]);
            
            // Move to next slot
            $currentTime = strtotime("+$timeSlotMinutes minutes", $currentTime);
        }
        
        $con->commit();
        return true;
    } catch (PDOException $e) {
        if ($con->inTransaction()) {
            $con->rollback();
        }
        error_log("Error creating appointment slots: " . $e->getMessage());
        return false;
    }
}

/**
 * Update appointment slot status when an appointment is booked
 * 
 * @param int $scheduleId The schedule ID
 * @param string $slotTime The time slot (HH:MM:SS)
 * @param int $appointmentId The appointment ID
 * @return bool True if successful, false otherwise
 */
function bookAppointmentSlot($scheduleId, $slotTime, $appointmentId) {
    global $con;
    
    try {
        $con->beginTransaction();
        
        // Check if slot exists and is not booked
        $checkQuery = "SELECT id, is_booked FROM appointment_slots 
                     WHERE schedule_id = ? AND slot_time = ?";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->execute([$scheduleId, $slotTime]);
        $slot = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$slot) {
            // Slot doesn't exist, create it
            $createQuery = "INSERT INTO appointment_slots (schedule_id, slot_time, is_booked, appointment_id) 
                          VALUES (?, ?, 1, ?)";
            $createStmt = $con->prepare($createQuery);
            $createStmt->execute([$scheduleId, $slotTime, $appointmentId]);
        } else if ($slot['is_booked']) {
            // Slot is already booked
            $con->rollback();
            return false;
        } else {
            // Update existing slot
            $updateQuery = "UPDATE appointment_slots SET is_booked = 1, appointment_id = ? 
                          WHERE id = ?";
            $updateStmt = $con->prepare($updateQuery);
            $updateStmt->execute([$appointmentId, $slot['id']]);
        }
        
        $con->commit();
        return true;
    } catch (PDOException $e) {
        if ($con->inTransaction()) {
            $con->rollback();
        }
        error_log("Error booking appointment slot: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a time slot is available
 * 
 * @param int $scheduleId The schedule ID
 * @param string $slotTime The time slot (HH:MM:SS)
 * @return bool True if available, false if booked
 */
function isSlotAvailable($scheduleId, $slotTime) {
    global $con;
    
    try {
        // Check appointment_slots table first
        $slotQuery = "SELECT is_booked FROM appointment_slots 
                    WHERE schedule_id = ? AND slot_time = ?";
        $slotStmt = $con->prepare($slotQuery);
        $slotStmt->execute([$scheduleId, $slotTime]);
        $slot = $slotStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($slot) {
            // Slot exists in the table
            return !$slot['is_booked'];
        }
        
        // If slot doesn't exist in the table, check appointments table
        $appointmentQuery = "SELECT COUNT(*) as count FROM appointments 
                           WHERE schedule_id = ? AND appointment_time = ? AND status != 'cancelled'";
        $appointmentStmt = $con->prepare($appointmentQuery);
        $appointmentStmt->execute([$scheduleId, $slotTime]);
        $appointmentCount = $appointmentStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Get max patients for this schedule
        $scheduleQuery = "SELECT max_patients FROM doctor_schedules WHERE id = ?";
        $scheduleStmt = $con->prepare($scheduleQuery);
        $scheduleStmt->execute([$scheduleId]);
        $maxPatients = $scheduleStmt->fetch(PDO::FETCH_ASSOC)['max_patients'];
        
        return $appointmentCount < $maxPatients;
    } catch (PDOException $e) {
        error_log("Error checking slot availability: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all booked slots for a schedule
 * 
 * @param int $scheduleId The schedule ID
 * @return array Array of booked slots
 */
function getBookedSlots($scheduleId) {
    global $con;
    
    try {
        // Get slots from appointment_slots table
        $slotQuery = "SELECT slot_time FROM appointment_slots 
                    WHERE schedule_id = ? AND is_booked = 1";
        $slotStmt = $con->prepare($slotQuery);
        $slotStmt->execute([$scheduleId]);
        $bookedSlots = $slotStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get slots from appointments table
        $appointmentQuery = "SELECT appointment_time, COUNT(*) as count FROM appointments 
                           WHERE schedule_id = ? AND status != 'cancelled'
                           GROUP BY appointment_time";
        $appointmentStmt = $con->prepare($appointmentQuery);
        $appointmentStmt->execute([$scheduleId]);
        $appointmentSlots = $appointmentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get max patients for this schedule
        $scheduleQuery = "SELECT max_patients FROM doctor_schedules WHERE id = ?";
        $scheduleStmt = $con->prepare($scheduleQuery);
        $scheduleStmt->execute([$scheduleId]);
        $maxPatients = $scheduleStmt->fetch(PDO::FETCH_ASSOC)['max_patients'];
        
        // Merge the results
        $result = [];
        foreach ($appointmentSlots as $slot) {
            $time = $slot['appointment_time'];
            $count = $slot['count'];
            $result[$time] = [
                'count' => $count,
                'is_full' => ($count >= $maxPatients)
            ];
        }
        
        // Add slots from appointment_slots table
        foreach ($bookedSlots as $time) {
            if (!isset($result[$time])) {
                $result[$time] = [
                    'count' => 1,
                    'is_full' => true
                ];
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error getting booked slots: " . $e->getMessage());
        return [];
    }
}

/**
 * Cancel an appointment slot
 * 
 * @param int $appointmentId The appointment ID
 * @return bool True if successful, false otherwise
 */
function cancelAppointmentSlot($appointmentId) {
    global $con;
    
    try {
        $con->beginTransaction();
        
        // Get appointment details
        $appointmentQuery = "SELECT schedule_id, appointment_time FROM appointments WHERE id = ?";
        $appointmentStmt = $con->prepare($appointmentQuery);
        $appointmentStmt->execute([$appointmentId]);
        $appointment = $appointmentStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            $con->rollback();
            return false;
        }
        
        // Update appointment_slots table
        $updateQuery = "UPDATE appointment_slots SET is_booked = 0, appointment_id = NULL 
                      WHERE schedule_id = ? AND slot_time = ? AND appointment_id = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->execute([
            $appointment['schedule_id'], 
            $appointment['appointment_time'], 
            $appointmentId
        ]);
        
        $con->commit();
        return true;
    } catch (PDOException $e) {
        if ($con->inTransaction()) {
            $con->rollback();
        }
        error_log("Error cancelling appointment slot: " . $e->getMessage());
        return false;
    }
}

/**
 * Sync appointment slots with appointments table
 * 
 * This function ensures that all appointments have corresponding slots in the appointment_slots table
 * 
 * @return bool True if successful, false otherwise
 */
function syncAppointmentSlots() {
    global $con;
    
    try {
        $con->beginTransaction();
        
        // Get all appointments
        $appointmentQuery = "SELECT id, schedule_id, appointment_time FROM appointments 
                           WHERE status != 'cancelled'";
        $appointmentStmt = $con->prepare($appointmentQuery);
        $appointmentStmt->execute();
        $appointments = $appointmentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($appointments as $appointment) {
            // Check if slot exists
            $slotQuery = "SELECT id FROM appointment_slots 
                        WHERE schedule_id = ? AND slot_time = ?";
            $slotStmt = $con->prepare($slotQuery);
            $slotStmt->execute([$appointment['schedule_id'], $appointment['appointment_time']]);
            $slot = $slotStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$slot) {
                // Create slot
                $createQuery = "INSERT INTO appointment_slots (schedule_id, slot_time, is_booked, appointment_id) 
                              VALUES (?, ?, 1, ?)";
                $createStmt = $con->prepare($createQuery);
                $createStmt->execute([
                    $appointment['schedule_id'], 
                    $appointment['appointment_time'], 
                    $appointment['id']
                ]);
            } else {
                // Update slot
                $updateQuery = "UPDATE appointment_slots SET is_booked = 1, appointment_id = ? 
                              WHERE id = ?";
                $updateStmt = $con->prepare($updateQuery);
                $updateStmt->execute([$appointment['id'], $slot['id']]);
            }
        }
        
        $con->commit();
        return true;
    } catch (PDOException $e) {
        if ($con->inTransaction()) {
            $con->rollback();
        }
        error_log("Error syncing appointment slots: " . $e->getMessage());
        return false;
    }
}
?>
