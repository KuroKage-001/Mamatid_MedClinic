<?php
session_start();

// Path adjustments for files in subdirectories
$base_path = '../..';
$common_service_path = "$base_path/common_service";

// Include role functions using adjusted path
require_once "$base_path/system/utilities/admin_client_role_functions_services.php";

// Determine the type of access control needed
$access_type = 'unauthorized'; // Default to 401 unauthorized
$error_code = '401';
$error_title = 'Unauthorized Access';
$error_icon = 'fas fa-lock';

// Check if this is an access denied scenario (user logged in but insufficient permissions)
if (isset($_GET['required_role']) && (isset($_SESSION['user_id']) || isset($_SESSION['client_id']))) {
    $access_type = 'access_denied';
    $error_code = '403';
    $error_title = 'Access Denied';
    $error_icon = 'fas fa-ban';
}

// Handle different scenarios based on access type
if ($access_type === 'unauthorized') {
    // 401 Unauthorized - User not logged in
    
    // Check if this is an account settings page
    $is_admin_settings = strpos($_GET['page'] ?? '', 'account_admin_settings.php') !== false;
    $is_client_settings = strpos($_GET['page'] ?? '', 'account_client_settings.php') !== false;

    // If user is already logged in as admin/staff, redirect to dashboard
    if (isset($_SESSION['user_id'])) {
        header("location:$base_path/admin_dashboard.php");
        exit;
    }

    // If user is already logged in as client and trying to access client settings
    if (isset($_SESSION['client_id']) && $is_client_settings) {
        header("location:$base_path/account_client_settings.php");
        exit;
    }

    // If user is already logged in as client but trying to access admin settings
    if (isset($_SESSION['client_id']) && $is_admin_settings) {
        header("location:$base_path/system/security/admin_client_unauthorized_access_control.php?required_role=admin");
        exit;
    }

    // Get the page that was attempted to be accessed
    $attempted_page = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'a protected page';

    // Determine the correct login page based on the attempted access
    $login_url = "$base_path/index.php";
    if ($is_client_settings) {
        $login_url = "$base_path/client_login.php";
    }
    
} else {
    // 403 Access Denied - User logged in but insufficient permissions
    
    // Redirect to login if not authenticated (fallback)
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['client_id'])) {
        header("location:$base_path/index.php");
        exit;
    }

    // Get the required role from URL parameter if available
    $required_role = isset($_GET['required_role']) ? $_GET['required_role'] : 'appropriate';

    // Get the current user's role
    $current_role = getUserRole();
    if (!$current_role && isset($_SESSION['client_id'])) {
        $current_role = 'client';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $error_title; ?> | MHC</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>/plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/dist/js/jquery_confirm/jquery-confirm.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/dist/css/default.css" />
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>/dist/img/logo01.png">

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
        
        .btn-login, .btn-back {
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
        
        .btn-login:hover, .btn-back:hover {
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
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
<div class="wrapper">

<!-- Content Wrapper (without including header/sidebar) -->
<div class="content-wrapper" style="margin-left: 0; min-height: 100vh;">
    <section class="error-page">
        <div class="container">
            <div class="error-content">
                <div class="logo-container">
                    <img src="<?php echo $base_path; ?>/dist/img/mamatid-transparent01.png" alt="System Logo">
                </div>
                <div class="error-icon">
                    <i class="<?php echo $error_icon; ?>"></i>
                </div>
                <h1><?php echo $error_code; ?></h1>
                <h2><?php echo $error_title; ?></h2>
                
                <?php if ($access_type === 'unauthorized'): ?>
                    <!-- 401 Unauthorized Access Content -->
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
                        <a href="<?php echo $base_path; ?>/index.php" class="btn-home">
                            <i class="fas fa-home mr-2"></i> Home
                        </a>
                    </div>
                    
                <?php else: ?>
                    <!-- 403 Access Denied Content -->
                    <p>
                        Sorry, you don't have permission to access this page. 
                        You need <strong><?php echo getRoleDisplayName($required_role); ?></strong> 
                        privileges to view this content.
                    </p>
                    <p class="text-muted">
                        Your current role: <strong><?php echo getRoleDisplayName($current_role); ?></strong>
                    </p>
                    <div>
                        <a href="<?php echo $base_path; ?>/admin_dashboard.php" class="btn-back">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                        </a>
                        <a href="<?php echo $base_path; ?>/index.php" class="btn-home">
                            <i class="fas fa-home mr-2"></i> Home
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

</div>

<!-- jQuery -->
<script src="<?php echo $base_path; ?>/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?php echo $base_path; ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo $base_path; ?>/dist/js/adminlte.min.js"></script>
<!-- SweetAlert2 -->
<script src="<?php echo $base_path; ?>/plugins/sweetalert2/sweetalert2.all.min.js"></script>

</body>
</html> 