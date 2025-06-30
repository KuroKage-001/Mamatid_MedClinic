<?php
/**
 * Mailer Utility for Mamatid Health Center
 * Uses PHPMailer for sending emails
 */

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Load PHPMailer autoloader
require_once __DIR__ . '/../PHPMailer-6.8.1/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-6.8.1/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-6.8.1/src/SMTP.php';

/**
 * Send an email using PHPMailer
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $name Recipient name
 * @param string $plainText Optional plain text version
 * @return array Status of email sending [success, message]
 */
function sendEmail($to, $subject, $body, $name = '', $plainText = '') {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings for Gmail SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'escobinleomar56@gmail.com'; // Your Gmail address
        $mail->Password   = 'ewel ebhc peny zmsj'; // Your app password (not your regular Gmail password)
        $mail->SMTPSecure = 'tls'; // Use TLS encryption
        $mail->Port       = 587;   // TLS port
        
        // Enable SMTP debugging for troubleshooting (remove in production)
        $mail->SMTPDebug = 0; // 0 = off, 1 = client messages, 2 = client and server messages
        
        // Uncomment this and comment out the SMTP settings above if you want to use PHP's mail() function instead
        // $mail->isMail();
        
        // Set sender
        $mail->setFrom('escobinleomar56@gmail.com', 'Mamatid Health Center');
        $mail->addReplyTo('escobinleomar56@gmail.com', 'Information');
        
        // Add recipient
        $mail->addAddress($to, $name);
        
        // Set email format to HTML
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // Set plain text version if provided
        if (!empty($plainText)) {
            $mail->AltBody = $plainText;
        } else {
            // Generate plain text from HTML as fallback
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        }
        
        // Send the email
        $mail->send();
        
        return ['success' => true, 'message' => 'Email has been sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"];
    }
}

/**
 * Generate appointment confirmation email body
 * 
 * @param array $appointmentDetails Appointment details
 * @return string HTML email body
 */
function generateAppointmentConfirmationEmail($appointmentDetails) {
    // Extract appointment details
    $patientName = $appointmentDetails['patient_name'] ?? 'Valued Patient';
    $doctorName = $appointmentDetails['doctor_name'] ?? 'Your Doctor';
    $appointmentDate = isset($appointmentDetails['appointment_date']) ? 
                     date('l, F j, Y', strtotime($appointmentDetails['appointment_date'])) : '';
    $appointmentTime = isset($appointmentDetails['appointment_time']) ? 
                     date('h:i A', strtotime($appointmentDetails['appointment_time'])) : '';
    $reason = $appointmentDetails['reason'] ?? 'General Consultation';
    $token = $appointmentDetails['view_token'] ?? '';
    
    // Ensure token is not empty
    if (empty($token)) {
        error_log('Warning: Empty token in appointment confirmation email');
    }
    
    // Build the PDF URL with the token - use a direct absolute path
    $pdfUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/repo-core/Mamatid_MedClinic/actions/generate_appointment_pdf.php?token=' . urlencode($token);
    
    // Debug log the URL
    error_log("PDF URL generated: " . $pdfUrl);

    // Build email body
    $body = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Appointment Confirmation</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #dddddd;
                border-radius: 5px;
            }
            .header {
                background-color: #3699FF;
                color: white;
                padding: 15px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }
            .content {
                padding: 20px;
                background-color: #ffffff;
            }
            .appointment-details {
                background-color: #f9f9f9;
                padding: 15px;
                border-radius: 5px;
                margin: 15px 0;
            }
            .appointment-details p {
                margin: 5px 0;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #777777;
                margin-top: 20px;
            }
            .cta-button {
                display: inline-block;
                background-color: #3699FF;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Appointment Confirmation</h2>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($patientName) . ',</p>
                
                <p>Thank you for booking an appointment with Mamatid Health Center. Your appointment has been confirmed successfully.</p>
                
                <div class="appointment-details">
                    <h3>Appointment Details:</h3>
                    <p><strong>Doctor:</strong> ' . htmlspecialchars($doctorName) . '</p>
                    <p><strong>Date:</strong> ' . htmlspecialchars($appointmentDate) . '</p>
                    <p><strong>Time:</strong> ' . htmlspecialchars($appointmentTime) . '</p>
                    <p><strong>Reason for Visit:</strong> ' . htmlspecialchars($reason) . '</p>
                </div>
                
                <p><strong>Important Reminders:</strong></p>
                <ul>
                    <li>Please arrive 15 minutes before your scheduled appointment time.</li>
                    <li>Bring your identification card and any relevant medical records.</li>
                    <li>If you need to cancel or reschedule, please contact us at least 24 hours in advance.</li>
                </ul>
                
                <p>You can view or download your appointment details using the button below.</p>';
    
    // Only add the button if we have a token
    if (!empty($token)) {
        $body .= '
                <center><a href="' . htmlspecialchars($pdfUrl) . '" class="cta-button">Download Appointment PDF</a></center>';
    } else {
        $body .= '
                <center><p style="color: #FF0000;">Your appointment link is being processed. Please check back later.</p></center>';
    }
                
    $body .= '
                <p>If you have any questions or need further assistance, please don\'t hesitate to contact us.</p>
                
                <p>Best regards,<br>Mamatid Health Center Team</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' Mamatid Health Center. All rights reserved.</p>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $body;
} 
