<?php

use FMT\Configuracion;
$config = Configuracion::instancia();

$vars_vista['SUBTITULO']                = 'Horarios de Empleado';
$vars_template['OPERACION']                = 'guardar';




$vars_vista['JS_FOOTER'][]['JS_SCRIPT']   = \App\Helper\Vista::get_url('/empleados/empleado_horarios.js');
$vars_vista['CSS_FILES'][]    = ['CSS_FILE' => \App\Helper\Vista::get_url() . '/css/empleados/horarios.css'];

$vars_template['PLANTILLAS'] = \FMT\Helper\Template::select_block($plantillas,null);


$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/empleados/index');
$template = (new \FMT\Template(VISTAS_PATH . '/templates/empleados/horarios.html', $vars_template, ['CLEAN' => false]));
$vars_vista['CONTENT'] = "$template";


$base_url                   = \App\Helper\Vista::get_url();
$horarios = $empleado->horarios;

$vars_vista['JS'][]['JS_CODE']			= <<<JS
	var \$endpoint_cdn          	= '{$config['app']['endpoint_cdn']}';
	var \$base_url					= "{$base_url}";
	var \$horarios	= '{$horarios}';
JS;
$vista->add_to_var('vars', $vars_vista);

return true;