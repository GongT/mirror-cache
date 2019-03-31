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
	
	public function isSpecial() {
		$uri = $this->uri();
		if (strpos(basename($uri), '.') === false) {
			return true;
		}
		return false;
	}
	
	public function handleSpecial() {
		$ch = create_direct_connect($this, $_SERVER['QUERY_STRING']);
		$ret = exec_request($ch, true);
		echo str_replace('https://registry.npmjs.org', 'https://mirror.service.gongt.me:59443/npm', $ret);
	}
}
