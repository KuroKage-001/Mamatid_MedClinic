<?php
// Include necessary files for DB connection and common functions
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';

// Handle form submission for updating a patient
if (isset($_POST['save_Patient'])) {

    // Retrieve and sanitize form inputs
    $hiddenId     = $_POST['hidden_id'];
    $patientName  = trim($_POST['patient_name']);
    $address      = trim($_POST['address']);
    $purpose      = trim($_POST['purpose']); // using "purpose" instead of "cnic"
    $dateBirthRaw = trim($_POST['date_of_birth']);
    $phoneNumber  = trim($_POST['phone_number']);
    $gender       = $_POST['gender'];

    // Convert Date of Birth from mm/dd/yyyy to yyyy-mm-dd
    $dateArr = explode("/", $dateBirthRaw);
    if(count($dateArr) === 3) {
        $dateBirth = $dateArr[2] . '-' . $dateArr[0] . '-' . $dateArr[1];
    } else {
        $dateBirth = '';
    }

    // Format name and address to have each word capitalized
    $patientName = ucwords(strtolower($patientName));
    $address     = ucwords(strtolower($address));

    // Check that all required fields are not empty
    if ($patientName != '' && $address != '' && $purpose != '' && $dateBirth != '' && $phoneNumber != '' && $gender != '') {
        // Build the UPDATE query (for better security, consider binding parameters)
        $query = "UPDATE `patients` 
                  SET `patient_name` = '$patientName', 
                      `address` = '$address', 
                      `purpose` = '$purpose', 
                      `date_of_birth` = '$dateBirth', 
                      `phone_number` = '$phoneNumber', 
                      `gender` = '$gender' 
                  WHERE `id` = $hiddenId;";
        try {
            // Begin transaction
            $con->beginTransaction();
            $stmtPatient = $con->prepare($query);
            $stmtPatient->execute();
            $con->commit();
            $message = 'Patient updated successfully.';
        } catch (PDOException $ex) {
            $con->rollback();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        }
    }
    // Redirect to congratulation page with a message and go back to patients list
    header("Location:congratulation.php?goto_page=patients.php&message=$message");
    exit;
}

// Retrieve the patient record to pre-populate the form using the GET parameter 'id'
try {
    $id = $_GET['id'];
    $query = "SELECT `id`, `patient_name`, `address`, 
                     `purpose`, DATE_FORMAT(`date_of_birth`, '%m/%d/%Y') as `date_of_birth`,  
                     `phone_number`, `gender` 
              FROM `patients` 
              WHERE `id` = $id;";
    $stmtPatient1 = $con->prepare($query);
    $stmtPatient1->execute();
    $row = $stmtPatient1->fetch(PDO::FETCH_ASSOC);
    $gender = $row['gender'];
    $dob    = $row['date_of_birth']; 
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
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <title>Update Patient Informations - Mamatid Health Center System</title>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar and Sidebar -->
    <?php 
      include './config/header.php';
      include './config/sidebar.php';
    ?>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Page Header -->
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>PATIENT INFORMATION</h1>
            </div>
          </div>
        </div>
      </section>

      <!-- Main Content Section -->
      <section class="content">
        <!-- Update Patient Form Card -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">UPDATE GENERAL INFORMATION</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <form method="post">
              <!-- Hidden field to store patient ID -->
              <input type="hidden" name="hidden_id" value="<?php echo $row['id']; ?>">
              <div class="row">
                <!-- Patient Name -->
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Patient Name</label>
                  <input type="text" id="patient_name" name="patient_name" required="required"
                    class="form-control form-control-sm rounded-0" value="<?php echo $row['patient_name']; ?>" />
                </div>
                <!-- Address -->
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Address</label> 
                  <input type="text" id="address" name="address" required="required"
                    class="form-control form-control-sm rounded-0" value="<?php echo $row['address']; ?>" />
                </div>
                <!-- Purpose -->
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Purpose</label>
                  <input type="text" id="purpose" name="purpose" required="required"
                    class="form-control form-control-sm rounded-0" value="<?php echo $row['purpose']; ?>" />
                </div>
                <!-- Date of Birth -->
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <div class="form-group">
                    <label>Date of Birth</label>
                    <div class="input-group date" id="date_of_birth" data-target-input="nearest">
                      <input type="text" class="form-control form-control-sm rounded-0 datetimepicker-input" 
                             data-target="#date_of_birth" name="date_of_birth" value="<?php echo $dob; ?>" />
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
                    class="form-control form-control-sm rounded-0" value="<?php echo $row['phone_number']; ?>" />
                </div>
                <!-- Gender -->
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Gender</label>
                  <select class="form-control form-control-sm rounded-0" id="gender" name="gender">
                    <?php echo getGender($gender); ?>
                  </select>
                </div>
              </div>
              <div class="clearfix">&nbsp;</div>
              <div class="row">
                <div class="col-lg-11 col-md-10 col-sm-10">&nbsp;</div>
                <div class="col-lg-1 col-md-2 col-sm-2 col-xs-2">
                  <button type="submit" id="save_Patient" name="save_Patient" 
                          class="btn btn-primary btn-sm btn-flat btn-block">Save</button>
                </div>
              </div>
            </form>
          </div>        
        </div>      
      </section>
      <br/><br/><br/>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
    <?php 
      include './config/footer.php';
      // Reset $message if provided in URL query
      $message = isset($_GET['message']) ? $_GET['message'] : '';
    ?>  
    <!-- /.control-sidebar -->
  </div>
  <!-- ./wrapper -->
  
  <?php include './config/site_js_links.php'; ?>
  <?php include './config/data_tables_js.php'; ?>
  <script src="plugins/moment/moment.min.js"></script>
  <script src="plugins/daterangepicker/daterangepicker.js"></script>
  <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
  <script>
    // Highlight the selected menu item in the sidebar
    showMenuSelected("#mnu_patients", "#mi_patients");

    // If a message is set, show a custom message
    var message = '<?php echo $message; ?>';
    if(message !== '') {
      showCustomMessage(message);
    }
    
    // Initialize the datetimepicker for Date of Birth input
    $('#date_of_birth').datetimepicker({
      format: 'L'
    });
    
    // Initialize DataTables (if needed) for a table with id "all_patients"
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
