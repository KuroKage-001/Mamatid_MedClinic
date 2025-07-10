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

// Fetch all appointments for calendar display
$appointmentsQuery = "SELECT a.*, u.display_name as doctor_name, u.role as doctor_role,
                      CASE 
                          WHEN a.schedule_id IN (SELECT id FROM admin_doctor_schedules) THEN 'doctor'
                          WHEN a.schedule_id IN (SELECT id FROM admin_hw_schedules) THEN 'staff'
                          ELSE 'unknown'
                      END as schedule_type
                      FROM admin_clients_appointments a
                      LEFT JOIN admin_user_accounts u ON a.doctor_id = u.id
                      WHERE a.status != 'cancelled'
                      ORDER BY a.appointment_date ASC, a.appointment_time ASC";
$appointmentsStmt = $con->prepare($appointmentsQuery);
$appointmentsStmt->execute();
$allAppointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Format all schedules and appointments for calendar
$calendarEvents = [];

// Add doctor schedules to calendar
foreach ($doctorSchedules as $schedule) {
    $isPast = strtotime($schedule['schedule_date']) < strtotime(date('Y-m-d'));
    $status = $schedule['is_approved'] ? 'Approved' : 'Pending';
    $color = $isPast ? '#A0A0A0' : ($schedule['is_approved'] ? '#1BC5BD' : '#FFA800');
    
    $calendarEvents[] = [
        'id' => 'doctor_schedule_' . $schedule['id'],
        'title' => 'Dr. ' . $schedule['doctor_name'] . ' (' . $status . ')',
        'start' => $schedule['schedule_date'] . 'T' . $schedule['start_time'],
        'end' => $schedule['schedule_date'] . 'T' . $schedule['end_time'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'type' => 'doctor_schedule',
            'doctor_name' => $schedule['doctor_name'],
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
    $color = $isPast ? '#A0A0A0' : ($schedule['staff_role'] == 'admin' ? '#8950FC' : '#17A2B8');
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
            'staff_name' => $schedule['staff_name'],
            'staff_role' => $schedule['staff_role'],
            'max_patients' => $schedule['max_patients'],
            'time_slot' => $schedule['time_slot_minutes'],
            'notes' => $schedule['notes'],
            'is_past' => $isPast
        ]
    ];
}

// Add appointments to calendar
foreach ($allAppointments as $appointment) {
    $appointmentTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
    $isPast = $appointmentTime < time();
    
    // Set color based on status and whether it's past
    $color = '#F64E60'; // Default red for active appointments
    if ($isPast) {
        if ($appointment['status'] == 'completed') {
            $color = '#28a745'; // Green for completed
        } else {
            $color = '#6c757d'; // Gray for past but not completed
        }
    } else {
        switch($appointment['status']) {
            case 'approved':
                $color = '#007bff'; // Blue for approved
                break;
            case 'pending':
                $color = '#ffc107'; // Yellow for pending
                break;
            default:
                $color = '#F64E60'; // Red for others
        }
    }
    
    $calendarEvents[] = [
        'id' => 'appointment_' . $appointment['id'],
        'title' => 'Appointment: ' . $appointment['patient_name'],
        'start' => $appointment['appointment_date'] . 'T' . $appointment['appointment_time'],
        'end' => $appointment['appointment_date'] . 'T' . $appointment['appointment_time'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'type' => 'appointment',
            'patient_name' => $appointment['patient_name'],
            'doctor_name' => $appointment['doctor_name'],
            'doctor_role' => $appointment['doctor_role'],
            'reason' => $appointment['reason'],
            'status' => $appointment['status'],
            'schedule_type' => $appointment['schedule_type'],
            'is_past' => $isPast
        ]
    ];
}

// Get statistics for overview
$totalDoctorSchedules = count($doctorSchedules);
$totalStaffSchedules = count($staffSchedules);
$totalAppointments = count($allAppointments);
$pendingApprovals = count(array_filter($doctorSchedules, function($schedule) {
    return !$schedule['is_approved'];
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <?php include './config/data_tables_css_js.php'; ?>
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
            padding: 0.5rem 0.75rem;
            font-weight: 500;
            border-radius: 6px;
        }

        .badge-success {
            background-color: rgba(27, 197, 189, 0.1);
            color: var(--success-color);
        }

        .badge-warning {
            background-color: rgba(255, 168, 0, 0.1);
            color: var(--warning-color);
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
            background-color: rgba(137, 80, 252, 0.1);
            color: var(--info-color);
        }
        
        .badge-health-worker {
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
            border-radius: 6px !important;
            padding: 3px 5px !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            border: none !important;
            transition: transform 0.2s !important;
            cursor: pointer !important;
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

        /* Statistics Cards */
        .stats-card {
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
            overflow: hidden;
            position: relative;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card .icon {
            opacity: 0.8;
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-card p {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0;
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, var(--info-color), var(--primary-color));
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, var(--success-color), #0d7e66);
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, var(--warning-color), #cc8400);
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
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-approved {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }

        .status-pending {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
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
            transform: scale(1.01);
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

        /* Bulk Actions Styling */
        .bulk-actions-container {
            position: relative;
        }

        .btn-bulk-actions {
            background: linear-gradient(135deg, #8950FC 0%, #7C3AED 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.25rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(137, 80, 252, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-bulk-actions::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-bulk-actions:hover::before {
            left: 100%;
        }

        .btn-bulk-actions:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(137, 80, 252, 0.4);
            background: linear-gradient(135deg, #7C3AED 0%, #8950FC 100%);
            color: white;
            text-decoration: none;
        }

        .btn-bulk-actions:focus {
            color: white;
            text-decoration: none;
            box-shadow: 0 0 0 3px rgba(137, 80, 252, 0.5);
        }

        .btn-bulk-actions i {
            font-size: 1rem;
            z-index: 2;
            position: relative;
        }

        .btn-text {
            z-index: 2;
            position: relative;
        }

        .pending-count {
            background: rgba(255, 255, 255, 0.9);
            color: #8950FC;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 0.5rem;
            animation: pulse-count 2s infinite;
            z-index: 2;
            position: relative;
        }

        @keyframes pulse-count {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
            }
            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 6px rgba(255, 255, 255, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
        }

        /* Bulk Dropdown Menu Styling */
        .bulk-dropdown-menu {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            padding: 0.5rem 0;
            min-width: 280px;
            backdrop-filter: blur(10px);
            animation: dropdownSlide 0.3s ease;
        }

        @keyframes dropdownSlide {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bulk-dropdown-menu .dropdown-header {
            color: #ecf0f1;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.75rem 1rem 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 0.25rem;
        }

        .bulk-dropdown-menu .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.1);
            margin: 0.5rem 0;
        }

        .bulk-action-item {
            background: transparent;
            border: none;
            color: #ecf0f1;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .bulk-action-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ecf0f1;
            text-decoration: none;
            backdrop-filter: blur(5px);
        }

        .bulk-action-item:focus {
            color: #ecf0f1;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.15);
        }

        .bulk-action-item i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .action-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .action-title {
            font-weight: 600;
            font-size: 0.9rem;
            line-height: 1.2;
        }

        .action-description {
            font-size: 0.75rem;
            color: #bdc3c7;
            line-height: 1.2;
            margin-top: 0.1rem;
        }

        /* All Approved Indicator */
        .all-approved-indicator {
            display: flex;
            align-items: center;
        }

        .success-badge {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
            animation: successPulse 3s infinite;
        }

        @keyframes successPulse {
            0%, 100% {
                box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
            }
            50% {
                box-shadow: 0 4px 20px rgba(39, 174, 96, 0.5);
            }
        }

        /* Select Mode Styling */
        .select-mode-active {
            position: relative;
        }

        .select-mode-active::before {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            background: linear-gradient(45deg, #3498db, #e67e22, #e74c3c, #9b59b6);
            border-radius: 15px;
            z-index: -1;
            animation: selectModeGlow 2s linear infinite;
        }

        @keyframes selectModeGlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .schedule-row-checkbox {
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s ease;
        }

        .select-mode-active .schedule-row-checkbox {
            opacity: 1;
            transform: scale(1);
        }

        .schedule-row-selected {
            background: rgba(52, 152, 219, 0.1) !important;
            border-left: 4px solid #3498db;
        }

        /* Bulk Action Bar */
        .bulk-action-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: none;
            align-items: center;
            gap: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1050;
            animation: slideUpBar 0.3s ease;
        }

        @keyframes slideUpBar {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        .bulk-action-bar.active {
            display: flex;
        }

        .selected-count {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
            padding: 0.3rem 0.8rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .bulk-action-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .btn-approve-selected,
        .btn-cancel-selection {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-approve-selected {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }

        .btn-approve-selected:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
        }

        .btn-cancel-selection {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
        }

        .btn-cancel-selection:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.4);
        }

                 /* Gap utility for flex containers */
         .gap-2 {
             gap: 0.5rem;
         }

         /* Card header bulk actions positioning */
         .card-header .bulk-actions-container {
             margin-right: 0.5rem;
         }

         /* Responsive Design for Bulk Actions */
         @media (max-width: 768px) {
             .btn-bulk-actions {
                 padding: 0.6rem 1rem;
                 font-size: 0.85rem;
             }

             .btn-bulk-actions .btn-text {
                 display: none;
             }

             .bulk-dropdown-menu {
                 min-width: 250px;
                 margin-left: -50px;
             }

             .bulk-action-bar {
                 left: 10px;
                 right: 10px;
                 transform: none;
                 padding: 0.75rem 1rem;
             }

             .bulk-action-buttons {
                 flex-direction: column;
                 gap: 0.5rem;
                 width: 100%;
             }

             .btn-approve-selected,
             .btn-cancel-selection {
                 width: 100%;
                 justify-content: center;
             }

             .card-header .d-flex {
                 flex-direction: column;
                 align-items: flex-start !important;
             }

             .card-header .bulk-actions-container {
                 margin-top: 0.75rem;
                 margin-right: 0;
                 align-self: flex-end;
             }

             .success-badge {
                 font-size: 0.8rem;
                 padding: 0.5rem 1rem;
             }
         }

        /* Modern Export Actions CSS */
        .dt-button-collection {
            display: none !important;
        }

        .export-container {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
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

        /* SweetAlert Custom Styling */
        .swal-bulk-container .swal2-popup {
            border-radius: 20px !important;
            backdrop-filter: blur(10px) !important;
        }

        .swal2-popup .swal2-title {
            font-weight: 600 !important;
            margin-bottom: 1rem !important;
        }

        .swal2-popup .swal2-content {
            font-size: 0.95rem !important;
            line-height: 1.5 !important;
        }

        .swal2-popup .swal2-actions {
            gap: 0.75rem !important;
        }

        .swal2-popup .swal2-confirm,
        .swal2-popup .swal2-cancel {
            border-radius: 10px !important;
            font-weight: 500 !important;
            padding: 0.75rem 1.5rem !important;
            font-size: 0.9rem !important;
            transition: all 0.3s ease !important;
        }

        .swal2-popup .swal2-confirm:hover,
        .swal2-popup .swal2-cancel:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3) !important;
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

                <!-- Statistics Overview -->
                <div class="row mb-4">
                    <div class="col-lg-4 col-md-6">
                        <div class="card bg-gradient-info text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $totalDoctorSchedules; ?></h3>
                                        <p class="mb-1 font-weight-bold">Doctor Schedules</p>
                                        <?php if ($pendingApprovals > 0): ?>
                                            <small class="d-block opacity-75">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?php echo $pendingApprovals; ?> Pending Approval<?php echo $pendingApprovals > 1 ? 's' : ''; ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="d-block opacity-75">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                All Approved
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-user-md fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card bg-gradient-success text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $totalStaffSchedules; ?></h3>
                                        <p class="mb-1 font-weight-bold">Staff Schedules</p>
                                        <small class="d-block opacity-75">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Auto-Approved
                                        </small>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <div class="card bg-gradient-primary text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $totalAppointments; ?></h3>
                                        <p class="mb-1 font-weight-bold">Total Appointments</p>
                                        <small class="d-block opacity-75">
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            All Providers
                                        </small>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-calendar-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
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
                        <div class="mt-4">
                            <div class="legend-container">
                                <h5 class="legend-title">Calendar Legend</h5>
                                <div class="legend-items">
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #1BC5BD;"></span>
                                        <span class="legend-text">Approved Doctor Schedules</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #FFA800;"></span>
                                        <span class="legend-text">Pending Doctor Schedules</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #8950FC;"></span>
                                        <span class="legend-text">Admin Schedules</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #17A2B8;"></span>
                                        <span class="legend-text">Health Worker Schedules</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #007bff;"></span>
                                        <span class="legend-text">Approved Appointments</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #ffc107;"></span>
                                        <span class="legend-text">Pending Appointments</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #28a745;"></span>
                                        <span class="legend-text">Completed Appointments</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background-color: #A0A0A0;"></span>
                                        <span class="legend-text">Past Schedules</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doctor Schedules Card -->
                <div class="card card-outline card-info mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Doctor Schedules
                            </h3>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bulk-actions-container">
                                    <?php if ($pendingApprovals > 0): ?>
                                    <div class="dropdown">
                                        <button class="btn-bulk-actions dropdown-toggle" type="button" id="bulkActionsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-tasks mr-2"></i>
                                            <span class="btn-text">Bulk Actions</span>
                                            <span class="pending-count"><?php echo $pendingApprovals; ?></span>
                                        </button>
                                        <div class="dropdown-menu bulk-dropdown-menu dropdown-menu-right" aria-labelledby="bulkActionsDropdown">
                                            <div class="dropdown-header">
                                                <i class="fas fa-clipboard-list mr-2"></i>Schedule Management
                                            </div>
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item bulk-action-item" onclick="approveAllPending()">
                                                <i class="fas fa-check-circle text-success mr-2"></i>
                                                <div class="action-content">
                                                    <span class="action-title">Approve All Pending</span>
                                                    <small class="action-description">Approve all <?php echo $pendingApprovals; ?> pending schedule<?php echo $pendingApprovals > 1 ? 's' : ''; ?></small>
                                                </div>
                                            </button>
                                            <button class="dropdown-item bulk-action-item" onclick="toggleSelectMode()">
                                                <i class="fas fa-check-square text-info mr-2"></i>
                                                <div class="action-content">
                                                    <span class="action-title">Select Multiple</span>
                                                    <small class="action-description">Choose specific schedules to approve</small>
                                                </div>
                                            </button>
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item bulk-action-item" onclick="refreshSchedules()">
                                                <i class="fas fa-sync-alt text-primary mr-2"></i>
                                                <div class="action-content">
                                                    <span class="action-title">Refresh Data</span>
                                                    <small class="action-description">Reload schedule information</small>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="all-approved-indicator">
                                        <div class="success-badge">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            <span>All Schedules Approved</span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="doctorSchedules" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <div class="custom-control custom-checkbox schedule-row-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="selectAllSchedules">
                                                <label class="custom-control-label" for="selectAllSchedules"></label>
                                            </div>
                                        </th>
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
                                        <td>
                                            <div class="custom-control custom-checkbox schedule-row-checkbox">
                                                <input type="checkbox" class="custom-control-input schedule-checkbox" 
                                                       id="schedule_<?php echo $schedule['id']; ?>" 
                                                       value="<?php echo $schedule['id']; ?>"
                                                       data-status="<?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'approved' : 'pending'; ?>">
                                                <label class="custom-control-label" for="schedule_<?php echo $schedule['id']; ?>"></label>
                                            </div>
                                        </td>
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

                <!-- Bulk Action Bar (Initially Hidden) -->
                <div class="bulk-action-bar" id="bulkActionBar">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fas fa-check-square fa-lg text-info"></i>
                        <span class="selected-count" id="selectedCount">0 selected</span>
                        <div class="bulk-action-buttons">
                            <button class="btn-approve-selected" onclick="approveSelectedSchedules()">
                                <i class="fas fa-check-circle"></i>
                                <span>Approve Selected</span>
                            </button>
                            <button class="btn-cancel-selection" onclick="exitSelectMode()">
                                <i class="fas fa-times"></i>
                                <span>Cancel</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Staff Schedules Card -->
                <div class="card card-outline card-success mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-md mr-2"></i>
                            Staff Schedules
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($staffSchedules)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                No staff schedules are currently available. Staff members can set their availability in the "My Availability" section.
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
                                Staff schedules are automatically approved and available for patient booking.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <?php include './config/admin_footer.php'; ?>
    </div>

    <?php include './config/site_css_js_links.php'; ?>
    
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
                    searchPlaceholder: "Search staff schedules...",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });

            // Hide default buttons for both tables
            $('.dt-buttons').hide();

            // Initialize Calendar
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
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
                        modalTitle = '<i class="fas fa-calendar-alt mr-2"></i> Doctor Schedule Information';
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
                        </div>`;
                    } else if (props.type === 'staff_schedule') {
                        modalTitle = '<i class="fas fa-users mr-2"></i> Staff Schedule Information';
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
                        </div>`;
                    } else if (props.type === 'appointment') {
                        modalTitle = '<i class="fas fa-user-clock mr-2"></i> Appointment Information';
                        headerClass = props.is_past ? 'bg-secondary text-white' : 'bg-primary text-white';
                        
                        var formattedDate = event.start.toLocaleDateString('en-US', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                        
                        modalContent = `
                        <div class="appointment-details">
                            <div class="text-center mb-3">
                                <h5 class="text-primary">${formattedDate}</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Patient:</strong> ${props.patient_name}</p>
                                    <p><strong>Time:</strong> ${event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Provider:</strong> ${props.doctor_name || 'Not specified'}</p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge badge-${props.status === 'approved' ? 'primary' : props.status === 'completed' ? 'success' : 'warning'}">
                                            ${props.status.toUpperCase()}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            ${props.reason ? `<div class="mt-3"><strong>Reason:</strong><br><p class="text-muted">${props.reason}</p></div>` : ''}
                        </div>`;
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
                    
                    // Remove modal from DOM when hidden
                    $('#' + modalId).on('hidden.bs.modal', function() {
                        $(this).remove();
                    });
                }
            });
            calendar.render();

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
                    title: 'Staff schedules copied to clipboard!'
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

             // Bulk Actions JavaScript
             
             // Global variables for bulk operations
             let isSelectModeActive = false;
             let selectedSchedules = new Set();
             
             // Initialize bulk action functionality
             initializeBulkActions();
         });

         // Initialize bulk action functionality
         function initializeBulkActions() {
             // Handle individual checkbox changes
             $(document).on('change', '.schedule-checkbox', function() {
                 const scheduleId = $(this).val();
                 const isChecked = $(this).is(':checked');
                 const $row = $(this).closest('tr');
                 
                 if (isChecked) {
                     selectedSchedules.add(scheduleId);
                     $row.addClass('schedule-row-selected');
                 } else {
                     selectedSchedules.delete(scheduleId);
                     $row.removeClass('schedule-row-selected');
                 }
                 
                 updateBulkActionBar();
                 updateSelectAllCheckbox();
             });
             
             // Handle select all checkbox
             $('#selectAllSchedules').change(function() {
                 const isChecked = $(this).is(':checked');
                 $('.schedule-checkbox').each(function() {
                     const $checkbox = $(this);
                     const $row = $checkbox.closest('tr');
                     const scheduleId = $checkbox.val();
                     
                     $checkbox.prop('checked', isChecked);
                     
                     if (isChecked) {
                         selectedSchedules.add(scheduleId);
                         $row.addClass('schedule-row-selected');
                     } else {
                         selectedSchedules.delete(scheduleId);
                         $row.removeClass('schedule-row-selected');
                     }
                 });
                 
                 updateBulkActionBar();
             });
         }
         
         // Toggle select mode
         function toggleSelectMode() {
             isSelectModeActive = !isSelectModeActive;
             const $doctorSchedulesCard = $('#doctorSchedules').closest('.card');
             
             if (isSelectModeActive) {
                 $doctorSchedulesCard.addClass('select-mode-active');
                 
                 // Show success message
                 Swal.fire({
                     icon: 'info',
                     title: 'Select Mode Activated',
                     text: 'Click checkboxes to select schedules for bulk approval.',
                     toast: true,
                     position: 'top-end',
                     showConfirmButton: false,
                     timer: 3000,
                     background: '#2c3e50',
                     color: '#ecf0f1'
                 });
             } else {
                 exitSelectMode();
             }
         }
         
         // Exit select mode
         function exitSelectMode() {
             isSelectModeActive = false;
             selectedSchedules.clear();
             
             const $doctorSchedulesCard = $('#doctorSchedules').closest('.card');
             $doctorSchedulesCard.removeClass('select-mode-active');
             
             // Clear all selections
             $('.schedule-checkbox').prop('checked', false);
             $('#selectAllSchedules').prop('checked', false);
             $('.schedule-row').removeClass('schedule-row-selected');
             
             // Hide bulk action bar
             $('#bulkActionBar').removeClass('active');
         }
         
         // Update bulk action bar
         function updateBulkActionBar() {
             const count = selectedSchedules.size;
             const $bulkActionBar = $('#bulkActionBar');
             const $selectedCount = $('#selectedCount');
             
             if (count > 0) {
                 $selectedCount.text(`${count} selected`);
                 $bulkActionBar.addClass('active');
             } else {
                 $bulkActionBar.removeClass('active');
             }
         }
         
         // Update select all checkbox state
         function updateSelectAllCheckbox() {
             const totalCheckboxes = $('.schedule-checkbox').length;
             const checkedCheckboxes = $('.schedule-checkbox:checked').length;
             const $selectAll = $('#selectAllSchedules');
             
             if (checkedCheckboxes === 0) {
                 $selectAll.prop('indeterminate', false);
                 $selectAll.prop('checked', false);
             } else if (checkedCheckboxes === totalCheckboxes) {
                 $selectAll.prop('indeterminate', false);
                 $selectAll.prop('checked', true);
             } else {
                 $selectAll.prop('indeterminate', true);
                 $selectAll.prop('checked', false);
             }
         }
         
         // Approve all pending schedules
         function approveAllPending() {
             const pendingCount = $('.schedule-row[data-schedule-status="pending"]').length;
             
             if (pendingCount === 0) {
                 Swal.fire({
                     icon: 'info',
                     title: 'No Pending Schedules',
                     text: 'All doctor schedules are already approved.',
                     confirmButtonColor: '#3498db',
                     background: '#2c3e50',
                     color: '#ecf0f1'
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
                 cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancel',
                 background: '#2c3e50',
                 color: '#ecf0f1',
                 customClass: {
                     container: 'swal-bulk-container'
                 }
             }).then((result) => {
                 if (result.isConfirmed) {
                     performBulkApproval('all');
                 }
             });
         }
         
         // Approve selected schedules
         function approveSelectedSchedules() {
             if (selectedSchedules.size === 0) {
                 Swal.fire({
                     icon: 'warning',
                     title: 'No Schedules Selected',
                     text: 'Please select at least one schedule to approve.',
                     confirmButtonColor: '#f39c12',
                     background: '#2c3e50',
                     color: '#ecf0f1'
                 });
                 return;
             }
             
             const selectedArray = Array.from(selectedSchedules);
             const pendingSelected = selectedArray.filter(id => {
                 return $(`.schedule-checkbox[value="${id}"]`).data('status') === 'pending';
             });
             
             if (pendingSelected.length === 0) {
                 Swal.fire({
                     icon: 'info',
                     title: 'No Pending Schedules Selected',
                     text: 'All selected schedules are already approved.',
                     confirmButtonColor: '#3498db',
                     background: '#2c3e50',
                     color: '#ecf0f1'
                 });
                 return;
             }
             
             Swal.fire({
                 title: 'Approve Selected Schedules?',
                 html: `
                     <div class="text-center">
                         <div class="mb-3">
                             <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                         </div>
                         <p>Are you sure you want to approve <strong>${pendingSelected.length}</strong> selected schedule${pendingSelected.length > 1 ? 's' : ''}?</p>
                         <div class="alert alert-info mt-3">
                             <i class="fas fa-info-circle mr-2"></i>
                             This action will make the selected schedules available for patient booking.
                         </div>
                     </div>
                 `,
                 icon: 'question',
                 showCancelButton: true,
                 confirmButtonColor: '#27ae60',
                 cancelButtonColor: '#95a5a6',
                 confirmButtonText: '<i class="fas fa-check-circle mr-2"></i>Yes, Approve Selected',
                 cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancel',
                 background: '#2c3e50',
                 color: '#ecf0f1'
             }).then((result) => {
                 if (result.isConfirmed) {
                     performBulkApproval('selected', pendingSelected);
                 }
             });
         }
         
         // Perform bulk approval
         function performBulkApproval(type, scheduleIds = null) {
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
                 showConfirmButton: false,
                 background: '#2c3e50',
                 color: '#ecf0f1'
             });
             
             // Prepare form data
             const formData = new FormData();
             formData.append('bulk_approve', '1');
             formData.append('type', type);
             
             if (type === 'selected' && scheduleIds) {
                 scheduleIds.forEach(id => {
                     formData.append('schedule_ids[]', id);
                 });
             }
             
             // Submit form
             fetch('actions/bulk_approve_schedules.php', {
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
                         confirmButtonColor: '#27ae60',
                         background: '#2c3e50',
                         color: '#ecf0f1'
                     }).then(() => {
                         // Reload page to reflect changes
                         location.reload();
                     });
                 } else {
                     Swal.fire({
                         icon: 'error',
                         title: 'Approval Failed',
                         text: data.message || 'An error occurred while approving schedules.',
                         confirmButtonColor: '#e74c3c',
                         background: '#2c3e50',
                         color: '#ecf0f1'
                     });
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 Swal.fire({
                     icon: 'error',
                     title: 'Error',
                     text: 'An unexpected error occurred. Please try again.',
                     confirmButtonColor: '#e74c3c',
                     background: '#2c3e50',
                     color: '#ecf0f1'
                 });
             });
         }
         
         // Refresh schedules
         function refreshSchedules() {
             Swal.fire({
                 title: 'Refreshing...',
                 text: 'Updating schedule information...',
                 allowOutsideClick: false,
                 allowEscapeKey: false,
                 showConfirmButton: false,
                 didOpen: () => {
                     Swal.showLoading();
                 },
                 background: '#2c3e50',
                 color: '#ecf0f1'
             });
             
             // Reload the page
             setTimeout(() => {
                 location.reload();
             }, 1000);
         }

        // Highlight current menu
        showMenuSelected("#mnu_appointments", "#mi_appointment_plotter");
    </script>
</body>
</html> 