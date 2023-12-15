<?php
/* Este archivo será obligatorio para colocar los mensajes de validación en español, con formato adecuado para la aplicación. */
return [
	'alpha'          => 'El campo <strong>:attribute</strong> debe contener solo letras.',
	'alpha_numeric'  => 'El campo <strong>:attribute</strong> debe contener solo letras y número.',
	'antesDe'        => 'El campo <strong>:attribute</strong> debe ser anterior a <i>:params(0)</i>.',
	'cuit'           => 'El <strong>CUIT</strong> no es válido',
	'despuesDe'      => 'El campo <strong>:attribute</strong> debe ser posterior a <i>:params(0)</i>.',
	'documento'      => 'El campo <strong>:attribute</strong> no es válido.',
	'email'          => 'El campo <strong>:attribute</strong> debe ser un Correo Electrónico válido.',
	'equals'         => 'El campo <strong>:attribute</strong> debe ser igual a <i>:params(0)</i>.',
	'exact_length'   => 'El campo <strong>:attribute</strong> exactamente <i>:params(0)</i> caracteres.',
	'existe'         => 'El <strong>:attribute</strong> no Existe en los registros',
	'fecha'          => 'El campo <strong>:attribute</strong> debe ser una fecha válida.',
	'float'          => 'El campo <strong>:attribute</strong> debe ser número decimal válido.',
	'integer'        => 'El campo <strong>:attribute</strong> debe ser número entero válido.',
	'ip'             => 'El campo <strong>:attribute</strong> debe tener un formato de IP válido.',
	'max_length'     => 'El campo <strong>:attribute</strong> debe tener máximo <i>:params(0)</i> caracteres.',
	'min_length'     => 'El campo <strong>:attribute</strong> debe tener mínimo <i>:params(0)</i> caracteres.',
	'no_contratista' => "El documento pertenece a un empleado <strong>Contratista</strong>.",
	'no_empleado'    => 'El documento pertenece a un empleado <strong>AGENTE</strong> interno.',
	'numeric'        => 'El campo <strong>:attribute</strong> debe ser numérico.',
	'required'       => 'El campo <strong>:attribute</strong> es obligatorio.',
	'texto'          => 'El campo <strong>:attribute</strong> no contiene datos válidos',
	'unico'          => 'El <strong>:attribute</strong> pertenece a otro registro. Este campo debe ser único.',
	'url'            => 'El campo <strong>:attribute</strong> debe tener un formato de dirección web válido.',
	'char'			 => 'El campo <strong>:attribute</strong> debe contener solo letras.',
	'mayorA'		 => 'El campo <strong>:attribute</strong> no puede ser menor a <i>:params(0)</i>.',
	'valida_dias'	 => 'El <strong>:attribute</strong> no respeta los días de la semana.',
	'valida_horas'   => 'El <strong>:attribute</strong> <strong>Desde</strong> debe ser menor al Horario  <strong>Hasta</strong>.',
	'boolean'		=> 	' ',
	'isJson'		=>	'El campo <strong>:attribute</strong> no cumple con el formato adecuado.',
	'correpondencia' =>  'El campo CUIT contiene un DNI distinto al cargado.',
	'empleado_x_usuario'       => 'El <strong>:attribute</strong> no tiene un empleado asignado.'
];