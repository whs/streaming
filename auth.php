<?php
require 'config.php';

/*
set $_SESSION['user'] = (object) array(
	"id" => "..",
	"avatar" => "..",
	"name" => ".."
);
*/

$protocol = $_SERVER['HTTPS'] ? 'https' : 'http';
$redirect = $protocol."://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

header("Content-Type: text/plain; charset=UTF-8");

if(isset($_SESSION['fb_token'])){
	$userData = json_decode(http('https://graph.facebook.com/v2.6/me?fields=first_name,id&access_token=' . $_SESSION['fb_token']));
	$avatar = json_decode(http('https://graph.facebook.com/v2.6/me/picture?redirect=0&type=small&access_token=' . $_SESSION['fb_token']));
	$_SESSION['user'] = (object) array(
		'id' => $userData->id,
		'name' => $userData->first_name,
		'avatar' => $avatar->data->url
	);

	header('Location: .');
}else if(isset($_GET['code'])){
	$token = json_decode(http('https://graph.facebook.com/v2.6/oauth/access_token?client_id=' . FB_ID . '&redirect_uri=' . $redirect . '&client_secret=' . FB_SECRET . '&code=' . $_GET['code']));

	if(!isset($token->access_token)){
		echo 'Cannot log you in: ' . $tokenData;
		die();
	}

	$_SESSION['fb_token'] = $token->access_token;
	header("Location: auth.php");
}else{
	header('Location: https://www.facebook.com/v2.6/dialog/oauth?client_id=' . FB_ID . '&redirect_uri=' . $redirect);
}
