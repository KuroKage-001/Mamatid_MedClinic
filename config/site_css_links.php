<?php
// Determine if we're in a subdirectory by checking the script path
$in_subdirectory = (strpos($_SERVER['SCRIPT_NAME'], '/system/') !== false);
$base_path = $in_subdirectory ? '../..' : '.';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Google Font: Source Sans Pro -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<!-- Font Awesome -->

<link rel="stylesheet" href="<?php echo $base_path; ?>/plugins/fontawesome-free/css/all.min.css">
<!-- Theme style -->

<link rel="stylesheet" href="<?php echo $base_path; ?>/dist/css/adminlte.min.css">

<link rel="stylesheet" href="<?php echo $base_path; ?>/dist/js/jquery_confirm/jquery-confirm.css">

<link rel="stylesheet" href="<?php echo $base_path; ?>/dist/css/default.css" />