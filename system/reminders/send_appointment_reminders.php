<?php
include '../../config/db_connection.php';
require_once '../../system/phpmailer/system/mailer.php';

// Error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting appointment reminder check at " . date('Y-m-d H:i:s'));

try {
    // Get current date and time
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');
    
    // Find appointments within 30 minutes that haven't received a reminder
    $query = "SELECT a.*, u.display_name as doctor_name, c.email as client_email
            FROM appointments a
            LEFT JOIN users u ON a.doctor_id = u.id
            LEFT JOIN clients c ON c.full_name = a.patient_name
            WHERE a.appointment_date = ?
            AND TIME_TO_SEC(TIMEDIFF(a.appointment_time, ?)) <= 1800
            AND TIME_TO_SEC(TIMEDIFF(a.appointment_time, ?)) > 0
            AND a.status = 'approved'
            AND a.reminder_sent = 0
            AND a.is_archived = 0
            AND c.email IS NOT NULL";
            
    $stmt = $con->prepare($query);
    $stmt->execute([$currentDate, $currentTime, $currentTime]);
    $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $reminderCount = 0;
    
    foreach ($upcomingAppointments as $appointment) {
        // Check if we have the client's email
        if (empty($appointment['client_email'])) {
            error_log("No email found for patient: " . $appointment['patient_name'] . ", Appointment ID: " . $appointment['id']);
            continue;
        }
        
        // Calculate minutes until appointment
        $appointmentTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
        $currentDateTime = strtotime(date('Y-m-d H:i:s'));
        $minutesUntil = round(($appointmentTime - $currentDateTime) / 60);
        
        // Prepare email details
        $formattedDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
        $formattedTime = date('h:i A', strtotime($appointment['appointment_time']));
        $doctorName = $appointment['doctor_name'] ?? 'your doctor';
        
        // Generate email body
        $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>
            <div style='text-align: center; padding: 10px; background-color: #f8f9fa; margin-bottom: 20px;'>
                <h2 style='color: #3699FF; margin: 0;'>Appointment Reminder</h2>
            </div>
            
            <p>Dear <strong>{$appointment['patient_name']}</strong>,</p>
            
            <p>This is a friendly reminder that your appointment at Mamatid Health Center is scheduled in approximately <strong>{$minutesUntil} minutes</strong>.</p>
            
            <div style='background-color: #f1f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #333;'>Appointment Details:</h3>
                <p><strong>Date:</strong> {$formattedDate}</p>
                <p><strong>Time:</strong> {$formattedTime}</p>
                <p><strong>Doctor:</strong> {$doctorName}</p>
                <p><strong>Reason for Visit:</strong> {$appointment['reason']}</p>
            </div>
            
            <p>Please arrive a few minutes early to complete any necessary paperwork.</p>";
            
        // Add appointment link if token is available
        if (!empty($appointment['view_token'])) {
            $viewUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 3) . "/actions/admin_generate_appointment_pdf.php?token=" . $appointment['view_token'];
            $emailBody .= "<p>View or download your appointment details: <a href='{$viewUrl}'>Click here</a></p>";
        }
            
        $emailBody .= "
            <p>If you need to reschedule or cancel, please contact us as soon as possible.</p>
            
            <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;'>
                <p style='font-size: 12px; color: #777;'>Mamatid Health Center<br>
                Email: clinic@mamatidhealth.com<br>
                Tel: (02) 888-7777</p>
            </div>
        </div>";
        
        // Send email
        $emailResult = sendEmail(
            $appointment['client_email'],
            '🕒 Reminder: Your Appointment in ' . $minutesUntil . ' Minutes - Mamatid Health Center',
            $emailBody,
            $appointment['patient_name']
        );
        
        // Update reminder status in database
        if ($emailResult['success']) {
            $updateQuery = "UPDATE appointments SET reminder_sent = 1 WHERE id = ?";
            $updateStmt = $con->prepare($updateQuery);
            $updateStmt->execute([$appointment['id']]);
            $reminderCount++;
            error_log("Reminder sent for Appointment ID: " . $appointment['id'] . ", Patient: " . $appointment['patient_name']);
        } else {
            error_log("Failed to send reminder for Appointment ID: " . $appointment['id'] . ", Error: " . $emailResult['message']);
        }
    }
    
    error_log("Reminder process completed. Sent {$reminderCount} reminders out of " . count($upcomingAppointments) . " eligible appointments.");
    
} catch(PDOException $ex) {
    error_log("Database error in appointment reminders: " . $ex->getMessage());
} catch(Exception $ex) {
    error_log("General error in appointment reminders: " . $ex->getMessage());
}
?> 
