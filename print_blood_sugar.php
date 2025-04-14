<?php
include './config/connection.php';
include './common_service/common_functions.php';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

// Convert the date strings to MySQL format (yyyy-mm-dd)
$fromArr = explode("/", $from);
$toArr = explode("/", $to);
$fromMysql = $fromArr[2] . '-' . $fromArr[0] . '-' . $fromArr[1];
$toMysql = $toArr[2] . '-' . $toArr[0] . '-' . $toArr[1];

$query = "SELECT * FROM random_blood_sugar 
          WHERE DATE(date) BETWEEN :from_date AND :to_date 
          ORDER BY date DESC";

$stmt = $con->prepare($query);
$stmt->bindParam(':from_date', $fromMysql);
$stmt->bindParam(':to_date', $toMysql);
$stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Random Blood Sugar Report</title>
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
  </style>
</head>
<body>
  <div class="header">
    <h2>MAMATID HEALTH CENTER</h2>
    <h3>RANDOM BLOOD SUGAR REPORT</h3>
  </div>
  
  <div class="date-range">
    <p>From: <?php echo date('F d, Y', strtotime($from)); ?> To: <?php echo date('F d, Y', strtotime($to)); ?></p>
  </div>

  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Name</th>
        <th>Age</th>
        <th>Address</th>
        <th>Blood Sugar Reading</th>
        <th>Classification</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($result as $row): ?>
      <tr>
        <td><?php echo date('m/d/Y', strtotime($row['date'])); ?></td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['age']); ?></td>
        <td><?php echo htmlspecialchars($row['address']); ?></td>
        <td><?php echo htmlspecialchars($row['result']); ?></td>
        <td><?php 
          $result = floatval($row['result']);
          if ($result < 70) {
            echo "Low";
          } elseif ($result >= 70 && $result <= 140) {
            echo "Normal";
          } else {
            echo "High";
          }
        ?></td>
      </tr>
      <?php endforeach; ?>
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