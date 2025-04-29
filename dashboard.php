<?php
// Ensure the correct timezone is set
date_default_timezone_set('Asia/Manila');

include './config/connection.php';

// Set current date components used throughout the queries
$date  = date('Y-m-d');
$year  = date('Y');
$month = date('m');

// Query for today's total visits (combining all services)
$queryToday = "SELECT 
    (SELECT COUNT(*) FROM bp_monitoring WHERE DATE(date) = :date) +
    (SELECT COUNT(*) FROM family_planning WHERE DATE(date) = :date) +
    (SELECT COUNT(*) FROM tetanus_toxoid WHERE DATE(date) = :date) +
    (SELECT COUNT(*) FROM random_blood_sugar WHERE DATE(date) = :date) +
    (SELECT COUNT(*) FROM deworming WHERE DATE(date) = :date) +
    (SELECT COUNT(*) FROM family_members WHERE DATE(date) = :date) as today";

// Query for current week visits
$queryWeek = "SELECT 
    (SELECT COUNT(*) FROM bp_monitoring WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)) +
    (SELECT COUNT(*) FROM family_planning WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)) +
    (SELECT COUNT(*) FROM tetanus_toxoid WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)) +
    (SELECT COUNT(*) FROM random_blood_sugar WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)) +
    (SELECT COUNT(*) FROM deworming WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)) +
    (SELECT COUNT(*) FROM family_members WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)) as week";

// Query for current month visits
$queryMonth = "SELECT 
    (SELECT COUNT(*) FROM bp_monitoring WHERE YEAR(date) = :year AND MONTH(date) = :month) +
    (SELECT COUNT(*) FROM family_planning WHERE YEAR(date) = :year AND MONTH(date) = :month) +
    (SELECT COUNT(*) FROM tetanus_toxoid WHERE YEAR(date) = :year AND MONTH(date) = :month) +
    (SELECT COUNT(*) FROM random_blood_sugar WHERE YEAR(date) = :year AND MONTH(date) = :month) +
    (SELECT COUNT(*) FROM deworming WHERE YEAR(date) = :year AND MONTH(date) = :month) +
    (SELECT COUNT(*) FROM family_members WHERE YEAR(date) = :year AND MONTH(date) = :month) as month";

// Query for current year visits
$queryYear = "SELECT 
    (SELECT COUNT(*) FROM bp_monitoring WHERE YEAR(date) = :year) +
    (SELECT COUNT(*) FROM family_planning WHERE YEAR(date) = :year) +
    (SELECT COUNT(*) FROM tetanus_toxoid WHERE YEAR(date) = :year) +
    (SELECT COUNT(*) FROM random_blood_sugar WHERE YEAR(date) = :year) +
    (SELECT COUNT(*) FROM deworming WHERE YEAR(date) = :year) +
    (SELECT COUNT(*) FROM family_members WHERE YEAR(date) = :year) as year";

// Initialize counts to default values
$todaysCount = 0;
$currentWeekCount = 0;
$currentMonthCount = 0;
$currentYearCount = 0;

try {
    // Execute query for today's count
    $stmtToday = $con->prepare($queryToday);
    $stmtToday->bindParam(':date', $date);
    $stmtToday->execute();
    $r = $stmtToday->fetch(PDO::FETCH_ASSOC);
    $todaysCount = $r['today'];

    // Execute query for current week's count
    $stmtWeek = $con->prepare($queryWeek);
    $stmtWeek->bindParam(':date', $date);
    $stmtWeek->execute();
    $r = $stmtWeek->fetch(PDO::FETCH_ASSOC);
    $currentWeekCount = $r['week'];

    // Execute query for current month's count
    $stmtMonth = $con->prepare($queryMonth);
    $stmtMonth->bindParam(':year', $year);
    $stmtMonth->bindParam(':month', $month);
    $stmtMonth->execute();
    $r = $stmtMonth->fetch(PDO::FETCH_ASSOC);
    $currentMonthCount = $r['month'];

    // Execute query for current year's count
    $stmtYear = $con->prepare($queryYear);
    $stmtYear->bindParam(':year', $year);
    $stmtYear->execute();
    $r = $stmtYear->fetch(PDO::FETCH_ASSOC);
    $currentYearCount = $r['year'];

} catch(PDOException $ex) {
    // For production, consider logging errors rather than echoing detailed messages
    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
}

/* Dynamic Data for Chart */

// WEEKLY DATA: Count all visits for the current week grouped by day
$queryWeekly = "SELECT 
    DAYNAME(date) as day,
    MIN(date) as first_date,
    COUNT(*) as count
FROM (
    SELECT date FROM bp_monitoring WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)
    UNION ALL
    SELECT date FROM family_planning WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)
    UNION ALL
    SELECT date FROM tetanus_toxoid WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)
    UNION ALL
    SELECT date FROM random_blood_sugar WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)
    UNION ALL
    SELECT date FROM deworming WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)
    UNION ALL
    SELECT date FROM family_members WHERE YEARWEEK(date, 1) = YEARWEEK(:date, 1)
) as combined_data
GROUP BY DAYNAME(date)
ORDER BY FIELD(DAYNAME(date), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";

$stmtWeekly = $con->prepare($queryWeekly);
$stmtWeekly->bindParam(':date', $date);
$stmtWeekly->execute();
$weeklyLabels = [];
$weeklyData = [];
while($row = $stmtWeekly->fetch(PDO::FETCH_ASSOC)) {
    $formattedDate = date("F j", strtotime($row['first_date']));
    $weeklyLabels[] = $row['day'] . " - (" . $formattedDate . ")";
    $weeklyData[] = $row['count'];
}

// MONTHLY DATA: Count all visits for each day in the current month
$queryMonthly = "SELECT 
    date,
    DAYNAME(date) as day_name,
    WEEK(date, 1) - WEEK(DATE_SUB(date, INTERVAL DAYOFMONTH(date)-1 DAY), 1) + 1 as week_in_month,
    COUNT(*) as count
FROM (
    SELECT date FROM bp_monitoring WHERE YEAR(date) = :year AND MONTH(date) = :month
    UNION ALL
    SELECT date FROM family_planning WHERE YEAR(date) = :year AND MONTH(date) = :month
    UNION ALL
    SELECT date FROM tetanus_toxoid WHERE YEAR(date) = :year AND MONTH(date) = :month
    UNION ALL
    SELECT date FROM random_blood_sugar WHERE YEAR(date) = :year AND MONTH(date) = :month
    UNION ALL
    SELECT date FROM deworming WHERE YEAR(date) = :year AND MONTH(date) = :month
    UNION ALL
    SELECT date FROM family_members WHERE YEAR(date) = :year AND MONTH(date) = :month
) as combined_data
GROUP BY date
ORDER BY date ASC";

$stmtMonthly = $con->prepare($queryMonthly);
$stmtMonthly->bindParam(':year', $year);
$stmtMonthly->bindParam(':month', $month);
$stmtMonthly->execute();
$monthlyLabels = [];
$monthlyData = [];
while($row = $stmtMonthly->fetch(PDO::FETCH_ASSOC)) {
    $formattedDate = date("F j", strtotime($row['date']));
    $monthlyLabels[] = "W" . $row['week_in_month'] . " - " . $row['day_name'] . " (" . $formattedDate . ")";
    $monthlyData[] = $row['count'];
}

// YEARLY DATA: Count all visits for the current year grouped by month
$queryYearly = "SELECT 
    MONTH(date) as month,
    COUNT(*) as count
FROM (
    SELECT date FROM bp_monitoring WHERE YEAR(date) = :year
    UNION ALL
    SELECT date FROM family_planning WHERE YEAR(date) = :year
    UNION ALL
    SELECT date FROM tetanus_toxoid WHERE YEAR(date) = :year
    UNION ALL
    SELECT date FROM random_blood_sugar WHERE YEAR(date) = :year
    UNION ALL
    SELECT date FROM deworming WHERE YEAR(date) = :year
    UNION ALL
    SELECT date FROM family_members WHERE YEAR(date) = :year
) as combined_data
GROUP BY MONTH(date)
ORDER BY month ASC";

$stmtYearly = $con->prepare($queryYearly);
$stmtYearly->bindParam(':year', $year);
$stmtYearly->execute();
$yearlyLabels = [];
$yearlyData = [];
while($row = $stmtYearly->fetch(PDO::FETCH_ASSOC)) {
    $monthName = date("F", mktime(0, 0, 0, $row['month'], 10));
    $yearlyLabels[] = $monthName;
    $yearlyData[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <link rel="stylesheet" href="system_styles/dashboard.css">
  <title>Dashboard - Mamatid Health Center System</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <div class="wrapper">
    <?php
      include './config/header.php';
      include './config/sidebar.php';
    ?>
    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row align-items-center mb-4">
            <div class="col-12 col-md-6" style="padding-left: 20px;">
              <h1>Dashboard Overview</h1>
            </div>
            <div class="col-12 col-md-6 text-md-right mt-3 mt-md-0">
              <span id="datetime" class="d-inline-block"></span>
            </div>
          </div>
        </div>
      </section>
      
      <section class="content">
        <div class="container-fluid">
          <!-- Stat Boxes -->
          <div class="row">
            <div class="col-lg-3 col-md-6 col-12 mb-4">
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
            <div class="col-lg-3 col-md-6 col-12 mb-4">
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
            <div class="col-lg-3 col-md-6 col-12 mb-4">
              <div class="small-box bg-warning">
                <div class="inner">
                  <h3><?php echo $currentMonthCount; ?></h3>
                  <p>Current Month</p>
                </div>
                <div class="icon">
                  <i class="fa fa-calendar"></i>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-12 mb-4">
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

          <!-- Chart Section -->
          <div class="chart-container">
            <div class="row mb-4 align-items-center">
              <div class="col-md-4">
                <div class="chart-select-wrapper">
                <select id="chartType" class="chart-select">
                  <option value="weekly">Weekly Patient Statistics</option>
                  <option value="monthly">Monthly Patient Statistics</option>
                  <option value="yearly">Yearly Patient Statistics</option>
                </select>
                  <div class="chart-select-icon">
                    <i class="fas fa-chart-bar"></i>
              </div>
            </div>
              </div>
              <div class="col-md-6">
                <div class="chart-period-info">
                  <span class="period-label">Current Period:</span>
                  <span class="period-value" id="currentPeriod">This Week</span>
                  <span class="period-dates" id="periodDates"></span>
                </div>
              </div>
              <div class="col-md-2 text-right">
                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-md-3">
                <div class="stat-card">
                  <div class="stat-card-content">
                    <div class="stat-card-info">
                      <h4 class="stat-card-title">Total Patients</h4>
                      <p class="stat-card-value" id="totalPatients">0</p>
                    </div>
                    <div class="stat-card-icon">
                      <i class="fas fa-users"></i>
                    </div>
                  </div>
                  <div class="stat-card-footer">
                    <span class="trend-indicator" id="totalTrend">
                      <i class="fas fa-arrow-up"></i> 0%
                    </span>
                    <span class="trend-period">vs previous period</span>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="stat-card">
                  <div class="stat-card-content">
                    <div class="stat-card-info">
                      <h4 class="stat-card-title">Average Daily</h4>
                      <p class="stat-card-value" id="avgDaily">0</p>
                    </div>
                    <div class="stat-card-icon">
                      <i class="fas fa-chart-line"></i>
                    </div>
                  </div>
                  <div class="stat-card-footer">
                    <span class="trend-indicator" id="avgTrend">
                      <i class="fas fa-arrow-up"></i> 0%
                    </span>
                    <span class="trend-period">vs previous period</span>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="stat-card">
                  <div class="stat-card-content">
                    <div class="stat-card-info">
                      <h4 class="stat-card-title">Peak Day</h4>
                      <p class="stat-card-value" id="peakDay">-</p>
                    </div>
                    <div class="stat-card-icon">
                      <i class="fas fa-calendar-check"></i>
                    </div>
                  </div>
                  <div class="stat-card-footer">
                    <span class="peak-patients" id="peakPatients">0 patients</span>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="stat-card">
                  <div class="stat-card-content">
                    <div class="stat-card-info">
                      <h4 class="stat-card-title">Growth Rate</h4>
                      <p class="stat-card-value" id="growthRate">0%</p>
                    </div>
                    <div class="stat-card-icon">
                      <i class="fas fa-chart-pie"></i>
                    </div>
                  </div>
                  <div class="stat-card-footer">
                    <span class="trend-period">Month over Month</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="chart-wrapper">
                  <div id="chartLoader" class="chart-loader d-none">
                    <div class="spinner-border text-primary" role="status">
                      <span class="sr-only">Loading...</span>
                    </div>
                  </div>
                <canvas id="patientChart"></canvas>
              </div>
                <div class="chart-footer mt-3">
                  <div class="row align-items-center">
                    <div class="col-md-6">
                      <div class="chart-legend">
                        <span class="legend-item">
                          <span class="legend-dot"></span>
                          Patient Visits
                        </span>
                        <span class="legend-item">
                          <span class="legend-line"></span>
                          Trend Line
                        </span>
            </div>
          </div>
                    <div class="col-md-6 text-right">
                      <div class="chart-actions">
                        <button class="btn btn-gradient btn-sm export-btn" id="btnCopy">
                          <i class="fas fa-copy"></i> Copy
                        </button>
                        <button class="btn btn-gradient btn-sm export-btn" id="btnCSV">
                          <i class="fas fa-file-csv"></i> CSV
                        </button>
                        <button class="btn btn-gradient btn-sm export-btn" id="btnExcel">
                          <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="btn btn-gradient btn-sm export-btn" id="btnPDF">
                          <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="btn btn-gradient btn-sm export-btn" id="btnPrint">
                          <i class="fas fa-print"></i> Print
                        </button>
                      </div>                     
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

      <!-- History Analytics Section -->
          <div class="history-analytics-container chart-container mt-4">
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="history-select-wrapper">
                <select id="historyType" class="chart-select">
                  <option value="" data-color="#000000">Select History Type</option>
                    <option value="family_members" data-color="#8B5CF6">Family Members History</option>
                  <option value="bp" data-color="#EF4444">Blood Pressure History</option>
                  <option value="blood_sugar" data-color="#3B82F6">Blood Sugar History</option>
                  <option value="tetanus" data-color="#EC4899">Tetanus History</option>
                  <option value="deworming" data-color="#10B981">Deworming History</option>
                  <option value="family" data-color="#F59E0B">Family Planning History</option>
                </select>
                  <div class="history-select-icon">
                    <i class="fas fa-chart-line"></i>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="history-select-wrapper">
                <select id="historyDateRange" class="chart-select">
                  <option value="7">Last 7 Days</option>
                  <option value="14">Last 14 Days</option>
                  <option value="30">Last 30 Days</option>
                  <option value="90">Last 90 Days</option>
                  <option value="0">All Data</option>
                </select>
                  <div class="history-select-icon">
                    <i class="fas fa-calendar-alt"></i>
                  </div>
                </div>
              </div>
              <div class="col-md-2 text-right">
                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="history-chart-wrapper">
                  <div id="historyChartLoader" class="chart-loader d-none">
                    <div class="spinner-border text-primary" role="status">
                      <span class="sr-only">Loading...</span>
                    </div>
                  </div>
                <canvas id="historyChart"></canvas>
                </div>
                <div class="chart-footer mt-3">
                  <div class="row align-items-center">
                    <div class="col-md-6">
                      <div class="chart-legend">
                        <span class="legend-item">
                          <span class="legend-dot"></span>
                          History Data
                        </span>
                        <span class="legend-item">
                          <span class="legend-line"></span>
                          Trend Line
                        </span>
                      </div>
                    </div>
                    <div class="col-md-6 text-right">
                      <div class="chart-actions">
                        <button class="btn btn-gradient btn-sm export-btn" id="historyBtnCopy">
                          <i class="fas fa-copy"></i> Copy
                        </button>
                        <button class="btn btn-gradient btn-sm export-btn" id="historyBtnCSV">
                          <i class="fas fa-file-csv"></i> CSV
                        </button>
                        <button class="btn btn-gradient btn-sm export-btn" id="historyBtnExcel">
                          <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="btn btn-gradient btn-sm export-btn" id="historyBtnPDF">
                          <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="btn btn-gradient btn-sm export-btn" id="historyBtnPrint">
                          <i class="fas fa-print"></i> Print
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
    <?php include './config/footer.php'; ?>
  </div>
  <?php include './config/site_js_links.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Highlight the active menu item on page load
    $(function(){
      showMenuSelected("#mnu_dashboard", "");
    });
    
    // Modern datetime display with animation
    function updateDateTime() {
      var now = new Date();
      var options = {
        month: 'long',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
      };
      var formattedDateTime = now.toLocaleString('en-US', options);
      document.getElementById('datetime').innerHTML = formattedDateTime;
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Chart configuration and data
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
      // Chart theme colors
      const chartThemes = {
        weekly: {
          gradient: ctx.createLinearGradient(0, 0, 0, 400),
          border: '#3498db',
          label: 'Weekly Patient Visits'
        },
        monthly: {
          gradient: ctx.createLinearGradient(0, 0, 0, 400),
          border: '#f39c12',
          label: 'Monthly Patient Visits'
        },
        yearly: {
          gradient: ctx.createLinearGradient(0, 0, 0, 400),
          border: '#2ecc71',
          label: 'Yearly Patient Visits'
        }
      };

      // Set up gradients with more opacity
      chartThemes.weekly.gradient.addColorStop(0, 'rgba(52, 152, 219, 0.6)');
      chartThemes.weekly.gradient.addColorStop(1, 'rgba(52, 152, 219, 0.1)');
      
      chartThemes.monthly.gradient.addColorStop(0, 'rgba(243, 156, 18, 0.6)');
      chartThemes.monthly.gradient.addColorStop(1, 'rgba(243, 156, 18, 0.1)');
      
      chartThemes.yearly.gradient.addColorStop(0, 'rgba(46, 204, 113, 0.6)');
      chartThemes.yearly.gradient.addColorStop(1, 'rgba(46, 204, 113, 0.1)');

      function updateDropdownActiveClass(value) {
        const selectEl = document.getElementById("chartType");
        selectEl.classList.remove("active-weekly", "active-monthly", "active-yearly");
        selectEl.classList.add(`active-${value}`);
      }

      // Function to calculate percentage change
      function calculatePercentageChange(current, previous) {
        if (previous === 0) return current > 0 ? 100 : 0;
        if (previous === undefined || current === undefined) return 0;
        return ((current - previous) / previous) * 100;
      }

      // Function to format percentage
      function formatPercentage(value) {
        if (isNaN(value) || !isFinite(value)) return '0';
        return value.toFixed(1);
      }

      // Function to update statistics
      function updateStatistics(type, data) {
        // Calculate total for current period
        const total = data.reduce((sum, val) => sum + val, 0);
        
        // Calculate average
        const avg = total / (data.length || 1);
        
        // Find peak day
        const maxValue = Math.max(...data);
        const maxIndex = data.indexOf(maxValue);
        
        // Split data into current and previous periods
        const midPoint = Math.floor(data.length / 2);
        const currentPeriod = data.slice(midPoint);
        const previousPeriod = data.slice(0, midPoint);
        
        // Calculate totals for each period
        const currentTotal = currentPeriod.reduce((sum, val) => sum + val, 0);
        const previousTotal = previousPeriod.reduce((sum, val) => sum + val, 0);
        
        // Calculate averages for each period
        const currentAvg = currentTotal / (currentPeriod.length || 1);
        const previousAvg = previousTotal / (previousPeriod.length || 1);
        
        // Calculate growth rates
        const totalGrowthRate = calculatePercentageChange(currentTotal, previousTotal);
        const avgGrowthRate = calculatePercentageChange(currentAvg, previousAvg);
        
        // Update display values
        document.getElementById('totalPatients').textContent = total;
        document.getElementById('avgDaily').textContent = avg.toFixed(1);
        document.getElementById('peakDay').textContent = data.length > 0 ? chartData[type].labels[maxIndex] : 'N/A';
        document.getElementById('peakPatients').textContent = maxValue > 0 ? `${maxValue} patients` : 'No data';
        
        // Update growth rate displays
        const totalTrend = document.getElementById('totalTrend');
        const avgTrend = document.getElementById('avgTrend');
        const growthRateElement = document.getElementById('growthRate');
        
        // Update total trend
        if (totalGrowthRate > 0) {
            totalTrend.classList.add('positive');
            totalTrend.classList.remove('negative');
            totalTrend.innerHTML = `<i class="fas fa-arrow-up"></i> ${formatPercentage(totalGrowthRate)}%`;
        } else if (totalGrowthRate < 0) {
            totalTrend.classList.add('negative');
            totalTrend.classList.remove('positive');
            totalTrend.innerHTML = `<i class="fas fa-arrow-down"></i> ${formatPercentage(Math.abs(totalGrowthRate))}%`;
        } else {
            totalTrend.classList.remove('positive', 'negative');
            totalTrend.innerHTML = `<i class="fas fa-minus"></i> 0%`;
        }
        
        // Update average trend
        if (avgGrowthRate > 0) {
            avgTrend.classList.add('positive');
            avgTrend.classList.remove('negative');
            avgTrend.innerHTML = `<i class="fas fa-arrow-up"></i> ${formatPercentage(avgGrowthRate)}%`;
        } else if (avgGrowthRate < 0) {
            avgTrend.classList.add('negative');
            avgTrend.classList.remove('positive');
            avgTrend.innerHTML = `<i class="fas fa-arrow-down"></i> ${formatPercentage(Math.abs(avgGrowthRate))}%`;
        } else {
            avgTrend.classList.remove('positive', 'negative');
            avgTrend.innerHTML = `<i class="fas fa-minus"></i> 0%`;
        }
        
        // Update overall growth rate
        growthRateElement.textContent = `${formatPercentage(totalGrowthRate)}%`;

        // Update period info
        const periodTypeElement = document.getElementById('currentPeriod');
        const periodDatesElement = document.getElementById('periodDates');
        
        switch(type) {
            case 'weekly':
                periodTypeElement.textContent = 'This Week';
                if (data.length > 0) {
                    periodDatesElement.textContent = `(${chartData[type].labels[0]} - ${chartData[type].labels[chartData[type].labels.length-1]})`;
                } else {
                    periodDatesElement.textContent = '(No data)';
                }
                break;
            case 'monthly':
                periodTypeElement.textContent = 'This Month';
                periodDatesElement.textContent = `(${new Date().toLocaleString('default', { month: 'long', year: 'numeric' })})`;
                break;
            case 'yearly':
                periodTypeElement.textContent = 'This Year';
                periodDatesElement.textContent = `(${new Date().getFullYear()})`;
                break;
        }
      }

      function renderChart(type) {
        if (currentChart) {
          currentChart.destroy();
        }
        
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(0, 0, 0, 0.8)');
        gradient.addColorStop(1, 'rgba(255, 255, 255, 0.1)');
        
        // Update statistics before rendering chart
        updateStatistics(type, chartData[type].data);
        
        const theme = chartThemes[type];
        
        currentChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: chartData[type].labels,
            datasets: [{
              label: theme.label,
              data: chartData[type].data,
              backgroundColor: gradient,
              borderColor: 'rgba(0, 0, 0, 0.8)',
              borderWidth: 2,
              borderRadius: 12,
              borderSkipped: false,
              maxBarThickness: 40,
              minBarLength: 5
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
              duration: 1000,
              easing: 'easeInOutQuart',
              delay: function(context) {
                return context.dataIndex * 100;
              }
            },
            layout: {
              padding: {
                left: 20,
                right: 20,
                top: 20,
                bottom: 20
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: 'rgba(0, 0, 0, 0.06)',
                  drawBorder: false,
                  lineWidth: 1
                },
                ticks: {
                  font: {
                    size: 12,
                    family: "'Inter', sans-serif",
                    weight: '500'
                  },
                  color: '#4a4a4a',
                  padding: 10
                }
              },
              x: {
                grid: {
                  display: false
                },
                ticks: {
                  maxRotation: 45,
                  minRotation: 45,
                  font: {
                    size: 11,
                    family: "'Inter', sans-serif",
                    weight: '500'
                  },
                  color: '#4a4a4a',
                  padding: 10
                }
              }
            },
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                enabled: true,
                backgroundColor: 'rgba(255, 255, 255, 0.98)',
                titleColor: '#1e293b',
                bodyColor: '#475569',
                bodyFont: {
                  size: 13,
                  family: "'Inter', sans-serif"
                },
                titleFont: {
                  size: 14,
                  family: "'Inter', sans-serif",
                  weight: '600'
                },
                padding: {
                  x: 15,
                  y: 12
                },
                cornerRadius: 12,
                displayColors: false,
                borderColor: 'rgba(0, 0, 0, 0.1)',
                borderWidth: 1
              }
            }
          }
        });
      }

      // Initial render
      renderChart("weekly");
      updateDropdownActiveClass("weekly");

      // Update chart on dropdown change
      document.getElementById("chartType").addEventListener("change", function() {
        renderChart(this.value);
        updateDropdownActiveClass(this.value);
      });
    });

    // Update History Analytics Chart Configuration
    let historyChart;
    const historyCtx = document.getElementById('historyChart').getContext('2d');

    // Define colors for different history types
    const historyTypeColors = {
        'family_members': 'rgba(139, 92, 246, 1)', // Purple
        'bp': 'rgba(239, 68, 68, 1)', // Red
        'blood_sugar': 'rgba(59, 130, 246, 1)', // Blue
        'tetanus': 'rgba(236, 72, 153, 1)', // Pink
        'deworming': 'rgba(16, 185, 129, 1)', // Green
        'family': 'rgba(245, 158, 11, 1)' // Orange
    };

    function getHistoryTypeColor(type) {
        const colors = {
            'bp': 'rgba(239, 68, 68, 1)',
            'blood_sugar': 'rgba(16, 185, 129, 1)',
            'tetanus': 'rgba(59, 130, 246, 1)',
            'family_members': 'rgba(139, 92, 246, 1)',
            'family_planning': 'rgba(245, 158, 11, 1)',
            'deworming': 'rgba(6, 182, 212, 1)'
        };
        return colors[type] || 'rgba(107, 114, 128, 1)';
    }

    function showLoader() {
        document.getElementById('historyChartLoader').classList.remove('d-none');
    }

    function hideLoader() {
        document.getElementById('historyChartLoader').classList.add('d-none');
    }

    function updateHistoryChart(type, days) {
        if (historyChart) {
            historyChart.destroy();
        }

        showLoader();
        
        console.log(`Fetching history data for type: ${type}, days: ${days}`);
        
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        fetch(`ajax/get_${type}_history.php?days=${days}&t=${timestamp}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                hideLoader();
                
                if (data.error) {
                    console.error('Server error:', data.error);
                    return;
                }
                
                // Handle BP data format differently
                if (type === 'bp') {
                    if (!data.labels || !data.bp_values || !data.patient_counts) {
                        console.error('Invalid BP data format received:', data);
                        return;
                    }
                } else {
                    if (!data.labels || !data.values) {
                        console.error('Invalid data format received:', data);
                        return;
                    }
                }
                
                // Create gradient
                const gradient = historyCtx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(0, 0, 0, 0.8)');
                gradient.addColorStop(1, 'rgba(255, 255, 255, 0.1)');
                
                const chartConfig = {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12,
                                    family: "'Inter', sans-serif",
                                    weight: '500'
                                },
                                color: '#1a1a1a'
                            }
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(255, 255, 255, 0.98)',
                            titleColor: '#1e293b',
                            bodyColor: '#475569',
                            borderColor: 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                            bodyFont: {
                                size: 13,
                                family: "'Inter', sans-serif"
                            },
                            titleFont: {
                                size: 14,
                                family: "'Inter', sans-serif",
                                weight: '600'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                padding: 10,
                                font: {
                                    size: 11,
                                    family: "'Inter', sans-serif",
                                    weight: '500'
                                },
                                color: '#4a4a4a'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.06)',
                                drawBorder: false
                            },
                            ticks: {
                                padding: 10,
                                font: {
                                    size: 12,
                                    family: "'Inter', sans-serif",
                                    weight: '500'
                                },
                                color: '#4a4a4a'
                            }
                        }
                    }
                };
                
                if (type === 'bp') {
                    // Special configuration for BP chart
                    historyChart = new Chart(historyCtx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: 'Average Blood Pressure',
                                    data: data.bp_values,
                                    borderColor: getHistoryTypeColor('bp'),
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    borderWidth: 2,
                                    fill: false,
                                    tension: 0.4
                                },
                                {
                                    label: 'Number of Patients',
                                    data: data.patient_counts,
                                    borderColor: 'rgba(59, 130, 246, 1)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    borderWidth: 2,
                                    type: 'bar'
                                }
                            ]
                        },
                        options: chartConfig
                    });
                } else {
                    // Standard configuration for other charts
                    const chartTitle = type.split('_')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                        .join(' ');

                    historyChart = new Chart(historyCtx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: `${chartTitle} History`,
                                data: data.values,
                                borderColor: getHistoryTypeColor(type),
                                backgroundColor: `${getHistoryTypeColor(type).replace('1)', '0.1)')}`,
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: chartConfig
                    });
                }
                
                // Now that we have data, update the column visibility
                try {
                    initializeColumnVisibility();
                } catch (err) {
                    console.warn('Column visibility initialization error:', err);
                }
            })
            .catch(error => {
                console.error('Error updating history chart:', error);
                hideLoader();
            });
    }

    // Immediately update when changing type or date range
    document.getElementById('historyType').addEventListener('change', function() {
        const selectedType = this.value;
        const selectedDays = document.getElementById('historyDateRange').value;
        if (selectedType) {
            updateChartSelectStyles();
            updateHistoryChart(selectedType, selectedDays);
        }
    });

    document.getElementById('historyDateRange').addEventListener('change', function() {
        const selectedType = document.getElementById('historyType').value;
        const selectedDays = this.value;
        if (selectedType) {
            updateHistoryChart(selectedType, selectedDays);
        }
    });

    // Enhanced select styling and interaction
    function updateChartSelectStyles() {
        const historyTypeSelect = document.getElementById('historyType');
        const selectedOption = historyTypeSelect.options[historyTypeSelect.selectedIndex];
        const color = selectedOption.getAttribute('data-color');
        
        if (selectedOption.value) {
            historyTypeSelect.style.borderColor = color;
            historyTypeSelect.style.backgroundColor = `${color}08`;
        } else {
            historyTypeSelect.style.borderColor = '#e2e8f0';
            historyTypeSelect.style.backgroundColor = '#ffffff';
        }
    }

    // Initialize collapse functionality
    $(document).ready(function() {
        $('.btn-tool').on('click', function() {
            const $container = $(this).closest('.chart-container');
            const $icon = $(this).find('i');
            const $chartArea = $container.find('.row:last-child');
            
            $chartArea.slideToggle(400);
            $icon.toggleClass('fa-minus fa-plus');
        });
    });

    // Add event listeners for new buttons
    document.getElementById('btnCopy').addEventListener('click', function() {
        const canvas = document.getElementById('patientChart');
        const image = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = 'patient-statistics.png';
        link.href = image;
        link.click();
    });

    document.getElementById('btnCSV').addEventListener('click', function() {
        const canvas = document.getElementById('patientChart');
        const image = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = 'patient-statistics.csv';
        link.href = image;
        link.click();
    });

    document.getElementById('btnExcel').addEventListener('click', function() {
        const canvas = document.getElementById('patientChart');
        const image = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = 'patient-statistics.xls';
        link.href = image;
        link.click();
    });

    document.getElementById('btnPDF').addEventListener('click', function() {
        const canvas = document.getElementById('patientChart');
        const image = canvas.toDataURL('image/png');
        const pdf = new jsPDF('l', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        
        pdf.addImage(image, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save('patient_statistics.pdf');
    });

    document.getElementById('btnPrint').addEventListener('click', function() {
        const canvas = document.getElementById('patientChart');
        const win = window.open('');
        win.document.write(`<img src="${canvas.toDataURL()}" onload="window.print();window.close()" />`);
    });

    // Add this to your existing JavaScript, after the chart initialization
    document.addEventListener('DOMContentLoaded', function() {
      // Function to export chart data as CSV
      function exportToCSV() {
        const labels = currentChart.data.labels;
        const data = currentChart.data.datasets[0].data;
        let csvContent = "data:text/csv;charset=utf-8,";
        
        // Add headers
        csvContent += "Date,Patients\n";
        
        // Add data rows
        labels.forEach((label, index) => {
          csvContent += `${label},${data[index]}\n`;
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "patient_statistics.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }

      // Function to copy data to clipboard
      function copyToClipboard() {
        const labels = currentChart.data.labels;
        const data = currentChart.data.datasets[0].data;
        let text = "Date\tPatients\n";
        
        labels.forEach((label, index) => {
          text += `${label}\t${data[index]}\n`;
        });
        
        navigator.clipboard.writeText(text).then(() => {
          alert('Data copied to clipboard!');
        });
      }

      // Function to export to Excel
      function exportToExcel() {
        const labels = currentChart.data.labels;
        const data = currentChart.data.datasets[0].data;
        let csvContent = "data:application/vnd.ms-excel,";
        
        // Add headers
        csvContent += "Date\tPatients\n";
        
        // Add data rows
        labels.forEach((label, index) => {
          csvContent += `${label}\t${data[index]}\n`;
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "patient_statistics.xls");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }

      // Function to export to PDF
      function exportToPDF() {
        const canvas = document.getElementById('patientChart');
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('l', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save('patient_statistics.pdf');
      }

      // Function to print chart
      function printChart() {
        const canvas = document.getElementById('patientChart');
        const win = window.open('');
        win.document.write(`<img src="${canvas.toDataURL()}" onload="window.print();window.close()" />`);
      }

      // Add event listeners to buttons
      document.getElementById('btnCopy').addEventListener('click', copyToClipboard);
      document.getElementById('btnCSV').addEventListener('click', exportToCSV);
      document.getElementById('btnExcel').addEventListener('click', exportToExcel);
      document.getElementById('btnPDF').addEventListener('click', exportToPDF);
      document.getElementById('btnPrint').addEventListener('click', printChart);

      // Initialize column visibility dropdown
      function initializeColumnVisibility() {
        const columnVisibilityMenu = document.getElementById('columnVisibility');
        // Check if element exists before proceeding
        if (!columnVisibilityMenu || !currentChart || !currentChart.data || !currentChart.data.datasets) {
          console.log('Required elements for column visibility not found');
          return;
        }
        
        // Clear existing items
        while (columnVisibilityMenu.firstChild) {
          columnVisibilityMenu.removeChild(columnVisibilityMenu.firstChild);
        }
        
        const datasets = currentChart.data.datasets;
        
        datasets.forEach((dataset, index) => {
          const item = document.createElement('div');
          item.className = 'dropdown-item';
          
          const checkbox = document.createElement('input');
          checkbox.type = 'checkbox';
          checkbox.checked = dataset.hidden !== true;
          checkbox.addEventListener('change', () => {
            dataset.hidden = !checkbox.checked;
            currentChart.update();
          });
          
          const label = document.createElement('label');
          label.appendChild(checkbox);
          label.appendChild(document.createTextNode(dataset.label || `Dataset ${index + 1}`));
          
          item.appendChild(label);
          columnVisibilityMenu.appendChild(item);
        });
      }

      // Only call this after chart initialization and when element exists
      document.addEventListener('DOMContentLoaded', function() {
        // Delay initialization to ensure chart is ready
        setTimeout(() => {
          try {
            initializeColumnVisibility();
          } catch (err) {
            console.warn('Column visibility initialization error:', err);
          }
        }, 1000);
      });
    });

    // Add this to your existing JavaScript, after the history chart initialization
    document.addEventListener('DOMContentLoaded', function() {
      // Function to export history chart data as CSV
      function exportHistoryToCSV() {
        const labels = historyChart.data.labels;
        const data = historyChart.data.datasets[0].data;
        let csvContent = "data:text/csv;charset=utf-8,";
        
        // Add headers
        csvContent += "Date,Value\n";
        
        // Add data rows
        labels.forEach((label, index) => {
          csvContent += `${label},${data[index]}\n`;
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "history_statistics.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }

      // Function to copy history data to clipboard
      function copyHistoryToClipboard() {
        const labels = historyChart.data.labels;
        const data = historyChart.data.datasets[0].data;
        let text = "Date\tValue\n";
        
        labels.forEach((label, index) => {
          text += `${label}\t${data[index]}\n`;
        });
        
        navigator.clipboard.writeText(text).then(() => {
          alert('History data copied to clipboard!');
        });
      }

      // Function to export history to Excel
      function exportHistoryToExcel() {
        const labels = historyChart.data.labels;
        const data = historyChart.data.datasets[0].data;
        let csvContent = "data:application/vnd.ms-excel,";
        
        // Add headers
        csvContent += "Date\tValue\n";
        
        // Add data rows
        labels.forEach((label, index) => {
          csvContent += `${label}\t${data[index]}\n`;
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "history_statistics.xls");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }

      // Function to export history to PDF
      function exportHistoryToPDF() {
        const canvas = document.getElementById('historyChart');
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('l', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save('history_statistics.pdf');
      }

      // Function to print history chart
      function printHistoryChart() {
        const canvas = document.getElementById('historyChart');
        const win = window.open('');
        win.document.write(`<img src="${canvas.toDataURL()}" onload="window.print();window.close()" />`);
      }

      // Add event listeners to history chart buttons
      document.getElementById('historyBtnCopy').addEventListener('click', copyHistoryToClipboard);
      document.getElementById('historyBtnCSV').addEventListener('click', exportHistoryToCSV);
      document.getElementById('historyBtnExcel').addEventListener('click', exportHistoryToExcel);
      document.getElementById('historyBtnPDF').addEventListener('click', exportHistoryToPDF);
      document.getElementById('historyBtnPrint').addEventListener('click', printHistoryChart);
    });

    // Initialize chart and styles when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        const historyType = document.getElementById('historyType');
        updateChartSelectStyles();
        
        // If a type is already selected, load its chart
        if (historyType.value) {
            updateHistoryChart(historyType.value, document.getElementById('historyDateRange').value);
        }

        // Set up auto-refresh every 5 minutes
        setInterval(() => {
            const selectedType = document.getElementById('historyType').value;
            const selectedDays = document.getElementById('historyDateRange').value;
            if (selectedType) {
                console.log('Auto-refreshing history chart...');
                updateHistoryChart(selectedType, selectedDays);
            }
        }, 300000); // 5 minutes in milliseconds
    });

    // Add manual refresh button
    document.getElementById('historyDateRange').insertAdjacentHTML('afterend', 
        '<button id="refreshHistory" class="btn btn-gradient btn-sm export-btn ml-2">' +
        '<i class="fas fa-sync-alt"></i> Refresh</button>'
    );

    document.getElementById('refreshHistory').addEventListener('click', function() {
        const selectedType = document.getElementById('historyType').value;
        const selectedDays = document.getElementById('historyDateRange').value;
        if (selectedType) {
            console.log('Manually refreshing history chart...');
            updateHistoryChart(selectedType, selectedDays);
        }
    });
  </script>
</body>
</html>
