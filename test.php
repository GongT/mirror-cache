<?php
http_response_code(500);

require 'inc/lib.php';

list($type, $url) = explode('/', ltrim($_SERVER['PATH_INFO'], '/'), 2);

$upstream = loadDomain($type, '/' . $url);

header('Content-Type: text/html; charset=utf-8');

if ($upstream->shouldCache()) {
	echo "<h1>This request will be cached</h1>";
	$target = $upstream->build_proxy_url();
	echo "<h1>Request will send to: $target</h1>";
	$finally = $upstream->requestUri();
	echo "<h1>And finally request to: $finally</h1>";
} else {
	echo "<h1>This request will <span style='color:red'>NOT</span> be cached</h1>";
	$finally = $upstream->requestUri();
	echo "<h1>Request will directly send to: $finally</h1>";
}
