<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');
require_once __DIR__ . "/constantes.php";
require_once BASE_PATH . '/vendor/autoload.php';

define('APP_VERSION','2.0.0');

$config	= FMT\Configuracion::instancia();
$config->cargar(BASE_PATH . '/config');


if(!defined('PHP_INTERPRETE')){
    define('PHP_INTERPRETE', \FMT\Helper\Arr::get($config['app'], 'php_interprete', 'php74'));
}


\PHPMailer\PHPMailer\PHPMailer::init($config['email']['app_mailer'], ['CURLOPT_SSL_VERIFYPEER' => \FMT\Helper\Arr::get($config['app'], 'ssl_verifypeer', true)]);
\FMT\Logger::init(empty($_SESSION['iu']) ? '1' : $_SESSION['iu'], $config['logs']['modulo'], $config['logs']['end_point_event'], $config['logs']['end_point_debug'], $config['logs']['debug']);
\FMT\Usuarios::init($config['app']['modulo'], $config['app']['endpoint_panel'].'/api.php', ['CURLOPT_SSL_VERIFYPEER' => \FMT\Helper\Arr::get($config['app'], 'ssl_verifypeer', true)]);
\FMT\Mailer::init($config['email']['app_mailer'], \FMT\Helper\Arr::get($config['app'], 'ssl_verifypeer', true));

\App\Modelo\SigarhuApi::init($config['app']['endpoint_sigarhu']);
\App\Modelo\SigarhuApi::setToken($config['app']['modulo'], \FMT\Helper\Arr::get($config['app'],'access_token_sigarhu'));

\App\Modelo\LocacionesApi::init($config['app']['endpoint_locaciones']);
\App\Modelo\LocacionesApi::setToken($config['app']['modulo'], \FMT\Helper\Arr::get($config['app'],'locaciones_access_token'));

\FMT\Informacion_fecha::init($config['app']['endpoint_informacion_fecha'], [
    'CURLOPT_SSL_VERIFYPEER' => \FMT\Helper\Arr::get($config['app'], 'ssl_verifypeer', true)
]);
if (!isset($_SESSION['contador_login']) && isset($_SESSION['datos_usuario_logueado'])) {
    $datos                      = [];
    $datos['session_data']      = $_SESSION['datos_usuario_logueado'];
    FMT\Logger::event('login', $datos);
    $_SESSION['contador_login'] = true;
}