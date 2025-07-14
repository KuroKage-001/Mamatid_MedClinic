<?php
// Include the database connection and common functions
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
// Retrieve patients list as HTML <option> elements using the getAllPatientsWithHistory() function
$patients = getAllPatientsWithHistory($con);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <?php include './config/data_tables_css_js.php'; ?>
  <!-- Toastr CSS -->
  <link rel="stylesheet" href="plugins/toastr/toastr.min.css">
  <!-- Select2 CSS -->
  <link rel="stylesheet" href="plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <!-- Logo for the tab bar -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <!-- SheetJS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>
  <!-- pdfmake -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>
  <title>Patient History - Mamatid Health Center System</title>

  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #3699FF 0%, #2684FF 100%);
      --success-gradient: linear-gradient(135deg, #1BC5BD 0%, #17B8B0 100%);
      --warning-gradient: linear-gradient(135deg, #FFA800 0%, #FF8B00 100%);
      --danger-gradient: linear-gradient(135deg, #F64E60 0%, #EE2D41 100%);
      --info-gradient: linear-gradient(135deg, #8950FC 0%, #7337EE 100%);
      --card-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      --transition: all 0.3s ease;
    }

    /* Card Styling */
    .card {
      background: #fff;
      border: none;
      box-shadow: var(--card-shadow);
      border-radius: 15px;
      margin-bottom: 30px;
      transition: var(--transition);
    }

    .card:hover {
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
    }

    .card-header {
      background: #fff;
      padding: 1.5rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      border-radius: 15px 15px 0 0 !important;
    }

    .card-header h3 {
      font-size: 1.25rem;
      font-weight: 600;
      color: #181C32;
      margin: 0;
    }

    /* Search Form */
    .search-form {
      background: #F3F6F9;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 25px;
    }

    /* Table Styling */
    .table-container {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 25px;
      box-shadow: var(--card-shadow);
    }

    .table {
      width: 100%;
      margin-bottom: 0;
    }

    .table thead th {
      background: #F3F6F9;
      color: #3F4254;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.85rem;
      padding: 1rem;
      border: none;
    }

    .table tbody td {
      padding: 1rem;
      vertical-align: middle;
      border-top: 1px solid #F3F6F9;
      color: #7E8299;
    }

    .table-hover tbody tr:hover {
      background-color: #F3F6F9;
    }

    /* Export Buttons */
    .export-buttons {
      display: flex;
      gap: 8px;
      align-items: center;
      margin-bottom: 15px;
    }

    .btn-gradient {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      border: none !important;
      color: #fff !important;
      transition: all 0.3s ease;
    }

    .export-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 15px;
      font-size: 0.875rem;
      font-weight: 500;
      border-radius: 6px;
      transition: all 0.3s ease;
    }

    .export-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .export-btn i {
      font-size: 0.875rem;
    }

    /* Button gradients */
    #btnCopyAll, #btnCopy {
      background: linear-gradient(135deg, #3699FF 0%, #2684FF 100%);
    }

    #btnCSVAll, #btnCSV {
      background: linear-gradient(135deg, #1BC5BD 0%, #17B8B0 100%);
    }

    #btnExcelAll, #btnExcel {
      background: linear-gradient(135deg, #20C997 0%, #1CB984 100%);
    }

    #btnPDFAll, #btnPDF {
      background: linear-gradient(135deg, #F64E60 0%, #EE2D41 100%);
    }

    #btnPrintAll, #btnPrint {
      background: linear-gradient(135deg, #8950FC 0%, #7337EE 100%);
    }

    /* Loading state */
    .export-btn.loading {
      opacity: 0.7;
      pointer-events: none;
    }

    .export-btn.loading i {
      animation: fa-spin 2s infinite linear;
    }

    /* Tabs Styling */
    .nav-tabs {
      border: none;
      margin-bottom: 20px;
      gap: 10px;
    }

    .nav-tabs .nav-item {
      margin: 0;
    }

    .nav-tabs .nav-link {
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      font-weight: 500;
      color: #7E8299;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .nav-tabs .nav-link:hover {
      color: #3699FF;
      background: #F3F6F9;
    }

    .nav-tabs .nav-link.active {
      color: #3699FF;
      background: #E1F0FF;
    }

    .nav-tabs .nav-link i {
      font-size: 1rem;
    }

    /* Search Input and Select2 */
    .form-control, .select2-container--bootstrap4 .select2-selection {
      height: calc(2.5rem + 2px);
      border-radius: 8px;
      border: 2px solid #E4E6EF;
      padding: 0.625rem 1rem;
      font-size: 1rem;
      transition: var(--transition);
      background-color: #fff;
    }

    .form-control:focus, .select2-container--bootstrap4.select2-container--focus .select2-selection {
      border-color: #3699FF;
      box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
    }

    /* Search Button */
    .btn-primary {
      background: var(--primary-gradient);
      border: none;
      padding: 0.625rem 1.25rem;
      font-weight: 500;
      border-radius: 8px;
      transition: var(--transition);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(54, 153, 255, 0.4);
    }

    /* Loading States */
    .loading {
      opacity: 0.7;
      pointer-events: none;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .export-buttons {
        flex-direction: column;
      }
      
      .export-btn {
        width: 100%;
      }

      .nav-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      .nav-tabs .nav-link {
        white-space: nowrap;
      }
    }

    /* Export Buttons Styling */
    .export-dropdown-btn {
      width: 100%;
      padding: 10px 15px;
      border-radius: 8px;
      font-weight: 500;
      background: linear-gradient(135deg, #3699FF 0%, #2684FF 100%);
      color: white;
      border: none;
      transition: all 0.3s ease;
      box-shadow: 0 2px 6px rgba(54, 153, 255, 0.2);
    }

    .export-dropdown-btn:hover,
    .export-dropdown-btn:focus {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(54, 153, 255, 0.3);
      color: white;
    }

    .dropdown-menu {
      padding: 8px;
      border: none;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    .dropdown-item.export-btn {
      padding: 10px 15px;
      border-radius: 6px;
      margin-bottom: 4px;
      color: white;
      transition: all 0.3s ease;
    }

    .dropdown-item.export-btn:last-child {
      margin-bottom: 0;
    }

    .dropdown-item.export-btn:hover {
      transform: translateX(5px);
    }

    .gradient-copy {
      background: linear-gradient(135deg, #3699FF 0%, #2684FF 100%);
    }

    .gradient-csv {
      background: linear-gradient(135deg, #1BC5BD 0%, #17B8B0 100%);
    }

    .gradient-excel {
      background: linear-gradient(135deg, #20C997 0%, #1CB984 100%);
    }

    .gradient-pdf {
      background: linear-gradient(135deg, #F64E60 0%, #EE2D41 100%);
    }

    .gradient-print {
      background: linear-gradient(135deg, #8950FC 0%, #7337EE 100%);
    }

    .dropdown-item.export-btn i {
      width: 20px;
      text-align: center;
    }

    /* Loading state for dropdown button */
    .export-dropdown-btn.loading {
      opacity: 0.7;
      pointer-events: none;
    }

    .export-dropdown-btn.loading i {
      animation: fa-spin 2s infinite linear;
    }

    @media (max-width: 768px) {
      .export-dropdown-btn {
        margin-top: 10px;
      }
    }
  </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar and Sidebar -->
    <?php 
      include './config/admin_header.php';
      include './config/admin_sidebar.php';
    ?>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
      <!-- Content Header -->
      <section class="content-header">
        <div class="container-fluid">
          <div class="row align-items-center mb-4">
            <div class="col-12 col-md-6" style="padding-left: 20px;">
              <h1>Patient History</h1>
            </div>
            <div class="col-12 col-md-6 text-md-right mt-3 mt-md-0">
              <span id="datetime" class="d-inline-block"></span>
            </div>
          </div>
        </div>
      </section>

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <!-- Search Card -->
          <div class="card card-outline card-primary rounded-lg shadow-sm">
            <div class="card-header bg-white border-bottom">
              <h3 class="card-title d-flex align-items-center">
                <i class="fas fa-search text-primary mr-2"></i>
                Search Patient History
              </h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <!-- Search Form -->
              <div class="search-container p-4 bg-light rounded-lg">
                <div class="row g-3 align-items-center">
                  <div class="col-lg-5 col-md-5 col-sm-12">
                    <div class="form-group mb-0">
                      <label for="patient" class="form-label text-muted mb-2">
                        <i class="fas fa-user-circle mr-1"></i> Select Patient
                      </label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text bg-white border-right-0">
                            <i class="fas fa-search text-primary"></i>
                          </span>
                        </div>
                        <select id="patient" class="form-control border-left-0 shadow-none">
                          <?php echo $patients; ?>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <div class="col-lg-3 col-md-3 col-sm-12">
                    <div class="form-group mb-0">
                      <label class="form-label text-muted mb-2">
                        <i class="fas fa-filter mr-1"></i> Actions
                      </label>
                      <button type="button" id="search" class="btn btn-primary btn-block d-flex align-items-center justify-content-center">
                        <i class="fas fa-search mr-2"></i>
                        Search Records
                      </button>
                    </div>
                  </div>

                  <div class="col-lg-4 col-md-4 col-sm-12">
                    <div class="form-group mb-0">
                      <label class="form-label text-muted mb-2">
                        <i class="fas fa-file-export mr-1"></i> Export Options
                      </label>
                      <div class="dropdown">
                        <button class="btn btn-gradient-primary btn-block dropdown-toggle d-flex align-items-center justify-content-center" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <i class="fas fa-file-export mr-2"></i>
                          Export Records
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow-lg w-100 border-0 py-2" aria-labelledby="exportDropdown">
                          <button class="dropdown-item d-flex align-items-center py-2 px-3 export-btn gradient-copy" id="btnCopyAll">
                            <i class="fas fa-copy mr-2"></i> Copy to Clipboard
                          </button>
                          <button class="dropdown-item d-flex align-items-center py-2 px-3 export-btn gradient-csv" id="btnCSVAll">
                            <i class="fas fa-file-csv mr-2"></i> Export as CSV
                          </button>
                          <button class="dropdown-item d-flex align-items-center py-2 px-3 export-btn gradient-excel" id="btnExcelAll">
                            <i class="fas fa-file-excel mr-2"></i> Export as Excel
                          </button>
                          <button class="dropdown-item d-flex align-items-center py-2 px-3 export-btn gradient-pdf" id="btnPDFAll">
                            <i class="fas fa-file-pdf mr-2"></i> Export as PDF
                          </button>
                          <button class="dropdown-item d-flex align-items-center py-2 px-3 export-btn gradient-print" id="btnPrintAll">
                            <i class="fas fa-print mr-2"></i> Print Records
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Patient History Tabs -->
              <div class="row">
                <div class="col-md-12">
                  <ul class="nav nav-tabs" id="historyTabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active" id="family-tab" data-toggle="tab" href="#family" role="tab">
                        <i class="fas fa-users mr-2"></i>Family Planning
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="deworming-tab" data-toggle="tab" href="#deworming" role="tab">
                        <i class="fas fa-pills mr-2"></i>Deworming
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="bp-tab" data-toggle="tab" href="#bp" role="tab">
                        <i class="fas fa-heartbeat mr-2"></i>BP Monitoring
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="blood-sugar-tab" data-toggle="tab" href="#blood-sugar" role="tab">
                        <i class="fas fa-tint mr-2"></i>Blood Sugar
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="tetanus-tab" data-toggle="tab" href="#tetanus" role="tab">
                        <i class="fas fa-syringe mr-2"></i>Tetanus Toxoid
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="family-members-tab" data-toggle="tab" href="#family-members" role="tab">
                        <i class="fas fa-users mr-2"></i>Family Members
                      </a>
                    </li>
                  </ul>

                  <div class="tab-content" id="historyTabsContent">
                    <!-- Family Planning History -->
                    <div class="tab-pane fade show active" id="family" role="tabpanel">
                      <div class="table-container">
                      <div class="table-responsive">
                          <table id="family_history" class="table table-hover">
                          <thead>
                            <tr>
                              <th>S.No</th>
                              <th>Name</th>
                              <th>Date</th>
                              <th>Age</th>
                              <th>Address</th>
                            </tr>
                          </thead>
                          <tbody id="family_data">
                          </tbody>
                        </table>
                        </div>
                      </div>
                    </div>

                    <!-- Deworming History -->
                    <div class="tab-pane fade" id="deworming" role="tabpanel">
                      <div class="table-container">
                      <div class="table-responsive">
                          <table id="deworming_history" class="table table-hover">
                          <thead>
                            <tr>
                              <th>S.No</th>
                              <th>Name</th>
                              <th>Date</th>
                              <th>Age</th>
                              <th>Birthday</th>
                            </tr>
                          </thead>
                          <tbody id="deworming_data">
                          </tbody>
                        </table>
                        </div>
                      </div>
                    </div>

                    <!-- BP Monitoring History -->
                    <div class="tab-pane fade" id="bp" role="tabpanel">
                      <div class="table-container">
                      <div class="table-responsive">
                          <table id="bp_history" class="table table-hover">
                          <thead>
                            <tr>
                              <th>S.No</th>
                              <th>Name</th>
                              <th>Date</th>
                              <th>BP Reading</th>
                              <th>Alcohol</th>
                              <th>Smoke</th>
                              <th>Obese</th>
                            </tr>
                          </thead>
                          <tbody id="bp_data">
                          </tbody>
                        </table>
                        </div>
                      </div>
                    </div>

                    <!-- Blood Sugar History -->
                    <div class="tab-pane fade" id="blood-sugar" role="tabpanel">
                      <div class="table-container">
                      <div class="table-responsive">
                          <table id="blood_sugar_history" class="table table-hover">
                          <thead>
                            <tr>
                              <th>S.No</th>
                              <th>Name</th>
                              <th>Date</th>
                              <th>Age</th>
                              <th>Result</th>
                            </tr>
                          </thead>
                          <tbody id="blood_sugar_data">
                          </tbody>
                        </table>
                        </div>
                      </div>
                    </div>

                    <!-- Tetanus Toxoid History -->
                    <div class="tab-pane fade" id="tetanus" role="tabpanel">
                      <div class="table-container">
                      <div class="table-responsive">
                          <table id="tetanus_history" class="table table-hover">
                          <thead>
                            <tr>
                              <th>S.No</th>
                              <th>Name</th>
                              <th>Date</th>
                              <th>Age</th>
                              <th>Diagnosis</th>
                              <th>Remarks</th>
                            </tr>
                          </thead>
                          <tbody id="tetanus_data">
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>

                    <!-- Family Members History -->
                    <div class="tab-pane fade" id="family-members" role="tabpanel">
                      <div class="table-container">
                        <div class="table-responsive">
                          <table id="family_members_history" class="table table-hover">
                            <thead>
                              <tr>
                                <th>S.No</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Created At</th>
                              </tr>
                            </thead>
                            <tbody id="family_members_data">
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
    <!-- /.content-wrapper -->

    <?php include './config/admin_footer.php'; ?>
    <!-- /.control-sidebar -->
  </div>
  <!-- ./wrapper -->

  <?php include './config/site_css_js_links.php'; ?>
  
  <!-- Toastr -->
  <script src="plugins/toastr/toastr.min.js"></script>
  <!-- Select2 -->
  <script src="plugins/select2/js/select2.full.min.js"></script>

  <script>
    $(document).ready(function() {
      // Define tableNames at the beginning of the script
      const tableNames = {
        family: 'Family Planning History',
        deworming: 'Deworming History',
        bp: 'BP Monitoring History',
        bloodSugar: 'Blood Sugar History',
        tetanus: 'Tetanus Toxoid History',
        familyMembers: 'Family Members History'
      };

      // Configure toastr options
      toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
      };

      // Function to handle AJAX errors globally
      $(document).ajaxError(function(event, jqXHR, settings, error) {
        console.error('Ajax error:', error);
        console.error('Server response:', jqXHR.responseText);
        toastr.error('An error occurred while processing your request. Please try again.');
      });

      // Initialize DataTables with modern styling
      var tables = {
        family: $('#family_history').DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          buttons: ["copy", "csv", "excel", "pdf", "print"],
          language: {
            search: "",
            searchPlaceholder: "Search records...",
            emptyTable: "No history available"
          }
        }),
        deworming: $('#deworming_history').DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          buttons: ["copy", "csv", "excel", "pdf", "print"],
          language: {
            search: "",
            searchPlaceholder: "Search records...",
            emptyTable: "No history available"
          }
        }),
        bp: $('#bp_history').DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          buttons: ["copy", "csv", "excel", "pdf", "print"],
          language: {
            search: "",
            searchPlaceholder: "Search records...",
            emptyTable: "No history available"
          }
        }),
        bloodSugar: $('#blood_sugar_history').DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          buttons: ["copy", "csv", "excel", "pdf", "print"],
          language: {
            search: "",
            searchPlaceholder: "Search records...",
            emptyTable: "No history available"
          }
        }),
        tetanus: $('#tetanus_history').DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          buttons: ["copy", "csv", "excel", "pdf", "print"],
          language: {
            search: "",
            searchPlaceholder: "Search records...",
            emptyTable: "No history available"
          }
        }),
        familyMembers: $('#family_members_history').DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          buttons: ["copy", "csv", "excel", "pdf", "print"],
          language: {
            search: "",
            searchPlaceholder: "Search records...",
            emptyTable: "No family members records available"
          },
          order: [[2, 'desc']], // Sort by date column by default
          columnDefs: [
            { targets: 2, type: 'date' }, // Ensure date column is treated as date
            { targets: 3, type: 'date' }  // Ensure created_at column is treated as date
          ]
        })
      };

      // Function to get all table data
      function getAllTableData() {
        const patientName = $("#patient option:selected").text();
        let allData = [];
        
        // Add title and patient info
        allData.push(['Patient History Report']);
        allData.push(['Patient Name:', patientName]);
        allData.push(['Generated on:', new Date().toLocaleString()]);
        allData.push([]);

        // Add data from each table
        Object.entries(tables).forEach(([key, table]) => {
          const data = table.data().toArray();
          if (data.length > 0) {
            // Add section title
            allData.push([tableNames[key]]);
            allData.push([]);
            
            // Add headers
            const headers = [];
            table.columns().every(function() {
              headers.push($(this.header()).text());
            });
            allData.push(headers);
            
            // Add data rows
            data.forEach(row => {
              allData.push(row.map(cell => {
                // Remove HTML tags if present
                return cell.toString().replace(/<[^>]*>/g, '');
              }));
            });
            
            // Add spacing
            allData.push([]);
            allData.push([]);
          }
        });
        
        return allData;
      }

      // Function to show loading state
      function showLoading(button) {
        const originalText = button.html();
        button.addClass('loading')
              .html('<i class="fas fa-spinner"></i> Processing...');
        return originalText;
      }

      // Function to hide loading state
      function hideLoading(button, originalText) {
        button.removeClass('loading').html(originalText);
      }

      // Copy All button
      $('#btnCopyAll').on('click', function() {
        const button = $(this);
        const originalText = showLoading(button);

        setTimeout(() => {
          const allData = getAllTableData();
          const textData = allData.map(row => row.join('\t')).join('\n');
          
          const temp = $("<textarea>");
          $("body").append(temp);
          temp.val(textData).select();
          document.execCommand("copy");
          temp.remove();
          
          hideLoading(button, originalText);
          toastr.success('Copied to clipboard');
        }, 500);
      });

      // CSV All button
      $('#btnCSVAll').on('click', function() {
        const button = $(this);
        const originalText = showLoading(button);

        setTimeout(() => {
          const allData = getAllTableData();
          const csvContent = allData.map(row => 
            row.map(cell => 
              typeof cell === 'string' ? `"${cell.replace(/"/g, '""')}"` : cell
            ).join(',')
          ).join('\n');

          const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
          const link = document.createElement("a");
          const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", "patient_history.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
          
          hideLoading(button, originalText);
          toastr.success('CSV file downloaded');
        }, 500);
      });

      // Excel All button
      $('#btnExcelAll').on('click', function() {
        const button = $(this);
        const originalText = showLoading(button);

        setTimeout(() => {
          const allData = getAllTableData();
          const wb = XLSX.utils.book_new();
          const ws = XLSX.utils.aoa_to_sheet(allData);
              XLSX.utils.book_append_sheet(wb, ws, "Patient History");
              XLSX.writeFile(wb, "patient_history.xlsx");
          
          hideLoading(button, originalText);
          toastr.success('Excel file downloaded');
        }, 500);
      });

      // PDF All button
      $('#btnPDFAll').on('click', function() {
        const button = $(this);
        const originalText = showLoading(button);

        setTimeout(() => {
          const allData = getAllTableData();
          const patientName = $("#patient option:selected").text();
          
          const docDefinition = {
            pageOrientation: 'landscape',
                content: [
                  { text: 'Patient History Report', style: 'header' },
              { text: patientName, style: 'subheader' },
              { text: 'Generated on: ' + new Date().toLocaleString(), style: 'date' },
              { text: '\n' }
                ],
                styles: {
                  header: {
                    fontSize: 18,
                    bold: true,
                    margin: [0, 0, 0, 10]
                  },
                  subheader: {
                    fontSize: 14,
                    bold: true,
                    margin: [0, 10, 0, 5]
              },
              date: {
                fontSize: 12,
                color: '#666',
                margin: [0, 0, 0, 20]
              },
              tableHeader: {
                bold: true,
                fontSize: 11,
                color: 'black',
                fillColor: '#f3f6f9'
              }
            },
            defaultStyle: {
              fontSize: 10
            }
          };

          // Add each section
          let currentSection = '';
          let currentTable = [];
          let currentHeaders = [];

          allData.forEach((row, index) => {
            if (row.length === 1 && Object.values(tableNames).includes(row[0])) {
              // If we have a previous table, add it
              if (currentTable.length > 0) {
                docDefinition.content.push({
                  table: {
                    headerRows: 1,
                    body: [currentHeaders, ...currentTable]
                  },
                  layout: {
                    fillColor: function(rowIndex) {
                      return rowIndex === 0 ? '#f3f6f9' : null;
                    }
                  }
                });
                docDefinition.content.push({ text: '\n\n' });
              }
              
              // Start new section
              currentSection = row[0];
              docDefinition.content.push({ text: currentSection, style: 'subheader' });
              currentTable = [];
              currentHeaders = [];
            } else if (row.length > 1) {
              if (currentHeaders.length === 0) {
                currentHeaders = row;
              } else {
                currentTable.push(row);
              }
            }
          });

          // Add the last table if exists
          if (currentTable.length > 0) {
            docDefinition.content.push({
              table: {
                headerRows: 1,
                body: [currentHeaders, ...currentTable]
              },
              layout: {
                fillColor: function(rowIndex) {
                  return rowIndex === 0 ? '#f3f6f9' : null;
                }
              }
            });
          }

              pdfMake.createPdf(docDefinition).download("patient_history.pdf");
          
          hideLoading(button, originalText);
          toastr.success('PDF file downloaded');
        }, 500);
      });

      // Print All button
      $('#btnPrintAll').on('click', function() {
        const button = $(this);
        const originalText = showLoading(button);

        setTimeout(() => {
          const allData = getAllTableData();
          const patientName = $("#patient option:selected").text();
          
          let printContent = `
            <html>
            <head>
              <title>Patient History - ${patientName}</title>
              <style>
                @media print {
                  @page { size: landscape; }
                }
                body { 
                  font-family: Arial, sans-serif;
                  padding: 20px;
                  color: #333;
                }
                .header {
                  text-align: center;
                  margin-bottom: 30px;
                }
                .header h1 { 
                  font-size: 24px;
                  margin-bottom: 10px;
                  color: #333;
                }
                .patient-info {
                  margin-bottom: 20px;
                  padding: 15px;
                  background: #f8f9fa;
                  border-radius: 5px;
                }
                .section {
                  margin-bottom: 30px;
                  page-break-inside: avoid;
                }
                .section h2 { 
                  font-size: 18px;
                  color: #3699FF;
                  margin: 20px 0 10px;
                  padding-bottom: 5px;
                  border-bottom: 2px solid #3699FF;
                }
                table {
                  width: 100%;
                  border-collapse: collapse;
                  margin-bottom: 20px;
                  font-size: 12px;
                }
                th, td {
                  border: 1px solid #ddd;
                  padding: 8px;
                  text-align: left;
                }
                th {
                  background-color: #f3f6f9;
                  font-weight: bold;
                }
                tr:nth-child(even) {
                  background-color: #f9f9f9;
                }
                .footer {
                  margin-top: 30px;
                  text-align: center;
                  font-size: 12px;
                  color: #666;
                }
                .no-data {
                  padding: 20px;
                  text-align: center;
                  color: #666;
                  font-style: italic;
                }
              </style>
            </head>
            <body>
              <div class="header">
                <h1>Patient History Report</h1>
              </div>
              <div class="patient-info">
                <strong>Patient Name:</strong> ${patientName}<br>
                <strong>Generated on:</strong> ${new Date().toLocaleString()}<br>
              </div>
          `;

          let currentSection = '';
          let currentTable = [];
          let currentHeaders = [];
          let hasData = false;

          allData.forEach((row, index) => {
            if (row.length === 1 && Object.values(tableNames).includes(row[0])) {
              // If we have a previous table, add it
              if (currentTable.length > 0) {
                printContent += `
                  <div class="section">
                    <h2>${currentSection}</h2>
                    <table>
                      <tr>${currentHeaders.map(h => `<th>${h}</th>`).join('')}</tr>
                      ${currentTable.map(r => `
                        <tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>
                      `).join('')}
                    </table>
                  </div>
                `;
                hasData = true;
              }
              
              // Start new section
              currentSection = row[0];
              currentTable = [];
              currentHeaders = [];
            } else if (row.length > 1) {
              if (currentHeaders.length === 0) {
                currentHeaders = row;
              } else {
                currentTable.push(row);
              }
            }
          });

          // Add the last table if exists
          if (currentTable.length > 0) {
            printContent += `
              <div class="section">
                <h2>${currentSection}</h2>
                <table>
                  <tr>${currentHeaders.map(h => `<th>${h}</th>`).join('')}</tr>
                  ${currentTable.map(r => `
                    <tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>
                  `).join('')}
                </table>
              </div>
            `;
            hasData = true;
          }

          // Add no data message if no records found
          if (!hasData) {
            printContent += `
              <div class="no-data">
                No records found for this patient.
              </div>
            `;
          }

          // Add footer
          printContent += `
              <div class="footer">
                <p>Mamatid Health Center System - Patient History Report</p>
                <p>Generated on: ${new Date().toLocaleString()}</p>
              </div>
            </body>
            </html>
          `;
            
          const printWindow = window.open('', '_blank');
          printWindow.document.write(printContent);
          printWindow.document.close();
          printWindow.focus();

          // Wait for resources to load before printing
          setTimeout(() => {
            printWindow.print();
            printWindow.close();
          }, 250);

          hideLoading(button, originalText);
          toastr.success('Print window opened');
        }, 500);
      });

      // Function to check missing records and show toast
      function checkMissingRecords(responses) {
        const tables = {
          'Family Planning': responses.family?.data?.length > 0,
          'Deworming': responses.deworming?.data?.length > 0,
          'BP Monitoring': responses.bp?.data?.length > 0,
          'Blood Sugar': responses.bloodSugar?.data?.length > 0,
          'Tetanus Toxoid': responses.tetanus?.data?.length > 0,
          'Family Members': responses.familyMembers?.data?.length > 0
        };

        // Get available and missing records
        const availableRecords = Object.entries(tables)
          .filter(([_, hasData]) => hasData)
          .map(([name, _]) => name);

        const missingRecords = Object.entries(tables)
          .filter(([_, hasData]) => !hasData)
          .map(([name, _]) => name);

        // Initialize SweetAlert2 Toast
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 5000,
          timerProgressBar: true,
          didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
          }
        });

        let title, html, icon;

        if (availableRecords.length === 0) {
          title = 'No Records Found';
          html = 'Patient has no existing records in the system.';
          icon = 'info';
        } else if (missingRecords.length === 0) {
          title = 'Complete Records';
          html = 'Patient has complete information in all categories.';
          icon = 'success';
        } else {
          title = 'Available Records';
          html = `
            <div style="text-align: left;">
              <p style="margin-bottom: 8px;">Patient has records in:</p>
              <ul style="list-style-type: none; padding-left: 0; margin-bottom: 12px;">
                ${availableRecords.map(record => `
                  <li><i class="fas fa-check-circle text-success"></i> ${record}</li>
                `).join('')}
              </ul>
              ${missingRecords.length > 0 ? `
                <p style="margin-bottom: 8px; color: #FFA800;">No records in:</p>
                <ul style="list-style-type: none; padding-left: 0; margin-bottom: 0;">
                  ${missingRecords.map(record => `
                    <li><i class="fas fa-exclamation-circle text-warning"></i> ${record}</li>
                  `).join('')}
                </ul>
              ` : ''}
            </div>
          `;
          icon = 'warning';
        }

        Toast.fire({
          icon: icon,
          title: title,
          html: html,
          customClass: {
            popup: 'swal2-toast-custom',
            title: 'swal2-toast-title-custom',
            htmlContainer: 'swal2-toast-html-custom'
          }
        });
      }

      // Add custom styles for the toast
      $('head').append(`
        <style>
          .swal2-toast-custom {
            background: #fff !important;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1) !important;
            padding: 15px !important;
            width: auto !important;
            max-width: 400px !important;
          }
          .swal2-toast-title-custom {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            color: #181C32 !important;
            margin-bottom: 8px !important;
          }
          .swal2-toast-html-custom {
            font-size: 0.95rem !important;
            color: #7E8299 !important;
          }
          .swal2-toast-html-custom ul {
            margin: 0 !important;
          }
          .swal2-toast-html-custom li {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin-bottom: 4px !important;
          }
          .swal2-toast-html-custom li:last-child {
            margin-bottom: 0 !important;
          }
          .swal2-toast-html-custom .fas {
            font-size: 14px !important;
          }
          .swal2-toast-html-custom .text-success {
            color: #1BC5BD !important;
          }
          .swal2-toast-html-custom .text-warning {
            color: #FFA800 !important;
          }
        </style>
      `);

      // Function to load patient history data
      function loadPatientHistory(patientName) {
        // Show loading state
        const loadingHtml = '<tr><td colspan="100%" class="text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</td></tr>';
        Object.values(tables).forEach(table => {
          table.clear().draw();
          $(table.table().body()).html(loadingHtml);
        });

        // Object to store all responses
        let responses = {};

        // Load Family Members History
        $.ajax({
          url: 'ajax/get_patient_family_members.php',
          type: 'GET',
          data: { patient_name: patientName },
          dataType: 'json',
          success: function(response) {
            responses.familyMembers = response;
            try {
              console.log('Family Members Response:', response);
            tables.familyMembers.clear();
              
              if (response.success && response.data && response.data.length > 0) {
              response.data.forEach(record => {
                tables.familyMembers.row.add([
                  record.sno,
                  record.name,
                    record.date,
                    record.created_at
                ]);
              });
              }
              
              tables.familyMembers.draw();
            } catch (e) {
              console.error('Error processing response:', e);
              tables.familyMembers.clear().draw();
            }
          },
          error: function(xhr, status, error) {
            responses.familyMembers = { success: false, data: [] };
            console.error('Error loading family members:', error);
            tables.familyMembers.clear().draw();
          }
        });

        // Load Family Planning History
        $.ajax({
          url: 'ajax/get_patient_family_planning.php',
          type: 'GET',
          data: { patient_name: patientName },
          dataType: 'json',
          success: function(response) {
            responses.family = response;
            tables.family.clear();
            if (response.success && response.data.length > 0) {
              response.data.forEach(record => {
            tables.family.row.add([
                  record.sno,
              record.name,
              record.date,
              record.age,
              record.address
            ]);
          });
        }
            tables.family.draw();
          },
          error: function() {
            responses.family = { success: false, data: [] };
            toastr.error('Error loading family planning history');
          }
        });

        // Load Deworming History
        $.ajax({
          url: 'ajax/get_patient_deworming.php',
          type: 'GET',
          data: { patient_name: patientName },
          dataType: 'json',
          success: function(response) {
            responses.deworming = response;
            tables.deworming.clear();
            if (response.success && response.data.length > 0) {
              response.data.forEach(record => {
            tables.deworming.row.add([
                  record.sno,
              record.name,
              record.date,
              record.age,
              record.birthday
            ]);
          });
        }
            tables.deworming.draw();
          },
          error: function() {
            responses.deworming = { success: false, data: [] };
            toastr.error('Error loading deworming history');
          }
        });

        // Load BP Monitoring History
        $.ajax({
          url: 'ajax/get_patient_bp.php',
          type: 'GET',
          data: { patient_name: patientName },
          dataType: 'json',
          success: function(response) {
            responses.bp = response;
            tables.bp.clear();
            if (response.success && response.data.length > 0) {
              response.data.forEach(record => {
            tables.bp.row.add([
                  record.sno,
              record.name,
              record.date,
              record.bp,
                  `<span class="badge badge-${record.alcohol === 'Yes' ? 'danger' : 'success'}">${record.alcohol}</span>`,
                  `<span class="badge badge-${record.smoke === 'Yes' ? 'danger' : 'success'}">${record.smoke}</span>`,
                  `<span class="badge badge-${record.obese === 'Yes' ? 'danger' : 'success'}">${record.obese}</span>`
            ]);
          });
        }
            tables.bp.draw();
          },
          error: function() {
            responses.bp = { success: false, data: [] };
            toastr.error('Error loading BP monitoring history');
          }
        });

        // Load Blood Sugar History
        $.ajax({
          url: 'ajax/get_patient_blood_sugar.php',
          type: 'GET',
          data: { patient_name: patientName },
          dataType: 'json',
          success: function(response) {
            responses.bloodSugar = response;
            tables.bloodSugar.clear();
            if (response.success && response.data.length > 0) {
              response.data.forEach(record => {
            tables.bloodSugar.row.add([
                  record.sno,
              record.name,
              record.date,
              record.age,
              record.result
            ]);
          });
        }
            tables.bloodSugar.draw();
          },
          error: function() {
            responses.bloodSugar = { success: false, data: [] };
            toastr.error('Error loading blood sugar history');
          }
        });

        // Load Tetanus History
        $.ajax({
          url: 'ajax/get_patient_tetanus.php',
          type: 'GET',
          data: { patient_name: patientName },
          dataType: 'json',
          success: function(response) {
            responses.tetanus = response;
            tables.tetanus.clear();
            if (response.success && response.data.length > 0) {
              response.data.forEach(record => {
            tables.tetanus.row.add([
                  record.sno,
              record.name,
              record.date,
              record.age,
              record.diagnosis,
              record.remarks
            ]);
          });
        }
            tables.tetanus.draw();
          },
          error: function() {
            responses.tetanus = { success: false, data: [] };
            toastr.error('Error loading tetanus history');
          },
          complete: function() {
            // After all requests are complete, check missing records
            setTimeout(() => checkMissingRecords(responses), 500);
          }
        });

        // Show export buttons after data is loaded
        $('#exportAllButtons').fadeIn();
      }

      // Search button click handler
      $("#search").click(function() {
        const button = $(this);
        const originalText = button.html();
        button.prop('disabled', true)
             .html('<i class="fas fa-spinner fa-spin"></i> Searching...');

        var patientName = $("#patient").val();
        if(patientName !== '') {
          loadPatientHistory(patientName);
          setTimeout(() => {
              button.prop('disabled', false).html(originalText);
          }, 500);
        } else {
          toastr.warning('Please select a patient');
          button.prop('disabled', false).html(originalText);
        }
      });

      // Initialize tooltips
      $('[data-toggle="tooltip"]').tooltip();

      // Initialize select2 for patient dropdown with modern styling
      $('#patient').select2({
        theme: 'bootstrap4',
        placeholder: 'Select a patient',
        allowClear: true,
        width: '100%'
      });

      // Add tab change animation
      $('.nav-tabs .nav-link').on('show.bs.tab', function(e) {
        $(this).addClass('fade-in');
      });

      // Add responsive handling for tables
      $(window).on('resize', function() {
        Object.values(tables).forEach(table => {
          table.columns.adjust().responsive.recalc();
        });
      });

      // Function to handle individual table exports
      function exportTable(tableKey, type) {
        const button = $(event.currentTarget);
        const originalText = button.html();
        button.prop('disabled', true)
             .html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');

        setTimeout(() => {
          const table = tables[tableKey];
          const patientName = $("#patient option:selected").text();
          const tableTitle = tableKey.charAt(0).toUpperCase() + tableKey.slice(1) + ' History';

          switch(type) {
            case 'copy':
              // Copy to clipboard
              const data = table.data().toArray();
              const headers = [];
              table.columns().every(function() {
                headers.push($(this.header()).text());
              });
              
              let copyText = `${tableTitle} - ${patientName}\n\n`;
              copyText += headers.join('\t') + '\n';
              copyText += data.map(row => row.join('\t')).join('\n');

              const temp = $("<textarea>");
              $("body").append(temp);
              temp.val(copyText).select();
              document.execCommand("copy");
              temp.remove();
              
              toastr.success(`${tableTitle} copied to clipboard`);
              break;

            case 'csv':
              // Export to CSV
              const csvData = table.data().toArray();
              const csvHeaders = [];
              table.columns().every(function() {
                csvHeaders.push($(this.header()).text());
              });
              
              let csvContent = `${tableTitle} - ${patientName}\n\n`;
              csvContent += csvHeaders.join(',') + '\n';
              csvContent += csvData.map(row => 
                row.map(cell => 
                  typeof cell === 'string' ? `"${cell.replace(/"/g, '""')}"` : cell
                ).join(',')
              ).join('\n');

              const csvBlob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
              const csvLink = document.createElement("a");
              const csvUrl = URL.createObjectURL(csvBlob);
              csvLink.setAttribute("href", csvUrl);
              csvLink.setAttribute("download", `${tableKey}_history.csv`);
              document.body.appendChild(csvLink);
              csvLink.click();
              document.body.removeChild(csvLink);
              
              toastr.success(`${tableTitle} exported to CSV`);
              break;

            case 'excel':
              // Export to Excel
              const excelData = table.data().toArray();
              const excelHeaders = [];
              table.columns().every(function() {
                excelHeaders.push($(this.header()).text());
              });
              
              const ws_data = [
                [`${tableTitle} - ${patientName}`],
                [],
                excelHeaders,
                ...excelData
              ];
              
              const wb = XLSX.utils.book_new();
              const ws = XLSX.utils.aoa_to_sheet(ws_data);
              XLSX.utils.book_append_sheet(wb, ws, tableTitle);
              XLSX.writeFile(wb, `${tableKey}_history.xlsx`);
              
              toastr.success(`${tableTitle} exported to Excel`);
              break;

            case 'pdf':
              // Export to PDF
              const pdfData = table.data().toArray();
              const pdfHeaders = [];
              table.columns().every(function() {
                pdfHeaders.push($(this.header()).text());
              });

              const docDefinition = {
                pageOrientation: 'landscape',
                content: [
                  { text: tableTitle, style: 'header' },
                  { text: patientName, style: 'subheader' },
                  { text: '\n' },
                  {
                    table: {
                      headerRows: 1,
                      body: [
                        pdfHeaders,
                        ...pdfData
                      ]
                    },
                    layout: {
                      fillColor: function(rowIndex) {
                        return rowIndex === 0 ? '#f3f6f9' : null;
                      }
                    }
                  }
                ],
                styles: {
                  header: {
                    fontSize: 18,
                    bold: true,
                    margin: [0, 0, 0, 10]
                  },
                  subheader: {
                    fontSize: 14,
                    bold: true,
                    margin: [0, 10, 0, 5]
                  }
                },
                defaultStyle: {
                  fontSize: 10
                }
              };

              pdfMake.createPdf(docDefinition).download(`${tableKey}_history.pdf`);
              toastr.success(`${tableTitle} exported to PDF`);
              break;

            case 'print':
              // Print table
              const printData = table.data().toArray();
              const printHeaders = [];
              table.columns().every(function() {
                printHeaders.push($(this.header()).text());
              });

              let printContent = `
                <html>
                <head>
                  <title>${tableTitle} - ${patientName}</title>
                  <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f3f6f9; }
                    h1 { font-size: 24px; margin-bottom: 10px; }
                    h2 { font-size: 18px; color: #3699FF; margin: 20px 0 10px; }
                  </style>
                </head>
                <body>
                  <h1>${tableTitle}</h1>
                  <h2>${patientName}</h2>
                  <table>
                    <tr>
                      ${printHeaders.map(h => `<th>${h}</th>`).join('')}
                    </tr>
                    ${printData.map(row => `
                      <tr>
                        ${row.map(cell => `<td>${cell}</td>`).join('')}
                      </tr>
                    `).join('')}
                  </table>
                </body>
                </html>
              `;

              const printWindow = window.open('', '_blank');
              printWindow.document.write(printContent);
              printWindow.document.close();
              printWindow.focus();
              printWindow.print();
              printWindow.close();
              
              toastr.success(`${tableTitle} print window opened`);
              break;
          }

          button.prop('disabled', false).html(originalText);
        }, 500);
      }
    });
  </script>
</body>
</html>
