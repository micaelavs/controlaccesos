<?php

use \FMT\Template;
use \App\Helper\Vista;

$vars_template['URL_BASE'] = Vista::get_url();
$vars_vista['SUBTITULO'] = 'Control de accesos';

$vars_template['PERSONA_DOCUMENTO'] = isset($acceso->persona) ? $acceso->persona->documento : '';
$vars_template['PERSONA_AUTORIZANTE'] = isset($acceso->autorizante) ? $acceso->autorizante->documento : '';
$vars_template['CREDENCIAL'] = isset($acceso->credencial) ? $acceso->credencial->codigo : '';
$vars_template['ORIGEN'] = isset($acceso->origen) ? $acceso->origen : '';
$vars_template['DESTINO'] = isset($acceso->origen) ? $acceso->origen : '';
$vars_template['OBSERVACIONES'] = isset($acceso->observaciones) ? $acceso->observaciones : '';

$vars_template['UBICACION']				= $_SESSION['id_ubicacion_actual'];
$vars['FORM_ALTA']				= \App\Helper\Vista::get_url('index.php/accesos/alta');
$vars_template['FORMULARIO'] = new Template(TEMPLATE_PATH . '/accesos/formulario.html', $vars,['CLEAN' => false]);
$vars_template['CAMBIAR_UBICACION']	 = \App\Helper\Vista::get_url('index.php/accesos/cambiar_ubicacion');
$vars_template['FORM_ACTION'] = \App\Helper\Vista::get_url("index.php/accesos/editar_observaciones");
$tipo_acceso = json_encode([
	'empleado' => App\Modelo\Acceso::EMPLEADO,
	'visita' => App\Modelo\Acceso::VISITANTE,
	'contratista'		=> App\Modelo\Acceso::CONTRATISTA,
	'visita_enrolada' => App\Modelo\Acceso::VISITA_ENROLADA
], JSON_UNESCAPED_UNICODE);

if(isset($_SESSION['id_ubicacion_actual'])){
    $vars_vista['UBICACION_USUARIO']= '<li class="breadcrumb-item">
    <span class=" text-primary"><i
          class="fa fa-building fa-fw"></i>'.$ubicacion->nombre.'</span>
    </li>';
};
$reporte = new Template(TEMPLATE_PATH . '/accesos/index.html', $vars_template, ['CLEAN' => false]);

$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('script.js');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('accesos/registro_visitas.js');
$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('bootstrap-typeahead.min.js');
$vars_vista['CSS_FILES'][]  = ['CSS_FILE' => \App\Helper\Vista::get_url('estilos.css')];
$vars_vista['CSS_FILES'][]  = ['CSS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . '/datatables/1.10.12/datatables.min.css'];
$vars_vista['JS_FILES'][]   = ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . "/datatables/1.10.12/datatables.min.js"];
//$vars_vista['JS_FILES'][]   = ['JS_FILE' => $vista->getSystemConfig()['app']['endpoint_cdn'] . "/datatables/defaults.js"];
$config = FMT\Configuracion::instancia();
$endpoint_cdn = $config['app']['endpoint_cdn'];
$base_url = \App\Helper\Vista::get_url();

$vars_vista['JS'][]['JS_CODE']    = <<<JS
var \$endpoint_cdn    = "{$endpoint_cdn}";
var \$base_url        = "{$base_url}";
var \$tipo_acceso        = {$tipo_acceso};
JS;

$vars_vista['CONTENT'] = "{$reporte}";
$vista->add_to_var('vars', $vars_vista);
