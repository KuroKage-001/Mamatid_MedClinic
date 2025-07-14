<?php
// Include admin-client session isolation functions
require_once '../system/security/admin_client_session_isolation.php';

// Get client profile picture using safe getter
$clientId = getClientSessionVar('client_id');
if (!getClientSessionVar('client_profile_picture') && $clientId) {
    // Fetch profile picture from database
    include '../config/db_connection.php';
    $profileQuery = "SELECT profile_picture FROM clients_user_accounts WHERE id = ?";
    $profileStmt = $con->prepare($profileQuery);
    $profileStmt->execute([$clientId]);
    $profileData = $profileStmt->fetch(PDO::FETCH_ASSOC);
    
    // Set profile picture in session using safe setter
    setClientSessionVar('client_profile_picture', !empty($profileData['profile_picture']) ? $profileData['profile_picture'] : 'default_client.png');
}

// Determine base path for assets
$base_path = '..';

// Add cache-busting timestamp
$timestamp = time();

// Get current page for active menu highlighting
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="client_dashboard.php" class="brand-link logo-switch">
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
                    <img src="<?php echo $base_path; ?>/system/client_images/<?php echo getClientSessionVar('client_profile_picture', 'default_client.png'); ?>?v=<?php echo $timestamp; ?>" class="user-img" alt="User Image" onerror="if(this.src.indexOf('default_client.png')===-1) this.src='<?php echo $base_path; ?>/dist/img/patient-avatar.png';">
                    <span class="user-status-indicator online"></span>
                </div>
                <!-- User Info -->
                <div class="user-info">
                    <a href="account_client_settings.php" class="user-display-name"><?php echo getClientSessionVar('client_name'); ?></a>
                    <div class="user-role-badge">
                        <span class="role-text">Client</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-3">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <!-- Dashboard Menu Item -->
                <li class="nav-item" id="mnu_dashboard">
                    <a href="client_dashboard.php" class="nav-link <?php echo ($current_page == 'client_dashboard.php' ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Client Services Section -->
                <li class="nav-header">CLIENT SERVICES</li>

                <!-- Book Appointment -->
                <li class="nav-item" id="mnu_appointments">
                    <a href="client_appointment_booking.php" class="nav-link <?php echo ($current_page == 'client_appointment_booking.php' ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-calendar-plus"></i>
                        <p>Book Appointment</p>
                    </a>
                </li>

                <!-- Account Management Section -->
                <li class="nav-header">ACCOUNT MANAGEMENT</li>

                <!-- Account Settings -->
                <li class="nav-item" id="mnu_account">
                    <a href="account_client_settings.php" class="nav-link <?php echo ($current_page == 'account_client_settings.php' ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-user-cog"></i>
                        <p>Account Settings</p>
                    </a>
                </li>

                <!-- Logout -->
                <li class="nav-item" id="mnu_logout">
                    <a href="client_logout.php" class="nav-link">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
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
    --client-accent: #1BC5BD;
    --client-hover: #0BB7B0;
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
    background: linear-gradient(135deg, var(--client-accent) 0%, var(--client-hover) 100%);
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
    padding-right: 20px;
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
    padding-right: 1.5rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: var(--menu-item-radius);
    width: calc(100% + 1rem);
    margin-right: -1rem;
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
    min-width: 150px;
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
    color: var(--client-accent);
    text-decoration: none;
}

.user-role-badge {
    display: inline-block;
    background: rgba(27, 197, 189, 0.15);
    border-radius: 30px;
    padding: 0.2rem 0.8rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--client-accent);
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
    background: var(--client-accent);
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
    background: linear-gradient(90deg, rgba(27, 197, 189, 0.15), rgba(27, 197, 189, 0.05));
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
        width: 250px;
    }
    
    .user-panel {
        padding: 1rem;
    }
}

@media (max-width: 768px) {
    .main-sidebar {
        width: 240px;
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

/* Sidebar */
.sidebar {
    padding-top: 1rem !important;
}

.user-panel {
    padding-top: 1rem !important;
    padding-bottom: 0.75rem !important;
    margin-bottom: 0.5rem !important;
    margin-top: 1rem !important;
}

.brand-link {
    margin-bottom: 4rem !important;
}
</style>

<script>
// Force image refresh on load
document.addEventListener('DOMContentLoaded', function() {
    // Add timestamp to force cache refresh
    const timestamp = new Date().getTime();
    
    // Update all profile images
    const images = document.querySelectorAll('.user-img');
    images.forEach(img => {
        // Only update if it's a profile image
        if (img.src.includes('client_images')) {
            const src = img.src.split('?')[0]; // Remove existing query params
            img.src = src + '?v=' + timestamp;
        }
    });
});
</script>