<?php

namespace App\Vista;

$config	= \FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Registrar accesos restringidos al nodo: '.$reloj->nodo;
$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/relojes/accesos_restringidosAlta.js'],
];
$vars_vista['CSS_FILES'][]	= [];
$vars_vista['JS_FILES']     = [];

$vars_template['OPERACION']		= 'alta';
$vars_template['IP']	= !empty($reloj->ip) ? ($reloj->ip) :'';
$vars_template['NODO']	= !empty($reloj->nodo) ? ($reloj->nodo) :'';
$vars_template['UBICACION']	= !empty($ubicacion) ? ($ubicacion->nombre) :'';
$vars_template['DOCUMENTO']	= !empty($persona_documento) ? ($persona_documento) :'';

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/relojes/accesos_restringidos/'.$reloj->id);

$content  = new \FMT\Template(TEMPLATE_PATH.'/relojes/accesos_restringidosAlta.html',$vars_template,['CLEAN'=>false]);

$vars_vista['CONTENT']      = "$content";
$base_url                   = \App\Helper\Vista::get_url();
$id_reloj = $reloj->id;

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$id_reloj				= "{$id_reloj}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars',$vars_vista);

return true;
