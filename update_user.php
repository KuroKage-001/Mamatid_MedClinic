<?php
include './config/connection.php';
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
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header("location:users.php?message=" . urlencode("Invalid user ID") . "&type=error");
    exit;
}

$user_id = intval($_GET['user_id']);

// Prevent editing own account (should use account settings)
if ($_SESSION['user_id'] == $user_id) {
    header("location:account_settings.php?message=" . urlencode("Use Account Settings to edit your own profile") . "&type=info");
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
        header("location:users.php?message=" . urlencode("User not found") . "&type=error");
        exit;
    }
    
    // Prevent admins from editing other admins
    if ($user['role'] === 'admin') {
        header("location:users.php?message=" . urlencode("You cannot edit other administrator accounts") . "&type=error");
        exit;
    }
    
} catch(PDOException $ex) {
    header("location:users.php?message=" . urlencode("Error fetching user details") . "&type=error");
    exit;
}

// Handle form submission
if (isset($_POST['update_user'])) {
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
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], 'user_images/' . $newFilename)) {
                    if ($user['profile_picture'] != 'default_profile.jpg' && file_exists('user_images/' . $user['profile_picture'])) {
                        unlink('user_images/' . $user['profile_picture']);
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
            header("location:users.php?message=" . urlencode("User updated successfully"));
            exit;
        } else {
            throw new Exception("Failed to update user");
        }
        
    } catch (Exception $ex) {
        $con->rollback();
        $message = $ex->getMessage();
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
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
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form Styling */
        .form-control {
            height: calc(2.5rem + 2px);
            border-radius: 8px;
            border: 2px solid #e4e6ef;
            padding: 0.625rem 1rem;
            font-size: 1rem;
            transition: all var(--transition-speed);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        /* Select Styling */
        select.form-control {
            appearance: none;
            background: #fff url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'%3E%3Cpath fill='%23343a40' d='M2 0L0 2h4zm0 5L0 3h4z'/%3E%3C/svg%3E") no-repeat right 0.75rem center;
            background-size: 8px 10px;
            padding-right: 2rem;
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

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #868e96 100%);
            border: none;
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
            color: white;
        }

        /* Profile Picture Preview */
        .profile-preview {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #e4e6ef;
            margin-bottom: 1rem;
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
        }

        /* Info Box Styling */
        .info-box {
            background: var(--light-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .info-box .text-muted {
            font-size: 0.9rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .card-header {
                padding: 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .form-control {
                height: calc(2.2rem + 2px);
                padding: 0.5rem 0.75rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/header.php'; include './config/sidebar.php'; ?>
        
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6" style="padding-left: 20px;">
                            <h1>Update User</h1>
                        </div>
                        <div class="col-12 col-md-6 text-right">
                            <a href="users.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Users
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <!-- User Info Box -->
                    <div class="info-box">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <img src="user_images/<?php echo $user['profile_picture']; ?>" 
                                     alt="Profile Picture" class="profile-preview">
                            </div>
                            <div class="col-md-10">
                                <h5 class="mb-1"><?php echo htmlspecialchars($user['display_name']); ?></h5>
                                <p class="text-muted mb-1">@<?php echo htmlspecialchars($user['user_name']); ?> • 
                                   <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?> • 
                                   <span class="badge badge-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                       <?php echo ucfirst($user['status']); ?>
                                   </span>
                                </p>
                                <small class="text-muted">Member since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></small>
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
                            <form method="post" enctype="multipart/form-data">
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

                                <div class="row mt-4">
                                    <div class="col-12 text-right">
                                        <a href="users.php" class="btn btn-secondary mr-2">
                                            <i class="fas fa-times mr-2"></i>Cancel
                                        </a>
                                        <button type="submit" name="update_user" class="btn btn-primary">
                                            <i class="fas fa-save mr-2"></i>Update User
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php include './config/footer.php'; ?>
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

            // Form validation
            $('form').on('submit', function(e) {
                const displayName = $('input[name="display_name"]').val().trim();
                const userName = $('input[name="user_name"]').val().trim();
                const role = $('select[name="role"]').val();
                const status = $('select[name="status"]').val();

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

                // Show loading state
                $(this).find('button[type="submit"]').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...'
                );
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
        });

        // Highlight current menu
        showMenuSelected("#mnu_user_management", "#mi_users");
    </script>
</body>
</html> 