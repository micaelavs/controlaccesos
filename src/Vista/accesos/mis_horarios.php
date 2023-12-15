<?php

$config	= FMT\Configuracion::instancia();
$vars_template = [];

$tipo_registros = json_encode([
	'online' => App\Modelo\Acceso::TIPO_REGISTRO_ONLINE,
	'offline' => App\Modelo\Acceso::TIPO_REGISTRO_OFFLINE,
	'registro_reloj'	=> App\Modelo\Acceso::TIPO_REGISTRO_RELOJ,
	'comision_horaria' 	=> App\Modelo\Acceso::TIPO_COMISION_HORARIA,	
	'biohacienda' 	=> App\Modelo\Acceso::TIPO_REGISTRO_RELOJ_BIOHACIENDA,		
], JSON_UNESCAPED_UNICODE);

$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Mis horarios';

$vars_vista['JS_FOOTER']    = [
	['JS_SCRIPT' => \App\Helper\Vista::get_url() . '/js/accesos/mis_horarios.js'],
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/mensajes.js'],
];
$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => \App\Helper\Vista::get_url() . '/css/accesos/horarios.css'];
$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . '/datatables/1.10.12/datatables.min.css'];
$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . '/bootstrap/datepicker/4.17.37/css/bootstrap-datetimepicker.min.css'];

$vars_vista['JS_FILES']     = [
	['JS_FILE' => $config['app']['endpoint_cdn'] . "/datatables/1.10.12/datatables.min.js"],
	['JS_FILE' => $config['app']['endpoint_cdn'] . "/datatables/defaults.js"],
    ['JS_FILE' => $config['app']['endpoint_cdn'] . "/bootstrap/datepicker/4.17.37/js/bootstrap-datetimepicker.min.js"],
];
$vars_template['UBICACIONES'] = \FMT\Helper\Template::select_block($ubicaciones);
if ($notificacion){
	$content  = new \FMT\Template(TEMPLATE_PATH . '/accesos/acceso_notificacion.html', $vars_template, ['CLEAN' => false]);
}elseif ($duplicado){
	$content  = new \FMT\Template(TEMPLATE_PATH . '/accesos/acceso_notificacion_duplicado.html', $vars_template, ['CLEAN' => false]);
}else{
	$horarios = json_decode($horarios);
	
	$vars_template['DOMINGO']  = !empty($horarios[0][0]) ? $horarios[0][0].' a '.$horarios[0][1]:'-';
	$vars_template['LUNES']    = !empty($horarios[1][0]) ? $horarios[1][0].' a '.$horarios[1][1]:'-';
	$vars_template['MARTES']   = !empty($horarios[2][0]) ? $horarios[2][0].' a '.$horarios[2][1]:'-';
	$vars_template['MIERCOLES']= !empty($horarios[3][0]) ? $horarios[3][0].' a '.$horarios[3][1]:'-';
	$vars_template['JUEVES']   = !empty($horarios[4][0]) ? $horarios[4][0].' a '.$horarios[4][1]:'-';
	$vars_template['VIERNES']  = !empty($horarios[5][0]) ? $horarios[5][0].' a '.$horarios[5][1]:'-';
	$vars_template['SABADO']   = !empty($horarios[6][0]) ? $horarios[6][0].' a '.$horarios[6][1]:'-';

	
	$base_url                   = \App\Helper\Vista::get_url();
	$endpoint_cdn = $config['app']['endpoint_cdn'];

$vars_vista['JS'][]['JS_CODE']    = <<<JS
var \$endpoint_cdn    = "{$endpoint_cdn}";
var \$base_url        = "{$base_url}";
var \$tipo_registros  = {$tipo_registros};
var \$no_relacionado = "{$no_relacionado}";
JS;
$content  = new \FMT\Template(TEMPLATE_PATH . '/accesos/mis_horarios.html', $vars_template, ['CLEAN' => false]);
}

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars', $vars_vista);
