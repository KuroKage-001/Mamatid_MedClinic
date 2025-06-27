<?php
// Determine if we're in a subdirectory by checking the script path
$in_subdirectory = (strpos($_SERVER['SCRIPT_NAME'], '/system/') !== false);
$base_path = $in_subdirectory ? '../..' : '.';
?>
<footer class="main-footer fixed-bottom bg-dark text-white py-2">
  <div class="container-fluid">
    <div class="row">
      <!-- Left side: copyright -->
      <div class="col-12 col-md-6 text-center text-md-left">
        <strong>&copy; <?php echo date('Y');?>
          <a href="<?php echo $base_path; ?>/admin_dashboard.php" class="text-white text-decoration-none">Mamatid Health Center System</a>.
        </strong> All rights reserved.
      </div>
      <!-- Right side: version info -->
      <div class="col-12 col-md-6 text-center text-md-right">
        Mamatid Health Center Version 1.0
      </div>
    </div>
  </div>
</footer>

<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
  <!-- Control sidebar content goes here -->
</aside>
