<?php
session_start();
include '../config/db_connection.php';
include '../system/phpmailer/system/mailer.php';

// Initialize variables
$message = '';
$error = '';

// Function to generate a secure random token
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Function to check if user has exceeded reset attempts
function checkResetAttempts($email, $con) {
    $query = "SELECT COUNT(*) as attempts, MIN(created_at) as first_attempt 
              FROM client_password_resets 
              WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt = $con->prepare($query);
    $stmt->execute([$email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Allow maximum 3 attempts per 24 hours
    if ($result['attempts'] >= 3) {
        $waitTime = strtotime($result['first_attempt']) + 86400 - time();
        if ($waitTime > 0) {
            $hours = floor($waitTime / 3600);
            $minutes = floor(($waitTime % 3600) / 60);
            return "For security reasons, please wait {$hours}h {$minutes}m before requesting another reset.";
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Check if email exists in clients table
        $query = "SELECT id, full_name FROM clients_user_accounts WHERE email = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$email]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            // For security, don't reveal if email exists or not
            $_SESSION['alert_type'] = 'success';
            $_SESSION['alert_message'] = 'If your email is registered, you will receive password reset instructions shortly.';
            header("Location: ../client_login.php");
            exit;
        }
        
        // Check reset attempts
        $attemptError = checkResetAttempts($email, $con);
        if ($attemptError) {
            throw new Exception($attemptError);
        }
        
        // Generate token and expiry
        $token = generateSecureToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Begin transaction
        $con->beginTransaction();
        
        // Insert reset token
        $insertQuery = "INSERT INTO client_password_resets (email, token, expiry, created_at) 
                       VALUES (?, ?, ?, NOW())";
        $stmt = $con->prepare($insertQuery);
        $stmt->execute([$email, $token, $expiry]);
        
        // Generate reset link
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . 
                    dirname(dirname($_SERVER['PHP_SELF'])) . 
                    "/common_service/client_reset_password.php?token=" . $token;
        
        // Prepare email content
        $emailBody = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Password Reset - Mamatid Health Center</title>
            <style>
                /* ... existing styles ... */
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2 style="margin:0;">Mamatid Health Center</h2>
                    <p style="margin:5px 0 0 0;">Password Reset Request</p>
                </div>
                <div class="content">
                    <p>Dear ' . htmlspecialchars($client['full_name']) . ',</p>
                    
                    <p>We received a request to reset the password for your account at Mamatid Health Center. If you did not make this request, please ignore this email.</p>
                    
                    <p>To reset your password, click the button below:</p>
                    
                    <div style="text-align:center;">
                        <a href="' . htmlspecialchars($resetLink) . '" class="reset-button">Reset My Password</a>
                    </div>
                    
                    <div class="warning">
                        <p><strong>Important:</strong></p>
                        <ul>
                            <li>This link will expire in 1 hour</li>
                            <li>You can only use this link once</li>
                            <li>For security reasons, you are limited to 3 reset requests per 24 hours</li>
                        </ul>
                    </div>
                    
                    <p>If the button above doesn\'t work, copy and paste this link into your browser:</p>
                    <p style="word-break:break-all;">' . htmlspecialchars($resetLink) . '</p>
                    
                    <p>For security reasons, if you did not request this password reset, please:</p>
                    <ol>
                        <li>Leave this link unused (it will expire automatically)</li>
                        <li>Review your account security</li>
                        <li>Contact us if you notice any suspicious activity</li>
                    </ol>
                </div>
                <div class="footer">
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; ' . date('Y') . ' Mamatid Health Center. All rights reserved.</p>
                    <p>Address: 123 Mamatid Street, Cabuyao City, Laguna</p>
                </div>
            </div>
        </body>
        </html>';
        
        // Send email
        $emailResult = sendEmail(
            $email,
            'Password Reset Request - Mamatid Health Center',
            $emailBody,
            $client['full_name']
        );
        
        if (!$emailResult['success']) {
            throw new Exception("Failed to send reset email. Please try again later.");
        }
        
        // Commit transaction
        $con->commit();
        
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'If your email is registered, you will receive password reset instructions shortly.';
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
    <title>Forgot Password - Mamatid Health Center</title>

    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="../plugins/sweetalert2/sweetalert2.min.css">
    <link rel="icon" type="image/png" href="../dist/img/logo01.png">
    
    <!-- Include jQuery and SweetAlert2 JS -->
    <script src="../plugins/jquery/jquery.min.js"></script>
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

        .forgot-password-container {
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
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .info-text {
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
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
</head>

<body>
    <div class="forgot-password-container">
        <div class="logo-container">
            <img src="../dist/img/mamatid-transparent01.png" alt="Mamatid Health Center Logo">
        </div>
        
        <h2>Reset Your Password</h2>
        
        <p class="info-text">
            Enter your email address below and we'll send you instructions to reset your password.
        </p>

        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-group">
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        placeholder="Enter your registered email"
                    >
                    <i class="fas fa-envelope"></i>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                Send Reset Instructions
            </button>
        </form>

        <a href="../client_login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>
</body>
</html> 