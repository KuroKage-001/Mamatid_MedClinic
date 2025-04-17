<?php
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';

// Handle form submission to save a new BP monitoring record
if (isset($_POST['save_bp'])) {
    // Retrieve and sanitize form inputs
    $name = trim($_POST['name']);
    $date = trim($_POST['date']);
    $address = trim($_POST['address']);
    $sex = trim($_POST['sex']);
    $bp = trim($_POST['bp']);
    $alcohol = isset($_POST['alcohol']) ? 1 : 0;
    $smoke = isset($_POST['smoke']) ? 1 : 0;
    $obese = isset($_POST['obese']) ? 1 : 0;
    $cp_number = trim($_POST['cp_number']);

    // Convert date format from MM/DD/YYYY to YYYY-MM-DD
    $dateArr = explode("/", $date);
    $date = $dateArr[2] . '-' . $dateArr[0] . '-' . $dateArr[1];

    // Format name and address (capitalize each word)
    $name = ucwords(strtolower($name));
    $address = ucwords(strtolower($address));

    // Check if all required fields are provided
    if ($name != '' && $date != '' && $address != '' && $sex != '' && $bp != '') {
        // Prepare INSERT query
        $query = "INSERT INTO `bp_monitoring`(`name`, `date`, `address`, `sex`, `bp`, 
                  `alcohol`, `smoke`, `obese`, `cp_number`)
                  VALUES('$name', '$date', '$address', '$sex', '$bp', 
                         $alcohol, $smoke, $obese, '$cp_number');";
        try {
            // Start transaction and execute query
            $con->beginTransaction();
            $stmt = $con->prepare($query);
            $stmt->execute();
            $con->commit();
            $message = 'BP monitoring record added successfully.';
        } catch (PDOException $ex) {
            // Rollback on error and output exception details (for debugging only)
            $con->rollback();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        }
    }
    // Redirect with a success or error message
    header("Location:congratulation.php?goto_page=bp_monitoring.php&message=$message");
    exit;
}

// Retrieve all BP monitoring records for the listing
try {
    $query = "SELECT `id`, `name`, `address`, `sex`, `bp`, `alcohol`, `smoke`, `obese`, `cp_number`,
                     DATE_FORMAT(`date`, '%d %b %Y') as `date`,
                     DATE_FORMAT(`created_at`, '%d %b %Y %h:%i %p') as `created_at`
              FROM `bp_monitoring`
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
  <title>BP Monitoring - Mamatid Health Center System</title>
  <style>
    :root {
      --transition-speed: 0.3s;
      --primary-color: #3699FF;
      --secondary-color: #6993FF;
      --success-color: #1BC5BD;
      --info-color: #8950FC;
      --warning-color: #FFA800;
      --danger-color: #F64E60;
      --light-color: #F3F6F9;
      --dark-color: #1a1a2d;
    }

    /* Card Styling */
    .card {
      border: none;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      border-radius: 15px;
      margin-bottom: 30px;
    }

    .card-outline {
      border-top: 3px solid var(--primary-color);
    }

    .card-header {
      background: white;
      padding: 1.5rem;
      border-bottom: 1px solid #eee;
    }

    .card-header h3 {
      margin: 0;
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--dark-color);
      text-transform: capitalize;
    }

    .card-body {
      padding: 1.5rem;
    }

    /* Form Controls */
    .form-control {
      height: calc(2.5rem + 2px);
      border-radius: 8px;
      border: 2px solid #e4e6ef;
      padding: 0.625rem 1rem;
      font-size: 1rem;
      transition: all var(--transition-speed);
    }

    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
    }

    .form-label {
      font-weight: 500;
      color: var(--dark-color);
      margin-bottom: 0.5rem;
    }

    /* Select Styling */
    select.form-control {
      appearance: none;
      background: #fff url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'%3E%3Cpath fill='%23343a40' d='M2 0L0 2h4zm0 5L0 3h4z'/%3E%3C/svg%3E") no-repeat right 0.75rem center;
      background-size: 8px 10px;
      padding-right: 2rem;
    }

    /* Date Picker Styling */
    .input-group-text {
      border-radius: 8px;
      border: 2px solid #e4e6ef;
      background-color: #f5f8fa;
      color: var(--dark-color);
    }

    .input-group > .form-control {
      border-top-right-radius: 8px !important;
      border-bottom-right-radius: 8px !important;
    }

    .datetimepicker-input {
      background-color: white !important;
    }

    /* Checkbox Styling */
    .form-check {
      padding-left: 2rem;
      margin-bottom: 1rem;
    }

    .form-check-input {
      width: 1.25rem;
      height: 1.25rem;
      margin-left: -2rem;
      margin-top: 0.15rem;
      border: 2px solid #e4e6ef;
      border-radius: 4px;
      transition: all var(--transition-speed);
    }

    .form-check-input:checked {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .form-check-label {
      color: var(--dark-color);
      font-weight: 500;
    }

    /* Button Styling */
    .btn {
      padding: 0.65rem 1rem;
      font-weight: 500;
      border-radius: 8px;
      transition: all var(--transition-speed);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      border: none;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(54, 153, 255, 0.4);
    }

    .btn-tool {
      color: var(--dark-color);
    }

    /* Table Styling */
    .table {
      margin-bottom: 0;
    }

    .table thead tr {
      background: var(--light-color);
    }

    .table thead th {
      border-bottom: none;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.85rem;
      padding: 1rem;
      vertical-align: middle;
      color: var(--dark-color);
    }

    .table tbody td {
      padding: 1rem;
      vertical-align: middle;
      border-color: #eee;
    }

    .table-striped tbody tr:nth-of-type(odd) {
      background-color: rgba(243, 246, 249, 0.5);
    }

    .table-hover tbody tr:hover {
      background-color: rgba(54, 153, 255, 0.05);
    }

    /* Content Header Styling */
    .content-header {
      padding: 20px 0;
    }

    .content-header h1 {
      font-size: 2rem;
      font-weight: 600;
      color: #1a1a1a;
      margin: 0;
      text-transform: capitalize;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .card-header {
        padding: 1rem;
      }

      .card-body {
        padding: 1rem;
      }

      .table thead th,
      .table tbody td {
        padding: 0.75rem;
      }

      .btn {
        width: 100%;
        margin-bottom: 0.5rem;
      }

      .form-group {
        margin-bottom: 1rem;
      }
    }
  </style>
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
          <div class="row align-items-center mb-4">
            <div class="col-12 col-md-6" style="padding-left: 20px;">
              <h1>BP Monitoring Record</h1>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title">Add BP Monitoring Record</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <form method="post">
              <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" id="name" name="name" required="required"
                           class="form-control" placeholder="Enter patient name"/>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <div class="form-group">
                    <label class="form-label">Date</label>
                    <div class="input-group date" id="date" data-target-input="nearest">
                      <input type="text" class="form-control datetimepicker-input" 
                             data-target="#date" name="date" placeholder="Select date"
                             data-toggle="datetimepicker" autocomplete="off" required />
                      <div class="input-group-append" data-target="#date" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <div class="form-group">
                    <label class="form-label">Sex</label>
                    <select id="sex" name="sex" required="required" class="form-control">
                      <option value="">Select Sex</option>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
                  <div class="form-group">
                    <label class="form-label">Address</label>
                    <input type="text" id="address" name="address" required="required"
                           class="form-control" placeholder="Enter complete address"/>
                  </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
                  <div class="form-group">
                    <label class="form-label">BP</label>
                    <input type="text" id="bp" name="bp" required="required"
                           class="form-control" placeholder="e.g. 120/80"/>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="alcohol" name="alcohol">
                    <label class="form-check-label" for="alcohol">Alcohol</label>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="smoke" name="smoke">
                    <label class="form-check-label" for="smoke">Smoke</label>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="obese" name="obese">
                    <label class="form-check-label" for="obese">Obese</label>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div class="form-group">
                    <label class="form-label">CP Number</label>
                    <input type="text" id="cp_number" name="cp_number"
                           class="form-control" placeholder="Enter contact number"/>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12 text-right">
                  <button type="submit" id="save_bp" name="save_bp" 
                          class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Save Record
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title">BP Monitoring Records</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="all_bp" class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Address</th>
                    <th>Sex</th>
                    <th>BP</th>
                    <th>Alcohol</th>
                    <th>Smoke</th>
                    <th>Obese</th>
                    <th>CP Number</th>
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
                    <td><?php echo $row['sex']; ?></td>
                    <td><?php echo $row['bp']; ?></td>
                    <td><span class="badge badge-<?php echo $row['alcohol'] ? 'warning' : 'light'; ?>"><?php echo $row['alcohol'] ? 'Yes' : 'No'; ?></span></td>
                    <td><span class="badge badge-<?php echo $row['smoke'] ? 'danger' : 'light'; ?>"><?php echo $row['smoke'] ? 'Yes' : 'No'; ?></span></td>
                    <td><span class="badge badge-<?php echo $row['obese'] ? 'info' : 'light'; ?>"><?php echo $row['obese'] ? 'Yes' : 'No'; ?></span></td>
                    <td><?php echo $row['cp_number']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                      <a href="update_bp.php?id=<?php echo $row['id']; ?>" 
                         class="btn btn-primary">
                        <i class="fa fa-edit"></i>
                      </a>
                    </td>
                  </tr>
                  <?php } ?>
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
      $(document).ready(function() {
        // Initialize DataTable
        $("#all_bp").DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
          language: {
            search: "",
            searchPlaceholder: "Search records..."
          },
          dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        }).buttons().container().appendTo('#all_bp_wrapper .col-md-6:eq(0)');

        // Initialize Date Picker
        $('#date').datetimepicker({
          format: 'L',
          icons: {
            time: 'fas fa-clock',
            date: 'fas fa-calendar',
            up: 'fas fa-arrow-up',
            down: 'fas fa-arrow-down',
            previous: 'fas fa-chevron-left',
            next: 'fas fa-chevron-right',
            today: 'fas fa-calendar-check',
            clear: 'fas fa-trash',
            close: 'fas fa-times'
          }
        });

        // Initialize Toast
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true
        });

        // Show message if exists
        var message = '<?php echo $message;?>';
        if(message !== '') {
          Toast.fire({
            icon: 'success',
            title: message
          });
        }
      });

      // Highlight current menu
      showMenuSelected("#mnu_patients", "#mi_bp_monitoring");
    </script>
</body>
</html> 