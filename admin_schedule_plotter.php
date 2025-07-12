<?php
/**
 * Appointment Plotter
 * 
 * This file manages doctor schedule approvals and planning for appointments.
 * Only admins and health workers can approve doctor schedules.
 * Staff schedules (admin and health worker) are auto-approved.
 */

include './config/db_connection.php';
require_once './system/utilities/admin_client_role_functions_services.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission - only admin and health workers can access this page
requireRole(['admin', 'health_worker']);

$message = '';
$error = '';

// Get message/error from URL if redirected
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Handle doctor schedule approval
if (isset($_POST['approve_schedule'])) {
    $scheduleId = $_POST['schedule_id'];
    $isApproved = isset($_POST['is_approved']) ? 1 : 0;
    $approvalNotes = $_POST['approval_notes'];
    
    try {
        $query = "UPDATE admin_doctor_schedules SET is_approved = ?, approval_notes = ? WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$isApproved, $approvalNotes, $scheduleId]);
        $message = "Doctor schedule " . ($isApproved ? "approved" : "rejected") . " successfully!";
    } catch (PDOException $ex) {
        $error = "Error updating schedule: " . $ex->getMessage();
    }
}

// Fetch all doctor schedules
$doctorSchedules = [];
$scheduleQuery = "SELECT ds.*, u.display_name as doctor_name 
                 FROM admin_doctor_schedules ds
                 JOIN admin_user_accounts u ON ds.doctor_id = u.id
                 ORDER BY ds.schedule_date ASC, ds.start_time ASC";
$scheduleStmt = $con->prepare($scheduleQuery);
$scheduleStmt->execute();
$doctorSchedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all staff schedules for reference (already auto-approved)
$staffSchedules = [];
$staffQuery = "SELECT ss.*, u.display_name as staff_name, u.role as staff_role
              FROM admin_hw_schedules ss
              JOIN admin_user_accounts u ON ss.staff_id = u.id
              WHERE u.role IN ('admin', 'health_worker')
              ORDER BY ss.schedule_date ASC, ss.start_time ASC";
$staffStmt = $con->prepare($staffQuery);
$staffStmt->execute();
$staffSchedules = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all appointments for calendar display (including walk-ins)
$appointmentsQuery = "SELECT a.*, u.display_name as doctor_name, u.role as doctor_role,
                      CASE 
                          WHEN a.schedule_id IN (SELECT id FROM admin_doctor_schedules) THEN 'doctor'
                          WHEN a.schedule_id IN (SELECT id FROM admin_hw_schedules) THEN 'staff'
                          ELSE 'unknown'
                      END as schedule_type,
                      'regular' as appointment_type
                      FROM admin_clients_appointments a
                      LEFT JOIN admin_user_accounts u ON a.doctor_id = u.id
                      WHERE a.status != 'cancelled' AND a.is_archived = 0
                      UNION ALL
                      SELECT w.id, w.patient_name, w.phone_number, w.address, w.date_of_birth, w.gender,
                             w.appointment_date, w.appointment_time, w.reason, w.status, w.notes,
                             w.schedule_id, w.provider_id as doctor_id, w.created_at, w.updated_at,
                             0 as email_sent, 0 as reminder_sent, w.is_archived,
                             NULL as view_token, NULL as token_expiry, NULL as archived_at, NULL as archived_by, NULL as archive_reason,
                             1 as is_walkin,
                             u2.display_name as doctor_name, u2.role as doctor_role,
                             CASE 
                                 WHEN w.schedule_id IN (SELECT id FROM admin_doctor_schedules) THEN 'doctor'
                                 WHEN w.schedule_id IN (SELECT id FROM admin_hw_schedules) THEN 'staff'
                                 ELSE 'unknown'
                             END as schedule_type,
                             'walk-in' as appointment_type
                      FROM admin_walkin_appointments w
                      LEFT JOIN admin_user_accounts u2 ON w.provider_id = u2.id
                      WHERE w.status != 'cancelled' AND w.is_archived = 0
                      ORDER BY appointment_date ASC, appointment_time ASC";
$appointmentsStmt = $con->prepare($appointmentsQuery);
$appointmentsStmt->execute();
$allAppointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Format all schedules and appointments for calendar
$calendarEvents = [];

// Add doctor schedules to calendar
foreach ($doctorSchedules as $schedule) {
    $isPast = strtotime($schedule['schedule_date']) < strtotime(date('Y-m-d'));
    $status = $schedule['is_approved'] ? 'Approved' : 'Pending';
    $color = $isPast ? 'rgba(108, 117, 125, 0.7)' : ($schedule['is_approved'] ? 'rgba(0, 123, 255, 0.7)' : 'rgba(253, 126, 20, 0.7)');
    
    $calendarEvents[] = [
        'id' => 'doctor_schedule_' . $schedule['id'],
        'title' => 'Dr. ' . $schedule['doctor_name'] . ' (' . $status . ')',
        'start' => $schedule['schedule_date'] . 'T' . $schedule['start_time'],
        'end' => $schedule['schedule_date'] . 'T' . $schedule['end_time'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'type' => 'doctor_schedule',
            'schedule_id' => $schedule['id'],
            'doctor_name' => $schedule['doctor_name'],
            'doctor_id' => $schedule['doctor_id'],
            'schedule_date' => $schedule['schedule_date'],
            'max_patients' => $schedule['max_patients'],
            'time_slot' => $schedule['time_slot_minutes'],
            'notes' => $schedule['notes'],
            'is_approved' => $schedule['is_approved'],
            'approval_notes' => $schedule['approval_notes'],
            'is_past' => $isPast
        ]
    ];
}

// Add staff schedules to calendar
foreach ($staffSchedules as $schedule) {
    $isPast = strtotime($schedule['schedule_date']) < strtotime(date('Y-m-d'));
    $color = $isPast ? 'rgba(108, 117, 125, 0.7)' : ($schedule['staff_role'] == 'admin' ? 'rgba(111, 66, 193, 0.7)' : 'rgba(32, 201, 151, 0.7)');
    $roleText = $schedule['staff_role'] == 'admin' ? 'Admin' : 'Health Worker';
    
    $calendarEvents[] = [
        'id' => 'staff_schedule_' . $schedule['id'],
        'title' => $roleText . ': ' . $schedule['staff_name'],
        'start' => $schedule['schedule_date'] . 'T' . $schedule['start_time'],
        'end' => $schedule['schedule_date'] . 'T' . $schedule['end_time'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'type' => 'staff_schedule',
            'schedule_id' => $schedule['id'],
            'staff_name' => $schedule['staff_name'],
            'staff_id' => $schedule['staff_id'],
            'staff_role' => $schedule['staff_role'],
            'schedule_date' => $schedule['schedule_date'],
            'max_patients' => $schedule['max_patients'],
            'time_slot' => $schedule['time_slot_minutes'],
            'notes' => $schedule['notes'],
            'is_past' => $isPast
        ]
    ];
}

// Note: Appointments are now displayed in popups when clicking on schedules
// This improves calendar visibility by showing only schedules as main events

// Get statistics for overview
$totalDoctorSchedules = count($doctorSchedules);
$totalStaffSchedules = count($staffSchedules);
// Add is_walkin column if it doesn't exist (for compatibility)
try {
    $checkColumnQuery = "SHOW COLUMNS FROM admin_clients_appointments LIKE 'is_walkin'";
    $checkStmt = $con->prepare($checkColumnQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        $alterQuery = "ALTER TABLE admin_clients_appointments ADD COLUMN is_walkin TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'If 1, appointment is a walk-in appointment'";
        $alterStmt = $con->prepare($alterQuery);
        $alterStmt->execute();
    }
} catch (PDOException $ex) {
    // Column might already exist, continue
}

$totalAppointments = count($allAppointments);
$pendingApprovals = count(array_filter($doctorSchedules, function($schedule) {
    return !$schedule['is_approved'];
}));

// Additional meaningful statistics
$approvedDoctorSchedules = $totalDoctorSchedules - $pendingApprovals;
$todaysSchedules = 0;
$upcomingSchedules = 0;
$totalWalkIns = 0;
$totalRegularAppointments = 0;

// Count today's schedules and upcoming schedules
$today = date('Y-m-d');
foreach (array_merge($doctorSchedules, $staffSchedules) as $schedule) {
    if ($schedule['schedule_date'] == $today) {
        $todaysSchedules++;
    } elseif ($schedule['schedule_date'] > $today) {
        $upcomingSchedules++;
    }
}

// Count walk-ins vs regular appointments
foreach ($allAppointments as $appointment) {
    if (isset($appointment['is_walkin']) && $appointment['is_walkin']) {
        $totalWalkIns++;
    } else {
        $totalRegularAppointments++;
    }
}

// Calculate active providers
$activeProviders = count(array_unique(array_merge(
    array_column($doctorSchedules, 'doctor_id'),
    array_column($staffSchedules, 'staff_id')
)));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <link href="plugins/fullcalendar/main.min.css" rel="stylesheet">
    <title>Appointment Plotter - Mamatid Health Center System</title>
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
            text-transform: capitalize;
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

        .btn-secondary {
            background: #e4e6ef;
            border: none;
            color: var(--dark-color);
        }

        .btn-secondary:hover {
            background: #d7dae7;
            color: var(--dark-color);
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

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(243, 246, 249, 0.5);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(54, 153, 255, 0.05);
        }

        /* Badge Styling */
        .badge {
            padding: 0.4rem 0.8rem;
            font-weight: 500;
            border-radius: 8px;
            font-size: 0.85rem;
            letter-spacing: 0.025rem;
            border: 1px solid transparent;
            backdrop-filter: blur(8px);
            transition: all 0.2s ease;
        }

        .badge-success {
            background-color: rgba(32, 201, 151, 0.15);
            color: #0d7b57;
            border-color: rgba(32, 201, 151, 0.25);
        }

        .badge-warning {
            background-color: rgba(253, 126, 20, 0.15);
            color: #ad4e00;
            border-color: rgba(253, 126, 20, 0.25);
        }

        .badge-primary {
            background-color: rgba(0, 123, 255, 0.15);
            color: #0056b3;
            border-color: rgba(0, 123, 255, 0.25);
        }

        .badge-info {
            background-color: rgba(111, 66, 193, 0.15);
            color: #5a2d91;
            border-color: rgba(111, 66, 193, 0.25);
        }

        .badge-secondary {
            background-color: rgba(108, 117, 125, 0.15);
            color: #495057;
            border-color: rgba(108, 117, 125, 0.25);
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

        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: var(--light-color);
            border-bottom: 1px solid #eee;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 1.5rem;
        }

        .modal-header .modal-title {
            font-weight: 600;
            color: var(--dark-color);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #eee;
            padding: 1rem 1.5rem;
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
            text-transform: capitalize;
        }

        /* Custom Switch */
        .custom-switch .custom-control-label::before {
            height: 1.5rem;
            width: 2.75rem;
            border-radius: 3rem;
        }

        .custom-switch .custom-control-label::after {
            width: calc(1.5rem - 4px);
            height: calc(1.5rem - 4px);
            border-radius: 50%;
        }

        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .card-header {
                padding: 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .table thead th,
            .table tbody td {
                padding: 0.75rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1rem;
            }
        }
        
        /* Staff schedule badge */
        .badge-role {
            font-size: 0.7rem;
            margin-left: 5px;
            padding: 0.2rem 0.4rem;
        }
        
        .badge-admin {
            background-color: rgba(111, 66, 193, 0.15);
            color: #5a2d91;
            border-color: rgba(111, 66, 193, 0.25);
        }
        
        .badge-health-worker {
            background-color: rgba(32, 201, 151, 0.15);
            color: #0d7b57;
            border-color: rgba(32, 201, 151, 0.25);
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

        /* Appointment Cards Styling */
        .appointment-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #e9ecef;
        }
        
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .appointment-card .card-body {
            padding: 1rem;
        }
        
        .appointment-card .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .appointment-card .card-text {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .appointment-card .badge-sm {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* Different border colors for appointment types */
        .appointment-card[data-type="regular"] {
            border-left-color: #2196F3;
        }
        
        .appointment-card[data-type="walk-in"] {
            border-left-color: #FF9800;
        }
        
        .appointment-card[data-status="completed"] {
            border-left-color: #4CAF50;
        }
        
        .appointment-card[data-status="past"] {
            border-left-color: #6c757d;
            background-color: #f8f9fa;
        }
        
        /* Appointments section styling */
        .appointments-section {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .appointments-section h6 {
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }
        
        .appointments-list {
            padding-top: 1rem;
        }
        
        /* Empty state styling */
        .appointments-section .alert {
            border: none;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .appointments-section .alert-info {
            background-color: rgba(54, 153, 255, 0.1);
            color: #2196F3;
        }
        
        .appointments-section .alert-warning {
            background-color: rgba(255, 152, 0, 0.1);
            color: #FF9800;
        }
        
        .appointments-section .alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        /* Modern Statistics Cards */
        .stats-card {
            border-radius: 16px;
            padding: 0;
            overflow: hidden;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .stats-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stats-card .card-body {
            padding: 2rem;
            position: relative;
            z-index: 2;
        }

        .stats-card .icon {
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stats-card p {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            opacity: 0.95;
        }

        .stats-card small {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.8;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), transparent);
            z-index: 1;
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            color: white;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #6f42c1 0%, #5a2d91 100%);
            color: white;
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #fd7e14 0%, #e85d04 100%);
            color: white;
        }

        .bg-gradient-teal {
            background: linear-gradient(135deg, #20c997 0%, #198754 100%);
            color: white;
        }

        .bg-gradient-purple {
            background: linear-gradient(135deg, #6f42c1 0%, #495057 100%);
            color: white;
        }

        /* Stats card icons with floating animation */
        .stats-icon {
            position: absolute;
            top: 50%;
            right: 2rem;
            transform: translateY(-50%);
            font-size: 3rem;
            opacity: 0.2;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(-50%) translateX(0); }
            50% { transform: translateY(-50%) translateX(5px); }
        }

        /* Modern metric cards grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .metric-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: white;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .metric-subtitle {
            font-size: 0.8rem;
            color: #6c757d;
            margin: 0 0 0.25rem 0;
        }

        .metric-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.4rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 0.25rem;
        }

        .metric-badge.success {
            background-color: rgba(32, 201, 151, 0.1);
            color: #20c997;
        }

        .metric-badge.warning {
            background-color: rgba(253, 126, 20, 0.1);
            color: #fd7e14;
        }

        .metric-badge.info {
            background-color: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }

        /* Additional badge styles */
        .badge-purple {
            background-color: rgba(137, 80, 252, 0.1);
            color: var(--info-color);
        }

        .bg-purple {
            background-color: #8950FC !important;
        }

        /* Modern Schedule Management Modal Styling - Dark Theme */
        .schedule-manage-card {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideInUp 0.5s ease;
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

        .schedule-manage-header {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem 2rem;
            color: white;
        }

        .schedule-icon-container {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 15px rgba(52, 152, 219, 0.3);
        }

        .schedule-icon-container i {
            font-size: 1.5rem;
            color: white;
        }

        .schedule-manage-title {
            color: #ecf0f1;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .schedule-manage-subtitle {
            color: #bdc3c7;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .btn-close-schedule {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-close-schedule:hover {
            background: rgba(231, 76, 60, 0.8);
            transform: scale(1.1);
        }

        .schedule-manage-body {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 2rem;
            color: white;
        }

        /* Doctor Info Section */
        .schedule-info-section {
            margin-bottom: 2rem;
        }

        .doctor-info-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .doctor-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .doctor-name {
            color: #ecf0f1;
            font-weight: 600;
        }

        .doctor-specialty {
            color: #bdc3c7;
        }

        /* Schedule Details Grid */
        .schedule-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
        }

        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.12);
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .detail-icon i {
            font-size: 1.2rem;
        }

        .detail-content {
            flex: 1;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #bdc3c7;
            font-weight: 500;
            margin-bottom: 0.25rem;
            display: block;
        }

        .detail-value {
            font-size: 1rem;
            color: #ecf0f1;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .detail-extra {
            font-size: 0.75rem;
            color: #95a5a6;
        }

        /* Status Badge */
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            border: 1px solid transparent;
            backdrop-filter: blur(8px);
            transition: all 0.2s ease;
            letter-spacing: 0.025rem;
        }

        .status-approved {
            background-color: rgba(0, 123, 255, 0.15);
            color: #0056b3;
            border-color: rgba(0, 123, 255, 0.25);
        }

        .status-pending {
            background-color: rgba(253, 126, 20, 0.15);
            color: #ad4e00;
            border-color: rgba(253, 126, 20, 0.25);
        }

        /* Form Sections */
        .approval-section,
        .notes-section,
        .doctor-notes-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .section-header {
            margin-bottom: 1.25rem;
        }

        .section-title {
            color: #ecf0f1;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .section-description {
            color: #bdc3c7;
            font-size: 0.9rem;
            margin: 0;
            opacity: 0.9;
        }

        /* Custom Switch Enhanced */
        .custom-switch-lg .custom-control-label::before {
            height: 2rem;
            width: 3.5rem;
            border-radius: 2rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.1);
        }

        .custom-switch-lg .custom-control-label::after {
            width: calc(2rem - 6px);
            height: calc(2rem - 6px);
            border-radius: 50%;
            background: #ecf0f1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .custom-switch-lg .custom-control-input:checked ~ .custom-control-label::before {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            border-color: #27ae60;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.3);
        }

        .approval-toggle-container {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .approve-switch-label {
            padding-left: 4.5rem !important;
            cursor: pointer;
        }

        .switch-text {
            color: #ecf0f1;
            font-size: 1rem;
            font-weight: 600;
            display: block;
            margin-bottom: 0.25rem;
        }

        .switch-subtext {
            color: #bdc3c7;
            font-size: 0.85rem;
            font-weight: 400;
            display: block;
        }

        /* Notes Input */
        .notes-input-container {
            position: relative;
        }

        .notes-textarea {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            resize: vertical;
            min-height: 120px;
            backdrop-filter: blur(5px);
        }

        .notes-textarea:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
        }

        .notes-textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Doctor Notes Display */
        .doctor-notes-display {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 1rem;
            border-left: 4px solid #3498db;
        }

        .doctor-notes-display p {
            color: #ecf0f1;
            margin: 0;
            line-height: 1.6;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-cancel-schedule,
        .btn-save-schedule {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 120px;
            justify-content: center;
        }

        .btn-cancel-schedule {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
        }

        .btn-cancel-schedule:hover {
            background: linear-gradient(135deg, #7f8c8d 0%, #95a5a6 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-save-schedule {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }

        .btn-save-schedule:hover {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(39, 174, 96, 0.4);
        }

                 /* Responsive Design for Schedule Management */
        @media (max-width: 768px) {
            .schedule-manage-header {
                padding: 1rem;
            }

            .schedule-manage-body {
                padding: 1rem;
            }

            .schedule-details-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-cancel-schedule,
            .btn-save-schedule {
                width: 100%;
            }
        }

        /* Modern Manage Button Styling */
        .btn-manage-schedule {
            position: relative;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.6rem 1.2rem;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
            overflow: hidden;
        }

        .btn-manage-schedule::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-manage-schedule:hover::before {
            left: 100%;
        }

        .btn-manage-schedule:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
            background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
        }

        .btn-manage-schedule:active {
            transform: translateY(-1px);
        }

        .btn-manage-schedule i {
            font-size: 0.9rem;
            opacity: 0.9;
            z-index: 2;
            position: relative;
        }

        .btn-text {
            z-index: 2;
            position: relative;
        }

        .btn-status-indicator {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
            z-index: 3;
        }

        .btn-status-indicator.status-approved {
            background: #27ae60;
            box-shadow: 0 0 8px rgba(39, 174, 96, 0.6);
            animation: pulse-green 2s infinite;
        }

        .btn-status-indicator.status-pending {
            background: #f39c12;
            box-shadow: 0 0 8px rgba(243, 156, 18, 0.6);
            animation: pulse-orange 2s infinite;
        }

        @keyframes pulse-green {
            0% {
                box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.7);
            }
            70% {
                box-shadow: 0 0 0 6px rgba(39, 174, 96, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(39, 174, 96, 0);
            }
        }

        @keyframes pulse-orange {
            0% {
                box-shadow: 0 0 0 0 rgba(243, 156, 18, 0.7);
            }
            70% {
                box-shadow: 0 0 0 6px rgba(243, 156, 18, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(243, 156, 18, 0);
            }
        }

        /* Enhanced Table Styling */
        .table tbody tr:hover {
            background-color: rgba(54, 153, 255, 0.05);
            transition: all 0.2s ease;
        }

        /* Badge enhancements for better visibility */
        .badge-success {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            border: none;
            box-shadow: 0 2px 6px rgba(39, 174, 96, 0.3);
        }

        .badge-warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            border: none;
            box-shadow: 0 2px 6px rgba(243, 156, 18, 0.3);
        }

        /* Visual Feedback for Approval Actions */
        .approval-granted {
            animation: approvalGranted 0.6s ease;
        }

        .approval-revoked {
            animation: approvalRevoked 0.6s ease;
        }

        @keyframes approvalGranted {
            0% { transform: scale(1); }
            50% { 
                transform: scale(1.05); 
                color: #2ecc71;
                text-shadow: 0 0 10px rgba(46, 204, 113, 0.5);
            }
            100% { transform: scale(1); }
        }

        @keyframes approvalRevoked {
            0% { transform: scale(1); }
            50% { 
                transform: scale(1.05); 
                color: #e74c3c;
                text-shadow: 0 0 10px rgba(231, 76, 60, 0.5);
            }
            100% { transform: scale(1); }
        }

        /* Enhanced tooltip styling */
        .btn-manage-schedule[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 5px;
        }

                 .btn-manage-schedule[title]:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }

        /* Schedule Management Tabs Styling */
        .nav-tabs {
            border: none;
            margin-bottom: 20px;
            gap: 10px;
        }

        .nav-tabs .nav-item {
            margin: 0;
        }

        .nav-tabs .nav-link {
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            color: #7E8299;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .nav-tabs .nav-link:hover {
            color: #3699FF;
            background: rgba(54, 153, 255, 0.1);
            border-color: rgba(54, 153, 255, 0.2);
            transform: translateY(-1px);
        }

        .nav-tabs .nav-link.active {
            color: #3699FF;
            background: rgba(54, 153, 255, 0.15);
            border-color: rgba(54, 153, 255, 0.3);
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.2);
        }

        .nav-tabs .nav-link i {
            font-size: 1rem;
        }

        .nav-tabs .nav-link .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 8px;
        }

        /* Tab Content Styling */
        .tab-content {
            background: transparent;
        }

        .tab-pane {
            border-radius: 12px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .schedule-tab-content {
            background: transparent;
        }

        .schedule-section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        /* Tab Animation */
        .tab-pane {
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .tab-pane.active {
            opacity: 1;
            transform: translateY(0);
        }

        .fade-in {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design for Tabs */
        @media (max-width: 768px) {
            .nav-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .nav-tabs .nav-link {
                white-space: nowrap;
                padding: 10px 16px;
                font-size: 0.9rem;
            }

            .schedule-section-title {
                font-size: 1.25rem;
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

        /* Custom layout for DataTable wrapper */
        .dataTables_wrapper .row:first-child {
            margin-bottom: 15px;
        }

        .dataTables_wrapper .dataTables_filter {
            float: left !important;
            text-align: left !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            width: 300px;
            border-radius: 8px;
            border: 2px solid #e4e6ef;
            padding: 0.625rem 1rem;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
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

            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
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
        }



        /* Modern Export Actions CSS */
    </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar and Sidebar -->
        <?php include './config/admin_header.php'; include './config/admin_sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6" style="padding-left: 20px;">
                            <h1>Appointment Plotter</h1>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">
                <!-- Display Messages -->
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

                <!-- Enhanced Statistics Overview -->
                <div class="row mb-4">
                    <!-- Primary Statistics Cards -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card bg-gradient-info text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $totalDoctorSchedules; ?></h3>
                                        <p class="mb-1 font-weight-bold">Doctor Schedules</p>
                                        <?php if ($pendingApprovals > 0): ?>
                                            <small class="d-block">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?php echo $pendingApprovals; ?> Pending Approval<?php echo $pendingApprovals > 1 ? 's' : ''; ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="d-block">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                All Approved
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-user-md fa-2x"></i>
                                    </div>
                                </div>
                                <i class="fas fa-user-md stats-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="card bg-gradient-success text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $totalStaffSchedules; ?></h3>
                                        <p class="mb-1 font-weight-bold">Health Worker Schedules</p>
                                        <small class="d-block">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Auto-Approved
                                        </small>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                                <i class="fas fa-users stats-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="card bg-gradient-primary text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $totalAppointments; ?></h3>
                                        <p class="mb-1 font-weight-bold">Total Appointments</p>
                                        <small class="d-block">
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            All Providers
                                        </small>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-calendar-check fa-2x"></i>
                                    </div>
                                </div>
                                <i class="fas fa-calendar-check stats-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="card bg-gradient-warning text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $activeProviders; ?></h3>
                                        <p class="mb-1 font-weight-bold">Active Providers</p>
                                        <small class="d-block">
                                            <i class="fas fa-heartbeat mr-1"></i>
                                            Healthcare Staff
                                        </small>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-user-friends fa-2x"></i>
                                    </div>
                                </div>
                                <i class="fas fa-user-friends stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Metrics Grid -->
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-title">Today's Activity</div>
                            <div class="metric-icon" style="background-color: #007bff;">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $todaysSchedules; ?></div>
                        <div class="metric-subtitle">Active schedules today</div>
                        <div class="metric-badge info">
                            <i class="fas fa-clock"></i>
                            Current Day
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-title">Upcoming Schedules</div>
                            <div class="metric-icon" style="background-color: #20c997;">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $upcomingSchedules; ?></div>
                        <div class="metric-subtitle">Future scheduled slots</div>
                        <div class="metric-badge success">
                            <i class="fas fa-arrow-up"></i>
                            Scheduled
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-title">Walk-in Appointments</div>
                            <div class="metric-icon" style="background-color: #fd7e14;">
                                <i class="fas fa-walking"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $totalWalkIns; ?></div>
                        <div class="metric-subtitle">Immediate care visits</div>
                        <div class="metric-badge warning">
                            <i class="fas fa-bolt"></i>
                            Urgent
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-title">Regular Appointments</div>
                            <div class="metric-icon" style="background-color: #6f42c1;">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $totalRegularAppointments; ?></div>
                        <div class="metric-subtitle">Pre-scheduled visits</div>
                        <div class="metric-badge info">
                            <i class="fas fa-calendar-check"></i>
                            Planned
                        </div>
                    </div>

                    <?php if ($pendingApprovals > 0): ?>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-title">Approval Queue</div>
                            <div class="metric-icon" style="background-color: #dc3545;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $pendingApprovals; ?></div>
                        <div class="metric-subtitle">Schedules awaiting approval</div>
                        <div class="metric-badge warning">
                            <i class="fas fa-clock"></i>
                            Action Required
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-title">Schedule Coverage</div>
                            <div class="metric-icon" style="background-color: #17a2b8;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo round((($totalDoctorSchedules + $totalStaffSchedules) / max(1, $activeProviders)), 1); ?></div>
                        <div class="metric-subtitle">Avg schedules per provider</div>
                        <div class="metric-badge info">
                            <i class="fas fa-chart-line"></i>
                            Utilization
                        </div>
                    </div>
                </div>

                <!-- Calendar Overview -->
                <div class="card card-outline card-primary mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Schedule Overview Calendar
                        </h3>
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
                                        <span class="legend-color" style="background-color: #007bff;"></span>
                                        <span class="legend-text">Approved Doctor Schedules</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #fd7e14;"></span>
                                        <span class="legend-text">Pending Doctor Schedules</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #6f42c1;"></span>
                                        <span class="legend-text">Admin Schedules</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #20c997;"></span>
                                        <span class="legend-text">Health Worker Schedules</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #6c757d;"></span>
                                        <span class="legend-text">Past Schedules</span>
                                    </div>
                                    </div>
                                <div class="legend-info mt-3">
                                    <div class="alert alert-info mb-2 py-2 px-3">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <strong>Click on any schedule</strong> to view associated appointments and walk-ins for that time slot.
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

                <!-- Schedule Management Tabs -->
                <div class="card card-outline card-primary mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                                <i class="fas fa-calendar-alt mr-2"></i>
                            Schedule Management
                            </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs" id="scheduleManagementTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="doctor-schedules-tab" data-toggle="tab" href="#doctor-schedules" role="tab">
                                    <i class="fas fa-user-md mr-2"></i>Doctor Schedules
                                    <span class="badge badge-info ml-2"><?php echo count($doctorSchedules); ?></span>
                                    <?php if ($pendingApprovals > 0): ?>
                                        <span class="badge badge-warning ml-2"><?php echo $pendingApprovals; ?> pending</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="staff-schedules-tab" data-toggle="tab" href="#staff-schedules" role="tab">
                                    <i class="fas fa-users mr-2"></i>Health Worker Schedules
                                    <span class="badge badge-success ml-2"><?php echo count($staffSchedules); ?></span>
                                </a>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="scheduleManagementTabsContent">
                            <!-- Doctor Schedules Tab -->
                            <div class="tab-pane fade show active" id="doctor-schedules" role="tabpanel">
                                <div class="schedule-tab-content">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="schedule-section-title">
                                            <i class="fas fa-user-md mr-2"></i>Doctor Schedules Management
                                        </h4>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($pendingApprovals > 0): ?>
                                <button class="btn btn-success" onclick="approveAllPending()">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Approve All Pending (<?php echo $pendingApprovals; ?>)
                                </button>
                                <?php else: ?>
                                <div class="alert alert-success mb-0 py-2 px-3">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    All Schedules Approved
                                </div>
                                <?php endif; ?>
                                </div>
                            </div>
                        <div class="table-responsive">
                            <table id="doctorSchedules" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Time Slot</th>
                                        <th>Max Patients</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doctorSchedules as $schedule): ?>
                                    <tr data-schedule-id="<?php echo $schedule['id']; ?>" 
                                        data-schedule-status="<?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'approved' : 'pending'; ?>"
                                        class="schedule-row <?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'approved-row' : 'pending-row'; ?>">
                                        <td><?php echo htmlspecialchars($schedule['doctor_name']); ?></td>
                                        <td><?php echo date('M d, Y (D)', strtotime($schedule['schedule_date'])); ?></td>
                                        <td>
                                            <?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                                     date('h:i A', strtotime($schedule['end_time'])); ?>
                                        </td>
                                        <td><?php echo $schedule['time_slot_minutes']; ?> minutes</td>
                                        <td><?php echo $schedule['max_patients']; ?></td>
                                        <td>
                                            <?php if (isset($schedule['is_approved'])): ?>
                                                <span class="badge badge-<?php echo $schedule['is_approved'] ? 'success' : 'warning'; ?>">
                                                    <?php echo $schedule['is_approved'] ? 'Approved' : 'Pending'; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($schedule['notes'] ?? ''); ?></td>
                                        <td>
                                            <button type="button" class="btn-manage-schedule" 
                                                    data-toggle="modal" 
                                                    data-target="#scheduleModal<?php echo $schedule['id']; ?>"
                                                    title="Manage Doctor Schedule">
                                                <i class="fas fa-cog mr-2"></i>
                                                <span class="btn-text">Manage</span>
                                                <div class="btn-status-indicator status-<?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'approved' : 'pending'; ?>"></div>
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modern Schedule Management Modal -->
                                    <div class="modal fade" id="scheduleModal<?php echo $schedule['id']; ?>">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content schedule-manage-card">
                                                <div class="modal-header schedule-manage-header">
                                                    <div class="d-flex align-items-center justify-content-between w-100">
                                                        <div class="d-flex align-items-center">
                                                            <div class="schedule-icon-container">
                                                                <i class="fas fa-user-md"></i>
                                                            </div>
                                                            <div class="ml-3">
                                                                <h4 class="schedule-manage-title mb-0">Doctor Schedule Management</h4>
                                                                <p class="schedule-manage-subtitle mb-0">Review and approve doctor availability</p>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn-close-schedule" data-dismiss="modal">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="modal-body schedule-manage-body">
                                                    <!-- Doctor Info Section -->
                                                    <div class="schedule-info-section">
                                                        <div class="doctor-info-card">
                                                            <div class="d-flex align-items-center mb-3">
                                                                <div class="doctor-avatar">
                                                                    <i class="fas fa-user-md fa-2x text-primary"></i>
                                                                </div>
                                                                <div class="ml-3">
                                                                    <h5 class="doctor-name mb-1">Dr. <?php echo htmlspecialchars($schedule['doctor_name']); ?></h5>
                                                                    <p class="doctor-specialty mb-0 text-muted">Medical Professional</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Schedule Details Grid -->
                                                    <div class="schedule-details-grid">
                                                        <div class="detail-card">
                                                            <div class="detail-icon">
                                                                <i class="fas fa-calendar-day text-info"></i>
                                                            </div>
                                                            <div class="detail-content">
                                                                <label class="detail-label">Schedule Date</label>
                                                                <p class="detail-value"><?php echo date('F d, Y', strtotime($schedule['schedule_date'])); ?></p>
                                                                <small class="detail-extra"><?php echo date('l', strtotime($schedule['schedule_date'])); ?></small>
                                                            </div>
                                                        </div>

                                                        <div class="detail-card">
                                                            <div class="detail-icon">
                                                                <i class="fas fa-clock text-warning"></i>
                                                            </div>
                                                            <div class="detail-content">
                                                                <label class="detail-label">Time Range</label>
                                                                <p class="detail-value"><?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])); ?></p>
                                                                <small class="detail-extra"><?php echo $schedule['time_slot_minutes']; ?> min slots</small>
                                                            </div>
                                                        </div>

                                                        <div class="detail-card">
                                                            <div class="detail-icon">
                                                                <i class="fas fa-users text-success"></i>
                                                            </div>
                                                            <div class="detail-content">
                                                                <label class="detail-label">Capacity</label>
                                                                <p class="detail-value"><?php echo $schedule['max_patients']; ?> patients</p>
                                                                <small class="detail-extra">per time slot</small>
                                                            </div>
                                                        </div>

                                                        <div class="detail-card">
                                                            <div class="detail-icon">
                                                                <i class="fas fa-<?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'check-circle text-success' : 'clock text-warning'; ?>"></i>
                                                            </div>
                                                            <div class="detail-content">
                                                                <label class="detail-label">Current Status</label>
                                                                <p class="detail-value">
                                                                    <span class="status-badge status-<?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'approved' : 'pending'; ?>">
                                                                        <?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'Approved' : 'Pending'; ?>
                                                                    </span>
                                                                </p>
                                                                <small class="detail-extra"><?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'Ready for booking' : 'Awaiting approval'; ?></small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Management Form -->
                                                    <form method="post" class="schedule-manage-form">
                                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                                        
                                                        <!-- Approval Section -->
                                                        <div class="approval-section">
                                                            <div class="section-header">
                                                                <h6 class="section-title">
                                                                    <i class="fas fa-clipboard-check mr-2"></i>Approval Management
                                                                </h6>
                                                                <p class="section-description">Review and approve this doctor's schedule for patient booking</p>
                                                            </div>
                                                            
                                                            <div class="approval-toggle-container">
                                                                <div class="custom-control custom-switch custom-switch-lg">
                                                                    <input type="checkbox" class="custom-control-input" 
                                                                           id="approveSwitch<?php echo $schedule['id']; ?>" 
                                                                           name="is_approved" 
                                                                           <?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'checked' : ''; ?>>
                                                                    <label class="custom-control-label approve-switch-label" 
                                                                           for="approveSwitch<?php echo $schedule['id']; ?>">
                                                                        <span class="switch-text">
                                                                            <?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'Schedule Approved' : 'Schedule Not Approved'; ?>
                                                                        </span>
                                                                        <small class="switch-subtext">
                                                                            <?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'Patients can book appointments' : 'Schedule is hidden from patients'; ?>
                                                                        </small>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Notes Section -->
                                                        <div class="notes-section">
                                                            <div class="section-header">
                                                                <h6 class="section-title">
                                                                    <i class="fas fa-sticky-note mr-2"></i>Administrative Notes
                                                                </h6>
                                                                <p class="section-description">Add any notes or comments about this schedule approval</p>
                                                            </div>
                                                            
                                                            <div class="notes-input-container">
                                                                <textarea name="approval_notes" class="notes-textarea" rows="4" 
                                                                          placeholder="Enter your notes about this schedule approval..."><?php echo htmlspecialchars($schedule['approval_notes'] ?? ''); ?></textarea>
                                                            </div>
                                                        </div>

                                                        <!-- Doctor's Original Notes -->
                                                        <?php if (!empty($schedule['notes'])): ?>
                                                        <div class="doctor-notes-section">
                                                            <div class="section-header">
                                                                <h6 class="section-title">
                                                                    <i class="fas fa-comment-medical mr-2"></i>Doctor's Notes
                                                                </h6>
                                                                <p class="section-description">Notes provided by the doctor</p>
                                                            </div>
                                                            
                                                            <div class="doctor-notes-display">
                                                                <p><?php echo htmlspecialchars($schedule['notes']); ?></p>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>

                                                        <!-- Action Buttons -->
                                                        <div class="action-buttons">
                                                            <button type="button" class="btn-cancel-schedule" data-dismiss="modal">
                                                                <i class="fas fa-times mr-2"></i>Cancel
                                                            </button>
                                                            <button type="submit" name="approve_schedule" class="btn-save-schedule">
                                                                <i class="fas fa-save mr-2"></i>Save Changes
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="export-container mt-4" id="doctorExportContainer">
                            <a href="#" class="export-action-btn export-copy-btn" id="btnDoctorCopy">
                                <i class="fas fa-copy"></i>
                                <span>Copy</span>
                            </a>
                            <a href="#" class="export-action-btn export-csv-btn" id="btnDoctorCSV">
                                <i class="fas fa-file-csv"></i>
                                <span>CSV</span>
                            </a>
                            <a href="#" class="export-action-btn export-excel-btn" id="btnDoctorExcel">
                                <i class="fas fa-file-excel"></i>
                                <span>Excel</span>
                            </a>
                            <a href="#" class="export-action-btn export-pdf-btn" id="btnDoctorPDF">
                                <i class="fas fa-file-pdf"></i>
                                <span>PDF</span>
                            </a>
                            <a href="#" class="export-action-btn export-print-btn" id="btnDoctorPrint">
                                <i class="fas fa-print"></i>
                                <span>Print</span>
                            </a>
                        </div>
                    </div>
                </div>
                            <!-- End Doctor Schedules Tab -->


                
                            <!-- Staff Schedules Tab -->
                            <div class="tab-pane fade" id="staff-schedules" role="tabpanel">
                                <div class="schedule-tab-content">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="schedule-section-title">
                                            <i class="fas fa-users mr-2"></i>Health Worker Schedules Management
                                        </h4>
                        </div>
                        <?php if (empty($staffSchedules)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                No health worker schedules are currently available. Health workers can set their availability in the "My Availability" section.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="staffSchedules" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Staff</th>
                                            <th>Role</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Time Slot</th>
                                            <th>Max Patients</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staffSchedules as $staffSchedule): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($staffSchedule['staff_name']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $staffSchedule['staff_role'] == 'admin' ? 'admin' : 'health-worker'; ?> badge-role">
                                                        <?php echo ucfirst($staffSchedule['staff_role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y (D)', strtotime($staffSchedule['schedule_date'])); ?></td>
                                                <td>
                                                    <?php echo date('h:i A', strtotime($staffSchedule['start_time'])) . ' - ' . 
                                                            date('h:i A', strtotime($staffSchedule['end_time'])); ?>
                                                </td>
                                                <td><?php echo $staffSchedule['time_slot_minutes']; ?> minutes</td>
                                                <td><?php echo $staffSchedule['max_patients']; ?></td>
                                                <td><?php echo htmlspecialchars($staffSchedule['notes'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="export-container mt-4" id="staffExportContainer">
                                <a href="#" class="export-action-btn export-copy-btn" id="btnStaffCopy">
                                    <i class="fas fa-copy"></i>
                                    <span>Copy</span>
                                </a>
                                <a href="#" class="export-action-btn export-csv-btn" id="btnStaffCSV">
                                    <i class="fas fa-file-csv"></i>
                                    <span>CSV</span>
                                </a>
                                <a href="#" class="export-action-btn export-excel-btn" id="btnStaffExcel">
                                    <i class="fas fa-file-excel"></i>
                                    <span>Excel</span>
                                </a>
                                <a href="#" class="export-action-btn export-pdf-btn" id="btnStaffPDF">
                                    <i class="fas fa-file-pdf"></i>
                                    <span>PDF</span>
                                </a>
                                <a href="#" class="export-action-btn export-print-btn" id="btnStaffPrint">
                                    <i class="fas fa-print"></i>
                                    <span>Print</span>
                                </a>
                            </div>
                            
                            <div class="mt-3 alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                Health worker schedules are automatically approved and available for patient booking.
                            </div>
                        <?php endif; ?>
                                </div>
                            </div>
                            <!-- End Staff Schedules Tab -->
                        </div>
                        <!-- End Tab Content -->
                    </div>
                </div>
            </section>
        </div>

        <?php include './config/admin_footer.php'; ?>
    </div>

    <?php include './config/site_css_js_links.php'; ?>
    
    <!-- DataTables JavaScript -->
    <script src="plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    <script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
    <script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
    <script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
    <script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
    <script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
    
    <script src="plugins/fullcalendar/main.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable for Doctor Schedules
            var doctorTable = $("#doctorSchedules").DataTable({
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
                    searchPlaceholder: "Search doctor schedules...",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });

            // Initialize DataTable for Staff Schedules
            var staffTable = $("#staffSchedules").DataTable({
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
                    searchPlaceholder: "Search Health worker schedules...",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });

            // Hide default buttons for both tables
            $('.dt-buttons').hide();

            // Handle tab switching for DataTables
            $('#scheduleManagementTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var target = $(e.target).attr("href");
                if (target === '#doctor-schedules') {
                    setTimeout(function() {
                        doctorTable.columns.adjust().responsive.recalc();
                    }, 200);
                } else if (target === '#staff-schedules') {
                    setTimeout(function() {
                        staffTable.columns.adjust().responsive.recalc();
                    }, 200);
                }
            });

            // Add tab change animation
            $('#scheduleManagementTabs .nav-link').on('show.bs.tab', function(e) {
                $(this).addClass('fade-in');
            });

            // Initialize tooltips for tab badges
            $('[data-toggle="tooltip"]').tooltip();

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
                    $(info.el).tooltip({
                        title: info.event.title + (info.event.extendedProps.is_past ? ' (Past)' : ''),
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                },
                eventClick: function(info) {
                    var event = info.event;
                    var props = event.extendedProps;
                    
                    // Create a modal for event details
                    var modalId = 'eventModal' + Date.now();
                    var modalTitle = '';
                    var modalContent = '';
                    var headerClass = '';
                    
                    if (props.type === 'doctor_schedule') {
                        modalTitle = '<i class="fas fa-calendar-alt mr-2"></i> Doctor Schedule & Appointments';
                        headerClass = props.is_past ? 'bg-secondary text-white' : (props.is_approved ? 'bg-success text-white' : 'bg-warning text-white');
                        
                        var formattedDate = event.start.toLocaleDateString('en-US', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                        
                        modalContent = `
                        <div class="schedule-details">
                            <div class="text-center mb-3">
                                <h5 class="text-primary">${formattedDate}</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Doctor:</strong> ${props.doctor_name}</p>
                                    <p><strong>Time:</strong> ${event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Time Slot:</strong> ${props.time_slot} minutes</p>
                                    <p><strong>Max Patients:</strong> ${props.max_patients}</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>Status:</strong> 
                                        <span class="badge ${props.is_approved ? 'badge-success' : 'badge-warning'}">
                                            ${props.is_approved ? 'Approved' : 'Pending'}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            ${props.notes ? `<div class="mt-3"><strong>Notes:</strong><br><p class="text-muted">${props.notes}</p></div>` : ''}
                            ${props.approval_notes ? `<div class="mt-3"><strong>Admin Notes:</strong><br><p class="text-muted">${props.approval_notes}</p></div>` : ''}
                            
                            <hr class="my-4">
                            <div class="appointments-section">
                                <h6 class="text-primary mb-3"><i class="fas fa-user-clock mr-2"></i>Appointments for this Schedule</h6>
                                <div id="appointmentsList" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Loading appointments...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Loading appointments...</p>
                                </div>
                            </div>
                        </div>`;
                    } else if (props.type === 'staff_schedule') {
                        var roleTitle = props.staff_role === 'admin' ? 'Admin' : 'Health Worker';
                        modalTitle = '<i class="fas fa-users mr-2"></i> ' + roleTitle + ' Schedule & Appointments';
                        headerClass = props.is_past ? 'bg-secondary text-white' : (props.staff_role === 'admin' ? 'bg-purple text-white' : 'bg-info text-white');
                        
                        var formattedDate = event.start.toLocaleDateString('en-US', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                        
                        modalContent = `
                        <div class="schedule-details">
                            <div class="text-center mb-3">
                                <h5 class="text-primary">${formattedDate}</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Staff:</strong> ${props.staff_name}</p>
                                    <p><strong>Role:</strong> <span class="badge ${props.staff_role === 'admin' ? 'badge-purple' : 'badge-info'}">${props.staff_role.replace('_', ' ').toUpperCase()}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Time:</strong> ${event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                                    <p><strong>Time Slot:</strong> ${props.time_slot} minutes</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>Max Patients:</strong> ${props.max_patients}</p>
                                </div>
                            </div>
                            ${props.notes ? `<div class="mt-3"><strong>Notes:</strong><br><p class="text-muted">${props.notes}</p></div>` : ''}
                            
                            <hr class="my-4">
                            <div class="appointments-section">
                                <h6 class="text-primary mb-3"><i class="fas fa-user-clock mr-2"></i>Appointments for this Schedule</h6>
                                <div id="appointmentsList" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Loading appointments...</span>
                            </div>
                                    <p class="mt-2 text-muted">Loading appointments...</p>
                                </div>
                                </div>
                        </div>`;
                    }
                    
                    // Create and show modal
                    var modalHTML = `
                    <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header ${headerClass}">
                                    <h5 class="modal-title">${modalTitle}</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
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
                    
                    // Fetch appointments for this schedule
                    if (props.type === 'doctor_schedule' || props.type === 'staff_schedule') {
                        fetchAppointmentsForSchedule(props, modalId);
                    }
                    
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

            // Function to fetch appointments for a specific schedule
            function fetchAppointmentsForSchedule(props, modalId) {
                var scheduleType = props.type === 'doctor_schedule' ? 'doctor' : 'staff';
                var scheduleId = props.schedule_id;
                var scheduleDate = props.schedule_date;
                
                // Make AJAX call to fetch appointments
                $.ajax({
                    url: 'ajax/get_schedule_appointments.php',
                    type: 'POST',
                    data: {
                        schedule_type: scheduleType,
                        schedule_id: scheduleId,
                        schedule_date: scheduleDate,
                        provider_id: props.doctor_id || props.staff_id,
                        provider_name: props.doctor_name || props.staff_name
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            displayAppointments(response.appointments, modalId);
                        } else {
                            $('#' + modalId + ' #appointmentsList').html(
                                '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Error loading appointments: ' + response.error + '</div>'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#' + modalId + ' #appointmentsList').html(
                            '<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>Failed to load appointments. Please try again.</div>'
                        );
                    }
                });
            }

            // Function to display appointments in the modal
            function displayAppointments(appointments, modalId) {
                var appointmentsList = $('#' + modalId + ' #appointmentsList');
                
                if (appointments.length === 0) {
                    appointmentsList.html(
                        '<div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>No appointments found for this schedule.</div>'
                    );
                    return;
                }
                
                var appointmentsHTML = '<div class="appointments-list">';
                
                appointments.forEach(function(appointment) {
                    var isWalkin = appointment.is_walkin == 1;
                    var isPast = new Date(appointment.appointment_date + ' ' + appointment.appointment_time) < new Date();
                    
                    var statusBadge = '';
                    if (isPast) {
                        statusBadge = '<span class="badge badge-secondary">Past</span>';
                    } else {
                        switch(appointment.status) {
                            case 'approved':
                                statusBadge = '<span class="badge badge-' + (isWalkin ? 'success' : 'primary') + '">Approved</span>';
                                break;
                            case 'completed':
                                statusBadge = '<span class="badge badge-success">Completed</span>';
                                break;
                            case 'pending':
                                statusBadge = '<span class="badge badge-' + (isWalkin ? 'warning' : 'info') + '">Pending</span>';
                                break;
                            default:
                                statusBadge = '<span class="badge badge-secondary">' + appointment.status + '</span>';
                        }
                    }
                    
                    var appointmentTypeIcon = isWalkin ? 
                        '<i class="fas fa-walking text-warning mr-2"></i>' : 
                        '<i class="fas fa-calendar-check text-primary mr-2"></i>';
                    
                    var cardDataAttrs = '';
                    if (isPast) {
                        cardDataAttrs = 'data-status="past"';
                    } else if (appointment.status === 'completed') {
                        cardDataAttrs = 'data-status="completed"';
                    } else {
                        cardDataAttrs = 'data-type="' + (isWalkin ? 'walk-in' : 'regular') + '"';
                    }
                    
                    appointmentsHTML += `
                        <div class="card mb-3 appointment-card" ${cardDataAttrs}>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6 class="card-title mb-2">
                                            ${appointmentTypeIcon}
                                            ${appointment.patient_name}
                                            ${isWalkin ? '<span class="badge badge-warning badge-sm ml-2">Walk-in</span>' : ''}
                                        </h6>
                                        <p class="card-text text-muted mb-1">
                                            <i class="fas fa-clock mr-1"></i> ${appointment.appointment_time}
                                        </p>
                                        ${appointment.reason ? `<p class="card-text text-muted mb-1"><i class="fas fa-notes-medical mr-1"></i> ${appointment.reason}</p>` : ''}
                                    </div>
                                    <div class="col-md-4 text-right">
                                        ${statusBadge}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                appointmentsHTML += '</div>';
                appointmentsList.html(appointmentsHTML);
            }

            // Doctor Export Button Handlers
            $('#btnDoctorCopy').click(function(e) {
                e.preventDefault();
                doctorTable.button('.buttons-copy').trigger();
                
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
                    title: 'Doctor schedules copied to clipboard!'
                });
            });

            $('#btnDoctorCSV').click(function(e) {
                e.preventDefault();
                doctorTable.button('.buttons-csv').trigger();
            });

            $('#btnDoctorExcel').click(function(e) {
                e.preventDefault();
                doctorTable.button('.buttons-excel').trigger();
            });

            $('#btnDoctorPDF').click(function(e) {
                e.preventDefault();
                doctorTable.button('.buttons-pdf').trigger();
            });

            $('#btnDoctorPrint').click(function(e) {
                e.preventDefault();
                doctorTable.button('.buttons-print').trigger();
            });

            // Staff Export Button Handlers
            $('#btnStaffCopy').click(function(e) {
                e.preventDefault();
                staffTable.button('.buttons-copy').trigger();
                
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
                    title: 'Health worker schedules copied to clipboard!'
                });
            });

            $('#btnStaffCSV').click(function(e) {
                e.preventDefault();
                staffTable.button('.buttons-csv').trigger();
            });

            $('#btnStaffExcel').click(function(e) {
                e.preventDefault();
                staffTable.button('.buttons-excel').trigger();
            });

            $('#btnStaffPDF').click(function(e) {
                e.preventDefault();
                staffTable.button('.buttons-pdf').trigger();
            });

            $('#btnStaffPrint').click(function(e) {
                e.preventDefault();
                staffTable.button('.buttons-print').trigger();
            });

            // Initialize Toast
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            // Show message if exists
            var message = '<?php echo $message;?>';
            if(message !== '') {
                Toast.fire({
                    icon: 'success',
                    title: message
                });
            }

            // Show error if exists
            var error = '<?php echo $error;?>';
            if(error !== '') {
                Toast.fire({
                    icon: 'error',
                    title: error
                });
            }

            // Auto hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
            
            // Enhanced toggle switch functionality
            $('.custom-switch .custom-control-input').change(function() {
                const $label = $(this).next('label');
                const $switchText = $label.find('.switch-text');
                const $switchSubtext = $label.find('.switch-subtext');
                
                if($(this).is(':checked')) {
                    if ($switchText.length > 0) {
                        $switchText.text('Schedule Approved');
                        $switchSubtext.text('Patients can book appointments');
                    } else {
                        $label.text('Approved');
                    }
                    
                    // Add visual feedback
                    $label.addClass('approval-granted');
                    setTimeout(() => $label.removeClass('approval-granted'), 2000);
                } else {
                    if ($switchText.length > 0) {
                        $switchText.text('Schedule Not Approved');
                        $switchSubtext.text('Schedule is hidden from patients');
                    } else {
                        $label.text('Not Approved');
                    }
                    
                    // Add visual feedback
                    $label.addClass('approval-revoked');
                    setTimeout(() => $label.removeClass('approval-revoked'), 2000);
                }
            });
            
            // Add hover effects to manage buttons
            $('.btn-manage-schedule').hover(
                function() {
                    $(this).find('i').addClass('fa-spin');
                },
                function() {
                    $(this).find('i').removeClass('fa-spin');
                }
            );
            
            // Add loading state to form submission
            $('.schedule-manage-form').on('submit', function() {
                const $submitBtn = $(this).find('.btn-save-schedule');
                const originalText = $submitBtn.html();
                
                $submitBtn.prop('disabled', true)
                          .html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');
                
                // Restore button after a delay (in case of same-page reload)
                setTimeout(() => {
                    $submitBtn.prop('disabled', false).html(originalText);
                                 }, 5000);
             });

                         // Simple approve all pending functionality
             $(document).on('click', '[onclick="approveAllPending()"]', function(e) {
                 e.preventDefault();
                 window.approveAllPending();
                 return false;
             });
         });

         // Approve all pending schedules - Make this function globally accessible
         window.approveAllPending = function() {
             const pendingCount = $('.schedule-row[data-schedule-status="pending"]').length;
             
             if (pendingCount === 0) {
                 Swal.fire({
                     icon: 'info',
                     title: 'No Pending Schedules',
                     text: 'All doctor schedules are already approved.',
                     confirmButtonColor: '#3498db'
                 });
                 return;
             }
             
             Swal.fire({
                 title: 'Approve All Pending Schedules?',
                 html: `
                     <div class="text-center">
                         <div class="mb-3">
                             <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                         </div>
                         <p>Are you sure you want to approve all <strong>${pendingCount}</strong> pending doctor schedule${pendingCount > 1 ? 's' : ''}?</p>
                         <div class="alert alert-info mt-3">
                             <i class="fas fa-info-circle mr-2"></i>
                             This action will make all pending schedules available for patient booking.
                         </div>
                     </div>
                 `,
                 icon: 'question',
                 showCancelButton: true,
                 confirmButtonColor: '#27ae60',
                 cancelButtonColor: '#95a5a6',
                 confirmButtonText: '<i class="fas fa-check-circle mr-2"></i>Yes, Approve All',
                 cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancel'
             }).then((result) => {
                 if (result.isConfirmed) {
                     // Show loading
                     Swal.fire({
                         title: 'Processing...',
                         html: `
                             <div class="text-center">
                                 <div class="spinner-border text-primary mb-3" role="status">
                                     <span class="sr-only">Loading...</span>
                                 </div>
                                 <p>Approving schedules, please wait...</p>
                             </div>
                         `,
                         allowOutsideClick: false,
                         allowEscapeKey: false,
                         showConfirmButton: false
                     });
                     
                     // Prepare form data
                     const formData = new FormData();
                     formData.append('bulk_approve', '1');
                     formData.append('type', 'all');
                     
                     // Submit form
                     fetch('actions/admin_approve_all_doctor_schedules.php', {
                         method: 'POST',
                         body: formData
                     })
                     .then(response => response.json())
                     .then(data => {
                         if (data.success) {
                             Swal.fire({
                                 icon: 'success',
                                 title: 'Schedules Approved!',
                                 text: data.message,
                                 confirmButtonColor: '#27ae60'
                             }).then(() => {
                                 // Reload page to reflect changes
                                 location.reload();
                             });
                         } else {
                             Swal.fire({
                                 icon: 'error',
                                 title: 'Approval Failed',
                                 text: data.message || 'An error occurred while approving schedules.',
                                 confirmButtonColor: '#e74c3c'
                             });
                         }
                     })
                     .catch(error => {
                         console.error('Error:', error);
                         Swal.fire({
                             icon: 'error',
                             title: 'Error',
                             text: 'An unexpected error occurred. Please try again.',
                             confirmButtonColor: '#e74c3c'
                         });
                     });
                 }
             });
         };

        // Highlight current menu
        showMenuSelected("#mnu_appointments", "#mi_appointment_plotter");
    </script>
</body>
</html> 