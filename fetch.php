<?php
http_response_code(500);

require 'inc/lib.php';

$TYPE = get_get('type');
$URL = get_get('url');
$ARGS = get_get('qs', '');

$upstream = loadDomain($TYPE, $URL);

$ch = create_direct_connect($upstream, $ARGS);
exec_request($ch);
