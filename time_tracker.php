<?php
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';
// Logic for inserting or updating time logs here

if (isset($_POST['save_time_log'])) {
  $userId   = $_SESSION['user_id'];
  $logDate  = $_POST['log_date'];
  $timeIn   = $_POST['time_in'];
  $timeOut  = $_POST['time_out'];

  // Calculate total hours
  $diffInSeconds = strtotime($timeOut) - strtotime($timeIn);
  $totalHours = round($diffInSeconds / 3600, 2);

  try {
    $con->beginTransaction();
    $query = "INSERT INTO `time_logs` (user_id, log_date, time_in, time_out, total_hours)
              VALUES (:user_id, :log_date, :time_in, :time_out, :total_hours)";
    $stmt = $con->prepare($query);
    $stmt->execute([
      ':user_id'     => $userId,
      ':log_date'    => $logDate,
      ':time_in'     => $timeIn,
      ':time_out'    => $timeOut,
      ':total_hours' => $totalHours
    ]);
    $con->commit();
    $message = 'Time log saved successfully!';
  } catch (PDOException $ex) {
    $con->rollback();
    $message = 'Error saving time log: ' . $ex->getMessage();
  }
}

// Fetch logs
try {
  $stmtLogs = $con->prepare("SELECT * FROM `time_logs` WHERE `user_id` = :uid ORDER BY `log_date` DESC");
  $stmtLogs->execute([':uid' => $_SESSION['user_id']]);
} catch (PDOException $ex) {
  echo $ex->getMessage();
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <?php include './config/site_css_links.php';?>
  <!-- Logo for the tab bar -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
 <title>Time Tracker - Mamatid Health Center System</title>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  <?php
    include './config/header.php';
    include './config/sidebar.php';
  ?>

  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>Time Tracker</h1>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>

    <section class="content">
      <!-- Time In/Out Form -->
      <div class="card card-outline card-primary rounded-0 shadow">
        <div class="card-header">
          <h3 class="card-title">Record Attendance</h3>
          <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
        </div>
        <div class="card-body">
          <?php if ($message != ''): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
          <?php endif; ?>

          <form method="post">
            <div class="row">
              <div class="col-lg-3 col-md-4 col-sm-6">
                <label for="log_date">Date</label>
                <input type="date" id="log_date" name="log_date" class="form-control form-control-sm" required>
              </div>
              <div class="col-lg-3 col-md-4 col-sm-6">
                <label for="time_in">Time In</label>
                <input type="time" id="time_in" name="time_in" class="form-control form-control-sm" required>
              </div>
              <div class="col-lg-3 col-md-4 col-sm-6">
                <label for="time_out">Time Out</label>
                <input type="time" id="time_out" name="time_out" class="form-control form-control-sm">
              </div>
              <div class="col-lg-2 col-md-2 col-sm-6">
                <label>&nbsp;</label>
                <button type="submit" name="save_time_log" class="btn btn-primary btn-sm btn-block">
                  Save
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Time Logs History Table -->
      <div class="card card-outline card-primary rounded-0 shadow">
        <div class="card-header">
          <h3 class="card-title">Time Logs History</h3>
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
              <?php while($log = $stmtLogs->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                  <td><?php echo $log['log_date']; ?></td>
                  <td><?php echo $log['time_in']; ?></td>
                  <td><?php echo $log['time_out']; ?></td>
                  <td><?php echo $log['total_hours']; ?></td>
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
</body>
</html>
