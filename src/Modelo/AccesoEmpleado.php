<?php 
namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Util;
use App\Helper\Validador;
use FMT\Logger;
//use FMT\Modelo;
use DateTime;
use FMT\Informacion_fecha;
use PharIo\Manifest\Extension;

class AccesoEmpleado extends Modelo {
	/** @var int */
	public $id;
	/** @var int */
	public $empleado_id;
	/** @var Empleado */
	public $empleado;
	/** @var int */
	public $tipo_acceso;
	/** @const Define el índice de la clase el la base de datos para la relación con la tabla correspondiente */
	const TIPO_MODEL = 1;

	const
			DOMINGO		= 0,
			LUNES		= 1,
			MARTES		= 2,
			MIERCOLES	= 3,
			JUEVES		= 4,
			VIERNES		= 5,
			SABADO		= 6;

	public static $nombre_dia	= [
		self::DOMINGO	=> 'Domingo',
		self::LUNES		=> 'Lunes',
		self::MARTES	=> 'Martes',
		self::MIERCOLES	=> 'Miercoles',
		self::JUEVES	=> 'Jueves',
		self::VIERNES	=> 'Viernes',
		self::SABADO	=> 'Sabado',
	];

	const
		PRESENTES			= 1,
		AUSENTES			= 2,
		AUSENTES_NOVEDADES	= 3,
		PRESENTES_AUSENTES_NOVEDADES	= 4;

	/**
	 * Varifica si el empleado ya se necuentra con un registro de acceso activo sin salidas
	 * @param string         $documento
	 * @param int       $ubicacion_id
	 * @param \DateTime $fecha
	 * @return int
	 */
	public static function enVisita($documento, $ubicacion_id = null, $fecha = null) {
		if (!empty($documento) &&
			!empty($ubicacion_id)) {
				/* Buscar si existe una visita activa */
			$sql = <<<SQL
SELECT
	acc.id
FROM accesos AS acc
	JOIN accesos_empleados AS ae ON acc.tipo_id = ae.id AND acc.tipo_modelo = :clase
	JOIN empleados AS e ON e.id = ae.empleado_id
	JOIN personas AS p ON e.persona_id = p.id
SQL;
			$conex = new Conexiones();
			$params = [
				':documento' => $documento,
				':clase'     => Acceso::getClassIndex(new self()),
			];
			$where = [
				"p.borrado = 0",
				"acc.hora_egreso IS NULL",
				"p.documento = :documento",
			];
			if ($ubicacion_id) {
				array_push($where, 'acc.ubicacion_id = :ubicacion_id');
				$params[':ubicacion_id'] = $ubicacion_id;
			}
			if ($fecha) {
				array_push($where, "acc.hora_ingreso LIKE :fecha");
				$params[':fecha'] = '%'.$fecha->format('Y-m-d').'%';
			}
			$sql .= "\nWHERE " . implode(" AND ", $where);
			$res = $conex->consulta(Conexiones::SELECT, $sql.' ORDER BY acc.id DESC LIMIT 1', $params);
			if (!empty($res) && is_array($res) && isset($res[0])) {
				return $res[0]['id'];
			}
		}

		return false;
	}

	/**
	 * @param int $id
	 * @return AccesoEmpleado
	 */
	public static function obtener($id) {
		$sql = "SELECT id, empleado_id, :tipo_acceso AS tipo_acceso FROM accesos_empleados WHERE id = :id;";
		if (is_numeric($id)) {
			if ($id > 0) {
				$params = [
					':id'          => $id,
					':tipo_acceso' => Acceso::EMPLEADO,
				];
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
	 * @return AccesoEmpleado
	 */
	static public function arrayToObject($res = []) {
		/** @var AccesoEmpleado $obj */
		$obj = new self();
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->empleado_id = isset($res['empleado_id']) ? (int)$res['empleado_id'] : 0;
		$obj->empleado = Empleado::obtener($obj->empleado_id);
		$obj->tipo_acceso = isset($res['tipo_acceso']) ? (int)$res['tipo_acceso'] : 0;

		return $obj;
	}

	/**
	 * @param int            $ubicacion_id
	 * @param null|\DateTime $fecha
	 * @return array|int|string
	 */
	static public function listar($ubicacion_id = 0, $fecha = null) {
		$sql = "SELECT
					acc.id                                                        AS acc_id,
					p.id                                                          AS persona_id,
					'Empleado'                                                    AS credencial,
					p.documento                                                   AS documento,
					CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, '')) AS nombre,
					acc.hora_ingreso                                              AS fecha_ingreso,
					acc.hora_egreso                                               AS fecha_egreso,
					DATE_FORMAT(acc.hora_ingreso, '%H:%i')                        AS hora_entrada,
					DATE_FORMAT(acc.hora_egreso, '%H:%i')                         AS hora_salida,
					NULL                                                          AS origen,
					NULL                                                          AS destino,
					acc.observaciones                                             AS observaciones,
					NULL                                                          AS autorizante_id,
					acc.tipo_id,
					acc.tipo_modelo,
					:tipo_acceso                                                  AS tipo_acceso,
					acc.ubicacion_id                                              AS ubicacion_id
				FROM accesos AS acc
					JOIN accesos_empleados AS ae ON acc.tipo_id = ae.id AND acc.tipo_modelo = :clase
					JOIN empleados AS e ON e.id = ae.empleado_id
					JOIN personas AS p ON p.id = e.persona_id";
		$params = [
			':clase'       => Acceso::getClassIndex(new self()),
			':tipo_acceso' => Acceso::EMPLEADO,
		];
		$where = [
			"p.borrado = 0",
			"acc.hora_egreso IS NULL",
		];
		if ($ubicacion_id > 0) {
			array_push($where, 'acc.ubicacion_id = :ubicacion_id');
			$params[':ubicacion_id'] = $ubicacion_id;
		}
		if ($fecha) {
			array_push($where, "DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') = :fecha");
			$params[':fecha'] = $fecha->format('d/m/Y');
		}
		if (count($where) > 0) {
			$sql .= "\nWHERE";
			for ($i = 0; $i < count($where); $i++) {
				if ($i < count($where) - 1) {
					$sql .= " {$where[$i]} AND";
				} else {
					$sql .= " {$where[$i]}";
				}
			}
		}
		$conex = new Conexiones();

		return $conex->consulta(Conexiones::SELECT, $sql . " LIMIT 0, 10", $params);
	}

	/**
	 * @return array|int|string
	 */
	public static function historico() {
		$conn = new Conexiones;
		$sql = "SELECT
					acc.ubicacion_id,
					e.nombre                                  AS ubicacion,
					edp.id_dependencia_principal              AS id_codep,
					d.nombre								  AS codep,
					p.documento,
					p.nombre                                  AS nombre,
					p.apellido                                AS apellido,
					DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') AS fecha_entrada,
					DATE_FORMAT(acc.hora_egreso, '%d/%m/%Y')  AS fecha_egreso,
					DATE_FORMAT(acc.hora_ingreso, '%H:%i')    AS hora_entrada,
					DATE_FORMAT(acc.hora_egreso, '%H:%i')     AS hora_egreso,
					acc.tipo_ingreso,
					acc.persona_id_ingreso,
					acc.tipo_egreso,
					acc.persona_id_egreso,
					CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, ''))   AS usuario_ingreso,
					CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso
				FROM accesos AS acc
					JOIN accesos_empleados AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = :clase
					JOIN ubicaciones AS e ON e.id = acc.ubicacion_id
					JOIN empleados AS cp ON cp.id = ac.empleado_id
					JOIN personas AS p ON p.id = cp.persona_id
					LEFT JOIN empleado_dependencia_principal edp ON ( edp.id_empleado = cp.id AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 ) 
					LEFT JOIN dependencias d ON (d.id = edp.id_dependencia_principal AND (ISNULL(d.fecha_hasta) OR d.fecha_hasta >= NOW()) )
					JOIN personas AS pin ON acc.persona_id_ingreso = pin.id
					LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id";
		$lista_historicos = $conn->consulta(Conexiones::SELECT, $sql, [
			':clase' => Acceso::getClassIndex(new self()),
		]);
		if (is_array($lista_historicos) && count($lista_historicos) > 0) {
			return $lista_historicos;
		}

		return [];
	}

	/**
	 * @param string    $documento
	 * @param Ubicacion $ubicacion
	 * @param \DateTime $fecha
	 * @return array
	 */
	public static function buscar($documento, $ubicacion, $fecha) {
		$sql = "SELECT
				a.id,
				a.hora_ingreso,
				a.persona_id_ingreso,
				a.tipo_ingreso,
				a.hora_egreso,
				a.persona_id_egreso,
				a.tipo_egreso,
				a.observaciones,
				a.ubicacion_id,
				e.id AS empleado_id,
				p.id AS persona_id,
				d.id AS direccion_id
			FROM personas AS p
				JOIN empleados AS e ON (e.persona_id = p.id AND p.borrado = 0 AND e.borrado = 0)
				LEFT JOIN empleado_dependencia_principal edp ON (edp.id_empleado = e.id AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 )
				LEFT JOIN dependencias AS d ON (d.id = edp.id_dependencia_principal AND (ISNULL(d.fecha_hasta) OR d.fecha_hasta >= NOW()) )
				LEFT JOIN (
					SELECT
						acc.id,
						acc.hora_ingreso,
						acc.persona_id_ingreso,
						acc.tipo_ingreso,
						acc.hora_egreso,
						acc.persona_id_egreso,
						acc.tipo_egreso,
						acc.observaciones,
						acc.ubicacion_id,
						ae.empleado_id AS empleado_id
					FROM
						accesos_empleados AS ae
						JOIN accesos AS acc ON (acc.tipo_id = ae.id AND acc.tipo_modelo = :clase)
					WHERE acc.hora_egreso IS NULL
					      AND DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') = :fecha
					      AND acc.ubicacion_id = :ubicacion_id
				GROUP BY acc.hora_ingreso
			) AS a ON a.empleado_id = e.id
			WHERE (p.documento LIKE CONCAT('%', :documento, '%'));";
		$params = [
			':clase'        => Acceso::getClassIndex(new self()),
			':documento'    => $documento,
			':fecha'        => $fecha->format('d/m/Y'),
			':ubicacion_id' => $ubicacion->id,
		];
		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, $params);
		$lista = [];
		if (isset($res[0])) {
			foreach ($res as &$re) {
				$re['empleado'] = Empleado::obtener($re['empleado_id']);
				$re['ubicacion'] = Ubicacion::obtener($re['ubicacion_id']);
				$lista[] = [
					'documento' => $re['empleado']->persona->documento,
					'registro'  => $re,
				];
			}
		}

		return $lista;
	}

	/**
	 * @return bool
	 * @throws \SimpleValidator\SimpleValidatorException
	 */
	public function alta() {
		if ($this->validar()) {
			$conex = new Conexiones();
			$sql = "INSERT INTO accesos_empleados (empleado_id) VALUE (:empleado_id);";
			$res = $conex->consulta(Conexiones::INSERT, $sql,
				[
					':empleado_id' => $this->empleado_id,
				]);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				$this->id = (int)$res;
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'acceso_empleados';
				Logger::event('alta', $datos);

				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 * @throws \SimpleValidator\SimpleValidatorException
	 */
	public function validar() {
		$rules = [
			'empleado_id' => ['required', 'numeric'],
		];
		$input_names = [
			'empleado_id' => "Empleado",
		];
		$validator = Validador::validate((array)$this, $rules, $input_names);
		if ($validator->isSuccess() == true) {
			return true;
		} else {
			$this->errores = $validator->getErrors();

			return false;
		}
	}

	public function baja() {
	}

	public function modificacion() {
	}

	/**
	 * @param int     $acceso_id
	 * @param Persona $persona_egreso
	 * @param int     $tipo_egreso
	 * @param \DateTime|string $hora_egreso
	 * @return bool
	 */
	public function terminar($acceso_id, $persona_egreso, $tipo_egreso, $hora_egreso = 'now', $observaciones='') {
		$sqlobservaciones = empty($observaciones) ? '' : ', acc.observaciones  = :observaciones';
		$sql = "UPDATE accesos AS acc
				SET acc.hora_egreso     = :hora_egreso,
					acc.persona_id_egreso = :persona_id_egreso,
					acc.tipo_egreso       = :tipo_egreso {$sqlobservaciones}
				WHERE acc.id = :acc_id AND acc.tipo_id = :id AND acc.tipo_modelo = :clase";
		$conex = new Conexiones();

		if(!is_a($hora_egreso, 'DateTime')){
			$hora_egreso = new \DateTime($hora_egreso);
		}

		$params = [
			':acc_id'            => $acceso_id,
			':id'                => $this->id,
			':clase'             => Acceso::getClassIndex(new self()),
			':persona_id_egreso' => $persona_egreso->id,
			':tipo_egreso'       => $tipo_egreso,
			':hora_egreso'       => $hora_egreso->format('Y-m-d H:i:s'),
		];

		if(!empty($observaciones)){
			$params[':observaciones'] = $observaciones;
		}
		
		$res = $conex->consulta(Conexiones::UPDATE, $sql, $params);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'acceso_empleados';
			Logger::event('fin_visita_empleado', $datos);

			return true;
		}

		return false;
	}

	/**
	 * @param Ubicacion $ubicacion
	 * @param Empleado  $empleado
	 * @param \DateTime $ingreso
	 * @param \DateTime $egreso
	 * @param Usuario   $usuario
	 * @param Acceso    $acceso
	 * @return array|bool
	 * @throws \SimpleValidator\SimpleValidatorException
	 * @internal param Acceso $acceso
	 */
	public static function validarManual($ubicacion, $empleado, $ingreso, $egreso, $usuario, $acceso = null) {
		$inputs = [
			'ubicacion_id' => $ubicacion->id,
			'empleado'     => $empleado,
			'ingreso'      => $ingreso,
			'egreso'       => $egreso,
			'usuario_id'   => $usuario->id,
			'persona_id_ingreso' => $usuario->getEmpleado()->persona_id,
        ];
        SituacionRevista::setAutenticacion();
		$rules = [
			'ubicacion_id' => ['required', 'numeric',],
			'empleado'     => ['empleado'    => function ($input) {
				return ($input instanceof Empleado) && !empty($input->id);
			}, 'puede_entrar(:ubicacion_id,:empleado)' => function ($input, $param1,$param2) {
				/** @var Empleado $input */
				$p = $input->puedeAcceder($param1,$param2);
				return $p;
			}, 'validacion_contrato' => function ($input) use ($usuario){
				if (in_array((int)$input->id_tipo_contrato, array_keys(SituacionRevista::listarParaSelect()))) {
					$resp = true;
				} else {
					$resp = false;
				}
				return $resp;
			}],
			'usuario_id'   => ['required', 'numeric',],
			'ingreso'      => ['fecha'],
			'egreso'       => ['fecha', 'despuesDe(:ingreso)'],
			'persona_id_ingreso'   => ['empleado_x_usuario', 'numeric',],
		];
		$naming = [
			'ubicacion_id' => "Ubicación",
			'empleado'     => "Empleado",
			'ingreso'      => "Ingreso",
			'egreso'       => "Egreso",
			'usuario_id'   => "Usuario",
			'persona_id_ingreso'   => "Usuario Logueado",
		];
		if ($acceso) {
			$inputs['acceso'] = $acceso->id;
			$rules['acceso'] = ['required', 'numeric'];
			$naming['acceso'] = 'Registro de Acceso';
		}
		$validator = Validador::validate($inputs, $rules, $naming);
		if ($validator->isSuccess() == true) {
			return true;
		}
		$validator->customErrors([
			'empleado'     => "El <strong>Empleado</strong> no es válido.",
			'puede_entrar' => "El <strong>Empleado</strong> no tiene permiso para acceder al <strong>Edificio</strong> seleccionado.",
			'validacion_contrato' => "El <strong>Empleado</strong> tiene un contrato para cual no tiene permisos su usuario.",
		]);

		return $validator->getErrors();
	}

	/**
	 * @param Ubicacion   $ubicacion
	 * @param Empleado    $empleado
	 * @param \DateTime   $ingreso
	 * @param \DateTime   $egreso
	 * @param Usuario     $usuario
	 * @param null|string $observaciones
	 * @return bool|array
	 * @throws \SimpleValidator\SimpleValidatorException
	 */
	public static function altaManual($ubicacion, $empleado, $ingreso, $egreso, Usuario $usuario, $observaciones = null) {
		$validator = static::validarManual($ubicacion, $empleado, $ingreso, $egreso, $usuario);
		if ($validator === true) {
			$sql = "INSERT INTO accesos_empleados(empleado_id) VALUE (:empleado_id);
INSERT INTO accesos(tipo_id, tipo_modelo, hora_ingreso, persona_id_ingreso, tipo_ingreso, hora_egreso, persona_id_egreso, tipo_egreso, observaciones, ubicacion_id) 
VALUE (LAST_INSERT_ID(), :clase, :hora_ingreso, :persona_id_ingreso, :tipo_ingreso, :hora_egreso, :persona_id_egreso, :tipo_egreso, :observaciones, :ubicacion_id)";
			$params = [
				":empleado_id"        => $empleado->id,
				':clase'              => Acceso::getClassIndex(new self()),
				":hora_ingreso"       =>($ingreso instanceof DateTime) ? $ingreso->format('Y-m-d H:i:s') : $ingreso,
				":persona_id_ingreso" => $usuario->getEmpleado()->persona_id,
				//TODO: Cambiar a constante Acceso::TIPO_INGRESO_OFFLINE al en merge con mejora_1219
				":tipo_ingreso"       => Acceso::TIPO_REGISTRO_OFFLINE,
				":hora_egreso"        => ($egreso instanceof DateTime) ? $egreso->format('Y-m-d H:i:s') : $egreso,
				":persona_id_egreso"  => $usuario->getEmpleado()->persona_id,
				//TODO: Cambiar a constante Acceso::TIPO_INGRESO_OFFLINE al en merge con mejora_1219
				":tipo_egreso"        => Acceso::TIPO_REGISTRO_OFFLINE,
				":observaciones"      => $observaciones,
				":ubicacion_id"       => $ubicacion->id,
			];
			$res = (new Conexiones())->consulta(Conexiones::INSERT, $sql, $params);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				$params['id'] = $res;
				$params['modelo'] = 'acceso_empleados';
				Logger::event('altaManual', $params);
				return true;
			}
		}

		return $validator;
	}

	/**
	 * @param Acceso    $acceso
	 * @param Ubicacion $ubicacion
	 * @param Empleado  $empleado
	 * @param \DateTime $ingreso
	 * @param \DateTime $egreso
	 * @param Usuario   $usuario
	 * @param null      $observaciones
	 * @return bool|array
	 * @throws \SimpleValidator\SimpleValidatorException
	 */
	public static function modificacionManual($acceso, $ubicacion, $empleado, $ingreso, $egreso, $usuario, $observaciones = null) {
		$validator = static::validarManual($ubicacion, $empleado, $ingreso, $egreso, $usuario);
		if ($validator === true) {
			$sql = "UPDATE accesos AS a
	JOIN accesos_empleados AS ae ON (a.tipo_id = ae.id AND a.tipo_modelo = :clase)
SET hora_ingreso       = :hora_ingreso,
	hora_egreso        = :hora_egreso,
	tipo_ingreso       = :tipo_ingreso,
	tipo_egreso        = :tipo_egreso,
	persona_id_ingreso = :persona_id_ingreso,
	persona_id_egreso  = :persona_id_egreso,
	observaciones      = :observaciones
WHERE ae.empleado_id = :empleado_id AND a.ubicacion_id = :ubicacion_id AND a.id = :acceso_id";
			$params = [
				':clase'              => Acceso::getClassIndex(new self()),
				":hora_ingreso"       => $ingreso->format('Y-m-d H:i:s'),
				":hora_egreso"        => $egreso->format('Y-m-d H:i:s'),
				":tipo_ingreso"       => Acceso::TIPO_REGISTRO_OFFLINE,
				":tipo_egreso"        => Acceso::TIPO_REGISTRO_OFFLINE,
				":persona_id_ingreso" => $usuario->getEmpleado()->persona->id,
				":persona_id_egreso"  => $usuario->getEmpleado()->persona->id,
				":observaciones"      => $observaciones,
				":empleado_id"        => $empleado->id,
				":ubicacion_id"       => $ubicacion->id,
				":acceso_id"          => $acceso->id,
			];
			$con = new Conexiones();
			$res = $con->consulta(Conexiones::INSERT, $sql, $params);
			if (is_numeric($res)) {
				return true;
			}
		}

		return $validator;
	}

	public static function ajax($order, $start, $length, $filtros, $extras = [], $count = false, $mis_horarios = false) {
		$con = new Conexiones();

		$mapeo_campos	= [
			'id'				=> 'acc.id',
			'ubicacion_id'		=> 'acc.ubicacion_id',
			'ubicacion'			=> 'e.nombre',
			'id_codep'			=> 'edp.id_dependencia_principal',
			'codep'				=> 'd.nombre',
			'documento'			=> 'p.documento',
			'nombre'			=> 'p.nombre',
			'apellido'			=> 'p.apellido',
			'fecha_entrada'		=> 'acc.hora_ingreso',
			'fecha_egreso'		=> 'acc.hora_egreso',
			'hora_entrada'		=> 'DATE_FORMAT(acc.hora_ingreso, "%H:%i")',
			'hora_egreso'		=> 'DATE_FORMAT(acc.hora_egreso, "%H:%i")',
			'tipo_ingreso'		=> 'acc.tipo_ingreso',
			'persona_id_ingreso'=> 'acc.persona_id_ingreso',
			'tipo_egreso'		=> 'acc.tipo_egreso',
			'persona_id_egreso'	=> 'acc.persona_id_egreso',
			'usuario_ingreso'	=> 'CONCAT(COALESCE(pin.nombre, ""), " ", COALESCE(pin.apellido, ""))',
			'usuario_egreso'	=> 'CONCAT(COALESCE(pout.nombre, ""), " ", COALESCE(pout.apellido, ""))',
			'observaciones'		=> 'acc.observaciones',
			'pertenencias'      => 'per.texto'
		];

		$select = <<<SQL
SELECT
	acc.id																AS id,
	acc.ubicacion_id,
	e.nombre                                                            AS ubicacion,
	edp.id_dependencia_principal 										AS id_codep,
	d.nombre                                                            AS codep,
	p.documento,
	p.nombre                                                            AS nombre,
	p.apellido                                                          AS apellido,
	DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y')                           AS fecha_entrada,
	DATE_FORMAT(acc.hora_egreso, '%d/%m/%Y')                            AS fecha_egreso,
	DATE_FORMAT(acc.hora_ingreso, '%H:%i')                              AS hora_entrada,
	DATE_FORMAT(acc.hora_egreso, '%H:%i')                               AS hora_egreso,
	acc.tipo_ingreso,
	acc.persona_id_ingreso,
	acc.tipo_egreso,
	acc.persona_id_egreso,
	CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, ''))   AS usuario_ingreso,
	CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso,
	acc.observaciones,
	'' as texto 
SQL;
	$filtro_empleado = ($mis_horarios) ? 'AND cp.id = '.$mis_horarios :'AND cp.id = 0'; 
	$from = <<<SQL
FROM accesos AS acc
	JOIN accesos_empleados AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = :clase
	JOIN ubicaciones AS e ON e.id = acc.ubicacion_id
	JOIN empleados AS cp ON cp.id = ac.empleado_id and cp.borrado=0 {$filtro_empleado}
	JOIN personas AS p ON p.id = cp.persona_id
	LEFT JOIN empleado_dependencia_principal edp ON (cp.id =edp.id_empleado AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 )
	LEFT JOIN dependencias d ON (d.id = edp.id_dependencia_principal AND (ISNULL(d.fecha_hasta) OR d.fecha_hasta >= NOW()) )
	JOIN personas AS pin ON pin.id = acc.persona_id_ingreso
	LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id

SQL;
	if(isset($extras['tipo_contrato'])) {
        $extras['tipo_contrato'] = implode(',', array_keys($extras['tipo_contrato']));
	$from .= <<<SQL
	LEFT JOIN empleado_contrato ec ON (cp.id = ec.id_empleado AND ISNULL(ec.fecha_hasta) AND ec.borrado = 0 AND ec.id_tipo_contrato IN ({$extras['tipo_contrato']}))
SQL;
	}

		$params[':clase'] = Acceso::getClassIndex(new static());
		$counter_query = "SELECT COUNT(DISTINCT acc.id) AS total {$from}";
		$recordsTotal = $recordsFiltered = $con->consulta(Conexiones::SELECT, $counter_query, $params)[0]['total'];
		$where = '';
		if ($filtros) {
			$filtro = [];
			foreach (explode(' ', $filtros) as $index => $value) {
				$filtro[] = <<<SQL
	(
		e.nombre LIKE :filtro{$index} OR
		d.nombre LIKE :filtro{$index} OR
		p.nombre LIKE :filtro{$index} OR
		p.apellido LIKE :filtro{$index} OR
		p.documento LIKE :filtro{$index} OR
		acc.hora_ingreso LIKE :filtro{$index} OR
		acc.tipo_ingreso LIKE :filtro{$index} OR
		acc.persona_id_ingreso LIKE :filtro{$index} OR
		acc.hora_egreso LIKE :filtro{$index} OR
		acc.tipo_egreso LIKE :filtro{$index} OR
		acc.persona_id_egreso LIKE :filtro{$index} OR
		acc.observaciones LIKE :filtro{$index} OR
		CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, '')) LIKE :filtro{$index} OR
		CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_egreso, '%d/%m/%Y') LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_ingreso, '%H:%i') LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_egreso, '%H:%i') LIKE :filtro{$index}
	)
SQL;
				$params[":filtro{$index}"] = "%{$value}%";
			}
			$where = " WHERE " . implode(' AND ', $filtro);
		}
		$extra_where = [];

		if (!empty($extras['ubicacion_id']) && (is_numeric($extras['ubicacion_id']))) {
			$extra_where[] = "(acc.ubicacion_id = :ubicacion_id)";
			$params[':ubicacion_id'] = $extras['ubicacion_id'];
		}
		if (!empty($extras['ubicacion_id']) 
			&& (is_array($extras['ubicacion_id']))) {
			
			$ubicaciones_id = [];			
			
			foreach ($extras['ubicacion_id'] as $ubicacion) {
				if($ubicacion instanceof Ubicacion){
					$ubicaciones_id[] = $ubicacion->id;
				} elseif($ubicacion > 0) {

					$ubicaciones_id[] = (int) $ubicacion;
				}
			}

			$ubicaciones_id = implode(',',$ubicaciones_id);

			if($ubicaciones_id != ''){					
					$extra_where[] = $ubicaciones_id != '' ? "acc.ubicacion_id in ($ubicaciones_id)" : '';
			}

		}
		if (!empty($extras['dependencias_autorizadas'])) {
			$dependencia= implode(',',$extras['dependencias_autorizadas']);
			$extra_where[] = "edp.id_dependencia_principal in ($dependencia)";
		}
		if (!empty($extras['codep'])) {
			$extra_where[] = "(edp.id_dependencia_principal = :codep)";
			$params[':codep'] = $extras['codep'];
		}
		if (!empty($extras['fecha_ini'])) {
			$extra_where[] = "(DATE(acc.hora_ingreso) >= STR_TO_DATE(:fecha_ini, '%d/%m/%Y'))";
			$params[':fecha_ini'] = $extras['fecha_ini'];
		}
		if (!empty($extras['fecha_fin'])) {
			$sin_cierre = ')';
			if ($extras['incluir_sin_cierre']) {
				$sin_cierre = "OR (DATE(acc.hora_ingreso) <= STR_TO_DATE(:fecha_fin, '%d/%m/%Y')) AND acc.hora_egreso IS NULL)";
			}
			$extra_where[] = "(DATE(acc.hora_egreso) <= STR_TO_DATE(:fecha_fin, '%d/%m/%Y')" . $sin_cierre;
			$params[':fecha_fin'] = $extras['fecha_fin'];
		}
		if (!empty($extra_where)) {
			if (empty($where)) {
				$where = " WHERE ";
			} else {
				$where .= ' AND ';
			}
			$where .= implode(' AND ', $extra_where);
			
		}

		if (!empty($filtros) || !empty($extra_where)) {

			$recordsFiltered = $con->consulta(Conexiones::SELECT, $counter_query . $where, $params)[0]['total'];
		}
		
		if ($count) {
			return $recordsFiltered;
		}
		$sql = $select . $from . $where;
		if ($order) {
			$orders			= [];
			foreach ($order as $i => $val) {
				if(!empty($val['campo']) && !empty($val['dir']))
					$orders[]	= "{$mapeo_campos[$val['campo']]} {$val['dir']}";
			}
			$sql 			.= $orders = " ORDER BY " . implode(',', $orders) . " ";
		}
		if ($start >= 0) {
			$limit = " LIMIT {$start}";
			if ($length) {
				$limit .= ", {$length}";
			}
			$sql .= $limit;
		}
		return [
			'recordsTotal'    => $recordsTotal,
			'recordsFiltered' => $recordsFiltered,
			'data'            => $con->consulta(Conexiones::SELECT, $sql, $params),
		];
	}

	public static function ajax_horas($order, $start, $length, $filtros, $extras = [], $count = false, $mis_horarios = false ,$orden_excel) {
		$con = new Conexiones();

		$mapeo_campos	= [
			'id'				=> 'acc.id',
			'ubicacion_id'		=> 'acc.ubicacion_id',
			'ubicacion'			=> 'e.nombre',
			'codep'				=> 'd.nombre',
			'documento'			=> 'p.documento',
			'cuit'				=> 'cp.cuit',
			'nombre'			=> 'p.nombre',
			'apellido'			=> 'p.apellido',
			'fecha_entrada'		=> 'acc.hora_ingreso',
			'fecha_egreso'		=> 'acc.hora_egreso',
			'hora_entrada'		=> 'DATE_FORMAT(acc.hora_ingreso, "%H:%i")',
			'hora_egreso'		=> 'DATE_FORMAT(acc.hora_egreso, "%H:%i")',
			'horas_trabajadas'	=> 'TIMEDIFF((acc.hora_egreso), (acc.hora_ingreso)) ',
			'tipo_ingreso'		=> 'acc.tipo_ingreso',
			'tipo_egreso'		=> 'acc.tipo_egreso',
			'observaciones'		=> 'acc.observaciones',
			'pertenencias'      => 'per.texto'
		];

		$select = <<<SQL
SELECT
	acc.id																AS id,
	acc.ubicacion_id,
	e.nombre                                                            AS ubicacion,
	d.nombre                                                            AS codep,
	p.documento,
	cp.cuit,
	p.nombre                                                            AS nombre,
	p.apellido                                                          AS apellido,
	DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y')                           AS fecha_entrada,
	DATE_FORMAT(acc.hora_egreso, '%d/%m/%Y')                            AS fecha_egreso,
	DATE_FORMAT(acc.hora_ingreso, '%H:%i')                              AS hora_entrada,
	DATE_FORMAT(acc.hora_egreso, '%H:%i')                               AS hora_egreso,
	TIMEDIFF((acc.hora_egreso), (acc.hora_ingreso)) 					AS horas_trabajadas,    
	acc.tipo_ingreso,
	acc.tipo_egreso,
	acc.observaciones,
	'' as texto 
SQL;
	$filtro_empleado = ($mis_horarios) ? 'AND cp.id = '.$mis_horarios :''; 
	$from = <<<SQL
FROM accesos AS acc
	JOIN accesos_empleados AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = :clase
	JOIN ubicaciones AS e ON e.id = acc.ubicacion_id
	JOIN empleados AS cp ON cp.id = ac.empleado_id and cp.borrado=0 {$filtro_empleado}
	JOIN personas AS p ON p.id = cp.persona_id
	LEFT JOIN empleado_dependencia_principal edp ON (cp.id =edp.id_empleado AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 )
	LEFT JOIN dependencias d ON (d.id = edp.id_dependencia_principal AND (ISNULL(d.fecha_hasta) OR d.fecha_hasta >= NOW()) )
	JOIN personas AS pin ON pin.id = acc.persona_id_ingreso
	LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id

SQL;
	if(isset($extras['tipo_contrato'])) {
        $extras['tipo_contrato'] = implode(',', array_keys($extras['tipo_contrato']));
	$from .= <<<SQL
	LEFT JOIN empleado_contrato ec ON (cp.id = ec.id_empleado AND ISNULL(ec.fecha_hasta) AND ec.borrado = 0 AND ec.id_tipo_contrato IN ({$extras['tipo_contrato']}))
SQL;
	}

		$params[':clase'] = Acceso::getClassIndex(new static());
		$counter_query = "SELECT COUNT(DISTINCT acc.id) AS total {$from}";
		$recordsTotal = $recordsFiltered = $con->consulta(Conexiones::SELECT, $counter_query, $params)[0]['total'];
		$where = '';
		if ($filtros) {
			$filtro = [];
			foreach (explode(' ', $filtros) as $index => $value) {
				$filtro[] = <<<SQL
	(
		e.nombre LIKE :filtro{$index} OR
		d.nombre LIKE :filtro{$index} OR
		p.nombre LIKE :filtro{$index} OR
		p.apellido LIKE :filtro{$index} OR
		p.documento LIKE :filtro{$index} OR
		cp.cuit LIKE :filtro{$index} OR
		acc.hora_ingreso LIKE :filtro{$index} OR
		acc.tipo_ingreso LIKE :filtro{$index} OR
		acc.hora_egreso LIKE :filtro{$index} OR
		acc.tipo_egreso LIKE :filtro{$index} OR
		acc.observaciones LIKE :filtro{$index} OR
		CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, '')) LIKE :filtro{$index} OR
		CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_egreso, '%d/%m/%Y') LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_ingreso, '%H:%i') LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_egreso, '%H:%i') LIKE :filtro{$index}
	)
SQL;
				$params[":filtro{$index}"] = "%{$value}%";
			}
			$where = " WHERE " . implode(' AND ', $filtro);
		}
		$extra_where = [];

		if (!empty($extras['ubicacion_id']) && (is_numeric($extras['ubicacion_id']))) {
			$extra_where[] = "(acc.ubicacion_id = :ubicacion_id)";
			$params[':ubicacion_id'] = $extras['ubicacion_id'];
		}
		if (!empty($extras['ubicacion_id']) 
			&& (is_array($extras['ubicacion_id']))) {
			
			$ubicaciones_id = [];			
			
			foreach ($extras['ubicacion_id'] as $ubicacion) {
				if($ubicacion instanceof Ubicacion){
					$ubicaciones_id[] = $ubicacion->id;
				} elseif($ubicacion > 0) {

					$ubicaciones_id[] = (int) $ubicacion;
				}
			}

			$ubicaciones_id = implode(',',$ubicaciones_id);

			if($ubicaciones_id != ''){					
					$extra_where[] = $ubicaciones_id != '' ? "acc.ubicacion_id in ($ubicaciones_id)" : '';
			}

		}
		if (!empty($extras['dependencias_autorizadas'])) {
			$dependencia= implode(',',$extras['dependencias_autorizadas']);
			$extra_where[] = "edp.id_dependencia_principal in ($dependencia)";
		}
		if (!empty($extras['codep'])) {
			$extra_where[] = "(edp.id_dependencia_principal = :codep)";
			$params[':codep'] = $extras['codep'];
		}
		if (!empty($extras['fecha_ini'])) {
			$extra_where[] = "(DATE(acc.hora_ingreso) >= STR_TO_DATE(:fecha_ini, '%d/%m/%Y'))";
			$params[':fecha_ini'] = $extras['fecha_ini'];
		}
		if (!empty($extras['fecha_fin'])) {
			$sin_cierre = ')';
			// if ($extras['incluir_sin_cierre']) {
			// 	$sin_cierre = "OR (DATE(acc.hora_ingreso) <= STR_TO_DATE(:fecha_fin, '%d/%m/%Y')) AND acc.hora_egreso IS NULL)";
			// }
			$extra_where[] = "(DATE(acc.hora_egreso) <= STR_TO_DATE(:fecha_fin, '%d/%m/%Y')" . $sin_cierre;
			$params[':fecha_fin'] = $extras['fecha_fin'];
		}
		if (!empty($extra_where)) {
			if (empty($where)) {
				$where = " WHERE ";
			} else {
				$where .= ' AND ';
			}
			$where .= implode(' AND ', $extra_where);
			
		}

		if (!empty($filtros) || !empty($extra_where)) {

			$recordsFiltered = $con->consulta(Conexiones::SELECT, $counter_query . $where, $params)[0]['total'];
		}
		
		if ($count) {
			return $recordsFiltered;
		}
		$sql = $select . $from . $where;

		if ($orden_excel){
			if ($order) {
			$orders			= [];
			foreach ($order as $i => $val) {
				if(!empty($val['campo']) && !empty($val['dir']))
					$orders[]	= "{$mapeo_campos[$val['campo']]} {$val['dir']}";
			}
			$sql 			.= $orders = " ORDER BY " . implode(',', $orders) . " ";			
			}
		}else{			
			$sql 			.= $orders = " ORDER BY codep,documento, hora_ingreso ";
		//$sql 			.= "GROUP BY cp.cuit, DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') ";
		}
		
		if ($start >= 0) {
			$limit = " LIMIT {$start}";
			if ($length) {
				$limit .= ", {$length}";
			}
			$sql .= $limit;
		}
		return [
			'recordsTotal'    => $recordsTotal,
			'recordsFiltered' => $recordsFiltered,
			'data'            => $con->consulta(Conexiones::SELECT, $sql, $params),
		];
	}

	public static function ajax_horas_agrupado($order, $start, $length, $filtros, $extras = [], $count = false, $mis_horarios = false) {
		$con = new Conexiones();

		$mapeo_campos	= [
			'id'				=> 'acc.id',
			'ubicacion_id'		=> 'acc.ubicacion_id',
			'ubicacion'			=> 'e.nombre',
			'codep'				=> 'd.nombre',
			'documento'			=> 'p.documento',
			'cuit'				=> 'cp.cuit',
			'nombre'			=> 'p.nombre',
			'apellido'			=> 'p.apellido',
			'fecha_entrada'		=> 'acc.hora_ingreso',
			'fecha_egreso'		=> 'acc.hora_egreso',
			'horas_trabajadas'	=> 'TIMEDIFF((acc.hora_egreso), (acc.hora_ingreso)) ',
			'observaciones'		=> 'acc.observaciones',
			'pertenencias'      => 'per.texto'
		];

		$select = <<<SQL
SELECT
	acc.id																AS id,
	acc.ubicacion_id,
	e.nombre                                                            AS ubicacion,
	d.nombre                                                            AS codep,
	p.documento,
	cp.cuit,
	p.nombre                                                            AS nombre,
	p.apellido                                                          AS apellido,
	DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y')                           AS fecha_entrada,
	SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF((acc.hora_egreso), (acc.hora_ingreso))))) 					AS horas_trabajadas,
	acc.observaciones,
	'' as texto 
SQL;
	$filtro_empleado = ($mis_horarios) ? 'AND cp.id = '.$mis_horarios :''; 
	$from = <<<SQL
FROM accesos AS acc
	JOIN accesos_empleados AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = :clase
	JOIN ubicaciones AS e ON e.id = acc.ubicacion_id
	JOIN empleados AS cp ON cp.id = ac.empleado_id and cp.borrado=0 {$filtro_empleado}
	JOIN personas AS p ON p.id = cp.persona_id
	LEFT JOIN empleado_dependencia_principal edp ON (cp.id =edp.id_empleado AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 )
	LEFT JOIN dependencias d ON (d.id = edp.id_dependencia_principal AND (ISNULL(d.fecha_hasta) OR d.fecha_hasta >= NOW()) )
	JOIN personas AS pin ON pin.id = acc.persona_id_ingreso
	LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
	-- LEFT JOIN (SELECT persona_id,GROUP_CONCAT(texto) as texto from pertenencias GROUP BY persona_id) per on per.persona_id = p.id linea comentada porque ralentiza la consulta

SQL;
	if(isset($extras['tipo_contrato'])) {
        $extras['tipo_contrato'] = implode(',', array_keys($extras['tipo_contrato']));
	$from .= <<<SQL
	LEFT JOIN empleado_contrato ec ON (cp.id = ec.id_empleado AND ISNULL(ec.fecha_hasta) AND ec.borrado = 0 AND ec.id_tipo_contrato IN ({$extras['tipo_contrato']}))
SQL;
	}

		$params[':clase'] = Acceso::getClassIndex(new static());
		$counter_query = "SELECT COUNT(DISTINCT acc.id) AS total {$from}";
		$recordsTotal = $recordsFiltered = $con->consulta(Conexiones::SELECT, $counter_query, $params)[0]['total'];
		$where = '';
		if ($filtros) {
			$filtro = [];
			foreach (explode(' ', $filtros) as $index => $value) {
				$filtro[] = <<<SQL
	(
		e.nombre LIKE :filtro{$index} OR
		d.nombre LIKE :filtro{$index} OR
		p.nombre LIKE :filtro{$index} OR
		p.apellido LIKE :filtro{$index} OR
		p.documento LIKE :filtro{$index} OR
		cp.cuit LIKE :filtro{$index} OR
		acc.hora_ingreso LIKE :filtro{$index} OR
		acc.hora_egreso LIKE :filtro{$index} OR
		acc.observaciones LIKE :filtro{$index} OR
		CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, '')) LIKE :filtro{$index} OR
		CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_egreso, '%d/%m/%Y') LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_ingreso, '%H:%i') LIKE :filtro{$index} OR
		DATE_FORMAT(acc.hora_egreso, '%H:%i') LIKE :filtro{$index}
	)
SQL;
				$params[":filtro{$index}"] = "%{$value}%";
			}
			$where = " WHERE " . implode(' AND ', $filtro);
		}
		$extra_where = [];

		if (!empty($extras['ubicacion_id']) && (is_numeric($extras['ubicacion_id']))) {
			$extra_where[] = "(acc.ubicacion_id = :ubicacion_id)";
			$params[':ubicacion_id'] = $extras['ubicacion_id'];
		}
		if (!empty($extras['ubicacion_id']) 
			&& (is_array($extras['ubicacion_id']))) {
			
			$ubicaciones_id = [];			
			
			foreach ($extras['ubicacion_id'] as $ubicacion) {
				if($ubicacion instanceof Ubicacion){
					$ubicaciones_id[] = $ubicacion->id;
				} elseif($ubicacion > 0) {

					$ubicaciones_id[] = (int) $ubicacion;
				}
			}

			$ubicaciones_id = implode(',',$ubicaciones_id);

			if($ubicaciones_id != ''){					
					$extra_where[] = $ubicaciones_id != '' ? "acc.ubicacion_id in ($ubicaciones_id)" : '';
			}

		}
		if (!empty($extras['dependencias_autorizadas'])) {
			$dependencia= implode(',',$extras['dependencias_autorizadas']);
			$extra_where[] = "edp.id_dependencia_principal in ($dependencia)";
		}
		if (!empty($extras['codep'])) {
			$extra_where[] = "(edp.id_dependencia_principal = :codep)";
			$params[':codep'] = $extras['codep'];
		}
		if (!empty($extras['fecha_ini'])) {
			$extra_where[] = "(DATE(acc.hora_ingreso) >= STR_TO_DATE(:fecha_ini, '%d/%m/%Y'))";
			$params[':fecha_ini'] = $extras['fecha_ini'];
		}
		if (!empty($extras['fecha_fin'])) {
			$sin_cierre = ')';
			$extra_where[] = "(DATE(acc.hora_egreso) <= STR_TO_DATE(:fecha_fin, '%d/%m/%Y')" . $sin_cierre;
			$params[':fecha_fin'] = $extras['fecha_fin'];
		}
		if (!empty($extra_where)) {
			if (empty($where)) {
				$where = " WHERE ";
			} else {
				$where .= ' AND ';
			}
			$where .= implode(' AND ', $extra_where);
			
		}

		if (!empty($filtros) || !empty($extra_where)) {

			$recordsFiltered = $con->consulta(Conexiones::SELECT, $counter_query . $where, $params)[0]['total'];
		}
		
		if ($count) {
			return $recordsFiltered;
		}
		$sql = $select . $from . $where;
		$sql 			.= "GROUP BY cp.cuit, DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') ";
		$sql 			.= $orders = " ORDER BY codep,documento, hora_ingreso ";
		if ($start >= 0) {
			$limit = " LIMIT {$start}";
			if ($length) {
				$limit .= ", {$length}";
			}
			$sql .= $limit;
		}
		return [
			'recordsTotal'    => $recordsTotal,
			'recordsFiltered' => $recordsFiltered,
			'data'            => $con->consulta(Conexiones::SELECT, $sql, $params),
		];
	}



	

	/**
	 * @param Ubicacion $ubicacion
	 * @param Direccion $direccion
	 * @param string    $search
	 * @param \DateTime $fecha_ini
	 * @param \DateTime $fecha_fin
	 * @param bool      $incluir_sin_cierre
	 * @return int
	 */
	public static function contarRegistros($ubicacion, $direccion, $search = null, $fecha_ini = null, $fecha_fin = null, $incluir_sin_cierre = false) {
		$extras = [
			'ubicacion_id'       => !empty($ubicacion) ? $ubicacion : null,
			'dependencias_autorizadas'              => !empty($direccion) ? $direccion: null,
			'fecha_ini'          => $fecha_ini ? $fecha_ini->format('d/m/Y') : null,
			'fecha_fin'          => $fecha_fin ? $fecha_fin->format('d/m/Y') : null,
			'incluir_sin_cierre' => $incluir_sin_cierre,
		];

		return static::ajax(null, null, null, $search, $extras, true);
	}

	/**
	 * @param int       $start
	 * @param int       $offset
	 * @param Ubicacion $ubicacion
	 * @param Direccion $direccion
	 * @param string    $search
	 * @param \DateTime $fecha_ini
	 * @param \DateTime $fecha_fin
	 * @param bool      $sin_cierre
	 * @return array
	 */
	public static function dataParaExcel($start, $offset, $ubicacion, $direccion, $search = null, $fecha_ini = null, $fecha_fin = null, $sin_cierre = false) {
		
		$extras = [

			'ubicacion_id'       => !empty($ubicacion) ? $ubicacion : null,
			'dependencias_autorizadas'              => !empty($direccion) ? $direccion: null,
			'fecha_ini'          => $fecha_ini ? $fecha_ini->format('d/m/Y') : null,
			'fecha_fin'          => $fecha_fin ? $fecha_fin->format('d/m/Y') : null,
			'incluir_sin_cierre' => $sin_cierre,
		];
        // $atributos_select = AppRoles::obtener_atributos_select(); 
        SituacionRevista::setAutenticacion();
		$atributos_select = SituacionRevista::listarParaSelect();
		// if(count($atributos_select) == 1 && $atributos_select[0] == Empleado::AT) {
		// 	$extras['tipo_contrato'] = $atributos_select[0];
		// }
        $extras['tipo_contrato'] = $atributos_select;

		return static::ajax(null, $start, $offset, $search, $extras, false)['data'];
	}

	/**
	 * @param int       $start
	 * @param int       $offset
	 * @param Ubicacion $ubicacion
	 * @param Direccion $direccion
	 * @param string    $search
	 * @param \DateTime $fecha_ini
	 * @param \DateTime $fecha_fin
	 * @param bool      $sin_cierre
	 * @return array
	 */
	public static function dataParaExcelHoras($start, $offset, $ubicacion, $direccion, $search = null, $fecha_ini = null, $fecha_fin = null, $sin_cierre = false) {
		
		$extras = [

			'ubicacion_id'       => !empty($ubicacion) ? $ubicacion : null,
			'dependencias_autorizadas'              => !empty($direccion) ? $direccion: null,
			'fecha_ini'          => $fecha_ini ? $fecha_ini->format('d/m/Y') : null,
			'fecha_fin'          => $fecha_fin ? $fecha_fin->format('d/m/Y') : null,
			'incluir_sin_cierre' => $sin_cierre,
		];
        SituacionRevista::setAutenticacion();
		$atributos_select = SituacionRevista::listarParaSelect();
		// }
        $extras['tipo_contrato'] = $atributos_select;

		return static::ajax_horas(null, $start, $offset, $search, $extras, false,false,false)['data'];
	}

	/**
	 * @param int       $start
	 * @param int       $offset
	 * @param Ubicacion $ubicacion
	 * @param Direccion $direccion
	 * @param string    $search
	 * @param \DateTime $fecha_ini
	 * @param \DateTime $fecha_fin
	 * @param bool      $sin_cierre
	 * @return array
	 */
	public static function dataParaExcelHorasAgrup($start, $offset, $ubicacion, $direccion, $search = null, $fecha_ini = null, $fecha_fin = null, $sin_cierre = false) {
		
		$extras = [

			'ubicacion_id'       => !empty($ubicacion) ? $ubicacion : null,
			'dependencias_autorizadas'              => !empty($direccion) ? $direccion: null,
			'fecha_ini'          => $fecha_ini ? $fecha_ini->format('d/m/Y') : null,
			'fecha_fin'          => $fecha_fin ? $fecha_fin->format('d/m/Y') : null,
			'incluir_sin_cierre' => $sin_cierre,
		];
        SituacionRevista::setAutenticacion();
		$atributos_select = SituacionRevista::listarParaSelect();
        $extras['tipo_contrato'] = $atributos_select;

		return static::ajax_horas_agrupado(null, $start, $offset, $search, $extras, false)['data'];
	}

/**
 * Listar los empleados que coincidan con los filtros establecidos.
 *
 * Presentes: Deben tener **hora_ingreso** en **accesos**.
 * Ausentes: No deben tener **hora_ingreso** en **accesos**, ni novedes de la fecha consultada.
 * Novedades: No deben tener **hora_ingreso** en **accesos**, pero vinculacion conlas novedes.
 *
 * @param      int  	$dependencia    dependencia
 * @param      DateTime $fecha			Año mes dia de los registros solicitados
 * @param      array  	$contrato_tipo  contrato tipo, viene como informacion por POST cuyo indice es numerico, lo importante es el valor.
 * @param      int  	$filtro_estado  Una de las siguientes opciones: self::PRESENTES, self::AUSENTES, self::AUSENTES_NOVEDADES
 *
 * @return     array
 */
	public static function detalle_listar_calendario($dependencia=null, $fecha=null, $contrato_tipo=null, $filtro_estado=null){
		if(empty($contrato_tipo) || empty($fecha) || empty($dependencia)){
			return [];
		}
		$params	= [
			':dep'	=> (int)$dependencia,
			':date'	=> $fecha->format('Y-m-d'),
		];
		$filtro_tipo_contrato	= '';
		if(is_array($contrato_tipo)){
			$filtro_tipo_contrato = " AND ec.id_tipo_contrato in (:contrato_tipo) ";
			$params[':contrato_tipo'] = $contrato_tipo; 
		}
 
		$sql_presentes	= <<<SQL
			SELECT
				e.id	AS empleado_id,
				ec.id_tipo_contrato AS contrato_id,
				null AS id_tipo_novedad,
				e.cuit,
				e.planilla_reloj,
				p.nombre,
				p.apellido,
				p.documento,
				date_format(a.hora_ingreso, '%Y-%m-%d') AS fecha_ingreso,
				a.id	AS acceso_id
			FROM empleados AS e
				INNER JOIN empleado_dependencia_principal AS dp
					ON ( dp.id_empleado = e.id AND ISNULL( dp.fecha_hasta) AND dp.borrado = 0 AND e.borrado = 0 AND dp.id_dependencia_principal = :dep)
				INNER JOIN empleado_contrato ec
					ON ( ec.id_empleado = e.id AND ec.borrado = 0 {$filtro_tipo_contrato}  AND (
						(:date BETWEEN date_format(ec.fecha_desde, '%Y-%m-%d') AND date_format(ec.fecha_hasta, '%Y-%m-%d')) 
						OR
						(date_format(ec.fecha_desde, '%Y-%m-%d') <= :date AND ISNULL(ec.fecha_hasta)))
					)
				INNER JOIN personas AS p
					ON (e.persona_id = p.id AND p.borrado = 0)
				INNER JOIN accesos_empleados ae ON (e.id = ae.empleado_id )
				INNER JOIN accesos a ON (ae.id = a.tipo_id AND a.tipo_modelo = 1 AND :date = date_format(a.hora_ingreso, '%Y-%m-%d'))
			WHERE
				e.borrado = 0 AND
				e.planilla_reloj = 1
			GROUP BY e.id
			ORDER BY date_format(a.hora_ingreso, '%Y-%m-%d') ASC, e.id ASC
SQL;

		$sql_ausentes	= <<<SQL
			SELECT
				e.id	AS empleado_id,
				ec.id_tipo_contrato AS contrato_id,
				null AS id_tipo_novedad,
				e.cuit,
				e.planilla_reloj,
				p.nombre,
				p.apellido,
				p.documento,
				null	AS fecha_ingreso,
				null	AS acceso_id
			FROM empleados AS e
				INNER JOIN empleado_dependencia_principal AS dp
					ON ( dp.id_empleado = e.id AND ISNULL( dp.fecha_hasta) AND dp.borrado = 0 AND dp.id_dependencia_principal = :dep)
				INNER JOIN empleado_contrato ec ON ( ec.id_empleado = e.id AND ec.borrado = 0 {$filtro_tipo_contrato} AND (
						((:date BETWEEN date_format(ec.fecha_desde, '%Y-%m-%d') AND date_format(ec.fecha_hasta, '%Y-%m-%d')) AND NOT (date_format(ec.fecha_desde, '%Y-%m-%d') <= :date AND ISNULL(ec.fecha_hasta))) 
						OR
						((date_format(ec.fecha_desde, '%Y-%m-%d') <= :date AND ISNULL(ec.fecha_hasta)) AND NOT (:date BETWEEN date_format(ec.fecha_desde, '%Y-%m-%d') AND date_format(ec.fecha_hasta, '%Y-%m-%d')))
				))

				INNER JOIN personas AS p
					ON (e.persona_id = p.id AND p.borrado = 0)
				LEFT JOIN (
					SELECT
						DISTINCT ae.empleado_id,
						a.id
					FROM accesos_empleados AS ae
						INNER JOIN accesos a ON (ae.id = a.tipo_id AND a.tipo_modelo = 1 AND :date = date_format(a.hora_ingreso, '%Y-%m-%d'))
					GROUP BY ae.empleado_id
				) AS acceso ON (acceso.empleado_id = e.id)
			WHERE
				e.borrado = 0 AND
				e.planilla_reloj = 1 AND
				ISNULL(acceso.id)
			GROUP BY e.id
SQL;

		$sql_novedades = <<<SQL
				SELECT
					e.id	AS empleado_id,
					ec.id_tipo_contrato AS contrato_id,
					nov.id_tipo_novedad	AS id_tipo_novedad,
					e.cuit,
					e.planilla_reloj,
					p.nombre,
					p.apellido,
					p.documento,
					null AS fecha_ingreso,
					null AS acceso_id
				FROM empleados AS e
					INNER JOIN empleado_dependencia_principal AS dp
						ON ( dp.id_empleado = e.id AND ISNULL( dp.fecha_hasta) AND dp.borrado = 0 AND dp.id_dependencia_principal = :dep)
					INNER JOIN empleado_contrato ec
					ON ( ec.id_empleado = e.id AND ec.borrado = 0 {$filtro_tipo_contrato} AND (
							(( :date BETWEEN date_format(ec.fecha_desde, '%Y-%m-%d') AND date_format(ec.fecha_hasta, '%Y-%m-%d') ) AND NOT (date_format(ec.fecha_desde, '%Y-%m-%d') <= :date AND ISNULL(ec.fecha_hasta) ))
							OR
							((date_format(ec.fecha_desde, '%Y-%m-%d') <= :date AND ISNULL(ec.fecha_hasta) ) AND NOT ( :date BETWEEN date_format(ec.fecha_desde, '%Y-%m-%d') AND date_format(ec.fecha_hasta, '%Y-%m-%d') ))
						) )
					INNER JOIN personas AS p
						ON (e.persona_id = p.id AND p.borrado = 0)
					INNER JOIN novedades AS nov
						ON (nov.id_empleado = e.id AND :date BETWEEN date_format(nov.fecha_desde, '%Y-%m-%d') AND date_format(nov.fecha_hasta, '%Y-%m-%d') AND nov.borrado = 0)
				WHERE
					e.planilla_reloj = 1
				ORDER BY e.id
SQL;

		$sql_todos	= <<<SQL
			SELECT
				todo.empleado_id,
				todo.contrato_id,
				todo.id_tipo_novedad,
				todo.cuit,
				todo.planilla_reloj,
				todo.nombre,
				todo.apellido,
				todo.documento,
				todo.fecha_ingreso,
				todo.acceso_id
			FROM (({$sql_presentes}) UNION  ({$sql_novedades}) UNION ({$sql_ausentes}) ) AS todo
			GROUP BY todo.empleado_id
			ORDER BY todo.empleado_id DESC
SQL;

		$return	= false;
		switch ($filtro_estado) {
			case AccesoEmpleado::PRESENTES:
				$return	= (new Conexiones())->consulta(Conexiones::SELECT, $sql_presentes, $params);
			break;
			case AccesoEmpleado::AUSENTES:
				$return	= (new Conexiones())->consulta(Conexiones::SELECT, $sql_ausentes, $params);
			break;
			case AccesoEmpleado::AUSENTES_NOVEDADES:
				$return	= (new Conexiones())->consulta(Conexiones::SELECT, $sql_novedades, $params);
			break;
			case AccesoEmpleado::PRESENTES_AUSENTES_NOVEDADES:
				$return	= (new Conexiones())->consulta(Conexiones::SELECT, $sql_todos, $params);
			break;
			}
		return empty($return) ? [] : $return;
	}
/**
 * Metodo que busca la cantidad de empleados ausentes, presentes, y con novedades por dia de un mes desterminado. 
 * Si el dia no tiene contenido carga por default los campos en 0.
 * Si el dia no es habil (feriado y fin de semana) no muestra contenido a menos de que existan datos.
 *
 * @param      int  $dependencia    dependencia
 * @param      DateTime  $fecha
 * @param      array  $contrato_tipo  contrato tipo
 *
 * @return     array - Indice string (fecha con formato sistema "Y-m-d")
 */
	public static function listar_calendario($dependencia=null, $fecha=null, $contrato_tipo=null){
		if(empty($contrato_tipo) || empty($fecha) || empty($dependencia)){
			return [];
		}
		$params					= [
			':dep'	=> $dependencia,
			':date'	=> $fecha->format('Y-m'),
		];
		$filtro_tipo_contrato	= '';
		if(is_array($contrato_tipo)){
			$filtro_tipo_contrato = " AND ec.id_tipo_contrato in (:contrato_tipo) ";
			$params[':contrato_tipo'] = $contrato_tipo; 
		}

		$total_empleados		= <<<SQL
			SELECT
				count(DISTINCT e.id) AS total_empleados,
				date_format(ec.fecha_desde,'%Y-%m-%d') as fecha_desde,
				date_format(ec.fecha_hasta,'%Y-%m-%d') as fecha_hasta
			FROM empleados AS e
				INNER JOIN empleado_dependencia_principal AS dp
					ON ( dp.id_empleado = e.id AND ISNULL( dp.fecha_hasta) AND dp.borrado = 0 AND dp.id_dependencia_principal = :dep)
				INNER JOIN empleado_contrato ec
					ON ( ec.id_empleado = e.id AND ec.borrado = 0 {$filtro_tipo_contrato}  AND (
							((:date BETWEEN date_format(ec.fecha_desde, '%Y-%m') AND date_format(ec.fecha_hasta, '%Y-%m')) AND NOT ( date_format(ec.fecha_desde, '%Y-%m') <= :date AND ISNULL(ec.fecha_hasta)) )
						OR  
							(( date_format(ec.fecha_desde, '%Y-%m') <= :date AND ISNULL(ec.fecha_hasta)) AND NOT (:date BETWEEN date_format(ec.fecha_desde, '%Y-%m') AND date_format(ec.fecha_hasta, '%Y-%m')))
					))
			WHERE
				e.borrado = 0 AND
				e.planilla_reloj = 1
			GROUP BY date_format(ec.fecha_desde,'%Y-%m-%d'), date_format(ec.fecha_hasta,'%Y-%m-%d')
			ORDER BY e.id DESC
SQL;
		$total_empleados		= (new Conexiones())->consulta(Conexiones::SELECT, $total_empleados, $params);

		$sql_presentes_x_dia	= <<<SQL
			SELECT
				COUNT(DISTINCT e.id) AS total_presentes,
				date_format(a.hora_ingreso, '%Y-%m-%d') as fecha_ingreso
			FROM empleados AS e
				INNER JOIN empleado_dependencia_principal AS dp
					ON ( dp.id_empleado = e.id AND ISNULL( dp.fecha_hasta) AND dp.borrado = 0 AND e.borrado = 0 AND dp.id_dependencia_principal = :dep)
				INNER JOIN empleado_contrato ec
					ON ( ec.id_empleado = e.id AND ec.borrado = 0 {$filtro_tipo_contrato}  AND (
						((:date BETWEEN date_format(ec.fecha_desde, '%Y-%m') AND date_format(ec.fecha_hasta, '%Y-%m')) AND NOT (date_format(ec.fecha_desde, '%Y-%m') <= :date AND ISNULL(ec.fecha_hasta))) 
						OR
						((date_format(ec.fecha_desde, '%Y-%m') <= :date AND ISNULL(ec.fecha_hasta)) AND NOT (:date BETWEEN date_format(ec.fecha_desde, '%Y-%m') AND date_format(ec.fecha_hasta, '%Y-%m')))
					) )
				INNER JOIN accesos_empleados ae ON (e.id = ae.empleado_id )
				INNER JOIN accesos a ON (ae.id = a.tipo_id AND a.tipo_modelo = 1 AND :date = date_format(a.hora_ingreso, '%Y-%m'))
			WHERE
				e.borrado = 0 AND
				e.planilla_reloj = 1
			GROUP BY date_format(a.hora_ingreso, '%Y-%m-%d')
			ORDER BY date_format(a.hora_ingreso, '%Y-%m-%d') ASC, e.id ASC
SQL;
		$sql_presentes_x_dia	= (new Conexiones())->consulta(Conexiones::SELECT, $sql_presentes_x_dia, $params);

		$total_novedades		= <<<SQL
			SELECT
				count(DISTINCT nov.id) AS total_novedades
				,count( DISTINCT e.id) AS total_empleados
				,date_format(nov.fecha_desde, '%Y-%m-%d') as fecha_desde
				,date_format(nov.fecha_hasta, '%Y-%m-%d') as fecha_hasta
			FROM empleados AS e
				INNER JOIN empleado_dependencia_principal AS dp
					ON ( dp.id_empleado = e.id AND ISNULL( dp.fecha_hasta) AND dp.borrado = 0 AND dp.id_dependencia_principal = :dep)
				INNER JOIN empleado_contrato ec
					ON ( ec.id_empleado = e.id AND ec.borrado = 0 {$filtro_tipo_contrato} AND (
							(( :date BETWEEN date_format(ec.fecha_desde, '%Y-%m') AND date_format(ec.fecha_hasta, '%Y-%m') ) AND NOT (date_format(ec.fecha_desde, '%Y-%m') <= :date AND ISNULL(ec.fecha_hasta) ))
							OR
							((date_format(ec.fecha_desde, '%Y-%m') <= :date AND ISNULL(ec.fecha_hasta) ) AND NOT ( :date BETWEEN date_format(ec.fecha_desde, '%Y-%m') AND date_format(ec.fecha_hasta, '%Y-%m') ))
						) )
				INNER JOIN novedades AS nov
					ON (nov.id_empleado = e.id AND :date BETWEEN date_format(nov.fecha_desde, '%Y-%m') AND date_format(nov.fecha_hasta, '%Y-%m'))
			WHERE
				e.planilla_reloj = 1 AND nov.borrado = 0
			GROUP BY date_format(nov.fecha_desde, '%Y-%m-%d'), date_format(nov.fecha_hasta, '%Y-%m-%d')
			ORDER BY  date_format(nov.fecha_desde, '%Y-%m-%d') ASC
SQL;
		$total_novedades		= (new Conexiones())->consulta(Conexiones::SELECT, $total_novedades, $params);

		$nueva_lista				= [];
		$function_calculo_totales	= function($fecha=null, &$total_empleados=null, $campo='total_empleados'){
			$total		= 0;
			$referencia	= !empty($referencia = \DateTime::createFromFormat('Y-m-d', $fecha))
						? $referencia->format('U')
						: null;
			$fecha_hasta_anti_repeticion = null;
			foreach ($total_empleados as $res) {
				$fecha_desde	= !empty($fecha_desde = \DateTime::createFromFormat('Y-m-d', $res['fecha_desde']))
								? $fecha_desde->format('U') : null;
				$fecha_hasta	= !empty($fecha_hasta = \DateTime::createFromFormat('Y-m-d', $res['fecha_hasta']))
								? $fecha_hasta->format('U') : null;
				if(
					($referencia >= $fecha_desde)
					&& ($referencia <= $fecha_hasta || $fecha_hasta === null)
				){
					if($fecha_hasta_anti_repeticion == $fecha_desde){
						continue;
					} else {
						$fecha_hasta_anti_repeticion = $fecha_hasta;
					}
					$total	= $total + $res[$campo];
				}
			}
			return $total;
		};
		
		foreach((array)$sql_presentes_x_dia as &$pres) {
			$nueva_lista[ $pres['fecha_ingreso'] ]	= [
				'presentes'			=> $pres['total_presentes'],
				'ausentes'			=> abs((int)$pres['total_presentes'] - $function_calculo_totales($pres['fecha_ingreso'], $total_empleados, 'total_empleados')),
				'novedades'			=> 0,
			];
		}
		$nueva_lista	= array_merge(static::contenido_default_calendario($fecha, [
			'presentes'	=> '0',
			'ausentes'	=> '0',
			'novedades'	=> '0',
		]), $nueva_lista);
 
		foreach($nueva_lista as $date => &$calen) {
			$nueva_lista[$date]['novedades'] = abs($function_calculo_totales($date, $total_novedades, 'total_novedades'));
			if($calen['presentes'] == '0' && $calen['ausentes'] == '0')
				$nueva_lista[$date]['ausentes']	= (string)abs($function_calculo_totales($date, $total_empleados, 'total_empleados'));
		}

		return $nueva_lista;
	}

/**
 * Genera contenido default para todos los dias habiles de un mes determinado. (se ignoran los feriados y fin de semanas).
 * Usado principalmente en el metodo self::listar_calendario() pero se puede reciclar.
 *
 * Tener en cuenta que el dia del mes debere empezar en 01, de lo contrario el conteo arrancara en la fecha local del sistema que ejecute el codigo.
 * Con **$valor_default** se pueden pasar los parametros que se desean usar por default por cada dia.
 *
 * @param DateTime|string 	$fecha
 * @param array 			$valor_default
 * @return array - Indice string (fecha con formato sistema "Y-m-d")
*/
	static public function contenido_default_calendario($fecha=null, $valor_default=null){
		if(is_string($fecha) && !($fecha instanceof DateTime)){
			$fecha	= \DateTime::createFromFormat('Y-m-d H:i:s', $fecha . '00:00:00');
		} else {
			$fecha	= \DateTime::createFromFormat('Y-m-d H:i:s', ($fecha->format('Y-m') . '-01 00:00:00'));
		}

		if(empty($valor_default)) {
			$valor_default = [
				'presentes'	=> "0",
				'ausentes'	=> "0",
				'novedades'	=> "0",
			];
		}
		$calendar	= [];
		$_mes = $fecha->format('m');
		while($fecha->format('m') == $_mes) {
			if(\FMT\Informacion_fecha::es_habil($fecha)) {
				$calendar[$fecha->format('Y-m-d')]	= $valor_default;
			}
			$fecha->add(new \DateInterval('P1D'));
		}
		return $calendar;
	}

/**
 * Genera datos para el PDF de Planilla Única Reloj
 *
 * @param int $dependencia		- ID de dependencia
 * @param string $fecha			- string con formato 'Y-m-d'
 * @param array $contrato_tipo - IDs con los tipos de Situaciones de Revista. Viene directo del POST Form
 * @return array
 */
	static public function listar_unico_reloj($dependencia=null, $fecha=null, $contrato_tipo=null) {
		$conex					= new Conexiones();
		$filtro_tipo_contrato	= '';
		$params					= [
			':dep'	=> $dependencia,
			':date'	=> $fecha,
		];
		$situacion_revista = SituacionRevista::idsReporte($contrato_tipo);
		$contratos = implode(',', $situacion_revista);
		$filtro_tipo_contrato = ' ec.id_tipo_contrato in ('.$contratos.')'; 
		
    
		$sql = <<<SQL
			SELECT * FROM
			(
				(
					SELECT
						CONCAT(p.apellido,' ',p.nombre) AS nombre_apellido,
						e.id,
						e.cuit,
						date_format(min(a.hora_ingreso), '%H:%i') AS hora_ingreso,
						date_format(max(hora_egreso), '%H:%i') AS hora_egreso,
						ec.id_tipo_contrato AS tipo_contrato,
						a.tipo_ingreso
					FROM empleados e
						INNER JOIN empleado_dependencia_principal dp ON (dp.id_empleado = e.id AND ISNULL(dp.fecha_hasta) AND dp.borrado = 0 AND dp.id_dependencia_principal IN(:dep))
						INNER JOIN personas p ON p.id = e.persona_id
						LEFT JOIN empleado_contrato ec ON (e.id = ec.id_empleado AND (:date between ec.fecha_desde AND ec.fecha_hasta OR ec.fecha_desde <= :date and isnull(ec.fecha_hasta)) AND ec.borrado = 0) 
						LEFT JOIN accesos_empleados ae ON ae.empleado_id = e.id
						LEFT JOIN accesos a ON ae.id = a.tipo_id AND a.tipo_modelo = 1
					WHERE
						date_format(hora_ingreso, '%Y-%m-%d') = :date
						AND $filtro_tipo_contrato
						AND e.planilla_reloj = 1
						AND e.borrado = 0
						AND p.borrado = 0
					GROUP BY e.id
					ORDER BY hora_ingreso,tipo_contrato,nombre_apellido
				) UNION (
					SELECT
						CONCAT(p.apellido,' ',p.nombre) AS nombre_apellido,
						e.id,
						e.cuit,
						null AS hora_ingreso,
						null AS hora_egreso,
						ec.id_tipo_contrato AS tipo_contrato,
						'' as tipo_ingreso
					FROM empleados e
						INNER JOIN empleado_dependencia_principal dp ON (dp.id_empleado = e.id AND ISNULL(dp.fecha_hasta) AND dp.borrado = 0 AND dp.id_dependencia_principal IN (:dep))
						INNER JOIN personas p ON p.id = e.persona_id
						LEFT JOIN empleado_contrato ec ON (e.id = ec.id_empleado AND (:date between ec.fecha_desde AND ec.fecha_hasta OR ec.fecha_desde <= :date and isnull(ec.fecha_hasta)) AND ec.borrado = 0)
					WHERE
						$filtro_tipo_contrato
						AND e.planilla_reloj = 1
						AND e.borrado = 0
						AND p.borrado = 0
				)
			) as todo
			GROUP BY todo.id
			ORDER BY CASE WHEN hora_ingreso IS NULL THEN 0 ELSE 1 END DESC, hora_ingreso ASC, nombre_apellido
SQL;

		$lista			= $conex->consulta(Conexiones::SELECT, $sql, $params);
		$nueva_lista	= [];
		$lista			= (!empty($lista)) ? $lista : [];

		foreach ($lista as $key => $value) {
			//INTEGRACION V1
			/*if(empty($nueva_lista[$value['tipo_contrato']])){
				$nueva_lista[$value['tipo_contrato']]	= [];
			}
			$nueva_lista[$value['tipo_contrato']][]	= $value;*/

			$contrato	= $value['tipo_contrato'];
			$modalidad_vinculacion = SituacionRevista::obtenerModalidad($contrato);
			if ($modalidad_vinculacion == Empleado::PRESTACION_SERVICIOS OR  $contrato == 10 ){
				$contrato =  Empleado::OTRAS_MODALIDADES;

			}else{
				$contrato =  Empleado::LEY_MARCO;
			}
			if ($contrato != '') {
			 	$nueva_lista[$contrato][] = $value;
			} 


		}
		return $nueva_lista;
	}

/**
 * Genera datos para el PDF de Planilla Única Reloj
 *
 * @param int $dependencia		- ID de dependencia
 * @param string $fecha			- string con formato 'Y-m-d'
 * @param array $contrato_tipo - IDs con los tipos de Situaciones de Revista.
 * @return array
 */
	static public function adjuntar_novedades($dependencia=null, $fecha=null, $contrato_tipo=null){
		$conex					= new Conexiones();
		$params					= [':desp' => $dependencia, ':fecha' => $fecha];
		$filtro_tipo_contrato	= '';
		
		//foreach ((array)$contrato_tipo as $value) {
		//	if(!array_key_exists($value, SituacionRevista::listarParaSelect())){
		//		return [];
		//	}
		//}
		$situacion_revista = SituacionRevista::idsReporte($contrato_tipo);
		$contratos = implode(',', $situacion_revista);
		$filtro_tipo_contrato = ' ec.id_tipo_contrato in ('.$contratos.') AND e.planilla_reloj = 1'; 
		
		$sql	= <<<SQL
			SELECT DISTINCT
				CONCAT(p.apellido,' ',p.nombre) AS nombre_apellido,
				e.cuit AS cuit,
				IF(ISNULL(tn.nombre),'[vacío]',IF(tn.id = '43',CONCAT(tn.nombre,' ',DATE_FORMAT( n.fecha_desde,'%H:%i'),'Hs',' - ',DATE_FORMAT( n.fecha_hasta,'%H:%i'), 'Hs'),tn.nombre)) AS novedad
			FROM empleados e
				INNER JOIN empleado_dependencia_principal dp ON (dp.id_empleado = e.id AND ISNULL(dp.fecha_hasta) AND dp.borrado = 0 AND dp.id_dependencia_principal IN (:desp))
				INNER JOIN personas p ON p.id = e.persona_id
				LEFT JOIN empleado_contrato ec ON (e.id = ec.id_empleado AND (:fecha between ec.fecha_desde AND ec.fecha_hasta OR ec.fecha_desde <= :fecha and isnull(ec.fecha_hasta)) AND ec.borrado = 0)
				LEFT JOIN novedades n ON e.id = n.id_empleado AND (:fecha BETWEEN DATE_FORMAT(n.fecha_desde ,'%Y-%m-%d' ) and DATE_FORMAT(n.fecha_hasta ,'%Y-%m-%d' )) AND n.borrado = 0
				LEFT JOIN tipo_novedades tn ON n.id_tipo_novedad = tn.id
			WHERE
				$filtro_tipo_contrato
				AND e.borrado = 0
				AND p.borrado = 0
			ORDER BY novedad, nombre_apellido, cuit
SQL;
		
		$lista	= $conex->consulta(Conexiones::SELECT, $sql, $params);
		$lista	= (!empty($lista)) ? $lista : [];
		return $lista;
	}

/**
 * Genera datos para el PDF de Planilla Única Reloj
 *
 * @param int $dependencia		- ID de dependencia
 * @param string $fecha			- string con formato 'Y-m-d'
 * @param array $contrato_tipo - IDs con los tipos de Situaciones de Revista.
 * @return array
 */
	static public function listar_informe_mensual($dependencia=null, $fecha=null, $contrato_tipo=null) {
		$conex					= new Conexiones();
		$filtro_tipo_contrato	= '';
		$params					= [
			':dep'	=> $dependencia,
			':fecha_d'	=> $fecha['fecha_desde'],
			':fecha_h'	=> $fecha['fecha_hasta'],
		];
		/*
		if(is_array($contrato_tipo)){
            $situacion_revista  = SituacionRevista::listarParaSelect();
			foreach ($contrato_tipo as $value) {
				if(!array_key_exists($value, $situacion_revista)){
					return [];
				}
			}
			$params[':contrato_tipo']	= $contrato_tipo;
			$filtro_tipo_contrato = ' AND ec.id_tipo_contrato in ( :contrato_tipo ) ';
		} else {
			$filtro_tipo_contrato	= '';
		}
		*/
		if ($contrato_tipo != 'ambos'){
			$situacion_revista = SituacionRevista::idsReporte($contrato_tipo);
			$contratos = implode(',', $situacion_revista);
			$filtro_tipo_contrato = ' AND ec.id_tipo_contrato in ('.$contratos.')'; 
		}else{
			$filtro_tipo_contrato = ''; 
		}
		
		$hora_ingreso = "date_format(a.hora_ingreso, '%Y-%m-%d')";

		$sql = <<<SQL
			SELECT
				CONCAT(p.apellido,' ',p.nombre) AS nombre_apellido,
				e.id AS empleado_id,
				date_format(a.hora_ingreso, '%Y-%m-%d') AS fecha,
				min(a.hora_ingreso) AS hora_ingreso,
				max(a.hora_egreso) AS hora_egreso,
			    CONCAT(
				if(MOD( TIMESTAMPDIFF(hour,min(a.hora_ingreso), max(a.hora_egreso)), 24)=0,'0:',concat(MOD( TIMESTAMPDIFF(hour,min(a.hora_ingreso), max(a.hora_egreso)), 24), ':')),
				MOD( TIMESTAMPDIFF(minute,min(hora_ingreso), max(a.hora_egreso)), 60)) as cant_horas,
				ec.id_tipo_contrato AS tipo_contrato,
			    a.tipo_id

			FROM
				accesos_empleados ae
				LEFT JOIN accesos a ON a.tipo_id = ae.id
				LEFT JOIN empleados e ON e.id = ae.empleado_id
				LEFT JOIN personas p ON p.id = e.persona_id

				LEFT JOIN empleado_contrato ec ON (e.id = ec.id_empleado AND (:fecha_d between ec.fecha_desde AND ec.fecha_hasta OR ec.fecha_desde <= :fecha_d AND isnull(ec.fecha_hasta)) AND ec.borrado = 0)

				INNER JOIN empleado_dependencia_principal dp ON (dp.id_empleado = e.id AND ISNULL(dp.fecha_hasta) AND dp.borrado = 0 AND dp.id_dependencia_principal = :dep)

			WHERE
				$hora_ingreso >= :fecha_d AND $hora_ingreso <= :fecha_h
			    $filtro_tipo_contrato 
				AND e.borrado = 0
				AND p.borrado = 0
			GROUP BY e.id , fecha
			ORDER BY nombre_apellido,fecha,tipo_contrato
			

SQL;
		$lista			= $conex->consulta(Conexiones::SELECT, $sql, $params);		
		$nueva_lista	= [];
		$lista			= (!empty($lista)) ? $lista : [];
		
		foreach ($lista as $key => $value) {
			/*$contrato	= $value['tipo_contrato'];
			$modalidad_vinculacion = SituacionRevista::obtenerModalidad($contrato);
			if ($modalidad_vinculacion == Empleado::PRESTACION_SERVICIOS OR  $contrato == 10 ){
				$contrato =  Empleado::OTRAS_MODALIDADES;

			}else{
				$contrato =  Empleado::LEY_MARCO;
			}
			if ($contrato != '') {
			 	$nueva_lista[$contrato][] = $value;
			}*/ 
			$contrato	= $value['tipo_contrato'];
			 if ($contrato != '') {
			 	$nueva_lista[$contrato][] = $value;
			 } 
		}
		return $nueva_lista;
	}

/**
 * Genera datos para el PDF de Planilla Única Reloj
 *
 * @param int $dependencia		- ID de dependencia
 * @param string $fecha			- string con formato 'Y-m-d'
 * @param array $contrato_tipo - IDs con los tipos de Situaciones de Revista.
 * @return array
 */
	static public function novedades_mensuales($dependencia=null, $fecha=null, $contrato_tipo=null){
		$conex					= new Conexiones();
		$filtro_tipo_contrato	= '';
		$params					= [
			':dep'	=> $dependencia,
			':fecha_d'	=> $fecha['fecha_desde'],
			':fecha_h'	=> $fecha['fecha_hasta'],
		];

		// if($contrato_tipo == Empleado::AT){
		// 	$filtro_tipo_contrato	= 'AND ec.id_tipo_contrato = '.Empleado::AT.' ';
		// }elseif($contrato_tipo == Empleado::LEY_MARCO){
		// 	$filtro_tipo_contrato = 'AND ec.id_tipo_contrato in ('.Empleado::LEY_MARCO.','.Empleado::AD_HONOREM.','.Empleado::AUTORIDADES_SUPERIORES.','.Empleado::PLANTA_PERMANENTE.','.Empleado::PLANTA_TRANSITORIA. ') ';
		// }else{
		// 	$filtro_tipo_contrato	= "";
		// }
		/*
		if(is_array($contrato_tipo)){
            $situacion_revista  = SituacionRevista::listarParaSelect();
			foreach ($contrato_tipo as $value) {
				if(!array_key_exists($value, $situacion_revista)){
					return [];
				}
			}
			$params[':contrato_tipo']	= $contrato_tipo;
			$filtro_tipo_contrato = ' AND ec.id_tipo_contrato in ( :contrato_tipo ) ';
		} else {
			$filtro_tipo_contrato	= '';
		}*/
		if ($contrato_tipo != 'ambos'){
			$situacion_revista = SituacionRevista::idsReporte($contrato_tipo);
			$contratos = implode(',', $situacion_revista);
			$filtro_tipo_contrato = ' AND ec.id_tipo_contrato in ('.$contratos.')'; 
		}else{
			$filtro_tipo_contrato = ''; 
		}

		$sql = <<<SQL
		SELECT 
			CONCAT(p.apellido,' ',p.nombre) AS nombre_apellido,
			ec.id_tipo_contrato AS tipo_contrato,
		    n.id_empleado AS empleado_id,
		    n.fecha_desde,
			n.fecha_hasta,
			n.id AS novedad_id,
		    tn.nombre AS novedad
		FROM
			novedades n
		INNER JOIN tipo_novedades tn ON tn.id = n.id_tipo_novedad
		INNER JOIN empleados e ON e.id = n.id_empleado
		INNER JOIN personas p ON p.id = e.persona_id
		LEFT JOIN empleado_contrato ec ON (e.id = ec.id_empleado AND (:fecha_d between ec.fecha_desde AND ec.fecha_hasta OR MONTH(ec.fecha_desde) <= MONTH(:fecha_d) AND isnull(ec.fecha_hasta)) AND ec.borrado = 0)
		INNER JOIN empleado_dependencia_principal dp ON (dp.id_empleado = e.id AND ISNULL(dp.fecha_hasta) AND dp.borrado = 0 AND dp.id_dependencia_principal = :dep)

		WHERE
			date_format(n.fecha_desde, '%Y-%m-%d') >= :fecha_d AND date_format(n.fecha_desde, '%Y-%m-%d') <= :fecha_h
		    $filtro_tipo_contrato
			AND e.borrado = 0
			AND p.borrado = 0

		ORDER BY nombre_apellido,n.fecha_desde,tipo_contrato
SQL;
		$lista	= $conex->consulta(Conexiones::SELECT, $sql, $params);
		$lista	= (!empty($lista)) ? $lista : [];

		return $lista;
	}

	static public function verificar_asistencia($fin_mes, $empleado_id){
		$sql = "
				SELECT a.tipo_id, a.hora_ingreso, a.persona_id_ingreso
				FROM accesos_empleados ae
				INNER JOIN accesos a ON a.tipo_id = ae.id
				INNER JOIN empleados e ON e.id = ae.empleado_id
				WHERE ae.empleado_id = :empleado_id
				AND date_format(a.hora_ingreso, '%Y-%m-%d') = :fin_mes
				";
	
		$params = [
			':fin_mes'          => $fin_mes,
			':empleado_id'		=> $empleado_id,
		];
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
		$resp	= (!empty($res)) ? $res : false;
		return $resp;
	}


/**
 * Genera la informacion requerida para armar el PDF de `accion_desvios_horarios`
 *
 * @param int $dependencia
 * @param DateTime $fecha_ini
 * @param DateTime $fecha_fin
 * @param int $tipo_contrato
 * @param string $doc_nombre
 * @return array
 */
	static public function empleados_desvios($dependencia=null, $fecha_ini=null, $fecha_fin=null, $tipo_contrato=null, $doc_nombre=null){
		$conex = new Conexiones();
			$where = "WHERE acc.hora_ingreso BETWEEN :fecha_ini  AND :fecha_fin"; 
			$params = [
			':fecha_ini'     => $fecha_ini ? $fecha_ini->format('Y-m-d') : null,
			':fecha_fin'     => $fecha_fin ? $fecha_fin->format('Y-m-d 23:59:59') : null
		     ];

		if(!is_null($doc_nombre)){
			$where .= " AND (pe.documento LIKE (:doc_nombre) or pe.nombre LIKE(:doc_nombre) or pe.apellido LIKE(:doc_nombre)) "; 
			$params[':doc_nombre'] = '%'.$doc_nombre.'%';
		} 
		if(!is_null($dependencia)) {
			$where .= " AND edp.id_dependencia_principal = :dependencia";
			$params[':dependencia'] = $dependencia; 
		}
		if(!empty($tipo_contrato)){
			$where .= " AND ec.id_tipo_contrato IN (:tipo_contrato)"; 
			$params[':tipo_contrato'] = $tipo_contrato;
		}
				    
		$sql	= <<<SQL
		SELECT acc.id,
				e.id AS empleado_id,
				pe.documento,
				pe.nombre, 
				pe.apellido, 
				date_format(acc.hora_ingreso, '%Y-%m-%d') as dia_ingreso, 
				date_format(min(acc.hora_ingreso), '%Y-%m-%d %H:%i:%s') as hora_ingreso,
				CONCAT( if(MOD( TIMESTAMPDIFF(hour,acc.hora_ingreso, acc.hora_egreso), 24)=0,'',concat(MOD( TIMESTAMPDIFF(hour,acc.hora_ingreso, acc.hora_egreso), 24), ':')), MOD( TIMESTAMPDIFF(minute,hora_ingreso, acc.hora_egreso), 60)) as cantidad_trabajadas,
				null AS ingreso_teorico,
				null AS dif_ingreso,
				date_format(max(acc.hora_egreso), '%Y-%m-%d %H:%i:%s') AS hora_egreso, 
				null AS egreso_teorico,
				null AS dif_egreso,
				ec.id_tipo_contrato,
				null AS dif_total
				FROM accesos AS acc
					JOIN accesos_empleados AS ae ON acc.tipo_id = ae.id AND acc.tipo_modelo = 1
					JOIN empleados AS e ON ae.empleado_id = e.id and e.borrado=0 
					JOIN personas AS pe ON e.persona_id = pe.id
				    LEFT JOIN empleado_dependencia_principal edp ON (e.id =edp.id_empleado AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 )
					LEFT JOIN dependencias d ON (d.id = edp.id_dependencia_principal AND (ISNULL(d.fecha_hasta) OR d.fecha_hasta >= NOW()) )
				    INNER JOIN empleado_contrato ec ON (e.id = ec.id_empleado AND ISNULL(ec.fecha_hasta) AND ec.borrado = 0 )
				{$where}
				group by dia_ingreso, e.id
				order by e.id, acc.hora_ingreso
SQL;

		$lista	= $conex->consulta(Conexiones::SELECT, $sql, $params);
		$lista	= (!empty($lista)) ? $lista : [];
		$aux = [];

		function redondear_interval($interval) {

			$horas = $interval->h;
			$minutos = $interval->i;
			$segundos = $interval->s;
			if($segundos > 30) $minutos = $interval->i + 1;
			if($minutos == 60) {
				$minutos = 0;
				$horas++;
			}
			$nuevo_date_interval_redondeado = new \DateInterval(("PT".$horas."H".$minutos."M"));
			$nuevo_date_interval_redondeado->invert = $interval->invert;
			return 	$nuevo_date_interval_redondeado;
		}


		function formatear ($dif_ingreso_sin_signo, $dif_egreso_sin_signo, $dif_ingreso_date, $dif_egreso_date) {

			$date_interval_redondeado_ingreso = redondear_interval($dif_ingreso_sin_signo);
			$date_interval_redondeado_egreso =  redondear_interval($dif_egreso_sin_signo);

			$second = $dif_ingreso_date->format("s");
			if($second > 30) $dif_ingreso_date->add(new \DateInterval("PT".(60-$second)."S"));

			$second = $dif_egreso_date->format("s");
			if($second > 30) $dif_egreso_date->add(new \DateInterval("PT".(60-$second)."S"));

			if (!$date_interval_redondeado_ingreso->invert && !$date_interval_redondeado_egreso->invert) { //  +  +
				$dif_total = $dif_ingreso_date->add($date_interval_redondeado_egreso);
				$dif_total = "+ ".$dif_total->format('H:i');
				return $dif_total;
			}

			if ($date_interval_redondeado_ingreso->invert && $date_interval_redondeado_egreso->invert) { //  - -
				$date_interval_redondeado_egreso->invert = 0;
				$dif_total = $dif_ingreso_date->add($date_interval_redondeado_egreso);
				$dif_total = "- ".$dif_total->format('H:i');
				return $dif_total;
			}

			if ($date_interval_redondeado_ingreso->invert && !$date_interval_redondeado_egreso->invert) { //  -  +

				if ($date_interval_redondeado_ingreso->h > $date_interval_redondeado_egreso->h){
					$date_interval_redondeado_egreso->invert = 1;
					$dif_total = $dif_ingreso_date->add($date_interval_redondeado_egreso);
					$dif_total = "- ".$dif_total->format('H:i');
					return $dif_total;
				} else {
					if ($date_interval_redondeado_ingreso->h == $date_interval_redondeado_egreso->h) {
						if($date_interval_redondeado_ingreso->i > $date_interval_redondeado_egreso->i){
							$date_interval_redondeado_egreso->invert = 1;
							$dif_total = $dif_ingreso_date->add($date_interval_redondeado_egreso);
							$dif_total = "- ".$dif_total->format('H:i');
							return $dif_total;
						} else {
							$dif_total = $dif_egreso_date->add($date_interval_redondeado_ingreso);
							$dif_total = "+ ".$dif_total->format('H:i');
							return $dif_total;
						}
					} else {
						$dif_total = $dif_egreso_date->add($date_interval_redondeado_ingreso);
						$dif_total = "+ ".$dif_total->format('H:i');
						return $dif_total;
					}
				}
			} else { //  +  -
				if ($date_interval_redondeado_ingreso->h > $date_interval_redondeado_egreso->h){
					$dif_total = $dif_ingreso_date->add($date_interval_redondeado_egreso);
					$dif_total = "+ ".$dif_total->format('H:i');
					return $dif_total;
				} else {
					if ($date_interval_redondeado_ingreso->h == $date_interval_redondeado_egreso->h) {
						if($date_interval_redondeado_ingreso->i > $date_interval_redondeado_egreso->i){
							$dif_total = $dif_ingreso_date->add($date_interval_redondeado_egreso);
							$dif_total = "+ ".$dif_total->format('H:i');
							return $dif_total;
						} else {
							$date_interval_redondeado_ingreso->invert = 1;
							$dif_total = $dif_egreso_date->add($date_interval_redondeado_ingreso);
							$dif_total = "- ".$dif_total->format('H:i');
							return $dif_total;
						}
					} else {
						$date_interval_redondeado_ingreso->invert = 1;
						$dif_total = $dif_egreso_date->add($date_interval_redondeado_ingreso);
						$dif_total = "- ".$dif_total->format('H:i');
						return $dif_total;
					}
				}
			}
		}

		foreach ($lista as &$value) {
			if(!isset($aux[$value['empleado_id']])){
				$emp = Empleado::obtener($value['empleado_id']);
				$aux[$value['empleado_id']] = $emp;
			}
			$dia_ingreso		      = $value['dia_ingreso'];
			$hora_ingreso_real		  = $value['hora_ingreso'];
			$hora_egreso_real 		  = $value['hora_egreso'];
			$dato_dia_ingreso		  = new DateTime($dia_ingreso);
			$dato_ingreso 			  = new DateTime($hora_ingreso_real);
			$dato_egreso 			  = new DateTime($hora_egreso_real);
			$dia_semana				  = $dato_ingreso->format('w');
			$horas_teoricas			  = $aux[$value['empleado_id']]->horarios[$dia_semana];
			$dato_ingreso_teorico 	  = new DateTime(substr($hora_ingreso_real,0,-8).$horas_teoricas[0]);
			$dato_egreso_teorico  	  = new DateTime(substr($hora_egreso_real,0,-8).$horas_teoricas[1]);
			$dif_ingreso_sin_signo    = date_diff($dato_ingreso ,$dato_ingreso_teorico);
			$dif_egreso_sin_signo     = date_diff($dato_egreso_teorico, $dato_egreso);
			$dif_ingreso_date         = new DateTime($dif_ingreso_sin_signo->format('%H:%I:%S'));
			$dif_egreso_date          = new DateTime($dif_egreso_sin_signo->format('%H:%I:%S'));
		 	$value['dif_ingreso']     = redondear_interval($dif_ingreso_sin_signo)->format('%R %H:%I');
			$value['dif_egreso']      = redondear_interval($dif_egreso_sin_signo)->format('%R %H:%I');
			$value['dif_total']       = formatear($dif_ingreso_sin_signo,$dif_egreso_sin_signo, $dif_ingreso_date,$dif_egreso_date);
			$diferencia_real          = $dato_ingreso_teorico->diff($dato_egreso_teorico);
			$horas_trabajadas         = $dato_ingreso->diff($dato_egreso);
			$value['horas_trabajadas']= $horas_trabajadas->format('%H:%I');
			$value['horas_laborales'] = $diferencia_real->format('%H:%I');
			$value['fecha']			  = $dato_dia_ingreso;
			$value['ingreso_teorico'] = $horas_teoricas[0];
			$value['egreso_teorico']  = $horas_teoricas[1];
			$value['hora_ingreso'] 	  = $dato_ingreso->format('H:i');
			$value['hora_egreso']	  = $dato_egreso->format('H:i');
		}
		return $lista;
	}

	 public static function listar_reporte($params = array()){
	 	$acceso_clase = Acceso::getClassIndex(new static());
		$rol = Usuario::obtenerUsuarioLogueado();
		
		$campos    = 'id, ubicacion_id, ubicacion, id_codep, codep, documento, nombre, apellido, fecha_entrada, fecha_egreso, hora_entrada, hora_egreso, tipo_ingreso, persona_id_ingreso, tipo_egreso, persona_id_egreso, 
					usuario_ingreso,usuario_egreso,observaciones';

        $sql_params = [];

        $where = [];

        $condicion = "";
      
        $params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo'])) ? 'tipo' : $params['order']['campo'];
        $params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']))   ? 'asc' : $params['order']['dir'];
        $params['start']  = (!isset($params['start'])  || empty($params['start']))  ? 0 :
        $params['start'];
        $params['lenght'] = (!isset($params['lenght']) || empty($params['lenght'])) ? 10 :
        $params['lenght'];

        $default_params = [
            'filtros'   => [
               	'ubicacion'   	=> null,
                'dependencia' 	=> null,
               	'fecha_desde' 	=> null,
                'fecha_hasta'	=> null,
                'incluir_sin_cierre' => null,
                'otro_criterio' => null

            ]
        ];

        $params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
        $params = array_merge($default_params, $params);

        /*Filtros */
        if(!empty($params['filtros']['ubicacion'])){
            $where [] = "e.id = :ubicacion";
            $sql_params[':ubicacion']    = $params['filtros']['ubicacion'];

        }

        if(!empty($params['filtros']['dependencia'])){
            $where [] = "d.id = :dependencia";
            $sql_params[':dependencia']    = $params['filtros']['dependencia'];

        }
    
        if(!empty($params['filtros']['fecha_desde'])){
            $where [] = "acc.hora_ingreso >= :fecha_desde";
            $fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_desde'])->format('Y-m-d');
            $sql_params[':fecha_desde']    = $fecha;

        }

      	if (!empty($params['filtros']['fecha_hasta'])) {
			$sin_cierre = '';
			if ($params['filtros']['incluir_sin_cierre'] == "1") {
				$sin_cierre =  " OR (acc.hora_ingreso <= :fecha_hasta AND acc.hora_egreso IS NULL))";
			}

			$where[] = (!empty($sin_cierre) ? '(':'')." acc.hora_egreso <= :fecha_hasta" . $sin_cierre;
			$fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_hasta'])->format('Y-m-d 23:59:59');
            $sql_params[':fecha_hasta']    = $fecha;
		}
		
		if(!empty($params['search'])){
            $where [] = "(p.nombre like :otro_criterio OR p.apellido like :otro_criterio OR p.documento like :otro_criterio OR e.nombre like :otro_criterio OR d.nombre like :otro_criterio OR acc.observaciones like :otro_criterio)";
            $sql_params[':otro_criterio']    = '%'.$params['search'].'%';
        }
		$params['search'] = '';

		$condicion .= !empty($where) ? ' WHERE ' . \implode(' AND ',$where) : '';

        $condicion = empty($condicion) ? "WHERE acc.hora_ingreso >= '".date("Y-m-d", strtotime("-7 days"))."'" : $condicion;

		if (!empty($rol->dependencias)) {
			$deps = implode("," ,$rol->dependencias);
			$condicion .= " AND d.id IN ( " . $deps . ")";
		} 
		//SE INSTANCIA POR SI HICIERA FALTA DEFINIR LA PARTICIÓN DE LA TABLA DE ACCESOS
		$anio_desde = (!empty($params['filtros']['fecha_desde'])) ? \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_desde'])->format('Y') : date('Y');
		$anio_hasta = (!empty($params['filtros']['fecha_hasta'])) ? \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_hasta'])->format('Y') : date('Y');
		$partition = [];
		for ($i=$anio_desde; $i <= $anio_hasta; $i++) { 
			array_push($partition,'p'.$i);
		}
		$partition = implode(",",$partition);

			$consulta = <<<SQL
				SELECT
				acc.id as id, acc.ubicacion_id, e.nombre as ubicacion, edp.id_dependencia_principal as id_codep,
				d.nombre as codep, p.documento, p.nombre, p.apellido, 
				acc.hora_ingreso as fecha_entrada, acc.hora_egreso as fecha_egreso,
				DATE_FORMAT(acc.hora_ingreso, '%H:%i:%s') as hora_entrada, DATE_FORMAT(acc.hora_egreso, '%H:%i:%s') as hora_egreso, 
				acc.tipo_ingreso, acc.persona_id_ingreso, acc.tipo_egreso, acc.persona_id_egreso, CONCAT(COALESCE(pin.nombre, ''), ' ', 
				COALESCE(pin.apellido, '')) as usuario_ingreso, CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) as usuario_egreso, acc.observaciones
				FROM 
				accesos AS acc 
				JOIN accesos_empleados AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = $acceso_clase
				JOIN ubicaciones AS e ON e.id = acc.ubicacion_id
				JOIN empleados AS cp ON cp.id = ac.empleado_id and cp.borrado=0 
				JOIN personas AS p ON p.id = cp.persona_id
				LEFT JOIN empleado_dependencia_principal edp ON (cp.id =edp.id_empleado AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 )
				LEFT JOIN dependencias d ON (d.id = edp.id_dependencia_principal AND (ISNULL(d.fecha_hasta) OR d.fecha_hasta >= NOW()) )
				JOIN personas AS pin ON pin.id = acc.persona_id_ingreso
				LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
				$condicion
SQL;

			$data = self::listadoAjaxMasivo($campos, $consulta, $params, $sql_params);
			return $data;

    }

    public static function listar_historico_empleados_excel($params){
    	$acceso_clase = Acceso::getClassIndex(new static());
        $cnx    = new Conexiones();
        $sql_params = [];
        $where = [];
        $condicion = '';
        $order = '';
		$rol = Usuario::obtenerUsuarioLogueado();

        $default_params = [
            'order'     => [
                [
                    'campo' => 'id',
                    'dir'   => 'ASC',
                ],
            ],
            'start'     => 0,
            'lenght'    => 10,
			'search'    => '',
            'filtros'   => [
                'ubicacion'   => null,
                'dependencia' => null,
                'fecha_desde' => null,
                'fecha_hasta'		=> null,
                'incluir_sin_cierre'=> null,
            ],
            'count'     => false
        ];

        $params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
        $params = array_merge($default_params, $params);

		$sql= <<<SQL
			SELECT  p.documento, p.nombre, p.apellido, DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') as fecha_entrada,
			DATE_FORMAT(acc.hora_ingreso, '%H:%i:%s') as hora_entrada, 
			(case when acc.tipo_ingreso = 1 then 'On line'
				when acc.tipo_ingreso = 2 then 'Off line'
				when acc.tipo_ingreso = 3 then 'Reloj'
				when acc.tipo_ingreso = 4 then 'Comision horaria'
				when acc.tipo_ingreso = 5 then 'BIO Hacienda'
				when acc.tipo_ingreso = 6 then 'Tarjeta Magnetica'
				else 'Sin registro'
			END) AS tipo_ingreso, 
			DATE_FORMAT(acc.hora_egreso, '%d/%m/%Y') as fecha_egreso, DATE_FORMAT(acc.hora_egreso, '%H:%i:%s') as hora_egreso, 
			(case when acc.tipo_egreso = 1 then 'On line'
				when acc.tipo_egreso = 2 then 'Off line'
				when acc.tipo_egreso = 3 then 'Reloj'
				when acc.tipo_egreso = 4 then 'Comision horaria'
				when acc.tipo_egreso = 5 then 'BIO Hacienda'
				when acc.tipo_egreso = 6 then 'Tarjeta Magnetica'
				else 'Sin registro'
			END) AS tipo_egreso,
			d.nombre as codep, 
			e.nombre as ubicacion,CONCAT(COALESCE(pin.nombre, ''), ' ',
			COALESCE(pin.apellido, '')) as usuario_ingreso, 
			CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) as usuario_egreso, acc.observaciones
		SQL;

		//SE INSTANCIA POR SI HICIERA FALTA DEFINIR LA PARTICIÓN DE LA TABLA DE ACCESOS
		$anio_desde = (!empty($params['filtros']['fecha_desde'])) ? \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_desde'])->format('Y') : date('Y');
		$anio_hasta = (!empty($params['filtros']['fecha_hasta'])) ? \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_hasta'])->format('Y') : date('Y');
		$partition = [];
		for ($i=$anio_desde; $i <= $anio_hasta; $i++) { 
			array_push($partition,'p'.$i);
		}
		$partition = implode(",",$partition);

		$from = <<<SQL
			FROM  accesos  AS acc
			JOIN accesos_empleados AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = $acceso_clase
			JOIN ubicaciones AS e ON e.id = acc.ubicacion_id
			JOIN empleados AS cp ON cp.id = ac.empleado_id and cp.borrado=0 
			JOIN personas AS p ON p.id = cp.persona_id
			LEFT JOIN empleado_dependencia_principal edp ON (cp.id =edp.id_empleado AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 )
			LEFT JOIN dependencias d ON (d.id = edp.id_dependencia_principal AND (ISNULL(d.fecha_hasta) OR d.fecha_hasta >= NOW()) )
			JOIN personas AS pin ON pin.id = acc.persona_id_ingreso
			LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
		SQL;

		 /*Filtros */
		 if(!empty($params['filtros']['ubicacion'])){
            $where [] = "e.id = :ubicacion";
            $sql_params[':ubicacion']    = $params['filtros']['ubicacion'];

        }

        if(!empty($params['filtros']['dependencia'])){
            $where [] = "d.id = :dependencia";
            $sql_params[':dependencia']    = $params['filtros']['dependencia'];

        }
    
        if(!empty($params['filtros']['fecha_desde'])){
            $where [] = "acc.hora_ingreso >= :fecha_desde";
            $fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_desde'])->format('Y-m-d');
            $sql_params[':fecha_desde']    = $fecha;

        }

      	if (!empty($params['filtros']['fecha_hasta'])) {
			$sin_cierre = '';
			if ($params['filtros']['incluir_sin_cierre'] == "true") {
				$sin_cierre =  " OR (acc.hora_ingreso <= :fecha_hasta AND acc.hora_egreso IS NULL))";
			}

			$where[] = (!empty($sin_cierre) ? '(':'')." acc.hora_egreso <= :fecha_hasta" . $sin_cierre;
			$fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_hasta'])->format('Y-m-d 23:59:59');
            $sql_params[':fecha_hasta']    = $fecha;
		}

		if(!empty($params['search'])){
            $where [] = "(p.nombre like :otro_criterio OR p.apellido like :otro_criterio OR p.documento like :otro_criterio OR e.nombre like :otro_criterio OR d.nombre like :otro_criterio OR acc.observaciones like :otro_criterio)";
            
            $sql_params[':otro_criterio']    = '%'.$params['search'].'%';

        }

        $condicion .= !empty($where) ? ' WHERE ' . \implode(' AND ',$where) : '';

        $condicion = empty($condicion) ? " WHERE acc.hora_ingreso >= '".date("Y-m-d", strtotime("-7 days"))."'" : $condicion;
		if (!empty($rol->dependencias)) {
			$deps = implode("," ,$rol->dependencias);
			$condicion .= " AND d.id IN ( " . $deps . ")";
		}


		
		/**Orden de las columnas */
        $orderna = [];
        foreach ($params['order'] as $i => $val) {
            $orderna[]  = "{$val['campo']} {$val['dir']}";
        }
       
        $order .= implode(',', $orderna);
       
        $limit = (isset($params['lenght']) && isset($params['start']) && $params['lenght'] != '')
            ? " LIMIT  {$params['start']}, {$params['lenght']}" : ' ';

       	$order .= (empty(trim($order)) ? '' : ', ').' p.apellido asc';
      
        $order = ' ORDER BY '.$order;

		ini_set('memory_limit', '1024M');
        $lista = $cnx->consulta(Conexiones::SELECT,  $sql.$from.$condicion.$order.$limit,$sql_params);

        return  ($lista) ? $lista : [];
    }

 }