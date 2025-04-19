<?php
include '../config/connection.php';

$days = isset($_GET['days']) ? intval($_GET['days']) : 7;
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime("-$days days"));

$query = "SELECT 
    DATE(date) as visit_date,
    GROUP_CONCAT(bp) as bp_values,
    COUNT(*) as patient_count
FROM bp_monitoring 
WHERE date BETWEEN :start_date AND :end_date
GROUP BY DATE(date)
ORDER BY visit_date ASC";

try {
    $stmt = $con->prepare($query);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    
    $labels = [];
    $bpValues = [];
    $patientCounts = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = date('M j, Y', strtotime($row['visit_date']));
        
        // Calculate average BP for multiple readings on the same day
        $readings = explode(',', $row['bp_values']);
        $avgBp = array_sum($readings) / count($readings);
        $bpValues[] = round($avgBp, 2);
        
        // Store patient count
        $patientCounts[] = intval($row['patient_count']);
    }
    
    // Fill in missing dates with zero values
    $currentDate = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    $filledLabels = [];
    $filledBpValues = [];
    $filledPatientCounts = [];
    
    while ($currentDate <= $endTimestamp) {
        $currentDateStr = date('M j, Y', $currentDate);
        $index = array_search($currentDateStr, $labels);
        
        $filledLabels[] = $currentDateStr;
        $filledBpValues[] = ($index !== false) ? $bpValues[$index] : 0;
        $filledPatientCounts[] = ($index !== false) ? $patientCounts[$index] : 0;
        
        $currentDate = strtotime('+1 day', $currentDate);
    }
    
    echo json_encode([
        'labels' => $filledLabels,
        'bp_values' => $filledBpValues,
        'patient_counts' => $filledPatientCounts
    ]);
    
} catch(PDOException $ex) {
    echo json_encode([
        'error' => $ex->getMessage()
    ]);
}
?> 