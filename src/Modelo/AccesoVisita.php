<?php namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Util;
use App\Helper\Validador;
use FMT\Logger;
use FMT\Modelo;
use App\Modelo\Ubicacion;
use DateTime;

/**
 * Gestiona las visitas
 * Class AccesoVisita
 */
class AccesoVisita extends Modelo {
	/** @var int */
	public $id;
	/** @var int */
	public $persona_id;
	/** @var int */
	public $autorizante_id;
	/** @var int */
	public $credencial_id;
	/** @var Persona */
	public $persona;
	/** @var Empleado */
	public $autorizante;
	/** @var Credencial */
	public $credencial;
	/** @var string */
	public $origen;
	/** @var string */
	public $destino;
	/** @var int */
	public $tipo_acceso;
	/** @const Define el índice de la clase el la base de datos para la relación con la tabla correspondiente */
	const TIPO_MODEL = 2;

	/**
	 * Regresa una instancia de AccesoVisita, si el ID existe en la base de datos, regresara el Modelo de
	 * dicho registro, si el ID no existe se ejecutara una Excepción.
	 * @param int $id Identificador de la visita (pasar 0 para obtener una nueva instancia de AccesoVisita)
	 * @return AccesoVisita
	 */
	static public function obtener($id) {
		$sql = "SELECT id, persona_id, credencial_id, autorizante_id, origen, destino, :tipo_acceso AS tipo_acceso
					FROM accesos_visitas
					WHERE id = :id;";
		if (is_numeric($id)) {
			if ($id > 0) {
				$params = [
					':id'          => $id,
					':tipo_acceso' => Acceso::VISITANTE,
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


	static public function obtenerPorCred($credencial_id) {
		$sql = "SELECT id, persona_id, credencial_id, autorizante_id, origen, destino, :tipo_acceso AS tipo_acceso
					FROM accesos_visitas
					WHERE credencial_id = :credencial_id;";
		if (is_numeric($credencial_id)) {
			if ($credencial_id > 0) {
				$params = [
					':credencial_id'          => $credencial_id,
					':tipo_acceso' => Acceso::VISITANTE,
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
	 * @param string    $documento
	 * @param Ubicacion $ubicacion
	 * @param \DateTime $fecha
	 * @return array
	 */
	public static function buscar($documento,$ubicacion = null) {
		$cond_empleado = '';
		$cond_contratista = '';		
		if (!is_null($ubicacion)) {
			$cond_empleado = "INNER JOIN empleados_x_ubicacion u ON e.id = u.empleado_id AND u.ubicacion_id= {$ubicacion->id}";
			$cond_contratista = "INNER JOIN contratista_x_ubicacion cu ON cp.id = cu.personal_id and cu.ubicacion_id = {$ubicacion->id} ";   
		}
		$sql = "SELECT * FROM personas AS p WHERE 
		id NOT IN(
			SELECT persona_id FROM empleados e $cond_empleado WHERE e.borrado =0
			UNION
			SELECT cp.persona_id FROM contratista_personal cp 
			$cond_contratista
			INNER JOIN contratistas c ON cp.contratista_id = c.id AND c.borrado = 0
		)
		AND (p.documento LIKE CONCAT('%',:documento, '%'));";
		$params = [
			':documento'    => $documento,
		];
		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, $params);
		return $res;
	}

	public static function buscar_autorizante($documento= NULL, $nombre= NULL) {
		$fecha_actual = date ("Y-m-d");
		if(!is_null($documento)){
			$condicion = " AND (p.documento LIKE CONCAT('%',:documento, '%'))"; 
		$params = [
			':documento'    => $documento,
			':fecha_actual' => $fecha_actual
		     ];

		} else {
			$condicion = " AND CONCAT (p.nombre,' ',p.apellido) like :nombre";
			$params = [
			':fecha_actual' => $fecha_actual,
			':nombre' => '%'.$nombre.'%'
		    ];
		}
			

		$sql = "SELECT 
			e.id as id_empleado, 
			CONCAT(p.nombre, ' ', p.apellido) AS nombre,
			p.documento
			FROM 
			empleados as e
			inner join personas as p on p.id = e.persona_id
			inner join empleado_contrato as ec on ec.id_empleado = e.id
			WHERE
			e.borrado = 0 AND p.borrado = 0 AND ec.borrado = 0 AND (ec.fecha_hasta >= :fecha_actual OR ec.fecha_hasta IS NULL) ".$condicion;

		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, $params);
		return $res;
	}
	/**
	 * @param Ubicacion   $ubicacion
	 * @param Persona     $persona
	 * @param \DateTime   $ingreso
	 * @param \DateTime   $egreso
	 * @param Usuario     $usuario
	 * @param null|string $observaciones
	 * @return bool|array
	 * @throws \SimpleValidator\SimpleValidatorException
	 */
	public static function altaManual($ubicacion, $persona, $ingreso, $egreso,$origen, $destino, $autorizante, $usuario, $credencial, $observaciones = null) {		
		$validator = static::validarManual($ubicacion, $persona, $ingreso, $egreso, $origen, $destino, $autorizante, $usuario, $credencial);
		if ($validator === true) {
			$sql = "INSERT INTO accesos_visitas(persona_id, autorizante_id, credencial_id, origen, destino) VALUE (:persona_id, :autorizante_id, :credencial_id, :origen, :destino);
			INSERT INTO accesos(tipo_id, tipo_modelo, hora_ingreso, persona_id_ingreso, tipo_ingreso, hora_egreso, persona_id_egreso, tipo_egreso, observaciones, ubicacion_id) 
			VALUE (LAST_INSERT_ID(), :clase, :hora_ingreso, :persona_id_ingreso, :tipo_ingreso, :hora_egreso, :persona_id_egreso, :tipo_egreso, :observaciones, :ubicacion_id)";
			$params = [
				":persona_id"         => $persona->id,
				':clase'              => Acceso::getClassIndex(new self()),
				":hora_ingreso"       => ($ingreso instanceof DateTime) ? $ingreso->format('Y-m-d H:i:s') : $ingreso,
				":persona_id_ingreso" => $usuario->getEmpleado()->persona_id,
				":origen"			  => $origen,
				":destino"			  => $destino,
				":autorizante_id"	  => $autorizante,
				":tipo_ingreso"       => Acceso::TIPO_REGISTRO_OFFLINE,
				":hora_egreso"        => ($egreso instanceof DateTime) ? $egreso->format('Y-m-d H:i:s') : $egreso,
				":persona_id_egreso"  => $usuario->getEmpleado()->persona_id,
				":tipo_egreso"        => Acceso::TIPO_REGISTRO_OFFLINE,
				":credencial_id"      => $credencial,
				":observaciones"      => $observaciones,
				":ubicacion_id"       => $ubicacion->id,
			];
			$res = (new Conexiones())->consulta(Conexiones::INSERT, $sql, $params);

			if (!empty($res) && is_numeric($res) && $res > 0) {
				$params['id'] = $res;
				$params['modelo'] = 'acceso_visitas';
				Logger::event('altaManual', $params);
				return true;
			}
		}
		return $validator;
	}
public static function validarManual($ubicacion, $persona, $ingreso, $egreso, $origen, $destino,  $autorizante, $usuario, $credencial, $acceso = null) {
		$inputs = [
			'ubicacion_id'    => $ubicacion->id,
			'persona_id'      => $persona->id,
			'ingreso'         => $ingreso,
			'egreso'          => $egreso,
			'origen'	      => $origen,
			'destino'	      => $destino,
			'autorizante_id'  => $autorizante,
			'usuario_id'      => $usuario->id,
			'credencial_id'   => $credencial,
			'documento'		  => $persona->documento,
			'persona_id_ingreso' => $usuario->getEmpleado()->persona_id,
		];
		$rules = [
			'documento'		 => ['documento'],
			'ubicacion_id'   => ['required', 'numeric'],
			'ingreso'        => ['fecha'],
			'egreso'         => ['fecha', 'despuesDe(:ingreso)'],
			'origen'         => ['texto', 'max_length(64)'],
			'destino'        => ['texto', 'max_length(64)'],
			'usuario_id'     => ['required', 'numeric'],
			'credencial_id'  => ['required', 'numeric'],
			'persona_id'	 => ['no_es_empleado_activo'=>function($input){
				$persona = Persona::obtener($input);
				$empleado = $persona->getEmpleados();
				return empty($empleado->id_tipo_contrato);
			}],
			'persona_id_ingreso'   => ['empleado_x_usuario', 'numeric',]
		];
		$naming = [
			'ubicacion_id' => "Ubicación",
			'persona'      => "Persona",
			'documento'    => "Documento",
			'ingreso'      => "Ingreso",
			'egreso'       => "Egreso",
			'usuario_id'   => "Usuario",
			'origen'       => "Origen",
			'destino'      => "Destino",
			'autorizante'  => "Autorizante",
			'credencial_id' => "Credencial",
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
			'persona'      => "La <strong>Persona</strong> no es válida.",
			'puede_entrar' => "La <strong>Persona</strong> no tiene permiso para acceder al <strong>Edificio</strong> seleccionado.",
			'documento'    => "El número de<strong> Documento </strong>no es válido, pertenece a otro registro.",
			'no_es_empleado_activo'  => "El número de<strong> Documento </strong>no es válido, pertenece a un Empleado Activo."
		]);

		return $validator->getErrors();
	}

	/**
	 * @param $res
	 * @return AccesoVisita
	 */
	private static function arrayToObject($res = []) {
		/** @var AccesoVisita $obj */
		$obj = new self();
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->persona_id = isset($res['persona_id']) ? (int)$res['persona_id'] : 0;
		$obj->autorizante_id = isset($res['autorizante_id']) ? (int)$res['autorizante_id'] : 0;
		$obj->credencial_id = isset($res['credencial_id']) ? (int)$res['credencial_id'] : 0;
		$obj->origen = isset($res['origen']) ? $res['origen'] : 0;
		$obj->destino = isset($res['destino']) ? $res['destino'] : 0;
		$obj->persona = Persona::obtener($obj->persona_id);
		$obj->autorizante = Empleado::obtener($obj->autorizante_id);
		$obj->credencial = Credencial::obtener($obj->credencial_id);
		$obj->tipo_acceso = isset($res['tipo_acceso']) ? (int)$res['tipo_acceso'] : 0;

		return $obj;
	}

	/**
	 * Regresa un array con los registros de visitas que coincidan con los argumentos de búsqueda
	 * @param int            $id_ubicacion
	 * @param null|\DateTime $fecha
	 * @param string         $estatus [
	 *      todos => despliega la lista completa de visitas,
	 *      activos => despliega las visitas cuya fecha de egreso es NULL,
	 *      inactivos => despliega las visitas finalizadas
	 * ]
	 * @return array
	 */
	static public function listar($id_ubicacion = 0, $fecha = null, $estatus = 'todos') {
		$sql = "SELECT
					acc.id                                                        AS acc_id,
					p.id                                                          AS persona_id,
					c.codigo                                                      AS credencial,
					p.documento                                                   AS documento,
					CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, '')) AS nombre,
					acc.hora_ingreso                                              AS fecha_ingreso,
					acc.hora_egreso                                               AS fecha_egreso,
					DATE_FORMAT(acc.hora_ingreso, '%H:%i')                        AS hora_entrada,
					DATE_FORMAT(acc.hora_egreso, '%H:%i')                         AS hora_salida,
					av.origen                                                     AS origen,
					av.destino                                                    AS destino,
					acc.observaciones                                             AS observaciones,
					av.autorizante_id                                             AS autorizante_id,
					acc.tipo_id,
					acc.tipo_modelo,
					:tipo_acceso                                                  AS tipo_acceso,
					acc.ubicacion_id                                              AS ubicacion_id
				FROM accesos AS acc
					JOIN accesos_visitas AS av ON acc.tipo_id = av.id AND acc.tipo_modelo = :clase
					JOIN personas AS p ON p.id = av.persona_id
					LEFT JOIN credenciales AS c ON av.credencial_id = c.id";
		$params = [
			':clase'       => Acceso::getClassIndex(new self()),
			':tipo_acceso' => Acceso::VISITANTE,
		];
		$where = ['p.borrado = 0', 'acc.hora_egreso IS NULL'];
		if ($estatus != 'todos') {
			if ($estatus == 'activos')
				array_push($where, 'c.estatus = 1', 'acc.hora_egreso IS NULL');
			if ($estatus == 'inactivos')
				array_push($where, 'c.estatus = 0');
		}
		if ($id_ubicacion > 0) {
			array_push($where, 'acc.ubicacion_id = :ubicacion_id');
			$params[':ubicacion_id'] = $id_ubicacion;
		}
		if ($fecha) {
			array_push($where, "DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') = :fecha");
			$params[':fecha'] = $fecha->format('d/m/Y');
		}
		if (count($where) > 0) {
			$sql .= "\n WHERE";
			for ($i = 0; $i < count($where); $i++) {
				if ($i < count($where) - 1) {
					$sql .= " {$where[$i]} AND";
				} else {
					$sql .= " {$where[$i]}";
				}
			}
		}
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::SELECT, $sql . " LIMIT 0, 10", $params);
		if (is_array($res) && count($res) > 0) {
			return $res;
		}

		return [];
	}

	public static function historico() {
		$conn = new Conexiones;
		$sql = "SELECT
					acc.ubicacion_id,
					e.nombre                                  AS ubicacion,
					:acceso                                   AS acceso,
					:tipo                                     AS tipo,
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
					CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso,
					auth_p.documento AS autorizante_documento
				FROM accesos AS acc
					JOIN accesos_visitas AS v ON acc.tipo_id = v.id AND acc.tipo_modelo = :clase
					INNER JOIN ubicaciones AS e ON e.id = acc.ubicacion_id
					INNER JOIN personas AS p ON p.id = v.persona_id
					JOIN personas AS pin ON acc.persona_id_ingreso = pin.id
					LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
					JOIN empleados AS auth ON v.autorizante_id = auth.id
					JOIN personas AS auth_p ON auth_p.id = auth.persona_id;";
		$lista_historicos = $conn->consulta(Conexiones::SELECT, $sql, [
			':clase'  => Acceso::getClassIndex(new self()),
			':tipo'   => Acceso::VISITANTE,
			':acceso' => Acceso::tipoAccesoToString(Acceso::VISITANTE),
		]);
		if (is_array($lista_historicos) && count($lista_historicos) > 0) {
			return $lista_historicos;
		}

		return [];
	}

	/**
	 * @param string         $documento
	 * @param int            $ubicacion_id
	 * @param \DateTime $fecha
	 * @return bool
	 */
	public static function enVisita($documento, $ubicacion_id, $fecha = null) {
		$class_str = Acceso::getClassIndex(new self());
		if (!empty($documento)) {
			$sql = "SELECT
						acc.id
					FROM accesos AS acc
						JOIN accesos_visitas AS av ON acc.tipo_id = av.id AND acc.tipo_modelo = :clase
						JOIN personas AS p ON av.persona_id = p.id
						LEFT JOIN credenciales AS cr ON cr.id=av.credencial_id";
			$params = [
				':documento' => $documento,
				':clase'     => $class_str,
			];
			$where = [
				"p.borrado = 0",
				"acc.hora_egreso IS NULL",
				"p.documento = :documento",
			];
			if ($ubicacion_id > 0) {
				array_push($where, 'acc.ubicacion_id = :ubicacion_id');
				$params[':ubicacion_id'] = $ubicacion_id;
			}
			if ($fecha) {
				array_push($where, "acc.hora_ingreso LIKE :fecha");
				$params[':fecha'] = '%'.$fecha->format('Y-m-d').'%';
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
			$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
			if (!empty($res) && is_array($res) && isset($res[0])) {
				return $res[0]['id'];
			}
		}

		return false;
	}


	/**
	 * @param string         $documento
	 * @param int            $ubicacion_id
	 * @param \DateTime $fecha
	 * @return bool
	 */
	public static function enVisitaTarjeta($documento, $ubicacion_id, $fecha = null) {
		$class_str = Acceso::getClassIndex(new self());
		if (!empty($documento)) {
			$sql = "SELECT acc.*,cr.acceso_id 
					FROM accesos AS acc
						JOIN accesos_visitas AS av ON acc.tipo_id = av.id AND acc.tipo_modelo = :clase
						JOIN personas AS p ON av.persona_id = p.id
						LEFT JOIN credenciales AS cr ON cr.id=av.credencial_id";
			$params = [
				':documento' => $documento,
				':clase'     => $class_str,
			];
			$where = [
				"p.borrado = 0",
				"acc.hora_egreso IS NULL",
				"p.documento = :documento",
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
			$sql .= " order by acc.id desc limit 1";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
			if (!empty($res) && is_array($res) && isset($res[0])) {

				$arrayAccesosTarjeta = array(
					"id"  => $res[0]['id'],
					"hora_egreso"  => $res[0]['hora_egreso'],
					"entradaVisita"  => ($res[0]['acceso_id'] == 1),
				);
                return $arrayAccesosTarjeta;
            }
		}

		return $arrayAccesosTarjeta = array(
			"id"			=> null,
			"hora_egreso"	=> null,
			"entradaVisita" => null,
		);

	}




	/**
	 * @param int     $acceso_id
	 * @param Persona $persona_egreso
	 * @param int     $tipo_egreso
	 * @return bool
	 */
	public function terminar($acceso_id, $persona_egreso, $tipo_egreso) {
		$sql = <<<SQL
UPDATE accesos AS acc
	JOIN accesos_visitas AS av ON acc.tipo_id = av.id AND acc.tipo_modelo = :clase
	JOIN credenciales AS c ON av.credencial_id = c.id
SET
	c.estatus             = 0,
	acc.hora_egreso       = NOW(),
	acc.persona_id_egreso = :persona_id_egreso,
	acc.tipo_egreso       = :tipo_egreso
WHERE acc.id = :acc_id AND av.id = :id;
SQL;
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::UPDATE, $sql, [
			':acc_id'            => $acceso_id,
			':id'                => $this->id,
			':clase'             => Acceso::getClassIndex(new self()),
			':persona_id_egreso' => $persona_egreso->id,
			':tipo_egreso'       => $tipo_egreso,
		]);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			$datos = (array)$this;
			$datos['modelo'] = 'acceso_visitas';
			Logger::event('fin_visita_visitas', $datos);

			return true;
		}

		return false;
	}

	/**
	 * Inserta en la base de datos el registro correspondiente a una visita
	 * @return array|int|string
	 */
	public function alta() {
		if ($this->validar() && $this->credencial->activar()) {
			$sql = "INSERT INTO accesos_visitas (persona_id, autorizante_id, credencial_id, origen, destino)
			VALUE (:persona_id, :autorizante_id, :credencial_id, :origen, :destino)";
			$data = [
				':persona_id'     => $this->persona->id,
				':autorizante_id' => $this->autorizante->id,
				':credencial_id'  => $this->credencial->id,
				':origen'         => $this->origen,
				':destino'        => $this->destino,
			];
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::INSERT, $sql, $data);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				$this->id = (int)$res;
				$datos = (array)$this;
				$datos['modelo'] = 'acceso_visitas';
				Logger::event('alta', $datos);

				$this->credencial->update_tipo_acceso(1);

				return true;
			}
		}

		return false;
	}

	/**
	 * Valida que lo datos ingresados son correctos y/o existen en la base de datos
	 * @return bool
	 */
	public function validar() {
		if (!empty($this->persona) && !empty($this->persona->id) && $this->persona instanceof Persona)
			$this->persona_id = $this->persona->id;
		if (!empty($this->autorizante) && !empty($this->autorizante->id) && $this->autorizante instanceof Empleado)
			$this->autorizante_id = $this->autorizante->id;
		if (!empty($this->credencial) && !empty($this->credencial->id) && $this->credencial instanceof Credencial)
			$this->credencial_id = $this->credencial->id;
		/** @var array $reglas */
		$reglas = [
			'persona_id'     => ['required', 'numeric'],
			'autorizante_id' => ['required', 'numeric'],
			'origen'         => ['texto', 'required', 'max_length(64)'],
			'destino'        => ['texto', 'required', 'max_length(64)'],
			'credencial_id'  => ['required', 'numeric'],
		];
		$input_names = [
			'persona_id'     => 'Persona',
			'autorizante_id' => 'Autorizante',
			'origen'         => 'Origen',
			'destino'        => 'Destino',
			'credencial_id'  => 'Credencial',
		];
		$validator = Validador::validate((array)$this, $reglas, $input_names);
		if ($validator->isSuccess()) {
			return true;
		}
		$this->errores = $validator->getErrors();

		return false;
	}

	public function baja() {
		// TODO: Implement baja() method.
	}

	public function modificacion() {
		// TODO: Implement modificacion() method.
	}
}