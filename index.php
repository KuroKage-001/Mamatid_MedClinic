<?php
  include './config/connection.php';

  $message = '';

  if(isset($_POST['login'])) {
    $userName = $_POST['user_name'];
    $password = $_POST['password'];

    $encryptedPassword = md5($password);

    $query = "SELECT `id`, `display_name`, `user_name`, `profile_picture` 
              FROM `users`
              WHERE `user_name` = '$userName' 
                AND `password` = '$encryptedPassword';";

    try {
      $stmtLogin = $con->prepare($query);
      $stmtLogin->execute();

      $count = $stmtLogin->rowCount();
      if($count == 1) {
        $row = $stmtLogin->fetch(PDO::FETCH_ASSOC);

        $_SESSION['user_id']         = $row['id'];
        $_SESSION['display_name']    = $row['display_name'];
        $_SESSION['user_name']       = $row['user_name'];
        $_SESSION['profile_picture'] = $row['profile_picture'];

        header("location:dashboard.php");
        exit;
      } else {
        $message = 'Incorrect username or password.';
      }
    } catch(PDOException $ex) {
      echo $ex->getTraceAsString();
      echo $ex->getMessage();
      exit;
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Logo for the tab bar -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Login - Clinic's Patient Management System in PHP</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">

  <style>
    /* Transparent background image via an overlay */
    body.login-page.light-mode {
      background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('dist/img/bg-001.jpg') no-repeat center center fixed;
      background-size: cover;
    }

    .login-box {
      width: 430px;
    }

    #system-logo {
      width: 5em !important;
      height: 5em !important;
      object-fit: cover;
      object-position: center center;
    }

    .text-stroked {
    color: #fff;
    -webkit-text-stroke: 1px #000;
    }

  </style>
</head>
<body class="hold-transition login-page light-mode">
<div class="login-box">
  <div class="login-logo mb-4">
    <img src="dist/img/mamatid-transparent01.png" 
         class="img-thumbnail p-0 border rounded-circle" id="system-logo">
         <div class="text-center h3 mb-0 text-stroked">
  <strong>Mamatid Health Center System</strong>
</div>
  </div>
  <!-- /.login-logo -->
  <div class="card card-outline card-primary rounded-0 shadow">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Please enter your login credentials</p>
      <form method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control form-control-lg rounded-0" 
                 placeholder="Username" id="user_name" name="user_name">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control form-control-lg rounded-0" 
                 placeholder="Password" id="password" name="password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <button name="login" type="submit" class="btn btn-primary rounded-0 btn-block">Sign In</button>
          </div>
          <!-- /.col -->
        </div>
        <div class="row">
          <div class="col-md-12">
            <p class="text-danger">
              <?php 
              if($message != '') {
                echo $message;
              }
              ?>
            </p>
          </div>
        </div>
      </form>
    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->
</body>
</html>
