<?php

$vars_vista['SUBTITULO']		= 'Modificar Reloj';
$vars_template['OPERACION']		= 'modificacion';

$vars_template['NUMERO_SERIE']	= !empty($reloj->numero_serie) ? $reloj->numero_serie : '';
$vars_template['ACCESO_TARJETA']	= !empty($reloj->acceso_tarjeta) ? 1 : 0;
$vars_template['ACCESO_RESTRINGIDO']	= !empty($reloj->acceso_restringido) ? 1 : 0;
$vars_template['MARCA']	= !empty($reloj->marca) ? ($reloj->marca) :'';
$vars_template['MODELO']	= !empty($reloj->modelo) ? ($reloj->modelo) :'';
$vars_template['IP']	= !empty($reloj->ip) ? ($reloj->ip) :'';
$vars_template['PUERTO']	= !empty($reloj->puerto) ? ($reloj->puerto) :'';
$vars_template['DNS']	= !empty($reloj->dns) ? ($reloj->dns) :'';
$vars_template['NODO']	= !empty($reloj->nodo) ? ($reloj->nodo) :'';
$vars_template['NODO']	= !empty($reloj->nodo) ? ($reloj->nodo) :'';
$vars_template['NOTAS']	= !empty($reloj->notas) ? ($reloj->notas) :'';
$vars_template['TIPOS_RELOJES'] = \FMT\Helper\Template::select_block($tipos_relojes, $reloj->tipo_id);
$vars_template['UBICACIONES'] = \FMT\Helper\Template::select_block($ubicaciones, $reloj->ubicacion_id);


$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/relojes/index');


$template = (new \FMT\Template(VISTAS_PATH.'/templates/relojes/modificacion.html', $vars_template,['CLEAN'=>false]));
$vars_vista['CONTENT'] = "$template";

$vars_vista['CSS_FILES'][]	= ['CSS_FILE' =>  \App\Helper\Vista::get_url().'/css/funkyradio.css'];
$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url().'/js/relojes/modificacion.js';
$vars_vista['JS_FOOTER'][]['JS_SCRIPT'] = \App\Helper\Vista::get_url('bootstrap-typeahead.min.js');

$base_url	= \App\Helper\Vista::get_url();

$vars_vista['JS'][]['JS_CODE'] = <<<JS
    var \$base_url = "{$base_url}";
    var \$acceso_tarjeta = "{$vars_template['ACCESO_TARJETA']}";
    var \$acceso_restringido = "{$vars_template['ACCESO_RESTRINGIDO']}";
JS;
$vista->add_to_var('vars',$vars_vista);

return true;
