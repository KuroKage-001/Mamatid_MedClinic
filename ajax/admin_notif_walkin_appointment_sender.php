<?php
session_start();
include '../config/db_connection.php';
require_once '../system/phpmailer/system/mailer.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $walkinId = $_POST['walkin_id'] ?? null;
    $doctorId = $_SESSION['user_id'];
    
    if (!$walkinId) {
        echo json_encode(['success' => false, 'message' => 'Walk-in appointment ID is required']);
        exit;
    }
    
    try {
        // Get walk-in appointment details
        $query = "SELECT w.*, ds.time_slot_minutes 
                  FROM admin_walkin_appointments w
                  JOIN admin_doctor_schedules ds ON w.schedule_id = ds.id
                  WHERE w.id = ? AND w.provider_id = ? AND w.provider_type = 'doctor'";
        $stmt = $con->prepare($query);
        $stmt->execute([$walkinId, $doctorId]);
        $walkin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$walkin) {
            echo json_encode(['success' => false, 'message' => 'Walk-in appointment not found or unauthorized']);
            exit;
        }
        
        // Check if email is provided
        if (empty($walkin['email'])) {
            echo json_encode(['success' => false, 'message' => 'No email address provided for this walk-in appointment']);
            exit;
        }
        
        // Get doctor name
        $doctorQuery = "SELECT display_name FROM admin_user_accounts WHERE id = ?";
        $doctorStmt = $con->prepare($doctorQuery);
        $doctorStmt->execute([$doctorId]);
        $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);
        $doctorName = $doctor['display_name'] ?? 'Doctor';
        
        // Prepare appointment details for email
        $appointmentDetails = [
            'patient_name' => $walkin['patient_name'],
            'doctor_name' => $doctorName,
            'appointment_date' => $walkin['appointment_date'],
            'appointment_time' => $walkin['appointment_time'],
            'reason' => $walkin['reason'] ?? 'General Consultation'
        ];
        
        // Generate email content
        $emailBody = generateWalkinAppointmentEmail($appointmentDetails);
        
        // Send email
        $emailSent = sendEmail(
            $walkin['email'],
            'Walk-in Appointment Confirmation - Mamatid Health Center',
            $emailBody,
            'Mamatid Health Center'
        );
        
        if ($emailSent) {
            // Update email_sent status in database
            $updateQuery = "UPDATE admin_walkin_appointments SET email_sent = 1, updated_at = NOW() WHERE id = ?";
            $updateStmt = $con->prepare($updateQuery);
            $updateStmt->execute([$walkinId]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Email notification sent successfully to ' . $walkin['email']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email notification']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 