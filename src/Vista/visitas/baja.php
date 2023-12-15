
<?php

$vars_vista['SUBTITULO'] = 'Baja de Visita';
$vars_template['TEXTO_AVISO'] = 'Dará de baja a la visita';
$vars_template['ARTICULO'] = '<br>';
$vars_template['CONTROL'] = '';
$vars_template['NOMBRE'] =  $visita->persona->nombre." ".$visita->persona->apellido;

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/Visitas/index');

$template = (new \FMT\Template(VISTAS_PATH.'/templates/visitas/baja.html', $vars_template,['CLEAN'=>false]));

$vars_vista['CONTENT'] = "$template";

$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;

$vista->add_to_var('vars',$vars_vista);
