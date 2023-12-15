<?php

use \FMT\Template;
use \App\Helper\Vista;

$config = FMT\Configuracion::instancia();

$vars_template['URL_BASE'] = Vista::get_url();
$vars_template['LINK'] = Vista::get_url('index.php/novedades/alta');
$vars_vista['SUBTITULO'] = 'Gestión de Novedades de Empleados.';
$vars_template['TIPO_NOVEDAD'] = \FMT\Helper\Template::select_block($lista_novedades_aux);

$vars_template['BOTON_EXCEL'] = \App\Helper\Vista::get_url("index.php/novedades/exportar_excel");

$novedades = new Template(TEMPLATE_PATH . '/novedades/index.html', $vars_template, ['CLEAN' => false]);

$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('script.js');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('/novedades/novedades.js');
$vars_vista['CSS_FILES'][]		= ['CSS_FILE'   => $config['app']['endpoint_cdn']."/js/select2/css/select2.min.css"];
$vars_vista['JS_FILES'][]		= ['JS_FILE'    => $config['app']['endpoint_cdn']."/js/select2/js/select2.full.min.js"];
$vars_vista['CSS_FILES'][]  = ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'].'/datatables/1.10.12/datatables.min.css'];
$vars_vista['JS_FILES'][]   = ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn']."/datatables/1.10.12/datatables.min.js"];
$vars_vista['JS_FILES'][]   = ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn']."/datatables/defaults.js"];
$endpoint_cdn = $config['app']['endpoint_cdn'];
$base_url = \App\Helper\Vista::get_url();

$vars_vista['JS'][]['JS_CODE']    = <<<JS
var \$endpoint_cdn    = "{$endpoint_cdn}";
var \$base_url        = "{$base_url}";
JS;

$vars_vista['CONTENT'] = "{$novedades}";
$vista->add_to_var('vars', $vars_vista);
