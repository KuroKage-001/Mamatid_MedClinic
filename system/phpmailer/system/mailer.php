<?php
/**
 * Mailer Utility for Mamatid Health Center
 * Uses PHPMailer for sending emails with fallback options
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
 * Test SMTP connectivity
 * @return bool
 */
function testSMTPConnection() {
    $connection = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);
    if (!$connection) {
        error_log("SMTP Connection Test Failed: $errno - $errstr");
        return false;
    } else {
        fclose($connection);
        error_log("SMTP Connection Test Successful");
        return true;
    }
}

/**
 * Check required PHP extensions
 * @return array
 */
function checkRequiredExtensions() {
    $extensions = ['openssl', 'mbstring', 'curl'];
    $missing = [];
    
    foreach ($extensions as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }
    
    return $missing;
}

/**
 * Send email using SMTP with Gmail
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $name Recipient name
 * @param string $plainText Optional plain text version
 * @return array Status of email sending [success, message]
 */
function sendEmailSMTP($to, $subject, $body, $name = '', $plainText = '') {
    // Check required extensions first
    $missing = checkRequiredExtensions();
    if (!empty($missing)) {
        $error = "Missing required PHP extensions: " . implode(', ', $missing);
        error_log("PHPMailer Error: $error");
        return ['success' => false, 'message' => $error];
    }
    
    // Test SMTP connection
    if (!testSMTPConnection()) {
        error_log("SMTP server not reachable");
        return ['success' => false, 'message' => 'SMTP server not reachable. Check firewall/network settings.'];
    }
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings for Gmail SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'escobinleomar56@gmail.com';
        $mail->Password   = 'ewel ebhc peny zmsj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Additional SMTP options for better compatibility
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Enable debugging for troubleshooting
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP Debug: $str");
        };
        
        // Set timeout
        $mail->Timeout = 60;
        
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
        
        return ['success' => true, 'message' => 'Email has been sent successfully via SMTP'];
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return ['success' => false, 'message' => "SMTP Error: {$mail->ErrorInfo}"];
    }
}

/**
 * Send email using PHP's mail() function as fallback
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $name Recipient name
 * @return array Status of email sending [success, message]
 */
function sendEmailLocal($to, $subject, $body, $name = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Use PHP's mail() function instead of SMTP
        $mail->isMail();
        
        // Set sender
        $mail->setFrom('noreply@mamatidhealth.com', 'Mamatid Health Center');
        $mail->addReplyTo('noreply@mamatidhealth.com', 'Information');
        
        // Add recipient
        $mail->addAddress($to, $name);
        
        // Set email format
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Generate plain text version
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully via PHP mail()'];
    } catch (Exception $e) {
        error_log("PHP mail() Error: " . $e->getMessage());
        return ['success' => false, 'message' => "PHP mail() Error: " . $e->getMessage()];
    }
}

/**
 * Try alternative SMTP configuration (port 465 with SSL)
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $name Recipient name
 * @return array Status of email sending [success, message]
 */
function sendEmailSMTPAlternative($to, $subject, $body, $name = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings for Gmail SMTP with SSL
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'escobinleomar56@gmail.com';
        $mail->Password   = 'ewel ebhc peny zmsj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        
        // Additional SMTP options
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Enable debugging
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP Alt Debug: $str");
        };
        
        $mail->Timeout = 60;
        
        // Set sender
        $mail->setFrom('escobinleomar56@gmail.com', 'Mamatid Health Center');
        $mail->addReplyTo('escobinleomar56@gmail.com', 'Information');
        
        // Add recipient
        $mail->addAddress($to, $name);
        
        // Set email format
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Generate plain text version
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully via alternative SMTP'];
    } catch (Exception $e) {
        error_log("Alternative SMTP Error: " . $e->getMessage());
        return ['success' => false, 'message' => "Alternative SMTP Error: " . $e->getMessage()];
    }
}

/**
 * Main email sending function with fallback options
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $name Recipient name
 * @param string $plainText Optional plain text version
 * @return array Status of email sending [success, message]
 */
function sendEmail($to, $subject, $body, $name = '', $plainText = '') {
    // Validate email address
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: $to");
        return ['success' => false, 'message' => 'Invalid email address format'];
    }
    
    // Log attempt
    error_log("Attempting to send email to: $to with subject: $subject");
    
    // Method 1: Try SMTP with TLS (port 587)
    $result = sendEmailSMTP($to, $subject, $body, $name, $plainText);
    if ($result['success']) {
        error_log("Email sent successfully via SMTP TLS");
        return $result;
    }
    
    error_log("SMTP TLS failed: " . $result['message']);
    
    // Method 2: Try SMTP with SSL (port 465)
    $result = sendEmailSMTPAlternative($to, $subject, $body, $name);
    if ($result['success']) {
        error_log("Email sent successfully via SMTP SSL");
        return $result;
    }
    
    error_log("SMTP SSL failed: " . $result['message']);
    
    // Method 3: Try PHP mail() function as last resort
    if (function_exists('mail')) {
        $result = sendEmailLocal($to, $subject, $body, $name);
        if ($result['success']) {
            error_log("Email sent successfully via PHP mail()");
            return $result;
        }
        error_log("PHP mail() failed: " . $result['message']);
    } else {
        error_log("PHP mail() function is not available");
    }
    
    // All methods failed
    $finalError = "All email sending methods failed. Check server configuration, firewall, and internet connectivity.";
    error_log($finalError);
    return ['success' => false, 'message' => $finalError];
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
    
    // Build the PDF URL with the token - use a more flexible approach
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . '://' . $host;
    
    // Try to detect the correct path
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $pdfUrl = $baseUrl . $scriptDir . '/actions/admin_generate_appointment_pdf.php?token=' . urlencode($token);
    
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
                border-left: 4px solid #3699FF;
            }
            .appointment-details p {
                margin: 5px 0;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #777777;
                margin-top: 20px;
                border-top: 1px solid #eee;
                padding-top: 15px;
            }
            .cta-button {
                display: inline-block;
                background-color: #3699FF;
                color: white;
                padding: 12px 25px;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 15px;
                font-weight: bold;
            }
            .cta-button:hover {
                background-color: #2980b9;
            }
            .important {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                padding: 10px;
                border-radius: 5px;
                margin: 15px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>🏥 Appointment Confirmation</h2>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($patientName) . ',</p>
                
                <p>Thank you for booking an appointment with <strong>Mamatid Health Center</strong>. Your appointment has been confirmed successfully.</p>
                
                <div class="appointment-details">
                    <h3>📅 Appointment Details:</h3>
                    <p><strong>👨‍⚕️ Doctor:</strong> ' . htmlspecialchars($doctorName) . '</p>
                    <p><strong>📅 Date:</strong> ' . htmlspecialchars($appointmentDate) . '</p>
                    <p><strong>🕒 Time:</strong> ' . htmlspecialchars($appointmentTime) . '</p>
                    <p><strong>📝 Reason for Visit:</strong> ' . htmlspecialchars($reason) . '</p>
                </div>
                
                <div class="important">
                    <p><strong>⚠️ Important Reminders:</strong></p>
                    <ul>
                        <li>Please arrive 15 minutes before your scheduled appointment time.</li>
                        <li>Bring your identification card and any relevant medical records.</li>
                        <li>If you need to cancel or reschedule, please contact us at least 24 hours in advance.</li>
                        <li>Wear a face mask and maintain social distancing protocols.</li>
                    </ul>
                </div>
                
                <p>You can view or download your appointment details using the button below:</p>';
    
    // Only add the button if we have a token
    if (!empty($token)) {
        $body .= '
                <center><a href="' . htmlspecialchars($pdfUrl) . '" class="cta-button">📄 Download Appointment PDF</a></center>';
    } else {
        $body .= '
                <center><p style="color: #FF0000;">📄 Your appointment link is being processed. Please check back later.</p></center>';
    }
                
    $body .= '
                <p>If you have any questions or need further assistance, please don\'t hesitate to contact us.</p>
                
                <p>Best regards,<br>
                <strong>Mamatid Health Center Team</strong><br>
                📧 Email: clinic@mamatidhealth.com<br>
                📞 Phone: (02) 888-7777</p>
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
