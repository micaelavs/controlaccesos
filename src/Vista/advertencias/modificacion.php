<?php

use App\Modelo\Ubicacion;

$vars_vista['SUBTITULO']                = 'Modificar Advertencia';
$vars_template['OPERACION']                = 'modificacion';
$vars_template['UBICACION']				= \FMT\Helper\Template::select_block($ubicaciones,$ubicacion->id);
$vars_template['ADVERTENCIA_GENERICA']				= \FMT\Helper\Template::select_block($advertencias);

$vars_template['PERSONA_DOCUMENTO'] = !empty($persona->documento) ? $persona->documento : '';
$vars_template['PERSONA_NOMBRE'] = !empty($persona->nombre) ? $persona->nombre : '';
$vars_template['PERSONA_APELLIDO'] = !empty($persona->apellido) ? $persona->apellido : '';

$vars_template['SOLICITANTE_DOCUMENTO'] = !empty($solicitante->documento) ? $solicitante->documento : '';
$vars_template['SOLICITANTE_NOMBRE'] = !empty($solicitante->nombre) ? $solicitante->nombre : '';
$vars_template['SOLICITANTE_APELLIDO'] = !empty($solicitante->apellido) ? $solicitante->apellido : '';

$vars_template['MSJ'] = !empty($msj) ? $msj: '';

$vars_template['LINK_AJAX_BUSCAR_PERSONA_PERS']= \App\Helper\Vista::get_url('index.php/advertencias/buscarPersonalAjax');
$vars_template['LINK_AJAX_BUSCAR_PERSONA_SOL']= \App\Helper\Vista::get_url('index.php/advertencias/buscarSolicitanteAjax');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/advertencias/advertencias_buscar.js');

// $vars['PERSONA']  = $personas;
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/advertencias/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/advertencias/modificacion.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";


$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;
$vista->add_to_var('vars', $vars_vista);

return true;
