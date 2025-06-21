<?php
/**
 * Sync Appointment Slots
 * 
 * This script syncs all existing appointments with the appointment_slots table
 */

include_once '../config/connection.php';
include_once 'manage_appointment_slots.php';

// Check if this is a direct script access
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    // Run the sync
    $result = syncAppointmentSlots();
    
    if ($result) {
        echo "Appointment slots synced successfully!";
    } else {
        echo "Error syncing appointment slots.";
    }
}
?>
