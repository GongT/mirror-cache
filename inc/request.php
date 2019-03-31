<?php

function create_request($type, $url) {
	$ch = curl_init();
	
	error_log('CURL Request: ' . $url);
	curl_setopt($ch, CURLOPT_URL, $url);
	header('X-Upstream: ' . $url);
	
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
	curl_setopt($ch, CURLOPT_TCP_FASTOPEN, true);
	curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_ALL);
	
	if ($type) {
		$cookie = '/data/contents/mirror-cache/cookie.' . $type . '.txt';
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	}
	
	//	curl_setopt($ch, CURLOPT_VERBOSE, true);
	//	$out = fopen('/dev/shm/test', 'w');
	//	curl_setopt($ch, CURLOPT_STDERR, $out);
	
	curl_setopt($ch, CURLOPT_HEADER, false);
	
	curl_setopt($ch, CURLOPT_REFERER, get_server('HTTP_REFERER'));
	
	curl_setopt($ch, CURLOPT_HEADERFUNCTION, "fn_CURLOPT_HEADERFUNCTION");
	//	curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'fn_CURLOPT_WRITEFUNCTION'); // callad every CURLOPT_BUFFERSIZE
	
	header_register_callback(function () {
		global $headers;
		if (empty($headers)) {
			return;
		}
		
		$statusLine = array_shift($headers);
		$matches = [];
		preg_match('# \d+ #', $statusLine, $matches);
		$code = isset($matches[0]) ? intval(trim($matches[0])) : 200;
		http_response_code($code);
		
		foreach ($headers as $str) {
			header(trim($str), true);
		}
	});
	
	return $ch;
}

function fn_CURLOPT_HEADERFUNCTION($ch, $str) {
	global $headers;
	static $cache;
	
	$len = strlen(trim($str));
	
	if (preg_match('#^(set-cookie|location|content-encoding):#i', $str)) {
		return strlen($str);
	}
	
	if ($len === 0) {
		$headers = $cache;
		$cache = [];
	} else {
		if ($cache && count($cache) === 0) {
			$cache = [];
			$headers = [];
		}
		$cache[] = $str;
	}
	
	return strlen($str);
}

/**
 * @param Upstream $upstream
 * @param string $type
 * @param string $ARGS
 * @return void
 */
function makeRequest(Upstream $upstream, $ARGS) {
	$ch = create_request($upstream->type(), appendArgs($upstream->requestUri(), $ARGS));
	
	curl_setopt($ch, CURLOPT_ENCODING, "");
	curl_setopt($ch, CURLOPT_PROXY, PROXY);
	
	$headers = $upstream->createHeaders();
	$headers[] = "accpet: " . get_server('HTTP_ACCEPT');
	$headers[] = "cache-control: " . get_server('HTTP_CACHE_CONTROL');
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	exec_request($ch);
}

function exec_request($ch) {
	http_response_code(200);
	
	$ret = curl_exec($ch);
	
	if (curl_errno($ch)) {
		error_log('CURL Fail: ' . curl_error($ch));
		http_response_code(500);
		xdebug_var_dump(curl_getinfo($ch));
		die('Curl error: ' . curl_error($ch));
	}
	
	curl_close($ch);
	
	return $ret;
}
