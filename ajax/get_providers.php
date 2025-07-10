<?php
/**
 * Get Providers by Type
 * 
 * This file handles fetching providers (doctors or health workers) 
 * based on the provider type for walk-in appointments.
 */

include '../config/db_connection.php';
require_once '../system/utilities/admin_client_role_functions_services.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check permission
try {
    requireRole(['admin', 'health_worker', 'doctor']);
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get provider type from request
$providerType = $_POST['provider_type'] ?? '';

if (empty($providerType)) {
    echo json_encode(['success' => false, 'message' => 'Provider type is required']);
    exit;
}

try {
    $providers = [];
    
    if ($providerType == 'admin') {
        // Get active administrators
        $query = "SELECT id, display_name FROM admin_user_accounts 
                  WHERE role = 'admin' AND status = 'active' 
                  ORDER BY display_name ASC";
    } elseif ($providerType == 'health_worker') {
        // Get active health workers
        $query = "SELECT id, display_name FROM admin_user_accounts 
                  WHERE role = 'health_worker' AND status = 'active' 
                  ORDER BY display_name ASC";
    } elseif ($providerType == 'doctor') {
        // Get active doctors
        $query = "SELECT id, display_name FROM admin_user_accounts 
                  WHERE role = 'doctor' AND status = 'active' 
                  ORDER BY display_name ASC";
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid provider type']);
        exit;
    }
    
    $stmt = $con->prepare($query);
    $stmt->execute();
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'providers' => $providers,
        'count' => count($providers)
    ]);
    
} catch (PDOException $ex) {
    error_log("Error fetching providers: " . $ex->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching providers. Please try again.'
    ]);
}
?> 