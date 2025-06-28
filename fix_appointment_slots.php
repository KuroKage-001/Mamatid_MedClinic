<?php
include './config/db_connection.php';

// Script to generate appointment slots for existing doctor schedules
echo "Starting to generate appointment slots for existing doctor schedules...\n";

try {
    $con->beginTransaction();
    
    // Get all active doctor schedules
    $query = "SELECT * FROM doctor_schedules 
              WHERE is_deleted = 0 OR is_deleted IS NULL
              ORDER BY id ASC";
    $stmt = $con->prepare($query);
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalSchedules = count($schedules);
    $processedSchedules = 0;
    $generatedSlots = 0;
    
    echo "Found {$totalSchedules} schedules to process.\n";
    
    foreach ($schedules as $schedule) {
        $scheduleId = $schedule['id'];
        $dateStr = $schedule['schedule_date'];
        $startTime = $schedule['start_time'];
        $endTime = $schedule['end_time'];
        $timeSlot = $schedule['time_slot_minutes'];
        
        echo "Processing schedule #{$scheduleId} for date {$dateStr}...\n";
        
        // Create appointment slots for this schedule
        $startDateTime = strtotime($dateStr . ' ' . $startTime);
        $endDateTime = strtotime($dateStr . ' ' . $endTime);
        $slotDuration = $timeSlot * 60; // Convert minutes to seconds
        
        $slotsForSchedule = 0;
        
        // Generate slots for the entire schedule duration
        $currentSlot = $startDateTime;
        while ($currentSlot < $endDateTime) {
            $slotTime = date('H:i:s', $currentSlot);
            
            // Check if slot already exists
            $checkSlotQuery = "SELECT id FROM appointment_slots WHERE schedule_id = ? AND slot_time = ?";
            $checkSlotStmt = $con->prepare($checkSlotQuery);
            $checkSlotStmt->execute([$scheduleId, $slotTime]);
            
            if ($checkSlotStmt->rowCount() == 0) {
                // Insert new slot if it doesn't exist
                $insertSlotQuery = "INSERT INTO appointment_slots (schedule_id, slot_time, is_booked) VALUES (?, ?, 0)";
                $insertSlotStmt = $con->prepare($insertSlotQuery);
                $insertSlotStmt->execute([$scheduleId, $slotTime]);
                $generatedSlots++;
                $slotsForSchedule++;
            }
            
            // Move to next slot
            $currentSlot += $slotDuration;
        }
        
        echo "Generated {$slotsForSchedule} slots for schedule #{$scheduleId}.\n";
        $processedSchedules++;
    }
    
    $con->commit();
    
    echo "Successfully processed {$processedSchedules} schedules.\n";
    echo "Generated {$generatedSlots} new appointment slots.\n";
    echo "Script completed successfully.\n";
    
} catch(PDOException $ex) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo "Error: " . $ex->getMessage() . "\n";
}
?> 