<?php

$vars_vista['SUBTITULO']                = 'Registrar Advertencia';
$vars_template['OPERACION']                = 'alta';
$vars_template['UBICACION']				= \FMT\Helper\Template::select_block($ubicaciones);
$vars_template['ADVERTENCIA_GENERICA']				= \FMT\Helper\Template::select_block($advertencias);

// $vars_template['DOCUMENTO'] = !empty($personas->documento) ? $personas->documento : '';
// $vars_template['NOMBRE'] = !empty($personas->nombre) ? $personas->nombre : '';
// $vars_template['APELLIDO'] = !empty($personas->apellido) ? $personas->apellido : '';
// $vars_template['GENERO']=\FMT\Helper\Template::select_block($generos);

$vars_template['LINK_AJAX_BUSCAR_PERSONA_PERS']= \App\Helper\Vista::get_url('index.php/advertencias/buscarPersonalAjax');
$vars_template['LINK_AJAX_BUSCAR_PERSONA_SOL']= \App\Helper\Vista::get_url('index.php/advertencias/buscarSolicitanteAjax');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/advertencias/advertencias_buscar.js');

// $vars['PERSONA']  = $personas;
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/advertencias/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/advertencias/alta.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";


$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;
$vista->add_to_var('vars', $vars_vista);

return true;
