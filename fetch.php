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
	$ckfile = tempnam(sys_get_temp_dir(), "purge");
	file_put_contents($ckfile, "purge_cache=yes");
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);

	$data = curl_exec($ch);
	systemLogError('Internal purge response: ' . $data);
	curl_close($ch);
}

$lastUrl = '';
$lastCode = 500;
const MAX_TRY = 5;
for ($retry = 0; $retry < MAX_TRY; $retry++) {
	$originalUrl = $upstream->originalUrl($ARGS);
	if ($lastUrl === $originalUrl && $retry > 0 && $lastCode !== 0) {
		systemLogError("No more mirror to try...");
		break;
	}
	$lastUrl = $originalUrl;

	$curl = new CurlFetch($originalUrl);

	$curl->useCookie($upstream->type());
	$curl->appendHeader($upstream->createHeaders());
	$curl->proxy(PROXY);

	$curl->passResponseHeaders();

	if ($upstream->shouldNotCache()) {
		systemLogInfo("fetch.php: nginx will NOT cache: type=$TYPE, url=" . $curl->getUrl());
		header('X-GongT-Cache-Type: never');
		$curl->filterHttpCacheHeaders();
		addNeverCacheHeader();
	} elseif ($upstream->shouldForceCache()) {
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
			systemLogDebug("need body transform: " . $curl->getUrl());
			$ret = $upstream->transformBody($curl);

			if (empty($ret)) {
				http_response_code(500);
				systemLogError('fetch.php: transform body return empty. ' . $curl->getUrl());
			} else {
				header('Content-Length: ' . strlen($ret)); // must overwride old content-length, content is changed by us
				http_response_code(200);
				echo $ret;
				systemLogInfo('fetch.php: request complete, data flushed (' . strlen($ret) . '). ' . basename($curl->getUrl()));
				exit(0);
			}
		} else {
			systemLogDebug("do not need body transform:" . $curl->getUrl());

			// still need a way to cache download, otherwise nginx will cache interrupted response
			$file = $curl->getResponseBodyFile();
			$fsize = filesize($file);
			header('Content-Length: ' . $fsize);
			http_response_code(200);
			$fp = fopen($file, 'r');
			fpassthru($fp);
			fclose($fp);
			systemLogInfo('fetch.php: request complete, file flushed [' . $fsize . '] - ' . basename($curl->getUrl()));
			exit(0);
		}
	} else {
		$lastCode = $curl->lastStatus;
	}

	systemLogInfo('fetch.php: ' . ($retry + 1) . ' tries | curl: ' . $curl->getUrl());

	unset($curl);
}

http_response_code($lastCode);
header('X-GongT-Cache-Type: error');
fatalError('fetch.php: failed after ' . ($retry + 1) . ' retry! return code ' . $lastCode);

