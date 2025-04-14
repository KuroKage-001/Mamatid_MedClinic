<?php
// Redirect to login page if the session is not set
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Get the current page filename for active state checking
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link logo-switch bg-primary">
        <h4 class="brand-image-xl logo-xs mb-0 text-center"><b>MHC</b></h4>
        <h4 class="brand-image-xl logo-xl mb-0 text-center">Clinic <b>MHC</b></h4>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 position-relative">
            <!-- User Image -->
            <div class="image" style="position: absolute; left: -5%; top: 50%; transform: translateY(-75%); z-index: 1;">
                <img src="user_images/<?php echo $_SESSION['profile_picture']; ?>" class="user-img" alt="User Image" />
            </div>
            <!-- User Info -->
            <div class="info" style="margin-left: 4em; position: relative; z-index: 2;">
                <a href="#" class="d-block user-display-name"><?php echo $_SESSION['display_name']; ?></a>
            </div>
        </div>
        
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <!-- Dashboard Menu Item -->
                <li class="nav-item" id="mnu_dashboard">
                    <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php' ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p> Dashboard </p>
                    </a>
                </li>

                <!-- General Menu (Patients & Prescriptions) -->
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
                                <p>Random Blood Sugar</p>
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
                        <!-- Manage Appointments -->
                        <li class="nav-item">
                            <a href="manage_appointments.php" class="nav-link <?php echo ($current_page == 'manage_appointments.php' ? 'active' : ''); ?>" id="mi_appointments">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Manage Appointments</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Medicines Menu -->
                <li class="nav-item <?php echo (in_array($current_page, ['medicines.php', 'medicine_details.php', 'medicine_inventory.php']) ? 'menu-open' : ''); ?>" id="mnu_medicines">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['medicines.php', 'medicine_details.php', 'medicine_inventory.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-pills"></i>
                        <p>
                            Medicines
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <!-- Add Medicine -->
                        <li class="nav-item">
                            <a href="medicines.php" class="nav-link <?php echo ($current_page == 'medicines.php' ? 'active' : ''); ?>" id="mi_medicines">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Add Medicine</p>
                            </a>
                        </li>
                        <!-- Medicine Details -->
                        <li class="nav-item">
                            <a href="medicine_details.php" class="nav-link <?php echo ($current_page == 'medicine_details.php' ? 'active' : ''); ?>" id="mi_medicine_details">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Medicine Details</p>
                            </a>
                        </li>
                        <!-- Medicine Inventory -->
                        <li class="nav-item">
                            <a href="medicine_inventory.php" class="nav-link <?php echo ($current_page == 'medicine_inventory.php' ? 'active' : ''); ?>" id="mi_medicine_inventory">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Inventory Management</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Reports Menu (Patient History & Reports) -->
                <li class="nav-item <?php echo (in_array($current_page, ['patient_history.php', 'reports.php']) ? 'menu-open' : ''); ?>" id="mnu_reports">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['patient_history.php', 'reports.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-edit"></i>
                        <p>
                            Patient History | Reports
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <!-- Patient History -->
                        <li class="nav-item" id="mnu_patient_history">
                            <a href="patient_history.php" class="nav-link <?php echo ($current_page == 'patient_history.php' ? 'active' : ''); ?>" id="mi_patient_history">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Patient History</p>
                            </a>
                        </li>
                        <!-- Reports -->
                        <li class="nav-item">
                            <a href="reports.php" class="nav-link <?php echo ($current_page == 'reports.php' ? 'active' : ''); ?>" id="mi_reports">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Reports</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Users & Time Tracker Menu -->
                <li class="nav-item <?php echo (in_array($current_page, ['users.php', 'time_tracker.php']) ? 'menu-open' : ''); ?>" id="mnu_user_management">
                    <a href="#" class="nav-link <?php echo (in_array($current_page, ['users.php', 'time_tracker.php']) ? 'active' : ''); ?>">
                        <i class="nav-icon fa fa-users"></i>
                        <p>
                            Users
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <!-- Users -->
                        <li class="nav-item">
                            <a href="users.php" class="nav-link <?php echo ($current_page == 'users.php' ? 'active' : ''); ?>" id="mi_users">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Users</p>
                            </a>
                        </li>
                        <!-- Time In | Time Out -->
                        <li class="nav-item">
                            <a href="time_tracker.php" class="nav-link <?php echo ($current_page == 'time_tracker.php' ? 'active' : ''); ?>" id="mi_time_tracker">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Time In|Time Out</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Logout Menu Item -->
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="nav-icon fa fa-sign-out-alt"></i>
                        <p> Logout </p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

<!-- Additional CSS -->
<style>
/* User image styling */
.user-img {
    width: 3em !important;
    height: 3em !important;
    border-radius: 50% !important;
    object-fit: cover;
    object-position: center;
}

/* Display name styling */
.user-display-name {
    font-size: 1.2rem;
    font-weight: bold;
    color: #fff;
    background: rgba(0, 0, 0, 0.5);
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
    transition: background 0.3s ease;
    display: inline-block;
}
.user-display-name:hover {
    background: rgba(0, 0, 0, 0.7);
}

/* Global active state styling for sidebar links */
.nav-sidebar .nav-link.active {
    background-color: rgba(54, 162, 235, 0.7) !important;
    color: #fff !important;
}

/* Active state for submenu (nav-treeview) links */
.nav-sidebar .nav-treeview .nav-link.active {
    background-color: rgba(54, 162, 235, 0.7) !important;
    color: #fff !important;
}

/* Force parent menu (when open) to display active background */
.nav-sidebar > .menu-open > .nav-link {
    background-color: rgba(54, 162, 235, 0.7) !important;
    color: #fff !important;
}
</style>
