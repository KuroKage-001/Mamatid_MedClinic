<?php
// Determine if we're in a subdirectory by checking the script path
$in_subdirectory = (strpos($_SERVER['SCRIPT_NAME'], '/system/') !== false);
$base_path = $in_subdirectory ? '../..' : '.';

// Include session isolation functions to prevent admin/client session conflicts
require_once $base_path . '/system/security/admin_client_session_isolation.php';
// Include role functions for access control
require_once $base_path . '/system/utilities/admin_client_role_functions_services.php';
// Include session configuration to prevent undefined variable errors
require_once $base_path . '/config/admin_session_config.php';

// Initialize secure session
initializeSecureSession();
// Validate session integrity
validateSessionIntegrity();
?>
<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-dark fixed-top">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link menu-trigger" data-widget="pushmenu" href="#" role="button">
                <div class="menu-trigger-content">
                    <i class="fas fa-bars"></i>
                </div>
            </a>
        </li>
    </ul>

    <!-- Brand -->
    <a href="<?php echo $base_path; ?>/admin_dashboard.php" class="navbar-brand">
        <div class="brand-container">
            <div class="brand-icon-wrapper">
                <img src="<?php echo $base_path; ?>/dist/img/logo01.png" alt="MHC Logo" class="brand-image">
            </div>
            <span class="brand-text">Mamatid Health Center</span>
        </div>
    </a>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Clock -->
        <li class="nav-item clock-container">
            <div class="clock-widget">
                <div class="clock-icon">
                    <i class="far fa-clock"></i>
                </div>
                <div class="clock-info">
                    <div id="digital-clock"></div>
                    <div id="date-display"></div>
                </div>
            </div>
        </li>

        <!-- User Menu -->
        <li class="nav-item dropdown user-menu">
            <a class="nav-link user-panel" data-toggle="dropdown" href="#" aria-expanded="false">
                <div class="user-avatar">
                    <img src="<?php echo $base_path; ?>/system/user_images/<?php echo $_SESSION['profile_picture']; ?>?v=<?php echo $_SESSION['profile_picture_timestamp'] ?? time(); ?>" class="user-image" alt="User Image" onerror="this.src='<?php echo $base_path; ?>/system/user_images/default_profile.jpg'">
                    <span class="status-indicator <?php echo isset($_SESSION['status']) && $_SESSION['status'] == 'active' ? 'status-online' : 'status-offline'; ?>"></span>
                </div>
                <div class="user-info d-none d-md-block">
                    <span class="user-name"><?php echo $_SESSION['display_name']; ?></span>
                    <span class="user-role"><?php echo getRoleDisplayName($_SESSION['role']); ?></span>
                </div>
                <!-- Switch Account Icon -->
                <button class="switch-account-btn" onclick="openSwitchAccountModal(event)" title="Switch Account">
                    <i class="fas fa-user-nurse"></i>
                </button>
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right dropdown-menu-dark">
                <div class="user-header">
                    <div class="user-header-bg"></div>
                    <div class="user-header-content">
                        <img src="<?php echo $base_path; ?>/system/user_images/<?php echo $_SESSION['profile_picture']; ?>?v=<?php echo $_SESSION['profile_picture_timestamp'] ?? time(); ?>" class="profile-img" alt="User Image" onerror="this.src='<?php echo $base_path; ?>/system/user_images/default_profile.jpg'">
                        <div class="user-details">
                            <h6><?php echo $_SESSION['display_name']; ?></h6>
                            <span class="username">@<?php echo $_SESSION['user_name']; ?></span>
                            <div class="role-badge role-<?php echo $_SESSION['role']; ?>"><?php echo getRoleDisplayName($_SESSION['role']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <div class="dropdown-body">
                    <a href="#" class="dropdown-item" onclick="openSwitchAccountModal(event)">
                        <i class="fas fa-user-nurse mr-2"></i>
                        <span>Switch Account</span>
                    </a>
                    <?php if (isAdmin() || isDoctor() || isHealthWorker()): ?>
                    <a href="<?php echo $base_path; ?>/account_admin_settings.php" class="dropdown-item">
                        <i class="fas fa-user-md mr-2"></i>
                        <span>Account Settings</span>
                    </a>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                    <a href="<?php echo $base_path; ?>/admin_users_management.php" class="dropdown-item">
                        <i class="fas fa-user-shield mr-2"></i>
                        <span>Manage Users</span>
                    </a>
                    <a href="<?php echo $base_path; ?>/admin_employee_time_tracker.php" class="dropdown-item">
                        <i class="fas fa-user-clock mr-2"></i>
                        <span>My Attendance</span>
                    </a>
                    <?php endif; ?>
                    <?php if (isDoctor() || isHealthWorker()): ?>
                    <a href="<?php echo $base_path; ?>/admin_employee_time_tracker.php" class="dropdown-item">
                        <i class="fas fa-user-clock mr-2"></i>
                        <span>My Attendance</span>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="dropdown-divider"></div>
                <div class="dropdown-footer">
                    <a href="<?php echo $base_path; ?>/logout.php" class="btn btn-danger btn-block">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>       
        </li>
    </ul>
</nav>

<!-- Switch Account Modal -->
<div class="modal fade" id="switchAccountModal" tabindex="-1" role="dialog" aria-labelledby="switchAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered switch-account-dialog" role="document">
        <div class="modal-content switch-account-content">
            <div class="modal-header switch-account-header">
                <div class="header-content">
                    <div class="switch-icon">
                        <i class="fas fa-user-nurse"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="modal-title" id="switchAccountModalLabel">Switch Account</h5>
                        <p class="subtitle">Switch to a different user account</p>
                    </div>
                </div>
                <button type="button" class="close-btn" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body switch-account-body">
                <form id="switchAccountForm" method="post" action="#" onsubmit="return false;">
                    <div class="current-user">
                        <img src="<?php echo $base_path; ?>/system/user_images/<?php echo $_SESSION['profile_picture']; ?>?v=<?php echo $_SESSION['profile_picture_timestamp'] ?? time(); ?>" class="current-user-img" alt="Current User" onerror="this.src='<?php echo $base_path; ?>/system/user_images/default_profile.jpg'">
                        <div class="current-user-info">
                            <span class="current-name"><?php echo $_SESSION['display_name']; ?></span>
                            <span class="current-username">@<?php echo $_SESSION['user_name']; ?></span>
                        </div>
                        <span class="current-badge">Current</span>
                    </div>
                    
                    <div class="switch-divider">
                        <span>Switch to</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="switch_username" class="form-label">
                            <i class="fas fa-user-md"></i>
                            Username
                        </label>
                        <input type="text" class="form-control switch-input" id="switch_username" name="username" placeholder="Enter username" required autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label for="switch_password" class="form-label">
                            <i class="fas fa-key"></i>
                            Password
                        </label>
                        <div class="password-input-group">
                            <input type="password" class="form-control switch-input" id="switch_password" name="password" placeholder="Enter password" required autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="toggleSwitchPassword()">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="save-login-option">
                            <input type="checkbox" id="save_login" name="save_login" class="save-checkbox">
                            <label for="save_login" class="save-label">
                                <span class="checkmark">
                                    <i class="fas fa-check"></i>
                                </span>
                                <span class="save-text">Save login info</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="switch-actions">
                        <button type="button" class="btn btn-cancel" data-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-switch">
                            <i class="fas fa-exchange-alt mr-2"></i>
                            Switch Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Header Variables */
:root {
    --header-bg: #1a1a2d;
    --header-light: #262638;
    --header-hover: rgba(54, 153, 255, 0.1);
    --text-primary: #ffffff;
    --text-secondary: rgba(255, 255, 255, 0.7);
    --text-muted: #B5B5C3;
    --accent-color: #3699FF;
    --accent-light: #4dabff;
    --accent-dark: #187DE4;
    --danger-color: #F64E60;
    --success-color: #1BC5BD;
    --dropdown-bg: #2D2D3A;
    --border-color: rgba(255, 255, 255, 0.08);
    --transition-speed: 0.3s;
    --transition-smooth: cubic-bezier(0.4, 0, 0.2, 1);
    --shadow-light: 0 2px 8px rgba(0, 0, 0, 0.1);
    --shadow-medium: 0 4px 12px rgba(0, 0, 0, 0.15);
    --shadow-heavy: 0 8px 24px rgba(0, 0, 0, 0.2);
    --glow-effect: 0 0 20px rgba(54, 153, 255, 0.3);
}

/* Main Header Styling */
.main-header {
    background: linear-gradient(135deg, var(--header-bg) 0%, var(--header-light) 100%);
    border-bottom: 1px solid var(--border-color);
    padding: 0.5rem 1rem;
    height: 70px;
    box-shadow: var(--shadow-heavy);
    position: relative;
    z-index: 1000;
}

.main-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 10% 20%, rgba(54, 153, 255, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 90% 80%, rgba(54, 153, 255, 0.02) 0%, transparent 50%);
    pointer-events: none;
    z-index: -1;
}

/* Menu Trigger Button */
.menu-trigger {
    padding: 0;
    border-radius: 12px;
    transition: all var(--transition-speed) var(--transition-smooth);
    background: transparent;
    border: 1px solid transparent;
    position: relative;
    overflow: hidden;

.menu-trigger:hover .menu-trigger-content {
    background: var(--header-hover);
    border-color: rgba(54, 153, 255, 0.3);
    transform: scale(1.05);
    box-shadow: var(--glow-effect);
}

.menu-trigger i {
    font-size: 1.3rem;
    color: var(--text-primary);
    transition: all var(--transition-speed) var(--transition-smooth);
}

.menu-trigger:hover i {
    color: var(--accent-color);
    text-shadow: 0 0 10px currentColor;
}

/* Brand Styling */
.navbar-brand {
    display: flex;
    align-items: center;
    padding: 0;
    margin-right: 2rem;
    transition: transform var(--transition-speed);
}

.navbar-brand:hover {
    transform: scale(1.02);
}

.brand-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.brand-icon-wrapper {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.2) 0%, rgba(54, 153, 255, 0.1) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(54, 153, 255, 0.3);
    backdrop-filter: blur(10px);
    box-shadow: var(--shadow-medium);
}

.brand-image {
    height: 28px;
    width: auto;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.brand-text {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--text-primary);
    letter-spacing: 0.5px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.clock-widget {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-color);
    backdrop-filter: blur(10px);
    transition: all var(--transition-speed) var(--transition-smooth);
    gap: 0.75rem;
}

.clock-widget:hover {
    background: var(--header-hover);
    border-color: rgba(54, 153, 255, 0.3);
    box-shadow: var(--shadow-medium);
    transform: translateY(-1px);
}

.clock-icon {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-light) 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--glow-effect);
}

.clock-icon i {
    font-size: 1.2rem;
    color: white;
}

.clock-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

#digital-clock {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.2;
    white-space: nowrap;
    color: var(--text-primary);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

#date-display {
    font-size: 0.75rem;
    color: var(--text-secondary);
    white-space: nowrap;
    margin-top: 2px;
    font-weight: 500;
}

/* User Menu */
.user-menu .nav-link {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    transition: all var(--transition-speed) var(--transition-smooth);
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-color);
    backdrop-filter: blur(10px);
    gap: 0.75rem;
}

.user-menu .nav-link:hover {
    background: var(--header-hover);
    border-color: rgba(54, 153, 255, 0.3);
    box-shadow: var(--shadow-medium);
    transform: translateY(-1px);

.user-image {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.15);
    transition: all var(--transition-speed) var(--transition-smooth);
    box-shadow: var(--shadow-medium);
}

.user-menu .nav-link:hover .user-image {
    transform: scale(1.05);
    border-color: rgba(54, 153, 255, 0.5);
    box-shadow: var(--glow-effect);
}

.status-indicator {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid var(--header-bg);
    animation: pulse-status 2s infinite;
}

@keyframes pulse-status {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.status-online {
    background-color: var(--success-color);
    box-shadow: 0 0 10px rgba(27, 197, 189, 0.5);
}

.status-offline {
    background-color: var(--danger-color);
    box-shadow: 0 0 10px rgba(246, 78, 96, 0.5);
}

.user-info {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    color: var(--text-secondary);
    font-size: 0.8rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dropdown-arrow {
    font-size: 0.7rem;
    color: var(--text-secondary);
    transition: transform var(--transition-speed);
    margin-left: auto;
}

.user-menu .nav-link[aria-expanded="true"] .dropdown-arrow {
    transform: rotate(180deg);
    color: var(--accent-color);
}

/* Switch Account Button */
.switch-account-btn {
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.15) 0%, rgba(54, 153, 255, 0.08) 100%);
    border: 1px solid rgba(54, 153, 255, 0.3);
    color: var(--accent-color);
    padding: 8px 10px;
    border-radius: 8px;
    transition: all var(--transition-speed) var(--transition-smooth);
    font-size: 0.9rem;
    backdrop-filter: blur(5px);
}

.switch-account-btn:hover {
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.25) 0%, rgba(54, 153, 255, 0.15) 100%);
    border-color: rgba(54, 153, 255, 0.5);
    color: var(--accent-light);
    transform: scale(1.1);
    box-shadow: var(--glow-effect);
}

.switch-account-btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(54, 153, 255, 0.3);
}

/* Dropdown Menus */
.dropdown-menu-dark {
    background: var(--dropdown-bg);
    border: none;
    border-radius: 16px;
    box-shadow: var(--shadow-heavy);
    padding: 0;
    margin-top: 0.75rem;
    min-width: 260px;
    border: 1px solid var(--border-color);
    backdrop-filter: blur(20px);
    overflow: hidden;
}

.dropdown-divider {
    border-top: 1px solid var(--border-color);
    margin: 0.5rem 0;
}

.dropdown-item {
    color: var(--text-primary);
    padding: 0.75rem 1.25rem;
    font-size: 0.95rem;
    transition: all var(--transition-speed) var(--transition-smooth);
    border-radius: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dropdown-item:hover {
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.15) 0%, rgba(54, 153, 255, 0.08) 100%);
    color: var(--text-primary);
    transform: translateX(4px);
}

.dropdown-item i {
    width: 20px;
    text-align: center;
    color: var(--accent-color);
    transition: all var(--transition-speed);
}

.dropdown-item:hover i {
    transform: scale(1.1);
    text-shadow: 0 0 10px currentColor;
}

/* User Dropdown Header */
.user-header {
    position: relative;
    padding: 1.5rem 1.25rem;
    text-align: center;
    overflow: hidden;
}

.user-header-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.1) 0%, rgba(105, 147, 255, 0.05) 100%);
    z-index: 0;
}

.user-header-content {
    position: relative;
    z-index: 1;
}

.profile-img {
    width: 70px;
    height: 70px;
    border-radius: 16px;
    object-fit: cover;
    border: 3px solid rgba(255, 255, 255, 0.15);
    margin-bottom: 12px;
    box-shadow: var(--shadow-heavy);
    transition: transform var(--transition-speed);
}

.profile-img:hover {
    transform: scale(1.05);
}

.user-details h6 {
    margin: 8px 0 4px 0;
    font-weight: 700;
    color: var(--text-primary);
    font-size: 1.1rem;
}

.username {
    display: block;
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin-bottom: 8px;
    font-weight: 500;
}

.role-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 8px;
    border: 1px solid;
    backdrop-filter: blur(10px);
}

.role-admin {
    background: linear-gradient(135deg, rgba(137, 80, 252, 0.2) 0%, rgba(137, 80, 252, 0.1) 100%);
    color: #8950FC;
    border-color: rgba(137, 80, 252, 0.3);
}

.role-doctor {
    background: linear-gradient(135deg, rgba(255, 168, 0, 0.2) 0%, rgba(255, 168, 0, 0.1) 100%);
    color: #FFA800;
    border-color: rgba(255, 168, 0, 0.3);
}

.role-health_worker {
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.2) 0%, rgba(54, 153, 255, 0.1) 100%);
    color: var(--accent-color);
    border-color: rgba(54, 153, 255, 0.3);
}

/* Dropdown Body and Footer */
.dropdown-body {
    padding: 0.5rem 0;
}

.dropdown-footer {
    padding: 1rem 1.25rem;
}

.btn-danger {
    background: linear-gradient(135deg, var(--danger-color) 0%, #F1416C 100%);
    border: none;
    font-weight: 600;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    transition: all var(--transition-speed) var(--transition-smooth);
    box-shadow: var(--shadow-medium);
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(246, 78, 96, 0.4);
}

/* Switch Account Modal Styling */
.switch-account-dialog {
    max-width: 450px;
    margin: 1.75rem auto;
}

.switch-account-content {
    background: linear-gradient(135deg, #2D2D3A 0%, #1a1a2d 100%);
    border: none;
    border-radius: 20px;
    box-shadow: var(--shadow-heavy);
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.switch-account-header {
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.1) 0%, rgba(105, 147, 255, 0.05) 100%);
    border-bottom: 1px solid var(--border-color);
    padding: 2rem 2rem 1.5rem 2rem;
    position: relative;
    color: #ffffff;
}

.header-content {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.switch-icon {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-light) 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--glow-effect);
    flex-shrink: 0;
}

.switch-icon i {
    font-size: 1.6rem;
    color: white;
}

.header-text {
    flex: 1;
}

.header-text h5,
.modal-title {
    margin: 0 0 4px 0;
    font-size: 1.6rem;
    font-weight: 700;
    color: #ffffff !important;
    line-height: 1.2;
}

.subtitle {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.95rem;
    font-weight: 500;
}

.close-btn {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: var(--text-secondary);
    padding: 10px;
    border-radius: 10px;
    transition: all var(--transition-speed);
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    backdrop-filter: blur(10px);
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    color: var(--text-primary);
    transform: scale(1.1);
}

.switch-account-body {
    padding: 2rem;
}

.current-user {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.04) 100%);
    border-radius: 16px;
    margin-bottom: 2rem;
    position: relative;
    border: 1px solid var(--border-color);
    backdrop-filter: blur(10px);
}

.current-user-img {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    object-fit: cover;
    margin-right: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.15);
    box-shadow: var(--shadow-medium);
}

.current-user-info {
    flex: 1;
    min-width: 0;
}

.current-name {
    display: block;
    font-weight: 700;
    color: var(--text-primary);
    font-size: 1.05rem;
    line-height: 1.3;
    margin-bottom: 2px;
}

.current-username {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

.current-badge {
    background: linear-gradient(135deg, var(--success-color) 0%, #0BB7B0 100%);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 15px rgba(27, 197, 189, 0.3);
}

.switch-divider {
    text-align: center;
    margin: 2rem 0;
    position: relative;
}

.switch-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, var(--border-color) 50%, transparent 100%);
}

.switch-divider span {
    background: #2D2D3A;
    color: var(--text-secondary);
    padding: 0 20px;
    font-size: 0.95rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: flex;
    align-items: center;
    color: var(--text-primary);
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 0.95rem;
    gap: 0.5rem;
}

.form-label i {
    width: 18px;
    color: var(--accent-color);
    font-size: 1rem;
}

.switch-input {
    background: rgba(255, 255, 255, 0.08);
    border: 2px solid var(--border-color);
    border-radius: 14px;
    color: #ffffff !important;
    padding: 16px 18px;
    font-size: 1rem;
    transition: all var(--transition-speed) var(--transition-smooth);
    width: 100%;
    backdrop-filter: blur(5px);
    font-weight: 500;
}

.switch-input:focus {
    background: rgba(255, 255, 255, 0.12);
    border-color: var(--accent-color);
    box-shadow: 0 0 0 4px rgba(54, 153, 255, 0.15);
    outline: none;
    color: #ffffff !important;
}

.switch-input::placeholder {
    color: var(--text-secondary);
    font-weight: 400;
}

.password-input-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: var(--text-secondary);
    padding: 10px;
    border-radius: 8px;
    transition: all var(--transition-speed);
    cursor: pointer;
    backdrop-filter: blur(5px);
}

.password-toggle:hover {
    color: var(--accent-color);
    background: rgba(54, 153, 255, 0.15);
    transform: translateY(-50%) scale(1.05);
}

.save-login-option {
    display: flex;
    align-items: center;
    margin-top: 1.5rem;
}

.save-checkbox {
    display: none;
}

.save-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
    color: var(--text-primary);
    font-size: 0.95rem;
    font-weight: 500;
    gap: 0.75rem;
}

.checkmark {
    width: 22px;
    height: 22px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-speed) var(--transition-smooth);
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(5px);
}

.save-checkbox:checked + .save-label .checkmark {
    background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-light) 100%);
    border-color: var(--accent-color);
    box-shadow: var(--glow-effect);
}

.checkmark i {
    color: white;
    font-size: 0.8rem;
    opacity: 0;
    transition: opacity var(--transition-speed);
}

.save-checkbox:checked + .save-label .checkmark i {
    opacity: 1;
}

.switch-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn-cancel {
    flex: 1;
    background: rgba(255, 255, 255, 0.08);
    border: 2px solid var(--border-color);
    color: var(--text-primary);
    padding: 14px 24px;
    border-radius: 14px;
    font-weight: 600;
    transition: all var(--transition-speed) var(--transition-smooth);
    backdrop-filter: blur(5px);
}

.btn-cancel:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
    color: var(--text-primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.btn-switch {
    flex: 2;
    background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-light) 100%);
    border: none;
    color: white;
    padding: 14px 24px;
    border-radius: 14px;
    font-weight: 700;
    transition: all var(--transition-speed) var(--transition-smooth);
    box-shadow: var(--glow-effect);
}

.btn-switch:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(54, 153, 255, 0.5);
    color: white;
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
        height: 26px;
    }
    
    .switch-account-btn {
        display: none;
    }
    
    .clock-info {
        display: none;
    }
    
    .clock-widget {
        padding: 0.75rem;
        gap: 0;
    }
    
    .clock-icon {
        width: 32px;
        height: 32px;
    }
    
    .clock-icon i {
        font-size: 1.1rem;
    }
    
    .switch-account-dialog {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    .switch-account-header {
        padding: 1.5rem;
    }
    
    .switch-account-body {
        padding: 1.5rem;
    }
    
    .switch-actions {
        flex-direction: column;
    }
    
    .btn-cancel, .btn-switch {
        flex: 1;
    }
}

/* Enhanced animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-menu-dark {
    animation: fadeInUp 0.3s ease-out;
}

.dropdown-item:nth-child(1) { animation-delay: 0.1s; }
.dropdown-item:nth-child(2) { animation-delay: 0.15s; }
.dropdown-item:nth-child(3) { animation-delay: 0.2s; }
.dropdown-item:nth-child(4) { animation-delay: 0.25s; }
.dropdown-item:nth-child(5) { animation-delay: 0.3s; }

/* Focus states for accessibility */
.menu-trigger:focus,
.user-menu .nav-link:focus,
.dropdown-item:focus {
    outline: 2px solid var(--accent-color);
    outline-offset: 2px;
}
</style>

<script>
// Basic JavaScript debugging - should run immediately
console.log('JavaScript is loading...');
console.log('jQuery available:', typeof $ !== 'undefined');
console.log('Document ready state:', document.readyState);

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
document.addEventListener('DOMContentLoaded', function() {
    updateClock();
    setInterval(updateClock, 1000);
});

// Switch Account Modal Functions
function openSwitchAccountModal(event) {
    event.preventDefault();
    event.stopPropagation();
    
    // Close any open dropdowns
    $('.dropdown-menu').removeClass('show');
    $('.dropdown-toggle').attr('aria-expanded', 'false');
    
    // Load saved login info if available
    loadSavedLoginInfo();
    
    // Open the modal
    $('#switchAccountModal').modal('show');
}

function toggleSwitchPassword() {
    const passwordField = document.getElementById('switch_password');
    const toggleIcon = document.querySelector('.password-toggle i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function loadSavedLoginInfo() {
    // Check if login info is saved in localStorage
    const savedUsername = localStorage.getItem('switchAccountUsername');
    const saveLoginChecked = localStorage.getItem('switchAccountSaveLogin') === 'true';
    
    if (savedUsername && saveLoginChecked) {
        document.getElementById('switch_username').value = savedUsername;
        document.getElementById('save_login').checked = true;
    }
}

function saveSwitchAccountInfo(username, saveLogin) {
    if (saveLogin) {
        localStorage.setItem('switchAccountUsername', username);
        localStorage.setItem('switchAccountSaveLogin', 'true');
    } else {
        localStorage.removeItem('switchAccountUsername');
        localStorage.removeItem('switchAccountSaveLogin');
    }
}

// Add native JavaScript event listener as fallback
document.addEventListener('DOMContentLoaded', function() {
    console.log('Native DOM ready - checking elements');
    
    const form = document.getElementById('switchAccountForm');
    const button = document.querySelector('#switchAccountForm .btn-switch');
    
    console.log('Form found:', !!form);
    console.log('Button found:', !!button);
    
    if (button) {
        button.addEventListener('click', function(e) {
            console.log('Native button click handler triggered');
            e.preventDefault();
            
            const username = document.getElementById('switch_username').value;
            const password = document.getElementById('switch_password').value;
            
            console.log('Username:', username);
            console.log('Password:', password ? '***' : '');
            
            if (!username || !password) {
                alert('Please enter both username and password');
                return false;
            }
            
            // Show loading state
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Switching...';
            
            // Create form data
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            formData.append('save_login', document.getElementById('save_login').checked ? 'on' : '');
            
            console.log('Making AJAX call...');
            
            // Use native fetch instead of jQuery
            fetch('actions/admin_switch_account.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                console.log('Fetch Success:', result);
                
                if (result.success) {
                    // Save login info if requested
                    saveSwitchAccountInfo(username, document.getElementById('save_login').checked);
                    
                    showSwitchAccountToast('success', 'Account switched successfully! Redirecting...');
                    
                    // Close modal
                    $('#switchAccountModal').modal('hide');
                    
                    // Redirect after short delay
                    setTimeout(function() {
                        window.location.href = result.redirect || 'admin_dashboard.php';
                    }, 1500);
                } else {
                    throw new Error(result.message || 'Invalid credentials');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Error: ' + error.message);
                
                // Re-enable button
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-exchange-alt mr-2"></i>Switch Account';
            });
            
            return false;
        });
    }
});

// Switch Account Form Submission
$(document).ready(function() {
    console.log('DOM ready - checking switch account elements');
    console.log('Form element:', $('#switchAccountForm').length);
    console.log('Button element:', $('#switchAccountForm .btn-switch').length);
    console.log('jQuery version:', $.fn.jquery);
    
    // Add multiple event listeners to ensure form submission is prevented
    $('#switchAccountForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Form submit event triggered');
        
        const formData = new FormData(this);
        const username = formData.get('username');
        const password = formData.get('password');
        const saveLogin = formData.get('save_login') === 'on';
        
        console.log('Form data:', {username, password: password ? '***' : '', saveLogin});
        
        // Validation
        if (!username || !password) {
            showSwitchAccountToast('error', 'Please enter both username and password');
            return false;
        }
        
        // Show loading state
        const submitBtn = $(this).find('.btn-switch');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Switching...');
        
        console.log('Starting AJAX call to actions/admin_switch_account.php');
        
        // Submit the form via AJAX
        $.ajax({
            url: 'actions/admin_switch_account.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('AJAX Success:', response);
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    console.log('Parsed result:', result);
                    
                    if (result.success) {
                        // Save login info if requested
                        saveSwitchAccountInfo(username, saveLogin);
                        
                        showSwitchAccountToast('success', 'Account switched successfully! Redirecting...');
                        
                        // Close modal
                        $('#switchAccountModal').modal('hide');
                        
                        // Redirect after short delay
                        setTimeout(function() {
                            window.location.href = result.redirect || 'admin_dashboard.php';
                        }, 1500);
                    } else {
                        throw new Error(result.message || 'Invalid credentials');
                    }
                } catch (error) {
                    console.error('Error processing response:', error);
                    showSwitchAccountToast('error', error.message || 'Invalid credentials');
                    
                    // Re-enable button
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr, status, error});
                let errorMessage = 'Connection error. Please try again.';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    // Use default error message
                }
                
                showSwitchAccountToast('error', errorMessage);
                
                // Re-enable button
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
        
        // Always return false to prevent form submission
        return false;
    });
    
    // Also prevent the submit button from submitting the form
    $('#switchAccountForm .btn-switch').on('click', function(e) {
        e.preventDefault();
        console.log('Switch button clicked');
        $('#switchAccountForm').trigger('submit');
        return false;
    });
    
    // Clear form when modal is hidden
    $('#switchAccountModal').on('hidden.bs.modal', function() {
        $('#switchAccountForm')[0].reset();
        // Reset password field type
        document.getElementById('switch_password').type = 'password';
        document.querySelector('.password-toggle i').className = 'fas fa-eye';
    });
});

function showSwitchAccountToast(type, message) {
    // Create toast notification
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
    
    Toast.fire({
        icon: type,
        title: message
    });
}

// Initialize tooltips for switch account button
$(document).ready(function() {
    $('[title]').tooltip();
});
</script>