<?php
include './config/connection.php';
include './common_service/common_functions.php';

// Set the timezone to your local timezone
date_default_timezone_set('Asia/Manila');

$message = '';
// Ensure the session is started elsewhere so that $_SESSION['user_id'] is available
$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

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
            // Insert a new record into time_logs for a Time In action
            $query = "INSERT INTO `time_logs` (`user_id`, `log_date`, `time_in`) 
                      VALUES (:uid, :today, :time_in)";
            $stmt = $con->prepare($query);
            $stmt->execute([':uid' => $userId, ':today' => $today, ':time_in' => $currentTime]);
            $message = 'Time In recorded successfully!';
        } elseif ($action == 'time_out' && $canTimeOut) {
            // Update time_logs with time out and calculate total hours
            $query = "UPDATE `time_logs` 
                     SET `time_out` = :time_out,
                         `total_hours` = ROUND(TIMESTAMPDIFF(SECOND, `time_in`, :time_out) / 3600, 2)
                     WHERE `user_id` = :uid 
                     AND `log_date` = :today";
            $stmt = $con->prepare($query);
            $stmt->execute([
                ':uid' => $userId,
                ':today' => $today,
                ':time_out' => $currentTime
            ]);
            $message = 'Time Out recorded successfully!';
        } else {
            // If the conditions aren't met, provide appropriate feedback message
            $message = $action == 'time_in' ? 'You have already timed in today.' : 'No active Time In found or Time Out already recorded.';
        }

        // Commit transaction if all queries executed successfully
        $con->commit();
    } catch (PDOException $ex) {
        // Roll back transaction in case of any error and capture error message
        $con->rollback();
        $message = 'Error: ' . $ex->getMessage();
    }

    // Redirect back to this page with the feedback message
    header("Location: time_tracker.php?message=" . urlencode($message));
    exit;
}

// Fetch logs for history (for the current user), ordered by log_date in descending order
try {
    $stmtLogs = $con->prepare("SELECT * FROM `time_logs` WHERE `user_id` = :uid ORDER BY `log_date` DESC");
    $stmtLogs->execute([':uid' => $userId]);
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
    <title>Time Tracker - Mamatid Health Center System</title>
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
                        <h1>Time Tracker</h1>
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
                            <i class="fas fa-user-clock mr-2"></i>Record Attendance
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['message'])): ?>
                            <div class="alert alert-info alert-modern">
                                <i class="fas fa-info-circle mr-2"></i>
                                <?php echo htmlspecialchars($_GET['message']); ?>
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
                            <i class="fas fa-history mr-2"></i>Time Log History
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
                                        <th><i class="far fa-calendar-alt mr-2"></i>Date</th>
                                        <th><i class="fas fa-sign-in-alt mr-2"></i>Time In</th>
                                        <th><i class="fas fa-sign-out-alt mr-2"></i>Time Out</th>
                                        <th><i class="far fa-clock mr-2"></i>Total Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($log = $stmtLogs->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
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
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <?php include './config/footer.php'; ?>
</div>

<?php include './config/site_js_links.php'; ?>
<?php include './config/data_tables_js.php'; ?>

<script>
    $(document).ready(function() {
        // Initialize DataTable with modern styling
        $('#time_logs').DataTable({
            responsive: true,
            lengthChange: false,
            autoWidth: false,
            buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
            language: {
                search: "",
                searchPlaceholder: "Search records..."
            },
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });

        // Append buttons container
        $('#time_logs_wrapper .col-md-6:eq(0)').append($('#time_logs_buttons_container'));

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

        // Fade out alert after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });

    // Highlight current menu
    showMenuSelected("#mnu_users", "");
</script>
</body>
</html>
