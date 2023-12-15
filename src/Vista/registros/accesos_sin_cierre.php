<?php

$config	= FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Registro de Accesos Sin Cierre';

$vars_vista['JS_FOOTER']    = [
	['JS_SCRIPT' => \App\Helper\Vista::get_url() . '/js/registros/accesos_sin_cierre.js'],
];
$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . '/datatables/1.10.12/datatables.min.css'];

$vars_vista['JS_FILES']     = [
	['JS_FILE' => $config['app']['endpoint_cdn'] . "/datatables/1.10.12/datatables.min.js"],
	['JS_FILE' => $config['app']['endpoint_cdn'] . "/datatables/defaults.js"],
];

$vars_template['UBICACIONES'] = \FMT\Helper\Template::select_block($ubicaciones);
$vars_template['TIPOS'] = \FMT\Helper\Template::select_block($tipos);
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/Registros/index');

$content  = new \FMT\Template(TEMPLATE_PATH . '/registros/accesos_sin_cierre.html', $vars_template, ['CLEAN' => false]);

$vars_vista['CONTENT']      = "$content";

$base_url                   = \App\Helper\Vista::get_url();
$tipos_registros =  json_encode([
	0 => [
		'type' => 'danger',
		'text' => 'Sin registro',
	],
	App\Modelo\Acceso::TIPO_REGISTRO_ONLINE   => [
		'type' => 'info',
		'text' => 'On line',
	],
	\App\Modelo\Acceso::TIPO_REGISTRO_OFFLINE => [
		'type' => 'warning',
		'text' => 'Off line',
	],
]);

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$tipos_registros				= '{$tipos_registros}';
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars', $vars_vista);
