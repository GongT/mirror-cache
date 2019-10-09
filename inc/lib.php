<?php

require 'const.php';
require 'upstream.php';
require 'curlFetch.php';
require 'cacheInformationLog.php';

set_error_handler(function () {
	
	systemLogError('Error/Notice in request: ' . (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : $_SERVER['QUERY_STRING']));
	return false;
}, E_ALL & ~E_NOTICE & ~E_USER_NOTICE);

function &selectDomain(array $domainArray) {
	$cnt = count($domainArray);
	if ($cnt === 1) return $domainArray[0];
	
	$id = rand(0, $cnt - 1);
	return $domainArray[$id];
}

function normalizePath($p) {
	$prefix = $p[0];
	$po = implode('/', array_filter(explode('/', str_replace('\\', '/', $p))));
	if ($prefix === '/' || $prefix === '\\') {
		return '/' . $po;
	} else {
		return $po;
	}
}

function makeCachePath($type, $url) {
	return normalizePath(implode('/', [CACHE_PATH, $type, $url]));
}

function addNeverCacheHeader() {
	header('Cache-Control: no-cache, no-store, must-revalidate');
	header('Pragma: no-cache');
	header('Expires: 0');
}

/**
 * @param $why string
 */
function fatalError($why) {
	if (http_response_code()<400) {
		http_response_code(500);
	}
	addNeverCacheHeader();
	$req = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : $_SERVER['QUERY_STRING'];
	echo "<h1>$why</h1>$req";
	systemLogError('Error/Notice in request: ' . $req . '. reason: ' . $why . '.');
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

function systemLogDebug(string $message) {
	sd_journal_send('MESSAGE=' . $message, 'SYSLOG_IDENTIFIER=mirror', 'PRIORITY=6');
}

function systemLogError(string $message) {
	sd_journal_send('MESSAGE=' . $message, 'SYSLOG_IDENTIFIER=mirror', 'PRIORITY=2');
}

function systemLogInfo(string $message) {
	sd_journal_send('MESSAGE=' . $message, 'SYSLOG_IDENTIFIER=mirror', 'PRIORITY=4');
}
