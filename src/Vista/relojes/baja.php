
<?php

$vars_vista['SUBTITULO'] = 'Baja de Reloj';
$vars_template['TEXTO_AVISO'] = 'Usted está por dar de baja el Reloj.';
$vars_template['ARTICULO'] = '<br>';
$vars_template['CONTROL'] = 'Nro Serie: ';
$vars_template['NOMBRE'] = ($reloj->numero_serie) ? $reloj->numero_serie : 'S/Nro Serie';

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/relojes/index');

$template = (new \FMT\Template(VISTAS_PATH.'/templates/relojes/baja.html', $vars_template,['CLEAN'=>false]));

$vars_vista['CONTENT'] = "$template";

$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;

$vista->add_to_var('vars',$vars_vista);
