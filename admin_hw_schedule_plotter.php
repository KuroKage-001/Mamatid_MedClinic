<?php
include './config/db_connection.php';
require_once './common_service/role_functions.php';

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

// Get booked appointments for this staff member
$appointmentsQuery = "SELECT a.*, ss.time_slot_minutes 
                     FROM admin_clients_appointments a 
                     JOIN admin_hw_schedules ss ON a.schedule_id = ss.id 
                     WHERE a.doctor_id = ? AND a.status != 'cancelled'";
$appointmentsStmt = $con->prepare($appointmentsQuery);
$appointmentsStmt->execute([$staffId]);
$appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Format schedules for calendar
$calendarEvents = [];
foreach ($schedules as $schedule) {
    $isPast = strtotime($schedule['schedule_date']) < strtotime(date('Y-m-d'));
    
    // Set color based on staff role
    $backgroundColor = $isPast ? '#A0A0A0' : ($staffRole == 'admin' ? '#8950FC' : '#1BC5BD');
    
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
    
    // Set color based on status and whether it's past
    $color = '#F64E60'; // Default red for active appointments
    if ($isPast) {
        if ($appointment['status'] == 'completed') {
            $color = '#28a745'; // Green for completed
        } else {
            $color = '#6c757d'; // Gray for past but not completed
        }
    }
    
    $calendarEvents[] = [
        'id' => 'appointment_' . $appointment['id'],
        'title' => 'Booked: ' . $appointment['patient_name'] . ($isPast ? ' [Past]' : ''),
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
    <?php include './config/data_tables_css.php'; ?>
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
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Schedule Plotter</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <!-- Messages will be handled by SweetAlert2 -->

                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Set Your Availability</h3>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="start_date">Start Date</label>
                                                    <input type="date" class="form-control" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="end_date">End Date</label>
                                                    <input type="date" class="form-control" id="end_date" name="end_date" min="<?= date('Y-m-d') ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="start_time">Start Time</label>
                                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="end_time">End Time</label>
                                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="time_slot">Time Slot (minutes)</label>
                                                    <select class="form-control" id="time_slot" name="time_slot" required>
                                                        <option value="15">15 minutes</option>
                                                        <option value="30" selected>30 minutes</option>
                                                        <option value="45">45 minutes</option>
                                                        <option value="60">60 minutes</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="max_patients">Max Patients per Slot</label>
                                                    <input type="hidden" id="max_patients" name="max_patients" value="1">
                                                    <input type="text" class="form-control" value="1" readonly disabled>
                                                    <small class="form-text text-muted">Each time slot can only accept one appointment</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional information about your availability"></textarea>
                                        </div>
                                        
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="skip_weekends" name="skip_weekends" checked>
                                            <label class="custom-control-label" for="skip_weekends">Skip Weekends</label>
                                        </div>
                                        
                                        <div class="custom-control custom-checkbox mt-2">
                                            <input type="checkbox" class="custom-control-input" id="replace_existing" name="replace_existing">
                                            <label class="custom-control-label" for="replace_existing">Replace existing schedules in this date range</label>
                                        </div>
                                        
                                        <div class="form-group mt-4">
                                            <button type="submit" name="submit_schedule" class="btn btn-primary">
                                                <i class="fas fa-save mr-2"></i> Save Schedule
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-7">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Your Schedule Calendar</h3>
                                </div>
                                <div class="card-body">
                                    <div id="calendar"></div>
                                    <div class="mt-4">
                                        <div class="legend-container">
                                            <h5 class="legend-title">Schedule Legend</h5>
                                            <div class="legend-items">
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: <?php echo $staffRole == 'admin' ? '#8950FC' : '#1BC5BD'; ?>"></span>
                                                    <span class="legend-text">Available Schedules</span>
                                            </div>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #A0A0A0"></span>
                                                    <span class="legend-text">Past Schedules</span>
                                            </div>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #F64E60"></span>
                                                    <span class="legend-text">Active Appointments</span>
                                            </div>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #28a745"></span>
                                                    <span class="legend-text">Completed Appointments</span>
                                            </div>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background-color: #6c757d"></span>
                                                    <span class="legend-text">Past Appointments</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="alert alert-info p-3 rounded-lg shadow-sm">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <span>Unlike doctor schedules, your availability is automatically approved for patients to book.</span>
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
                                    <h3 class="card-title">Your Schedules</h3>
                                </div>
                                <div class="card-body">
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
                                                        // Check if schedule has appointments
                                                        $checkQuery = "SELECT COUNT(*) as appt_count FROM admin_clients_appointments WHERE schedule_id = ?";
                                                        $checkStmt = $con->prepare($checkQuery);
                                                        $checkStmt->execute([$schedule['id']]);
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include './config/admin_footer.php'; ?>
    </div>
    
    <?php include './config/site_js_links.php'; ?>
    <?php include './config/data_tables_js.php'; ?>
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
            
            // Initialize DataTable
            $("#schedules_table").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "order": [[0, "asc"]]
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
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: <?= json_encode($calendarEvents) ?>,
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
                        title: info.event.extendedProps.type.charAt(0).toUpperCase() + info.event.extendedProps.type.slice(1) + 
                              (info.event.extendedProps.is_past ? ' (Past)' : ''),
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
                        modalTitle = '<i class="fas fa-user-clock mr-2"></i> Appointment Information';
                        
                        // Set header class based on appointment status and whether it's past
                        if (props.is_past) {
                            if (props.status == 'completed') {
                                headerClass = 'bg-success text-white';
                            } else {
                                headerClass = 'bg-secondary text-white';
                            }
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
                        
                        modalContent = `
                        <div class="appointment-details p-0">
                            <div class="card mb-0 border-0">
                                <div class="card-body p-0">
                                    <div class="patient-info p-4 ${props.is_past ? (props.status == 'completed' ? 'bg-success-light' : 'bg-secondary-light') : 'bg-danger-light'} border-bottom">
                                        <div class="d-flex align-items-center">
                                            <div class="patient-icon mr-3">
                                                <div class="patient-avatar bg-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-user fa-2x ${props.is_past ? (props.status == 'completed' ? 'text-success' : 'text-secondary') : 'text-danger'}"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h4 class="mb-1 font-weight-bold">${props.patient_name}</h4>
                                                <p class="mb-1 text-muted">
                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                    ${formattedDate}
                                                </p>
                                                <p class="mb-0 text-muted">
                                                    <i class="fas fa-user-md mr-1"></i>
                                                    Appointment with ${<?= json_encode($staffRole) ?> === 'admin' ? 'Administrator' : 'Health Worker'}
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
                        
                        // Add send notification button for active appointments
                        if (props.type === 'appointment' && !props.is_past && props.appointment_id) {
                            modalContent += `
                            <div class="text-center pb-3">
                                <button type="button" class="btn btn-primary send-notification" data-appointment-id="${props.appointment_id}">
                                    <i class="fas fa-envelope mr-2"></i> Send Email Notification
                                </button>
                            </div>`;
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