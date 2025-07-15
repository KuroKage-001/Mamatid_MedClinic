<?php 
	include '../config/db_connection.php';

	// Get username from request and sanitize
	$userName = isset($_GET['user_name']) ? trim($_GET['user_name']) : '';

	// Use proper parameter binding to prevent SQL injection
	$query = "SELECT COUNT(*) as `count` FROM `users` WHERE `user_name` = :user_name";
	$stmt = $con->prepare($query);
	$stmt->bindParam(':user_name', $userName, PDO::PARAM_STR);
	$stmt->execute();

	$r = $stmt->fetch(PDO::FETCH_ASSOC);
	$count = $r['count'];

	echo $count;
?>