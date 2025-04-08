<?php
include './config/connection.php';
include './common_service/common_functions.php';

// Initialize feedback message variable
$message = '';
// Retrieve the user ID from GET parameters (ensure proper sanitization if needed)
$user_id = $_GET['user_id'];

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
    $message = "user update successfully";
    $con->commit();

  } catch(PDOException $ex) {
    // Rollback transaction in case of error
    $con->rollback();
    echo $ex->getTraceAsString();
    echo $ex->getMessage();
    exit;
  }
  // Redirect to congratulation page with feedback message
  header("Location:congratulation.php?goto_page=users.php&message=$message");
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
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">

  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar -->
    <?php
      include './config/site_header.php';
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
                  <label>Password</label>
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
                <div class="col-lg-11 col-md-10 col-sm-10">&nbsp;</div>
                <div class="col-lg-1 col-md-2 col-sm-2 col-xs-2">
                  <button type="submit" id="save_user"
                    name="save_user" class="btn btn-primary btn-sm btn-flat btn-block">Update</button>
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
      var message = '<?php echo $message;?>';

      if(message !== '') {
        showCustomMessage(message);
      }
    </script>
</body>
</html>
