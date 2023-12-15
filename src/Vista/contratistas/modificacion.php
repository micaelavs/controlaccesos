<?php

$vars_vista['SUBTITULO']                = 'Modificar Contratista';
$vars_template['OPERACION']                = 'modificacion';

if(is_null($data_contratista)){
    $data = $contratista;
}else{
    $data = $data_contratista;
}

$vars_template['CUIT'] = !empty($data->cuit) ? $data->cuit : '';
$vars_template['DOCUMENTO'] = !empty($data->documento) ? $data->documento : '';
$vars_template['NOMBRE'] = !empty($data->nombre) ? $data->nombre : '';
$vars_template['DIRECCION'] = !empty($data->direccion) ? $data->direccion : '';
$vars_template['LOCALIDAD'] = \FMT\Helper\Template::select_block($localidades, (!empty($data->localidad_id) ? $data->localidad_id : ''));
$vars_template['PROVINCIA']= \FMT\Helper\Template::select_block($provincias, (!empty($data->provincia_id) ? $data->provincia_id : ''));

$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url().'/js/contratistas/modificacion.js';


$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/contratistas/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/contratistas/modificacion.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";


$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;
$vista->add_to_var('vars', $vars_vista);

return true;
