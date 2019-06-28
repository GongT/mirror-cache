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
	systemLogInfo("fetch.php: nginx will cache (timeout from header): type=$TYPE, url=" . $curl->getUrl());
	header('X-GongT-Cache-Type: parse-header');
}

if ($upstream->needTransformBody($curl)) {
	systemLogDebug("fetch.php: the response will buffered in memory and parse after finish");
	header('X-GongT-Cache-Transport: buffered');
	$ok = $curl->exec();
	if ($ok) {
		echo $upstream->transformBody($curl);
	} else {
		http_response_code(500);
		echo('<h1>Error while run curl:' . curl_error($this->ch) . '</h1>');
		echo('<pre>URL = ' . $this->url . '</pre>');
		xdebug_var_dump(curl_getinfo($this->ch));
		echo('Body:');
		echo(htmlentities($this->responseBody));
	}
} else {
	systemLogDebug("fetch.php: the response will pipe to browser directly");
	header('X-GongT-Cache-Transport: unbuffered');
	$curl->passResponseBody();
	$ok = $curl->exec();
	if (!$ok) {
		systemLogError('Finishing request');
		fastcgi_finish_request();
		systemLogError('Finished');
		sleep(1);
		$url = $upstream->toOutsideNginxPurgeUrl($ARGS);
		systemLogError('Internal sending purge request: ' . $url);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$ckfile = tempnam ("/tmp", "purge_cache=yes");
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);

		$data = curl_exec($ch);
		systemLogError('Internal purge response: ' . $data);
		curl_close($ch);
	}
}
