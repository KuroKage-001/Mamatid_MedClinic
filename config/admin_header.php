<?php
// Determine if we're in a subdirectory by checking the script path
$in_subdirectory = (strpos($_SERVER['SCRIPT_NAME'], '/system/') !== false);
$base_path = $in_subdirectory ? '../..' : '.';

// Include role functions for access control
require_once $base_path . '/common_service/role_functions.php';
// Include session fix to prevent undefined variable errors
require_once $base_path . '/config/admin_session_fixer.php';
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
    <a href="<?php echo $base_path; ?>/admin_dashboard.php" class="navbar-brand">
        <img src="<?php echo $base_path; ?>/dist/img/logo01.png" alt="MHC Logo" class="brand-image">
        <span class="brand-text">Mamatid Health Center</span>
    </a>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

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
                    <i class="fas fa-user-friends"></i>
                </button>
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right dropdown-menu-dark">
                <div class="user-header">
                    <img src="<?php echo $base_path; ?>/system/user_images/<?php echo $_SESSION['profile_picture']; ?>?v=<?php echo $_SESSION['profile_picture_timestamp'] ?? time(); ?>" class="profile-img" alt="User Image" onerror="this.src='<?php echo $base_path; ?>/system/user_images/default_profile.jpg'">
                    <div class="user-details">
                        <h6><?php echo $_SESSION['display_name']; ?></h6>
                        <span class="username">@<?php echo $_SESSION['user_name']; ?></span>
                        <div class="role-badge role-<?php echo $_SESSION['role']; ?>"><?php echo getRoleDisplayName($_SESSION['role']); ?></div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <div class="dropdown-body">
                    <a href="#" class="dropdown-item" onclick="openSwitchAccountModal(event)">
                        <i class="fas fa-user-friends mr-2"></i>
                        <span>Switch Account</span>
                    </a>
                    <?php if (isAdmin() || isDoctor() || isHealthWorker()): ?>
                    <a href="<?php echo $base_path; ?>/account_admin_settings.php" class="dropdown-item">
                        <i class="fas fa-user-cog mr-2"></i>
                        <span>Account Settings</span>
                    </a>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                    <a href="<?php echo $base_path; ?>/admin_users_management.php" class="dropdown-item">
                        <i class="fas fa-users mr-2"></i>
                        <span>Manage Users</span>
                    </a>
                    <a href="<?php echo $base_path; ?>/admin_employee_time_tracker.php" class="dropdown-item">
                        <i class="fas fa-clock mr-2"></i>
                        <span>My Attendance</span>
                    </a>
                    <?php endif; ?>
                    <?php if (isDoctor() || isHealthWorker()): ?>
                    <a href="<?php echo $base_path; ?>/admin_employee_time_tracker.php" class="dropdown-item">
                        <i class="fas fa-clock mr-2"></i>
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
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="modal-title" id="switchAccountModalLabel">Switch Account</h5>
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
                            <i class="fas fa-user"></i>
                            Username
                        </label>
                        <input type="text" class="form-control switch-input" id="switch_username" name="username" placeholder="Enter username" required autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label for="switch_password" class="form-label">
                            <i class="fas fa-lock"></i>
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

/* Switch Account Button */
.switch-account-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    padding: 6px 8px;
    margin-left: 8px;
    border-radius: 6px;
    transition: all var(--transition-speed);
    font-size: 0.9rem;
}

.switch-account-btn:hover {
    background: var(--header-hover);
    color: var(--accent-color);
    transform: scale(1.1);
}

.switch-account-btn:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(54, 153, 255, 0.3);
}

/* Switch Account Modal Styling */
.switch-account-dialog {
    max-width: 420px;
    margin: 1.75rem auto;
}

.switch-account-content {
    background: linear-gradient(135deg, #2D2D3A 0%, #1a1a2d 100%);
    border: none;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    overflow: hidden;
}

.switch-account-header {
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.1) 0%, rgba(105, 147, 255, 0.05) 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 24px;
    position: relative;
    color: #ffffff;
}

.header-content {
    display: flex;
    align-items: center;
}

.switch-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--accent-color) 0%, #6993FF 100%);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
    box-shadow: 0 8px 20px rgba(54, 153, 255, 0.3);
}

.switch-icon i {
    font-size: 1.5rem;
    color: white;
}

.header-text {
    flex: 1;
}

.header-text h5,
.modal-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #ffffff !important;
    line-height: 1.2;
}

.subtitle {
    margin: 4px 0 0 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.close-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    padding: 8px;
    border-radius: 8px;
    transition: all var(--transition-speed);
    position: absolute;
    top: 20px;
    right: 20px;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
}

.switch-account-body {
    padding: 28px;
}

.current-user {
    display: flex;
    align-items: center;
    padding: 16px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    margin-bottom: 24px;
    position: relative;
}

.current-user-img {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    object-fit: cover;
    margin-right: 12px;
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.current-user-info {
    flex: 1;
}

.current-name {
    display: block;
    font-weight: 600;
    color: var(--text-primary);
    font-size: 1rem;
    line-height: 1.3;
}

.current-username {
    color: var(--text-secondary);
    font-size: 0.85rem;
}

.current-badge {
    background: linear-gradient(135deg, #1BC5BD 0%, #0BB7B0 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.switch-divider {
    text-align: center;
    margin: 24px 0;
    position: relative;
}

.switch-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
}

.switch-divider span {
    background: #2D2D3A;
    color: var(--text-secondary);
    padding: 0 16px;
    font-size: 0.9rem;
    font-weight: 500;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: flex;
    align-items: center;
    color: var(--text-primary);
    font-weight: 500;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.form-label i {
    margin-right: 8px;
    width: 16px;
    color: var(--accent-color);
}

.switch-input {
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #ffffff !important;
    padding: 14px 16px;
    font-size: 1rem;
    transition: all var(--transition-speed);
    width: 100%;
}

.switch-input:focus {
    background: rgba(255, 255, 255, 0.08);
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(54, 153, 255, 0.2);
    outline: none;
    color: #ffffff !important;
}

.switch-input::placeholder {
    color: var(--text-secondary);
}

.password-input-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-secondary);
    padding: 8px;
    border-radius: 6px;
    transition: all var(--transition-speed);
    cursor: pointer;
}

.password-toggle:hover {
    color: var(--accent-color);
    background: rgba(255, 255, 255, 0.05);
}

.save-login-option {
    display: flex;
    align-items: center;
    margin-top: 20px;
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
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    margin-right: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-speed);
    background: rgba(255, 255, 255, 0.05);
}

.save-checkbox:checked + .save-label .checkmark {
    background: linear-gradient(135deg, var(--accent-color) 0%, #6993FF 100%);
    border-color: var(--accent-color);
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
    gap: 12px;
    margin-top: 28px;
}

.btn-cancel {
    flex: 1;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 500;
    transition: all var(--transition-speed);
}

.btn-cancel:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    color: var(--text-primary);
}

.btn-switch {
    flex: 2;
    background: linear-gradient(135deg, var(--accent-color) 0%, #6993FF 100%);
    border: none;
    color: white;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    transition: all var(--transition-speed);
    box-shadow: 0 4px 15px rgba(54, 153, 255, 0.3);
}

.btn-switch:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(54, 153, 255, 0.4);
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
        height: 30px;
    }
    
    .switch-account-btn {
        display: none;
    }
    
    .switch-account-dialog {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    .switch-account-header {
        padding: 20px;
    }
    
    .switch-account-body {
        padding: 20px;
    }
    
    .switch-actions {
        flex-direction: column;
    }
    
    .btn-cancel, .btn-switch {
        flex: 1;
      }
  }
</style>

<script>
// Basic JavaScript debugging - should run immediately
console.log('JavaScript is loading...');
console.log('jQuery available:', typeof $ !== 'undefined');
console.log('Document ready state:', document.readyState);

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
            fetch('actions/switch_account.php', {
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
        
        console.log('Starting AJAX call to actions/switch_account.php');
        
        // Submit the form via AJAX
        $.ajax({
            url: 'actions/switch_account.php',
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