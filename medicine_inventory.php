<?php
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';

// Handle form submission for adding new inventory
if(isset($_POST['add_inventory'])) {
    $medicineDetailId = $_POST['medicine_detail'];
    $quantity = $_POST['quantity'];
    $batchNumber = $_POST['batch_number'];
    $expiryDate = $_POST['expiry_date'];
    $unitPrice = $_POST['unit_price'];

    try {
        $con->beginTransaction();

        // Insert into inventory
        $query = "INSERT INTO medicine_inventory 
                 (medicine_details_id, quantity, batch_number, expiry_date, unit_price)
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($query);
        $stmt->execute([$medicineDetailId, $quantity, $batchNumber, $expiryDate, $unitPrice]);

        // Get the last inserted inventory ID
        $inventoryId = $con->lastInsertId();

        // Record the IN transaction
        $query = "INSERT INTO medicine_inventory_transactions 
                 (medicine_inventory_id, transaction_type, quantity, notes)
                 VALUES (?, 'IN', ?, 'Initial stock entry')";
        $stmt = $con->prepare($query);
        $stmt->execute([$inventoryId, $quantity]);

        $con->commit();
        $message = 'Inventory added successfully.';
    } catch(PDOException $ex) {
        $con->rollback();
        echo $ex->getMessage();
        echo $ex->getTraceAsString();
        exit;
    }
}

// Get list of medicines with details
$query = "SELECT md.id, CONCAT(m.medicine_name, ' (', md.packing, ')') as medicine
         FROM medicine_details md
         JOIN medicines m ON md.medicine_id = m.id
         ORDER BY m.medicine_name";
$stmt = $con->prepare($query);
$stmt->execute();
$medicineDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current inventory status
$query = "SELECT * FROM medicine_stock_view ORDER BY stock_status ASC, medicine_name ASC";
$stmt = $con->prepare($query);
$stmt->execute();
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get medicine movement analytics
$query = "SELECT * FROM medicine_movement_view ORDER BY total_quantity_out DESC LIMIT 10";
$stmt = $con->prepare($query);
$stmt->execute();
$movement = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php';?>
    <?php include './config/data_tables_css.php';?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title>Medicine Inventory - Mamatid Health Center System</title>
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

        /* Stock Status Styling */
        .stock-low {
            background-color: rgba(246, 78, 96, 0.1) !important;
            color: var(--danger-color);
            font-weight: 500;
        }

        .stock-medium {
            background-color: rgba(255, 168, 0, 0.1) !important;
            color: var(--warning-color);
            font-weight: 500;
        }

        .stock-good {
            background-color: rgba(27, 197, 189, 0.1) !important;
            color: var(--success-color);
            font-weight: 500;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
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

        /* Select2 Styling */
        .select2-container--default .select2-selection--single {
            height: calc(2.5rem + 2px);
            border: 2px solid #e4e6ef;
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.5rem + 2px);
            padding-left: 1rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(2.5rem + 2px);
        }

        /* Toast Styling */
        .swal2-toast {
            background: white !important;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1) !important;
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
        <?php include './config/header.php';
        include './config/sidebar.php';?>
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6" style="padding-left: 20px;">
                            <h1>Medicine Inventory</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <!-- Add Inventory Card -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Add New Inventory</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="form-group">
                                            <label class="form-label">Select Medicine</label>
                                            <select name="medicine_detail" class="form-control select2" required>
                                                <option value="">Select Medicine</option>
                                                <?php foreach($medicineDetails as $md): ?>
                                                    <option value="<?php echo $md['id']; ?>"><?php echo $md['medicine']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <div class="form-group">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" name="quantity" class="form-control" required min="1" 
                                                   placeholder="Enter quantity">
                                        </div>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <div class="form-group">
                                            <label class="form-label">Batch Number</label>
                                            <input type="text" name="batch_number" class="form-control" required 
                                                   placeholder="Enter batch no.">
                                        </div>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <div class="form-group">
                                            <label class="form-label">Expiry Date</label>
                                            <input type="date" name="expiry_date" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <div class="form-group">
                                            <label class="form-label">Unit Price</label>
                                            <input type="number" step="0.01" name="unit_price" class="form-control" required 
                                                   placeholder="Enter price">
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <label class="form-label d-none d-md-block">&nbsp;</label>
                                            <button type="submit" name="add_inventory" class="btn btn-primary">
                                                <i class="fas fa-plus-circle mr-2"></i>Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Current Inventory Card -->
                        <div class="col-md-8">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Current Inventory</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="inventory_table" class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Medicine</th>
                                                    <th>Packing</th>
                                                    <th>Batch</th>
                                                    <th>Stock</th>
                                                    <th>Expiry</th>
                                                    <th>Status</th>
                                                    <th>Days to Expiry</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($inventory as $item): ?>
                                                    <tr class="stock-<?php echo strtolower($item['stock_status']); ?>">
                                                        <td><?php echo $item['medicine_name']; ?></td>
                                                        <td><?php echo $item['packing']; ?></td>
                                                        <td><?php echo $item['batch_number']; ?></td>
                                                        <td><?php echo $item['current_stock']; ?></td>
                                                        <td><?php echo $item['expiry_date']; ?></td>
                                                        <td><?php echo $item['stock_status']; ?></td>
                                                        <td><?php echo $item['days_until_expiry']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Medicine Movement Analytics Card -->
                        <div class="col-md-4">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Fast Moving Medicines</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="medicineMovementChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <?php include './config/footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>
    <?php include './config/data_tables_js.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            // Initialize DataTable
            $("#inventory_table").DataTable({
                responsive: true,
                lengthChange: false,
                autoWidth: false,
                buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
                language: {
                    search: "",
                    searchPlaceholder: "Search inventory..."
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            }).buttons().container().appendTo('#inventory_table_wrapper .col-md-6:eq(0)');

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

            // Prepare data for the chart
            var medicineNames = <?php echo json_encode(array_column($movement, 'medicine_name')); ?>;
            var quantities = <?php echo json_encode(array_column($movement, 'total_quantity_out')); ?>;

            // Create the bar chart with modern styling
            var ctx = document.getElementById('medicineMovementChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: medicineNames,
                    datasets: [{
                        label: 'Total Units Dispensed',
                        data: quantities,
                        backgroundColor: 'rgba(54, 153, 255, 0.2)',
                        borderColor: 'rgba(54, 153, 255, 1)',
                        borderWidth: 2,
                        borderRadius: 5,
                        barThickness: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#1a1a2d',
                            bodyColor: '#1a1a2d',
                            borderColor: '#e4e6ef',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false
                        }
                    }
                }
            });
        });

        // Highlight current menu
        showMenuSelected("#mnu_medicines", "#mi_medicine_inventory");
    </script>
</body>
</html> 