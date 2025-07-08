<?php
/**
 * Manage Appointments
 * 
 * This file handles appointment management.
 * Doctor schedule approval functionality has been moved to appointment_plotter.php.
 */

include './config/db_connection.php';
require_once './system/utilities/admin_client_role_functions_services.php';

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
        $query = "UPDATE admin_clients_appointments SET notes = ? WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$notes, $appointmentId]);
        $message = "Appointment notes updated successfully!";
    } catch (PDOException $ex) {
        $error = "Error updating appointment notes: " . $ex->getMessage();
    }
}

// Handle archive filter
$showArchived = isset($_GET['archived']) ? (int)$_GET['archived'] : 0;

// Add is_walkin column if it doesn't exist
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

// Fetch all appointments with archive metadata
try {
    if ($showArchived) {
        $query = "SELECT a.*, 
                         DATE_FORMAT(a.appointment_date, '%M %d, %Y') as formatted_date,
                         DATE_FORMAT(a.appointment_time, '%h:%i %p') as formatted_time,
                         DATE_FORMAT(a.archived_at, '%d %b %Y %h:%i %p') as archived_at_formatted,
                         u.display_name as archived_by_name
                  FROM admin_clients_appointments a
                  LEFT JOIN admin_user_accounts u ON a.archived_by = u.id
                  WHERE a.is_archived = 1 
                  ORDER BY a.archived_at DESC";
    } else {
        $query = "SELECT *, 
                         DATE_FORMAT(appointment_date, '%M %d, %Y') as formatted_date,
                         DATE_FORMAT(appointment_time, '%h:%i %p') as formatted_time
                  FROM admin_clients_appointments 
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
               FROM admin_clients_appointments";
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
    <?php include './config/data_tables_css_js.php'; ?>
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

        /* Archive Button Styling */
        .btn-archive {
            background: linear-gradient(135deg, #FFA800 0%, #F09000 100%);
            color: white !important;
            border: none;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-archive:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(255, 168, 0, 0.3);
            color: white !important;
        }

        .btn-unarchive {
            background: linear-gradient(135deg, #1BC5BD 0%, #17B8B0 100%);
            color: white !important;
            border: none;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-unarchive:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(27, 197, 189, 0.3);
            color: white !important;
        }

            /* Archive Filter Buttons */
    .archive-filter-btn {
      background: linear-gradient(135deg, #E1F0FF 0%, #F8FBFF 100%);
      color: var(--primary-color) !important;
      border: 2px solid var(--primary-color);
      padding: 0.5rem 1rem;
      border-radius: 25px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
      margin-right: 0.5rem;
    }

    .archive-filter-btn:hover {
      background: var(--primary-color);
      color: white !important;
      transform: translateY(-1px);
      box-shadow: 0 4px 15px rgba(54, 153, 255, 0.3);
      text-decoration: none;
    }

    .archive-filter-btn.active {
      background: var(--primary-color);
      color: white !important;
    }

        /* Archived Row Styling */
        .archived-row {
            background-color: rgba(255, 168, 0, 0.05) !important;
            border-left: 4px solid #FFA800;
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
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            border: none;
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
            color: white;
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

        /* Archive Button Styling */
        .btn-archive {
            background: linear-gradient(135deg, #FFA800 0%, #F09000 100%);
            color: white !important;
            border: none;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-archive:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(255, 168, 0, 0.3);
            color: white !important;
        }

        .btn-unarchive {
            background: linear-gradient(135deg, #1BC5BD 0%, #17B8B0 100%);
            color: white !important;
            border: none;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-unarchive:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(27, 197, 189, 0.3);
            color: white !important;
        }

        /* Archive Filter Buttons */
        .archive-filter-btn {
            background: linear-gradient(135deg, #E1F0FF 0%, #F8FBFF 100%);
            color: var(--primary-color) !important;
            border: 2px solid var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
        }

        .archive-filter-btn:hover {
            background: var(--primary-color);
            color: white !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.3);
            text-decoration: none;
        }

        .archive-filter-btn.active {
            background: var(--primary-color);
            color: white !important;
        }

        /* Archived Row Styling */
        .archived-row {
            background-color: rgba(255, 168, 0, 0.05) !important;
            border-left: 4px solid #FFA800;
        }

        /* Walk-in Appointment Form Styling - Dark Theme */
        .walkin-form-card {
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

        .walkin-form-header {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem 2rem;
            color: white;
        }

        .walkin-icon-container {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 15px rgba(52, 152, 219, 0.3);
        }

        .walkin-icon-container i {
            font-size: 1.5rem;
            color: white;
        }

        .walkin-form-title {
            color: #ecf0f1;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .walkin-form-subtitle {
            color: #bdc3c7;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .btn-close-walkin {
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

        .btn-close-walkin:hover {
            background: rgba(231, 76, 60, 0.8);
            transform: scale(1.1);
        }

        .walkin-form-body {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 2rem;
        }

        /* Progress Steps */
        .walkin-progress-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            background: #7f8c8d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .step-item.active .step-icon {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            transform: scale(1.1);
            box-shadow: 0 8px 15px rgba(52, 152, 219, 0.4);
        }

        .step-item.completed .step-icon {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            box-shadow: 0 8px 15px rgba(39, 174, 96, 0.4);
        }

        .step-icon i {
            color: white;
            font-size: 1.1rem;
        }

        .step-label {
            color: #bdc3c7;
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
            margin-top: 0.5rem;
        }

        .step-item.active .step-label {
            color: #3498db;
            font-weight: 600;
        }

        .step-item.completed .step-label {
            color: #27ae60;
            font-weight: 600;
        }

        .step-divider {
            width: 80px;
            height: 2px;
            background: #7f8c8d;
            margin: 0 1rem;
            position: relative;
            top: -25px;
        }

        .step-item.completed ~ .step-divider {
            background: linear-gradient(to right, #27ae60, #2ecc71);
        }

        /* Form Steps */
        .walkin-step {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .walkin-step.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .step-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .step-title {
            color: #ecf0f1;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .step-description {
            color: #bdc3c7;
            font-size: 0.95rem;
            margin: 0;
            opacity: 0.9;
        }

        /* Form Grid */
        .walkin-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .walkin-form-grid.single-column {
            grid-template-columns: 1fr;
        }

        .walkin-form-grid .form-group.full-width {
            grid-column: 1 / -1;
        }

        .walkin-form-grid .form-group {
            margin-bottom: 0;
        }

        /* Form Controls */
        .walkin-label {
            display: block;
            color: #ecf0f1;
            font-weight: 500;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        .walkin-label i {
            color: #3498db;
            width: 20px;
        }

        .walkin-input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .walkin-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
        }

        .walkin-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .walkin-input option {
            background: #34495e;
            color: white;
        }

        .walkin-textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Navigation Buttons */
        .walkin-form-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .nav-buttons-right {
            display: flex;
            gap: 1rem;
        }

        .btn-walkin-prev,
        .btn-walkin-next,
        .btn-walkin-cancel,
        .btn-walkin-submit {
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
        }

        .btn-walkin-prev {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
        }

        .btn-walkin-prev:hover {
            background: linear-gradient(135deg, #7f8c8d 0%, #95a5a6 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-walkin-next {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .btn-walkin-next:hover {
            background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(52, 152, 219, 0.4);
        }

        .btn-walkin-cancel {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .btn-walkin-cancel:hover {
            background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(231, 76, 60, 0.4);
        }

        .btn-walkin-submit {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }

        .btn-walkin-submit:hover {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(39, 174, 96, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .walkin-form-header {
                padding: 1rem;
            }

            .walkin-form-body {
                padding: 1rem;
            }

            .walkin-form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .walkin-progress-steps {
                flex-direction: column;
                gap: 1rem;
            }

            .step-divider {
                width: 2px;
                height: 30px;
                top: 0;
            }

            .walkin-form-navigation {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-buttons-right {
                width: 100%;
                justify-content: center;
            }

            .btn-walkin-prev,
            .btn-walkin-next,
            .btn-walkin-cancel,
            .btn-walkin-submit {
                flex: 1;
                justify-content: center;
            }
        }

        /* Loading States */
        .walkin-input:disabled {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.5);
            cursor: not-allowed;
        }

        /* Custom Scrollbar for Dark Theme */
        .walkin-form-body::-webkit-scrollbar {
            width: 8px;
        }

        .walkin-form-body::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .walkin-form-body::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .walkin-form-body::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
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
                    <div class="card-header">
                        <h3 class="card-title">
                            <?php echo $showArchived ? 'Archived Appointments' : 'Active Appointments'; ?>
                        </h3>
                        <div class="card-tools">
                            <div class="d-flex align-items-center">
                                <?php if (!$showArchived): ?>
                                    <button type="button" class="btn btn-success btn-sm mr-2" onclick="toggleWalkinForm()">
                                        <i class="fas fa-plus-circle mr-1"></i> Add Walk-in Appointment
                                    </button>
                                <?php endif; ?>
                                <a href="?archived=0" 
                                   class="archive-filter-btn <?php echo !$showArchived ? 'active' : ''; ?>">
                                    <i class="fas fa-list"></i> Active Records
                                </a>
                                <a href="?archived=1" 
                                   class="archive-filter-btn <?php echo $showArchived ? 'active' : ''; ?>">
                                    <i class="fas fa-archive"></i> Archived Records
                                </a>
                                <button type="button" class="btn btn-tool ml-2" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
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
                                            <?php if (isset($row['is_walkin']) && $row['is_walkin']): ?>
                                                <span class="badge badge-info ml-1"><i class="fas fa-walking fa-xs"></i> Walk-in</span>
                                            <?php endif; ?>
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
                                                <button type="button" class="btn btn-unarchive btn-sm" 
                                                        onclick="unarchiveRecord(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['patient_name']); ?>')">
                                                    <i class="fas fa-undo"></i> Unarchive
                                                </button>
                                            <?php else: ?>
                                                <!-- Edit Button -->
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        data-toggle="modal" 
                                                        data-target="#updateModal<?php echo $row['id']; ?>">
                                                    <i class="fas fa-edit"></i> Notes
                                                </button>
                                                
                                                <!-- Archive Button -->
                                                <button type="button" class="btn btn-archive btn-sm ml-1" 
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
                        
                        <!-- Walk-in Appointment Form (Initially Hidden) -->
                        <?php if (!$showArchived): ?>
                        <div id="walkinFormContainer" class="mt-4" style="display: none;">
                            <div class="card walkin-form-card">
                                <div class="card-header walkin-form-header">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="walkin-icon-container">
                                                <i class="fas fa-walking"></i>
                                            </div>
                                            <div class="ml-3">
                                                <h4 class="walkin-form-title mb-0">Quick Walk-in Appointment</h4>
                                                <p class="walkin-form-subtitle mb-0">Book immediate appointment without registration</p>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-close-walkin" onclick="toggleWalkinForm()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body walkin-form-body">
                                    <!-- Progress Steps -->
                                    <div class="walkin-progress-steps mb-4">
                                        <div class="step-item active" data-step="1">
                                            <div class="step-icon">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <span class="step-label">Patient Info</span>
                                        </div>
                                        <div class="step-divider"></div>
                                        <div class="step-item" data-step="2">
                                            <div class="step-icon">
                                                <i class="fas fa-calendar-check"></i>
                                            </div>
                                            <span class="step-label">Appointment</span>
                                        </div>
                                        <div class="step-divider"></div>
                                        <div class="step-item" data-step="3">
                                            <div class="step-icon">
                                                <i class="fas fa-notes-medical"></i>
                                            </div>
                                            <span class="step-label">Details</span>
                                        </div>
                                    </div>
                                    
                                    <form id="walkinForm">
                                        <!-- Step 1: Patient Information -->
                                        <div class="walkin-step active" data-step="1">
                                            <div class="step-header">
                                                <h5 class="step-title">
                                                    <i class="fas fa-user mr-2"></i>Patient Information
                                                </h5>
                                                <p class="step-description">Enter basic patient details</p>
                                            </div>
                                            
                                            <div class="walkin-form-grid">
                                                <div class="form-group">
                                                    <label for="walkin_patient_name" class="walkin-label">
                                                        <i class="fas fa-user-circle mr-2"></i>Full Name *
                                                    </label>
                                                    <input type="text" class="walkin-input" id="walkin_patient_name" name="patient_name" 
                                                           placeholder="Enter patient's full name" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="walkin_phone_number" class="walkin-label">
                                                        <i class="fas fa-phone mr-2"></i>Phone Number *
                                                    </label>
                                                    <input type="tel" class="walkin-input" id="walkin_phone_number" name="phone_number" 
                                                           placeholder="e.g., 09123456789" required>
                                                </div>
                                                
                                                <div class="form-group full-width">
                                                    <label for="walkin_address" class="walkin-label">
                                                        <i class="fas fa-map-marker-alt mr-2"></i>Address *
                                                    </label>
                                                    <input type="text" class="walkin-input" id="walkin_address" name="address" 
                                                           placeholder="Complete address" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="walkin_date_of_birth" class="walkin-label">
                                                        <i class="fas fa-birthday-cake mr-2"></i>Date of Birth *
                                                    </label>
                                                    <input type="date" class="walkin-input" id="walkin_date_of_birth" name="date_of_birth" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="walkin_gender" class="walkin-label">
                                                        <i class="fas fa-venus-mars mr-2"></i>Gender *
                                                    </label>
                                                    <select class="walkin-input" id="walkin_gender" name="gender" required>
                                                        <option value="">Choose gender</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Step 2: Appointment Details -->
                                        <div class="walkin-step" data-step="2">
                                            <div class="step-header">
                                                <h5 class="step-title">
                                                    <i class="fas fa-calendar-check mr-2"></i>Appointment Scheduling
                                                </h5>
                                                <p class="step-description">Select date, provider, and time slot</p>
                                            </div>
                                            
                                            <div class="walkin-form-grid">
                                                <div class="form-group">
                                                    <label for="walkin_appointment_date" class="walkin-label">
                                                        <i class="fas fa-calendar-alt mr-2"></i>Appointment Date *
                                                    </label>
                                                    <input type="date" class="walkin-input" id="walkin_appointment_date" name="appointment_date" 
                                                           min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="walkin_provider_type" class="walkin-label">
                                                        <i class="fas fa-user-md mr-2"></i>Provider Type *
                                                    </label>
                                                    <select class="walkin-input" id="walkin_provider_type" name="provider_type" required>
                                                        <option value="">Choose provider type</option>
                                                        <option value="health_worker">Health Worker</option>
                                                        <option value="doctor">Doctor</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="walkin_provider" class="walkin-label">
                                                        <i class="fas fa-stethoscope mr-2"></i>Select Provider *
                                                    </label>
                                                    <select class="walkin-input" id="walkin_provider" name="provider_id" required disabled>
                                                        <option value="">First select provider type</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="walkin_appointment_time" class="walkin-label">
                                                        <i class="fas fa-clock mr-2"></i>Available Time Slots *
                                                    </label>
                                                    <select class="walkin-input" id="walkin_appointment_time" name="appointment_time" required disabled>
                                                        <option value="">First select provider and date</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Step 3: Visit Details -->
                                        <div class="walkin-step" data-step="3">
                                            <div class="step-header">
                                                <h5 class="step-title">
                                                    <i class="fas fa-notes-medical mr-2"></i>Visit Details
                                                </h5>
                                                <p class="step-description">Describe the reason for this appointment</p>
                                            </div>
                                            
                                            <div class="walkin-form-grid single-column">
                                                <div class="form-group">
                                                    <label for="walkin_reason" class="walkin-label">
                                                        <i class="fas fa-clipboard-list mr-2"></i>Reason for Visit *
                                                    </label>
                                                    <textarea class="walkin-input walkin-textarea" id="walkin_reason" name="reason" rows="4" 
                                                              placeholder="Describe the main reason for this appointment..." required></textarea>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="walkin_notes" class="walkin-label">
                                                        <i class="fas fa-sticky-note mr-2"></i>Additional Notes
                                                    </label>
                                                    <textarea class="walkin-input walkin-textarea" id="walkin_notes" name="notes" rows="3" 
                                                              placeholder="Any additional notes or special instructions..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Navigation Buttons -->
                                        <div class="walkin-form-navigation">
                                            <button type="button" class="btn-walkin-prev" id="walkin_prev_btn" onclick="previousStep()" style="display: none;">
                                                <i class="fas fa-chevron-left mr-2"></i>Previous
                                            </button>
                                            
                                            <div class="nav-buttons-right">
                                                <button type="button" class="btn-walkin-cancel" onclick="toggleWalkinForm()">
                                                    <i class="fas fa-times mr-2"></i>Cancel
                                                </button>
                                                
                                                <button type="button" class="btn-walkin-next" id="walkin_next_btn" onclick="nextStep()">
                                                    Next<i class="fas fa-chevron-right ml-2"></i>
                                                </button>
                                                
                                                <button type="submit" class="btn-walkin-submit" id="walkin_submit_btn" style="display: none;">
                                                    <i class="fas fa-plus-circle mr-2"></i>Book Appointment
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
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

    <?php include './config/site_css_js_links.php'; ?>
    
    
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
                        <label for="archiveReason" class="form-label">Archive Reason (Optional):</label>
                        <textarea id="archiveReason" class="form-control" rows="3" placeholder="Enter reason for archiving..."></textarea>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FFA800',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-archive"></i> Archive Record',
                cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                customClass: {
                    container: 'swal-archive-container'
                },
                preConfirm: () => {
                    const reason = document.getElementById('archiveReason').value.trim();
                    return { reason: reason };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Archiving...',
                        text: 'Please wait while we archive the record.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

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
                confirmButtonText: '<i class="fas fa-undo"></i> Unarchive Record',
                cancelButtonText: '<i class="fas fa-times"></i> Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Unarchiving...',
                        text: 'Please wait while we unarchive the record.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

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

        // Walk-in Appointment JavaScript
        let currentStep = 1;
        const totalSteps = 3;
        
        // Toggle walk-in form visibility
        function toggleWalkinForm() {
            const formContainer = $('#walkinFormContainer');
            if (formContainer.is(':visible')) {
                formContainer.slideUp(300);
                // Reset form when hiding
                resetWalkinForm();
            } else {
                formContainer.slideDown(300);
                // Initialize form
                initializeWalkinForm();
                // Focus on first input
                setTimeout(() => {
                    $('#walkin_patient_name').focus();
                }, 350);
            }
        }
        
        // Initialize walk-in form
        function initializeWalkinForm() {
            // Set default date to today
            $('#walkin_appointment_date').val('<?= date('Y-m-d') ?>');
            // Reset to first step
            currentStep = 1;
            showStep(1);
            // Clear any validation errors
            $('.walkin-input').removeClass('is-invalid');
            // Reset progress indicators
            $('.step-item').removeClass('active completed');
            $('.step-item[data-step="1"]').addClass('active');
        }
        
        // Reset form to initial state
        function resetWalkinForm() {
            $('#walkinForm')[0].reset();
            $('#walkin_provider').prop('disabled', true).html('<option value="">First select provider type</option>');
            $('#walkin_appointment_time').prop('disabled', true).html('<option value="">First select provider and date</option>');
            currentStep = 1;
            showStep(1);
            // Reset progress indicators
            $('.step-item').removeClass('active completed');
            $('.step-item[data-step="1"]').addClass('active');
        }
        
        // Show specific step
        function showStep(step) {
            // Hide all steps
            $('.walkin-step').removeClass('active');
            // Show current step
            $(`.walkin-step[data-step="${step}"]`).addClass('active');
            
            // Update progress indicators
            $('.step-item').removeClass('active completed');
            for (let i = 1; i <= totalSteps; i++) {
                if (i < step) {
                    $(`.step-item[data-step="${i}"]`).addClass('completed');
                } else if (i === step) {
                    $(`.step-item[data-step="${i}"]`).addClass('active');
                }
            }
            
            // Update navigation buttons
            updateNavigationButtons();
        }
        
        // Update navigation buttons visibility
        function updateNavigationButtons() {
            const prevBtn = $('#walkin_prev_btn');
            const nextBtn = $('#walkin_next_btn');
            const submitBtn = $('#walkin_submit_btn');
            
            if (currentStep === 1) {
                prevBtn.hide();
                nextBtn.show();
                submitBtn.hide();
            } else if (currentStep === totalSteps) {
                prevBtn.show();
                nextBtn.hide();
                submitBtn.show();
            } else {
                prevBtn.show();
                nextBtn.show();
                submitBtn.hide();
            }
        }
        
        // Go to next step
        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                }
            }
        }
        
        // Go to previous step
        function previousStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }
        
        // Validate current step
        function validateCurrentStep() {
            const currentStepElement = $(`.walkin-step[data-step="${currentStep}"]`);
            const requiredFields = currentStepElement.find('input[required], select[required], textarea[required]');
            let isValid = true;
            let firstInvalidField = null;
            
            requiredFields.each(function() {
                const field = $(this);
                const value = field.val().trim();
                
                if (!value) {
                    isValid = false;
                    field.addClass('is-invalid');
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                } else {
                    field.removeClass('is-invalid');
                }
            });
            
            if (!isValid) {
                // Show error message
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Fields Missing',
                    text: 'Please fill in all required fields before proceeding.',
                    confirmButtonColor: '#3498db',
                    background: '#2c3e50',
                    color: '#ecf0f1'
                });
                
                // Focus on first invalid field
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
            }
            
            return isValid;
        }
        
        // Add CSS class for invalid fields and event handlers
        $(document).ready(function() {
            $('<style>').text(`
                .walkin-input.is-invalid {
                    border-color: #e74c3c !important;
                    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.3) !important;
                }
                
                .walkin-input.is-invalid:focus {
                    border-color: #e74c3c !important;
                    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.3) !important;
                }
            `).appendTo('head');
            
            // Clear validation errors when user starts typing
            $(document).on('input change', '.walkin-input', function() {
                $(this).removeClass('is-invalid');
            });
        });
        
        // Provider type change handler
        $('#walkin_provider_type').change(function() {
            const providerType = $(this).val();
            const providerSelect = $('#walkin_provider');
            
            providerSelect.prop('disabled', true).html('<option value="">Loading providers...</option>');
            $('#walkin_appointment_time').prop('disabled', true).html('<option value="">First select provider and date</option>');
            
            if (providerType) {
                $.ajax({
                    url: 'ajax/get_providers.php',
                    type: 'POST',
                    data: { provider_type: providerType },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let options = '<option value="">Select Provider</option>';
                            response.providers.forEach(function(provider) {
                                options += `<option value="${provider.id}">${provider.display_name}</option>`;
                            });
                            providerSelect.html(options).prop('disabled', false);
                        } else {
                            providerSelect.html('<option value="">No providers available</option>');
                        }
                    },
                    error: function() {
                        providerSelect.html('<option value="">Error loading providers</option>');
                    }
                });
            } else {
                providerSelect.html('<option value="">First select provider type</option>');
            }
        });
        
        // Provider and date change handler
        $('#walkin_provider, #walkin_appointment_date').change(function() {
            const providerId = $('#walkin_provider').val();
            const providerType = $('#walkin_provider_type').val();
            const appointmentDate = $('#walkin_appointment_date').val();
            const timeSelect = $('#walkin_appointment_time');
            
            if (providerId && appointmentDate && providerType) {
                timeSelect.prop('disabled', true).html('<option value="">Loading available slots...</option>');
                
                $.ajax({
                    url: 'ajax/get_available_slots.php',
                    type: 'POST',
                    data: { 
                        provider_id: providerId,
                        provider_type: providerType,
                        appointment_date: appointmentDate
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.slots.length > 0) {
                            let options = '<option value="">Select Available Time</option>';
                            response.slots.forEach(function(slot) {
                                options += `<option value="${slot.time}">${slot.formatted_time}</option>`;
                            });
                            timeSelect.html(options).prop('disabled', false);
                        } else {
                            timeSelect.html('<option value="">No available slots for this date</option>');
                        }
                    },
                    error: function() {
                        timeSelect.html('<option value="">Error loading time slots</option>');
                    }
                });
            } else {
                timeSelect.prop('disabled', true).html('<option value="">First select provider and date</option>');
            }
        });
        
        // Walk-in appointment form submission
        $('#walkinForm').submit(function(e) {
            e.preventDefault();
            
            // Final validation before submission
            if (!validateCurrentStep()) {
                return;
            }
            
            const submitBtn = $('#walkin_submit_btn');
            const originalBtnText = submitBtn.html();
            
            // Show loading state
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Booking Appointment...');
            
            // Prepare form data
            const formData = {
                patient_name: $('#walkin_patient_name').val(),
                phone_number: $('#walkin_phone_number').val(),
                address: $('#walkin_address').val(),
                date_of_birth: $('#walkin_date_of_birth').val(),
                gender: $('#walkin_gender').val(),
                appointment_date: $('#walkin_appointment_date').val(),
                appointment_time: $('#walkin_appointment_time').val(),
                provider_id: $('#walkin_provider').val(),
                provider_type: $('#walkin_provider_type').val(),
                reason: $('#walkin_reason').val(),
                notes: $('#walkin_notes').val()
            };
            
            $.ajax({
                url: 'actions/book_walkin_appointment.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Walk-in Appointment Booked!',
                            text: response.message,
                            showConfirmButton: true,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#27ae60',
                            background: '#2c3e50',
                            color: '#ecf0f1'
                        }).then(() => {
                            // Hide the form and refresh the page
                            $('#walkinFormContainer').slideUp(300);
                            setTimeout(() => {
                                location.reload(); // Refresh to show new appointment
                            }, 400);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Booking Failed',
                            text: response.message,
                            confirmButtonColor: '#e74c3c',
                            background: '#2c3e50',
                            color: '#ecf0f1'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while booking the appointment. Please try again.',
                        confirmButtonColor: '#e74c3c',
                        background: '#2c3e50',
                        color: '#ecf0f1'
                    });
                },
                complete: function() {
                    // Reset button
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });

        // Highlight current menu
        showMenuSelected("#mnu_appointments", "#mi_appointments");
    </script>
</body>
</html> 