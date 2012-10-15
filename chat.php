<?php
require "config.php";
if(!$_SESSION['user']){
	print "No login";
	die();
}
$user = $_SESSION['user'];

if(isset($_POST['act']) && $_POST['act'] == "sync"){
	ws_push("streaming/chat", "sync", $_POST['data']);
}else if(isset($_GET['act']) && $_GET['act'] == "online"){
	ws_push("streaming/chat", "online", array(
		"user" => array(
			"id" => $user->id,
			"name" => $user->name,
			"avatar" => $user->avatar
		)
	));
}else{
	if(!isset($_POST['text']) || trim($_POST['text']) == ""){
		die();
	}
	ws_push("streaming/chat", "chat", array(
		"message" => $_POST['text'],
		"user" => array(
			"id" => $user->id,
			"name" => $user->name,
			"avatar" => $user->avatar
		),
		"time" => time()
	));
}