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

    $query = "SELECT * FROM general_tetanus_toxoid 
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
  <title>Tetanus Toxoid Report</title>
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
    <h3>TETANUS TOXOID REPORT</h3>
  </div>
  
  <div class="date-range">
    <p>From: <?php echo date('F d, Y', strtotime($fromMysql)); ?> To: <?php echo date('F d, Y', strtotime($toMysql)); ?></p>
  </div>

  <?php if (count($result) > 0): ?>
    <div class="summary">
      <h4>Summary</h4>
      <p>Total Patients: <?php echo count($result); ?></p>
      <?php
        $ageGroups = [
          '0-18' => 0,
          '19-30' => 0,
          '31-50' => 0,
          '51+' => 0
        ];
        
        $diagnosisCounts = [];
        
        foreach($result as $row) {
          // Count age groups
          $age = intval($row['age']);
          if ($age <= 18) $ageGroups['0-18']++;
          elseif ($age <= 30) $ageGroups['19-30']++;
          elseif ($age <= 50) $ageGroups['31-50']++;
          else $ageGroups['51+']++;
          
          // Count diagnoses
          $diagnosis = $row['diagnosis'];
          if (!isset($diagnosisCounts[$diagnosis])) {
            $diagnosisCounts[$diagnosis] = 0;
          }
          $diagnosisCounts[$diagnosis]++;
        }
        arsort($diagnosisCounts);
      ?>
      <p>Age Distribution:</p>
      <ul>
        <li>0-18 years: <?php echo $ageGroups['0-18']; ?></li>
        <li>19-30 years: <?php echo $ageGroups['19-30']; ?></li>
        <li>31-50 years: <?php echo $ageGroups['31-50']; ?></li>
        <li>51+ years: <?php echo $ageGroups['51+']; ?></li>
      </ul>
      
      <p>Common Diagnoses:</p>
      <ul>
        <?php 
        $i = 0;
        foreach($diagnosisCounts as $diagnosis => $count): 
          if ($i++ < 5): // Show top 5 diagnoses
        ?>
          <li><?php echo htmlspecialchars($diagnosis); ?>: <?php echo $count; ?></li>
        <?php 
          endif;
        endforeach; 
        ?>
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
        <th>Diagnosis</th>
        <th>Remarks</th>
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
          <td><?php echo htmlspecialchars($row['diagnosis']); ?></td>
          <td><?php echo htmlspecialchars($row['remarks']); ?></td>
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
