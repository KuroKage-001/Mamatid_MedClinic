<?php
// Include client authentication check
require_once './system/utilities/check_client_auth.php';

include './config/db_connection.php';
require_once './common_service/role_functions.php';

// Restrict this page to clients only
requireClient();

$message = '';
$error = '';
$clientId = $_SESSION['client_id'];

// Get message/error from URL if redirected
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Get all approved schedules from doctors - ONLY FUTURE DATES
$doctorScheduleQuery = "SELECT ds.*, u.display_name as doctor_name, u.role 
                FROM admin_doctor_schedules ds
                JOIN admin_user_accounts u ON ds.doctor_id = u.id
                WHERE ds.schedule_date >= CURDATE()
                AND ds.is_approved = 1
                ORDER BY ds.schedule_date ASC, ds.start_time ASC";
$doctorScheduleStmt = $con->prepare($doctorScheduleQuery);
$doctorScheduleStmt->execute();
$doctorSchedules = $doctorScheduleStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all staff schedules (auto-approved) - ONLY FUTURE DATES
$staffScheduleQuery = "SELECT ss.*, u.display_name as staff_name, u.role 
                FROM admin_hw_schedules ss
                JOIN admin_user_accounts u ON ss.staff_id = u.id
                WHERE ss.schedule_date >= CURDATE()
                ORDER BY ss.schedule_date ASC, ss.start_time ASC";
$staffScheduleStmt = $con->prepare($staffScheduleQuery);
$staffScheduleStmt->execute();
$staffSchedules = $staffScheduleStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle appointment booking
if (isset($_POST['book_appointment'])) {
    $clientId = $_SESSION['client_id'];
    $scheduleId = $_POST['schedule_id'];
    $scheduleType = $_POST['schedule_type']; // 'doctor' or 'staff'
    $appointmentTime = $_POST['appointment_time'];
    $reason = $_POST['reason'];

    // Get client details
    $query = "SELECT * FROM clients_user_accounts WHERE id = ?";
    $stmt = $con->prepare($query);
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        $error = "Invalid client information.";
        header("location:client_appointment_booking.php?error=" . urlencode($error));
        exit;
    }

    try {
        // Start transaction
        $con->beginTransaction();
        
        // Get schedule details with a lock based on schedule type
        if ($scheduleType == 'doctor') {
            $scheduleQuery = "SELECT ds.*, u.id as provider_id, u.display_name as provider_name 
                            FROM admin_doctor_schedules ds
                            JOIN admin_user_accounts u ON ds.doctor_id = u.id
                            WHERE ds.id = ? AND ds.is_approved = 1 FOR UPDATE";
            $tableName = 'admin_doctor_schedules';
            $providerIdColumn = 'doctor_id';
        } else if ($scheduleType == 'staff') {
            $scheduleQuery = "SELECT ss.*, u.id as provider_id, u.display_name as provider_name 
                            FROM admin_hw_schedules ss
                            JOIN admin_user_accounts u ON ss.staff_id = u.id
                            WHERE ss.id = ? FOR UPDATE";
            $tableName = 'admin_hw_schedules';
            $providerIdColumn = 'staff_id';
        } else {
            $con->rollBack();
            $error = "Invalid schedule type selected.";
            header("location:client_appointment_booking.php?error=" . urlencode($error));
            exit;
        }
        
        $scheduleStmt = $con->prepare($scheduleQuery);
        $scheduleStmt->execute([$scheduleId]);
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            $con->rollBack();
            $error = "Invalid schedule selected.";
            header("location:client_appointment_booking.php?error=" . urlencode($error));
            exit;
        }
        
        $maxPatients = $schedule['max_patients'];
        $providerId = $schedule['provider_id'];
        $providerName = $schedule['provider_name'];
        
        // Check if the selected time slot is available with a lock
        $slotQuery = "SELECT COUNT(*) as slot_count FROM admin_clients_appointments 
                    WHERE schedule_id = ? AND appointment_time = ? AND status != 'cancelled' 
                    FOR UPDATE";
        $slotStmt = $con->prepare($slotQuery);
        $slotStmt->execute([$scheduleId, $appointmentTime]);
        $slotCount = $slotStmt->fetch(PDO::FETCH_ASSOC)['slot_count'];

        if ($slotCount > 0) {
            $con->rollBack();
            $error = "This time slot is already booked. Please select another time.";
            header("location:client_appointment_booking.php?error=" . urlencode($error));
            exit;
        }
        
        // Check if this client already has an appointment at this time slot
        $existingQuery = "SELECT COUNT(*) as existing_count FROM admin_clients_appointments 
                        WHERE schedule_id = ? AND appointment_time = ? AND status != 'cancelled' 
                        AND patient_name = ? FOR UPDATE";
        $existingStmt = $con->prepare($existingQuery);
        $existingStmt->execute([$scheduleId, $appointmentTime, $client['full_name']]);
        $existingCount = $existingStmt->fetch(PDO::FETCH_ASSOC)['existing_count'];
        
        if ($existingCount > 0) {
            $con->rollBack();
            $error = "You already have an appointment booked for this time slot.";
            header("location:client_appointment_booking.php?error=" . urlencode($error));
            exit;
        }

        // Check or create the appointment slot record based on schedule type
        if ($scheduleType == 'doctor') {
            $slotExistsQuery = "SELECT id, is_booked FROM admin_doctor_appointment_slots 
                              WHERE schedule_id = ? AND slot_time = ? 
                              FOR UPDATE";
            $slotExistsStmt = $con->prepare($slotExistsQuery);
            $slotExistsStmt->execute([$scheduleId, $appointmentTime]);
            $slotExists = $slotExistsStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$slotExists) {
                // Create the slot if it doesn't exist
                $createSlotQuery = "INSERT INTO admin_doctor_appointment_slots 
                                  (schedule_id, slot_time, is_booked) 
                                  VALUES (?, ?, 0)";
                $createSlotStmt = $con->prepare($createSlotQuery);
                $createSlotStmt->execute([$scheduleId, $appointmentTime]);
                
                // Get the newly created slot ID
                $slotId = $con->lastInsertId();
            } else {
                $slotId = $slotExists['id'];
            }
        } else {
            // For staff schedules, use admin_hw_appointment_slots table
            $slotExistsQuery = "SELECT id, is_booked FROM admin_hw_appointment_slots 
                              WHERE schedule_id = ? AND slot_time = ? 
                              FOR UPDATE";
            $slotExistsStmt = $con->prepare($slotExistsQuery);
            $slotExistsStmt->execute([$scheduleId, $appointmentTime]);
            $slotExists = $slotExistsStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$slotExists) {
                // Create the slot if it doesn't exist
                $createSlotQuery = "INSERT INTO admin_hw_appointment_slots 
                                  (schedule_id, slot_time, is_booked) 
                                  VALUES (?, ?, 0)";
                $createSlotStmt = $con->prepare($createSlotQuery);
                $createSlotStmt->execute([$scheduleId, $appointmentTime]);
                
                // Get the newly created slot ID
                $slotId = $con->lastInsertId();
            } else {
                $slotId = $slotExists['id'];
            }
        }

        // Insert the appointment
        $query = "INSERT INTO admin_clients_appointments (
                    patient_name, phone_number, address, date_of_birth,
                    gender, appointment_date, appointment_time, reason, status,
                    schedule_id, doctor_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?, ?)";

        $stmt = $con->prepare($query);
        $stmt->execute([
            $client['full_name'],
            $client['phone_number'],
            $client['address'],
            $client['date_of_birth'],
            $client['gender'],
            $schedule['schedule_date'],
            $appointmentTime,
            $reason,
            $scheduleId,
            $providerId
        ]);
        
        // Get the newly created appointment ID
        $appointmentId = $con->lastInsertId();
        
        // Update the appropriate slot table based on schedule type
        if ($scheduleType == 'doctor') {
            $updateSlotQuery = "UPDATE admin_doctor_appointment_slots 
                              SET is_booked = 1, appointment_id = ? 
                              WHERE id = ?";
            $updateSlotStmt = $con->prepare($updateSlotQuery);
            $updateSlotStmt->execute([$appointmentId, $slotId]);
        } else {
            $updateSlotQuery = "UPDATE admin_hw_appointment_slots 
                              SET is_booked = 1, appointment_id = ? 
                              WHERE id = ?";
            $updateSlotStmt = $con->prepare($updateSlotQuery);
            $updateSlotStmt->execute([$appointmentId, $slotId]);
        }

        // Commit the transaction
        $con->commit();
        $message = 'Appointment booked successfully!';
        
        // Send confirmation email to client
        try {
            // Include mailer utility
            require_once './system/phpmailer/system/mailer.php';
            
            // Get client email and ensure it exists
            $clientEmail = $client['email'] ?? '';
            if (empty($clientEmail)) {
                error_log("No email address found for client ID: {$clientId}");
                throw new Exception("Client email address not found");
            }
            
            // Validate email format
            if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid email format for client ID: {$clientId}, Email: {$clientEmail}");
                throw new Exception("Invalid email address format");
            }
            
            $clientName = $client['full_name'];
            
            // Generate a secure token for viewing the appointment
            $token = bin2hex(random_bytes(32)); // Generate a 64-character random hex string
            $expiry = date('Y-m-d H:i:s', strtotime('+30 days')); // Token expires after 30 days
            
            // Add token and email sent status to the appointment record
            $updateAppointmentQuery = "UPDATE admin_clients_appointments SET view_token = ?, token_expiry = ? WHERE id = ?";
            $updateAppointmentStmt = $con->prepare($updateAppointmentQuery);
            $updateAppointmentStmt->execute([
                $token, 
                $expiry, 
                $appointmentId
            ]);
            
            // Prepare appointment details
            $appointmentDetails = [
                'patient_name' => $clientName,
                'doctor_name' => $providerName,
                'appointment_date' => $schedule['schedule_date'],
                'appointment_time' => $appointmentTime,
                'reason' => $reason,
                'view_token' => $token // Include the token in the appointment details
            ];
            
            // Debug logging for token
            error_log("Appointment ID: " . $appointmentId . " - Token: " . $token);
            error_log("Attempting to send email to: {$clientEmail}");
            
            // Generate email body
            $emailBody = generateAppointmentConfirmationEmail($appointmentDetails);
            
            // Send email with improved error handling
            $emailResult = sendEmail(
                $clientEmail,
                'Your Appointment Confirmation - Mamatid Health Center',
                $emailBody,
                $clientName
            );
            
            // Log detailed email result
            error_log("Email sending result: " . json_encode($emailResult));
            
            // Update email sent status
            $updateEmailStatusQuery = "UPDATE admin_clients_appointments SET email_sent = ? WHERE id = ?";
            $updateEmailStatusStmt = $con->prepare($updateEmailStatusQuery);
            $updateEmailStatusStmt->execute([
                $emailResult['success'] ? 1 : 0,
                $appointmentId
            ]);
            
            // If email failed, log the specific reason but don't fail the booking
            if (!$emailResult['success']) {
                error_log("Email sending failed: " . $emailResult['message']);
                $message .= ' (Note: Email notification could not be sent - ' . $emailResult['message'] . ')';
            } else {
                error_log("Email sent successfully to: {$clientEmail}");
            }
            
        } catch (Exception $ex) {
            // Log email error but don't prevent successful appointment booking
            error_log("Failed to send confirmation email: " . $ex->getMessage());
            error_log("Stack trace: " . $ex->getTraceAsString());
            
            // Add user-friendly message
            $message .= ' (Note: Email notification could not be sent. Please check with the clinic for confirmation.)';
        }
        
        // Redirect to dashboard
        header("location:client_dashboard.php?message=" . urlencode($message));
        exit;
    } catch(PDOException $ex) {
        // Rollback the transaction in case of error
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        $error = 'An error occurred: ' . $ex->getMessage();
        header("location:client_appointment_booking.php?error=" . urlencode($error));
        exit;
    }
}

// Format all schedules for calendar
$calendarEvents = [];

// Add doctor schedules to calendar events
foreach ($doctorSchedules as $schedule) {
    // Generate time slots based on schedule
    $startTime = strtotime($schedule['start_time']);
    $endTime = strtotime($schedule['end_time']);
    $timeSlotMinutes = $schedule['time_slot_minutes'];
    $staffName = $schedule['doctor_name'];
    $staffRole = $schedule['role'];
    $scheduleDate = $schedule['schedule_date'];
    
    // Skip past schedules for client view
    if (strtotime($scheduleDate) < strtotime(date('Y-m-d'))) {
        continue;
    }
    
    // Set color based on staff role
    $backgroundColor = '#3699FF'; // Default blue for doctors
    
    // Format for calendar
    $calendarEvents[] = [
        'id' => 'doctor_' . $schedule['id'],
        'title' => $staffName . ' (Doctor)',
        'start' => $scheduleDate . 'T' . date('H:i:s', $startTime),
        'end' => $scheduleDate . 'T' . date('H:i:s', $endTime),
        'backgroundColor' => $backgroundColor,
        'borderColor' => $backgroundColor,
        'extendedProps' => [
            'staff_name' => $staffName,
            'doctor_name' => $staffName, // Add doctor_name explicitly
            'staff_role' => $staffRole,
            'time_slot' => $timeSlotMinutes,
            'max_patients' => $schedule['max_patients'],
            'schedule_id' => $schedule['id'],
            'schedule_type' => 'doctor'
        ]
    ];
}

// Add staff schedules to calendar events
foreach ($staffSchedules as $schedule) {
    // Generate time slots based on schedule
    $startTime = strtotime($schedule['start_time']);
    $endTime = strtotime($schedule['end_time']);
    $timeSlotMinutes = $schedule['time_slot_minutes'];
    $staffName = $schedule['staff_name'];
    $staffRole = $schedule['role'];
    $scheduleDate = $schedule['schedule_date'];
    
    // Skip past schedules for client view
    if (strtotime($scheduleDate) < strtotime(date('Y-m-d'))) {
        continue;
    }
    
    // Set color based on staff role
    $backgroundColor = ($staffRole == 'admin') ? '#8950FC' : '#1BC5BD'; // Purple for admins, teal for health workers
    
    // Format display name with role
    $displayName = $staffName . ' (' . ucfirst($staffRole) . ')';
    
    // Format for calendar
    $calendarEvents[] = [
        'id' => 'staff_' . $schedule['id'],
        'title' => $displayName,
        'start' => $scheduleDate . 'T' . date('H:i:s', $startTime),
        'end' => $scheduleDate . 'T' . date('H:i:s', $endTime),
        'backgroundColor' => $backgroundColor,
        'borderColor' => $backgroundColor,
        'extendedProps' => [
            'staff_name' => $staffName,
            'staff_role' => $staffRole,
            'time_slot' => $timeSlotMinutes,
            'max_patients' => $schedule['max_patients'],
            'schedule_id' => $schedule['id'],
            'schedule_type' => 'staff'
        ]
    ];
}

// Set a message if no schedules are available
if (empty($calendarEvents)) {
    $message = 'No approved schedules are currently available. Please check back later.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Appointment - Mamatid Health Center</title>
    <?php include './config/site_css_links.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    
    <!-- FullCalendar CSS -->
    <link href="plugins/fullcalendar/main.min.css" rel="stylesheet">

    <style>
        :root {
            --transition-speed: 0.3s;
            --primary-color: #3699FF;
            --secondary-color: #6993FF;
            --success-color: #1BC5BD;
            --info-color: #8950FC;
            --warning-color: #FFA800;
            --danger-color: #F64E60;
            --light-color: #F3F6F9;
            --dark-color: #1a1a2d;
        }

        /* Fix for SweetAlert2 font loading issue */
        .swal2-popup {
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
        }
        
        /* Override SweetAlert2 icons to use FontAwesome instead of built-in icons */
        .swal2-icon {
            font-family: 'Font Awesome 5 Free' !important;
        }

        /* Modern Card Styles */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            transition: transform var(--transition-speed);
        }

        .card:hover {
            transform: translateY(-5px);
        }

        /* Time Slot Styling */
        .time-slots-wrapper {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 5px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border: 1px solid #e2e8f0;
            margin-bottom: 15px;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-color) #e2e8f0;
        }
        
        .time-slots-wrapper::-webkit-scrollbar {
            width: 6px;
        }
        
        .time-slots-wrapper::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px;
        }
        
        .time-slots-wrapper::-webkit-scrollbar-thumb {
            background-color: var(--primary-color);
            border-radius: 10px;
        }
        
        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
            padding: 15px;
        }
        
        .time-slot {
            padding: 10px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all var(--transition-speed);
            background-color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            height: 100%;
        }
        
        .time-slot:hover:not(.booked) {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .time-slot.selected {
            border-color: var(--primary-color);
            background-color: rgba(54, 153, 255, 0.1);
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.2);
            transform: scale(1.05);
        }
        
        .time-slot.booked {
            background-color: #f8f9fa;
            border-color: #F64E60;
            cursor: not-allowed;
            opacity: 0.9;
            box-shadow: none;
        }
        
        .time-slot.booked::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(
                45deg,
                rgba(246, 78, 96, 0.05),
                rgba(246, 78, 96, 0.05) 10px,
                rgba(246, 78, 96, 0.1) 10px,
                rgba(246, 78, 96, 0.1) 20px
            );
            z-index: 1;
        }
        
        .time-slot .time-label {
            font-weight: 600;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
            font-size: 1rem;
        }
        
        .time-slot.booked .time-label {
            color: #7E8299;
        }
        
        .time-slot .time-label i {
            margin-right: 5px;
        }
        
        .time-slot.booked .time-label i {
            color: #F64E60;
        }
        
        .time-slot .badge {
            font-size: 0.7rem;
            padding: 4px 6px;
            border-radius: 20px;
            z-index: 2;
            margin-top: 5px;
        }
        
        .time-slot.booked .badge {
            background-color: #F64E60;
            color: white;
        }
        
        .time-slot .slot-status {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
            z-index: 2;
        }
        
        .time-period-divider {
            grid-column: 1 / -1;
            background-color: #e9ecef;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
            color: #495057;
            margin: 5px 0;
            display: flex;
            align-items: center;
        }
        
        .time-period-divider i {
            margin-right: 8px;
        }
        
        /* Calendar Legend Styling */
        .legend-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 1.25rem;
            margin-top: 1.5rem;
        }
        
        .legend-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
            text-align: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.75rem;
        }
        
        .legend-items {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            transition: all 0.2s;
            border: 1px solid #e9ecef;
        }
        
        .legend-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 0.75rem;
            display: inline-block;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .legend-text {
            font-weight: 500;
            font-size: 0.9rem;
        }
         
         /* Legend Styling */
         .time-slot-legend {
             display: flex;
             flex-wrap: wrap;
             justify-content: center;
             margin-bottom: 15px;
             padding: 12px;
             background-color: #f8f9fa;
             border-radius: 8px;
             box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
             border: 1px solid #e2e8f0;
         }
         
         .legend-item {
             display: flex;
             align-items: center;
             margin: 5px;
             padding: 6px 10px;
             border-radius: 6px;
             cursor: pointer;
             transition: all 0.2s ease;
             position: relative;
             min-width: 100px;
             justify-content: center;
         }
         
         .legend-item:hover {
             background-color: rgba(0, 0, 0, 0.03);
             transform: translateY(-2px);
         }
         
         .legend-item.active {
             background-color: rgba(54, 153, 255, 0.1);
             box-shadow: 0 2px 5px rgba(54, 153, 255, 0.2);
         }
         
         .legend-item.active::after {
             content: '✓';
             position: absolute;
             top: -5px;
             right: -5px;
             background-color: var(--primary-color);
             color: white;
             width: 18px;
             height: 18px;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             font-size: 10px;
             font-weight: bold;
             animation: pop-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
         }
         
         @keyframes pop-in {
             0% { transform: scale(0); }
             80% { transform: scale(1.2); }
             100% { transform: scale(1); }
         }
         
         .legend-item.available.active {
             background-color: rgba(54, 153, 255, 0.1);
         }
         
         .legend-item.selected.active {
             background-color: rgba(54, 153, 255, 0.15);
         }
         
         .legend-item.booked.active {
             background-color: rgba(246, 78, 96, 0.1);
         }
         
         /* Click effect */
         .legend-item:active {
             transform: scale(0.95);
         }
         
         .legend-color {
             width: 16px;
             height: 16px;
             border-radius: 4px;
             margin-right: 6px;
             box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
         }
         
         .legend-color.available {
             background-color: white;
             border: 2px solid #e2e8f0;
         }
         
         .legend-color.selected {
             background-color: rgba(54, 153, 255, 0.1);
             border: 2px solid var(--primary-color);
         }
         
         .legend-color.booked {
             background-color: #f8f9fa;
             border: 2px solid #F64E60;
             position: relative;
             overflow: hidden;
         }
         
         .legend-color.booked::after {
             content: '';
             position: absolute;
             top: 0;
             left: 0;
             width: 100%;
             height: 100%;
             background: repeating-linear-gradient(
                 45deg,
                 rgba(246, 78, 96, 0.05),
                 rgba(246, 78, 96, 0.05) 3px,
                 rgba(246, 78, 96, 0.1) 3px,
                 rgba(246, 78, 96, 0.1) 6px
             );
         }
         
         .legend-item span {
             font-weight: 500;
             font-size: 0.85rem;
         }
         
         .legend-item .count {
             margin-left: 4px;
             background-color: #e2e8f0;
             color: #4a5568;
             padding: 1px 5px;
             border-radius: 10px;
             font-size: 0.7rem;
             min-width: 18px;
             text-align: center;
         }
         
         .legend-item.available .count {
             background-color: #ebf8ff;
             color: #3182ce;
         }
         
         .legend-item.selected .count {
             background-color: rgba(54, 153, 255, 0.2);
             color: var(--primary-color);
         }
         
         .legend-item.booked .count {
             background-color: rgba(246, 78, 96, 0.1);
             color: #F64E60;
         }
         
         /* Time slot filter controls */
         .time-slot-filters {
             display: flex;
             align-items: center;
             justify-content: space-between;
             margin-bottom: 15px;
             flex-wrap: wrap;
         }
         
         .time-slot-search {
             position: relative;
             flex: 1;
             max-width: 200px;
             margin-right: 10px;
             margin-bottom: 10px;
         }
         
         .time-slot-search input {
             padding-left: 30px;
             border-radius: 20px;
             border: 1px solid #e2e8f0;
             font-size: 0.9rem;
             width: 100%;
         }
         
         .time-slot-search i {
             position: absolute;
             left: 10px;
             top: 50%;
             transform: translateY(-50%);
             color: #a0aec0;
         }
         
         .time-period-filter {
             display: flex;
             gap: 5px;
             margin-bottom: 10px;
         }
         
         .time-period-btn {
             padding: 5px 10px;
             border-radius: 20px;
             background-color: #f8f9fa;
             border: 1px solid #e2e8f0;
             font-size: 0.85rem;
             cursor: pointer;
             transition: all 0.2s;
         }
         
         .time-period-btn:hover {
             background-color: #e9ecef;
         }
         
         .time-period-btn.active {
             background-color: var(--primary-color);
             color: white;
             border-color: var(--primary-color);
         }
         
         /* Count animation */
         @keyframes pulse-once {
             0% { transform: scale(1); }
             50% { transform: scale(1.3); }
             100% { transform: scale(1); }
         }
         
         .count.pulse-once {
             animation: pulse-once 0.5s ease-in-out;
         }
         
         .time-slot.filtered {
             display: none;
         }
         
         /* Highlight animation for time slots */
         @keyframes highlight-pulse {
             0% { box-shadow: 0 0 0 0 rgba(54, 153, 255, 0.4); }
             70% { box-shadow: 0 0 0 10px rgba(54, 153, 255, 0); }
             100% { box-shadow: 0 0 0 0 rgba(54, 153, 255, 0); }
         }
         
         .time-slot.highlight-pulse {
             animation: highlight-pulse 1.5s infinite;
             transform: translateY(-2px);
             z-index: 5;
         }
         
         .time-slot.booked.highlight-pulse {
             animation: highlight-pulse-red 1.5s infinite;
         }
         
         @keyframes highlight-pulse-red {
             0% { box-shadow: 0 0 0 0 rgba(246, 78, 96, 0.4); }
             70% { box-shadow: 0 0 0 10px rgba(246, 78, 96, 0); }
             100% { box-shadow: 0 0 0 0 rgba(246, 78, 96, 0); }
         }
         
         /* Transition effects for filtering */
         .time-slot {
             transition: opacity 0.3s ease, transform 0.3s ease;
         }
         
         /* Slot Summary Styling */
         .slot-summary {
             border-radius: 8px;
             margin-bottom: 15px;
             transition: opacity 0.5s ease;
         }
         
         .slot-summary .alert {
             border-radius: 8px;
             box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
             border-left: 4px solid;
         }
         
         .slot-summary .alert-info {
             border-left-color: var(--primary-color);
         }
         
         .slot-summary .alert-warning {
             border-left-color: var(--warning-color);
         }
         
         .slot-summary .progress {
             height: 6px;
             margin-top: 5px;
             margin-bottom: 5px;
             border-radius: 3px;
             background-color: rgba(0, 0, 0, 0.05);
         }
         
         .slot-summary .small {
             padding: 8px;
             background-color: #f8f9fa;
             border-radius: 6px;
         }
         
         /* Progress Bar Styling */
         .slot-details {
             width: 100%;
             margin-left: 10px;
         }
         
         .booking-progress {
             display: flex;
             flex-direction: column;
             width: 100%;
         }
         
         .booking-progress .progress {
             height: 6px;
             margin-bottom: 5px;
             background-color: #e9ecef;
             border-radius: 3px;
             overflow: hidden;
         }
         
         .booking-progress small {
             font-size: 0.75rem;
             text-align: right;
         }
         
         .time-slot.booked .booking-progress .progress-bar {
             background-color: #F64E60;
         }
         
         /* Animation for booked slots */
         @keyframes pulse-border {
             0% { border-color: #F64E60; }
             50% { border-color: #ff8c98; }
             100% { border-color: #F64E60; }
         }
         
         .time-slot.booked {
             animation: pulse-border 2s infinite;
         }
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a1a2d;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            height: auto;
            font-size: 1rem;
            transition: all var(--transition-speed);
        }

        .form-control:focus {
            border-color: #3699FF;
            box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
        }

        .input-group-text {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background-color: #f8fafc;
            color: #4a5568;
            padding: 0.75rem 1rem;
        }

        .input-group > .form-control {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .input-group-prepend .input-group-text {
            border-right: 0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Button Styling */
        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all var(--transition-speed);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3699FF 0%, #6993FF 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(54, 153, 255, 0.4);
        }

        .btn i {
            margin-right: 0.5rem;
        }

        /* Alert Styling */
        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.5rem;
        }

        .alert-info {
            background-color: rgba(54, 153, 255, 0.1);
            color: #3699FF;
        }

        .alert-danger {
            background-color: rgba(246, 78, 96, 0.1);
            color: #F64E60;
        }

        /* Content Header */
        .content-header {
            padding: 20px 0;
        }

        .content-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }

        /* DateTime Badge Styling */
        #datetime {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2); }
            50% { box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4); }
            100% { box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2); }
        }

        /* Calendar Styling */
        .fc {
            font-family: inherit;
        }
        
        .fc-toolbar-title {
            font-size: 1.5rem !important;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .fc-button {
            padding: 0.5rem 1rem !important;
            font-weight: 500 !important;
            border-radius: 6px !important;
            box-shadow: none !important;
            transition: all 0.2s !important;
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        .fc-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(54, 153, 255, 0.3) !important;
            background-color: var(--secondary-color) !important;
            border-color: var(--secondary-color) !important;
        }
        
        .fc-day-today {
            background-color: rgba(54, 153, 255, 0.05) !important;
        }
        
        .fc-event {
            border-radius: 6px !important;
            padding: 3px 5px !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            border: none !important;
            transition: transform 0.2s !important;
            cursor: pointer;
            margin-bottom: 5px;
        }
        
        .fc-event:hover {
            transform: scale(1.02);
        }
        
        .fc-daygrid-day-number {
            font-weight: 500;
            color: #555;
        }
        
        .fc-col-header-cell-cushion {
            font-weight: 600;
            color: var(--dark-color);
        }

        /* Time Slot Styling */
        .time-slot {
            display: block;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 8px;
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.3s;
        }

        .time-slot:hover {
            background-color: #edf2f7;
            border-color: #cbd5e0;
        }

        .time-slot.selected {
            background-color: rgba(54, 153, 255, 0.1);
            border-color: #3699FF;
        }

        .time-slot.booked {
            background-color: #f1f1f1;
            border-color: #d1d1d1;
            color: #999;
            cursor: not-allowed;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 15px;
            }

            .card-header {
                padding: 1.25rem;
            }

            .form-control, 
            .input-group-text {
                padding: 0.625rem 0.875rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            #datetime {
                font-size: 1rem;
                padding: 8px 15px;
            }
        }

        /* Lock overlay for booked slots */
        .time-slot.locked {
            position: relative;
            overflow: hidden;
        }
        
        .lock-overlay {
            position: absolute;
            top: 0;
            right: 0;
            background-color: rgba(246, 78, 96, 0.1);
            color: #F64E60;
            padding: 5px;
            border-radius: 0 8px 0 8px;
            font-size: 0.8rem;
            z-index: 3;
        }
        
        .time-slot.locked:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.03);
            z-index: 1;
            pointer-events: none;
        }
        
        .time-slot.locked .time-label,
        .time-slot.locked .slot-details {
            position: relative;
            z-index: 2;
        }
        
        /* Shake animation for locked slots when clicked */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .time-slot.locked.shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        /* Lock icon for booked slots */
        .time-slot.booked .time-label i.fa-ban {
            color: #F64E60;
        }
        
        .time-slot.booked .time-label i.fa-lock {
            color: #F64E60;
            margin-left: 5px;
            animation: pulse-lock 1.5s infinite;
        }
        
        @keyframes pulse-lock {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        /* Event Modal Styling */
        .modal-content {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            padding: 1.25rem;
            border-bottom: none;
        }
        
        .modal-header .close {
            opacity: 0.8;
            text-shadow: none;
        }
        
        .modal-header .close:hover {
            opacity: 1;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .bg-purple {
            background-color: #8950FC !important;
        }
        
        .bg-teal {
            background-color: #1BC5BD !important;
        }
        
        .bg-info-light {
            background-color: rgba(54, 153, 255, 0.1);
        }
        
        .bg-success-light {
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        .bg-secondary-light {
            background-color: rgba(108, 117, 125, 0.1);
        }
        
        .bg-danger-light {
            background-color: rgba(246, 78, 96, 0.1);
        }
        
        .bg-warning-light {
            background-color: rgba(255, 168, 0, 0.1);
        }
        
        .event-date h4 {
            font-weight: 600;
            color: #333;
        }
        
        .info-item {
            margin-bottom: 0.5rem;
        }
        
        .info-label {
            color: #6c757d;
            margin-right: 0.25rem;
        }
        
        .info-value {
            color: #333;
        }
        
        .notes, .reason {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .modal-footer {
            border-top: none;
            padding: 0.75rem 1.25rem 1.25rem;
            justify-content: center;
        }
        
        .modal-footer .btn {
            min-width: 120px;
            font-weight: 500;
            border-radius: 6px;
            padding: 0.5rem 1.5rem;
            transition: all 0.2s;
        }
        
        .modal-footer .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/client_ui/client_header.php'; ?>
        <?php include './config/client_ui/client_sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6">
                            <h1>Book Appointment</h1>
                        </div>
                        <div class="col-12 col-md-6 text-md-right mt-3 mt-md-0">
                            <span id="datetime" class="d-inline-block"></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <?php if ($message): ?>
                        <div class="alert alert-info alert-dismissible fade show">
                            <i class="fas fa-info-circle mr-2"></i>
                            <?php echo $message; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-calendar-alt mr-2"></i>
                                        Available Doctor Schedules
                                    </h3>
                                    <div class="card-tools">
                                        <button type="button" id="refresh-calendar" class="btn btn-sm btn-info">
                                            <i class="fas fa-sync-alt mr-1"></i> Refresh Calendar
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="calendar"></div>
                                    <div class="mt-4">
                                        <div class="legend-container">
                                            <h5 class="legend-title">Schedule Legend</h5>
                                            <div class="legend-items">
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #3699FF;"></span>
                                                    <span class="legend-text">Doctor Schedules</span>
                                                </div>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #8950FC;"></span>
                                                    <span class="legend-text">Admin Schedules</span>
                                                </div>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #1BC5BD;"></span>
                                                    <span class="legend-text">Health Worker Schedules</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="alert alert-info p-3 rounded-lg shadow-sm">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <span>Only future dates are available for booking. Past appointments are automatically removed from view.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-calendar-plus mr-2"></i>
                                        Book Your Appointment
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div id="calendar"></div>
                                    
                                    <div class="mt-4">
                                        <h5 class="mb-3">Available Time Slots</h5>
                                        <div class="alert alert-info mb-3">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Each time slot can only be booked by one patient.
                                        </div>
                                        
                                        <div class="time-slot-filters">
                                            <div class="time-slot-search">
                                                <i class="fas fa-search"></i>
                                                <input type="text" id="timeSlotSearch" class="form-control" placeholder="Search time...">
                                            </div>
                                            <div class="time-period-filter">
                                                <button type="button" class="time-period-btn active" data-period="all">All</button>
                                                <button type="button" class="time-period-btn" data-period="morning">Morning</button>
                                                <button type="button" class="time-period-btn" data-period="afternoon">Afternoon</button>
                                                <button type="button" class="time-period-btn" data-period="evening">Evening</button>
                                            </div>
                                        </div>
                                        
                                        <div class="time-slot-legend mb-3">
                                            <div class="legend-item available active" data-filter="available">
                                                <div class="legend-color available"></div>
                                                <span>Available</span>
                                                <span class="count" id="available-count">0</span>
                                            </div>
                                            <div class="legend-item selected" data-filter="selected">
                                                <div class="legend-color selected"></div>
                                                <span>Selected</span>
                                                <span class="count" id="selected-count">0</span>
                                            </div>
                                            <div class="legend-item booked active" data-filter="booked">
                                                <div class="legend-color booked"></div>
                                                <span>Booked</span>
                                                <span class="count" id="booked-count">0</span>
                                            </div>
                                        </div>
                                        
                                        <div id="timeSlots" class="time-slot-container">
                                            <p class="text-muted">Please select a schedule from the calendar to view available time slots.</p>
                                        </div>
                                                </div>

                                    <form id="appointmentForm" method="post" action="" class="mt-4">
                                        <input type="hidden" id="scheduleId" name="schedule_id">
                                        <input type="hidden" id="appointmentTime" name="appointment_time">
                                        <input type="hidden" name="schedule_type" value="doctor">
                                        
                                        <div class="form-group">
                                            <label for="selectedDoctor">Doctor</label>
                                            <input type="text" class="form-control" id="selectedDoctor" readonly>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="selectedDate">Date</label>
                                            <input type="text" class="form-control" id="selectedDate" readonly>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="reason">Reason for Visit</label>
                                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                                        </div>
                                        
                                        <div class="text-center">
                                            <button type="submit" id="bookBtn" name="book_appointment" class="btn btn-primary" disabled>
                                                <i class="fas fa-calendar-check mr-2"></i> Book Appointment
                                        </button>
                                    </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php include './config/client_ui/client_footer.php'; ?>
    </div>
    <!-- ./wrapper -->
    <?php include './config/site_js_links.php'; ?>
    
    <!-- FullCalendar JS -->
    <script src="plugins/fullcalendar/main.min.js"></script>
    
    <script>
        $(function() {
            // Initialize SweetAlert2 with modern styling
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // Show success message if redirected from successful booking
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            if (message) {
                Toast.fire({
                    icon: 'success',
                    title: message
                });
            }

            // Show error message if exists
            const error = urlParams.get('error');
            if (error) {
                Toast.fire({
                    icon: 'error',
                    title: error
                });
            }
            
            // Handle refresh calendar button
            $('#refresh-calendar').click(function() {
                const $btn = $(this);
                
                // Show loading spinner
                $btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Refreshing...').prop('disabled', true);
                
                // Refresh calendar events
                calendar.refetchEvents();
                
                // Reset button after a delay
                setTimeout(function() {
                    $btn.html('<i class="fas fa-sync-alt mr-1"></i> Refresh Calendar').prop('disabled', false);
                    
                    // Show success message
                    Toast.fire({
                        icon: 'success',
                        title: 'Calendar refreshed successfully'
                    });
                }, 1000);
            });

            // Modern datetime display with animation
            function updateDateTime() {
                var now = new Date();
                var options = {
                    month: 'long',
                    day: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                };
                var formattedDateTime = now.toLocaleString('en-US', options);
                document.getElementById('datetime').innerHTML = formattedDateTime;
            }
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // Initialize Calendar
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: <?php echo json_encode($calendarEvents); ?>,
                height: 'auto',
                themeSystem: 'bootstrap',
                dayMaxEvents: true,
                navLinks: true,
                firstDay: 1, // Start week on Monday
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                eventDidMount: function(info) {
                    // Add tooltip for all events
                    $(info.el).tooltip({
                        title: info.event.title,
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                },
                eventClick: function(info) {
                    // Get event details
                    const event = info.event;
                    const props = event.extendedProps;
                    const scheduleId = props.schedule_id;
                    const timeSlot = props.time_slot;
                    const maxPatients = props.max_patients;
                    const scheduleType = props.schedule_type;
                    
                    // Check if the selected date is in the past
                    const eventDate = event.start;
                    const today = new Date();
                    today.setHours(0, 0, 0, 0); // Reset time part for date comparison
                    
                    if (eventDate < today) {
                        // Show error message for past dates
                        Toast.fire({
                            icon: 'error',
                            title: 'Cannot book appointments for past dates'
                        });
                        return;
                    }
                    
                    // Get staff name from all possible sources in props
                    let staffName = props.staff_name || props.doctor_name;
                    
                    // Debug to console
                    console.log('Event props:', props);
                    console.log('Event title:', event.title);
                    
                    if (!staffName) {
                        // Extract name from title if not in props
                        const titleParts = event.title.split(' (');
                        staffName = titleParts[0];
                        console.log('Extracted staff name from title:', staffName);
                    }
                    
                    // If still no name, use a default
                    if (!staffName || staffName === 'undefined') {
                        if (scheduleType === 'doctor') {
                            staffName = 'Selected Doctor';
                        } else {
                            staffName = 'Selected Staff';
                        }
                    }
                    
                    // Create a modal to display schedule details
                    var modalId = 'eventModal' + Date.now();
                    var modalTitle = '<i class="fas fa-calendar-alt mr-2"></i> Schedule Information';
                    var headerClass = '';
                    
                    // Set header class based on staff type
                    if (scheduleType === 'doctor') {
                        headerClass = 'bg-primary text-white';
                    } else if (props.staff_role === 'admin') {
                        headerClass = 'bg-purple text-white';
                    } else {
                        headerClass = 'bg-teal text-white';
                    }
                    
                    var formattedDate = event.start.toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    
                    var formattedStartTime = event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    var formattedEndTime = event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    var modalContent = `
                    <div class="schedule-details p-0">
                        <div class="card mb-0 border-0">
                            <div class="card-body p-0">
                                <div class="event-date text-center py-3 bg-info-light">
                                    <h4 class="mb-0">${formattedDate}</h4>
                                </div>
                                <div class="event-info p-4">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="info-item">
                                                <i class="fas fa-user-md text-muted mr-2"></i>
                                                <span class="info-label">Provider:</span>
                                                <span class="info-value font-weight-bold">${staffName}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-item">
                                                <i class="fas fa-clock text-muted mr-2"></i>
                                                <span class="info-label">Time:</span>
                                                <span class="info-value font-weight-bold">
                                                    ${formattedStartTime} - ${formattedEndTime}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="info-item">
                                                <i class="fas fa-hourglass-half text-muted mr-2"></i>
                                                <span class="info-label">Time Slot:</span>
                                                <span class="info-value font-weight-bold">${timeSlot} minutes</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-item">
                                                <i class="fas fa-users text-muted mr-2"></i>
                                                <span class="info-label">Max Patients:</span>
                                                <span class="info-value font-weight-bold">${maxPatients}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                    
                    // Create and show modal
                    var modalHTML = `
                    <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header ${headerClass}">
                                    <h5 class="modal-title">${modalTitle}</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body p-0">
                                    ${modalContent}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary book-this-schedule">Book This Schedule</button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                    
                    // Append modal to body
                    $('body').append(modalHTML);
                    
                    // Show modal
                    $('#' + modalId).modal('show');
                    
                    // Handle "Book This Schedule" button click
                    $('.book-this-schedule').click(function() {
                        // Close the modal
                        $('#' + modalId).modal('hide');
                        
                        // Set form values
                        $('#selectedDoctor').val(staffName);
                        $('#scheduleId').val(scheduleId);
                        
                        // Set the schedule type properly
                        console.log('Schedule type:', scheduleType);
                        $('input[name="schedule_type"]').val(scheduleType);
                        
                        // Update label based on schedule type
                        if (scheduleType === 'staff') {
                            $('label[for="selectedDoctor"]').text('Healthcare Provider');
                        } else {
                            $('label[for="selectedDoctor"]').text('Doctor');
                        }
                        
                        $('#selectedDate').val(formattedDate);
                        
                        // Generate time slots
                        generateTimeSlots(event.start, event.end, timeSlot, scheduleId, maxPatients);
                        
                        // Scroll to the booking form
                        $('html, body').animate({
                            scrollTop: $("#timeSlots").offset().top - 100
                        }, 500);
                    });
                    
                    // Remove modal from DOM when hidden
                    $('#' + modalId).on('hidden.bs.modal', function() {
                        $(this).remove();
                    });
                }
            });
            calendar.render();

            // Generate time slots based on schedule
            function generateTimeSlots(start, end, slotMinutes, scheduleId, maxPatients) {
                const timeSlotContainer = $('#timeSlots');
                timeSlotContainer.empty();
                
                // If no end time is provided, fetch schedule details
                if (!end) {
                    // Get the schedule date from the start parameter
                    var scheduleDate = start.toISOString().split('T')[0];
                    
                    // Show loading indicator
                    timeSlotContainer.html('<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div><p class="ml-2 text-info">Loading available time slots...</p></div>');
                    
                    // Fetch schedule details
                    $.ajax({
                        url: 'ajax/get_schedule_details.php',
                        type: 'POST',
                        data: {
                            schedule_id: scheduleId,
                            schedule_type: $('input[name="schedule_type"]').val()
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.error) {
                                timeSlotContainer.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>' + response.error + '</div>');
                                return;
                            }
                            
                            // Create end time from schedule data
                            var startTime = new Date(scheduleDate + 'T' + response.start_time);
                            var endTime = new Date(scheduleDate + 'T' + response.end_time);
                            
                            // Call generateTimeSlots again with complete parameters
                            generateTimeSlots(
                                startTime, 
                                endTime, 
                                response.time_slot_minutes, 
                                scheduleId, 
                                response.max_patients
                            );
                        },
                        error: function() {
                            timeSlotContainer.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>Error loading schedule details. Please try again.</div>');
                        }
                    });
                    return;
                }
                
                // Get available slots from the server
                $.ajax({
                    url: 'ajax/client_check_booked_appointment_slots.php',
                    type: 'POST',
                    data: {
                        schedule_id: scheduleId,
                        schedule_type: $('input[name="schedule_type"]').val(),
                        client_id: <?php echo isset($_SESSION['client_id']) ? $_SESSION['client_id'] : 'null'; ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Clear previous time slots
                        timeSlotContainer.empty();
                        
                        // Check for error in response
                        if (response.error) {
                            timeSlotContainer.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>' + response.error + '</div>');
                            $('#bookBtn').prop('disabled', true);
                            return;
                        }
                        
                        var bookedSlots = response.booked_slots;
                        var slotStatuses = response.slot_statuses || {};
                        var serverMaxPatients = response.max_patients;
                        var clientAppointments = response.client_appointments || [];
                        
                        // Use server-provided max_patients if available
                        if (serverMaxPatients) {
                            maxPatients = serverMaxPatients;
                        }
                        
                        // Generate time slots based on schedule
                        var currentTime = new Date(start);
                        var endTime = new Date(end);
                        var hasAvailableSlots = false;
                        
                        // Create a wrapper for scrollable time slots
                        const slotsWrapper = $('<div class="time-slots-wrapper"></div>');
                        // Create a grid container for time slots
                        const slotsContainer = $('<div class="time-slots-grid"></div>');
                        const loadingOverlay = $('<div class="position-absolute w-100 h-100 d-none" style="background: rgba(255,255,255,0.8); z-index: 10; display: flex; align-items: center; justify-content: center;"><div class="spinner-border text-primary" role="status"></div><span class="ml-2">Checking availability...</span></div>');
                        
                        // Group time slots by period (morning, afternoon, evening)
                        let morningSlots = [];
                        let afternoonSlots = [];
                        let eveningSlots = [];
                        let allTimeSlots = [];
                        
                        while (currentTime < endTime) {
                            var timeString = currentTime.toTimeString().substring(0, 5);
                            var formattedTime = currentTime.toLocaleTimeString('en-US', { 
                                hour: '2-digit', 
                                minute: '2-digit'
                            });
                            
                            // Check if this time slot is in the past (current day but earlier time)
                            var slotDateTime = new Date(start);
                            slotDateTime.setHours(currentTime.getHours(), currentTime.getMinutes());
                            var isPastTime = slotDateTime < new Date();
                            
                            // Skip if the slot is in the past
                            if (isPastTime) {
                                currentTime.setMinutes(currentTime.getMinutes() + parseInt(slotMinutes));
                                continue;
                            }
                            
                            // Check if this slot is fully booked
                            var slotCount = 0;
                            var isBooked = false;
                            var isPast = false;
                            
                            // First check the appointment_slots table status
                            if (slotStatuses && slotStatuses[timeString + ':00']) {
                                isBooked = slotStatuses[timeString + ':00'].is_booked === 1;
                            }
                            
                            // Then check the actual appointments count and if it's past
                            if (bookedSlots && bookedSlots[timeString + ':00']) {
                                slotCount = parseInt(bookedSlots[timeString + ':00'].count);
                                isBooked = bookedSlots[timeString + ':00'].is_full;
                                isPast = bookedSlots[timeString + ':00'].is_past || false;
                            }
                            
                            // Skip if the slot is in the past
                            if (isPast) {
                                currentTime.setMinutes(currentTime.getMinutes() + parseInt(slotMinutes));
                                continue;
                            }
                            
                            // Check if client already has an appointment at this time
                            var clientHasAppointment = clientAppointments.includes(timeString + ':00');
                            
                            var remainingSlots = maxPatients - slotCount;
                            
                            // Determine time period (morning, afternoon, evening)
                            const hour = currentTime.getHours();
                            let timePeriod = 'morning';
                            if (hour >= 12 && hour < 17) {
                                timePeriod = 'afternoon';
                            } else if (hour >= 17) {
                                timePeriod = 'evening';
                            }
                            
                            let slotElement;
                            
                            if (!isBooked && !clientHasAppointment) {
                                hasAvailableSlots = true;
                                
                                slotElement = $('<div class="time-slot" data-time="' + timeString + ':00" data-schedule-id="' + scheduleId + '" data-period="' + timePeriod + '">' +
                                    '<div class="time-label"><i class="far fa-clock"></i>' + formattedTime + '</div>' +
                                    '<span class="badge badge-info">' + remainingSlots + ' available</span>' +
                                    '<div class="slot-status">Open</div>' +
                                    '</div>');
                                
                            } else if (clientHasAppointment) {
                                // Client already has an appointment at this time
                                slotElement = $('<div class="time-slot booked locked" data-period="' + timePeriod + '">' +
                                    '<div class="time-label"><i class="fas fa-calendar-check"></i>' + formattedTime + '</div>' +
                                    '<span class="badge badge-primary">Your appointment</span>' +
                                    '<div class="slot-status">Booked by you</div>' +
                                    '</div>');
                                
                            } else {
                                slotElement = $('<div class="time-slot booked locked" data-period="' + timePeriod + '">' +
                                    '<div class="time-label"><i class="fas fa-ban"></i>' + formattedTime + '</div>' +
                                    '<span class="badge badge-danger">Booked</span>' +
                                    '<div class="slot-status">Unavailable</div>' +
                                    '</div>');
                            }
                            
                            // Add to the appropriate time period array
                            if (timePeriod === 'morning') {
                                morningSlots.push(slotElement);
                            } else if (timePeriod === 'afternoon') {
                                afternoonSlots.push(slotElement);
                            } else {
                                eveningSlots.push(slotElement);
                            }
                            
                            allTimeSlots.push(slotElement);
                            
                            // Add minutes to current time
                            currentTime.setMinutes(currentTime.getMinutes() + parseInt(slotMinutes));
                        }
                        
                        // Add time period dividers and slots to the container
                        if (morningSlots.length > 0) {
                            slotsContainer.append('<div class="time-period-divider"><i class="fas fa-sun"></i> Morning</div>');
                            morningSlots.forEach(slot => slotsContainer.append(slot));
                        }
                        
                        if (afternoonSlots.length > 0) {
                            slotsContainer.append('<div class="time-period-divider"><i class="fas fa-cloud-sun"></i> Afternoon</div>');
                            afternoonSlots.forEach(slot => slotsContainer.append(slot));
                        }
                        
                        if (eveningSlots.length > 0) {
                            slotsContainer.append('<div class="time-period-divider"><i class="fas fa-moon"></i> Evening</div>');
                            eveningSlots.forEach(slot => slotsContainer.append(slot));
                        }
                        
                        // Add the slots container to the wrapper
                        slotsWrapper.append(slotsContainer);
                        
                        // Add the wrapper to the main container
                        timeSlotContainer.append(slotsWrapper);
                        
                        if (!hasAvailableSlots) {
                            timeSlotContainer.prepend('<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>No available time slots for this schedule.</div>');
                            $('#bookBtn').prop('disabled', true);
                        } else {
                            // Initialize tooltips
                            $('[data-toggle="tooltip"]').tooltip();
                            
                            // Handle time slot selection
                            $('.time-slot:not(.booked)').click(function() {
                                $('.time-slot').removeClass('selected');
                                $(this).addClass('selected');
                                $('#appointmentTime').val($(this).data('time'));
                                $('#bookBtn').prop('disabled', false);
                                
                                // Update tooltip
                                $(this).attr('title', 'Selected time slot')
                                       .tooltip('dispose')
                                       .tooltip();
                                
                                // Update legend counts
                                updateLegendCounts();
                            });
                            
                            // Add lock effect to booked slots
                            $('.time-slot.locked').append('<div class="lock-overlay"><i class="fas fa-lock"></i></div>');
                            
                            // Update legend counts initially
                            updateLegendCounts();
                            
                            // Initialize legend functionality
                            initializeLegend();
                            
                            // Add a summary of available slots
                            addSlotsSummary();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', status, error);
                        console.log('Response:', xhr.responseText);
                        timeSlotContainer.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>Error loading time slots. Please try again.</div>');
                        $('#bookBtn').prop('disabled', true);
                    }
                });
            }
            
            // Function to update legend counts
            function updateLegendCounts() {
                const availableCount = $('.time-slot:not(.booked):not(.selected)').length;
                const selectedCount = $('.time-slot.selected').length;
                const bookedCount = $('.time-slot.booked').length;
                const totalCount = availableCount + selectedCount + bookedCount;
                
                // Update count badges
                $('#available-count').text(availableCount);
                $('#selected-count').text(selectedCount);
                $('#booked-count').text(bookedCount);
                
                // Animate count changes
                $('.legend-item .count').each(function() {
                    $(this).addClass('pulse-once');
                    setTimeout(() => {
                        $(this).removeClass('pulse-once');
                    }, 500);
                });
                
                // Update the progress bar in the summary if it exists
                const $progressBar = $('.slot-summary .progress-bar');
                if ($progressBar.length) {
                    const availablePercentage = Math.round(((availableCount + selectedCount) / totalCount) * 100);
                    $progressBar.css('width', availablePercentage + '%');
                    $progressBar.attr('aria-valuenow', availableCount + selectedCount);
                    $progressBar.attr('aria-valuemax', totalCount);
                    
                    // Update the text
                    $('.slot-summary small.text-muted').text(
                        (availableCount + selectedCount) + ' of ' + totalCount + ' slots available'
                    );
                }
            }
            
            // Function to initialize legend functionality
            function initializeLegend() {
                $('.legend-item').click(function() {
                    const filterType = $(this).data('filter');
                    $(this).toggleClass('active');
                    
                    // Apply filters based on active legend items
                    applyFilters();
                    
                    // Show toast notification
                    const isActive = $(this).hasClass('active');
                    const actionText = isActive ? 'showing' : 'hiding';
                    const itemText = $(this).find('span').first().text();
                    
                    Toast.fire({
                        icon: 'info',
                        title: `Now ${actionText} ${itemText} time slots`
                    });
                });
                
                // Add hover effect to highlight corresponding time slots
                $('.legend-item').hover(
                    function() {
                        const filterType = $(this).data('filter');
                        if (filterType === 'available') {
                            $('.time-slot:not(.booked):not(.selected)').addClass('highlight-pulse');
                        } else if (filterType === 'selected') {
                            $('.time-slot.selected').addClass('highlight-pulse');
                        } else if (filterType === 'booked') {
                            $('.time-slot.booked').addClass('highlight-pulse');
                        }
                    },
                    function() {
                        $('.time-slot').removeClass('highlight-pulse');
                    }
                );
            }
            
            // Function to apply filters based on active legend items
            function applyFilters() {
                // Get active filters
                const showAvailable = $('.legend-item.available').hasClass('active');
                const showSelected = $('.legend-item.selected').hasClass('active');
                const showBooked = $('.legend-item.booked').hasClass('active');
                
                // First fade out all slots that will be filtered
                $('.time-slot').each(function() {
                    const $slot = $(this);
                    let shouldShow = true;
                    
                    if ($slot.hasClass('booked') && !showBooked) {
                        shouldShow = false;
                    } else if ($slot.hasClass('selected') && !showSelected) {
                        shouldShow = false;
                    } else if (!$slot.hasClass('booked') && !$slot.hasClass('selected') && !showAvailable) {
                        shouldShow = false;
                    }
                    
                    if (shouldShow) {
                        // If the slot should be shown but is currently filtered
                        if ($slot.hasClass('filtered')) {
                            $slot.css('opacity', '0');
                            setTimeout(function() {
                                $slot.removeClass('filtered');
                                $slot.css('opacity', '1');
                                $slot.css('transform', 'translateY(0)');
                            }, 300);
                        }
                    } else {
                        // If the slot should be hidden
                        $slot.css('opacity', '0');
                        $slot.css('transform', 'translateY(-10px)');
                        setTimeout(function() {
                            $slot.addClass('filtered');
                        }, 300);
                    }
                });
                
                // Check if any slots are visible after a short delay
                setTimeout(function() {
                    const visibleSlots = $('.time-slot:not(.filtered)').length;
                    if (visibleSlots === 0) {
                        // If no slots are visible, show a message
                        if ($('#no-visible-slots-message').length === 0) {
                            const message = $('<p id="no-visible-slots-message" class="text-warning">No time slots are currently visible. Use the legend above to show slots.</p>');
                            message.css('opacity', '0');
                            $('#timeSlots').prepend(message);
                            setTimeout(function() {
                                message.css('opacity', '1');
                            }, 100);
                        }
                    } else {
                        // Remove the message if slots are visible
                        $('#no-visible-slots-message').fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                }, 350);
                
                // Update the legend item counts
                updateLegendCounts();
            }

            // Form validation
            $('#appointmentForm').submit(function(e) {
                console.log('Form submitted');
                
                if (!$('#scheduleId').val()) {
                    e.preventDefault();
                    Toast.fire({
                        icon: 'error',
                        title: 'Please select a schedule from the calendar'
                    });
                    console.log('No schedule ID');
                    return false;
                }
                
                if (!$('#appointmentTime').val()) {
                    e.preventDefault();
                    Toast.fire({
                        icon: 'error',
                        title: 'Please select a time slot'
                    });
                    console.log('No appointment time');
                    return false;
                }
                
                // Show loading indicator using FontAwesome instead of built-in icons
                Swal.fire({
                    title: 'Checking availability',
                    html: '<i class="fas fa-spinner fa-spin fa-2x mb-3"></i><br>Please wait while we verify this time slot is still available...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false
                });
                
                // Check real-time availability before submitting
                e.preventDefault();
                console.log('Checking availability...');
                console.log('Schedule ID:', $('#scheduleId').val());
                console.log('Appointment Time:', $('#appointmentTime').val());
                
                $.ajax({
                    url: 'ajax/client_check_timeslot_availability.php',
                    type: 'POST',
                    data: {
                        schedule_id: $('#scheduleId').val(),
                        appointment_time: $('#appointmentTime').val(),
                        schedule_type: $('input[name="schedule_type"]').val(),
                        client_id: <?php echo isset($_SESSION['client_id']) ? $_SESSION['client_id'] : 'null'; ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Availability response:', response);
                        
                        // Close loading indicator
                        Swal.close();
                        
                        if (response.error) {
                            Toast.fire({
                                icon: 'error',
                                title: response.error
                            });
                            console.log('Error:', response.error);
                            return;
                        }
                        
                        if (!response.is_available) {
                            Swal.fire({
                                icon: 'error',
                                html: '<i class="fas fa-exclamation-circle text-danger fa-2x mb-3"></i><br><strong>Time slot no longer available</strong><br>' + 
                                      (response.client_has_appointment ? 
                                       'You already have an appointment booked for this time slot.' : 
                                       'This time slot has been booked by someone else. Please select another time.'),
                                confirmButtonText: 'Select another time'
                            });
                            console.log('Slot not available');
                            
                            // Refresh the time slots
                            var scheduleId = $('#scheduleId').val();
                            var events = calendar.getEvents();
                            var originalEvent = events.find(function(event) {
                                return event.extendedProps.schedule_id == scheduleId;
                            });
                            
                            // Refresh the time slots with a slight delay to allow the user to see the message
                            setTimeout(function() {
                                if (originalEvent) {
                                    console.log('Original event found:', originalEvent);
                                    generateTimeSlots(
                                        originalEvent.start,
                                        originalEvent.end,
                                        30,
                                        $('#scheduleId').val(),
                                        response.max_patients
                                    );
                                } else {
                                    console.log('Original event not found, using fallback');
                                    // Fallback if event not found
                                    generateTimeSlots(
                                        null,
                                        null,
                                        30,
                                        $('#scheduleId').val(),
                                        response.max_patients
                                    );
                                }
                            }, 500);
                            return;
                        }
                        
                        // If available, show confirmation and submit
                        Swal.fire({
                            html: '<i class="fas fa-question-circle text-primary fa-2x mb-3"></i><br><strong>Confirm Booking</strong><br>Would you like to book this appointment?',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, book it!',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#3699FF',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Show loading indicator during form submission
                                Swal.fire({
                                    html: '<i class="fas fa-spinner fa-spin fa-2x mb-3"></i><br><strong>Booking appointment</strong><br>Please wait...',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false
                                });
                                
                                // Use the original form but ensure it has the correct values
                                $('#scheduleId').val($('#scheduleId').val());
                                $('#appointmentTime').val($('#appointmentTime').val());
                                
                                // Add a hidden input for the book_appointment flag if it doesn't exist
                                if ($('input[name="book_appointment"]').length === 0) {
                                    $('#appointmentForm').append(
                                        $('<input>').attr({
                                            type: 'hidden',
                                            name: 'book_appointment',
                                            value: '1'
                                        })
                                    );
                                }
                                
                                // Submit the original form
                                $('#appointmentForm').off('submit').submit();
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', status, error);
                        console.log('Response:', xhr.responseText);
                        
                        // Close loading indicator
                        Swal.close();
                        
                        // Show error message
                        Swal.fire({
                            html: '<i class="fas fa-exclamation-triangle text-warning fa-2x mb-3"></i><br><strong>Connection Error</strong><br>Could not verify slot availability. Please try again.',
                            confirmButtonText: 'Try Again'
                        });
                    }
                });
                return false;
            });

            // Function to add a summary of available slots
            function addSlotsSummary() {
                const availableCount = $('.time-slot:not(.booked)').length;
                const totalCount = $('.time-slot').length;
                const doctorName = $('#selectedDoctor').val();
                const selectedDate = $('#selectedDate').val();
                
                // Create a summary element
                const summaryEl = $('<div class="slot-summary mb-3"></div>');
                
                // Add availability information
                if (availableCount > 0) {
                    summaryEl.append(`
                        <div class="alert alert-info mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle mr-2"></i>
                                <div>
                                    <strong>${doctorName}</strong> has <strong>${availableCount}</strong> available time slots on <strong>${selectedDate}</strong>.
                                    <div class="mt-1">
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-info" role="progressbar" 
                                                style="width: ${Math.round((availableCount / totalCount) * 100)}%" 
                                                aria-valuenow="${availableCount}" aria-valuemin="0" aria-valuemax="${totalCount}">
                                            </div>
                                        </div>
                                        <small class="text-muted">${availableCount} of ${totalCount} slots available</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                } else {
                    summaryEl.append(`
                        <div class="alert alert-warning mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <div>
                                    <strong>${doctorName}</strong> has no available time slots on <strong>${selectedDate}</strong>.
                                    <div class="mt-1">Please select another date from the calendar.</div>
                                </div>
                            </div>
                        </div>
                    `);
                }
                
                // Add instructions
                summaryEl.append(`
                    <div class="small text-muted">
                        <i class="fas fa-mouse-pointer mr-1"></i> Click on an available time slot to select it.
                        <br>
                        <i class="fas fa-filter mr-1"></i> Use the legend above to filter time slots.
                    </div>
                `);
                
                // Add to the container with a fade-in effect
                summaryEl.css('opacity', '0');
                $('#timeSlots').prepend(summaryEl);
                setTimeout(() => {
                    summaryEl.css('transition', 'opacity 0.5s ease');
                    summaryEl.css('opacity', '1');
                }, 100);
            }

            // Add click handler for locked slots to show a message
            $(document).on('click', '.time-slot.locked', function() {
                // Add shake animation
                $(this).addClass('shake');
                
                // Remove shake class after animation completes
                setTimeout(() => {
                    $(this).removeClass('shake');
                }, 500);
                
                // Show message
                const isClientSlot = $(this).find('.badge-primary').length > 0;
                
                Toast.fire({
                    icon: isClientSlot ? 'info' : 'error',
                    title: isClientSlot ? 
                        'You already have an appointment booked for this time slot.' : 
                        'This time slot is fully booked and cannot be selected.'
                });
            });
        });
    </script>
</body>
</html> 
