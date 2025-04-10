<?php
// Include the database connection and common functions
include './config/connection.php';
include './common_service/common_functions.php';

// Initialize message variable
$message = '';

// Check if the update form is submitted
if (isset($_POST['submit'])) {

    // Retrieve form data
    $medicineId = $_POST['medicine'];             // Expected numeric value
    $medicineDetailId = $_POST['hidden_id'];        // Expected numeric value
    $packing = $_POST['packing'];                   // String value

    // Build the update query
    // NOTE: Consider using bound parameters for better security.
    $query = "UPDATE `medicine_details` 
              SET `medicine_id` = $medicineId, 
                  `packing` = '$packing'
              WHERE `id` = $medicineDetailId;";

    try {
        // Begin transaction
        $con->beginTransaction();

        // Prepare and execute the update query
        $stmtUpdate = $con->prepare($query);
        $stmtUpdate->execute();

        // Commit the transaction
        $con->commit();

        // Set success message
        $message = 'Medicine details updated successfully.';
    } catch (PDOException $ex) {
        // Rollback if there is an error
        $con->rollback();
        echo $ex->getMessage();
        echo $ex->getTraceAsString();
        exit;
    }
    // Redirect to the congratulation page with a success message
    header("location:congratulation.php?goto_page=medicine_details.php&message=$message");
    exit;
}

// Get parameters from GET (used to pre-populate the form)
$medicineId = $_GET['medicine_id'];
$medicineDetailId = $_GET['medicine_detail_id'];
$packing = $_GET['packing'];

// Get list of medicines as HTML options (function defined in common_functions.php)
$medicines = getMedicines($con, $medicineId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <?php include './config/data_tables_css.php'; ?>
  <!-- Logo for the tab bar -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Update Medicine Details - Mamatid Health Center System</title>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <!-- Site Wrapper -->
  <div class="wrapper">
    <!-- Header & Sidebar -->
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
              <h1>MEDICINE DETAILS</h1>
            </div>
          </div>
        </div>
      </section>

      <!-- Main Content -->
      <section class="content">
        <!-- Update Medicine Details Card -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">UPDATE MEDICINE DETAILS</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- Update Form -->
            <form method="post">
              <!-- Hidden field to store the medicine detail id -->
              <input type="hidden" name="hidden_id" value="<?php echo $medicineDetailId; ?>" />

              <div class="row">
                <!-- Medicine selection -->
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <label>Select Medicine</label>
                  <select id="medicine" name="medicine" class="form-control form-control-sm rounded-0" required>
                    <?php echo $medicines; ?>
                  </select>
                </div>

                <!-- Packing input -->
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <label>Packing</label>
                  <input id="packing" name="packing" class="form-control form-control-sm rounded-0" required value="<?php echo $packing; ?>" />
                </div>

                <!-- Submit button -->
                <div class="col-lg-1 col-md-2 col-sm-4 col-xs-12">
                  <label>&nbsp;</label>
                  <button type="submit" id="submit" name="submit" class="btn btn-primary btn-sm btn-flat btn-block">Update</button>
                </div>
              </div>
            </form>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Footer -->
    <?php 
      include './config/footer.php';

      // Capture message from GET if available (for custom message display)
      $message = isset($_GET['message']) ? $_GET['message'] : '';
    ?>  
  </div>
  <!-- ./wrapper -->

  <?php include './config/site_js_links.php'; ?>
  <?php include './config/data_tables_js.php'; ?>

  <script>
    // Highlight the proper menu in the sidebar
    showMenuSelected("#mnu_medicines", "#mi_medicine_details");

    // Show custom message if one is passed via GET
    var message = '<?php echo $message; ?>';
    if (message !== '') {
      showCustomMessage(message);
    }
  </script>
</body>
</html>
