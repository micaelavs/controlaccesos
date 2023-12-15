<?php

use \App\Modelo\AppRoles;

$menu		= new \FMT\Menu();
$config		= FMT\Configuracion::instancia();
if ($config['app']['dev']) {
	$menu->activar_dev();
}


$opcion1 = $menu->agregar_opcion('<i class="fa fa-folder-open-o mr-2"></i>Mis consultas');
if (AppRoles::puede('Accesos', 'mis_horarios')) {
	$opcion1->agregar_link('Mis horarios', \App\Helper\Vista::get_url('index.php') . '/accesos/mis_horarios', \FMT\Opcion::COLUMNA1);
}

$opcion1 = $menu->agregar_opcion('<i class="fa fa-folder-open-o mr-2"></i>Reportes');
if (AppRoles::puede('Accesos', 'historico_empleados')) {
	$opcion1->agregar_link('Historico Empleados', \App\Helper\Vista::get_url('index.php/Accesos/historico_empleados'));
}

if (AppRoles::puede('Accesos', 'historico_visitas_contratistas')) {
	$opcion1->agregar_link('Contratistas y Visitas', \App\Helper\Vista::get_url('index.php/Accesos/historico_visitas_contratistas'));
}

if (AppRoles::puede('Accesos', 'horas_trabajadas')) {
	$opcion1->agregar_link('Horas Trabajadas', \App\Helper\Vista::get_url('index.php/Accesos/horas_trabajadas'));
}

if (AppRoles::puede('Accesos', 'informe_mensual')) {
	$opcion1->agregar_link('Informe Mensual', \App\Helper\Vista::get_url('index.php/Accesos/informe_mensual'));
}

if (AppRoles::puede('Accesos', 'planilla_reloj')) {
	$opcion1->agregar_link('Planilla única reloj', \App\Helper\Vista::get_url('index.php/Accesos/planilla_reloj'));
}

if(AppRoles::puede('Registros', 'index')){
	$opcion1 = $menu->agregar_opcion('Registro');
	$opcion1->agregar_link('Accesos Manuales', \App\Helper\Vista::get_url('index.php') . '/Registros/index', \FMT\Opcion::COLUMNA1);
}

$opcion1 = $menu->agregar_opcion('<i class="fa fa-folder-open-o mr-2"></i>Altas');
if (AppRoles::puede('Usuarios', 'index')) {
	$opcion1->agregar_link('Usuarios', \App\Helper\Vista::get_url('index.php') . '/usuarios/index', \FMT\Opcion::COLUMNA1);
}
if (AppRoles::puede('Personas', 'index')) {
	$opcion1->agregar_link('Personas', \App\Helper\Vista::get_url('index.php/personas/index'));
}
if (AppRoles::puede('Empleados', 'index')) {
	$opcion1->agregar_link('Empleados', \App\Helper\Vista::get_url('index.php/empleados/index'));
}
if (AppRoles::puede('Contratistas', 'index')) {
	$opcion1->agregar_link('Contratistas', \App\Helper\Vista::get_url('index.php/contratistas/index'));
}

if (AppRoles::puede('Advertencias', 'index')) {
	$opcion1->agregar_link('Advertencias', \App\Helper\Vista::get_url('index.php/advertencias/index'));
}
if (AppRoles::puede('Pertenencias', 'index')) {
	$opcion1->agregar_link('Pertenencias', \App\Helper\Vista::get_url('index.php/pertenencias/index'));
}
if (AppRoles::puede('Relojes', 'index')) {
	$opcion1->agregar_link('Relojes', \App\Helper\Vista::get_url('index.php/relojes/index'));
}
if (AppRoles::puede('AlertaRelojes', 'index')) {
	$opcion1->agregar_link('Alerta Reloj', \App\Helper\Vista::get_url('index.php/AlertaRelojes/index'));
}
if (AppRoles::puede('Visitas', 'index')) {
	$opcion1->agregar_link('Visitas Enroladas', \App\Helper\Vista::get_url('index.php/Visitas/index'));
}
if (AppRoles::puede('Accesosbio', 'index')) {
	$opcion1->agregar_link('Accesos BIO Hacienda', \App\Helper\Vista::get_url('index.php/accesosbio/index'));
}
if (AppRoles::puede('Tarjetas', 'index')) {
	$opcion1->agregar_link('Tarjetas', \App\Helper\Vista::get_url('index.php/Tarjetas/index'));
}
if (AppRoles::puede('Novedades', 'index')) {
	$opcion1->agregar_link('Novedades', \App\Helper\Vista::get_url('index.php/Novedades/index'));
}
//----------------------------------------/
//----------------------------------------/

if (AppRoles::puede('Manuales', 'index')) {
	$menu->agregar_manual(\App\Helper\Vista::get_url('index.php/Manuales/index'));
}
$menu->agregar_salir($config['app']['endpoint_panel'] . '/logout.php');
$vars['CABECERA'] = "{$menu}";
$vista->add_to_var('vars', $vars);
return true;