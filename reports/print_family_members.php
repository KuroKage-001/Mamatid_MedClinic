<?php
include '../config/db_connection.php';
include '../system/utilities/admin_client_common_functions_services.php';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

try {
    // Convert the date strings to MySQL format (yyyy-mm-dd)
    $fromArr = explode("/", $from);
    $toArr = explode("/", $to);
    $fromMysql = $fromArr[2] . '-' . $fromArr[0] . '-' . $fromArr[1];
    $toMysql = $toArr[2] . '-' . $toArr[0] . '-' . $toArr[1];

    // Get family members data
    $query = "SELECT 
                id,
                name,
                date,
                created_at
              FROM general_family_members
              WHERE DATE(date) BETWEEN :from_date AND :to_date
              ORDER BY date DESC";

    $stmt = $con->prepare($query);
    $stmt->bindParam(':from_date', $fromMysql, PDO::PARAM_STR);
    $stmt->bindParam(':to_date', $toMysql, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Members Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .date-range {
            text-align: center;
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
            background-color: #f2f2f2;
        }
        .footer {
            text-align: right;
            margin-top: 20px;
        }
        .summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .summary h4 {
            margin-top: 0;
            color: #333;
        }
        .summary ul {
            list-style-type: none;
            padding-left: 0;
        }
        .summary li {
            margin-bottom: 5px;
        }
        @media print {
            .summary {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>MAMATID HEALTH CENTER</h2>
        <h3>FAMILY MEMBERS REPORT</h3>
    </div>

    <div class="date-range">
        <p>From: <?php echo date('F d, Y', strtotime($fromMysql)); ?> To: <?php echo date('F d, Y', strtotime($toMysql)); ?></p>
    </div>

    <?php if (count($result) > 0): ?>
        <div class="summary">
            <h4>Summary</h4>
            <?php
                $totalMembers = count($result);
                
                // Group by months
                $monthlyDistribution = [];
                foreach($result as $row) {
                    $month = date('F Y', strtotime($row['date']));
                    if (!isset($monthlyDistribution[$month])) {
                        $monthlyDistribution[$month] = 0;
                    }
                    $monthlyDistribution[$month]++;
                }
                krsort($monthlyDistribution); // Sort by most recent month first
            ?>
            <p><strong>Total Family Members:</strong> <?php echo $totalMembers; ?></p>
            
            <p><strong>Monthly Distribution:</strong></p>
            <ul>
                <?php foreach($monthlyDistribution as $month => $count): ?>
                    <li><?php echo $month; ?>: <?php echo $count; ?> members 
                        (<?php echo round(($count/$totalMembers)*100, 1); ?>%)</li>
                <?php endforeach; ?>
            </ul>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Registration Date</th>
                    <th>Name</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($result as $row): ?>
                    <tr>
                        <td><?php echo date('m/d/Y', strtotime($row['date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo date('m/d/Y h:i A', strtotime($row['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align: center; padding: 20px;">
            <p>No family members found for the selected date range.</p>
        </div>
    <?php endif; ?>

    <div class="footer">
        <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html> 