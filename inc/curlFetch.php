<?php


class CurlFetch {
	private $ch;
	
	private $url;
	
	private $handleCookie = false;
	private $receiveStatus = false;
	private $headerHandled = false;
	private $executed = false;
	private $removeHttpCache = false;
	private $bodyHandled = false;
	
	private $responseHeaders = [];
	private $requestHeaders = [];
	private $bodyTmpPath = '';
	private $bodyTmpFp = null;
	
	public function __construct($url) {
		$this->url = $url;
		
		$ch = $this->ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		
		curl_setopt($ch, CURLOPT_TCP_FASTOPEN, true);
		curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_ALL);
		
		curl_setopt($ch, CURLOPT_REFERER, get_server('HTTP_REFERER'));
		
		$this->bodyTmpPath = sys_get_temp_dir() . '/mirror/';
		$uriInfo = parse_url($url);
		if (!empty($uriInfo['query'])) {
			$this->bodyTmpPath .= crc32($uriInfo['query']) . '_';
		}
		$this->bodyTmpPath .= basename($uriInfo['path']);
		$this->bodyTmpPath = normalizePath($this->bodyTmpPath);
//		curl_setopt($ch, CURLOPT_SSLVERSION, 3);
		
		return $ch;
	}
	
	public function __destruct() {
		if ($this->bodyTmpFp) {
			fclose($this->bodyTmpFp);
			$this->bodyTmpFp = null;
		}
		if (file_exists($this->bodyTmpPath)) {
//			systemLogError('the temp file is : ' . $this->bodyTmpPath);
			unlink($this->bodyTmpPath);
		}
	}
	
	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}
	
	public function proxy($server) {
		curl_setopt($this->ch, CURLOPT_PROXY, $server);
		curl_setopt($this->ch, CURLOPT_PROXYTYPE, 7);
		// CURLPROXY_SOCKS5 -> CURLPROXY_SOCKS5_HOSTNAME(7)
	}
	
	public function useCookie($site) {
		$this->handleCookie = true;
		$cookie = '/data/contents/mirror-cache/cookie.' . $site . '.txt';
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $cookie);
	}
	
	/**
	 * @return string[][]
	 */
	public function getResponseHeaders() {
		return $this->responseHeaders;
	}
	
	public function passResponseHeaders() {
		if ($this->headerHandled) {
			throw new Error('passResponseHeaders already called');
		}
		$this->headerHandled = true;
	}
	
	private function _downloadResponseBody() {
//		 systemLogInfo('download ' . $this->getUrl());
//		 systemLogInfo('download file to ' . $this->bodyTmpPath);
		
		$d = dirname($this->bodyTmpPath);
		if (!file_exists($d)) {
//			systemLogInfo('Create directory: ' . $d);
			mkdir($d);
		}
		
		$this->bodyTmpFp = fopen($this->bodyTmpPath, 'w');
		
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($this->ch, CURLOPT_FILE, $this->bodyTmpFp);
		
		curl_exec($this->ch);
		
		fclose($this->bodyTmpFp);
		$this->bodyTmpFp = null;
	}
	
	public function passResponseBody() {
		if ($this->bodyHandled) {
			throw new Error('passResponseBody already called');
		}
		$this->bodyHandled = true;
		$headerSent = false;
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		
		curl_setopt($this->ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$headerSent) {
			if (!$headerSent) {
				http_response_code(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));
				if ($this->headerHandled) {
					$this->doPassHeader();
				}
				$headerSent = true;
			}
			echo $data;
			return strlen($data);
		}); // callad every CURLOPT_BUFFERSIZE
		
	}
	
	private function doPassHeader() {
		$ignoredHeaders = ['content-encoding'];
		$httpCacheHeaders = ['x-accel-expires', 'expires', 'cache-control', 'set-cookie', 'vary'];
		foreach ($this->responseHeaders as $name => $values) {
			if ($this->handleCookie && $name === 'set-cookie') {
				continue;
			} elseif (in_array($name, $ignoredHeaders)) {
				foreach ($values as $index => $value) {
					header("X-Ignore-Header: $name = $value");
				}
			} elseif ($this->removeHttpCache && in_array($name, $httpCacheHeaders)) {
				continue;
			} else {
				foreach ($values as $index => $value) {
					header("$name: $value", $index === 0);
				}
			}
		}
	}
	
	public function appendHeader(array $headers) {
		$this->requestHeaders = array_merge($this->requestHeaders, $headers);
		$this->requestHeaders[] = "accpet: " . get_server('HTTP_ACCEPT');
		$this->requestHeaders[] = "cache-control: " . get_server('HTTP_CACHE_CONTROL');
	}
	
	public function filterHttpCacheHeaders() {
		$this->removeHttpCache = true;
	}
	
	private function CURLOPT_HEADERFUNCTION($ch, $rawHeader) {
		if ($this->receiveStatus) { // skip first time, it is HTTP 200 OK
			$header = explode(':', $rawHeader, 2);
			if (count($header) < 2) // ignore invalid headers
			{
				return strlen($rawHeader);
			}
			
			$name = strtolower(trim($header[0]));
			if (!array_key_exists($name, $this->responseHeaders)) {
				$this->responseHeaders[$name] = [trim($header[1])];
			} else {
				$this->responseHeaders[$name][] = trim($header[1]);
			}
			
		} else {
			$this->receiveStatus = true;
		}
		return strlen($rawHeader);
	}
	
	public function exec() {
		if ($this->executed) {
			throw new Error('CURL Already executed');
		}
		$this->executed = true;
		
		curl_setopt($this->ch, CURLOPT_ENCODING, "");
		curl_setopt($this->ch, CURLOPT_HEADER, false);
		curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, [$this, 'CURLOPT_HEADERFUNCTION']);
		
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->requestHeaders);
		
		if ($this->bodyHandled) {
			curl_exec($this->ch);
		} else {
			$this->_downloadResponseBody();
		}
		
		if ($this->headerHandled) {
			http_response_code(curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE));
		} elseif (!$this->bodyHandled) {
			http_response_code(200);
		}
		
		if (curl_errno($this->ch)) {
			systemLogError('CURL Fail: ' . curl_error($this->ch));
			curl_close($this->ch);
			return false;
		}
		curl_close($this->ch);
		
		if ($this->headerHandled && !$this->bodyHandled) {
			$this->doPassHeader();
		}
		
		return true;
	}
	
	public function getResponseBodyFile() {
		return $this->bodyTmpPath;
	}
	
	/**
	 * @return string
	 */
	public function getResponseBody() {
		if ($this->bodyHandled) {
			throw new Error('Response body has already been passed to browser, cannot get it.');
		}
		if (!file_exists($this->bodyTmpPath)) {
			throw new Error('Response body not write to disk.');
		}
		
		return file_get_contents($this->bodyTmpPath);
	}
}
