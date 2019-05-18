<?php

require 'inc/lib.php';

systemLogDebug('hello world');
systemLogInfo(json_encode($_SERVER, JSON_PRETTY_PRINT));

$cacheKey = isset($_SERVER['CACHE_KEY']) ? trim($_SERVER['CACHE_KEY'], ':') : '';

$originalUri = isset($_SERVER['ORIGINAL_URI']) ? $_SERVER['ORIGINAL_URI'] : '';
if (!$originalUri) {
	exit;
}

@list($type, $url) = explode('/', ltrim($originalUri, '/'), 2);
if (empty($type) || empty($url)) {
	exit;
}

$upstream = loadDomain($type, '/' . $url);

if ($cacheKey) { // fetch.php
	
} else { // index.php
	
}