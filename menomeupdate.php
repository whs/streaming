<?php
require_once 'config.php';
header("Content-Type: text/plain");
date_default_timezone_set('UTC');

if(!isset($_SESSION['menome_token'])){
	print 'ไม่มี token';
	die();
}
header('X-Token: '.$_SESSION['menome_token']);
if(!isset($_POST['anime']) || !isset($_POST['episode'])){
	print 'ข้อมูลไม่ครบ';
	die();
}
$anime = http('https://api.menome.in.th/1/anime/'.$_POST['anime'].'.json');
$anime = json_decode($anime, true);
if(empty($anime)){
	print 'ไม่พบข้อมูลอนิเมะ';
	die();
}
$animelist = http('https://api.menome.in.th/1/user/animelist.json?filter='.$_POST['anime'].'&access_token='.$_SESSION['menome_token']);
$animelist = json_decode($animelist, true);
$thisAnime = array();
foreach($animelist as $anAnime){
	if($anAnime['anime_id'] === $_POST['anime']){
		$thisAnime = $anAnime;
		break;
	}
}
$status = (int) $_POST['episode'] >= $anime['episodes'] ? 'Completed' : 'Watching';
if(empty($thisAnime)){
	$thisAnime = array(
		'from' => date('Y-m-d'),
		'rewatch' => 0
	);
}
$thisAnime['status'] = $status;
$thisAnime['access_token'] = $_SESSION['menome_token'];
$thisAnime['watched'] = (int) $_POST['episode'];
header('X-URL: '.'https://api.menome.in.th/1/user/animelist/'.$_POST['anime'].'.json');
print http('https://api.menome.in.th/1/user/animelist/'.$_POST['anime'].'.json', 'POST', $thisAnime);
print 'อัพเดตเรียบร้อบ';