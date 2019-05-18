<?php
http_response_code(500);

require 'inc/lib.php';

$TYPE = get_get(ARG_NAME_TYPE);
$URL = get_get(ARG_NAME_URL);
$ARGS = get_get(ARG_NAME_QS, '');

$upstream = loadDomain($TYPE, $URL);

$curl = new CurlFetch($upstream->originalUrl($ARGS));

$curl->useCookie($upstream->type());
$curl->appendHeader($upstream->createHeaders());
$curl->proxy(PROXY);

$curl->passResponseHeaders();


if ($upstream->shouldForceCache()) {
	systemLogInfo("fetch.php: nginx will cache (forever): type=$TYPE, url=" . $curl->getUrl());
	header('X-GongT-Cache-Type: force-save');
	$curl->filterHttpCacheHeaders();
} else {
	systemLogInfo("fetch.php: nginx will cache (with timeout): type=$TYPE, url=" . $curl->getUrl());
	header('X-GongT-Cache-Type: parse-header');
}

if ($upstream->needTransformBody($curl)) {
	systemLogDebug("fetch.php: the response will buffered in memory and parse after finish");
	header('X-GongT-Cache-Transport: buffered');
	$curl->exec();
	echo $upstream->transformBody($curl);
} else {
	systemLogDebug("fetch.php: the response will pipe to browser directly");
	header('X-GongT-Cache-Transport: unbuffered');
	$curl->passResponseBody();
	$curl->exec();
}
