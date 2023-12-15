<?php

namespace App\Vista;

use App\Modelo\Reloj;

$config	= \FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Logs del Reloj';

$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/relojes/historicosLogsPorNodo.js'],
];
$vars_vista['CSS_FILES'] = [ 
    ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'].'/datatables/1.10.12/datatables.min.css'],
    ['CSS_FILE'   => $vista->getSystemConfig()['app']['endpoint_cdn']."/js/select2/css/select2.min.css"],
    ['CSS_FILE'   => $vista->getSystemConfig()['app']['endpoint_cdn']."/bootstrap/datepicker/4.17.37/css/bootstrap-datetimepicker.min.css"],
    ['CSS_FILE' => \App\Helper\Vista::get_url().'/css/relojes/historicosLogsPorNodo.css'],
];

$vars_vista['JS_FILES']     = [
    ['JS_FILE' => $config['app']['endpoint_cdn']."/datatables/1.10.12/datatables.min.js"],
    ['JS_FILE' => $config['app']['endpoint_cdn']."/datatables/defaults.js"],
    ['JS_FILE'    => $config['app']['endpoint_cdn']."/js/select2/js/select2.full.min.js"],
    ['JS_FILE'    => $config['app']['endpoint_cdn']."/bootstrap/datepicker/4.17.37/js/bootstrap-datetimepicker.min.js"]
];
$vars_template['CODIGOS'] = \FMT\Helper\Template::select_block($codigos, null);

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/Relojes/index');

$vars_template['CONEXION_EXITOSA'] = Reloj::CONEXION_EXITOSA;
$vars_template['SIN_CONEXION'] = Reloj::SIN_CONEXION;
$vars_template['TIMEOUT_CONNECTION'] = Reloj::TIMEOUT_CONNECTION;
$vars_template['SINCRONIZACION_NI_EMPLEADO_NI_VISITA_ENROLADA'] = Reloj::SINCRONIZACION_NI_EMPLEADO_NI_VISITA_ENROLADA;
$vars_template['ACCESO_NI_EMPLEADO_NI_VISITA_ENROLADA'] = Reloj::ACCESO_NI_EMPLEADO_NI_VISITA_ENROLADA;
$vars_template['OPERACION_EXITOSA'] = Reloj::OPERACION_EXITOSA;


$content  = new \FMT\Template(TEMPLATE_PATH.'/relojes/historicosLogsPorNodo.html',$vars_template,['CLEAN'=>false]);

$vars_vista['CONTENT']      = "$content";
$base_url                   = \App\Helper\Vista::get_url();
$nodo = $reloj->nodo;

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$nodo				= "{$nodo}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars',$vars_vista);

return true;
