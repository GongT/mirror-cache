<?php

class yarn extends Upstream {
	public function shouldForceCache() {
		return true;
	}
	
	protected function getDomain() {
		return [
			'https://registry.yarnpkg.com/'
		];
	}
	
	public function type() {
		return 'yarn';
	}
	
	public function needTransformBody(CurlFetch $curl) {
		return false;
	}
}

