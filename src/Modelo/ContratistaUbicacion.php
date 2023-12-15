<?php namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Validador;
use FMT\Logger;
use App\Modelo\Modelo;

class ContratistaUbicacion extends Modelo {
	/** @var int */
	public $id;
	/** @var int */
	public $personal_id;
	/** @var int */
	public $ubicacion_id;
	/** @var  \DateTime */
	public $acceso_inicio;
	/** @var  \DateTime */
	public $acceso_fin;

	public function validar() {
		$inputs = (array)$this;
		$rules = [
			'personal_id'   => ['required'],
			'ubicacion_id'  => ['required'],
			'acceso_inicio' => ['fecha'],
			'acceso_fin'    => ['fecha', 'despuesDe(:acceso_inicio)'],
		];
		$naming = [
			'personal_id'   => 'Personal',
			'ubicacion_id'  => 'Ubicación',
			'acceso_inicio' => 'Fecha de Inicio de Acceso',
			'acceso_fin'    => 'Fecha de Final de Acceso',
		];
		$validator = Validador::validate($inputs, $rules, $naming);
		if ($validator->isSuccess()) {
			return true;
		}
		$this->errores = $validator->getErrors();

		return false;
	}

	public function alta() {
		if ($this->validar()) {
			$sql = "INSERT INTO contratista_x_ubicacion (personal_id, ubicacion_id, acceso_inicio, acceso_fin)
						VALUE (:personal_id, :ubicacion_id, :acceso_inicio, :acceso_fin)";
			$params = [
				':personal_id'   => $this->personal_id,
				':ubicacion_id'  => $this->ubicacion_id,
				':acceso_inicio' => $this->acceso_inicio,
				':acceso_fin'    => $this->acceso_fin,
			];
			$res = (new Conexiones())->consulta(Conexiones::INSERT, $sql, $params);
			if ($res > 0) {
				$this->id = $res;
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'contratista_ubicacion';
				Logger::event('alta', $datos);

				return true;
			}
		}

		return false;
	}

	public function baja() {
		if ($this->validar()) {
			// $sql = "DELETE FROM contratista_x_ubicacion
			// 			WHERE id = :id";
			$sql = "UPDATE contratista_x_ubicacion SET borrado = 1 WHERE id = :id";
						
			$params = [
				':id' => $this->id,
			];
			$res = (new Conexiones())->consulta(Conexiones::DELETE, $sql, $params);
			if ($res > 0) {
				//Log
				// $datos = (array)$this;
				// $datos['modelo'] = 'contratista_ubicacion';
				// Logger::event('baja', $datos);

				return true;
			}
		}

		return false;
	}

	public function modificacion() {
		if ($this->validar()) {
			$sql = "UPDATE contratista_x_ubicacion
					SET personal_id = :personal_id, ubicacion_id = :ubicacion_id, acceso_inicio = :acceso_inicio, acceso_fin = :acceso_fin
					WHERE id = :id";
			$params = [
				':personal_id'   => $this->personal_id,
				':ubicacion_id'  => $this->ubicacion_id,
				':acceso_inicio' => $this->acceso_inicio,
				':acceso_fin'    => $this->acceso_fin,
				':id'            => $this->id,
			];
			$res = (new Conexiones())->consulta(Conexiones::UPDATE, $sql, $params);
			if ($res > 0) {
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'contratista_ubicacion';
				Logger::event('modificacion', $datos);

				return true;
			}
		}

		return false;
	}

	/**
	 * @param $id
	 * @return ContratistaUbicacion
	 */
	public static function obtener($id) {
		$sql = "SELECT
					id,
					personal_id,
					ubicacion_id,
					acceso_inicio,
					acceso_fin
				FROM contratista_x_ubicacion
				WHERE id = :id";
		if (is_numeric($id)) {
			$obj = new static();
			if ($id > 0) {
				$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, [':id' => $id]);
				if (is_array($res) && isset($res[0])) {
					$res = $res[0];
					$obj->id = (int)$res['id'];
					$obj->personal_id = (int)$res['personal_id'];
					$obj->ubicacion_id = (int)$res['ubicacion_id'];
					$obj->acceso_inicio = $res['acceso_inicio'] ? new \DateTime($res['acceso_inicio']) : null;
					$obj->acceso_fin = $res['acceso_fin'] ? new \DateTime($res['acceso_fin']) : null;
				}
			} else {
				$obj->id = 0;
				$obj->personal_id = 0;
				$obj->ubicacion_id = 0;
				$obj->acceso_inicio = null;
				$obj->acceso_fin = null;
			}

			return $obj;
		}

		return null;
	}


	public static function obtenerPermisos($contratista_empleado,$ubicacionPermiso) {
		$sql = "SELECT
					id,
					personal_id,
					ubicacion_id,
					acceso_inicio,
					acceso_fin
				FROM contratista_x_ubicacion";
		$params = [];
		if ($contratista_empleado) {
			$params[':contratista_empleado'] = $contratista_empleado->id;
			$params[':ubicacionPermiso'] = $ubicacionPermiso;
			$sql .= " WHERE personal_id = :contratista_empleado" . " AND (acceso_fin >= NOW() OR acceso_fin IS NULL) AND ubicacion_id = :ubicacionPermiso AND borrado = 0";
		}
		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, $params);
		$lista = [];
		if (is_array($res)) {
			foreach ($res as $re) {
				$obj = new static();
				$obj->id = (int)$re['id'];
				$obj->personal_id = (int)$re['personal_id'];
				$obj->ubicacion_id = (int)$re['ubicacion_id'];
				$obj->acceso_inicio = $re['acceso_inicio'] ? new \DateTime($re['acceso_inicio']) : null;
				$obj->acceso_fin = $re['acceso_fin'] ? new \DateTime($re['acceso_fin']) : null;
				$lista[] = $obj;
			}
		}

		return $lista;
	}

	static public function listarContratistaUbicacion($params)
	{
		$campos    = 'id, personal_id, ubicacion_id, nombre_ubicacion, acceso_inicio, acceso_fin, acceso_inicio_str, acceso_fin_str';
		$sql_params = [];
		$where = [];
		

		$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo'])) ? 'tipo' : $params['order']['campo'];
		$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']))   ? 'asc' : $params['order']['dir'];
		$params['start']  = (!isset($params['start'])  || empty($params['start']))  ? 0 :
			$params['start'];
		$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght'])) ? 10 :
			$params['lenght'];
		$params['search'] = (!isset($params['search']) || empty($params['search'])) ? '' :
			$params['search'];
		
		if (!empty($params['filtros']['idContratista'])) {
			$where[] = "cu.personal_id = :idContratista";
			$sql_params[':idContratista']    = $params['filtros']['idContratista'];
		}			

		$condicion = !empty($where) ? ' WHERE ' . \implode(' AND ',$where): ''; 
		$condicion .= " AND cu.borrado = 0";

		if(!empty($params['search'])){
            $indice = 0;
            $search[]   = <<<SQL
            (u.nombre like :search{$indice}) 
SQL;
            $texto = $params['search'];
            $sql_params[":search{$indice}"] = "%{$texto}%";

            $buscar =  implode(' AND ', $search);
            $condicion .= empty($condicion) ? "{$buscar}" : " AND {$buscar} ";

          
        }

		$consulta = <<<SQL
        		SELECT
					cu.id,
					cu.personal_id,
					cu.ubicacion_id,
                    u.nombre AS nombre_ubicacion,
					cu.acceso_inicio,
					cu.acceso_fin,                    
                    Ifnull(DATE_FORMAT(cu.acceso_inicio, '%d/%m/%Y'), 'SIN RESTRICCIÓN')        AS acceso_inicio_str,
					Ifnull(DATE_FORMAT(cu.acceso_fin, '%d/%m/%Y'), 'SIN RESTRICCIÓN')           AS acceso_fin_str
				FROM contratista_x_ubicacion cu
                LEFT JOIN ubicaciones AS u ON cu.ubicacion_id = u.id
				$condicion  
SQL;

		$data = self::listadoAjax($campos, $consulta, $params, $sql_params);
		return $data;
	}

	public function toArray() {
		return [
			'id'            => $this->id,
			'personal_id'   => $this->personal_id,
			'ubicacion_id'  => $this->ubicacion_id,
			'acceso_inicio' => $this->acceso_inicio->format('d/m/Y'),
			'acceso_fin'    => $this->acceso_fin->format('d/m/Y'),
		];
	}

	/**
	 * @return ContratistaUbicacion[]
	 */
	// public static function listar() {
	// 	return self::listarPorContratistaEmpleado();
	// }

	/**
	 * Recibe un Objeto ContratistaEmpleado y devuelve los edificios asociados a este.
	 * @param ContratistaEmpleado $contratista_empleado
	 * @return ContratistaUbicacion[]
	 */
	public static function listarPorContratistaEmpleado($contratista_empleado = null) {
		$sql = "SELECT
					id,
					personal_id,
					ubicacion_id,
					acceso_inicio,
					acceso_fin
				FROM contratista_x_ubicacion";
		$params = [];
		if ($contratista_empleado) {
			$params[':contratista_empleado'] = $contratista_empleado->id;
			$sql .= " WHERE personal_id = :contratista_empleado" . " AND (acceso_fin >= NOW() OR acceso_fin IS NULL) AND borrado = 0";
		}
		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, $params);
		$lista = [];
		if (is_array($res)) {
			foreach ($res as $re) {
				$obj = new static();
				$obj->id = (int)$re['id'];
				$obj->personal_id = (int)$re['personal_id'];
				$obj->ubicacion_id = (int)$re['ubicacion_id'];
				$obj->acceso_inicio = $re['acceso_inicio'] ? new \DateTime($re['acceso_inicio']) : null;
				$obj->acceso_fin = $re['acceso_fin'] ? new \DateTime($re['acceso_fin']) : null;
				$lista[] = $obj;
			}
		}

		return $lista;
	}

	/**
	 * @return ContratistaEmpleado
	 */
	public function getContratistaEmpleado() {
		return ContratistaEmpleado::obtener($this->personal_id);
	}

	/**
	 * @param ContratistaEmpleado $contratista_empleado
	 */
	public function setContratistaEmpleado($contratista_empleado) {
		$this->personal_id = $contratista_empleado->id;
	}

	/**
	 * @return Ubicacion
	 */
	public function getUbicacion() {
		return Ubicacion::obtener($this->ubicacion_id);
	}

	/**
	 * @param Ubicacion $ubicacion
	 */
	public function setUbicacion($ubicacion) {
		$this->ubicacion_id = $ubicacion->id;
	}
}