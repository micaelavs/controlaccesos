<?php
	namespace App\Vista;
	$vars_vista['SUBTITULO'] = 'Baja de Usuarios del Sistema.';
	$vars['CONTROL'] = '';
	$vars['ARTICULO'] = '';
	$vars['TEXTO_AVISO'] = 'Esta por finalizar el contrato del empleado  ';			
	$vars['NOMBRE'] = $data_empleado->nombre.' '.$data_empleado->apellido;
	$vars['CANCELAR'] = \App\Helper\Vista::get_url('index.php/empleados/index');

	$template = (new \FMT\Template(VISTAS_PATH.'/widgets/confirmacion.html', $vars));
	$vars_vista['CONTENT'] = "$template";
	$vista->add_to_var('vars',$vars_vista);

	return true;