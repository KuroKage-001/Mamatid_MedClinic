<?php
// Get the current page filename for active state checking
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="client_dashboard.php" class="brand-link logo-switch">
        <img src="dist/img/logo01.png" alt="Logo" class="brand-image img-circle elevation-3 mb-0" style="opacity: .8">
        <span class="brand-text">Client Portal</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="info">
                <a href="#" class="d-block"><?php echo $_SESSION['client_name']; ?></a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="client_dashboard.php" class="nav-link <?php echo ($current_page == 'client_dashboard.php' ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="book_appointment.php" class="nav-link <?php echo ($current_page == 'book_appointment.php' ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-calendar-plus"></i>
                        <p>Book Appointment</p>
                    </a>
                </li>
                <li class="nav-item mt-auto">
                    <a href="client_logout.php" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside> 