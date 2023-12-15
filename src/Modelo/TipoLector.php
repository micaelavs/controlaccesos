<?php namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Validador;
use FMT\Logger;
use FMT\Modelo;

class TipoLector extends Modelo {
	public $id;
	public $descripcion;

	public function validar() {
		$inputs = [
			'descripcion' => $this->descripcion,
		];
		$rules = [
			'descripcion' => ['required'],
		];
		$naming = [
			'descripcion' => "Descripción",
		];
		$validator = Validador::validate($inputs, $rules, $naming);
		if ($validator->isSuccess()) {
			return true;
		}
		$this->errores = $validator->getErrors();

		return false;
	}

	public function alta() {
			$sql = "INSERT INTO lectores_reloj
                      (descripcion) 
                    VALUE 
                      (:descripcion);";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::INSERT, $sql,
				[
					':descripcion' => $this->descripcion,
				]);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				$this->id = $res;
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'lectores_reloj';
				Logger::event('alta', $datos);
			}

		return $res;
	}

	public function baja() {
		$conn = new Conexiones();
		$sql = "UPDATE lectores_reloj SET borrado = 1 WHERE id = :id";
		$resultado = $conn->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
		if ($resultado !== false) {
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'lectores_reloj';
			Logger::event('baja', $datos);
		}

		return $resultado;
	}

	public function modificacion() {
			$sql = "UPDATE lectores_reloj SET descripcion = :descripcion WHERE id = :id;";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::INSERT, $sql,
				[
					':id' => $this->id,
					':descripcion' => $this->descripcion,
				]);
			if (is_numeric($res) && $res > 0) {
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'lectores_reloj';
				Logger::event('modificacion', $datos);
			}


		return $res;
	}

	public static function listar() {
		$sql = "SELECT id, descripcion FROM lectores_reloj WHERE borrado = 0;";
		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, []);
		if (is_array($res)) {
			$list = [];
			foreach ($res as $re) {
				$list[] = self::arrayToObject($re);
			}

			return $list;
		}

		return [];
	}

	public static function obtener($id) {
		$sql = "SELECT l.id, l.descripcion FROM lectores_reloj AS l 
                    WHERE l.id = :id AND l.borrado = 0;";
		$params = [':id' => $id];
		if (is_numeric($id)) {
			if ($id > 0) {
				$conex = new Conexiones();
				$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
				if (!empty($res) && is_array($res) && isset($res[0])) {
					return static::arrayToObject($res[0]);
				}
			} else {
				return static::arrayToObject();
			}
		}

		return null;
	}

	public static function arrayToObject($res = []) {
		$obj = new self();
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->descripcion = isset($res['descripcion']) ? $res['descripcion'] : null;

		return $obj;
	}

	public static function porTipoReloj(TipoReloj $tipoReloj) {
		$sql = "SELECT lr.id, lr.descripcion
				FROM lectore_x_tipo_reloj AS lxtr
					JOIN lectores_reloj AS lr
				WHERE 
					lxtr.tipo_reloj_id = :tipo_reloj_id;";
		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, [':tipo_reloj_id' => $tipoReloj->id]);
		if (is_array($res)) {
			$list = [];
			foreach ($res as $re) {
				$list[] = self::arrayToObject($re);
			}

			return $list;
		}

		return [];
	}
	
}