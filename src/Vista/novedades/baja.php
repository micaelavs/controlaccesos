<?php
/** @var $enfermeras */
/** @var $vista */

$vars_template['TEXTO_AVISO'] = 'Dará de baja  ';
$vars_template['ARTICULO'] = 'el Registro';
$vars_vista['SUBTITULO'] = 'Baja de Novedad';
$vars_template['CONTROL'] = 'novedad: ';
$vars_template['NOMBRE'] =  $lista_novedades_aux[$novedad->tipo_novedad]['nombre'] . 'del Empleado: '. $nombre_empleado;
$vars_template['ACCESO'] = isset($id_acceso) ? $id_acceso : 0;
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/novedades/index');
$template = (new \FMT\Template(VISTAS_PATH.'/widgets/confirmacion.html', $vars_template,['CLEAN'=>false]));
$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'].'/datatables/1.10.12/datatables.min.css'];
$vars_vista['JS_FILES'][]	= ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn']."/datatables/1.10.12/datatables.min.js"];
$vars_vista['JS_FILES'][]	= ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn']."/datatables/defaults.js"];
$vars_vista['CONTENT'] = "$template";
$vista->add_to_var('vars',$vars_vista);
return true;
