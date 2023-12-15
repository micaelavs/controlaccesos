<?php

namespace App\Vista;

$config	= \FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Enrolamiento Biométrico para la Visita';

$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/visitas/enrolar.js'],
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/mensajes.js'],
];
$vars_vista['CSS_FILES'] = [ ];

$vars_vista['JS_FILES']     = [];

$vars_template['NOMBRE'] =($visita->persona->nombre);
$vars_template['APELLIDO'] =($visita->persona->apellido);
$vars_template['DOCUMENTO'] =($visita->persona->documento);
$vars_template['UBICACION_ID'] =($visita->ubicacion->id);
$vars_template['UBICACION_NOMBRE'] =($visita->ubicacion->nombre);
$vars_template['UBICACION_CALLE'] =($visita->ubicacion->calle);
$vars_template['UBICACION_NUMERO'] =($visita->ubicacion->numero);

$vars_template['AUTORIZANTE_NOMBRE'] =($visita->autorizante->nombre.' '.$visita->autorizante->apellido);

$vars_template['ENROLADO'] = !($estaEnrolado) ? 'display:none' : '';
$vars_template['NO_ENROLADO'] = !($estaEnrolado) ? '' : 'display:none';

$vars_template['RE_ENROLAMIENTO'] = ($estaEnrolado) ? 're-' : '';


$vars_template['CANCELAR'] = \App\Helper\Vista::get_url().'/index.php/Visitas/index';

$content  = new \FMT\Template(TEMPLATE_PATH.'/visitas/enrolar.html',$vars_template,['CLEAN'=>false]);

$vars_vista['CONTENT']      = "$content";
$base_url                   = \App\Helper\Vista::get_url();

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars',$vars_vista);

return true;
