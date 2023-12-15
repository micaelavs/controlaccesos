<?php

	$config	= \FMT\Configuracion::instancia();
	$vars['CSS_FILES'][]['CSS_FILE'] = $config['app']['endpoint_cdn']."/bootstrap/css/bootstrap.min.css";
	$vars['CSS_FILES'][]['CSS_FILE'] = $config['app']['endpoint_cdn']."/bootstrap/datepicker/4.17.37/css/bootstrap-datetimepicker.min.css";
	$vars['CSS_FILES'][]['CSS_FILE'] = $config['app']['endpoint_cdn']."/poncho-v01/css/droid-serif.css";
	$vars['CSS_FILES'][]['CSS_FILE'] = $config['app']['endpoint_cdn']."/poncho-v01/css/font-awesome.min.css";
	$vars['CSS_FILES'][]['CSS_FILE'] = $config['app']['endpoint_cdn']."/poncho-v01/css/roboto-fontface.css";
	$vars['CSS_FILES'][]['CSS_FILE'] = $config['app']['endpoint_cdn']."/poncho-v01/css/poncho.min.css";
	$vars['CSS_FILES'][]['CSS_FILE'] = $config['app']['endpoint_cdn']."/estiloIS/4/estilois.css";
	$vars['CSS_FILES'][]['CSS_FILE'] = \App\Helper\Vista::get_url('estilos.css');
	$vars['CSS_FILES'][]['CSS_FILE'] = $config['app']['endpoint_cdn']."/js/select2/css/select2.min.css";

	$vars['JS_FILES'][]['JS_FILE'] = $config['app']['endpoint_cdn']."/js/jquery.js";
	$vars['JS_FILES'][]['JS_FILE'] = $config['app']['endpoint_cdn']."/bootstrap/js/bootstrap.min.js";
	$vars['JS_FILES'][]['JS_FILE'] = $config['app']['endpoint_cdn']."/momentjs/2.14.1/moment.min.js";
	$vars['JS_FILES'][]['JS_FILE'] = $config['app']['endpoint_cdn']."/momentjs/2.14.1/es.js";
	$vars['JS_FILES'][]['JS_FILE'] = $config['app']['endpoint_cdn']."/bootstrap/datepicker/4.17.37/js/bootstrap-datetimepicker.min.js";
	$vars['JS_FILES'][]['JS_FILE'] = $config['app']['endpoint_cdn']."/js/select2/js/select2.full.min.js";
	$vars['JS_FILES'][]['JS_FILE'] = $config['app']['endpoint_cdn']."/js/inputmask/3.3.3/min/jquery.inputmask.bundle.min.js";
	$vars['JS_FILES'][]['JS_FILE'] = $config['app']['endpoint_cdn']."/js/inputmask/is.js";
	$vars['TITLE'] = $config['app']['title'];
	$vars['TITULO'] = $config['app']['titulo_pantalla'];
	
	$vars['PIE'] = new \FMT\Template(VISTAS_PATH.'/widgets/footer.php','');
	$vars['NOMBRE_USUARIO'] = "{$user->nombre} {$user->apellido}";
	$vars['ROL_USUARIO'] = "{$user->rol_nombre}";
	
	$vista->add_to_var('vars', $vars);
	
	return true;