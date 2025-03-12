<?php
// Ensure the correct timezone is set
date_default_timezone_set('Asia/Manila');

include './config/connection.php';

$date  = date('Y-m-d');
$year  = date('Y');
$month = date('m');

// Existing queries for stat boxes
$queryToday = "SELECT count(*) as `today`
  FROM `patient_visits`
  WHERE DATE(`created_at`) = '$date';";

$queryWeek = "SELECT count(*) as `week`
  FROM `patient_visits`
  WHERE YEARWEEK(`visit_date`, 1) = YEARWEEK('$date', 1);";

$queryYear = "SELECT count(*) as `year`
  FROM `patient_visits`
  WHERE YEAR(`visit_date`) = YEAR('$date');";

$queryMonth = "SELECT count(*) as `month`
  FROM `patient_visits`
  WHERE YEAR(`visit_date`) = $year
    AND MONTH(`visit_date`) = $month;";

$todaysCount      = 0;
$currentWeekCount = 0;
$currentMonthCount= 0;
$currentYearCount = 0;

try {
    $stmtToday = $con->prepare($queryToday);
    $stmtToday->execute();
    $r = $stmtToday->fetch(PDO::FETCH_ASSOC);
    $todaysCount = $r['today'];

    $stmtWeek = $con->prepare($queryWeek);
    $stmtWeek->execute();
    $r = $stmtWeek->fetch(PDO::FETCH_ASSOC);
    $currentWeekCount = $r['week'];

    $stmtYear = $con->prepare($queryYear);
    $stmtYear->execute();
    $r = $stmtYear->fetch(PDO::FETCH_ASSOC);
    $currentYearCount = $r['year'];

    $stmtMonth = $con->prepare($queryMonth);
    $stmtMonth->execute();
    $r = $stmtMonth->fetch(PDO::FETCH_ASSOC);
    $currentMonthCount = $r['month'];
} catch(PDOException $ex) {
    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
}

/* Dynamic Data for Chart */

// WEEKLY DATA: Count patients for the current week grouped by day name.
$queryWeekly = "SELECT DAYNAME(visit_date) as day, COUNT(*) as count 
                FROM patient_visits 
                WHERE YEARWEEK(visit_date, 1) = YEARWEEK('$date', 1) 
                GROUP BY day 
                ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');";
$stmtWeekly = $con->prepare($queryWeekly);
$stmtWeekly->execute();
$weeklyLabels = [];
$weeklyData   = [];
while($row = $stmtWeekly->fetch(PDO::FETCH_ASSOC)) {
    $weeklyLabels[] = $row['day'];
    $weeklyData[]   = $row['count'];
}

// MONTHLY DATA: Count patients for the current month grouped by week number.
$queryMonthly = "SELECT WEEK(visit_date, 1) as week, COUNT(*) as count 
                 FROM patient_visits 
                 WHERE YEAR(visit_date) = '$year' AND MONTH(visit_date) = '$month'
                 GROUP BY week 
                 ORDER BY week ASC;";
$stmtMonthly = $con->prepare($queryMonthly);
$stmtMonthly->execute();
$monthlyLabels = [];
$monthlyData   = [];
while($row = $stmtMonthly->fetch(PDO::FETCH_ASSOC)) {
    $monthlyLabels[] = "Week " . $row['week'];
    $monthlyData[]   = $row['count'];
}

// YEARLY DATA: Count patients for the current year grouped by month.
$queryYearly = "SELECT MONTH(visit_date) as month, COUNT(*) as count 
                FROM patient_visits 
                WHERE YEAR(visit_date) = '$year'
                GROUP BY month 
                ORDER BY month ASC;";
$stmtYearly = $con->prepare($queryYearly);
$stmtYearly->execute();
$yearlyLabels = [];
$yearlyData   = [];
while($row = $stmtYearly->fetch(PDO::FETCH_ASSOC)) {
    $monthName = date("F", mktime(0, 0, 0, $row['month'], 10));
    $yearlyLabels[] = $monthName;
    $yearlyData[]   = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <!-- Logo for the tab bar -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Dashboard - Mamatid Health Center System</title>
  <style>
    .dark-mode .bg-fuchsia, .dark-mode .bg-maroon {
      color: #fff!important;
    }
  </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar -->
    <?php 
      include './config/header.php';
      include './config/sidebar.php';
    ?>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <section class="content-header">
        <div class="container-fluid">
          <div class="row align-items-center mb-2">
            <div class="col-md-6">
              <h1>Dashboard</h1>
            </div>
            <div class="col-md-6 text-right">
              <!-- Live-updating Date & Time styled with a Bootstrap badge -->
              <h4><span id="datetime" class="badge badge-primary"></span></h4>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>
      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <!-- Stat Boxes -->
          <div class="row">
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h3><?php echo $todaysCount; ?></h3>
                  <p>Today's Patients</p>
                </div>
                <div class="icon">
                  <i class="fa fa-calendar-day"></i>
                </div>
              </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
              <div class="small-box bg-purple">
                <div class="inner">
                  <h3><?php echo $currentWeekCount; ?></h3>
                  <p>Current Week</p>
                </div>
                <div class="icon">
                  <i class="fa fa-calendar-week"></i>
                </div>
              </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
              <div class="small-box bg-fuchsia text-reset">
                <div class="inner">
                  <h3><?php echo $currentMonthCount; ?></h3>
                  <p>Current Month</p>
                </div>
                <div class="icon">
                  <i class="fa fa-calendar"></i>
                </div>
              </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
              <div class="small-box bg-maroon text-reset">
                <div class="inner">
                  <h3><?php echo $currentYearCount; ?></h3>
                  <p>Current Year</p>
                </div>
                <div class="icon">
                  <i class="fa fa-user-injured"></i>
                </div>
              </div>
            </div>
          </div>
          <!-- End of Stat Boxes -->

          <!-- Chart Section -->
          <div class="container my-4">
            <div class="row">
              <div class="col-md-4">
                <select id="chartType" class="form-select">
                  <option value="weekly">Weekly Breakdown</option>
                  <option value="monthly">Monthly Breakdown</option>
                  <option value="yearly">Yearly Breakdown</option>
                </select>
              </div>
            </div>
            <div class="row my-3">
              <div class="col">
                <canvas id="patientChart"></canvas>
              </div>
            </div>
          </div>
          <!-- End of Chart Section -->

        </div>
      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
    <?php include './config/footer.php'; ?>
  </div>
  <!-- ./wrapper -->
  <?php include './config/site_js_links.php'; ?>
  <!-- Include Chart.js from CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    $(function(){
      showMenuSelected("#mnu_dashboard", "");
    });
    
    // JavaScript to update date and time every second in the desired format:
    // e.g., "February 02, 2025 03:15:23 PM"
    function updateDateTime() {
      var now = new Date();
      var options = {
        month: 'long',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      };
      var formattedDateTime = now.toLocaleString('en-US', options);
      document.getElementById('datetime').innerHTML = formattedDateTime;
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Dynamic chart data from PHP arrays
    const chartData = {
      weekly: {
        labels: <?php echo json_encode($weeklyLabels); ?>,
        data: <?php echo json_encode($weeklyData); ?>
      },
      monthly: {
        labels: <?php echo json_encode($monthlyLabels); ?>,
        data: <?php echo json_encode($monthlyData); ?>
      },
      yearly: {
        labels: <?php echo json_encode($yearlyLabels); ?>,
        data: <?php echo json_encode($yearlyData); ?>
      }
    };

    let currentChart;
    const ctx = document.getElementById('patientChart').getContext('2d');

    function renderChart(type) {
      if (currentChart) {
        currentChart.destroy();
      }
      currentChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: chartData[type].labels,
          datasets: [{
            label: "Number of Patients",
            data: chartData[type].data,
            backgroundColor: "rgba(0, 123, 255, 0.7)",
            borderColor: "rgba(0, 123, 255, 1)",
            borderWidth: 1
          }]
        },
        options: {
          scales: {
            y: {
              beginAtZero: true,
              ticks: { stepSize: 10 }
            }
          }
        }
      });
    }

    // Initial render using weekly data
    renderChart("weekly");

    document.getElementById("chartType").addEventListener("change", function() {
      renderChart(this.value);
    });
  </script>
</body>
</html>
