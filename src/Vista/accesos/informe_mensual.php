<?php

use \FMT\Template;
use \App\Helper\Vista;

$rol = App\Modelo\AppRoles::obtener_rol();
$config = FMT\Configuracion::instancia();

$vars_template['URL_BASE'] = Vista::get_url();
$vars_vista['SUBTITULO'] = 'Informe Mensual de Accesos';

$vars_template['DEPENDENCIA']                = \FMT\Helper\Template::select_block($dependencias);

$vars_template['OPERACION_OTRAS']                = 'pdf_otras';
$vars_template['OPERACION_EMPLEADOS']                = 'pdf_empleados';
$vars_template['OPERACION_AMBOS']                = 'pdf_ambos';

$vars_template['LINK'] = \App\Helper\Vista::get_url('index.php/Accesos/informe_mensual_pdf/');

$reporte = new Template(TEMPLATE_PATH . '/accesos/informe_mensual.html', $vars_template, ['CLEAN' => false]);

$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('script.js');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('/accesos/informe_mensual.js');
$vars_vista['CSS_FILES'][]  = ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . '/datatables/1.10.12/datatables.min.css'];
$vars_vista['JS_FILES'][]   = ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . "/datatables/1.10.12/datatables.min.js"];
$vars_vista['JS_FILES'][]   = ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . "/datatables/defaults.js"];
$endpoint_cdn = $config['app']['endpoint_cdn'];
$base_url = \App\Helper\Vista::get_url();

$vars_vista['JS'][]['JS_CODE']    = <<<JS
var \$endpoint_cdn    = "{$endpoint_cdn}";
var \$base_url        = "{$base_url}";
JS;

$vars_vista['CONTENT'] = "{$reporte}";
$vista->add_to_var('vars', $vars_vista);
