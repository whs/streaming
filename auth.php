<?php
require 'config.php';

/*
set $_SESSION['user'] = (object) array(
	"id" => "..",
	"avatar" => "..",
	"name" => ".."
);
*/

$redirect = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

header("Content-Type: text/plain; charset=UTF-8");

if(isset($_SESSION['fb_token'])){
	$userData = json_decode(http('https://graph.facebook.com/me?access_token=' . $_SESSION['fb_token']));
	$avatar = json_decode(http('https://graph.facebook.com/me/picture?redirect=false&type=small&access_token=' . $_SESSION['fb_token']));
	$_SESSION['user'] = (object) array(
		'id' => $userData->id,
		'name' => $userData->first_name,
		'avatar' => $avatar->data->url
	);

	if(!empty(FB_REQUIRE_EVENT)){
		$eventData = json_decode(http('https://graph.facebook.com/' . FB_REQUIRE_EVENT . '/invited/?access_token=' . $_SESSION['fb_token']));

		$allowed = false;
		foreach($eventData->data as $rsvp){
			if($rsvp->id === $userData->id){
				$allowed = true;
				break;
			}
		}
		if(!$allowed){
			unset($_SESSION['user']);
			echo 'Access denied: Please join the event at https://www.facebook.com/events/' . FB_REQUIRE_EVENT . '/' . "\n";
			echo 'You\'re logged in as ' . $userData->name;
			die();
		}
	}

	header('Location: .');
}else if(isset($_GET['code'])){
	$tokenData = http('https://graph.facebook.com/oauth/access_token?client_id=' . FB_ID . '&redirect_uri=' . $redirect . '&client_secret=' . FB_SECRET . '&code=' . $_GET['code']);
	parse_str($tokenData, $token);

	if(!isset($token['access_token'])){
		echo 'Cannot log you in: ' . $tokenData;
		die();
	}

	$_SESSION['fb_token'] = $token['access_token'];
	header("Location: auth.php");
}else{
	header('Location: https://www.facebook.com/dialog/oauth?client_id=' . FB_ID . '&redirect_uri=' . $redirect);
}