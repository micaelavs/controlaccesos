<?php

use App\Modelo\AppRoles;

$config	= FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Registro de Acceso Manual';

$vars_vista['JS_FOOTER']    = [
];

$vars_vista['JS_FILES']     = [
];
$vars_template['REGISTRO_EMPLEADO'] = (AppRoles::puede('Registros','carga_individual') != false) ? 'display:block;' :'display:none;';
$vars_template['REGISTRO_CONTRATISTA'] = (AppRoles::puede('Registros','carga_individual_contratista')  != false) ? 'display:block;' :'display:none;';
$vars_template['REGISTRO_VISITA'] =  (AppRoles::puede('Registros','carga_individual_visita')  != false) ? 'display:block;' :'display:none;';
$vars_template['REGISTRO_SIN_CIERRE'] = (AppRoles::puede('Registros','accesos_sin_cierre')  != false) ? 'display:block;' :'display:none;';

$content  = new \FMT\Template(TEMPLATE_PATH . '/registros/index.html', $vars_template, ['CLEAN' => false]);

$vars_vista['CONTENT']      = "$content";

$base_url                   = \App\Helper\Vista::get_url();
$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars', $vars_vista);
