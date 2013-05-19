<?php
require "config.php";
if(!$_SESSION['user']){
	print "No login";
	die();
}
$user = $_SESSION['user'];

if(!isset($_POST['text']) || trim($_POST['text']) == ""){
	die();
}
ws_push(CHAT_CHAT, array(
	"message" => $_POST['text'],
	"user" => array(
		"id" => $user->id,
		"name" => $user->name,
		"avatar" => $user->avatar
	),
	"time" => time()
));