<?php
include './config/connection.php';
include './common_service/common_functions.php';

// Initialize message variable to store user feedback
$message = '';

if(isset($_POST['submit'])) {

  // Retrieve posted form values
  $patientId     = $_POST['patient'];
  $visitDate     = $_POST['visit_date'];
  $nextVisitDate = $_POST['next_visit_date'];
  $bp            = $_POST['bp'];
  $weight        = $_POST['weight'];
  $disease       = $_POST['disease'];

  // Retrieve new checkbox values (1 if checked, 0 otherwise)
  $alcohol = isset($_POST['alcohol']) ? 1 : 0;
  $smoke   = isset($_POST['smoke']) ? 1 : 0;
  $obese   = isset($_POST['obese']) ? 1 : 0;

  // Fix: assign default empty arrays if not set for medicines data
  $medicineDetailIds = $_POST['medicineDetailIds'] ?? [];
  $quantities        = $_POST['quantities'] ?? [];
  $dosages           = $_POST['dosages'] ?? [];

  // Convert visit date from mm/dd/yyyy to yyyy-mm-dd format
  $visitDateArr = explode("/", $visitDate);
  $visitDate = $visitDateArr[2].'-'.$visitDateArr[0].'-'.$visitDateArr[1];

  // Convert next visit date if provided
  if($nextVisitDate != '') {
    $nextVisitDateArr = explode("/", $nextVisitDate);
    $nextVisitDate = $nextVisitDateArr[2].'-'.$nextVisitDateArr[0].'-'.$nextVisitDateArr[1];
  }

  try {
    // Start transaction to ensure data consistency across multiple inserts
    $con->beginTransaction();

    // Insert a new record into patient_visits with the checkbox fields included
    $queryVisit = "INSERT INTO `patient_visits`(`visit_date`, 
      `next_visit_date`, `bp`, `weight`, `disease`, `patient_id`, `alcohol`, `smoke`, `obese`) 
      VALUES('$visitDate', 
      nullif('$nextVisitDate', ''), 
      '$bp', '$weight', '$disease', $patientId, '$alcohol', '$smoke', '$obese');";
    $stmtVisit = $con->prepare($queryVisit);
    $stmtVisit->execute();

    // Retrieve the last inserted patient visit id for linking medication history
    $lastInsertId = $con->lastInsertId(); // latest patient visit id

    // Now store data in patient_medication_history for each medicine detail provided
    $size = sizeof($medicineDetailIds);
    $curMedicineDetailId = 0;
    $curQuantity = 0;
    $curDosage = 0;

    // Loop through each provided medicine detail to insert corresponding medication history records
    for($i = 0; $i < $size; $i++) {
      $curMedicineDetailId = $medicineDetailIds[$i];
      $curQuantity = $quantities[$i];
      $curDosage = $dosages[$i];

      // Insert each medication history record linked to the patient visit
      $qeuryMedicationHistory = "INSERT INTO `patient_medication_history`(
        `patient_visit_id`,
        `medicine_details_id`, `quantity`, `dosage`)
        VALUES($lastInsertId, $curMedicineDetailId, $curQuantity, '$curDosage');";
      $stmtDetails = $con->prepare($qeuryMedicationHistory);
      $stmtDetails->execute();
    }

    // Commit the transaction if all inserts were successful
    $con->commit();
    $message = 'Patient Medication stored successfully.';
  } catch(PDOException $ex) {
    // Roll back the transaction if any error occurred
    $con->rollback();
    echo $ex->getTraceAsString();
    echo $ex->getMessage();
    exit;
  }

  // Redirect to congratulation page with feedback message via URL parameter
  header("location:congratulation.php?goto_page=new_prescription.php&message=$message");
  exit;
}

// Retrieve patients and medicines list for the dropdown selections
$patients = getPatients($con);
$medicines = getMedicines($con);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php' ?>
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- Logo for the tab bar -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Blood Pressure - Mamatid Health Center</title>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar -->
    <?php include './config/header.php';
          include './config/sidebar.php'; ?>  
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>BLOOD PRESSURE INFORMATION</h1>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>
      <!-- Main content -->
      <section class="content">
        <!-- Default box for adding blood pressure and prescription information -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">ADD BLOOD PRESSURE</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- best practices-->
            <form method="post">
              <div class="row">
                <!-- Patient selection dropdown -->
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <label>Select Patient</label>
                  <select id="patient" name="patient" class="form-control form-control-sm rounded-0" required="required">
                    <?php echo $patients; ?>
                  </select>
                </div>
                <!-- Visit Date input with datetimepicker -->
                <div class="col-lg-3 col-md-3 col-sm-4 col-xs-10">
                  <div class="form-group">
                    <label>Visit Date</label>
                    <div class="input-group date" id="visit_date" data-target-input="nearest">
                      <input type="text" class="form-control form-control-sm rounded-0 datetimepicker-input" data-target="#visit_date" name="visit_date" required="required" data-toggle="datetimepicker" autocomplete="off"/>
                      <div class="input-group-append" data-target="#visit_date" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Next Visit Date input with datetimepicker -->
                <div class="col-lg-3 col-md-3 col-sm-4 col-xs-10">
                  <div class="form-group">
                    <label>Next Visit Date</label>
                    <div class="input-group date" id="next_visit_date" data-target-input="nearest">
                      <input type="text" class="form-control form-control-sm rounded-0 datetimepicker-input" data-target="#next_visit_date" name="next_visit_date" data-toggle="datetimepicker" autocomplete="off"/>
                      <div class="input-group-append" data-target="#next_visit_date" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="clearfix">&nbsp;</div>
                <!-- BP, Weight, and Disease input fields -->
                <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
                  <label>BP</label>
                  <input id="bp" class="form-control form-control-sm rounded-0" name="bp" required="required" />
                </div>
                <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
                  <label>Weight</label>
                  <input id="weight" name="weight" class="form-control form-control-sm rounded-0" required="required" />
                </div>
                <div class="col-lg-8 col-md-8 col-sm-6 col-xs-12">
                  <label>Disease</label>
                  <input id="disease" required="required" name="disease" class="form-control form-control-sm rounded-0" />
                </div>
              </div>

              <!-- New Row: Checkboxes for Alcohol, Smoke, and Obese -->
              <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="alcohol" name="alcohol" value="1">
                    <label class="form-check-label" for="alcohol">Alcohol</label>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="smoke" name="smoke" value="1">
                    <label class="form-check-label" for="smoke">Smoke</label>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="obese" name="obese" value="1">
                    <label class="form-check-label" for="obese">Obese</label>
                  </div>
                </div>
              </div>

              <div class="col-md-12"><hr /></div>
              <div class="clearfix">&nbsp;</div>
              <!-- Row for selecting medicine details to add to the prescription list -->
              <div class="row">
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                  <label>Select Medicine</label>
                  <select id="medicine" class="form-control form-control-sm rounded-0">
                    <?php echo $medicines; ?>
                  </select>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                  <label>Select Packing</label>
                  <select id="packing" class="form-control form-control-sm rounded-0">
                  </select>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
                  <label>Quantity</label>
                  <input id="quantity" class="form-control form-control-sm rounded-0" />
                </div>
                <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
                  <label>Dosage</label>
                  <input id="dosage" class="form-control form-control-sm rounded-0" />
                </div>
                <div class="col-lg-1 col-md-1 col-sm-6 col-xs-12">
                  <label>&nbsp;</label>
                  <button id="add_to_list" type="button" class="btn btn-primary btn-sm btn-flat btn-block">
                    <i class="fa fa-plus"></i>
                  </button>
                </div>
              </div>

              <div class="clearfix">&nbsp;</div>
              <!-- Table to display the current list of medicines added -->
              <div class="row table-responsive">
                <table id="medication_list" class="table table-striped table-bordered">
                  <colgroup>
                    <col width="10%">
                    <col width="50%">
                    <col width="10%">
                    <col width="10%">
                    <col width="15%">
                    <col width="5%">
                  </colgroup>
                  <thead class="bg-primary">
                    <tr>
                      <th>S.No</th>
                      <th>Medicine Name</th>
                      <th>Packing</th>
                      <th>QTY</th>
                      <th>Dosage</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody id="current_medicines_list">
                  </tbody>
                </table>
              </div>

              <div class="clearfix">&nbsp;</div>
              <!-- Submit button to save the new prescription -->
              <div class="row">
                <div class="col-md-10">&nbsp;</div>
                <div class="col-md-2">
                  <button type="submit" id="submit" name="submit" class="btn btn-primary btn-sm btn-flat btn-block">Save</button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <!-- /.card -->
      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <?php include './config/footer.php';
    $message = '';
    if(isset($_GET['message'])) {
      $message = $_GET['message'];
    }
    ?>  
    <!-- /.control-sidebar -->
  </div>
  <!-- ./wrapper -->

  <?php include './config/site_js_links.php'; ?>

  <!-- Include necessary JS libraries for datetimepicker and date range functionality -->
  <script src="plugins/moment/moment.min.js"></script>
  <script src="plugins/daterangepicker/daterangepicker.js"></script>
  <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>

  <script>
    // Initialize serial counter for listing medicines
    var serial = 1;
    // Highlight the active menu items for patients and new prescription
    showMenuSelected("#mnu_patients", "#mi_new_prescription");

    var message = '<?php echo $message;?>';
    if(message !== '') {
      showCustomMessage(message);
    }

    $(document).ready(function() {
      // Add consistent padding and alignment classes to table header and cells
      $('#medication_list').find('td').addClass("px-2 py-1 align-middle");
      $('#medication_list').find('th').addClass("p-1 align-middle");
      
      // Initialize datetimepicker for visit and next visit dates
      $('#visit_date, #next_visit_date').datetimepicker({
        format: 'L'
      });

      // When a medicine is selected, trigger AJAX request to fetch corresponding packings
      $("#medicine").change(function() {
        var medicineId = $(this).val();
        if(medicineId !== '') {
          $.ajax({
            url: "ajax/get_packings.php",
            type: 'GET', 
            data: { 'medicine_id': medicineId },
            cache:false,
            async:false,
            success: function (data, status, xhr) {
              // Populate the packing dropdown with retrieved options
              $("#packing").html(data);
            },
            error: function (jqXhr, textStatus, errorMessage) {
              showCustomMessage(errorMessage);
            }
          });
        }
      });

      // When clicking "add_to_list", validate inputs and append a new row to the medication list
      $("#add_to_list").click(function() {
        var medicineId = $("#medicine").val();
        var medicineName = $("#medicine option:selected").text();
        
        var medicineDetailId = $("#packing").val();
        var packing = $("#packing option:selected").text();

        var quantity = $("#quantity").val().trim();
        var dosage = $("#dosage").val().trim();

        var oldData = $("#current_medicines_list").html();

        // Ensure all required fields are provided before adding to the list
        if(medicineName !== '' && packing !== '' && quantity !== '' && dosage !== '') {
          var inputs = '';
          inputs += '<input type="hidden" name="medicineDetailIds[]" value="'+medicineDetailId+'" />';
          inputs += '<input type="hidden" name="quantities[]" value="'+quantity+'" />';
          inputs += '<input type="hidden" name="dosages[]" value="'+dosage+'" />';

          var tr = '<tr>';
          tr += '<td class="px-2 py-1 align-middle">'+serial+'</td>';
          tr += '<td class="px-2 py-1 align-middle">'+medicineName+'</td>';
          tr += '<td class="px-2 py-1 align-middle">'+packing+'</td>';
          tr += '<td class="px-2 py-1 align-middle">'+quantity+'</td>';
          tr += '<td class="px-2 py-1 align-middle">'+dosage + inputs +'</td>';
          tr += '<td class="px-2 py-1 align-middle text-center"><button type="button" class="btn btn-outline-danger btn-sm rounded-0" onclick="deleteCurrentRow(this);"><i class="fa fa-times"></i></button></td>';
          tr += '</tr>';
          oldData += tr;
          serial++;

          // Update the list and clear the input fields
          $("#current_medicines_list").html(oldData);
          $("#medicine").val('');
          $("#packing").val('');
          $("#quantity").val('');
          $("#dosage").val('');
        } else {
          showCustomMessage('Please fill all fields.');
        }
      });
    });

    // Function to delete a row from the medication list table
    function deleteCurrentRow(obj) {
      var rowIndex = obj.parentNode.parentNode.rowIndex;
      document.getElementById("medication_list").deleteRow(rowIndex);
    }
  </script>
</body>
</html>
