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
        <title>Appointment Confirmation - Mamatid Health Center</title>
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
            .appointment-details p {
                margin: 8px 0;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #666666;
                margin-top: 30px;
                border-top: 1px solid #eeeeee;
                padding-top: 20px;
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
            .cta-button:hover {
                background-color: #234320;
            }
            .important-notice {
                background-color: #f8f9fa;
                border: 1px solid #e9ecef;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .contact-info {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2 style="margin:0;">Mamatid Health Center</h2>
                <p style="margin:5px 0 0 0;">Appointment Confirmation</p>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($patientName) . ',</p>
                
                <p>Thank you for choosing Mamatid Health Center for your healthcare needs. This email confirms your upcoming appointment details.</p>
                
                <div class="appointment-details">
                    <h3 style="margin-top:0;color:#2D5A27;">Appointment Information</h3>
                    <p><strong>Healthcare Provider:</strong> ' . htmlspecialchars($doctorName) . '</p>
                    <p><strong>Date:</strong> ' . htmlspecialchars($appointmentDate) . '</p>
                    <p><strong>Time:</strong> ' . htmlspecialchars($appointmentTime) . '</p>
                    <p><strong>Purpose of Visit:</strong> ' . htmlspecialchars($reason) . '</p>
                </div>
                
                <div class="important-notice">
                    <h4 style="margin-top:0;color:#2D5A27;">Pre-Appointment Instructions</h4>
                    <ul style="padding-left:20px;margin:10px 0;">
                        <li>Please arrive 15 minutes before your scheduled appointment time for registration.</li>
                        <li>Bring a valid government-issued ID and your medical insurance card (if applicable).</li>
                        <li>Bring a list of current medications and any relevant medical records.</li>
                        <li>If you are experiencing fever, cough, or other COVID-19 symptoms, please contact us before your visit.</li>
                        <li>Wear a face mask within the facility premises.</li>
                </ul>
                </div>';
    
    // Only add the button if we have a token
    if (!empty($token)) {
        $body .= '
                <div style="text-align:center;">
                    <p>Click below to view or download your appointment details:</p>
                    <a href="' . htmlspecialchars($pdfUrl) . '" class="cta-button">View Appointment Details</a>
                </div>';
    }
                
    $body .= '
                <div class="contact-info">
                    <h4 style="margin-top:0;color:#2D5A27;">Need to Reschedule?</h4>
                    <p>If you need to reschedule or cancel your appointment, please contact us at least 24 hours in advance through:</p>
                    <ul style="padding-left:20px;margin:10px 0;">
                        <li>Phone: 0991-871-9610</li>
                        <li>Email: mamatid.medclinic@gmail.com</li>
                    </ul>
                </div>

                <p>We look forward to providing you with quality healthcare services.</p>
                
                <p>Best regards,<br>
                <strong>Mamatid Health Center</strong></p>
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
    
    return $body;
}

/**
 * Generate walk-in appointment confirmation email body
 * 
 * @param array $appointmentDetails Appointment details
 * @return string HTML email body
 */
function generateWalkinAppointmentEmail($appointmentDetails) {
    // Extract appointment details
    $patientName = $appointmentDetails['patient_name'] ?? 'Valued Patient';
    $providerName = $appointmentDetails['doctor_name'] ?? 'Healthcare Provider';
    $appointmentDate = isset($appointmentDetails['appointment_date']) ? 
                     date('l, F j, Y', strtotime($appointmentDetails['appointment_date'])) : '';
    $appointmentTime = isset($appointmentDetails['appointment_time']) ? 
                     date('h:i A', strtotime($appointmentDetails['appointment_time'])) : '';
    $reason = $appointmentDetails['reason'] ?? 'General Consultation';

    // Build email body
    $body = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Walk-in Appointment Confirmation - Mamatid Health Center</title>
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
                background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
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
                border-left: 4px solid #3498db;
            }
            .appointment-details p {
                margin: 8px 0;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #666666;
                margin-top: 30px;
                border-top: 1px solid #eeeeee;
                padding-top: 20px;
            }
            .important-notice {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
            }
            .contact-info {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
            }
            .walkin-badge {
                display: inline-block;
                background: linear-gradient(135deg, #FFA800 0%, #F09000 100%);
                color: white;
                padding: 5px 12px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: bold;
                margin-left: 10px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2 style="margin:0;">Mamatid Health Center</h2>
                <p style="margin:5px 0 0 0;">Walk-in Appointment Confirmation<span class="walkin-badge">WALK-IN</span></p>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($patientName) . ',</p>
                
                <p>Thank you for choosing Mamatid Health Center for your healthcare needs. This email confirms your walk-in appointment details.</p>
                
                <div class="appointment-details">
                    <h3 style="margin-top:0;color:#3498db;">Appointment Information</h3>
                    <p><strong>Healthcare Provider:</strong> ' . htmlspecialchars($providerName) . '</p>
                    <p><strong>Date:</strong> ' . htmlspecialchars($appointmentDate) . '</p>
                    <p><strong>Time:</strong> ' . htmlspecialchars($appointmentTime) . '</p>
                    <p><strong>Purpose of Visit:</strong> ' . htmlspecialchars($reason) . '</p>
                    <p><strong>Appointment Type:</strong> <span style="color: #FFA800; font-weight: bold;">Walk-in Appointment</span></p>
                </div>
                
                <div class="important-notice">
                    <h4 style="margin-top:0;color:#856404;">Important Information</h4>
                    <ul style="padding-left:20px;margin:10px 0;">
                        <li>This is a <strong>walk-in appointment</strong> that was booked on your behalf by our staff.</li>
                        <li>Please arrive at least 10 minutes before your scheduled time.</li>
                        <li>Bring a valid government-issued ID for registration.</li>
                        <li>If you have any medical records or current medications, please bring them.</li>
                        <li>Wear a face mask within the facility premises.</li>
                    </ul>
                </div>
                
                <div class="contact-info">
                    <h4 style="margin-top:0;color:#3498db;">Need to Make Changes?</h4>
                    <p>If you need to reschedule or cancel your appointment, please contact us at least 24 hours in advance through:</p>
                    <ul style="padding-left:20px;margin:10px 0;">
                        <li>Phone: 0991-871-9610</li>
                        <li>Email: mamatid.medclinic@gmail.com</li>
                    </ul>
                </div>

                <p>We look forward to providing you with quality healthcare services.</p>
                
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
    
    return $body;
} 

/**
 * Generate walk-in appointment reminder email body
 * 
 * @param array $appointmentDetails Appointment details
 * @return string HTML email body
 */
function generateWalkinAppointmentReminderEmail($appointmentDetails) {
    // Extract appointment details
    $patientName = $appointmentDetails['patient_name'] ?? 'Valued Patient';
    $providerName = $appointmentDetails['doctor_name'] ?? 'Healthcare Provider';
    $appointmentDate = isset($appointmentDetails['appointment_date']) ? 
                     date('l, F j, Y', strtotime($appointmentDetails['appointment_date'])) : '';
    $appointmentTime = isset($appointmentDetails['appointment_time']) ? 
                     date('h:i A', strtotime($appointmentDetails['appointment_time'])) : '';
    $reason = $appointmentDetails['reason'] ?? 'General Consultation';

    // Build email body
    $body = '
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
                background: linear-gradient(135deg, #FFA800 0%, #F09000 100%);
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
                border-left: 4px solid #FFA800;
            }
            .appointment-details p {
                margin: 8px 0;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #666666;
                margin-top: 30px;
                border-top: 1px solid #eeeeee;
                padding-top: 20px;
            }
            .reminder-notice {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
            }
            .checklist {
                background-color: #e8f5e8;
                border: 1px solid #c8e6c9;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
            }
            .contact-info {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
            }
            .walkin-badge {
                display: inline-block;
                background: linear-gradient(135deg, #FFA800 0%, #F09000 100%);
                color: white;
                padding: 5px 12px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: bold;
                margin-left: 10px;
            }
            .checklist ul {
                list-style: none;
                padding-left: 0;
            }
            .checklist li {
                padding: 5px 0;
                position: relative;
                padding-left: 25px;
            }
            .checklist li:before {
                content: "✓";
                position: absolute;
                left: 0;
                color: #4caf50;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2 style="margin:0;">Mamatid Health Center</h2>
                <p style="margin:5px 0 0 0;">Appointment Reminder<span class="walkin-badge">WALK-IN</span></p>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($patientName) . ',</p>
                
                <p>This is a friendly reminder about your upcoming walk-in appointment at Mamatid Health Center. We look forward to seeing you!</p>
                
                <div class="appointment-details">
                    <h3 style="margin-top:0;color:#FFA800;">Your Appointment Details</h3>
                    <p><strong>Healthcare Provider:</strong> ' . htmlspecialchars($providerName) . '</p>
                    <p><strong>Date:</strong> ' . htmlspecialchars($appointmentDate) . '</p>
                    <p><strong>Time:</strong> ' . htmlspecialchars($appointmentTime) . '</p>
                    <p><strong>Purpose of Visit:</strong> ' . htmlspecialchars($reason) . '</p>
                    <p><strong>Appointment Type:</strong> <span style="color: #FFA800; font-weight: bold;">Walk-in Appointment</span></p>
                </div>
                
                <div class="checklist">
                    <h4 style="margin-top:0;color:#2e7d32;">Pre-Appointment Checklist</h4>
                    <ul>
                        <li>Plan to arrive 10-15 minutes early</li>
                        <li>Bring your valid ID for verification</li>
                        <li>Bring any relevant medical records or test results</li>
                        <li>Wear a face mask and follow health protocols</li>
                        <li>Bring a list of current medications (if any)</li>
                        <li>Prepare any questions you may have for the doctor</li>
                    </ul>
                </div>
                
                <div class="reminder-notice">
                    <h4 style="margin-top:0;color:#856404;">Important Reminder</h4>
                    <p>This is a <strong>walk-in appointment</strong> that was booked on your behalf. Please arrive on time to ensure we can provide you with the best care possible.</p>
                </div>
                
                <div class="contact-info">
                    <h4 style="margin-top:0;color:#3498db;">Need to Make Changes?</h4>
                    <p>If you need to reschedule or cancel your appointment, please contact us as soon as possible so we can accommodate other patients who may need care:</p>
                    <ul style="padding-left:20px;margin:10px 0;">
                        <li>Phone: 0991-871-9610</li>
                        <li>Email: mamatid.medclinic@gmail.com</li>
                    </ul>
                </div>

                <p>We appreciate you choosing Mamatid Health Center for your healthcare needs.</p>
                
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
    
    return $body;
} 
