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
    // Get walk-in appointment details including provider information
    $query = "SELECT w.*, u.display_name as provider_name, u.role as provider_role
              FROM admin_walkin_appointments w
              LEFT JOIN admin_user_accounts u ON w.provider_id = u.id
              WHERE w.id = ?";
    
    $stmt = $con->prepare($query);
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Walk-in appointment not found']);
        exit;
    }
    
    // Check if patient email is available
    if (empty($appointment['email'])) {
        echo json_encode(['success' => false, 'message' => 'Patient email not found. Please update the walk-in appointment with an email address.']);
        exit;
    }
    
    // Format appointment date and time
    $appointmentDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
    $appointmentTime = date('h:i A', strtotime($appointment['appointment_time']));
    
    // Format provider title based on role
    $providerTitle = '';
    switch($appointment['provider_role']) {
        case 'doctor':
            $providerTitle = 'Dr. ';
            break;
        case 'health_worker':
            $providerTitle = 'Health Worker ';
            break;
        case 'admin':
            $providerTitle = 'Administrator ';
            break;
        default:
            $providerTitle = '';
    }
    
    // Prepare email content
    $subject = "Walk-in Appointment Information: " . $appointmentDate;
    
    $body = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Walk-in Appointment Information - Mamatid Health Center</title>
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
                background-color: #FFA800;
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }
            .walkin-badge {
                background-color: rgba(255, 255, 255, 0.2);
                color: white;
                padding: 5px 15px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: bold;
                margin-top: 10px;
                display: inline-block;
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
                border-left: 4px solid #FFA800;
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
                background-color: #FFA800;
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
                <p style="margin:5px 0 0 0;">Walk-in Appointment Information</p>
                <div class="walkin-badge">
                    <i class="fas fa-walking"></i> Walk-in Service
                </div>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($appointment['patient_name']) . ',</p>
                
                <p>This is a notification regarding your walk-in appointment at Mamatid Health Center. Your walk-in appointment has been successfully processed and scheduled.</p>
                
                <div class="appointment-details">
                    <h3 style="margin-top:0;color:#FFA800;">Walk-in Appointment Details</h3>
                    <p><strong>Healthcare Provider:</strong> ' . htmlspecialchars($providerTitle . $appointment['provider_name']) . '</p>
                    <p><strong>Date:</strong> ' . htmlspecialchars($appointmentDate) . '</p>
                    <p><strong>Time:</strong> ' . htmlspecialchars($appointmentTime) . '</p>
                    <p><strong>Purpose of Visit:</strong> ' . htmlspecialchars($appointment['reason']) . '</p>
                    <p><strong>Appointment Type:</strong> Walk-in Service</p>
                </div>
                
                <div class="info-section">
                    <h4 style="margin-top:0;color:#FFA800;">What is a Walk-in Appointment?</h4>
                    <p>A walk-in appointment means you can come to the clinic at your scheduled time without needing to book in advance. This appointment has been reserved for you by our staff to ensure you receive timely healthcare services.</p>
                </div>
                
                <div class="info-section">
                    <h4 style="margin-top:0;color:#FFA800;">Important Reminders</h4>
                    <ul style="padding-left:20px;margin:10px 0;">
                        <li>Please arrive 15 minutes before your scheduled time</li>
                        <li>Bring your valid ID and medical insurance card (if applicable)</li>
                        <li>Bring a list of your current medications</li>
                        <li>Bring any relevant medical records or test results</li>
                        <li>Wear a face mask within the facility</li>
                    </ul>
                </div>
                
                <div class="info-section">
                    <h4 style="margin-top:0;color:#FFA800;">Health and Safety Protocol</h4>
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
                    <li>Phone: 0991-871-9610</li>
                    <li>Email: mamatid.medclinic@gmail.com</li>
                </ul>
                
                <p>Thank you for choosing Mamatid Health Center for your healthcare needs. We look forward to serving you during your walk-in appointment.</p>
                
                <p>Best regards,<br>
                <strong>Mamatid Health Center Team</strong></p>
            </div>
            <div class="footer">
                <p>This is an automated message regarding your walk-in appointment. Please do not reply to this email.</p>
                <p>&copy; ' . date('Y') . ' Mamatid Health Center. All rights reserved.</p>
                <p>Address: 123 Mamatid Street, Cabuyao City, Laguna</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Plain text alternative
    $plainText = "WALK-IN APPOINTMENT INFORMATION\n\n" .
                 "Dear " . $appointment['patient_name'] . ",\n\n" .
                 "This is a notification regarding your walk-in appointment at Mamatid Health Center. Your walk-in appointment has been successfully processed and scheduled.\n\n" .
                 "WALK-IN APPOINTMENT DETAILS:\n" .
                 "Healthcare Provider: " . $providerTitle . $appointment['provider_name'] . "\n" .
                 "Date: " . $appointmentDate . "\n" .
                 "Time: " . $appointmentTime . "\n" .
                 "Purpose of Visit: " . $appointment['reason'] . "\n" .
                 "Appointment Type: Walk-in Service\n\n" .
                 "WHAT IS A WALK-IN APPOINTMENT?\n" .
                 "A walk-in appointment means you can come to the clinic at your scheduled time without needing to book in advance. This appointment has been reserved for you by our staff to ensure you receive timely healthcare services.\n\n" .
                 "IMPORTANT REMINDERS:\n" .
                 "- Please arrive 15 minutes before your scheduled time\n" .
                 "- Bring your valid ID and medical insurance card (if applicable)\n" .
                 "- Bring a list of your current medications\n" .
                 "- Bring any relevant medical records or test results\n" .
                 "- Wear a face mask within the facility\n\n" .
                 "HEALTH AND SAFETY PROTOCOL:\n" .
                 "If you are experiencing any of the following symptoms, please contact us before your visit:\n" .
                 "- Fever or chills\n" .
                 "- Cough or sore throat\n" .
                 "- Difficulty breathing\n" .
                 "- Loss of taste or smell\n\n" .
                 "For any questions or concerns, please contact us:\n" .
                 "Phone: 0991-871-9610\n" .
                 "Email: mamatid.medclinic@gmail.com\n\n" .
                 "Thank you for choosing Mamatid Health Center for your healthcare needs. We look forward to serving you during your walk-in appointment.\n\n" .
                 "Best regards,\n" .
                 "Mamatid Health Center Team\n\n" .
                 "This is an automated message regarding your walk-in appointment. Please do not reply to this email.\n" .
                 "Mamatid Health Center | 123 Mamatid Street, Cabuyao City, Laguna";
    
    // Send the email
    $emailSent = sendEmail($appointment['email'], $subject, $body, $appointment['patient_name'], $plainText);
    
    if ($emailSent) {
        echo json_encode(['success' => true, 'message' => 'Walk-in appointment notification sent successfully to ' . $appointment['email']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send walk-in appointment notification']);
    }
    
} catch (PDOException $ex) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $ex->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 