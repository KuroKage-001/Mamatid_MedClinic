<?php
include './config/db_connection.php';
include './system/utilities/admin_client_common_functions_services.php';
require_once './system/utilities/admin_client_role_functions_services.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

$message = '';

// Determine if showing archived records
$show_archived = isset($_GET['archived']) && $_GET['archived'] == '1';

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
        // Prepare INSERT query with parameterized statement
        $query = "INSERT INTO `general_bp_monitoring`(`name`, `date`, `address`, `sex`, `bp`, `alcohol`, `smoke`, `obese`, `cp_number`)
                  VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            // Start transaction and execute query
            $con->beginTransaction();
            $stmt = $con->prepare($query);
            $stmt->execute([$name, $date, $address, $sex, $bp, (int)$alcohol, (int)$smoke, (int)$obese, $cp_number]);
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
    header("Location:system/utilities/congratulation.php?goto_page=general_bp_monitoring.php&message=$message");
    exit;
}

// Retrieve BP monitoring records based on archive status
try {
    $archive_condition = $show_archived ? 1 : 0;
    
    if ($show_archived) {
        $query = "SELECT `id`,
                     `name`,
                     `address`,
                     `sex`,
                     `bp`,
                     `alcohol`,
                     `smoke`,
                     `obese`,
                     `cp_number`,
                     DATE_FORMAT(`date`, '%d %b %Y') as `date`,
                     DATE_FORMAT(`created_at`, '%d %b %Y %h:%i %p') as `created_at`,
                     DATE_FORMAT(`archived_at`, '%d %b %Y %h:%i %p') as `archived_at`,
                     `archive_reason`,
                     (SELECT `display_name` FROM `admin_user_accounts` WHERE `id` = `general_bp_monitoring`.`archived_by`) as `archived_by_name`
             FROM `general_bp_monitoring`
             WHERE `is_archived` = ?
             ORDER BY `archived_at` DESC;";
    } else {
        $query = "SELECT `id`,
                     `name`,
                     `address`,
                     `sex`,
                     `bp`,
                     `alcohol`,
                     `smoke`,
                     `obese`,
                     `cp_number`,
                     DATE_FORMAT(`date`, '%d %b %Y') as `date`,
                     DATE_FORMAT(`created_at`, '%d %b %Y %h:%i %p') as `created_at`
             FROM `general_bp_monitoring`
             WHERE `is_archived` = ?
             ORDER BY `date` DESC;";
    }
    
    $stmt = $con->prepare($query);
    $stmt->execute([$archive_condition]);
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
  <?php include './config/data_tables_css_js.php'; ?>
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

    /* Archive Button Styling */
    .btn-archive {
      background: linear-gradient(135deg, #FFA800 0%, #F09000 100%);
      color: white !important;
      border: none;
      padding: 0.375rem 0.75rem;
      font-size: 0.875rem;
      border-radius: 6px;
      transition: all 0.3s ease;
    }

    .btn-archive:hover {
      transform: translateY(-1px);
      box-shadow: 0 3px 10px rgba(255, 168, 0, 0.3);
      color: white !important;
    }

    .btn-unarchive {
      background: linear-gradient(135deg, #1BC5BD 0%, #17B8B0 100%);
      color: white !important;
      border: none;
      padding: 0.375rem 0.75rem;
      font-size: 0.875rem;
      border-radius: 6px;
      transition: all 0.3s ease;
    }

    .btn-unarchive:hover {
      transform: translateY(-1px);
      box-shadow: 0 3px 10px rgba(27, 197, 189, 0.3);
      color: white !important;
    }

    /* Archive Filter Buttons */
    .archive-filter-btn {
      background: linear-gradient(135deg, #E1F0FF 0%, #F8FBFF 100%);
      color: var(--primary-color) !important;
      border: 2px solid var(--primary-color);
      padding: 0.5rem 1rem;
      border-radius: 25px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
      margin-right: 0.5rem;
    }

    .archive-filter-btn:hover {
      background: var(--primary-color);
      color: white !important;
      transform: translateY(-1px);
      box-shadow: 0 4px 15px rgba(54, 153, 255, 0.3);
      text-decoration: none;
    }

    .archive-filter-btn.active {
      background: var(--primary-color);
      color: white !important;
    }

    /* Archived Row Styling */
    .archived-row {
      background-color: rgba(255, 168, 0, 0.05) !important;
      border-left: 4px solid #FFA800;
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
      border-radius: 8px;
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

    /* Modern Export Actions Inline CSS */
    .dt-button-collection {
      display: none !important;
    }

    .export-container {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      flex-wrap: wrap;
      align-items: center;
      margin-bottom: 15px;
    }

    /* Elegant Export Options - Non-Button Design */
    .export-action-btn {
      display: inline-flex !important;
      align-items: center !important;
      gap: 10px !important;
      padding: 12px 18px !important;
      font-size: 0.875rem !important;
      font-weight: 600 !important;
      text-decoration: none !important;
      border-radius: 12px !important;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
      cursor: pointer !important;
      position: relative !important;
      overflow: hidden !important;
      border: 2px solid transparent !important;
      background: rgba(255, 255, 255, 0.9) !important;
      backdrop-filter: blur(10px) !important;
      -webkit-backdrop-filter: blur(10px) !important;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
      text-transform: none !important;
      letter-spacing: 0.3px !important;
    }

    .export-action-btn::before {
      content: '' !important;
      position: absolute !important;
      top: 0 !important;
      left: -100% !important;
      width: 100% !important;
      height: 100% !important;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent) !important;
      transition: left 0.6s ease !important;
    }

    .export-action-btn:hover::before {
      left: 100% !important;
    }

    .export-action-btn:hover {
      transform: translateY(-3px) scale(1.02) !important;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15) !important;
      border-color: currentColor !important;
    }

    .export-action-btn:active {
      transform: translateY(-1px) scale(1.01) !important;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12) !important;
    }

    .export-action-btn i {
      font-size: 1rem !important;
      transition: all 0.3s ease !important;
      opacity: 0.9 !important;
      flex-shrink: 0 !important;
    }

    .export-action-btn:hover i {
      transform: scale(1.15) rotate(5deg) !important;
      opacity: 1 !important;
    }

    /* Sophisticated Color Schemes for Each Export Type */
    .export-copy-btn {
      color: #6366F1 !important;
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(99, 102, 241, 0.05)) !important;
    }

    .export-copy-btn:hover {
      color: #4F46E5 !important;
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(99, 102, 241, 0.08)) !important;
      box-shadow: 0 8px 30px rgba(99, 102, 241, 0.25) !important;
    }

    .export-csv-btn {
      color: #10B981 !important;
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)) !important;
    }

    .export-csv-btn:hover {
      color: #059669 !important;
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.08)) !important;
      box-shadow: 0 8px 30px rgba(16, 185, 129, 0.25) !important;
    }

    .export-excel-btn {
      color: #22C55E !important;
      background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05)) !important;
    }

    .export-excel-btn:hover {
      color: #16A34A !important;
      background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.08)) !important;
      box-shadow: 0 8px 30px rgba(34, 197, 94, 0.25) !important;
    }

    .export-pdf-btn {
      color: #EF4444 !important;
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05)) !important;
    }

    .export-pdf-btn:hover {
      color: #DC2626 !important;
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.08)) !important;
      box-shadow: 0 8px 30px rgba(239, 68, 68, 0.25) !important;
    }

    .export-print-btn {
      color: #8B5CF6 !important;
      background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(139, 92, 246, 0.05)) !important;
    }

    .export-print-btn:hover {
      color: #7C3AED !important;
      background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(139, 92, 246, 0.08)) !important;
      box-shadow: 0 8px 30px rgba(139, 92, 246, 0.25) !important;
    }

    /* Hide default DataTable buttons */
    .dt-buttons {
      display: none !important;
    }

    /* Custom layout for DataTable wrapper */
    #all_bp_wrapper .row:first-child {
      margin-bottom: 15px;
    }

    #all_bp_wrapper .dataTables_filter {
      float: left !important;
      text-align: left !important;
    }

    #all_bp_wrapper .dataTables_filter input {
      width: 300px;
      border-radius: 8px;
      border: 2px solid #e4e6ef;
      padding: 0.625rem 1rem;
      font-size: 1rem;
      transition: all 0.3s;
    }

    #all_bp_wrapper .dataTables_filter input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
    }

    /* Responsive Design for Modern Export Options */
    @media (max-width: 768px) {
      .export-container {
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
      }

      .export-action-btn {
        padding: 10px 14px !important;
        font-size: 0.8125rem !important;
        gap: 8px !important;
        border-radius: 10px !important;
      }

      .export-action-btn i {
        font-size: 0.9rem !important;
      }
    }

    @media (max-width: 576px) {
      .export-container {
        gap: 6px;
        flex-direction: column;
        align-items: stretch;
      }

      .export-action-btn {
        padding: 8px 12px !important;
        font-size: 0.75rem !important;
        gap: 6px !important;
        border-radius: 8px !important;
        justify-content: center !important;
      }

      .export-action-btn i {
        font-size: 0.85rem !important;
      }

      .export-action-btn:hover {
        transform: translateY(-2px) scale(1.01) !important;
      }

      #all_bp_wrapper .dataTables_filter input {
        width: 100%;
      }
    }
  </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <div class="wrapper">
    <?php 
      include './config/admin_header.php';
      include './config/admin_sidebar.php'; 
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

      <!-- Add BP Monitoring Record Form (only show for active records) -->
      <?php if (!$show_archived): ?>
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
      <?php endif; ?>

      <section class="content">
        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title"><?php echo $show_archived ? 'Archived BP Monitoring Records' : 'Active BP Monitoring Records'; ?></h3>
            <div class="card-tools">
              <div class="d-flex align-items-center">
                <a href="general_bp_monitoring.php" 
                   class="archive-filter-btn <?php echo !$show_archived ? 'active' : ''; ?>">
                  <i class="fas fa-list"></i> Active Records
                </a>
                <a href="general_bp_monitoring.php?archived=1" 
                   class="archive-filter-btn <?php echo $show_archived ? 'active' : ''; ?>">
                  <i class="fas fa-archive"></i> Archived Records
                </a>
                <button type="button" class="btn btn-tool ml-2" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
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
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td><?php echo htmlspecialchars($row['sex']); ?></td>
                    <td><?php echo htmlspecialchars($row['bp']); ?></td>
                    <td><span class="badge badge-<?php echo ($row['alcohol'] == 1) ? 'warning' : 'light'; ?>"><?php echo ($row['alcohol'] == 1) ? 'Yes' : 'No'; ?></span></td>
                    <td><span class="badge badge-<?php echo ($row['smoke'] == 1) ? 'danger' : 'light'; ?>"><?php echo ($row['smoke'] == 1) ? 'Yes' : 'No'; ?></span></td>
                    <td><span class="badge badge-<?php echo ($row['obese'] == 1) ? 'info' : 'light'; ?>"><?php echo ($row['obese'] == 1) ? 'Yes' : 'No'; ?></span></td>
                    <td><?php echo htmlspecialchars($row['cp_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <?php if ($show_archived): ?>
                    <td><?php echo htmlspecialchars($row['archived_at'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['archived_by_name'] ?? ''); ?></td>
                    <td>
                      <?php if (!empty($row['archive_reason'])): ?>
                        <span class="badge badge-info" data-toggle="tooltip" title="<?php echo htmlspecialchars($row['archive_reason']); ?>">
                          <i class="fas fa-info-circle"></i> Reason
                        </span>
                      <?php else: ?>
                        <span class="text-muted">No reason provided</span>
                      <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                      <?php if ($show_archived): ?>
                        <button type="button" class="btn btn-unarchive btn-sm" 
                                onclick="unarchiveBPRecord(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>')">
                          <i class="fas fa-undo"></i> Unarchive
                        </button>
                      <?php else: ?>
                        <a href="update_bp.php?id=<?php echo $row['id']; ?>" 
                           class="btn btn-primary btn-sm">
                          <i class="fa fa-edit"></i> Edit
                        </a>
                        <button type="button" class="btn btn-archive btn-sm ml-1" 
                                onclick="archiveBPRecord(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>')">
                          <i class="fas fa-archive"></i> Archive
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
              <div class="export-container mt-3 mb-3" id="exportContainer">
                <a href="#" class="export-action-btn export-copy-btn" id="btnCopy">
                  <i class="fas fa-copy"></i>
                  <span>Copy</span>
                </a>
                <a href="#" class="export-action-btn export-csv-btn" id="btnCSV">
                  <i class="fas fa-file-csv"></i>
                  <span>CSV</span>
                </a>
                <a href="#" class="export-action-btn export-excel-btn" id="btnExcel">
                  <i class="fas fa-file-excel"></i>
                  <span>Excel</span>
                </a>
                <a href="#" class="export-action-btn export-pdf-btn" id="btnPDF">
                  <i class="fas fa-file-pdf"></i>
                  <span>PDF</span>
                </a>
                <a href="#" class="export-action-btn export-print-btn" id="btnPrint">
                  <i class="fas fa-print"></i>
                  <span>Print</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <?php
      include './config/admin_footer.php';
      $message = isset($_GET['message']) ? $_GET['message'] : '';
    ?>
    
    <?php include './config/site_css_js_links.php'; ?>
    
    <script src="plugins/moment/moment.min.js"></script>
    <script src="plugins/daterangepicker/daterangepicker.js"></script>
    <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <script>
      // Archive BP Record Function
      function archiveBPRecord(id, name) {
        Swal.fire({
          title: 'Archive BP Monitoring Record',
          html: `
            <p>Are you sure you want to archive the BP monitoring record for <strong>${name}</strong>?</p>
            <div class="form-group mt-3">
              <label for="archiveReason" class="form-label">Archive Reason (Optional):</label>
              <textarea id="archiveReason" class="form-control" rows="3" placeholder="Enter reason for archiving..."></textarea>
            </div>
          `,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#FFA800',
          cancelButtonColor: '#6c757d',
          confirmButtonText: '<i class="fas fa-archive"></i> Archive Record',
          cancelButtonText: '<i class="fas fa-times"></i> Cancel',
          customClass: {
            container: 'swal-archive-container'
          },
          preConfirm: () => {
            const reason = document.getElementById('archiveReason').value.trim();
            return { reason: reason };
          }
        }).then((result) => {
          if (result.isConfirmed) {
            // Show loading
            Swal.fire({
              title: 'Archiving...',
              text: 'Please wait while we archive the record.',
              allowOutsideClick: false,
              allowEscapeKey: false,
              showConfirmButton: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });

            // Send archive request
            fetch('actions/archive_bp_monitoring.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                id: id,
                reason: result.value.reason
              })
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  icon: 'success',
                  title: 'Archived Successfully!',
                  text: data.message,
                  confirmButtonColor: '#1BC5BD'
                }).then(() => {
                  location.reload();
                });
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Archive Failed',
                  text: data.message,
                  confirmButtonColor: '#F64E60'
                });
              }
            })
            .catch(error => {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred. Please try again.',
                confirmButtonColor: '#F64E60'
              });
            });
          }
        });
      }

      // Unarchive BP Record Function
      function unarchiveBPRecord(id, name) {
        Swal.fire({
          title: 'Unarchive BP Monitoring Record',
          text: `Are you sure you want to unarchive the BP monitoring record for ${name}?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#1BC5BD',
          cancelButtonColor: '#6c757d',
          confirmButtonText: '<i class="fas fa-undo"></i> Unarchive Record',
          cancelButtonText: '<i class="fas fa-times"></i> Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            // Show loading
            Swal.fire({
              title: 'Unarchiving...',
              text: 'Please wait while we unarchive the record.',
              allowOutsideClick: false,
              allowEscapeKey: false,
              showConfirmButton: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });

            // Send unarchive request
            fetch('actions/unarchive_bp_monitoring.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                id: id
              })
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  icon: 'success',
                  title: 'Unarchived Successfully!',
                  text: data.message,
                  confirmButtonColor: '#1BC5BD'
                }).then(() => {
                  location.reload();
                });
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Unarchive Failed',
                  text: data.message,
                  confirmButtonColor: '#F64E60'
                });
              }
            })
            .catch(error => {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred. Please try again.',
                confirmButtonColor: '#F64E60'
              });
            });
          }
        });
      }

      $(document).ready(function() {
        // Initialize DataTable with export buttons
        var table = $("#all_bp").DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          pageLength: 5,
          pagingType: "simple_numbers",
          dom: 'Bfrtip',
          buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
          ],
          language: {
            search: "",
            searchPlaceholder: "Search records...",
            paginate: {
              previous: "<i class='fas fa-chevron-left'></i>",
              next: "<i class='fas fa-chevron-right'></i>"
            }
          }
        });

        // Hide default buttons
        $('.dt-buttons').hide();

        // Custom export button handlers
        $('#btnCopy').click(function(e) {
          e.preventDefault();
          table.button('.buttons-copy').trigger();
          
          // Show toast notification
          const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
          });
          
          Toast.fire({
            icon: 'success',
            title: 'Data copied to clipboard!'
          });
        });

        $('#btnCSV').click(function(e) {
          e.preventDefault();
          table.button('.buttons-csv').trigger();
        });

        $('#btnExcel').click(function(e) {
          e.preventDefault();
          table.button('.buttons-excel').trigger();
        });

        $('#btnPDF').click(function(e) {
          e.preventDefault();
          table.button('.buttons-pdf').trigger();
        });

        $('#btnPrint').click(function(e) {
          e.preventDefault();
          table.button('.buttons-print').trigger();
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

        // Show menu
        showMenuSelected("#mnu_patients", "#mi_bp_monitoring");
      });
    </script>
</body>
</html> 