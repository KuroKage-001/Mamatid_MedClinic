<?php
// Role-based access control functions

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if client is logged in
function isClientLoggedIn() {
    return isset($_SESSION['client_id']);
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

// Check if user is client
function isClient() {
    return isClientLoggedIn();
}

// Check if user has any of the specified roles
function hasAnyRole($roles) {
    // Handle special case for 'client' role
    if (in_array('client', $roles) && isClientLoggedIn()) {
        return true;
    }
    
    // Handle staff roles
    $userRole = getUserRole();
    return in_array($userRole, $roles);
}

// Restrict access based on roles
function requireRole($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }

    // Special handling for client pages
    if (in_array('client', $roles)) {
        // If this is a client-only page and a client is logged in, allow access
        if (isClientLoggedIn()) {
            return true;
        }
        
        // If a staff member is trying to access a client page
        if (isLoggedIn()) {
            header("Location: system/security/access_denied.php?required_role=client");
            exit();
        }
        
        // Not logged in at all
        header("Location: client_login.php");
        exit();
    }
    
    // For staff roles
    if (!isLoggedIn() || !hasAnyRole($roles)) {
        // Build the required role string for display
        $required_role = implode(' or ', $roles);
        
        header("Location: system/security/access_denied.php?required_role=$required_role");
        exit();
    }
    
    return true;
}

// Restrict page to admin only
function requireAdmin() {
    requireRole(['admin']);
}

// Restrict page to health workers and doctors
function requireHealthStaff() {
    requireRole(['health_worker', 'doctor']);
}

// Restrict page to clients only
function requireClient() {
    requireRole(['client']);
}

// Get role display name
function getRoleDisplayName($role) {
    $roleNames = [
        'admin' => 'Administrator',
        'health_worker' => 'Health Worker',
        'doctor' => 'Doctor',
        'client' => 'Client/Patient',
        'appropriate' => 'Appropriate'
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
        ],
        'client' => [
            'book_appointment',
            'view_appointments',
            'client_account_settings'
        ]
    ];
    
    // Check client permissions if applicable
    if (isClientLoggedIn() && isset($permissions['client'])) {
        return in_array($feature, $permissions['client']);
    }
    
    // Check staff permissions
    $userRole = getUserRole();
    return isset($permissions[$userRole]) && in_array($feature, $permissions[$userRole]);
}
?> 