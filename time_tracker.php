<?php
include './config/connection.php';
include './common_service/common_functions.php';

// Set the timezone to your local timezone
date_default_timezone_set('Asia/Manila');

$message = '';
$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

// Check today's log in time_logs
$logToday = null;
$query = "SELECT * FROM `time_logs` WHERE `user_id` = :uid AND `log_date` = :today";
$stmt = $con->prepare($query);
$stmt->execute([':uid' => $userId, ':today' => $today]);
$logToday = $stmt->fetch();

// Determine if user can Time In or Time Out
$canTimeIn = !$logToday; // Can Time In if no log exists for today
$canTimeOut = $logToday && $logToday['time_in'] && !$logToday['time_out']; // Can Time Out if Time In is set and Time Out is not

// Handle form submissions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $currentTime = date('H:i:s');

    try {
        $con->beginTransaction();

        if ($action == 'time_in' && $canTimeIn) {
            $query = "INSERT INTO `time_in_logs` (`user_id`, `log_date`, `time_in`) 
                      VALUES (:uid, :today, :time_in)";
            $stmt = $con->prepare($query);
            $stmt->execute([':uid' => $userId, ':today' => $today, ':time_in' => $currentTime]);
            $message = 'Time In recorded successfully!';
        } elseif ($action == 'time_out' && $canTimeOut) {
            $query = "INSERT INTO `time_out_logs` (`user_id`, `log_date`, `time_out`) 
                      VALUES (:uid, :today, :time_out)";
            $stmt = $con->prepare($query);
            $stmt->execute([':uid' => $userId, ':today' => $today, ':time_out' => $currentTime]);
            $message = 'Time Out recorded successfully!';
        } else {
            $message = $action == 'time_in' ? 'You have already timed in today.' : 'No active Time In found or Time Out already recorded.';
        }

        $con->commit();
    } catch (PDOException $ex) {
        $con->rollback();
        $message = 'Error: ' . $ex->getMessage();
    }

    header("Location: time_tracker.php?message=" . urlencode($message));
    exit;
}

// Fetch logs for history
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
        .user-img { width: 3em; height: 3em; object-fit: cover; object-position: center; border-radius: 50%; }
        .card-body form { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0px 0px 15px rgba(0,0,0,0.1); }
        table.dataTable { font-size: 0.9rem; }
        .btn-primary { background-color: #007bff; border: none; transition: background 0.3s ease-in-out; }
        .btn-primary:hover { background-color: #0056b3; }
    </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
<div class="wrapper">
    <?php include './config/header.php'; include './config/sidebar.php'; ?>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6"><h1>TIME TRACKER</h1></div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="card card-outline card-primary rounded-0 shadow">
                <div class="card-header">
                    <h3 class="card-title">RECORD ATTENDANCE</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['message'])): ?>
                        <div class="alert alert-info"><?php echo htmlspecialchars($_GET['message']); ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6">
                            <form method="post">
                                <input type="hidden" name="action" value="time_in">
                                <button type="submit" class="btn btn-primary btn-block" <?php if (!$canTimeIn) echo 'disabled'; ?>>
                                    Time In
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="post">
                                <input type="hidden" name="action" value="time_out">
                                <button type="submit" class="btn btn-primary btn-block" <?php if (!$canTimeOut) echo 'disabled'; ?>>
                                    Time Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary rounded-0 shadow">
                <div class="card-header">
                    <h3 class="card-title">TIME LOG HISTORY</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead class="bg-light">
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Total Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($log = $stmtLogs->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo date('Y F d', strtotime($log['log_date'])); ?></td>
                                    <td><?php echo $log['time_in'] ? date('h:i:s A', strtotime($log['time_in'])) : 'N/A'; ?></td>
                                    <td><?php echo $log['time_out'] ? date('h:i:s A', strtotime($log['time_out'])) : 'Not yet timed out'; ?></td>
                                    <td><?php echo $log['time_out'] ? number_format($log['total_hours'], 2) : 'N/A'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
    <?php include './config/footer.php'; ?>
</div>

<?php include './config/site_js_links.php'; ?>
<?php include './config/data_tables_js.php'; ?>

<script>
    showMenuSelected("#mnu_users", "");
    var message = '<?php echo isset($_GET['message']) ? addslashes($_GET['message']) : ''; ?>';
    if (message !== '') showCustomMessage(message);

    // Fade out the alert after 5 seconds (5000 milliseconds)
    setTimeout(function(){
        $('.alert.alert-info').fadeOut('slow');
    }, 5000);
</script>
</body>
</html>