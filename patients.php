<?php
// Include database connection and common functions
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';

// Handle form submission to save a new patient
if (isset($_POST['save_Patient'])) {
    // Retrieve and sanitize form inputs
    $patientName = trim($_POST['patient_name']);
    $address     = trim($_POST['address']);
    $purpose     = trim($_POST['purpose']);
    $dateBirth   = trim($_POST['date_of_birth']);
    $phoneNumber = trim($_POST['phone_number']);
    $gender      = $_POST['gender'];

    // Convert date format from MM/DD/YYYY to YYYY-MM-DD
    $dateArr   = explode("/", $dateBirth);
    $dateBirth = $dateArr[2] . '-' . $dateArr[0] . '-' . $dateArr[1];

    // Format patient name and address (capitalize each word)
    $patientName = ucwords(strtolower($patientName));
    $address     = ucwords(strtolower($address));

    // Check if all required fields are provided
    if ($patientName != '' && $address != '' && $purpose != '' && $dateBirth != '' && $phoneNumber != '' && $gender != '') {
        // Prepare INSERT query
        $query = "INSERT INTO `patients`(`patient_name`, `address`, `purpose`, `date_of_birth`, `phone_number`, `gender`)
                  VALUES('$patientName', '$address', '$purpose', '$dateBirth', '$phoneNumber', '$gender');";
        try {
            // Start transaction and execute query
            $con->beginTransaction();
            $stmtPatient = $con->prepare($query);
            $stmtPatient->execute();
            $con->commit();
            $message = 'Patient added successfully.';
        } catch (PDOException $ex) {
            // Rollback on error and output exception details (for debugging only)
            $con->rollback();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        }
    }
    // Redirect with a success or error message
    header("Location:congratulation.php?goto_page=patients.php&message=$message");
    exit;
}

// Retrieve all patients for the listing
try {
    $query = "SELECT `id`, `patient_name`, `address`, `purpose`,
                     DATE_FORMAT(`date_of_birth`, '%d %b %Y') as `date_of_birth`, 
                     `phone_number`, `gender`,
                     DATE_FORMAT(`created_at`, '%d %b %Y %h:%i %p') as `created_at`
              FROM `patients`
              ORDER BY `patient_name` ASC;";
    $stmtPatient1 = $con->prepare($query);
    $stmtPatient1->execute();
} catch (PDOException $ex) {
    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <?php include './config/data_tables_css.php'; ?>
  <!-- Tempus Dominus DateTime Picker CSS -->
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- Favicon -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Patients - Mamatid Health Center System</title>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <!-- Site Wrapper -->
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
              <h1>GENERAL INFORMATION</h1>
            </div>
          </div>
        </div>
      </section>

      <!-- Main Content -->
      <section class="content">
        <!-- Patient Add Form -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">ADD PATIENT</h3>
            <div class="card-tools">
              <!-- Collapse Button -->
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <form method="post">
              <div class="row">
                <!-- Patient Name -->
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Patient Name</label>
                  <input type="text" id="patient_name" name="patient_name" required="required"
                         class="form-control form-control-sm rounded-0"/>
                </div>
                <!-- Address -->
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Address</label>
                  <input type="text" id="address" name="address" required="required"
                         class="form-control form-control-sm rounded-0"/>
                </div>
                <!-- Purpose -->
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Purpose</label>
                  <input type="text" id="purpose" name="purpose" required="required"
                         class="form-control form-control-sm rounded-0"/>
                </div>
                <!-- Date of Birth -->
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <div class="form-group">
                    <label>Date of Birth</label>
                    <div class="input-group date" id="date_of_birth" data-target-input="nearest">
                      <input type="text" class="form-control form-control-sm rounded-0 datetimepicker-input" 
                             data-target="#date_of_birth" name="date_of_birth"
                             data-toggle="datetimepicker" autocomplete="off" />
                      <div class="input-group-append" data-target="#date_of_birth" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Phone Number -->
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Phone Number</label>
                  <input type="text" id="phone_number" name="phone_number" required="required"
                         class="form-control form-control-sm rounded-0"/>
                </div>
                <!-- Gender -->
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Gender</label>
                  <select class="form-control form-control-sm rounded-0" id="gender" name="gender">
                    <?php echo getGender(); ?>
                  </select>
                </div>
              </div>
              <div class="clearfix">&nbsp;</div>
              <div class="row">
                <div class="col-lg-11 col-md-10 col-sm-10 xs-hidden">&nbsp;</div>
                <div class="col-lg-1 col-md-2 col-sm-2 col-xs-12">
                  <button type="submit" id="save_Patient" name="save_Patient" 
                          class="btn btn-primary btn-sm btn-flat btn-block">Save</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </section>

      <!-- Spacer -->
      <br/><br/><br/>

      <!-- Patient List Section -->
      <section class="content">
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">TOTAL PATIENTS</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row table-responsive">
              <table id="all_patients" class="table table-striped dataTable table-bordered dtr-inline" role="grid" aria-describedby="all_patients_info">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Patient Name</th>
                    <th>Address</th>
                    <th>Purpose</th>
                    <th>Date Of Birth</th>
                    <th>Phone Number</th>
                    <th>Gender</th>
                    <th>Created At</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $count = 0;
                  while ($row = $stmtPatient1->fetch(PDO::FETCH_ASSOC)) {
                      $count++;
                  ?>
                  <tr>
                    <td><?php echo $count; ?></td>
                    <td><?php echo $row['patient_name']; ?></td>
                    <td><?php echo $row['address']; ?></td>
                    <td><?php echo $row['purpose']; ?></td>
                    <td><?php echo $row['date_of_birth']; ?></td>
                    <td><?php echo $row['phone_number']; ?></td>
                    <td><?php echo $row['gender']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                      <a href="update_patient.php?id=<?php echo $row['id']; ?>" 
                         class="btn btn-primary btn-sm btn-flat">
                        <i class="fa fa-edit"></i>
                      </a>
                    </td>
                  </tr>
                  <?php
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
          <!-- /.card-footer -->
        </div>
      </section>
    </div>
    <!-- /.content-wrapper -->

    <?php
      // Include the footer and get any message passed via GET
      include './config/footer.php';
      $message = isset($_GET['message']) ? $_GET['message'] : '';
    ?>
    <!-- /.control-sidebar -->
    <?php include './config/site_js_links.php'; ?>
    <?php include './config/data_tables_js.php'; ?>
    <!-- Scripts for date/time picker and DataTable -->
    <script src="plugins/moment/moment.min.js"></script>
    <script src="plugins/daterangepicker/daterangepicker.js"></script>
    <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <script>
      // Highlight the patients menu
      showMenuSelected("#mnu_patients", "#mi_patients");
      
      // Display custom message if available
      var message = '<?php echo $message;?>';
      if(message !== '') {
        showCustomMessage(message);
      }
      
      // Initialize the datetimepicker for Date of Birth
      $('#date_of_birth').datetimepicker({
          format: 'L'
      });
      
      // Initialize the DataTable for patient listing
      $(function () {
        $("#all_patients").DataTable({
          "responsive": true,
          "lengthChange": false,
          "autoWidth": false,
          "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#all_patients_wrapper .col-md-6:eq(0)');
      });
    </script>
</body>
</html>
