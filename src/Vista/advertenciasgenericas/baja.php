<?php
$vars_template['TEXTO_AVISO'] = 'Dará de baja  ';
$vars_template['ARTICULO'] = 'el Registro de advertencia generica: ' ;
$vars_template['NOMBRE'] =  $advertencia->texto;


$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/advertenciasgenericas/index');
$template = (new \FMT\Template(VISTAS_PATH.'/widgets/confirmacion.html', $vars_template,['CLEAN'=>false]));
$vars_vista['CONTENT'] = "$template";
$vista->add_to_var('vars',$vars_vista);

return true;
