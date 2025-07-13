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

    /* Card Styling */
    .card {
      border: none;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      border-radius: 15px;
      margin-bottom: 30px;
    }

    .card-outline {
      border-top: 3px solid var(--primary-color);
    }

    .card-header {
      background: white;
      padding: 1.5rem;
      border-bottom: 1px solid #eee;
    }

    .card-header h3 {
      margin: 0;
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--dark-color);
    }

    .card-body {
      padding: 1.5rem;
    }

    /* Form Controls */
    .form-control {
      height: calc(2.5rem + 2px);
      border-radius: 8px;
      border: 2px solid #e4e6ef;
      padding: 0.625rem 1rem;
      font-size: 1rem;
      transition: all var(--transition-speed);
    }

    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
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

    /* DateTimePicker Styling */
    .datetimepicker-input {
      background-color: white;
    }

    .tempusdominus-bootstrap-4 .datepicker-days table {
      border-radius: 8px;
      overflow: hidden;
    }

    .tempusdominus-bootstrap-4 .datepicker-days th,
    .tempusdominus-bootstrap-4 .datepicker-days td {
      padding: 0.75rem;
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

    /* Modern Tabs Styling */
    .nav-tabs {
      border: none;
      margin-bottom: 20px;
      gap: 10px;
    }
    .nav-tabs .nav-item {
      margin: 0;
    }
    .nav-tabs .nav-link {
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      font-weight: 500;
      color: #7E8299;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .nav-tabs .nav-link:hover {
      color: #3699FF;
      background: rgba(54, 153, 255, 0.1);
      border-color: rgba(54, 153, 255, 0.2);
      transform: translateY(-1px);
    }
    .nav-tabs .nav-link.active {
      color: #3699FF;
      background: rgba(54, 153, 255, 0.15);
      border-color: rgba(54, 153, 255, 0.3);
      box-shadow: 0 4px 15px rgba(54, 153, 255, 0.2);
    }
    .nav-tabs .nav-link i {
      font-size: 1rem;
    }
    .tab-content {
      background: transparent;
    }
    .tab-pane {
      border-radius: 12px;
      padding: 20px;
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(0, 0, 0, 0.05);
      opacity: 0;
      transform: translateY(10px);
      transition: all 0.3s ease;
    }
    .tab-pane.active {
      opacity: 1;
      transform: translateY(0);
    }

    /* Alert Styling */
    .alert {
      border: none;
      border-radius: 12px;
      padding: 1rem 1.5rem;
      margin-bottom: 1.5rem;
    }

    .alert-info {
      background: linear-gradient(135deg, rgba(137, 80, 252, 0.1) 0%, rgba(54, 153, 255, 0.1) 100%);
      color: #8950FC;
      border-left: 4px solid #8950FC;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .card-header {
        padding: 1rem;
      }

      .card-body {
        padding: 1rem;
      }

      .btn {
        width: 100%;
        margin-bottom: 0.5rem;
      }

      .form-group {
        margin-bottom: 1rem;
      }

      .nav-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      .nav-tabs .nav-link {
        white-space: nowrap;
        padding: 10px 16px;
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
          <div class="row align-items-center mb-4">
            <div class="col-12 col-md-6" style="padding-left: 20px;">
              <h1>Reports</h1>
            </div>
          </div>
        </div>
      </section>

      <!-- Main Content Section -->
      <section class="content">
        <div class="container-fluid">
          <!-- Reports Overview with Tabs -->
          <div class="card card-outline card-primary mb-4">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-chart-bar mr-2"></i>
                Reports Management
              </h3>
            </div>
            <div class="card-body">
              <!-- Tab Navigation -->
              <ul class="nav nav-tabs" id="reportsTabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="medicine-inventory-tab" data-toggle="tab" href="#medicine-inventory" role="tab">
                    <i class="fas fa-pills mr-2"></i>Medicine Inventory
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="family-planning-tab" data-toggle="tab" href="#family-planning" role="tab">
                    <i class="fas fa-users mr-2"></i>Family Planning
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="family-members-tab" data-toggle="tab" href="#family-members" role="tab">
                    <i class="fas fa-user-friends mr-2"></i>Family Members
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="deworming-tab" data-toggle="tab" href="#deworming" role="tab">
                    <i class="fas fa-shield-virus mr-2"></i>Deworming
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="bp-monitoring-tab" data-toggle="tab" href="#bp-monitoring" role="tab">
                    <i class="fas fa-heartbeat mr-2"></i>BP Monitoring
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="blood-sugar-tab" data-toggle="tab" href="#blood-sugar" role="tab">
                    <i class="fas fa-tint mr-2"></i>Blood Sugar
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="tetanus-toxoid-tab" data-toggle="tab" href="#tetanus-toxoid" role="tab">
                    <i class="fas fa-syringe mr-2"></i>Tetanus Toxoid
                  </a>
                </li>
              </ul>
              
              <!-- Tab Content -->
              <div class="tab-content" id="reportsTabsContent">
                <!-- Medicine Inventory Report Tab -->
                <div class="tab-pane fade show active" id="medicine-inventory" role="tabpanel">
                  <div class="row mt-4">
                    <div class="col-md-12">
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Medicine Inventory Report:</strong> Generate a comprehensive report of all medicine inventory including stock levels, expiry dates, and transaction history.
                      </div>
                      <div class="text-center">
                        <button type="button" id="print_inventory" class="btn btn-primary btn-lg">
                          <i class="fas fa-file-pdf mr-2"></i>Generate Medicine Inventory Report
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Family Planning Report Tab -->
                <div class="tab-pane fade" id="family-planning" role="tabpanel">
                  <div class="row mt-4">
                    <div class="col-md-12">
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Family Planning Report:</strong> Generate reports for family planning services within a specified date range.
                      </div>
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
                        <div class="col-md-4 mb-3">
                          <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" id="print_family" class="btn btn-primary btn-lg w-100">
                              <i class="fas fa-file-pdf mr-2"></i>Generate Report
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Family Members Report Tab -->
                <div class="tab-pane fade" id="family-members" role="tabpanel">
                  <div class="row mt-4">
                    <div class="col-md-12">
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Family Members Report:</strong> Generate reports for family members registration within a specified date range.
                      </div>
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
                        <div class="col-md-4 mb-3">
                          <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" id="print_family_members" class="btn btn-primary btn-lg w-100">
                              <i class="fas fa-file-pdf mr-2"></i>Generate Report
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Deworming Report Tab -->
                <div class="tab-pane fade" id="deworming" role="tabpanel">
                  <div class="row mt-4">
                    <div class="col-md-12">
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Deworming Report:</strong> Generate reports for deworming services within a specified date range.
                      </div>
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
                        <div class="col-md-4 mb-3">
                          <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" id="print_deworming" class="btn btn-primary btn-lg w-100">
                              <i class="fas fa-file-pdf mr-2"></i>Generate Report
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- BP Monitoring Report Tab -->
                <div class="tab-pane fade" id="bp-monitoring" role="tabpanel">
                  <div class="row mt-4">
                    <div class="col-md-12">
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>BP Monitoring Report:</strong> Generate reports for blood pressure monitoring within a specified date range.
                      </div>
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
                        <div class="col-md-4 mb-3">
                          <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" id="print_bp" class="btn btn-primary btn-lg w-100">
                              <i class="fas fa-file-pdf mr-2"></i>Generate Report
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Random Blood Sugar Report Tab -->
                <div class="tab-pane fade" id="blood-sugar" role="tabpanel">
                  <div class="row mt-4">
                    <div class="col-md-12">
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Random Blood Sugar Report:</strong> Generate reports for blood sugar monitoring within a specified date range.
                      </div>
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
                        <div class="col-md-4 mb-3">
                          <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" id="print_blood_sugar" class="btn btn-primary btn-lg w-100">
                              <i class="fas fa-file-pdf mr-2"></i>Generate Report
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Tetanus Toxoid Report Tab -->
                <div class="tab-pane fade" id="tetanus-toxoid" role="tabpanel">
                  <div class="row mt-4">
                    <div class="col-md-12">
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Tetanus Toxoid Report:</strong> Generate reports for tetanus toxoid vaccinations within a specified date range.
                      </div>
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
                        <div class="col-md-4 mb-3">
                          <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" id="print_tetanus" class="btn btn-primary btn-lg w-100">
                              <i class="fas fa-file-pdf mr-2"></i>Generate Report
                            </button>
                          </div>
                        </div>
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
