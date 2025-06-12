<?php
// Include the database connection (connection.php already calls session_start())
include './config/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}
require_once './common_service/role_functions.php';

// Check if user is admin
requireAdmin();

$message = '';

// Process the user registration form submission
if (isset($_POST['save_user'])) {
    // Get form inputs
    $displayName = $_POST['display_name'];
    $userName    = $_POST['user_name'];
    $password    = $_POST['password'];
    $email       = $_POST['email'] ?? '';
    $phone       = $_POST['phone'] ?? '';
    $role        = $_POST['role'];
    $status      = $_POST['status'];
    
    // Hash password using MD5 (to match existing system)
    $hashedPassword = md5($password);

    // Handle file upload for profile picture
    $targetFile = 'default_profile.jpg'; // Default profile picture
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
        $baseName   = basename($_FILES["profile_picture"]["name"]);
        $targetFile = time() . '_' . $baseName;
        $status_upload = move_uploaded_file($_FILES["profile_picture"]["tmp_name"], 'user_images/' . $targetFile);
        
        if (!$status_upload) {
            $targetFile = 'default_profile.jpg';
        }
    }

    try {
        // Insert new user record
        $query = "INSERT INTO `users`(`display_name`, `user_name`, `password`, `profile_picture`, `email`, `phone`, `role`, `status`)
                  VALUES(:display_name, :user_name, :password, :profile_picture, :email, :phone, :role, :status)";
        $stmtUser = $con->prepare($query);
        $stmtUser->bindParam(':display_name', $displayName);
        $stmtUser->bindParam(':user_name', $userName);
        $stmtUser->bindParam(':password', $hashedPassword);
        $stmtUser->bindParam(':profile_picture', $targetFile);
        $stmtUser->bindParam(':email', $email);
        $stmtUser->bindParam(':phone', $phone);
        $stmtUser->bindParam(':role', $role);
        $stmtUser->bindParam(':status', $status);
        
        if ($stmtUser->execute()) {
            $message = 'User registered successfully';
            header("location:users.php?message=" . urlencode($message));
            exit;
        } else {
            throw new Exception('Failed to insert user');
        }
    } catch (Exception $ex) {
        $message = 'Error: ' . $ex->getMessage();
        header("location:users.php?message=" . urlencode($message) . "&type=error");
        exit;
    }
}

// Query to get all users except the current logged-in user, ordered by display name
$queryUsers = "SELECT `id`, `display_name`, `user_name`, `profile_picture`, `role`, `status`, `email`, `phone` 
               FROM `users` 
               WHERE `id` != :current_user_id
               ORDER BY `display_name` ASC";
try {
    $stmtUsers = $con->prepare($queryUsers);
    $stmtUsers->bindParam(':current_user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmtUsers->execute();
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    echo $ex->getTraceAsString();
    echo $ex->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <?php include './config/data_tables_css.php'; ?>
  <link rel="stylesheet" href="system_styles/users.css">
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Users - Mamatid Health Center System</title>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed users-page">
  <div class="wrapper">
    <?php include './config/header.php'; include './config/sidebar.php'; ?>
    
    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row align-items-center mb-4">
            <div class="col-12 col-md-6" style="padding-left: 20px;">
              <h1>Users Management</h1>
            </div>
          </div>
        </div>
      </section>
      
      <section class="content">
        <div class="container-fluid">
          <!-- Add Users Form Card -->
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Add Users</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="display_name" class="form-label">Display Name</label>
                    <input type="text" id="display_name" name="display_name" required 
                           class="form-control" placeholder="Enter display name" />
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="user_name" class="form-label">Username</label>
                    <input type="text" id="user_name" name="user_name" required 
                           class="form-control" placeholder="Enter username" />
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input-wrapper">
                    <input type="password" id="password" name="password" required 
                             class="form-control password-field" placeholder="Enter password" />
                      <span class="password-toggle-btn" 
                            onclick="togglePassword('password')" 
                            title="Show password"
                            aria-label="Toggle password visibility">
                        <i class="fas fa-eye" id="password-eye"></i>
                      </span>
                    </div>
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" 
                           class="form-control" placeholder="Enter email" />
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" id="phone" name="phone" 
                           class="form-control" placeholder="Enter phone number" />
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select id="role" name="role" required class="form-control">
                      <option value="">Select Role</option>
                      <option value="admin">Administrator</option>
                      <option value="health_worker">Health Worker</option>
                      <option value="doctor">Doctor</option>
                    </select>
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" required class="form-control">
                      <option value="active" selected>Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="profile_picture" class="form-label">Profile Picture (Optional)</label>
                    <input type="file" id="profile_picture" name="profile_picture" 
                           class="form-control" accept="image/*" />
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                    <label class="d-none d-sm-block">&nbsp;</label>
                    <button type="submit" id="save_user" name="save_user" 
                            class="btn btn-primary w-100">
                      <i class="fas fa-save mr-2"></i>Save User
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          
          <!-- All Users Table Card -->
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">All Users</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="all_users" class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th class="text-center">S.No</th>
                      <th class="text-center">Picture</th>
                      <th>Display Name</th>
                      <th>Username</th>
                      <th>Email</th>
                      <th>Phone</th>
                      <th>Role</th>
                      <th>Status</th>
                      <th class="text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $serial = 0;
                    foreach ($users as $row) {
                        $serial++;
                        $roleClass = '';
                        $statusClass = '';
                        
                        // Role badge colors
                        switch($row['role']) {
                            case 'admin':
                                $roleClass = 'badge-danger';
                                break;
                            case 'doctor':
                                $roleClass = 'badge-primary';
                                break;
                            case 'health_worker':
                                $roleClass = 'badge-info';
                                break;
                        }
                        
                        // Status badge colors
                        $statusClass = ($row['status'] == 'active') ? 'badge-success' : 'badge-secondary';
                    ?>
                    <tr>
                      <td class="text-center"><?php echo $serial; ?></td>
                      <td class="text-center">
                        <img class="user-img" src="user_images/<?php echo $row['profile_picture']; ?>" 
                             alt="User Picture" onerror="this.src='user_images/default_profile.jpg'">
                      </td>
                      <td><?php echo $row['display_name']; ?></td>
                      <td><?php echo $row['user_name']; ?></td>
                      <td><?php echo $row['email'] ?: '-'; ?></td>
                      <td><?php echo $row['phone'] ?: '-'; ?></td>
                      <td>
                        <span class="badge <?php echo $roleClass; ?>">
                          <?php echo getRoleDisplayName($row['role']); ?>
                        </span>
                      </td>
                      <td>
                        <span class="badge <?php echo $statusClass; ?>">
                          <?php echo ucfirst($row['status']); ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <a href="update_user.php?user_id=<?php echo $row['id']; ?>" 
                           class="btn btn-primary btn-sm" title="Edit">
                          <i class="fa fa-edit"></i>
                        </a>
                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                          <?php if ($row['role'] != 'admin'): ?>
                        <button type="button" class="btn btn-sm <?php echo ($row['status'] == 'active') ? 'btn-warning' : 'btn-success'; ?>"
                                onclick="toggleUserStatus(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')"
                                title="<?php echo ($row['status'] == 'active') ? 'Deactivate' : 'Activate'; ?>">
                          <i class="fa fa-<?php echo ($row['status'] == 'active') ? 'ban' : 'check'; ?>"></i>
                        </button>
                          <?php else: ?>
                            <button type="button" class="btn btn-sm btn-secondary" disabled
                                    title="Cannot deactivate administrator accounts">
                              <i class="fa fa-shield-alt"></i>
                            </button>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
    
    <?php include './config/footer.php'; ?>
  </div>

  <?php include './config/site_js_links.php'; ?>
  <?php include './config/data_tables_js.php'; ?>

  <script>
    $(document).ready(function() {
      // Initialize DataTable with modern styling
      $('#all_users').DataTable({
        responsive: true,
        lengthChange: false,
        autoWidth: false,
        buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
        language: {
          search: "",
          searchPlaceholder: "Search users..."
        },
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
      });

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

      // Show message if redirected with message parameter
      const urlParams = new URLSearchParams(window.location.search);
      const message = urlParams.get('message');
      const type = urlParams.get('type') || 'success';
      
      if (message) {
        Toast.fire({
          icon: type,
          title: message
        });
      }

      // Enhanced form field animations
      $('.form-control').on('focus', function() {
        $(this).closest('.form-group, .mb-3').addClass('focused');
      }).on('blur', function() {
        $(this).closest('.form-group, .mb-3').removeClass('focused');
      });
    });

    // Password toggle function
    window.togglePassword = function(fieldId) {
      const passwordField = document.getElementById(fieldId);
      const eyeIcon = document.getElementById(fieldId + '-eye');
      const toggleBtn = eyeIcon.closest('.password-toggle-btn');
      
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
        toggleBtn.classList.add('password-visible');
        toggleBtn.setAttribute('title', 'Hide password');
                } else {
        passwordField.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
        toggleBtn.classList.remove('password-visible');
        toggleBtn.setAttribute('title', 'Show password');
              }
    };

    // Function to toggle user status
    window.toggleUserStatus = function(userId, currentStatus) {
      const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
      const action = currentStatus === 'active' ? 'deactivate' : 'activate';
      
      Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to ${action} this user?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: `Yes, ${action}!`,
        customClass: {
          confirmButton: 'btn btn-primary',
          cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.isConfirmed) {
          // Initialize Toast for this function
          const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
          });

          $.ajax({
            url: 'ajax/toggle_user_status.php',
            type: 'POST',
            data: { 
              user_id: userId,
              status: newStatus
            },
            dataType: 'json',
            success: function(response) {
              if (response.success) {
                Toast.fire({
                  icon: 'success',
                  title: `User ${action}d successfully`
                });
                setTimeout(() => location.reload(), 1500);
              } else {
                Toast.fire({
                  icon: 'error',
                  title: response.message || 'Error updating user status'
                });
              }
            },
            error: function() {
              Toast.fire({
                icon: 'error',
                title: 'Error updating user status'
              });
            }
          });
        }
      });
    };

    // Enhanced form field animations
    $('.form-control').on('focus', function() {
      $(this).closest('.form-group, .mb-3').addClass('focused');
    }).on('blur', function() {
      $(this).closest('.form-group, .mb-3').removeClass('focused');
    });

    // Highlight current menu
    showMenuSelected("#mnu_user_management", "#mi_users");
  </script>
</body>
</html>
