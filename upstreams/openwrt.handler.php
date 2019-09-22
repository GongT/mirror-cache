<?php

class openwrt extends Upstream {
	public function createHeaders() {
		return [
		];
	}
	
	function shouldNotCache() {
		$uri = $this->uri();
		if (preg_match('#snapshots\/#', $uri)) {
			return true;
		}
		return false;
	}

	public function shouldForceCache() {
		$uri = $this->uri();
		if (preg_match('#Packages\.(gz|sig)$#i', $uri)) {
			return false;
		}
		return true;
	}
	
	protected function getDomain() {
		return [
			'https://downloads.openwrt.org/'
		];
	}
	
	public function type() {
		return 'openwrt';
	}
	
	public function needTransformBody(CurlFetch $curl) {
		return false;
	}
}

