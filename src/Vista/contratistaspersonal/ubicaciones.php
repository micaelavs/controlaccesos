<?php

$config	= FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Asignación de permisos de entrada a ubicaciones | '.$nombreCompletoPersonal;
$vars_template['OPERACION']                = 'agregar';

$vars_template['UBICACION']				= \FMT\Helper\Template::select_block($ubicaciones);
$vars_template['IDCONTRATISTA']				= $idPersonal;

$vars_vista['JS_FOOTER']    = [['JS_SCRIPT' => \App\Helper\Vista::get_url() . '/js/contratistaspersonal/contratistasubicaciones.js'],];
$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . '/datatables/1.10.12/datatables.min.css'];
$vars_vista['JS_FILES']     = [
	['JS_FILE' => $config['app']['endpoint_cdn'] . "/datatables/1.10.12/datatables.min.js"],
	['JS_FILE' => $config['app']['endpoint_cdn'] . "/datatables/defaults.js"],
];

$vars_template['LINK'] = \App\Helper\Vista::get_url('index.php/contratistaspersonal/alta');
$vars_template['FORM_EDITAR'] = \App\Helper\Vista::get_url('index.php/contratistaspersonal/ubicacion_editar/'.$idPersonal);

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/contratistaspersonal/index/'.$empresaId);
$content  = new \FMT\Template(TEMPLATE_PATH . '/contratistaspersonal/ubicaciones.html', $vars_template, ['CLEAN' => false]);
$vars_vista['CONTENT']      = "$content";

$base_url                   = \App\Helper\Vista::get_url();
$idContratistaPersonal= $idPersonal;

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$idContratistaPersonal				= "{$idContratistaPersonal}";
JS;

$vars_vista['CONTENT'] = "{$content}";
$vista->add_to_var('vars', $vars_vista);