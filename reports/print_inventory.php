<?php
include '../config/db_connection.php';
include '../system/utilities/admin_client_common_functions_services.php';

// Get the current inventory status using the medicine_stock_view
$query = "SELECT 
    msv.*,
    mi.unit_price,
    COALESCE(mit.total_out, 0) as total_dispensed
FROM medicine_stock_view msv
LEFT JOIN medicine_inventory mi ON mi.medicine_details_id = (
    SELECT id FROM medicine_details 
    WHERE medicine_name = msv.medicine_name 
    AND packing = msv.packing 
    LIMIT 1
)
LEFT JOIN (
    SELECT mi.medicine_details_id, 
           SUM(CASE WHEN mit.transaction_type = 'OUT' THEN mit.quantity ELSE 0 END) as total_out
    FROM medicine_inventory mi
    LEFT JOIN medicine_inventory_transactions mit ON mi.id = mit.medicine_inventory_id
    GROUP BY mi.medicine_details_id
) mit ON mit.medicine_details_id = mi.medicine_details_id
ORDER BY msv.stock_status ASC, msv.medicine_name ASC";

try {
    $stmt = $con->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $ex) {
    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Inventory Report - Mamatid Health Center</title>
    <link rel="icon" type="image/png" href="../dist/img/logo01.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .report-date {
            font-size: 14px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .status-low {
            color: #dc3545;
            font-weight: bold;
        }
        .status-medium {
            color: #ffc107;
            font-weight: bold;
        }
        .status-good {
            color: #28a745;
            font-weight: bold;
        }
        .summary-box {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="report-title">Medicine Inventory Report</div>
        <div class="report-date">Generated on: <?php echo date('F d, Y h:i A'); ?></div>
    </div>

    <div class="summary-box">
        <h3>Inventory Summary</h3>
        <?php
        $totalItems = count($result);
        $lowStock = 0;
        $mediumStock = 0;
        $goodStock = 0;
        $totalValue = 0;

        foreach($result as $row) {
            switch($row['stock_status']) {
                case 'LOW': $lowStock++; break;
                case 'MEDIUM': $mediumStock++; break;
                case 'GOOD': $goodStock++; break;
            }
            $totalValue += ($row['current_stock'] * $row['unit_price']);
        }
        ?>
        <p>Total Items: <?php echo $totalItems; ?></p>
        <p>Low Stock Items: <?php echo $lowStock; ?></p>
        <p>Medium Stock Items: <?php echo $mediumStock; ?></p>
        <p>Good Stock Items: <?php echo $goodStock; ?></p>
        <p>Total Inventory Value: ₱<?php echo number_format($totalValue, 2); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Medicine Name</th>
                <th>Packing</th>
                <th>Current Stock</th>
                <th>Total Dispensed</th>
                <th>Batch Number</th>
                <th>Unit Price</th>
                <th>Total Value</th>
                <th>Expiry Date</th>
                <th>Days Until Expiry</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($result as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                <td><?php echo htmlspecialchars($row['packing']); ?></td>
                <td><?php echo $row['current_stock']; ?></td>
                <td><?php echo $row['total_dispensed']; ?></td>
                <td><?php echo htmlspecialchars($row['batch_number'] ?? 'N/A'); ?></td>
                <td>₱<?php echo number_format($row['unit_price'], 2); ?></td>
                <td>₱<?php echo number_format($row['current_stock'] * $row['unit_price'], 2); ?></td>
                <td><?php echo $row['expiry_date'] ? date('M d, Y', strtotime($row['expiry_date'])) : 'N/A'; ?></td>
                <td><?php echo $row['days_until_expiry'] ?? 'N/A'; ?></td>
                <td class="status-<?php echo strtolower($row['stock_status']); ?>">
                    <?php echo $row['stock_status']; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print();" style="padding: 10px 20px;">Print Report</button>
    </div>
</body>
</html> 