<?php

use App\Modelo\AppRoles;

$config	= FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Empleados';

$vars_template['UBICACION']				= \FMT\Helper\Template::select_block($ubicaciones);
$vars_template['DEPENDENCIA']				= \FMT\Helper\Template::select_block($dependencias);
$vars_template['CONTRATO']				= \FMT\Helper\Template::select_block($contratos);
$roles = json_encode([
	'admin_ciet' => App\Modelo\AppRoles::ADMINISTRADOR_CIET,
	'reg_acceso' => App\Modelo\AppRoles::REGISTRO_DE_ACCESO,
	'carg_datos'=> App\Modelo\AppRoles::CARGA_DE_DATOS,
	'auditor' => App\Modelo\AppRoles::AUDITOR,
	'admin_rrhh' => App\Modelo\AppRoles::ADMINISTRADOR_RRHH,
	'admin_convenios' => App\Modelo\AppRoles::ADMINISTRADOR_CONVENIOS,
	'rol_dis' => App\Modelo\AppRoles::ROL_DIS,
	'enrolador' => App\Modelo\AppRoles::ENROLADOR,
	'enrolador_dis' => App\Modelo\AppRoles::ENROLADOR_DIS,
	'rca' => App\Modelo\AppRoles::RCA,
	'empleado_ciet' => App\Modelo\AppRoles::EMPLEADO_CIET,
	'rol_default' => App\Modelo\AppRoles::ROL_DEFAULT,
	'rol_administracion' => App\Modelo\AppRoles::ROL_ADMINISTRACION

], JSON_UNESCAPED_UNICODE);
$vars_vista['JS_FOOTER']    = [
	['JS_SCRIPT' => \App\Helper\Vista::get_url() . '/js/empleados/empleados.js'],
];
$vars_vista['CSS_FILES'][]	= ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . '/datatables/1.10.12/datatables.min.css'];

$vars_vista['JS_FILES']     = [
	['JS_FILE' => $config['app']['endpoint_cdn'] . "/datatables/1.10.12/datatables.min.js"],
	['JS_FILE' => $config['app']['endpoint_cdn'] . "/datatables/defaults.js"],
];
$vars_template['BOTON_EXCEL'] = \App\Helper\Vista::get_url("index.php/empleados/exportar_excel_empleados");
$vars_template['LINK'] = \App\Helper\Vista::get_url('index.php/empleados/alta');

$content  = new \FMT\Template(TEMPLATE_PATH . '/empleados/index.html', $vars_template, ['CLEAN' => false]);

$vars_vista['CONTENT']      = "$content";

$base_url                   = \App\Helper\Vista::get_url();
$rol_actual = AppRoles::obtener_nombre_rol();

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$roles				= {$roles};
	var \$rol_actual				= "{$rol_actual}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars', $vars_vista);