<?php
include './config/connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';
$validToken = false;

if ($token) {
    // Check if token exists and is not expired
    $query = "SELECT id FROM clients WHERE reset_token = ? AND reset_token_expiry > NOW()";
    $stmt = $con->prepare($query);
    $stmt->execute([$token]);
    $validToken = $stmt->rowCount() > 0;
}

if (isset($_POST['update_password'])) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $token = $_POST['token'];

    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        try {
            $con->beginTransaction();
            
            // Update password and clear reset token
            $query = "UPDATE clients 
                     SET password = ?, 
                         reset_token = NULL, 
                         reset_token_expiry = NULL 
                     WHERE reset_token = ? AND reset_token_expiry > NOW()";
            
            $stmt = $con->prepare($query);
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $token]);
            
            if ($stmt->rowCount() > 0) {
                $con->commit();
                $message = "Password has been reset successfully. You can now login with your new password.";
                header("refresh:3;url=client_login.php");
            } else {
                $con->rollback();
                $message = "Invalid or expired reset token.";
            }
        } catch(PDOException $ex) {
            $con->rollback();
            $message = "An error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Mamatid Health Center</title>
    <?php include './config/site_css_links.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">

    <style>
        body {
            background: url('dist/img/mamatid.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .login-box {
            margin-top: 20vh;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.9);
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <img src="dist/img/logo01.png" style="width: 100px;">
                <h1 class="h3 mt-2">Reset Password</h1>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-info">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($validToken): ?>
                    <p class="login-box-msg">Enter your new password.</p>

                    <form method="post">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="input-group mb-3">
                            <input type="password" class="form-control" 
                                   name="password" required minlength="6"
                                   placeholder="New Password">
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="input-group mb-3">
                            <input type="password" class="form-control" 
                                   name="confirm_password" required minlength="6"
                                   placeholder="Confirm New Password">
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" name="update_password" 
                                        class="btn btn-primary btn-block">
                                    Update Password
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Invalid or expired reset token. Please request a new password reset.
                    </div>
                    <p class="mt-3 mb-1">
                        <a href="forgot_password.php">Request New Reset Link</a>
                    </p>
                <?php endif; ?>

                <p class="mt-3 mb-1">
                    <a href="client_login.php">Back to Login</a>
                </p>
            </div>
        </div>
    </div>

    <?php include './config/site_js_links.php'; ?>
</body>
</html> 