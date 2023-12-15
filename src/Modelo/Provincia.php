<?php namespace App\Modelo;

use App\Helper\Conexiones;
use FMT\Modelo;

class Provincia extends Modelo {
	/** @var  int */
	public $id = 0;
	/** @var  string */
	public $nombre = '';
	/** @var  string */
	public $abreviatura = '';
	/** @var  string */
	public $informacion = '';

	public static function listar_select() {
		$sql = "SELECT id, nombre FROM provincias";
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::SELECT, $sql);

		return $res;
	}

	public static function obtener($id = null) {
		$obj = new static();
		if (is_numeric($id)) {
			if ($id == 0) {
				return $obj;
			} else if ($id > 0) {
				$sql = "SELECT * FROM provincias WHERE id = :id";
				$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, [':id' => $id]);
				if (is_array($res) && isset($res[0])) {
					$res = $res[0];
					$obj->id = (int)(isset($res['id']) ? $res['id'] : 0);
					$obj->nombre = isset($res['nombre']) ? $res['nombre'] : null;
					$obj->abreviatura = isset($res['abreviatura']) ? $res['abreviatura'] : null;
					$obj->informacion = isset($res['informacion']) ? $res['informacion'] : null;

					return $obj;
				}
			}
		}

		return null;
	}

	public function validar() {
		// TODO: Implement validar() method.
	}

	public function alta() {
		// TODO: Implement alta() method.
	}

	public function baja() {
		// TODO: Implement baja() method.
	}

	public function modificacion() {
		// TODO: Implement modificacion() method.
	}
}