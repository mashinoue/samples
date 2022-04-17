<?php

if (isset($_GET['c'])) {
	$url = $_GET['c'];
	$index = 0;
	
	if (isset($_GET['i']))
        $index = $_GET['i'];
	
	get_video($url, $index);
	
} else {
	http_response_code(404);
    	die('Invalid Source');
}

function get_video($url, $index) {
	$url = drc($url);
	if (strpos($url, 'blogger.com')) {
		$json = blogger_fetch($url);
        	$url = $json['links'][$index]['play_url'];
		header('Location:'.$url);
	} else if (strpos($url, 'drive.google.com')) {
		gdrive_fetch($url);
	} else if (strpos($url, 'fembed.com')) {
		$json = fembed_fetch($url);
        	$url = $json['data'][$index]['file'];
		header('Location:'.$url);
	} else if (strpos($url, 'send.cm')) {
		$url = sendcm_fetch($url);
		header('Location:'.$url);
	} else if (strpos($url, 'ok.ru')) {
		$url = okru_fetch($url)[$index]['url'];
		header('Location:'.$url);
	}
}
function blogger_fetch($url) {
	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:69.0) Gecko/20100101 Firefox/69.0");
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    	$data = curl_exec($ch);
    	curl_close($ch);
	
    	$internalErrors = libxml_use_internal_errors(true);
    	$dom = new DOMDocument();
    	@$dom->loadHTML($data);
    	$xpath = new DOMXPath($dom);
    	$nlist = $xpath->query("//script");
    	$fileurl = $nlist[0]->nodeValue;
    	$diix = explode('var VIDEO_CONFIG = ', $fileurl);

    	$xix = [];
    	$ress = json_decode($diix[1], true);
    	$xix['links'] = $ress['streams'];
    	$xix['img'] = $ress['thumbnail'];
    	return $xix;
}
function gdrive_fetch($url) {
	$id = gdrive_getID($url);
	$ch = curl_init("https://drive.google.com/uc?id=$id&authuser=0&export=download");
    	curl_setopt_array($ch, array(
        	CURLOPT_CUSTOMREQUEST => 'POST',
        	CURLOPT_SSL_VERIFYPEER => false,
        	CURLOPT_POSTFIELDS => [],
        	CURLOPT_RETURNTRANSFER => true,
        	CURLOPT_ENCODING => 'gzip,deflate',
        	CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        	CURLOPT_HTTPHEADER => [
            		'accept-encoding: gzip, deflate, br',
            		'content-length: 0',
            		'content-type: application/x-www-form-urlencoded;charset=UTF-8',
            		'origin: https://drive.google.com',
            		'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36',
            		'x-client-data: CKG1yQEIkbbJAQiitskBCMS2yQEIqZ3KAQioo8oBGLeYygE=',
            		'x-drive-first-party: DriveWebUi',
            		'x-json-requested: true'
        	]
    	));
    	$response = curl_exec($ch);
    	$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	curl_close($ch);
    	if ($response_code == '200') {
        	$object = json_decode(str_replace(')]}\'', '', $response));
        	if (isset($object->downloadUrl)) {
            		header('Location:'.$object->downloadUrl);
            		//echo $object->downloadUrl;
        	} else {
            		http_response_code(404);
            		die('Not found');
        	}
    	} else {
        	http_response_code(403);
        	die('Forbidden');
    	}
}
function gdrive_getID($url) {
	$filter1 = preg_match('/drive\.google\.com\/open\?id\=(.*)/', $url, $id1);
	$filter2 = preg_match('/drive\.google\.com\/file\/d\/(.*?)\//', $url, $id2);
	$filter3 = preg_match('/drive\.google\.com\/uc\?id\=(.*?)\&/', $url, $id3);
	if($filter1){
		$id = $id1[1];
	} else if($filter2){
		$id = $id2[1];
	} else if($filter3){
		$id = $id3[1];
	} else {
		$id = null;
	}
	
	return($id);
}
function fembed_fetch($url) {
	$url = 'https://www.fembed.com/api/source/' .fembed_getID($url);
	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    	$data = curl_exec($ch);
    	curl_close($ch);
	
	$data = json_decode($data, true);
	
	return $data;
}
function fembed_getID($url) {
	if (strpos($url,'f/')) {
		$id = substr($url, strpos($url,'f/')+2);
	} else if (strpos($url,'v/')) {
		$id = substr($url, strpos($url,'v/')+2);
	}
	
	return $id;
}
function sendcm_fetch($url) {
	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:69.0) Gecko/20100101 Firefox/69.0");
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    	$data = curl_exec($ch);
    	curl_close($ch);
	
    	$internalErrors = libxml_use_internal_errors(true);
    	$dom = new DOMDocument();
    	@$dom->loadHTML($data);
    	$xpath = new DOMXPath($dom);
    	$nlist = $xpath->query("//source");
    	$fileurl = $nlist[0]->getAttribute("src");
    
    	return $fileurl;
}
function okru_fetch($url) {
	if (strpos($url,'/video/'))
		$url = str_replace('/video/','/videoembed/',$url);
	
	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; Android 4.1.1; Galaxy Nexus Build/JRO03C) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.166 Mobile Safari/535.19");
	curl_setopt($ch, CURLOPT_REFERER, 'https://ok.ru/');
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    	$data = curl_exec($ch);
    	curl_close($ch);
	
    	$internalErrors = libxml_use_internal_errors(true);
    	$dom = new DOMDocument();
    	@$dom->loadHTML($data);
    	$xpath = new DOMXPath($dom);
    	$div = $xpath->query('//div[@data-module="OKVideo"]');
	
	$json = json_decode($div[0]->getAttribute("data-options"), true);
	$videos = json_decode($json['flashvars']['metadata'], true);
	// 0 = 144p
	// 1 = 240p
	// 2 = 360p
	// 3 = 480p
	// 4 = 720p
	// 5 = 1080p
	
	return $videos['videos'];
}
function drc($code) {
    $start = "aHR0cA";
    $code = substr($code, 3, -3);
    $code = substr($code, 0, 3) . substr($code, 6);
    $code = base64_decode($start . "==") . base64_decode($code);
    return $code;
}
