<?php
include './config/db_connection.php';
require_once './common_service/role_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Only admins can access this page
requireAdmin();

$message = '';
$messageType = 'success';

// Get and validate user ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location:admin_users_management.php?message=" . urlencode("Invalid user ID") . "&type=error");
    exit;
}

$user_id = intval($_GET['id']);

// Prevent editing own account (should use account settings)
if ($_SESSION['user_id'] == $user_id) {
    header("location:account_admin_settings.php?message=" . urlencode("Use Account Settings to edit your own profile") . "&type=info");
    exit;
}

// Query to fetch user details
$query = "SELECT `id`, `display_name`, `user_name`, `email`, `phone`, `role`, `status`, `profile_picture`, `created_at` 
          FROM `users` WHERE `id` = :user_id";

try {
    $stmt = $con->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("location:admin_users_management.php?message=" . urlencode("User not found") . "&type=error");
        exit;
    }
    
    // Prevent admins from editing other admins
    if ($user['role'] === 'admin') {
        header("location:admin_users_management.php?message=" . urlencode("You cannot edit other administrator accounts") . "&type=error");
        exit;
    }
    
} catch(PDOException $ex) {
    header("location:admin_users_management.php?message=" . urlencode("Error fetching user details") . "&type=error");
    exit;
}

// Handle form submission
if (isset($_POST['update_user'])) {
    error_log("Update user form submitted for user ID: " . $user_id);
    error_log("POST data: " . print_r($_POST, true));
    
    try {
        $con->beginTransaction();
        
        // Get form data
        $displayName = trim($_POST['display_name']);
        $userName = trim($_POST['user_name']);
        $email = trim($_POST['email']) ?: null;
        $phone = trim($_POST['phone']) ?: null;
        $role = $_POST['role'];
        $status = $_POST['status'];
        $newPassword = trim($_POST['new_password']);
        
        error_log("Processed form data - Display: $displayName, Username: $userName, Role: $role, Status: $status");
        
        // Validate required fields
        if (empty($displayName) || empty($userName) || empty($role) || empty($status)) {
            throw new Exception("Please fill all required fields");
        }
        
        // Validate role (prevent setting as admin)
        if (!in_array($role, ['health_worker', 'doctor'])) {
            throw new Exception("Invalid role selected");
        }
        
        // Check username uniqueness
        $checkQuery = "SELECT id FROM users WHERE user_name = :user_name AND id != :user_id";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->bindParam(':user_name', $userName);
        $checkStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            throw new Exception("Username already exists");
        }
        
        // Handle profile picture
        $profilePicture = $user['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $newFilename = $user_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], 'system/user_images/' . $newFilename)) {
                    if ($user['profile_picture'] != 'default_profile.jpg' && file_exists('system/user_images/' . $user['profile_picture'])) {
                        unlink('system/user_images/' . $user['profile_picture']);
                    }
                    $profilePicture = $newFilename;
                }
            }
        }
        
        // Update query
        $updateFields = [
            'display_name = :display_name',
            'user_name = :user_name', 
            'email = :email',
            'phone = :phone',
            'role = :role',
            'status = :status',
            'profile_picture = :profile_picture'
        ];
        
        if (!empty($newPassword)) {
            $updateFields[] = 'password = :password';
        }
        
        $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :user_id";
        $updateStmt = $con->prepare($updateQuery);
        
        $updateStmt->bindParam(':display_name', $displayName);
        $updateStmt->bindParam(':user_name', $userName);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->bindParam(':phone', $phone);
        $updateStmt->bindParam(':role', $role);
        $updateStmt->bindParam(':status', $status);
        $updateStmt->bindParam(':profile_picture', $profilePicture);
        $updateStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if (!empty($newPassword)) {
            $hashedPassword = md5($newPassword);
            $updateStmt->bindParam(':password', $hashedPassword);
        }
        
        if ($updateStmt->execute()) {
            $con->commit();
            error_log("User updated successfully for user ID: " . $user_id);
            
            // Check if it's an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                error_log("Returning AJAX response");
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
                exit;
            } else {
                error_log("Redirecting to admin_users_management.php");
            header("location:admin_users_management.php?message=" . urlencode("User updated successfully"));
            exit;
            }
        } else {
            throw new Exception("Failed to update user");
        }
        
    } catch (Exception $ex) {
        $con->rollback();
        error_log("Update user error: " . $ex->getMessage());
        
        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            error_log("Returning AJAX error response");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
            exit;
        } else {
        $message = $ex->getMessage();
        $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <link rel="stylesheet" href="dist/css/admin_system_styles/admin_update_users.css">
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title>Update User - Mamatid Health Center System</title>
    <style>
        :root {
          --transition-speed: 0.3s;
          --primary-color: #3699FF;
          --secondary-color: #6993FF;
          --success-color: #1BC5BD;
          --info-color: #8950FC;
          --warning-color: #FFA800;
          --danger-color: #F64E60;
          --light-color: #F3F6F9;
          --dark-color: #1a1a2d;
        }
        
        /* User Info Card Styling */
        .user-info-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        }
        
        .profile-section {
            position: relative;
        }
        
        .profile-preview {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .info-section {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 1.4rem;
            color: var(--dark-color);
            margin-bottom: 5px;
            line-height: 1.2;
        }
        
        .user-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #7E8299;
            font-size: 0.9rem;
        }
        
        .username {
            font-weight: 500;
        }
        
        .info-separator {
            color: #B5B5C3;
        }
        
        .role-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .role-admin {
            background-color: rgba(137, 80, 252, 0.1);
            color: var(--info-color);
        }
        
        .role-doctor {
            background-color: rgba(255, 168, 0, 0.1);
            color: var(--warning-color);
        }
        
        .role-health_worker {
            background-color: rgba(54, 153, 255, 0.1);
            color: var(--primary-color);
        }
        
        .user-status-info {
            margin-top: 5px;
            font-size: 0.9rem;
            color: #7E8299;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 3px;
        }
        
        .status-active {
            background-color: var(--success-color);
            box-shadow: 0 0 0 3px rgba(27, 197, 189, 0.2);
        }
        
        .status-inactive {
            background-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(246, 78, 96, 0.2);
        }
        
        .status-text {
            font-weight: 500;
        }
        
        .member-since {
            color: #7E8299;
            font-size: 0.9rem;
        }
        
        .edit-badge {
            background: linear-gradient(135deg, rgba(54, 153, 255, 0.1), rgba(105, 147, 255, 0.1));
            color: var(--primary-color);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Bootstrap 5 compatibility for spacing utilities */
        .me-4 {
            margin-right: 1.5rem !important;
        }
        
        .mb-1 {
            margin-bottom: 0.25rem !important;
        }
        
        .mb-2 {
            margin-bottom: 0.5rem !important;
        }
        
        .mb-4 {
            margin-bottom: 1.5rem !important;
        }
        
        .p-3 {
            padding: 1rem !important;
        }
        
        .p-0 {
            padding: 0 !important;
        }
        
        .me-3 {
            margin-right: 1rem !important;
        }
        
        .me-1 {
            margin-right: 0.25rem !important;
        }
        
        .ms-auto {
            margin-left: auto !important;
        }
        
        .text-end {
            text-align: right !important;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed update-user-page">
    <div class="wrapper">
        <?php include './config/admin_header.php'; include './config/admin_sidebar.php'; ?>
        
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6" style="padding-left: 20px;">
                            <h1>Update User</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <!-- User Info Box -->
                    <div class="card card-outline card-primary user-info-card mb-4">
                        <div class="card-body p-0">
                            <div class="d-flex align-items-center p-3">
                                <div class="profile-section me-4">
                                    <img src="system/user_images/<?php echo $user['profile_picture']; ?>" 
                                         alt="Profile Picture" class="profile-preview"
                                         onerror="this.src='system/user_images/default_profile.jpg'">
                                </div>
                                <div class="info-section">
                                    <h4 class="user-name mb-1"><?php echo htmlspecialchars($user['display_name']); ?></h4>
                                    <div class="user-meta mb-2">
                                        <span class="username">@<?php echo htmlspecialchars($user['user_name']); ?></span>
                                        <span class="info-separator">•</span>
                                        <span class="role-badge role-<?php echo $user['role']; ?>"><?php echo getRoleDisplayName($user['role']); ?></span>
                                    </div>
                                    <div class="user-status-info d-flex align-items-center">
                                        <div class="status-indicator me-3">
                                            <span class="status-dot <?php echo $user['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>"></span>
                                            <span class="status-text"><?php echo ucfirst($user['status']); ?></span>
                                        </div>
                                        <div class="member-since">
                                            <i class="far fa-calendar-alt me-1"></i>
                                            Member since <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="ms-auto text-end">
                                    <div class="edit-badge">
                                        <i class="fas fa-pen"></i> Editing User
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Form -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Update User Details</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $user_id; ?>">
                                <div class="row">
                                    <!-- Display Name -->
                                    <div class="col-md-6 form-group">
                                        <label class="form-label">Display Name <span class="text-danger">*</span></label>
                                        <input type="text" name="display_name" required class="form-control" 
                                               value="<?php echo htmlspecialchars($user['display_name']); ?>" 
                                               placeholder="Enter display name"/>
                                    </div>

                                    <!-- Username -->
                                    <div class="col-md-6 form-group">
                                        <label class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" name="user_name" required class="form-control" 
                                               value="<?php echo htmlspecialchars($user['user_name']); ?>" 
                                               placeholder="Enter username"/>
                                        <small class="text-muted">Must be unique across the system</small>
                                    </div>

                                    <!-- Email -->
                                    <div class="col-md-6 form-group">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                               placeholder="Enter email address"/>
                                    </div>

                                    <!-- Phone -->
                                    <div class="col-md-6 form-group">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                               placeholder="Enter phone number"/>
                                    </div>

                                    <!-- Role -->
                                    <div class="col-md-6 form-group">
                                        <label class="form-label">Role <span class="text-danger">*</span></label>
                                        <select name="role" required class="form-control">
                                            <option value="">Select Role</option>
                                            <option value="health_worker" <?php echo $user['role'] == 'health_worker' ? 'selected' : ''; ?>>
                                                Health Worker
                                            </option>
                                            <option value="doctor" <?php echo $user['role'] == 'doctor' ? 'selected' : ''; ?>>
                                                Doctor
                                            </option>
                                        </select>
                                        <small class="text-muted">Only Health Workers and Doctors can be updated</small>
                                    </div>

                                    <!-- Status -->
                                    <div class="col-md-6 form-group">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" required class="form-control">
                                            <option value="">Select Status</option>
                                            <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>
                                                Active
                                            </option>
                                            <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>
                                                Inactive
                                            </option>
                                        </select>
                                    </div>

                                    <!-- New Password -->
                                    <div class="col-md-6 form-group">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" 
                                               placeholder="Leave empty to keep current password"/>
                                        <small class="text-muted">Only fill if you want to change the password</small>
                                    </div>

                                    <!-- Profile Picture -->
                                    <div class="col-md-6 form-group">
                                        <label class="form-label">Profile Picture</label>
                                        <input type="file" name="profile_picture" class="form-control" 
                                               accept="image/jpeg,image/jpg,image/png,image/gif"/>
                                        <small class="text-muted">JPG, JPEG, PNG, GIF files only</small>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <a href="admin_users_management.php" class="btn btn-secondary">
                                            <i class="fas fa-times mr-2"></i>Cancel
                                        </a>
                                        <button type="submit" name="update_user" class="btn btn-primary">
                                            <i class="fas fa-save mr-2"></i>Update User
                                        </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php include './config/admin_footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>

    <script>
        $(function() {
            // Initialize Toast
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // Show message if exists
            <?php if (!empty($message)): ?>
            Toast.fire({
                icon: '<?php echo $messageType; ?>',
                title: '<?php echo addslashes($message); ?>'
            });
            <?php endif; ?>

            // Form submission with validation
            $('form').on('submit', function(e) {
                const displayName = $('input[name="display_name"]').val().trim();
                const userName = $('input[name="user_name"]').val().trim();
                const role = $('select[name="role"]').val();
                const status = $('select[name="status"]').val();

                // Validation - only prevent submission if validation fails
                if (!displayName || !userName || !role || !status) {
                    e.preventDefault();
                    Toast.fire({
                        icon: 'warning',
                        title: 'Please fill all required fields'
                    });
                    return false;
                }

                // Username validation
                if (userName.length < 3) {
                    e.preventDefault();
                    Toast.fire({
                        icon: 'warning',
                        title: 'Username must be at least 3 characters long'
                    });
                    return false;
                }

                // Form should submit normally if we reach here
                return true;
            });

            // File upload preview
            $('input[name="profile_picture"]').on('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('.profile-preview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            // Enhanced form field animations
            $('.form-control').on('focus', function() {
                $(this).closest('.form-group').addClass('focused');
            }).on('blur', function() {
                $(this).closest('.form-group').removeClass('focused');
            });
        });

        // Highlight current menu
        showMenuSelected("#mnu_user_management", "#mi_users");
    </script>
</body>
</html> 