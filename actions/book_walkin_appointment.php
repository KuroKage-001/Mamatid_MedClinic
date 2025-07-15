<?php
/**
 * Book Walk-in Appointment
 * 
 * This file handles booking walk-in appointments directly from the admin interface.
 * Walk-in appointments are automatically approved and booked immediately.
 */

include '../config/db_connection.php';
require_once '../system/utilities/admin_client_role_functions_services.php';

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

// Validate email if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
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
        $email,
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
    
    // Send email notification if email is provided
    $emailSent = false;
    if (!empty($email)) {
        try {
            // Include the mailer utility
            require_once '../system/phpmailer/system/mailer.php';
            
            // Get provider name
            $providerName = '';
            if ($providerType == 'doctor') {
                $providerQuery = "SELECT display_name FROM admin_user_accounts WHERE id = ? AND role = 'doctor'";
            } else {
                $providerQuery = "SELECT display_name FROM admin_user_accounts WHERE id = ? AND role IN ('admin', 'health_worker')";
            }
            $providerStmt = $con->prepare($providerQuery);
            $providerStmt->execute([$providerId]);
            $provider = $providerStmt->fetch(PDO::FETCH_ASSOC);
            $providerName = $provider['display_name'] ?? 'Healthcare Provider';
            
            // Prepare appointment details for email
            $appointmentDetails = [
                'patient_name' => $patientName,
                'doctor_name' => $providerName,
                'appointment_date' => $appointmentDate,
                'appointment_time' => $appointmentTime,
                'reason' => $reason,
                'view_token' => null // Walk-in appointments don't have view tokens
            ];
            
            // Generate email body
            $emailBody = generateWalkinAppointmentEmail($appointmentDetails);
            
            // Send email
            $emailResult = sendEmail($email, 'Walk-in Appointment Confirmation - Mamatid Health Center', $emailBody, $patientName);
            
            if ($emailResult['success']) {
                $emailSent = true;
                error_log("Walk-in appointment confirmation email sent successfully to: $email");
            } else {
                error_log("Failed to send walk-in appointment confirmation email: " . $emailResult['message']);
            }
        } catch (Exception $e) {
            error_log("Error sending walk-in appointment confirmation email: " . $e->getMessage());
        }
    }
    
    // Format appointment time for display
    $formattedTime = date('h:i A', strtotime($appointmentTime));
    $formattedDate = date('M d, Y', strtotime($appointmentDate));
    
    $message = "Walk-in appointment successfully booked for {$patientName} on {$formattedDate} at {$formattedTime}";
    if ($emailSent) {
        $message .= ". Confirmation email has been sent to the provided email address.";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'appointment_id' => $appointmentId,
        'email_sent' => $emailSent
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