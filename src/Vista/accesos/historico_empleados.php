<?php

use \FMT\Template;
use \App\Helper\Vista;
$rol = App\Modelo\AppRoles::obtener_rol();
$config = FMT\Configuracion::instancia();

$tipo_registros = json_encode([
	'online' => App\Modelo\Acceso::TIPO_REGISTRO_ONLINE,
	'ofline' => App\Modelo\Acceso::TIPO_REGISTRO_OFFLINE,
	'registro_reloj'	=> App\Modelo\Acceso::TIPO_REGISTRO_RELOJ,
	'comision_horaria' 	=> App\Modelo\Acceso::TIPO_COMISION_HORARIA,
	'biohacienda' 	=> App\Modelo\Acceso::TIPO_REGISTRO_RELOJ_BIOHACIENDA,
], JSON_UNESCAPED_UNICODE);

$vars_template['URL_BASE'] = Vista::get_url();
$vars_vista['SUBTITULO'] = 'Histórico de Empleados.';
$vars_template['DEPENDENCIAS'] = \FMT\Helper\Template::select_block($listado_direcciones_aux);
$vars_template['UBICACIONES'] = \FMT\Helper\Template::select_block($listado_ubicaciones_aux);

$vars_template['BOTON_EXCEL'] = \App\Helper\Vista::get_url("index.php/accesos/exportar_excel_historico_empleados");

$reporte = new Template(TEMPLATE_PATH . '/accesos/historico_empleados.html', $vars_template, ['CLEAN' => false]);

$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('script.js');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('/accesos/historico_empleados.js');
$vars_vista['CSS_FILES'][]['CSS_FILE']	= Vista::get_url('/accesos/historico_empleados.css');
$vars_vista['CSS_FILES'][]  = ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'].'/datatables/1.10.12/datatables.min.css'];
$vars_vista['JS_FILES'][]   = ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn']."/datatables/1.10.12/datatables.min.js"];
$vars_vista['JS_FILES'][]   = ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn']."/datatables/defaults.js"];
$endpoint_cdn = $config['app']['endpoint_cdn'];
$base_url = \App\Helper\Vista::get_url();

$vars_vista['JS'][]['JS_CODE']    = <<<JS
var \$endpoint_cdn    = "{$endpoint_cdn}";
var \$base_url        = "{$base_url}";
var \$tipo_registros  = {$tipo_registros};
JS;

$vars_vista['CONTENT'] = "{$reporte}";
$vista->add_to_var('vars', $vars_vista);
