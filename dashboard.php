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
  <title>Dashboard - Mamatid Health Center System</title>

  <style>
    :root {
      --transition-speed: 0.3s;
    }

    /* Modern Card Styles */
    .small-box {
      border-radius: 15px;
      overflow: hidden;
      transition: transform var(--transition-speed), box-shadow var(--transition-speed);
      border: none;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .small-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .small-box .inner {
      padding: 20px;
    }

    .small-box .inner h3 {
      font-size: 2.5rem;
      font-weight: 600;
      margin-bottom: 10px;
      transition: var(--transition-speed);
    }

    .small-box .inner p {
      font-size: 1.1rem;
      font-weight: 500;
      margin-bottom: 0;
      opacity: 0.9;
    }

    .small-box .icon {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 4rem;
      opacity: 0.3;
      transition: var(--transition-speed);
    }

    .small-box:hover .icon {
      opacity: 0.5;
      transform: translateY(-50%) scale(1.1);
    }

    /* Modern Gradients for Stat Boxes */
    .bg-success {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%)!important;
    }

    .bg-primary {
      background: linear-gradient(135deg, #007bff 0%, #6610f2 100%)!important;
    }

    .bg-warning {
      background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)!important;
      color: #fff!important;
    }

    .bg-danger {
      background: linear-gradient(135deg, #dc3545 0%, #c81e1e 100%)!important;
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

    @keyframes pulse {
      0% { box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2); }
      50% { box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4); }
      100% { box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2); }
    }

    /* Chart Section Styling */
    .chart-container {
      background: linear-gradient(145deg, #ffffff, #f8fafc);
      border: 1px solid rgba(226, 232, 240, 0.8);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      border-radius: 24px;
      padding: 2rem;
      position: relative;
      overflow: hidden;
    }

    .chart-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #8B5CF6, #3B82F6, #10B981);
      opacity: 0.7;
    }

    .chart-wrapper {
      position: relative;
      min-height: 400px;
      padding: 1rem;
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .chart-loader {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 10;
      background: rgba(255, 255, 255, 0.9);
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    /* Chart Select Styles */
    .chart-select {
      width: 100%;
      padding: 15px 25px;
      font-size: 1rem;
      font-weight: 500;
      color: #2d3748;
      background-color: #ffffff;
      border: 2px solid #000000;  /* Set default border color to black */
      border-radius: 15px;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 15px center;
      background-size: 16px;
      transition: all 0.3s ease;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .chart-select:hover {
      border-color: #000000;  /* Keep black border on hover for default state */
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    .chart-select:focus {
      outline: none;
      border-color: #000000;  /* Keep black border on focus for default state */
      box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
    }

    .chart-select.active-weekly {
      background-color: rgba(54, 162, 235, 0.1);
      border-color: rgba(54, 162, 235, 0.5);
    }

    .chart-select.active-monthly {
      background-color: rgba(255, 159, 64, 0.1);
      border-color: rgba(255, 159, 64, 0.5);
    }

    .chart-select.active-yearly {
      background-color: rgba(75, 192, 192, 0.1);
      border-color: rgba(75, 192, 192, 0.5);
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
      .small-box .inner h3 {
        font-size: 2rem;
      }
      
      .small-box .icon {
        font-size: 3rem;
      }
      
      #datetime {
        font-size: 1rem;
        padding: 8px 15px;
      }
    }

    /* History Analytics Section Styling */
    .history-analytics-container {
      background: linear-gradient(145deg, #ffffff, #f8fafc);
      border: 1px solid rgba(226, 232, 240, 0.8);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      border-radius: 24px;
      padding: 2rem;
      margin-top: 2rem;
      position: relative;
      overflow: hidden;
    }

    .history-analytics-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #8B5CF6, #3B82F6, #10B981);
      opacity: 0.7;
    }

    .history-select-wrapper {
      position: relative;
      margin-bottom: 1rem;
    }

    .history-select-icon {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      pointer-events: none;
      z-index: 2;
    }

    .chart-select {
      width: 100%;
      padding: 1rem 3rem 1rem 1.5rem;
      font-size: 0.95rem;
      font-weight: 500;
      color: #1e293b;
      background-color: #ffffff;
      border: 2px solid #e2e8f0;
      border-radius: 16px;
      appearance: none;
      transition: all 0.3s ease;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      cursor: pointer;
    }

    .chart-select:hover {
      border-color: #cbd5e1;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      transform: translateY(-1px);
    }

    .chart-select:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .history-chart-wrapper {
      position: relative;
      min-height: 400px;
      padding: 1rem;
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .chart-loader {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 10;
      background: rgba(255, 255, 255, 0.9);
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    /* Animation for chart transitions */
    @keyframes chartFadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    #patientChart,
    #historyChart {
      animation: chartFadeIn 0.5s ease-out;
    }

    /* Collapse Button Styling */
    .btn-tool {
      padding: 0.5rem;
      font-size: 1rem;
      line-height: 1;
      background: transparent;
      border: none;
      border-radius: 0.375rem;
      color: #64748b;
      transition: all 0.2s ease;
    }

    .btn-tool:hover {
      color: #1e293b;
      background-color: rgba(0, 0, 0, 0.05);
    }

    .btn-tool:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Enhanced Chart Section Styling */
    .chart-period-info {
      padding: 0.5rem 1rem;
      background: rgba(0, 0, 0, 0.03);
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
    }

    .period-label {
      color: #64748b;
      font-size: 0.875rem;
    }

    .period-value {
      font-weight: 600;
      color: #1e293b;
    }

    .period-dates {
      color: #64748b;
      font-size: 0.875rem;
    }

    /* Stat Cards */
    .stat-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 1.25rem;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }

    .stat-card-content {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
    }

    .stat-card-info {
      flex-grow: 1;
    }

    .stat-card-title {
      color: #64748b;
      font-size: 0.875rem;
      margin-bottom: 0.5rem;
      font-weight: 500;
    }

    .stat-card-value {
      color: #1e293b;
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 0;
    }

    .stat-card-icon {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      background: rgba(0, 0, 0, 0.04);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #1e293b;
    }

    .stat-card-footer {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.875rem;
    }

    .trend-indicator {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.25rem 0.5rem;
      border-radius: 6px;
      font-weight: 500;
    }

    .trend-indicator.positive {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
    }

    .trend-indicator.negative {
      background: rgba(239, 68, 68, 0.1);
      color: #ef4444;
    }

    .trend-period {
      color: #64748b;
    }

    .peak-patients {
      color: #64748b;
      font-size: 0.875rem;
    }

    /* Chart Legend and Actions */
    .chart-footer {
      padding-top: 1rem;
      border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .chart-legend {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: #64748b;
      font-size: 0.875rem;
    }

    .legend-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: rgba(0, 0, 0, 0.8);
    }

    .legend-line {
      width: 16px;
      height: 2px;
      background: rgba(0, 0, 0, 0.8);
    }

    .chart-actions {
      display: flex;
      gap: 0.75rem;
      justify-content: flex-end;
    }

    .chart-actions .btn {
      padding: 0.5rem 1rem;
      font-size: 0.875rem;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      border-radius: 8px;
      transition: all 0.2s ease;
    }

    .chart-actions .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    /* Chart Select Enhancement */
    .chart-select-wrapper {
      position: relative;
    }

    .chart-select-icon {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      pointer-events: none;
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
                        <button class="btn btn-outline-secondary btn-sm" id="downloadChart">
                          <i class="fas fa-download"></i> Download Report
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="shareChart">
                          <i class="fas fa-share-alt"></i> Share
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
                  <option value="30">Last 30 Days</option>
                  <option value="90">Last 90 Days</option>
                  <option value="365">Last Year</option>
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
        return historyTypeColors[type] || 'rgba(107, 114, 128, 1)'; // Default to gray if type not found
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

        fetch(`ajax/get_${type}_history.php?days=${days}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                hideLoader();
                
                // Create gradient
                const gradient = historyCtx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(0, 0, 0, 0.8)');  // Soft black at top
                gradient.addColorStop(1, 'rgba(255, 255, 255, 0.1)');  // Nearly transparent white at bottom

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
                                color: '#1a1a1a'  // Dark gray for better readability
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
                                color: '#4a4a4a'  // Darker gray for better readability
                            }
                        },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                color: 'rgba(0, 0, 0, 0.06)',  // Very light black for grid
                                        drawBorder: false
                                    },
                                    ticks: {
                                padding: 10,
                                        font: {
                                            size: 12,
                                            family: "'Inter', sans-serif",
                                            weight: '500'
                                        },
                                color: '#4a4a4a'  // Darker gray for better readability
                            }
                        }
                    }
                };

                if (type === 'bp') {
                    // Blood Pressure specific chart configuration
                    historyChart = new Chart(historyCtx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: 'Average Blood Pressure',
                                    data: data.bp_values,
                                    borderColor: 'rgba(0, 0, 0, 0.8)',  // Soft black for line
                                    backgroundColor: gradient,
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.4,
                                    yAxisID: 'y-bp'
                                },
                                {
                                    label: 'Number of Patients',
                                    data: data.patient_counts,
                                    borderColor: 'rgba(0, 0, 0, 0.6)',  // Slightly transparent black
                                    backgroundColor: 'rgba(0, 0, 0, 0.1)',  // Very light black
                                    borderWidth: 2,
                                    yAxisID: 'y-patients',
                                    type: 'bar'
                                }
                            ]
                        },
                        options: {
                            ...chartConfig,
                            scales: {
                                ...chartConfig.scales,
                                'y-bp': {
                                    position: 'left',
                                    title: {
                                        display: true,
                                        text: 'Blood Pressure',
                                        font: {
                                            size: 12,
                                            weight: '500'
                                        },
                                        color: '#4a4a4a'
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.06)'
                                    }
                                },
                                'y-patients': {
                                    position: 'right',
                                    title: {
                                        display: true,
                                        text: 'Number of Patients',
                                        font: {
                                            size: 12,
                                            weight: '500'
                                        },
                                        color: '#4a4a4a'
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                } else {
                    // Standard chart configuration for other types
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
                                borderColor: 'rgba(0, 0, 0, 0.8)',  // Soft black for line
                                backgroundColor: gradient,
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: chartConfig
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching history data:', error);
                hideLoader();
            });
    }

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

    // Event listeners with loading states
    document.getElementById('historyType').addEventListener('change', function() {
        const type = this.value;
        if (type) {
            const days = document.getElementById('historyDateRange').value;
            updateHistoryChart(type, days);
            updateChartSelectStyles();
        }
    });

    document.getElementById('historyDateRange').addEventListener('change', function() {
        const type = document.getElementById('historyType').value;
        if (type) {
            updateHistoryChart(type, this.value);
        }
    });

    // Initialize chart and styles when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        const historyType = document.getElementById('historyType');
        updateChartSelectStyles();
        
        // If a type is already selected, load its chart
        if (historyType.value) {
            updateHistoryChart(historyType.value, document.getElementById('historyDateRange').value);
        }
    });

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
    document.getElementById('downloadChart').addEventListener('click', function() {
        const canvas = document.getElementById('patientChart');
        const image = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = 'patient-statistics.png';
        link.href = image;
        link.click();
    });

    document.getElementById('shareChart').addEventListener('click', function() {
        // Implement share functionality based on your requirements
        alert('Share functionality to be implemented based on your needs');
    });
  </script>
</body>
</html>
