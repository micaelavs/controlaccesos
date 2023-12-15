<?php
namespace App\Controlador;

use App\Helper\Vista;

class Error extends Base {
	protected $accion;

	public function accion_index() {
	 	$vista = new Vista(VISTAS_PATH.'/widgets/error_no_encontrado.php','');
	 	$this->vista->add_to_var('vars',['CONTENT' => "$vista"]);
 	}
}