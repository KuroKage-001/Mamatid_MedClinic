<?php
include './config/connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';

if (isset($_POST['reset_password'])) {
    $email = trim($_POST['email']);
    
    // Check if email exists in database
    $query = "SELECT id, full_name FROM clients WHERE email = ?";
    $stmt = $con->prepare($query);
    $stmt->execute([$email]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($client) {
        // Generate random token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        try {
            $con->beginTransaction();
            
            // Store reset token in database
            $query = "UPDATE clients SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";
            $stmt = $con->prepare($query);
            $stmt->execute([$token, $expiry, $client['id']]);
            
            // Create reset link
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . 
                        dirname($_SERVER['PHP_SELF']) . 
                        "/reset_password.php?token=" . $token;
            
            // Email content
            $to = $email;
            $subject = "Password Reset - Mamatid Health Center";
            $message_body = "Dear " . $client['full_name'] . ",\n\n" .
                          "You have requested to reset your password. Please click the link below to reset your password:\n\n" .
                          $resetLink . "\n\n" .
                          "This link will expire in 1 hour.\n\n" .
                          "If you did not request this password reset, please ignore this email.\n\n" .
                          "Best regards,\n" .
                          "Mamatid Health Center";
            
            // Send email
            mail($to, $subject, $message_body);
            
            $con->commit();
            $message = "Password reset instructions have been sent to your email.";
        } catch(PDOException $ex) {
            $con->rollback();
            $message = "An error occurred. Please try again later.";
        }
    } else {
        $message = "Email address not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - Mamatid Health Center</title>
    <?php include './config/site_css_links.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">

    <style>
        body.login-page.light-mode {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                        url('dist/img/bg-001.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .login-box {
            margin-top: 20vh;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
        }
        .card-header {
            border-radius: 15px 15px 0 0 !important;
            background-color: transparent !important;
        }
        .text-stroked {
            color: white;
            text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
        }
        .input-group-text {
            border-right: none;
            background-color: transparent;
        }
        .form-control {
            border-left: none;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: none;
        }
        .btn-primary {
            background-color: #3c4b64;
            border-color: #3c4b64;
        }
        .btn-primary:hover {
            background-color: #2d3a4e;
            border-color: #2d3a4e;
        }
        .alert {
            border-radius: 10px;
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
                 class="img-thumbnail p-0 border rounded-circle" style="width: 100px;" alt="System Logo">
            <div class="text-center h3 mb-0 text-stroked">
                <strong>Password Recovery</strong>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div id="alertMessage" class="alert alert-info alert-dismissible fade show">
                        <i class="fas fa-info-circle mr-1"></i> <?php echo $message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="text-center mb-4">
                    <i class="fas fa-lock-open fa-3x text-primary mb-3"></i>
                    <p class="text-muted">
                        Enter your email address and we'll send you instructions to reset your password.
                    </p>
                </div>

                <form method="post">
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-envelope text-primary"></i>
                            </span>
                        </div>
                        <input type="email" class="form-control form-control-lg" 
                               name="email" required
                               placeholder="Enter your email address">
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" name="reset_password" 
                                    class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-paper-plane mr-1"></i> Send Reset Link
                            </button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <a href="client_login.php" class="btn btn-link text-primary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include './config/site_js_links.php'; ?>
    
    <script>
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