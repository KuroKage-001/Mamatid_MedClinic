<?php
/**
 * Manage Appointments
 * 
 * This file handles appointment management.
 * Doctor schedule approval functionality has been moved to appointment_plotter.php.
 */

include './config/db_connection.php';
require_once './common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

$message = '';
$error = '';

// Get message/error from URL if redirected
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Handle appointment notes updates
if (isset($_POST['update_notes'])) {
    $appointmentId = $_POST['appointment_id'];
    $notes = $_POST['notes'];

    try {
        $query = "UPDATE appointments SET notes = ? WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$notes, $appointmentId]);
        $message = "Appointment notes updated successfully!";
    } catch (PDOException $ex) {
        $error = "Error updating appointment notes: " . $ex->getMessage();
    }
}

// Handle archive filter
$showArchived = isset($_GET['archived']) ? (int)$_GET['archived'] : 0;

// Fetch all appointments with archive metadata
try {
    if ($showArchived) {
        $query = "SELECT a.*, 
                         DATE_FORMAT(a.appointment_date, '%M %d, %Y') as formatted_date,
                         DATE_FORMAT(a.appointment_time, '%h:%i %p') as formatted_time,
                         DATE_FORMAT(a.archived_at, '%d %b %Y %h:%i %p') as archived_at_formatted,
                         u.display_name as archived_by_name
                  FROM appointments a
                  LEFT JOIN users u ON a.archived_by = u.id
                  WHERE a.is_archived = 1 
                  ORDER BY a.archived_at DESC";
    } else {
        $query = "SELECT *, 
                         DATE_FORMAT(appointment_date, '%M %d, %Y') as formatted_date,
                         DATE_FORMAT(appointment_time, '%h:%i %p') as formatted_time
                  FROM appointments 
                  WHERE is_archived = 0 
                  ORDER BY appointment_date DESC, appointment_time DESC";
    }
    $stmt = $con->prepare($query);
    $stmt->execute();
} catch (PDOException $ex) {
    $error = "Error fetching appointments: " . $ex->getMessage();
}

// Count total archived and active appointments for the filter
$countQuery = "SELECT 
                SUM(CASE WHEN is_archived = 0 THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN is_archived = 1 THEN 1 ELSE 0 END) as archived_count
               FROM appointments";
$countStmt = $con->prepare($countQuery);
$countStmt->execute();
$countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
$activeCount = $countResult['active_count'] ?? 0;
$archivedCount = $countResult['archived_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <?php include './config/data_tables_css.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title>Manage Appointments - Mamatid Health Center System</title>
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

        .badge-danger {
            background-color: rgba(246, 78, 96, 0.1);
            color: var(--danger-color);
        }

        .badge-info {
            background-color: rgba(137, 80, 252, 0.1);
            color: var(--info-color);
        }

        .badge-secondary {
            background-color: rgba(128, 128, 128, 0.1);
            color: #808080;
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

        /* Filter buttons */
        .filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            margin-right: 0.5rem;
            transition: all var(--transition-speed);
        }

        .filter-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .filter-btn:not(.active) {
            background-color: white;
            color: var(--dark-color);
            border-color: #e4e6ef;
        }

        .filter-btn .badge {
            margin-left: 5px;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-weight: 600;
        }

        .filter-btn.active .badge {
            background-color: white;
            color: var(--primary-color);
        }

        .filter-btn:not(.active) .badge {
            background-color: #e4e6ef;
            color: var(--dark-color);
        }

        /* Archived appointment styling */
        tr.archived-row {
            opacity: 0.7;
        }

        tr.archived-row:hover {
            opacity: 1;
        }

        .archived-tag {
            display: inline-block;
            padding: 2px 5px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #6c757d;
            margin-left: 5px;
        }

        /* Archive filter tabs styling */
        .archive-filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .archive-filter-tabs .btn {
            border-radius: 20px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all var(--transition-speed);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #E8A317 100%);
            border: none;
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 168, 0, 0.4);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #159C96 100%);
            border: none;
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(27, 197, 189, 0.4);
            color: white;
        }

        .archived-row {
            background-color: rgba(255, 168, 0, 0.1) !important;
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

        /* Responsive Adjustments */
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
                            <h1>Manage Appointments</h1>
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

                <!-- Appointments Table Card -->
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <?php echo $showArchived ? 'Archived Appointments' : 'Active Appointments'; ?>
                        </h3>
                        <div class="d-flex gap-2">
                            <div class="archive-filter-tabs">
                                <a href="?archived=0" class="btn <?php echo !$showArchived ? 'btn-primary' : 'btn-secondary'; ?>">
                                    <i class="fas fa-calendar-check"></i> Active Records
                                    <span class="badge ml-1" style="background-color: white; color: <?php echo !$showArchived ? 'var(--primary-color)' : 'var(--dark-color)'; ?>;"><?php echo $activeCount; ?></span>
                                </a>
                                <a href="?archived=1" class="btn <?php echo $showArchived ? 'btn-warning' : 'btn-secondary'; ?>">
                                    <i class="fas fa-archive"></i> Archived Records
                                    <span class="badge ml-1" style="background-color: white; color: <?php echo $showArchived ? 'var(--warning-color)' : 'var(--dark-color)'; ?>;"><?php echo $archivedCount; ?></span>
                                </a>
                            </div>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <div class="table-responsive">
                            <table id="appointments" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Patient Name</th>
                                        <th>Phone</th>
                                        <th>Gender</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <?php if ($showArchived): ?>
                                            <th>Archived At</th>
                                            <th>Archived By</th>
                                            <th>Archive Reason</th>
                                        <?php endif; ?>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr class="<?php echo $row['is_archived'] ? 'archived-row' : ''; ?>">
                                        <td><?php echo isset($row['formatted_date']) ? $row['formatted_date'] : date('M d, Y', strtotime($row['appointment_date'])); ?></td>
                                        <td><?php echo isset($row['formatted_time']) ? $row['formatted_time'] : date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['patient_name']); ?>
                                            <?php if ($row['is_archived']): ?>
                                                <span class="archived-tag"><i class="fas fa-archive fa-xs"></i> Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $row['status'] == 'pending' ? 'warning' : 
                                                    ($row['status'] == 'approved' ? 'success' : 
                                                    ($row['status'] == 'completed' ? 'info' : 'danger')); 
                                            ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                                        <?php if ($showArchived): ?>
                                            <td><?php echo $row['archived_at_formatted'] ?? 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($row['archived_by_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($row['archive_reason'] ?? 'No reason provided'); ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <?php if ($showArchived): ?>
                                                <!-- Unarchive Button -->
                                                <button type="button" class="btn btn-success btn-sm" 
                                                        onclick="unarchiveRecord(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['patient_name']); ?>')">
                                                    <i class="fas fa-undo"></i> Unarchive
                                                </button>
                                            <?php else: ?>
                                                <!-- Edit Button -->
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-toggle="modal" 
                                                        data-target="#updateModal<?php echo $row['id']; ?>">
                                                    <i class="fas fa-edit"></i> Notes
                                                </button>
                                                
                                                <!-- Archive Button -->
                                                <button type="button" class="btn btn-warning btn-sm" 
                                                        onclick="archiveRecord(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['patient_name']); ?>')">
                                                    <i class="fas fa-archive"></i> Archive
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Update Notes Modal -->
                                    <div class="modal fade" id="updateModal<?php echo $row['id']; ?>">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">
                                                        <i class="fas fa-calendar-check mr-2"></i>
                                                        Update Appointment Notes
                                                    </h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <form method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                                        
                                                        <div class="form-group">
                                                            <label class="form-label">Current Status</label>
                                                            <input type="text" class="form-control" value="<?php echo ucfirst($row['status']); ?>" readonly>
                                                            <small class="text-muted">Appointments are automatically approved when booked by patients</small>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label class="form-label">Notes</label>
                                                            <textarea name="notes" class="form-control" rows="3" 
                                                                      placeholder="Enter notes about the appointment"><?php echo htmlspecialchars($row['notes'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                            <i class="fas fa-times mr-2"></i>Close
                                                        </button>
                                                        <button type="submit" name="update_notes" class="btn btn-primary">
                                                            <i class="fas fa-save mr-2"></i>Update Notes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="export-container mt-4" id="appointmentExportContainer">
                            <a href="#" class="export-action-btn export-copy-btn" id="btnAppointmentCopy">
                                <i class="fas fa-copy"></i>
                                <span>Copy</span>
                            </a>
                            <a href="#" class="export-action-btn export-csv-btn" id="btnAppointmentCSV">
                                <i class="fas fa-file-csv"></i>
                                <span>CSV</span>
                            </a>
                            <a href="#" class="export-action-btn export-excel-btn" id="btnAppointmentExcel">
                                <i class="fas fa-file-excel"></i>
                                <span>Excel</span>
                            </a>
                            <a href="#" class="export-action-btn export-pdf-btn" id="btnAppointmentPDF">
                                <i class="fas fa-file-pdf"></i>
                                <span>PDF</span>
                            </a>
                            <a href="#" class="export-action-btn export-print-btn" id="btnAppointmentPrint">
                                <i class="fas fa-print"></i>
                                <span>Print</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php include './config/admin_footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>
    <?php include './config/data_tables_js.php'; ?>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable for Appointments
            var appointmentTable = $("#appointments").DataTable({
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
                    searchPlaceholder: "Search appointments...",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });

            // Hide default buttons
            $('.dt-buttons').hide();

            // Export Button Handlers
            $('#btnAppointmentCopy').click(function(e) {
                e.preventDefault();
                appointmentTable.button('.buttons-copy').trigger();
                
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
                    title: 'Appointments copied to clipboard!'
                });
            });

            $('#btnAppointmentCSV').click(function(e) {
                e.preventDefault();
                appointmentTable.button('.buttons-csv').trigger();
            });

            $('#btnAppointmentExcel').click(function(e) {
                e.preventDefault();
                appointmentTable.button('.buttons-excel').trigger();
            });

            $('#btnAppointmentPDF').click(function(e) {
                e.preventDefault();
                appointmentTable.button('.buttons-pdf').trigger();
            });

            $('#btnAppointmentPrint').click(function(e) {
                e.preventDefault();
                appointmentTable.button('.buttons-print').trigger();
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
            
            // Toggle switch label text
            $('.custom-switch .custom-control-input').change(function() {
                if($(this).is(':checked')) {
                    $(this).next('label').text('Approved');
                } else {
                    $(this).next('label').text('Not Approved');
                }
            });
        });

        // Archive Appointment Function
        function archiveRecord(id, name) {
            Swal.fire({
                title: 'Archive Appointment',
                html: `
                    <p>Are you sure you want to archive the appointment for <strong>${name}</strong>?</p>
                    <div class="form-group mt-3">
                        <label for="archive_reason">Reason for archiving:</label>
                        <textarea class="form-control" id="archive_reason" rows="3" placeholder="Enter reason for archiving (optional)"></textarea>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FFA800',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-archive"></i> Archive',
                cancelButtonText: 'Cancel',
                focusConfirm: false,
                preConfirm: () => {
                    const reason = document.getElementById('archive_reason').value;
                    return { reason: reason };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'actions/archive_appointment.php';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'archive_id';
                    idInput.value = id;
                    
                    const reasonInput = document.createElement('input');
                    reasonInput.type = 'hidden';
                    reasonInput.name = 'archive_reason';
                    reasonInput.value = result.value.reason;
                    
                    form.appendChild(idInput);
                    form.appendChild(reasonInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Unarchive Appointment Function
        function unarchiveRecord(id, name) {
            Swal.fire({
                title: 'Unarchive Appointment',
                text: `Are you sure you want to unarchive the appointment for ${name}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1BC5BD',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-undo"></i> Unarchive',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'actions/unarchive_appointment.php';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'unarchive_id';
                    idInput.value = id;
                    
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Highlight current menu
        showMenuSelected("#mnu_appointments", "#mi_appointments");
    </script>
</body>
</html> 