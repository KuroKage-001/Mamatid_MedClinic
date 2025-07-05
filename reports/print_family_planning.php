<?php
include '../config/db_connection.php';
include '../common_service/common_functions.php';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

try {
    // Convert the date strings to MySQL format (yyyy-mm-dd)
    $fromArr = explode("/", $from);
    $toArr = explode("/", $to);
    $fromMysql = $fromArr[2] . '-' . $fromArr[0] . '-' . $fromArr[1];
    $toMysql = $toArr[2] . '-' . $toArr[0] . '-' . $toArr[1];

    $query = "SELECT * FROM general_family_planning 
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Family Planning Report</title>
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
      padding: 10px;
      background-color: #f8f9fa;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
  </style>
</head>
<body>
  <div class="header">
    <h2>MAMATID HEALTH CENTER</h2>
    <h3>FAMILY PLANNING REPORT</h3>
  </div>
  
  <div class="date-range">
    <p>From: <?php echo date('F d, Y', strtotime($fromMysql)); ?> To: <?php echo date('F d, Y', strtotime($toMysql)); ?></p>
  </div>

  <?php if (count($result) > 0): ?>
    <div class="summary">
      <h4>Summary</h4>
      <p>Total Participants: <?php echo count($result); ?></p>
      <?php
        $ageGroups = [
          '18-25' => 0,
          '26-35' => 0,
          '36-45' => 0,
          '46+' => 0
        ];
        
        foreach($result as $row) {
          $age = intval($row['age']);
          if ($age <= 25) $ageGroups['18-25']++;
          elseif ($age <= 35) $ageGroups['26-35']++;
          elseif ($age <= 45) $ageGroups['36-45']++;
          else $ageGroups['46+']++;
        }
      ?>
      <p>Age Distribution:</p>
      <ul>
        <li>18-25 years: <?php echo $ageGroups['18-25']; ?></li>
        <li>26-35 years: <?php echo $ageGroups['26-35']; ?></li>
        <li>36-45 years: <?php echo $ageGroups['36-45']; ?></li>
        <li>46+ years: <?php echo $ageGroups['46+']; ?></li>
      </ul>
    </div>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Name</th>
        <th>Age</th>
        <th>Address</th>
        <th>Method</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($result) > 0): ?>
        <?php foreach($result as $row): ?>
        <tr>
          <td><?php echo date('m/d/Y', strtotime($row['date'])); ?></td>
          <td><?php echo htmlspecialchars($row['name']); ?></td>
          <td><?php echo htmlspecialchars($row['age']); ?></td>
          <td><?php echo htmlspecialchars($row['address']); ?></td>
          <td><?php echo htmlspecialchars($row['method'] ?? 'N/A'); ?></td>
          <td><?php echo htmlspecialchars($row['status'] ?? 'Active'); ?></td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" style="text-align: center;">No records found for the selected date range.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="footer">
    <p>Generated on: <?php echo date('F d, Y H:i:s'); ?></p>
  </div>

  <script>
    window.print();
  </script>
</body>
</html> 