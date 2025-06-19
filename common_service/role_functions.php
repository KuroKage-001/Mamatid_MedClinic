<?php
// Role-based access control functions

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get user role
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Check if user has specific role
function hasRole($role) {
    return getUserRole() === $role;
}

// Check if user is admin
function isAdmin() {
    return hasRole('admin');
}

// Check if user is health worker
function isHealthWorker() {
    return hasRole('health_worker');
}

// Check if user is doctor
function isDoctor() {
    return hasRole('doctor');
}

// Check if user has any of the specified roles
function hasAnyRole($roles) {
    $userRole = getUserRole();
    return in_array($userRole, $roles);
}

// Restrict access based on roles
function requireRole($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    if (!isLoggedIn() || !hasAnyRole($roles)) {
        header("Location: access_denied.php");
        exit();
    }
}

// Restrict page to admin only
function requireAdmin() {
    requireRole(['admin']);
}

// Restrict page to health workers and doctors
function requireHealthStaff() {
    requireRole(['health_worker', 'doctor']);
}

// Get role display name
function getRoleDisplayName($role) {
    $roleNames = [
        'admin' => 'Administrator',
        'health_worker' => 'Health Worker',
        'doctor' => 'Doctor'
    ];
    return isset($roleNames[$role]) ? $roleNames[$role] : 'Unknown';
}

// Check if user can access specific feature
function canAccess($feature) {
    $permissions = [
        'admin' => [
            'users_management',
            'health_worker_management',
            'doctor_management',
            'reports_full',
            'inventory_management',
            'patient_management',
            'appointments_management',
            'time_tracking',
            'account_settings'
        ],
        'health_worker' => [
            'patient_management',
            'appointments_management',
            'inventory_view',
            'reports_limited',
            'account_settings',
            'time_tracking'
        ],
        'doctor' => [
            'patient_management',
            'appointments_management',
            'inventory_management',
            'reports_full',
            'account_settings',
            'time_tracking'
        ]
    ];
    
    $userRole = getUserRole();
    return isset($permissions[$userRole]) && in_array($feature, $permissions[$userRole]);
}
?> 