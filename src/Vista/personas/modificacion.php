<?php

$vars_vista['SUBTITULO']	= 'Actualizar Persona';
$vars_template['OPERACION'] = 'modificacion';
$vars_template['DOCUMENTO'] = !empty($personas->documento) ? $personas->documento : '';
$vars_template['NOMBRE'] = !empty($personas->nombre) ? $personas->nombre : '';
$vars_template['APELLIDO'] = !empty($personas->apellido) ? $personas->apellido : '';
$vars_template['GENERO']=\FMT\Helper\Template::select_block($generos,$personas->genero);

$vars_template['LINK_AJAX_BUSCAR_PERSONA']= \App\Helper\Vista::get_url('index.php/personas/buscarPersonaAjax');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/personas/personas_buscar.js');


$vars['PERSONA']  = $personas;
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/personas/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/personas/alta.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";


$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;
$vista->add_to_var('vars', $vars_vista);

return true;
