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
      background: linear-gradient(145deg, #ffffff, #f5f7fa);
      border-radius: 20px;
      padding: 25px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      margin-top: 30px;
      border: 1px solid rgba(228, 231, 236, 0.8);
      backdrop-filter: blur(10px);
    }

    .chart-select {
      width: 100%;
      padding: 15px 25px;
      font-size: 1rem;
      font-weight: 500;
      color: #2d3748;
      background-color: #ffffff;
      border: 2px solid #e2e8f0;
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
      border-color: #cbd5e0;
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    .chart-select:focus {
      outline: none;
      border-color: #4299e1;
      box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15);
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
            <div class="row mb-4">
              <div class="col-md-4">
                <select id="chartType" class="chart-select">
                  <option value="weekly">Weekly Patient Statistics</option>
                  <option value="monthly">Monthly Patient Statistics</option>
                  <option value="yearly">Yearly Patient Statistics</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <canvas id="patientChart"></canvas>
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

      function renderChart(type) {
        if (currentChart) {
          currentChart.destroy();
        }
        
        const theme = chartThemes[type];
        
        currentChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: chartData[type].labels,
            datasets: [{
              label: theme.label,
              data: chartData[type].data,
              backgroundColor: type === 'weekly' ? 'rgba(56, 189, 248, 0.85)' :  // Sky blue
                             type === 'monthly' ? 'rgba(168, 85, 247, 0.85)' :  // Purple
                             'rgba(34, 197, 94, 0.85)',  // Green
              borderColor: type === 'weekly' ? 'rgba(56, 189, 248, 1)' :
                          type === 'monthly' ? 'rgba(168, 85, 247, 1)' :
                          'rgba(34, 197, 94, 1)',
              borderWidth: 2,
              borderRadius: 12,
              borderSkipped: false,
              maxBarThickness: 40,
              minBarLength: 5,
              shadowOffsetX: 3,
              shadowOffsetY: 3,
              shadowBlur: 10,
              shadowColor: 'rgba(0, 0, 0, 0.1)'
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
                  display: true,
                  color: 'rgba(0, 0, 0, 0.03)',
                  drawBorder: false,
                  lineWidth: 1
                },
                ticks: {
                  font: {
                    size: 12,
                    family: "'Inter', sans-serif",
                    weight: '500'
                  },
                  color: '#64748b',
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
                  color: '#64748b',
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
                borderColor: 'rgba(226, 232, 240, 0.9)',
                borderWidth: 1,
                callbacks: {
                  label: function(context) {
                    return `Patients: ${context.parsed.y}`;
                  }
                }
              }
            },
            interaction: {
              intersect: false,
              mode: 'index'
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
  </script>
</body>
</html>
