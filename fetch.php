<?php
http_response_code(500);

require 'inc/lib.php';

$TYPE = get_get(ARG_NAME_TYPE);
$URL = get_get(ARG_NAME_URL);
$ARGS = get_get(ARG_NAME_QS, '');

$upstream = loadDomain($TYPE, $URL);

function purgeInternal() {
	global $upstream, $ARGS;
	fastcgi_finish_request();
	systemLogError('Finished');
	sleep(1);
	$url = $upstream->toOutsideNginxPurgeUrl($ARGS);
	systemLogError('Internal sending purge request: ' . $url);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$ckfile = tempnam("/tmp", "purge_cache=yes");
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
	
	$data = curl_exec($ch);
	systemLogError('Internal purge response: ' . $data);
	curl_close($ch);
}

for ($retry = 0; $retry < 5; $retry++) {
	$originalUrl = $upstream->originalUrl($ARGS);
	$curl = new CurlFetch($originalUrl);
	
	$curl->useCookie($upstream->type());
	$curl->appendHeader($upstream->createHeaders());
	$curl->proxy(PROXY);
	
	$curl->passResponseHeaders();
	
	header('X-GongT-Cache-Transport: buffered');
	if ($upstream->shouldForceCache()) {
		systemLogInfo("fetch.php: nginx will cache (forever): type=$TYPE, url=" . $curl->getUrl());
		header('X-GongT-Cache-Type: force-save');
		$curl->filterHttpCacheHeaders();
	} else {
		systemLogInfo("fetch.php: nginx will cache (timeout from header): type=$TYPE, url=" . $curl->getUrl());
		header('X-GongT-Cache-Type: parse-header');
	}
	header('X-GongT-Cache-Source: ' . $originalUrl);
	
	$ok = $curl->exec();
	if ($ok) {
		
		if ($upstream->needTransformBody($curl)) {
			$ret = $upstream->transformBody($curl);
			
			if (empty($ret)) {
				http_response_code(500);
				systemLogInfo('fetch.php: request complete, but got empty response. ' . $curl->getUrl());
			} else {
				header('Content-Length: ' . strlen($ret)); // must overwride old content-length, content is changed by us
				echo $ret;
				systemLogInfo('fetch.php: request complete, data flushed (' . strlen($ret) . '). ' . basename($curl->getUrl()));
				exit(0);
			}
			
		} else {
			// still need a way to cache download, otherwise nginx will cache interrupted response
			$file = $curl->getResponseBodyFile();
			if (!file_exists($file)) {
				http_response_code(500);
				systemLogError('fetch.php: request complete, but temp file does not exists. ' . $file);
				exit(0);
			}
			$fsize = filesize($file);
			if ($fsize === 0) {
				http_response_code(500);
				systemLogInfo('fetch.php: request complete, but got empty response file. ' . $file);
			} else {
				$fp = fopen($file, 'r');
				fpassthru($fp);
				fclose($fp);
				systemLogInfo('fetch.php: request complete, file flushed (' . $fsize . '). ' . basename($curl->getUrl()));
				exit(0);
			}
			
		}
	}
	
	systemLogInfo('fetch.php: ' . ($retry + 1) . ' tries | curl: ' . $curl->getUrl());
	
	unset($curl);
}

header('X-GongT-Cache-Type: error');
systemLogError('fetch.php: failed after ' . ($retry + 1) . ' retry!');

http_response_code(500);
echo('<h1>Error while run curl:' . curl_error($this->ch) . '</h1>');
echo('<pre>URL = ' . $curl->getUrl() . '</pre>');
xdebug_var_dump(curl_getinfo($this->ch));
echo('Body:');
echo(htmlentities($this->responseBody));
