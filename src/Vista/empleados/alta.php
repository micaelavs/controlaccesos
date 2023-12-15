<?php

use App\Modelo\Empleado;
use App\Modelo\Persona;
use FMT\Configuracion;
$config = Configuracion::instancia();

$vars_vista['SUBTITULO']                = 'Registrar Empleado';
$vars_template['OPERACION']                = 'alta';

//DATOS DE PERSONA
$vars_template['DOCUMENTO'] = !empty($empleado->documento) ? $empleado->documento : '';
$vars_template['NOMBRE'] = !empty($empleado->nombre) ?  $empleado->nombre : '';
$vars_template['APELLIDO'] = !empty($empleado->apellido) ?  $empleado->apellido : '';
$vars_template['CUIT'] = !empty($empleado->cuit) ?  $empleado->cuit : '';
$vars_template['EMAIL'] = !empty($empleado->email) ?  $empleado->email : '';
$vars_template['GENERO'] = \FMT\Helper\Template::select_block($generos,$empleado->genero);
$vars_template['OBSERVACIONES'] = !empty($empleado->observacion) ?  $empleado->observacion : '';


$vars_template['DEPENDENCIA']				= \FMT\Helper\Template::select_block($dependencias,$empleado->dependencia_principal);
$vars_template['CONTRATO']				= \FMT\Helper\Template::select_block($contratos,$empleado->id_tipo_contrato);


$vars_template['CARGOS']=\FMT\Helper\Template::select_block($cargos,$empleado->cargo);
$vars_template['UBICACIONES_AUTORIZADAS'] = \FMT\Helper\Template::select_block($ubicaciones);



$vars_template['LINK_AJAX_BUSCAR_PERSONA']= \App\Helper\Vista::get_url('index.php/empleados/buscarDatosPersonaAjax');
$vars_template['LINK_AJAX_BUSCAR_EMPLEADO']= \App\Helper\Vista::get_url('index.php/empleados/buscarDatosEmpleadoAjax');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/empleados/empleados_alta.js');

$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => \App\Helper\Vista::get_url() . '/css/empleados/empleados.css'];

if($recuper_info instanceof Persona){
	$vars_template['DOCUMENTO'] = !empty($recuper_info->documento) ? $recuper_info->documento : '';
	$vars_template['NOMBRE'] = !empty($recuper_info->nombre) ?  $recuper_info->nombre : '';
	$vars_template['APELLIDO'] = !empty($recuper_info->apellido) ?  $recuper_info->apellido : '';
	$vars_template['GENERO'] = \FMT\Helper\Template::select_block($generos,$recuper_info->genero);
}


$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/empleados/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/empleados/alta.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";

$ubicaciones_autorizadas = json_encode($empleado->ubicaciones_autorizadas);
$ubicacion = $empleado->ubicacion;
$fecha_desde = !empty($empleado->desde_principal)? $empleado->desde_principal->format('d/m/Y') : '';
$fecha_hasta = !empty($empleado->hasta_principal)? $empleado->hasta_principal->format('d/m/Y') : '';
$desde_contrato = !empty($empleado->desde_contrato)? $empleado->desde_contrato->format('d/m/Y') : '';
$hasta_contrato = !empty($empleado->hasta_contrato)? $empleado->hasta_contrato->format('d/m/Y') : '';
$base_url                   = \App\Helper\Vista::get_url();

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          	= '{$config['app']['endpoint_cdn']}';
	var \$base_url					= "{$base_url}";
	var \$ubicaciones_autorizadas	= {$ubicaciones_autorizadas};
	var \$ubicacion					= "{$ubicacion}";
	var \$fecha_desde				= "{$fecha_desde}";
	var \$fecha_hasta				= "{$fecha_hasta}";
	var \$desde_contrato		= "{$desde_contrato}";
	var \$hasta_contrato		= "{$hasta_contrato}";
JS;
$vista->add_to_var('vars', $vars_vista);

return true;