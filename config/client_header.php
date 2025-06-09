<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-dark navbar-light fixed-top">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>
    <!-- Brand -->
    <a href="#" class="navbar-brand">
        <span class="brand-text font-weight-light">Mamatid Health Center System</span>
    </a>
    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- User Menu -->
        <li class="nav-item dropdown user-menu">
            <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
                <i class="fas fa-user-circle mr-2" style="font-size: 1.5rem;"></i>
                <span class="d-none d-md-inline">Hello, <?php echo $_SESSION['client_name']; ?>!</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right dropdown-menu-dark">
                <div class="dropdown-header">
                    <strong><?php echo $_SESSION['client_name']; ?></strong>
                    <small class="text-muted d-block">Client Portal</small>
                </div>
                <div class="dropdown-divider"></div>
                <a href="client_logout.php" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Logout
                </a>
            </div>       
        </li>
    </ul>
</nav>

<style>
/* Header Dropdown Styling for Client Portal */
.user-menu .nav-link {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    transition: all 0.3s;
    color: #fff !important;
}

.user-menu .nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
}

.dropdown-menu-dark {
    background: #2D2D3A;
    border: none;
    border-radius: 10px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
    padding: 0.5rem 0;
    margin-top: 0.5rem;
    min-width: 200px;
}

.dropdown-header {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

.dropdown-divider {
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    margin: 0.5rem 0;
}

.dropdown-item {
    color: #fff;
    padding: 0.6rem 1rem;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.dropdown-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.dropdown-item i {
    width: 20px;
    text-align: center;
}

.dropdown-item.text-danger {
    color: #F64E60 !important;
}

.dropdown-item.text-danger:hover {
    background: rgba(246, 78, 96, 0.1);
}
</style>