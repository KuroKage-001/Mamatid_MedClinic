<?php
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';

// Handle form submission to save a new family member
if (isset($_POST['save_family_member'])) {
    // Retrieve and sanitize form inputs
    $serialNumber = trim($_POST['serial_number']);
    $name = trim($_POST['name']);
    $date = trim($_POST['date']);

    // Convert date format from MM/DD/YYYY to YYYY-MM-DD
    $dateArr = explode("/", $date);
    $date = $dateArr[2] . '-' . $dateArr[0] . '-' . $dateArr[1];

    // Format name (capitalize each word)
    $name = ucwords(strtolower($name));

    // Check if all required fields are provided
    if ($serialNumber != '' && $name != '' && $date != '') {
        // Prepare INSERT query
        $query = "INSERT INTO `family_members`(`serial_number`, `name`, `date`)
                  VALUES('$serialNumber', '$name', '$date');";
        try {
            // Start transaction and execute query
            $con->beginTransaction();
            $stmt = $con->prepare($query);
            $stmt->execute();
            $con->commit();
            $message = 'Family member added successfully.';
        } catch (PDOException $ex) {
            // Rollback on error and output exception details (for debugging only)
            $con->rollback();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        }
    }
    // Redirect with a success or error message
    header("Location:congratulation.php?goto_page=family_members.php&message=$message");
    exit;
}

// Retrieve all family members for the listing
try {
    $query = "SELECT `id`, `serial_number`, `name`, 
                     DATE_FORMAT(`date`, '%d %b %Y') as `date`,
                     DATE_FORMAT(`created_at`, '%d %b %Y %h:%i %p') as `created_at`
              FROM `family_members`
              ORDER BY `date` DESC;";
    $stmt = $con->prepare($query);
    $stmt->execute();
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
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Family Members - Mamatid Health Center System</title>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <div class="wrapper">
    <?php 
      include './config/header.php';
      include './config/sidebar.php'; 
    ?>
    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>FAMILY MEMBERS</h1>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">ADD FAMILY MEMBER</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <form method="post">
              <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Serial Number</label>
                  <input type="text" id="serial_number" name="serial_number" required="required"
                         class="form-control form-control-sm rounded-0"/>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Name</label>
                  <input type="text" id="name" name="name" required="required"
                         class="form-control form-control-sm rounded-0"/>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <div class="form-group">
                    <label>Date</label>
                    <div class="input-group date" id="date" data-target-input="nearest">
                      <input type="text" class="form-control form-control-sm rounded-0 datetimepicker-input" 
                             data-target="#date" name="date"
                             data-toggle="datetimepicker" autocomplete="off" />
                      <div class="input-group-append" data-target="#date" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="clearfix">&nbsp;</div>
              <div class="row">
                <div class="col-lg-11 col-md-10 col-sm-10 xs-hidden">&nbsp;</div>
                <div class="col-lg-1 col-md-2 col-sm-2 col-xs-12">
                  <button type="submit" id="save_family_member" name="save_family_member" 
                          class="btn btn-primary btn-sm btn-flat btn-block">Save</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </section>

      <br/><br/><br/>

      <section class="content">
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">FAMILY MEMBERS LIST</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row table-responsive">
              <table id="all_family_members" class="table table-striped dataTable table-bordered dtr-inline" role="grid" aria-describedby="all_family_members_info">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Serial Number</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Created At</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $count = 0;
                  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      $count++;
                  ?>
                  <tr>
                    <td><?php echo $count; ?></td>
                    <td><?php echo $row['serial_number']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['date']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                      <a href="update_family_member.php?id=<?php echo $row['id']; ?>" 
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
    </div>

    <?php
      include './config/footer.php';
      $message = isset($_GET['message']) ? $_GET['message'] : '';
    ?>
    
    <?php include './config/site_js_links.php'; ?>
    <?php include './config/data_tables_js.php'; ?>
    <script src="plugins/moment/moment.min.js"></script>
    <script src="plugins/daterangepicker/daterangepicker.js"></script>
    <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <script>
      showMenuSelected("#mnu_patients", "#mi_family_members");
      
      var message = '<?php echo $message;?>';
      if(message !== '') {
        showCustomMessage(message);
      }
      
      $('#date').datetimepicker({
          format: 'L'
      });
      
      $(function () {
        $("#all_family_members").DataTable({
          "responsive": true,
          "lengthChange": false,
          "autoWidth": false,
          "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#all_family_members_wrapper .col-md-6:eq(0)');
      });
    </script>
</body>
</html> 