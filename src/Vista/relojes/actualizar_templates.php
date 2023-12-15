
<?php

$vars_vista['SUBTITULO'] = 'Actualizar Huellas';
$vars_template['TEXTO_AVISO'] = 'Usted está por actualizar <b>todas</b> las huellas de <b>todos</b> los empleados para el Reloj. <br /><br /> ESTA ACCION DEJARA EL RELOJ EN ESTADO <b>SINCRONIZANDO</b> DURANTE 30 MINUTOS O 1HS <br />¿ESTA REALMENTE SEGURO?<br /> ';
$vars_template['ARTICULO'] = '<br>';
$vars_template['CONTROL'] = 'Nro Serie: ';
$vars_template['NOMBRE'] = ($reloj->numero_serie) ? $reloj->numero_serie : 'S/Nro Serie';

$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/Relojes/index');

$template = (new \FMT\Template(VISTAS_PATH.'/templates/relojes/actualizar_templates.html', $vars_template,['CLEAN'=>false]));

$vars_vista['CONTENT'] = "$template";

$vars_vista['JS'][]['JS_CODE'] = <<<JS
JS;

$vista->add_to_var('vars',$vars_vista);
