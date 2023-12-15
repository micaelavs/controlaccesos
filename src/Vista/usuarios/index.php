<?php
	namespace App\Vista;
	$config	= \FMT\Configuracion::instancia();

	$vars_vista['SUBTITULO'] = 'Gestion de Usuarios del Sistema.';
	$vars['TITULOS'] = [
			['TITULO' => 'Nombre y apellido'],
			['TITULO' => 'Usuario'],
			['TITULO' => 'Correo'],
			['TITULO' => 'Rol'],
			['TITULO' => 'Dependencias'],
			['TITULO' => 'Acciones']
		];

	$usuarios_permitidos = \App\Modelo\AppRoles::obtener_lista_roles_permitidos();

	foreach ($usuarios as $usuario) {

		$modifica ='<a href="'.\App\Helper\Vista::get_url("index.php/usuarios/modificar/{$usuario->idUsuario}").'" data-toggle="tooltip" data-placement="top" data-id="'.$usuario->idUsuario.'" title="Ver/Modificar" class="dis" data-toggle="modal"><i class="fa fa-eye"></i><i class="fa fa-pencil"></i></a>';
		
		$elimina = 	'<a href="'.\App\Helper\Vista::get_url("index.php/usuarios/baja/{$usuario->idUsuario}").'" class="borrar" data-user="'.$usuario->nombre.'" data-toggle="tooltip" data-placement="top" title="Eliminar" target="_self"><i class="fa fa-trash"></i></a>';
		
		$no_aplica = '';
			
			$vars['ROW'][] = ['COL' => [
				['CONT' => $usuario->nombre.' '.$usuario->apellido],
				['CONT' => $usuario->user],
				['CONT' => $usuario->email],
				['CONT' => $usuario->rol_nombre],
				['CONT' => $usuario->dependencias],
				['CONT' =>  (in_array($usuario->rol_nombre, $usuarios_permitidos)) ? '<span class="acciones">'.$modifica . $elimina.'</span> ' : '<span class="acciones">'.$no_aplica.'</span>']
				]
			];
		}
	$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'].'/datatables/1.10.12/datatables.min.css'];
	$vars_vista['JS_FILES'][]	= ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn']."/datatables/1.10.12/datatables.min.js"];
	$vars_vista['JS_FILES'][]	= ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn']."/datatables/defaults.js"];
	$vars_vista['JS_FOOTER'][]    = ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/usuarios/index.js'];
	$html = (new \FMT\Template(VISTAS_PATH.'/templates/tabla.html',$vars));
	$vars['CLASS_COL'] = 'col-md-12';
	$vars['BOTON_ACCION'][] = ['HTTP'=> \App\Helper\Vista::get_url('index.php'),'ACCION' => 'alta','CONTROL' => 'usuarios','CLASS'=>'btn-primary','NOMBRE' => 'NUEVO'];
	$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
JS;
	$html2 = (new \FMT\Template(VISTAS_PATH.'/widgets/botonera.html',$vars));

	$vars_vista['CONTENT'] = "{$html}{$html2}";
	$vista->add_to_var('vars',$vars_vista);
	return true;
