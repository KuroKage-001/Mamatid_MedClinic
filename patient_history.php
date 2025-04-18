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
    }

    .card-body {
      padding: 1.5rem;
    }

    /* Search Form Styling */
    .search-form {
      margin-bottom: 2rem;
    }

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

    .btn-primary {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      border: none;
      border-radius: 8px;
      padding: 0.625rem 1.25rem;
      font-weight: 500;
      transition: all var(--transition-speed);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(54, 153, 255, 0.4);
    }

    /* Export Button Styling */
    .btn-info {
      background: linear-gradient(135deg, var(--info-color) 0%, #A37FFC 100%);
      border: none;
      border-radius: 8px;
      padding: 0.625rem 1.25rem;
      font-weight: 500;
      color: white;
    }

    .dropdown-menu {
      border: none;
      box-shadow: 0 0 50px 0 rgba(82, 63, 105, 0.15);
      border-radius: 10px;
      padding: 0.5rem 0;
    }

    .dropdown-item {
      padding: 0.75rem 1.25rem;
      font-size: 0.95rem;
      transition: all var(--transition-speed);
    }

    .dropdown-item:hover {
      background: var(--light-color);
      color: var(--primary-color);
    }

    /* Tabs Styling */
    .nav-tabs {
      border-bottom: 2px solid #eee;
      margin-bottom: 1.5rem;
    }

    .nav-tabs .nav-item {
      margin-bottom: -2px;
    }

    .nav-tabs .nav-link {
      border: none;
      border-bottom: 2px solid transparent;
      padding: 1rem 1.5rem;
      font-weight: 500;
      color: #6c757d;
      transition: all var(--transition-speed);
    }

    .nav-tabs .nav-link:hover {
      border-color: transparent;
      color: var(--primary-color);
    }

    .nav-tabs .nav-link.active {
      color: var(--primary-color);
      border-bottom: 2px solid var(--primary-color);
      background: transparent;
    }

    /* DataTable Styling */
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

    /* DataTable Controls */
    .dataTables_wrapper .dataTables_length select {
      border-radius: 6px;
      border: 2px solid #e4e6ef;
      padding: 0.25rem 1.5rem 0.25rem 0.5rem;
    }

    .dataTables_wrapper .dataTables_filter input {
      border-radius: 6px;
      border: 2px solid #e4e6ef;
      padding: 0.25rem 0.5rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
      border-radius: 6px;
      padding: 0.5rem 1rem;
      margin: 0 0.2rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--primary-color);
      border-color: var(--primary-color);
      color: white !important;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .card-header {
        padding: 1rem;
      }

      .card-body {
        padding: 1rem;
      }

      .nav-tabs .nav-link {
        padding: 0.75rem 1rem;
      }

      .table thead th,
      .table tbody td {
        padding: 0.75rem;
      }
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
                  <div class="btn-group" id="exportAllButtons" style="display: none;">
                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                      <i class="fas fa-download mr-2"></i>Download All History
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="#" id="copyAll">
                        <i class="fas fa-copy mr-2"></i>Copy All
                      </a>
                      <a class="dropdown-item" href="#" id="csvAll">
                        <i class="fas fa-file-csv mr-2"></i>Export All to CSV
                      </a>
                      <a class="dropdown-item" href="#" id="excelAll">
                        <i class="fas fa-file-excel mr-2"></i>Export All to Excel
                      </a>
                      <a class="dropdown-item" href="#" id="pdfAll">
                        <i class="fas fa-file-pdf mr-2"></i>Export All to PDF
                      </a>
                      <a class="dropdown-item" href="#" id="printAll">
                        <i class="fas fa-print mr-2"></i>Print All
                      </a>
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
                  </ul>

                  <div class="tab-content" id="historyTabsContent">
                    <!-- Family Planning History -->
                    <div class="tab-pane fade show active" id="family" role="tabpanel">
                      <div class="table-responsive">
                        <table id="family_history" class="table table-striped table-hover">
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

                    <!-- Deworming History -->
                    <div class="tab-pane fade" id="deworming" role="tabpanel">
                      <div class="table-responsive">
                        <table id="deworming_history" class="table table-striped table-hover">
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

                    <!-- BP Monitoring History -->
                    <div class="tab-pane fade" id="bp" role="tabpanel">
                      <div class="table-responsive">
                        <table id="bp_history" class="table table-striped table-hover">
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

                    <!-- Blood Sugar History -->
                    <div class="tab-pane fade" id="blood-sugar" role="tabpanel">
                      <div class="table-responsive">
                        <table id="blood_sugar_history" class="table table-striped table-hover">
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

                    <!-- Tetanus Toxoid History -->
                    <div class="tab-pane fade" id="tetanus" role="tabpanel">
                      <div class="table-responsive">
                        <table id="tetanus_history" class="table table-striped table-hover">
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
          buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
          language: {
            search: "",
            searchPlaceholder: "Search records..."
          },
          dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        }),
        deworming: $('#deworming_history').DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
          language: {
            search: "",
            searchPlaceholder: "Search records..."
          }
        }),
        bp: $('#bp_history').DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
          language: {
            search: "",
            searchPlaceholder: "Search records..."
          }
        }),
        bloodSugar: $('#blood_sugar_history').DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
          language: {
            search: "",
            searchPlaceholder: "Search records..."
          }
        }),
        tetanus: $('#tetanus_history').DataTable({
          responsive: true,
          lengthChange: false,
          autoWidth: false,
          buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
          language: {
            search: "",
            searchPlaceholder: "Search records..."
          }
        })
      };

      // Append buttons container for each table
      Object.keys(tables).forEach(key => {
        tables[key].buttons()
          .container()
          .appendTo(`#${tables[key].table().node().id}_wrapper .col-md-6:eq(0)`);
      });

      // Function to combine all table data
      function getAllTableData() {
        var allData = {};
        var patientName = $("#patient option:selected").text();
        
        Object.keys(tables).forEach(key => {
          allData[key] = tables[key].data().toArray();
        });
        
        return {
          patientName: patientName,
          data: allData
        };
      }

      // Function to format data for export
      function formatDataForExport(allData) {
        var exportData = [];
        var patientName = allData.patientName;
        
        exportData.push(['Patient History for: ' + patientName]);
        exportData.push([]);
        
        Object.keys(allData.data).forEach(tableKey => {
          var tableData = allData.data[tableKey];
          if (tableData.length > 0) {
            exportData.push([tableKey.toUpperCase() + ' HISTORY']);
            
            var headers;
            switch(tableKey) {
              case 'family':
                headers = ['S.No', 'Name', 'Date', 'Age', 'Address'];
                break;
              case 'deworming':
                headers = ['S.No', 'Name', 'Date', 'Age', 'Birthday'];
                break;
              case 'bp':
                headers = ['S.No', 'Name', 'Date', 'BP Reading', 'Alcohol', 'Smoke', 'Obese'];
                break;
              case 'bloodSugar':
                headers = ['S.No', 'Name', 'Date', 'Age', 'Result'];
                break;
              case 'tetanus':
                headers = ['S.No', 'Name', 'Date', 'Age', 'Diagnosis', 'Remarks'];
                break;
            }
            
            exportData.push(headers);
            tableData.forEach(row => {
              exportData.push(row);
            });
            
            exportData.push([]);
            exportData.push([]);
          }
        });
        
        return exportData;
      }

      // Handle export buttons with loading states
      function showLoading(button) {
        const originalText = button.html();
        button.prop('disabled', true)
             .html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
        return originalText;
      }

      function hideLoading(button, originalText) {
        button.prop('disabled', false).html(originalText);
      }

      $('#copyAll').click(function(e) {
        e.preventDefault();
        const button = $(this);
        const originalText = showLoading(button);

        setTimeout(() => {
          var allData = getAllTableData();
          var formattedData = formatDataForExport(allData);
          var textData = formattedData.map(row => row.join('\t')).join('\n');
          
          var temp = $("<textarea>");
          $("body").append(temp);
          temp.val(textData).select();
          document.execCommand("copy");
          temp.remove();
          
          hideLoading(button, originalText);
          toastr.success('All history data copied to clipboard');
        }, 500);
      });

      $('#csvAll, #excelAll, #pdfAll, #printAll').click(function(e) {
        e.preventDefault();
        const button = $(this);
        const originalText = showLoading(button);
        const action = this.id.replace('All', '');

        setTimeout(() => {
          var allData = getAllTableData();
          var formattedData = formatDataForExport(allData);

          switch(action) {
            case 'csv':
              var csvContent = formattedData.map(row => row.join(',')).join('\n');
              var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
              var link = document.createElement("a");
              if (link.download !== undefined) {
                var url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", "patient_history.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
              }
              break;

            case 'excel':
              var wb = XLSX.utils.book_new();
              var ws = XLSX.utils.aoa_to_sheet(formattedData);
              XLSX.utils.book_append_sheet(wb, ws, "Patient History");
              XLSX.writeFile(wb, "patient_history.xlsx");
              break;

            case 'pdf':
              var docDefinition = {
                content: [
                  { text: 'Patient History Report', style: 'header' },
                  { text: allData.patientName, style: 'subheader' },
                  { text: '\n' },
                  {
                    table: {
                      headerRows: 1,
                      body: formattedData
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
                }
              };
              pdfMake.createPdf(docDefinition).download("patient_history.pdf");
              break;

            case 'print':
              var printContent = '<html><head><title>Patient History</title>';
              printContent += '<style>table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid black; padding: 8px; text-align: left; }</style>';
              printContent += '</head><body>';
              printContent += '<h1>Patient History Report</h1>';
              printContent += '<h2>' + allData.patientName + '</h2>';
              
              Object.keys(allData.data).forEach(tableKey => {
                if (allData.data[tableKey].length > 0) {
                  printContent += '<h3>' + tableKey.toUpperCase() + ' HISTORY</h3>';
                  printContent += '<table>';
                  formattedData.forEach(row => {
                    printContent += '<tr><td>' + row.join('</td><td>') + '</td></tr>';
                  });
                  printContent += '</table><br><br>';
                }
              });
              
              printContent += '</body></html>';
              
              var printWindow = window.open('', '_blank');
              printWindow.document.write(printContent);
              printWindow.document.close();
              printWindow.focus();
              printWindow.print();
              printWindow.close();
              break;
          }

          hideLoading(button, originalText);
          toastr.success(`Successfully ${action}ed patient history`);
        }, 500);
      });

      // Function to populate tables with animation
      function populateTables(data) {
        $('#exportAllButtons').fadeIn();
        
        Object.values(tables).forEach(table => table.clear());
        
        if (data.family && data.family.length > 0) {
          data.family.forEach((record, index) => {
            tables.family.row.add([
              index + 1,
              record.name,
              record.date,
              record.age,
              record.address
            ]);
          });
        }
        
        if (data.deworming && data.deworming.length > 0) {
          data.deworming.forEach((record, index) => {
            tables.deworming.row.add([
              index + 1,
              record.name,
              record.date,
              record.age,
              record.birthday
            ]);
          });
        }
        
        if (data.bp && data.bp.length > 0) {
          data.bp.forEach((record, index) => {
            tables.bp.row.add([
              index + 1,
              record.name,
              record.date,
              record.bp,
              record.alcohol ? '<span class="badge badge-danger">Yes</span>' : '<span class="badge badge-success">No</span>',
              record.smoke ? '<span class="badge badge-danger">Yes</span>' : '<span class="badge badge-success">No</span>',
              record.obese ? '<span class="badge badge-danger">Yes</span>' : '<span class="badge badge-success">No</span>'
            ]);
          });
        }
        
        if (data.blood_sugar && data.blood_sugar.length > 0) {
          data.blood_sugar.forEach((record, index) => {
            tables.bloodSugar.row.add([
              index + 1,
              record.name,
              record.date,
              record.age,
              record.result
            ]);
          });
        }
        
        if (data.tetanus && data.tetanus.length > 0) {
          data.tetanus.forEach((record, index) => {
            tables.tetanus.row.add([
              index + 1,
              record.name,
              record.date,
              record.age,
              record.diagnosis,
              record.remarks
            ]);
          });
        }
        
        Object.values(tables).forEach(table => {
          table.draw();
          $(table.table().node()).parent().fadeIn();
        });
      }

      // Search button click handler with loading state
      $("#search").click(function() {
        const button = $(this);
        const originalText = button.html();
        button.prop('disabled', true)
             .html('<i class="fas fa-spinner fa-spin"></i>');

        var patientName = $("#patient").val();
        if(patientName !== '') {
          $.ajax({
            url: "ajax/get_patient_history.php",
            type: 'GET',
            data: { patient_name: patientName },
            dataType: 'json',
            success: function(response) {
              if(response.success) {
                populateTables(response.data);
                toastr.success('Patient history loaded successfully');
              } else {
                toastr.error('Error: ' + response.message);
              }
            },
            error: function(xhr, status, error) {
              toastr.error('An error occurred while fetching patient history');
            },
            complete: function() {
              button.prop('disabled', false).html(originalText);
            }
          });
        } else {
          toastr.warning('Please select a patient');
          button.prop('disabled', false).html(originalText);
        }
      });

      // Initialize tooltips
      $('[data-toggle="tooltip"]').tooltip();

      // Initialize select2 for patient dropdown
      $('#patient').select2({
        theme: 'bootstrap4',
        placeholder: 'Select a patient',
        allowClear: true
      });
    });
  </script>
</body>
</html>
