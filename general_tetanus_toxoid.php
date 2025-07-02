<?php
include './config/db_connection.php';
include './common_service/common_functions.php';
require_once './common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

$message = '';

// Handle form submission to save a new tetanus toxoid record
if (isset($_POST['save_tetanus'])) {
    // Retrieve and sanitize form inputs
    $name = trim($_POST['name'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($date)) $errors[] = "Date is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($age)) $errors[] = "Age is required";

    if (empty($errors)) {
        try {
            // Convert date format from MM/DD/YYYY to YYYY-MM-DD
            $dateArr = explode("/", $date);
            if (count($dateArr) !== 3) {
                throw new Exception("Invalid date format");
            }
            $formatted_date = $dateArr[2] . '-' . $dateArr[0] . '-' . $dateArr[1];

            // Format name and address (capitalize each word)
            $name = ucwords(strtolower($name));
            $address = ucwords(strtolower($address));

            // Prepare INSERT query with parameterized statement
            $query = "INSERT INTO general_tetanus_toxoid (name, date, address, age, diagnosis, remarks) 
                     VALUES (:name, :date, :address, :age, :diagnosis, :remarks)";

            // Start transaction
            $con->beginTransaction();
            
            $stmt = $con->prepare($query);
            $result = $stmt->execute([
                ':name' => $name,
                ':date' => $formatted_date,
                ':address' => $address,
                ':age' => $age,
                ':diagnosis' => $diagnosis,
                ':remarks' => $remarks
            ]);

            if ($result) {
                $con->commit();
                header("Location: general_tetanus_toxoid.php?message=" . urlencode("Record saved successfully"));
                exit;
            } else {
                throw new Exception("Failed to save record");
            }
        } catch (Exception $e) {
            $con->rollback();
            $message = $e->getMessage();
        }
    } else {
        $message = implode(", ", $errors);
    }
}

// Retrieve all tetanus toxoid records for the listing
try {
    $query = "SELECT id, name, address, age, diagnosis, remarks,
                     DATE_FORMAT(date, '%d %b %Y') as date,
                     DATE_FORMAT(created_at, '%d %b %Y %h:%i %p') as created_at
              FROM general_tetanus_toxoid
              ORDER BY date DESC";
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

    textarea.form-control {
      height: auto;
      min-height: 100px;
      resize: vertical;
    }

    .form-label {
      font-weight: 500;
      color: var(--dark-color);
      margin-bottom: 0.5rem;
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

    /* Export Buttons and Column Visibility Styling */
    .chart-actions {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .export-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #fff !important;
      font-weight: 500;
      transition: all 0.3s ease;
      border: none !important;
      padding: 8px 15px;
      font-size: 0.875rem;
    }

    /* Gradient colors for each button */
    #btnCopy {
      background: linear-gradient(135deg, #3699FF 0%, #2684FF 100%);
    }

    #btnCSV {
      background: linear-gradient(135deg, #1BC5BD 0%, #17B8B0 100%);
    }

    #btnExcel {
      background: linear-gradient(135deg, #20C997 0%, #1CB984 100%);
    }

    #btnPDF {
      background: linear-gradient(135deg, #F64E60 0%, #EE2D41 100%);
    }

    #btnPrint {
      background: linear-gradient(135deg, #8950FC 0%, #7337EE 100%);
    }

    .export-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      filter: brightness(110%);
    }

    .export-btn i {
      font-size: 0.875rem;
    }

    /* Column visibility button and dropdown styles */
    .btn-group .btn-gradient {
      background: linear-gradient(135deg, #FFA800 0%, #F09000 100%);
      color: #fff !important;
      border: none !important;
      padding: 8px 15px;
      font-size: 0.875rem;
    }

    .btn-group .btn-gradient:hover {
      filter: brightness(110%);
      transform: translateY(-1px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .dropdown-menu {
      padding: 8px;
      border-radius: 8px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.15);
      border: none;
      background: #fff;
      min-width: 160px;
    }

    .dropdown-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 4px;
      transition: all 0.2s ease;
      color: #3F4254;
      font-size: 0.875rem;
    }

    .dropdown-item:hover {
      background-color: #F3F6F9;
    }

    .dropdown-item input[type="checkbox"] {
      margin: 0;
      width: 16px;
      height: 16px;
    }

    .dropdown-item label {
      margin: 0;
      cursor: pointer;
      color: #3F4254;
      font-weight: 500;
    }

    /* Ensure dropdown text is visible */
    .dropdown-toggle::after {
      margin-left: 8px;
      vertical-align: middle;
    }

    /* Add some spacing between button groups */
    .chart-actions > * + * {
      margin-left: 4px;
    }

    /* Make sure the buttons maintain their shape */
    .btn {
      white-space: nowrap;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
  </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <div class="wrapper">
    <?php include './config/admin_header.php'; include './config/admin_sidebar.php'; ?>
    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row align-items-center mb-4">
            <div class="col-12 col-md-6" style="padding-left: 20px;">
              <h1>Tetanus Toxoid Record</h1>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title">Add Tetanus Toxoid Record</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <form method="post" id="saveForm">
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
                    <label class="form-label">Age</label>
                    <input type="number" id="age" name="age" required="required"
                           class="form-control" placeholder="Enter age"/>
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
                    <label class="form-label">Diagnosis</label>
                    <input type="text" id="diagnosis" name="diagnosis"
                           class="form-control" placeholder="Enter diagnosis"/>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div class="form-group">
                    <label class="form-label">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3"
                             class="form-control" placeholder="Enter additional remarks"></textarea>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12 text-right">
                  <button type="submit" id="save_tetanus" name="save_tetanus" 
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
            <h3 class="card-title">Tetanus Toxoid Records</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <div class="mb-3">
                <div class="row align-items-center">
                  <div class="col-md-6">
                    <div class="chart-actions">
                      <button class="btn btn-gradient btn-sm export-btn" id="btnCopy">
                        <i class="fas fa-copy"></i> Copy
                      </button>
                      <button class="btn btn-gradient btn-sm export-btn" id="btnCSV">
                        <i class="fas fa-file-csv"></i> CSV
                      </button>
                      <button class="btn btn-gradient btn-sm export-btn" id="btnExcel">
                        <i class="fas fa-file-excel"></i> Excel
                      </button>
                      <button class="btn btn-gradient btn-sm export-btn" id="btnPDF">
                        <i class="fas fa-file-pdf"></i> PDF
                      </button>
                      <button class="btn btn-gradient btn-sm export-btn" id="btnPrint">
                        <i class="fas fa-print"></i> Print
                      </button>
                      <div class="btn-group">
                        <button type="button" class="btn btn-gradient btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                          <i class="fas fa-columns"></i> Columns
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" id="columnVisibility">
                          <!-- Column visibility options will be added dynamically -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <table id="all_tetanus" class="table table-striped table-hover">
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
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td><?php echo htmlspecialchars($row['age']); ?></td>
                    <td><?php echo htmlspecialchars($row['diagnosis']); ?></td>
                    <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td>
                      <a href="update_tetanus.php?id=<?php echo $row['id']; ?>" 
                         class="btn btn-primary btn-sm">
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
    <?php include './config/admin_footer.php'; ?>
  </div>

  <?php include './config/site_js_links.php'; ?>
  <?php include './config/data_tables_js.php'; ?>
  <script src="plugins/moment/moment.min.js"></script>
  <script src="plugins/daterangepicker/daterangepicker.js"></script>
  <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
  
  <script>
    $(document).ready(function() {
      // Initialize DataTable with export buttons
      var table = $("#all_tetanus").DataTable({
        responsive: true,
        lengthChange: false,
        autoWidth: false,
        buttons: [
          {
            extend: 'copy',
            text: '<i class="fas fa-copy"></i> Copy',
            className: 'btn btn-gradient btn-sm'
          },
          {
            extend: 'csv',
            text: '<i class="fas fa-file-csv"></i> CSV',
            className: 'btn btn-gradient btn-sm'
          },
          {
            extend: 'excel',
            text: '<i class="fas fa-file-excel"></i> Excel',
            className: 'btn btn-gradient btn-sm'
          },
          {
            extend: 'pdf',
            text: '<i class="fas fa-file-pdf"></i> PDF',
            className: 'btn btn-gradient btn-sm'
          },
          {
            extend: 'print',
            text: '<i class="fas fa-print"></i> Print',
            className: 'btn btn-gradient btn-sm'
          }
        ],
        language: {
          search: "",
          searchPlaceholder: "Search records..."
        }
      });

      // Custom button click handlers
      $('#btnCopy').on('click', function() {
        table.button('.buttons-copy').trigger();
      });

      $('#btnCSV').on('click', function() {
        table.button('.buttons-csv').trigger();
      });

      $('#btnExcel').on('click', function() {
        table.button('.buttons-excel').trigger();
      });

      $('#btnPDF').on('click', function() {
        table.button('.buttons-pdf').trigger();
      });

      $('#btnPrint').on('click', function() {
        table.button('.buttons-print').trigger();
      });

      // Initialize column visibility menu
      var columnVisibility = $('#columnVisibility');
      table.columns().every(function(index) {
        var column = this;
        var title = $(column.header()).text();
        
        var menuItem = $('<div class="dropdown-item">' +
          '<input type="checkbox" checked="checked" id="col_' + index + '">' +
          '<label for="col_' + index + '">' + title + '</label>' +
          '</div>');
          
        $('input', menuItem).on('click', function() {
          var isVisible = column.visible();
          column.visible(!isVisible);
        });
        
        columnVisibility.append(menuItem);
      });

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
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer)
          toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
      });

      // Show message if exists
      var message = <?php echo json_encode(isset($_GET['message']) ? $_GET['message'] : ''); ?>;
      if(message !== '') {
        Toast.fire({
          icon: 'success',
          title: message
        });
      }

      // Form validation and submission
      $('#saveForm').submit(function(e) {
        // Basic form validation
        let isValid = true;
        const name = $('#name').val().trim();
        const date = $('#date input').val().trim();
        const address = $('#address').val().trim();
        const age = $('#age').val().trim();

        // Clear previous error messages
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // Validate each field
        if (!name) {
          isValid = false;
          $('#name').addClass('is-invalid')
            .after('<div class="invalid-feedback">Name is required</div>');
        }

        if (!date) {
          isValid = false;
          $('#date input').addClass('is-invalid')
            .after('<div class="invalid-feedback">Date is required</div>');
        }

        if (!address) {
          isValid = false;
          $('#address').addClass('is-invalid')
            .after('<div class="invalid-feedback">Address is required</div>');
        }

        if (!age) {
          isValid = false;
          $('#age').addClass('is-invalid')
            .after('<div class="invalid-feedback">Age is required</div>');
        }

        if (!isValid) {
          e.preventDefault();
          // Scroll to first error
          const firstError = $('.is-invalid').first();
          if (firstError.length) {
            $('html, body').animate({
              scrollTop: firstError.offset().top - 100
            }, 500);
          }
        }
      });

      // Show menu
      showMenuSelected("#mnu_patients", "#mi_general_tetanus_toxoid");
    });
  </script>
</body>
</html> 
