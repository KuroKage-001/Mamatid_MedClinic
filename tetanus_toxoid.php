<?php
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';

// Handle form submission to save a new tetanus toxoid record
if (isset($_POST['save_tetanus'])) {
    // Retrieve and sanitize form inputs
    $name = trim($_POST['name']);
    $date = trim($_POST['date']);
    $address = trim($_POST['address']);
    $age = trim($_POST['age']);
    $diagnosis = trim($_POST['diagnosis']);
    $remarks = trim($_POST['remarks']);

    // Convert date format from MM/DD/YYYY to YYYY-MM-DD
    $dateArr = explode("/", $date);
    $date = $dateArr[2] . '-' . $dateArr[0] . '-' . $dateArr[1];

    // Format name and address (capitalize each word)
    $name = ucwords(strtolower($name));
    $address = ucwords(strtolower($address));

    // Check if all required fields are provided
    if ($name != '' && $date != '' && $address != '' && $age != '') {
        // Prepare INSERT query
        $query = "INSERT INTO `tetanus_toxoid`(`name`, `date`, `address`, `age`, `diagnosis`, `remarks`)
                  VALUES('$name', '$date', '$address', '$age', '$diagnosis', '$remarks');";
        try {
            // Start transaction and execute query
            $con->beginTransaction();
            $stmt = $con->prepare($query);
            $stmt->execute();
            $con->commit();
            $message = 'Tetanus toxoid record added successfully.';
        } catch (PDOException $ex) {
            // Rollback on error and output exception details (for debugging only)
            $con->rollback();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        }
    }
    // Redirect with a success or error message
    header("Location:congratulation.php?goto_page=tetanus_toxoid.php&message=$message");
    exit;
}

// Retrieve all tetanus toxoid records for the listing
try {
    $query = "SELECT `id`, `name`, `address`, `age`, `diagnosis`, `remarks`,
                     DATE_FORMAT(`date`, '%d %b %Y') as `date`,
                     DATE_FORMAT(`created_at`, '%d %b %Y %h:%i %p') as `created_at`
              FROM `tetanus_toxoid`
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
  <title>Tetanus Toxoid - Mamatid Health Center System</title>
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
              <h1>TETANUS TOXOID RECORD</h1>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">ADD TETANUS TOXOID RECORD</h3>
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
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Age</label>
                  <input type="number" id="age" name="age" required="required"
                         class="form-control form-control-sm rounded-0"/>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
                  <label>Address</label>
                  <input type="text" id="address" name="address" required="required"
                         class="form-control form-control-sm rounded-0"/>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
                  <label>Diagnosis</label>
                  <input type="text" id="diagnosis" name="diagnosis"
                         class="form-control form-control-sm rounded-0"/>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                  <label>Remarks</label>
                  <textarea id="remarks" name="remarks" rows="3"
                            class="form-control form-control-sm rounded-0"></textarea>
                </div>
              </div>
              <div class="clearfix">&nbsp;</div>
              <div class="row">
                <div class="col-lg-11 col-md-10 col-sm-10 xs-hidden">&nbsp;</div>
                <div class="col-lg-1 col-md-2 col-sm-2 col-xs-12">
                  <button type="submit" id="save_tetanus" name="save_tetanus" 
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
            <h3 class="card-title">TETANUS TOXOID RECORDS</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="row table-responsive">
              <table id="all_tetanus" class="table table-striped dataTable table-bordered dtr-inline" role="grid" aria-describedby="all_tetanus_info">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Address</th>
                    <th>Age</th>
                    <th>Diagnosis</th>
                    <th>Remarks</th>
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
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['date']; ?></td>
                    <td><?php echo $row['address']; ?></td>
                    <td><?php echo $row['age']; ?></td>
                    <td><?php echo $row['diagnosis']; ?></td>
                    <td><?php echo $row['remarks']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                      <a href="update_tetanus.php?id=<?php echo $row['id']; ?>" 
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
      showMenuSelected("#mnu_patients", "#mi_tetanus_toxoid");
      
      var message = '<?php echo $message;?>';
      if(message !== '') {
        showCustomMessage(message);
      }
      
      $('#date').datetimepicker({
          format: 'L'
      });
      
      $(function () {
        $("#all_tetanus").DataTable({
          "responsive": true,
          "lengthChange": false,
          "autoWidth": false,
          "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#all_tetanus_wrapper .col-md-6:eq(0)');
      });
    </script>
</body>
</html> 