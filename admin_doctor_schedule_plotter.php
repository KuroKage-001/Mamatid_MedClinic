<?php
include './config/db_connection.php';
require_once './system/utilities/admin_client_role_functions_services.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission - only doctors can access this page
requireRole(['doctor']);

$message = '';
$error = '';
$doctorId = $_SESSION['user_id'];
$doctorName = $_SESSION['display_name'];

// Get message/error from URL if redirected
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Automatically update past appointments to completed status (both client and walk-in appointments)
try {
    $con->beginTransaction();
    
    // Update past client appointments to completed status
    $updateClientQuery = "UPDATE admin_clients_appointments
                          SET status = 'completed', updated_at = NOW() 
                          WHERE CONCAT(appointment_date, ' ', appointment_time) < NOW() 
                          AND status = 'approved'
                          AND doctor_id = ?";
    $updateClientStmt = $con->prepare($updateClientQuery);
    $updateClientStmt->execute([$doctorId]);
    $updatedClientCount = $updateClientStmt->rowCount();
    
    // Update past walk-in appointments to completed status
    $updateWalkinQuery = "UPDATE admin_walkin_appointments
                          SET status = 'completed', updated_at = NOW() 
                          WHERE CONCAT(appointment_date, ' ', appointment_time) < NOW() 
                          AND status = 'approved'
                          AND provider_id = ? AND provider_type = 'doctor'";
    $updateWalkinStmt = $con->prepare($updateWalkinQuery);
    $updateWalkinStmt->execute([$doctorId]);
    $updatedWalkinCount = $updateWalkinStmt->rowCount();
    
    $totalUpdated = $updatedClientCount + $updatedWalkinCount;
    if ($totalUpdated > 0) {
        $message = $totalUpdated . " past appointments were automatically marked as completed.";
    }
    
    $con->commit();
} catch(PDOException $ex) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    $error = "Error updating past appointments: " . $ex->getMessage();
}

// Generate a unique form token if it doesn't exist
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

// Handle schedule submission
if (isset($_POST['submit_schedule'])) {
    // Verify form token to prevent duplicate submissions
    if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        // Generate a new token for next submission
        $_SESSION['form_token'] = bin2hex(random_bytes(32));
        
        try {
            $con->beginTransaction();
        
        // Delete existing schedule entries for the selected dates if requested
        if (isset($_POST['replace_existing'])) {
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            
            $deleteQuery = "DELETE FROM admin_doctor_schedules 
                            WHERE doctor_id = ? 
                            AND schedule_date BETWEEN ? AND ?";
            $deleteStmt = $con->prepare($deleteQuery);
            $deleteStmt->execute([$doctorId, $startDate, $endDate]);
        }
        
        // Insert new schedule entries
        $scheduleQuery = "INSERT INTO admin_doctor_schedules 
                         (doctor_id, schedule_date, start_time, end_time, time_slot_minutes, max_patients, notes) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        $scheduleStmt = $con->prepare($scheduleQuery);
        
        // Get form data
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $startTime = $_POST['start_time'];
        $endTime = $_POST['end_time'];
        $timeSlot = $_POST['time_slot'];
        $maxPatients = $_POST['max_patients'];
        $notes = $_POST['notes'];
        
        // Create schedule for each day in the range
        $currentDate = new DateTime($startDate);
        $lastDate = new DateTime($endDate);
        
        while ($currentDate <= $lastDate) {
            $dateStr = $currentDate->format('Y-m-d');
            
            // Skip if weekend is selected and the current day is a weekend
            if (isset($_POST['skip_weekends']) && 
                ($currentDate->format('N') == 6 || $currentDate->format('N') == 7)) {
                $currentDate->modify('+1 day');
                continue;
            }
            
            // Check if schedule already exists for this date and time range
            $checkDuplicateQuery = "SELECT id FROM admin_doctor_schedules 
                                   WHERE doctor_id = ? 
                                   AND schedule_date = ? 
                                   AND start_time = ? 
                                   AND end_time = ? 
                                   AND (is_deleted = 0 OR is_deleted IS NULL)";
            $checkDuplicateStmt = $con->prepare($checkDuplicateQuery);
            $checkDuplicateStmt->execute([$doctorId, $dateStr, $startTime, $endTime]);
            
            // Skip if duplicate found
            if ($checkDuplicateStmt->rowCount() > 0) {
                $currentDate->modify('+1 day');
                continue;
            }
            
            $scheduleStmt->execute([
                $doctorId,
                $dateStr,
                $startTime,
                $endTime,
                $timeSlot,
                $maxPatients,
                $notes
            ]);
            
            // Get the ID of the newly created schedule
            $scheduleId = $con->lastInsertId();
            
            // Create appointment slots for this schedule
            $startDateTime = strtotime($dateStr . ' ' . $startTime);
            $endDateTime = strtotime($dateStr . ' ' . $endTime);
            $slotDuration = $timeSlot * 60; // Convert minutes to seconds
            
            // Generate slots for the entire schedule duration
            $currentSlot = $startDateTime;
            while ($currentSlot < $endDateTime) {
                $slotTime = date('H:i:s', $currentSlot);
                
                // Check if slot already exists
                $checkSlotQuery = "SELECT id FROM admin_doctor_appointment_slots WHERE schedule_id = ? AND slot_time = ?";
                $checkSlotStmt = $con->prepare($checkSlotQuery);
                $checkSlotStmt->execute([$scheduleId, $slotTime]);
                
                if ($checkSlotStmt->rowCount() == 0) {
                    // Insert new slot if it doesn't exist
                    $insertSlotQuery = "INSERT INTO admin_doctor_appointment_slots (schedule_id, slot_time, is_booked) VALUES (?, ?, 0)";
                    $insertSlotStmt = $con->prepare($insertSlotQuery);
                    $insertSlotStmt->execute([$scheduleId, $slotTime]);
                }
                
                // Move to next slot
                $currentSlot += $slotDuration;
            }
            
            $currentDate->modify('+1 day');
        }
        
        $con->commit();
        $message = "Schedule successfully saved! Your schedule will be reviewed by an administrator.";
        
        // Redirect to prevent form resubmission on refresh
        header("Location: admin_doctor_schedule_plotter.php?message=" . urlencode($message));
        exit;
        
    } catch(PDOException $ex) {
        $con->rollback();
        $error = "Error: " . $ex->getMessage();
    }
  }
}

// Fetch doctor's existing schedules
$query = "SELECT * FROM admin_doctor_schedules 
          WHERE doctor_id = ? 
          AND (is_deleted = 0 OR is_deleted IS NULL)
          ORDER BY schedule_date ASC";
$stmt = $con->prepare($query);
$stmt->execute([$doctorId]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get booked appointments for this doctor (both regular and walk-in)
$appointmentsQuery = "SELECT a.*, ds.time_slot_minutes, 'regular' as appointment_type
                     FROM admin_clients_appointments a 
                     JOIN admin_doctor_schedules ds ON a.schedule_id = ds.id 
                     WHERE a.doctor_id = ? AND a.status != 'cancelled' AND a.is_archived = 0
                     UNION ALL
                     SELECT w.id, w.patient_name, w.phone_number, w.address, w.date_of_birth, w.gender,
                            w.appointment_date, w.appointment_time, w.reason, w.status, w.notes,
                            w.schedule_id, w.provider_id as doctor_id, w.created_at, w.updated_at,
                            0 as email_sent, 0 as reminder_sent, w.is_archived, 
                            NULL as view_token, NULL as token_expiry, NULL as archived_at, NULL as archived_by, NULL as archive_reason,
                            1 as is_walkin, ds2.time_slot_minutes, 'walk-in' as appointment_type
                     FROM admin_walkin_appointments w
                     JOIN admin_doctor_schedules ds2 ON w.schedule_id = ds2.id 
                     WHERE w.provider_id = ? AND w.provider_type = 'doctor' 
                     AND w.status != 'cancelled' AND w.is_archived = 0";
$appointmentsStmt = $con->prepare($appointmentsQuery);
$appointmentsStmt->execute([$doctorId, $doctorId]);
$appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Format schedules for calendar
$calendarEvents = [];
foreach ($schedules as $schedule) {
    $isPast = strtotime($schedule['schedule_date']) < strtotime(date('Y-m-d'));
    $status = $schedule['is_approved'] ? 'Approved' : 'Pending';
    $color = $isPast ? '#A0A0A0' : ($schedule['is_approved'] ? '#1BC5BD' : '#FFA800');
    
    $calendarEvents[] = [
        'id' => 'schedule_' . $schedule['id'],
        'title' => $doctorName . ' (' . $status . ')' . ($isPast ? ' [Past]' : ''),
        'start' => $schedule['schedule_date'] . 'T' . $schedule['start_time'],
        'end' => $schedule['schedule_date'] . 'T' . $schedule['end_time'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'max_patients' => $schedule['max_patients'],
            'time_slot' => $schedule['time_slot_minutes'],
            'notes' => $schedule['notes'],
            'is_approved' => $schedule['is_approved'],
            'approval_notes' => $schedule['approval_notes'],
            'type' => 'schedule',
            'is_past' => $isPast
        ]
    ];
}

// Add booked appointments to calendar
foreach ($appointments as $appointment) {
    $appointmentTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
    $endTime = $appointmentTime + ($appointment['time_slot_minutes'] * 60);
    $isPast = $appointmentTime < time();
    $isWalkIn = isset($appointment['appointment_type']) && $appointment['appointment_type'] == 'walk-in';
    
    // Set color based on status, whether it's past, and if it's a walk-in
    $color = '#F64E60'; // Default red for active appointments
    if ($isPast) {
        $color = '#000000'; // Black for all past appointments
    } else if ($isWalkIn) {
        $color = '#FF8F00'; // Orange for active walk-in appointments
    }
    
    // Create appointment title with walk-in indicator
    $appointmentTitle = ($isWalkIn ? 'Walk-in: ' : 'Booked: ') . $appointment['patient_name'];
    if ($isPast) {
        $appointmentTitle .= ' [Past]';
    }
    
    $calendarEvents[] = [
        'id' => ($isWalkIn ? 'walkin_' : 'appointment_') . $appointment['id'],
        'title' => $appointmentTitle,
        'start' => date('Y-m-d\TH:i:s', $appointmentTime),
        'end' => date('Y-m-d\TH:i:s', $endTime),
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'patient_name' => $appointment['patient_name'],
            'reason' => $appointment['reason'],
            'status' => $appointment['status'],
            'type' => 'appointment',
            'is_past' => $isPast,
            'is_walk_in' => $isWalkIn,
            'appointment_type' => $isWalkIn ? 'walk-in' : 'regular',
            'appointment_id' => $appointment['id']
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <?php include './config/data_tables_css_js.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <link href="plugins/fullcalendar/main.min.css" rel="stylesheet">
    <title>Doctor Schedule Plotter - Mamatid Health Center System</title>
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

        /* --- PAGE BACKGROUND --- */
        body,
        .content-wrapper {
            background: linear-gradient(135deg, #232b3e 0%, #34495e 100%) !important;
            min-height: 100vh;
        }

        /* --- SET AVAILABILITY BUTTON --- */
        .btn-set-availability {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: #fff !important;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.05rem;
            padding: 0.85rem 2.1rem;
            box-shadow: 0 8px 24px rgba(52, 152, 219, 0.18);
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
        }
        .btn-set-availability i {
            font-size: 1.2rem;
        }
        .btn-set-availability:hover, .btn-set-availability:focus {
            background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
            color: #fff !important;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 12px 32px rgba(52, 152, 219, 0.25);
        }
        .btn-set-availability:active {
            transform: scale(0.98);
        }

        @media (max-width: 576px) {
            .btn-set-availability {
                width: 100%;
                justify-content: center;
                font-size: 1rem;
                padding: 0.75rem 1.2rem;
            }
        }

        /* Card Styling */
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .card-outline {
            border-top: 3px solid var(--primary-color);
        }

        .card-header {
            background: white;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form Controls */
        .form-control {
            height: calc(2.5rem + 2px);
            border-radius: 8px;
            border: 2px solid #e4e6ef;
            padding: 0.625rem 1rem;
            font-size: 1rem;
            transition: all var(--transition-speed);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
        }

        textarea.form-control {
            height: auto;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        /* Button Styling */
        .btn {
            padding: 0.65rem 1rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all var(--transition-speed);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.4);
        }

        /* Table Styling */
        .table {
            margin-bottom: 0;
        }

        .table thead tr {
            background: var(--light-color);
        }

        .table thead th {
            border-bottom: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 1rem;
            vertical-align: middle;
            color: var(--dark-color);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #eee;
        }

        /* Alert Styling */
        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.5rem;
        }

        .alert-info {
            background-color: rgba(54, 153, 255, 0.1);
            color: var(--primary-color);
        }

        /* Content Styling */
        .content {
            padding: 20px 0;
        }

        .content .container-fluid {
            padding: 0 15px;
        }

        /* Custom Checkbox */
        .custom-checkbox {
            margin-top: 1rem;
        }

        .custom-checkbox .custom-control-input:checked ~ .custom-control-label::before {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .custom-control-label::before {
            border-radius: 4px;
            border: 2px solid #e4e6ef;
        }

        /* Calendar Styling */
        .fc-event {
            border-radius: 6px;
            padding: 5px;
            cursor: pointer;
        }

        .fc-day-today {
            background-color: rgba(54, 153, 255, 0.05) !important;
        }

        .fc-button {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .fc-button:hover {
            background-color: var(--secondary-color) !important;
        }

        .badge-approved {
            background-color: rgba(27, 197, 189, 0.1);
            color: var(--success-color);
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
        }

        .badge-pending {
            background-color: rgba(255, 168, 0, 0.1);
            color: var(--warning-color);
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
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

        /* Enhanced Calendar Legend Styling */
        .calendar-legend-wrapper {
            display: flex;
            justify-content: center;
            margin: 1.25rem 0;
        }
        
        .legend-container {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            max-width: 1200px;
            width: 100%;
            border: 1px solid #e9ecef;
        }
        
        .legend-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            text-align: center;
            position: relative;
            padding-bottom: 0.75rem;
        }
        
        .legend-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }
        
        .legend-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: space-between;
            align-items: center;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            background: #ffffff;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            transition: all 0.3s ease;
            border: 1px solid #f1f3f6;
            flex: 0 1 calc(50% - 0.375rem);
            min-width: 200px;
            max-width: 250px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        
        .legend-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            border-color: #e2e8f0;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            margin-right: 0.75rem;
            display: inline-block;
            border: 1px solid rgba(0, 0, 0, 0.15);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }
        
        .legend-text {
            font-weight: 600;
            font-size: 0.85rem;
            color: #4a5568;
            line-height: 1.3;
        }
        
        /* Responsive Design for Legend */
        @media (max-width: 768px) {
            .legend-container {
                padding: 1rem;
                margin: 0.75rem;
            }
            
            .legend-grid {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .legend-item {
                flex: 1 1 100%;
                min-width: auto;
                max-width: none;
                padding: 0.5rem 0.75rem;
            }
            
            .legend-title {
                font-size: 1rem;
                margin-bottom: 0.75rem;
                padding-bottom: 0.5rem;
            }
            
            .legend-text {
                font-size: 0.8rem;
            }
            
            .legend-color {
                width: 14px;
                height: 14px;
                margin-right: 0.6rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .legend-item {
                flex: 0 1 calc(33.333% - 0.5rem);
                min-width: 180px;
            }
        }

        /* Export Buttons and Column Visibility Styling */
        .chart-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #fff !important;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none !important;
            padding: 8px 15px;
            font-size: 0.875rem;
            border-radius: 8px;
        }

        /* Gradient colors for each button */
        #btnCopy {
            background: linear-gradient(135deg, #3699FF 0%, #2684FF 100%);
        }

        #btnCSV {
            background: linear-gradient(135deg, #1BC5BD 0%, #17B8B0 100%);
        }

        #btnExcel {
            background: linear-gradient(135deg, #20C997 0%, #1CB984 100%);
        }

        #btnPDF {
            background: linear-gradient(135deg, #F64E60 0%, #EE2D41 100%);
        }

        #btnPrint {
            background: linear-gradient(135deg, #8950FC 0%, #7337EE 100%);
        }

        .export-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            filter: brightness(110%);
        }

        .export-btn i {
            font-size: 0.875rem;
        }

        /* Modern Export Actions Inline CSS */
        .dt-button-collection {
            display: none !important;
        }

        .export-container {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 15px;
        }

        /* Elegant Export Options - Non-Button Design */
        .export-action-btn {
            display: inline-flex !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 12px 18px !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            border-radius: 12px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            cursor: pointer !important;
            position: relative !important;
            overflow: hidden !important;
            border: 2px solid transparent !important;
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
            text-transform: none !important;
            letter-spacing: 0.3px !important;
        }

        .export-action-btn::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: -100% !important;
            width: 100% !important;
            height: 100% !important;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent) !important;
            transition: left 0.6s ease !important;
        }

        .export-action-btn:hover::before {
            left: 100% !important;
        }

        .export-action-btn:hover {
            transform: translateY(-3px) scale(1.02) !important;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15) !important;
            border-color: currentColor !important;
        }

        .export-action-btn:active {
            transform: translateY(-1px) scale(1.01) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12) !important;
        }

        .export-action-btn i {
            font-size: 1rem !important;
            transition: all 0.3s ease !important;
            opacity: 0.9 !important;
            flex-shrink: 0 !important;
        }

        .export-action-btn:hover i {
            transform: scale(1.15) rotate(5deg) !important;
            opacity: 1 !important;
        }

        /* Sophisticated Color Schemes for Each Export Type */
        .export-copy-btn {
            color: #6366F1 !important;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(99, 102, 241, 0.05)) !important;
        }

        .export-copy-btn:hover {
            color: #4F46E5 !important;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(99, 102, 241, 0.08)) !important;
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.25) !important;
        }

        .export-csv-btn {
            color: #10B981 !important;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)) !important;
        }

        .export-csv-btn:hover {
            color: #059669 !important;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.08)) !important;
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.25) !important;
        }

        .export-excel-btn {
            color: #22C55E !important;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05)) !important;
        }

        .export-excel-btn:hover {
            color: #16A34A !important;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.08)) !important;
            box-shadow: 0 8px 30px rgba(34, 197, 94, 0.25) !important;
        }

        .export-pdf-btn {
            color: #EF4444 !important;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05)) !important;
        }

        .export-pdf-btn:hover {
            color: #DC2626 !important;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.08)) !important;
            box-shadow: 0 8px 30px rgba(239, 68, 68, 0.25) !important;
        }

        .export-print-btn {
            color: #8B5CF6 !important;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(139, 92, 246, 0.05)) !important;
        }

        .export-print-btn:hover {
            color: #7C3AED !important;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(139, 92, 246, 0.08)) !important;
            box-shadow: 0 8px 30px rgba(139, 92, 246, 0.25) !important;
        }

        /* Hide default DataTable buttons */
        .dt-buttons {
            display: none !important;
        }

        /* Custom layout for DataTable wrapper */
        #schedules_table_wrapper .row:first-child {
            margin-bottom: 15px;
        }

        #schedules_table_wrapper .dataTables_filter {
            float: left !important;
            text-align: left !important;
        }

        #schedules_table_wrapper .dataTables_filter input {
            width: 300px;
            border-radius: 8px;
            border: 2px solid #e4e6ef;
            padding: 0.625rem 1rem;
            font-size: 1rem;
            transition: all 0.3s;
        }

        #schedules_table_wrapper .dataTables_filter input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
        }

        /* Responsive Design for Modern Export Options */
        @media (max-width: 768px) {
            .export-container {
                flex-wrap: wrap;
                gap: 8px;
                justify-content: center;
            }

            .export-action-btn {
                padding: 10px 14px !important;
                font-size: 0.8125rem !important;
                gap: 8px !important;
                border-radius: 10px !important;
            }

            .export-action-btn i {
                font-size: 0.9rem !important;
            }
        }

        /* Tab Styling */
        .nav-tabs {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 0;
        }

        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            color: #6c757d;
            font-weight: 500;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: var(--primary-color);
            background-color: rgba(54, 153, 255, 0.05);
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom: 3px solid var(--primary-color);
            font-weight: 600;
        }

        .nav-tabs .nav-link i {
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }

        .tab-content {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }

        .tab-pane {
            padding: 1.5rem;
        }

        /* Form Section Styling */
        .form-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e9ecef;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 50px;
            height: 2px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 1px;
        }

        .section-title i {
            color: var(--primary-color);
            opacity: 0.8;
        }

        .options-container {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #e9ecef;
        }

        .options-container .custom-control-label {
            font-size: 0.9rem;
            color: #495057;
        }

        .options-container .custom-control-label i {
            color: var(--primary-color);
            opacity: 0.7;
        }

        /* Enhanced Form Controls */
        #availability-form .form-control {
            border: 2px solid #e4e6ef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        #availability-form .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.15);
            background-color: #fff;
        }

        #availability-form .input-group-text {
            background-color: #f8f9fa;
            border: 2px solid #e4e6ef;
            color: #495057;
            font-weight: 500;
        }

        #availability-form .input-group .form-control {
            border-right: none;
        }

        #availability-form .input-group .input-group-append .input-group-text {
            border-left: none;
            border-radius: 0 8px 8px 0;
        }

        #availability-form .input-group .form-control:focus + .input-group-append .input-group-text {
            border-color: var(--primary-color);
        }

        /* Submit Button Styling */
        #availability-form .btn-lg {
            padding: 0.875rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        #availability-form .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.3);
        }

        #availability-form .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(54, 153, 255, 0.4);
        }

        #availability-form .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        #availability-form .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }

        /* Responsive Design for Tabs and Form */
        @media (max-width: 768px) {
            .nav-tabs .nav-link {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }

            .nav-tabs .nav-link i {
                margin-right: 0.3rem;
                font-size: 0.8rem;
            }

            .tab-pane {
                padding: 1rem;
            }

            .form-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .section-title {
                font-size: 1rem;
                margin-bottom: 1rem;
            }

            #availability-form .btn-lg {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
            }

            .options-container {
                padding: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .export-container {
                gap: 6px;
                flex-direction: column;
                align-items: stretch;
            }

            .export-action-btn {
                padding: 8px 12px !important;
                font-size: 0.75rem !important;
                gap: 6px !important;
                border-radius: 8px !important;
                justify-content: center !important;
            }

            .export-action-btn i {
                font-size: 0.85rem !important;
            }

            .export-action-btn:hover {
                transform: translateY(-2px) scale(1.01) !important;
            }

            #schedules_table_wrapper .dataTables_filter input {
                width: 100%;
            }
        }
        
        /* Calendar Styling Enhancements */
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
        }
        
        .fc-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(54, 153, 255, 0.3) !important;
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
        
        .bg-orange-light {
            background-color: rgba(255, 143, 0, 0.1);
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
        
        .patient-info {
            border-bottom: 1px solid #eee;
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

        /* Availability Form Dark Theme Styling */
        .availability-form-card {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideInUp 0.5s ease;
            margin-bottom: 2rem;
        }

        @keyframes slideInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .availability-form-header {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem 2rem;
            color: white;
        }

        .availability-icon-container {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 15px rgba(52, 152, 219, 0.3);
        }

        .availability-icon-container i {
            font-size: 1.3rem;
            color: white;
        }

        .availability-form-title {
            color: #ecf0f1;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .availability-form-subtitle {
            color: #bdc3c7;
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .btn-close-availability {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-close-availability:hover {
            background: rgba(231, 76, 60, 0.8);
            transform: scale(1.1);
        }

        .availability-form-body {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 1.5rem;
        }

        /* Form Grid */
        .availability-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .availability-form-grid .form-group.full-width {
            grid-column: 1 / -1;
        }

        .availability-form-grid .form-group {
            margin-bottom: 0;
        }

        /* Form Controls */
        .availability-label {
            display: block;
            color: #ecf0f1;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .availability-label i {
            color: #3498db;
            width: 16px;
        }

        .availability-input {
            width: 100%;
            padding: 0.75rem 0.875rem;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .availability-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
        }

        .availability-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .availability-input option {
            background: #34495e;
            color: white;
        }

        .availability-input:disabled {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.5);
            cursor: not-allowed;
        }

        .availability-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .availability-help-text {
            color: #bdc3c7;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            opacity: 0.8;
        }

        /* Checkbox Styling */
        .availability-options {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .availability-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .availability-checkbox-input {
            width: 18px;
            height: 18px;
            accent-color: #3498db;
            cursor: pointer;
        }

        .availability-checkbox-label {
            color: #ecf0f1;
            font-size: 0.9rem;
            cursor: pointer;
            margin: 0;
        }

        .availability-checkbox-label i {
            color: #3498db;
            width: 16px;
        }

        /* Navigation Buttons */
        .availability-form-navigation {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .nav-buttons-right {
            display: flex;
            gap: 0.75rem;
        }

        .btn-availability-cancel,
        .btn-availability-submit {
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-availability-cancel {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
        }

        .btn-availability-cancel:hover {
            background: linear-gradient(135deg, #7f8c8d 0%, #95a5a6 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-availability-submit {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }

        .btn-availability-submit:hover {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(39, 174, 96, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .availability-form-header {
                padding: 1rem;
            }

            .availability-form-body {
                padding: 1rem;
            }

            .availability-form-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .availability-form-navigation {
                flex-direction: column;
                gap: 0.75rem;
            }

            .nav-buttons-right {
                width: 100%;
                justify-content: center;
            }

            .btn-availability-cancel,
            .btn-availability-submit {
                flex: 1;
                justify-content: center;
            }
        }

        /* --- MODERN HEADER FOR DOCTOR SCHEDULE PLOTTER --- */
        .modern-header {
            background: linear-gradient(135deg, #232b3e 0%, #3498db 100%);
            border-radius: 18px;
            padding: 2rem 2.5rem 1.5rem 2.5rem;
            margin-bottom: 2.2rem;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.18);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            position: relative;
        }
        .modern-header .header-icon {
            background: linear-gradient(135deg, #2980b9 0%, #1BC5BD 100%);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(27, 197, 189, 0.15);
        }
        .modern-header .header-icon i {
            color: #fff;
            font-size: 2rem;
        }
        .modern-header .header-title {
            color: #fff;
            font-size: 2.1rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 12px rgba(0,0,0,0.18);
            margin: 0;
        }
        @media (max-width: 576px) {
            .modern-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 1.2rem 1rem 1rem 1rem;
                gap: 0.7rem;
            }
            .modern-header .header-title {
                font-size: 1.3rem;
            }
            .modern-header .header-icon {
                width: 44px;
                height: 44px;
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/admin_header.php'; ?>
        <?php include './config/admin_sidebar.php'; ?>
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="modern-header">
                        <div class="header-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h1 class="header-title">Doctor Schedule Plotter</h1>
                    </div>
                </div>
            </div>

            <div class="content">
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

                    <!-- Set Your Availability Form -->
                    <div class="collapse" id="availability-form">
                        <div class="availability-form-card">
                            <div class="availability-form-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="availability-icon-container">
                                            <i class="fas fa-calendar-plus"></i>
                                </div>
                                        <div class="ml-3">
                                            <h4 class="availability-form-title mb-0">Set Your Availability</h4>
                                            <p class="availability-form-subtitle mb-0">Configure your schedule and time slots</p>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close-availability" data-toggle="collapse" data-target="#availability-form">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="availability-form-body">
                                <form method="post" id="availability-form">
                                        <!-- Add form token to prevent duplicate submissions -->
                                        <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                                    
                                    <!-- Compact Form Grid -->
                                    <div class="availability-form-grid">
                                        <!-- Schedule Period -->
                                                <div class="form-group">
                                            <label for="start_date" class="availability-label">
                                                <i class="fas fa-calendar-day mr-2"></i>Start Date *
                                            </label>
                                            <input type="date" class="availability-input" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>" required>
                                                </div>
                                        
                                                <div class="form-group">
                                            <label for="end_date" class="availability-label">
                                                <i class="fas fa-calendar-day mr-2"></i>End Date *
                                            </label>
                                            <input type="date" class="availability-input" id="end_date" name="end_date" min="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        
                                        <!-- Time Settings -->
                                                <div class="form-group">
                                            <label for="start_time" class="availability-label">
                                                <i class="fas fa-clock mr-2"></i>Start Time *
                                            </label>
                                            <input type="time" class="availability-input" id="start_time" name="start_time" required>
                                                </div>
                                        
                                                <div class="form-group">
                                            <label for="end_time" class="availability-label">
                                                <i class="fas fa-clock mr-2"></i>End Time *
                                            </label>
                                            <input type="time" class="availability-input" id="end_time" name="end_time" required>
                                        </div>
                                        
                                        <!-- Appointment Settings -->
                                                <div class="form-group">
                                            <label for="time_slot" class="availability-label">
                                                <i class="fas fa-user-clock mr-2"></i>Time Slot Duration *
                                            </label>
                                            <select class="availability-input" id="time_slot" name="time_slot" required>
                                                        <option value="15">15 minutes</option>
                                                        <option value="30" selected>30 minutes</option>
                                                        <option value="45">45 minutes</option>
                                                        <option value="60">60 minutes</option>
                                                    </select>
                                                </div>
                                        
                                                <div class="form-group">
                                            <label class="availability-label">
                                                <i class="fas fa-users mr-2"></i>Max Patients per Slot
                                            </label>
                                                    <input type="hidden" id="max_patients" name="max_patients" value="1">
                                            <input type="text" class="availability-input" value="1 Patient per slot" readonly disabled>
                                            <small class="availability-help-text">Each time slot accepts one appointment only</small>
                                        </div>
                                        
                                        <!-- Additional Settings -->
                                        <div class="form-group full-width">
                                            <label for="notes" class="availability-label">
                                                <i class="fas fa-sticky-note mr-2"></i>Additional Notes
                                            </label>
                                            <textarea class="availability-input availability-textarea" id="notes" name="notes" rows="2" placeholder="Any additional information about your availability (optional)"></textarea>
                                        </div>
                                        
                                        <!-- Options -->
                                        <div class="form-group full-width">
                                            <label class="availability-label">
                                                <i class="fas fa-cog mr-2"></i>Options
                                            </label>
                                            <div class="availability-options">
                                                <div class="availability-checkbox">
                                                    <input type="checkbox" class="availability-checkbox-input" id="skip_weekends" name="skip_weekends" checked>
                                                    <label class="availability-checkbox-label" for="skip_weekends">
                                                        <i class="fas fa-calendar-times mr-1"></i>Skip Weekends
                                                    </label>
                                        </div>
                                                <div class="availability-checkbox">
                                                    <input type="checkbox" class="availability-checkbox-input" id="replace_existing" name="replace_existing">
                                                    <label class="availability-checkbox-label" for="replace_existing">
                                                        <i class="fas fa-sync-alt mr-1"></i>Replace existing schedules
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                        
                                    <!-- Submit Buttons -->
                                    <div class="availability-form-navigation">
                                        <div class="nav-buttons-right">
                                            <button type="button" class="btn-availability-cancel" data-toggle="collapse" data-target="#availability-form">
                                                <i class="fas fa-times mr-2"></i>Cancel
                                            </button>
                                            <button type="submit" name="submit_schedule" class="btn-availability-submit">
                                                <i class="fas fa-save mr-2"></i>Save Schedule
                                            </button>
                                        </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                    <!-- Tabs Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h3 class="card-title mb-0">Schedule Management</h3>
                                    <button type="button" class="btn btn-set-availability ml-auto" data-toggle="collapse" data-target="#availability-form" aria-expanded="false" aria-controls="availability-form">
                                        <i class="fas fa-plus-circle mr-2"></i>Set Availability
                                    </button>
                                </div>
                                <div class="card-body">
                                    <!-- Nav tabs -->
                                    <ul class="nav nav-tabs" id="scheduleTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link active" id="calendar-tab" data-toggle="tab" href="#calendar-tab-pane" role="tab" aria-controls="calendar-tab-pane" aria-selected="true">
                                                <i class="fas fa-calendar-alt mr-2"></i>Calendar View
                                            </a>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link" id="schedules-tab" data-toggle="tab" href="#schedules-tab-pane" role="tab" aria-controls="schedules-tab-pane" aria-selected="false">
                                                <i class="fas fa-list mr-2"></i>Availability Schedules
                                            </a>
                                        </li>
                                    </ul>
                                    
                                    <!-- Tab panes -->
                                    <div class="tab-content" id="scheduleTabsContent">
                                        <!-- Calendar Tab -->
                                        <div class="tab-pane fade show active" id="calendar-tab-pane" role="tabpanel" aria-labelledby="calendar-tab">
                                            <div class="pt-3">
                                    <div id="calendar"></div>
                                                <div class="calendar-legend-wrapper">
                                        <div class="legend-container">
                                                        <h5 class="legend-title">Calendar Legend</h5>
                                                        <div class="legend-grid">
                                                <div class="legend-item">
                                                                <span class="legend-color" style="background-color: #1BC5BD"></span>
                                                    <span class="legend-text">Approved Schedules</span>
                                            </div>
                                                <div class="legend-item">
                                                                <span class="legend-color" style="background-color: #FFA800"></span>
                                                    <span class="legend-text">Pending Schedules</span>
                                            </div>
                                                <div class="legend-item">
                                                                <span class="legend-color" style="background-color: #A0A0A0"></span>
                                                    <span class="legend-text">Past Schedules</span>
                                            </div>
                                                <div class="legend-item">
                                                                <span class="legend-color" style="background-color: #F64E60"></span>
                                                    <span class="legend-text">Regular Appointments</span>
                                            </div>
                                                <div class="legend-item">
                                                                <span class="legend-color" style="background-color: #FF8F00"></span>
                                                    <span class="legend-text">Walk-in Appointments</span>
                                            </div>
                                                <div class="legend-item">
                                                                <span class="legend-color" style="background-color: #000000"></span>
                                                    <span class="legend-text">Past Appointments</span>
                                                </div>
                                            </div>
                                                        <div class="legend-info mt-3">
                                                            <div class="alert alert-info mb-2 py-2 px-3 auto-hide-alert">
                                                                <i class="fas fa-info-circle mr-2"></i>
                                                                <strong>Click on any event</strong> to view detailed information including patient details, appointment reasons, and schedule notes.
                                        </div>
                                                            <div class="alert alert-success mb-0 py-2 px-3 auto-hide-alert">
                                                                <i class="fas fa-calendar-week mr-2"></i>
                                                                <strong>Navigation Tips:</strong> Use Month/Week/Day buttons to switch views. Click on dates to jump to specific days. Week view shows detailed time slots from 6 AM to 10 PM.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                                </div>
                                        
                                        <!-- Schedules Tab -->
                                        <div class="tab-pane fade" id="schedules-tab-pane" role="tabpanel" aria-labelledby="schedules-tab">
                                            <div class="pt-3">
                                                <div class="table-responsive">
                                    <table id="schedules_table" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Slot Duration</th>
                                                <th>Max Patients</th>
                                                <th>Status</th>
                                                <th>Notes</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($schedules as $schedule) { ?>
                                                <tr>
                                                    <td><?= date('M d, Y', strtotime($schedule['schedule_date'])) ?></td>
                                                    <td><?= date('h:i A', strtotime($schedule['start_time'])) ?> - <?= date('h:i A', strtotime($schedule['end_time'])) ?></td>
                                                    <td><?= $schedule['time_slot_minutes'] ?> minutes</td>
                                                    <td><?= $schedule['max_patients'] ?></td>
                                                    <td>
                                                        <?php if ($schedule['is_approved']) { ?>
                                                            <span class="badge badge-success">Approved</span>
                                                        <?php } else { ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <?= $schedule['notes'] ?>
                                                        <?php if (!empty($schedule['approval_notes'])) { ?>
                                                            <br><small class="text-muted">Admin notes: <?= $schedule['approval_notes'] ?></small>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $isPastSchedule = strtotime($schedule['schedule_date']) < strtotime(date('Y-m-d'));
                                                        if (!$schedule['is_approved'] || $isPastSchedule) { ?>
                                                            <a href="actions/delete_schedule.php?id=<?= $schedule['id'] ?>" 
                                                               class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('Are you sure you want to delete this schedule? Any appointments made for this schedule will NOT be affected.')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php } else { ?>
                                                            <button class="btn btn-sm btn-secondary" disabled>
                                                                <i class="fas fa-lock"></i>
                                                            </button>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                                    <div class="export-container mt-3 mb-3" id="exportContainer">
                                                        <a href="#" class="export-action-btn export-copy-btn" id="btnCopy">
                                                            <i class="fas fa-copy"></i>
                                                            <span>Copy</span>
                                                        </a>
                                                        <a href="#" class="export-action-btn export-csv-btn" id="btnCSV">
                                                            <i class="fas fa-file-csv"></i>
                                                            <span>CSV</span>
                                                        </a>
                                                        <a href="#" class="export-action-btn export-excel-btn" id="btnExcel">
                                                            <i class="fas fa-file-excel"></i>
                                                            <span>Excel</span>
                                                        </a>
                                                        <a href="#" class="export-action-btn export-pdf-btn" id="btnPDF">
                                                            <i class="fas fa-file-pdf"></i>
                                                            <span>PDF</span>
                                                        </a>
                                                        <a href="#" class="export-action-btn export-print-btn" id="btnPrint">
                                                            <i class="fas fa-print"></i>
                                                            <span>Print</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include './config/admin_footer.php'; ?>
    </div>
    
    <?php include './config/site_css_js_links.php'; ?>
    
    <script src="plugins/fullcalendar/main.min.js"></script>
    
    <script>
        $(function() {
            // Initialize DataTable with export buttons
            var table = $("#schedules_table").DataTable({
                responsive: true,
                lengthChange: false,
                autoWidth: false,
                pageLength: 5,
                pagingType: "simple_numbers",
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Search schedules...",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                },
                order: [[0, "asc"]],
                columnDefs: [
                    {
                        // Target the Notes column (index 5)
                        targets: 5,
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return data; // Return HTML for display
                            } else if (type === 'type' || type === 'sort') {
                                // Strip HTML tags for sorting
                                return data.replace(/<[^>]*>/g, '').trim();
                            } else if (type === 'filter') {
                                // Strip HTML tags for searching/filtering
                                return data.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                            }
                            return data;
                        }
                    },
                    {
                        // Target the Status column (index 4) for better searching
                        targets: 4,
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return data; // Return HTML for display
                            } else if (type === 'filter' || type === 'type' || type === 'sort') {
                                // Extract text content from badge for searching/sorting
                                var text = data.replace(/<[^>]*>/g, '').trim();
                                return text;
                            }
                            return data;
                        }
                    }
                ],
                search: {
                    // Enable smart searching
                    smart: true,
                    // Enable regex searching
                    regex: false,
                    // Case insensitive search
                    caseInsensitive: true
                }
            });

            // Hide default buttons
            $('.dt-buttons').hide();

            // Enhance search to work with all text content including HTML stripped content
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'schedules_table') {
                    return true;
                }
                
                var searchTerm = $('#schedules_table_filter input').val().toLowerCase();
                if (!searchTerm) {
                    return true;
                }
                
                // Search through all columns including stripped HTML content
                for (var i = 0; i < data.length; i++) {
                    var columnData = data[i];
                    // Strip HTML and normalize whitespace for searching
                    var cleanData = columnData.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').toLowerCase();
                    if (cleanData.indexOf(searchTerm) !== -1) {
                        return true;
                    }
                }
                return false;
            });

            // Trigger table redraw when search input changes
            $('#schedules_table_filter input').off('keyup.DT search.DT input.DT paste.DT cut.DT').on('keyup.DT search.DT input.DT paste.DT cut.DT', function() {
                table.draw();
            });

            // Custom export button handlers
            $('#btnCopy').click(function(e) {
                e.preventDefault();
                table.button('.buttons-copy').trigger();
                
                // Show toast notification
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: 'Schedule data copied to clipboard!'
                });
            });

            $('#btnCSV').click(function(e) {
                e.preventDefault();
                table.button('.buttons-csv').trigger();
            });

            $('#btnExcel').click(function(e) {
                e.preventDefault();
                table.button('.buttons-excel').trigger();
            });

            $('#btnPDF').click(function(e) {
                e.preventDefault();
                table.button('.buttons-pdf').trigger();
            });

            $('#btnPrint').click(function(e) {
                e.preventDefault();
                table.button('.buttons-print').trigger();
            });
            
            // Handle send notification button click for regular appointments
            $(document).on('click', '.send-notification', function() {
                const appointmentId = $(this).data('appointment-id');
                const btn = $(this);
                
                // Disable button and show loading state
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Sending...');
                
                // Send AJAX request to send notification
                $.ajax({
                    url: 'ajax/admin_notif_appointment_sender.php',
                    type: 'POST',
                    data: {
                        appointment_id: appointmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            const alertHtml = `
                                <div class="alert alert-success alert-dismissible fade show mt-3">
                                    <i class="fas fa-check-circle mr-2"></i> ${response.message}
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            `;
                            btn.closest('.modal-content').find('.modal-body').append(alertHtml);
                            
                            // Update button to show sent status
                            btn.removeClass('btn-primary').addClass('btn-success')
                               .html('<i class="fas fa-check mr-2"></i> Notification Sent')
                               .prop('disabled', true);
                        } else {
                            // Show error message
                            const alertHtml = `
                                <div class="alert alert-danger alert-dismissible fade show mt-3">
                                    <i class="fas fa-exclamation-circle mr-2"></i> ${response.message}
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            `;
                            btn.closest('.modal-content').find('.modal-body').append(alertHtml);
                            
                            // Reset button
                            btn.prop('disabled', false)
                               .html('<i class="fas fa-envelope mr-2"></i> Send Email Notification');
                        }
                    },
                    error: function() {
                        // Show error message
                        const alertHtml = `
                            <div class="alert alert-danger alert-dismissible fade show mt-3">
                                <i class="fas fa-exclamation-circle mr-2"></i> An error occurred while sending the notification.
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                            </div>
                        `;
                        btn.closest('.modal-content').find('.modal-body').append(alertHtml);
                        
                        // Reset button
                        btn.prop('disabled', false)
                           .html('<i class="fas fa-envelope mr-2"></i> Send Email Notification');
                    }
                });
            });
            
            // Handle send notification button click for walk-in appointments
            $(document).on('click', '.send-walkin-notification', function() {
                const walkinId = $(this).data('walkin-id');
                const btn = $(this);
                
                // Disable button and show loading state
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Sending...');
                
                // Send AJAX request to send walk-in notification
                $.ajax({
                    url: 'ajax/admin_notif_walkin_appointment_sender.php',
                    type: 'POST',
                    data: {
                        walkin_id: walkinId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            const alertHtml = `
                                <div class="alert alert-success alert-dismissible fade show mt-3">
                                    <i class="fas fa-check-circle mr-2"></i> ${response.message}
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            `;
                            btn.closest('.modal-content').find('.modal-body').append(alertHtml);
                            
                            // Update button to show sent status
                            btn.removeClass('btn-warning').addClass('btn-success')
                               .html('<i class="fas fa-check mr-2"></i> Email Sent')
                               .prop('disabled', true);
                        } else {
                            // Show error message
                            const alertHtml = `
                                <div class="alert alert-danger alert-dismissible fade show mt-3">
                                    <i class="fas fa-exclamation-circle mr-2"></i> ${response.message}
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            `;
                            btn.closest('.modal-content').find('.modal-body').append(alertHtml);
                            
                            // Reset button
                            btn.prop('disabled', false)
                               .html('<i class="fas fa-envelope mr-2"></i> Send Email Notification');
                        }
                    },
                    error: function() {
                        // Show error message
                        const alertHtml = `
                            <div class="alert alert-danger alert-dismissible fade show mt-3">
                                <i class="fas fa-exclamation-circle mr-2"></i> An error occurred while sending the email notification.
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                            </div>
                        `;
                        btn.closest('.modal-content').find('.modal-body').append(alertHtml);
                        
                        // Reset button
                        btn.prop('disabled', false)
                           .html('<i class="fas fa-envelope mr-2"></i> Send Email Notification');
                    }
                });
            });
            
            // Function to automatically update past appointments to completed status
            function updatePastAppointments() {
                $.ajax({
                    url: 'ajax/admin_check_update_past_appointment.php',
                    type: 'POST',
                    data: {
                        doctor_id: <?= $doctorId ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.updated > 0) {
                            console.log('Updated ' + response.updated + ' past appointments to completed status');
                            // Optionally refresh the calendar if needed
                            // calendar.refetchEvents();
                        }
                    }
                });
            }
            
            // Call the update function when the page loads
            updatePastAppointments();
            
            // Function to refresh appointments and update calendar
            function refreshAppointmentsCalendar() {
                $.ajax({
                    url: 'ajax/get_doctor_appointments.php',
                    type: 'POST',
                    data: {
                        doctor_id: <?= $doctorId ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove all existing appointment events
                            var existingEvents = calendar.getEvents();
                            existingEvents.forEach(function(event) {
                                if (event.id.startsWith('appointment_')) {
                                    event.remove();
                                }
                            });
                            
                            // Add the newly fetched appointments
                            if (response.appointments && response.appointments.length > 0) {
                                calendar.addEventSource(response.appointments);
                                console.log('Calendar updated with ' + response.appointments.length + ' appointments');
                            }
                        }
                    }
                });
            }
            
            // Set up periodic refresh (every 60 seconds)
            setInterval(refreshAppointmentsCalendar, 60000);
            

            
            // Refresh once when page loads (after calendar is initialized)
            setTimeout(refreshAppointmentsCalendar, 1000);
            
            // Initialize Calendar
            var calendarEl = document.getElementById('calendar');
            
            if (!calendarEl) {
                console.error('Calendar element not found');
                return;
            }
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.auto-hide-alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
            
            try {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prevYear,prev,next,nextYear today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                events: <?= json_encode($calendarEvents) ?>,
                height: 'auto',
                themeSystem: 'bootstrap',
                dayMaxEvents: 6, // Show more events per day
                moreLinkClick: 'popover', // Show popup for overflow events
                // Responsive design
                aspectRatio: 1.8,
                expandRows: true,
                handleWindowResize: true,
                firstDay: 1, // Start week on Monday
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                // Enhanced week and day view settings
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                slotDuration: '00:30:00',
                slotLabelInterval: '01:00:00',
                allDaySlot: false,
                nowIndicator: true,
                scrollTime: '08:00:00',
                // Week view specific settings
                weekNumbers: true,
                weekText: 'Week',
                // Enhanced navigation
                navLinks: true,
                navLinkDayClick: function(date, jsEvent) {
                    calendar.changeView('timeGridDay', date);
                },
                navLinkWeekClick: function(weekStart, jsEvent) {
                    calendar.changeView('timeGridWeek', weekStart);
                },
                // Custom buttons for better navigation
                customButtons: {
                    goToToday: {
                        text: 'Today',
                        click: function() {
                            calendar.today();
                        }
                    },
                    prevWeek: {
                        text: '← Week',
                        click: function() {
                            calendar.changeView('timeGridWeek');
                            calendar.prev();
                        }
                    },
                    nextWeek: {
                        text: 'Week →',
                        click: function() {
                            calendar.changeView('timeGridWeek');
                            calendar.next();
                        }
                    }
                },
                // Improved event display
                eventDisplay: 'block',
                eventOrder: ['type', 'start', 'title'],
                eventMinHeight: 25,
                eventShortHeight: 20,
                eventDidMount: function(info) {
                    // Add tooltip for all events
                    var tooltipTitle = '';
                    if (info.event.extendedProps.type === 'schedule') {
                        tooltipTitle = 'Schedule' + (info.event.extendedProps.is_past ? ' (Past)' : '');
                    } else {
                        var appointmentType = info.event.extendedProps.is_walk_in ? 'Walk-in Appointment' : 'Regular Appointment';
                        tooltipTitle = appointmentType + (info.event.extendedProps.is_past ? ' (Past)' : '');
                    }
                    
                    $(info.el).tooltip({
                        title: tooltipTitle,
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                },
                eventClick: function(info) {
                    var event = info.event;
                    var props = event.extendedProps;
                    
                    // Create a modal instead of toast for better UI
                    var modalId = 'eventModal' + Date.now();
                    var modalTitle = '';
                    var modalContent = '';
                    var headerClass = '';
                    
                    if (props.type === 'schedule') {
                        // Schedule event
                        modalTitle = '<i class="fas fa-calendar-alt mr-2"></i> Schedule Information';
                        
                        // Set header class based on approval status and whether it's past
                        if (props.is_past) {
                            headerClass = 'bg-secondary text-white';
                        } else {
                            headerClass = props.is_approved ? 'bg-teal text-white' : 'bg-warning text-white';
                        }
                        
                        var formattedDate = event.start.toLocaleDateString('en-US', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                        
                        var statusClass = props.is_approved ? 'badge-success' : 'badge-warning';
                        var statusText = props.is_approved ? 'Approved' : 'Pending';
                        
                        modalContent = `
                        <div class="schedule-details p-0">
                            <div class="card mb-0 border-0">
                                <div class="card-body p-0">
                                    <div class="event-date text-center py-3 ${props.is_past ? 'bg-secondary-light' : (props.is_approved ? 'bg-success-light' : 'bg-warning-light')}">
                                        <h4 class="mb-0">${formattedDate}</h4>
                                    </div>
                                    <div class="event-info p-4">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <i class="fas fa-clock text-muted mr-2"></i>
                                                    <span class="info-label">Time:</span>
                                                    <span class="info-value font-weight-bold">
                                                        ${event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - 
                                                        ${event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <i class="fas fa-hourglass-half text-muted mr-2"></i>
                                                    <span class="info-label">Time Slot:</span>
                                                    <span class="info-value font-weight-bold">${props.time_slot} minutes</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <i class="fas fa-users text-muted mr-2"></i>
                                                    <span class="info-label">Max Patients:</span>
                                                    <span class="info-value font-weight-bold">${props.max_patients}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <i class="fas fa-check-circle text-muted mr-2"></i>
                                                    <span class="info-label">Status:</span>
                                                    <span class="badge ${statusClass} px-2 py-1">
                                                        ${statusText} ${props.is_past ? '(Past)' : ''}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        ${props.notes ? `
                                        <div class="notes mt-3 pt-3 border-top">
                                            <h6 class="font-weight-bold"><i class="fas fa-sticky-note text-muted mr-2"></i> Notes:</h6>
                                            <p class="mb-0 text-muted">${props.notes}</p>
                                        </div>` : ''}
                                        ${props.approval_notes ? `
                                        <div class="notes mt-3 pt-3 border-top">
                                            <h6 class="font-weight-bold"><i class="fas fa-comment-alt text-muted mr-2"></i> Admin Notes:</h6>
                                            <p class="mb-0 text-muted">${props.approval_notes}</p>
                                        </div>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    } else {
                        // Appointment event
                        modalTitle = '<i class="fas fa-user-clock mr-2"></i> ' + (props.is_walk_in ? 'Walk-in' : 'Regular') + ' Appointment Information';
                        
                        // Set header class based on appointment status, whether it's past, and if it's walk-in
                        if (props.is_past) {
                            if (props.status == 'completed') {
                                headerClass = 'bg-success text-white';
                            } else {
                                headerClass = 'bg-secondary text-white';
                            }
                        } else if (props.is_walk_in) {
                            headerClass = 'bg-warning text-white'; // Orange for walk-in appointments
                        } else {
                            headerClass = 'bg-danger text-white';
                        }
                        
                        var formattedDate = event.start.toLocaleDateString('en-US', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                        
                        var statusClass = '';
                        var statusText = props.status.charAt(0).toUpperCase() + props.status.slice(1);
                        
                        switch(props.status) {
                            case 'approved':
                                statusClass = 'badge-primary';
                                break;
                            case 'completed':
                                statusClass = 'badge-success';
                                break;
                            case 'cancelled':
                                statusClass = 'badge-danger';
                                break;
                            default:
                                statusClass = 'badge-warning';
                        }
                        
                        // Set background color class based on appointment type and status
                        var bgClass = props.is_past ? 
                            (props.status == 'completed' ? 'bg-success-light' : 'bg-secondary-light') : 
                            (props.is_walk_in ? 'bg-orange-light' : 'bg-danger-light');
                        
                        var iconColor = props.is_past ? 
                            (props.status == 'completed' ? 'text-success' : 'text-secondary') : 
                            (props.is_walk_in ? 'text-warning' : 'text-danger');
                        
                        modalContent = `
                        <div class="appointment-details p-0">
                            <div class="card mb-0 border-0">
                                <div class="card-body p-0">
                                    <div class="patient-info p-3 ${bgClass}">
                                        <div class="d-flex align-items-center">
                                            <div class="patient-icon mr-3">
                                                <i class="fas fa-${props.is_walk_in ? 'walking' : 'user-circle'} fa-3x ${iconColor}"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-1">
                                                    ${props.patient_name}
                                                    ${props.is_walk_in ? '<span class="badge badge-warning ml-2"><i class="fas fa-walking fa-xs"></i> Walk-in</span>' : ''}
                                                </h5>
                                                <p class="mb-0 text-muted">
                                                    ${formattedDate} - ${props.is_walk_in ? 'Walk-in appointment' : 'Scheduled appointment'} with Doctor
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="appointment-info p-4">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <i class="fas fa-clock text-muted mr-2"></i>
                                                    <span class="info-label">Time:</span>
                                                    <span class="info-value font-weight-bold">
                                                        ${event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <i class="fas fa-check-circle text-muted mr-2"></i>
                                                    <span class="info-label">Status:</span>
                                                    <span class="badge ${statusClass} px-2 py-1">
                                                        ${statusText} ${props.is_past ? '(Past)' : ''}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        ${props.reason ? `
                                        <div class="reason mt-3 pt-3 border-top">
                                            <h6 class="font-weight-bold"><i class="fas fa-comment-medical text-muted mr-2"></i> Reason for Visit:</h6>
                                            <p class="mb-0 text-muted">${props.reason}</p>
                                        </div>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                        
                        // Add send notification button for active appointments
                        if (props.type === 'appointment' && !props.is_past && props.appointment_id) {
                            if (props.is_walk_in) {
                                // For walk-in appointments, check if email is available
                                modalContent += `
                                <div class="text-center pb-3">
                                    <button type="button" class="btn btn-warning send-walkin-notification" data-walkin-id="${props.appointment_id}">
                                        <i class="fas fa-envelope mr-2"></i> Send Email Notification
                                    </button>
                                    <small class="d-block text-muted mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Send confirmation email if patient provided an email address
                                    </small>
                                </div>`;
                            } else {
                                // For regular appointments
                                modalContent += `
                                <div class="text-center pb-3">
                                    <button type="button" class="btn btn-primary send-notification" data-appointment-id="${props.appointment_id}">
                                        <i class="fas fa-envelope mr-2"></i> Send Email Notification
                                    </button>
                                </div>`;
                            }
                        }
                    }
                    
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
                                </div>
                            </div>
                        </div>
                    </div>`;
                    
                    // Append modal to body
                    $('body').append(modalHTML);
                    
                    // Show modal
                    $('#' + modalId).modal('show');
                    
                    // Remove modal from DOM when hidden
                    $('#' + modalId).on('hidden.bs.modal', function() {
                        $(this).remove();
                    });
                }
            });
            calendar.render();
            } catch (error) {
                console.error('Error initializing FullCalendar:', error);
                $('#calendar').html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>Error loading calendar. Please refresh the page.</div>');
            }
            
            // Form validation
            $('#start_date, #end_date').on('change', function() {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                
                if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                    $('#end_date').val(startDate);
                    alert('End date cannot be before start date');
                }
            });
            
            $('#start_time, #end_time').on('change', function() {
                var startTime = $('#start_time').val();
                var endTime = $('#end_time').val();
                
                if (startTime && endTime && endTime <= startTime) {
                    $('#end_time').val('');
                    alert('End time must be after start time');
                }
            });
        });
    </script>
</body>
</html>