<?php
// Enhanced Role Functions with Session Isolation
// Protects client sessions from admin session operations

// Include admin-client session isolation if not already loaded
if (!defined('SESSION_ISOLATION_INCLUDED')) {
    require_once __DIR__ . '/../security/admin_client_session_isolation.php';
}

/**
 * Check if user is logged in (any type)
 */
function isLoggedIn() {
    $adminId = getAdminSessionVar('user_id');
    $clientId = getClientSessionVar('client_id');
    return (isset($adminId) && !empty($adminId)) || (isset($clientId) && !empty($clientId));
}

/**
 * Check if client is logged in
 */
function isClientLoggedIn() {
    $clientId = getClientSessionVar('client_id');
    return isset($clientId) && !empty($clientId);
}

/**
 * Get current user role (admin side)
 */
function getUserRole() {
    return getAdminSessionVar('role');
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    $userRole = getAdminSessionVar('role');
    return isset($userRole) && $userRole === $role;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is health worker
 */
function isHealthWorker() {
    return hasRole('health_worker');
}

/**
 * Check if user is doctor
 */
function isDoctor() {
    return hasRole('doctor');
}

/**
 * Check if user is client
 */
function isClient() {
    return isClientLoggedIn();
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    $userRole = getUserRole();
    return in_array($userRole, $roles);
}

/**
 * Require specific role(s) or redirect
 */
function requireRole($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    if (!hasAnyRole($roles)) {
        // Check if user is logged in but has wrong role
        if (isLoggedIn()) {
            header("Location: " . getBasePath() . "/system/security/admin_client_unauthorized_access_control.php?required_role=" . implode(',', $roles));
        } else {
            header("Location: " . getBasePath() . "/system/security/admin_client_unauthorized_access_control.php");
        }
        exit;
    }
}

/**
 * Get base path for redirects
 */
function getBasePath() {
    $script_path = $_SERVER['SCRIPT_NAME'];
    $path_parts = explode('/', $script_path);
    
    // Count how many directories deep we are
    $depth = count($path_parts) - 2; // -2 for script name and empty first element
    
    // If we're in a subdirectory, go up
    if ($depth > 1) {
        return str_repeat('../', $depth - 1);
    }
    
    return '.';
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireRole('admin');
}

/**
 * Require health staff (admin, doctor, or health_worker)
 */
function requireHealthStaff() {
    requireRole(['admin', 'doctor', 'health_worker']);
}

/**
 * Require client role
 */
function requireClient() {
    if (!isClientLoggedIn()) {
        header("Location: " . getBasePath() . "/client_login.php");
        exit;
    }
}

/**
 * Get display name for role
 */
function getRoleDisplayName($role) {
    $roleNames = [
        'admin' => 'Administrator',
        'doctor' => 'Doctor',
        'health_worker' => 'Health Worker',
        'client' => 'Client'
    ];
    
    return $roleNames[$role] ?? ucfirst($role);
}

/**
 * Check if user can access a specific feature
 */
function canAccess($feature) {
    // Define feature permissions
    $permissions = [
        'admin_panel' => ['admin'],
        'user_management' => ['admin'],
        'appointments' => ['admin', 'doctor', 'health_worker'],
        'client_appointments' => ['client'],
        'schedules' => ['admin', 'doctor', 'health_worker'],
        'reports' => ['admin', 'doctor']
    ];
    
    if (!isset($permissions[$feature])) {
        return false;
    }
    
    if (isClient()) {
        return in_array('client', $permissions[$feature]);
    }
    
    return hasAnyRole($permissions[$feature]);
}
?> 