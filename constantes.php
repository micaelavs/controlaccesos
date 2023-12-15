<?php
define('BASE_PATH', realpath(__DIR__));
define('VISTAS_PATH', BASE_PATH .'/src/Vista');
define('TEMPLATE_PATH', BASE_PATH .'/src/Vista/templates');
define('CONSOLA_FILE', 'public/consola.php');

if(!empty($_SERVER['_']) && !defined('PHP_INTERPRETE')){
	define('PHP_INTERPRETE', $_SERVER['_']);
}

define('SCRIPT_NAME', '');
define('REQUEST_SCHEME', 'https');


define('DATE_DB_OBJ',					'Y-m-d H:i:s.u');
define('DATE_VIEW_OBJ',					'd/m/Y');
define('DATE_VIEW_OBJ_LONG',			'd/m/Y H:i');
define('DATE_OBJ_DB_LONG',				'Y-m-d H:i:s');
define('DATE_FILENAME',					'Ymd_His');
define('DATE_OBJ_DB_SHORT',				'Y-m-d');
define('DATE_OBJ_DB_SHORT_FIRST_DAY',	'Y-m-01');
define('DATE_OBJ_DB_SHORT_LAST_DAY',	'Y-m-t');
define('DATE_OBJ_DB_LONG_FIRST_HOUR',	'Y-m-d 00:00:01');
define('DATE_OBJ_DB_LONG_LAST_HOUR',	'Y-m-d 23:59:59');