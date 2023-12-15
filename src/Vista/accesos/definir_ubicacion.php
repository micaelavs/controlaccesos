<?php

use \FMT\Template;
use \App\Helper\Vista;

$vars_template['URL_BASE'] = Vista::get_url();
$vars_vista['SUBTITULO'] = 'Definir Ubicacion';
$vars_template['FORM_ACTION'] = \App\Helper\Vista::get_url("index.php/accesos/index");
$vars_template['UBICACION']				= \FMT\Helper\Template::select_block($ubicaciones);

$ubicacion = new Template(TEMPLATE_PATH . '/accesos/definir_ubicacion.html', $vars_template, ['CLEAN' => false]);

$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('script.js');


$vars_vista['JS_FILES'][]   = ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . "/datatables/defaults.js"];

$base_url = \App\Helper\Vista::get_url();

$vars_vista['CONTENT'] = "{$ubicacion}";
$vista->add_to_var('vars', $vars_vista);
