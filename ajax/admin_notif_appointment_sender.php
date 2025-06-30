<?php
// Include required files
include '../config/db_connection.php';
include '../system/phpmailer/system/mailer.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if appointment ID is provided
if (!isset($_POST['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
    exit;
}

$appointmentId = intval($_POST['appointment_id']);

try {
    // Get appointment details including patient information
    $query = "SELECT a.*, c.email, c.full_name, u.display_name AS doctor_name 
              FROM appointments a
              LEFT JOIN clients c ON a.patient_name = c.full_name
              LEFT JOIN users u ON a.doctor_id = u.id
              WHERE a.id = ?";
    
    $stmt = $con->prepare($query);
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    // Check if patient email is available
    if (empty($appointment['email'])) {
        echo json_encode(['success' => false, 'message' => 'Patient email not found']);
        exit;
    }
    
    // Format appointment date and time
    $appointmentDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
    $appointmentTime = date('h:i A', strtotime($appointment['appointment_time']));
    
    // Prepare email content
    $subject = "Appointment Reminder: " . $appointmentDate . " at " . $appointmentTime;
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #3699FF; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            .info-item { margin-bottom: 10px; }
            .info-label { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Appointment Reminder</h2>
            </div>
            <div class='content'>
                <p>Dear " . $appointment['patient_name'] . ",</p>
                
                <p>This is a friendly reminder about your upcoming appointment at Mamatid Health Center:</p>
                
                <div class='info-item'>
                    <span class='info-label'>Date:</span> " . $appointmentDate . "
                </div>
                <div class='info-item'>
                    <span class='info-label'>Time:</span> " . $appointmentTime . "
                </div>
                <div class='info-item'>
                    <span class='info-label'>Doctor:</span> Dr. " . $appointment['doctor_name'] . "
                </div>
                <div class='info-item'>
                    <span class='info-label'>Reason:</span> " . $appointment['reason'] . "
                </div>
                
                <p>Please arrive 15 minutes before your appointment time. If you need to reschedule or cancel, please contact us as soon as possible.</p>
                
                <p>Thank you for choosing Mamatid Health Center for your healthcare needs.</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Mamatid Health Center | Contact: (02) 8-123-4567</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Plain text alternative
    $plainText = "APPOINTMENT REMINDER\n\n" .
                 "Dear " . $appointment['patient_name'] . ",\n\n" .
                 "This is a friendly reminder about your upcoming appointment at Mamatid Health Center:\n\n" .
                 "Date: " . $appointmentDate . "\n" .
                 "Time: " . $appointmentTime . "\n" .
                 "Doctor: Dr. " . $appointment['doctor_name'] . "\n" .
                 "Reason: " . $appointment['reason'] . "\n\n" .
                 "Please arrive 15 minutes before your appointment time. If you need to reschedule or cancel, " .
                 "please contact us as soon as possible.\n\n" .
                 "Thank you for choosing Mamatid Health Center for your healthcare needs.\n\n" .
                 "This is an automated message. Please do not reply to this email.\n" .
                 "Mamatid Health Center | Contact: (02) 8-123-4567";
    
    // Send the email
    $emailSent = sendEmail($appointment['email'], $subject, $body, $appointment['patient_name'], $plainText);
    
    if ($emailSent) {
        // Update the database to mark email as sent
        $updateQuery = "UPDATE appointments SET email_sent = 1, updated_at = NOW() WHERE id = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->execute([$appointmentId]);
        
        echo json_encode(['success' => true, 'message' => 'Appointment notification sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email notification']);
    }
    
} catch (PDOException $ex) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $ex->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} 