<?php
$vars_template['TEXTO_AVISO'] = 'Dará de baja  ';
$vars_template['ARTICULO'] = 'el Registro';
$vars_vista['SUBTITULO'] = 'Empleado';
$vars_template['CONTROL'] = 'empleado:';

$vars_template['NOMBRE'] = $empleado->nombre.' '.$empleado->apellido;
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/empleados/index');
$template = (new \FMT\Template(VISTAS_PATH.'/widgets/confirmacion.html', $vars_template,['CLEAN'=>false]));
$vars_vista['CONTENT'] = "$template";
$vista->add_to_var('vars',$vars_vista);

return true;
