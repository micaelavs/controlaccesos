<?php namespace App\Modelo;

use App\Helper\Conexiones;
use FMT\Modelo;

class Localidad extends Modelo {
	public $id;
	public $nombre;
	public $informacion;
	public $provincia;

	/**
	 * @param $provincia_id
	 * @return array|int|string
	 */
	public static function listar_select($provincia_id = null) {
		$params = [];
		$sql = "SELECT id, nombre, provincia_id FROM localidades";
		if ($provincia_id) {
			$sql .= " WHERE provincia_id = :provincia_id";
			$params = [':provincia_id' => $provincia_id];
		}
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::SELECT, $sql, $params);

		return $res;
	}

	public static function obtener($id = null) {
		$obj = new static();
		if (is_numeric($id)) {
			if ($id == 0) {
				$obj->id = 0;
				$obj->nombre = "Otra";
				return $obj;
			} else if ($id > 0) {
				$sql = "SELECT * FROM localidades WHERE id = :id";
				$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, [':id' => $id]);
				if (is_array($res) && isset($res[0])) {
					$res = $res[0];
					$obj->id = (int)(isset($res['id']) ? $res['id'] : 0);
					$obj->nombre = isset($res['nombre']) ? $res['nombre'] : null;
					$obj->informacion = isset($res['informacion']) ? $res['informacion'] : null;
					$obj->provincia = Provincia::obtener((int)(isset($res['provincia_id']) ? $res['provincia_id'] : 0));

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