<?php
	namespace App\Vista;

	$vars_vista['SUBTITULO']	= "$operacion Usuarios";
	$vars_vista['CSS_FILES'][]    = ['CSS_FILE'   => $vista->getSystemConfig()['app']['endpoint_cdn']."/js/select2/css/select2.min.css"];
    $vars_vista['JS_FILES'][]     = ['JS_FILE'    => $vista->getSystemConfig()['app']['endpoint_cdn']."/js/select2/js/select2.full.min.js"];
    $vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('/usuarios/usuario.js');
	$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/usuarios/empleado_buscar.js');

	$vars['LINK_AJAX_BUSCAR_EMPLEADO']= \App\Helper\Vista::get_url('index.php/usuarios/buscarAutorizanteAjax');

    $vars['ROLES'] = \FMT\Helper\Template::select_block($roles, $usuario->rol_id);
	$usuario->ubicacion = null;
    $vars['USER']  = $usuario;
	$vars['EMPLEADO_FULLNAME']  = $empleado_usuario_fullname;
	$vars['EMPLEADO_DNI']  = $empleado_usuario_dni;
	$vars['UBICACION']  = $ubicacion_nombre;
	$vars['DEPENDENCIAS'] = \FMT\Helper\Template::select_block($dependencias, $usuario->dependencias);
    $vars['CANCELAR'] = \App\Helper\Vista::get_url('index.php/usuarios/index');
	$template = new \FMT\Template(VISTAS_PATH.'/templates/usuarios/alta.html', $vars, ['CLEAN'=>true]);
	$vars_vista['CONTENT'] = "$template";
	$vista->add_to_var('vars',$vars_vista);

	return true;
?>