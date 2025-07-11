<?php
include './config/db_connection.php';
require_once './system/utilities/admin_client_role_functions_services.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get POST data
$scheduleType = $_POST['schedule_type'] ?? '';
$scheduleId = $_POST['schedule_id'] ?? '';
$scheduleDate = $_POST['schedule_date'] ?? '';
$providerId = $_POST['provider_id'] ?? '';
$providerName = $_POST['provider_name'] ?? '';

// Validate required fields
if (empty($scheduleType) || empty($scheduleId) || empty($scheduleDate)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $appointments = [];
    

    
    if ($scheduleType === 'doctor') {
        // Get appointments for doctor schedule
        $query = "
            SELECT 
                a.id,
                a.patient_name,
                a.appointment_date,
                a.appointment_time,
                a.reason,
                a.status,
                0 as is_walkin,
                'regular' as appointment_type
            FROM admin_clients_appointments a
            WHERE a.schedule_id = :schedule_id1 
            AND a.appointment_date = :schedule_date1
            
            UNION ALL
            
            SELECT 
                w.id,
                w.patient_name,
                w.appointment_date,
                w.appointment_time,
                w.reason,
                w.status,
                1 as is_walkin,
                'walk-in' as appointment_type
            FROM admin_walkin_appointments w
            WHERE w.schedule_id = :schedule_id2 
            AND w.appointment_date = :schedule_date2
            
            ORDER BY appointment_time ASC
        ";
        
    } else if ($scheduleType === 'staff') {
        // Get appointments for staff schedule
        $query = "
            SELECT 
                a.id,
                a.patient_name,
                a.appointment_date,
                a.appointment_time,
                a.reason,
                a.status,
                0 as is_walkin,
                'regular' as appointment_type
            FROM admin_clients_appointments a
            WHERE a.schedule_id = :schedule_id1 
            AND a.appointment_date = :schedule_date1
            
            UNION ALL
            
            SELECT 
                w.id,
                w.patient_name,
                w.appointment_date,
                w.appointment_time,
                w.reason,
                w.status,
                1 as is_walkin,
                'walk-in' as appointment_type
            FROM admin_walkin_appointments w
            WHERE w.schedule_id = :schedule_id2 
            AND w.appointment_date = :schedule_date2
            
            ORDER BY appointment_time ASC
        ";
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid schedule type: ' . $scheduleType]);
        exit;
    }
    
    $stmt = $con->prepare($query);
    $stmt->bindParam(':schedule_id1', $scheduleId);
    $stmt->bindParam(':schedule_date1', $scheduleDate);
    $stmt->bindParam(':schedule_id2', $scheduleId);
    $stmt->bindParam(':schedule_date2', $scheduleDate);
    $stmt->execute();
    
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format appointment times for display
    foreach ($appointments as &$appointment) {
        $appointment['appointment_time'] = date('g:i A', strtotime($appointment['appointment_time']));
        $appointment['is_walkin'] = (int)$appointment['is_walkin'];
    }
    
    echo json_encode([
        'success' => true,
        'appointments' => $appointments,
        'total_count' => count($appointments)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?> 