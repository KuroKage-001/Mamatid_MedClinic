<?php
// Start output buffering to prevent header issues
ob_start();

// Redirect to login page if the session is not set
if (!isset($_SESSION['user_id'])) {
    ob_end_clean(); // Clear any output
    header("location:index.php");
    exit;
}

// Determine if we're in a subdirectory by checking the script path
$in_subdirectory = (strpos($_SERVER['SCRIPT_NAME'], '/system/') !== false);
$base_path = $in_subdirectory ? '../..' : '.';

// Include role functions
require_once $base_path . '/system/utilities/admin_client_role_functions_services.php';
// Include session configuration to prevent undefined variable errors
require_once $base_path . '/config/admin_session_config.php';

// Get the current page filename for active state checking
$current_page = basename($_SERVER['PHP_SELF']);

// Get user role
$user_role = getUserRole();
$role_display_name = getRoleDisplayName($user_role);
?>
<!-- Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?php echo $base_path; ?>/admin_dashboard.php" class="brand-link logo-switch">
        <div class="brand-logo-container">
            <h3 class="brand-image-xl logo-xs mb-0"><b>MHC</b></h3>
            <h3 class="brand-image-xl logo-xl mb-0">Clinic <b>MHC</b></h3>
            <div class="brand-spacer"></div>
        </div>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="user-info-container">
                <!-- User Image -->
                <div class="user-image-container">
                    <img src="<?php echo $base_path; ?>/system/user_images/<?php echo $_SESSION['profile_picture']; ?>?v=<?php echo $_SESSION['profile_picture_timestamp'] ?? time(); ?>" class="user-img" alt="User Image" onerror="this.src='<?php echo $base_path; ?>/system/user_images/default_profile.jpg'" />
                    <span class="user-status-indicator <?php echo isset($_SESSION['online_status']) && $_SESSION['online_status'] ? 'online' : 'offline'; ?>"></span>
                </div>
                <!-- User Info -->
                <div class="user-info">
                    <a href="<?php echo $base_path; ?>/account_admin_settings.php" class="user-display-name"><?php echo $_SESSION['display_name']; ?></a>
                    <div class="user-role-badge">
                        <span class="role-text"><?php echo getRoleDisplayName($_SESSION['role'] ?? 'admin'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Menu -->
        <nav class="mt-3">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <!-- Dashboard Menu Item -->
                <li class="nav-item" id="mnu_dashboard">
                    <a href="<?php echo $base_path; ?>/admin_dashboard.php" class="nav-link <?php echo ($current_page == 'admin_dashboard.php' ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-clinic-medical"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- General Menu (Patients & Prescriptions) -->
                <?php if (isAdmin() || isHealthWorker() || isDoctor()): ?>
                <li class="nav-header">PATIENT MANAGEMENT</li>
                <li class="nav-item <?php echo (in_array($current_page, ['general_family_members.php', 'general_rbs.php', 'deworming.php', 'general_deworming.php', 'general_tetanus_toxoid.php', 'general_bp_monitoring.php', 'general_family_planning.php']) ? 'menu-open' : ''); ?>" id="mnu_patients">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['general_family_members.php', 'general_rbs.php', 'deworming.php', 'general_deworming.php', 'general_tetanus_toxoid.php', 'general_bp_monitoring.php', 'general_family_planning.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-hospital-user"></i>
                        <p>
                            General
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_family_members.php" class="nav-link <?php echo ($current_page == 'general_family_members.php' ? 'active' : ''); ?>" id="mi_family_members">
                                <i class="nav-icon-sm fas fa-house-user"></i>
                                <p>Family Members</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_family_planning.php" class="nav-link <?php echo ($current_page == 'general_family_planning.php' ? 'active' : ''); ?>" id="mi_family_planning">
                                <i class="nav-icon-sm fas fa-user-friends"></i>
                                <p>Family Planning</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_deworming.php" class="nav-link <?php echo ($current_page == 'general_deworming.php' || $current_page == 'deworming.php' ? 'active' : ''); ?>" id="mi_deworming">
                                <i class="nav-icon-sm fas fa-tablets"></i>
                                <p>Deworming</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_tetanus_toxoid.php" class="nav-link <?php echo ($current_page == 'general_tetanus_toxoid.php' ? 'active' : ''); ?>" id="mi_general_tetanus_toxoid">
                                <i class="nav-icon-sm fas fa-shield-virus"></i>
                                <p>Tetanus Toxoid</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_bp_monitoring.php" class="nav-link <?php echo ($current_page == 'general_bp_monitoring.php' ? 'active' : ''); ?>" id="mi_bp_monitoring">
                                <i class="nav-icon-sm fas fa-heart"></i>
                                <p>BP Monitoring</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_rbs.php" class="nav-link <?php echo ($current_page == 'general_rbs.php' ? 'active' : ''); ?>" id="mi_general_rbs">
                                <i class="nav-icon-sm fas fa-vial"></i>
                                <p>RBS</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isAdmin() || isHealthWorker() || isDoctor()): ?>
                <li class="nav-header">CLINIC SERVICES</li>
                <!-- Appointments Menu -->
                <li class="nav-item <?php echo (in_array($current_page, ['admin_appointment_management.php', 'admin_doctor_schedule_plotter.php', 'admin_schedule_plotter.php']) ? 'menu-open' : ''); ?>" id="mnu_appointments">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['admin_appointment_management.php', 'admin_doctor_schedule_plotter.php', 'admin_schedule_plotter.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-stethoscope"></i>
                        <p>
                            Appointments
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php if (isAdmin() || isHealthWorker()): ?>
                            <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_hw_schedule_plotter.php" class="nav-link <?php echo ($current_page == 'admin_hw_schedule_plotter.php' ? 'active' : ''); ?>" id="mi_staff_availability">
                                <i class="nav-icon-sm fas fa-user-md"></i>
                                <p>My Availability</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_appointment_management.php" class="nav-link <?php echo ($current_page == 'admin_appointment_management.php' ? 'active' : ''); ?>" id="mi_appointments">
                                <i class="nav-icon-sm fas fa-notes-medical"></i>
                                <p>Appointments</p>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (isAdmin() || isHealthWorker()): ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_schedule_plotter.php" class="nav-link <?php echo ($current_page == 'admin_schedule_plotter.php' ? 'active' : ''); ?>" id="mi_appointment_plotter">
                                <i class="nav-icon-sm fas fa-calendar-plus"></i>
                                <p>Schedule Plotter</p>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (isDoctor()): ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_doctor_schedule_plotter.php" class="nav-link <?php echo ($current_page == 'admin_doctor_schedule_plotter.php' ? 'active' : ''); ?>" id="mi_doctor_schedule">
                                <i class="nav-icon-sm fas fa-user-nurse"></i>
                                <p>My Schedule</p>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isAdmin() || isHealthWorker()): ?>
                <!-- Inventory Management Menu -->
                <li class="nav-header">INVENTORY MANAGEMENT</li>
                <li class="nav-item <?php echo (in_array($current_page, ['medicines.php', 'medicine_categories.php', 'medicine_stock.php', 'medicine_dispensing.php']) ? 'menu-open' : ''); ?>" id="mnu_inventory">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['medicines.php', 'medicine_categories.php', 'medicine_stock.php', 'medicine_dispensing.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-prescription-bottle-alt"></i>
                        <p>
                            Medicine Inventory
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/medicine_categories.php" class="nav-link <?php echo ($current_page == 'medicine_categories.php' ? 'active' : ''); ?>" id="mi_medicine_categories">
                                <i class="nav-icon-sm fas fa-layer-group"></i>
                                <p>Categories</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/medicines.php" class="nav-link <?php echo ($current_page == 'medicines.php' ? 'active' : ''); ?>" id="mi_medicines">
                                <i class="nav-icon-sm fas fa-prescription"></i>
                                <p>Medicines</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/medicine_stock.php" class="nav-link <?php echo ($current_page == 'medicine_stock.php' ? 'active' : ''); ?>" id="mi_medicine_stock">
                                <i class="nav-icon-sm fas fa-box-open"></i>
                                <p>Stock Management</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/medicine_dispensing.php" class="nav-link <?php echo ($current_page == 'medicine_dispensing.php' ? 'active' : ''); ?>" id="mi_medicine_dispensing">
                                <i class="nav-icon-sm fas fa-mortar-pestle"></i>
                                <p>Dispensing</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isAdmin() || isHealthWorker() || isDoctor()): ?>
                <li class="nav-header">REPORTS & MANAGEMENT</li>
                <!-- Reports Menu -->
                <li class="nav-item <?php echo (in_array($current_page, ['patient_history.php', 'admin_report_management.php']) ? 'menu-open' : ''); ?>" id="mnu_reports">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['patient_history.php', 'admin_report_management.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-file-medical"></i>
                        <p>
                            Reports
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="mnu_patient_history">
                            <a href="<?php echo $base_path; ?>/patient_history.php" class="nav-link <?php echo ($current_page == 'patient_history.php' ? 'active' : ''); ?>" id="mi_patient_history">
                                <i class="nav-icon-sm fas fa-file-medical-alt"></i>
                                <p>Patient History</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_report_management.php" class="nav-link <?php echo ($current_page == 'admin_report_management.php' ? 'active' : ''); ?>" id="mi_reports">
                                <i class="nav-icon-sm fas fa-chart-bar"></i>
                                <p>Reports</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                <!-- Users Menu -->
                <li class="nav-item <?php echo (in_array($current_page, ['admin_users_management.php', 'admin_employee_time_tracker.php']) ? 'menu-open' : ''); ?>" id="mnu_user_management">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['admin_users_management.php', 'admin_employee_time_tracker.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-user-md"></i>
                        <p>
                            User Management
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_users_management.php" class="nav-link <?php echo ($current_page == 'admin_users_management.php' ? 'active' : ''); ?>" id="mi_users">
                                <i class="nav-icon-sm fas fa-user-shield"></i>
                                <p>Users</p>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_employee_time_tracker.php" class="nav-link <?php echo ($current_page == 'admin_employee_time_tracker.php' ? 'active' : ''); ?>" id="mi_time_tracker">
                                <i class="nav-icon-sm fas fa-user-clock"></i>
                                <p>Attendance</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
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
    --sidebar-bg: #1E1E2D;
    --sidebar-width: 250px;
    --menu-item-radius: 0.6rem;
    --header-color: #6993FF;
}

/* Sidebar Container */
.main-sidebar {
    background: var(--sidebar-bg);
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    width: var(--sidebar-width);
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
    transition: width var(--transition-speed);
    overflow-x: hidden;
}

/* Brand Logo */
.brand-link {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    padding: 1rem 0.75rem !important;
    background: linear-gradient(135deg, #3699FF 0%, #2563EB 100%);
    text-align: center;
    height: 65px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.brand-logo-container {
    display: flex;
    flex-direction: row;
    align-items: center;
    width: 100%;
    padding-right: 10px;
}

.brand-link .logo-xs,
.brand-link .logo-xl {
    color: #fff;
    letter-spacing: 1px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    margin: 0;
}

.brand-spacer {
    flex-grow: 1;
}

/* User Panel */
.user-panel {
    padding: 1.5rem 1rem 1.5rem 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    margin-bottom: 0.5rem;
}

.user-info-container {
    display: flex;
    align-items: center;
    position: relative;
    padding: 0.5rem;
    padding-left: 0.25rem;
    padding-right: 1rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: var(--menu-item-radius);
    width: calc(100% + 0.5rem);
    margin-right: -0.5rem;
}

.user-image-container {
    position: relative;
    margin-right: 0.75rem;
    margin-left: -0.25rem;
}

.user-img {
    width: 45px !important;
    height: 45px !important;
    border-radius: 10px !important;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.1);
    transition: transform var(--transition-speed), box-shadow var(--transition-speed);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.user-img:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.user-status-indicator {
    position: absolute;
    bottom: -3px;
    right: -3px;
    width: 12px;
    height: 12px;
    border: 2px solid var(--sidebar-bg);
    border-radius: 50%;
}

.user-status-indicator.online {
    background-color: #50CD89;
}

.user-status-indicator.offline {
    background-color: #F1416C;
}

.user-info {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 120px;
}

.user-display-name {
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.4rem;
    transition: color var(--transition-speed);
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-display-name:hover {
    color: var(--primary-color);
    text-decoration: none;
}

.user-role-badge {
    display: inline-block;
    background: rgba(54, 153, 255, 0.15);
    border-radius: 30px;
    padding: 0.2rem 0.8rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--primary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Navigation */
.nav-header {
    color: var(--header-color) !important;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1rem;
    padding: 1.5rem 1.25rem 0.5rem !important;
    margin-top: 0.5rem;
}

.nav-sidebar .nav-item {
    margin-bottom: 0.25rem;
    padding: 0 0.75rem;
}

.nav-sidebar .nav-link {
    color: #9899AC !important;
    padding: 0.85rem 1rem;
    border-radius: var(--menu-item-radius);
    transition: all var(--transition-speed);
    position: relative;
    overflow: hidden;
}

.nav-sidebar .nav-link:before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 0;
    background: var(--primary-color);
    opacity: 0;
    transition: all 0.3s ease;
}

.nav-sidebar .nav-link:hover {
    color: #fff !important;
    background-color: rgba(255, 255, 255, 0.05);
    transform: translateX(3px);
}

.nav-sidebar .nav-link:hover:before {
    width: 4px;
    opacity: 1;
}

.nav-sidebar .nav-link.active {
    color: #fff !important;
    background: linear-gradient(90deg, var(--active-bg), rgba(54, 153, 255, 0.05));
    font-weight: 500;
}

.nav-sidebar .nav-link.active:before {
    width: 4px;
    opacity: 1;
}

.nav-sidebar .nav-link .nav-icon {
    color: inherit;
    font-size: 1.15rem;
    margin-right: 0.75rem;
    text-align: center;
    width: 1.6rem;
    transition: transform var(--transition-speed);
}

.nav-sidebar .nav-link:hover .nav-icon {
    transform: scale(1.1);
}

/* Treeview */
.nav-treeview {
    margin: 0.25rem 0 0.5rem 0;
    padding-left: 1rem;
    border-left: 1px dashed rgba(255, 255, 255, 0.1);
    margin-left: 1rem;
}

.nav-treeview .nav-link {
    padding: 0.6rem 1rem;
    font-size: 0.925rem;
    margin-bottom: 0.2rem;
}

.nav-icon-sm {
    font-size: 0.9rem !important;
    margin-right: 0.75rem;
    width: 1.4rem;
    text-align: center;
    opacity: 0.8;
}

/* Menu Open State */
.nav-sidebar .menu-open > .nav-link {
    color: #fff !important;
    background-color: rgba(255, 255, 255, 0.05) !important;
}

.nav-sidebar .menu-open > .nav-link:before {
    width: 4px;
    opacity: 1;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .main-sidebar {
        width: 200px;
    }
    
    .user-panel {
        padding: 1rem;
    }
}

@media (max-width: 768px) {
    .main-sidebar {
        width: 180px;
    }
    
    .user-panel {
        padding: 0.75rem;
    }
    
    .user-img {
        width: 40px !important;
        height: 40px !important;
    }
    
    .user-display-name {
        font-size: 0.9rem;
    }
    
    .nav-sidebar .nav-link {
        padding: 0.75rem 0.9rem;
    }
    
    .nav-treeview {
        padding-left: 0.75rem;
        margin-left: 0.75rem;
    }
}

/* Collapsed Sidebar (for future implementation) */
.sidebar-collapse .main-sidebar {
    width: 70px;
}

.sidebar-collapse .brand-link .logo-xl {
    display: none;
}

.sidebar-collapse .user-info,
.sidebar-collapse .nav-sidebar .nav-link p,
.sidebar-collapse .nav-sidebar .nav-link .right {
    display: none;
}

.sidebar-collapse .user-image-container {
    margin: 0 auto;
}

.sidebar-collapse .nav-sidebar .nav-link .nav-icon {
    margin: 0 auto;
    font-size: 1.25rem;
}

.sidebar-collapse .nav-header {
    display: none;
}
</style>
