<?php


abstract class Upstream {
	/** @var  string */
	private $m_uri;
	
	/**
	 * @return string
	 */
	abstract public function type();
	
	/**
	 * @return string[]
	 */
	protected abstract function getDomain();
	
	function __construct($path) {
		$this->m_uri = $path;
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
	public abstract function shouldForceCache();

	public function shouldNotCache() {
		return false;
	}
	
	/** @return string */
	public function requestUri() {
		return rtrim(selectDomain($this->getDomain()), '/') . '/' . ltrim($this->uri(), '/');
	}
	
	/** @return  string */
	public function uri() {
		return $this->m_uri;
	}
	
	/**
	 * @param string $qs
	 * @return string
	 */
	public function originalUrl(string $qs) {
		return appendArgs($this->requestUri(), $qs);
	}
	
	public function toOutsideNginxPurgeUrl(string $qs) {
		return '/' . $this->type() . '/' . ltrim($this->uri(), '/') . '?' . $qs;
	}
	
	public function toNginxPurgeUrl(string $qs) {
		$opt = [
			ARG_NAME_TYPE => $this->type(),
			ARG_NAME_URL => $this->uri(),
			ARG_NAME_QS => $qs,
		];
		
		return '/purge.php?' .
			str_replace('%2F', '/', http_build_query($opt, null, '&', PHP_QUERY_RFC3986));
	}
	
	public function toNginxProxiedUrl(string $qs) {
		$opt = [
			ARG_NAME_TYPE => $this->type(),
			ARG_NAME_URL => $this->uri(),
			ARG_NAME_QS => $qs,
		];
		
		return '/fetch.php?' .
			str_replace('%2F', '/', http_build_query($opt, null, '&', PHP_QUERY_RFC3986));
	}
	
	
	/**
	 * @param CurlFetch $curl
	 * @return boolean
	 */
	public abstract function needTransformBody(CurlFetch $curl);
	
	/**
	 * @param CurlFetch $curl
	 * @return string
	 */
	public function transformBody(CurlFetch $curl) {
		throw new Error('Must implement transferBody');
	}
}
