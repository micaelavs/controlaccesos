<?php

$vars_vista['SUBTITULO']                = 'Modificar Advertencia Genérica';
$vars_template['OPERACION']                = 'modificacion';
$vars_template['TEXTO'] = !empty($advertencia->texto) ? $advertencia->texto : '';

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/advertenciasgenericas/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/advertenciasgenericas/modificacion.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";


$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;
$vista->add_to_var('vars', $vars_vista);

return true;
