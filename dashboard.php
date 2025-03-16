<?php
// Ensure the correct timezone is set
date_default_timezone_set('Asia/Manila');

include './config/connection.php';

// Set current date components used throughout the queries
$date  = date('Y-m-d');
$year  = date('Y');
$month = date('m');

// Existing queries for stat boxes

// Query for today's patient visits
$queryToday = "SELECT count(*) as `today`
  FROM `patient_visits`
  WHERE DATE(`created_at`) = '$date';";

// Query for current week visits
$queryWeek = "SELECT count(*) as `week`
  FROM `patient_visits`
  WHERE YEARWEEK(`visit_date`, 1) = YEARWEEK('$date', 1);";

// Query for current year visits
$queryYear = "SELECT count(*) as `year`
  FROM `patient_visits`
  WHERE YEAR(`visit_date`) = YEAR('$date');";

// Query for current month visits
$queryMonth = "SELECT count(*) as `month`
  FROM `patient_visits`
  WHERE YEAR(`visit_date`) = $year
    AND MONTH(`visit_date`) = $month;";

// Initialize counts to default values
$todaysCount       = 0;
$currentWeekCount  = 0;
$currentMonthCount = 0;
$currentYearCount  = 0;

try {
    // Execute query for today's patient count
    $stmtToday = $con->prepare($queryToday);
    $stmtToday->execute();
    $r = $stmtToday->fetch(PDO::FETCH_ASSOC);
    $todaysCount = $r['today'];

    // Execute query for current week's patient count
    $stmtWeek = $con->prepare($queryWeek);
    $stmtWeek->execute();
    $r = $stmtWeek->fetch(PDO::FETCH_ASSOC);
    $currentWeekCount = $r['week'];

    // Execute query for current year's patient count
    $stmtYear = $con->prepare($queryYear);
    $stmtYear->execute();
    $r = $stmtYear->fetch(PDO::FETCH_ASSOC);
    $currentYearCount = $r['year'];

    // Execute query for current month's patient count
    $stmtMonth = $con->prepare($queryMonth);
    $stmtMonth->execute();
    $r = $stmtMonth->fetch(PDO::FETCH_ASSOC);
    $currentMonthCount = $r['month'];
} catch(PDOException $ex) {
    // For production, consider logging errors rather than echoing detailed messages
    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
}

/* Dynamic Data for Chart */

// WEEKLY DATA: Count patients for the current week grouped by day name,
// and also retrieve the earliest visit_date for each day
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
    // Format the earliest visit date for each day as "Month Day"
    $formattedDate = date("F j", strtotime($row['first_date']));
    $weeklyLabels[] = $row['day'] . " - (" . $formattedDate . ")";
    $weeklyData[]   = $row['count'];
}

// MONTHLY DATA: Count patients for each day in the current month,
// and calculate the week number within the month
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
    // Format the visit date and include the week number and day name in the label
    $formattedDate = date("F j", strtotime($row['visit_date']));
    $monthlyLabels[] = "W" . $row['week_in_month'] . " - " . $row['day_name'] . " (" . $formattedDate . ")";
    $monthlyData[]   = $row['count'];
}

// YEARLY DATA: Count patients for the current year grouped by month
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
    // Convert month number to full month name
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
    /* Dark mode overrides for specific background classes */
    .dark-mode .bg-fuchsia, .dark-mode .bg-maroon {
      color: #fff!important;
    }

    /* Styling for the chart type dropdown */
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
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg width='12' height='12' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M6 9L1 4h10L6 9z' fill='%23333'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 1rem center;
      background-size: 12px 12px;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .chart-select:focus {
      outline: none;
      border-color: #007bff;
      box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }

    /* Active state classes with updated colors */
    .chart-select.active-weekly {
      background-color: rgba(54, 162, 235, 0.7); /* Soft Blue */
      color: #fff;
    }

    .chart-select.active-monthly {
      background-color: rgba(255, 159, 64, 0.7); /* Warm Orange */
      color: #fff;
    }

    .chart-select.active-yearly {
      background-color: rgba(75, 192, 192, 0.7); /* Cool Teal */
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
            <!-- Dashboard Title -->
            <div class="col-12 col-md-6 text-md-left text-center">
              <h1>Dashboard</h1>
            </div>
            <!-- Date & Time (Responsive) -->
            <div class="col-12 col-md-6 text-md-right text-center">
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
              <div class="small-box bg-success">
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
              <div class="small-box bg-primary">
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
              <div class="small-box bg-warning text-dark">
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
              <div class="small-box bg-danger">
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
                <!-- Dropdown to select chart type -->
                <select id="chartType" class="chart-select">
                  <option value="weekly">Weekly Breakdown</option>
                  <option value="monthly">Monthly Breakdown</option>
                  <option value="yearly">Yearly Breakdown</option>
                </select>
              </div>
            </div>
            <div class="row my-3">
              <div class="col">
                <!-- Canvas for rendering the Chart.js chart -->
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
    // Highlight the active menu item on page load
    $(function(){
      showMenuSelected("#mnu_dashboard", "");
    });
    
    // JavaScript to update date and time every second in the desired format
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

    // Dynamic chart data retrieved from PHP arrays and encoded as JSON
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
      // Function to update the active class on the dropdown element based on selected chart type
      function updateDropdownActiveClass(value) {
        const selectEl = document.getElementById("chartType");
        // Remove any active state classes
        selectEl.classList.remove("active-weekly", "active-monthly", "active-yearly");
        // Add the appropriate active class based on the value
        if (value === "weekly") {
          selectEl.classList.add("active-weekly");
        } else if (value === "monthly") {
          selectEl.classList.add("active-monthly");
        } else if (value === "yearly") {
          selectEl.classList.add("active-yearly");
        }
      }

      // Render chart function with updated color schemes based on selected chart type
      function renderChart(type) {
        if (currentChart) {
          currentChart.destroy();
        }
        
        let backgroundColor, borderColor;
        if (type === "weekly") {
          backgroundColor = "rgba(54, 162, 235, 0.7)"; // Soft Blue
          borderColor = "rgba(54, 162, 235, 1)";
        } else if (type === "monthly") {
          backgroundColor = "rgba(255, 159, 64, 0.7)"; // Warm Orange
          borderColor = "rgba(255, 159, 64, 1)";
        } else if (type === "yearly") {
          backgroundColor = "rgba(75, 192, 192, 0.7)";   // Cool Teal
          borderColor = "rgba(75, 192, 192, 1)";
        } else {
          backgroundColor = "rgba(0, 123, 255, 0.7)";
          borderColor = "rgba(0, 123, 255, 1)";
        }
        
        // Instantiate the Chart.js bar chart
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

      // Update the chart when the dropdown selection changes
      document.getElementById("chartType").addEventListener("change", function() {
        renderChart(this.value);
        updateDropdownActiveClass(this.value);
      });
    });
  </script>
</body>
</html>
