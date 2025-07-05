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
    $subject = "Appointment Information: " . $appointmentDate;
    
    $body = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Appointment Information - Mamatid Health Center</title>
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
            .info-section {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .cta-button {
                display: inline-block;
                background-color: #2D5A27;
                color: white;
                padding: 12px 25px;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2 style="margin:0;">Mamatid Health Center</h2>
                <p style="margin:5px 0 0 0;">Appointment Information</p>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($appointment['patient_name']) . ',</p>
                
                <p>This is a notification regarding your appointment at Mamatid Health Center.</p>
                
                <div class="appointment-details">
                    <h3 style="margin-top:0;color:#2D5A27;">Appointment Details</h3>
                    <p><strong>Healthcare Provider:</strong> Dr. ' . htmlspecialchars($appointment['doctor_name']) . '</p>
                    <p><strong>Date:</strong> ' . htmlspecialchars($appointmentDate) . '</p>
                    <p><strong>Time:</strong> ' . htmlspecialchars($appointmentTime) . '</p>
                    <p><strong>Purpose of Visit:</strong> ' . htmlspecialchars($appointment['reason']) . '</p>
                </div>
                
                <div class="info-section">
                    <h4 style="margin-top:0;color:#2D5A27;">Important Reminders</h4>
                    <ul style="padding-left:20px;margin:10px 0;">
                        <li>Please arrive 15 minutes before your scheduled time</li>
                        <li>Bring your valid ID and medical insurance card (if applicable)</li>
                        <li>Bring a list of your current medications</li>
                        <li>Bring any relevant medical records or test results</li>
                        <li>Wear a face mask within the facility</li>
                    </ul>
                </div>
                
                <div class="info-section">
                    <h4 style="margin-top:0;color:#2D5A27;">Health and Safety Protocol</h4>
                    <p>If you are experiencing any of the following symptoms, please contact us before your visit:</p>
                    <ul style="padding-left:20px;margin:10px 0;">
                        <li>Fever or chills</li>
                        <li>Cough or sore throat</li>
                        <li>Difficulty breathing</li>
                        <li>Loss of taste or smell</li>
                    </ul>
                </div>
                
                <p>For any questions or concerns, please contact us at:</p>
                <ul style="padding-left:20px;margin:10px 0;">
                    <li>Phone: (02) 888-7777</li>
                    <li>Email: appointments@mamatidhealth.com</li>
                </ul>
                
                <p>Thank you for choosing Mamatid Health Center for your healthcare needs.</p>
                
                <p>Best regards,<br>
                <strong>Mamatid Health Center Team</strong></p>
            </div>
            <div class="footer">
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; ' . date('Y') . ' Mamatid Health Center. All rights reserved.</p>
                <p>Address: 123 Mamatid Street, Cabuyao City, Laguna</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Plain text alternative
    $plainText = "APPOINTMENT INFORMATION\n\n" .
                 "Dear " . $appointment['patient_name'] . ",\n\n" .
                 "This is a notification regarding your appointment at Mamatid Health Center.\n\n" .
                 "APPOINTMENT DETAILS:\n" .
                 "Healthcare Provider: Dr. " . $appointment['doctor_name'] . "\n" .
                 "Date: " . $appointmentDate . "\n" .
                 "Time: " . $appointmentTime . "\n" .
                 "Purpose of Visit: " . $appointment['reason'] . "\n\n" .
                 "IMPORTANT REMINDERS:\n" .
                 "- Please arrive 15 minutes before your scheduled time\n" .
                 "- Bring your valid ID and medical insurance card (if applicable)\n" .
                 "- Bring a list of your current medications\n" .
                 "- Bring any relevant medical records or test results\n" .
                 "- Wear a face mask within the facility\n\n" .
                 "For any questions or concerns, please contact us:\n" .
                 "Phone: (02) 888-7777\n" .
                 "Email: appointments@mamatidhealth.com\n\n" .
                 "Thank you for choosing Mamatid Health Center for your healthcare needs.\n\n" .
                 "Best regards,\n" .
                 "Mamatid Health Center Team\n\n" .
                 "This is an automated message. Please do not reply to this email.\n" .
                 "Mamatid Health Center | 123 Mamatid Street, Cabuyao City, Laguna";
    
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