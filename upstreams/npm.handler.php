<?php

class npm extends Upstream {
	private function isGettingPackageJson() {
		return strpos(basename($this->uri()), '.') === false;
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
		return str_replace('https://registry.npmjs.org', 'https://mirror.service.gongt.me:59443/npm', $curl->getResponseBody());
	}
	
	public function needTransformBody(CurlFetch $curl) {
		return $this->isGettingPackageJson();
	}
}
