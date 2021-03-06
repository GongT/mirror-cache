<?php


class fedora extends Upstream {
	public function shouldForceCache() {
		$uri = $this->uri();
		if (preg_match('#/repodata/#', $uri)) {
			return false;
		}
		return true;
	}
	
	protected function getDomain() {
		return apcu_entry('fedora-mirror-list', 'fetch_new_list', 3600);
		/*
		$uri = $this->uri();
		if (preg_match('#/repodata/#', $uri)) {
			return ['https://download.nus.edu.sg/mirror/fedora/linux/'];
		}
		return [
			'https://download.nus.edu.sg/mirror/fedora/linux/',
			'http://my.fedora.ipserverone.com/fedora/linux/',
			'https://mirror.hoster.kz/fedora/fedora/linux/',
			'http://mirror.dhakacom.com/fedora/linux/',
			'http://sg.fedora.ipserverone.com/fedora/linux/',
			 'https://ftp.yzu.edu.tw/Linux/Fedora/linux/',
		];
		*/
	}
	
	public function type() {
		return 'fedora';
	}
	
	public function needTransformBody(CurlFetch $curl) {
		return false;
	}
}

function fetch_new_list() {
	systemLogInfo("fedora: fetch new mirror list.");
	$curl = new CurlFetch('https://mirrors.fedoraproject.org/metalink?arch=x86_64&repo=rawhide');
	
	$curl->proxy(PROXY);
	$ok = $curl->exec();
	if ($ok) {
		$ret = $curl->getResponseBody();
	} else {
		http_response_code(500);
		systemLogError('fedora: cannot get mirror list file from ' . $curl->getUrl());
		exit(0);
	}
	
	$found = preg_match_all('#https?://.+/repomd\.xml#', $ret, $matches);
	if (!$found) {
		fatalError("Cannot find right repo url");
	}
	
	$ret = [];
	foreach ($matches[0] as $url) {
		$host = explode('development/rawhide/Everything/x86_64/os/repodata/repomd.xml', $url);
		if (count($host) === 2 && strlen($host[1]) === 0) {
			$host = $host[0];
			
			if (filter_blacklist(parse_url($host, PHP_URL_HOST))) {
				$ret[] = $host;
			}else{
				systemLogInfo("Blacklist url: $url");
			}
		} else {
			systemLogInfo("Ignore url: $url");
		}
	}
	
	systemLogInfo('fedora: mirrors to try: ' . implode('', array_map(function ($n) {
			return "\n    * $n";
		}, $ret)));
	
	return $ret;
}

function filter_blacklist($host) {
	$blackList = [
		'mirror2.totbb.net'
	];
	if (in_array($host, $blackList)) {
		return false;
	}
	
	if (strtolower(substr($host, -3)) === '.cn') {
		return false;
	}
	return true;
}

