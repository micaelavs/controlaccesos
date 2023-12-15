<?php

$vars_vista['SUBTITULO']                = 'Registrar Nueva Advertencia Genérica';
$vars_template['OPERACION']                = 'alta';
// $vars_template['TEXTO'] = !empty($personas->documento) ? $personas->documento : '';

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/advertenciasgenericas/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/advertenciasgenericas/alta.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";


$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;
$vista->add_to_var('vars', $vars_vista);

return true;
