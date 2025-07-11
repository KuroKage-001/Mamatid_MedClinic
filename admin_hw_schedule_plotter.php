<?php
include './config/db_connection.php';
require_once './system/utilities/admin_client_role_functions_services.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission - only admins and health workers can access this page
requireRole(['admin', 'health_worker']);

$message = '';
$error = '';
$staffId = $_SESSION['user_id'];
$staffName = $_SESSION['display_name'];
$staffRole = $_SESSION['role'];

// Get message/error from URL if redirected
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Automatically update past appointments to completed status
try {
    $con->beginTransaction();
    
    // Update past appointments to completed status
    $updateQuery = "UPDATE admin_clients_appointments
                  SET status = 'completed', updated_at = NOW() 
                  WHERE CONCAT(appointment_date, ' ', appointment_time) < NOW() 
                  AND status = 'approved'
                  AND doctor_id = ?";
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->execute([$staffId]);
    $updatedCount = $updateStmt->rowCount();
    
    if ($updatedCount > 0) {
        $message = $updatedCount . " past appointments were automatically marked as completed.";
    }
    
    $con->commit();
} catch(PDOException $ex) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    $error = "Error updating past appointments: " . $ex->getMessage();
}

// Handle schedule submission
if (isset($_POST['submit_schedule'])) {
    try {
        $con->beginTransaction();
        
        // Delete existing schedule entries for the selected dates if requested
        if (isset($_POST['replace_existing'])) {
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            
            $deleteQuery = "DELETE FROM admin_hw_schedules 
                            WHERE staff_id = ? 
                            AND schedule_date BETWEEN ? AND ?";
            $deleteStmt = $con->prepare($deleteQuery);
            $deleteStmt->execute([$staffId, $startDate, $endDate]);
        }
        
        // Insert new schedule entries - auto-approved for admins and health workers
        $scheduleQuery = "INSERT INTO admin_hw_schedules 
                         (staff_id, schedule_date, start_time, end_time, time_slot_minutes, max_patients, notes) 
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
            
            // Check if a schedule already exists for this date and time
            $checkQuery = "SELECT COUNT(*) FROM admin_hw_schedules 
                          WHERE staff_id = ? 
                          AND schedule_date = ?
                          AND start_time = ?
                          AND end_time = ?";
            $checkStmt = $con->prepare($checkQuery);
            $checkStmt->execute([$staffId, $dateStr, $startTime, $endTime]);
            $exists = $checkStmt->fetchColumn();
            
            // Only insert if no existing schedule
            if (!$exists) {
            $scheduleStmt->execute([
                $staffId,
                $dateStr,
                $startTime,
                $endTime,
                $timeSlot,
                $maxPatients,
                $notes
            ]);
            }
            
            $currentDate->modify('+1 day');
        }
        
        $con->commit();
        $message = "Schedule successfully saved and automatically approved for patient booking!";
        
        // Redirect to prevent form resubmission on page refresh
        header("Location: admin_hw_schedule_plotter.php?message=" . urlencode($message));
        exit;
        
    } catch(PDOException $ex) {
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        $error = "Error: " . $ex->getMessage();
        
        // Redirect with error message
        header("Location: admin_hw_schedule_plotter.php?error=" . urlencode($error));
        exit;
    }
}

// Fetch staff's existing schedules
$query = "SELECT * FROM admin_hw_schedules 
          WHERE staff_id = ? 
          ORDER BY schedule_date ASC";
$stmt = $con->prepare($query);
$stmt->execute([$staffId]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get booked appointments for this staff member (both regular and walk-in)
$appointmentsQuery = "SELECT a.*, ss.time_slot_minutes, 'regular' as appointment_type
                     FROM admin_clients_appointments a 
                     JOIN admin_hw_schedules ss ON a.schedule_id = ss.id 
                     WHERE a.doctor_id = ? AND a.status != 'cancelled' AND a.is_archived = 0
                     UNION ALL
                     SELECT w.id, w.patient_name, w.phone_number, w.address, w.date_of_birth, w.gender,
                            w.appointment_date, w.appointment_time, w.reason, w.status, w.notes,
                            w.schedule_id, w.provider_id as doctor_id, w.created_at, w.updated_at,
                            0 as email_sent, 0 as reminder_sent, w.is_archived, 
                            NULL as view_token, NULL as token_expiry, NULL as archived_at, NULL as archived_by, NULL as archive_reason,
                            1 as is_walkin, ss2.time_slot_minutes, 'walk-in' as appointment_type
                     FROM admin_walkin_appointments w
                     JOIN admin_hw_schedules ss2 ON w.schedule_id = ss2.id 
                     WHERE (w.provider_id = ? AND (w.provider_type = 'admin' OR w.provider_type = 'health_worker')) 
                     AND w.status != 'cancelled' AND w.is_archived = 0";
$appointmentsStmt = $con->prepare($appointmentsQuery);
$appointmentsStmt->execute([$staffId, $staffId]);
$appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Format schedules for calendar
$calendarEvents = [];
foreach ($schedules as $schedule) {
    $isPast = strtotime($schedule['schedule_date']) < strtotime(date('Y-m-d'));
    
    // Set color based on schedule status
    $backgroundColor = $isPast ? '#495057' : '#1e3a8a'; // Dark grey for past, dark blue for available
    
    $calendarEvents[] = [
        'id' => 'schedule_' . $schedule['id'],
        'title' => $staffName . ($isPast ? ' [Past]' : ''),
        'start' => $schedule['schedule_date'] . 'T' . $schedule['start_time'],
        'end' => $schedule['schedule_date'] . 'T' . $schedule['end_time'],
        'backgroundColor' => $backgroundColor,
        'borderColor' => $backgroundColor,
        'extendedProps' => [
            'max_patients' => $schedule['max_patients'],
            'time_slot' => $schedule['time_slot_minutes'],
            'notes' => $schedule['notes'],
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
    $color = '#dc2626'; // Red for regular appointments
    if ($isPast) {
        $color = '#7c3aed'; // Violet for all past appointments
    } else if ($isWalkIn) {
        $color = '#ea580c'; // Orange for active walk-in appointments
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

// Get the staff role name for display
$roleDisplay = ucfirst($staffRole);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <?php include './config/data_tables_css_js.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <link href="plugins/fullcalendar/main.min.css" rel="stylesheet">
    <link href="plugins/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <title>Schedule Plotter - Mamatid Health Center System</title>
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
            color: #2c3e50;
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
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
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

        /* Content Header Styling */
        .content-header {
            padding: 20px 0;
        }

        .content-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
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

        .custom-control-label {
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Form Text Improvements */
        .form-text {
            color: #6c757d !important;
            font-weight: 500;
            font-size: 0.8rem;
        }

        /* Input placeholder text */
        .form-control::placeholder {
            color: #6c757d;
            opacity: 0.8;
        }

        /* Improve text visibility in disabled fields */
        .form-control:disabled {
            background-color: #f8f9fa;
            color: #495057 !important;
            font-weight: 500;
        }

        /* Select option text */
        select.form-control option {
            color: #2c3e50;
            font-weight: 500;
        }

        /* Textarea text */
        textarea.form-control {
            color: #2c3e50;
            font-weight: 400;
        }

        /* Input text color */
        .form-control {
            color: #2c3e50 !important;
            font-weight: 500;
        }

        /* Input Group Text Styling */
        .input-group-text {
            background-color: #f8f9fa;
            border: 2px solid #e4e6ef;
            color: #495057;
            font-weight: 600;
            border-radius: 0 8px 8px 0;
        }

        .input-group-prepend .input-group-text {
            border-radius: 8px 0 0 8px;
        }

        .input-group-append .input-group-text {
            border-radius: 0 8px 8px 0;
        }

        /* Availability Form Specific Styling */
        #availability-form .card-body {
            background-color: #ffffff;
        }

        #availability-form label,
        #availability-form .form-label {
            color: #2c3e50 !important;
            font-weight: 600 !important;
            font-size: 0.9rem !important;
        }

        #availability-form .custom-control-label {
            color: #2c3e50 !important;
            font-weight: 500 !important;
        }

        #availability-form .form-text {
            color: #6c757d !important;
            font-weight: 500 !important;
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
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        .fc-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(54, 153, 255, 0.3) !important;
            background-color: var(--secondary-color) !important;
        }
        
        .fc-day-today {
            background-color: rgba(54, 153, 255, 0.05) !important;
        }
        
        .fc-event {
            border-radius: 8px !important;
            padding: 4px 8px !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            cursor: pointer !important;
            backdrop-filter: blur(10px) !important;
            position: relative !important;
        }
        
        .fc-event::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), transparent) !important;
            border-radius: inherit !important;
            pointer-events: none !important;
        }
        
        .fc-event:hover {
            transform: scale(1.05) translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.4) !important;
        }
        
        .fc-daygrid-day-number {
            font-weight: 500;
            color: #555;
        }
        
        .fc-col-header-cell-cushion {
            font-weight: 600;
            color: var(--dark-color);
        }

        /* Enhanced Week View Styling for Better Activity Visibility */
        .fc-timeGridWeek-view {
            font-size: 0.9rem;
        }
        
        .fc-timegrid-slot-label {
            font-size: 0.8rem !important;
            font-weight: 500 !important;
            color: #666 !important;
        }
        
        .fc-timegrid-axis-cushion {
            color: #666 !important;
            font-weight: 500 !important;
        }
        
        .fc-timegrid-event {
            border-radius: 8px !important;
            padding: 4px 8px !important;
            font-size: 0.8rem !important;
            line-height: 1.3 !important;
            border-left: 3px solid rgba(255, 255, 255, 0.4) !important;
            backdrop-filter: blur(8px) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
        }
        
        .fc-timegrid-event .fc-event-title {
            font-weight: 600 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
            color: rgba(255, 255, 255, 0.95) !important;
        }
        
        .fc-timegrid-event .fc-event-time {
            font-size: 0.7rem !important;
            opacity: 0.9 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        .fc-event-title {
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
            color: rgba(255, 255, 255, 0.95) !important;
        }
        
        /* Column styling for better separation */
        .fc-col-header-cell {
            background-color: #f8f9fa !important;
            font-weight: 600 !important;
            border-bottom: 2px solid #e9ecef !important;
        }
        
        .fc-timegrid-col-frame {
            position: relative;
        }
        
        .fc-timegrid-col-bg .fc-timegrid-bg-harness {
            border-right: 1px solid #e9ecef !important;
        }
        
        /* Now indicator styling */
        .fc-timegrid-now-indicator-line {
            border-color: #dc3545 !important;
            border-width: 2px !important;
        }
        
        .fc-timegrid-now-indicator-arrow {
            border-color: #dc3545 !important;
        }
        
        /* Event stacking improvements */
        .fc-timegrid-event-harness {
            margin-right: 2px !important;
        }
        
        .fc-timegrid-event-harness-inset .fc-timegrid-event {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
        }
        
        .fc-timegrid-event:hover {
            transform: scale(1.02) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
            backdrop-filter: blur(12px) !important;
        }
        
        /* More link styling for overflow events */
        .fc-more-link {
            background-color: #f8f9fa !important;
            color: #495057 !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 4px !important;
            padding: 2px 6px !important;
            font-size: 0.8rem !important;
            font-weight: 500 !important;
        }
        
        .fc-more-link:hover {
            background-color: #e9ecef !important;
            color: #212529 !important;
        }
        
        /* Popover styling for overflow events */
        .fc-popover {
            border: none !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
            border-radius: 8px !important;
        }
        
        .fc-popover-header {
            background-color: #f8f9fa !important;
            border-bottom: 1px solid #dee2e6 !important;
            padding: 8px 12px !important;
            font-weight: 600 !important;
        }
        
        .fc-popover-body {
            padding: 8px !important;
        }
        
        /* Enhanced Week View Styling */
        .fc-timeGrid-view .fc-day-grid {
            display: none;
        }
        
        .fc-timeGrid-view .fc-axis {
            width: 65px !important;
        }
        
        .fc-timeGrid-view .fc-time {
            font-weight: 600 !important;
            font-size: 0.85rem !important;
        }
        
        .fc-timeGrid-view .fc-slats .fc-minor {
            border-color: rgba(0, 0, 0, 0.05) !important;
        }
        
        .fc-timeGrid-view .fc-slats .fc-major {
            border-color: rgba(0, 0, 0, 0.15) !important;
        }
        
        .fc-day-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
            font-weight: 600 !important;
            padding: 8px !important;
            border-bottom: 2px solid #dee2e6 !important;
        }
        
        .fc-today .fc-day-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%) !important;
            color: white !important;
        }
        
        /* Week navigation enhancements */
        .fc-button-group .fc-button {
            margin: 0 2px !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
        }
        
        .fc-timeGridWeek-button,
        .fc-timeGridDay-button {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
        }
        
        .fc-timeGridWeek-button:hover,
        .fc-timeGridDay-button:hover {
            background: linear-gradient(135deg, #138496 0%, #0f6674 100%) !important;
        }
        
        /* Responsive improvements for mobile */
        @media (max-width: 768px) {
            .fc-timeGrid-view {
                font-size: 0.8rem;
            }
            
            .fc-timegrid-event {
                font-size: 0.7rem !important;
                padding: 2px 4px !important;
            }
            
            .fc-col-header-cell-cushion {
                font-size: 0.8rem !important;
            }
            
            .fc-timeGrid-view .fc-axis {
                width: 45px !important;
            }
            
            .fc-day-header {
                padding: 6px !important;
                font-size: 0.8rem !important;
            }
        }

        .role-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
            border-radius: 30px;
            margin-left: 0.5rem;
        }
        
        .role-admin {
            background-color: rgba(137, 80, 252, 0.1);
            color: var(--info-color);
        }
        
        .role-health_worker {
            background-color: rgba(27, 197, 189, 0.1);
            color: var(--success-color);
        }
        
        /* Calendar Legend Styling */
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
            background-color: rgba(255, 143, 0, 0.1);
        }
        
        .bg-dark-light {
            background-color: rgba(0, 0, 0, 0.1);
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
        
        /* Info Card Styling */
        .info-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .info-card h6 {
            color: #495057;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-card p {
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .info-card i {
            opacity: 0.8;
        }
        
        .patient-avatar {
            border: 2px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .notes-section, .reason-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .notes-section .bg-light,
        .reason-section .bg-light {
            border-left: 4px solid #17a2b8;
            background-color: #ffffff !important;
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

        /* Modern Export Actions CSS */
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
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/admin_header.php'; ?>
        <?php include './config/admin_sidebar.php'; ?>
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">
                                Schedule Plotter
                                <span class="role-badge role-<?php echo $staffRole; ?>"><?php echo $roleDisplay; ?></span>
                            </h1>
                        </div>
                        <div class="col-sm-6">
                            <div class="float-sm-right">
                                <button type="button" class="btn btn-primary" data-toggle="collapse" data-target="#availability-form" aria-expanded="false" aria-controls="availability-form">
                                    <i class="fas fa-plus-circle mr-2"></i>Set Availability
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <!-- Messages will be handled by SweetAlert2 -->

                    <!-- Set Your Availability Form -->
                    <div class="collapse" id="availability-form">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Set Your Availability</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <div class="form-group">
                                                <label class="form-label">Start Date</label>
                                                <div class="input-group">
                                                    <input type="date" class="form-control" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>" required>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text"><i class="far fa-calendar"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-group">
                                                <label class="form-label">End Date</label>
                                                <div class="input-group">
                                                    <input type="date" class="form-control" id="end_date" name="end_date" min="<?= date('Y-m-d') ?>" required>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text"><i class="far fa-calendar"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-group">
                                                <label class="form-label">Start Time</label>
                                                <div class="input-group">
                                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text"><i class="far fa-clock"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-group">
                                                <label class="form-label">End Time</label>
                                                <div class="input-group">
                                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text"><i class="far fa-clock"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="form-group">
                                                <label class="form-label">Time Slot Duration</label>
                                                <select class="form-control" id="time_slot" name="time_slot" required>
                                                    <option value="15">15 minutes</option>
                                                    <option value="30" selected>30 minutes</option>
                                                    <option value="45">45 minutes</option>
                                                    <option value="60">60 minutes</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="form-group">
                                                <label class="form-label">Max Patients per Slot</label>
                                                <input type="hidden" id="max_patients" name="max_patients" value="1">
                                                <input type="text" class="form-control" value="1 Patient per slot" readonly disabled>
                                                <small class="form-text text-muted">Each time slot accepts one appointment only</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="submit" name="submit_schedule" class="btn btn-primary w-100">
                                                    <i class="fas fa-save mr-2"></i>Save Schedule
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <div class="form-group">
                                                <label class="form-label">Additional Notes</label>
                                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any additional information about your availability"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Options</label>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="skip_weekends" name="skip_weekends" checked>
                                                    <label class="custom-control-label" for="skip_weekends">Skip Weekends</label>
                                                </div>
                                                <div class="custom-control custom-checkbox mt-2">
                                                    <input type="checkbox" class="custom-control-input" id="replace_existing" name="replace_existing">
                                                    <label class="custom-control-label" for="replace_existing">Replace existing schedules</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Calendar</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="calendar"></div>
                                    <div class="calendar-legend-wrapper">
                                        <div class="legend-container">
                                            <h5 class="legend-title">Calendar Legend</h5>
                                            <div class="legend-grid">
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #1e3a8a"></span>
                                                    <span class="legend-text">Available Schedules</span>
                                                </div>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #495057"></span>
                                                    <span class="legend-text">Past Schedules</span>
                                                </div>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #dc2626"></span>
                                                    <span class="legend-text">Regular Appointments</span>
                                                </div>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #ea580c"></span>
                                                    <span class="legend-text">Walk-in Appointments</span>
                                                </div>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #7c3aed"></span>
                                                    <span class="legend-text">Past Appointments</span>
                                                </div>
                                            </div>
                                            <div class="legend-info mt-3">
                                                <div class="alert alert-info mb-2 py-2 px-3">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    <strong>Click on any event</strong> to view detailed information including patient details, appointment reasons, and schedule notes.
                                                </div>
                                                <div class="alert alert-success mb-0 py-2 px-3">
                                                    <i class="fas fa-calendar-week mr-2"></i>
                                                    <strong>Navigation Tips:</strong> Use Month/Week/Day buttons to switch views. Click on dates to jump to specific days. Week view shows detailed time slots from 6 AM to 10 PM.
                                                </div>
                                            </div>
                                        </div>
                                    </div>                           
                                </div>
                            </div>
                        </div>
                    </div>  
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Availability Schedules</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="schedules_table" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Slot Duration</th>
                                                    <th>Max Patients</th>
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
                                                        <td><?= $schedule['notes'] ?></td>
                                                        <td>
                                                            <?php 
                                                            // Check if schedule has appointments (including walk-in appointments)
                                                            $checkQuery = "SELECT (
                                                                            SELECT COUNT(*) FROM admin_clients_appointments WHERE schedule_id = ? AND is_archived = 0
                                                                        ) + (
                                                                            SELECT COUNT(*) FROM admin_walkin_appointments WHERE schedule_id = ?
                                                                        ) as appt_count";
                                                            $checkStmt = $con->prepare($checkQuery);
                                                            $checkStmt->execute([$schedule['id'], $schedule['id']]);
                                                            $hasAppointments = ($checkStmt->fetch(PDO::FETCH_ASSOC)['appt_count'] > 0);
                                                            
                                                            if (!$hasAppointments) {
                                                                ?>
                                                                <a href="actions/delete_schedule.php?id=<?= $schedule['id'] ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this schedule?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                                <?php
                                                            } else {
                                                                ?>
                                                                <button class="btn btn-sm btn-secondary" disabled title="Cannot delete: Has booked appointments">
                                                                    <i class="fas fa-lock"></i>
                                                                </button>
                                                                <?php
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
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
        
        <?php include './config/admin_footer.php'; ?>
    </div>
    
    <?php include './config/site_css_js_links.php'; ?>
    
    <script src="plugins/fullcalendar/main.min.js"></script>
    <script src="plugins/sweetalert2/sweetalert2.min.js"></script>
    
    <script>
        $(function() {
            // Initialize SweetAlert2 Toast
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // Check for PHP messages and display them
            <?php if ($message): ?>
                Toast.fire({
                    icon: 'success',
                    title: '<?php echo addslashes($message); ?>'
                });
            <?php endif; ?>
            
            <?php if ($error): ?>
                Toast.fire({
                    icon: 'error',
                    title: '<?php echo addslashes($error); ?>'
                });
            <?php endif; ?>
            
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
                        // Target the Notes column (index 4)
                        targets: 4,
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
                    title: 'Data copied to clipboard!'
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
            
            // Handle send notification button click
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
                            // Show success toast
                            Toast.fire({
                                icon: 'success',
                                title: response.message
                            });
                            
                            // Update button to show sent status
                            btn.removeClass('btn-primary').addClass('btn-success')
                               .html('<i class="fas fa-check mr-2"></i> Notification Sent')
                               .prop('disabled', true);
                        } else {
                            // Show error toast
                            Toast.fire({
                                icon: 'error',
                                title: response.message
                            });
                            
                            // Reset button
                            btn.prop('disabled', false)
                               .html('<i class="fas fa-envelope mr-2"></i> Send Email Notification');
                        }
                    },
                    error: function() {
                        // Show error toast
                        Toast.fire({
                            icon: 'error',
                            title: 'An error occurred while sending the notification.'
                        });
                        
                        // Reset button
                        btn.prop('disabled', false)
                           .html('<i class="fas fa-envelope mr-2"></i> Send Email Notification');
                    }
                });
            });
            
            // Handle send walk-in notification button click
            $(document).on('click', '.send-walkin-notification', function() {
                const appointmentId = $(this).data('appointment-id');
                const btn = $(this);
                
                // Disable button and show loading state
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Sending...');
                
                // Send AJAX request to send walk-in notification
                $.ajax({
                    url: 'ajax/admin_notif_walkin_appointment_sender.php',
                    type: 'POST',
                    data: {
                        appointment_id: appointmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success toast
                            Toast.fire({
                                icon: 'success',
                                title: response.message
                            });
                            
                            // Update button to show sent status
                            btn.removeClass('btn-warning').addClass('btn-success')
                               .html('<i class="fas fa-check mr-2"></i> Walk-in Notification Sent')
                               .prop('disabled', true);
                        } else {
                            // Show error toast
                            Toast.fire({
                                icon: 'error',
                                title: response.message
                            });
                            
                            // Reset button
                            btn.prop('disabled', false)
                               .html('<i class="fas fa-walking mr-2"></i> Send Walk-in Notification');
                        }
                    },
                    error: function() {
                        // Show error toast
                        Toast.fire({
                            icon: 'error',
                            title: 'An error occurred while sending the walk-in notification.'
                        });
                        
                        // Reset button
                        btn.prop('disabled', false)
                           .html('<i class="fas fa-walking mr-2"></i> Send Walk-in Notification');
                    }
                });
            });
            
            // Function to automatically update past appointments to completed status
            function updatePastAppointments() {
                $.ajax({
                    url: 'ajax/admin_check_update_past_appointment.php',
                    type: 'POST',
                    data: {
                        doctor_id: <?= $staffId ?>
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
            
            // Initialize Calendar
            var calendarEl = document.getElementById('calendar');
            
            if (!calendarEl) {
                console.error('Calendar element not found');
                return;
            }
            
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
                        headerClass = props.is_past ? 'bg-secondary text-white' : 
                                     (<?= json_encode($staffRole) ?> === 'admin' ? 'bg-purple text-white' : 'bg-teal text-white');
                        
                        var formattedDate = event.start.toLocaleDateString('en-US', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                        
                        modalContent = `
                        <div class="schedule-details p-0">
                            <div class="card mb-0 border-0">
                                <div class="card-body p-0">
                                    <div class="event-date text-center py-4 ${props.is_past ? 'bg-secondary-light' : 'bg-info-light'} border-bottom">
                                        <div class="date-icon mb-2">
                                            <i class="fas fa-calendar-day fa-2x ${props.is_past ? 'text-secondary' : 'text-primary'}"></i>
                                        </div>
                                        <h4 class="mb-1 font-weight-bold">${formattedDate}</h4>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-user-md mr-1"></i>
                                            ${<?= json_encode($staffRole) ?> === 'admin' ? 'Administrator' : 'Health Worker'} Schedule
                                        </p>
                                    </div>
                                    <div class="event-info p-4">
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <div class="info-card bg-light rounded p-3 text-center">
                                                    <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                                                    <h6 class="font-weight-bold mb-1">Schedule Time</h6>
                                                    <p class="mb-0 text-muted">
                                                        ${event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - 
                                                        ${event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-card bg-light rounded p-3 text-center">
                                                    <i class="fas fa-hourglass-half fa-2x text-success mb-2"></i>
                                                    <h6 class="font-weight-bold mb-1">Time Slot</h6>
                                                    <p class="mb-0 text-muted">${props.time_slot} minutes per appointment</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <div class="info-card bg-light rounded p-3 text-center">
                                                    <i class="fas fa-users fa-2x text-warning mb-2"></i>
                                                    <h6 class="font-weight-bold mb-1">Capacity</h6>
                                                    <p class="mb-0 text-muted">${props.max_patients} patient per slot</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-card bg-light rounded p-3 text-center">
                                                    <i class="fas fa-${props.is_past ? 'history' : 'check-circle'} fa-2x ${props.is_past ? 'text-secondary' : 'text-success'} mb-2"></i>
                                                    <h6 class="font-weight-bold mb-1">Status</h6>
                                                    <span class="badge ${props.is_past ? 'badge-secondary' : 'badge-success'} px-3 py-2">
                                                        ${props.is_past ? 'Past Schedule' : 'Available'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        ${props.notes ? `
                                        <div class="notes-section mt-4 pt-3 border-top">
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="fas fa-sticky-note fa-lg text-info mr-2"></i>
                                                <h6 class="font-weight-bold mb-0">Additional Notes</h6>
                                            </div>
                                            <div class="bg-light rounded p-3">
                                                <p class="mb-0 text-dark">${props.notes}</p>
                                            </div>
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
                            headerClass = 'bg-dark text-white'; // Black for all past appointments
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
                            'bg-dark-light' : 
                            (props.is_walk_in ? 'bg-warning-light' : 'bg-danger-light');
                        
                        var iconColor = props.is_past ? 
                            'text-dark' : 
                            (props.is_walk_in ? 'text-warning' : 'text-danger');
                        
                        modalContent = `
                        <div class="appointment-details p-0">
                            <div class="card mb-0 border-0">
                                <div class="card-body p-0">
                                    <div class="patient-info p-4 ${bgClass} border-bottom">
                                        <div class="d-flex align-items-center">
                                            <div class="patient-icon mr-3">
                                                <div class="patient-avatar bg-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-${props.is_walk_in ? 'walking' : 'user'} fa-2x ${iconColor}"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h4 class="mb-1 font-weight-bold">
                                                    ${props.patient_name}
                                                    ${props.is_walk_in ? '<span class="badge badge-warning ml-2"><i class="fas fa-walking fa-xs"></i> Walk-in</span>' : ''}
                                                </h4>
                                                <p class="mb-1 text-muted">
                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                    ${formattedDate}
                                                </p>
                                                <p class="mb-0 text-muted">
                                                    <i class="fas fa-user-md mr-1"></i>
                                                    ${props.is_walk_in ? 'Walk-in appointment' : 'Scheduled appointment'} with ${<?= json_encode($staffRole) ?> === 'admin' ? 'Administrator' : 'Health Worker'}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="appointment-info p-4">
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <div class="info-card bg-light rounded p-3 text-center">
                                                    <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                                                    <h6 class="font-weight-bold mb-1">Appointment Time</h6>
                                                    <p class="mb-0 text-muted font-weight-bold">
                                                        ${event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-card bg-light rounded p-3 text-center">
                                                    <i class="fas fa-${props.status == 'completed' ? 'check-circle' : (props.status == 'approved' ? 'calendar-check' : 'exclamation-triangle')} fa-2x ${statusClass.includes('success') ? 'text-success' : (statusClass.includes('primary') ? 'text-primary' : 'text-warning')} mb-2"></i>
                                                    <h6 class="font-weight-bold mb-1">Status</h6>
                                                    <span class="badge ${statusClass} px-3 py-2">
                                                        ${statusText} ${props.is_past ? '(Past)' : ''}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        ${props.reason ? `
                                        <div class="reason-section mt-4 pt-3 border-top">
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="fas fa-comment-medical fa-lg text-info mr-2"></i>
                                                <h6 class="font-weight-bold mb-0">Reason for Visit</h6>
                                            </div>
                                            <div class="bg-light rounded p-3">
                                                <p class="mb-0 text-dark">${props.reason}</p>
                                            </div>
                                        </div>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                        
                        // Add send notification button for active appointments (both regular and walk-in)
                        if (props.type === 'appointment' && !props.is_past && props.appointment_id) {
                            if (props.is_walk_in) {
                                modalContent += `
                                <div class="text-center pb-3">
                                    <button type="button" class="btn btn-warning send-walkin-notification" data-appointment-id="${props.appointment_id}">
                                        <i class="fas fa-walking mr-2"></i> Send Email Notification
                                    </button>
                                </div>`;
                            } else {
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
                    Toast.fire({
                        icon: 'warning',
                        title: 'End date cannot be before start date'
                    });
                }
            });
            
            $('#start_time, #end_time').on('change', function() {
                var startTime = $('#start_time').val();
                var endTime = $('#end_time').val();
                
                if (startTime && endTime && endTime <= startTime) {
                    $('#end_time').val('');
                    Toast.fire({
                        icon: 'warning',
                        title: 'End time must be after start time'
                    });
                }
            });
            
            // Form submission validation
            $('form').on('submit', function(e) {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                var startTime = $('#start_time').val();
                var endTime = $('#end_time').val();
                
                if (!startDate || !endDate || !startTime || !endTime) {
                    e.preventDefault();
                    Toast.fire({
                        icon: 'error',
                        title: 'Please fill in all required fields'
                    });
                    return false;
                }
                
                if (new Date(endDate) < new Date(startDate)) {
                    e.preventDefault();
                    Toast.fire({
                        icon: 'error',
                        title: 'End date cannot be before start date'
                    });
                    return false;
                }
                
                if (endTime <= startTime) {
                    e.preventDefault();
                    Toast.fire({
                        icon: 'error',
                        title: 'End time must be after start time'
                    });
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html> 