<?php
// Include the database connection and common functions
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';
// Retrieve patients list as HTML <option> elements using the getAllPatientsWithHistory() function
$patients = getAllPatientsWithHistory($con);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <?php include './config/data_tables_css.php'; ?>
  <!-- Logo for the tab bar -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
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
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .export-btn {
      display: inline-flex;
      align-items: center;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 500;
      font-size: 0.9rem;
      color: #fff;
      border: none;
      cursor: pointer;
      transition: var(--transition);
      gap: 8px;
    }

    .btn-copy { background: var(--primary-gradient); }
    .btn-csv { background: var(--success-gradient); }
    .btn-excel { background: var(--warning-gradient); }
    .btn-pdf { background: var(--danger-gradient); }
    .btn-print { background: var(--info-gradient); }

    .export-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .export-btn i {
      font-size: 1rem;
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
  </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar and Sidebar -->
    <?php 
      include './config/header.php';
      include './config/sidebar.php';
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
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Search Patient History</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <!-- Search Form -->
              <div class="row search-form">
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <select id="patient" class="form-control">
                    <?php echo $patients; ?>
                  </select>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-4 col-xs-12">
                  <button type="button" id="search" class="btn btn-primary btn-block">
                    <i class="fas fa-search mr-2"></i>Search
                  </button>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <div class="chart-actions" id="exportAllButtons" style="display: none;">
                    <button class="btn export-btn" id="btnCopy">
                      <i class="fas fa-copy"></i> Copy All
                    </button>
                    <button class="btn export-btn" id="btnCSV">
                      <i class="fas fa-file-csv"></i> CSV All
                    </button>
                    <button class="btn export-btn" id="btnExcel">
                      <i class="fas fa-file-excel"></i> Excel All
                    </button>
                    <button class="btn export-btn" id="btnPDF">
                      <i class="fas fa-file-pdf"></i> PDF All
                    </button>
                    <button class="btn export-btn" id="btnPrint">
                      <i class="fas fa-print"></i> Print All
                    </button>
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
                        <div class="export-buttons">
                          <button class="export-btn btn-copy" onclick="exportTable('family', 'copy')">
                            <i class="fas fa-copy"></i> Copy
                          </button>
                          <button class="export-btn btn-csv" onclick="exportTable('family', 'csv')">
                            <i class="fas fa-file-csv"></i> CSV
                          </button>
                          <button class="export-btn btn-excel" onclick="exportTable('family', 'excel')">
                            <i class="fas fa-file-excel"></i> Excel
                          </button>
                          <button class="export-btn btn-pdf" onclick="exportTable('family', 'pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                          </button>
                          <button class="export-btn btn-print" onclick="exportTable('family', 'print')">
                            <i class="fas fa-print"></i> Print
                          </button>
                        </div>
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
                        <div class="export-buttons">
                          <button class="export-btn btn-copy" onclick="exportTable('deworming', 'copy')">
                            <i class="fas fa-copy"></i> Copy
                          </button>
                          <button class="export-btn btn-csv" onclick="exportTable('deworming', 'csv')">
                            <i class="fas fa-file-csv"></i> CSV
                          </button>
                          <button class="export-btn btn-excel" onclick="exportTable('deworming', 'excel')">
                            <i class="fas fa-file-excel"></i> Excel
                          </button>
                          <button class="export-btn btn-pdf" onclick="exportTable('deworming', 'pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                          </button>
                          <button class="export-btn btn-print" onclick="exportTable('deworming', 'print')">
                            <i class="fas fa-print"></i> Print
                          </button>
                        </div>
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
                        <div class="export-buttons">
                          <button class="export-btn btn-copy" onclick="exportTable('bp', 'copy')">
                            <i class="fas fa-copy"></i> Copy
                          </button>
                          <button class="export-btn btn-csv" onclick="exportTable('bp', 'csv')">
                            <i class="fas fa-file-csv"></i> CSV
                          </button>
                          <button class="export-btn btn-excel" onclick="exportTable('bp', 'excel')">
                            <i class="fas fa-file-excel"></i> Excel
                          </button>
                          <button class="export-btn btn-pdf" onclick="exportTable('bp', 'pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                          </button>
                          <button class="export-btn btn-print" onclick="exportTable('bp', 'print')">
                            <i class="fas fa-print"></i> Print
                          </button>
                        </div>
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
                        <div class="export-buttons">
                          <button class="export-btn btn-copy" onclick="exportTable('bloodSugar', 'copy')">
                            <i class="fas fa-copy"></i> Copy
                          </button>
                          <button class="export-btn btn-csv" onclick="exportTable('bloodSugar', 'csv')">
                            <i class="fas fa-file-csv"></i> CSV
                          </button>
                          <button class="export-btn btn-excel" onclick="exportTable('bloodSugar', 'excel')">
                            <i class="fas fa-file-excel"></i> Excel
                          </button>
                          <button class="export-btn btn-pdf" onclick="exportTable('bloodSugar', 'pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                          </button>
                          <button class="export-btn btn-print" onclick="exportTable('bloodSugar', 'print')">
                            <i class="fas fa-print"></i> Print
                          </button>
                        </div>
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
                        <div class="export-buttons">
                          <button class="export-btn btn-copy" onclick="exportTable('tetanus', 'copy')">
                            <i class="fas fa-copy"></i> Copy
                          </button>
                          <button class="export-btn btn-csv" onclick="exportTable('tetanus', 'csv')">
                            <i class="fas fa-file-csv"></i> CSV
                          </button>
                          <button class="export-btn btn-excel" onclick="exportTable('tetanus', 'excel')">
                            <i class="fas fa-file-excel"></i> Excel
                          </button>
                          <button class="export-btn btn-pdf" onclick="exportTable('tetanus', 'pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                          </button>
                          <button class="export-btn btn-print" onclick="exportTable('tetanus', 'print')">
                            <i class="fas fa-print"></i> Print
                          </button>
                        </div>
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
                        <div class="export-buttons">
                          <button class="export-btn btn-copy" onclick="exportTable('familyMembers', 'copy')">
                            <i class="fas fa-copy"></i> Copy
                          </button>
                          <button class="export-btn btn-csv" onclick="exportTable('familyMembers', 'csv')">
                            <i class="fas fa-file-csv"></i> CSV
                          </button>
                          <button class="export-btn btn-excel" onclick="exportTable('familyMembers', 'excel')">
                            <i class="fas fa-file-excel"></i> Excel
                          </button>
                          <button class="export-btn btn-pdf" onclick="exportTable('familyMembers', 'pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                          </button>
                          <button class="export-btn btn-print" onclick="exportTable('familyMembers', 'print')">
                            <i class="fas fa-print"></i> Print
                          </button>
                        </div>
                        <div class="table-responsive">
                          <table id="family_members_history" class="table table-hover">
                            <thead>
                              <tr>
                                <th>S.No</th>
                                <th>Name</th>
                                <th>Date</th>
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

    <?php include './config/footer.php'; ?>
    <!-- /.control-sidebar -->
  </div>
  <!-- ./wrapper -->

  <?php include './config/site_js_links.php'; ?>
  <?php include './config/data_tables_js.php'; ?>

  <script>
    $(document).ready(function() {
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
            emptyTable: "No history available"
          }
        })
      };

      // Function to combine all table data
      function getAllTableData() {
        var allData = [];
        var patientName = $("#patient option:selected").text();
        
        // Add title
        allData.push(['Patient History Report for: ' + patientName]);
        allData.push([]);

        // Add data from each table
        Object.entries(tables).forEach(([key, table]) => {
          var data = table.data().toArray();
          if (data.length > 0) {
            // Add section title
            allData.push([key.toUpperCase() + ' HISTORY']);
            
            // Add headers
            var headers = [];
            table.columns().every(function() {
              headers.push($(this.header()).text());
            });
            allData.push(headers);
            
            // Add data rows
            data.forEach(row => {
              allData.push(row);
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
        button.prop('disabled', true)
             .html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
      }

      // Function to hide loading state
      function hideLoading(button, icon, text) {
        button.prop('disabled', false)
             .html(`<i class="fas ${icon} mr-2"></i>${text}`);
      }

      // Copy All button
      $('#btnCopy').on('click', function() {
        const button = $(this);
        showLoading(button);

        setTimeout(() => {
          var allData = getAllTableData();
          var textData = allData.map(row => row.join('\t')).join('\n');
          
          var temp = $("<textarea>");
          $("body").append(temp);
          temp.val(textData).select();
          document.execCommand("copy");
          temp.remove();
          
          hideLoading(button, 'fa-copy', 'Copy All');
          toastr.success('All history data copied to clipboard');
        }, 500);
      });

      // CSV All button
      $('#btnCSV').on('click', function() {
        const button = $(this);
        showLoading(button);

        setTimeout(() => {
          var allData = getAllTableData();
          var csvContent = allData.map(row => 
            row.map(cell => 
              typeof cell === 'string' ? `"${cell.replace(/"/g, '""')}"` : cell
            ).join(',')
          ).join('\n');

              var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
              var link = document.createElement("a");
                var url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", "patient_history.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
          
          hideLoading(button, 'fa-file-csv', 'CSV All');
          toastr.success('Successfully exported to CSV');
        }, 500);
      });

      // Excel All button
      $('#btnExcel').on('click', function() {
        const button = $(this);
        showLoading(button);

        setTimeout(() => {
          var allData = getAllTableData();
              var wb = XLSX.utils.book_new();
          var ws = XLSX.utils.aoa_to_sheet(allData);
              XLSX.utils.book_append_sheet(wb, ws, "Patient History");
              XLSX.writeFile(wb, "patient_history.xlsx");
          
          hideLoading(button, 'fa-file-excel', 'Excel All');
          toastr.success('Successfully exported to Excel');
        }, 500);
      });

      // PDF All button
      $('#btnPDF').on('click', function() {
        const button = $(this);
        showLoading(button);

        setTimeout(() => {
          var allData = getAllTableData();
          var patientName = $("#patient option:selected").text();
          
              var docDefinition = {
            pageOrientation: 'landscape',
                content: [
                  { text: 'Patient History Report', style: 'header' },
              { text: patientName, style: 'subheader' },
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
          var currentSection = '';
          var currentTable = [];
          var currentHeaders = [];

          allData.forEach((row, index) => {
            if (row.length === 1 && row[0].includes('HISTORY')) {
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
          
          hideLoading(button, 'fa-file-pdf', 'PDF All');
          toastr.success('Successfully exported to PDF');
        }, 500);
      });

      // Print All button
      $('#btnPrint').on('click', function() {
        const button = $(this);
        showLoading(button);

        setTimeout(() => {
          var allData = getAllTableData();
          var patientName = $("#patient option:selected").text();
          
          var printContent = `
            <html>
            <head>
              <title>Patient History - ${patientName}</title>
              <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f3f6f9; }
                h1 { font-size: 24px; margin-bottom: 10px; }
                h2 { font-size: 18px; color: #3699FF; margin: 20px 0 10px; }
                .section-break { height: 20px; }
              </style>
            </head>
            <body>
              <h1>Patient History Report</h1>
              <h2>${patientName}</h2>
          `;

          var currentSection = '';
          var currentTable = [];
          var currentHeaders = [];

          allData.forEach((row, index) => {
            if (row.length === 1) {
              if (row[0].includes('HISTORY')) {
                // If we have a previous table, add it
                if (currentTable.length > 0) {
                  printContent += '<table>';
                  printContent += '<tr>' + currentHeaders.map(h => `<th>${h}</th>`).join('') + '</tr>';
                  currentTable.forEach(r => {
                    printContent += '<tr>' + r.map(c => `<td>${c}</td>`).join('') + '</tr>';
                  });
                  printContent += '</table>';
                  printContent += '<div class="section-break"></div>';
                }
                
                // Start new section
                currentSection = row[0];
                printContent += `<h2>${currentSection}</h2>`;
                currentTable = [];
                currentHeaders = [];
              }
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
            printContent += '<table>';
            printContent += '<tr>' + currentHeaders.map(h => `<th>${h}</th>`).join('') + '</tr>';
            currentTable.forEach(r => {
              printContent += '<tr>' + r.map(c => `<td>${c}</td>`).join('') + '</tr>';
            });
            printContent += '</table>';
          }
              
              printContent += '</body></html>';
              
              var printWindow = window.open('', '_blank');
              printWindow.document.write(printContent);
              printWindow.document.close();
              printWindow.focus();
              printWindow.print();
              printWindow.close();

          hideLoading(button, 'fa-print', 'Print All');
          toastr.success('Print window opened');
        }, 500);
      });

      // Function to load patient history data
      function loadPatientHistory(patientName) {
        // Show loading state
        const loadingHtml = '<tr><td colspan="100%" class="text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</td></tr>';
        Object.values(tables).forEach(table => {
          table.clear().draw();
          $(table.table().body()).html(loadingHtml);
        });

        // Load Family Members History
        $.ajax({
          url: 'ajax/get_patient_family_members.php',
          type: 'GET',
          data: { patient_name: patientName },
          dataType: 'json',
          success: function(response) {
            console.log('Family Members Response:', response); // Debug log
            tables.familyMembers.clear();
            if (response.success && response.data.length > 0) {
              response.data.forEach(record => {
                tables.familyMembers.row.add([
                  record.sno,
                  record.name,
                  record.date
                ]);
              });
              tables.familyMembers.draw();
              console.log('Family Members Data loaded:', response.data.length, 'records');
            } else {
              // Show "No data" message
              tables.familyMembers.clear().draw();
              console.log('No family members data found');
            }
          },
          error: function(xhr, status, error) {
            console.error('Error loading family members:', error, xhr.responseText);
            toastr.error('Error loading family members history');
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
            toastr.error('Error loading tetanus history');
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
