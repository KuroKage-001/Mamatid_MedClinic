<?php
// Include client authentication check
require_once './system/utilities/check_client_auth.php';

require_once 'config/db_connection.php';
require_once 'system/utilities/admin_client_common_functions_services.php';
require_once 'system/utilities/admin_client_role_functions_services.php';

// Check if this is a client-only page
requireClient();

$message = '';
$error = '';
$client_id = function_exists('getClientSessionVar') ? getClientSessionVar('client_id') : $_SESSION['client_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);
        $address = trim($_POST['address']);
        $date_of_birth = trim($_POST['date_of_birth']);
        $gender = trim($_POST['gender']);
        
        // Check if email is already taken by another user
        $check_email_sql = "SELECT id FROM clients_user_accounts WHERE email = :email AND id != :client_id";
        $check_stmt = $con->prepare($check_email_sql);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->bindParam(':client_id', $client_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = "Email address is already in use by another account.";
        } else {
            $sql = "UPDATE clients_user_accounts SET full_name = :full_name, email = :email, phone_number = :phone_number, 
                    address = :address, date_of_birth = :date_of_birth, gender = :gender 
                    WHERE id = :client_id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone_number', $phone_number);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':date_of_birth', $date_of_birth);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':client_id', $client_id);
            
            if ($stmt->execute()) {
                $_SESSION['client_name'] = $full_name;
                $_SESSION['client_email'] = $email;
                $message = "Profile updated successfully!";
            } else {
                $error = "Error updating profile";
            }
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
            $sql = "SELECT password FROM clients_user_accounts WHERE id = :client_id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':client_id', $client_id);
            $stmt->execute();
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($client && md5($current_password) === $client['password']) {
                $hashed_password = md5($new_password);
                $sql = "UPDATE clients_user_accounts SET password = :password WHERE id = :client_id";
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':client_id', $client_id);
                
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
            $new_filename = $client_id . '_' . time() . '.' . $ext;
            $upload_path = 'system/client_images/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if it's not the default
                $sql = "SELECT profile_picture FROM clients_user_accounts WHERE id = :client_id";
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':client_id', $client_id);
                $stmt->execute();
                $current_pic = $stmt->fetchColumn();
                
                if ($current_pic != 'default_client.png' && file_exists('system/client_images/' . $current_pic)) {
                    unlink('system/client_images/' . $current_pic);
                }
                
                // Update database
                $sql = "UPDATE clients_user_accounts SET profile_picture = :profile_picture WHERE id = :client_id";
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':profile_picture', $new_filename);
                $stmt->bindParam(':client_id', $client_id);
                
                if ($stmt->execute()) {
                    $_SESSION['client_profile_picture'] = $new_filename;
                    $message = "Profile picture updated successfully!";
                    
                    // Force immediate refresh of the profile image cache and reload page
                    echo "<script>
                        // Add timestamp to force cache refresh
                        const timestamp = new Date().getTime();
                        
                        // Function to update image sources with timestamp
                        function updateImageSrc(selector) {
                            const images = document.querySelectorAll(selector);
                            images.forEach(img => {
                                let src = img.src.split('?')[0]; // Remove existing query params
                                img.src = src + '?v=' + timestamp;
                            });
                        }
                        
                        // Update all profile images when DOM is ready
                        document.addEventListener('DOMContentLoaded', function() {
                            updateImageSrc('.main-header .user-image');
                            updateImageSrc('.main-header .profile-img');
                            updateImageSrc('.main-sidebar .user-img');
                            
                            // Reload the page after a short delay to refresh all frames
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        });
                    </script>";
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
$sql = "SELECT * FROM clients_user_accounts WHERE id = :client_id";
$stmt = $con->prepare($sql);
$stmt->bindParam(':client_id', $client_id);
$stmt->execute();
$client_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default profile picture path
$profile_pic = isset($client_data['profile_picture']) && !empty($client_data['profile_picture']) 
               ? $client_data['profile_picture'] : 'default_client.png';
$profile_pic_url = 'system/client_images/' . $profile_pic;

// If the file doesn't exist, use a fallback image
if (!file_exists($profile_pic_url)) {
    $profile_pic_url = 'dist/img/patient-avatar.png';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Account Settings | Mamatid Health Center</title>
    <?php include 'config/site_css_links.php'; ?>
    <link rel="stylesheet" href="dist/css/admin_system_styles/admin_update_users.css">
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

        body {
            padding-top: 60px;
            padding-bottom: 60px;
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
        .profile-info {
            text-align: center;
            padding: 1.5rem;
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
            margin: 0 auto;
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

        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .profile-email {
            color: #6c757d;
            margin-bottom: 15px;
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

<?php include 'config/client_ui/client_header.php'; ?>
<?php include 'config/client_ui/client_sidebar.php'; ?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row align-items-center mb-4">
                <div class="col-12 col-md-6" style="padding-left: 20px;">
                    <h1>My Account Settings</h1>
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
                                    <img src="<?php echo $profile_pic_url; ?>" alt="Profile Picture" class="profile-pic" id="profilePreview">
                                    <label for="profilePicInput" class="profile-pic-overlay">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                </div>
                                <input type="file" id="profilePicInput" name="profile_picture" 
                                       accept="image/*" style="display: none;" onchange="previewImage(this);">
                                <div class="profile-info">
                                    <h4 class="profile-name"><?php echo htmlspecialchars($client_data['full_name']); ?></h4>
                                    <p class="profile-email"><?php echo htmlspecialchars($client_data['email']); ?></p>
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary" 
                                            id="uploadBtn" style="display: none;">
                                        <i class="fas fa-upload mr-2"></i>Upload Picture
                                    </button>
                                    </div>
                                </div>
                            </form>
                            <div class="mt-3">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="info-item">
                                            <span class="info-label"><i class="fas fa-phone mr-2"></i>Phone</span>
                                            <span class="info-value"><?php echo htmlspecialchars($client_data['phone_number']); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-item">
                                            <span class="info-label"><i class="fas fa-calendar mr-2"></i>Age</span>
                                            <span class="info-value">
                                                <?php 
                                                    $birthdate = new DateTime($client_data['date_of_birth']);
                                                    $today = new DateTime();
                                                    echo $birthdate->diff($today)->y;
                                                ?> years
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-map-marker-alt mr-2"></i>Address</span>
                                        <span class="info-value"><?php echo htmlspecialchars($client_data['address']); ?></span>
                                    </div>
                                </div>
                            </div>
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
                                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="full_name" 
                                                   value="<?php echo htmlspecialchars($client_data['full_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($client_data['email']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="phone_number" 
                                                   value="<?php echo htmlspecialchars($client_data['phone_number']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="date_of_birth" 
                                                   value="<?php echo htmlspecialchars($client_data['date_of_birth']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                                            <select class="form-control" name="gender" required>
                                                <?php echo getGender($client_data['gender']); ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Address <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="address" 
                                                   value="<?php echo htmlspecialchars($client_data['address']); ?>" required>
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

<?php include 'config/client_ui/client_footer.php'; ?>
</div>

<?php include 'config/site_css_js_links.php'; ?>

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
    
    // Handle successful profile picture update
    <?php if(strpos($message, 'Profile picture updated successfully') !== false): ?>
    // Add a random timestamp to force image refresh in header and sidebar
    const timestamp = new Date().getTime();
    
    // Find all profile images in header and sidebar and refresh them
    // Try both direct document and parent document (in case of frames)
    const headerImages = document.querySelectorAll('.main-header img.user-image, .main-header img.profile-img');
    const sidebarImages = document.querySelectorAll('.main-sidebar img.user-img');
    
    // Update images in current document
    headerImages.forEach(img => {
        let src = img.src.split('?')[0]; // Remove any existing query parameters
        img.src = src + '?v=' + timestamp;
    });
    
    sidebarImages.forEach(img => {
        let src = img.src.split('?')[0]; // Remove any existing query parameters
        img.src = src + '?v=' + timestamp;
    });
    
    // Also update in parent document if it exists (for frames)
    if (window.parent && window.parent.document) {
        const parentHeaderImages = window.parent.document.querySelectorAll('.main-header img.user-image, .main-header img.profile-img');
        const parentSidebarImages = window.parent.document.querySelectorAll('.main-sidebar img.user-img');
        
        parentHeaderImages.forEach(img => {
            let src = img.src.split('?')[0];
            img.src = src + '?v=' + timestamp;
        });
        
        parentSidebarImages.forEach(img => {
            let src = img.src.split('?')[0];
            img.src = src + '?v=' + timestamp;
        });
    }
    
    // Reload the page after a short delay to ensure all images are refreshed
    setTimeout(function() {
        window.location.reload();
    }, 1500);
    <?php endif; ?>
});
</script>

</body>
</html>
