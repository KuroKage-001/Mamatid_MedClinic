<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

require_once './common_service/role_functions.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Access Denied | MHC</title>
    <?php include './config/site_css_links.php'; ?>
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

<?php include './config/header.php'; include './config/sidebar.php'; ?>

<!-- Content Wrapper -->
<div class="content-wrapper">
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
                    You need <strong><?php echo isset($_GET['required_role']) ? getRoleDisplayName($_GET['required_role']) : 'appropriate'; ?></strong> 
                    privileges to view this content.
                </p>
                <p class="text-muted">
                    Your current role: <strong><?php echo getRoleDisplayName(getUserRole()); ?></strong>
                </p>
                <a href="dashboard.php" class="btn-back">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </section>
</div>

<?php include './config/footer.php'; ?>
</div>

<?php include './config/site_js_links.php'; ?>

</body>
</html> 