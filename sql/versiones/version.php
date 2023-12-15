<?php
require_once __DIR__ . "/../../constantes.php";
require_once BASE_PATH . '/vendor/autoload.php';

$config = FMT\Configuracion::instancia();
$config->cargar(BASE_PATH . '/config');

if(!isset($argv[1])){
    echo "\n\t\t:: Especifique una versión. ::\n\n";   
    exit;
}

$file = (preg_match('/\.sql$/',$argv[1])) 
    ? BASE_PATH.'/sql/versiones/'.$argv[1] 
    : BASE_PATH."/sql/versiones/{$argv[1]}.sql";

$contenido = file_get_contents("$file");
$sql = preg_replace(
    ['/\{\{\{user_mysql\}\}\}/','/\{\{\{db_log\}\}\}/','/\{\{\{db_app\}\}\}/'],
    [$config['database']['user_admin'],$config['database']['db_log']['database'],$config['database']['database']],
    $contenido
);
echo $sql;
