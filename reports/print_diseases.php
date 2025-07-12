<?php
// Include the PDF library and database connection
include("../system/utilities/pdf_logics_builder.php");
include '../config/db_connection.php';

// Set report title and retrieve GET parameters
$reportTitle = "Disease Based Visits";
$from = $_GET['from'];     // Expected format: MM/DD/YYYY
$to = $_GET['to'];         // Expected format: MM/DD/YYYY
$disease = $_GET['disease'];

// Convert the input dates to MySQL format (YYYY-MM-DD)
$fromArr = explode("/", $from);
$toArr = explode("/", $to);
$fromMysql = $fromArr[2] . '-' . $fromArr[0] . '-' . $fromArr[1];
$toMysql = $toArr[2] . '-' . $toArr[0] . '-' . $toArr[1];

// Create a new PDF instance with landscape orientation
$pdf = new LB_PDF('L', false, $reportTitle, $from, $to);
$pdf->SetMargins(15, 10);
$pdf->AliasNbPages();
$pdf->AddPage();

// Define table headers and their properties
$titlesArr = array('S.No', 'Visit Date', 'Patient Name', 'Address', 'Contact#', 'Disease');
$pdf->SetWidths(array(15, 25, 50, 70, 30, 70));
$pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L'));

// Add some space before the table and add a caption in uppercase
$pdf->Ln(15);
$diseaseCaption = strtoupper($disease);
$pdf->AddTableCaption($diseaseCaption);

// Add table header to the PDF
$pdf->AddTableHeader($titlesArr);

// Build the SQL query to fetch disease-based visits between the specified dates
$query = "SELECT p.patient_name, p.address, p.phone_number, pv.visit_date, pv.disease
          FROM patients AS p, patient_visits AS pv
          WHERE pv.visit_date BETWEEN '$fromMysql' AND '$toMysql'
            AND pv.patient_id = p.id
            AND pv.disease LIKE('%$disease%')
          ORDER BY pv.visit_date ASC;";
$stmt = $con->prepare($query);
$stmt->execute();

// Initialize serial number counter
$i = 0;
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $i++;
    // Prepare the data row
    $data = array(
        $i,
        $r['visit_date'],
        $r['patient_name'],
        $r['address'],
        $r['phone_number'],
        $r['disease']
    );
    
    // Add the row to the PDF table
    $pdf->AddRow($data);
}

// Output the PDF to the browser
$pdf->Output('print_patient_diseases.pdf', 'I');
?>
