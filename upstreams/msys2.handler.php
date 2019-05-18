<?php

class msys2 extends Upstream {
	public function shouldForceCache() {
		$uri = $this->uri();
		if (preg_match('#/(mingw32|mingw64|msys)\.(db|files)#i', $uri)) {
			return false;
		}
		return true;
	}
	
	public function needTransformBody(CurlFetch $curl) {
		return false;
	}
	
	protected function getDomain() {
		return [
			'http://repo.msys2.org'
		];
	}
	
	public function type() {
		return 'msys2';
	}
}

