<?php

namespace App\Vista;

$config	= \FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Modificar Visita';
$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('bootstrap-typeahead.min.js');

$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/visitas/modificacion.js'],
	['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/bootstrap-typeahead.min.js']
];
$vars_vista['CSS_FILES'][]	= [];
$vars_vista['JS_FILES']     = [];

$vars_template['OPERACION']		= 'modificacion';

$vars_template['NOMBRE'] =($visita->persona->nombre);
$vars_template['APELLIDO'] =($visita->persona->apellido);
$vars_template['DOCUMENTO'] =($visita->persona->documento);
$vars_template['UBICACIONES_AUTORIZADAS'] = \FMT\Helper\Template::select_block($ubicaciones_autorizadas, $visita->ubicacion->id);
$vars_template['AUTORIZANTE_ID'] =($visita->autorizante->id);
$vars_template['NOMBRE_AUTORIZANTE'] =($visita->autorizante->nombre.''.$visita->autorizante->apellido);
$vars_template['ACLARACION_AUTORIZACION'] =($visita->aclaracion_autorizacion);
$vars_template['FECHA_DESDE'] =($visita->fecha_desde);
$vars_template['FECHA_HASTA'] =($visita->fecha_hasta);


$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/Visitas/index');

$content  = new \FMT\Template(TEMPLATE_PATH.'/visitas/modificacion.html',$vars_template,['CLEAN'=>false]);

$vars_vista['CONTENT']      = "$content";
$base_url                   = \App\Helper\Vista::get_url();
$fecha_desde = $visita->fecha_desde;
$fecha_hasta = $visita->fecha_hasta;

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$fecha_desde				= "{$fecha_desde}";
	var \$fecha_hasta				= "{$fecha_hasta}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars',$vars_vista);

return true;
