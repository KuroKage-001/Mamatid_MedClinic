<?php
// Include role functions for access control
require_once './common_service/role_functions.php';
// Include session fix to prevent undefined variable errors
require_once './config/session_fix.php';
?>
<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-dark fixed-top">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link menu-trigger" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <!-- Brand -->
    <a href="dashboard.php" class="navbar-brand">
        <img src="dist/img/logo01.png" alt="MHC Logo" class="brand-image">
        <span class="brand-text">Mamatid Health Center</span>
    </a>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

        <!-- User Menu -->
        <li class="nav-item dropdown user-menu">
            <a class="nav-link user-panel" data-toggle="dropdown" href="#" aria-expanded="false">
                <div class="user-avatar">
                    <img src="user_images/<?php echo $_SESSION['profile_picture']; ?>" class="user-image" alt="User Image" onerror="this.src='user_images/default_profile.jpg'">
                    <span class="status-indicator <?php echo isset($_SESSION['status']) && $_SESSION['status'] == 'active' ? 'status-online' : 'status-offline'; ?>"></span>
                </div>
                <div class="user-info d-none d-md-block">
                    <span class="user-name"><?php echo $_SESSION['display_name']; ?></span>
                    <span class="user-role"><?php echo getRoleDisplayName($_SESSION['role']); ?></span>
                </div>
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right dropdown-menu-dark">
                <div class="user-header">
                    <img src="user_images/<?php echo $_SESSION['profile_picture']; ?>" class="profile-img" alt="User Image" onerror="this.src='user_images/default_profile.jpg'">
                    <div class="user-details">
                        <h6><?php echo $_SESSION['display_name']; ?></h6>
                        <span class="username">@<?php echo $_SESSION['user_name']; ?></span>
                        <div class="role-badge role-<?php echo $_SESSION['role']; ?>"><?php echo getRoleDisplayName($_SESSION['role']); ?></div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <div class="dropdown-body">
                    <?php if (canAccess('account_settings')): ?>
                    <a href="account_settings.php" class="dropdown-item">
                        <i class="fas fa-user-cog mr-2"></i>
                        <span>Account Settings</span>
                    </a>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                    <a href="users.php" class="dropdown-item">
                        <i class="fas fa-users mr-2"></i>
                        <span>Manage Users</span>
                    </a>
                    <?php endif; ?>
                    <a href="dashboard.php" class="dropdown-item">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="dropdown-divider"></div>
                <div class="dropdown-footer">
                    <a href="logout.php" class="btn btn-danger btn-block">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>       
        </li>
    </ul>
</nav>

<style>
/* Header Variables */
:root {
    --header-bg: #1a1a2d;
    --header-hover: #2d2d44;
    --text-primary: #ffffff;
    --text-secondary: rgba(255, 255, 255, 0.7);
    --accent-color: #3699FF;
    --danger-color: #F64E60;
    --dropdown-bg: #2D2D3A;
    --transition-speed: 0.3s;
}

/* Main Header Styling */
.main-header {
    background: var(--header-bg);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    padding: 0.5rem 1rem;
    height: 60px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Menu Trigger Button */
.menu-trigger {
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    transition: all var(--transition-speed);
}

.menu-trigger:hover {
    background: var(--header-hover);
}

.menu-trigger i {
    font-size: 1.2rem;
}

/* Brand Styling */
.navbar-brand {
    display: flex;
    align-items: center;
    padding: 0;
    margin-right: 2rem;
}

.brand-image {
    height: 35px;
    width: auto;
    margin-right: 0.75rem;
}

.brand-text {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    letter-spacing: 0.5px;
}

/* Notification Badge */
.badge-primary {
    background: var(--accent-color);
    font-size: 0.7rem;
    padding: 0.25em 0.5em;
    position: absolute;
    top: 5px;
    right: 3px;
}

/* Dropdown Menus */
.dropdown-menu-dark {
    background: var(--dropdown-bg);
    border: none;
    border-radius: 10px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
    padding: 0.5rem 0;
    margin-top: 0.5rem;
    min-width: 200px;
}

.dropdown-header {
    color: var(--text-secondary);
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

.dropdown-divider {
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    margin: 0.5rem 0;
}

.dropdown-item {
    color: var(--text-primary);
    padding: 0.6rem 1rem;
    font-size: 0.9rem;
    transition: all var(--transition-speed);
}

.dropdown-item:hover {
    background: var(--header-hover);
    color: var(--text-primary);
}

.dropdown-item i {
    width: 20px;
    text-align: center;
}

.dropdown-item.text-danger {
    color: var(--danger-color) !important;
}

.dropdown-item.text-danger:hover {
    background: rgba(246, 78, 96, 0.1);
}

/* User Menu */
.user-menu .nav-link {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    transition: all var(--transition-speed);
}

.user-menu .nav-link:hover {
    background: var(--header-hover);
}

.user-image {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    margin-right: 0.75rem;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.1);
}

/* Enhanced User Menu Styling */
.user-panel {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    border-radius: 10px;
}

.user-avatar {
    position: relative;
    flex-shrink: 0;
}

.status-indicator {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid var(--header-bg);
}

.status-online {
    background-color: #1BC5BD;
}

.status-offline {
    background-color: #B5B5C3;
}

.user-info {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.user-name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.95rem;
}

.user-role {
    color: var(--text-secondary);
    font-size: 0.8rem;
}

.dropdown-arrow {
    font-size: 0.7rem;
    margin-left: 5px;
    color: var(--text-secondary);
    transition: transform var(--transition-speed);
}

.user-menu .nav-link[aria-expanded="true"] .dropdown-arrow {
    transform: rotate(180deg);
}

/* User Dropdown Header */
.user-header {
    padding: 1.25rem;
    text-align: center;
    background: linear-gradient(135deg, rgba(26, 26, 45, 0.5), rgba(45, 45, 68, 0.5));
    border-radius: 10px 10px 0 0;
}

.profile-img {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.user-details h6 {
    margin: 5px 0;
    font-weight: 600;
    color: var(--text-primary);
}

.username {
    display: block;
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin-bottom: 5px;
}

.role-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-top: 5px;
}

.role-admin {
    background-color: rgba(137, 80, 252, 0.15);
    color: #8950FC;
}

.role-doctor {
    background-color: rgba(255, 168, 0, 0.15);
    color: #FFA800;
}

.role-health_worker {
    background-color: rgba(54, 153, 255, 0.15);
    color: #3699FF;
}

/* Dropdown Body and Footer */
.dropdown-body {
    padding: 0.5rem 0;
}

.dropdown-footer {
    padding: 0.75rem 1rem;
}

.btn-danger {
    background: linear-gradient(135deg, var(--danger-color) 0%, #F1416C 100%);
    border: none;
    font-weight: 500;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all var(--transition-speed);
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(246, 78, 96, 0.3);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .brand-text {
        font-size: 1.1rem;
    }
    
    .user-menu .d-none {
        display: none !important;
    }
    
    .navbar-brand {
        margin-right: 1rem;
    }
    
    .brand-image {
        height: 30px;
    }
}
</style>