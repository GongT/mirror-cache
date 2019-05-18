<?php

class CacheInformationLog
{
	private $upstream;
	private $pdo;
	
	public function __construct(Upstream &$upstream)
	{
		$this->upstream = &$upstream;
		$this->pdo = new PDO("mysql:charset=utf8mb4;unix_socket=" . MYSQL_SOCKET . ";dbname=" . MYSQL_USAGE, MYSQL_USAGE, MYSQL_USAGE);
	}
	
	
}
