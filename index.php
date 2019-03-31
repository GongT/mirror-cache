<?php
http_response_code(500);

require 'inc/lib.php';

list($type, $url) = explode('/', ltrim($_SERVER['PATH_INFO'], '/'), 2);

$upstream = loadDomain($type, '/' . $url);

if ($upstream->isSpecial()) {
	header('X-GongT-Cache: special');
	
	$upstream->handleSpecial();
} elseif ($upstream->shouldCache()) {
	$target = $upstream->build_proxy_url();
	
	header('X-GongT-Cache: yes');
	
	$ch = create_request(false, $target);
	exec_request($ch);
} else {
	header('X-GongT-Cache: direct');
	// todo: etag & last-modify
	$ch = create_direct_connect($upstream, $ARGS);
	exec_request($ch);
}
