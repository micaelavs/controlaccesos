<?php



$vars_vista['SUBTITULO']                = 'Registrar Pertenencia';
$vars_template['OPERACION']                = 'alta';

$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/pertenencias/alta.js');

$vars_vista['CSS_FILES'] = [ 
    ['CSS_FILE'   => $vista->getSystemConfig()['app']['endpoint_cdn']."/js/select2/css/select2.min.css"],
];

$vars_vista['JS_FILES']     = [
    ['JS_FILE'    => $vista->getSystemConfig()['app']['endpoint_cdn']."/js/select2/js/select2.full.min.js"],
];

$vars_template['LINK_AJAX_BUSCAR_PERSONAL']= \App\Helper\Vista::get_url('index.php/pertenencias/buscar_documento');
$vars_template['LINK_AJAX_BUSCAR_SOLICITANTE']= \App\Helper\Vista::get_url('index.php/pertenencias/buscar_documento_solicitante');
$vars_template['TEXTO']  = $pertenencia->texto;
$vars_template['UBICACION'] = \FMT\Helper\Template::select_block($ubicaciones, $pertenencia->ubicacion_id);
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/pertenencias/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/pertenencias/alta.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";

$base_url                   = \App\Helper\Vista::get_url();
$persona_documento = !empty($pertenencia->persona) ? $pertenencia->persona->documento : '';
$solicitante_documento = !empty($pertenencia->solicitante) ? $pertenencia->solicitante->documento : '';

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          = '{$config['app']['endpoint_cdn']}';
	var \$base_url				= "{$base_url}";
	var \$persona_documento		= "{$persona_documento}";
	var \$solicitante_documento	= "{$solicitante_documento}";
JS;
$vista->add_to_var('vars', $vars_vista);

return true;
