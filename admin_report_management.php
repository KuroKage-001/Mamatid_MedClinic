<?php
// Start output buffering to catch any accidental output
ob_start();

// Include session configuration first - before any HTML
include './system/security/admin_session_config.php';

// Then include database connection and common functions
include './config/db_connection.php';
include './system/utilities/admin_client_common_functions_services.php';

// Check if user is logged in - if not, redirect to login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}

// Ensure session variables are properly set to avoid undefined variable warnings
if (!isset($_SESSION['display_name']) || empty($_SESSION['display_name'])) {
    $_SESSION['display_name'] = $_SESSION['user_name'] ?? 'Unknown User';
}

if (!isset($_SESSION['role']) || empty($_SESSION['role'])) {
    $_SESSION['role'] = 'user';
}

if (!isset($_SESSION['profile_picture']) || empty($_SESSION['profile_picture'])) {
    $_SESSION['profile_picture'] = 'default_profile.jpg';
}

// Clean any output that might have been generated
ob_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

  <!-- Tempus Dominus Bootstrap 4 CSS for datetimepicker -->
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- Tab icon -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Reports Management - Mamatid Health Center System</title>

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

    /* --- PAGE BACKGROUND --- */
    body,
    .content-wrapper {
      background: linear-gradient(135deg, #232b3e 0%, #34495e 100%) !important;
      min-height: 100vh;
    }

    /* --- MODERN HEADER FOR REPORTS MANAGEMENT --- */
    .modern-header {
      position: relative;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.18);
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
      border-radius: 20px;
      padding: 25px 30px;
      margin: 20px 0 30px;
      display: flex;
      align-items: center;
      gap: 25px;
      overflow: hidden;
    }

    .modern-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(54, 153, 255, 0.1), rgba(105, 147, 255, 0.1));
      z-index: 0;
    }

    .modern-header .header-icon {
      position: relative;
      background: linear-gradient(135deg, #3699FF 0%, #6993FF 100%);
      width: 56px;
      height: 56px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 15px rgba(54, 153, 255, 0.4);
      z-index: 1;
    }

    .modern-header .header-icon i {
      color: white;
      font-size: 28px;
      text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .modern-header .header-title {
      position: relative;
      color: #ffffff;
      margin: 0;
      font-size: 2rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      z-index: 1;
    }

    .modern-header .header-title::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 40px;
      height: 3px;
      background: linear-gradient(to right, #3699FF, #6993FF);
      border-radius: 2px;
    }

    @media (max-width: 768px) {
      .modern-header {
        flex-direction: column;
        text-align: center;
        padding: 20px;
        gap: 15px;
      }

      .modern-header .header-icon {
        width: 48px;
        height: 48px;
      }

      .modern-header .header-icon i {
        font-size: 24px;
      }

      .modern-header .header-title {
        font-size: 1.75rem;
      }

      .modern-header .header-title::after {
        left: 50%;
        transform: translateX(-50%);
      }
    }

    /* Content Header Enhanced Styling */
    .content-header {
      padding: 15px 0;
      position: relative;
    }

    .content-header .container-fluid {
      position: relative;
      z-index: 1;
    }

    .content {
      padding: 20px 0;
    }

    .content .container-fluid {
      max-width: 1400px;
      margin: 0 auto;
    }

    /* Card Styling */
    .card {
      background: rgba(255, 255, 255, 0.95);
      border: none;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      border-radius: 15px;
      margin-bottom: 30px;
      backdrop-filter: blur(10px);
    }

    .card-outline {
      border-top: 3px solid var(--primary-color);
    }

    .card-header {
      background: transparent;
      padding: 1.5rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .card-header .nav-tabs {
      border-bottom: none;
      margin-bottom: -1.5rem;
    }

    .card-body {
      padding: 1.5rem;
    }

    /* Tab Styling */
    .nav-tabs .nav-link {
      color: var(--dark-color);
      padding: 1rem 1.5rem;
      font-weight: 500;
      border: none;
      border-bottom: 2px solid transparent;
      background: transparent;
      transition: all var(--transition-speed);
    }

    .nav-tabs .nav-link:hover {
      color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .nav-tabs .nav-link.active {
      color: var(--primary-color);
      background: transparent;
      border-bottom: 2px solid var(--primary-color);
    }

    /* Form Controls */
    .form-control {
      height: calc(2.5rem + 2px);
      border-radius: 8px;
      border: 2px solid #e4e6ef;
      padding: 0.625rem 1rem;
      font-size: 1rem;
      transition: all var(--transition-speed);
      background: rgba(255, 255, 255, 0.9);
    }

    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
      background: white;
    }

    .input-group-text {
      border-radius: 8px;
      border: 2px solid #e4e6ef;
      background-color: var(--light-color);
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

    @media (max-width: 768px) {
      .modern-header {
        flex-direction: column;
        text-align: center;
        padding: 15px;
      }

      .modern-header .header-icon {
        width: 40px;
        height: 40px;
      }

      .modern-header .header-title {
        font-size: 1.5rem;
      }

      .nav-tabs .nav-link {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Include Navbar and Sidebar -->
    <?php 
      include './config/admin_header.php';
      include './config/admin_sidebar.php';
    ?>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
      <!-- Content Header -->
      <section class="content-header">
        <div class="container-fluid">
          <div class="modern-header">
            <div class="header-icon">
              <i class="fas fa-chart-pie"></i>
            </div>
            <div class="header-content">
              <h1 class="header-title">Reports Management</h1>
            </div>
          </div>
        </div>
      </section>

      <!-- Main Content Section -->
      <section class="content">
        <div class="container-fluid">
          <div class="card card-outline card-primary">
            <div class="card-header">
              <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="medicine-tab" data-toggle="tab" href="#medicine" role="tab">
                    Medicine Inventory
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="family-planning-tab" data-toggle="tab" href="#family-planning" role="tab">
                    Family Planning
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="family-members-tab" data-toggle="tab" href="#family-members" role="tab">
                    Family Members
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="deworming-tab" data-toggle="tab" href="#deworming" role="tab">
                    Deworming
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="bp-tab" data-toggle="tab" href="#bp" role="tab">
                    Blood Pressure
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="blood-sugar-tab" data-toggle="tab" href="#blood-sugar" role="tab">
                    Blood Sugar
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="tetanus-tab" data-toggle="tab" href="#tetanus" role="tab">
                    Tetanus Toxoid
                  </a>
                </li>
              </ul>
            </div>
            <div class="card-body">
              <div class="tab-content" id="reportTabsContent">
                <!-- Medicine Inventory Tab -->
                <div class="tab-pane fade show active" id="medicine" role="tabpanel">
                  <div class="row">
                    <div class="col-md-3">
                      <button type="button" id="print_inventory" class="btn btn-primary w-100">
                        <i class="fas fa-file-pdf mr-2"></i>Generate PDF
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Family Planning Tab -->
                <div class="tab-pane fade" id="family-planning" role="tabpanel">
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">From Date</label>
                        <div class="input-group date" id="family_from_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="family_from" 
                                 data-target="#family_from_date" placeholder="Select start date"/>
                          <div class="input-group-append" data-target="#family_from_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">To Date</label>
                        <div class="input-group date" id="family_to_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="family_to" 
                                 data-target="#family_to_date" placeholder="Select end date"/>
                          <div class="input-group-append" data-target="#family_to_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" id="print_family" class="btn btn-primary w-100">
                          <i class="fas fa-file-pdf mr-2"></i>Generate PDF
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Family Members Tab -->
                <div class="tab-pane fade" id="family-members" role="tabpanel">
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">From Date</label>
                        <div class="input-group date" id="family_members_from_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="family_members_from" 
                                 data-target="#family_members_from_date" placeholder="Select start date"/>
                          <div class="input-group-append" data-target="#family_members_from_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">To Date</label>
                        <div class="input-group date" id="family_members_to_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="family_members_to" 
                                 data-target="#family_members_to_date" placeholder="Select end date"/>
                          <div class="input-group-append" data-target="#family_members_to_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" id="print_family_members" class="btn btn-primary w-100">
                          <i class="fas fa-file-pdf mr-2"></i>Generate PDF
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Deworming Tab -->
                <div class="tab-pane fade" id="deworming" role="tabpanel">
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">From Date</label>
                        <div class="input-group date" id="deworming_from_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="deworming_from" 
                                 data-target="#deworming_from_date" placeholder="Select start date"/>
                          <div class="input-group-append" data-target="#deworming_from_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">To Date</label>
                        <div class="input-group date" id="deworming_to_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="deworming_to" 
                                 data-target="#deworming_to_date" placeholder="Select end date"/>
                          <div class="input-group-append" data-target="#deworming_to_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" id="print_deworming" class="btn btn-primary w-100">
                          <i class="fas fa-file-pdf mr-2"></i>Generate PDF
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Blood Pressure Tab -->
                <div class="tab-pane fade" id="bp" role="tabpanel">
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">From Date</label>
                        <div class="input-group date" id="bp_from_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="bp_from" 
                                 data-target="#bp_from_date" placeholder="Select start date"/>
                          <div class="input-group-append" data-target="#bp_from_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">To Date</label>
                        <div class="input-group date" id="bp_to_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="bp_to" 
                                 data-target="#bp_to_date" placeholder="Select end date"/>
                          <div class="input-group-append" data-target="#bp_to_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" id="print_bp" class="btn btn-primary w-100">
                          <i class="fas fa-file-pdf mr-2"></i>Generate PDF
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Blood Sugar Tab -->
                <div class="tab-pane fade" id="blood-sugar" role="tabpanel">
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">From Date</label>
                        <div class="input-group date" id="blood_sugar_from_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="blood_sugar_from" 
                                 data-target="#blood_sugar_from_date" placeholder="Select start date"/>
                          <div class="input-group-append" data-target="#blood_sugar_from_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">To Date</label>
                        <div class="input-group date" id="blood_sugar_to_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="blood_sugar_to" 
                                 data-target="#blood_sugar_to_date" placeholder="Select end date"/>
                          <div class="input-group-append" data-target="#blood_sugar_to_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" id="print_blood_sugar" class="btn btn-primary w-100">
                          <i class="fas fa-file-pdf mr-2"></i>Generate PDF
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Tetanus Toxoid Tab -->
                <div class="tab-pane fade" id="tetanus" role="tabpanel">
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">From Date</label>
                        <div class="input-group date" id="tetanus_from_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="tetanus_from" 
                                 data-target="#tetanus_from_date" placeholder="Select start date"/>
                          <div class="input-group-append" data-target="#tetanus_from_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <div class="form-group">
                        <label class="form-label">To Date</label>
                        <div class="input-group date" id="tetanus_to_date" data-target-input="nearest">
                          <input type="text" class="form-control datetimepicker-input" id="tetanus_to" 
                                 data-target="#tetanus_to_date" placeholder="Select end date"/>
                          <div class="input-group-append" data-target="#tetanus_to_date" data-toggle="datetimepicker">
                            <span class="input-group-text"><i class="far fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" id="print_tetanus" class="btn btn-primary w-100">
                          <i class="fas fa-file-pdf mr-2"></i>Generate PDF
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
      <!-- End Main Content Section -->
    </div>
    <!-- End Content Wrapper -->

    <!-- Include Footer -->
    <?php include './config/admin_footer.php'; ?>  
  </div>
  <!-- End Wrapper -->

  <!-- Include JS Libraries -->
  <?php include './config/site_css_js_links.php'; ?>
  
  <script src="plugins/moment/moment.min.js"></script>
  <script src="plugins/daterangepicker/daterangepicker.js"></script>
  <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>

  <script>
    // Highlight the Reports menu in the sidebar
    showMenuSelected("#mnu_reports", "#mi_reports");

    $(document).ready(function() {
      // Initialize all date pickers
      $('.date').datetimepicker({
        format: 'L',
        useCurrent: false,
        icons: {
          time: 'far fa-clock',
          date: 'far fa-calendar',
          up: 'fas fa-arrow-up',
          down: 'fas fa-arrow-down',
          previous: 'fas fa-chevron-left',
          next: 'fas fa-chevron-right',
          today: 'fas fa-calendar-check',
          clear: 'far fa-trash-alt',
          close: 'far fa-times-circle'
        }
      });

      // Toast for messages
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
      });

      // Function to handle report generation
      function generateReport(url, fromDate, toDate) {
        if (!fromDate || !toDate) {
          Toast.fire({
            icon: 'warning',
            title: 'Please select both start and end dates'
          });
          return;
        }

        var win = window.open(url + "?from=" + fromDate + "&to=" + toDate, "_blank");
        if (!win) {
          Toast.fire({
            icon: 'error',
            title: 'Please allow popups to generate reports'
          });
        }
      }

      // Medicine Inventory Report
      $("#print_inventory").click(function() {
        var win = window.open("reports/print_inventory.php", "_blank");
        if (!win) {
          Toast.fire({
            icon: 'error',
            title: 'Please allow popups to generate reports'
          });
        }
      });

      // Family Planning Report
      $("#print_family").click(function() {
        generateReport(
          "reports/print_family_planning.php",
          $("#family_from").val(),
          $("#family_to").val()
        );
      });

      // Family Members Report
      $("#print_family_members").click(function() {
        generateReport(
          "reports/print_family_members.php",
          $("#family_members_from").val(),
          $("#family_members_to").val()
        );
      });

      // Deworming Report
      $("#print_deworming").click(function() {
        generateReport(
          "reports/print_deworming.php",
          $("#deworming_from").val(),
          $("#deworming_to").val()
        );
      });

      // BP Monitoring Report
      $("#print_bp").click(function() {
        generateReport(
          "reports/print_bp_monitoring.php",
          $("#bp_from").val(),
          $("#bp_to").val()
        );
      });

      // Blood Sugar Report
      $("#print_blood_sugar").click(function() {
        generateReport(
          "reports/print_blood_sugar.php",
          $("#blood_sugar_from").val(),
          $("#blood_sugar_to").val()
        );
      });

      // Tetanus Toxoid Report
      $("#print_tetanus").click(function() {
        generateReport(
          "reports/print_general_general_tetanus_toxoid.php",
          $("#tetanus_from").val(),
          $("#tetanus_to").val()
        );
      });
    });
  </script>
</body>
</html>
