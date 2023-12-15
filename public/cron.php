<?php
require_once __DIR__.'/../bootstrap.php';

$_SESSION['iu']	= $config['app']['id_usuario_sistema'];

$denegar    = ['REMOTE_ADDR', 'REQUEST_METHOD', 'HTTP_HOST', 'HTTP_CONNECTION'];
foreach ($denegar as $value) {
    if(isset($_SERVER[$value])){
        header('HTTP/1.0 403 Forbidden ');
        exit;
    }
}
$controller = 'Cron';
$accion     = FMT\Helper\Arr::path($_SERVER, 'argv.1', 'index');
$class      = 'App\\Controlador\\' . ucfirst(strtolower($controller));
$control    = new $class(strtolower($accion));
$control->procesar();
