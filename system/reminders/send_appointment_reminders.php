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
        $doctorName = $appointment['doctor_name'] ?? 'your healthcare provider';
        
        // Generate email body
        $emailBody = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Appointment Reminder - Mamatid Health Center</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333333;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    border: 1px solid #dddddd;
                    border-radius: 5px;
                    background-color: #ffffff;
                }
                .header {
                    background-color: #2D5A27;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    padding: 30px;
                    background-color: #ffffff;
                }
                .appointment-details {
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-radius: 5px;
                    margin: 20px 0;
                    border-left: 4px solid #2D5A27;
                }
                .footer {
                    text-align: center;
                    font-size: 12px;
                    color: #666666;
                    margin-top: 30px;
                    border-top: 1px solid #eeeeee;
                    padding-top: 20px;
                }
                .reminder-alert {
                    background-color: #fff3cd;
                    border: 1px solid #ffeeba;
                    color: #856404;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                    text-align: center;
                    font-weight: bold;
                }
                .checklist {
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2 style="margin:0;">Mamatid Health Center</h2>
                    <p style="margin:5px 0 0 0;">Appointment Reminder</p>
                </div>
                <div class="content">
                    <p>Dear ' . htmlspecialchars($appointment['patient_name']) . ',</p>
                    
                    <div class="reminder-alert">
                        Your appointment is scheduled in approximately ' . $minutesUntil . ' minutes
            </div>
            
                    <div class="appointment-details">
                        <h3 style="margin-top:0;color:#2D5A27;">Appointment Information</h3>
                        <p><strong>Healthcare Provider:</strong> ' . htmlspecialchars($doctorName) . '</p>
                        <p><strong>Date:</strong> ' . htmlspecialchars($formattedDate) . '</p>
                        <p><strong>Time:</strong> ' . htmlspecialchars($formattedTime) . '</p>
                        <p><strong>Purpose of Visit:</strong> ' . htmlspecialchars($appointment['reason']) . '</p>
            </div>
            
                    <div class="checklist">
                        <h4 style="margin-top:0;color:#2D5A27;">Final Checklist</h4>
                        <ul style="padding-left:20px;margin:10px 0;">
                            <li>Valid ID and medical insurance card (if applicable)</li>
                            <li>List of current medications</li>
                            <li>Relevant medical records or test results</li>
                            <li>Face mask</li>
                            <li>Payment method (if applicable)</li>
                        </ul>
                    </div>';
            
        // Add appointment link if token is available
        if (!empty($appointment['view_token'])) {
            $viewUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 3) . "/actions/admin_generate_appointment_pdf.php?token=" . $appointment['view_token'];
            $emailBody .= '
                    <p style="text-align:center;">
                        <a href="' . htmlspecialchars($viewUrl) . '" style="display:inline-block;background-color:#2D5A27;color:white;padding:12px 25px;text-decoration:none;border-radius:5px;margin:20px 0;font-weight:bold;">
                            View Appointment Details
                        </a>
                    </p>';
        }
        
        $emailBody .= '
                    <p>If you are experiencing any COVID-19 symptoms (fever, cough, etc.), please contact us before coming to the clinic.</p>
                    
                    <p>For any urgent concerns, please contact us at:</p>
                    <ul style="padding-left:20px;margin:10px 0;">
                        <li>Phone: (02) 888-7777</li>
                        <li>Email: appointments@mamatidhealth.com</li>
                    </ul>
                    
                    <p>We look forward to serving you.</p>
                    
                    <p>Best regards,<br>
                    <strong>Mamatid Health Center</strong></p>
                </div>
                <div class="footer">
                    <p>This is an automated reminder. Please do not reply to this email.</p>
                    <p>&copy; ' . date('Y') . ' Mamatid Health Center. All rights reserved.</p>
                    <p>Address: 123 Mamatid Street, Cabuyao City, Laguna</p>
                </div>
            </div>
        </body>
        </html>';
        
        // Send email
        $emailResult = sendEmail(
            $appointment['client_email'],
            '🕒 Reminder: Your Appointment Today at Mamatid Health Center',
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
