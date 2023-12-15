<?php
	$template = new \FMT\Template(TEMPLATE_PATH.'/acceso_denegado.html',['ERROR_NO_AUTORIZADO'=>\App\Helper\Vista::get_url().'/img/error_no_autorizado.png']);
	$vars = ['CONTENT'=>"$template"];
	$vista = new \App\Helper\Vista(VISTAS_PATH.'/base.php',['vars'=>$vars]);
	return $vista;