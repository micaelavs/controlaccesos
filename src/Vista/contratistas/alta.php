<?php

$vars_vista['SUBTITULO']                = 'Registrar Contratista';
$vars_template['OPERACION']                = 'alta';

$vars_template['PROVINCIA']=\FMT\Helper\Template::select_block($provincias);
$vars_template['LOCALIDAD'] = \FMT\Helper\Template::select_block($localidades);

$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/mensajes.js'],
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/contratistas/alta.js'],
];

$vars['PERSONA']  = $personas;
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/contratistas/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/contratistas/alta.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";

$base_url                   = \App\Helper\Vista::get_url();
$vars_vista['JS'][]['JS_CODE'] = <<<JS
	var \$base_url				= "{$base_url}";
JS;
$vista->add_to_var('vars', $vars_vista);

return true;
