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
            <div class="brand-text">
                <h3 class="brand-image-xl logo-xs mb-0"><b>MHC</b></h3>
                <h3 class="brand-image-xl logo-xl mb-0">Clinic <b>MHC</b></h3>
            </div>
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
                <div class="user-actions">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Menu -->
        <nav class="mt-3">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <!-- Dashboard Menu Item -->
                <li class="nav-item" id="mnu_dashboard">
                    <a href="<?php echo $base_path; ?>/admin_dashboard.php" class="nav-link <?php echo ($current_page == 'admin_dashboard.php' ? 'active' : ''); ?>">
                        <div class="nav-item-content">
                            <i class="nav-icon fas fa-clinic-medical"></i>
                            <p>Dashboard</p>
                        </div>
                    </a>
                </li>

                <!-- General Menu (Patients & Prescriptions) -->
                <?php if (isAdmin() || isHealthWorker() || isDoctor()): ?>
                <li class="nav-header">
                    <span>PATIENT MANAGEMENT</span>
                </li>
                <li class="nav-item <?php echo (in_array($current_page, ['general_family_members.php', 'general_rbs.php', 'deworming.php', 'general_deworming.php', 'general_tetanus_toxoid.php', 'general_bp_monitoring.php', 'general_family_planning.php']) ? 'menu-open' : ''); ?>" id="mnu_patients">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['general_family_members.php', 'general_rbs.php', 'deworming.php', 'general_deworming.php', 'general_tetanus_toxoid.php', 'general_bp_monitoring.php', 'general_family_planning.php']) ? 'active' : ''); ?>">
                        <div class="nav-item-content">
                            <i class="nav-icon fas fa-hospital-user"></i>
                            <p>General</p>
                            <i class="right fas fa-angle-left"></i>
                        </div>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_family_members.php" class="nav-link <?php echo ($current_page == 'general_family_members.php' ? 'active' : ''); ?>" id="mi_family_members">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-house-user"></i>
                                    <p>Family Members</p>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_family_planning.php" class="nav-link <?php echo ($current_page == 'general_family_planning.php' ? 'active' : ''); ?>" id="mi_family_planning">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-user-friends"></i>
                                    <p>Family Planning</p>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_deworming.php" class="nav-link <?php echo ($current_page == 'general_deworming.php' || $current_page == 'deworming.php' ? 'active' : ''); ?>" id="mi_deworming">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-tablets"></i>
                                    <p>Deworming</p>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_tetanus_toxoid.php" class="nav-link <?php echo ($current_page == 'general_tetanus_toxoid.php' ? 'active' : ''); ?>" id="mi_general_tetanus_toxoid">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-shield-virus"></i>
                                    <p>Tetanus Toxoid</p>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_bp_monitoring.php" class="nav-link <?php echo ($current_page == 'general_bp_monitoring.php' ? 'active' : ''); ?>" id="mi_bp_monitoring">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-heart"></i>
                                    <p>BP Monitoring</p>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/general_rbs.php" class="nav-link <?php echo ($current_page == 'general_rbs.php' ? 'active' : ''); ?>" id="mi_general_rbs">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-vial"></i>
                                    <p>RBS</p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isAdmin() || isHealthWorker() || isDoctor()): ?>
                <li class="nav-header">
                    <span>CLINIC SERVICES</span>
                </li>
                <!-- Appointments Menu -->
                <li class="nav-item <?php echo (in_array($current_page, ['admin_appointment_management.php', 'admin_doctor_schedule_plotter.php', 'admin_schedule_plotter.php']) ? 'menu-open' : ''); ?>" id="mnu_appointments">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['admin_appointment_management.php', 'admin_doctor_schedule_plotter.php', 'admin_schedule_plotter.php']) ? 'active' : ''); ?>">
                        <div class="nav-item-content">
                            <i class="nav-icon fas fa-stethoscope"></i>
                            <p>Appointments</p>
                            <i class="right fas fa-angle-left"></i>
                        </div>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php if (isAdmin() || isHealthWorker()): ?>
                            <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_hw_schedule_plotter.php" class="nav-link <?php echo ($current_page == 'admin_hw_schedule_plotter.php' ? 'active' : ''); ?>" id="mi_staff_availability">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-user-md"></i>
                                    <p>My Availability</p>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_appointment_management.php" class="nav-link <?php echo ($current_page == 'admin_appointment_management.php' ? 'active' : ''); ?>" id="mi_appointments">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-notes-medical"></i>
                                    <p>Appointments</p>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (isAdmin() || isHealthWorker()): ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_schedule_plotter.php" class="nav-link <?php echo ($current_page == 'admin_schedule_plotter.php' ? 'active' : ''); ?>" id="mi_appointment_plotter">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-calendar-plus"></i>
                                    <p>Schedule Plotter</p>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (isDoctor()): ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_doctor_schedule_plotter.php" class="nav-link <?php echo ($current_page == 'admin_doctor_schedule_plotter.php' ? 'active' : ''); ?>" id="mi_doctor_schedule">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-user-nurse"></i>
                                    <p>My Schedule</p>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isAdmin() || isHealthWorker()): ?>
                <!-- Inventory Management Menu -->
                <li class="nav-header">
                    <span>INVENTORY MANAGEMENT</span>
                </li>
                <li class="nav-item <?php echo (in_array($current_page, ['medicines.php', 'medicine_categories.php', 'medicine_stock.php', 'medicine_dispensing.php']) ? 'menu-open' : ''); ?>" id="mnu_inventory">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['medicines.php', 'medicine_categories.php', 'medicine_stock.php', 'medicine_dispensing.php']) ? 'active' : ''); ?>">
                        <div class="nav-item-content">
                            <i class="nav-icon fas fa-prescription-bottle-alt"></i>
                            <p>Medicine Inventory</p>
                            <i class="right fas fa-angle-left"></i>
                        </div>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/medicine_categories.php" class="nav-link <?php echo ($current_page == 'medicine_categories.php' ? 'active' : ''); ?>" id="mi_medicine_categories">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-layer-group"></i>
                                    <p>Categories</p>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/medicines.php" class="nav-link <?php echo ($current_page == 'medicines.php' ? 'active' : ''); ?>" id="mi_medicines">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-prescription"></i>
                                    <p>Medicines</p>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/medicine_stock.php" class="nav-link <?php echo ($current_page == 'medicine_stock.php' ? 'active' : ''); ?>" id="mi_medicine_stock">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-box-open"></i>
                                    <p>Stock Management</p>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/medicine_dispensing.php" class="nav-link <?php echo ($current_page == 'medicine_dispensing.php' ? 'active' : ''); ?>" id="mi_medicine_dispensing">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-mortar-pestle"></i>
                                    <p>Dispensing</p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isAdmin() || isHealthWorker() || isDoctor()): ?>
                <li class="nav-header">
                    <span>REPORTS & MANAGEMENT</span>
                </li>
                <!-- Reports Menu -->
                <li class="nav-item <?php echo (in_array($current_page, ['patient_history.php', 'admin_report_management.php']) ? 'menu-open' : ''); ?>" id="mnu_reports">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['patient_history.php', 'admin_report_management.php']) ? 'active' : ''); ?>">
                        <div class="nav-item-content">
                            <i class="nav-icon fas fa-file-medical"></i>
                            <p>Reports</p>
                            <i class="fas fa-angle-left right"></i>
                        </div>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="mnu_patient_history">
                            <a href="<?php echo $base_path; ?>/patient_history.php" class="nav-link <?php echo ($current_page == 'patient_history.php' ? 'active' : ''); ?>" id="mi_patient_history">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-file-medical-alt"></i>
                                    <p>Patient History</p>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_report_management.php" class="nav-link <?php echo ($current_page == 'admin_report_management.php' ? 'active' : ''); ?>" id="mi_reports">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-chart-bar"></i>
                                    <p>Reports</p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                <!-- Users Menu -->
                <li class="nav-item <?php echo (in_array($current_page, ['admin_users_management.php', 'admin_employee_time_tracker.php']) ? 'menu-open' : ''); ?>" id="mnu_user_management">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['admin_users_management.php', 'admin_employee_time_tracker.php']) ? 'active' : ''); ?>">
                        <div class="nav-item-content">
                            <i class="nav-icon fas fa-user-md"></i>
                            <p>User Management</p>
                            <i class="fas fa-angle-left right"></i>
                        </div>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_users_management.php" class="nav-link <?php echo ($current_page == 'admin_users_management.php' ? 'active' : ''); ?>" id="mi_users">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-user-shield"></i>
                                    <p>Users</p>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>/admin_employee_time_tracker.php" class="nav-link <?php echo ($current_page == 'admin_employee_time_tracker.php' ? 'active' : ''); ?>" id="mi_time_tracker">
                                <div class="nav-item-content">
                                    <i class="nav-icon-sm fas fa-user-clock"></i>
                                    <p>Attendance</p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <div class="footer-decoration"></div>
        </div>
    </div>
</aside>

<style>
/* Root Variables */
:root {
    --transition-speed: 0.3s;
    --transition-smooth: cubic-bezier(0.4, 0, 0.2, 1);
    --primary-color: #3699FF;
    --primary-light: #4dabff;
    --primary-dark: #187DE4;
    --hover-color: #187DE4;
    --active-bg: rgba(54, 153, 255, 0.15);
    --active-shadow: rgba(54, 153, 255, 0.3);
    --text-muted: #B5B5C3;
    --text-light: #9899AC;
    --sidebar-bg: #1E1E2D;
    --sidebar-light: #262638;
    --sidebar-width: 260px;
    --menu-item-radius: 0.8rem;
    --header-color: #6993FF;
    --border-color: rgba(255, 255, 255, 0.08);
    --shadow-light: 0 2px 8px rgba(0, 0, 0, 0.1);
    --shadow-medium: 0 4px 12px rgba(0, 0, 0, 0.15);
    --shadow-heavy: 0 8px 24px rgba(0, 0, 0, 0.2);
    --glow-effect: 0 0 20px rgba(54, 153, 255, 0.3);
}

/* Sidebar Container */
.main-sidebar {
    background: linear-gradient(180deg, var(--sidebar-bg) 0%, #1a1a2e 100%);
    border-right: 1px solid var(--border-color);
    width: var(--sidebar-width);
    box-shadow: var(--shadow-heavy);
    transition: all var(--transition-speed) var(--transition-smooth);
    overflow-x: hidden;
    position: relative;
}

.main-sidebar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 20%, rgba(54, 153, 255, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(54, 153, 255, 0.02) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.main-sidebar > * {
    position: relative;
    z-index: 1;
}

/* Brand Logo */
.brand-link {
    border-bottom: 1px solid var(--border-color) !important;
    padding: 1.25rem 1rem !important;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    text-align: center;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-medium);
}

.brand-link::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
    transition: transform 0.6s;
    transform: translateX(-100%);
}

.brand-link:hover::before {
    transform: translateX(100%);
}

.brand-logo-container {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
}

.brand-text {
    text-align: center;
}

.brand-link .logo-xs,
.brand-link .logo-xl {
    color: #fff;
    letter-spacing: 1px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    margin: 0;
    font-weight: 700;
}

/* User Panel */
.user-panel {
    padding: 1.5rem 1rem;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 0.5rem;
}

.user-info-container {
    display: flex;
    align-items: center;
    position: relative;
    padding: 0.75rem;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
    border-radius: var(--menu-item-radius);
    border: 1px solid rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    transition: all var(--transition-speed) var(--transition-smooth);
    cursor: pointer;
}

.user-info-container:hover {
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.08) 0%, rgba(54, 153, 255, 0.04) 100%);
    border-color: rgba(54, 153, 255, 0.2);
    box-shadow: var(--shadow-medium);
    transform: translateY(-1px);
}

.user-image-container {
    position: relative;
    margin-right: 0.75rem;
}

.user-img {
    width: 50px !important;
    height: 50px !important;
    border-radius: 12px !important;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.15);
    transition: all var(--transition-speed) var(--transition-smooth);
    box-shadow: var(--shadow-medium);
}

.user-img:hover {
    transform: scale(1.05);
    box-shadow: var(--shadow-heavy);
    border-color: rgba(54, 153, 255, 0.5);
}

.user-status-indicator {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 14px;
    height: 14px;
    border: 2px solid var(--sidebar-bg);
    border-radius: 50%;
    animation: pulse-status 2s infinite;
}

@keyframes pulse-status {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.user-status-indicator.online {
    background-color: #50CD89;
    box-shadow: 0 0 10px rgba(80, 205, 137, 0.5);
}

.user-status-indicator.offline {
    background-color: #F1416C;
    box-shadow: 0 0 10px rgba(241, 65, 108, 0.5);
}

.user-info {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
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
    color: var(--primary-light);
    text-decoration: none;
}

.user-role-badge {
    display: inline-block;
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.2) 0%, rgba(54, 153, 255, 0.1) 100%);
    border-radius: 20px;
    padding: 0.25rem 0.75rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--primary-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid rgba(54, 153, 255, 0.3);
    backdrop-filter: blur(5px);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-actions {
    margin-left: 0.5rem;
    color: var(--text-muted);
    font-size: 0.8rem;
    transition: all var(--transition-speed);
}

.user-info-container:hover .user-actions {
    color: var(--primary-color);
    transform: translateX(2px);
}

/* Navigation Headers */
.nav-header {
    color: var(--header-color) !important;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1rem;
    padding: 1.5rem 1.25rem 0.75rem !important;
    margin-top: 0.5rem;
    position: relative;
}

.nav-header span {
    position: relative;
    padding-left: 1rem;
}

.nav-header span::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    background: var(--primary-color);
    border-radius: 50%;
    box-shadow: var(--glow-effect);
}

/* Navigation Items */
.nav-sidebar .nav-item {
    margin-bottom: 0.3rem;
    padding: 0 0.75rem;
}

.nav-item-content {
    display: flex;
    align-items: center;
    width: 100%;
}

.nav-sidebar .nav-link {
    color: var(--text-light) !important;
    padding: 0.9rem 1rem;
    border-radius: var(--menu-item-radius);
    transition: all var(--transition-speed) var(--transition-smooth);
    position: relative;
    overflow: hidden;
    border: 1px solid transparent;
    background: transparent;
}

.nav-sidebar .nav-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-light) 100%);
    opacity: 0;
    transition: all var(--transition-speed) var(--transition-smooth);
    border-radius: 0 4px 4px 0;
}

.nav-sidebar .nav-link::after {
    content: '';
    position: absolute;
    top: 50%;
    right: 1rem;
    transform: translateY(-50%) scale(0);
    width: 6px;
    height: 6px;
    background: var(--primary-color);
    border-radius: 50%;
    transition: all var(--transition-speed) var(--transition-smooth);
    box-shadow: var(--glow-effect);
}

.nav-sidebar .nav-link:hover {
    color: #fff !important;
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.08) 0%, rgba(54, 153, 255, 0.04) 100%);
    border-color: rgba(54, 153, 255, 0.2);
    transform: translateX(4px);
    box-shadow: var(--shadow-medium);
}

.nav-sidebar .nav-link:hover::before {
    opacity: 1;
}

.nav-sidebar .nav-link:hover::after {
    transform: translateY(-50%) scale(1);
}

.nav-sidebar .nav-link.active {
    color: #fff !important;
    background: linear-gradient(135deg, var(--active-bg) 0%, rgba(54, 153, 255, 0.08) 100%);
    font-weight: 600;
    border-color: rgba(54, 153, 255, 0.3);
    box-shadow: var(--glow-effect);
}

.nav-sidebar .nav-link.active::before {
    opacity: 1;
}

.nav-sidebar .nav-link.active::after {
    transform: translateY(-50%) scale(1);
}

.nav-sidebar .nav-link .nav-icon {
    color: inherit;
    font-size: 1.15rem;
    margin-right: 0.75rem;
    text-align: center;
    width: 1.8rem;
    transition: all var(--transition-speed) var(--transition-smooth);
}

.nav-sidebar .nav-link:hover .nav-icon {
    transform: scale(1.1);
    text-shadow: 0 0 10px currentColor;
}

.nav-sidebar .nav-link p {
    margin: 0;
    flex: 1;
    font-weight: inherit;
}

.nav-sidebar .nav-link .right {
    transition: transform var(--transition-speed) var(--transition-smooth);
    font-size: 0.9rem;
    margin-left: auto;
}

.nav-sidebar .menu-open > .nav-link .right {
    transform: rotate(-90deg);
}

/* Treeview */
.nav-treeview {
    margin: 0.25rem 0 0.5rem 0;
    padding-left: 1.5rem;
    border-left: 2px solid rgba(54, 153, 255, 0.2);
    margin-left: 1.5rem;
    position: relative;
}

.nav-treeview::before {
    content: '';
    position: absolute;
    left: -2px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, var(--primary-color) 0%, transparent 100%);
    opacity: 0;
    transition: opacity var(--transition-speed);
}

.nav-sidebar .menu-open .nav-treeview::before {
    opacity: 1;
}

.nav-treeview .nav-link {
    padding: 0.7rem 1rem;
    font-size: 0.9rem;
    margin-bottom: 0.2rem;
    border-radius: 8px;
}

.nav-treeview .nav-link:hover {
    color: #fff !important;
    background: rgba(255, 255, 255, 0.08) !important;
    border-color: rgba(255, 255, 255, 0.1);
    transform: translateX(4px);
    box-shadow: var(--shadow-light);
}

.nav-treeview .nav-link.active {
    color: #fff !important;
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.1) 0%, rgba(54, 153, 255, 0.05) 100%);
    border-color: rgba(54, 153, 255, 0.25);
    font-weight: 500;
}

.nav-icon-sm {
    font-size: 0.95rem !important;
    margin-right: 0.75rem;
    width: 1.5rem;
    text-align: center;
    opacity: 0.8;
    transition: all var(--transition-speed);
}

.nav-treeview .nav-link:hover .nav-icon-sm {
    opacity: 1;
    transform: scale(1.1);
}

/* Menu Open State */
.nav-sidebar .menu-open > .nav-link {
    color: #fff !important;
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.08) 0%, rgba(54, 153, 255, 0.04) 100%) !important;
    border-color: rgba(54, 153, 255, 0.2);
}

.nav-sidebar .menu-open > .nav-link::before {
    opacity: 1;
}

/* Sidebar Footer */
.sidebar-footer {
    margin-top: auto;
    padding: 1rem;
    border-top: 1px solid var(--border-color);
    background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.1) 100%);
}

.footer-decoration {
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-light) 50%, var(--primary-color) 100%);
    border-radius: 2px;
    opacity: 0.6;
    animation: pulse-decoration 3s infinite;
}

@keyframes pulse-decoration {
    0%, 100% { opacity: 0.6; transform: scaleX(1); }
    50% { opacity: 1; transform: scaleX(1.02); }
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .main-sidebar {
        width: 260px;
    }
}

@media (max-width: 768px) {
    .main-sidebar {
        width: 250px;
    }
    
    .user-panel {
        padding: 1rem;
    }
    
    .user-img {
        width: 45px !important;
        height: 45px !important;
    }
    
    .nav-sidebar .nav-link {
        padding: 0.8rem 0.9rem;
    }
}

/* Collapsed Sidebar */
.sidebar-collapse .main-sidebar {
    width: 70px;
}

.sidebar-collapse .brand-text,
.sidebar-collapse .user-info,
.sidebar-collapse .nav-sidebar .nav-link p,
.sidebar-collapse .nav-sidebar .nav-link .right,
.sidebar-collapse .nav-header {
    display: none;
}

.sidebar-collapse .brand-link {
    justify-content: center;
}

.sidebar-collapse .user-info-container {
    justify-content: center;
}

.sidebar-collapse .nav-sidebar .nav-link .nav-icon {
    margin: 0 auto;
    font-size: 1.3rem;
}

/* Smooth Animations */
@keyframes slideInLeft {
    from { transform: translateX(-100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.nav-item {
    animation: slideInLeft 0.3s ease-out;
}

.nav-item:nth-child(1) { animation-delay: 0.1s; }
.nav-item:nth-child(2) { animation-delay: 0.2s; }
.nav-item:nth-child(3) { animation-delay: 0.3s; }
.nav-item:nth-child(4) { animation-delay: 0.4s; }
.nav-item:nth-child(5) { animation-delay: 0.5s; }

/* Focus States for Accessibility */
.nav-sidebar .nav-link:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Custom Scrollbar */
.sidebar::-webkit-scrollbar {
    width: 4px;
}

.sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(54, 153, 255, 0.3);
    border-radius: 2px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(54, 153, 255, 0.5);
}
</style>

