<?php
// Include the database connection and common functions
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';
// Retrieve patients list as HTML <option> elements using the getPatients() function
$patients = getPatients($con);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <?php include './config/data_tables_css.php'; ?>
  <!-- Logo for the tab bar -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Patient History - Mamatid Health Center System</title>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar and Sidebar -->
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
              <h1>PATIENT HISTORY</h1>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>

      <!-- Main content -->
      <section class="content">
        <!-- Search Card -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">SEARCH PATIENT HISTORY</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- Search Form Row -->
            <div class="row">
              <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <select id="patient" class="form-control form-control-sm rounded-0">
                  <?php echo $patients; ?>
                </select>
              </div>
              <div class="col-lg-1 col-md-2 col-sm-4 col-xs-12">
                <button type="button" id="search" class="btn btn-primary btn-sm btn-flat btn-block">
                  Search
                </button>
              </div>
            </div>

            <!-- Spacing -->
            <div class="clearfix">&nbsp;</div>
            <div class="clearfix">&nbsp;</div>

            <!-- Patient History Tabs -->
            <div class="row">
              <div class="col-md-12">
                <ul class="nav nav-tabs" id="historyTabs" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" id="visits-tab" data-toggle="tab" href="#visits" role="tab">Visits</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="family-tab" data-toggle="tab" href="#family" role="tab">Family Planning</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="deworming-tab" data-toggle="tab" href="#deworming" role="tab">Deworming</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="bp-tab" data-toggle="tab" href="#bp" role="tab">BP Monitoring</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="blood-sugar-tab" data-toggle="tab" href="#blood-sugar" role="tab">Blood Sugar</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tetanus-tab" data-toggle="tab" href="#tetanus" role="tab">Tetanus Toxoid</a>
                  </li>
                </ul>

                <div class="tab-content" id="historyTabsContent">
                  <!-- Visits History -->
                  <div class="tab-pane fade show active" id="visits" role="tabpanel">
                    <div class="table-responsive">
                      <table id="visits_history" class="table table-striped table-bordered">
                        <colgroup>
                          <col width="5%">
                          <col width="10%">
                          <col width="10%">
                          <col width="10%">
                          <col width="10%">
                          <col width="10%">
                          <col width="15%">
                          <col width="10%">
                          <col width="5%">
                          <col width="5%">
                        </colgroup>
                        <thead>
                          <tr class="bg-gradient-primary text-light">
                            <th class="p-1 text-center">S.No</th>
                            <th class="p-1 text-center">Visit Date</th>
                            <th class="p-1 text-center">Disease</th>
                            <th class="p-1 text-center">Alcohol</th>
                            <th class="p-1 text-center">Smoke</th>
                            <th class="p-1 text-center">Obese</th>
                            <th class="p-1 text-center">Medicine</th>
                            <th class="p-1 text-center">Packing</th>
                            <th class="p-1 text-center">QTY</th>
                            <th class="p-1 text-center">Dosage</th>
                          </tr>
                        </thead>
                        <tbody id="visits_data">
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Family Planning History -->
                  <div class="tab-pane fade" id="family" role="tabpanel">
                    <div class="table-responsive">
                      <table id="family_history" class="table table-striped table-bordered">
                        <colgroup>
                          <col width="5%">
                          <col width="20%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                        </colgroup>
                        <thead>
                          <tr class="bg-gradient-primary text-light">
                            <th class="p-1 text-center">S.No</th>
                            <th class="p-1 text-center">Name</th>
                            <th class="p-1 text-center">Date</th>
                            <th class="p-1 text-center">Age</th>
                            <th class="p-1 text-center">Address</th>
                            <th class="p-1 text-center">Civil Status</th>
                            <th class="p-1 text-center">Educational Attainment</th>
                          </tr>
                        </thead>
                        <tbody id="family_data">
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Deworming History -->
                  <div class="tab-pane fade" id="deworming" role="tabpanel">
                    <div class="table-responsive">
                      <table id="deworming_history" class="table table-striped table-bordered">
                        <colgroup>
                          <col width="5%">
                          <col width="20%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                        </colgroup>
                        <thead>
                          <tr class="bg-gradient-primary text-light">
                            <th class="p-1 text-center">S.No</th>
                            <th class="p-1 text-center">Name</th>
                            <th class="p-1 text-center">Date</th>
                            <th class="p-1 text-center">Age</th>
                            <th class="p-1 text-center">Birthday</th>
                            <th class="p-1 text-center">Sex</th>
                            <th class="p-1 text-center">Address</th>
                          </tr>
                        </thead>
                        <tbody id="deworming_data">
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- BP Monitoring History -->
                  <div class="tab-pane fade" id="bp" role="tabpanel">
                    <div class="table-responsive">
                      <table id="bp_history" class="table table-striped table-bordered">
                        <colgroup>
                          <col width="5%">
                          <col width="20%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                        </colgroup>
                        <thead>
                          <tr class="bg-gradient-primary text-light">
                            <th class="p-1 text-center">S.No</th>
                            <th class="p-1 text-center">Name</th>
                            <th class="p-1 text-center">Date</th>
                            <th class="p-1 text-center">Age</th>
                            <th class="p-1 text-center">Address</th>
                            <th class="p-1 text-center">BP Reading</th>
                            <th class="p-1 text-center">Classification</th>
                          </tr>
                        </thead>
                        <tbody id="bp_data">
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Blood Sugar History -->
                  <div class="tab-pane fade" id="blood-sugar" role="tabpanel">
                    <div class="table-responsive">
                      <table id="blood_sugar_history" class="table table-striped table-bordered">
                        <colgroup>
                          <col width="5%">
                          <col width="20%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                        </colgroup>
                        <thead>
                          <tr class="bg-gradient-primary text-light">
                            <th class="p-1 text-center">S.No</th>
                            <th class="p-1 text-center">Name</th>
                            <th class="p-1 text-center">Date</th>
                            <th class="p-1 text-center">Age</th>
                            <th class="p-1 text-center">Address</th>
                            <th class="p-1 text-center">Result</th>
                            <th class="p-1 text-center">Classification</th>
                          </tr>
                        </thead>
                        <tbody id="blood_sugar_data">
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Tetanus Toxoid History -->
                  <div class="tab-pane fade" id="tetanus" role="tabpanel">
                    <div class="table-responsive">
                      <table id="tetanus_history" class="table table-striped table-bordered">
                        <colgroup>
                          <col width="5%">
                          <col width="20%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                          <col width="15%">
                        </colgroup>
                        <thead>
                          <tr class="bg-gradient-primary text-light">
                            <th class="p-1 text-center">S.No</th>
                            <th class="p-1 text-center">Name</th>
                            <th class="p-1 text-center">Date</th>
                            <th class="p-1 text-center">Age</th>
                            <th class="p-1 text-center">Address</th>
                            <th class="p-1 text-center">Dose</th>
                            <th class="p-1 text-center">Next Visit</th>
                          </tr>
                        </thead>
                        <tbody id="tetanus_data">
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <?php include './config/footer.php'; ?>
    <!-- /.control-sidebar -->
  </div>
  <!-- ./wrapper -->

  <?php include './config/site_js_links.php'; ?>
  <?php include './config/data_tables_js.php'; ?>

  <!-- Custom Script for Loading Patient History via AJAX -->
  <script>
    // Highlight the correct sidebar menu items
    showMenuSelected("#mnu_patients", "#mi_patient_history");

    $(document).ready(function() {
      // Initialize all DataTables
      var visitsTable = $('#visits_history').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": false,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true
      });

      var familyTable = $('#family_history').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": false,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true
      });

      var dewormingTable = $('#deworming_history').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": false,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true
      });

      var bpTable = $('#bp_history').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": false,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true
      });

      var bloodSugarTable = $('#blood_sugar_history').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": false,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true
      });

      var tetanusTable = $('#tetanus_history').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": false,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true
      });

      // Search button click handler
      $("#search").click(function() {
        var patientId = $("#patient").val();
        if(patientId !== '') {
          // Load visits history
          $.ajax({
            url: "ajax/get_patient_history.php",
            type: 'GET',
            data: { 'patient_id': patientId },
            cache: false,
            async: false,
            success: function(data, status, xhr) {
              $("#visits_data").html(data);
              visitsTable.draw();
            }
          });

          // Load family planning history
          $.ajax({
            url: "ajax/get_family_history.php",
            type: 'GET',
            data: { 'patient_id': patientId },
            cache: false,
            async: false,
            success: function(data, status, xhr) {
              $("#family_data").html(data);
              familyTable.draw();
            }
          });

          // Load deworming history
          $.ajax({
            url: "ajax/get_deworming_history.php",
            type: 'GET',
            data: { 'patient_id': patientId },
            cache: false,
            async: false,
            success: function(data, status, xhr) {
              $("#deworming_data").html(data);
              dewormingTable.draw();
            }
          });

          // Load BP monitoring history
          $.ajax({
            url: "ajax/get_bp_history.php",
            type: 'GET',
            data: { 'patient_id': patientId },
            cache: false,
            async: false,
            success: function(data, status, xhr) {
              $("#bp_data").html(data);
              bpTable.draw();
            }
          });

          // Load blood sugar history
          $.ajax({
            url: "ajax/get_blood_sugar_history.php",
            type: 'GET',
            data: { 'patient_id': patientId },
            cache: false,
            async: false,
            success: function(data, status, xhr) {
              $("#blood_sugar_data").html(data);
              bloodSugarTable.draw();
            }
          });

          // Load tetanus toxoid history
          $.ajax({
            url: "ajax/get_tetanus_history.php",
            type: 'GET',
            data: { 'patient_id': patientId },
            cache: false,
            async: false,
            success: function(data, status, xhr) {
              $("#tetanus_data").html(data);
              tetanusTable.draw();
            }
          });
        }
      });
    });
  </script>
</body>
</html>
