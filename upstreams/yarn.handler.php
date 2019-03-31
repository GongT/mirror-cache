<?php

class yarn extends Upstream {
	public function shouldCache() {
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
}

