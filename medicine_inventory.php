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
        .stock-low { background-color: #ffcdd2 !important; }
        .stock-medium { background-color: #fff9c4 !important; }
        .stock-good { background-color: #c8e6c9 !important; }
    </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/header.php';
        include './config/sidebar.php';?>
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Medicine Inventory</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <!-- Add Inventory Card -->
                <div class="card card-outline card-primary rounded-0 shadow">
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
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Select Medicine</label>
                                        <select name="medicine_detail" class="form-control form-control-sm rounded-0" required>
                                            <option value="">Select Medicine</option>
                                            <?php foreach($medicineDetails as $md): ?>
                                                <option value="<?php echo $md['id']; ?>"><?php echo $md['medicine']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Quantity</label>
                                        <input type="number" name="quantity" class="form-control form-control-sm rounded-0" required min="1">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Batch Number</label>
                                        <input type="text" name="batch_number" class="form-control form-control-sm rounded-0" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Expiry Date</label>
                                        <input type="date" name="expiry_date" class="form-control form-control-sm rounded-0" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Unit Price</label>
                                        <input type="number" step="0.01" name="unit_price" class="form-control form-control-sm rounded-0" required>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" name="add_inventory" class="btn btn-primary btn-sm btn-flat btn-block">Add</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <!-- Current Inventory Card -->
                    <div class="col-md-8">
                        <div class="card card-outline card-primary rounded-0 shadow">
                            <div class="card-header">
                                <h3 class="card-title">Current Inventory</h3>
                            </div>
                            <div class="card-body">
                                <table id="inventory_table" class="table table-bordered table-striped">
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

                    <!-- Medicine Movement Analytics Card -->
                    <div class="col-md-4">
                        <div class="card card-outline card-primary rounded-0 shadow">
                            <div class="card-header">
                                <h3 class="card-title">Fast Moving Medicines</h3>
                            </div>
                            <div class="card-body">
                                <canvas id="medicineMovementChart"></canvas>
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
        $(function () {
            showMenuSelected("#mnu_medicines", "#mi_medicine_inventory");

            var message = '<?php echo $message;?>';
            if(message !== '') {
                showCustomMessage(message);
            }

            // Initialize DataTable
            $("#inventory_table").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#inventory_table_wrapper .col-md-6:eq(0)');

            // Prepare data for the chart
            var medicineNames = <?php echo json_encode(array_column($movement, 'medicine_name')); ?>;
            var quantities = <?php echo json_encode(array_column($movement, 'total_quantity_out')); ?>;

            // Create the bar chart
            var ctx = document.getElementById('medicineMovementChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: medicineNames,
                    datasets: [{
                        label: 'Total Units Dispensed',
                        data: quantities,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
</body>
</html> 