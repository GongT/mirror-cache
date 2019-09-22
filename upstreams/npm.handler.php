<?php

class npm extends Upstream {
	private function isGettingPackageJson() {
		return strpos(basename($this->uri()), '.') === false;
	}
	
	public function shouldNotCache() {
		systemLogInfo("shouldNotCache. ${_SERVER['REQUEST_METHOD']}");
		return $_SERVER['REQUEST_METHOD'] === 'POST';
	}
	public function shouldForceCache() {
		if ($this->isGettingPackageJson()) {
			return false;
		}
		return true;
	}
	
	protected function getDomain() {
		return [
			'https://registry.npmjs.org/'
		];
	}
	
	public function type() {
		return 'npm';
	}
	
	
	public function transformBody(CurlFetch $curl) {
		$data = str_replace('https://registry.npmjs.org', 'https://mirror.service.gongt.me:59443/npm', $curl->getResponseBody());
		$test = json_decode($data);
		if($test === NULL) {
			$f = tempnam(sys_get_temp_dir(), "failed-download-json-");
			file_put_contents($f, $data);
			fatalError("JSON not valid. saved to temp file: $f");
		}
		systemLogInfo("json is ok: ". $this->uri());
		return $data;
	}
	
	public function needTransformBody(CurlFetch $curl) {
		return $this->isGettingPackageJson();
	}
}
