<?php
// Include database connection and common functions
include './config/connection.php';
include './common_service/common_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>

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

        <!-- Card: Patient Visits Between Two Dates -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">PATIENT VISITS BETWEEN TWO DATES</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <!-- Generate date textboxes using helper function (defined in common_functions.php) -->
              <?php 
                echo getDateTextBox('From', 'patients_from');
                echo getDateTextBox('To', 'patients_to');
              ?>
              <div class="col-md-2">
                <!-- Empty label for alignment -->
                <label>&nbsp;</label>
                <button type="button" id="print_visits" class="btn btn-primary btn-sm btn-flat btn-block">
                  Generate PDF
                </button>
              </div>
            </div>
          </div>
        </div>
        <!-- End Patient Visits Card -->

        <!-- Card: Disease Based Report Between Two Dates -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">DISEASE BASED REPORT BETWEEN TWO DATES</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <!-- Disease input field -->
              <div class="col-md-3">
                <label>Disease</label>
                <input id="disease" class="form-control form-control-sm rounded-0" />
              </div>
              <!-- Date textboxes for disease report -->
              <?php 
                echo getDateTextBox('From', 'disease_from');
                echo getDateTextBox('To', 'disease_to');
              ?>
              <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" id="print_diseases" class="btn btn-primary btn-sm btn-flat btn-block">
                  Generate PDF
                </button>
              </div>
            </div>
          </div>
        </div>
        <!-- End Disease Based Report Card -->

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
  <script src="plugins/moment/moment.min.js"></script>
  <script src="plugins/daterangepicker/daterangepicker.js"></script>
  <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>

  <script>
    // Highlight the Reports menu in the sidebar
    showMenuSelected("#mnu_reports", "#mi_reports");

    $(document).ready(function() {
      // Initialize datetimepicker for all specified date fields
      $('#patients_from, #patients_to, #disease_from, #disease_to').datetimepicker({
        format: 'L'
      });

      // Generate PDF for Patient Visits Report
      $("#print_visits").click(function() {
        var from = $("#patients_from").val();
        var to = $("#patients_to").val();
        if(from !== '' && to !== '') {
          var win = window.open("print_patients_visits.php?from=" + from + "&to=" + to, "_blank");
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
          var win = window.open("print_diseases.php?from=" + from + "&to=" + to + "&disease=" + disease, "_blank");
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
