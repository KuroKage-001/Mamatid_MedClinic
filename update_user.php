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
    showCustomMessage("please fill");
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
  <?php
    include './config/site_css_links.php';
  ?>

  <!-- Logo for the tab bar -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Update User  Details - Mamatid Health Center System</title>
  
  <!-- Include SweetAlert2 CSS -->
  <link rel="stylesheet" href="plugins/sweetalert2/sweetalert2.min.css">
  
  <!-- Include jQuery and SweetAlert2 JS -->
  <script src="plugins/jquery/jquery.min.js"></script>
  <script src="plugins/sweetalert2/sweetalert2.all.min.js"></script>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">

  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar -->
    <?php
      include './config/header.php';
      include './config/sidebar.php';
    ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>USERS</h1>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>

      <!-- Main content -->
      <section class="content">
        <!-- Default box for updating user information -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">UPDATE USER</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>

          <div class="card-body">
            <!-- Form for updating user details -->
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="hidden_id" value="<?php echo $user_id;?>">
              <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Display Name</label>
                  <input type="text" id="display_name" name="display_name" required="required"
                    class="form-control form-control-sm rounded-0" value="<?php echo $row['display_name'];?>" />
                </div>

                <br>
                <br>
                <br>

                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Username</label>
                  <input type="text" id="username" name="username" required="required"
                    class="form-control form-control-sm rounded-0" value="<?php echo $row['user_name'];?>" />
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Current Password</label>
                  <input type="password" id="current_password" name="current_password" required="required"
                    class="form-control form-control-sm rounded-0"/>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>New Password</label>
                  <input type="password" id="password" name="password"
                    class="form-control form-control-sm rounded-0"/>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10">
                  <label>Profile picture</label>
                  <input type="file" id="profile_picture" name="profile_picture"
                    class="form-control form-control-sm rounded-0" />
                </div>
              </div>
              <div class="clearfix">&nbsp;</div>
              <div class="row">
                <div class="col-lg-10 col-md-9 col-sm-9">&nbsp;</div>
                <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                  <button type="submit" id="save_user"
                    name="save_user" class="btn btn-primary btn-sm btn-flat btn-block">Update</button>
                </div>
                <div class="col-lg-1 col-md-2 col-sm-2 col-xs-1">
                  <button type="button" id="delete_user" class="btn btn-danger btn-sm btn-flat btn-block">Delete</button>
                </div>
              </div>
            </form>
          </div>
          <!-- /.card-body -->
        </div>
      </section>

      <?php
      include './config/footer.php';

      $message = '';
      if(isset($_GET['message'])) {
        $message = $_GET['message'];
      }
      ?>
      <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <?php
      include './config/site_js_links.php';
    ?>

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
              window.location.href = 'delete_user.php?user_id=<?php echo $user_id; ?>';
            }
          });
        });
      });
    </script>
</body>
</html>
