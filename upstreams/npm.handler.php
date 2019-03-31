<?php

class npm extends Upstream {
	public function shouldCache() {
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
}

