<?php
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

if ($id == '') {
    header("Location:random_blood_sugar.php");
    exit;
}

// Handle form submission to update random blood sugar record
if (isset($_POST['update_random_blood_sugar'])) {
    // Retrieve and sanitize form inputs
    $name = trim($_POST['name']);
    $date = trim($_POST['date']);
    $age = trim($_POST['age']);
    $address = trim($_POST['address']);
    $result = trim($_POST['result']);

    // Convert date format from MM/DD/YYYY to YYYY-MM-DD
    $dateArr = explode("/", $date);
    $date = $dateArr[2] . '-' . $dateArr[0] . '-' . $dateArr[1];

    // Format name and address (capitalize each word)
    $name = ucwords(strtolower($name));
    $address = ucwords(strtolower($address));

    // Check if all required fields are provided
    if ($name != '' && $date != '' && $address != '' && $age != '' && $result != '') {
        // Prepare UPDATE query
        $query = "UPDATE `random_blood_sugar` SET 
                 `name` = '$name',
                 `date` = '$date',
                 `age` = '$age',
                 `address` = '$address',
                 `result` = '$result'
                 WHERE `id` = $id;";
        try {
            // Start transaction and execute query
            $con->beginTransaction();
            $stmt = $con->prepare($query);
            $stmt->execute();
            $con->commit();
            $message = 'Random blood sugar record updated successfully.';
        } catch (PDOException $ex) {
            // Rollback on error and output exception details (for debugging only)
            $con->rollback();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        }
    }
    // Redirect with a success or error message
    header("Location:congratulation.php?goto_page=random_blood_sugar.php&message=$message");
    exit;
}

// Retrieve the random blood sugar record for editing
try {
    $query = "SELECT `id`, `name`, `address`, `age`, `result`,
                     DATE_FORMAT(`date`, '%m/%d/%Y') as `date`
              FROM `random_blood_sugar`
              WHERE `id` = $id;";
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
  <?php include './config/site_css_links.php'; ?>
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Update Random Blood Sugar - Mamatid Health Center System</title>
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
              <h1>UPDATE RANDOM BLOOD SUGAR RECORD</h1>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">UPDATE RANDOM BLOOD SUGAR RECORD</h3>
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
                         class="form-control form-control-sm rounded-0"
                         value="<?php echo $row['name']; ?>"/>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <div class="form-group">
                    <label>Date</label>
                    <div class="input-group date" id="date" data-target-input="nearest">
                      <input type="text" class="form-control form-control-sm rounded-0 datetimepicker-input" 
                             data-target="#date" name="date"
                             data-toggle="datetimepicker" autocomplete="off"
                             value="<?php echo $row['date']; ?>"/>
                      <div class="input-group-append" data-target="#date" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Age</label>
                  <input type="number" id="age" name="age" required="required"
                         class="form-control form-control-sm rounded-0"
                         value="<?php echo $row['age']; ?>"/>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
                  <label>Address</label>
                  <input type="text" id="address" name="address" required="required"
                         class="form-control form-control-sm rounded-0"
                         value="<?php echo $row['address']; ?>"/>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
                  <label>Result</label>
                  <input type="text" id="result" name="result" required="required"
                         class="form-control form-control-sm rounded-0"
                         value="<?php echo $row['result']; ?>"/>
                </div>
              </div>
              <div class="clearfix">&nbsp;</div>
              <div class="row">
                <div class="col-lg-11 col-md-10 col-sm-10 xs-hidden">&nbsp;</div>
                <div class="col-lg-1 col-md-2 col-sm-2 col-xs-12">
                  <button type="submit" id="update_random_blood_sugar" name="update_random_blood_sugar" 
                          class="btn btn-primary btn-sm btn-flat btn-block">Update</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </section>
    </div>

    <?php include './config/footer.php'; ?>
    
    <?php include './config/site_js_links.php'; ?>
    <script src="plugins/moment/moment.min.js"></script>
    <script src="plugins/daterangepicker/daterangepicker.js"></script>
    <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <script>
      showMenuSelected("#mnu_patients", "#mi_random_blood_sugar");
      
      $('#date').datetimepicker({
          format: 'L'
      });
    </script>
</body>
</html> 