<?php
require_once __DIR__.'/../bootstrap.php';

//\FMT\ApiAccess::permitir(\FMT\Helper\Arr::get($config['api'], 'tokens_autorizados'));
$_SESSION['iu']	= $config['app']['id_usuario_sistema'];

$accion			= 'index';

switch (true) {
	case (isset($_SERVER['PATH_INFO'])):
		$path_info = explode('/',ltrim($_SERVER['PATH_INFO'],'/'));
		if(isset($path_info[0])){
			$accion		= $path_info[0];
		}
		if(isset($path_info[1])){
			$_id		= $path_info[1];
		}
		break;
	case (isset($_GET['a'])):
		$accion  = (isset($_GET['a'])) ? $_GET['a'] : $accion;
		break;
}

$class		= 'App\\Api\\Api';
if (!class_exists($class, 1)) {
	$class	= 'App\\Controlador\\Error';
	$accion = 'index';
}
$control	= new $class($accion);
if(isset($_id)) {
	$control->set_query('id',$_id);
}
$control->procesar();