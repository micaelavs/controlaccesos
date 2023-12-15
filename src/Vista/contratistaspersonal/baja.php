<?php
$vars_template['TEXTO_AVISO'] = 'Dará de baja  ';
$vars_template['ARTICULO'] = 'el registro Personal Contratista: ';
//$vars_vista['SUBTITULO'] = 'Personas';
//$vars_template['CONTROL'] = 'personal:';

$vars_template['NOMBRE'] =  $contratistasPersonal->persona->nombre . ' ' . $contratistasPersonal->persona->apellido;
$vars_template['TEXTO_EXTRA'] = "<br> de la Empresa contratista : <strong>".$contratistasPersonal->contratista->nombre . "</strong>";
$vars_template['CANCELAR'] = \App\Helper\Vista::get_url('index.php/contratistaspersonal/index/'.$contratistasPersonal->contratista->id);

$template = (new \FMT\Template(VISTAS_PATH.'/widgets/confirmacion.html', $vars_template,['CLEAN'=>false]));
$vars_vista['CONTENT'] = "$template";
$vista->add_to_var('vars',$vars_vista);

return true;
