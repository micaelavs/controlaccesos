<?php

$vars_vista['SUBTITULO']                = 'Registrar Personal para '.$nombreContratista;
$vars_template['OPERACION']                = 'alta';

$vars_template['LINK_AJAX_BUSCAR_PERSONA_PERS']= \App\Helper\Vista::get_url('index.php/contratistaspersonal/buscarPersonalAjax');
$vars_template['LINK_AJAX_BUSCAR_PERSONA_AUT']= \App\Helper\Vista::get_url('index.php/contratistaspersonal/buscarAutorizanteAjax');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/contratistaspersonal/contratistaspersonal_buscar.js');


$vars['PERSONA']  = $personas;
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/contratistaspersonal/index/'.$idContratista);
$template = (new \FMT\Template(VISTAS_PATH . '/templates/contratistaspersonal/alta.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$idContratista				= "{$idContratista}";
JS;

$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;
$vista->add_to_var('vars', $vars_vista);

return true;
