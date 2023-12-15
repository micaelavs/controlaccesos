<?php

namespace App\Vista;

$config	= \FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Registrar empleado que recibe correo para Alerta Reloj';
$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/alertarelojes/alta.js'],
];
$vars_vista['CSS_FILES'][]	= [];
$vars_vista['JS_FILES']     = [];

$vars_template['OPERACION']		= 'alta';

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/AlertaRelojes/index');

$content  = new \FMT\Template(TEMPLATE_PATH.'/alertarelojes/alta.html',$vars_template,['CLEAN'=>false]);

$vars_vista['CONTENT']      = "$content";
$base_url                   = \App\Helper\Vista::get_url();
$id_reloj = $reloj->id;

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars',$vars_vista);

return true;
