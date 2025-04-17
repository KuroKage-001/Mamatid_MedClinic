<?php
include '../config/connection.php';

$days = isset($_GET['days']) ? intval($_GET['days']) : 7;
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime("-$days days"));

$query = "SELECT 
    DATE(date) as visit_date,
    COUNT(*) as visit_count
FROM family_members 
WHERE date BETWEEN :start_date AND :end_date
GROUP BY DATE(date)
ORDER BY visit_date ASC";

try {
    $stmt = $con->prepare($query);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    
    $labels = [];
    $values = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = date('M j, Y', strtotime($row['visit_date']));
        $values[] = intval($row['visit_count']);
    }
    
    // Fill in missing dates with zero values
    $currentDate = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    $filledLabels = [];
    $filledValues = [];
    
    while ($currentDate <= $endTimestamp) {
        $currentDateStr = date('M j, Y', $currentDate);
        $index = array_search($currentDateStr, $labels);
        
        $filledLabels[] = $currentDateStr;
        $filledValues[] = ($index !== false) ? $values[$index] : 0;
        
        $currentDate = strtotime('+1 day', $currentDate);
    }
    
    echo json_encode([
        'labels' => $filledLabels,
        'values' => $filledValues
    ]);
    
} catch(PDOException $ex) {
    echo json_encode([
        'error' => $ex->getMessage()
    ]);
} 