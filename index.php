<?php
// Include database connection
include './config/connection.php';

// Initialize an empty message string
$message = '';

// Handle login submission
if (isset($_POST['login'])) {
    // Get form inputs
    $userName = $_POST['user_name'];
    $password = $_POST['password'];

    // Encrypt password with MD5 (note: MD5 is not recommended for production use)
    $encryptedPassword = md5($password);

    // Prepare query to fetch user details
    $query = "SELECT `id`, `display_name`, `user_name`, `profile_picture`
              FROM `users`
              WHERE `user_name` = '$userName'
                AND `password` = '$encryptedPassword';";

    try {
        // Execute the query
        $stmtLogin = $con->prepare($query);
        $stmtLogin->execute();

        // Check if exactly one user was found
        $count = $stmtLogin->rowCount();
        if ($count == 1) {
            // Fetch user data
            $row = $stmtLogin->fetch(PDO::FETCH_ASSOC);

            // Store user data in session
            $_SESSION['user_id']         = $row['id'];
            $_SESSION['display_name']    = $row['display_name'];
            $_SESSION['user_name']       = $row['user_name'];
            $_SESSION['profile_picture'] = $row['profile_picture'];

            // Handle "Remember Me" functionality
            if (isset($_POST['remember_me'])) {
                // Set cookie to remember the username for 30 days
                setcookie("remembered_username", $userName, time() + (30 * 24 * 60 * 60), "/");
            } else {
                // Clear the cookie if "Remember Me" is unchecked
                setcookie("remembered_username", "", time() - 3600, "/");
            }

            // Redirect to dashboard
            header("location:dashboard.php");
            exit;
        } else {
            // Invalid credentials
            $message = 'Incorrect username or password.';
        }
    } catch (PDOException $ex) {
        // On query error, display debugging info (not recommended in production)
        echo $ex->getTraceAsString();
        echo $ex->getMessage();
        exit;
    }
}

// Retrieve remembered username from cookie, if available
$rememberedUsername = isset($_COOKIE['remembered_username']) ? $_COOKIE['remembered_username'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Logo for the browser tab -->
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Login - Mamatid Health Center System</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- iCheck Bootstrap -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- AdminLTE CSS -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- Additional Custom CSS -->
  <link rel="stylesheet" href="dist/css/adminlte01.css">

  <style>
    /* Background image styling for the login page */
    body.login-page.light-mode {
      background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                  url('dist/img/bg-001.jpg') no-repeat center center fixed;
      background-size: cover;
    }

    /* Login box styling: smaller and centered */
    .login-box {
      max-width: 400px; /* Adjust the max width to your preference */
      width: 90%;
      margin: 2rem auto;
    }

    /* Rounded corners for the outer card */
    .card.card-outline.card-primary {
      border-radius: 10px !important;
    }

    /* Rounded corners for the inner card body */
    .card-body.login-card-body {
      border-radius: 10px !important;
    }
  </style>
</head>
<body class="hold-transition login-page light-mode">

<!-- Main login container -->
<div class="login-box">
  <!-- Logo / Branding -->
  <div class="login-logo mb-4">
    <!-- Transparent logo -->
    <img src="dist/img/mamatid-transparent01.png"
         class="img-thumbnail p-0 border rounded-circle" id="system-logo" alt="System Logo">
    <!-- System title -->
    <div class="text-center h3 mb-0 text-stroked">
      <strong>Mamatid Health Center System</strong>
    </div>
  </div>

  <!-- Login Card -->
  <div class="card card-outline card-primary shadow">
    <div class="card-body login-card-body">
      <!-- Animated typewriter text -->
      <p class="login-box-msg">
        <span id="typewriter-text"></span><span class="cursor">|</span>
      </p>

      <!-- Login Form -->
      <form method="post" class="p-3">
        <!-- Username field -->
        <div class="mb-3">
          <label for="user_name" class="form-label fw-bold">Username</label>
          <div class="input-group">
            <span class="input-group-text bg-light">
              <i class="fas fa-user"></i>
            </span>
            <input 
              type="text"
              class="form-control form-control-lg"
              placeholder="Enter your username"
              id="user_name"
              name="user_name"
              value="<?php echo htmlspecialchars($rememberedUsername); ?>"
              required
            >
          </div>
        </div>

        <!-- Password field -->
        <div class="mb-3">
          <label for="password" class="form-label fw-bold">Password</label>
          <div class="input-group">
            <span class="input-group-text bg-light">
              <i class="fas fa-lock"></i>
            </span>
            <input
              type="password"
              class="form-control form-control-lg"
              placeholder="Enter your password"
              id="password"
              name="password"
              required
            >
          </div>
        </div>

        <!-- Remember Me checkbox -->
        <div class="form-check mb-3">
          <input 
            class="form-check-input"
            type="checkbox"
            id="remember_me"
            name="remember_me"
            <?php echo ($rememberedUsername !== '') ? 'checked' : ''; ?>
          >
          <label class="form-check-label" for="remember_me">
            Remember me
          </label>
        </div>

        <!-- Submit button -->
        <button
          name="login"
          type="submit"
          class="btn btn-primary btn-lg w-100 fw-bold shadow-sm"
        >
          Sign In
        </button>

        <!-- Error Message Display -->
        <?php if ($message !== ''): ?>
          <div
            id="alertMessage"
            class="alert alert-danger text-center mt-3"
            role="alert"
            style="opacity: 0.9;"
          >
            <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
          </div>

          <script>
            // Auto-hide alert after 5 seconds
            setTimeout(function() {
              var alertBox = document.getElementById("alertMessage");
              if (alertBox) {
                alertBox.style.transition = "opacity 0.5s ease";
                alertBox.style.opacity = "0";
                setTimeout(() => alertBox.remove(), 500);
              }
            }, 5000);
          </script>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>

<!-- Typewriting animation script -->
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const text = "Please enter your login credentials";
    let index = 0;
    const speed = 50;  // Typing speed
    const pause = 2000; // Pause before repeating
    const typewriter = document.getElementById("typewriter-text");

    if (!typewriter) {
      console.error("Element with id 'typewriter-text' not found!");
      return;
    }

    function typeEffect() {
      if (index < text.length) {
        typewriter.innerHTML += text.charAt(index);
        index++;
        setTimeout(typeEffect, speed);
      } else {
        // After finishing, wait, then clear and restart
        setTimeout(() => {
          typewriter.innerHTML = "";
          index = 0;
          typeEffect();
        }, pause);
      }
    }

    typeEffect();
  });
</script>

</body>
</html>
