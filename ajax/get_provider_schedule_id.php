<?php
// Include authentication check
require_once '../system/utilities/check_auth.php';

require_once '../config/db_connection.php';
require_once '../system/utilities/admin_client_role_functions_services.php';

header('Content-Type: application/json');

// Check permission
try {
    requireRole(['admin', 'health_worker', 'doctor']);
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $providerId = $_POST['provider_id'] ?? '';
    $providerType = $_POST['provider_type'] ?? '';
    $appointmentDate = $_POST['appointment_date'] ?? '';
    
    // Debug logging
    error_log("get_provider_schedule_id.php - Received: provider_id={$providerId}, provider_type={$providerType}, appointment_date={$appointmentDate}");
    
    if (empty($providerId) || empty($providerType) || empty($appointmentDate)) {
        throw new Exception('Provider ID, provider type, and appointment date are required');
    }
    
    // Validate provider type
    if (!in_array($providerType, ['doctor', 'staff', 'health_worker', 'admin'])) {
        throw new Exception('Invalid provider type');
    }
    
    // Get schedule details based on provider type
    if ($providerType === 'doctor') {
        $query = "SELECT ds.*, u.display_name as provider_name, u.role 
                 FROM admin_doctor_schedules ds
                 JOIN admin_user_accounts u ON ds.doctor_id = u.id
                 WHERE ds.doctor_id = ? AND ds.schedule_date = ? AND ds.is_approved = 1 AND ds.is_deleted = 0";
    } else { // staff, health_worker, admin (includes health_worker and admin)
        $query = "SELECT ss.*, u.display_name as provider_name, u.role 
                 FROM admin_hw_schedules ss
                 JOIN admin_user_accounts u ON ss.staff_id = u.id
                 WHERE ss.staff_id = ? AND ss.schedule_date = ? AND ss.is_approved = 1";
    }
    
    $stmt = $con->prepare($query);
    $stmt->execute([$providerId, $appointmentDate]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        error_log("get_provider_schedule_id.php - No schedule found for provider_id={$providerId}, provider_type={$providerType}, date={$appointmentDate}");
        throw new Exception('No approved schedule found for the selected provider on this date');
    }
    
    // Debug logging for successful schedule retrieval
    error_log("get_provider_schedule_id.php - Schedule found: schedule_id={$schedule['id']}, start_time={$schedule['start_time']}, end_time={$schedule['end_time']}");
    
    // Return the schedule details including the schedule_id
    echo json_encode([
        'success' => true,
        'schedule_id' => $schedule['id'],
        'schedule_date' => $schedule['schedule_date'],
        'start_time' => $schedule['start_time'],
        'end_time' => $schedule['end_time'],
        'time_slot_minutes' => $schedule['time_slot_minutes'],
        'max_patients' => $schedule['max_patients'],
        'provider_name' => $schedule['provider_name'],
        'provider_role' => $schedule['role'],
        'notes' => $schedule['notes'] ?? ''
    ]);
    
} catch (PDOException $ex) {
    error_log("Database error in get_provider_schedule_id.php: " . $ex->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $ex) {
    echo json_encode([
        'success' => false,
        'error' => $ex->getMessage()
    ]);
}
?> 