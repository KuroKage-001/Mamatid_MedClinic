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
  
  <!-- Include SweetAlert2 CSS -->
  <link rel="stylesheet" href="plugins/sweetalert2/sweetalert2.min.css">
  
  <!-- Include jQuery and SweetAlert2 JS -->
  <script src="plugins/jquery/jquery.min.js"></script>
  <script src="plugins/sweetalert2/sweetalert2.all.min.js"></script>

  <style>
    /* Background image styling for the login page */
    body.login-page.light-mode {
      background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
                  url('dist/img/bg-001.jpg') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Login box styling */
    .login-box {
      max-width: 400px;
      width: 90%;
      margin: 0 auto;
    }

    /* Card styling */
    .card.card-outline.card-primary {
      border-radius: 15px;
      border: none;
      box-shadow: 0 0 40px rgba(0, 0, 0, 0.2);
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
    }

    .card-body.login-card-body {
      border-radius: 15px;
      padding: 2rem;
    }

    /* Logo styling */
    .login-logo img {
      width: 120px;
      height: 120px;
      transition: transform 0.3s ease;
      border-radius: 50% !important;
      background: white;
      padding: 5px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
    }

    .login-logo img:hover {
      transform: scale(1.05);
    }

    .text-stroked {
      color: white;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    /* Form styling */
    .form-control {
      height: 45px;
      border-radius: 10px;
      border: 1px solid #e4e6ef;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .form-control:focus {
      border-color: #3699FF;
      box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
    }

    .input-group-text {
      border-radius: 10px;
      border: 1px solid #e4e6ef;
      background-color: #f5f8fa;
      color: #7E8299;
    }

    /* Button styling */
    .btn-primary {
      height: 45px;
      border-radius: 10px;
      background-color: #3699FF;
      border-color: #3699FF;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #187DE4;
      border-color: #187DE4;
      transform: translateY(-1px);
    }

    /* Remember me checkbox styling */
    .form-check-input {
      width: 20px;
      height: 20px;
      margin-top: 0;
      border: 1px solid #e4e6ef;
      border-radius: 6px;
      cursor: pointer;
    }

    .form-check-label {
      padding-left: 0.5rem;
      color: #7E8299;
      cursor: pointer;
    }

    /* Typewriter cursor animation */
    .cursor {
      display: inline-block;
      width: 2px;
      animation: blink 1s infinite;
    }

    @keyframes blink {
      0% { opacity: 1; }
      50% { opacity: 0; }
      100% { opacity: 1; }
    }

    /* Form labels */
    .form-label {
      color: #3F4254;
      font-size: 0.95rem;
      margin-bottom: 0.5rem;
    }

    /* Modern toast styling */
    .modern-toast {
      background: rgba(255, 255, 255, 0.95) !important;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15) !important;
      backdrop-filter: blur(4px) !important;
      border: 1px solid rgba(255, 255, 255, 0.18) !important;
      border-radius: 12px !important;
      padding: 16px !important;
    }

    .modern-toast.swal2-icon-error {
      border-left: 4px solid #dc3545 !important;
    }

    .modern-toast .swal2-title {
      color: #1a1a1a !important;
      font-size: 1.1rem !important;
      font-weight: 600 !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    .modern-toast .swal2-html-container {
      color: #4a5568 !important;
      font-size: 0.95rem !important;
      margin: 5px 0 0 0 !important;
      padding: 0 !important;
    }

    .modern-toast .swal2-timer-progress-bar {
      background: #dc3545 !important;
      height: 3px !important;
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
         class="img-thumbnail p-0 border-0" id="system-logo" alt="System Logo">
    <!-- System title -->
    <div class="text-center h3 mb-0 text-stroked">
      <strong>Mamatid Health Center System</strong>
    </div>
  </div>

  <!-- Login Card -->
  <div class="card card-outline card-primary shadow">
    <div class="card-body login-card-body">
      <!-- Animated typewriter text -->
      <p class="login-box-msg mb-4">
        <span id="typewriter-text"></span><span class="cursor">|</span>
      </p>

      <!-- Login Form -->
      <form method="post">
        <!-- Username field -->
        <div class="mb-4">
          <label for="user_name" class="form-label">Username</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="fas fa-user"></i>
            </span>
            <input 
              type="text"
              class="form-control"
              placeholder="Enter your username"
              id="user_name"
              name="user_name"
              value="<?php echo htmlspecialchars($rememberedUsername); ?>"
              required
            >
          </div>
        </div>

        <!-- Password field -->
        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="fas fa-lock"></i>
            </span>
            <input
              type="password"
              class="form-control"
              placeholder="Enter your password"
              id="password"
              name="password"
              required
            >
          </div>
        </div>

        <!-- Remember Me checkbox -->
        <div class="form-check mb-4">
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
          class="btn btn-primary w-100"
        >
          Sign In
        </button>
      </form>

      <?php if (!empty($message)): ?>
      <script>
        $(document).ready(function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                showCloseButton: true,
                timer: 4000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                },
                customClass: {
                    popup: 'modern-toast'
                }
            });

            Toast.fire({
                icon: 'error',
                title: 'Access Denied',
                html: '<div style="display: flex; align-items: center; gap: 8px;">' +
                      '<div style="flex-grow: 1;"><?php echo addslashes($message); ?><br>' +
                      '<small style="color: #666;">Please check your credentials and try again.</small></div>' +
                      '</div>',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp animate__faster'
                }
            });
        });
      </script>
      <?php endif; ?>
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
