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
?>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">0 Notifications</span>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-envelope mr-2"></i> No new notifications
                </a>
            </div>
        </li>
        
        <!-- User Dropdown Menu -->
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                <img src="<?php echo $base_path; ?>/system/client_images/<?php echo getClientSessionVar('client_profile_picture', 'default_client.png'); ?>?v=<?php echo $timestamp; ?>" class="user-image" alt="User Image" onerror="if(this.src.indexOf('default_client.png')===-1) this.src='<?php echo $base_path; ?>/dist/img/patient-avatar.png';">
                <span class="user-name"><?php echo getClientSessionVar('client_name'); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <!-- User image -->
                <li class="user-header bg-primary">
                    <img src="<?php echo $base_path; ?>/system/client_images/<?php echo getClientSessionVar('client_profile_picture', 'default_client.png'); ?>?v=<?php echo $timestamp; ?>" class="profile-img" alt="User Image" onerror="if(this.src.indexOf('default_client.png')===-1) this.src='<?php echo $base_path; ?>/dist/img/patient-avatar.png';">
                    <h6><?php echo getClientSessionVar('client_name'); ?></h6>
                    <span class="username"><?php echo getClientSessionVar('client_email'); ?></span>
                </li>
                <!-- Menu Footer-->
                <li class="user-footer">
                    <a href="account_client_settings.php" class="btn btn-default btn-flat">Profile</a>
                    <a href="client_logout.php" class="btn btn-default btn-flat float-right">Sign out</a>
                </li>
            </ul>
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

.role-client {
    background-color: rgba(27, 197, 189, 0.15);
    color: #1BC5BD;
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

/* Clock Styling */
.clock-container {
    margin-right: 1.5rem;
    display: flex;
    align-items: center;
    height: 100%;
    padding-top: 0.5rem;
}

.clock-widget {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    border-radius: 8px;
    color: var(--text-primary);
    height: 40px;
}

.clock-widget i {
    font-size: 1.2rem;
    margin-right: 0.75rem;
    color: var(--accent-color);
}

.clock-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

#digital-clock {
    font-size: 1.3rem;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
    color: var(--text-primary);
}

#date-display {
    font-size: 0.8rem;
    color: var(--text-secondary);
    white-space: nowrap;
    margin-top: 2px;
    text-align: center;
}

/* Ensure user menu alignment */
.user-menu {
    display: flex;
    align-items: center;
    height: 100%;
}

.user-panel {
    height: 40px;
}

@media (max-width: 768px) {
    .clock-container {
        margin-right: 0.75rem;
        padding-top: 0.25rem;
    }
    
    .clock-widget {
        padding: 0.5rem;
        height: 40px;
    }
    
    .clock-info {
        display: none;
    }
    
    .clock-widget i {
        margin-right: 0;
        font-size: 1.4rem;
    }
}
</style>

<script>
// Force image refresh on load
document.addEventListener('DOMContentLoaded', function() {
    // Add timestamp to force cache refresh
    const timestamp = new Date().getTime();
    
    // Update all profile images
    const images = document.querySelectorAll('.user-image, .profile-img');
    images.forEach(img => {
        // Only update if it's a profile image
        if (img.src.includes('client_images')) {
            const src = img.src.split('?')[0]; // Remove existing query params
            img.src = src + '?v=' + timestamp;
        }
    });

    // Clock functionality
    function updateClock() {
        const now = new Date();
        
        // Update time in 12-hour format
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        
        // Convert to 12-hour format without leading zeros
        hours = hours % 12;
        hours = hours ? hours : 12; // Convert 0 to 12
        
        document.getElementById('digital-clock').textContent = `${hours}:${minutes} ${ampm}`;
        
        // Update date with shorter format
        const options = { 
            weekday: 'short',
            month: 'short', 
            day: 'numeric'
        };
        document.getElementById('date-display').textContent = now.toLocaleDateString('en-US', options);
    }

    // Update clock immediately and then every second
    updateClock();
    setInterval(updateClock, 1000);
});
</script> 