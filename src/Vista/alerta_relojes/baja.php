
<?php

$vars_vista['SUBTITULO'] = 'Baja de Alerta Reloj';
$vars_template['TEXTO_AVISO'] = 'Usted está por dar de baja la Alerta Reloj.';
$vars_template['ARTICULO'] = '<br>';
$vars_template['CONTROL'] = 'del Empleado: ';
$vars_template['NOMBRE'] = ($alertaReloj->empleado->id) ? $alertaReloj->empleado->nombre." ".$alertaReloj->empleado->apellido : 'S/D';

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/AlertaRelojes/index');

$template = (new \FMT\Template(VISTAS_PATH.'/templates/alertarelojes/baja.html', $vars_template,['CLEAN'=>false]));

$vars_vista['CONTENT'] = "$template";

$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;

$vista->add_to_var('vars',$vars_vista);
