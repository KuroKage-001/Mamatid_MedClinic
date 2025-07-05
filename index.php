<?php
session_start();
include './config/db_connection.php';

// Alert Handler Code
if (!defined('ALERT_HANDLER_INCLUDED')) {
    define('ALERT_HANDLER_INCLUDED', true);

    // Function to display alert message using SweetAlert2
    if (!function_exists('displayAlert')) {
        function displayAlert() {
            if (isset($_SESSION['alert_message'])): ?>
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
                            icon: '<?php echo $_SESSION['alert_type']; ?>',
                            title: '<?php echo addslashes($_SESSION['alert_message']); ?>',
                            showClass: {
                                popup: 'animate__animated animate__fadeInDown animate__faster'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__fadeOutUp animate__faster'
                            }
                        });
                    });
                </script>
                <?php
                unset($_SESSION['alert_message']);
                unset($_SESSION['alert_type']);
            endif;
        }
    }
}

// Initialize an empty message string
$message = '';

// Handle login submission
if (isset($_POST['login'])) {
    // Get form inputs
    $userName = $_POST['user_name'];
    $password = $_POST['password'];

    // Encrypt password with MD5 (note: MD5 is not recommended for production use)
    $encryptedPassword = md5($password);

    // Prepare query to fetch user details with role and status
    $query = "SELECT `id`, `display_name`, `user_name`, `profile_picture`, `role`, `status`
              FROM `admin_user_accounts`
              WHERE `user_name` = :user_name
                AND `password` = :password
                AND `status` = 'active'";

    try {
        // Execute the query
        $stmtLogin = $con->prepare($query);
        $stmtLogin->bindParam(':user_name', $userName, PDO::PARAM_STR);
        $stmtLogin->bindParam(':password', $encryptedPassword, PDO::PARAM_STR);
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
            $_SESSION['role']            = $row['role'];
            $_SESSION['admin_last_activity'] = time(); // Set admin activity timestamp

            // Handle "Remember Me" functionality
            if (isset($_POST['remember_me'])) {
                // Set cookie to remember the username for 30 days
                setcookie("remembered_username", $userName, time() + (30 * 24 * 60 * 60), "/");
            } else {
                // Clear the cookie if "Remember Me" is unchecked
                setcookie("remembered_username", "", time() - 3600, "/");
            }

            // Redirect to dashboard
            header("location:admin_dashboard.php");
            exit;
        } else {
            // Invalid credentials or inactive account
            $_SESSION['alert_type'] = 'error';
            $_SESSION['alert_message'] = 'Access Denied: Incorrect username or password, or account is inactive. Please check your credentials and try again.';
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
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Login - Mamatid Health Center System</title>

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- AdminLTE CSS -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="plugins/sweetalert2/sweetalert2.min.css">
  <!-- Animate.css -->
  <link rel="stylesheet" href="dist/css/animate.min.css">
  
  <!-- Include jQuery and SweetAlert2 JS -->
  <script src="plugins/jquery/jquery.min.js"></script>
  <script src="plugins/sweetalert2/sweetalert2.all.min.js"></script>

  <style>
    :root {
      --primary-color: #4F46E5;
      --primary-hover: #4338CA;
      --secondary-color: #6366F1;
      --text-primary: #1F2937;
      --text-secondary: #6B7280;
      --bg-light: #F9FAFB;
      --border-color: #E5E7EB;
    }

    * {
      font-family: 'Inter', sans-serif;
    }

    body.login-page {
      background: linear-gradient(135deg, rgba(79, 70, 229, 0.02) 0%, rgba(99, 102, 241, 0.02) 100%),
                  url('dist/img/bg-001.jpg') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    body.login-page::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.50);
      backdrop-filter: blur(2px);
    }

    .login-wrapper {
      position: relative;
      z-index: 1;
      display: flex;
      width: 1000px;
      max-width: 95%;
      background: rgba(255, 255, 255, 0.85);
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      backdrop-filter: blur(8px);
    }

    .login-left {
      flex: 1;
      background: linear-gradient(135deg, rgba(79, 70, 229, 0.85), rgba(99, 102, 241, 0.85));
      padding: 3rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      color: white;
      position: relative;
      overflow: hidden;
    }

    .login-left::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('dist/img/bg-001.jpg') center/cover;
      opacity: 0.6;
      mix-blend-mode: multiply;
    }

    .login-left-content {
      position: relative;
      z-index: 1;
    }

    .login-left h1 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
    }

    .login-left p {
      font-size: 1.1rem;
      opacity: 0.9;
      line-height: 1.6;
    }

    .login-right {
      flex: 1;
      padding: 3rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .login-logo {
      text-align: center;
      margin-bottom: 2rem;
    }

    .login-logo img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      padding: 8px;
      background: white;
      box-shadow: 0 4px 15px rgba(0, 169, 255, 0.2);
      border: 2px solid var(--primary-color);
      transition: all 0.3s ease;
      object-fit: cover;
    }

    .login-logo img:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 20px rgba(0, 169, 255, 0.3);
    }

    /* Typewriter animation styles */
    .typewriter {
      text-align: center;
      margin-bottom: 2rem;
    }

    .typewriter h2 {
      color: var(--text-primary);
      font-size: 1.5rem;
      font-weight: 600;
      overflow: hidden;
      white-space: nowrap;
      margin: 0 auto;
      letter-spacing: 0.15em;
      animation: typing 3.5s steps(40, end);
    }

    @keyframes typing {
      from { width: 0 }
      to { width: 100% }
    }

    .login-form-group {
      margin-bottom: 1.5rem;
    }

    .login-form-group label {
      display: block;
      color: var(--text-primary);
      font-weight: 500;
      margin-bottom: 0.5rem;
    }

    .login-input-group {
      position: relative;
    }

    .login-input-group input {
      width: 100%;
      padding: 0.75rem 1rem 0.75rem 3rem;
      border: 2px solid var(--border-color);
      border-radius: 12px;
      font-size: 1rem;
      transition: all 0.3s ease;
      color: var(--text-primary);
    }

    .login-input-group input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
      outline: none;
    }

    .login-input-group i {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-secondary);
      transition: color 0.3s ease;
    }

    .login-input-group input:focus + i {
      color: var(--primary-color);
    }

    .remember-me {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 1.5rem;
    }

    .remember-me input[type="checkbox"] {
      width: 1.25rem;
      height: 1.25rem;
      border-radius: 6px;
      border: 2px solid var(--border-color);
      cursor: pointer;
    }

    .remember-me label {
      color: var(--text-secondary);
      cursor: pointer;
      user-select: none;
    }

    .login-btn {
      background: var(--primary-color);
      color: white;
      border: none;
      padding: 1rem;
      border-radius: 12px;
      font-weight: 600;
      font-size: 1rem;
      width: 100%;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .login-btn:hover {
      background: var(--primary-hover);
      transform: translateY(-1px);
    }

    .login-btn:active {
      transform: translateY(0);
    }

    @media (max-width: 768px) {
      .login-wrapper {
        flex-direction: column;
      }

      .login-left {
        padding: 2rem;
        text-align: center;
      }

      .login-right {
        padding: 2rem;
      }
    }

    .modern-toast {
      background: white !important;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
      border-radius: 16px !important;
      padding: 1rem !important;
      border-left: 4px solid #DC2626 !important;
    }

    .modern-toast .swal2-title {
      color: var(--text-primary) !important;
      font-size: 1.1rem !important;
      font-weight: 600 !important;
    }

    .modern-toast .swal2-html-container {
      color: var(--text-secondary) !important;
    }

    .modern-toast .swal2-timer-progress-bar {
      background: #DC2626 !important;
      height: 3px !important;
    }
  </style>
</head>
<body class="login-page">
  <?php displayAlert(); ?>
  
  <div class="login-wrapper">
    <div class="login-left">
      <div class="login-left-content">
        <h1>Welcome Back!</h1>
        <p>Access the Mamatid Health Center System to manage patient records, appointments, and healthcare services efficiently.</p>
      </div>
    </div>
    
    <div class="login-right">
      <div class="login-logo">
        <img src="dist/img/mamatid-logo-01.jpg.png" alt="MHC Logo">
      </div>
      
      <div class="typewriter">
        <h2>Enter your Credentials</h2>
      </div>
      
      <form method="post">
        <div class="login-form-group">
          <label for="user_name">Username</label>
          <div class="login-input-group">
            <input 
              type="text"
              id="user_name"
              name="user_name"
              placeholder="Enter your username"
              value="<?php echo htmlspecialchars($rememberedUsername); ?>"
              required
            >
            <i class="fas fa-user"></i>
          </div>
        </div>

        <div class="login-form-group">
          <label for="password">Password</label>
          <div class="login-input-group">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              required
            >
            <i class="fas fa-lock"></i>
          </div>
        </div>

        <div class="remember-me">
          <input 
            type="checkbox"
            id="remember_me"
            name="remember_me"
            <?php echo ($rememberedUsername !== '') ? 'checked' : ''; ?>
          >
          <label for="remember_me">Remember me</label>
        </div>

        <button type="submit" name="login" class="login-btn">
          Sign In
        </button>
      </form>
    </div>
  </div>
</body>
</html>
