<?php
require "config.php";
if(!$_SESSION['user']){
	header("Location: auth.php");
	die();
}
$master = false;
if(isset($_GET['master'])){
	$master = true;
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Streaming</title>
	<link rel="stylesheet" href="normalize.css">
	<script src="<?=CHAT_SERVER?>socket.io/socket.io.js"></script>
	<style>
body{
	background: black;
	color: white;
}
#playerbox{
	background: #111;
	width: 852px;
	height: 480px;
	margin: auto;
	text-align: center;
	line-height: 426px;
	font-family: monospace;
	font-size: 12pt;
	position: relative;
	overflow: hidden;
}
#nicochatbox{
	position: absolute;
	top: 0.5em; left: 0;
	line-height: 1em;
	font-family: sans-serif;
	width: 100%;
	height: 100%;
	pointer-events: none;
}
#nicochat{
	width: 100%;
	height: 100%;
	position: relative;
	text-shadow: black 0px 0px 5px;
	font-size: 14pt;
}
.nico{
	position: absolute;
	left: 852px;
	display: inline-block;
	word-break: normal;
	white-space: nowrap;
	height: 1em;
}
#chatbox{
	width: 1000px; margin: auto;
	font-size: 10pt;
	margin-top: 10px;
}
#chatbox ul{
	height: 200px;
	overflow-x: hidden;
	overflow-y: scroll;
	word-break: break-word;
	padding: 0;
	margin:0;
	margin-bottom: 10px;
	float: left;
}
#onlineuser{
	width: 250px;
}
#chatmsg{
	width: 720px;
	margin-right: 30px !important;
}
#chatbox li{
	list-style: none;
	margin-bottom: 10px;
}
#chatbox img{
	max-width: 32px;
	max-height: 32px;
	vertical-align: middle;
}
.user{
	display: block;
	width: 140px;
	text-overflow:ellipsis;
	overflow: hidden;
	color: #eeee22;
	text-decoration: none;
	font-weight: bold;
	white-space: nowrap;
	float: left;
}
.meta{
	color: #aaa;
	font-size: 8pt;
	margin-left: 10px;
}
button{
	background: #222;
	margin-top: 2px;
	font-family: verdana;
	border: none;
	color: #00ccff;
}
#chatbox input{
	width: 650px;
	border: none;
	background: transparent;
	border-bottom: #00ccff solid 2px;
	color: white;
}
#chatbox input:focus{
	outline: none;
}
.body{
	padding-left: 140px;
}
.body:after{
	display: block;
	content: "";
	clear: both;
}
video{
	width: 100%;
	height: 100%;
	display: block;
}
#lagmeter{
	display: block;
	color: #222;
	margin-left: 10px;
	cursor: pointer;
	font-size: 8pt;
}
#announce{
	background: #222;
	padding: 8px;
	display: none;
}
#onlineuser li{
	margin-bottom: 10px;
}
#oz{
	position: absolute;
	top: 0; left: 0;
	pointer-events: none;
}
.ozbox{
	position: absolute;
	padding: 30px 20px 30px 20px;
	border-radius: 999px;
	background: rgba(255,255,255,0.8);
	border: rgba(255,255,255,0.95) solid 2px;
	display: inline-block;
	font-family: verdana, sans-serif;
	-webkit-transform: rotateY(90deg);
	-webkit-transition: -webkit-transform ease-in 500ms;
	text-shadow: white 0px 0px 10px;
	word-break: normal;
	white-space: nowrap;
	min-width: 60px;
	text-align: center;
	color: black;
}
<?php if($master): ?>
#control{
	position: absolute;
	top: 0px;
	right: 0px;
	width: 150px;
	text-align: right;
}
<?php endif; ?>
	</style>
</head>
<body>
<?php if($master): ?>
<div id="control">
	<button data-act="file">Open file</button>
	<button data-act="stream">Open stream</button>
	<button data-act="announce">Set announce</button>
</div>
<?php endif; ?>
<div id="playerbox">
<div id="player">
	Connecting to message bus
</div>
<div id="nicochatbox">
	<div id="nicochat"></div>
</div>
</div>
<div id="chatbox">
<a class="user" target="_blank" href="http://menome.in.th/<?=$user->id?>"><img src="<?=$user->avatar?>"> <?=htmlspecialchars($user->name)?>:</a> <input type="text" name="chat" autofocus> 
	<button id="nicotoggle">Nicochat</button> <button id="oztoggle" style="color:red;">OZView</button>
	<span id="lagmeter">(<span id="lagdata">lag <span id="lag"></span>ms </span>ping <span id="lp"></span>ms)</span>
<ul id="chatmsg">
	<li id="announce">ฟหกดเสวง</li>
</ul>
<ul id="onlineuser"></ul>
<div style="clear: both;"></div>
</div>
<div id="oz">
</div>
<script id="streamplayer" type="text/html">
<object width="852" height="480"> <param name="movie" value="http://madoka.whs.in.th/FlashMediaPlayback_101.swf"></param><param name="flashvars" value="src=http://stream.whs.in.th/live/anime/manifest.f4m?session=<?=time()?>&amp;controlBarMode=floating&amp;playButtonOverlay=false&amp;autoPlay=true"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://fpdownload.adobe.com/strobe/FlashMediaPlayback_101.swf" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="853" height="480" flashvars="src=http%3A%2F%2Fstream.whs.in.th%2F&amp;controlBarMode=floating&amp;playButtonOverlay=false&amp;autoPlay=true"></embed></object>
</script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
<script>
var socket = io.connect('<?=CHAT_SERVER?>streaming/chat');
var loadTimeout, reload=false, lp = new Date().getTime();
var source = {type: null};
var nicoSlot = {0:0, 1:0, 2:0, 3:0, 4:0, 5:0, 6:0, 7:0, 8:0, 9:0};
var nicoOn = true, ozOn = false;
socket.on('chat', function(data){
	var ele = $("<li><a class='user' target='_blank'></a><div class='body'></div></li>");
	ele.find(".user").text(" "+data.user.name+":");
	$("<img>").attr("src", data.user.avatar).prependTo(ele.find(".user"));
	ele.find(".body").text(data.message+" ");
	$("<span class='meta'></span>").text(new Date(data.time * 1000).toLocaleTimeString()).appendTo(ele.find(".body"));
	ele.insertAfter("#announce");

	// nicochat
	if(nicoOn){
		var nc = $("<span class='nico'>").text(data.message);
		var nSlot = 0, nCount = 99999999;
		$.each(nicoSlot, function(k,v){
			v = v - new Date().getTime();
			if(v<nCount){
				nSlot = k;
				nCount = v;
			}
		});
		nc.css("top", nSlot * 30).css("font-size", Math.floor(Math.random() * 6) + 20);
		nc.appendTo("#nicochat");
		var width = nc.width() + 10;
		nicoSlot[nSlot] = new Date().getTime() + 4000 + (width * 2);
		nc.animate({left: width*-1}, 4000 + (width * 2), function(){
			nicoSlot[nSlot] = 0;
		});
	}

	// ozview
	if(ozOn){
		var oz = $("<div class='ozbox'></div>").text(data.message).appendTo("#oz");
		oz.css("top", Math.floor(Math.random() * ($(window).height() - oz.outerHeight()))).css("left", Math.floor(Math.random() * ($(window).width() - oz.outerWidth())));
		oz.css("-webkit-transform", "rotateY(0deg)");
		setTimeout(function(){
			oz.css("-webkit-transform", "rotateY(90deg)");
		}, 2000 + (oz.width() * 2));
	}
});
socket.on("online", function(data){
	var ele = $("#onlineuser .user_"+data.user.id);
	if(ele.length == 1){
		ele.data("update", new Date().getTime());
	}else{
		ele = $("<li><a class='user' target='_blank'></a></li>");
		ele.find(".user").text(" "+data.user.name);
		$("<img>").attr("src", data.user.avatar).prependTo(ele.find(".user"));
		ele.addClass("user_" + data.user.id).data("update", new Date().getTime());
		ele.appendTo("#onlineuser");
	}
});
socket.on('connect', function (){
	$("#player").text("Waiting for initial packet");
	$.get("chat.php?act=online");
	loadTimeout = setTimeout(function(){
		$("#player").text("Initial packet is missing. Master is missing?");
	}, 5000);
});
socket.on("sync", function(d){
	lp = new Date().getTime()
	clearTimeout(loadTimeout);
	//d.source = {"type": "file", "file": "clannad.mp4"};
	if(d.force == "true" || source.type != d.source.type || source.file != d.source.file){
		// switch
		if(d.source.type == "file"){
			$("#lagdata").show();
			if($("#player video").attr("src") != d.source.file){
				$("#player").html("<video autoplay<?php if($master): ?> controls<?php endif; ?>></video>");
				$("#player video").attr("src", d.source.file).get(0).pause()
				$("#player video").get(0).play();
			}
			try{
				$("#player video").get(0).currentTime = parseFloat(d.time);
			}catch(e){}
		}else if(d.source.type == "stream"){
			$("#lagdata").hide();
			$("#player").html($("#streamplayer").html());
		}
		source = d.source;
	}
	if(d.announce.length > 0){
		$("#announce").text(d.announce).show();
	}else{
		$("#announce").text("").hide();
	}
	<?php if(!$master): ?>
	if(source.type == "file"){
		var lag = Math.floor(parseFloat(d.time) * 1000 - $("#player video").get(0).currentTime * 1000);
		$("#lag").text(lag);
		if(reload || Math.abs(lag) > 3000){
			try{
				$("#player video").get(0).currentTime = parseFloat(d.time);
			}catch(e){}
			reload = false;
		}
	}
	<?php endif; ?>
});
setInterval(function(){
	$("#lp").text(Math.max(0, (new Date().getTime() - lp) - 3000));
}, 500);
setInterval(function(){
	$.get("chat.php?act=online&rnd="+(new Date().getTime()).toString());
	$("#onlineuser li").each(function(){
		if($(this).data("update") < new Date().getTime() - 6000){
			$(this).remove();
		}
	});
}, 3000);
<?php if($master): ?>
/* master */
var announce="", syncTimer;
function sync(change){
	$.post("chat.php", {
		act: "sync",
		data: {
			source: source,
			time: $("#player video").length == 1 ? $("#player video").get(0).currentTime + 0.5 : null,
			force: !!change,
			announce: announce
		}
	});
}
$("button[data-act=file]").click(function(){
	var file = prompt("File URL?", "data/");
	if(!file) return false;
	source = {
		type: "file",
		file: file
	};
	sync(true);
	clearInterval(syncTimer);
	syncTimer = setInterval(sync, 3000);
	return false;
});
$("button[data-act=stream]").click(function(){
	source = {
		type: "stream",
		file: null
	};
	sync(true);
	clearInterval(syncTimer);
	syncTimer = setInterval(sync, 3000);
	return false;
});
$("button[data-act=announce]").click(function(){
	var a = prompt("Announce?", announce);
	if(a !== null){
		announce = a;
	}
	sync();
	return false;
});
/* endmaster */
<?php endif; ?>
$("#lagmeter").click(function(){
	reload = true;
});
$("#nicotoggle").click(function(){
	nicoOn = !nicoOn;
	if(!nicoOn){
		$(".nico").remove();
		$("#nicotoggle").css("color", "red");
	}else{
		$("#nicotoggle").css("color", "#00ccff");
	}
});
$("#oztoggle").click(function(){
	ozOn = !ozOn;
	if(!ozOn){
		$(".ozbox").remove();
		$("#oztoggle").css("color", "red");
	}else{
		$("#oztoggle").css("color", "#00ccff");
	}
});
$("input[name=chat]").keyup(function(e){
	if(e.which == 13){
		var self = this;
		this.disabled = true;
		//this.value = "FFFUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUU".substr(0, Math.floor(Math.random()*40) + 10);
		$.post("chat.php", {"text": this.value}, function(){
			self.disabled = false;
			self.value = "";
		});
		return false;
	}
});
</script>
</body>
</html>