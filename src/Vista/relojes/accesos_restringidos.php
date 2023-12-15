<?php

namespace App\Vista;

$config	= \FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Empleados con acceso restringido al nodo: '.$reloj->nodo;

$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/relojes/accesos_restringidos.js'],
];
$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'].'/datatables/1.10.12/datatables.min.css'];

$vars_vista['JS_FILES']     = [
    ['JS_FILE' => $config['app']['endpoint_cdn']."/datatables/1.10.12/datatables.min.js"],
    ['JS_FILE' => $config['app']['endpoint_cdn']."/datatables/defaults.js"],
];

$vars_template['LINK'] = \App\Helper\Vista::get_url().'/index.php/relojes/accesos_restringidosAlta/'.$reloj->id;
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/relojes/index');

$content  = new \FMT\Template(TEMPLATE_PATH.'/relojes/accesos_restringidos.html',$vars_template,['CLEAN'=>false]);

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
