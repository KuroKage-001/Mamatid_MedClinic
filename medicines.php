<?php
include './config/connection.php';

// Initialize the message variable for user feedback
$message = '';
if(isset($_POST['save_medicine'])) {
  $message = '';
  // Retrieve and sanitize the medicine name input
  $medicineName = trim($_POST['medicine_name']);
  $medicineName = ucwords(strtolower($medicineName)); // Capitalizes each word
  if($medicineName != '') {
    $query = "INSERT INTO `medicines`(`medicine_name`)
   VALUES('$medicineName');";
   
   try {
    // Begin a transaction to ensure data consistency
    $con->beginTransaction();

    $stmtMedicine = $con->prepare($query);
    $stmtMedicine->execute();

    // Commit the transaction if execution is successful
    $con->commit();

    $message = 'Medicine added successfully.';
  } catch(PDOException $ex) {
    // Roll back the transaction on error
    $con->rollback();

    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
  }
} else {
  $message = 'Empty form can not be submitted.';
}
// Redirect to the congratulation page with a feedback message via URL parameter
header("Location:congratulation.php?goto_page=medicines.php&message=$message");
exit;
}

try {
  // Retrieve all medicines ordered by name (ascending)
  $query = "select `id`, `medicine_name` from `medicines` 
  order by `medicine_name` asc;";
  $stmt = $con->prepare($query);
  $stmt->execute();
} catch(PDOException $ex) {
  echo $ex->getMessage();
  echo $e->getTraceAsString();
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
 <title>Medicines - Mamatid Health Center System</title>
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
              <h1>MEDICINES</h1>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>
      <!-- Main content -->
      <section class="content">
        <!-- Card for adding new medicine -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">ADD MEDICINES</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- Form to add a new medicine -->
            <form method="post">
             <div class="row">
              <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                <label>Medicine Name</label>
                <input type="text" id="medicine_name" name="medicine_name" required="required"
                class="form-control form-control-sm rounded-0" />
              </div>
              <div class="col-lg-1 col-md-2 col-sm-2 col-xs-2">
                <label>&nbsp;</label>
                <button type="submit" id="save_medicine" 
                name="save_medicine" class="btn btn-primary btn-sm btn-flat btn-block">Save</button>
              </div>
            </div>
          </form>
        </div>
      </div>
      <!-- /.card -->
    </section>
    <section class="content">
      <!-- Card for displaying all medicines -->
      <div class="card card-outline card-primary rounded-0 shadow">
        <div class="card-header">
          <h3 class="card-title">ALL MEDICINES</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
              <i class="fas fa-minus"></i>
            </button>
          </div>
        </div>
        <div class="card-body">
         <div class="row table-responsive">
          <table id="all_medicines" 
          class="table table-striped dataTable table-bordered dtr-inline" 
          role="grid" aria-describedby="all_medicines_info">
          <colgroup>
            <col width="10%">
            <col width="80%">
            <col width="10%">
          </colgroup>
          <thead>
            <tr>
             <th class="text-center">S.No</th>
             <th>Medicine Name</th>
             <th class="text-center">Action</th>
           </tr>
         </thead>
         <tbody>
          <?php 
          // Generate table rows for each medicine record retrieved
          $serial = 0;
          while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
           $serial++;
           ?>
           <tr>
             <td class="text-center"><?php echo $serial;?></td>
             <td><?php echo $row['medicine_name'];?></td>
             <td class="text-center">
              <!-- Link to update medicine details -->
              <a href="update_medicine.php?id=<?php echo $row['id'];?>" 
               class="btn btn-primary btn-sm btn-flat">
               <i class="fa fa-edit"></i>
             </a>
           </td>
         </tr>
       <?php } ?>
     </tbody>
   </table>
 </div>
</div>
<!-- /.card-footer-->
</div>
<!-- End of All Medicines card -->
</section>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php 
include './config/footer.php';

// Retrieve the message parameter from URL if available to display feedback
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
  // Highlight the current menu items for medicines
  showMenuSelected("#mnu_medicines", "#mi_medicines");

  var message = '<?php echo $message;?>';

  if(message !== '') {
    // Display any custom message if available
    showCustomMessage(message);
  }

  $(function () {
    // Initialize the DataTable with export and responsive features
    $("#all_medicines").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#all_medicines_wrapper .col-md-6:eq(0)');
  });

  $(document).ready(function() {
    // When the medicine name input loses focus, check for duplicate names via AJAX
    $("#medicine_name").blur(function() {
      var medicineName = $(this).val().trim();
      $(this).val(medicineName);

      if(medicineName !== '') {
        $.ajax({
          url: "ajax/check_medicine_name.php",
          type: 'GET', 
          data: {
            'medicine_name': medicineName
          },
          cache: false,
          async: false,
          success: function (count, status, xhr) {
            // If the medicine name already exists, disable the save button and show a message
            if(count > 0) {
              showCustomMessage("This medicine name has already been stored. Please choose another name");
              $("#save_medicine").attr("disabled", "disabled");
            } else {
              $("#save_medicine").removeAttr("disabled");
            }
          },
          error: function (jqXhr, textStatus, errorMessage) {
            // Show an error message if the AJAX request fails
            showCustomMessage(errorMessage);
          }
        });
      }
    });    
  });
</script>
</body>
</html>
