<?php
include '../config/db_connection.php';
include '../system/utilities/admin_client_common_functions_services.php';
require('../system/fpdf182/fpdf.php');

// Create a custom PDF class without logo dependency
class AppointmentPDF extends FPDF {
    function Header() {
        // Set font
        $this->SetFont('Arial', 'B', 15);
        // Title
        $this->Cell(0, 10, 'Mamatid Health Center', 0, 1, 'C');
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, 'Appointment Confirmation', 0, 1, 'C');
        // Line break
        $this->Ln(10);
    }
    
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    echo "<h2>Error: No appointment token provided.</h2>";
    echo "<p>Please check your email for the correct link or contact the clinic for assistance.</p>";
    exit;
}

$token = trim($_GET['token']);

try {
    // Find appointment by token
    $query = "SELECT a.*, u.display_name as doctor_name 
            FROM appointments a
            LEFT JOIN users u ON a.doctor_id = u.id
            WHERE a.view_token = ? AND a.token_expiry > NOW()
            LIMIT 1";
            
    $stmt = $con->prepare($query);
    $stmt->execute([$token]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        // Check if token exists but expired
        $expiredQuery = "SELECT id FROM appointments WHERE view_token = ?";
        $expiredStmt = $con->prepare($expiredQuery);
        $expiredStmt->execute([$token]);
        
        if ($expiredStmt->fetch()) {
            echo "<h2>Error: This appointment link has expired.</h2>";
            echo "<p>Please contact the clinic for assistance.</p>";
        } else {
            echo "<h2>Error: Invalid appointment link.</h2>";
            echo "<p>Please check your email for the correct link.</p>";
        }
        exit;
    }
    
    // Format date for display
    $formattedDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
    $formattedTime = date('h:i A', strtotime($appointment['appointment_time']));
    
    // Create PDF document
    $pdf = new AppointmentPDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Add appointment details to PDF
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'APPOINTMENT DETAILS', 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 12);
    
    // Status badge
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Status: ' . strtoupper($appointment['status']), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Ln(5);

    // Patient information
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(60, 8, 'Patient Name:', 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 8, $appointment['patient_name'], 0, 1);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(60, 8, 'Doctor:', 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 8, $appointment['doctor_name'] ?? 'Not Assigned', 0, 1);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(60, 8, 'Date:', 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 8, $formattedDate, 0, 1);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(60, 8, 'Time:', 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 8, $formattedTime, 0, 1);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(60, 8, 'Reason for Visit:', 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 8, $appointment['reason'], 0, 1);
    
    // Add notes if available
    if (!empty($appointment['notes'])) {
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(60, 8, 'Doctor\'s Notes:', 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->MultiCell(0, 8, $appointment['notes'], 0);
    }
    
    $pdf->Ln(10);
    
    // Additional information
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 10, 'Important Information', 0, 1, 'L');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 6, 'Please arrive 15 minutes before your scheduled appointment time. Bring any relevant medical records, insurance information, and a list of current medications. If you need to cancel or reschedule your appointment, please contact the clinic at least 24 hours in advance.', 0, 'L');
    
    $pdf->Ln(5);
    
    // Clinic information
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, 'Mamatid Health Center', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Contact: clinic@mamatidhealth.com | Tel: (02) 888-7777', 0, 1, 'C');
    
    // Output PDF
    $pdf->Output('Appointment_' . $appointment['id'] . '.pdf', 'I');
    
} catch(PDOException $ex) {
    echo "<h2>Database Error</h2>";
    echo "<p>An error occurred: " . $ex->getMessage() . "</p>";
    exit;
}
?>