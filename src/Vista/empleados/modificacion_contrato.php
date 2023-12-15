<?php

$config	= \FMT\Configuracion::instancia();

$vars_vista['SUBTITULO']                = 'Modificar Contrato';
$vars_template['OPERACION_RENOVAR']                = 'renovar_contrato';
$vars_template['OPERACION_FINALIZAR']                = 'finalizar_contrato';


$data = $empleado;
$vars_template['TIENE_CONTRATO'] = '';
$vars_template['BOTON_FINALIZAR_DISPLAY'] = '';

if($inactivo){
    $vars_template['TIENE_CONTRATO'] = 'readonly';
    $vars_template['BOTON_FINALIZAR_DISPLAY'] = 'style="display:none"';
}

//DATOS DE PERSONA
$vars_template['DOCUMENTO'] = !empty($data->documento) ? $data->documento : '';
$vars_template['NOMBRE'] = !empty($data->nombre) ?  $data->nombre : '';
$vars_template['APELLIDO'] = !empty($data->apellido) ?  $data->apellido : '';
$vars_template['GENERO'] = \FMT\Helper\Template::select_block($generos, (!empty($data->genero) ?  $data->genero : ''));

//DATOS DE EMPLEADO


$vars_template['CUIT'] =  !empty($data->cuit) ? $data->cuit  : '';
$vars_template['EMAIL'] = !empty($data->email) ?  $data->email : '';
$vars_template['DEPENDENCIA'] = \FMT\Helper\Template::select_block($dependencias, (!empty($data->dependencia_principal) ? $data->dependencia_principal : ''));
$vars_template['CONTRATO'] = \FMT\Helper\Template::select_block($contratos, (!empty($data->id_tipo_contrato) ? $data->id_tipo_contrato : ''));
$vars_template['CARGOS'] = \FMT\Helper\Template::select_block($cargos, (!empty($data->cargo) ? $data->cargo : ''));
$vars_template['DESDE_PRINCIPAL']= !empty($data->desde_principal) ? $data->desde_principal->format('dd/mm/YYYY'):'';
$vars_template['HASTA_PRINCIPAL']= !empty($data->hasta_principal) ? $data->hasta_principal->format('dd/mm/YYYY'):'';
$vars_template['DESDE_CONTRATO']= !empty($data->desde_contrato) ? $data->desde_contrato->format('dd/mm/YYYY'):'';
$vars_template['HASTA_CONTRATO']= !empty($data->hasta_contrato) ? $data->hasta_contrato->format('dd/mm/YYYY'):'';
$vars_template['OBSERVACIONES'] = !empty($data->observacion) ? $data->observacion  : '';

$ubicaciones_autorizadas = explode(",", str_replace(' ', '', (!empty($data->ubicaciones_autorizadas) ? $data->ubicaciones_autorizadas : '')));
$vars_template['UBICACION'] = \FMT\Helper\Template::select_block($ubicaciones, $ubicaciones_autorizadas);

if (!empty($data->planilla_reloj)){
    if ($data->planilla_reloj == 1){
        $vars_template['PLANILLA_RELOJ'] =  'checked';
    }else{
        $vars_template['PLANILLA_RELOJ'] =  '';    
    }
}else{
    $vars_template['PLANILLA_RELOJ'] =  '';    
}







$vars_template['LINK_AJAX_BUSCAR_PERSONA'] = \App\Helper\Vista::get_url('index.php/empleados/buscarDatosPersonaAjax');
$vars_template['LINK_AJAX_BUSCAR_EMPLEADO'] = \App\Helper\Vista::get_url('index.php/empleados/buscarDatosEmpleadoAjax');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url().'/js/mensajes.js';
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/empleados/empleados_modificacion.js');
$vars_vista['CSS_FILES'][]    = ['CSS_FILE' => \App\Helper\Vista::get_url() . '/css/empleados/empleados.css'];


$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/empleados/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/empleados/modificacion_contrato.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";

$base_url                   = \App\Helper\Vista::get_url();

$vars_vista['JS'][]['JS_CODE']            = <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
JS;
$vista->add_to_var('vars', $vars_vista);

return true;
