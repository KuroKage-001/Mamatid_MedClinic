<?php
/**
 * Book Walk-in Appointment
 * 
 * This file handles booking walk-in appointments directly from the admin interface.
 * Walk-in appointments are automatically approved and booked immediately.
 */

include '../config/db_connection.php';
require_once '../system/utilities/admin_client_role_functions_services.php';
require_once '../system/phpmailer/system/mailer.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check permission
try {
    requireRole(['admin', 'health_worker', 'doctor']);
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data
$patientName = trim($_POST['patient_name'] ?? '');
$phoneNumber = trim($_POST['phone_number'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$dateOfBirth = $_POST['date_of_birth'] ?? '';
$gender = $_POST['gender'] ?? '';
$appointmentDate = $_POST['appointment_date'] ?? '';
$appointmentTime = $_POST['appointment_time'] ?? '';
$providerId = $_POST['provider_id'] ?? '';
$providerType = $_POST['provider_type'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// Validate required fields
$errors = [];

if (empty($patientName)) {
    $errors[] = 'Patient name is required';
}

if (empty($phoneNumber)) {
    $errors[] = 'Phone number is required';
}

if (empty($address)) {
    $errors[] = 'Address is required';
}

if (empty($dateOfBirth)) {
    $errors[] = 'Date of birth is required';
}

if (empty($gender)) {
    $errors[] = 'Gender is required';
}

if (empty($appointmentDate)) {
    $errors[] = 'Appointment date is required';
}

if (empty($appointmentTime)) {
    $errors[] = 'Appointment time is required';
}

if (empty($providerId)) {
    $errors[] = 'Provider is required';
}

if (empty($providerType)) {
    $errors[] = 'Provider type is required';
}

if (empty($reason)) {
    $errors[] = 'Reason for visit is required';
}

// Validate date formats
if (!empty($dateOfBirth) && !DateTime::createFromFormat('Y-m-d', $dateOfBirth)) {
    $errors[] = 'Invalid date of birth format';
}

if (!empty($appointmentDate) && !DateTime::createFromFormat('Y-m-d', $appointmentDate)) {
    $errors[] = 'Invalid appointment date format';
}

// Validate appointment date is not in the past
if (!empty($appointmentDate) && $appointmentDate < date('Y-m-d')) {
    $errors[] = 'Appointment date cannot be in the past';
}

// Validate provider type
if (!in_array($providerType, ['admin', 'health_worker', 'doctor'])) {
    $errors[] = 'Invalid provider type';
}

// Validate email format if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

// Return validation errors
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed: ' . implode(', ', $errors)
    ]);
    exit;
}

try {
    $con->beginTransaction();
    
    // Get the appropriate schedule ID
    $scheduleId = null;
    
    if ($providerType == 'admin' || $providerType == 'health_worker') {
        $scheduleQuery = "SELECT id FROM admin_hw_schedules 
                         WHERE staff_id = ? AND schedule_date = ? AND is_approved = 1
                         LIMIT 1";
        $scheduleStmt = $con->prepare($scheduleQuery);
        $scheduleStmt->execute([$providerId, $appointmentDate]);
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$schedule) {
            $providerLabel = ($providerType == 'admin') ? 'administrator' : 'health worker';
            throw new Exception("No approved schedule found for the selected {$providerLabel} on this date");
        }
        
        $scheduleId = $schedule['id'];
        
        // Check if the time slot is still available (check both regular and walk-in appointments)
        $checkSlotQuery = "SELECT 'regular' as type FROM admin_clients_appointments 
                          WHERE schedule_id = ? AND appointment_time = ? 
                          AND status != 'cancelled' AND is_archived = 0
                          UNION ALL
                          SELECT 'walkin' as type FROM admin_walkin_appointments 
                          WHERE schedule_id = ? AND appointment_time = ? 
                          AND status != 'cancelled'";
        $checkStmt = $con->prepare($checkSlotQuery);
        $checkStmt->execute([$scheduleId, $appointmentTime, $scheduleId, $appointmentTime]);
        
        if ($checkStmt->rowCount() > 0) {
            throw new Exception('The selected time slot is no longer available');
        }
        
    } elseif ($providerType == 'doctor') {
        $scheduleQuery = "SELECT id FROM admin_doctor_schedules 
                         WHERE doctor_id = ? AND schedule_date = ? AND is_approved = 1 
                         AND (is_deleted = 0 OR is_deleted IS NULL)
                         LIMIT 1";
        $scheduleStmt = $con->prepare($scheduleQuery);
        $scheduleStmt->execute([$providerId, $appointmentDate]);
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$schedule) {
            throw new Exception('No approved schedule found for the selected doctor on this date');
        }
        
        $scheduleId = $schedule['id'];
        
        // Check if the time slot is still available (check both regular and walk-in appointments)
        $checkSlotQuery = "SELECT 'regular' as type FROM admin_clients_appointments 
                          WHERE schedule_id = ? AND appointment_time = ? 
                          AND status != 'cancelled' AND is_archived = 0
                          UNION ALL
                          SELECT 'walkin' as type FROM admin_walkin_appointments 
                          WHERE schedule_id = ? AND appointment_time = ? 
                          AND status != 'cancelled'";
        $checkStmt = $con->prepare($checkSlotQuery);
        $checkStmt->execute([$scheduleId, $appointmentTime, $scheduleId, $appointmentTime]);
        
        if ($checkStmt->rowCount() > 0) {
            throw new Exception('The selected time slot is no longer available');
        }
    }
    
    // Prepare notes for walk-in appointment
    $appointmentNotes = $notes;
    if (!empty($appointmentNotes)) {
        $appointmentNotes = "[Walk-in Appointment] " . $appointmentNotes;
    } else {
        $appointmentNotes = "[Walk-in Appointment] Booked by " . $_SESSION['display_name'] . " on " . date('Y-m-d H:i:s');
    }
    
    // Insert the walk-in appointment into the dedicated table
    $insertQuery = "INSERT INTO admin_walkin_appointments 
                   (patient_name, phone_number, email, address, date_of_birth, gender, 
                    appointment_date, appointment_time, reason, status, notes, 
                    schedule_id, provider_id, provider_type, booked_by) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $insertStmt = $con->prepare($insertQuery);
    $insertStmt->execute([
        $patientName,
        $phoneNumber,
        $email ?: NULL, // Use NULL if email is empty
        $address,
        $dateOfBirth,
        $gender,
        $appointmentDate,
        $appointmentTime,
        $reason,
        'approved',
        $appointmentNotes,
        $scheduleId,
        $providerId,
        $providerType,
        $_SESSION['user_id']
    ]);
    
    $appointmentId = $con->lastInsertId();
    
    // Update the appointment slot to mark it as booked
    if ($providerType == 'admin' || $providerType == 'health_worker') {
        // Check if slot exists in admin_hw_appointment_slots
        $slotExistsQuery = "SELECT id FROM admin_hw_appointment_slots 
                           WHERE schedule_id = ? AND slot_time = ?";
        $slotExistsStmt = $con->prepare($slotExistsQuery);
        $slotExistsStmt->execute([$scheduleId, $appointmentTime]);
        
        if ($slotExistsStmt->rowCount() > 0) {
            // Update existing slot
            $updateSlotQuery = "UPDATE admin_hw_appointment_slots 
                               SET is_booked = 1, appointment_id = ? 
                               WHERE schedule_id = ? AND slot_time = ?";
            $updateSlotStmt = $con->prepare($updateSlotQuery);
            $updateSlotStmt->execute([$appointmentId, $scheduleId, $appointmentTime]);
        } else {
            // Create new slot
            $createSlotQuery = "INSERT INTO admin_hw_appointment_slots 
                               (schedule_id, slot_time, is_booked, appointment_id) 
                               VALUES (?, ?, 1, ?)";
            $createSlotStmt = $con->prepare($createSlotQuery);
            $createSlotStmt->execute([$scheduleId, $appointmentTime, $appointmentId]);
        }
        
    } elseif ($providerType == 'doctor') {
        // Check if slot exists in admin_doctor_appointment_slots
        $slotExistsQuery = "SELECT id FROM admin_doctor_appointment_slots 
                           WHERE schedule_id = ? AND slot_time = ?";
        $slotExistsStmt = $con->prepare($slotExistsQuery);
        $slotExistsStmt->execute([$scheduleId, $appointmentTime]);
        
        if ($slotExistsStmt->rowCount() > 0) {
            // Update existing slot
            $updateSlotQuery = "UPDATE admin_doctor_appointment_slots 
                               SET is_booked = 1, appointment_id = ? 
                               WHERE schedule_id = ? AND slot_time = ?";
            $updateSlotStmt = $con->prepare($updateSlotQuery);
            $updateSlotStmt->execute([$appointmentId, $scheduleId, $appointmentTime]);
        } else {
            // Create new slot
            $createSlotQuery = "INSERT INTO admin_doctor_appointment_slots 
                               (schedule_id, slot_time, is_booked, appointment_id) 
                               VALUES (?, ?, 1, ?)";
            $createSlotStmt = $con->prepare($createSlotQuery);
            $createSlotStmt->execute([$scheduleId, $appointmentTime, $appointmentId]);
        }
    }
    
    $con->commit();
    
    // Send email notification if patient provided email
    if (!empty($email)) {
        // Get provider information for the email
        $providerQuery = "SELECT display_name, role FROM admin_user_accounts WHERE id = ?";
        $providerStmt = $con->prepare($providerQuery);
        $providerStmt->execute([$providerId]);
        $provider = $providerStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($provider) {
            // Format provider title based on role
            $providerTitle = '';
            switch($provider['role']) {
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
            
            // Format appointment date and time
            $appointmentDate = date('l, F j, Y', strtotime($appointmentDate));
            $appointmentTime12 = date('h:i A', strtotime($appointmentTime));
            
            // Prepare email content
            $subject = "Walk-in Appointment Confirmation: " . $appointmentDate;
            
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
                        <p style="margin:5px 0 0 0;">Walk-in Appointment Confirmation</p>
                        <div class="walkin-badge">
                            Walk-in Service
                        </div>
                    </div>
                    <div class="content">
                        <p>Dear ' . htmlspecialchars($patientName) . ',</p>
                        
                        <p>Thank you for choosing Mamatid Health Center. Your walk-in appointment has been successfully booked and confirmed.</p>
                        
                        <div class="appointment-details">
                            <h3 style="margin-top:0;color:#FFA800;">Walk-in Appointment Details</h3>
                            <p><strong>Healthcare Provider:</strong> ' . htmlspecialchars($providerTitle . $provider['display_name']) . '</p>
                            <p><strong>Date:</strong> ' . htmlspecialchars($appointmentDate) . '</p>
                            <p><strong>Time:</strong> ' . htmlspecialchars($appointmentTime12) . '</p>
                            <p><strong>Purpose of Visit:</strong> ' . htmlspecialchars($reason) . '</p>
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
            $plainText = "WALK-IN APPOINTMENT CONFIRMATION\n\n" .
                         "Dear " . $patientName . ",\n\n" .
                         "Thank you for choosing Mamatid Health Center. Your walk-in appointment has been successfully booked and confirmed.\n\n" .
                         "WALK-IN APPOINTMENT DETAILS:\n" .
                         "Healthcare Provider: " . $providerTitle . $provider['display_name'] . "\n" .
                         "Date: " . $appointmentDate . "\n" .
                         "Time: " . $appointmentTime12 . "\n" .
                         "Purpose of Visit: " . $reason . "\n" .
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
            $emailResult = sendEmail($email, $subject, $body, $patientName, $plainText);
            
            // Log email sending result
            if ($emailResult['success']) {
                error_log("Walk-in appointment confirmation email sent successfully to: " . $email);
            } else {
                error_log("Failed to send walk-in appointment confirmation email to: " . $email . " - " . $emailResult['message']);
            }
        }
    }
    
    // Format appointment time for display
    $formattedTime = date('h:i A', strtotime($appointmentTime));
    $formattedDate = date('M d, Y', strtotime($appointmentDate));
    
    $successMessage = "Walk-in appointment successfully booked for {$patientName} on {$formattedDate} at {$formattedTime}";
    
    // Add email status to success message if email was provided
    if (!empty($email)) {
        if (isset($emailResult) && $emailResult['success']) {
            $successMessage .= ". Confirmation email sent to {$email}.";
        } else {
            $successMessage .= ". Note: Confirmation email could not be sent to {$email}.";
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => $successMessage,
        'appointment_id' => $appointmentId
    ]);
    
} catch (Exception $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    
    error_log("Walk-in appointment booking error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 