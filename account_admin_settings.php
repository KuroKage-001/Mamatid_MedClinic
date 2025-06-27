<?php
require_once './config/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}
require_once './common_service/common_functions.php';
require_once './common_service/role_functions.php';

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

$message = '';
$error = '';
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $display_name = trim($_POST['display_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        $sql = "UPDATE users SET display_name = :display_name, email = :email, phone = :phone WHERE id = :user_id";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':display_name', $display_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['display_name'] = $display_name;
            $message = "Profile updated successfully!";
        } else {
            $error = "Error updating profile";
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error = "New passwords do not match!";
        } else {
            // Verify current password
            $sql = "SELECT password FROM users WHERE id = :user_id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && md5($current_password) === $user['password']) {
                $hashed_password = md5($new_password);
                $sql = "UPDATE users SET password = :password WHERE id = :user_id";
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Error updating password";
                }
            } else {
                $error = "Current password is incorrect!";
            }
        }
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = $user_id . '_' . time() . '.' . $ext;
            $upload_path = 'system/user_images/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if it's not the default
                if ($_SESSION['profile_picture'] != 'default_profile.jpg') {
                    $old_file = 'system/user_images/' . $_SESSION['profile_picture'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                // Update database
                $sql = "UPDATE users SET profile_picture = :profile_picture WHERE id = :user_id";
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':profile_picture', $new_filename);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['profile_picture'] = $new_filename;
                    $message = "Profile picture updated successfully!";
                } else {
                    $error = "Error updating profile picture";
                }
            } else {
                $error = "Error uploading file!";
            }
        } else {
            $error = "Invalid file type! Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    }
}

// Get current user data
$sql = "SELECT * FROM users WHERE id = :user_id";
$stmt = $con->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Account Settings | Mamatid Health Center System</title>
    <?php include './config/site_css_links.php'; ?>
    <link rel="stylesheet" href="system_styles/account_settings.css">
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
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

        /* Card Styling */
        .card {
            border: none;
          box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
          border-radius: 15px;
          margin-bottom: 30px;
        }

        .card-outline {
          border-top: 3px solid var(--primary-color);
        }

        .card-header {
          background: white;
          padding: 1.5rem;
          border-bottom: 1px solid #eee;
        }

        .card-header h3 {
            margin: 0;
          font-size: 1.25rem;
          font-weight: 600;
          color: var(--dark-color);
          text-transform: capitalize;
        }

        .card-body {
          padding: 1.5rem;
        }

        /* Form Controls */
        .form-control, .form-select {
          height: calc(2.5rem + 2px);
          border-radius: 8px;
          border: 2px solid #e4e6ef;
          padding: 0.625rem 1rem;
          font-size: 1rem;
          transition: all var(--transition-speed);
        }

        .form-control:focus, .form-select:focus {
          border-color: var(--primary-color);
          box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
        }

        .form-label {
          font-weight: 500;
          color: var(--dark-color);
          margin-bottom: 0.5rem;
        }

        /* Button Styling */
        .btn {
          padding: 0.65rem 1rem;
            font-weight: 500;
          border-radius: 8px;
          transition: all var(--transition-speed);
        }

        .btn-primary {
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
          border: none;
        }

        .btn-primary:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 15px rgba(54, 153, 255, 0.4);
        }
        
        .btn-tool {
          color: var(--dark-color);
        }

        /* Profile section styling */
        .profile-pic-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }
        
        .profile-pic {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #f4f6f9;
            box-shadow: 0 5px 15px rgba(0,0,0,.1);
        }
        
        .profile-pic-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .profile-pic-overlay:hover {
            background: var(--secondary-color);
            transform: scale(1.1);
        }

        .role-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-block;
            margin-top: 10px;
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

        /* Content Header Styling */
        .content-header {
            padding: 20px 0;
        }

        .content-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
            text-transform: capitalize;
        }

        /* Alert Styling */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.5rem;
        }

        .alert-success {
            background-color: rgba(27, 197, 189, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background-color: rgba(246, 78, 96, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        /* User info styles */
        .user-info {
            text-align: center;
        }
        
        .user-info h4 {
            margin-top: 10px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .user-info .username {
            color: #7E8299;
            margin-bottom: 15px;
            display: block;
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .strength-weak {
            background-color: var(--danger-color);
            width: 30%;
        }
        
        .strength-medium {
            background-color: var(--warning-color);
            width: 60%;
        }
        
        .strength-strong {
            background-color: var(--success-color);
            width: 100%;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .card-header {
                padding: 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
<div class="wrapper">

<?php include './config/admin_header.php'; include './config/admin_sidebar.php'; ?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row align-items-center mb-4">
                <div class="col-12 col-md-6" style="padding-left: 20px;">
                    <h1>Account Settings</h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-4">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Profile Picture</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="pictureForm">
                                <div class="profile-pic-container">
                                    <img src="system/user_images/<?php echo $_SESSION['profile_picture']; ?>" 
                                         alt="Profile Picture" class="profile-pic"
                                         onerror="this.src='system/user_images/default_profile.jpg'"
                                         id="profileImage">
                                    <label for="fileInput" class="profile-pic-overlay" title="Change Profile Picture">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                    <input type="file" id="fileInput" name="profile_picture" style="display: none;" 
                                           accept="image/jpeg,image/png,image/gif">
                                </div>
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($user_data['display_name']); ?></h4>
                                    <span class="username">@<?php echo htmlspecialchars($user_data['user_name']); ?></span>
                                    <div class="role-badge role-<?php echo $user_data['role']; ?>">
                                        <?php echo getRoleDisplayName($user_data['role']); ?>
                                    </div>
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary" 
                                            id="uploadBtn" style="display: none;">
                                        <i class="fas fa-upload mr-2"></i>Upload Picture
                                    </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Update Profile Form -->
                <div class="col-lg-8">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Update Profile</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="profileForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Display Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="display_name" 
                                                   value="<?php echo htmlspecialchars($user_data['display_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($user_data['user_name']); ?>" 
                                                   disabled>
                                            <small class="text-muted">Username cannot be changed</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Phone</label>
                                            <input type="text" class="form-control" name="phone" 
                                                   value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-right">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Change Password</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="passwordForm">
                                <div class="form-group">
                                    <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="current_password" id="currentPassword" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" name="new_password" 
                                                   id="newPassword" required minlength="6">
                                            <div class="password-strength" id="passwordStrength"></div>
                                            <small class="text-muted">Minimum 6 characters</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" name="confirm_password" 
                                                   id="confirmPassword" required minlength="6">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-right">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key mr-2"></i>Change Password
                                </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include './config/admin_footer.php'; ?>
</div>

<?php include './config/site_js_links.php'; ?>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
            document.getElementById('uploadBtn').style.display = 'inline-block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

$(document).ready(function() {
    // Initialize Toast
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
    
    // Password strength indicator
    $('#newPassword').on('input', function() {
        const password = $(this).val();
        const strength = $('#passwordStrength');
        
        // Remove any existing classes
        strength.removeClass('strength-weak strength-medium strength-strong');
        
        if (password.length === 0) {
            strength.css('width', '0');
            return;
        }
        
        // Simple strength check
        if (password.length < 6) {
            strength.addClass('strength-weak');
        } else if (password.length < 10 || !/[!@#$%^&*(),.?":{}|<>]/.test(password) || !/\d/.test(password)) {
            strength.addClass('strength-medium');
        } else {
            strength.addClass('strength-strong');
        }
    });
    
    // Password form validation
    $('#passwordForm').submit(function(e) {
        const newPassword = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();
    
    if (newPassword !== confirmPassword) {
            e.preventDefault();
            Toast.fire({
                icon: 'error',
                title: 'New passwords do not match!'
            });
        return false;
    }
    
    if (newPassword.length < 6) {
            e.preventDefault();
            Toast.fire({
                icon: 'error',
                title: 'Password must be at least 6 characters long!'
            });
        return false;
    }
    
    return true;
    });
    
    // Show menu selected
    showMenuSelected("#mnu_user_management", "");
});
</script>

</body>
</html> 