<?php

namespace App\Vista;

$config	= \FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Alta Visita';
$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('bootstrap-typeahead.min.js');

$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/visitas/alta.js'],
	['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/bootstrap-typeahead.min.js']
];
$vars_vista['CSS_FILES'][]	= [];
$vars_vista['JS_FILES']     = [];

$vars_template['OPERACION']		= 'alta';

$vars_template['NOMBRE'] = !empty($visita->persona) ? $visita->persona->nombre : null;
$vars_template['APELLIDO'] = !empty($visita->persona) ? ($visita->persona->apellido) : null;
$vars_template['GENERO']=\FMT\Helper\Template::select_block($generos,!empty($visita->persona) ? ($visita->persona->documento) : null);
$vars_template['DOCUMENTO'] = !empty($visita->persona) ? ($visita->persona->documento) : null;
$vars_template['UBICACIONES_AUTORIZADAS'] = \FMT\Helper\Template::select_block($ubicaciones_autorizadas, !empty($visita->ubicacion) ? $visita->ubicacion->id : null);
$vars_template['AUTORIZANTE_ID'] = !empty($visita->autorizante) ? ($visita->autorizante->id) : null;
$vars_template['NOMBRE_AUTORIZANTE'] = !empty($visita->autorizante) ? ($visita->autorizante->nombre.' '.$visita->autorizante->apellido) : null;
$vars_template['ACLARACION_AUTORIZACION'] = ($visita->aclaracion_autorizacion);
$vars_template['FECHA_DESDE'] = ($visita->fecha_desde);
$vars_template['FECHA_HASTA'] = ($visita->fecha_hasta);


$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/Visitas/index');

$content  = new \FMT\Template(TEMPLATE_PATH.'/visitas/alta.html',$vars_template,['CLEAN'=>false]);

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
