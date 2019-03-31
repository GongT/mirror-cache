<?php

require 'const.php';
require 'upstream.php';
require 'request.php';

function &selectDomain(array $domainArray) {
	$cnt = count($domainArray);
	if ($cnt === 1) return $domainArray[0];
	
	$id = rand(0, $cnt - 1);
	return $domainArray[$id];
}

function normalizePath($p) {
	return implode('/', array_filter(explode('/', str_replace('\\', '/', $p))));
}

function makeCachePath($type, $url) {
	return normalizePath(implode('/', [CACHE_PATH, $type, $url]));
}

/**
 * @param $why string
 */
function fatalError($why) {
	http_response_code(500);
	echo "<h1>$why</h1>";
	die;
}

function get_server($key) {
	if (isset($_SERVER[$key])) {
		return $_SERVER[$key];
	} else {
		return '';
	}
}

function get_get($key, $def = null) {
	if (empty($_GET[$key])) {
		if (is_null($def)) {
			fatalError("Require argument: $key");
		} else {
			return $def;
		}
	}
	return $_GET[$key];
}

/**
 * @param $type string
 * @param $url string
 * @return Upstream
 */
function loadDomain($type, $url) {
	$handlerFile = __DIR__ . '/../upstreams/' . $type . '.handler.php';
	if (file_exists($handlerFile)) {
		require_once($handlerFile);
		return new $type($url);
	} else {
		fatalError("Cannot find upstream: $type");
		return null;
	}
}

function appendArgs($url, $args) {
	if ($args) {
		return $url . '?' . $args;
	} else {
		return $url;
	}
}
