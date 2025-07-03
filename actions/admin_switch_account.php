<?php
session_start();
header('Content-Type: application/json');

// Include database connection
include '../config/db_connection.php';
include '../common_service/role_functions.php';

try {
    // Check if user is currently logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('You must be logged in to switch accounts');
    }
    
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $save_login = isset($_POST['save_login']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        throw new Exception('Username and password are required');
    }
    
    // Check if user exists and verify credentials
    $query = "SELECT id, display_name, user_name, email, phone, role, status, profile_picture 
              FROM users 
              WHERE user_name = :username AND password = :password";
    
    $stmt = $con->prepare($query);
    $hashed_password = md5($password); // Store MD5 hash in variable first
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashed_password); // Pass variable by reference
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('Invalid username or password');
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        throw new Exception('Account is inactive. Please contact administrator.');
    }
    
    // Prevent switching to the same account
    if ($user['id'] == $_SESSION['user_id']) {
        throw new Exception('You are already logged in to this account');
    }
    
    // Store previous session info for audit/logging
    $previous_user_id = $_SESSION['user_id'];
    $previous_username = $_SESSION['user_name'];
    
    // Clear current session data
    session_unset();
    
    // Set new session data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['display_name'] = $user['display_name'];
    $_SESSION['user_name'] = $user['user_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['status'] = $user['status'];
    $_SESSION['profile_picture'] = $user['profile_picture'];
    $_SESSION['profile_picture_timestamp'] = time();
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Log the account switch (optional - for audit trail)
    try {
        $log_query = "INSERT INTO user_activity_log (user_id, action, details, ip_address, created_at) 
                      VALUES (:user_id, 'account_switch', :details, :ip_address, NOW())";
        
        $log_stmt = $con->prepare($log_query);
        $log_details = "Switched from user ID: $previous_user_id ($previous_username) to user ID: {$user['id']} ({$user['user_name']})";
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $log_stmt->bindParam(':user_id', $user['id']);
        $log_stmt->bindParam(':details', $log_details);
        $log_stmt->bindParam(':ip_address', $ip_address);
        $log_stmt->execute();
    } catch (Exception $e) {
        // Log error but don't fail the switch operation
        error_log("Failed to log account switch: " . $e->getMessage());
    }
    
    // All users redirect to admin dashboard after account switch
    $redirect_url = 'admin_dashboard.php';
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Successfully switched to {$user['display_name']}'s account",
        'redirect' => $redirect_url,
        'user' => [
            'id' => $user['id'],
            'display_name' => $user['display_name'],
            'user_name' => $user['user_name'],
            'role' => $user['role']
        ]
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    // Database error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
    error_log("Switch account database error: " . $e->getMessage());
}
?> 