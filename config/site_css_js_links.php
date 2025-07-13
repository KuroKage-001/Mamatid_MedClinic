<?php
// Determine if we're in a subdirectory by checking the script path
$in_subdirectory = (strpos($_SERVER['SCRIPT_NAME'], '/system/') !== false);
$in_client_portal = (strpos($_SERVER['SCRIPT_NAME'], '/client_portal/') !== false);
$base_path = $in_subdirectory ? '../..' : ($in_client_portal ? '..' : '.');
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="<?php echo $base_path; ?>/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="<?php echo $base_path; ?>/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="<?php echo $base_path; ?>/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

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

<!-- DataTables JS -->
<script src="<?php echo $base_path; ?>/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo $base_path; ?>/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo $base_path; ?>/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo $base_path; ?>/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="<?php echo $base_path; ?>/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?php echo $base_path; ?>/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="<?php echo $base_path; ?>/plugins/jszip/jszip.min.js"></script>
<script src="<?php echo $base_path; ?>/plugins/pdfmake/pdfmake.min.js"></script>
<script src="<?php echo $base_path; ?>/plugins/pdfmake/vfs_fonts.js"></script>
<script src="<?php echo $base_path; ?>/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="<?php echo $base_path; ?>/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="<?php echo $base_path; ?>/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

<script src="<?php echo $base_path; ?>/dist/js/jquery_confirm/jquery-confirm.js"></script>

<script src="<?php echo $base_path; ?>/dist/js/common_javascript_functions.js"></script>

<script src="<?php echo $base_path; ?>/dist/js/sidebar.js"></script>

<script>
    $('.dataTable').find('td').addClass("px-2 py-1 align-middle")
    $('.dataTable').find('th').addClass("p-1 align-middle")
</script> 