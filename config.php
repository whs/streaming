<?php
session_start();
define("FB_ID", "");
define("FB_SECRET", "");
define("FB_REQUIRE_EVENT", "");

$chatserver = $_SERVER['SERVER_NAME'];
define("CHAT_MASTER", "http://".$chatserver."/privpub/master");
define("CHAT_CHAT", "http://".$chatserver."/privpub/chat");
define("CHAT_ONLINE", "http://".$chatserver."/pub/online");


function ws_push($room, $data=array()){
	return http($room, "POST", json_encode($data));
}

/**
 * Make an HTTP request
 * Copied from twitteroauth.php
 *
 * @return API results
 */
function http($url, $method="GET", $postfields = NULL, $agent="animestream/1.1") {
	$ci = curl_init();
	/* Curl settings */
	curl_setopt($ci, CURLOPT_USERAGENT, $agent);
	curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ci, CURLOPT_TIMEOUT, 30);
	curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
	curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, true);

	switch ($method) {
	  case 'POST':
		curl_setopt($ci, CURLOPT_POST, TRUE);
		if (!empty($postfields)) {
		  curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
		}
		break;
	  case 'DELETE':
		curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
		if (!empty($postfields)) {
		  $url = "{$url}?{$postfields}";
		}
	}

	curl_setopt($ci, CURLOPT_URL, $url);
	$response = curl_exec($ci);
	curl_close ($ci);
	return $response;
}
