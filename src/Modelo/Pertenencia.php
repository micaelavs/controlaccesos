<?php

namespace App\Modelo;

use App\Modelo\Modelo;
use App\Helper\Validador;
use App\Helper\Conexiones;
use FMT\Logger;

class Pertenencia extends Modelo {
	/** @var int */
	public $id;
	/** @var int */
	public $persona_id;
	/** @var Persona */
	public $persona;
	/** @var Ubicacion */
	public $ubicacion;
	/** @var int */
	public $ubicacion_id;
	/** @var Empleado */
	public $solicitante;
	/** @var  int */
	public $solicitante_id;
	/** @var string */
	public $texto;
	/** @var string */


	static public function listar_pertenencias($params) {
		
		$campos    = 'id,texto,persona_id,persona_documento,persona_nombre,persona_apellido,ubicacion_id,ubicacion_nombre,
		ubicacion_direccion,solicitante_id,solicitante_persona_id,solicitante_persona_nombre,solicitante_persona_apellido';

		$sql_params = [];

		$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo'])) ? 'tipo' : $params['order']['campo'];
		$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']))   ? 'asc' : $params['order']['dir'];
		$params['start']  = (!isset($params['start'])  || empty($params['start']))  ? 0 :
			$params['start'];
		$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght'])) ? 10 :
			$params['lenght'];
		$params['search'] = (!isset($params['search']) || empty($params['search'])) ? '' :
			$params['search'];

		$consulta = <<<SQL
       			SELECT
					pr.id                                                              AS id,
					pr.texto                                                           AS texto,
					p.id                                                               AS persona_id,
					p.documento                                                        AS persona_documento,
					p.nombre                                                           AS persona_nombre,
					p.apellido                                                         AS persona_apellido,
					ed.id                                                              AS ubicacion_id,
					CASE WHEN ed.nombre is NULL THEN 'TODAS' ELSE ed.nombre END        AS ubicacion_nombre,
					Ifnull(CONCAT(', ', ed.calle, ' ', ed.numero), '')     			   AS ubicacion_direccion,
					em.id                                                              AS solicitante_id,
					p2.id                                                              AS solicitante_persona_id,
					Ifnull (p2.nombre,'')                                              AS solicitante_persona_nombre,
					Ifnull (p2.apellido,'')                                            AS solicitante_persona_apellido
				FROM pertenencias AS pr
					JOIN personas AS p ON p.id = pr.persona_id
					LEFT JOIN ubicaciones AS ed ON ed.id = pr.ubicacion_id
					LEFT JOIN empleados AS em ON em.id = pr.solicitante_id
					LEFT JOIN personas AS p2 ON p2.id = em.persona_id
				WHERE pr.borrado = 0
SQL;
		$data = self::listadoAjax($campos, $consulta, $params, $sql_params);
		return $data;
	}

	/**
	 * @param $id
	 * @return Pertenencia
	 */
	static public function obtener($id) {
		$sql = "SELECT pr.* FROM pertenencias AS pr 
		LEFT JOIN personas AS p ON p.id = pr.persona_id 
		WHERE (pr.borrado = 0 AND p.borrado = 0) AND (pr.id = :id)";
		if (is_numeric($id)) {
			if ($id > 0) {
				$params = [':id' => $id];
				$conex = new Conexiones();
				$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
				if (!empty($res) && is_array($res) && isset($res[0])) {
					return static::arrayToObject($res[0]);
				}
			}
		}

		return static::arrayToObject();
	}

	/**
	 * @param $res
	 * @return Pertenencia
	 */
	static public function arrayToObject($res = [])
	{

		$obj = new self();
        $obj->id = isset($res['id']) ? (int)$res['id'] : 0;
        $obj->persona_id = isset($res['persona_id']) ? $res['persona_id'] : null;
        $obj->ubicacion_id = isset($res['ubicacion_id']) ? $res['ubicacion_id'] : null;
        $obj->solicitante_id = isset($res['solicitante_id']) ? $res['solicitante_id'] : null;
        $obj->persona = Persona::obtener($obj->persona_id);
        $obj->ubicacion = Ubicacion::obtener($obj->ubicacion_id);
        $obj->solicitante = Empleado::obtener($obj->solicitante_id);
		$obj->texto = isset($res['texto']) ? $res['texto'] : null;
        return $obj;
	}


	/**
	 * @param $ubicacion
	 * @return Pertenencia[]
	 */
	public static function listarPorUbicacion($ubicacion) {
		$sql = "SELECT id, persona_id, ubicacion_id, solicitante_id, GROUP_CONCAT(texto SEPARATOR '<br>') as texto FROM pertenencias 
				WHERE ubicacion_id = :ubicacion_id or ubicacion_id = 0  AND borrado = 0 GROUP BY persona_id";
		$params = [':ubicacion_id' => $ubicacion];
		if (!empty($ubicacion)) {
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, $params);

			if (!empty($res) && is_array($res) && count($res) > 0) {
				return $res;
			}

			return [];
		}

		return [];
	}


	public function alta() {
		
		$sql = "INSERT INTO pertenencias (persona_id, ubicacion_id, solicitante_id, texto) VALUE (:persona_id, :ubicacion_id, :solicitante_id, :texto)";
		$params = [
			':persona_id'     => $this->persona->id,
			':ubicacion_id'   => $this->ubicacion_id,
			':solicitante_id' => $this->solicitante->id,
			':texto'          => $this->texto,
		];
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::INSERT, $sql, $params);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			$datos = (array)$this;
			$datos['modelo'] = 'Pertenencia';
			Logger::event('alta', $datos);

			return true;
		}
		return false;
	}

	public function validar() {
		 $this->persona = Persona::obtenerOAlta($this->persona);
		 $reglas = [
		 	'persona' => [
		 		'existe' => function ($obj) {
		 			/** @var Persona $obj */
		 			if (!empty($obj) &&
		 				!empty($obj->id) &&
		 				$obj->id > 0
		 			) {
		 				return true;
		 			}

		 			return false;
		 		},
		 	],
		 	'texto'   => ['required', 'texto', 'min_length(2)'],
		 ];
		 $nombres = [
		 	'persona'     => 'Persona',
		 	'solicitante' => 'Solicitante',
		 	'ubicacion_id'   => 'Ubicacion',
		 	'texto'       => 'Pertenencia',
		 ];
		 $validator = Validador::validate((array)$this, $reglas, $nombres);
		 if (empty($this->persona->errores) && $validator->isSuccess()) {
		 	return true;
		 }
		 $this->errores = [];
		 $this->errores = array_merge($this->errores, $validator->getErrors(), ($this->persona->errores ?: []));

		 return false;
	}

	public function baja() {
		$sql = "UPDATE pertenencias SET borrado = 1 WHERE id = :id";
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			$datos = (array)$this;
			$datos['modelo'] = 'Pertenencia';
			Logger::event('baja', $datos);
			return true;
		}
		return false;
	}

	public function modificacion() {
		$sql = "UPDATE pertenencias SET persona_id = :persona_id, ubicacion_id = :ubicacion_id, " .
			"solicitante_id = :solicitante_id, texto = :texto WHERE id = :id";
		$params = [
			':persona_id'     => $this->persona->id,
			':ubicacion_id'   => $this->ubicacion->id,
			':solicitante_id' => $this->solicitante->id,
			':texto'          => $this->texto,
			':id'             => $this->id,
		];
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::UPDATE, $sql, $params);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'Pertenencia';
			Logger::event('modificacion', $datos);

			return true;
		}
		return false;
	}

	/**
	 * @param $documento
	 * @return Pertenencia[]
	 */
	public static function listarPorDocumento($documento) {
		$sql = "SELECT pr.* FROM pertenencias AS pr 
				LEFT JOIN personas AS p ON p.id = pr.persona_id 
				WHERE (pr.borrado = 0 AND p.borrado = 0) AND (p.documento = :documento)";
		$params = [':documento' => $documento];
		if (!empty($documento)) {
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
			if (!empty($res) && is_array($res) && count($res) > 0) {
				/** @var Pertenencia[] $prs */
				$prs = [];
				foreach ($res as $re) {
					$prs[] = static::arrayToObject($re);
				}

				return $prs;
			}

			return [];
		}

		return null;
	}

}