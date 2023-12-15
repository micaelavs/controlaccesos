<?php
$vars_template['TEXTO_AVISO'] = 'Dará de baja  ';
$vars_template['ARTICULO'] = 'el Registro de advertencia ' . $advertencia->texto . ' de la persona: ' ;
$vars_template['NOMBRE'] =  $advertencia->persona->nombre . $advertencia->persona->apellido;
$vars_template['TEXTO_EXTRA'] = '<br> y solicitante : ' . $advertencia->solicitante->nombre . $advertencia->solicitante->apellido;;


$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/advertencias/index');
$template = (new \FMT\Template(VISTAS_PATH.'/widgets/confirmacion.html', $vars_template,['CLEAN'=>false]));
$vars_vista['CONTENT'] = "$template";
$vista->add_to_var('vars',$vars_vista);

return true;
