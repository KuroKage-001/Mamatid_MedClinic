<?php
// Include the PDF library and database connection
include("./pdflib/logics-builder-pdf.php");
include './config/connection.php';

// Set the report title and retrieve the date range from the query string
$reportTitle = "Patients Visits";
$from = $_GET['from']; // Expected format: mm/dd/yyyy
$to   = $_GET['to'];   // Expected format: mm/dd/yyyy

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
$query = "SELECT p.patient_name, p.address, p.phone_number, pv.visit_date, pv.disease
          FROM patients AS p, patient_visits AS pv
          WHERE pv.visit_date BETWEEN '$fromMysql' AND '$toMysql'
            AND pv.patient_id = p.id
          ORDER BY pv.visit_date ASC;";
$stmt = $con->prepare($query);
$stmt->execute();

// Loop through the query results and add each row to the PDF table
$i = 0;
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $i++;
    $data = array(
        $i,
        $r['visit_date'],
        $r['patient_name'],
        $r['address'],
        $r['phone_number'],
        $r['disease']
    );
    $pdf->AddRow($data);
}

// Output the generated PDF to the browser (inline display)
$pdf->Output('print_patient_visits.pdf', 'I');
?>
