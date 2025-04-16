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
    
    <!-- Include SweetAlert2 CSS -->
    <link rel="stylesheet" href="plugins/sweetalert2/sweetalert2.min.css">
    
    <!-- Include jQuery and SweetAlert2 JS -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/sweetalert2/sweetalert2.all.min.js"></script>

    <style>
        /* Background image styling */
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
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .card-body {
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

        /* Links styling */
        .btn-link {
            color: #3699FF;
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }

        .btn-link:hover {
            color: #187DE4;
            text-decoration: none;
        }

        /* Icon styling */
        .icon-container {
            width: 80px;
            height: 80px;
            background: rgba(54, 153, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .icon-container i {
            font-size: 2.5rem;
            color: #3699FF;
        }

        /* Message text styling */
        .message-text {
            color: #7E8299;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body class="hold-transition login-page light-mode">
    <div class="login-box">
        <!-- Logo / Branding -->
        <div class="login-logo mb-4">
            <img src="dist/img/mamatid-transparent01.png"
                 class="img-thumbnail p-0 border-0" id="system-logo" alt="System Logo">
            <div class="text-center h3 mb-0 text-stroked">
                <strong>Password Recovery</strong>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="text-center">
                    <div class="icon-container">
                        <i class="fas fa-lock-open"></i>
                    </div>
                    <p class="message-text">
                        Enter your email address and we'll send you instructions to reset your password.
                    </p>
                </div>

                <form method="post">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control"
                                   id="email" name="email" required
                                   placeholder="Enter your email address">
                        </div>
                    </div>

                    <button type="submit" name="reset_password" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane mr-2"></i> Send Reset Link
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="client_login.php" class="btn btn-link">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Login
                    </a>
                </div>

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
                            icon: '<?php echo strpos(strtolower($message), 'error') !== false ? 'error' : 'success'; ?>',
                            title: '<?php echo strpos(strtolower($message), 'error') !== false ? 'Error' : 'Success'; ?>',
                            text: '<?php echo addslashes($message); ?>',
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

    <?php include './config/site_js_links.php'; ?>
</body>
</html> 