<?php
/**
 * Admin Switch Account Action
 * Safely switches admin accounts without affecting client sessions
 */

session_start();

// Include necessary files
require_once '../system/security/admin_client_session_isolation.php';
require_once '../system/utilities/admin_client_role_functions_services.php';
require_once '../config/db_connection.php';

// Set JSON response header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

try {
    // Check if user is already logged in as admin/staff
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('No active admin session found');
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate input
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $saveLogin = isset($_POST['save_login']);

    if (empty($username) || empty($password)) {
        throw new Exception('Username and password are required');
    }

    // Log the switch attempt
    logSessionOperation('admin_switch_attempt', [
        'current_user_id' => $_SESSION['user_id'],
        'current_username' => $_SESSION['user_name'] ?? 'unknown',
        'target_username' => $username,
        'has_concurrent_client' => isset($_SESSION['client_id']) && !empty($_SESSION['client_id'])
    ]);

    // Prepare query to check new admin credentials
    $query = "SELECT `id`, `user_name`, `display_name`, `role`, `status`, `profile_picture`, `password`
              FROM `admin_user_accounts`
              WHERE `user_name` = ? AND `status` = 'active'";

    $stmt = $con->prepare($query);
    $stmt->execute([$username]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception('Invalid username or account not active');
    }

    $newUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password (assuming MD5 - should be upgraded to better hashing)
    $encryptedPassword = md5($password);
    if ($newUser['password'] !== $encryptedPassword) {
        throw new Exception('Invalid password');
    }

    // Check if user is trying to switch to the same account
    if ($newUser['id'] == $_SESSION['user_id']) {
        throw new Exception('You are already logged in as this user');
    }

    // Prepare new admin session data
    $newAdminData = [
        'user_id' => $newUser['id'],
        'user_name' => $newUser['user_name'],
        'display_name' => $newUser['display_name'],
        'role' => $newUser['role'],
        'status' => $newUser['status'],
        'profile_picture' => $newUser['profile_picture'] ?: 'default_profile.jpg',
        'profile_picture_timestamp' => time(),
        'last_activity' => time(),
        'login_time' => time(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    // Safely switch account using session isolation
    safeSwitchAdminAccount($newAdminData);

    // Log successful switch
    logSessionOperation('admin_switch_success', [
        'new_user_id' => $newUser['id'],
        'new_username' => $newUser['user_name'],
        'new_role' => $newUser['role'],
        'client_session_preserved' => isset($_SESSION['client_id']) && !empty($_SESSION['client_id'])
    ]);

    // Determine redirect URL based on role
    $redirectUrl = 'admin_dashboard.php';
    switch ($newUser['role']) {
        case 'admin':
            $redirectUrl = 'admin_dashboard.php';
            break;
        case 'doctor':
            $redirectUrl = 'admin_dashboard.php'; // or specific doctor dashboard
            break;
        case 'health_worker':
            $redirectUrl = 'admin_dashboard.php'; // or specific health worker dashboard
            break;
    }

    $response['success'] = true;
    $response['message'] = 'Account switched successfully to ' . $newUser['display_name'] . ' (' . ucfirst($newUser['role']) . ')';
    $response['redirect'] = $redirectUrl;

} catch (PDOException $e) {
    error_log("Database error in admin switch account: " . $e->getMessage());
    $response['message'] = 'Database error occurred. Please try again.';
    
    logSessionOperation('admin_switch_db_error', [
        'error' => $e->getMessage(),
        'username' => $username ?? 'unknown'
    ]);
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    
    logSessionOperation('admin_switch_error', [
        'error' => $e->getMessage(),
        'username' => $username ?? 'unknown'
    ]);
}

// Return JSON response
echo json_encode($response);
exit;
?> 