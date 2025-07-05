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

// Handle form submission to save a new family member
if (isset($_POST['save_family_member'])) {
    // Retrieve and sanitize form inputs
    $name = trim($_POST['name']);
    $date = trim($_POST['date']);

    // Convert date format from MM/DD/YYYY to YYYY-MM-DD
    $dateArr = explode("/", $date);
    $date = $dateArr[2] . '-' . $dateArr[0] . '-' . $dateArr[1];

    // Format name (capitalize each word)
    $name = ucwords(strtolower($name));

    // Check if all required fields are provided
    if ($name != '' && $date != '') {
        // Prepare INSERT query
        $query = "INSERT INTO `general_family_members`(`name`, `date`)
                  VALUES('$name', '$date');";
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
    header("Location:system/utilities/congratulation.php?goto_page=general_family_members.php&message=$message");
    exit;
}

// Add filter for viewing archived/unarchived records
$show_archived = isset($_GET['show_archived']) ? (bool)$_GET['show_archived'] : false;

// Retrieve family members based on archive status
try {
    if ($show_archived) {
        $query = "SELECT `id`, `name`, 
                         DATE_FORMAT(`date`, '%d %b %Y') as `date`,
                         DATE_FORMAT(`created_at`, '%d %b %Y %h:%i %p') as `created_at`,
                         DATE_FORMAT(`archived_at`, '%d %b %Y %h:%i %p') as `archived_at`,
                         `archive_reason`,
                         (SELECT `display_name` FROM `admin_user_accounts` WHERE `id` = `general_family_members`.`archived_by`) as `archived_by_name`
                  FROM `general_family_members`
                  WHERE `is_archived` = 1
                  ORDER BY `archived_at` DESC;";
    } else {
        $query = "SELECT `id`, `name`, 
                         DATE_FORMAT(`date`, '%d %b %Y') as `date`,
                         DATE_FORMAT(`created_at`, '%d %b %Y %h:%i %p') as `created_at`
                  FROM `general_family_members`
                  WHERE `is_archived` = 0
                  ORDER BY `date` DESC;";
    }
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

    /* Archive-specific styles */
    .btn-warning {
      background: linear-gradient(135deg, var(--warning-color) 0%, #E8A317 100%);
      border: none;
      color: white;
    }

    .btn-warning:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(255, 168, 0, 0.4);
      color: white;
    }

    .btn-success {
      background: linear-gradient(135deg, var(--success-color) 0%, #159C96 100%);
      border: none;
      color: white;
    }

    .btn-success:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(27, 197, 189, 0.4);
      color: white;
    }

    .btn-secondary {
      background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
      border: none;
      color: white;
    }

    .btn-secondary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
      color: white;
    }

    .archived-row {
      background-color: rgba(255, 168, 0, 0.1) !important;
    }

    .archive-filter-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .archive-filter-tabs .btn {
      border-radius: 20px;
      padding: 8px 20px;
      font-weight: 500;
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
              <h1>Family Members</h1>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title">Add Family Member</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <form method="post">
              <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
                  <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" id="name" name="name" required="required"
                           class="form-control" placeholder="Enter member name"/>
                  </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
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
              </div>
              <div class="row">
                <div class="col-12 text-right">
                  <button type="submit" id="save_family_member" name="save_family_member" 
                          class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Save Member
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="card card-outline card-primary">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">
              <?php echo $show_archived ? 'Archived Family Members' : 'Active Family Members'; ?>
            </h3>
            <div class="d-flex gap-2">
              <div class="archive-filter-tabs">
                <a href="general_family_members.php" class="btn <?php echo !$show_archived ? 'btn-primary' : 'btn-secondary'; ?>">
                  <i class="fas fa-users"></i> Active Records
                </a>
                <a href="general_family_members.php?show_archived=1" class="btn <?php echo $show_archived ? 'btn-warning' : 'btn-secondary'; ?>">
                  <i class="fas fa-archive"></i> Archived Records
                </a>
              </div>
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
              <table id="all_family_members" class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Created At</th>
                    <?php if ($show_archived): ?>
                      <th>Archived At</th>
                      <th>Archived By</th>
                      <th>Archive Reason</th>
                    <?php endif; ?>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $count = 0;
                  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      $count++;
                  ?>
                  <tr <?php echo $show_archived ? 'class="archived-row"' : ''; ?>>
                    <td><?php echo $count; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo $row['date']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <?php if ($show_archived): ?>
                      <td><?php echo $row['archived_at'] ?? 'N/A'; ?></td>
                      <td><?php echo htmlspecialchars($row['archived_by_name'] ?? 'Unknown'); ?></td>
                      <td><?php echo htmlspecialchars($row['archive_reason'] ?? 'No reason provided'); ?></td>
                    <?php endif; ?>
                    <td>
                      <?php if ($show_archived): ?>
                        <!-- Unarchive Button -->
                        <button type="button" class="btn btn-success btn-sm" 
                                onclick="unarchiveRecord(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                          <i class="fas fa-undo"></i> Unarchive
                        </button>
                      <?php else: ?>
                        <!-- Edit Button -->
                        <a href="update_family_member.php?id=<?php echo $row['id']; ?>" 
                           class="btn btn-primary btn-sm">
                          <i class="fa fa-edit"></i> Edit
                        </a>
                        <!-- Archive Button -->
                        <button type="button" class="btn btn-warning btn-sm" 
                                onclick="archiveRecord(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                          <i class="fas fa-archive"></i> Archive
                        </button>
                      <?php endif; ?>
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
      var table = $("#all_family_members").DataTable({
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
    });

    // Archive Record Function
    function archiveRecord(id, name) {
      Swal.fire({
        title: 'Archive Family Member',
        html: `
          <p>Are you sure you want to archive <strong>${name}</strong>?</p>
          <div class="form-group mt-3">
            <label for="archive_reason">Reason for archiving:</label>
            <textarea class="form-control" id="archive_reason" rows="3" placeholder="Enter reason for archiving (optional)"></textarea>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FFA800',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-archive"></i> Archive',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        preConfirm: () => {
          const reason = document.getElementById('archive_reason').value;
          return { reason: reason };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          // Create form and submit
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'actions/archive_family_member.php';
          
          const idInput = document.createElement('input');
          idInput.type = 'hidden';
          idInput.name = 'archive_id';
          idInput.value = id;
          
          const reasonInput = document.createElement('input');
          reasonInput.type = 'hidden';
          reasonInput.name = 'archive_reason';
          reasonInput.value = result.value.reason;
          
          form.appendChild(idInput);
          form.appendChild(reasonInput);
          document.body.appendChild(form);
          form.submit();
        }
      });
    }

    // Unarchive Record Function
    function unarchiveRecord(id, name) {
      Swal.fire({
        title: 'Unarchive Family Member',
        text: `Are you sure you want to unarchive ${name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1BC5BD',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-undo"></i> Unarchive',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          // Create form and submit
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'actions/unarchive_family_member.php';
          
          const idInput = document.createElement('input');
          idInput.type = 'hidden';
          idInput.name = 'unarchive_id';
          idInput.value = id;
          
          form.appendChild(idInput);
          document.body.appendChild(form);
          form.submit();
        }
      });
    }

    // Show menu
    showMenuSelected("#mnu_patients", "#mi_family_members");
  </script>
</body>
</html> 