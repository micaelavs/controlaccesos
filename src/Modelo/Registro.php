<?php

namespace App\Modelo;

use App\Modelo\Modelo;
use App\Helper\Validador;
use App\Helper\Conexiones;
use FMT\Logger;
use VARIANT;

class Registro extends Modelo {

	/** @var Ubicacion */
	public $ubicacion;
	/** @var Persona */
	public $persona;
    /** @var Empleado */
	public $empleado;
	/** @var Empleado */
	public $autorizante;
	/** @var DateTime */
	public $fecha;
	/** @var DateTime */
	public $hora_ingreso;
	/** @var DateTime */
	public $hora_egreso;
	/** @var String */
	public $observaciones;
	/** @var Usuario */
	public $usuario;
	/** @var Credencial */
	public $credencial;
	/** @var Visita */
	public $visita;
	/** @var String */
	public $origen;
	/** @var String */
	public $destino;

    static $tipo_validacion = null;
    const CARGA_INDIVIDUAL = 1;
    const CARGA_CONTRATISTA = 2;
    const CARGA_VISITA = 3;
    const SIN_CIERRE = 4;

	/**
	 * @param $id
	 * @return Registro
	 */
	static public function obtener($id = null) {
		return static::arrayToObject();
	}

	static public function listar_accesos($params = array(), $rca = null){

		$campos    = 'id, 
					fecha_ingreso, 
					hora_ingreso,
					observaciones, 
					tipo_egreso, 
					tipo_id, 
					tipo_ingreso, 
					tipo_modelo, 
					persona_id_egreso, 
					persona_id_ingreso, 
					ubicacion_nombre, 
					ingreso_empleado_id, 
					ingreso_usuario_persona_id, 
					ingreso_usuario_persona_documento, 
					ingreso_usuario_persona_nombre, 
					persona_id, 
					persona_documento, 
					persona_nombre,
					credencial_codigo,
					origen,
					destino,
					autorizante_persona_documento,
					autorizante_persona_nombre';

		$sql_params = [];

		$where = [];

		$condicion = "";

		$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo'])) ? 'tipo' : $params['order']['campo'];
		$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']))   ? 'asc' : $params['order']['dir'];
		$params['start']  = (!isset($params['start'])  || empty($params['start']))  ? 0 :
		$params['start'];
		$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght'])) ? 10 :
		$params['lenght'];
		$params['search'] = (!isset($params['search']) || empty($params['search'])) ? '' :
		$params['search'];

		$default_params = [
			'filtros'   => [
				'ubicacion'   	=> null,
				'tipo' 	=> null,
				'fecha_desde' 	=> null,
				'dependencias_autorizadas' 	=> null,
			]
		];

		$params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
		$params = array_merge($default_params, $params);

		/*Filtros */
		if(!empty($params['filtros']['ubicacion'])){
		$where [] = "(u.id = :ubicacion)";
		$sql_params[':ubicacion']    = $params['filtros']['ubicacion'];

		}

		if(!empty($params['filtros']['tipo_acceso'])){
		$where [] = "(acc.tipo_modelo = :tipo_acceso)";
		$sql_params[':tipo_acceso']    = $params['filtros']['tipo_acceso'];

		}

		if(!empty($params['filtros']['fecha_desde'])){
			$where [] = "DATE_FORMAT(acc.hora_ingreso, '%Y-%m-%d') >= :fecha_desde";
			$fecha = \DateTime::createFromFormat('d/m/Y', (String)$params['filtros']['fecha_desde'])->format('Y-m-d');
			$sql_params[':fecha_desde']    = $fecha;
		}

		if(!empty($params['filtros']['dependencias_autorizadas'])){
			$dependencias = $params['filtros']['dependencias_autorizadas'];
			$where [] = "edp.id_dependencia_principal in (:dependencias)";
			$sql_params[':dependencias']    = $dependencias;
		}

		$condicion .= !empty($where) ? ' WHERE ' . \implode(' AND ',$where) : '';

		if(!empty($params['search'])){
		$indice = 0;
		$search[]   = <<<SQL
		(p.nombre like :search{$indice} OR pin.nombre like :search{$indice} OR p.apellido like :search{$indice} OR pin.apellido like :search{$indice} OR p.documento like :search{$indice} OR pin.documento like :search{$indice} OR acc.observaciones like :search{$indice} OR u.nombre like :search{$indice}) 
		SQL;
		$texto = $params['search'];
		$sql_params[":search{$indice}"] = "%{$texto}%";

		$buscar =  implode(' AND ', $search);
		$condicion .= empty($condicion) ? "WHERE {$buscar}" : " AND {$buscar} ";

		}

		if(!empty($_SESSION["consulta"]) && ($_SESSION["start"] != $params['start'] || $_SESSION["lenght"] != $params['lenght'])){
		$_SESSION["start"] = $params['start'];
		$_SESSION["lenght"] = $params['lenght'];

		$data = self::listadoAjax($campos, $_SESSION["consulta"], $params, $_SESSION["sql_params"]);
		$data['recordsTotal'] = $_SESSION["total"];
		return $data;
		}
		
		$condicion .= empty($condicion) ? "WHERE acc.hora_egreso IS NULL" : " AND acc.hora_egreso IS NULL";

		//EMPLEADO
		$join0 = <<<SQL
		FROM accesos AS acc
		JOIN ubicaciones AS u ON acc.ubicacion_id = u.id
		JOIN organismos AS o ON u.organismo_id = o.id
		JOIN accesos_empleados AS a ON (acc.tipo_id = a.id AND acc.tipo_modelo = 1)
		JOIN empleados AS e ON a.empleado_id = e.id
		JOIN personas AS p ON e.persona_id = p.id
		LEFT JOIN empleados AS ein ON ein.persona_id = acc.persona_id_ingreso
		LEFT JOIN empleado_dependencia_principal edp ON (edp.id_empleado = ein.id AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 ) 
		LEFT JOIN dependencias AS din ON (edp.id_dependencia_principal = din.id AND ISNULL(din.fecha_hasta)) 
		LEFT JOIN personas AS pin ON ein.persona_id = pin.id
		$condicion
		SQL;

		//VISITAS
		$join1 = <<<SQL
		FROM accesos AS acc
		JOIN ubicaciones AS u ON acc.ubicacion_id = u.id
		JOIN organismos AS o ON u.organismo_id = o.id
		JOIN accesos_visitas AS a ON (acc.tipo_id = a.id AND acc.tipo_modelo = 2)
		JOIN personas AS p ON a.persona_id = p.id
		JOIN credenciales AS c ON a.credencial_id = c.id

		JOIN empleados AS ein ON ein.persona_id=acc.persona_id_ingreso
		LEFT JOIN empleado_dependencia_principal edp ON (edp.id_empleado = ein.id  AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 )
		LEFT JOIN dependencias AS din ON (edp.id_dependencia_principal = din.id AND ISNULL(din.fecha_hasta))
		LEFT JOIN personas AS pin ON ein.persona_id = pin.id

		LEFT JOIN empleados AS aute ON a.autorizante_id = aute.id
		LEFT JOIN empleado_dependencia_principal aute_dp ON (aute_dp.id_empleado = aute.id AND ISNULL(aute_dp.fecha_hasta) AND aute_dp.borrado = 0 )
		LEFT JOIN dependencias AS autd ON (aute_dp.id_dependencia_principal = autd.id AND ISNULL(autd.fecha_hasta)) 
		LEFT JOIN personas AS autp ON aute.persona_id = autp.id
		$condicion
		SQL;

		//CONTRATISTAS
		$join2 = <<<SQL
		FROM accesos AS acc
		JOIN ubicaciones AS u ON acc.ubicacion_id = u.id
		JOIN organismos AS o ON u.organismo_id = o.id
		JOIN accesos_contratistas AS a ON (acc.tipo_id = a.id AND acc.tipo_modelo = 3)
		JOIN contratista_personal AS cp ON a.empleado_id = cp.id
		JOIN personas AS p ON cp.persona_id = p.id
		JOIN credenciales AS c ON a.credencial_id = c.id     
		JOIN empleados AS ein ON ein.persona_id=acc.persona_id_ingreso
		LEFT JOIN empleado_dependencia_principal edp ON (edp.id_empleado = ein.id AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 ) 
		LEFT JOIN dependencias AS din ON (edp.id_dependencia_principal = din.id AND ISNULL(din.fecha_hasta))
		LEFT JOIN personas AS pin ON ein.persona_id = pin.id
		LEFT JOIN empleados AS aute ON cp.autorizante_id = aute.id
		LEFT JOIN empleado_dependencia_principal aute_dp ON (aute_dp.id_empleado = aute.id  AND ISNULL(aute_dp.fecha_hasta) AND aute_dp.borrado = 0 )
		LEFT JOIN dependencias AS autd ON (aute_dp.id_dependencia_principal = autd.id AND ISNULL(autd.fecha_hasta))
		LEFT JOIN personas AS autp ON aute.persona_id = autp.id
		$condicion
		SQL;

		//ENROLADA
		$join3 = <<<SQL
		FROM accesos AS acc
		JOIN ubicaciones AS u ON acc.ubicacion_id = u.id
		JOIN organismos AS o ON u.organismo_id = o.id
		JOIN accesos_visitas_enroladas AS a ON (acc.tipo_id = a.id AND acc.tipo_modelo = 4)
		JOIN visitas AS v ON (a.visita_id = v.visita_id)
		JOIN personas AS p ON v.persona_id = p.id
		LEFT JOIN empleados AS ein ON ein.persona_id = acc.persona_id_ingreso
		LEFT JOIN empleado_dependencia_principal edp ON (edp.id_empleado = ein.id AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 ) 
		LEFT JOIN dependencias AS din ON (edp.id_dependencia_principal = din.id AND ISNULL(din.fecha_hasta)) 
		LEFT JOIN personas AS pin ON ein.persona_id = pin.id
		LEFT JOIN empleados AS aute ON v.autorizante_id = aute.id
		LEFT JOIN empleado_dependencia_principal aute_dp ON (aute_dp.id_empleado = aute.id AND ISNULL(aute_dp.fecha_hasta) AND aute_dp.borrado = 0 )
		LEFT JOIN dependencias AS autd ON (aute_dp.id_dependencia_principal = autd.id AND ISNULL(autd.fecha_hasta)) 
		LEFT JOIN personas AS autp ON aute.persona_id = autp.id
		$condicion
		SQL;

		$join_rca = <<<SQL
			FROM accesos AS acc
			JOIN ubicaciones AS u ON acc.ubicacion_id = u.id
			JOIN organismos AS o ON u.organismo_id = o.id
			JOIN accesos_empleados AS a ON (acc.tipo_id = a.id AND acc.tipo_modelo = 1)
			JOIN empleados AS e ON a.empleado_id = e.id
			JOIN personas AS p ON e.persona_id = p.id
					JOIN empleado_dependencia_principal edp ON (edp.id_empleado = e.id AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 )
			LEFT JOIN empleados AS ein ON ein.persona_id = acc.persona_id_ingreso
			LEFT JOIN empleado_dependencia_principal edpi ON (edpi.id_empleado = ein.id AND ISNULL(edpi.fecha_hasta) AND edpi.borrado = 0 ) 
			LEFT JOIN dependencias AS din ON (edpi.id_dependencia_principal = din.id AND ISNULL(din.fecha_hasta)) 
			LEFT JOIN personas AS pin ON ein.persona_id = pin.id
			$condicion
		SQL;
	
			
		$common_fields = <<<SQL
		acc.id                                                                                      AS id,
		acc.hora_ingreso                                         						            AS fecha_ingreso,
		DATE_FORMAT(acc.hora_ingreso, '%H:%i:%s')                                                      AS hora_ingreso,
		acc.observaciones,
		acc.tipo_egreso,
		acc.tipo_id,
		acc.tipo_ingreso,
		(CASE acc.tipo_modelo
		WHEN 1
			THEN 'Empleado'
		WHEN 2
			THEN 'Visita'
		WHEN 3
			THEN 'Contratista'
		WHEN 4
			THEN 'Visita Enrolada'
		ELSE 'Desconocido' END)                                                                    AS tipo_modelo,
		acc.persona_id_egreso,
		acc.persona_id_ingreso,
		CONCAT(COALESCE(u.nombre, ''), ' | ', COALESCE(u.calle, ''), ', ', COALESCE(u.numero, ''))  AS ubicacion_nombre,
		ein.id                                                                                      AS ingreso_empleado_id,
		pin.id                                                                                      AS ingreso_usuario_persona_id,
		pin.documento                                                                               AS ingreso_usuario_persona_documento,
		CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido,''))                            AS ingreso_usuario_persona_nombre,
		p.id                                                                                        AS persona_id,
		p.documento                                                                                 AS persona_documento,
		CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, ''))                               AS persona_nombre,
		SQL;
		
		if($rca){
			$consulta = <<<SQL
				SELECT
					{$common_fields}
					NULL           AS credencial_codigo,
					NULL           AS origen,
					NULL           AS destino,
					NULL           AS autorizante_persona_documento,
					NULL           AS autorizante_persona_nombre
					{$join_rca}
				SQL;
		}else{
			$consulta = <<<SQL
				SELECT
					{$common_fields}
					c.codigo       AS credencial_codigo,
					a.origen       AS origen,
					a.destino      AS destino,
					autp.documento AS autorizante_persona_documento,
					CONCAT(COALESCE(autp.nombre, ''), ' ', COALESCE(autp.apellido, ''))    AS autorizante_persona_nombre
					{$join1}
				UNION
				SELECT
					{$common_fields}
					NULL           AS credencial_codigo,
					NULL           AS origen,
					NULL           AS destino,
					NULL           AS autorizante_persona_documento,
					NULL           AS autorizante_persona_nombre
					{$join0}
				UNION
				SELECT
					{$common_fields}
					c.codigo       AS credencial_codigo,
					NULL           AS origen,
					NULL           AS destino,
					autp.documento AS autorizante_persona_documento,
					CONCAT(COALESCE(autp.nombre, ''), ' ', COALESCE(autp.apellido, ''))    AS autorizante_persona_nombre
					{$join2}
				UNION
				SELECT
					{$common_fields}
					NULL           AS credencial_codigo,
					NULL           AS origen,
					NULL           AS destino,
					autp.documento AS autorizante_persona_documento,
					CONCAT(COALESCE(autp.nombre, ''), ' ', COALESCE(autp.apellido, ''))    AS autorizante_persona_nombre
					{$join3}
				
			SQL;
		}

		$data = self::listadoAjax($campos, $consulta, $params, $sql_params);
		return $data;

	}

	/**
	 * @param $res
	 * @return Registro
	 */
	static public function arrayToObject($res = [])
	{

		$obj = new self();
        $obj->persona = Persona::obtener(null);
        $obj->ubicacion = Ubicacion::obtener(null);
        $obj->empleado = Empleado::obtener(null);
        $obj->autorizante = Empleado::obtener(null);
        $obj->fecha = null;
        $obj->hora_ingreso = null;
        $obj->hora_egreso = null;
        $obj->observaciones = null;
        $obj->usuario = null;
        $obj->credencial = Credencial::obtener(null);
        $obj->visita = Visita::obtener(null);
        $obj->origen = null;
        $obj->destino = null;

        return $obj;
	}

    public function carga_individual(){
		$res = AccesoEmpleado::altaManual(
			$this->ubicacion,
			$this->empleado,
			$this->hora_ingreso,
			$this->hora_egreso,
			$this->usuario,
			$this->observaciones);
		
		$this->errores = $res;
		return (is_array($res)) ? false : true;

    }

	public function carga_individual_contratista(){
		$res = AccesoContratista::altaManual(
			$this->ubicacion,
			$this->empleado,
			$this->hora_ingreso,
			$this->hora_egreso,
			$this->usuario,
			$this->credencial->id,
			$this->observaciones);
		
		$this->errores = $res;
		return (is_array($res)) ? false : true;

    }

	public function carga_individual_visita(){
		$res = AccesoVisita::altaManual(
					 $this->ubicacion,
					 $this->visita->persona,
					 $this->hora_ingreso,
					 $this->hora_egreso,
					 $this->origen,
					 $this->destino,
					 $this->autorizante->id,
					 $this->usuario,
					 $this->credencial->id,
					 $this->observaciones);

		$this->errores = $res;
		return (is_array($res)) ? false : true;
    }

	public function alta() {return false;}

	public function validar() {

		$reglas = [];

		if($this::$tipo_validacion == $this::CARGA_INDIVIDUAL){
			$reglas = [
				'empleado' => [
					'existe' => function ($obj) {
						/** @var Empleado $obj */
						if (!empty($obj) &&
							!empty($obj->id) &&
							$obj->id > 0
						) {
							return true;
						}
	
						return false;
					},
				],
				'ubicacion' => [
					'existe' => function ($obj) {
						/** @var Ubicacion $obj */
						if (!empty($obj) &&
							!empty($obj->id) &&
							$obj->id > 0
						) {
							return true;
						}
	
						return false;
					},
				],
				'fecha'   => ['required'],
				'hora_ingreso'   => ['required', 'texto'],
				'hora_egreso'   => ['required', 'texto'],
				'observaciones'   => ['texto'],
			];
			$nombres = [
				'empleado'     => 'Empleado',
				'ubicacion' => 'Ubicación',
				'hora_ingreso'       => 'Hora Ingreso',
				'hora_egreso'       => 'Hora Egreso',
				'fecha'       => 'Fecha',
				'observaciones'       => 'Observaciones',
			];
		}else if($this::$tipo_validacion == $this::CARGA_CONTRATISTA){
			$reglas = [
				'empleado' => [
					'existe' => function ($obj) {
						/** @var Empleado $obj */
						if (!empty($obj) &&
							!empty($obj->id) &&
							$obj->id > 0
						) {
							return true;
						}
	
						return false;
					},
				],
				'ubicacion' => [
					'existe' => function ($obj) {
						/** @var Ubicacion $obj */
						if (!empty($obj) &&
							!empty($obj->id) &&
							$obj->id > 0
						) {
							return true;
						}
	
						return false;
					},
				],
				'credencial' => [
					'existe' => function ($obj) {
						/** @var Credencial $obj */
						if (!empty($obj) &&
							!empty($obj->id) &&
							$obj->id > 0
						) {
							return true;
						}
	
						return false;
					},
				],
				'fecha'   => ['required'],
				'hora_ingreso'   => ['required', 'texto'],
				'hora_egreso'   => ['required', 'texto'],
				'observaciones'   => ['texto'],
			];
			$nombres = [
				'empleado'     => 'Empleado',
				'ubicacion' => 'Ubicación',
				'hora_ingreso'       => 'Hora Ingreso',
				'hora_egreso'       => 'Hora Egreso',
				'fecha'       => 'Fecha',
				'credencial'       => 'Credencial',
				'observaciones'       => 'Observaciones',
			];
		}else if($this::$tipo_validacion == $this::CARGA_VISITA){
			$reglas = [
				'autorizante' => [
					'existe' => function ($obj) {
						/** @var Empleado $obj */
						if (!empty($obj) && !empty($obj->id) && $obj->id > 0 && $obj->tiene_contrato()) {
							return true;
						}
						return false;
					},
				],
				'ubicacion' => [
					'existe' => function ($obj) {
						/** @var Ubicacion $obj */
						if (!empty($obj) &&
							!empty($obj->id) &&
							$obj->id > 0
						) {
							return true;
						}
	
						return false;
					},
				],
				'credencial' => [
					'existe' => function ($obj) {
						/** @var Credencial $obj */
						if (!empty($obj) &&
							!empty($obj->id) &&
							$obj->id > 0
						) {
							return true;
						}
	
						return false;
					},
				],
				'fecha'   => ['required'],
				'hora_ingreso'   => ['required', 'texto'],
				'hora_egreso'   => ['required', 'texto'],
				'origen'   => ['required', 'texto'],
				'destino'   => ['required', 'texto'],
				'observaciones'   => ['texto'],
			];
			$nombres = [
				'visita'     => 'Visita',
				'empleado'     => 'Empleado',
				'autorizante'     => 'Autorizante',
				'ubicacion' => 'Ubicación',
				'hora_ingreso'       => 'Hora Ingreso',
				'hora_egreso'       => 'Hora Egreso',
				'fecha'       => 'Fecha',
				'credencial'       => 'Credencial',
				'observaciones'       => 'Observaciones',
			];
		}

		$validator = Validador::validate((array)$this, $reglas, $nombres);
		$validator->customErrors([
			'required'      => ' El Campo :attribute es requerido',
			'existe'      => ' :attribute no existente en los registros o no está activo',
		]);
		if ($validator->isSuccess()) {
			return true;
		} else {
			$this->errores = $validator->getErrors();
			return false;
		}
    }

	public function baja() {return false;}

	public function modificacion() {return false;}

}