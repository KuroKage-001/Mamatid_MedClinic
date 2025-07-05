<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if this is an account settings page
$is_admin_settings = strpos($_GET['page'] ?? '', 'account_admin_settings.php') !== false;
$is_client_settings = strpos($_GET['page'] ?? '', 'account_client_settings.php') !== false;

// If user is already logged in as admin/staff, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("location:../../admin_dashboard.php");
    exit;
}

// If user is already logged in as client and trying to access client settings
if (isset($_SESSION['client_id']) && $is_client_settings) {
    header("location:../../account_client_settings.php");
    exit;
}

// If user is already logged in as client but trying to access admin settings
if (isset($_SESSION['client_id']) && $is_admin_settings) {
    header("location:../../system/security/access_denied.php?required_role=admin");
    exit;
}

// Get the page that was attempted to be accessed
$attempted_page = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'a protected page';

// Determine the correct login page based on the attempted access
$login_url = "../../index.php";
if ($is_client_settings) {
    $login_url = "../../client_login.php";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Unauthorized Access | MHC</title>
    <?php include '../../config/site_css_links.php'; ?>
    <link rel="icon" type="image/png" href="../../dist/img/logo01.png">
    <style>
        .error-page {
            padding: 70px 0;
            background: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-page h1 {
            font-size: 120px;
            font-weight: 700;
            color: #F64E60;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .error-page h2 {
            font-size: 32px;
            font-weight: 600;
            color: #1a1a2d;
            margin-bottom: 20px;
        }
        
        .error-page p {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 30px;
        }
        
        .error-content {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background: #fff;
        }
        
        .error-icon {
            font-size: 180px;
            color: #F64E60;
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .btn-login {
            background: linear-gradient(135deg, #3699FF 0%, #6993FF 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            margin-right: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(54, 153, 255, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #20C997 0%, #1BC5BD 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(32, 201, 151, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .logo-container {
            margin-bottom: 30px;
        }
        
        .logo-container img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            padding: 8px;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 169, 255, 0.2);
            border: 2px solid #3699FF;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="container">
            <div class="error-content">
                <div class="logo-container">
                    <img src="../../dist/img/mamatid-transparent01.png" alt="System Logo">
                </div>
                <div class="error-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h1>401</h1>
                <h2>Unauthorized Access</h2>
                <p>
                    You need to login first to access <strong><?php echo $attempted_page; ?></strong>.
                </p>
                <p class="text-muted">
                    Please login with your credentials to continue.
                </p>
                <div>
                    <a href="<?php echo $login_url; ?>" class="btn-login">
                        <i class="fas fa-sign-in-alt mr-2"></i> Login
                    </a>
                    <a href="../../index.php" class="btn-home">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../config/site_js_links.php'; ?>
</body>
</html>