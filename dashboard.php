<?php
// Start output buffering to prevent header issues
ob_start();

// Ensure the correct timezone is set
date_default_timezone_set('Asia/Manila');

// Include authentication check
require_once './config/check_auth.php';

include './config/connection.php';
// Include session fix to prevent undefined variable errors
require_once './config/session_fix.php';

// Set current date components used throughout the queries
$date  = date('Y-m-d');
$year  = date('Y');
$month = date('m');

// Query for today's total visits (combining all services)
$queryToday = "SELECT 
    (SELECT COUNT(*) FROM bp_monitoring WHERE DATE(date) = ?) +
    (SELECT COUNT(*) FROM family_planning WHERE DATE(date) = ?) +
    (SELECT COUNT(*) FROM tetanus_toxoid WHERE DATE(date) = ?) +
    (SELECT COUNT(*) FROM random_blood_sugar WHERE DATE(date) = ?) +
    (SELECT COUNT(*) FROM deworming WHERE DATE(date) = ?) +
    (SELECT COUNT(*) FROM family_members WHERE DATE(date) = ?) as today";

// Query for current week visits
$queryWeek = "SELECT 
    (SELECT COUNT(*) FROM bp_monitoring WHERE YEARWEEK(date, 1) = YEARWEEK(?, 1)) +
    (SELECT COUNT(*) FROM family_planning WHERE YEARWEEK(date, 1) = YEARWEEK(?, 1)) +
    (SELECT COUNT(*) FROM tetanus_toxoid WHERE YEARWEEK(date, 1) = YEARWEEK(?, 1)) +
    (SELECT COUNT(*) FROM random_blood_sugar WHERE YEARWEEK(date, 1) = YEARWEEK(?, 1)) +
    (SELECT COUNT(*) FROM deworming WHERE YEARWEEK(date, 1) = YEARWEEK(?, 1)) +
    (SELECT COUNT(*) FROM family_members WHERE YEARWEEK(date, 1) = YEARWEEK(?, 1)) as week";

// Query for current month visits
$queryMonth = "SELECT 
    (SELECT COUNT(*) FROM bp_monitoring WHERE YEAR(date) = ? AND MONTH(date) = ?) +
    (SELECT COUNT(*) FROM family_planning WHERE YEAR(date) = ? AND MONTH(date) = ?) +
    (SELECT COUNT(*) FROM tetanus_toxoid WHERE YEAR(date) = ? AND MONTH(date) = ?) +
    (SELECT COUNT(*) FROM random_blood_sugar WHERE YEAR(date) = ? AND MONTH(date) = ?) +
    (SELECT COUNT(*) FROM deworming WHERE YEAR(date) = ? AND MONTH(date) = ?) +
    (SELECT COUNT(*) FROM family_members WHERE YEAR(date) = ? AND MONTH(date) = ?) as month";

// Query for current year visits
$queryYear = "SELECT 
    (SELECT COUNT(*) FROM bp_monitoring WHERE YEAR(date) = ?) +
    (SELECT COUNT(*) FROM family_planning WHERE YEAR(date) = ?) +
    (SELECT COUNT(*) FROM tetanus_toxoid WHERE YEAR(date) = ?) +
    (SELECT COUNT(*) FROM random_blood_sugar WHERE YEAR(date) = ?) +
    (SELECT COUNT(*) FROM deworming WHERE YEAR(date) = ?) +
    (SELECT COUNT(*) FROM family_members WHERE YEAR(date) = ?) as year";

// Initialize counts to default values
$todaysCount = 0;
$currentWeekCount = 0;
$currentMonthCount = 0;
$currentYearCount = 0;

try {
    // Execute query for today's count
    $stmtToday = $con->prepare($queryToday);
    $stmtToday->execute([$date, $date, $date, $date, $date, $date]);
    $r = $stmtToday->fetch(PDO::FETCH_ASSOC);
    $todaysCount = $r['today'];

    // Execute query for current week's count
    $stmtWeek = $con->prepare($queryWeek);
    $stmtWeek->execute([$date, $date, $date, $date, $date, $date]);
    $r = $stmtWeek->fetch(PDO::FETCH_ASSOC);
    $currentWeekCount = $r['week'];

    // Execute query for current month's count
    $stmtMonth = $con->prepare($queryMonth);
    $stmtMonth->execute([$year, $month, $year, $month, $year, $month, $year, $month, $year, $month, $year, $month]);
    $r = $stmtMonth->fetch(PDO::FETCH_ASSOC);
    $currentMonthCount = $r['month'];

    // Execute query for current year's count
    $stmtYear = $con->prepare($queryYear);
    $stmtYear->execute([$year, $year, $year, $year, $year, $year]);
    $r = $stmtYear->fetch(PDO::FETCH_ASSOC);
    $currentYearCount = $r['year'];

} catch(PDOException $ex) {
    // For production, consider logging errors rather than echoing detailed messages
    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
}

// WEEKLY CHART DATA
$weeklyLabels = [];
$weeklyData = [];
try {
    // Get last 7 days data
    for ($i = 6; $i >= 0; $i--) {
        $checkDate = date('Y-m-d', strtotime("-$i days"));
        $dayName = date('D', strtotime($checkDate));
        $weeklyLabels[] = $dayName;
        
        // Count visits for this day across all services
        $dayQuery = "SELECT 
            (SELECT COUNT(*) FROM bp_monitoring WHERE DATE(date) = ?) +
            (SELECT COUNT(*) FROM family_planning WHERE DATE(date) = ?) +
            (SELECT COUNT(*) FROM tetanus_toxoid WHERE DATE(date) = ?) +
            (SELECT COUNT(*) FROM random_blood_sugar WHERE DATE(date) = ?) +
            (SELECT COUNT(*) FROM deworming WHERE DATE(date) = ?) +
            (SELECT COUNT(*) FROM family_members WHERE DATE(date) = ?) as total";
        
        $stmt = $con->prepare($dayQuery);
        $stmt->execute([$checkDate, $checkDate, $checkDate, $checkDate, $checkDate, $checkDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $weeklyData[] = (int)$result['total'];
    }
} catch(PDOException $ex) {
    error_log("Weekly data error: " . $ex->getMessage());
    // Fill with default data if error
    $weeklyLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $weeklyData = [0, 0, 0, 0, 0, 0, 0];
}

// MONTHLY CHART DATA
$monthlyLabels = [];
$monthlyData = [];
try {
    // Get last 30 days data grouped by week
    for ($i = 3; $i >= 0; $i--) {
        $weekStart = date('Y-m-d', strtotime("-" . ($i * 7 + 6) . " days"));
        $weekEnd = date('Y-m-d', strtotime("-" . ($i * 7) . " days"));
        $monthlyLabels[] = "Week " . (4 - $i);
        
        // Count visits for this week across all services
        $weekQuery = "SELECT 
            (SELECT COUNT(*) FROM bp_monitoring WHERE DATE(date) BETWEEN ? AND ?) +
            (SELECT COUNT(*) FROM family_planning WHERE DATE(date) BETWEEN ? AND ?) +
            (SELECT COUNT(*) FROM tetanus_toxoid WHERE DATE(date) BETWEEN ? AND ?) +
            (SELECT COUNT(*) FROM random_blood_sugar WHERE DATE(date) BETWEEN ? AND ?) +
            (SELECT COUNT(*) FROM deworming WHERE DATE(date) BETWEEN ? AND ?) +
            (SELECT COUNT(*) FROM family_members WHERE DATE(date) BETWEEN ? AND ?) as total";
        
        $stmt = $con->prepare($weekQuery);
        $stmt->execute([$weekStart, $weekEnd, $weekStart, $weekEnd, $weekStart, $weekEnd, 
                       $weekStart, $weekEnd, $weekStart, $weekEnd, $weekStart, $weekEnd]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $monthlyData[] = (int)$result['total'];
    }
} catch(PDOException $ex) {
    error_log("Monthly data error: " . $ex->getMessage());
    // Fill with default data if error
    $monthlyLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    $monthlyData = [0, 0, 0, 0];
}

// YEARLY CHART DATA
$yearlyLabels = [];
$yearlyData = [];
try {
    // Get last 12 months data
    for ($i = 11; $i >= 0; $i--) {
        $monthDate = date('Y-m', strtotime("-$i months"));
        $monthName = date('M', strtotime("-$i months"));
        $yearlyLabels[] = $monthName;
        
        $monthYear = explode('-', $monthDate);
        $checkYear = $monthYear[0];
        $checkMonth = $monthYear[1];
        
        // Count visits for this month across all services
        $monthQuery = "SELECT 
            (SELECT COUNT(*) FROM bp_monitoring WHERE YEAR(date) = ? AND MONTH(date) = ?) +
            (SELECT COUNT(*) FROM family_planning WHERE YEAR(date) = ? AND MONTH(date) = ?) +
            (SELECT COUNT(*) FROM tetanus_toxoid WHERE YEAR(date) = ? AND MONTH(date) = ?) +
            (SELECT COUNT(*) FROM random_blood_sugar WHERE YEAR(date) = ? AND MONTH(date) = ?) +
            (SELECT COUNT(*) FROM deworming WHERE YEAR(date) = ? AND MONTH(date) = ?) +
            (SELECT COUNT(*) FROM family_members WHERE YEAR(date) = ? AND MONTH(date) = ?) as total";
        
        $stmt = $con->prepare($monthQuery);
        $stmt->execute([$checkYear, $checkMonth, $checkYear, $checkMonth, $checkYear, $checkMonth,
                       $checkYear, $checkMonth, $checkYear, $checkMonth, $checkYear, $checkMonth]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $yearlyData[] = (int)$result['total'];
    }
} catch(PDOException $ex) {
    error_log("Yearly data error: " . $ex->getMessage());
    // Fill with default data if error
    $yearlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $yearlyData = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
}

// Note: Chart data is already prepared above in the WEEKLY, MONTHLY, and YEARLY sections
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <link rel="stylesheet" href="system_styles/dashboard.css">
  <title>Dashboard - Mamatid Health Center System</title>
  
  <!-- Modern Export Actions Inline CSS -->
  <style>
    /* Modern Export Actions Container */
    .chart-actions {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      flex-wrap: wrap;
      align-items: center;
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

    /* Responsive Design for Modern Export Options */
    @media (max-width: 768px) {
      .chart-actions {
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
      .chart-actions {
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
    }
  </style>
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
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
    <?php include './config/footer.php'; ?>
  </div>
  <?php include './config/site_js_links.php'; ?>
  <!-- Local Chart.js and jsPDF libraries -->
  <script src="dist/libs/chart.min.js"></script>
  <script src="dist/libs/jspdf.min.js"></script>
  <!-- Enhanced Export Functions -->
  <script src="dist/js/export-functions.js"></script>
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

    // Debug chart data
    console.log('Chart Data:', chartData);

    window.currentChart = null; // Make currentChart globally accessible
    const ctx = document.getElementById('patientChart').getContext('2d');

    document.addEventListener("DOMContentLoaded", function() {
      console.log('DOM Content Loaded - Initializing charts...');
      
      // Check if Chart.js is loaded
      if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded!');
        return;
      }
      
      // Check if canvas element exists
      const canvasElement = document.getElementById('patientChart');
      if (!canvasElement) {
        console.error('Canvas element #patientChart not found!');
        return;
      }
      
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
        try {
          console.log('Rendering chart for type:', type);
          
          if (window.currentChart) {
            window.currentChart.destroy();
          }
          
          if (!chartData[type] || !chartData[type].labels || !chartData[type].data) {
            console.error('Invalid chart data for type:', type);
            return;
          }
          
          const theme = chartThemes[type];
          
          // Update statistics before rendering chart
          updateStatistics(type, chartData[type].data);
          
          window.currentChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: chartData[type].labels,
            datasets: [{
              label: theme.label,
              data: chartData[type].data,
              backgroundColor: theme.gradient,
              borderColor: theme.border,
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
        
        console.log('Chart rendered successfully for type:', type);
        } catch (error) {
          console.error('Error rendering chart:', error);
        }
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


  </script>
</body>
</html>
