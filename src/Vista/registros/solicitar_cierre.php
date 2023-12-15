<?php

use App\Modelo\AppRoles;
use App\Modelo\Ubicacion;

$config	= FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Confirmación de Cierre de Acceso';

$vars_vista['JS_FOOTER']    = [
	['JS_SCRIPT' => \App\Helper\Vista::get_url() . '/js/registros/solicitar_cierre.js'],
];

$vars_vista['JS_FILES']     = [
];


$vars_template['TIPO_ACCESO'] =  \App\Modelo\Acceso::tipoAccesoToString($acceso->tipo_acceso);
$vars_template['NOMBRE'] =  $acceso->persona->nombre;
$vars_template['APELLIDO'] =  $acceso->persona->apellido;
$vars_template['USUARIO_NOMBRE'] =  $empleado->nombre." ".$empleado->apellido;
$vars_template['TIPO_INGRESO'] =   \App\Modelo\Acceso::tipoRegistroToString($acceso->tipo_ingreso);
$vars_template['FECHA_INGRESO'] =  $acceso->ingreso->format('d/m/Y');
$vars_template['HORA_INGRESO'] =  $acceso->ingreso->format('H:i');
$vars_template['PERSONA_INGRESO'] =  $acceso->persona_ingreso->nombre." ".$acceso->persona_ingreso->apellido;
$vars_template['UBICACION'] =  $acceso->ubicacion->nombre;
$vars_template['OBSERVACIONES'] =  $acceso->observaciones;

if($acceso->tipo_acceso === \App\Modelo\Acceso::VISITANTE){
    $vars_template['VISITANTE'][0]['ORIGEN'] = $acceso->origen;
    $vars_template['VISITANTE'][0]['DESTINO'] = $acceso->destino;
}
if(!empty($acceso->autorizante)){
    $vars_template['AUTORIZANTE'][0]['NOMBRE'] = $acceso->autorizante->nombre. " " . $acceso->autorizante->apellido;
}
if ($acceso->tipo_acceso === \App\Modelo\Acceso::CONTRATISTA){
    $vars_template['CONTRATISTA'][0]['NOMBRE'] = $acceso->contratista_empleado->contratista->nombre;
}
if ($acceso->tipo_acceso === \App\Modelo\Acceso::EMPLEADO){
    $vars_template['EMPLEADO'][0]['NOMBRE'] = $acceso->empleado->nombre. " " . $acceso->empleado->apellido;
    $vars_template['EMPLEADO'][0]['DIRECCION'] = \App\Modelo\Ubicacion::obtener($acceso->empleado->ubicacion)->calle." ".\App\Modelo\Ubicacion::obtener($acceso->empleado->ubicacion)->numero;
}


$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/Registros/accesos_sin_cierre');

$content  = new \FMT\Template(TEMPLATE_PATH . '/registros/solicitar_cierre.html', $vars_template, ['CLEAN' => false]);

$vars_vista['CONTENT']      = "$content";

$ingreso = $acceso->ingreso->format('d/m/Y');
$base_url                   = \App\Helper\Vista::get_url();

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$ingreso				= "{$ingreso}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars', $vars_vista);
