<?php
return [
	'api'	=> [
		'tokens_autorizados'	=> [
			['id_modulo'	=>' 1', 'token'	=> 'EXAMPLE'],
		],
	],
];

/**
 * Los token se pueden generar con:
 * $seed   = openssl_random_pseudo_bytes(4028);
 * echo base64_encode(sha1($seed).md5($seed));
 */

