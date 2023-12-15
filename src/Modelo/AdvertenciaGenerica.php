<?php namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Validador;
use FMT\Logger;
use App\Modelo\Modelo;

class AdvertenciaGenerica extends Modelo {
	public $id;
	public $texto;

	public static function listar() {
		$res = (new Conexiones())->consulta(Conexiones::SELECT,
			"SELECT id, texto FROM advertencias_genericas WHERE borrado = 0");
		if (!is_array($res)) {
			$res = [];
		}

		return $res;
	}

	public static function listar_advertencias_genericas($params) {
		$campos    = 'id, texto';
		$sql_params = [];
		$where[] = "borrado = 0";
        $condicion = "";

		$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo'])) ? 'tipo' : $params['order']['campo'];
		$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']))   ? 'asc' : $params['order']['dir'];
		$params['start']  = (!isset($params['start'])  || empty($params['start']))  ? 0 :
			$params['start'];
		$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght'])) ? 10 :
			$params['lenght'];
		$params['search'] = (!isset($params['search']) || empty($params['search'])) ? '' :
			$params['search'];

		$condicion .= !empty($where[0]) ? ' WHERE ' . \implode(' AND ',$where) : '';
		
		if(!empty($params['search'])){
			$indice = 0;
			$search[]   = <<<SQL
			(id like :search{$indice} OR texto like :search{$indice}) 
			SQL;
			$texto = $params['search'];
			$sql_params[":search{$indice}"] = "%{$texto}%";

			$buscar =  implode(' AND ', $search);
			$condicion .= empty($condicion) ? "{$buscar}" : " AND {$buscar} ";
		}
		
		$consulta = <<<SQL
        SELECT id, texto FROM advertencias_genericas $condicion
SQL;
		$data = self::listadoAjax($campos, $consulta, $params, $sql_params);		
		return $data;

	}

	public static function obtener($id) {
		$sql = "SELECT * FROM advertencias_genericas WHERE id = :id";
		if (is_numeric($id)) {
			if ($id > 0) {
				$params = [':id' => $id];
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
		$obj->texto = isset($res['texto']) ? $res['texto'] : null;

		return $obj;
	}

	public function alta() {
		if ($this->validar()) {
			$sql = "INSERT INTO advertencias_genericas (texto) VALUE (:texto)";
			$params = [':texto' => $this->texto];
			$res = (new Conexiones())->consulta(Conexiones::INSERT, $sql, $params);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				$this->id = $res;
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'advertencia_generica';
				Logger::event('alta', $datos);

				return true;
			}
		}

		return false;
	}

	public function validar() {
		$validator = Validador::validate(['texto' => $this->texto],
			['texto' => ['required', 'texto', 'min_length(5)']], ['texto' => 'Mensaje genérico']);
		if ($validator->isSuccess()) {
			return true;
		}
		$this->errores = $validator->getErrors();

		return false;
	}

	public function baja() {
		$sql = "UPDATE advertencias_genericas SET borrado = 1 WHERE id = :id";
		$params = [':id' => $this->id];
		$res = (new Conexiones())->consulta(Conexiones::DELETE, $sql, $params);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'advertencia_generica';
			Logger::event('baja', $datos);

			return true;
		}

		return false;
	}

	public function modificacion() {
		if ($this->validar()) {
			$sql = "UPDATE advertencias_genericas SET texto = :texto WHERE id = :id";
			$params = [':texto' => $this->texto, ':id' => $this->id];
			$res = (new Conexiones())->consulta(Conexiones::UPDATE, $sql, $params);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'advertencia_generica';
				Logger::event('modificacion', $datos);

				return true;
			}
		}

		return false;
	}
}