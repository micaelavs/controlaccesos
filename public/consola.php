<?php
require_once __DIR__.'/../bootstrap.php';

//Id de Usuario generico designado para marcar las operaciones del sistema.
$_SESSION['iu'] = $config['app']['id_usuario_sistema'];

$denegar	= ['REMOTE_ADDR', 'REQUEST_METHOD', 'HTTP_HOST', 'HTTP_CONNECTION'];
foreach ($denegar as $value) {
	if(isset($_SERVER[$value])){
		header('HTTP/1.0 403 Forbidden ');
		exit;
	}
}

$controller	= 'Consola';
$accion		= FMT\Helper\Arr::path($_SERVER, 'argv.1', 'help');
$class		= 'App\\Consola\\' . ucfirst(strtolower($controller));

if (!class_exists($class, 1) || !method_exists($class, 'accion_'.$accion)) {
	// $class	= 'App\\Consola\\Consola';
	$accion = 'help';
}

$control	= new $class(strtolower($accion));
$control->procesar();
