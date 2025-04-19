<?php
// Define helper as included
define('HISTORY_HELPER_INCLUDED', true);

include '../config/connection.php';
include 'history_helper.php';

// Apply cache prevention headers
prevent_cache();

// Get days parameter
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;

// Force fresh query execution
$cacheBuster = uniqid();

// Get the maximum date from the database
$maxDate = get_max_date($con, 'bp_monitoring');
$endDate = $maxDate;

// Calculate start date based on the end date
if ($days > 0) {
    $startDate = date('Y-m-d', strtotime("$endDate -$days days"));
} else {
    // Get minimum date if showing all
    try {
        $minQuery = "SELECT MIN(date) as min_date FROM bp_monitoring";
        $minStmt = $con->prepare($minQuery);
        $minStmt->execute();
        $minResult = $minStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($minResult && $minResult['min_date']) {
            $startDate = $minResult['min_date'];
        } else {
            $startDate = date('Y-m-d', strtotime("$endDate -30 days"));
        }
    } catch(PDOException $ex) {
        $startDate = date('Y-m-d', strtotime("$endDate -30 days"));
        error_log("Error getting min date: " . $ex->getMessage());
    }
}

error_log("BP History Query - Start Date: $startDate, End Date: $endDate");

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
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process BP-specific data
    $labels = [];
    $bpValues = [];
    $patientCounts = [];
    
    foreach ($data as $row) {
        $labels[] = date('M j, Y', strtotime($row['visit_date']));
        
        // Calculate average BP for multiple readings on the same day
        $readings = explode(',', $row['bp_values']);
        $validReadings = array_filter($readings, function($value) {
            // Check if it's a numeric value or a valid BP reading format (e.g., "120/80")
            return is_numeric($value) || preg_match('/^\d+\/\d+$/', $value);
        });
        
        // Extract first number from BP readings (systolic)
        $systolicValues = [];
        foreach ($validReadings as $reading) {
            if (strpos($reading, '/') !== false) {
                $parts = explode('/', $reading);
                if (is_numeric($parts[0])) {
                    $systolicValues[] = (int)$parts[0];
                }
            } elseif (is_numeric($reading)) {
                $systolicValues[] = (int)$reading;
            }
        }
        
        if (count($systolicValues) > 0) {
            $avgBp = array_sum($systolicValues) / count($systolicValues);
            $bpValues[] = round($avgBp, 2);
        } else {
            $bpValues[] = 0; // Default value if no valid readings
        }
        
        // Store patient count
        $patientCounts[] = intval($row['patient_count']);
    }
    
    // Fill in missing dates
    $filledLabels = [];
    $filledBpValues = [];
    $filledPatientCounts = [];
    $currentDate = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    
    while ($currentDate <= $endTimestamp) {
        $currentDateStr = date('M j, Y', $currentDate);
        $index = array_search($currentDateStr, $labels);
        
        $filledLabels[] = $currentDateStr;
        $filledBpValues[] = ($index !== false) ? $bpValues[$index] : 0;
        $filledPatientCounts[] = ($index !== false) ? $patientCounts[$index] : 0;
        
        $currentDate = strtotime('+1 day', $currentDate);
    }
    
    $response = [
        'labels' => $filledLabels,
        'bp_values' => $filledBpValues,
        'patient_counts' => $filledPatientCounts
    ];
    
    // Log response for debugging
    error_log("BP History Response: " . json_encode($response));
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch(PDOException $ex) {
    error_log("BP History Error: " . $ex->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $ex->getMessage()
    ]);
}
?> 