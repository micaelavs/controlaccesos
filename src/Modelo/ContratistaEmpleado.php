<?php
namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Validador;
use FMT\Logger;
use App\Modelo\Modelo;
use App\Modelo\Empleado;
use DateTime;

/**
 * Class ContratistaEmpleado
 */
class ContratistaEmpleado extends Modelo {
	/** @var  int */
	public $id;
	/** @var  Contratista */
	public $contratista;
	/** @var int */
	public $contratista_id;
	/** @var  Empleado */
	public $autorizante;
	/** @var int */
	public $autorizante_id;
	/** @var  Persona */
	public $persona;
	/** @var int */
	public $persona_id;
	/** @var  \DateTime */
	public $art_inicio;
	/** @var  \DateTime */
	public $art_fin;
	/** @var  Ubicacion[] */
	public $ubicaciones;

	//Datos de persona
	public $documento;
	public $nombre;
	public $apellido;


	static public function listar_contratistas_empleados($params)
	{
		$campos    = 'id,persona_id,autorizante_id,contratista_id,contratista_nombre,persona_documento,persona_nombre,persona_apellido,autorizante_documento,autorizante_nombre,autorizante_apellido,art_inicio_str,art_fin_str,art_inicio,art_fin';
		$sql_params = [];
		$where = [];
		//$condicion = "AND p.borrado = 0";

		$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo'])) ? 'tipo' : $params['order']['campo'];
		$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']))   ? 'asc' : $params['order']['dir'];
		$params['start']  = (!isset($params['start'])  || empty($params['start']))  ? 0 :
			$params['start'];
		$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght'])) ? 10 :
			$params['lenght'];
		$params['search'] = (!isset($params['search']) || empty($params['search'])) ? '' :
			$params['search'];
		
		if (!empty($params['filtros']['idContratista'])) {
			$where[] = "contratista_id = :idContratista";
			$sql_params[':idContratista']    = $params['filtros']['idContratista'];
		}			

		$condicion = !empty($where) ? ' WHERE p.borrado = 0 AND ' . \implode(' AND ',$where): ''; 

		if(!empty($params['search'])){
            $indice = 0;
            $search[]   = <<<SQL
            (p.documento like :search{$indice} OR p.nombre like :search{$indice} OR p.apellido like :search{$indice}) 
SQL;
            $texto = $params['search'];
            $sql_params[":search{$indice}"] = "%{$texto}%";

            $buscar =  implode(' AND ', $search);
            $condicion .= empty($condicion) ? "{$buscar}" : " AND {$buscar} ";

          
        }

		$consulta = <<<SQL
        		SELECT
					cp.id                                         AS id,
					cp.persona_id                                 AS persona_id,
					cp.autorizante_id                             AS autorizante_id,
					cp.contratista_id                             AS contratista_id,
					c.nombre                                      AS contratista_nombre,
					p.documento                                   AS persona_documento,
					p.nombre                                      AS persona_nombre,
					p.apellido                                    AS persona_apellido,
					p2.documento                                  AS autorizante_documento,
					p2.nombre                                     AS autorizante_nombre,
					p2.apellido                                   AS autorizante_apellido,
					DATE_FORMAT(cp.art_inicio, '%d/%m/%Y')        AS art_inicio_str,
					DATE_FORMAT(cp.art_fin, '%d/%m/%Y')           AS art_fin_str,
					cp.art_inicio                                 AS art_inicio,
					cp.art_fin                                    AS art_fin
				FROM contratista_personal AS cp
					LEFT JOIN contratistas AS c ON c.id = cp.contratista_id
					LEFT JOIN personas AS p ON cp.persona_id = p.id
					LEFT JOIN empleados AS e ON cp.autorizante_id = e.id
					LEFT JOIN personas AS p2 ON e.persona_id = p2.id
			$condicion     
SQL;

		$data = self::listadoAjax($campos, $consulta, $params, $sql_params);
		return $data;
	}

	/**
	 * @param int $contratista_id
	 * @return null
	 */
	public static function listarPorContratistaId($contratista_id) {
		if (!empty($contratista_id) && is_numeric($contratista_id) && $contratista_id > 0) {
			$extra = " AND contratista_id = :contratista_id";
			$conex = new Conexiones();

			return $conex->consulta(Conexiones::SELECT, static::sql($extra), [
				':contratista_id' => $contratista_id,
			]);
		}

		return null;
	}

	/**
	 * @param $extra
	 * @return string
	 */
	protected static function sql($extra = '') {
		return "SELECT
					cp.id                                         AS id,
					cp.persona_id                                 AS persona_id,
					cp.autorizante_id                             AS autorizante_id,
					cp.contratista_id                             AS contratista_id,
					c.nombre                                      AS contratista_nombre,
					p.documento                                   AS persona_documento,
					p.nombre                                      AS persona_nombre,
					p.apellido                                    AS persona_apellido,
					p2.documento                                  AS autorizante_documento,
					p2.nombre                                     AS autorizante_nombre,
					p2.apellido                                   AS autorizante_apellido,
					DATE_FORMAT(cp.art_inicio, '%d/%m/%Y')        AS art_inicio_str,
					DATE_FORMAT(cp.art_fin, '%d/%m/%Y')           AS art_fin_str,
					cp.art_inicio                                 AS art_inicio,
					cp.art_fin                                    AS art_fin
				FROM contratista_personal AS cp
					INNER JOIN contratistas AS c ON c.id = cp.contratista_id AND c.borrado = 0
					LEFT JOIN personas AS p ON cp.persona_id = p.id
					LEFT JOIN empleados AS e ON cp.autorizante_id = e.id
					LEFT JOIN personas AS p2 ON e.persona_id = p2.id
				WHERE p.borrado = 0" . $extra;
	}

	public static function obtenerPorDocumento($documento) {
		if (!empty($documento)) {
			$sql = "SELECT cp.id
			FROM contratista_personal AS cp
				JOIN personas AS p ON cp.persona_id = p.id
			WHERE p.borrado = 0
				AND p.documento = :documento AND cp.art_fin >= NOW()";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, [':documento' => $documento]);
			if (!empty($res) && is_array($res) && count($res) > 0) {
				$empleado = static::obtener($res[0]['id']);
				if (!empty($empleado)) {
					return $empleado;
				}
			}
			$empleado = static::obtener(0);
			$empleado->documento = $documento;

			return $empleado;
		}

		return static::obtener(0);
	}

	static public function obtener($id) {
		$sql = static::sql(" AND (p.documento = :id OR cp.id = :id)");
		$_sql = "SELECT * FROM ubicaciones AS ed LEFT JOIN contratista_x_ubicacion AS cxe ON cxe.ubicacion_id = ed.id 
		WHERE cxe.personal_id = :id";
		if (is_numeric($id)) {
			if ($id > 0) {
				$params = [':id' => $id];
				$conex = new Conexiones();
				$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
				if (!empty($res) && is_array($res) && isset($res[0])) {
					$_res = $conex->consulta(Conexiones::SELECT, $_sql, $params);
					if (!empty($_res) && is_array($_res) && count($_res) > 0) {
						$res['ubicaciones'] = $_res;
					}

					return static::arrayToObject($res[0]);
				}
			} else {

				return static::arrayToObject();
			}
		}

		return static::arrayToObject();
	}

	/**
	 * @param $res
	 * @return ContratistaEmpleado
	 */
	public static function arrayToObject($res = []) {
		$obj = new self();
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->art_inicio = isset($res['art_inicio']) ? new \DateTime($res['art_inicio']) : null;
		$obj->art_fin = isset($res['art_fin']) ? new \DateTime($res['art_fin']) : null;
		$obj->persona_id = isset($res['persona_id']) ? (int)$res['persona_id'] : 0;
		$obj->autorizante_id = isset($res['autorizante_id']) ? (int)$res['autorizante_id'] : 0;
		$obj->contratista_id = isset($res['contratista_id']) ? (int)$res['contratista_id'] : 0;
		$obj->persona = Persona::obtener($obj->persona_id);
		$obj->autorizante =  Empleado::obtener($obj->autorizante_id);
		$obj->contratista = Contratista::obtener($obj->contratista_id);
		if (isset($res['ubicaciones'])) {
			foreach ($res['ubicaciones'] as $ubicacion) {
				$obj->ubicaciones[] = Ubicacion::arrayToObject($ubicacion);
			}
		}
		$obj->documento = isset($res['documento']) ? (int)$res['documento'] : null;
		$obj->nombre = isset($res['nombre']) ? (int)$res['nombre'] : null;
		$obj->apellido = isset($res['apellido']) ? (int)$res['apellido'] : null;

		return $obj;
	}

	public function alta() {
		if ($this->validar()) {
			$per = Persona::obtenerPorDocumento($this->documento);
			if (empty($per) || empty($per->id)) {
				$per = new Persona();
                $per->documento = $this->documento;
                $per->nombre = $this->nombre;
                $per->apellido = $this->apellido;
                $per->genero = '0';
                $flag = $per->alta();
                $this->persona_id = $flag;
			} else {
				$per->documento = $this->documento;
                $per->nombre = $this->nombre;
                $per->apellido = $this->apellido;
                $flag = $per->modificacion();
                $this->persona_id = $per->id;
			}
			if ($flag) {
				$sql = "INSERT INTO contratista_personal
						(contratista_id, autorizante_id, persona_id, art_inicio, art_fin) " .
					"VALUE (:contratista_id, :autorizante_id, :persona_id, :art_inicio, :art_fin)";
				$data = [
					':contratista_id' => $this->contratista_id,
					':autorizante_id' => $this->autorizante_id,
					':persona_id'     => $this->persona_id,
					':art_inicio'     => $this->art_inicio,
					':art_fin'        => $this->art_fin,
				];
				$conex = new Conexiones();
				$res = $conex->consulta(Conexiones::INSERT, $sql, $data);
				if (!empty($res) && is_numeric($res) && $res > 0) {
					$this->id = $res;

					return true;
				}

			}
			$this->errores = $per->errores;
			
		}

		return false;
	}

	public function validar() {
		$self = $this;
		$inputs = [
			'contratista_id' => $this->contratista_id,
			'art_inicio'     => $this->art_inicio,
			'art_fin'        => $this->art_fin,
			'now'            => new \DateTime('now'),
			'autorizante_id' => $this->autorizante_id,
			'documento' 	 => $this->documento,
		];

		$reglas = [
			'contratista_id' => ['required'],
			'art_inicio'     => ['fecha', 'required', 'antesDe(:art_fin)'],
			'art_fin'        => ['fecha', 'required', 'despuesDe(:art_inicio)', 'despuesDe(:now)'],
			'autorizante_id' => ['required', 'existe(empleados,id)'],
			'documento'      => [
				'required',
				'no_empleado'    => function ($doc) use ($self) {
					$emp = Empleado::obtenerPorDocumento($doc);

					return !empty($self->id) || empty($emp->id);
				},
				'no_contratista' => function ($doc) use ($self) {
					$cont = ContratistaEmpleado::obtenerPorDocumento($doc);

					return !empty($self->id) || empty($cont->id);
				},
			],
		];
		$nombres = [
			'contratista_id' => 'Contratista',
			'persona_id'     => 'Personal de Contratista',
			'art_inicio'     => 'Inicio de la vigencia de la ART',
			'art_fin'        => 'Final de la vigencia de la ART',
			'now'            => 'hoy',
			'autorizante_id' => 'Empleado Autorizante'
		];
		$validator = Validador::validate($inputs, $reglas, $nombres);
		if ($validator->isSuccess()) {
			return true;
		}
		$this->errores = $validator->getErrors();

		return false;
	}

	public function baja() {
		if (!empty($this->id) && is_numeric($this->id) && $this->id > 0) {
			$sql = "UPDATE personas AS p JOIN contratista_personal AS cp ON cp.persona_id = p.id SET p.borrado = 1 WHERE cp.id = :id";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::DELETE, $sql, [':id' => $this->id]);
			if (!empty($res) && $res > 0) {
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'contratista_empleado';
				Logger::event('baja', $datos);

				return true;
			}
		}

		return false;
	}

	public static function baja_contratista_personal($idContratista) {
		if (!empty($idContratista) && is_numeric($idContratista) && $idContratista > 0) {
			$sql = "UPDATE personas AS p 
					INNER JOIN contratista_personal AS cp ON cp.persona_id = p.id 
					INNER JOIN contratistas AS c ON c.id = cp.contratista_id
					SET p.borrado = 1
					WHERE c.id = :id";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::DELETE, $sql, [':id' => $idContratista]);
			if (!empty($res) && $res > 0) {
				//Log
				// $datos = (array)$this;
				// $datos['modelo'] = 'contratista_empleado';
				// Logger::event('baja', $datos);

				return true;
			}
		}

		return false;
	}

	public function modificacion() {
		if ($this->validar()) {
			$sql = "UPDATE contratista_personal AS cp
						JOIN personas AS p ON cp.persona_id = p.id
					SET p.nombre          = :persona_nombre, 
						p.apellido        = :persona_apellido,
						p.documento       = :persona_documento,
						cp.autorizante_id = :autorizante_id,
						cp.art_inicio     = :art_inicio,
						cp.art_fin        = :art_fin
					WHERE cp.id = :id";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::UPDATE, $sql, [
				':id'                => $this->id,
				':persona_nombre'    => $this->nombre,
				':persona_apellido'  => $this->apellido,
				':persona_documento'  => $this->documento,
				':autorizante_id' => $this->autorizante_id,
				':art_inicio'     => ($this->art_inicio instanceof DateTime) ? $this->art_inicio->format('Y-m-d H:i:s') : \DateTime::createFromFormat('d/m/Y', $this->art_inicio)->format('Y-m-d H:i:s'),
				':art_fin'        => ($this->art_fin instanceof DateTime) ? $this->art_fin->format('Y-m-d H:i:s') : \DateTime::createFromFormat('d/m/Y', $this->art_fin)->format('Y-m-d H:i:s'),
				
			]);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'contratista_empleado';
				Logger::event('modificacion', $datos);

				return true;
			}
		}

		return false;
	}

	public function ubicacionesSinPermiso() {
		$sql = "SELECT * FROM ubicaciones AS e WHERE e.borrado = 0 AND e.id NOT IN (SELECT ce.ubicacion_id 
FROM contratista_x_ubicacion AS ce WHERE ce.personal_id = :pid)";
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::SELECT, $sql, [':pid' => $this->id]);
		if (!empty($res) && is_array($res) && count($res) > 0) {
			return $res;
		}

		return [];
	}

	/**
	 * @param int       $ubicacion_id <p>Índice de clase <strong>Ubicacion</strong></p>
	 * @param \DateTime $fecha_acceso_inicio
	 * @param \DateTime $fecha_acceso_fin
	 * @return bool
	 */
	public function agregarUbicacion($ubicacion_id, $fecha_acceso_inicio, $fecha_acceso_fin) {
		$contratistaXUbicacion = ContratistaUbicacion::obtener(0);
		$contratistaXUbicacion->ubicacion_id = $ubicacion_id;
		$contratistaXUbicacion->acceso_inicio = $fecha_acceso_inicio;
		$contratistaXUbicacion->acceso_fin = $fecha_acceso_fin;
		$contratistaXUbicacion->personal_id = $this->id;
		if ($contratistaXUbicacion->alta()) {
			return true;
		}
		$this->errores = $contratistaXUbicacion->errores;

		return false;
	}

	/**
	 * @return ContratistaUbicacion[]
	 */
	public function ubicaciones() {
		return ContratistaUbicacion::listarPorContratistaEmpleado($this);
	}

	public function retirarUbicacion($ubicacion_id) {
		$ubis = $this->ubicaciones();
		foreach ($ubis as $ubi) {
			if ($ubi->id === $ubicacion_id) {
				return $ubi->baja();
			}
		}

		return false;
	}

	/**
	 * El parámetro debe ser de una Ubicacion.
	 * @param int $ubicacion_id
	 * @return bool
	 */
	public function puedeAcceder($ubicacion_id) {
		foreach ($this->ubicaciones() as $autorizada) {
			if ($autorizada->ubicacion_id == $ubicacion_id) {
				return true;
			}
		}

		return false;
	}

	/**
	 * El parámetro debe ser una Ubicacion.
	 * @param int $id
	 * @return ContratistaUbicacion
	 */
	public function obtenerUbicacion($id) {
		$ubis = $this->ubicaciones();
		foreach ($ubis as $ubi) {
			if ($ubi->ubicacion_id == $id) {
				return $ubi;
			}
		}

		return null;
	}
}