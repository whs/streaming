<?php
require 'config.php';
if(!$_SESSION['user']){
	header("Location: auth.php");
	die();
}
$user = $_SESSION['user'];
$config = [
	'master' => isset($_GET['master']),
	'id' => $user->id,
	'name' => $user->name,
	'avatar' => $user->avatar,
	'chat_online' => CHAT_ONLINE,
];
if($config['master']){
	$config['chat_master'] = CHAT_MASTER;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Streaming</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/4.1.1/normalize.min.css">
	<link rel="stylesheet" href="assets/style.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/video.js/5.7.1/video-js.min.css">
	<script src="PushStream.min.js"></script>
</head>
<body>
<div id="control">
	<button data-act="file">Open file</button>
	<button data-act="youtube">Open YouTube</button>
	<button data-act="announce">Set announce</button>
</div>
<div id="playerbox">
	<video autoplay controls preload class="video-js" id="player">Connecting to message bus</video>
	<div id="nicochatbox">
		<div id="nicochat"></div>
	</div>
</div>
<div id="chatbox">
	<a class="user" target="_blank" href="https://www.facebook.com/app_scoped_user_id/<?=$user->id?>/">
		<img src="<?=$user->avatar?>"> <?=htmlspecialchars($user->name)?>:
	</a> <input type="text" name="chat" autofocus> 
	<button id="nicotoggle">Nicochat</button> <button id="oztoggle" style="color:red;">OZView</button>
	<span id="lagmeter">(<span id="lagdata">lag <span id="lag"></span>ms </span>ping <span id="lp"></span>ms)</span>
	<ul id="chatmsg">
		<li id="announce"></li>
	</ul>
	<ul id="onlineuser"></ul>
	<div style="clear: both;"></div>
</div>
<div id="oz"></div>
<script>window.HELP_IMPROVE_VIDEOJS = false;window.config = <?php echo json_encode($config); ?>;</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0-beta1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/video.js/5.7.1/video.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/videojs-youtube/2.0.8/Youtube.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>
