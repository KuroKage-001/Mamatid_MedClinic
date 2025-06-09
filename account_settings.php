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
            $upload_path = 'user_images/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if it's not the default
                if ($_SESSION['profile_picture'] != 'default_profile.jpg') {
                    $old_file = 'user_images/' . $_SESSION['profile_picture'];
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
    <title>Account Settings | MHC</title>
    <?php include './config/site_css_links.php'; ?>
    <style>
        .content-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,.08);
            border-radius: 10px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1.2rem;
        }
        .card-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 500;
        }
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
            background: #667eea;
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
            background: #764ba2;
            transform: scale(1.1);
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e0e6ed;
            padding: 0.6rem 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 0.6rem 2rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .role-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            background: #f0f3ff;
            color: #667eea;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
<div class="wrapper">

<?php include './config/header.php'; include './config/sidebar.php'; ?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Account Settings</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Account Settings</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Profile Picture</h3>
                        </div>
                        <div class="card-body text-center">
                            <form method="POST" enctype="multipart/form-data" id="pictureForm">
                                <div class="profile-pic-container">
                                    <img src="user_images/<?php echo $_SESSION['profile_picture']; ?>" 
                                         alt="Profile Picture" class="profile-pic" id="profilePreview">
                                    <label for="profilePicInput" class="profile-pic-overlay">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                </div>
                                <input type="file" id="profilePicInput" name="profile_picture" 
                                       accept="image/*" style="display: none;" onchange="previewImage(this);">
                                <h4><?php echo $user_data['display_name']; ?></h4>
                                <p class="text-muted">@<?php echo $user_data['user_name']; ?></p>
                                <div class="role-badge"><?php echo getRoleDisplayName($user_data['role']); ?></div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary btn-sm" 
                                            id="uploadBtn" style="display: none;">
                                        <i class="fas fa-upload mr-2"></i>Upload Picture
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Update Profile Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Update Profile</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Display Name</label>
                                            <input type="text" class="form-control" name="display_name" 
                                                   value="<?php echo $user_data['display_name']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Username</label>
                                            <input type="text" class="form-control" value="<?php echo $user_data['user_name']; ?>" 
                                                   disabled>
                                            <small class="text-muted">Username cannot be changed</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo $user_data['email'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Phone</label>
                                            <input type="text" class="form-control" name="phone" 
                                                   value="<?php echo $user_data['phone'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" onsubmit="return validatePassword()">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>New Password</label>
                                            <input type="password" class="form-control" name="new_password" 
                                                   id="newPassword" required minlength="6">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" 
                                                   id="confirmPassword" required minlength="6">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key mr-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include './config/footer.php'; ?>
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

function validatePassword() {
    var newPassword = document.getElementById('newPassword').value;
    var confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
        alert('New passwords do not match!');
        return false;
    }
    
    if (newPassword.length < 6) {
        alert('Password must be at least 6 characters long!');
        return false;
    }
    
    return true;
}

$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>

</body>
</html> 