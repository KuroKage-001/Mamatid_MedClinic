<?php
include './config/db_connection.php';

$message = '';
if (isset($_POST['save_medicine'])) {
    // Retrieve and format the medicine name from the submitted form
    $medicineName = trim($_POST['medicine_name']);
    $medicineName = ucwords(strtolower($medicineName));

    // Retrieve the hidden ID of the medicine record to update
    $id = $_POST['hidden_id'];
    if ($medicineName !== '') {

        // NOTE: Using direct variable interpolation in the query may expose you to SQL injection risks.
        // Consider parameterizing the query in the future.
        $query = "UPDATE `medicines`
        set `medicine_name` ='$medicineName'
        where `id`= $id";
        try {
            $con->beginTransaction();

            // Prepare and execute the update query
            $stmtMedicine = $con->prepare($query);
            $stmtMedicine->execute();

            $con->commit();

            $message = "Record updated sucessfully.";

        } catch (PDOException $ex) {
            $con->rollback();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        }
    }
    // Redirect to congratulation page with feedback message
    header("Location:system/utilities/congratulation.php?goto_page=medicines.php&message=$message");
    exit;
}

try {
    // Retrieve the medicine ID from GET parameters
    $id = $_GET['id'];
    $query = "SELECT `id`, `medicine_name` from `medicines`
              where `id` = $id";
    $stmt = $con->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {
    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <?php include './config/site_css_links.php';?>
   <!-- Logo for the tab bar -->
   <link rel="icon" type="image/png" href="dist/img/logo01.png">
 <title>Update Medicine - Clinic's Patient Management System in PHP</title>

 <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
 <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
 <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
<!-- Site wrapper -->
<div class="wrapper">
  <!-- Navbar -->
  <?php include './config/admin_header.php';
  include './config/admin_sidebar.php';?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>MEDICINES</h1>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">

      <!-- Default box card for updating medicine -->
      <div class="card card-outline card-primary rounded-0 shadow">
        <div class="card-header">
          <h3 class="card-title">UPDATE MEDICINE</h3>

          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
              <i class="fas fa-minus"></i>
            </button>
          </div>
        </div>
        <div class="card-body">
          <!-- Form to update medicine name -->
          <form method="post">
            <div class="row">
              <!-- Hidden field to carry the record ID -->
              <input type="hidden" name="hidden_id" 
              id="hidden_id" value="<?php echo $id;?>" />

              <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                <!-- Input field for medicine name pre-filled with current value -->
                <input type="text" id="medicine_name" name="medicine_name" required="required"
                class="form-control form-control-sm rounded-0" value="<?php echo $row['medicine_name'];?>" />
              </div>
              <div class="col-lg-1 col-md-2 col-sm-2 col-xs-2">
                <!-- Submit button to update medicine -->
                <button type="submit" id="save_medicine" 
                name="save_medicine" class="btn btn-primary btn-sm btn-flat btn-block">Update</button>
              </div>
            </div>
          </form>
        </div>
    
        <!-- /.card-footer-->
      </div>
    </section>	
  </div>
  <!-- /.content-wrapper -->
<?php 
 include './config/footer.php';

    $message = '';
    if (isset($_GET['message'])) {
        $message = $_GET['message'];
    }
?>  
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<?php include './config/site_css_js_links.php'; ?>

</body>
</html>
