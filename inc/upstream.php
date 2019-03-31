<?php


abstract class Upstream {
	/** @var  string */
	protected $m_uri;
	/** @var  string */
	protected $m_domain;
	
	function __construct($path) {
		$this->m_uri = $path;
		$this->m_domain = selectDomain($this->getDomain());
	}
	
	/**
	 * @return string[]
	 */
	public function createHeaders() {
		return [];
	}
	
	/**
	 * @return bool
	 */
	public abstract function shouldCache();
	
	/**
	 * @return string[]
	 */
	protected abstract function getDomain();
	
	/** @return string */
	public function requestUri() {
		return rtrim($this->m_domain, '/') . '/' . ltrim($this->uri(), '/');
	}
	
	/** @return  string */
	public function uri() { return $this->m_uri; }
	
	/** @return  string */
	public function domain() { return $this->m_domain; }
	
	abstract public function type();
	
	public function build_proxy_url() {
		$opt = [
			'type' => $this->type(),
			'url' => $this->uri(),
			'qs' => $_SERVER['QUERY_STRING'],
		];
		
		return 'http://127.0.0.1:22222/fetch-upstream?' .
			str_replace('%2F', '/', http_build_query($opt, null, '&', PHP_QUERY_RFC3986));
	}
}
