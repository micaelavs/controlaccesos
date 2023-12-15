<?php

use App\Modelo\AppRoles;

$config	= FMT\Configuracion::instancia();
$vars_template = [];
$vars_template['URL_BASE']  = \App\Helper\Vista::get_url();
$vars_vista['SUBTITULO']    = 'Registro de Acceso Manual para Visitas';
$vars_template['OPERACION']  = 'alta';

$vars_vista['JS_FOOTER']    = [
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/mensajes.js'],
    ['JS_SCRIPT' => \App\Helper\Vista::get_url().'/js/registros/carga_individual_visita.js'],
];

$vars_vista['JS_FILES']     = [
    ['JS_FILE'    => $config['app']['endpoint_cdn']."/js/select2/js/select2.full.min.js"],
];

$vars_template['UBICACIONES'] = \FMT\Helper\Template::select_block($ubicaciones, $registro->ubicacion->id);
$vars_template['DOCUMENTO_VISITA'] = $registro->visita->persona->documento;
$vars_template['NOMBRE'] = $registro->visita->persona->nombre;
$vars_template['APELLIDO'] = $registro->visita->persona->apellido;
$vars_template['CREDENCIAL'] = $registro->credencial->codigo;
$vars_template['ORIGEN'] = $registro->origen;
$vars_template['DESTINO'] = $registro->destino;
$vars_template['OBSERVACIONES'] = $registro->observaciones;

$vars_template['DOCUMENTO_AUTORIZANTE'] = $registro->autorizante->documento;
$vars_template['NOMBRE_AUTORIZANTE'] = $registro->autorizante->nombre . " " .  $registro->autorizante->apellido;

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/Registros/index');


$content  = new \FMT\Template(TEMPLATE_PATH . '/registros/carga_individual_visita.html', $vars_template, ['CLEAN' => false]);

$vars_vista['CONTENT']      = "$content";

$base_url                   = \App\Helper\Vista::get_url();
$fecha = !empty($temp = $registro->fecha) ? $temp->format('d/m/Y') : null;
$ingreso = !empty($temp = new DateTime($registro->hora_ingreso)) ? $temp->format('H:i') : null;
$egreso = !empty($temp = new DateTime($registro->hora_egreso)) ? $temp->format('H:i') : null;

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$fecha				= "{$fecha}";
	var \$ingreso				= "{$ingreso}";
	var \$egreso				= "{$egreso}";
JS;

$vars_vista['CONTENT'] = "{$content}";

$vista->add_to_var('vars', $vars_vista);
