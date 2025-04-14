<?php
// Include database connection
include './config/connection.php';

// Initialize an empty message string
$message = '';

// Handle login submission
if (isset($_POST['login'])) {
    // Get form inputs
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Encrypt password with MD5 (note: MD5 is not recommended for production use)
    $encryptedPassword = md5($password);

    // Prepare query to fetch client details
    $query = "SELECT `id`, `full_name`, `email`
              FROM `clients`
              WHERE `email` = ? AND `password` = ?";

    try {
        // Execute the query with prepared statement
        $stmtLogin = $con->prepare($query);
        $stmtLogin->execute([$email, $encryptedPassword]);

        // Check if exactly one client was found
        $count = $stmtLogin->rowCount();
        if ($count == 1) {
            // Fetch client data
            $row = $stmtLogin->fetch(PDO::FETCH_ASSOC);

            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Store client data in session
            $_SESSION['client_id'] = $row['id'];
            $_SESSION['client_name'] = $row['full_name'];
            $_SESSION['client_email'] = $row['email'];

            // Handle "Remember Me" functionality
            if (isset($_POST['remember_me'])) {
                // Set cookie to remember the email for 30 days
                setcookie("remembered_client_email", $email, time() + (30 * 24 * 60 * 60), "/");
            } else {
                // Clear the cookie if "Remember Me" is unchecked
                setcookie("remembered_client_email", "", time() - 3600, "/");
            }

            // Redirect to client dashboard
            header("location:client_dashboard.php");
            exit;
        } else {
            // Invalid credentials
            $message = 'Incorrect email or password.';
        }
    } catch(PDOException $ex) {
        $message = 'An error occurred. Please try again later.';
    }
}

// Retrieve remembered email from cookie, if available
$rememberedEmail = isset($_COOKIE['remembered_client_email']) ? $_COOKIE['remembered_client_email'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Login - Mamatid Health Center</title>

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
    <link rel="icon" type="image/png" href="dist/img/logo01.png">

    <style>
        /* Background image styling for the login page */
        body.login-page.light-mode {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                        url('dist/img/bg-001.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        /* Login box styling: smaller and centered */
        .login-box {
            max-width: 400px;
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

        .text-stroked {
            color: white;
            text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
        }

        .cursor {
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0; }
            100% { opacity: 1; }
        }
    </style>
</head>

<body class="hold-transition login-page light-mode">
    <div class="login-box">
        <!-- Logo / Branding -->
        <div class="login-logo mb-4">
            <img src="dist/img/mamatid-transparent01.png"
                 class="img-thumbnail p-0 border rounded-circle" id="system-logo" alt="System Logo">
            <div class="text-center h3 mb-0 text-stroked">
                <strong>Client Portal</strong>
            </div>
        </div>

        <!-- Login Card -->
        <div class="card card-outline card-primary shadow">
            <div class="card-body login-card-body">
                <!-- Animated typewriter text -->
                <p class="login-box-msg">
                    <span id="typewriter-text"></span><span class="cursor">|</span>
                </p>

                <?php 
                if($message != '') {
                    echo '<div id="alertMessage" class="alert alert-danger text-center" role="alert" style="opacity: 0.9;">
                        <i class="fas fa-exclamation-circle"></i> '.$message.'
                    </div>';
                }
                ?>

                <!-- Login Form -->
                <form method="post" class="p-3">
                    <!-- Email field -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control form-control-lg"
                                   placeholder="Enter your email"
                                   id="email" name="email"
                                   value="<?php echo htmlspecialchars($rememberedEmail); ?>"
                                   required>
                        </div>
                    </div>

                    <!-- Password field -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control form-control-lg"
                                   placeholder="Enter your password"
                                   id="password" name="password"
                                   required>
                        </div>
                    </div>

                    <!-- Remember Me checkbox -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox"
                               id="remember_me" name="remember_me"
                               <?php echo ($rememberedEmail !== '') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remember_me">
                            Remember me
                        </label>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" name="login" class="btn btn-primary btn-block">Sign In</button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <a href="forgot_password.php" class="btn btn-link text-primary">
                                <i class="fas fa-key mr-1"></i> Forgot Password?
                            </a>
                        </div>
                        <div class="col-12">
                            <a href="client_register.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus mr-1"></i> Create New Account
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include './config/site_js_links.php'; ?>

    <!-- Typewriting animation script -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const text = "Please enter your login credentials";
            let index = 0;
            const speed = 50;  // Typing speed
            const pause = 2000; // Pause before repeating
            const typewriter = document.getElementById("typewriter-text");

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

        // Auto-hide alert after 5 seconds
        if (document.getElementById("alertMessage")) {
            setTimeout(function() {
                var alertBox = document.getElementById("alertMessage");
                alertBox.style.transition = "opacity 0.5s ease";
                alertBox.style.opacity = "0";
                setTimeout(() => alertBox.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html> 