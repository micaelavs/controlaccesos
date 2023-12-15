<?php

namespace App\Vista;

$config	= \FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Listado Tarjetas';

$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/tarjetas/index.js'],
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/mensajes.js'],
];
$vars_vista['CSS_FILES'] = [ 
    ['CSS_FILE'   => $vista->getSystemConfig()['app']['endpoint_cdn']."/js/select2/css/select2.min.css"],
];

$vars_vista['JS_FILES']     = [
    ['JS_FILE'    => $config['app']['endpoint_cdn']."/js/select2/js/select2.full.min.js"],
];

$vars_template['RELOJES'] = \FMT\Helper\Template::select_block($listaRelojes, null);

$content  = new \FMT\Template(TEMPLATE_PATH.'/tarjetas/index.html',$vars_template,['CLEAN'=>false]);

$vars_vista['CONTENT']      = "$content";
$base_url                   = \App\Helper\Vista::get_url();

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars',$vars_vista);

return true;
