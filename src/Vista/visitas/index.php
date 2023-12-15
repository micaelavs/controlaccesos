<?php

namespace App\Vista;

$config	= \FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Visitas Enroladas';

$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/visitas/index.js'],
];
$vars_vista['CSS_FILES'] = [ 
    ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'].'/datatables/1.10.12/datatables.min.css'],
    ['CSS_FILE'   => $vista->getSystemConfig()['app']['endpoint_cdn']."/js/select2/css/select2.min.css"],
];

$vars_vista['JS_FILES']     = [
    ['JS_FILE' => $config['app']['endpoint_cdn']."/datatables/1.10.12/datatables.min.js"],
    ['JS_FILE' => $config['app']['endpoint_cdn']."/datatables/defaults.js"],
    ['JS_FILE'    => $config['app']['endpoint_cdn']."/js/select2/js/select2.full.min.js"],
];

$vars_template['UBICACIONES_AUTORIZADAS'] = \FMT\Helper\Template::select_block($ubicaciones_autorizadas, 0);

$vars_template['CLASS_ESTADO'] = ($estado == 0) ? 'btn-default' : 'btn-info';
$vars_template['ESTADO'] = ($estado == 0) ? 'Activos' : 'Inactivos';
$vars_template['LINK_ESTADO'] =\App\Helper\Vista::get_url('index.php/Visitas/index/').(($estado == 0) ? 1 : 0);
$vars_template['LINK'] = \App\Helper\Vista::get_url().'/index.php/Visitas/alta';

$content  = new \FMT\Template(TEMPLATE_PATH.'/visitas/index.html',$vars_template,['CLEAN'=>false]);

$vars_vista['CONTENT']      = "$content";
$base_url                   = \App\Helper\Vista::get_url();

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$estado				= "{$estado}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars',$vars_vista);

return true;
