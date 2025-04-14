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
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>REPORTS</h1>
            </div>
          </div>
        </div>
      </section>

      <!-- Main Content Section -->
      <section class="content">

        <!-- Medicine Inventory Report -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">MEDICINE INVENTORY REPORT</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" id="print_inventory" class="btn btn-primary btn-sm btn-flat btn-block">
                  Generate PDF
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Family Planning Report -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">FAMILY PLANNING REPORT</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <?php 
                echo getDateTextBox('From', 'family_from');
                echo getDateTextBox('To', 'family_to');
              ?>
              <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" id="print_family" class="btn btn-primary btn-sm btn-flat btn-block">
                  Generate PDF
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Deworming Report -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">DEWORMING REPORT</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <?php 
                echo getDateTextBox('From', 'deworming_from');
                echo getDateTextBox('To', 'deworming_to');
              ?>
              <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" id="print_deworming" class="btn btn-primary btn-sm btn-flat btn-block">
                  Generate PDF
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- BP Monitoring Report -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">BP MONITORING REPORT</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <?php 
                echo getDateTextBox('From', 'bp_from');
                echo getDateTextBox('To', 'bp_to');
              ?>
              <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" id="print_bp" class="btn btn-primary btn-sm btn-flat btn-block">
                  Generate PDF
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Random Blood Sugar Report -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">RANDOM BLOOD SUGAR REPORT</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <?php 
                echo getDateTextBox('From', 'blood_sugar_from');
                echo getDateTextBox('To', 'blood_sugar_to');
              ?>
              <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" id="print_blood_sugar" class="btn btn-primary btn-sm btn-flat btn-block">
                  Generate PDF
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tetanus Toxoid Report -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">TETANUS TOXOID REPORT</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <?php 
                echo getDateTextBox('From', 'tetanus_from');
                echo getDateTextBox('To', 'tetanus_to');
              ?>
              <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" id="print_tetanus" class="btn btn-primary btn-sm btn-flat btn-block">
                  Generate PDF
                </button>
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
      // Initialize datetimepicker for all date fields
      $('.datetimepicker-input').each(function() {
        var target = $(this).data('target');
        $(this).datetimepicker({
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
      });

      // Generate PDF for Patient Visits Report
      $("#print_visits").click(function() {
        var from = $("#patients_from").val();
        var to = $("#patients_to").val();
        if(from !== '' && to !== '') {
          var win = window.open("reports/print_patients_visits.php?from=" + from + "&to=" + to, "_blank");
          if(win) {
            win.focus();
          } else {
            showCustomMessage('Please allow popups.');
          }
        }
      });

      // Generate PDF for Disease Based Report
      $("#print_diseases").click(function() {
        var from = $("#disease_from").val();
        var to = $("#disease_to").val();
        var disease = $("#disease").val().trim();
        if(from !== '' && to !== '' && disease !== '') {
          var win = window.open("reports/print_diseases.php?from=" + from + "&to=" + to + "&disease=" + disease, "_blank");
          if(win) {
            win.focus();
          } else {
            showCustomMessage('Please allow popups.');
          }
        }
      });

      // Medicine Inventory Report
      $("#print_inventory").click(function() {
        var win = window.open("reports/print_inventory.php", "_blank");
        if(win) {
          win.focus();
        } else {
          showCustomMessage('Please allow popups.');
        }
      });

      // Family Planning Report
      $("#print_family").click(function() {
        var from = $("#family_from").val();
        var to = $("#family_to").val();
        if(from !== '' && to !== '') {
          var win = window.open("reports/print_family_planning.php?from=" + from + "&to=" + to, "_blank");
          if(win) {
            win.focus();
          } else {
            showCustomMessage('Please allow popups.');
          }
        }
      });

      // Deworming Report
      $("#print_deworming").click(function() {
        var from = $("#deworming_from").val();
        var to = $("#deworming_to").val();
        if(from !== '' && to !== '') {
          var win = window.open("reports/print_deworming.php?from=" + from + "&to=" + to, "_blank");
          if(win) {
            win.focus();
          } else {
            showCustomMessage('Please allow popups.');
          }
        }
      });

      // BP Monitoring Report
      $("#print_bp").click(function() {
        var from = $("#bp_from").val();
        var to = $("#bp_to").val();
        if(from !== '' && to !== '') {
          var win = window.open("reports/print_bp_monitoring.php?from=" + from + "&to=" + to, "_blank");
          if(win) {
            win.focus();
          } else {
            showCustomMessage('Please allow popups.');
          }
        }
      });

      // Random Blood Sugar Report
      $("#print_blood_sugar").click(function() {
        var from = $("#blood_sugar_from").val();
        var to = $("#blood_sugar_to").val();
        if(from !== '' && to !== '') {
          var win = window.open("reports/print_blood_sugar.php?from=" + from + "&to=" + to, "_blank");
          if(win) {
            win.focus();
          } else {
            showCustomMessage('Please allow popups.');
          }
        }
      });

      // Tetanus Toxoid Report
      $("#print_tetanus").click(function() {
        var from = $("#tetanus_from").val();
        var to = $("#tetanus_to").val();
        if(from !== '' && to !== '') {
          var win = window.open("reports/print_tetanus_toxoid.php?from=" + from + "&to=" + to, "_blank");
          if(win) {
            win.focus();
          } else {
            showCustomMessage('Please allow popups.');
          }
        }
      });
    });
  </script>
</body>
</html>
