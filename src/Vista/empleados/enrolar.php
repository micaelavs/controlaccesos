<?php

$vars_vista['SUBTITULO']                = 'Enrolamiento Biométrico para el Empleado';
$vars_template['OPERACION']                = 'enrolar';

if(!isset($data_empleado)){
    $data = $empleado;
}else{
    $data = $data_empleado;
}

//DATOS DE PERSONA
$vars_template['DOCUMENTO'] = !empty($data->documento) ? $data->documento : '';
$vars_template['NOMBRE'] = !empty($data->nombre) ?  $data->nombre : '';
$vars_template['APELLIDO'] = !empty($data->apellido) ?  $data->apellido : '';
$vars_template['GENERO'] = \FMT\Helper\Template::select_block($generos, (!empty($data->genero) ?  $data->genero : ''));

//DATOS DE EMPLEADO


$vars_template['CUIT'] =  !empty($data->cuit) ? $data->cuit  : '';
$vars_template['EMAIL'] = !empty($data->email) ?  $data->email : '';
$vars_template['DEPENDENCIA'] = $dependencia->nombre;
$vars_template['CONTRATO'] = \FMT\Helper\Template::select_block($contratos, (!empty($data->id_tipo_contrato) ? $data->id_tipo_contrato : ''));
$vars_template['CARGOS'] = \FMT\Helper\Template::select_block($cargos, (!empty($data->cargo) ? $data->cargo : ''));
$vars_template['DESDE_PRINCIPAL']= !empty($data->desde_principal) ? $data->desde_principal->format('dd/mm/YYYY'):'';
$vars_template['HASTA_PRINCIPAL']= !empty($data->hasta_principal) ? $data->hasta_principal->format('dd/mm/YYYY'):'';
$vars_template['DESDE_CONTRATO']= !empty($data->desde_contrato) ? $data->desde_contrato->format('dd/mm/YYYY'):'';
$vars_template['HASTA_CONTRATO']= !empty($data->hasta_contrato) ? $data->hasta_contrato->format('dd/mm/YYYY'):'';
$vars_template['OBSERVACIONES'] = !empty($data->observacion) ? $data->observacion  : '';

$ubicaciones_autorizadas = explode(",", str_replace(' ', '', (!empty($data->ubicaciones_autorizadas) ? $data->ubicaciones_autorizadas : '')));


$vars_template['SPAN_UBICACION'] = '';

$array_ubicaciones_autorizadas=[];

foreach ($ubicaciones as $ubicacion){
    if(in_array($ubicacion['id'], $ubicaciones_autorizadas)){
        array_push($array_ubicaciones_autorizadas,$ubicacion);
    }
}

$vars_template['UBICACIONES_AUT'] = '';
foreach ($array_ubicaciones_autorizadas as $ubicacion){
    $vars_template['UBICACIONES_AUT'] .= '<option value="'. $ubicacion['id'].'">'.$ubicacion['nombre'].'</option>';
}

foreach ($array_ubicaciones_autorizadas as $ubicacion){
    if($data->ubicacion == $ubicacion['id']){
        $vars_template['SPAN_UBICACION'] .= '<li class="list-group-item active"><span class="badge">principal</span><span>'.$ubicacion['nombre'].' | '.$ubicacion['calle'].' '. $ubicacion['numero'].'</span></li>';
    }else{
        $vars_template['SPAN_UBICACION'] .= '<li class="list-group-item"><span>'.$ubicacion['nombre'].' | '.$ubicacion['calle'].' '. $ubicacion['numero'].'</span></li>';
    }
}

$vars_template['ESTA_ENROLADO'] = '';
$vars_template['ENROLAMIENTO'] = '';
$vars_template['DOC_PERSONA'] = $persona->documento;

if($estaEnrolado){
    $vars_template['ESTA_ENROLADO'] .= '<div class="m-b-0 alert alert-success" role="alert"> <span class="fa fa-fw fa-check-circle"></span> <strong> Usuario enrolado</strong> </div>' ;
    $vars_template['ENROLAMIENTO'] .= '<div class="section-title">re-enrolamiento</div>';
}else{    
    $vars_template['ESTA_ENROLADO'] .= '<div class="m-b-0 alert alert-danger" role="alert" id="enrolar" > <span class="fa fa-fw fa-times-circle"></span> <strong> Usuario no enrolado</strong> </div>';
    $vars_template['ENROLAMIENTO'] .= '<div class="section-title">enrolamiento</div>';
}




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
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/mensajes.js');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/empleados/empleados_modificacion.js');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/empleados/empleados_enrolar.js');

$vars_vista['CSS_FILES'][]    = ['CSS_FILE' => \App\Helper\Vista::get_url() . '/css/empleados/empleados.css'];


$vars['PERSONA']  = $personas;
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/empleados/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/empleados/enrolar.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";

$base_url = \App\Helper\Vista::get_url();
$vars_vista['JS'][]['JS_CODE']            = <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
JS;
$vista->add_to_var('vars', $vars_vista);

return true;
