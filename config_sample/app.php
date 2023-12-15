<?php
return [
	'app' => [
		'dev' => false, // Estado del desarrollo
        'modulo' => 0, // Numero del modulo
        'title' => 'Control de Acceso - Ministerio de Transporte', // Nombre del Modulo,
        'titulo_pantalla'           => 'Control de Accesos',
        'endpoint_panel'			=> 'https://panel-testing.transporte.gob.ar',
        'endpoint_informacion_fecha'=> 'https://fecha-testing.transporte.gob.ar/index.php/consulta/',
        'endpoint_sigarhu'			=> 'https://sigarhu-testing.transporte.gob.ar/api.php',
        'endpoint_cdn'              => 'https://cdn-testing.transporte.gob.ar',
        'access_token_sigarhu'	    => '',
		'ssl_verifypeer'			=> true,
		'php_interprete'            => '/usr/bin/php74',
		'id_usuario_sistema'		=>	'999999', //En caso de operaciones automaticas, se establece un id de usuario que identifique al sistema
	]
];
