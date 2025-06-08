<?php
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';

// Handle new stock entry
if (isset($_POST['add_stock'])) {
    $medicineId = $_POST['medicine_id'];
    $batchNumber = trim($_POST['batch_number']);
    $quantity = floatval($_POST['quantity']);
    $expiryDate = $_POST['expiry_date'];
    $purchaseDate = $_POST['purchase_date'];
    $purchasePrice = floatval($_POST['purchase_price']);
    $supplier = trim($_POST['supplier']);
    $remarks = trim($_POST['remarks']);
    
    // Validation
    if (empty($medicineId) || empty($batchNumber) || $quantity <= 0 || empty($expiryDate)) {
        $message = 'Required fields: Medicine, Batch Number, Quantity, and Expiry Date.';
    } else {
        try {
            // Start transaction
            $con->beginTransaction();
            
            // Insert stock entry
            $query = "INSERT INTO medicine_stock (medicine_id, batch_number, quantity, expiry_date, 
                      purchase_date, purchase_price, supplier, remarks, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $con->prepare($query);
            $stmt->execute([
                $medicineId,
                $batchNumber,
                $quantity,
                $expiryDate,
                $purchaseDate,
                $purchasePrice,
                $supplier,
                $remarks
            ]);
            
            $con->commit();
            $message = 'Stock added successfully.';
            
            // Redirect to avoid form resubmission
            header("Location: medicine_stock.php?message=" . urlencode($message));
            exit;
        } catch (PDOException $ex) {
            $con->rollback();
            $message = 'Error: ' . $ex->getMessage();
        }
    }
}

// Handle stock dispensing
if (isset($_POST['dispense_stock'])) {
    $stockId = $_POST['stock_id'];
    $medicineId = $_POST['medicine_id'];
    $quantity = floatval($_POST['dispense_quantity']);
    $patientName = trim($_POST['patient_name']);
    $remarks = trim($_POST['remarks']);
    
    // Validation
    if (empty($stockId) || empty($medicineId) || $quantity <= 0 || empty($patientName)) {
        $message = 'Required fields: Stock, Medicine, Quantity, and Patient Name.';
    } else {
        try {
            // Start transaction
            $con->beginTransaction();
            
            // Check available quantity
            $checkQuery = "SELECT quantity FROM medicine_stock WHERE id = ? FOR UPDATE";
            $stmt = $con->prepare($checkQuery);
            $stmt->execute([$stockId]);
            $currentStock = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($currentStock['quantity'] < $quantity) {
                throw new Exception('Insufficient stock available.');
            }
            
            // Update stock quantity
            $updateQuery = "UPDATE medicine_stock SET quantity = quantity - ? WHERE id = ?";
            $stmt = $con->prepare($updateQuery);
            $stmt->execute([$quantity, $stockId]);
            
            // Record dispensing
            $dispenseQuery = "INSERT INTO medicine_dispensing (medicine_id, stock_id, patient_name, 
                             quantity, remarks, dispensed_date, dispensed_by, created_at) 
                             VALUES (?, ?, ?, ?, ?, CURDATE(), ?, NOW())";
            $stmt = $con->prepare($dispenseQuery);
            $stmt->execute([
                $medicineId,
                $stockId,
                $patientName,
                $quantity,
                $remarks,
                $_SESSION['user_id'] ?? null
            ]);
            
            $con->commit();
            $message = 'Medicine dispensed successfully.';
            
            // Redirect to avoid form resubmission
            header("Location: medicine_stock.php?message=" . urlencode($message));
            exit;
        } catch (Exception $ex) {
            $con->rollback();
            $message = 'Error: ' . $ex->getMessage();
        }
    }
}

// Fetch all medicines for dropdown
try {
    $medicineQuery = "SELECT id, medicine_name, generic_name FROM medicines ORDER BY medicine_name ASC";
    $medicineStmt = $con->prepare($medicineQuery);
    $medicineStmt->execute();
    $medicines = $medicineStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    $message = 'Error fetching medicines: ' . $ex->getMessage();
}

// Fetch current stock levels
try {
    $stockQuery = "SELECT s.id, m.id as medicine_id, m.medicine_name, m.generic_name, c.category_name,
                          s.batch_number, s.quantity, s.expiry_date, s.purchase_date, s.purchase_price,
                          s.supplier, s.remarks,
                          DATE_FORMAT(s.created_at, '%d %b %Y %h:%i %p') as created_at
                   FROM medicine_stock s
                   JOIN medicines m ON s.medicine_id = m.id
                   JOIN medicine_categories c ON m.category_id = c.id
                   WHERE s.quantity > 0
                   ORDER BY s.expiry_date ASC, m.medicine_name ASC";
    $stockStmt = $con->prepare($stockQuery);
    $stockStmt->execute();
    $stockItems = $stockStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    $message = 'Error fetching stock: ' . $ex->getMessage();
}

// Fetch low stock items (less than 10 units)
try {
    $lowStockQuery = "SELECT m.medicine_name, m.generic_name, c.category_name,
                             SUM(s.quantity) as total_quantity
                      FROM medicine_stock s
                      JOIN medicines m ON s.medicine_id = m.id
                      JOIN medicine_categories c ON m.category_id = c.id
                      WHERE s.quantity > 0
                      GROUP BY m.id
                      HAVING total_quantity < 10
                      ORDER BY total_quantity ASC";
    $lowStockStmt = $con->prepare($lowStockQuery);
    $lowStockStmt->execute();
    $lowStockItems = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    $message = 'Error fetching low stock items: ' . $ex->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <?php include './config/data_tables_css.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title>Medicine Stock - Mamatid Health Center System</title>
    <style>
        .stock-alert {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .expiry-alert {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .stock-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fff;
        }
        .stock-card h3 {
            margin-top: 0;
            color: #333;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include './config/sidebar.php'; ?>
        
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Medicine Stock Management</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($lowStockItems)): ?>
                        <div class="stock-alert">
                            <h4><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h4>
                            <p>The following medicines are running low on stock:</p>
                            <ul>
                                <?php foreach ($lowStockItems as $item): ?>
                                    <li>
                                        <?php echo htmlspecialchars($item['medicine_name']); ?>
                                        (<?php echo htmlspecialchars($item['generic_name']); ?>) -
                                        Only <?php echo $item['total_quantity']; ?> units remaining
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Add New Stock</h3>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Medicine</label>
                                                    <select name="medicine_id" class="form-control" required>
                                                        <option value="">Select Medicine</option>
                                                        <?php foreach ($medicines as $medicine): ?>
                                                            <option value="<?php echo $medicine['id']; ?>">
                                                                <?php echo htmlspecialchars($medicine['medicine_name']); ?>
                                                                (<?php echo htmlspecialchars($medicine['generic_name']); ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Batch Number</label>
                                                    <input type="text" name="batch_number" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Quantity</label>
                                                    <input type="number" name="quantity" class="form-control" step="0.01" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Expiry Date</label>
                                                    <input type="date" name="expiry_date" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Purchase Date</label>
                                                    <input type="date" name="purchase_date" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Purchase Price</label>
                                                    <input type="number" name="purchase_price" class="form-control" step="0.01">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Supplier</label>
                                                    <input type="text" name="supplier" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Remarks</label>
                                                    <textarea name="remarks" class="form-control" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" name="add_stock" class="btn btn-primary">Add Stock</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Current Stock Levels</h3>
                                </div>
                                <div class="card-body">
                                    <table id="stockTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Medicine</th>
                                                <th>Category</th>
                                                <th>Batch Number</th>
                                                <th>Quantity</th>
                                                <th>Expiry Date</th>
                                                <th>Purchase Date</th>
                                                <th>Purchase Price</th>
                                                <th>Supplier</th>
                                                <th>Remarks</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stockItems as $item): ?>
                                                <tr class="<?php echo strtotime($item['expiry_date']) < strtotime('+3 months') ? 'expiry-alert' : ''; ?>">
                                                    <td>
                                                        <?php echo htmlspecialchars($item['medicine_name']); ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($item['generic_name']); ?>
                                                        </small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['batch_number']); ?></td>
                                                    <td><?php echo number_format($item['quantity'], 2); ?></td>
                                                    <td><?php echo date('d M Y', strtotime($item['expiry_date'])); ?></td>
                                                    <td><?php echo date('d M Y', strtotime($item['purchase_date'])); ?></td>
                                                    <td><?php echo number_format($item['purchase_price'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($item['supplier']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['remarks']); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-info" 
                                                                onclick="dispenseStock(<?php echo $item['id']; ?>, <?php echo $item['medicine_id']; ?>)">
                                                            Dispense
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Dispense Stock Modal -->
        <div class="modal fade" id="dispenseModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Dispense Medicine</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <input type="hidden" name="stock_id" id="dispense_stock_id">
                            <input type="hidden" name="medicine_id" id="dispense_medicine_id">
                            
                            <div class="form-group">
                                <label>Patient Name</label>
                                <input type="text" name="patient_name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Quantity to Dispense</label>
                                <input type="number" name="dispense_quantity" class="form-control" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="dispense_stock" class="btn btn-primary">Dispense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php include './config/footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>
    <?php include './config/data_tables_js.php'; ?>
    
    <script>
        $(document).ready(function() {
            $('#stockTable').DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "buttons": ["copy", "csv", "excel", "pdf", "print"]
            }).buttons().container().appendTo('#stockTable_wrapper .col-md-6:eq(0)');
        });

        function dispenseStock(stockId, medicineId) {
            $('#dispense_stock_id').val(stockId);
            $('#dispense_medicine_id').val(medicineId);
            $('#dispenseModal').modal('show');
        }
    </script>
</body>
</html> 