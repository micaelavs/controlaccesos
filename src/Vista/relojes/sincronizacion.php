<?php

namespace App\Vista;

$config	= \FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Lotes de Sincronización del Nodo N° '.$reloj->nodo;

$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/relojes/sincronizacion.js'],
];
$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'].'/datatables/1.10.12/datatables.min.css'];

$vars_vista['JS_FILES']     = [
    ['JS_FILE' => $config['app']['endpoint_cdn']."/datatables/1.10.12/datatables.min.js"],
    ['JS_FILE' => $config['app']['endpoint_cdn']."/datatables/defaults.js"],
];

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/relojes/index');

$content  = new \FMT\Template(TEMPLATE_PATH.'/relojes/sincronizacion.html',$vars_template,['CLEAN'=>false]);

$vars_vista['CONTENT']      = "$content";
$base_url                   = \App\Helper\Vista::get_url();
$nodo = $reloj->nodo;

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$nodo				= "{$nodo}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars',$vars_vista);

return true;
