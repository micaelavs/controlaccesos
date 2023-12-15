<?php

use App\Modelo\Novedad;
use \FMT\Helper\Template;
use FMT\Vista;

$config	= \FMT\Configuracion::instancia();
$vars_vista['SUBTITULO']		= 'Modificación de Novedades de Empleados';
$vars_vista['CSS_FILES'][]		= ['CSS_FILE'   => $config['app']['endpoint_cdn']."/js/select2/css/select2.min.css"];
$vars_vista['JS_FILES'][]		= ['JS_FILE'    => $config['app']['endpoint_cdn']."/js/select2/js/select2.full.min.js"];
$vars_template['OPERACION']				= 'modificacion';
$vars_template['TITULO_2'] = 'Histórico de Novedades';
$vars_template['SUBTITULO_2']		= 'Ingrese DNI para obtener las últimas 7 novedades';
$vars_template['TIPO_NOVEDAD'] = \FMT\Helper\Template::select_block($lista_novedades_aux, $novedad->tipo_novedad);
$vars_template['dni']				= $novedad->empleado->documento;
$vars_template['NOMBRE']			= $novedad->empleado->nombre.' '.$novedad->empleado->apellido;
$vars_template['FECHA_DESDE']		= !empty($temp = $novedad->fecha_desde) ? $temp->format('d/m/Y') : '';
$vars_template['FECHA_HASTA']		= !empty($temp = $novedad->fecha_hasta) ? $temp->format('d/m/Y') : '';
$vars_template['HORA_INICIO']		= !empty($temp = $novedad->fecha_desde) ? $temp->format("H:i") : '';
$vars_template['HORA_FIN']		= !empty($temp = $novedad->fecha_hasta) ? $temp->format("H:i") : '';
$vars_template['DISABLED'] = 'disabled';
$vars_template['BOTON_EXCEL'] = \App\Helper\Vista::get_url("index.php/novedades/exportar_ultimas_siete_excel");
$vars_vista['CSS_FILES'][]  = ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'].'/datatables/1.10.12/datatables.min.css'];
$vars_vista['JS_FILES'][]   = ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn']."/datatables/1.10.12/datatables.min.js"];
$vars_vista['JS_FILES'][]   = ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn']."/datatables/defaults.js"];
$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] =   \App\Helper\Vista::get_url('bootstrap-typeahead.min.js');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']  =  \App\Helper\Vista::get_url('script.js');
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/Novedades/index'); 
$template = (new \FMT\Template(VISTAS_PATH.'/templates/novedades/alta.html', $vars_template,['CLEAN'=>false]));
$vars_vista['CONTENT'] = "$template";
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']     = \App\Helper\Vista::get_url('/novedades/novedades.js');
$base_url = \App\Helper\Vista::get_url('index.php');
$endpoint_cdn = $config['app']['endpoint_cdn'];
$comision_horaria = Novedad::TIPO_COMISION_HORARIA;
$vars_vista['JS'][]['JS_CODE']	= <<<JS
var \$endpoint_cdn    = "{$endpoint_cdn}";
var \$base_url = "{$base_url}";
var \$comision_horaria = "{$comision_horaria}";
JS;
	$vista->add_to_var('vars',$vars_vista);
	return true;
