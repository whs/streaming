<?php
session_start();
define("API_HOST", "http://api.menome.in.th/");
define("API_KEY", "");
define("API_SECRET", "");

define("CHAT_SERVER", "http://localhost:8036/");
define("CHAT_SECRET", "soraserver");


function ws_push($room, $type, $data=array()){
	$url = CHAT_SERVER.$room;
	$reqData = array(
		"key" => CHAT_SECRET,
		"data" => json_encode($data),
		"type" => $type
	);
	$url .= "?" . http_build_query($reqData);
	return file_get_contents($url);
}

/**
 * Make an HTTP request
 * Copied from twitteroauth.php
 *
 * @return API results
 */
function http($url, $method="GET", $postfields = NULL, $agent="whsStreaming/1.0") {
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