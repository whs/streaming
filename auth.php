<?php
require "config.php";
if($_GET['code']){
	$out = http(API_HOST."1/auth/token", "POST", array(
		"grant_type" => "authorization_code",
		"code" => $_GET['code'],
		"client_secret" => API_SECRET
	));
	$out = json_decode($out);
	if(isset($out->error)){
		header("Location: ".API_HOST."1/auth/authorize?response_type=code&client_id=".API_KEY);
		die();
	}
	$at = $out->access_token;
	$user = http(API_HOST."1/user/user.json", "POST", array(
		"access_token" => $at
	));
	$user = json_decode($user);
	if($user->error){
		header("Location: ".API_HOST."1/auth/authorize?response_type=code&client_id=".API_KEY);
		die();
	}
	// check relation with me
	$myRel = http(API_HOST."1/user/whs.json?access_token=".$at);
	$whs = json_decode($myRel);
	if($whs->rel < 2){
		print 'ไม่ได้รับอนุญาต กรุณาให้ <a href="http://menome.in.th/whs">แอดมิน</a> รับ friend ก่อนเข้าชม';
		die();
	}
	$_SESSION['user'] = $user;
	$_SESSION['menome_token'] = $at;
	header("Location: .");
}else{
	header("Location: ".API_HOST."1/auth/authorize?response_type=code&client_id=".API_KEY."&scope=update_animelist");
}