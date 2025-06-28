<?php
// Include the PDF library and database connection
include("../system/logics-builder-pdf.php");
include '../config/db_connection.php';
include '../common_service/common_functions.php';

// Set the report title and retrieve the date range from the query string
$reportTitle = "Patients Visits";
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

// Convert the date strings to MySQL format (yyyy-mm-dd)
$fromArr = explode("/", $from);
$toArr   = explode("/", $to);
$fromMysql = $fromArr[2] . '-' . $fromArr[0] . '-' . $fromArr[1];
$toMysql   = $toArr[2]   . '-' . $toArr[0]   . '-' . $toArr[1];

// Create a new PDF instance in landscape mode
$pdf = new LB_PDF('L', false, $reportTitle, $from, $to);
$pdf->SetMargins(15, 10);
$pdf->AliasNbPages();
$pdf->AddPage();

// Define the table headers and set column widths/alignments
$titlesArr = array('S.No', 'Visit Date', 'Patient Name', 'Address', 'Contact#', 'Disease');
$pdf->SetWidths(array(15, 25, 50, 70, 30, 70));
$pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L'));

// Add vertical space before the table header
$pdf->Ln(15);
$pdf->AddTableHeader($titlesArr);

// Build the SQL query to retrieve patient visits within the given date range
$query = "SELECT v.*, m.name as medicine_name 
          FROM visits v 
          LEFT JOIN medicines m ON v.medicine_id = m.id 
          WHERE DATE(v.date) BETWEEN :from_date AND :to_date 
          ORDER BY v.date DESC";
$stmt = $con->prepare($query);
$stmt->bindParam(':from_date', $from);
$stmt->bindParam(':to_date', $to);
$stmt->execute();

// Loop through the query results and add each row to the PDF table
$i = 0;
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $i++;
    $data = array(
        $i,
        $r['date'],
        $r['name'],
        $r['address'],
        $r['phone_number'],
        $r['disease']
    );
    $pdf->AddRow($data);
}

// Output the generated PDF to the browser (inline display)
$pdf->Output('print_patient_visits.pdf', 'I');

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient Visits Report</title>
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
    <h3>PATIENT VISITS REPORT</h3>
  </div>
  
  <div class="date-range">
    <p>From: <?php echo date('F d, Y', strtotime($from)); ?> To: <?php echo date('F d, Y', strtotime($to)); ?></p>
  </div>

  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Patient Name</th>
        <th>Disease</th>
        <th>Alcohol</th>
        <th>Smoke</th>
        <th>Obese</th>
        <th>Medicine</th>
        <th>Packing</th>
        <th>QTY</th>
        <th>Dosage</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($result as $row): ?>
      <tr>
        <td><?php echo date('m/d/Y', strtotime($row['date'])); ?></td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['disease']); ?></td>
        <td><?php echo $row['alcohol'] ? 'Yes' : 'No'; ?></td>
        <td><?php echo $row['smoke'] ? 'Yes' : 'No'; ?></td>
        <td><?php echo $row['obese'] ? 'Yes' : 'No'; ?></td>
        <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
        <td><?php echo htmlspecialchars($row['packing']); ?></td>
        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
        <td><?php echo htmlspecialchars($row['dosage']); ?></td>
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
?>
