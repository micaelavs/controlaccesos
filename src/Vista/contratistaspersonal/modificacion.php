<?php

$vars_vista['SUBTITULO']                = 'Modificacion Personal para '.$nombreContratista;
$vars_template['OPERACION']                = 'modificacion';

$vars_template['PERSONA_DOCUMENTO'] = !empty($contratistasPersonal->persona->documento) ? $contratistasPersonal->persona->documento : '';
$vars_template['PERSONA_NOMBRE'] = !empty($contratistasPersonal->persona->nombre) ? $contratistasPersonal->persona->nombre : '';
$vars_template['PERSONA_APELLIDO'] = !empty($contratistasPersonal->persona->apellido) ? $contratistasPersonal->persona->apellido : '';

$vars_template['AUTORIZANTE_DOCUMENTO'] = !empty($contratistasPersonal->autorizante->documento) ? $contratistasPersonal->autorizante->documento : '';
$vars_template['AUTORIZANTE_NOMBRE'] = !empty($contratistasPersonal->autorizante->nombre) ? $contratistasPersonal->autorizante->nombre : '';
$vars_template['AUTORIZANTE_APELLIDO'] = !empty($contratistasPersonal->autorizante->apellido) ? $contratistasPersonal->autorizante->apellido : '';

$vars_template['ART_INICIO'] = !empty($contratistasPersonal->art_inicio) ? $contratistasPersonal->art_inicio->format('dd/mm/YYYY'): '';
$vars_template['ART_FIN'] = !empty($contratistasPersonal->art_fin) ? $contratistasPersonal->art_fin->format('dd/mm/YYYY'):'';

$vars_template['LINK_AJAX_BUSCAR_PERSONA_PERS']= \App\Helper\Vista::get_url('index.php/contratistaspersonal/buscarPersonalAjax');
$vars_template['LINK_AJAX_BUSCAR_PERSONA_AUT']= \App\Helper\Vista::get_url('index.php/contratistaspersonal/buscarAutorizanteAjax');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/contratistaspersonal/contratistaspersonal_buscar.js');

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/contratistaspersonal/index/'.$idContratista);
$template = (new \FMT\Template(VISTAS_PATH . '/templates/contratistaspersonal/modificacion.html', $vars_template, ['CLEAN' => false]));
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
