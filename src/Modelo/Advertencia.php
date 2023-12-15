<?php

namespace App\Modelo;

use App\Modelo\Modelo;
use App\Helper\Validador;
use App\Helper\Conexiones;
use FMT\Logger;

class Advertencia extends Modelo
{
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
	public $generico;

	static public function listar()
	{
		$sql = "SELECT
					ad.id                                                              AS id,
					ad.texto                                                           AS texto,
					p.id                                                               AS persona_id,
					p.documento                                                        AS persona_documento,
					p.nombre                                                           AS persona_nombre,
					p.apellido                                                         AS persona_apellido,
					ed.id                                                              AS ubicacion_id,
					CASE WHEN ed.nombre is NULL THEN 'TODAS' ELSE ed.nombre END        AS ubicacion_nombre,
					CONCAT(', ', ed.calle, ' ', ed.numero) AS ubicacion_direccion,
					em.id                                                              AS solicitante_id,
					p2.id                                                              AS solicitante_persona_id,
					p2.nombre                                                          AS solicitante_persona_nombre,
					p2.apellido                                                        AS solicitante_persona_apellido
				FROM advertencias AS ad
					JOIN personas AS p ON p.id = ad.persona_id
					LEFT JOIN ubicaciones AS ed ON ed.id = ad.ubicacion_id
					LEFT JOIN empleados AS em ON em.id = ad.solicitante_id
					LEFT JOIN personas AS p2 ON p2.id = em.persona_id
				WHERE ad.borrado = 0";
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::SELECT, $sql);
		if (!empty($res) && is_array($res) && count($res) > 0) {
			return $res;
		}

		return [];
	}

	static public function listar_advertencias($params)
	{
		$campos    = 'id,texto,persona_id,persona_documento,persona_nombre,persona_apellido,ubicacion_id,ubicacion_nombre,ubicacion_direccion,solicitante_id,solicitante_persona_id,solicitante_persona_nombre,solicitante_persona_apellido';

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
					ad.id                                                              AS id,
					ad.texto                                                           AS texto,
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
				FROM advertencias AS ad
					JOIN personas AS p ON p.id = ad.persona_id
					LEFT JOIN ubicaciones AS ed ON ed.id = ad.ubicacion_id
					LEFT JOIN empleados AS em ON em.id = ad.solicitante_id
					LEFT JOIN personas AS p2 ON p2.id = em.persona_id
				WHERE ad.borrado = 0
SQL;
		$data = self::listadoAjax($campos, $consulta, $params, $sql_params);
		return $data;
	}

	/**
	 * @param $id
	 * @return Advertencia
	 */
	static public function obtener($id)
	{
		$sql = "SELECT ad.* FROM advertencias AS ad 
LEFT JOIN personas AS p ON p.id = ad.persona_id 
WHERE (ad.borrado = 0 AND p.borrado = 0) AND (ad.id = :id)";
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

	/**
	 * @param $res
	 * @return Advertencia
	 */
	public static function arrayToObject($res = []) {
		$obj = new self();
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->persona = Persona::obtener(isset($res['persona_id']) ? $res['persona_id'] : 0);
		$obj->ubicacion = Ubicacion::obtener(isset($res['ubicacion_id']) ? $res['ubicacion_id'] : 0);
		$obj->solicitante = Empleado::obtener(isset($res['solicitante_id']) ? $res['solicitante_id'] : 0);
		$obj->texto = isset($res['texto']) ? $res['texto'] : null;

		return $obj;
	}

	/**
	 * @param $documento
	 * @return Advertencia[]
	 */
	public static function listarPorDocumento($documento)
	{
		$sql = "SELECT ad.* FROM advertencias AS ad 
LEFT JOIN personas AS p ON p.id = ad.persona_id 
WHERE (ad.borrado = 0 AND p.borrado = 0) AND (p.documento = :documento)";
		$params = [':documento' => $documento];
		if (!empty($documento)) {
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
			if (!empty($res) && is_array($res) && count($res) > 0) {
				/** @var Advertencia[] $ads */
				$ads = [];
				foreach ($res as $re) {
					$ads[] = static::arrayToObject($re);
				}

				return $ads;
			}

			return [];
		}

		return null;
	}

	/**
	 * @param $ubicacion
	 * @return Advertencias[]
	 */
	public static function listarPorUbicacion($ubicacion) {
		$sql = "SELECT id, persona_id, ubicacion_id, solicitante_id, GROUP_CONCAT(texto SEPARATOR '<br>') as texto FROM advertencias 
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

	public function alta()
	{
		if ($this->validar()) {
			$sql = "INSERT INTO advertencias (persona_id, ubicacion_id, solicitante_id, texto) VALUE (:persona_id, :ubicacion_id, :solicitante_id, :texto)";
			$params = [
				':persona_id'     => $this->persona->id,
				':ubicacion_id'   => $this->ubicacion->id,
				':solicitante_id' => $this->solicitante->id,
				':texto'          => $this->texto,
			];
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::INSERT, $sql, $params);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'Advertencia';
				Logger::event('alta', $datos);

				return true;
			}
		}

		return false;
	}

	public function validar()
	{
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
			'ubicacion'   => 'Ubicacion',
			'texto'       => 'Mensaje',
		];
		$validator = Validador::validate((array)$this, $reglas, $nombres);
		if (empty($this->persona->errores) && $validator->isSuccess()) {
			return true;
		}
		$this->errores = [];
		$this->errores = array_merge($this->errores, $validator->getErrors(), ($this->persona->errores ?: []));

		return false;
	}

	public function baja()
	{
		$sql = "UPDATE advertencias SET borrado = 1 WHERE id = :id";
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'Advertencia';
			Logger::event('baja', $datos);

			return true;
		}

		return false;
	}

	public function modificacion()
	{
		if ($this->validar()) {
			$sql = "UPDATE advertencias SET persona_id = :persona_id, ubicacion_id = :ubicacion_id, " .
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
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getAdvertenciaMsj()
	{
		if (!empty($this->texto)) {
			$msj = "{$this->texto}";
			if (
				!empty($this->solicitante) &&
				!empty($this->solicitante->id) &&
				!empty($this->solicitante->persona) &&
				!empty($this->solicitante->persona->id)
			) {
				$msj .= "<br>&emsp;&emsp;&emsp;<strong>{$this->solicitante->persona->full_name}</strong>";
			}

			return $msj;
		}

		return '';
	}
}
