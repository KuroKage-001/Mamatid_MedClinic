<?php
session_start();

// Path adjustments for files in subdirectories
$base_path = '../..';
$common_service_path = "$base_path/common_service";

// Include role functions using adjusted path
require_once "$common_service_path/role_functions.php";

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("location:$base_path/index.php");
    exit;
}

// Get the required role from URL parameter if available
$required_role = isset($_GET['required_role']) ? $_GET['required_role'] : 'appropriate';

// Get the current user's role
$current_role = getUserRole();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Access Denied | MHC</title>
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

    <style>
        .error-page {
            padding: 70px 0;
            background: #fff;
            min-height: calc(100vh - 200px);
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
        
        .btn-back {
            background: linear-gradient(135deg, #3699FF 0%, #6993FF 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(54, 153, 255, 0.4);
            color: white;
            text-decoration: none;
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
                <div class="error-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <h1>403</h1>
                <h2>Access Denied</h2>
                <p>
                    Sorry, you don't have permission to access this page. 
                    You need <strong><?php echo getRoleDisplayName($required_role); ?></strong> 
                    privileges to view this content.
                </p>
                <p class="text-muted">
                    Your current role: <strong><?php echo getRoleDisplayName($current_role); ?></strong>
                </p>
                <a href="<?php echo $base_path; ?>/admin_dashboard.php" class="btn-back">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
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