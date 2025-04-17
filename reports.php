<?php
// Include database connection and common functions
include './config/connection.php';
include './common_service/common_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <?php include './config/data_tables_css.php'; ?>

  <!-- Tempus Dominus Bootstrap 4 CSS for datetimepicker -->
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- Tab icon -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Reports - Mamatid Health Center System</title>

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
    }
  </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Include Navbar and Sidebar -->
    <?php 
      include './config/header.php';
      include './config/sidebar.php';
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
          <!-- Medicine Inventory Report -->
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Medicine Inventory Report</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-3">
                  <button type="button" id="print_inventory" class="btn btn-primary w-100">
                    <i class="fas fa-file-pdf mr-2"></i>Generate PDF
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Family Planning Report -->
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Family Planning Report</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
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
          </div>

          <!-- Deworming Report -->
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Deworming Report</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
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
          </div>

          <!-- BP Monitoring Report -->
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">BP Monitoring Report</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
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
          </div>

          <!-- Random Blood Sugar Report -->
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Random Blood Sugar Report</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
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
          </div>

          <!-- Tetanus Toxoid Report -->
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Tetanus Toxoid Report</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
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
      </section>
      <!-- End Main Content Section -->
    </div>
    <!-- End Content Wrapper -->

    <!-- Include Footer -->
    <?php include './config/footer.php'; ?>  
  </div>
  <!-- End Wrapper -->

  <!-- Include JS Libraries -->
  <?php include './config/site_js_links.php'; ?>
  <?php include './config/data_tables_js.php'; ?>
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
          "reports/print_tetanus_toxoid.php",
          $("#tetanus_from").val(),
          $("#tetanus_to").val()
        );
      });
    });
  </script>
</body>
</html>
