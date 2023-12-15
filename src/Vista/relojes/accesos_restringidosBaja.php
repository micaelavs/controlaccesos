
<?php

$vars_vista['SUBTITULO'] = 'Baja de Acceso Restringido';
$vars_template['TEXTO_AVISO'] = 'Usted está por dar de baja el Acceso restringido del nodo N° '.$acceso_restringido[0]->nodo;
$vars_template['ARTICULO'] = '<br>';
$vars_template['CONTROL'] = 'al usuario: ';
$vars_template['NOMBRE'] = ($acceso_restringido[1]->nombre) ? $acceso_restringido[1]->nombre.", DNI: ".$acceso_restringido[1]->documento : 'S/Nombre con DNI:'.$acceso_restringido[1]->documento;

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/relojes/accesos_restringidos/'.$acceso_restringido[0]->id);

$template = (new \FMT\Template(VISTAS_PATH.'/templates/relojes/accesos_restringidosBaja.html', $vars_template,['CLEAN'=>false]));

$vars_vista['CONTENT'] = "$template";

$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;

$vista->add_to_var('vars',$vars_vista);
