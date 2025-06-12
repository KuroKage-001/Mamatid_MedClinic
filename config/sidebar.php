<?php
// Start output buffering to prevent header issues
ob_start();

// Redirect to login page if the session is not set
if (!isset($_SESSION['user_id'])) {
    ob_end_clean(); // Clear any output
    header("location:index.php");
    exit;
}

// Include role functions
require_once './common_service/role_functions.php';
// Include session fix to prevent undefined variable errors
require_once './config/session_fix.php';

// Get the current page filename for active state checking
$current_page = basename($_SERVER['PHP_SELF']);

// Get user role
$user_role = getUserRole();
$role_display_name = getRoleDisplayName($user_role);
?>
<!-- Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link logo-switch bg-primary">
        <h3 class="brand-image-xl logo-xs mb-0 text-center"><b>MHC</b></h3>
        <h3 class="brand-image-xl logo-xl mb-0 text-center">Clinic <b>MHC</b></h3>
    </a>

    <br>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3">
            <div class="user-info-container d-flex align-items-center">
                <!-- User Image -->
                <div class="user-image-container">
                    <img src="user_images/<?php echo $_SESSION['profile_picture']; ?>" class="user-img" alt="User Image" />
                    <span class="user-status-indicator online"></span>
                </div>
                <!-- User Info -->
                <div class="user-info">
                    <a href="#" class="d-block user-display-name"><?php echo $_SESSION['display_name']; ?></a>
                    <span class="user-role"><?php echo getRoleDisplayName($_SESSION['role'] ?? 'admin'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent nav-legacy" data-widget="treeview" role="menu" data-accordion="false">
                
                <!-- Dashboard Menu Item -->
                <li class="nav-item" id="mnu_dashboard">
                    <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php' ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p> Dashboard </p>
                    </a>
                </li>

                <?php if (canAccess('patient_management')): ?>
                <!-- General Menu (Patients & Prescriptions) -->
                <li class="nav-header">PATIENT MANAGEMENT</li>
                <li class="nav-item <?php echo (in_array($current_page, ['family_members.php', 'random_blood_sugar.php', 'deworming.php', 'tetanus_toxoid.php', 'bp_monitoring.php', 'family_planning.php']) ? 'menu-open' : ''); ?>" id="mnu_patients">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['family_members.php', 'random_blood_sugar.php', 'deworming.php', 'tetanus_toxoid.php', 'bp_monitoring.php', 'family_planning.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-user-injured"></i>
                        <p>
                            General
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="family_members.php" class="nav-link <?php echo ($current_page == 'family_members.php' ? 'active' : ''); ?>" id="mi_family_members">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Family Members</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="random_blood_sugar.php" class="nav-link <?php echo ($current_page == 'random_blood_sugar.php' ? 'active' : ''); ?>" id="mi_random_blood_sugar">
                                <i class="far fa-circle nav-icon"></i>
                                <p>RBS</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="deworming.php" class="nav-link <?php echo ($current_page == 'deworming.php' ? 'active' : ''); ?>" id="mi_deworming">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Deworming</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="tetanus_toxoid.php" class="nav-link <?php echo ($current_page == 'tetanus_toxoid.php' ? 'active' : ''); ?>" id="mi_tetanus_toxoid">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Tetanus Toxoid</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="bp_monitoring.php" class="nav-link <?php echo ($current_page == 'bp_monitoring.php' ? 'active' : ''); ?>" id="mi_bp_monitoring">
                                <i class="far fa-circle nav-icon"></i>
                                <p>BP Monitoring</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="family_planning.php" class="nav-link <?php echo ($current_page == 'family_planning.php' ? 'active' : ''); ?>" id="mi_family_planning">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Family Planning</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (canAccess('appointments_management')): ?>
                <li class="nav-header">CLINIC SERVICES</li>
                <!-- Appointments Menu -->
                <li class="nav-item <?php echo (in_array($current_page, ['manage_appointments.php']) ? 'menu-open' : ''); ?>" id="mnu_appointments">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['manage_appointments.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>
                            Appointments
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="manage_appointments.php" class="nav-link <?php echo ($current_page == 'manage_appointments.php' ? 'active' : ''); ?>" id="mi_appointments">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Manage Appointments</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (canAccess('inventory_management') || canAccess('inventory_view')): ?>
                <!-- Inventory Management Menu -->
                <li class="nav-header">INVENTORY MANAGEMENT</li>
                <li class="nav-item <?php echo (in_array($current_page, ['medicines.php', 'medicine_categories.php', 'medicine_stock.php', 'medicine_dispensing.php']) ? 'menu-open' : ''); ?>" id="mnu_inventory">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['medicines.php', 'medicine_categories.php', 'medicine_stock.php', 'medicine_dispensing.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-pills"></i>
                        <p>
                            Medicine Inventory
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="medicine_categories.php" class="nav-link <?php echo ($current_page == 'medicine_categories.php' ? 'active' : ''); ?>" id="mi_medicine_categories">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Categories</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="medicines.php" class="nav-link <?php echo ($current_page == 'medicines.php' ? 'active' : ''); ?>" id="mi_medicines">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Medicines</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="medicine_stock.php" class="nav-link <?php echo ($current_page == 'medicine_stock.php' ? 'active' : ''); ?>" id="mi_medicine_stock">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Stock Management</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="medicine_dispensing.php" class="nav-link <?php echo ($current_page == 'medicine_dispensing.php' ? 'active' : ''); ?>" id="mi_medicine_dispensing">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Dispensing</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (canAccess('reports_full') || canAccess('reports_limited')): ?>
                <li class="nav-header">REPORTS & MANAGEMENT</li>
                <!-- Reports Menu -->
                <li class="nav-item <?php echo (in_array($current_page, ['patient_history.php', 'reports.php']) ? 'menu-open' : ''); ?>" id="mnu_reports">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['patient_history.php', 'reports.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>
                            Reports
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="mnu_patient_history">
                            <a href="patient_history.php" class="nav-link <?php echo ($current_page == 'patient_history.php' ? 'active' : ''); ?>" id="mi_patient_history">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Patient History</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports.php" class="nav-link <?php echo ($current_page == 'reports.php' ? 'active' : ''); ?>" id="mi_reports">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Reports</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                <!-- Users Menu -->
                <li class="nav-item <?php echo (in_array($current_page, ['users.php', 'time_tracker.php']) ? 'menu-open' : ''); ?>" id="mnu_user_management">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['users.php', 'time_tracker.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fa fa-users"></i>
                        <p>
                            Users
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="users.php" class="nav-link <?php echo ($current_page == 'users.php' ? 'active' : ''); ?>" id="mi_users">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Users</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="time_tracker.php" class="nav-link <?php echo ($current_page == 'time_tracker.php' ? 'active' : ''); ?>" id="mi_time_tracker">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Time In|Time Out</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <br>
            </ul>
        </nav>
    </div>
</aside>

<style>
/* Root Variables */
:root {
    --transition-speed: 0.3s;
    --primary-color: #3699FF;
    --hover-color: #187DE4;
    --active-bg: rgba(54, 153, 255, 0.15);
    --text-muted: #B5B5C3;
}

/* Sidebar Container */
.main-sidebar {
    background: #1E1E2D;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    width: 260px;
}

/* Brand Logo */
.brand-link {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    padding: 1rem 1rem !important;
}

.brand-link .logo-xs,
.brand-link .logo-xl {
    color: #fff;
    letter-spacing: 1px;
}

/* User Panel */
.user-info-container {
    padding: 0 1rem;
    position: relative;
}

.user-image-container {
    position: relative;
    margin-right: 1rem;
}

.user-img {
    width: 40px !important;
    height: 40px !important;
    border-radius: 8px !important;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.1);
    transition: transform var(--transition-speed);
}

.user-img:hover {
    transform: scale(1.05);
}

.user-status-indicator {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    background-color: #50CD89;
    border: 2px solid #1E1E2D;
    border-radius: 50%;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-display-name {
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.2rem;
    transition: color var(--transition-speed);
}

.user-display-name:hover {
    color: var(--primary-color);
    text-decoration: none;
}

.user-role {
    color: var(--text-muted);
    font-size: 0.85rem;
}

/* Navigation */
.nav-header {
    color: var(--text-muted) !important;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.1rem;
    padding: 1.5rem 1rem 0.5rem !important;
}

.nav-sidebar .nav-item {
    margin-bottom: 0.25rem;
}

.nav-sidebar .nav-link {
    color: #9899AC !important;
    padding: 0.75rem 1rem;
    margin: 0 0.5rem;
    border-radius: 0.475rem;
    transition: all var(--transition-speed);
}

.nav-sidebar .nav-link:hover {
    color: #fff !important;
    background-color: rgba(255, 255, 255, 0.05);
}

.nav-sidebar .nav-link.active {
    color: var(--primary-color) !important;
    background-color: var(--active-bg) !important;
}

.nav-sidebar .nav-link .nav-icon {
    color: inherit;
    font-size: 1.15rem;
    margin-right: 0.75rem;
}

/* Treeview */
.nav-treeview {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.nav-treeview .nav-link {
    padding: 0.55rem 1rem;
    font-size: 0.925rem;
}

.nav-treeview .nav-icon {
    font-size: 0.85rem !important;
}

/* Menu Open State */
.nav-sidebar .menu-open > .nav-link {
    color: #fff !important;
    background-color: rgba(255, 255, 255, 0.05) !important;
}



/* Responsive Adjustments */
@media (max-width: 768px) {
    .user-panel {
        padding: 0.5rem;
    }
    
    .user-img {
        width: 35px !important;
        height: 35px !important;
    }
    
    .user-display-name {
        font-size: 0.9rem;
    }
    
    .user-role {
        font-size: 0.75rem;
    }
}
</style>
