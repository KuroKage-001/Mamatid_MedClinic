<?php
// Determine if we're in a subdirectory by checking the script path
$in_subdirectory = (strpos($_SERVER['SCRIPT_NAME'], '/system/') !== false);
$base_path = $in_subdirectory ? '../..' : '.';
?>
<!-- jQuery -->
<script src="<?php echo $base_path; ?>/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?php echo $base_path; ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo $base_path; ?>/dist/js/adminlte.min.js"></script>
<!-- SweetAlert2 -->
<script src="<?php echo $base_path; ?>/plugins/sweetalert2/sweetalert2.all.min.js"></script>
<!-- AdminLTE for demo purposes -->
<!-- <script src="dist/js/demo.js"></script> -->


<script src="<?php echo $base_path; ?>/dist/js/jquery_confirm/jquery-confirm.js"></script>

<script src="<?php echo $base_path; ?>/dist/js/common_javascript_functions.js"></script>

<script src="<?php echo $base_path; ?>/dist/js/sidebar.js"></script>

