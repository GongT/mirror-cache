<?php


class fedora extends Upstream {
	public function shouldCache() {
		$uri = $this->uri();
		if (preg_match('#/repodata/repomd.xml$#', $uri)) {
			return false;
		}
		return true;
	}
	
	protected function getDomain() {
//		return apcu_entry('fedora-mirror-list', 'fetch_new_list', 3600);
		return [
			'http://mirror.0x.sg/fedora/linux/',
			'http://my.fedora.ipserverone.com/fedora/linux/',
			'https://mirror.hoster.kz/fedora/fedora/linux/',
			'http://mirror.dhakacom.com/fedora/linux/',
			'http://sg.fedora.ipserverone.com/fedora/linux/',
			'https://ftp.yzu.edu.tw/Linux/Fedora/linux/',
		];
	}
	
	public function type() {
		return 'fedora';
	}
}

/*
function fetch_new_list() {
	$ch = create_request('fedora', 'https://mirrors.fedoraproject.org/metalink?arch=x86_64&repo=rawhide');
	
	curl_setopt($ch, CURLOPT_ENCODING, "");
	curl_setopt($ch, CURLOPT_PROXY, PROXY);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$ret = exec_request($ch);
	
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
			}
		}
	}
	
	return $ret;
}

$blackList = [
	'mirror2.totbb.net'
];
function filter_blacklist($host) {
	global $blackList;
	return isset($blackList[$host]);
}
*/
