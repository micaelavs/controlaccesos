<?php
$vars_template['TEXTO_AVISO'] = 'Dará de baja  ';
$vars_template['ARTICULO'] = 'el Registro';
$vars_vista['SUBTITULO'] = 'Personas';
$vars_template['CONTROL'] = 'personas:';

$vars_template['NOMBRE'] = "con"." borrado:". $personas->borrado;
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/personas/index');
$template = (new \FMT\Template(VISTAS_PATH.'/widgets/confirmacion.html', $vars_template,['CLEAN'=>false]));
$vars_vista['CONTENT'] = "$template";
$vista->add_to_var('vars',$vars_vista);

return true;
