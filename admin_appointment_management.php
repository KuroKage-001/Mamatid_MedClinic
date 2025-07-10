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
// Handle appointment notes updates (for both regular and walk-in appointments)
if (isset($_POST['update_notes'])) {
    $appointmentId = $_POST['appointment_id'];
    $notes = $_POST['notes'];

    try {
        // First, try to update notes in admin_clients_appointments table
        $query1 = "UPDATE admin_clients_appointments SET notes = ?, updated_at = NOW() WHERE id = ?";
        $stmt1 = $con->prepare($query1);
        $stmt1->execute([$notes, $appointmentId]);
        $rowsAffected1 = $stmt1->rowCount();
        
        // If no rows affected, try admin_walkin_appointments table
        if ($rowsAffected1 == 0) {
            $query2 = "UPDATE admin_walkin_appointments SET notes = ?, updated_at = NOW() WHERE id = ?";
            $stmt2 = $con->prepare($query2);
            $stmt2->execute([$notes, $appointmentId]);
            $rowsAffected2 = $stmt2->rowCount();
            
            if ($rowsAffected2 > 0) {
                $message = "Walk-in appointment notes updated successfully!";
            } else {
                $error = "Appointment not found.";
            }
        } else {
            $message = "Regular appointment notes updated successfully!";
        }
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
                         u.display_name as archived_by_name,
                         'regular' as appointment_type
                  FROM admin_clients_appointments a
                  LEFT JOIN admin_user_accounts u ON a.archived_by = u.id
                  WHERE a.is_archived = 1 
                  UNION ALL
                  SELECT w.id, w.patient_name, w.phone_number, w.address, w.date_of_birth, w.gender,
                         w.appointment_date, w.appointment_time, w.reason, w.status, w.notes,
                         w.schedule_id, w.provider_id as doctor_id, w.created_at, w.updated_at,
                         0 as email_sent, 0 as reminder_sent, w.is_archived, 
                         NULL as view_token, NULL as token_expiry, w.archived_at, w.archived_by, w.archive_reason,
                         1 as is_walkin,
                         DATE_FORMAT(w.appointment_date, '%M %d, %Y') as formatted_date,
                         DATE_FORMAT(w.appointment_time, '%h:%i %p') as formatted_time,
                         DATE_FORMAT(w.archived_at, '%d %b %Y %h:%i %p') as archived_at_formatted,
                         u2.display_name as archived_by_name,
                         'walk-in' as appointment_type
                  FROM admin_walkin_appointments w
                  LEFT JOIN admin_user_accounts u2 ON w.archived_by = u2.id
                  WHERE w.is_archived = 1
                  ORDER BY archived_at DESC";
    } else {
        $query = "SELECT *, 
                         DATE_FORMAT(appointment_date, '%M %d, %Y') as formatted_date,
                         DATE_FORMAT(appointment_time, '%h:%i %p') as formatted_time,
                         'regular' as appointment_type
                  FROM admin_clients_appointments 
                  WHERE is_archived = 0 
                  UNION ALL
                  SELECT w.id, w.patient_name, w.phone_number, w.address, w.date_of_birth, w.gender,
                         w.appointment_date, w.appointment_time, w.reason, w.status, w.notes,
                         w.schedule_id, w.provider_id as doctor_id, w.created_at, w.updated_at,
                         0 as email_sent, 0 as reminder_sent, w.is_archived, 
                         NULL as view_token, NULL as token_expiry, NULL as archived_at, NULL as archived_by, NULL as archive_reason,
                         1 as is_walkin,
                         DATE_FORMAT(w.appointment_date, '%M %d, %Y') as formatted_date,
                         DATE_FORMAT(w.appointment_time, '%h:%i %p') as formatted_time,
                         'walk-in' as appointment_type
                  FROM admin_walkin_appointments w
                  WHERE w.is_archived = 0
                  ORDER BY appointment_date DESC, appointment_time DESC";
    }
    $stmt = $con->prepare($query);
    $stmt->execute();
} catch (PDOException $ex) {
    $error = "Error fetching appointments: " . $ex->getMessage();
}

// Count total archived and active appointments for the filter (including walk-in appointments)
$countQuery = "SELECT 
                SUM(CASE WHEN is_archived = 0 THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN is_archived = 1 THEN 1 ELSE 0 END) as archived_count
               FROM (
                   SELECT is_archived FROM admin_clients_appointments
                   UNION ALL
                   SELECT is_archived FROM admin_walkin_appointments
               ) AS all_appointments";
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
    <!-- Essential JavaScript Libraries (must be loaded before DataTables and custom scripts) -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="plugins/sweetalert2/sweetalert2.all.min.js"></script>
    
    <!-- Debug script to verify jQuery is loaded -->
    <script>
        console.log('Essential libraries loading status:');
        console.log('jQuery available:', typeof jQuery !== 'undefined');
        console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
        console.log('SweetAlert2 available:', typeof Swal !== 'undefined');
    </script>
    
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

        /* Walk-in Badge Styling */
        .badge-warning {
            background: linear-gradient(135deg, #FFA800 0%, #F09000 100%);
            color: white !important;
            font-weight: 500;
            border-radius: 12px;
            padding: 0.25rem 0.5rem;
        }

        /* Walk-in Row Styling */
        tr[data-appointment-type="walk-in"] {
            background-color: rgba(255, 168, 0, 0.03) !important;
            border-left: 3px solid #FFA800;
        }

        tr[data-appointment-type="walk-in"]:hover {
            background-color: rgba(255, 168, 0, 0.08) !important;
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

        /* Walk-in Time Slot Selection Styling */
        .time-slot-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-height: 400px;
            overflow-y: auto;
        }

        .time-slot-container::-webkit-scrollbar {
            width: 6px;
        }

        .time-slot-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .time-slot-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .walkin-time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            padding: 10px 0;
        }

        .walkin-time-slot {
            padding: 12px 8px;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            color: white;
            backdrop-filter: blur(5px);
        }

        .walkin-time-slot:hover:not(.booked) {
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            background: rgba(52, 152, 219, 0.2);
        }

        .walkin-time-slot.selected {
            border-color: #3498db;
            background: rgba(52, 152, 219, 0.3);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
            transform: scale(1.05);
        }

        .walkin-time-slot.booked {
            background: rgba(231, 76, 60, 0.2);
            border-color: rgba(231, 76, 60, 0.5);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .walkin-time-slot.booked::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(
                45deg,
                rgba(231, 76, 60, 0.1),
                rgba(231, 76, 60, 0.1) 10px,
                rgba(231, 76, 60, 0.2) 10px,
                rgba(231, 76, 60, 0.2) 20px
            );
            z-index: 1;
        }

        .walkin-time-slot .time-label {
            font-weight: 600;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .walkin-time-slot .time-label i {
            margin-right: 5px;
            font-size: 0.8rem;
        }

        .walkin-time-slot.booked .time-label {
            color: rgba(255, 255, 255, 0.7);
        }

        .walkin-time-slot .badge {
            font-size: 0.65rem;
            padding: 3px 6px;
            border-radius: 15px;
            z-index: 2;
            margin-top: 5px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .walkin-time-slot.selected .badge {
            background: rgba(52, 152, 219, 0.8);
            color: white;
        }

        .walkin-time-slot.booked .badge {
            background: rgba(231, 76, 60, 0.8);
            color: white;
        }

        .walkin-time-slot .slot-status {
            font-size: 0.65rem;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 3px;
            text-align: center;
            line-height: 1.2;
            font-weight: 400;
            z-index: 2;
            position: relative;
        }

        .walkin-time-slot.booked .slot-status {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .walkin-time-period-divider {
            grid-column: 1 / -1;
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            color: #ecf0f1;
            margin: 8px 0;
            display: flex;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .walkin-time-period-divider i {
            margin-right: 8px;
            color: #3498db;
        }

        /* Walk-in Time Slot Filters */
        .time-slot-filters {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .time-slot-search {
            position: relative;
            flex: 1;
            max-width: 200px;
        }

        .time-slot-search input {
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            font-size: 0.85rem;
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(5px);
        }

        .time-slot-search input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .time-slot-search input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
        }

        .time-slot-search i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
        }

        .time-period-filter {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .time-period-btn {
            padding: 6px 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
            backdrop-filter: blur(5px);
        }

        .time-period-btn:hover {
            border-color: #3498db;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .time-period-btn.active {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-color: #3498db;
            color: white;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        /* Walk-in Time Slot Legend */
        .time-slot-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .legend-item {
            display: flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            min-width: 80px;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .legend-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .legend-item.active {
            background: rgba(52, 152, 219, 0.2);
            border-color: rgba(52, 152, 219, 0.5);
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
        }

        .legend-item.active::after {
            content: '✓';
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #3498db;
            color: white;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }

        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            margin-right: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .legend-color.available {
            background: rgba(255, 255, 255, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .legend-color.selected {
            background: rgba(52, 152, 219, 0.5);
            border: 2px solid #3498db;
        }

        .legend-color.booked {
            background: rgba(231, 76, 60, 0.3);
            border: 2px solid rgba(231, 76, 60, 0.6);
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
                rgba(231, 76, 60, 0.1),
                rgba(231, 76, 60, 0.1) 3px,
                rgba(231, 76, 60, 0.2) 3px,
                rgba(231, 76, 60, 0.2) 6px
            );
        }

        .legend-item span {
            font-weight: 500;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .legend-item .count {
            margin-left: 6px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.7rem;
            min-width: 20px;
            text-align: center;
            backdrop-filter: blur(5px);
        }

        .legend-item.active .count {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        /* Animations */
        @keyframes walkin-pulse-once {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }

        .count.pulse-once {
            animation: walkin-pulse-once 0.5s ease-in-out;
        }

        .walkin-time-slot.filtered {
            display: none;
        }

        .search-filtered {
            display: none !important;
        }

        /* Full width styling for time slot interface */
        .form-group.full-width {
            grid-column: 1 / -1;
            width: 100%;
        }

        /* Responsive adjustments for walk-in time slots */
        @media (max-width: 768px) {
            .walkin-time-slots-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 8px;
            }

            .walkin-time-slot {
                padding: 10px 6px;
                font-size: 0.85rem;
            }

            .time-slot-filters {
                flex-direction: column;
                align-items: stretch;
            }

            .time-slot-search {
                max-width: none;
                width: 100%;
            }

            .time-period-filter {
                justify-content: center;
                gap: 6px;
            }

            .time-period-btn {
                flex: 1;
                text-align: center;
            }

            .time-slot-legend {
                gap: 8px;
            }

            .legend-item {
                min-width: 70px;
                padding: 5px 8px;
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
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-md-end justify-content-start" style="padding-right: 20px;">
                                <?php if (!$showArchived): ?>
                                    <button type="button" class="btn btn-success" onclick="toggleWalkinForm()">
                                        <i class="fas fa-plus-circle mr-1"></i> Add Walk-in Appointment
                                    </button>
                                <?php endif; ?>
                            </div>
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
                        
                        <!-- Walk-in Appointment Form (Initially Hidden) -->
                        <?php if (!$showArchived): ?>
                <div id="walkinFormContainer" class="mb-4" style="display: none;">
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
                                                        <option value="admin">Administrator</option>
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
                                                
                                                                                        <div class="form-group full-width">
                                                    <label for="walkin_appointment_time" class="walkin-label">
                                                        <i class="fas fa-clock mr-2"></i>Available Time Slots *
                                                    </label>
                                                    
                                                    <!-- Time Slot Selection Interface -->
                                                    <div id="timeSlotInterface" style="display: none;">
                                                        <div class="time-slot-filters mb-3">
                                                            <div class="time-slot-search">
                                                                <i class="fas fa-search"></i>
                                                                <input type="text" id="walkinTimeSlotSearch" class="walkin-input" placeholder="Search time..." style="padding-left: 30px;">
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
                                                                <span class="count" id="walkin-available-count">0</span>
                                                            </div>
                                                            <div class="legend-item selected" data-filter="selected">
                                                                <div class="legend-color selected"></div>
                                                                <span>Selected</span>
                                                                <span class="count" id="walkin-selected-count">0</span>
                                                            </div>
                                                            <div class="legend-item booked active" data-filter="booked">
                                                                <div class="legend-color booked"></div>
                                                                <span>Booked</span>
                                                                <span class="count" id="walkin-booked-count">0</span>
                                                            </div>
                                                        </div>
                                                        
                                                        <div id="walkinTimeSlots" class="time-slot-container">
                                                            <p class="text-muted text-center">Please select provider, date and provider type to view available time slots.</p>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Fallback dropdown for when no slots are available -->
                                                    <select class="walkin-input" id="walkin_appointment_time" name="appointment_time" required style="display: none;">
                                                        <option value="">First select provider and date</option>
                                                    </select>
                                                    
                                                    <!-- Hidden input to store selected time -->
                                                    <input type="hidden" id="walkin_selected_time" name="selected_appointment_time">
                                                    <!-- Hidden input to store schedule ID -->
                                                    <input type="hidden" id="walkin_schedule_id" name="schedule_id">
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

                <!-- Appointments Table Card -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <?php echo $showArchived ? 'Archived Appointments' : 'Active Appointments'; ?>
                        </h3>
                        <div class="card-tools">
                            <div class="d-flex align-items-center">
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
                                    <tr class="<?php echo $row['is_archived'] ? 'archived-row' : ''; ?>" 
                                        data-appointment-type="<?php echo isset($row['appointment_type']) ? $row['appointment_type'] : 'regular'; ?>">
                                        <td><?php echo isset($row['formatted_date']) ? $row['formatted_date'] : date('M d, Y', strtotime($row['appointment_date'])); ?></td>
                                        <td><?php echo isset($row['formatted_time']) ? $row['formatted_time'] : date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['patient_name']); ?>
                                            <?php if (isset($row['appointment_type']) && $row['appointment_type'] == 'walk-in'): ?>
                                                <span class="badge badge-warning ml-1"><i class="fas fa-walking fa-xs"></i> Walk-in</span>
                                            <?php elseif (isset($row['is_walkin']) && $row['is_walkin']): ?>
                                                <span class="badge badge-warning ml-1"><i class="fas fa-walking fa-xs"></i> Walk-in</span>
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

    <!-- Additional JavaScript Libraries (excluding already loaded jQuery, Bootstrap, SweetAlert2) -->
    <script src="dist/js/adminlte.min.js"></script>
    <script src="dist/js/jquery_confirm/jquery-confirm.js"></script>
    <script src="dist/js/common_javascript_functions.js"></script>
    <script src="dist/js/sidebar.js"></script>
    
    <script>
        $('.dataTable').find('td').addClass("px-2 py-1 align-middle")
        $('.dataTable').find('th').addClass("p-1 align-middle")
    </script>
    
    
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
                let value = field.val();
                
                // Special handling for appointment time - check both dropdown and visual selection
                if (field.attr('id') === 'walkin_appointment_time') {
                    const selectedVisualTime = $('#walkin_selected_time').val();
                    const timeSlotInterfaceVisible = $('#timeSlotInterface').is(':visible');
                    
                    if (timeSlotInterfaceVisible) {
                        value = selectedVisualTime;
                    }
                }
                
                if (!value || value.trim() === '') {
                    isValid = false;
                    field.addClass('is-invalid');
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                    
                    // Special handling for time slot validation message
                    if (field.attr('id') === 'walkin_appointment_time' && $('#timeSlotInterface').is(':visible')) {
                        // Add visual indicator to time slots
                        $('.walkin-time-slot:not(.booked)').addClass('validation-highlight');
                        setTimeout(() => {
                            $('.walkin-time-slot').removeClass('validation-highlight');
                        }, 3000);
                    }
                } else {
                    field.removeClass('is-invalid');
                }
            });
            
            if (!isValid) {
                let errorMessage = 'Please fill in all required fields before proceeding.';
                
                // Check if the issue is specifically with time slot selection
                if ($('#timeSlotInterface').is(':visible') && !$('#walkin_selected_time').val()) {
                    errorMessage = 'Please select an available time slot to continue.';
                }
                
                // Show error message
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Fields Missing',
                    text: errorMessage,
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
                
                .walkin-time-slot.validation-highlight {
                    animation: validation-pulse 1s infinite;
                    border-color: #f39c12 !important;
                    box-shadow: 0 0 15px rgba(243, 156, 18, 0.6) !important;
                }
                
                @keyframes validation-pulse {
                    0% { transform: scale(1); box-shadow: 0 0 15px rgba(243, 156, 18, 0.6); }
                    50% { transform: scale(1.02); box-shadow: 0 0 20px rgba(243, 156, 18, 0.8); }
                    100% { transform: scale(1); box-shadow: 0 0 15px rgba(243, 156, 18, 0.6); }
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
        
        // Provider and date change handler - Updated for visual time slot selection
        $('#walkin_provider, #walkin_appointment_date').change(function() {
            const providerId = $('#walkin_provider').val();
            const providerType = $('#walkin_provider_type').val();
            const appointmentDate = $('#walkin_appointment_date').val();
            
            if (providerId && appointmentDate && providerType) {
                // Show time slot interface and hide dropdown
                $('#timeSlotInterface').show();
                $('#walkin_appointment_time').hide();
                
                // Generate visual time slots
                generateWalkinTimeSlots(providerId, providerType, appointmentDate);
            } else {
                // Hide time slot interface and show dropdown
                $('#timeSlotInterface').hide();
                $('#walkin_appointment_time').show().prop('disabled', true).html('<option value="">First select provider and date</option>');
                $('#walkin_selected_time').val(''); // Clear selected time
                $('#walkin_schedule_id').val(''); // Clear schedule ID
            }
        });
        
        // Function to generate visual time slots for walk-in appointments
        function generateWalkinTimeSlots(providerId, providerType, appointmentDate) {
            const timeSlotContainer = $('#walkinTimeSlots');
            
            // Show loading indicator
            timeSlotContainer.html(`
                <div class="d-flex justify-content-center align-items-center py-4">
                    <div class="spinner-border text-light mr-3" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="text-light mb-0">Loading available time slots...</p>
                </div>
            `);
            
            // First, get the schedule_id based on provider and date
                $.ajax({
                url: 'ajax/get_provider_schedule_id.php',
                    type: 'POST',
                    data: { 
                        provider_id: providerId,
                        provider_type: providerType,
                        appointment_date: appointmentDate
                    },
                    dataType: 'json',
                success: function(scheduleResponse) {
                    if (!scheduleResponse.success) {
                        timeSlotContainer.html(`
                            <div class="text-center py-4">
                                <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 2rem;"></i>
                                <p class="text-light mb-0">${scheduleResponse.error}</p>
                            </div>
                        `);
                        return;
                    }
                    
                    // Get available slots using the schedule_id we just retrieved
                    $.ajax({
                        url: 'ajax/client_check_booked_appointment_slots.php',
                        type: 'POST',
                        data: {
                            schedule_id: scheduleResponse.schedule_id,
                            schedule_type: providerType,
                            appointment_date: appointmentDate
                        },
                        dataType: 'json',
                        success: function(availabilityResponse) {
                            if (availabilityResponse.error) {
                                timeSlotContainer.html(`
                                    <div class="text-center py-4">
                                        <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 2rem;"></i>
                                        <p class="text-light mb-0">${availabilityResponse.error}</p>
                                    </div>
                                `);
                                return;
                            }
                            
                            // Store the schedule_id for form submission
                            $('#walkin_schedule_id').val(scheduleResponse.schedule_id);
                            
                            // Generate time slots using the schedule data
                            generateWalkinTimeSlotsVisual(
                                scheduleResponse,
                                availabilityResponse.booked_slots || {},
                                availabilityResponse.slot_statuses || {}
                            );
                    },
                    error: function() {
                            timeSlotContainer.html(`
                                <div class="text-center py-4">
                                    <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 2rem;"></i>
                                    <p class="text-light mb-0">Error loading time slot availability. Please try again.</p>
                                </div>
                            `);
                        }
                    });
                },
                error: function() {
                    timeSlotContainer.html(`
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 2rem;"></i>
                            <p class="text-light mb-0">Error loading provider schedule. Please try again.</p>
                        </div>
                    `);
                }
            });
        }
        
        // Function to generate visual time slots
        function generateWalkinTimeSlotsVisual(scheduleData, bookedSlots, slotStatuses) {
            const timeSlotContainer = $('#walkinTimeSlots');
            timeSlotContainer.empty();
            
            // Create slots container
            const slotsContainer = $('<div class="walkin-time-slots-grid"></div>');
            
            // Parse schedule times
            const startTime = new Date(`${scheduleData.schedule_date}T${scheduleData.start_time}`);
            const endTime = new Date(`${scheduleData.schedule_date}T${scheduleData.end_time}`);
            const slotMinutes = parseInt(scheduleData.time_slot_minutes);
            const maxPatients = parseInt(scheduleData.max_patients);
            
            let currentTime = new Date(startTime);
            let hasAvailableSlots = false;
            
            // Group slots by time period
            let morningSlots = [];
            let afternoonSlots = [];
            let eveningSlots = [];
            
            while (currentTime < endTime) {
                const timeString = currentTime.toTimeString().substring(0, 8); // HH:MM:SS format
                const formattedTime = currentTime.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit'
                });
                
                // Check if this slot is in the past
                const slotDateTime = new Date(currentTime);
                const isPastTime = slotDateTime < new Date();
                
                if (isPastTime) {
                    currentTime.setMinutes(currentTime.getMinutes() + slotMinutes);
                    continue;
                }
                
                // Determine time period
                const hour = currentTime.getHours();
                let timePeriod = 'morning';
                if (hour >= 12 && hour < 17) {
                    timePeriod = 'afternoon';
                } else if (hour >= 17) {
                    timePeriod = 'evening';
                }
                
                // Check availability
                let slotCount = 0;
                let isBooked = false;
                
                if (slotStatuses && slotStatuses[timeString]) {
                    isBooked = slotStatuses[timeString].is_booked === 1;
                }
                
                let walkinCount = 0;
                let regularCount = 0;
                if (bookedSlots && bookedSlots[timeString]) {
                    slotCount = parseInt(bookedSlots[timeString].count);
                    walkinCount = parseInt(bookedSlots[timeString].walkin_count || 0);
                    regularCount = parseInt(bookedSlots[timeString].regular_count || 0);
                    isBooked = bookedSlots[timeString].is_full;
                }
                
                const remainingSlots = maxPatients - slotCount;
                
                let slotElement;
                if (!isBooked && remainingSlots > 0) {
                    hasAvailableSlots = true;
                    slotElement = $(`
                        <div class="walkin-time-slot" data-time="${timeString}" data-period="${timePeriod}" tabindex="0" role="button">
                            <div class="time-label">
                                <i class="far fa-clock"></i>
                                ${formattedTime}
                            </div>
                            <span class="badge">${remainingSlots} available</span>
                        </div>
                    `);
            } else {
                    // Create detailed status message for admin interface
                    let statusMessage = 'Booked';
                    let badgeText = 'Booked';
                    let iconClass = 'fas fa-ban';
                    let badgeClass = 'badge';
                    
                    if (walkinCount > 0 && regularCount > 0) {
                        statusMessage = `${walkinCount} walk-in, ${regularCount} regular`;
                        badgeText = `${slotCount} booked`;
                        iconClass = 'fas fa-users';
                        badgeClass = 'badge badge-warning';
                    } else if (walkinCount > 0) {
                        statusMessage = walkinCount === 1 ? 'Walk-in appointment' : `${walkinCount} walk-in appointments`;
                        badgeText = 'Walk-in';
                        iconClass = 'fas fa-walking';
                        badgeClass = 'badge badge-info';
                    } else if (regularCount > 0) {
                        statusMessage = regularCount === 1 ? 'Regular appointment' : `${regularCount} regular appointments`;
                        badgeText = 'Regular';
                        iconClass = 'fas fa-calendar-check';
                        badgeClass = 'badge badge-success';
                    }
                    
                    slotElement = $(`
                        <div class="walkin-time-slot booked" data-period="${timePeriod}" tabindex="0" role="button" aria-disabled="true">
                            <div class="time-label">
                                <i class="${iconClass}"></i>
                                ${formattedTime}
                            </div>
                            <span class="${badgeClass}">${badgeText}</span>
                            <div class="slot-status">${statusMessage}</div>
                        </div>
                    `);
                }
                
                // Add to appropriate time period
                if (timePeriod === 'morning') {
                    morningSlots.push(slotElement);
                } else if (timePeriod === 'afternoon') {
                    afternoonSlots.push(slotElement);
                } else {
                    eveningSlots.push(slotElement);
                }
                
                currentTime.setMinutes(currentTime.getMinutes() + slotMinutes);
            }
            
            // Add time period dividers and slots
            if (morningSlots.length > 0) {
                slotsContainer.append('<div class="walkin-time-period-divider"><i class="fas fa-sun"></i> Morning</div>');
                morningSlots.forEach(slot => slotsContainer.append(slot));
            }
            
            if (afternoonSlots.length > 0) {
                slotsContainer.append('<div class="walkin-time-period-divider"><i class="fas fa-cloud-sun"></i> Afternoon</div>');
                afternoonSlots.forEach(slot => slotsContainer.append(slot));
            }
            
            if (eveningSlots.length > 0) {
                slotsContainer.append('<div class="walkin-time-period-divider"><i class="fas fa-moon"></i> Evening</div>');
                eveningSlots.forEach(slot => slotsContainer.append(slot));
            }
            
            timeSlotContainer.append(slotsContainer);
            
            if (!hasAvailableSlots) {
                timeSlotContainer.html(`
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times text-warning mb-2" style="font-size: 2rem;"></i>
                        <p class="text-light mb-0">No available time slots for this schedule.</p>
                    </div>
                `);
            } else {
                // Initialize time slot interactions
                initializeWalkinTimeSlotInteractions();
                updateWalkinLegendCounts();
            }
        }
        
        // Function to initialize time slot interactions
        function initializeWalkinTimeSlotInteractions() {
            // Handle time slot selection
            $(document).off('click', '.walkin-time-slot:not(.booked)').on('click', '.walkin-time-slot:not(.booked)', function() {
                selectWalkinTimeSlot($(this));
            });
            
            // Handle keyboard navigation
            $(document).off('keydown', '.walkin-time-slot:not(.booked)').on('keydown', '.walkin-time-slot:not(.booked)', function(e) {
                if (e.which === 13 || e.which === 32) { // Enter or Space
                    e.preventDefault();
                    selectWalkinTimeSlot($(this));
                }
            });
            
            // Handle legend interactions
            $('.time-slot-legend .legend-item').off('click').on('click', function() {
                $(this).toggleClass('active');
                applyWalkinTimeSlotFilters();
            });
            
            // Handle time period filter buttons
            $('.time-period-btn').off('click').on('click', function() {
                $('.time-period-btn').removeClass('active');
                $(this).addClass('active');
                applyWalkinTimeSlotFilters();
            });
            
            // Handle search functionality
            $('#walkinTimeSlotSearch').off('input').on('input', function() {
                const searchText = $(this).val().toLowerCase();
                $('.walkin-time-slot').each(function() {
                    const $slot = $(this);
                    const timeText = $slot.find('.time-label').text().toLowerCase();
                    
                    if (timeText.includes(searchText)) {
                        $slot.removeClass('search-filtered');
                    } else {
                        $slot.addClass('search-filtered');
                    }
                });
                
                // Show/hide time period dividers
                $('.walkin-time-period-divider').each(function() {
                    const $divider = $(this);
                    const hasVisibleSlots = $divider.nextUntil('.walkin-time-period-divider').not('.search-filtered').not('.filtered').length > 0;
                    $divider.toggle(hasVisibleSlots);
                });
            });
        }
        
        // Function to select a walk-in time slot
        function selectWalkinTimeSlot($slot) {
            $('.walkin-time-slot').removeClass('selected');
            $slot.addClass('selected');
            
            const selectedTime = $slot.data('time');
            $('#walkin_selected_time').val(selectedTime);
            
            // Update legend counts
            updateWalkinLegendCounts();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Time slot selected!',
                text: 'Selected time: ' + $slot.find('.time-label').text(),
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                background: '#2c3e50',
                color: '#ecf0f1'
            });
        }
        
        // Function to update legend counts
        function updateWalkinLegendCounts() {
            const availableCount = $('.walkin-time-slot:not(.booked):not(.selected)').length;
            const selectedCount = $('.walkin-time-slot.selected').length;
            const bookedCount = $('.walkin-time-slot.booked').length;
            
            $('#walkin-available-count').text(availableCount);
            $('#walkin-selected-count').text(selectedCount);
            $('#walkin-booked-count').text(bookedCount);
            
            // Add animation to count changes
            $('.time-slot-legend .count').addClass('pulse-once');
            setTimeout(() => {
                $('.time-slot-legend .count').removeClass('pulse-once');
            }, 500);
        }
        
        // Function to apply filters to time slots
        function applyWalkinTimeSlotFilters() {
            const showAvailable = $('.legend-item.available').hasClass('active');
            const showSelected = $('.legend-item.selected').hasClass('active');
            const showBooked = $('.legend-item.booked').hasClass('active');
            const activePeriod = $('.time-period-btn.active').data('period');
            
            $('.walkin-time-slot').each(function() {
                const $slot = $(this);
                let shouldShow = true;
                
                // Check status filters
                if ($slot.hasClass('booked') && !showBooked) {
                    shouldShow = false;
                } else if ($slot.hasClass('selected') && !showSelected) {
                    shouldShow = false;
                } else if (!$slot.hasClass('booked') && !$slot.hasClass('selected') && !showAvailable) {
                    shouldShow = false;
                }
                
                // Check time period filter
                if (activePeriod !== 'all' && $slot.data('period') !== activePeriod) {
                    shouldShow = false;
                }
                
                if (shouldShow) {
                    $slot.removeClass('filtered');
                } else {
                    $slot.addClass('filtered');
                }
            });
            
            // Show/hide time period dividers
            $('.walkin-time-period-divider').each(function() {
                const $divider = $(this);
                const hasVisibleSlots = $divider.nextUntil('.walkin-time-period-divider').not('.search-filtered').not('.filtered').length > 0;
                $divider.toggle(hasVisibleSlots);
            });
        }
        
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
                appointment_time: $('#walkin_selected_time').val() || $('#walkin_appointment_time').val(),
                schedule_id: $('#walkin_schedule_id').val(),
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