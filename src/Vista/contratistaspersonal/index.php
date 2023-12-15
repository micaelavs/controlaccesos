<?php

$config	= FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
//$vars_vista['SUBTITULO']    = 'Lista de Personal para ' . '<a href= >' . $nombreContratista . '</a>';
$vars_vista['SUBTITULO']    = 'Lista de Personal para ' . $nombreContratista;

$vars_template['IDCONTRATISTA']				= $idContratista;

$vars_vista['JS_FOOTER']    = [
	['JS_SCRIPT' => \App\Helper\Vista::get_url() . '/js/contratistaspersonal/contratistaspersonal.js'],
];
$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . '/datatables/1.10.12/datatables.min.css'];

$vars_vista['JS_FILES']     = [
	['JS_FILE' => $config['app']['endpoint_cdn'] . "/datatables/1.10.12/datatables.min.js"],
	['JS_FILE' => $config['app']['endpoint_cdn'] . "/datatables/defaults.js"],
];

$vars_template['LINK'] = \App\Helper\Vista::get_url('index.php/contratistaspersonal/alta/'.$idContratista);
$vars_template['VOLVER'] = \App\Helper\Vista::get_url('index.php/contratistas/index/');

$content  = new \FMT\Template(TEMPLATE_PATH . '/contratistaspersonal/index.html', $vars_template, ['CLEAN' => false]);

$vars_vista['CONTENT']      = "$content";

$base_url                   = \App\Helper\Vista::get_url();
$idContratistaPersonal= $idContratista;

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$idContratistaPersonal				= "{$idContratistaPersonal}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars', $vars_vista);