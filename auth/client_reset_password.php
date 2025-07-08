<?php
session_start();
include '../system/database/db_connection.php';
include '../system/phpmailer/system/mailer.php';

// Initialize variables
$message = '';
$error = '';
$validToken = false;
$tokenEmail = '';

// Validate token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Check if token exists and is valid
        $query = "SELECT email, expiry FROM client_password_resets 
                 WHERE token = ? AND used = 0 
                 AND expiry > NOW() 
                 ORDER BY created_at DESC LIMIT 1";
        $stmt = $con->prepare($query);
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $validToken = true;
            $tokenEmail = $result['email'];
        } else {
            // Check if token exists but expired
            $query = "SELECT expiry FROM client_password_resets WHERE token = ? LIMIT 1";
            $stmt = $con->prepare($query);
            $stmt->execute([$token]);
            $expired = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($expired) {
                $error = "This password reset link has expired. Please request a new one.";
            } else {
                $error = "Invalid password reset link. Please request a new one.";
            }
        }
    } catch (PDOException $ex) {
        $error = "An error occurred. Please try again later.";
    }
} else {
    $error = "No reset token provided. Please request a password reset from the login page.";
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    try {
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validate password
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception("Passwords do not match.");
        }
        
        // Begin transaction
        $con->beginTransaction();
        
        // Update password
        $hashedPassword = md5($password); // Note: In production, use a more secure hashing method
        $updateQuery = "UPDATE clients_user_accounts SET password = ? WHERE email = ?";
        $stmt = $con->prepare($updateQuery);
        $stmt->execute([$hashedPassword, $tokenEmail]);
        
        // Mark token as used
        $markUsedQuery = "UPDATE client_password_resets SET used = 1 WHERE token = ?";
        $stmt = $con->prepare($markUsedQuery);
        $stmt->execute([$token]);
        
        // Send confirmation email
        $emailBody = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Password Changed - Mamatid Health Center</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333333;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    border: 1px solid #dddddd;
                    border-radius: 5px;
                    background-color: #ffffff;
                }
                .header {
                    background-color: #2D5A27;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    padding: 30px;
                    background-color: #ffffff;
                }
                .success-message {
                    background-color: #D1FAE5;
                    border: 1px solid #A7F3D0;
                    color: #065F46;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    font-size: 12px;
                    color: #666666;
                    margin-top: 30px;
                    border-top: 1px solid #eeeeee;
                    padding-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2 style="margin:0;">Mamatid Health Center</h2>
                    <p style="margin:5px 0 0 0;">Password Changed Successfully</p>
                </div>
                <div class="content">
                    <div class="success-message">
                        <p><strong>Your password has been successfully changed.</strong></p>
                    </div>
                    
                    <p>This email confirms that your password was changed on ' . date('F j, Y') . ' at ' . date('g:i A') . '.</p>
                    
                    <p>If you did not make this change, please contact us immediately at:</p>
                    <ul>
                        <li>Phone: (02) 888-7777</li>
                        <li>Email: support@mamatidhealth.com</li>
                    </ul>
                    
                    <p>For your security, please:</p>
                    <ul>
                        <li>Use a unique password for your Mamatid Health Center account</li>
                        <li>Never share your password with anyone</li>
                        <li>Enable two-factor authentication if available</li>
                    </ul>
                </div>
                <div class="footer">
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; ' . date('Y') . ' Mamatid Health Center. All rights reserved.</p>
                    <p>Address: 123 Mamatid Street, Cabuyao City, Laguna</p>
                </div>
            </div>
        </body>
        </html>';
        
        // Send confirmation email
        $emailResult = sendEmail(
            $tokenEmail,
            'Password Changed Successfully - Mamatid Health Center',
            $emailBody
        );
        
        // Commit transaction
        $con->commit();
        
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Your password has been successfully reset. You can now login with your new password.';
        header("Location: ../client_login.php");
        exit;
        
    } catch (Exception $ex) {
        // Rollback transaction if active
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        $error = $ex->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Mamatid Health Center</title>

    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../system_styles/default.css">
    
    <!-- Logo -->
    <link rel="icon" type="image/png" href="../dist/img/logo01.png">
    
    <!-- JavaScript Dependencies -->
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/adminlte.min.js"></script>
    <script src="../plugins/sweetalert2/sweetalert2.all.min.js"></script>

    <style>
        :root {
            --primary-color: #2D5A27;
            --secondary-color: #89CFF3;
            --text-primary: #2B2A4C;
            --text-secondary: #4A4A4A;
            --bg-light: #F6F8FC;
            --border-color: #E1E6EF;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, rgba(45, 90, 39, 0.05) 0%, rgba(45, 90, 39, 0.1) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-password-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-container img {
            width: 100px;
            height: auto;
        }

        h2 {
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(45, 90, 39, 0.1);
            outline: none;
        }

        .input-group i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .password-requirements {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        .password-requirements ul {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            width: 100%;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #234320;
            transform: translateY(-2px);
        }

        .submit-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            background: #FEE2E2;
            border: 1px solid #FCA5A5;
            color: #DC2626;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary-color);
        }
    </style>

    <script>
        function validatePasswords() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submit-btn');
            
            // Enable/disable submit button based on validation
            submitBtn.disabled = password.length < 8 || password !== confirmPassword;
            
            // Show/hide password match message
            const matchMessage = document.getElementById('password-match');
            if (confirmPassword) {
                matchMessage.style.display = 'block';
                matchMessage.style.color = password === confirmPassword ? '#065F46' : '#DC2626';
                matchMessage.innerHTML = password === confirmPassword ? 
                    '<i class="fas fa-check"></i> Passwords match' : 
                    '<i class="fas fa-times"></i> Passwords do not match';
            } else {
                matchMessage.style.display = 'none';
            }
        }
    </script>
</head>

<body>
    <div class="reset-password-container">
        <div class="logo-container">
            <img src="../dist/img/mamatid-transparent01.png" alt="Mamatid Health Center Logo">
        </div>
        
        <h2>Reset Your Password</h2>

        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($validToken): ?>
        <form method="post" onsubmit="return confirm('Are you sure you want to reset your password?');">
            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-group">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        minlength="8"
                        oninput="validatePasswords()"
                    >
                    <i class="fas fa-lock"></i>
                </div>
                <div class="password-requirements">
                    Password requirements:
                    <ul>
                        <li>At least 8 characters long</li>
                        <li>Include numbers and letters</li>
                        <li>Include special characters</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="input-group">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required
                        oninput="validatePasswords()"
                    >
                    <i class="fas fa-lock"></i>
                </div>
                <div id="password-match" style="display:none;margin-top:0.5rem;font-size:0.9rem;"></div>
            </div>

            <button type="submit" id="submit-btn" class="submit-btn" disabled>
                Reset Password
            </button>
        </form>
        <?php endif; ?>

        <a href="../client_login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>
</body>
</html> 