<?php
	namespace App\Vista;
	$vars_vista['SUBTITULO'] = 'Baja de Usuarios del Sistema.';
	$vars['CONTROL'] = 'usuario';
	$vars['ARTICULO'] = 'el';
	$vars['TEXTO_AVISO'] = 'Dará de baja ';			
	$vars['NOMBRE'] = $usuario->nombre.' '.$usuario->apellido;
	$vars['CANCELAR'] = \App\Helper\Vista::get_url('index.php/usuarios/index');

	$template = (new \FMT\Template(VISTAS_PATH.'/widgets/confirmacion.html', $vars));
	$vars_vista['CONTENT'] = "$template";
	$vista->add_to_var('vars',$vars_vista);

	return true;