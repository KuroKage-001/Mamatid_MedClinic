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
            <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
                <img src="user_images/<?php echo $_SESSION['profile_picture']; ?>" class="user-image" alt="User Image">
                <span class="d-none d-md-inline">Hello, <?php echo $_SESSION['display_name']; ?>!</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right dropdown-menu-dark">
                <div class="dropdown-header">
                    <strong><?php echo $_SESSION['display_name']; ?></strong>
                    <small class="text-muted d-block"><?php echo $_SESSION['user_name']; ?></small>
                </div>
                <div class="dropdown-divider"></div>
                <?php if (canAccess('account_settings')): ?>
                <a href="account_settings.php" class="dropdown-item">
                    <i class="fas fa-user-cog mr-2"></i>
                    Account Settings
                </a>
                <div class="dropdown-divider"></div>
                <?php endif; ?>
                <a href="logout.php" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Logout
                </a>
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