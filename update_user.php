<?php
include './config/connection.php';
include './common_service/common_functions.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Initialize feedback message variable
$message = '';
// Retrieve the user ID from GET parameters (ensure proper sanitization if needed)
$user_id = $_GET['user_id'];

// Check if the logged-in user has permission to edit this user
if ($_SESSION['user_id'] != $user_id) {
    header("location:users.php?message=" . urlencode("You don't have permission to edit other users.") . "&type=error");
    exit;
}

// Query to fetch current user's details
$query = "SELECT `id`, `display_name`, `user_name` from `users`
where `id` = $user_id;";

try {
  // Prepare and execute the query to fetch user details
  $stmtUpdateUser = $con->prepare($query);
  $stmtUpdateUser->execute();
  $row = $stmtUpdateUser->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $ex) {
  // Output error details and stop execution if query fails
  echo $ex->getTraceAsString();
  echo $ex->getMessage();
  exit;
}

// Check if the update form has been submitted
if (isset($_POST['save_user'])) {
  // Verify current password first
  $currentPassword = trim($_POST['current_password']);
  
  // Check if current password is empty
  if (empty($currentPassword)) {
    $message = "Please enter your current password to proceed with the update";
    header("location:update_user.php?user_id=" . $user_id . "&message=" . urlencode($message) . "&type=warning");
    exit;
  }
  
  $encryptedCurrentPassword = md5($currentPassword);
  
  // Check if current password is correct
  $verifyQuery = "SELECT id FROM users WHERE id = ? AND password = ?";
  $stmt = $con->prepare($verifyQuery);
  $stmt->execute([$_SESSION['user_id'], $encryptedCurrentPassword]);
  
  if ($stmt->rowCount() == 0) {
    $message = "Current password is incorrect";
    header("location:update_user.php?user_id=" . $user_id . "&message=" . urlencode($message) . "&type=error");
    exit;
  }

  // Retrieve and trim user input values
  $displayName = trim($_POST['display_name']);
  $userName = trim($_POST['username']);
  $password = $_POST['password'];
  $hiddenId = $_POST['hidden_id'];

  // Process the uploaded profile picture file
  $profilePicture = basename($_FILES["profile_picture"]["name"]);
  // Prepend current timestamp to file name to avoid collisions
  $targetFile = time() . $profilePicture;
  // Move the uploaded file to the 'user_images' directory
  $status = move_uploaded_file($_FILES["profile_picture"]["tmp_name"], 'user_images/' . $targetFile);

  // Encrypt the password using MD5 (Consider stronger algorithms for production)
  $encryptedPassword = md5($password);

  // Build the update query based on provided fields.
  // NOTE: Direct variable interpolation here can expose you to SQL injection risks.
  if($displayName != '' && $userName != '' && $password != '' && $status != '') {

    $updateUserQuery = "UPDATE `users`
    set `display_name` = '$displayName' ,`user_name` = '$userName', `password` = 
    '$encryptedPassword' , `profile_picture` = '$targetFile'
    where `id` = $hiddenId";
  
  } elseif ($displayName !== '' && $userName !== '' && $password !== ''){

    $updateUserQuery = "UPDATE `users`
    set `display_name` = '$displayName' ,`user_name` = '$userName' , `password` = 
    '$encryptedPassword'
    where `id` = $hiddenId";
  
  } elseif ($displayName !== '' && $userName !== '' && $status !== ''){

    $updateUserQuery = "UPDATE `users`
    set `display_name` = '$displayName' , `user_name` = '$userName' , `profile_picture` = '$targetFile '
    where `id` = $hiddenId";
  
  } else {
    // If required fields are missing, display a custom message
    $message = "Please fill all required fields.";
    header("location:update_user.php?user_id=" . $user_id . "&message=" . urlencode($message) . "&type=warning");
    exit;
  }

  try {
    // Begin transaction to ensure that the update operation is atomic
    $con->beginTransaction();
    $stmtUpdateUser = $con->prepare($updateUserQuery);
    $stmtUpdateUser->execute();
    $con->commit();
    $message = "User updated successfully";
    // Redirect to users.php with the message
    header("Location:users.php?message=" . urlencode($message));
    exit;
  } catch(PDOException $ex) {
    // Rollback transaction in case of error
    $con->rollback();
    echo $ex->getTraceAsString();
    echo $ex->getMessage();
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Update User Details - Mamatid Health Center System</title>

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

    .btn-danger {
      background: linear-gradient(135deg, var(--danger-color) 0%, #FF647C 100%);
      border: none;
    }

    .btn-danger:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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

    /* Form Group Spacing */
    .form-group {
      margin-bottom: 1.5rem;
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
          </div>
        </div>
      </section>

      <section class="content">
        <div class="container-fluid">
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
              <input type="hidden" name="hidden_id" value="<?php echo $user_id;?>">
              <div class="row">
                  <div class="col-md-4 form-group">
                    <label class="form-label">Display Name</label>
                    <input type="text" id="display_name" name="display_name" required
                           class="form-control" value="<?php echo $row['display_name'];?>" />
                </div>

                  <div class="col-md-4 form-group">
                    <label class="form-label">Username</label>
                    <input type="text" id="username" name="username" required
                           class="form-control" value="<?php echo $row['user_name'];?>" />
                </div>

                  <div class="col-md-4 form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required
                           class="form-control" placeholder="Enter current password"/>
                </div>

                  <div class="col-md-4 form-group">
                    <label class="form-label">New Password</label>
                  <input type="password" id="password" name="password"
                           class="form-control" placeholder="Enter new password"/>
                </div>

                  <div class="col-md-4 form-group">
                    <label class="form-label">Profile Picture</label>
                  <input type="file" id="profile_picture" name="profile_picture"
                           class="form-control" accept="image/*"/>
                  </div>
                </div>

                <div class="row mt-4">
                  <div class="col-12 text-right">
                    <button type="submit" id="save_user" name="save_user" 
                            class="btn btn-primary">
                      <i class="fas fa-save mr-2"></i>Update
                    </button>
                    <button type="button" id="delete_user" 
                            class="btn btn-danger ml-2">
                      <i class="fas fa-trash mr-2"></i>Delete
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

        // Form validation before submit
        $('#save_user').on('click', function(e) {
          const currentPassword = $('#current_password').val().trim();
          
          if (!currentPassword) {
            e.preventDefault();
            Toast.fire({
              icon: 'warning',
              title: 'Please enter your current password to proceed with the update'
            });
          }
        });

        // Delete user confirmation
        $('#delete_user').on('click', function() {
          Swal.fire({
            title: 'Delete User?',
            text: "Are you sure you want to delete this user? This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, keep it'
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = 'actions/delete_user.php?user_id=<?php echo $user_id; ?>';
            }
          });
        });
      });

    // Highlight current menu
    showMenuSelected("#mnu_users", "");
    </script>
</body>
</html>
