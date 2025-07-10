<?php
// Prevent direct access
if (!defined('HISTORY_HELPER_INCLUDED')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access forbidden');
}

/**
 * Apply cache prevention headers
 */
function prevent_cache() {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

/**
 * Format and fill missing dates for history data
 *
 * @param array $data Raw data from database
 * @param string $startDate Start date in Y-m-d format
 * @param string $endDate End date in Y-m-d format
 * @param string $dateField Field name containing date values
 * @param string $countField Field name containing count values
 * @return array Formatted data with labels and values
 */
function format_history_data($data, $startDate, $endDate, $dateField = 'visit_date', $countField = 'visit_count') {
    $labels = [];
    $values = [];
    
    // Extract dates and counts from data
    foreach ($data as $row) {
        $labels[] = date('M j, Y', strtotime($row[$dateField]));
        $values[] = intval($row[$countField]);
    }
    
    // Fill in missing dates
    $filledLabels = [];
    $filledValues = [];
    $currentDate = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    
    while ($currentDate <= $endTimestamp) {
        $currentDateStr = date('M j, Y', $currentDate);
        $index = array_search($currentDateStr, $labels);
        
        $filledLabels[] = $currentDateStr;
        $filledValues[] = ($index !== false) ? $values[$index] : 0;
        
        $currentDate = strtotime('+1 day', $currentDate);
    }
    
    return [
        'labels' => $filledLabels,
        'values' => $filledValues
    ];
}

/**
 * Log query parameters and results for debugging
 *
 * @param string $type History type
 * @param string $startDate Start date
 * @param string $endDate End date
 * @param array $response Response data
 */
function log_history_debug($type, $startDate, $endDate, $response) {
    error_log("[$type] History Query - Start: $startDate, End: $endDate");
    error_log("[$type] Response: " . json_encode($response));
}

/**
 * Get the maximum date from a table
 *
 * @param PDO $con Database connection
 * @param string $table Table name
 * @return string Maximum date in YYYY-MM-DD format
 */
function get_max_date($con, $table) {
    try {
        $query = "SELECT MAX(date) as max_date FROM $table";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['max_date']) {
            return $result['max_date'];
        }
    } catch(PDOException $ex) {
        error_log("Error getting max date: " . $ex->getMessage());
    }
    
    // Default to current date if no data or error
    return date('Y-m-d');
}

/**
 * Execute a standard history query and return formatted results
 *
 * @param PDO $con Database connection
 * @param string $type History type (for logging)
 * @param string $table Table name to query
 * @param int $days Number of days to look back
 * @return array Formatted history data
 */
function get_standard_history_data($con, $type, $table, $days) {
    // Get the maximum date from the database
    $maxDate = get_max_date($con, $table);
    $endDate = $maxDate;
    
    // If using a fixed number of days
    if ($days > 0) {
        $startDate = date('Y-m-d', strtotime("$endDate -$days days"));
    } else {
        // If showing all data, get min date too (or default to 30 days before max)
        try {
            $minQuery = "SELECT MIN(date) as min_date FROM $table";
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
    
    $query = "SELECT 
        DATE(date) as visit_date,
        COUNT(*) as visit_count
    FROM $table 
    WHERE date BETWEEN :start_date AND :end_date
    GROUP BY DATE(date)
    ORDER BY visit_date ASC";
    
    try {
        $stmt = $con->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $formattedData = format_history_data($data, $startDate, $endDate);
        
        log_history_debug($type, $startDate, $endDate, $formattedData);
        
        return $formattedData;
    } catch(PDOException $ex) {
        error_log("[$type] Error: " . $ex->getMessage());
        return ['error' => $ex->getMessage()];
    }
}
?> 