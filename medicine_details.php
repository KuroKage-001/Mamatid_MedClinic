<?php
include './config/connection.php';
include './common_service/common_functions.php';

// Initialize message variable to provide feedback to the user
$message = '';

if(isset($_POST['submit'])) {
  // Retrieve form inputs
  $medicineId = $_POST['medicine'];
  $packing = $_POST['packing'];
  
  // Construct insert query for new medicine detail
  // NOTE: Consider using parameterized queries here to avoid SQL injection risks
  $query = "insert into `medicine_details` (`medicine_id`, `packing`) values($medicineId, '$packing');";
  try {
    // Begin transaction to ensure data consistency
    $con->beginTransaction();
    
    // Prepare and execute the query
    $stmtDetails = $con->prepare($query);
    $stmtDetails->execute();

    // Commit the transaction on successful execution
    $con->commit();

    $message = 'Packing saved successfully.';
  } catch(PDOException $ex) {
    // Roll back transaction if any error occurs
    $con->rollback();

    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
 }
 // Redirect to congratulation page with a feedback message in the query string
 header("location:congratulation.php?goto_page=medicine_details.php&message=$message");
 exit;
}

// Fetch list of medicines for the dropdown selection in the form
$medicines = getMedicines($con);

// Query to retrieve medicine details along with medicine name
// Note: Consider using an explicit JOIN syntax for clarity
$query = "select `m`.`medicine_name`,
`md`.`id`, `md`.`packing`,  `md`.`medicine_id` 
from `medicines` as `m`, 
`medicine_details` as `md` 
where `m`.`id` = `md`.`medicine_id` 
order by `m`.`id` asc, `md`.`id` asc;";

try {
    // Prepare and execute the query
    $stmtDetails = $con->prepare($query);
    $stmtDetails->execute();
} catch(PDOException $ex) {
    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <?php include './config/site_css_links.php';?>
 <?php include './config/data_tables_css.php';?>
   <!-- Logo for the tab bar -->
   <link rel="icon" type="image/png" href="dist/img/logo01.png">
 <title>Medicine Details - Mamatid Health Center System</title>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar -->
    <?php include './config/header.php';
    include './config/sidebar.php';?>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>MEDICINE DETAILS</h1>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>

      <!-- Main content -->
      <section class="content">
        <!-- Default box for adding new medicine detail -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">ADD MEDICINE DETAILS</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- Form to add a new medicine detail -->
            <form method="post">
              <div class="row">
                <!-- Medicine selection dropdown -->
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <label>Select Medicine</label>
                  <select id="medicine" name="medicine" class="form-control form-control-sm rounded-0" required="required">
                    <?php echo $medicines;?>
                  </select>
                </div>
                <!-- Packing input field -->
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <label>Packing</label>
                  <input id="packing" name="packing" class="form-control form-control-sm rounded-0"  required="required" />
                </div>
                <!-- Submit button -->
                <div class="col-lg-1 col-md-2 col-sm-4 col-xs-12">
                  <label>&nbsp;</label>
                  <button type="submit" id="submit" name="submit" class="btn btn-primary btn-sm btn-flat btn-block">Save</button>
                </div>
              </div>
            </form>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </section>

      <!-- Spacing -->
      <div class="clearfix">&nbsp;</div>
      <div class="clearfix">&nbsp;</div>
      
      <!-- Section for displaying the list of medicine details -->
      <section class="content">
        <!-- Default box for listing medicine details -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">MEDICINE DETAILS</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row table-responsive">
              <table id="medicine_details" class="table table-striped dataTable table-bordered dtr-inline" role="grid" aria-describedby="medicine_details_info">
                <colgroup>
                  <col width="10%">
                  <col width="50%">
                  <col width="30%">
                  <col width="10%">
                </colgroup>
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Medicine Name</th>
                    <th>Packing</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  // Serial counter for table rows
                  $serial = 0;
                  while($row = $stmtDetails->fetch(PDO::FETCH_ASSOC)) {
                    $serial++;
                  ?>
                  <tr>
                    <td class="text-center"><?php echo $serial; ?></td>
                    <td><?php echo $row['medicine_name']; ?></td>
                    <td><?php echo $row['packing']; ?></td>
                    <td class="text-center">
                      <!-- Link to update medicine detail, passing required parameters -->
                      <a href="update_medicine_details.php?medicine_id=<?php echo $row['medicine_id']; ?>&medicine_detail_id=<?php echo $row['id']; ?>&packing=<?php echo $row['packing']; ?>" 
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
        </div>
      </section>
      <!-- /.content-wrapper -->
    </div>

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
  <?php include './config/data_tables_js.php'; ?>
  <script>
    // Highlight the active menu items for medicines section
    showMenuSelected("#mnu_medicines", "#mi_medicine_details");

    var message = '<?php echo $message; ?>';

    // If there is a message, display it using a custom message handler
    if(message !== '') {
      showCustomMessage(message);
    }
    $(function () {
      // Initialize DataTable with responsive and export features
      $("#medicine_details").DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
      }).buttons().container().appendTo('#medicine_details_wrapper .col-md-6:eq(0)');
    });
  </script>
</body>
</html>
