<?php
include './config/connection.php';
include './common_service/common_functions.php';
include './common_service/role_functions.php';

// Set the timezone to your local timezone
date_default_timezone_set('Asia/Manila');

$message = '';
// Ensure the session is started elsewhere so that $_SESSION['user_id'] is available
$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

// Check if user is admin
$isAdmin = isAdmin();

// Check today's log in time_logs table for the current user
$query = "SELECT * FROM `time_logs` WHERE `user_id` = :uid AND `log_date` = :today";
$stmt = $con->prepare($query);
$stmt->execute([':uid' => $userId, ':today' => $today]);
$logToday = $stmt->fetch();

// Determine if user can Time In or Time Out
// Can Time In if no log exists for today; can Time Out if a log exists with time_in set but no time_out
$canTimeIn = !$logToday;
$canTimeOut = $logToday && $logToday['time_in'] && !$logToday['time_out'];

// Handle form submissions for Time In or Time Out actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $currentTime = date('H:i:s'); // Current time in 24-hour format

    try {
        // Start transaction to ensure data consistency
        $con->beginTransaction();

        if ($action == 'time_in' && $canTimeIn) {
            // Insert a new record into time_in_logs table
            $query = "INSERT INTO `time_in_logs` (`user_id`, `log_date`, `time_in`) 
                      VALUES (:uid, :today, :time_in)";
            $stmt = $con->prepare($query);
            $stmt->execute([':uid' => $userId, ':today' => $today, ':time_in' => $currentTime]);
            $message = 'Time In recorded successfully!';
            $type = 'success';
        } elseif ($action == 'time_out' && $canTimeOut) {
            // Insert a record into time_out_logs table
            $query = "INSERT INTO `time_out_logs` (`user_id`, `log_date`, `time_out`) 
                     VALUES (:uid, :today, :time_out)";
            $stmt = $con->prepare($query);
            $stmt->execute([
                ':uid' => $userId,
                ':today' => $today,
                ':time_out' => $currentTime
            ]);
            $message = 'Time Out recorded successfully!';
            $type = 'success';
        } else {
            // If the conditions aren't met, provide appropriate feedback message
            $message = $action == 'time_in' ? 'You have already timed in today.' : 'No active Time In found or Time Out already recorded.';
            $type = 'warning';
        }

        // Commit transaction if all queries executed successfully
        $con->commit();
    } catch (PDOException $ex) {
        // Roll back transaction in case of any error and capture error message
        $con->rollback();
        $message = 'Error: ' . $ex->getMessage();
        $type = 'error';
    }

    // Redirect back to this page with the feedback message and type
    header("Location: time_tracker.php?message=" . urlencode($message) . "&type=" . urlencode($type));
    exit;
}

// Handle admin actions for editing and deleting attendance records
if ($isAdmin && isset($_POST['admin_action'])) {
    $adminAction = $_POST['admin_action'];
    
    try {
        $con->beginTransaction();
        
        if ($adminAction === 'edit_attendance' && isset($_POST['log_id'], $_POST['time_in'])) {
            $logId = $_POST['log_id'];
            $timeIn = $_POST['time_in'];
            $timeOut = !empty($_POST['time_out']) ? $_POST['time_out'] : null;
            
            // Get the user_id and log_date from the time_logs table
            $queryGetLog = "SELECT user_id, log_date FROM time_logs WHERE id = :log_id";
            $stmtGetLog = $con->prepare($queryGetLog);
            $stmtGetLog->bindParam(':log_id', $logId);
            $stmtGetLog->execute();
            $logData = $stmtGetLog->fetch(PDO::FETCH_ASSOC);
            
            if ($logData) {
                $userId = $logData['user_id'];
                $logDate = $logData['log_date'];
                
                // Update time_in in time_in_logs table
                $queryTimeIn = "INSERT INTO time_in_logs (user_id, log_date, time_in) 
                               VALUES (:uid, :log_date, :time_in)
                               ON DUPLICATE KEY UPDATE time_in = :time_in_update";
                $stmtTimeIn = $con->prepare($queryTimeIn);
                $stmtTimeIn->bindParam(':uid', $userId);
                $stmtTimeIn->bindParam(':log_date', $logDate);
                $stmtTimeIn->bindParam(':time_in', $timeIn);
                $stmtTimeIn->bindParam(':time_in_update', $timeIn);
                $stmtTimeIn->execute();
                
                // Handle time_out in time_out_logs table
                if ($timeOut) {
                    $queryTimeOut = "INSERT INTO time_out_logs (user_id, log_date, time_out) 
                                    VALUES (:uid, :log_date, :time_out)
                                    ON DUPLICATE KEY UPDATE time_out = :time_out_update";
                    $stmtTimeOut = $con->prepare($queryTimeOut);
                    $stmtTimeOut->bindParam(':uid', $userId);
                    $stmtTimeOut->bindParam(':log_date', $logDate);
                    $stmtTimeOut->bindParam(':time_out', $timeOut);
                    $stmtTimeOut->bindParam(':time_out_update', $timeOut);
                    $stmtTimeOut->execute();
                } else {
                    // If time_out is NULL, delete any existing time_out record
                    $queryDeleteTimeOut = "DELETE FROM time_out_logs WHERE user_id = :uid AND log_date = :log_date";
                    $stmtDeleteTimeOut = $con->prepare($queryDeleteTimeOut);
                    $stmtDeleteTimeOut->bindParam(':uid', $userId);
                    $stmtDeleteTimeOut->bindParam(':log_date', $logDate);
                    $stmtDeleteTimeOut->execute();
                    
                    // Also update time_logs to ensure time_out is NULL
                    $queryUpdateTimeLog = "UPDATE time_logs SET time_out = NULL, total_hours = NULL 
                                          WHERE user_id = :uid AND log_date = :log_date";
                    $stmtUpdateTimeLog = $con->prepare($queryUpdateTimeLog);
                    $stmtUpdateTimeLog->bindParam(':uid', $userId);
                    $stmtUpdateTimeLog->bindParam(':log_date', $logDate);
                    $stmtUpdateTimeLog->execute();
                }
            }
            
            $message = 'Attendance record updated successfully!';
            $type = 'success';
        }
        elseif ($adminAction === 'delete_attendance' && isset($_POST['log_id'])) {
            $logId = $_POST['log_id'];
            
            // Get the user_id and log_date from the time_logs table
            $queryGetLog = "SELECT user_id, log_date FROM time_logs WHERE id = :log_id";
            $stmtGetLog = $con->prepare($queryGetLog);
            $stmtGetLog->bindParam(':log_id', $logId);
            $stmtGetLog->execute();
            $logData = $stmtGetLog->fetch(PDO::FETCH_ASSOC);
            
            if ($logData) {
                $userId = $logData['user_id'];
                $logDate = $logData['log_date'];
                
                // Delete from time_in_logs
                $queryDeleteTimeIn = "DELETE FROM time_in_logs WHERE user_id = :uid AND log_date = :log_date";
                $stmtDeleteTimeIn = $con->prepare($queryDeleteTimeIn);
                $stmtDeleteTimeIn->bindParam(':uid', $userId);
                $stmtDeleteTimeIn->bindParam(':log_date', $logDate);
                $stmtDeleteTimeIn->execute();
                
                // Delete from time_out_logs
                $queryDeleteTimeOut = "DELETE FROM time_out_logs WHERE user_id = :uid AND log_date = :log_date";
                $stmtDeleteTimeOut = $con->prepare($queryDeleteTimeOut);
                $stmtDeleteTimeOut->bindParam(':uid', $userId);
                $stmtDeleteTimeOut->bindParam(':log_date', $logDate);
                $stmtDeleteTimeOut->execute();
                
                // Delete from time_logs (will be handled by triggers, but just to be sure)
                $queryDeleteTimeLog = "DELETE FROM time_logs WHERE id = :log_id";
                $stmtDeleteTimeLog = $con->prepare($queryDeleteTimeLog);
                $stmtDeleteTimeLog->bindParam(':log_id', $logId);
                $stmtDeleteTimeLog->execute();
            }
            
            $message = 'Attendance record deleted successfully!';
            $type = 'success';
        }
        
        $con->commit();
    } catch (PDOException $ex) {
        $con->rollback();
        $message = 'Error: ' . $ex->getMessage();
        $type = 'error';
    }
    
    // Redirect back to this page with the feedback message and type
    header("Location: time_tracker.php?message=" . urlencode($message) . "&type=" . urlencode($type));
    exit;
}

// Fetch logs for history
try {
    if ($isAdmin) {
        // Admin can see all users' logs
        $stmtLogs = $con->prepare("
            SELECT tl.*, u.display_name, u.role 
            FROM `time_logs` tl 
            JOIN `users` u ON tl.user_id = u.id 
            ORDER BY tl.log_date DESC, u.display_name ASC
        ");
        $stmtLogs->execute();
        
        // Get all users for the filter dropdown
        $stmtUsers = $con->prepare("SELECT id, display_name FROM users ORDER BY display_name");
        $stmtUsers->execute();
        $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Regular users can only see their own logs
        $stmtLogs = $con->prepare("SELECT * FROM `time_logs` WHERE `user_id` = :uid ORDER BY `log_date` DESC");
        $stmtLogs->execute([':uid' => $userId]);
    }
} catch (PDOException $ex) {
    $message = 'Error fetching logs: ' . $ex->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <?php include './config/data_tables_css.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title><?php echo $isAdmin ? 'Attendance Management' : 'My Attendance'; ?> - Mamatid Health Center System</title>
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

        .btn-primary:disabled {
            background: #e4e6ef;
            transform: none;
            box-shadow: none;
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

        /* Alert Styling */
        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-info {
            background: rgba(54, 153, 255, 0.1);
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

        /* Modern Table Styling */
        .table {
            margin-bottom: 0;
            border-spacing: 0 8px;
            border-collapse: separate;
        }

        .table thead tr {
            background: transparent;
        }

        .table thead th {
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 1rem 1.5rem;
            color: #6c757d;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 8px;
        }

        .table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            background-color: #f8f9fa;
        }

        .table tbody td {
            padding: 1rem 1.5rem;
            border: none;
            vertical-align: middle;
        }

        /* Custom Badges */
        .date-badge {
            background: #e8f3ff;
            color: #3699FF;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .time-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .time-badge.time-in {
            background: #E1F0FF;
            color: #3699FF;
        }

        .time-badge.time-out {
            background: #FFE2E5;
            color: #F64E60;
        }

        .hours-badge {
            background: linear-gradient(135deg, #3699FF 0%, #6993FF 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .hours-badge small {
            opacity: 0.8;
            margin-left: 2px;
        }

        /* Card Enhancements */
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            border-radius: 15px;
            overflow: hidden;
        }

        .card-header {
            background: white;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .card-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a1a2d;
            display: flex;
            align-items: center;
        }

        /* DataTables Customization */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e4e6ef;
            border-radius: 6px;
            padding: 8px 12px;
            margin-left: 8px;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #e4e6ef;
            border-radius: 6px;
            padding: 6px 24px 6px 12px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px;
            padding: 8px 12px;
            margin: 0 2px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #3699FF;
            border-color: #3699FF;
            color: white !important;
        }

        /* Modern Alert Styling */
        .alert-modern {
            border: none;
            background: rgba(54, 153, 255, 0.1);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            color: #3699FF;
        }

        /* Attendance Actions Styling */
        .attendance-actions {
            padding: 0.5rem;
        }

        .btn-attendance {
            width: 100%;
            padding: 1.5rem;
            border-radius: 12px;
            border: none;
            text-align: center;
            font-weight: 500;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }

        .btn-attendance .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }

        .btn-attendance i {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }

        .btn-attendance.time-in-btn {
            background: linear-gradient(135deg, #3699FF 0%, #6993FF 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.2);
        }

        .btn-attendance.time-out-btn {
            background: linear-gradient(135deg, #F64E60 0%, #FF8087 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(246, 78, 96, 0.2);
        }

        .btn-attendance:not(.disabled):hover {
            transform: translateY(-2px);
        }

        .btn-attendance.time-in-btn:not(.disabled):hover {
            box-shadow: 0 6px 20px rgba(54, 153, 255, 0.3);
        }

        .btn-attendance.time-out-btn:not(.disabled):hover {
            box-shadow: 0 6px 20px rgba(246, 78, 96, 0.3);
        }

        .btn-attendance.disabled {
            opacity: 0.8;
            cursor: not-allowed;
            background: #f5f5f5;
            color: #999;
            box-shadow: none;
        }

        .btn-attendance .status-text {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }

        /* Animation for button hover */
        .btn-attendance:not(.disabled):hover .btn-content {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .btn-attendance {
                padding: 1rem;
                min-height: 100px;
                font-size: 1rem;
            }

            .btn-attendance i {
                font-size: 1.2rem;
            }
        }
        
        /* User Badge Styling */
        .user-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
        }
        
        .user-role-admin {
            background-color: rgba(137, 80, 252, 0.15);
            color: #8950FC;
        }
        
        .user-role-doctor {
            background-color: rgba(255, 168, 0, 0.15);
            color: #FFA800;
        }
        
        .user-role-health_worker {
            background-color: rgba(54, 153, 255, 0.15);
            color: #3699FF;
        }
        
        .user-role-user {
            background-color: rgba(181, 181, 195, 0.15);
            color: #7E8299;
        }
        
        /* Action Buttons Group */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
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
        #time_logs_wrapper .row:first-child {
            margin-bottom: 15px;
        }

        #time_logs_wrapper .dataTables_filter {
            float: left !important;
            text-align: left !important;
        }

        #time_logs_wrapper .dataTables_filter input {
            width: 300px;
            border-radius: 8px;
            border: 2px solid #e4e6ef;
            padding: 0.625rem 1rem;
            font-size: 1rem;
            transition: all 0.3s;
        }

        #time_logs_wrapper .dataTables_filter input:focus {
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

            #time_logs_wrapper .dataTables_filter input {
                width: 100%;
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
<div class="wrapper">
    <!-- Header and sidebar include -->
    <?php include './config/header.php'; include './config/sidebar.php'; ?>
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row align-items-center mb-4">
                    <div class="col-12 col-md-6" style="padding-left: 20px;">
                        <h1><?php echo $isAdmin ? 'Attendance Management' : 'My Attendance'; ?></h1>
                    </div>
                    <div class="col-12 col-md-6 text-md-right mt-3 mt-md-0">
                        <span id="datetime" class="d-inline-block"></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content section -->
        <section class="content">
            <div class="container-fluid">
                <!-- Card for recording attendance -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-clock mr-2"></i><?php echo $isAdmin ? 'Attendance Recording' : 'Record My Attendance'; ?>
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!$isAdmin): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> You can record your attendance by using the Time In and Time Out buttons below. Only administrators can edit or delete attendance records.
                        </div>
                        <?php endif; ?>
                        <div class="row attendance-actions">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <form method="post">
                                    <input type="hidden" name="action" value="time_in">
                                    <button type="submit" class="btn btn-attendance time-in-btn <?php echo !$canTimeIn ? 'disabled' : ''; ?>" <?php if (!$canTimeIn) echo 'disabled'; ?>>
                                        <div class="btn-content">
                                            <i class="fas fa-sign-in-alt"></i>
                                            <span class="ml-2">Time In</span>
                                        </div>
                                        <?php if (!$canTimeIn): ?>
                                            <div class="status-text">Already Timed In</div>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="post">
                                    <input type="hidden" name="action" value="time_out">
                                    <button type="submit" class="btn btn-attendance time-out-btn <?php echo !$canTimeOut ? 'disabled' : ''; ?>" <?php if (!$canTimeOut) echo 'disabled'; ?>>
                                        <div class="btn-content">
                                            <i class="fas fa-sign-out-alt"></i>
                                            <span class="ml-2">Time Out</span>
                                        </div>
                                        <?php if (!$canTimeOut): ?>
                                            <div class="status-text">No Active Time In</div>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card for time log history -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history mr-2"></i><?php echo $isAdmin ? 'All Staff Attendance Records' : 'My Attendance History'; ?>
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="time_logs" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <?php if ($isAdmin): ?>
                                        <th><i class="fas fa-user mr-2"></i>Employee</th>
                                        <?php endif; ?>
                                        <th><i class="far fa-calendar-alt mr-2"></i>Date</th>
                                        <th><i class="fas fa-sign-in-alt mr-2"></i>Time In</th>
                                        <th><i class="fas fa-sign-out-alt mr-2"></i>Time Out</th>
                                        <th><i class="far fa-clock mr-2"></i>Total Hours</th>
                                        <?php if ($isAdmin): ?>
                                        <th><i class="fas fa-cogs mr-2"></i>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($log = $stmtLogs->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <?php if ($isAdmin): ?>
                                            <td>
                                                <span class="user-badge user-role-<?php echo isset($log['role']) ? $log['role'] : 'user'; ?>">
                                                    <?php echo isset($log['display_name']) ? $log['display_name'] : 'Unknown User'; ?>
                                                </span>
                                            </td>
                                            <?php endif; ?>
                                            <td>
                                                <span class="date-badge">
                                                    <?php echo date('Y F d', strtotime($log['log_date'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log['time_in']): ?>
                                                    <span class="time-badge time-in">
                                                        <i class="fas fa-sign-in-alt mr-1"></i>
                                                        <?php echo date('h:i:s A', strtotime($log['time_in'])); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-light">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($log['time_out']): ?>
                                                    <span class="time-badge time-out">
                                                        <i class="fas fa-sign-out-alt mr-1"></i>
                                                        <?php echo date('h:i:s A', strtotime($log['time_out'])); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Not yet timed out</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($log['time_out']): ?>
                                                    <span class="hours-badge">
                                                        <?php 
                                                            $time_in = strtotime($log['time_in']);
                                                            $time_out = strtotime($log['time_out']);
                                                            $total_hours = round(($time_out - $time_in) / 3600, 2);
                                                            echo number_format($total_hours, 2);
                                                        ?>
                                                        <small>hrs</small>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-light">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($isAdmin): ?>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-primary btn-sm edit-attendance" 
                                                            data-id="<?php echo $log['id']; ?>"
                                                            data-date="<?php echo $log['log_date']; ?>"
                                                            data-time-in="<?php echo $log['time_in']; ?>"
                                                            data-time-out="<?php echo $log['time_out']; ?>"
                                                            data-toggle="tooltip" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm delete-attendance" 
                                                            data-id="<?php echo $log['id']; ?>"
                                                            data-toggle="tooltip" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="export-container mt-4" id="exportContainer">
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
        </section>
    </div>
    <?php include './config/footer.php'; ?>
</div>

<!-- Edit Attendance Modal -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="editAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="editAttendanceModalLabel">Edit Attendance Record</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editAttendanceForm" method="post">
                <div class="modal-body">
                    <input type="hidden" name="admin_action" value="edit_attendance">
                    <input type="hidden" name="log_id" id="edit_log_id">
                    
                    <div class="form-group">
                        <label for="edit_date">Date</label>
                        <input type="text" class="form-control" id="edit_date" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_time_in">Time In</label>
                        <input type="time" class="form-control" name="time_in" id="edit_time_in">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_time_out">Time Out <small class="text-muted">(Leave empty if not timed out yet)</small></label>
                        <input type="time" class="form-control" name="time_out" id="edit_time_out">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="deleteAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAttendanceModalLabel">Delete Attendance Record</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="deleteAttendanceForm" method="post">
                <div class="modal-body">
                    <input type="hidden" name="admin_action" value="delete_attendance">
                    <input type="hidden" name="log_id" id="delete_log_id">
                    <p>Are you sure you want to delete this attendance record? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include './config/site_js_links.php'; ?>
<?php include './config/data_tables_js.php'; ?>

<script>
    $(document).ready(function() {
        // Initialize Toast
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

        // Show message if exists in URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        const type = urlParams.get('type') || 'success';
        
        if (message) {
            Toast.fire({
                icon: type,
                title: message
            });
        }

        // Initialize DataTable with modern styling
        var table = $('#time_logs').DataTable({
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
                searchPlaceholder: "Search records...",
                paginate: {
                    previous: "<i class='fas fa-chevron-left'></i>",
                    next: "<i class='fas fa-chevron-right'></i>"
                }
            }
        });

        // Hide default buttons
        $('.dt-buttons').hide();
        
        // Custom export button handlers
        $('#btnCopy').click(function(e) {
            e.preventDefault();
            var table = $('#time_logs').DataTable();
            table.button('.buttons-copy').trigger();
            
            Toast.fire({
                icon: 'success',
                title: 'Data copied to clipboard!'
            });
        });

        $('#btnCSV').click(function(e) {
            e.preventDefault();
            var table = $('#time_logs').DataTable();
            table.button('.buttons-csv').trigger();
        });

        $('#btnExcel').click(function(e) {
            e.preventDefault();
            var table = $('#time_logs').DataTable();
            table.button('.buttons-excel').trigger();
        });

        $('#btnPDF').click(function(e) {
            e.preventDefault();
            var table = $('#time_logs').DataTable();
            table.button('.buttons-pdf').trigger();
        });

        $('#btnPrint').click(function(e) {
            e.preventDefault();
            var table = $('#time_logs').DataTable();
            table.button('.buttons-print').trigger();
        });

        // Update datetime display
        function updateDateTime() {
            var now = new Date();
            var options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            $('#datetime').text(now.toLocaleDateString('en-US', options));
        }
        
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Add click handlers for Time In/Out buttons with Toast notifications
        $('.btn-attendance').on('click', function(e) {
            if ($(this).hasClass('disabled')) {
                e.preventDefault();
                Toast.fire({
                    icon: 'warning',
                    title: $(this).find('.status-text').text()
                });
            }
        });
        
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Handle edit attendance button click
        $('.edit-attendance').on('click', function() {
            const id = $(this).data('id');
            const date = $(this).data('date');
            const timeIn = $(this).data('time-in');
            const timeOut = $(this).data('time-out');
            
            $('#edit_log_id').val(id);
            $('#edit_date').val(date);
            $('#edit_time_in').val(timeIn ? timeIn.substring(0, 5) : '');
            
            // Only set time_out value if it exists
            if (timeOut) {
                $('#edit_time_out').val(timeOut.substring(0, 5));
            } else {
                $('#edit_time_out').val('');
            }
            
            $('#editAttendanceModal').modal('show');
        });
        
        // Handle delete attendance button click
        $('.delete-attendance').on('click', function() {
            const id = $(this).data('id');
            $('#delete_log_id').val(id);
            $('#deleteAttendanceModal').modal('show');
        });
    });

    // Highlight current menu
    showMenuSelected("#mnu_users", "");
</script>
</body>
</html>
