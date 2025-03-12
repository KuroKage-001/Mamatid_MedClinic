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

// WEEKLY DATA: Count patients for the current week grouped by day name,
// and also retrieve the earliest visit_date for each day.
$queryWeekly = "SELECT DAYNAME(visit_date) as day, MIN(visit_date) as first_date, COUNT(*) as count
                FROM patient_visits
                WHERE YEARWEEK(visit_date, 1) = YEARWEEK('$date', 1)
                GROUP BY day
                ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');";
$stmtWeekly = $con->prepare($queryWeekly);
$stmtWeekly->execute();
$weeklyLabels = [];
$weeklyData   = [];
while($row = $stmtWeekly->fetch(PDO::FETCH_ASSOC)) {
    // Format: "DayName - (MonthName Day)"
    $formattedDate = date("F j", strtotime($row['first_date']));
    $weeklyLabels[] = $row['day'] . " - (" . $formattedDate . ")";
    $weeklyData[]   = $row['count'];
}

// MONTHLY DATA: Count patients for each day in the current month,
// and calculate the week number within the month.
$queryMonthly = "SELECT
                    visit_date,
                    DAYNAME(visit_date) as day_name,
                    WEEK(visit_date, 1) - WEEK(DATE_SUB(visit_date, INTERVAL DAYOFMONTH(visit_date)-1 DAY), 1) + 1 as week_in_month, 
                    COUNT(*) as count
                 FROM patient_visits
                 WHERE YEAR(visit_date) = '$year' AND MONTH(visit_date) = '$month'
                 GROUP BY visit_date
                 ORDER BY visit_date ASC;";
$stmtMonthly = $con->prepare($queryMonthly);
$stmtMonthly->execute();
$monthlyLabels = [];
$monthlyData   = [];
while($row = $stmtMonthly->fetch(PDO::FETCH_ASSOC)) {
    $formattedDate = date("F j", strtotime($row['visit_date']));
    $monthlyLabels[] = "W" . $row['week_in_month'] . " - " . $row['day_name'] . " (" . $formattedDate . ")";
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

    .chart-select {
      width: 100%;
      padding: 0.5rem 1rem;
      font-size: 1rem;
      font-weight: 500;
      color: #333;
      background-color: #fff; /* default white background */
      border: 1px solid #ccc;
      border-radius: 4px;
      appearance: none;
      background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg width='12' height='12' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M6 9L1 4h10L6 9z' fill='%23333'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 1rem center;
      background-size: 12px 12px;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .chart-select:focus {
      outline: none;
      border-color: #007bff;
      box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }

    /* Active state classes */
    .chart-select.active-weekly {
      background-color: rgba(128, 0, 128, 0.7); /* Purple */
      color: #fff;
    }

    .chart-select.active-monthly {
      background-color: rgba(255, 0, 255, 0.7); /* Fuchsia */
      color: #fff;
    }

    .chart-select.active-yearly {
      background-color: rgba(128, 0, 0, 0.7); /* Maroon */
      color: #fff;
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
              <h4><span id="datetime" class="badge"></span></h4>
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
                <select id="chartType" class="chart-select">
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

    document.addEventListener("DOMContentLoaded", function() {
  // Function to update the active class on the dropdown
  function updateDropdownActiveClass(value) {
    const selectEl = document.getElementById("chartType");
    // Remove any active state classes
    selectEl.classList.remove("active-weekly", "active-monthly", "active-yearly");
    // Add the active class based on the selected value
    if (value === "weekly") {
      selectEl.classList.add("active-weekly");
    } else if (value === "monthly") {
      selectEl.classList.add("active-monthly");
    } else if (value === "yearly") {
      selectEl.classList.add("active-yearly");
    }
  }

// Render chart function remains the same:
function renderChart(type) {
  if (currentChart) {
    currentChart.destroy();
  }
  
  let backgroundColor, borderColor;
  if (type === "weekly") {
    backgroundColor = "rgba(128, 0, 128, 0.7)"; // Purple
    borderColor = "rgba(128, 0, 128, 1)";
  } else if (type === "monthly") {
    backgroundColor = "rgba(255, 0, 255, 0.7)"; // Fuchsia
    borderColor = "rgba(255, 0, 255, 1)";
  } else if (type === "yearly") {
    backgroundColor = "rgba(128, 0, 0, 0.7)";   // Maroon
    borderColor = "rgba(128, 0, 0, 1)";
  } else {
    backgroundColor = "rgba(0, 123, 255, 0.7)";
    borderColor = "rgba(0, 123, 255, 1)";
  }
  
  currentChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartData[type].labels,
      datasets: [{
        label: "Number of Patients",
        data: chartData[type].data,
        backgroundColor: backgroundColor,
        borderColor: borderColor,
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

// Initial render using weekly data and set the active class on the dropdown
renderChart("weekly");
  updateDropdownActiveClass("weekly");

  document.getElementById("chartType").addEventListener("change", function() {
    renderChart(this.value);
    updateDropdownActiveClass(this.value);
  });
});
  </script>
</body>
</html>
