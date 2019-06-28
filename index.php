<?php
http_response_code(500);

require 'inc/lib.php';

@list($type, $url) = explode('/', ltrim($_SERVER['PATH_INFO'], '/'), 2);

if (empty($type) || empty($url)) {
	throw new Error('Invalid request: ' . ltrim($_SERVER['PATH_INFO'], '/'));
}

$purge = empty($_COOKIE['purge_cache']) ? 'no' : 'yes';

$upstream = loadDomain($type, '/' . $url);
systemLogDebug("index.php: got request: purge=$purge, type=$type, url=$url");

if ($purge === 'yes') {
	$url = $upstream->toNginxPurgeUrl($_SERVER['QUERY_STRING']);
} else {
	$url = $upstream->toNginxProxiedUrl($_SERVER['QUERY_STRING']);
}

systemLogDebug("index.php: internal redirect to: " . $url);
header('X-Accel-Redirect: ' . $url);
