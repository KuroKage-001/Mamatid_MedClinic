<?php
// Include admin-client session isolation functions first (before any session operations)
require_once '../system/security/admin_client_session_isolation.php';

// Initialize secure session using the isolation functions
if (!initializeSecureSession()) {
    die('Failed to initialize session. Please try again.');
}

// Include database connection after session is properly initialized
include '../config/db_connection.php';

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
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Please fill in all required fields.';
    } else {
        // Encrypt password with MD5 (note: MD5 is not recommended for production use)
        $encryptedPassword = md5($password);

        // Prepare query to fetch client details
        $query = "SELECT `id`, `full_name`, `email`
                  FROM `clients_user_accounts`
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

                // Store client data in session using safe functions
                setClientSessionVar('client_id', $row['id']);
                setClientSessionVar('client_name', $row['full_name']);
                setClientSessionVar('client_email', $row['email']);
                setClientSessionVar('client_last_activity', time()); // Set client activity timestamp
                setClientSessionVar('client_login_time', time()); // Set login timestamp
                setClientSessionVar('client_ip_address', $_SERVER['REMOTE_ADDR'] ?? 'unknown'); // Set IP address
                setClientSessionVar('client_user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'); // Set user agent
                
                // Fetch client profile picture
                $profileQuery = "SELECT profile_picture FROM clients_user_accounts WHERE id = ?";
                $profileStmt = $con->prepare($profileQuery);
                $profileStmt->execute([$row['id']]);
                $profileData = $profileStmt->fetch(PDO::FETCH_ASSOC);
                
                // Set profile picture in session
                setClientSessionVar('client_profile_picture', !empty($profileData['profile_picture']) ? $profileData['profile_picture'] : 'default_client.png');

                // Handle "Remember Me" functionality
                if (isset($_POST['remember_me'])) {
                    // Set cookie to remember the email for 30 days
                    setcookie("remembered_client_email", $email, time() + (30 * 24 * 60 * 60), "/");
                } else {
                    // Clear the cookie if "Remember Me" is unchecked
                    setcookie("remembered_client_email", "", time() - 3600, "/");
                }

                // Log successful client login
                logSessionOperation('client_login_success', [
                    'client_id' => $row['id'],
                    'client_name' => $row['full_name'],
                    'client_email' => $row['email'],
                    'has_concurrent_admin' => isset($_SESSION['user_id']) && !empty($_SESSION['user_id']),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);

                // Redirect to client dashboard
                header("Location: client_dashboard.php");
                exit;
            } else {
                // Invalid credentials
                $_SESSION['alert_type'] = 'error';
                $_SESSION['alert_message'] = 'Access Denied: Incorrect email or password. Please check your credentials and try again.';
                
                // Log failed login attempt
                logSessionOperation('client_login_failed', [
                    'email' => $email,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
            }
        } catch(PDOException $ex) {
            $_SESSION['alert_type'] = 'error';
            $_SESSION['alert_message'] = 'An error occurred. Please try again later.';
            
            // Log database error
            error_log("Client login database error: " . $ex->getMessage());
            logSessionOperation('client_login_db_error', [
                'error' => $ex->getMessage(),
                'email' => $email ?? 'unknown'
            ]);
        }
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
    <title>Client Portal - Mamatid Health Center</title>

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
            --primary-color: #00A9FF;
            --secondary-color: #89CFF3;
            --accent-color: #A0E9FF;
            --text-primary: #2B2A4C;
            --text-secondary: #4A4A4A;
            --bg-light: #F6F8FC;
            --border-color: #E1E6EF;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.02) 0%, rgba(99, 102, 241, 0.02) 100%),
                        url('../dist/img/bg-001.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.50);
            backdrop-filter: blur(3px);
        }

        .client-container {
            position: relative;
            z-index: 1;
            display: flex;
            width: 1000px;
            max-width: 95%;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .client-left {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .client-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../dist/img/bg-001.jpg') center/cover;
            opacity: 0.5;
            mix-blend-mode: overlay;
        }

        .client-left-content {
            position: relative;
            z-index: 1;
        }

        .client-left h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .client-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .client-features {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .client-features li {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .client-features li i {
            margin-right: 10px;
            background: rgba(255, 255, 255, 0.2);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .client-right {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .client-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .client-logo img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            padding: 8px;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 169, 255, 0.2);
            border: 2px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .client-logo img:hover {
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
            animation: typing 6s ease-in-out infinite;
            width: 0;
        }

        @keyframes typing {
            0% { width: 0 }
            30% { width: 100% }
            70% { width: 100% }
            100% { width: 0 }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.9);
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(0, 169, 255, 0.1);
            outline: none;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .input-group input:focus + i {
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

        .client-btn {
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

        .client-btn:hover {
            background: #0095e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 169, 255, 0.3);
        }

        .client-btn:active {
            transform: translateY(0);
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #0095e0;
        }

        @media (max-width: 768px) {
            .client-container {
                flex-direction: column;
            }

            .client-left {
                padding: 2rem;
                text-align: center;
            }

            .client-features li {
                justify-content: center;
            }

            .client-right {
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

        .forgot-password-link {
            color: #DC2626;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .forgot-password-link:hover {
            color: #B91C1C;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <?php displayAlert(); ?>
    <div class="client-container">
        <div class="client-left">
            <div class="client-left-content">
                <h1>Welcome to Your Health Portal</h1>
                <p>Access your healthcare services and manage your appointments with ease at Mamatid Health Center.</p>
                
                <ul class="client-features">
                    <li><i class="fas fa-calendar-alt"></i> View and manage all your appointments</li>
                    <li><i class="fas fa-calendar-plus"></i> Book new appointments with real-time slot availability</li>
                    <li><i class="fas fa-bell"></i> Get instant updates on appointment status changes</li>
                    <li><i class="fas fa-history"></i> Track your complete appointment history</li>
                    <li><i class="fas fa-check-circle"></i> Monitor pending and approved appointments</li>
                    <li><i class="fas fa-notes-medical"></i> Add detailed reasons for your visits</li>
                </ul>
            </div>
        </div>
        
        <div class="client-right">
            <div class="client-logo">
                <img src="../dist/img/mamatid-transparent01.png" alt="System Logo">
            </div>
            
            <div class="typewriter">
                <h2>Enter your Credentials</h2>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <input 
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            value="<?php echo htmlspecialchars($rememberedEmail); ?>"
                            required
                        >
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
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
                        <?php echo ($rememberedEmail !== '') ? 'checked' : ''; ?>
                    >
                    <label for="remember_me">Remember me</label>
                </div>

                <button type="submit" name="login" class="client-btn">
                    Sign In to Portal
                </button>
            </form>

            <div class="register-link">
                <p><a href="auth/client_forgot_password.php" class="forgot-password-link">Forgot Password?</a></p>
                <p>Don't have an account? <a href="client_register.php">Create One</a></p>
            </div>
        </div>
    </div>
</body>
</html> 