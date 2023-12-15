<?php namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Util;
use App\Helper\Validador;
use DateTime;
use FMT\Logger;
use FMT\Modelo;

class AccesoContratista extends Modelo {
	/** @var  int */
	public $id;
	/** @var ContratistaEmpleado */
	public $empleado;
	/** @var  Credencial */
	public $credencial;
	/** @var Usuario */
	public $usuario_egreso;
	/** @var int */
	public $tipo_acceso;
	/** @const Define el índice de la clase el la base de datos para la relación con la tabla correspondiente */
	const TIPO_MODEL = 3;

	/**
	 * @param int $id
	 */
	public static function obtener($id) {
		$sql = "SELECT id, empleado_id, credencial_id, :tipo_acceso AS tipo_acceso FROM accesos_contratistas WHERE id = :id;";
		if (is_numeric($id)) {
			if ($id > 0) {
				$params = [
					':id'          => $id,
					':tipo_acceso' => Acceso::CONTRATISTA,
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
	 * @return AccesoContratista
	 */
	public static function arrayToObject($res = []) {
		$obj = new self();
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->empleado = ContratistaEmpleado::obtener(isset($res['empleado_id']) ? (int)$res['empleado_id'] : 0);
		$obj->credencial = Credencial::obtener(isset($res['credencial_id']) ? (int)$res['credencial_id'] : 0);
		$obj->tipo_acceso = isset($res['tipo_acceso']) ? (int)$res['tipo_acceso'] : 0;

		return $obj;
	}

	/**
	 * @param int            $ubicacion_id
	 * @param \DateTime $fecha
	 * @return array|int|string
	 */
	static public function listar($ubicacion_id = 0, $fecha = null) {
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
					NULL                                                          AS origen,
					NULL                                                          AS destino,
					acc.observaciones                                             AS observaciones,
					cp.autorizante_id                                             AS autorizante_id,
					acc.tipo_id,
					acc.tipo_modelo,
					:tipo_acceso                                                  AS tipo_acceso,
					acc.ubicacion_id                                              AS ubicacion_id 
				FROM accesos AS acc
					JOIN accesos_contratistas AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = :clase
					JOIN contratista_personal AS cp ON cp.id = ac.empleado_id
					JOIN personas AS p ON p.id = cp.persona_id
					LEFT JOIN credenciales AS c ON ac.credencial_id = c.id";
		$params = [
			':clase'       => Acceso::getClassIndex(new self()),
			':tipo_acceso' => Acceso::CONTRATISTA,
		];
		$where = [
			"p.borrado = 0",
			"acc.hora_egreso IS NULL",
		];
		if ($ubicacion_id > 0) {
			$where[] = 'acc.ubicacion_id = :ubicacion_id';
			$params[':ubicacion_id'] = $ubicacion_id;
		}
		if ($fecha) {
			$where[] = "DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') = :fecha";
			$params[':fecha'] = $fecha->format('d/m/Y');
		}
		if (count($where) > 0) {
			$sql .= "\nWHERE " . implode(" AND ", $where);
		}
		$conex = new Conexiones();

		return $conex->consulta(Conexiones::SELECT, $sql . " LIMIT 0, 10", $params);
	}

	public static function historico() {
		$conn = new Conexiones;
		$sql = "SELECT
					acc.ubicacion_id,
					u.nombre                                                            AS ubicacion,
					:acceso                                                             AS acceso,
					:tipo                                                               AS tipo,
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
					auth_p.documento AS autorizante_documento
				FROM accesos AS acc
					JOIN accesos_contratistas AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = :clase
					JOIN ubicaciones AS u ON u.id = acc.ubicacion_id
					JOIN contratista_personal AS cp ON cp.id = ac.empleado_id
					JOIN personas AS p ON p.id = cp.persona_id
					JOIN personas AS pin ON acc.persona_id_ingreso = pin.id
					LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
					JOIN contratista_personal AS auth ON cp.autorizante_id = auth.id
					JOIN personas AS auth_p ON auth_p.id = auth.persona_id
					";
		$lista_historicos = $conn->consulta(Conexiones::SELECT, $sql, [
			':clase'  => Acceso::getClassIndex(self::class),
			':tipo'   => Acceso::CONTRATISTA,
			':acceso' => Acceso::tipoAccesoToString(Acceso::CONTRATISTA),
		]);
		if (is_array($lista_historicos) && count($lista_historicos) > 0) {
			return $lista_historicos;
		}

		return [];
	}

	/**
	 * @param string         $documento
	 * @param int       $ubicacion_id
	 * @param \DateTime $fecha
	 * @return bool
	 */
	public static function enVisita($documento, $ubicacion_id = null, $fecha = null) {
		$class_str = str_replace('\\', '/', static::class);
		if (!empty($documento) &&
			!empty($ubicacion_id)) {
			$sql = "SELECT
						acc.id
					FROM accesos AS acc
						JOIN accesos_contratistas AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = :clase
						JOIN contratista_personal AS cp ON ac.empleado_id = cp.id
						JOIN personas AS p ON cp.persona_id = p.id
						LEFT JOIN credenciales AS cr ON cr.id = ac.credencial_id";
			$conex = new Conexiones();
			$params = [
				':ubicacion_id' => $ubicacion_id,
				':documento'    => $documento,
				':clase'        => $class_str,
			];
			$where = [
				"p.borrado = 0",
				"acc.hora_egreso IS NULL",
				"p.documento = :documento",
				"cr.estatus = 1",
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
			$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
			if (!empty($res) && is_array($res) && isset($res[0])) {
				return $res[0]['id'];
			}
		}

		return false;
	}

	/**
	 * @param string         $documento
	 * @param int       $ubicacion_id
	 * @param \DateTime $fecha
	 * @return bool
	 */
	public static function enVisitaTarjeta($documento, $ubicacion_id = null, $fecha = null) {
		$class_str = Acceso::getClassIndex(new self());
		if (!empty($documento) &&
			!empty($ubicacion_id)) {
			$sql = "SELECT acc.*,cr.acceso_id 
					FROM accesos AS acc
						JOIN accesos_contratistas AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = :clase
						JOIN contratista_personal AS cp ON ac.empleado_id = cp.id
						JOIN personas AS p ON cp.persona_id = p.id
						LEFT JOIN credenciales AS cr ON cr.id = ac.credencial_id";
			$conex = new Conexiones();
			$params = [
				':ubicacion_id' => $ubicacion_id,
				':documento'    => $documento,
				':clase'        => $class_str,
			];
			$where = [
				"p.borrado = 0",
				"acc.hora_egreso IS NULL",
				"p.documento = :documento",
				"cr.estatus = 1",
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
			$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
			if (!empty($res) && is_array($res)) {
				$res	= !empty($res[0]) ? $res[0] : $res;

				$arrayAccesosTarjeta = array(
					"id"			=> $res['id'],
					"hora_egreso"	=> $res['hora_egreso'],
					"entradaVisita" => ($res['acceso_id'] == 1),
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

	public function alta() {
		if ($this->validar() && $this->credencial->activar()) {
			$conex = new Conexiones();
			$sql = "INSERT INTO accesos_contratistas (empleado_id, credencial_id) VALUE (:empleado_id, :credencial_id);";
			$res = $conex->consulta(Conexiones::INSERT, $sql, [':empleado_id' => $this->empleado->id, ':credencial_id' => $this->credencial->id]);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				$this->id = (int)$res;
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'acceso_contratistas';
				Logger::event('alta', $datos);

				$this->credencial->update_tipo_acceso(2);

				return true;
			}
		}

		return false;
	}

	public function validar() {
		$rules = [
			'empleado_id'   => ['required', 'numeric'],
			'credencial_id' => ['required', 'existe(credenciales,id)'],
			'art_inicio'    => ['antesDe(:hoy)'],
			'art_fin'       => ['despuesDe(:hoy)'],
		];
		$input_names = [
			'empleado_id'   => "Empleado",
			'credencial_id' => "Credencial",
			'art_inicio'    => "ART Inicio",
			'art_fin'       => "ART Fin",
			':hoy'          => 'Hoy',
		];
		$inputs = [
			'empleado_id'   => $this->empleado->id,
			'credencial_id' => $this->credencial->id,
			'art_inicio'    => $this->empleado->art_inicio,
			'art_fin'       => $this->empleado->art_fin,
			'hoy'           => new \DateTime(),
		];
		$validator = Validador::validate($inputs, $rules, $input_names);
		if ($validator->isSuccess() == true) {
			return true;
		} else {
			$this->errores = $validator->getErrors();

			return false;
		}
	}

	public function baja() {
	}

	/**
	 * @param int     $acceso_id
	 * @param Persona $persona_egreso
	 * @param int     $tipo_egreso
	 * @return bool
	 */
	public function terminar($acceso_id, $persona_egreso, $tipo_egreso) {
		$sql = "UPDATE accesos acc
					JOIN accesos_contratistas AS ac ON acc.tipo_id = ac.id
					JOIN credenciales AS c ON ac.credencial_id = c.id
				SET c.estatus           = 0,
					acc.hora_egreso       = NOW(),
					acc.persona_id_egreso = :persona_id_egreso,
					acc.tipo_egreso       = :tipo_egreso
				WHERE acc.id = :acc_id AND acc.tipo_id = :id AND acc.tipo_modelo = :clase;";
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::UPDATE, $sql, [
			':acc_id'            => $acceso_id,
			':id'                => $this->id,
			':clase'             => Acceso::getClassIndex(new self()),
			':persona_id_egreso' => $persona_egreso->id,
			':tipo_egreso'       => $tipo_egreso,
		]);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'acceso_contratistas';
			Logger::event('fin_visita_contratista', $datos);

			return true;
		}

		return false;
	}

	public function modificacion() {
		// TODO: Implement modificacion() method.
	}


	public static function validarManual($ubicacion, $empleado, $ingreso, $egreso, $usuario, $credencial, $acceso = null) {
		$inputs = [
			'ubicacion_id' => $ubicacion->id,
			'empleado'     => $empleado,
			'ingreso'      => $ingreso,
			'egreso'       => $egreso,
			'usuario_id'   => $usuario->id,
			'credencial'   => $credencial,
			'persona_id_ingreso' => $usuario->getEmpleado()->persona_id,
		];
		$rules = [
			'ubicacion_id' => ['required', 'numeric',],
			'empleado'     => ['empleado'    => function ($input) {
				return ($input instanceof ContratistaEmpleado) && !empty($input->id);
			}, 'puede_entrar(:ubicacion_id)' => function ($input, $param1) {
				$p = $input->obtenerUbicacion($param1);
				return !empty($p) ? true : false;
			}],
			'usuario_id'   => ['required', 'numeric'],
			'ingreso'      => ['fecha'],
			'egreso'       => ['fecha', 'despuesDe(:ingreso)'],
			'credencial'   => ['required', 'numeric'],
			'persona_id_ingreso'   => ['empleado_x_usuario', 'numeric',],
		];
		$naming = [
			'ubicacion_id' => "Ubicación",
			'empleado'     => "Empleado",
			'ingreso'      => "Ingreso",
			'egreso'       => "Egreso",
			'usuario_id'   => "Usuario",
			'credencial'  => "Credencial",
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
			'empleado'     => "El <strong>Contratista</strong> no es válido.",
			'puede_entrar' => "El <strong>Contratista</strong> no tiene permiso para acceder al <strong>Edificio</strong> seleccionado.",
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
	public static function altaManual($ubicacion, $empleado, $ingreso, $egreso, $usuario, $credencial, $observaciones = null) {
		$validator = static::validarManual($ubicacion, $empleado, $ingreso, $egreso, $usuario, $credencial);
		if ($validator === true) {
			$sql = "INSERT INTO accesos_contratistas(empleado_id, credencial_id) VALUE (:empleado_id, :credencial_id);
		INSERT INTO accesos(tipo_id, tipo_modelo, hora_ingreso, persona_id_ingreso, tipo_ingreso, hora_egreso, persona_id_egreso, tipo_egreso, observaciones, ubicacion_id) 
			VALUE (LAST_INSERT_ID(), :clase, :hora_ingreso, :persona_id_ingreso, :tipo_ingreso, :hora_egreso, :persona_id_egreso, :tipo_egreso, :observaciones, :ubicacion_id)";
			$params = [
				":empleado_id"        => $empleado->id,
				':clase'              => Acceso::getClassIndex(new self()),
				":hora_ingreso"       => ($ingreso instanceof DateTime) ? $ingreso->format('Y-m-d H:i:s') : $ingreso,
				":persona_id_ingreso" => $usuario->getEmpleado()->persona_id,
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
				$params['modelo'] = 'acceso_contratistas';
				Logger::event('altaManual', $params);
				return true;
			}
		}

		return $validator;
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
				cp.id AS empleado_id,
				p.id AS persona_id
			FROM personas AS p
			INNER JOIN contratista_personal AS cp ON cp.persona_id = p.id
            INNER JOIN contratista_x_ubicacion AS cxu ON cxu.personal_id = cp.id AND cxu.ubicacion_id = :ubicacion_id
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
						accesos_contratistas AS ae
						JOIN accesos AS acc ON (acc.tipo_id = ae.id AND acc.tipo_modelo = :clase)
					WHERE acc.hora_egreso IS NULL
					      AND DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') = :fecha
					
				GROUP BY acc.hora_ingreso
			) AS a ON a.empleado_id = cp.id
			WHERE (p.documento LIKE CONCAT('%', :documento, '%')) AND p.borrado = 0 ";
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
				$re['empleado'] = ContratistaEmpleado::obtener($re['empleado_id']);
				$re['ubicacion'] = Ubicacion::obtener($re['ubicacion_id']);
				$lista[] = [
					'documento' => $re['empleado']->persona->documento,
					'registro'  => $re,
				];
			}
		}

		return $lista;
	}
}