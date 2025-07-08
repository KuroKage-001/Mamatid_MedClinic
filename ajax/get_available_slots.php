<?php
/**
 * Get Available Time Slots
 * 
 * This file handles fetching available time slots for a specific provider
 * and date for walk-in appointments.
 */

include '../config/db_connection.php';
require_once '../common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check permission
try {
    requireRole(['admin', 'health_worker', 'doctor']);
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get parameters from request
$providerId = $_POST['provider_id'] ?? '';
$providerType = $_POST['provider_type'] ?? '';
$appointmentDate = $_POST['appointment_date'] ?? '';

if (empty($providerId) || empty($providerType) || empty($appointmentDate)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $availableSlots = [];
    
    if ($providerType == 'health_worker') {
        // Get health worker schedules for the specified date
        $scheduleQuery = "SELECT id, start_time, end_time, time_slot_minutes 
                         FROM admin_hw_schedules 
                         WHERE staff_id = ? AND schedule_date = ? AND is_approved = 1";
        $scheduleStmt = $con->prepare($scheduleQuery);
        $scheduleStmt->execute([$providerId, $appointmentDate]);
        $schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($schedules as $schedule) {
            // Get all time slots for this schedule
            $slotsQuery = "SELECT slot_time, is_booked 
                          FROM admin_hw_appointment_slots 
                          WHERE schedule_id = ? 
                          ORDER BY slot_time ASC";
            $slotsStmt = $con->prepare($slotsQuery);
            $slotsStmt->execute([$schedule['id']]);
            $slots = $slotsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no slots exist, generate them
            if (empty($slots)) {
                $startDateTime = strtotime($appointmentDate . ' ' . $schedule['start_time']);
                $endDateTime = strtotime($appointmentDate . ' ' . $schedule['end_time']);
                $slotDuration = $schedule['time_slot_minutes'] * 60; // Convert to seconds
                
                $currentSlot = $startDateTime;
                while ($currentSlot < $endDateTime) {
                    $slotTime = date('H:i:s', $currentSlot);
                    
                    // Check if this time slot is already booked
                    $bookedQuery = "SELECT id FROM admin_clients_appointments 
                                   WHERE schedule_id = ? AND appointment_time = ? 
                                   AND status != 'cancelled' AND is_archived = 0";
                    $bookedStmt = $con->prepare($bookedQuery);
                    $bookedStmt->execute([$schedule['id'], $slotTime]);
                    $isBooked = $bookedStmt->rowCount() > 0;
                    
                    if (!$isBooked) {
                        $availableSlots[] = [
                            'schedule_id' => $schedule['id'],
                            'time' => $slotTime,
                            'formatted_time' => date('h:i A', $currentSlot)
                        ];
                    }
                    
                    $currentSlot += $slotDuration;
                }
            } else {
                // Use existing slots
                foreach ($slots as $slot) {
                    if (!$slot['is_booked']) {
                        $slotDateTime = strtotime($appointmentDate . ' ' . $slot['slot_time']);
                        $availableSlots[] = [
                            'schedule_id' => $schedule['id'],
                            'time' => $slot['slot_time'],
                            'formatted_time' => date('h:i A', $slotDateTime)
                        ];
                    }
                }
            }
        }
        
    } elseif ($providerType == 'doctor') {
        // Get doctor schedules for the specified date
        $scheduleQuery = "SELECT id, start_time, end_time, time_slot_minutes 
                         FROM admin_doctor_schedules 
                         WHERE doctor_id = ? AND schedule_date = ? AND is_approved = 1 
                         AND (is_deleted = 0 OR is_deleted IS NULL)";
        $scheduleStmt = $con->prepare($scheduleQuery);
        $scheduleStmt->execute([$providerId, $appointmentDate]);
        $schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($schedules as $schedule) {
            // Get all time slots for this schedule
            $slotsQuery = "SELECT slot_time, is_booked 
                          FROM admin_doctor_appointment_slots 
                          WHERE schedule_id = ? 
                          ORDER BY slot_time ASC";
            $slotsStmt = $con->prepare($slotsQuery);
            $slotsStmt->execute([$schedule['id']]);
            $slots = $slotsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no slots exist, generate them
            if (empty($slots)) {
                $startDateTime = strtotime($appointmentDate . ' ' . $schedule['start_time']);
                $endDateTime = strtotime($appointmentDate . ' ' . $schedule['end_time']);
                $slotDuration = $schedule['time_slot_minutes'] * 60; // Convert to seconds
                
                $currentSlot = $startDateTime;
                while ($currentSlot < $endDateTime) {
                    $slotTime = date('H:i:s', $currentSlot);
                    
                    // Check if this time slot is already booked
                    $bookedQuery = "SELECT id FROM admin_clients_appointments 
                                   WHERE schedule_id = ? AND appointment_time = ? 
                                   AND status != 'cancelled' AND is_archived = 0";
                    $bookedStmt = $con->prepare($bookedQuery);
                    $bookedStmt->execute([$schedule['id'], $slotTime]);
                    $isBooked = $bookedStmt->rowCount() > 0;
                    
                    if (!$isBooked) {
                        $availableSlots[] = [
                            'schedule_id' => $schedule['id'],
                            'time' => $slotTime,
                            'formatted_time' => date('h:i A', $currentSlot)
                        ];
                    }
                    
                    $currentSlot += $slotDuration;
                }
            } else {
                // Use existing slots
                foreach ($slots as $slot) {
                    if (!$slot['is_booked']) {
                        $slotDateTime = strtotime($appointmentDate . ' ' . $slot['slot_time']);
                        $availableSlots[] = [
                            'schedule_id' => $schedule['id'],
                            'time' => $slot['slot_time'],
                            'formatted_time' => date('h:i A', $slotDateTime)
                        ];
                    }
                }
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid provider type']);
        exit;
    }
    
    // Sort slots by time
    usort($availableSlots, function($a, $b) {
        return strcmp($a['time'], $b['time']);
    });
    
    echo json_encode([
        'success' => true,
        'slots' => $availableSlots,
        'count' => count($availableSlots)
    ]);
    
} catch (PDOException $ex) {
    error_log("Error fetching available slots: " . $ex->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching available slots. Please try again.'
    ]);
}
?> 