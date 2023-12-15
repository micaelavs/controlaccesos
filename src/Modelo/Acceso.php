<?php

namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Util;
use App\Helper\Validador;
use FMT\Logger;
use App\Modelo\Modelo;
use App\Modelo\AccesoEmpleado;
use DateTime;

class Acceso extends Modelo
{
	// Campos para validación
	const EMPLEADO = 1;
	const VISITANTE = 2;
	const CONTRATISTA = 3;
	const VISITA_ENROLADA = 4;
	const VISITA_TARJETA_ENROLADA = 5;

	const DIRECCION_ENTRADA = 200;
	const DIRECCION_SALIDA = 201;
	/** Tipo de Registro de Ingreso/Egreso al momento que el Accesante lo solicita */
	const TIPO_REGISTRO_ONLINE = 1;
	/** Tipo de Registro de Ingreso/Egreso ejecutado manualmente */
	const TIPO_REGISTRO_OFFLINE = 2;
	/** Tipo de Registro de Ingreso/Egreso mediante reloj biométrico */
	const TIPO_REGISTRO_RELOJ = 3;

	const TIPO_COMISION_HORARIA = 4;
	/** Tipo de Registro de Ingreso/Egreso mediante reloj biométrico del edificio de MECON*/
	const TIPO_REGISTRO_RELOJ_BIOHACIENDA = 5;

	/** Tipo de Registro de Tarjeta Magnetica Enrolada para Visita  */
	const TIPO_REGISTRO_TARJETA_RELOJ = 6;

	/** Tipo de acceso TM */
	const TIPO_ACCESO_VISITA = 1;
	const TIPO_ACCESO_CONTRATISTA = 2;

	/** @var array Descriptor de clases asociadas al control de acceso */
	public static $clases = [
		self::EMPLEADO    => 'App/Modelo/AccesoEmpleado',
		self::VISITANTE   => 'App/Modelo/AccesoVisita',
		self::CONTRATISTA => 'App/Modelo/AccesoContratista',
		self::VISITA_ENROLADA => 'App/Modelo/AccesoVisitaEnrolada'
	];
	/** @var int */
	public $id;
	/** @var Persona Representación de la persona que solicita el acceso */
	public $persona;
	/** @var string Texto descriptivo de Posesiones del Accesante */
	public $observaciones;
	/** @var string Empresa, Institución o Particular del que proviene o es referido el Visitante */
	public $origen;
	// Tipos de Accesantes
	/** @var string Empleado, Oficina, Departamento o Piso al que se dirige el Visitante */
	public $destino;
	/** @var Credencial Credencial de Acceso */
	public $credencial;
	/** @var Empleado Empleado Autorizante del Acceso */
	public $autorizante;
	/** @var Ubicacion Ubicación al que se concede el Acceso */
	public $ubicacion;
	/** @var \DateTime Hora y Fecha de ingreso del Accesante */
	public $ingreso;
	/** @var \DateTime Hora y Fecha de egreso del Accesante */
	public $egreso;
	/** @var int identificador numérico que indica el tipo de Accesante */
	public $tipo_acceso;
	/** @var Empleado Representación del Empleado que solicita Acceso */
	public $empleado;
	/** @var ContratistaEmpleado  Representación del Empleado de Contratista que solicita Acceso */
	public $contratista_empleado;
	/** @var Visita  Representación de la visita enrolada que solicita Acceso */
	public $visita_enrolada;
	/** @var \DateTime Hora en la que se registra el Acceso */
	public $hora_ingreso;
	/** @var \DateTime Hora en la que se termina el Acceso */
	public $hora_egreso;
	/** @var string Representación del Modelo al que pertenece el Accesante: Visita, Empleado o Contratista */
	public $tipo_modelo;
	/** @var int ID del Objeto que corresponde al Modelo */
	public $tipo_id;
	/** @var Persona Usuario que registra el Ingreso */
	public $persona_ingreso;
	/** @var Persona Usuario que registra el Egreso */
	public $persona_egreso;
	/** @var int Medio del Registro de Ingreso */
	public $tipo_ingreso;
	/** @var string Forma textual del tipo de ingreso */
	public $tipo_ingreso_str;
	/** @var int Medio del registro de Egreso */
	public $tipo_egreso;
	/** @var string Forma textual del tipo de Egreso */
	public $tipo_egreso_str;
	/** @var AccesoEmpleado|AccesoContratista|AccesoVisita|AccesoVisitaEnrolada Modelo al que se refiere el registro de Acceso */
	public $modelo = "Acceso";

	public $es_tarjeta_magnetica;

	/**
	 * Ejecuta el cerrado de un acceso.
	 * @param int     $acceso_id
	 * @param Persona $persona_egreso
	 * @param int     $tipo_egreso
	 * @return array
	 */
	public static function terminar($acceso_id, $persona_egreso, $tipo_egreso)
	{
		$msj = 'No se pudo terminar la Visita';
		$terminado = false;
		if (!empty($acceso_id) && is_numeric($acceso_id) && $acceso_id > 0) {
			$sql = "SELECT acc.id AS acc_id, tipo_id AS id, tipo_modelo AS clase 
					FROM accesos AS acc WHERE acc.id = :id AND acc.hora_egreso IS NULL ";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, [':id' => $acceso_id]);
			if (is_array($res) && isset($res[0]) && is_array($res[0])) {
				$clase = str_replace('/', '\\', self::$clases[$res[0]['clase']]);
				$id = $res[0]['id'];
				$acc_id = $res[0]['acc_id'];
				$obj = call_user_func([$clase, 'obtener'], $id);
				if ($obj->terminar($acc_id, $persona_egreso, $tipo_egreso)) {
					$msj = 'Salida marcada';
					$terminado = true;
				}
			};
		}

		return compact('msj', 'terminado');
	}

	public  function agregarObservaciones()
	{

		$sql = "UPDATE accesos AS a
						SET
							a.observaciones       = :observaciones
							
						WHERE a.id = :id";
		$params = [
			":observaciones"      => $this->observaciones,
			":id"                 => $this->id,
		];

		$con = new Conexiones();
		$res = $con->consulta(Conexiones::UPDATE, $sql, $params);
		if (is_numeric($res)) {
			Logger::event('modificacion', $this);
			return true;
		}
		return false;
	}

	/**

	 * @param int       $ubicacion_id
	 * @param \DateTime $fecha
	 * @return array
	 */
	public static function listarPorUbicacionYFecha($ubicacion_id = null, $fecha = null)
	{
		$listaVisitas = AccesoVisita::listar($ubicacion_id, $fecha, 'activos');
		if (!is_array($listaVisitas)) {
			$listaVisitas = [];
		} else {
			foreach ($listaVisitas as &$visita) {
				$visita['autorizante'] = Empleado::obtener($visita['autorizante_id']);
			}
		}
		$listaEmpleados = AccesoEmpleado::listar($ubicacion_id, $fecha);
		if (!is_array($listaEmpleados)) {
			$listaEmpleados = [];
		}
		$listaContratistas = AccesoContratista::listar($ubicacion_id, $fecha);
		if (!is_array($listaContratistas)) {
			$listaContratistas = [];
		}

		return array_merge($listaVisitas, $listaEmpleados, $listaContratistas);
	}

	/**
	 * Lista los registros que no tiene Cierre
	 * @param $order
	 * @param $start
	 * @param $length
	 * @param $filtros
	 * @param $extras
	 * @return array
	 */
	public static function json_sin_cierre($order, $start, $length, $filtros, $extras)
	{
		try {
			$from0 = <<<SQL
		FROM accesos AS acc
		     JOIN ubicaciones AS u ON acc.ubicacion_id = u.id
		     JOIN organismos AS o ON u.organismo_id = o.id
		     JOIN accesos_visitas AS a ON (acc.tipo_id = a.id AND acc.tipo_modelo = :modelo0)
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
	     WHERE acc.hora_egreso IS NULL
SQL;

			$from1 = <<<SQL
		FROM accesos AS acc
		     JOIN ubicaciones AS u ON acc.ubicacion_id = u.id
		     JOIN organismos AS o ON u.organismo_id = o.id
		     JOIN accesos_empleados AS a ON (acc.tipo_id = a.id AND acc.tipo_modelo = :modelo1)
		     JOIN empleados AS e ON a.empleado_id = e.id
		     JOIN personas AS p ON e.persona_id = p.id
		     LEFT JOIN empleados AS ein ON ein.persona_id = acc.persona_id_ingreso
		     LEFT JOIN empleado_dependencia_principal edp ON (edp.id_empleado = ein.id AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 ) 
		     LEFT JOIN dependencias AS din ON (edp.id_dependencia_principal = din.id AND ISNULL(din.fecha_hasta)) 
		     LEFT JOIN personas AS pin ON ein.persona_id = pin.id
	     WHERE acc.hora_egreso IS NULL
SQL;

			$from2 = <<<SQL
		FROM accesos AS acc
		     JOIN ubicaciones AS u ON acc.ubicacion_id = u.id
		     JOIN organismos AS o ON u.organismo_id = o.id
		     JOIN accesos_contratistas AS a ON (acc.tipo_id = a.id AND acc.tipo_modelo = :modelo2)
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
	     WHERE acc.hora_egreso IS NULL
SQL;
			$from3 = <<<SQL
		FROM accesos AS acc
		     JOIN ubicaciones AS u ON acc.ubicacion_id = u.id
		     JOIN organismos AS o ON u.organismo_id = o.id
		     JOIN accesos_visitas_enroladas AS a ON (acc.tipo_id = a.id AND acc.tipo_modelo = :modelo3)
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
	     WHERE acc.hora_egreso IS NULL
SQL;

			$sql_contador = <<<SQL
SELECT COUNT(id) AS count FROM (
	select acc.id AS id {$from0}
	UNION 
	select acc.id AS id {$from1}
	UNION 
	select acc.id AS id {$from2}
	UNION 
	select acc.id AS id {$from3}
) AS resultado
SQL;
			$con = new Conexiones();
			$params = [
				':modelo0' => static::getClassIndex(new AccesoVisita()),
				':modelo1' => static::getClassIndex(new AccesoEmpleado()),
				':modelo2' => static::getClassIndex(new AccesoContratista()),
				':modelo3' => static::getClassIndex(new AccesoVisitaEnrolada()),
			];
			$recordsTotal = $recordsFiltered = $con->consulta(Conexiones::SELECT, $sql_contador, $params)[0]['count'];
			$filtros0 = [];
			$filtros1 = [];
			$filtros2 = [];
			$filtros3 = [];
			if (!empty($filtros)) {
				foreach (explode(' ', $filtros) as $index => $filtro) {
					$common_filters = <<<SQL
		     u.nombre LIKE :filtro{$index} OR
		     u.calle LIKE :filtro{$index} OR
		     u.numero LIKE :filtro{$index} OR
		     acc.tipo_modelo LIKE :filtro{$index} OR
		     p.documento LIKE :filtro{$index} OR
		     p.nombre LIKE :filtro{$index} OR
		     p.apellido LIKE :filtro{$index} OR
		     acc.tipo_ingreso LIKE :filtro{$index} OR
		     acc.hora_ingreso LIKE :filtro{$index} OR
		     acc.observaciones LIKE :filtro{$index} OR
		     pin.documento LIKE :filtro{$index} OR
		     pin.nombre LIKE :filtro{$index} OR
		     pin.apellido LIKE :filtro{$index}
SQL;
					$filtros0[] = <<<SQL
		 (
		     {$common_filters} OR
		     c.codigo LIKE :filtro{$index} OR
		     a.origen LIKE :filtro{$index} OR
		     a.destino LIKE :filtro{$index} OR
		     autp.documento LIKE :filtro{$index} OR
		     autp.nombre LIKE :filtro{$index} OR
		     autp.apellido LIKE :filtro{$index}
	     )
SQL;
					$filtros1[] = <<<SQL
		 (
		     {$common_filters} 
	     )
SQL;
					$filtros2[] = <<<SQL
		 (
		     {$common_filters} OR
		     c.codigo LIKE :filtro{$index} OR
		     autd.nombre LIKE :filtro{$index} OR
		     autp.documento LIKE :filtro{$index} OR
		     autp.nombre LIKE :filtro{$index} OR
		     autp.apellido LIKE :filtro{$index} 
	     )
SQL;
					$filtros3[] = <<<SQL
		 (
		     {$common_filters} 
	     )
SQL;
					$params[":filtro{$index}"] = "%{$filtro}%";
				}
			}
			if ($extras['ubicacion_id']) {
				$ubicacion = "(acc.ubicacion_id = :ubicacion_id)";
				$filtros0[] = $ubicacion;
				$filtros1[] = $ubicacion;
				$filtros2[] = $ubicacion;
				$filtros3[] = $ubicacion;
				$params[':ubicacion_id'] = $extras['ubicacion_id'];
			}
			if ($extras['fecha_ini']) {
				$hora_ingreso = "(DATE(acc.hora_ingreso) >= STR_TO_DATE(:fecha_ini, '%d/%m/%Y'))";
				$filtros0[] = $hora_ingreso;
				$filtros1[] = $hora_ingreso;
				$filtros2[] = $hora_ingreso;
				$filtros3[] = $hora_ingreso;
				$params[':fecha_ini'] = $extras['fecha_ini'];
			}
			if ($extras['tipo_acceso']) {
				$tipo_modelo = "(acc.tipo_modelo = :tipo_acceso)";
				$filtros0[] = $tipo_modelo;
				$filtros1[] = $tipo_modelo;
				$filtros2[] = $tipo_modelo;
				$filtros3[] = $tipo_modelo;
				$params[':tipo_acceso'] = $extras['tipo_acceso'];
			}
			if (!empty($filtros1)) {
				$from0 .= ' AND ' . implode(' AND ', $filtros0);
				$from1 .= ' AND ' . implode(' AND ', $filtros1);
				$from2 .= ' AND ' . implode(' AND ', $filtros2);
				$from3 .= ' AND ' . implode(' AND ', $filtros3);
				$sql_contador = <<<SQL
SELECT COUNT(id) AS count FROM (
	select acc.id AS id {$from0}
	UNION 
	select acc.id AS id {$from1}
	UNION 
	select acc.id AS id {$from2}
	UNION 
	select acc.id AS id {$from3}
) AS resultado
SQL;
				$recordsFiltered = $con->consulta(Conexiones::SELECT, $sql_contador, $params)[0]['count'];
			}
			$common_fields = <<<SQL
	acc.id                                                                                      AS acc_id,
	acc.hora_ingreso                                         						            AS fecha_ingreso,
	DATE_FORMAT(acc.hora_ingreso, '%H:%i:%s')                                                      AS hora_ingreso,
	acc.observaciones,
	acc.tipo_egreso,
	acc.tipo_id,
	acc.tipo_ingreso,
	(CASE acc.tipo_modelo
	 WHEN :modelo1
		 THEN 'Empleado'
	 WHEN :modelo0
		 THEN 'Visita'
	 WHEN :modelo2
		 THEN 'Contratista'
	 WHEN :modelo3
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
			$sql = <<<SQL
	SELECT *
	FROM (
	     SELECT
		     {$common_fields}
		     c.codigo       AS credencial_codigo,
		     a.origen       AS origen,
		     a.destino      AS destino,
		     autp.documento AS autorizante_persona_documento,
		     CONCAT(COALESCE(autp.nombre, ''), ' ', COALESCE(autp.apellido, ''))    AS autorizante_persona_nombre
	     {$from0}
	     UNION
	     SELECT
		     {$common_fields}
		     NULL           AS credencial_codigo,
		     NULL           AS origen,
		     NULL           AS destino,
		     NULL           AS autorizante_persona_documento,
		     NULL           AS autorizante_persona_nombre
	     {$from1}
	     UNION
	     SELECT
		     {$common_fields}
		     c.codigo       AS credencial_codigo,
		     NULL           AS origen,
		     NULL           AS destino,
		     autp.documento AS autorizante_persona_documento,
		     CONCAT(COALESCE(autp.nombre, ''), ' ', COALESCE(autp.apellido, ''))    AS autorizante_persona_nombre
	     {$from2}
	      UNION
	     SELECT
		     {$common_fields}
		     NULL           AS credencial_codigo,
		     NULL           AS origen,
		     NULL           AS destino,
		     autp.documento AS autorizante_persona_documento,
		     CONCAT(COALESCE(autp.nombre, ''), ' ', COALESCE(autp.apellido, ''))    AS autorizante_persona_nombre
	     {$from3}
     ) AS resultado
SQL;
			if ($order) {
				$columns = [
					'acc_id',
					'ubicacion_nombre',
					'tipo_modelo',
					'credencial_codigo',
					'persona_documento',
					'persona_nombre',
					'tipo_ingreso',
					'fecha_ingreso',
					'hora_ingreso',
				];
				$orders = [];
				foreach ($order as $ord) {
					if (isset($ord['column']) && isset($columns[$ord['column']]) && in_array($ord['dir'], ['asc', 'desc'])) {
						$orders[] = $columns[$ord['column']] . ' ' . $ord['dir'];
					}
				}
				if ($orders) {
					$sql .= ' ORDER BY ' . implode(',', $orders);
				}
			}
			if ($start >= 0) {
				$limit = " LIMIT {$start}";
				if ($length) {
					$limit .= ", {$length}";
				}
				$sql .= $limit;
			}
			$data = $con->consulta(Conexiones::SELECT, $sql, $params);

			return [
				'recordsTotal'    => $recordsTotal,
				'recordsFiltered' => $recordsFiltered,
				'data'            => $data,
			];
		} catch (\Exception $ex) {
			return [
				'recordsTotal'    => 0,
				'recordsFiltered' => 0,
				'data'            => [],
				'error'           => $ex,
			];
		}
	}

	public static function json_sin_cierre_rca($order, $start, $length, $filtros, $extras)
	{
		try {
			$from1 = <<<SQL
				FROM accesos AS acc
		    	JOIN ubicaciones AS u ON acc.ubicacion_id = u.id
		    	JOIN organismos AS o ON u.organismo_id = o.id
		    	JOIN accesos_empleados AS a ON (acc.tipo_id = a.id AND acc.tipo_modelo = :modelo1)
		    	JOIN empleados AS e ON a.empleado_id = e.id
		    	JOIN personas AS p ON e.persona_id = p.id
                        JOIN empleado_dependencia_principal edp ON (edp.id_empleado = e.id AND ISNULL(edp.fecha_hasta) AND edp.borrado = 0 )
		    	LEFT JOIN empleados AS ein ON ein.persona_id = acc.persona_id_ingreso
		    	LEFT JOIN empleado_dependencia_principal edpi ON (edpi.id_empleado = ein.id AND ISNULL(edpi.fecha_hasta) AND edpi.borrado = 0 ) 
		    	LEFT JOIN dependencias AS din ON (edpi.id_dependencia_principal = din.id AND ISNULL(din.fecha_hasta)) 
		    	LEFT JOIN personas AS pin ON ein.persona_id = pin.id
	    		WHERE acc.hora_egreso IS NULL
SQL;


			$sql_contador = "SELECT COUNT(acc.id) AS count {$from1}";
			$con = new Conexiones();
			$params = [
				':modelo1' => static::getClassIndex(new AccesoEmpleado()),
			];
			$recordsTotal = $recordsFiltered = $con->consulta(Conexiones::SELECT, $sql_contador, $params)[0]['count'];
			$filtros1 = [];
			if (!empty($filtros)) {
				foreach (explode(' ', $filtros) as $index => $filtro) {
					$common_filters = <<<SQL
		     u.nombre LIKE :filtro{$index} OR
		     u.calle LIKE :filtro{$index} OR
		     u.numero LIKE :filtro{$index} OR
		     acc.tipo_modelo LIKE :filtro{$index} OR
		     p.documento LIKE :filtro{$index} OR
		     p.nombre LIKE :filtro{$index} OR
		     p.apellido LIKE :filtro{$index} OR
		     acc.tipo_ingreso LIKE :filtro{$index} OR
		     acc.hora_ingreso LIKE :filtro{$index} OR
		     acc.observaciones LIKE :filtro{$index} OR
		     pin.documento LIKE :filtro{$index} OR
		     pin.nombre LIKE :filtro{$index} OR
		     pin.apellido LIKE :filtro{$index}
SQL;
					$filtros1[] = <<<SQL
		 (
		     {$common_filters} 
	     )
SQL;

					$params[":filtro{$index}"] = "%{$filtro}%";
				}
			}
			if ($extras['ubicacion_id']) {
				$ubicacion = "(acc.ubicacion_id = :ubicacion_id)";
				$filtros1[] = $ubicacion;
				$params[':ubicacion_id'] = $extras['ubicacion_id'];
			}
			if ($extras['codep']) {
				$filtros1[] = "(d.id_codep = :codep)";
				$params[':codep'] = $extras['codep'];
			}
			if ($extras['dependencias_autorizadas']) {
				$dependencia = implode(',', $extras['dependencias_autorizadas']);
				$filtros1[] = "edp.id_dependencia_principal in ($dependencia)";
			}
			if ($extras['fecha_ini']) {
				$hora_ingreso = "(DATE(acc.hora_ingreso) >= STR_TO_DATE(:fecha_ini, '%d/%m/%Y'))";
				$filtros1[] = $hora_ingreso;
				$params[':fecha_ini'] = $extras['fecha_ini'];
			}
			if ($extras['tipo_acceso']) {
				$tipo_modelo = "(acc.tipo_modelo = :tipo_acceso)";
				$filtros1[] = $tipo_modelo;
				$params[':tipo_acceso'] = $extras['tipo_acceso'];
			}
			if (!empty($filtros1)) {
				$from1 .= ' AND ' . implode(' AND ', $filtros1);
				$sql_contador = <<<SQL
					SELECT COUNT(acc.id) AS count {$from1}
SQL;
				$recordsFiltered = $con->consulta(Conexiones::SELECT, $sql_contador, $params)[0]['count'];
			}
			$common_fields = <<<SQL
	acc.id                                                                                      AS acc_id,
	acc.hora_ingreso                                         						            AS fecha_ingreso,
	DATE_FORMAT(acc.hora_ingreso, '%H:%i:%s')                                                      AS hora_ingreso,
	acc.observaciones,
	acc.tipo_egreso,
	acc.tipo_id,
	acc.tipo_ingreso,
	'Empleado'				                                                                    AS tipo_modelo,
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
			$sql = <<<SQL
	     SELECT
		     {$common_fields}
		     NULL           AS credencial_codigo,
		     NULL           AS origen,
		     NULL           AS destino,
		     NULL           AS autorizante_persona_documento,
		     NULL           AS autorizante_persona_nombre
	     {$from1}
SQL;
			if ($order) {
				$columns = [
					'acc_id',
					'ubicacion_nombre',
					'tipo_modelo',
					'credencial_codigo',
					'persona_documento',
					'persona_nombre',
					'tipo_ingreso',
					'fecha_ingreso',
					'hora_ingreso',
				];
				$orders = [];
				foreach ($order as $ord) {
					if (isset($ord['column']) && isset($columns[$ord['column']]) && in_array($ord['dir'], ['asc', 'desc'])) {
						$orders[] = $columns[$ord['column']] . ' ' . $ord['dir'];
					}
				}
				if ($orders) {
					$sql .= ' ORDER BY ' . implode(',', $orders);
				}
			}
			if ($start >= 0) {
				$limit = " LIMIT {$start}";
				if ($length) {
					$limit .= ", {$length}";
				}
				$sql .= $limit;
			}
			$data = $con->consulta(Conexiones::SELECT, $sql, $params);

			return [
				'recordsTotal'    => $recordsTotal,
				'recordsFiltered' => $recordsFiltered,
				'data'            => $data,
			];
		} catch (\Exception $ex) {
			return [
				'recordsTotal'    => 0,
				'recordsFiltered' => 0,
				'data'            => [],
				'error'           => $ex,
			];
		}
	}



	/**
	 * @param $clase
	 * @return false|int|string
	 */
	public static function getClassIndex($clase)
	{
		return array_search(Util::obtenerClaseDeObjeto($clase), self::$clases);
	}

	/**
	 * Cuenta los registros del día en una ubicación, que no tengan marca de salida.
	 * @param Ubicacion $ubicacion
	 * @param \DateTime $fecha
	 * @return array
	 */
	public static function hashDeRegistro($ubicacion, $fecha)
	{
		$sql = <<<SQL
SELECT SUM(id) AS id
FROM accesos
WHERE
	tipo_modelo in (1,4)
	AND
	hora_egreso IS NULL
	AND
	ubicacion_id = :ubicacion_id
	AND
	DATE(hora_ingreso) = :fecha
	ORDER BY id ASC;
SQL;
		$params = [
			':ubicacion_id' => $ubicacion->id,
			':fecha'        => $fecha->format('Y-m-d'),

		];

		return (new Conexiones())->consulta(Conexiones::SELECT, $sql, $params)[0]['id'];
	}

	/**
	 * @param Ubicacion $ubicacion
	 * @param \DateTime $fecha
	 * @param           $order
	 * @param int       $start
	 * @param int       $length
	 * @param string    $filtros
	 * @return array
	 */
	public static function ajax($ubicacion, $fecha, $order, $start, $length, $filtros)
	{
		$sql0 = <<<SQL
SELECT
	acc.id                                                        AS acc_id,
	p.id                                                          AS persona_id,
	c.codigo                                                      AS credencial,
	p.documento                                                   AS documento,
	CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, '')) AS nombre,
	acc.hora_ingreso                                              AS fecha_ingreso,
	acc.hora_egreso                                               AS fecha_egreso,
	DATE_FORMAT(acc.hora_ingreso, '%H:%i')                        AS hora_entrada,
	DATE_FORMAT(acc.hora_egreso, '%H:%i')                         AS hora_salida,
	a.origen                                                      AS origen,
	a.destino                                                     AS destino,
	acc.observaciones                                             AS observaciones,
	a.autorizante_id                                              AS autorizante_id,
	acc.tipo_id,
	acc.tipo_modelo,
	:tipo_acceso0                                                 AS tipo_acceso,
	acc.ubicacion_id                                              AS ubicacion_id,
	c.acceso_id													  AS tipo_credencial
FROM accesos AS acc
	JOIN accesos_visitas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = :clase0
	JOIN personas AS p ON p.id = a.persona_id
	LEFT JOIN credenciales AS c ON a.credencial_id = c.id
WHERE c.estatus = 1 AND acc.hora_egreso IS NULL
SQL;

		$sql1 = <<<SQL
SELECT
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
	:tipo_acceso1                                                 AS tipo_acceso,
	acc.ubicacion_id                                              AS ubicacion_id,
	'0'     													  AS tipo_credencial
FROM accesos AS acc
	JOIN accesos_empleados AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = :clase1
	JOIN empleados AS e ON e.id = a.empleado_id
	JOIN personas AS p ON p.id = e.persona_id
WHERE acc.hora_egreso IS NULL 

SQL;
		$sql2 = <<<SQL
SELECT
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
	:tipo_acceso2                                                 AS tipo_acceso,
	acc.ubicacion_id                                              AS ubicacion_id,
	c.acceso_id													  AS tipo_credencial
FROM accesos AS acc
	JOIN accesos_contratistas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = :clase2
	JOIN contratista_personal AS cp ON cp.id = a.empleado_id
	JOIN personas AS p ON p.id = cp.persona_id
	LEFT JOIN credenciales AS c ON a.credencial_id = c.id
WHERE c.estatus = 1 AND acc.hora_egreso IS NULL 

SQL;

		$sql3 = <<<SQL
SELECT
	acc.id                                                        AS acc_id,
	p.id                                                          AS persona_id,
	'Visita enrolada'                                             AS credencial,
	p.documento                                                   AS documento,
	CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, '')) AS nombre,
	acc.hora_ingreso                                              AS fecha_ingreso,
	acc.hora_egreso                                               AS fecha_egreso,
	DATE_FORMAT(acc.hora_ingreso, '%H:%i')                        AS hora_entrada,
	DATE_FORMAT(acc.hora_egreso, '%H:%i')                         AS hora_salida,
	NULL                                                      	  AS origen,
	NULL                                                     	  AS destino,
	acc.observaciones                                             AS observaciones,
	vis.autorizante_id                                            AS autorizante_id,
	acc.tipo_id,
	acc.tipo_modelo,
	:tipo_acceso3  		                                          AS tipo_acceso,
	acc.ubicacion_id                                              AS ubicacion_id,
	'0'															  AS tipo_credencial
FROM accesos AS acc
	JOIN accesos_visitas_enroladas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = :clase3
    JOIN visitas AS vis ON a.visita_id = vis.visita_id
	JOIN personas AS p ON p.id = vis.persona_id
WHERE acc.hora_egreso IS NULL

SQL;

		$params = [];
		if ($filtros) {
			$sql_filtros_v = [];
			$sql_filtros_e = [];
			$sql_filtros_c = [];
			foreach (explode(' ', $filtros) as $index => $value) {
				$sql_filtros_v[] = <<<SQL
      (
	      c.codigo LIKE :filtro{$index} OR
	      p.documento LIKE :filtro{$index} OR
	      p.nombre LIKE :filtro{$index} OR
	      p.apellido LIKE :filtro{$index} OR
	      a.origen LIKE :filtro{$index} OR
	      a.destino LIKE :filtro{$index} OR
	      acc.observaciones LIKE :filtro{$index} 
      )
SQL;
				$sql_filtros_e[] = <<<SQL
      (
	      p.documento LIKE :filtro{$index} OR
	      p.nombre LIKE :filtro{$index} OR
	      p.apellido LIKE :filtro{$index} 
      )
SQL;
				$sql_filtros_c[] = <<<SQL
      (
	      c.codigo LIKE :filtro{$index} OR
	      p.documento LIKE :filtro{$index} OR
	      p.nombre LIKE :filtro{$index} OR
	      p.apellido LIKE :filtro{$index} OR
	      acc.observaciones LIKE :filtro{$index} 
      )
SQL;
				$params[":filtro{$index}"] = "%{$value}%";
			}
			$sql0 .= ' AND ' . implode(' AND ', $sql_filtros_v);
			$sql1 .= ' AND ' . implode(' AND ', $sql_filtros_e);
			$sql2 .= ' AND ' . implode(' AND ', $sql_filtros_c);
		}
		$sql = <<<SQL
FROM (
	     {$sql0}
     UNION
	     {$sql1}
     UNION
	     {$sql2}
	 UNION
	     {$sql3}
     ) AS resultado
WHERE ubicacion_id = :ubicacion AND DATE_FORMAT(fecha_ingreso, '%d/%m/%Y') = :fecha
SQL;
		$sql_contador_total = "SELECT COUNT(*) AS cantidad FROM accesos WHERE ubicacion_id = :ubicacion AND (DATE_FORMAT(hora_ingreso, '%d/%m/%Y') = :fecha or DATE_FORMAT(STR_TO_DATE(RIGHT(observaciones,19),'%Y-%m-%d'), '%d/%m/%Y') = :fecha) AND hora_egreso IS NULL";
		$recordsFiltered = 0;
		$fecha = $fecha->format('d/m/Y');
		$ubicacion_id = $ubicacion->id;
		$params += [
			':ubicacion' => $ubicacion_id,
			':fecha'     => $fecha,
		];
		if ($order) {
			$colums = [
				'credencial',
				'documento',
				'nombre',
				'observaciones',
				'origen',
				'destino',
				'acc_id',
			];
			$orders = [];
			foreach ($order as $ord) {
				if (isset($ord['column']) && isset($colums[$ord['column']]) && in_array($ord['dir'], ['asc', 'desc'])) {
					$orders[] = $colums[$ord['column']] . ' ' . $ord['dir'];
				}
			}
			if ($orders) {
				$sql .= ' ORDER BY ' . implode(',', $orders);
			}
		}
		if ($start >= 0) {
			$limit = " LIMIT {$start}";
			if ($length) {
				$limit .= ", {$length}";
			}
			$sql .= $limit;
		}
		$con = new Conexiones();
		$recordsTotal = $con->consulta(Conexiones::SELECT, $sql_contador_total, [
			':ubicacion' => $ubicacion_id,
			':fecha'     => $fecha,
		])[0]['cantidad'];
		$params += [
			':clase0' => static::getClassIndex(new AccesoVisita()),
			':clase1' => static::getClassIndex(new AccesoEmpleado()),
			':clase2' => static::getClassIndex(new AccesoContratista()),
			':clase3' => static::getClassIndex(new AccesoVisitaEnrolada()),
		];
		$params += [
			':tipo_acceso0' => static::VISITANTE,
			':tipo_acceso1' => static::EMPLEADO,
			':tipo_acceso2' => static::CONTRATISTA,
			':tipo_acceso3' => static::VISITA_ENROLADA,
		];
		if ($filtros) {
			$result = $con->consulta(Conexiones::SELECT, "SELECT COUNT(*) AS filt " . $sql, $params);
			$recordsFiltered = isset($result[0]['filt']) ? $result[0]['filt'] : 0;
		}
		$data = $con->consulta(Conexiones::SELECT, "SELECT * " . $sql, $params);
		$pertenencias = Pertenencia::listarPorUbicacion($ubicacion_id);
		$advertencias = Advertencia::listarPorUbicacion($ubicacion_id);
		$indices = array_column($pertenencias, 'persona_id');
		$indices_adv = array_column($advertencias, 'persona_id');
		foreach ($data as $key => $value) {
			$per = array_search($value['persona_id'], $indices);
			$data[$key]['pertenencias'] = ($per === false) ? null : $pertenencias[$per]['texto'];
			$per_adv = array_search($value['persona_id'], $indices_adv);
			$data[$key]['advertencias'] = ($per_adv === false) ? null : $advertencias[$per_adv]['texto'];
			$per = null;
		}

		return [
			'recordsTotal'    => $recordsTotal,
			'recordsFiltered' => $recordsFiltered ?: $recordsTotal,
			'data'            => $data,
		];
	}

	/**
	 * @param Ubicacion  $ubicacion
	 * @param int        $tipo_acceso
	 * @param Credencial $credencial
	 * @param string     $search
	 * @param \DateTime  $fecha_ini
	 * @param \DateTime  $fecha_fin
	 * @param bool       $incluir_sin_cierre
	 * @return int
	 */
	public static function contarRegistros($ubicacion = null, $tipo_acceso = null, $credencial = null, $search = null, $fecha_ini = null, $fecha_fin = null, $incluir_sin_cierre = false)
	{
		$extras = [
			'ubicacion_id'       => $ubicacion ? $ubicacion->id : null,
			'tipo_acceso'        => $tipo_acceso,
			'nro_credencial'     => $credencial,
			'fecha_ini'          => $fecha_ini ? $fecha_ini->format('d/m/Y') : null,
			'fecha_fin'          => $fecha_fin ? $fecha_fin->format('d/m/Y') : null,
			'incluir_sin_cierre' => $incluir_sin_cierre,
		];
		$count = static::json_visitas_contratistas(null, null, null, $search, $extras, true);

		return $count;
	}

	public static function json_visitas_contratistas($order, $start, $length, $filtros, $extras = [], $count = false)
	{
		$sql_vistas = <<<SQL
SELECT
	acc.id                                                              AS acc_id,
	acc.ubicacion_id,
	u.nombre                                                            AS ubicacion,
	:accesoV                                                            AS acceso,
	:tipoV                                                              AS tipo,
	p.documento,
	p.nombre                                                            AS nombre,
	p.apellido                                                          AS apellido,
	acc.hora_ingreso                							        AS fecha_ingreso,
	acc.hora_egreso                            							AS fecha_egreso,
	DATE_FORMAT(acc.hora_ingreso, '%H:%i')                              AS hora_ingreso,
	DATE_FORMAT(acc.hora_egreso, '%H:%i')                               AS hora_egreso,
	CASE
    WHEN c.acceso_id < 1  THEN acc.tipo_ingreso
    ELSE  6    
END as tipo_ingreso	,
	acc.persona_id_ingreso,
	CASE
    WHEN c.acceso_id < 1 or acc.tipo_egreso is null THEN acc.tipo_egreso
    ELSE  6    
END as tipo_egreso	,	
	acc.persona_id_egreso,
	CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, ''))   AS usuario_ingreso,
	pin.documento                                                       AS usuario_ingreso_documento,
	CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso,
	pout.documento                                                      AS usuario_egreso_documento,
	c.codigo                                                            AS credencial,
	acc.observaciones,
	a.origen AS origen,
	a.destino AS destino,
	CONCAT(COALESCE(pau.nombre, ''), ' ', COALESCE(pau.apellido, '')) AS autorizante
FROM accesos AS acc
	JOIN accesos_visitas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = :claseV
	LEFT JOIN credenciales AS c ON a.credencial_id = c.id
	JOIN ubicaciones AS u ON u.id = acc.ubicacion_id
	JOIN personas AS p ON p.id = a.persona_id
	JOIN personas AS pin ON acc.persona_id_ingreso = pin.id 
	JOIN empleados AS eau ON eau.id = a.autorizante_id
	JOIN personas AS pau ON eau.persona_id = pau.id 
	LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id

SQL;
		$sql_contratistas = <<<SQL
SELECT
	acc.id                                                              AS acc_id,
	acc.ubicacion_id,
	u.nombre                                                            AS ubicacion,
	:accesoC                                                            AS acceso,
	:tipoC                                                              AS tipo,
	p.documento,
	p.nombre                                                            AS nombre,
	p.apellido                                                          AS apellido,
	acc.hora_ingreso							                        AS fecha_ingreso,
	acc.hora_egreso    							                        AS fecha_egreso,
	DATE_FORMAT(acc.hora_ingreso, '%H:%i')                              AS hora_ingreso,
	DATE_FORMAT(acc.hora_egreso, '%H:%i')                               AS hora_egreso,	
	acc.tipo_ingreso,
	acc.persona_id_ingreso,
	acc.tipo_egreso,
	acc.persona_id_egreso,
	CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, ''))   AS usuario_ingreso,
	pin.documento                                                       AS usuario_ingreso_documento,
	CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso,
	pout.documento                                                      AS usuario_egreso_documento,
	c.codigo                                                            AS credencial,
	acc.observaciones,
	con.nombre AS origen,
	u.nombre AS destino,
	CONCAT(COALESCE(pa.nombre, ''), ' ', COALESCE(pa.apellido, '')) AS autorizante
FROM accesos AS acc
	JOIN accesos_contratistas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = :claseC
	JOIN credenciales AS c ON a.credencial_id = c.id
	JOIN ubicaciones AS u ON u.id = acc.ubicacion_id
	JOIN contratista_personal AS cp ON cp.id = a.empleado_id
	JOIN contratistas AS con ON con.id = cp.contratista_id
	JOIN personas AS p ON p.id = cp.persona_id
	JOIN personas AS pin ON acc.persona_id_ingreso = pin.id
	JOIN empleados AS emp ON cp.autorizante_id = emp.id
    JOIN personas AS pa ON emp.persona_id = pa.id
	LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id

SQL;

		$sqlVisitasEnroladas = <<<SQL
 SELECT
	acc.id                                                              AS acc_id,
	acc.ubicacion_id,
	u.nombre                                                            AS ubicacion,
	:accesoEnroladaV                                                    AS acceso,
	:tipoEnroladaV                                                      AS tipo,
	p.documento,
	p.nombre                                                            AS nombre,
	p.apellido                                                          AS apellido,								
	acc.hora_ingreso                							        AS fecha_ingreso,
	acc.hora_egreso                            							AS fecha_egreso,
	DATE_FORMAT(acc.hora_ingreso, '%H:%i')                              AS hora_ingreso,
	DATE_FORMAT(acc.hora_egreso, '%H:%i')                               AS hora_egreso,
	acc.tipo_ingreso,
	acc.persona_id_ingreso,
	acc.tipo_egreso,
	acc.persona_id_egreso,
	CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, ''))   AS usuario_ingreso,
	pin.documento                                                       AS usuario_ingreso_documento,
	CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso,
	pout.documento                                                      AS usuario_egreso_documento,
	NULL AS credencial,
	acc.observaciones,
	NULL AS origen,
	NULL AS destino,
	CONCAT(COALESCE(pau.nombre, ''), ' ', COALESCE(pau.apellido, '')) AS autorizante
FROM accesos AS acc
	JOIN accesos_visitas_enroladas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = :claseEnr
	JOIN ubicaciones AS u ON u.id = acc.ubicacion_id
    JOIN visitas as vis on vis.visita_id = a.visita_id
	JOIN personas AS p ON p.id = vis.persona_id
	JOIN personas AS pin ON acc.persona_id_ingreso = pin.id 
	JOIN empleados AS eau ON eau.id = vis.autorizante_id
	JOIN personas AS pau ON eau.persona_id = pau.id 
	LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id

SQL;

		$params_vistas = [
			':claseV' => static::getClassIndex(new AccesoVisita()),
		];
		$params_contratistas = [
			':claseC' => static::getClassIndex(new AccesoContratista()),
		];
		$params_visitas_enroladas = [
			':claseEnr' => static::getClassIndex(new AccesoVisitaEnrolada()),
		];

		$con = new Conexiones();
		$recordsTotal = $recordsFiltered = $con->consulta(
			Conexiones::SELECT,
			"SELECT COUNT(DISTINCT id) AS total FROM accesos WHERE tipo_modelo IN (:claseV,:claseC,:claseEnr)",
			$params_vistas + $params_contratistas + $params_visitas_enroladas
		)[0]['total'];
		$params_vistas += [
			':tipoV'   => static::VISITANTE,
			':accesoV' => static::tipoAccesoToString(static::VISITANTE),
		];
		$params_contratistas += [
			':tipoC'   => static::CONTRATISTA,
			':accesoC' => static::tipoAccesoToString(static::CONTRATISTA),
		];
		$params_visitas_enroladas += [
			':tipoEnroladaV'   => static::VISITA_ENROLADA,
			':accesoEnroladaV' => static::tipoAccesoToString(static::VISITA_ENROLADA),
		];

		if ($extras['nro_credencial'] != "") {
			$params = $params_vistas + $params_contratistas;
		} else {
			$params = $params_vistas + $params_contratistas + $params_visitas_enroladas;
		}
		$where_visitas = '';
		$where_contratistas = '';

		$where_visitas_enroladas = '';

		if ($filtros) {
			$filtros_visitas = [];
			foreach (explode(' ', $filtros) as $index => $value) {
				$sql_filtros = <<<SQL
          (
		     u.nombre LIKE :filtro{$index} OR
		     :accesoC LIKE :filtro{$index} OR
		     p.documento LIKE :filtro{$index} OR
		     p.nombre LIKE :filtro{$index} OR
		     p.apellido LIKE :filtro{$index} OR
		     pin.documento LIKE :filtro{$index} OR
		     pin.nombre LIKE :filtro{$index} OR
		     pin.apellido LIKE :filtro{$index} OR
		     pout.documento LIKE :filtro{$index} OR
		     pout.nombre LIKE :filtro{$index} OR
		     pout.apellido LIKE :filtro{$index} OR
		     acc.observaciones LIKE :filtro{$index} OR
		     DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') LIKE :filtro{$index} OR
		     DATE_FORMAT(acc.hora_egreso, '%d/%m/%Y') LIKE :filtro{$index} OR
		     DATE_FORMAT(acc.hora_ingreso, '%H:%i') LIKE :filtro{$index} OR
		     DATE_FORMAT(acc.hora_egreso, '%H:%i') LIKE :filtro{$index} OR
		     CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, '')) LIKE :filtro{$index} OR
		     CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) LIKE :filtro{$index}
SQL;

				$origen_destino = <<<SQL
		     OR CONCAT(COALESCE(pau.nombre, ''), ' ', COALESCE(pau.apellido, '')) LIKE :filtro{$index}
		     OR a.origen LIKE :filtro{$index} 
		     OR a.destino LIKE :filtro{$index} 		    
SQL;
				$filtros_visitas[] = $sql_filtros . $origen_destino . ")";
				$filtros_contratistas[] = $sql_filtros . ")";
				$filtros_visitas_enroladas[] = $sql_filtros . ")";
				$params[":filtro{$index}"] = "%{$value}%";
			}
			$where_visitas = " WHERE " . implode(' AND ', $filtros_visitas);
			$where_contratistas = " WHERE " . implode(' AND ', $filtros_contratistas);
			$where_visitas_enroladas = " WHERE " . implode(' AND ', $filtros_visitas_enroladas);
			$sql_vistas .= $where_visitas;
			$sql_contratistas .= $where_contratistas;
			$sqlVisitasEnroladas .= $where_visitas_enroladas;
		}
		$extra_where = [];
		if (!empty($extras['ubicacion_id'])) {
			$extra_where[] = "acc.ubicacion_id = :ubicacion_id";
			$params[':ubicacion_id'] = $extras['ubicacion_id'];
		}
		if (!empty($extras['nro_credencial'])) {
			$extra_where[] = "c.codigo = :nro_credencial";
			$params[':nro_credencial'] = $extras['nro_credencial'];
		}
		if (!empty($extras['fecha_ini'])) {
			$extra_where[] = "(DATE(acc.hora_ingreso) >= STR_TO_DATE(:fecha_ini, '%d/%m/%Y'))";
			$params[':fecha_ini'] = $extras['fecha_ini'];
		}
		if (!empty($extras['fecha_fin'])) {
			$sin_cierre = '';
			if ($extras['incluir_sin_cierre']) {
				$sin_cierre = ' OR acc.hora_egreso IS NULL';
			}
			$sin_cierre .= ')';
			$extra_where[] = "(DATE(acc.hora_egreso) <= STR_TO_DATE(:fecha_fin, '%d/%m/%Y')" . $sin_cierre;
			$params[':fecha_fin'] = $extras['fecha_fin'];
		}
		$extra_having = "";
		if (!empty($extras['tipo_acceso'])) {
			$extra_having = " HAVING (acceso = :tipo_acceso)";
			$params[':tipo_acceso'] = $extras['tipo_acceso'];
		}
		if (!empty($extra_where)) {
			if (!empty($where_visitas)) {
				$where_visitas = " AND ";
				$where_contratistas = " AND ";
				$where_visitas_enroladas = " AND ";
			} else {
				$where_visitas .= ' WHERE ';
				$where_contratistas .= ' WHERE ';
				$where_visitas_enroladas = " WHERE ";
			}
			$where_visitas .= implode(' AND ', $extra_where);
			$where_contratistas .= implode(' AND ', $extra_where);
			$where_visitas_enroladas  .= implode(' AND ', $extra_where);
			$sql_vistas .= $where_visitas;
			$sql_contratistas .= $where_contratistas;
			$sqlVisitasEnroladas .= $where_visitas_enroladas;
		}
		if ($extra_having) {
			$sql_vistas .= $extra_having;
			$sql_contratistas .= $extra_having;
			$sqlVisitasEnroladas .= $extra_having;
		}
		if (!empty($filtros) || !empty($extra_where)) {
			if ($extras['nro_credencial'] != "") {
				$recordsFiltered = $con->consulta(Conexiones::SELECT, "SELECT COUNT(acc_id) AS filt FROM ({$sql_vistas} UNION {$sql_contratistas}) AS resultado", $params)[0]['filt'];
			} else {
				$recordsFiltered = $con->consulta(Conexiones::SELECT, "SELECT COUNT(acc_id) AS filt FROM ({$sql_vistas} UNION {$sql_contratistas} UNION {$sqlVisitasEnroladas}) AS resultado", $params)[0]['filt'];
			}
		}
		if ($count) {
			return $recordsFiltered;
		}

		if (empty($extras['nro_credencial'])) {
			$sql = "
		SELECT *
		FROM (
				{$sql_vistas}
			     UNION
				{$sql_contratistas}
				UNION
				{$sqlVisitasEnroladas}
		     ) AS resultado ";
		} else {
			$sql = "SELECT *
		FROM (
				{$sql_vistas}
			     UNION
				{$sql_contratistas}
			) AS resultado ";
		}

		if ($order) {
			$colums = [
				'acc_id',
				'documento',
				'nombre',
				'apellido',
				'ubicacion',
				'acceso',
				'ubicacion_id',
				'fecha_ingreso',
				'hora_ingreso',
				'tipo_ingreso',
				'usuario_ingreso',
				'usuario_ingreso_documento',
				'fecha_egreso',
				'hora_egreso',
				'tipo_egreso',
				'credencial',
				'usuario_egreso',
				'usuario_egreso_documento',
				'tipo',
			];
			$orders = [];
			foreach ($order as $ord) {
				if (isset($ord['column']) && isset($colums[$ord['column']]) && in_array($ord['dir'], ['asc', 'desc'])) {
					$orders[] = $colums[$ord['column']] . ' ' . $ord['dir'];
				}
			}
			if ($orders) {
				$sql .= ' ORDER BY ' . implode(',', $orders);
			}
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

	/**
	 * Traduce las constantes de Tipo de Acceso en texto legible
	 * @param int $tipo_id
	 * @return string
	 */
	public static function tipoAccesoToString($tipo_id = 0)
	{
		switch ($tipo_id) {
			case static::EMPLEADO:
				return 'Empleado';
				break;
			case static::CONTRATISTA:
				return 'Contratista';
				break;
			case static::VISITA_ENROLADA:
				return 'Visita enrolada';
				break;
			default:
				return 'Visitante';
		}
	}

	/**
	 * @param int        $start
	 * @param int        $offset
	 * @param Ubicacion  $ubicacion
	 * @param int        $tipo_acceso
	 * @param Credencial $credencial
	 * @param string     $search
	 * @param \DateTime  $fecha_ini
	 * @param \DateTime  $fecha_fin
	 * @param bool       $sin_cierre
	 * @return array
	 */
	public static function dataParaExcel($start = null, $offset = null, $ubicacion = null, $tipo_acceso = null, $credencial = null, $search = null, $fecha_ini = null, $fecha_fin = null, $sin_cierre = false)
	{
		$extras = [
			'ubicacion_id'       => $ubicacion ? $ubicacion->id : null,
			'tipo_acceso'        => $tipo_acceso,
			'nro_credencial'     => $credencial,
			'fecha_ini'          => $fecha_ini ? $fecha_ini->format('d/m/Y') : null,
			'fecha_fin'          => $fecha_fin ? $fecha_fin->format('d/m/Y') : null,
			'incluir_sin_cierre' => $sin_cierre,
		];

		return static::json_visitas_contratistas(null, $start, $offset, $search, $extras);
	}

	/**
	 * Obtiene el acceso activo en una ubicación por documento.
	 * @param string    $documento
	 * @param Ubicacion $ubicacion
	 * @return Acceso
	 */
	public static function obtenerAccesoEmpleadoPorDocumento($documento, $ubicacion)
	{
		$sql = <<<SQL
SELECT acc.id
FROM accesos AS acc
	JOIN accesos_empleados AS ae ON acc.tipo_id = ae.id AND acc.tipo_modelo = :tipo_empleado
	JOIN empleados AS e ON ae.empleado_id = e.id
	JOIN personas AS pe ON e.persona_id = pe.id
WHERE
	pe.documento = :documento
	AND
	acc.ubicacion_id = :ubicacion_id
	AND
	acc.hora_egreso IS NULL;
SQL;
		$params = [
			':tipo_empleado' => static::EMPLEADO,
			':documento'     => $documento,
			':ubicacion_id'  => $ubicacion->id,
		];
		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, $params);
		if (!empty($res) && isset($res[0]['id'])) {
			return static::obtener($res[0]['id']);
		}

		return null;
	}

	/**
	 * @param $id
	 * @return Acceso
	 */
	public static function obtener($id)
	{
		/** @var Acceso $obj */
		$obj = new self();
		if (is_numeric($id)) {
			if ($id > 0) {
				$sql = "SELECT
						id,
						ubicacion_id,
						observaciones,
						hora_ingreso,
						hora_egreso,
						tipo_modelo,
						tipo_id,
						persona_id_ingreso,
						persona_id_egreso,
						tipo_ingreso,
						tipo_egreso
					FROM accesos
					WHERE id = :id";
				$conex = new Conexiones();
				$res = $conex->consulta(Conexiones::SELECT, $sql, [':id' => $id]);
				if (is_array($res) && isset($res[0])) {
					$res = $res[0];
					// Atributos propios
					$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
					$obj->ingreso = isset($res['hora_ingreso']) ? new \DateTime($res['hora_ingreso']) : null;
					$obj->hora_ingreso = $obj->ingreso ? $obj->ingreso->format('H:i') : null;
					$obj->egreso = isset($res['hora_egreso']) ? new \DateTime($res['hora_egreso']) : null;
					$obj->hora_egreso = $obj->egreso ? $obj->egreso->format('H:i') : null;
					$obj->observaciones = isset($res['observaciones']) ? $res['observaciones'] : null;
					$obj->tipo_id = isset($res['tipo_id']) ? (int)$res['tipo_id'] : 0;
					$obj->tipo_modelo = isset($res['tipo_modelo']) ? self::$clases[$res['tipo_modelo']] : null;
					$obj->tipo_egreso = (int)$res['tipo_egreso'];
					$obj->tipo_egreso_str = static::tipoRegistroToString($obj->tipo_egreso);
					$obj->tipo_ingreso = (int)$res['tipo_ingreso'];
					$obj->tipo_ingreso_str = static::tipoRegistroToString($obj->tipo_ingreso);
					$obj->ubicacion = Ubicacion::obtener(isset($res['ubicacion_id']) ? (int)$res['ubicacion_id'] : 0);
					$obj->persona_egreso = Persona::obtener($res['persona_id_egreso']);
					$obj->persona_ingreso = Persona::obtener($res['persona_id_ingreso']);
					// Atributos heredados
					$obj->modelo = call_user_func([str_replace("/", "\\", $obj->tipo_modelo), 'obtener'], $obj->tipo_id);
					$obj->tipo_acceso = isset($obj->modelo->tipo_acceso) ? $obj->modelo->tipo_acceso : static::VISITANTE;
					if ($obj->tipo_acceso === static::VISITANTE) {
						$obj->autorizante = isset($obj->modelo->autorizante) ? $obj->modelo->autorizante : null;
						$obj->destino = isset($obj->modelo->destino) ? $obj->modelo->destino : null;
						$obj->origen = isset($obj->modelo->origen) ? $obj->modelo->origen : null;
						$obj->persona = isset($obj->modelo->persona) ? $obj->modelo->persona : null;
					}
					if ($obj->tipo_acceso === static::CONTRATISTA) {
						$obj->autorizante = isset($obj->modelo->empleado->autorizante) ? $obj->modelo->empleado->autorizante : null;
						$obj->persona = isset($obj->modelo->empleado->persona) ? $obj->modelo->empleado->persona : null;
						$obj->contratista_empleado = isset($obj->modelo->empleado) ? $obj->modelo->empleado : null;
					}
					if ($obj->tipo_acceso === static::EMPLEADO) {
						$obj->persona = isset($obj->modelo->empleado->persona_id) ? Persona::obtener($obj->modelo->empleado->persona_id) : null;
						$obj->empleado = isset($obj->modelo->empleado) ? $obj->modelo->empleado : null;
					}
					if ($obj->tipo_acceso === static::VISITANTE || $obj->tipo_acceso === static::CONTRATISTA) {
						$obj->credencial = isset($obj->modelo->credencial) ? $obj->modelo->credencial : null;
					}

					if ($obj->tipo_acceso === static::VISITA_ENROLADA) {
						$obj->autorizante = isset($obj->modelo->visita->autorizante) ? $obj->modelo->visita->autorizante : null;
						$obj->persona = isset($obj->modelo->visita->persona) ? $obj->modelo->visita->persona : null;
					}

					if ($obj->tipo_acceso === static::VISITA_TARJETA_ENROLADA) {
						$obj->credencial = isset($obj->modelo->credencial) ? $obj->modelo->credencial : null;
					}
				}

				return $obj;
			} else {
				$obj->id = 0;
				$obj->ubicacion = Ubicacion::obtener(0);
				$obj->observaciones = null;
				$obj->hora_ingreso = null;
				$obj->hora_egreso = null;
				$obj->tipo_modelo = null;
				$obj->tipo_id = 0;
				$obj->persona_ingreso = Persona::obtener(0);
				$obj->persona_egreso = Persona::obtener(0);
				$obj->tipo_ingreso = 0;
				$obj->tipo_egreso = 0;
				$obj->tipo_ingreso_str = null;
				$obj->tipo_egreso_str = null;
			}

			return $obj;
		}

		return null;
	}

	/**
	 * Traduce las constantes de Tipo de Registro en texto legible
	 * @param $tipo_acceso
	 * @return string
	 */
	public static function tipoRegistroToString($tipo_acceso)
	{
		switch ($tipo_acceso) {
			case static::TIPO_REGISTRO_ONLINE:
				return 'Online';
				break;
			case static::TIPO_REGISTRO_OFFLINE:
				return 'Offline';
				break;
			case static::TIPO_REGISTRO_RELOJ:
				return 'Reloj';
				break;
			case static::TIPO_COMISION_HORARIA:
				return 'Comisión Horaria';
				break;
			case static::TIPO_REGISTRO_TARJETA_RELOJ:
				return 'Tarjeta Magnetica';
				break;
			default:
				return 'Sin asignar';
		}
	}

	public function alta($es_tarjeta_magnetica = null) {
		$this->es_tarjeta_magnetica = $es_tarjeta_magnetica;
		if ($this->validar()) {
			$this->ingreso = new \DateTime('now');
			if ($this->tipo_acceso >= 0) {
				switch ($this->tipo_acceso) {
					case static::VISITANTE:
						return $this->altaVisita();
						break;
					case static::EMPLEADO:
						return $this->altaEmpleado();
						break;
					case static::CONTRATISTA:
						return $this->altaContratista();
						break;
                    case static::VISITA_ENROLADA:
                        return $this->altaVisitaEnrolada();
                        break;
                    case static::VISITA_TARJETA_ENROLADA:
                        return $this->altaVisita_TM();
                        break;
					default:
						return false;
				}
			}
		}

		return false;
	}

	public function baja_acceso()
	{
		$sql = "DELETE FROM accesos WHERE id = :id";
		$params = [':id' => $this->id];
		$res = (new Conexiones())->consulta(Conexiones::DELETE, $sql, $params);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'Acceso';
			Logger::event('baja', $datos);

			return true;
		}

		return false;
	}

	public function alta_log_accesos()
	{
		$return = false;
		$mbd = new Conexiones;
		$sql = "INSERT INTO log_accesos (id, ubicacion_id, tipo_id, tipo_modelo, hora_ingreso, persona_id_ingreso, tipo_ingreso, hora_egreso, persona_id_egreso, tipo_egreso, observaciones, motivo) 
				VALUES (:id, :ubicacion_id, :tipo_id, :tipo_modelo, :hora_ingreso, :persona_id_ingreso, :tipo_ingreso, :hora_egreso, :persona_id_egreso, :tipo_egreso, :observaciones,:motivo)";
		$params = [
			':id' => $this->id,
			':ubicacion_id'	=> $this->ubicacion->id,
			':tipo_id' 		=> $this->tipo_id,
			':tipo_modelo' 	=> static::EMPLEADO,
			':hora_ingreso'	=> $this->ingreso->format('Y-m-d H:i:s'),
			':persona_id_ingreso' => $this->persona_ingreso->id,
			':tipo_ingreso' => $this->tipo_ingreso,
			':hora_egreso' 	=> $this->egreso->format('Y-m-d H:i:s'),
			':persona_id_egreso' => $this->persona_egreso->id,
			':tipo_egreso' 	=> $this->tipo_egreso,
			':observaciones' => $this->observaciones,
			':motivo' 		=> 'Baja Comisión Horaria'
		];

		$resultado = $mbd->consulta(Conexiones::INSERT, $sql, $params);
		$this->id = $resultado;
		$datos = (array)$this;
		$datos['modelo'] = 'Acceso';
		if (is_numeric($resultado) && $resultado > 0) {
			$return = true;
		} else {
			$datos['error_db'] = $mbd->errorInfo;
		}

		Logger::event('alta', $datos);

		return $return;
	}

	public function validar()
	{
		$inputs = [
			'documento'       => $this->persona->documento,
			'nombre'          => $this->persona->nombre,
			'apellido'        => $this->persona->apellido,
			'observaciones'   => $this->observaciones,
			'ubicacion_id'    => $this->ubicacion->id,
			//DESCOMENTAR LÍNEA DE ABAJO Y COMENTAR LA ASIGNACIÓN DEL VALOR 1 EN usuario_ingreso
			// 'usuario_ingreso' => 1,			
			'usuario_ingreso' => $this->persona_ingreso->id,
			'tipo_ingreso'    => $this->tipo_ingreso,
			'ingreso'         => $this->ingreso,
			'egreso'          => $this->egreso,
		];
		$reglas = [
			'documento'       => ['required', 'documento'],
			'nombre'          => ['required', 'texto', 'min_length(2)'],
			'apellido'        => ['required', 'texto', 'min_length(2)'],
			'observaciones'   => ['texto'],
			'ubicacion_id'    => ['required', 'min_length(1)', 'numeric'],
			'usuario_ingreso' => ['empleado_x_usuario'],
			'tipo_ingreso'    => ['required'],
			'ingreso'         => ['fecha'],
			'egreso'          => ['fecha', 'despuesDe(:ingreso)'],
		];
		$nombres = [
			'documento'      => "Documento",
			'nombre'         => "Nombre del Visitante",
			'apellido'       => "Apellido del Visitante",
			'observaciones'  => "Observaciones",
			'ubicacion_id'   => "Ubicación",
			'usuario_ingreso' => "Usuario Logueado",
			'tipo_egreso'    => "Tipo de Ingreso",
			'ingreso'        => "Fecha y Hora de Ingreso",
			'egreso'         => "Fecha y Hora de Egreso",
		];
		if ($this->tipo_acceso == static::VISITANTE || $this->tipo_acceso == static::CONTRATISTA) {
			if (empty($this->credencial->errores)) {
				//Sólo valida si es credencial, no tarjeta. CHEQUEAR!
				//if($this->credencial->acceso_id == 0){
				$inputs += [
					'credencial_codigo' => $this->credencial->codigo,
				];
				$reglas += [
					'credencial_codigo' => ['existe(credenciales,codigo)'],
				];
				$nombres += [
					'credencial_codigo' => "Código de Credencial",
				];
				//}
			}
		}
		$customErrors = [];
		if ($this->tipo_acceso == static::VISITANTE) {
			$ubicacion = $this->ubicacion;
			$inputs += [
				'origen'                => $this->origen,
				'destino'               => $this->destino,
				'autorizante_documento' => $this->autorizante->documento,
				'autorizante'           => $this->autorizante,
			];
			$reglas += [
				'origen'                => ['required', 'texto', 'min_length(3)', 'max_length(64)'],
				'destino'               => ['required', 'texto', 'min_length(3)', 'max_length(64)'],
				'autorizante_documento' => ['documento'],
			];
			$nombres += [
				'origen'                => "Origen",
				'destino'               => "Destino",
				'autorizante_documento' => "Documento del Autorizante",
				'autorizante'           => "Autorizante",
			];
			$customErrors = [
				'es_autorizante' => "<strong>" .
					(!empty(trim($this->autorizante->nombre)) ?
						"{$this->autorizante->nombre} {$this->autorizante->apellido}" :
						"El Documento del Autorizante"
					) .
					"</strong> no " .
					"tiene autoridad para permitir el acceso en <strong>{$this->ubicacion->nombre}</strong><br>" .
					(Ubicacion::obtener($this->autorizante->ubicacion)->nombre
						? "Este empleado pertenece a <u>".Ubicacion::obtener($this->autorizante->ubicacion)->nombre."</u>" : ''),
			];
		}

		if ($this->tipo_acceso == static::VISITA_TARJETA_ENROLADA) {
			if (empty($this->credencial->errores)) {
				$inputs += [
					'credencial_codigo' => $this->credencial,
				];
				$reglas += [
					'credencial_codigo' => ['existe(credenciales,codigo)'],
				];
				$nombres += [
					'credencial_codigo' => "Código de Credencial",
				];
			}
		}

		$validator = Validador::validate($inputs, $reglas, $nombres);
		if ($validator->isSuccess()) {
			return true;
		}
		$validator->customErrors($customErrors);
		$this->errores = $validator->getErrors();

		return false;
	}

	private function altaVisita()
	{
		$acceso = AccesoVisita::obtener(0);
		
		$per = Persona::obtenerPorDocumento($this->persona->documento);
		if (empty($per) || empty($per->id)) {
			$per = new Persona();
			$per->documento = $this->persona->documento;
			$per->nombre = $this->persona->nombre;
			$per->apellido = $this->persona->apellido;
			$per->genero = '0';
			$per->id = $per->alta();
			$acceso->persona = $per;
		}else{
			$acceso->persona = $per;
		}

		$acceso->autorizante = $this->autorizante;
		$acceso->credencial = $this->credencial;
		$acceso->origen = $this->origen;
		$acceso->destino = $this->destino;
		if ($acceso->alta()) {
			$this->tipo_id = $acceso->id;
			$this->tipo_modelo = Util::obtenerClaseDeObjeto($acceso);
			$acceso = $this->registrarAcceso();

			return $acceso;
		}
		$this->errores = $acceso->errores;

		return false;
	}

	private function altaVisita_TM2()
	{
		if (empty($this->persona) || empty($this->persona->id)) {
			if (!$this->persona->alta()) {
				$this->errores = $this->persona->errores;

				return false;
			}
		}
		$acceso = AccesoVisita::obtener(0);
		$acceso->persona = $this->persona;
		$acceso->autorizante = $this->autorizante;
		$acceso->credencial = $this->credencial;
		$acceso->origen = $this->origen;
		$acceso->destino = $this->destino;
		if ($acceso->alta()) {
			$this->tipo_id = $acceso->id;
			$this->tipo_modelo = Util::obtenerClaseDeObjeto($acceso);
			$acceso = $this->registrarAcceso();

			return $acceso;
		}
		$this->errores = $acceso->errores;

		return false;
	}

	private function altaVisita_TM()
	{
		$acceso = AccesoVisita::obtener(0);
		$acceso = $this->registrarAcceso();

		return false;
	}

	/**
	 * @param bool $set_hora se usa para registro de sincronizacion con el reloj
	 * @return bool
	 */
	public function registrarAcceso($set_hora = false)
	{

		$currentDateTime = date('Y-m-d H:i:s');

		$hora_ingreso = $currentDateTime;
		$sql = "INSERT INTO accesos (tipo_id, tipo_modelo, hora_ingreso, persona_id_ingreso, tipo_ingreso, observaciones, ubicacion_id, hora_egreso)
	VALUE (:tipo_id, :tipo_modelo, :hora_ingreso, :persona_id_ingreso, :tipo_ingreso, :observaciones, :ubicacion_id, NULL)";
		$conex = new Conexiones();
		$params = [
			':tipo_id'            => $this->tipo_id,
			':tipo_modelo'        => array_search($this->tipo_modelo, self::$clases),
			':hora_ingreso'       => $hora_ingreso,
			':observaciones'      => $this->observaciones,
			':ubicacion_id'       => $this->ubicacion->id,
			':persona_id_ingreso' => $this->persona_ingreso->id,
			':tipo_ingreso'       => $this->tipo_ingreso,
		];

		if ($set_hora) { //caso hora egreso está vacía
			$sql = 'INSERT INTO accesos (tipo_id, tipo_modelo, persona_id_ingreso, tipo_ingreso, observaciones, ubicacion_id, hora_egreso, hora_ingreso)
	VALUE (:tipo_id, :tipo_modelo, :persona_id_ingreso, :tipo_ingreso, :observaciones, :ubicacion_id,NULL, :hora_ingreso)';
			$params[':hora_ingreso'] = $this->ingreso->format('Y-m-d H:i:s');

			if (!empty($this->egreso)) {
				$sql = 'INSERT INTO accesos (tipo_id, tipo_modelo, persona_id_ingreso, tipo_ingreso, observaciones, ubicacion_id, hora_egreso, hora_ingreso,tipo_egreso, persona_id_egreso)
					VALUE (:tipo_id, :tipo_modelo, :persona_id_ingreso, :tipo_ingreso, :observaciones, :ubicacion_id, :hora_egreso, :hora_ingreso, :tipo_egreso, :persona_id_egreso)';

				$params[':hora_ingreso'] = $this->ingreso->format('Y-m-d H:i:s');
				$params[':hora_egreso'] = $this->egreso->format('Y-m-d H:i:s');
				$params[':tipo_egreso'] = $this->tipo_egreso;
				$params[':persona_id_egreso'] =  $this->persona_egreso->id;
			}
		}

		$res = $conex->consulta(Conexiones::INSERT, $sql, $params);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			$this->id;
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'Acceso';
			Logger::event('alta', $datos);

			return true;
		}

		return false;
	}

	private function altaEmpleado()
	{
		/** @var AccesoEmpleado $acceso */
		$acceso = AccesoEmpleado::obtener(0);
		$acceso->empleado_id = $this->empleado->id;
		if ($acceso->alta()) {
			$this->tipo_id = $acceso->id;
			$this->tipo_modelo = Util::obtenerClaseDeObjeto($acceso);
			return $this->registrarAcceso();
		}
		$this->errores = $acceso->errores;

		return false;
	}

	public function altaVisitaEnrolada()
	{
		/** @var AccesoVisitaEnrolada $acceso */
		$acceso = AccesoVisitaEnrolada::obtener(0);
		$acceso->visita_id = $this->visita_enrolada->id;

		if ($acceso->alta()) {
			$this->tipo_id = $acceso->id;
			$this->tipo_modelo = Util::obtenerClaseDeObjeto($acceso);

			return  $this->registrarAcceso();
		}
		$this->errores = $acceso->errores;

		return false;
	}

	/**
	 * Da ingreso a un Contratista.
	 * @return bool
	 */
	private function altaContratista()
	{
		/** @var AccesoContratista $acceso */
		$acceso = AccesoContratista::obtener(0);		
		$acceso->credencial = $this->credencial;
		$acceso->empleado = $this->contratista_empleado;

		$ubicaciones_permisos = $this->contratista_empleado->obtenerUbicacion($acceso->credencial->ubicacion->id);

		if($this->validar_acceso_contratista($ubicaciones_permisos)){
			if ($this->validar_acceso_abierto($this->contratista_empleado , $acceso->credencial->ubicacion->id)){
				if ($acceso->alta()) {
					$this->tipo_id = $acceso->id;
					$this->tipo_modelo = Util::obtenerClaseDeObjeto($acceso);
					$acceso = $this->registrarAcceso();
		
					return $acceso;
				}
			}else{	
				return false;	
			}
			$this->errores = $acceso->errores;
	
			return false;

		}
	
		return false;
		
	}

	public function validar_acceso_abierto($contratista, $ubicacion){

		$sql = "SELECT a.*
		FROM   accesos AS a
			   INNER JOIN accesos_contratistas AS acc
					   ON a.tipo_id = acc.id
			   INNER JOIN contratista_personal AS cp
					   ON acc.empleado_id = cp.id
		WHERE  cp.id = :contratista_id
			   AND acc.empleado_id = :contratista_id
			   AND a.ubicacion_id = :ubicacion
			   AND a.hora_egreso IS NULL
		ORDER  BY a.id DESC
		LIMIT  1 ";

		$params = [':contratista_id' => $contratista->id,
				   ':ubicacion' => $ubicacion];

		$res = (new Conexiones)->consulta(Conexiones::SELECT, $sql, $params);

		if(!empty($res)){
			$ahora = new DateTime();
			if($ahora->format("d-m-Y") == date_format(date_create($res[0]["hora_ingreso"]), "d-m-Y")){
				$this->errores =  [ 0 =>  "Existe un acceso abierto del día de hoy" ];
				return false;
			}else{
				return true;
			}			
		}else{
			return true;
		}
	}
	/**
	 * @return array
	 */
	public function validar_acceso_contratista($ubicaciones_permisos)
	{
		
		$rules = [
			'acceso_inicio'    => ['antesDe(:hoy)'],
			'acceso_fin'       => ['despuesDe(:hoy)'],
		];
		$input_names = [
			'acceso_inicio'    => "Acceso Inicio",
			'acceso_fin'       => "Acceso Fin",
			':hoy'          => 'Hoy',
		];
		$inputs = [
			'acceso_inicio'    => $ubicaciones_permisos->acceso_inicio,//$ubicaciones_permisos->acceso_inicio,
			'acceso_fin'       => $ubicaciones_permisos->acceso_fin,//$ubicaciones_permisos->acceso_fin,
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

	/**
	 * No Implementado
	 */
	public function baja()
	{
		// TODO: Implement baja() method.
	}

	public function update_acceso_TM($id, $ahora)
	{
		$sql = "UPDATE accesos AS a
					 SET a.hora_ingreso  = :hora_ingreso , a.tipo_ingreso  = 6
					 WHERE id = :id";
		$params = [
			':id'           => $id,
			":hora_ingreso" => $ahora->format("Y-m-d H:i:s")
		];
		$res = (new Conexiones())->consulta(Conexiones::UPDATE, $sql, $params);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'Acceso';
			Logger::event('update', $datos);

			return true;
		}

		return false;
	}

	/**
	 * No Implementado
	 */
	public function modificacion()
	{

		$sql = "UPDATE accesos AS a
					SET
						a.hora_ingreso       = :hora_ingreso,
						a.tipo_ingreso       = :tipo_ingreso,
						a.persona_id_ingreso = :persona_id_ingreso,
						a.hora_egreso        = :hora_egreso,
						a.tipo_egreso        = :tipo_egreso,
						a.persona_id_egreso  = :persona_id_egreso,
						a.observaciones      = :observaciones
					WHERE a.id = :id";
		$params = [
			":hora_ingreso"       => $this->ingreso->format("Y-m-d H:i:s"),
			":tipo_ingreso"       => $this->tipo_ingreso,
			":persona_id_ingreso" => $this->persona_ingreso->id,
			":hora_egreso"        => $this->egreso->format("Y-m-d H:i:s"),
			":tipo_egreso"        => $this->tipo_egreso,
			":persona_id_egreso"  => $this->persona_egreso->id,
			":observaciones"      => $this->observaciones,
			":id"                 => $this->id,
		];
		$con = new Conexiones();
		$res = $con->consulta(Conexiones::UPDATE, $sql, $params);
		if (is_numeric($res)) {
			$clase = str_replace('/', '\\', $this->tipo_modelo);
			$id = $this->tipo_id;
			$obj = call_user_func([$clase, 'obtener'], $id);
			if (isset($obj->credencial_id) && !empty($obj->credencial_id)) {
				$cred = Credencial::obtener($obj->credencial_id);
				$cred->liberar();
			}
			Logger::event('modificacion', $this);
			return true;
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function enVisita()
	{
		$validator = Validador::validate([
			'documento'    => $this->persona->documento,
			'id_ubicacion' => $this->ubicacion->id,
		], [
			'documento'    => ['required', 'documento'],
			'id_ubicacion' => ['required', 'existe(ubicaciones,id)'],
		], [
			'documento'    => 'Documento',
			'id_ubicacion' => 'Ubicación',
		]);
		$validator->customErrors([
			'documento' => 'El documento no es válido.',
		]);
		if ($validator->isSuccess()) {
			$visita = AccesoVisita::enVisita($this->persona->documento, $this->ubicacion->id, new \DateTime());
			$empleado = AccesoEmpleado::enVisita($this->persona->documento, $this->ubicacion->id, new \DateTime());
			$contratista = AccesoContratista::enVisita($this->persona->documento, $this->ubicacion->id, new \DateTime());
			$visitaEnrolada = AccesoVisitaEnrolada::enVisita($this->persona->documento, $this->ubicacion->id, new \DateTime());
			$enVisita = $visita || $empleado || $contratista || $visitaEnrolada;
			$tipo = null;
			if ($visita) {
				$tipo = 'visita';
			} else if ($empleado) {
				$tipo = 'empleado';
			} else if ($contratista) {
				$tipo = 'contratista';
			} else if ($visitaEnrolada) {
				$tipo = 'visita enrolada';
			}

			return [$enVisita, $tipo];
		}
		$this->errores = $validator->getErrors();

		return [false, null];
	}

	public function esVisitaEnrolada()
	{
		$validator = Validador::validate([
			'documento'    => $this->persona->documento,
		], [
			'documento'    => ['required', 'documento'],
		], [
			'documento'    => 'Documento',
		]);
		$validator->customErrors([
			'documento' => 'El documento no es válido.',
		]);
		if ($validator->isSuccess()) {
			$conex = new Conexiones();
			$sql = '
				select a.id
				FROM accesos_visitas_enroladas a
				INNER JOIN visitas AS v ON a.visita_id = v.visita_id
				INNER JOIN personas AS p ON v.persona_id = p.id
				where p.documento = :documento and p.borrado = 0 
				LIMIT 1';
			$sql_params = [':documento' => $this->persona->documento];
			$res = $conex->consulta(Conexiones::SELECT, $sql, $sql_params);
			if (!empty($res) && isset($res[0])) {
				return true;
			} else
				return false;
		}

		$this->errores = $validator->getErrors();

		return false;
	}

	private function bajaEmpleado()
	{
		/** @var AccesoEmpleado $acceso */
		$acceso = AccesoEmpleado::obtener(0);
		$acceso->empleado_id = $this->empleado->id;
		if ($acceso->alta()) {
			$this->tipo_id = $acceso->id;
			$this->tipo_modelo = Util::obtenerClaseDeObjeto($acceso);

			return $this->registrarAcceso();
		}
		$this->errores = $acceso->errores;

		return false;
	}


	public function altaSync()
	{
		switch ($this->tipo_acceso) {
			case static::EMPLEADO:
				$acceso = AccesoEmpleado::obtener(0);
				$acceso->empleado_id = $this->empleado->id;
				break;
			case static::VISITA_ENROLADA:
				$acceso = AccesoVisitaEnrolada::obtener(0);
				$acceso->visita_id = $this->empleado->id;
				break;
			case static::CONTRATISTA:
				$acceso = AccesoContratista::obtener(0);
				$acceso->credencial = $this->credencial;
				$acceso->empleado = $this->contratista_empleado;
				break;
			case static::VISITA_TARJETA_ENROLADA:
				$acceso = AccesoVisita::obtener(0);
				break;
		}
		if ($acceso->alta()) {
			$this->tipo_id = $acceso->id;
			$this->tipo_modelo = Util::obtenerClaseDeObjeto($acceso);
			$resultado = $this->registrarAcceso(true);
			return $resultado;
		}
		$this->errores = $acceso->errores;

		return false;

		return false;
	}

	public function cierreSync()
	{

		if ($this->tipo_acceso == Acceso::EMPLEADO) {
			$acceso = AccesoEmpleado::obtener($this->tipo_id);
			return  $acceso->terminar($this->id, $this->persona_egreso, $this->tipo_egreso, $this->egreso, $this->observaciones);
		} elseif ($this->tipo_acceso == Acceso::VISITA_ENROLADA) {
			$acceso = AccesoVisitaEnrolada::obtener($this->tipo_id);
			return  $acceso->terminar($this->id, $this->persona_egreso, $this->tipo_egreso, $this->egreso, $this->observaciones);
		} elseif ($this->tipo_acceso == Acceso::VISITA_TARJETA_ENROLADA) {
			$acceso = AccesoVisita::obtener($this->tipo_id);
			return  $acceso->terminar($this->id, $this->persona_egreso, $this->tipo_egreso);
		} elseif ($this->tipo_acceso == Acceso::CONTRATISTA) {
			$acceso = AccesoContratista::obtener($this->tipo_id);
			return  $acceso->terminar($this->id, $this->persona_egreso, $this->tipo_egreso);
		}
	}

	public function obtener_id_acceso()
	{
		$empleado = Acceso::EMPLEADO;
		$comision_horaria = Acceso::TIPO_COMISION_HORARIA;

		$sql = "SELECT
						a.id
					FROM accesos a 
					WHERE tipo_ingreso = ".$comision_horaria." AND
					tipo_egreso = ".$comision_horaria." AND
					tipo_modelo = ".$empleado." AND
					hora_ingreso =:hora_ingreso AND
					hora_egreso =:hora_egreso";
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::SELECT, $sql, [':hora_ingreso' => $this->hora_ingreso->format("Y-m-d H:i:s"), ':hora_egreso' => $this->hora_egreso->format("Y-m-d H:i:s")]);

		if (!empty($res) && isset($res[0])) {
			return $res[0]['id'];
		}

		return '0';
	}

	/**
	 * Obtiene el acceso activo en una ubicación por documento.
	 * @param string    $documento
	 * @param integer   $ubicacion	 
	 * @return Acceso
	 */
	public static function obtenerUltimaSalidaEmpleadoPorDocumento($documento, $ubicacion)
	{
		$sql = <<<SQL
			SELECT acc.id, acc.hora_egreso
			FROM accesos AS acc
			JOIN accesos_empleados AS ae ON acc.tipo_id = ae.id 
			JOIN empleados AS e ON ae.empleado_id = e.id
			JOIN personas AS pe ON e.persona_id = pe.id
			WHERE 	pe.documento =  :documento	
			AND acc.ubicacion_id   = :ubicacion 
			AND hora_egreso is not null
			order by acc.hora_egreso desc limit 1 
			SQL;
		$params = [
			':documento'     => $documento,
			':ubicacion'     => $ubicacion,
		];
		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, $params);

		if (is_array($res) && isset($res[0]) && !empty($res)) {
			$obj = new self();
			$res = $res[0];
			$obj->egreso = isset($res['hora_egreso']) ? \DateTime::createFromFormat('Y-m-d H:i:s.u', $res['hora_egreso'].'.000000') : null;
			return $obj;
		}

		return null;
	}

	public static function obtenerDatosaccesoExclusivo($nodo, $persona_id)
	{
		$sql = <<<SQL
				select T.id_persona ,sum(T.cantidad) as cantidad
				from ( 
					
					select ar.id_persona , 1000 as cantidad 
					from accesos_restringidos ar 
					inner join personas p on ar.id_persona = p.id 
							inner join relojes r on r.id  = ar.id_reloj 
					where ar.id_persona = :persona_id
					and r.nodo = :nodo and r.acceso_restringido = 1
					and ar.borrado = 0
					union all
					
					select :persona_id as id_persona , 1 as cantidad
					from relojes r
					left join accesos_restringidos ar on ar.id_reloj = r.id and ar.borrado = 0
					where r.nodo = :nodo and r.acceso_restringido = 1 and ar.id_reloj is null 
					union all 
					
							select :persona_id as id_persona , count(*) as cantidad 
					from accesos_restringidos ar
							inner join relojes r on r.id  = ar.id_reloj 
					where r.nodo  = :nodo and r.acceso_restringido = 1
					and ar.borrado =0 ) as T  
				SQL;
		$params = [
			':nodo'     => $nodo,
			':persona_id'     => $persona_id,
		];
		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, $params);


		if (is_array($res) && isset($res[0]) && !empty($res)) {
			$res = $res[0];
			return $res['cantidad'];
		} else {
			return 0;
		}
	}

	public static function listar_reporte($params = array())
	{

		$campos    = 'id, ubicacion_id, ubicacion, acceso, tipo, documento, nombre,
					apellido, fecha_ingreso, fecha_egreso, hora_ingreso, hora_egreso,
					tipo_ingreso, persona_id_ingreso, tipo_egreso, persona_id_egreso, usuario_ingreso,
					usuario_ingreso_documento, usuario_egreso, usuario_egreso_documento, credencial, observaciones,
					origen, destino, autorizante';

		$sql_params = [];
		$where = [];
		$where1 = [];
		$where2 = [];
		$where3 = [];
		$condicion = '';
		$condicion2 = '';
		$condicion3 = '';

		$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo'])) ? 'tipo' : $params['order']['campo'];
		$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']))   ? 'asc' : $params['order']['dir'];
		$params['start']  = (!isset($params['start'])  || empty($params['start']))  ? 0 :
			$params['start'];
		$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght'])) ? 10 :
			$params['lenght'];
		/*$params['search'] = (!isset($params['search']) || empty($params['search'])) ? '' :
			$params['search'];*/

		$default_params = [
			'filtros'   => [
				'ubicacion'       	=> null,
				'fecha_desde'       => null,
				'fecha_hasta'       => null,
				'otros_criterios'	=> null
			]
		];

		$params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
		$params = array_merge($default_params, $params);

		if (!empty($params['filtros']['ubicacion'])) {
			$where[] = "acc.ubicacion_id = :ubicacion";
			$sql_params[':ubicacion']    = $params['filtros']['ubicacion'];
		}

		if (!empty($params['filtros']['fecha_desde'])) {
			$where[] = "(acc.hora_ingreso >= :fecha_desde)";
			$fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_desde'])->format('Y-m-d');
			$sql_params[':fecha_desde']    = $fecha;
		}

		if (!empty($params['filtros']['fecha_hasta'])) {
			$where[] = "(acc.hora_ingreso <= :fecha_hasta)";
			$fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_hasta'])->format('Y-m-d 23:59:59');
			$sql_params[':fecha_hasta']    = $fecha;
		}

		if ($params['filtros']['sin_cierre'] == "0") {
			$where[] = "acc.hora_egreso IS NOT NULL";
		}
		
		if (!empty($params['filtros']['otros_criterios'])) {
			$where1[] = "(p.documento like :otros_criterios OR p.nombre like :otros_criterios OR p.apellido like :otros_criterios OR c.codigo like :otros_criterios OR con.nombre like :otros_criterios)";
			$where2[] = "(p.documento like :otros_criterios OR p.nombre like :otros_criterios OR p.apellido like :otros_criterios OR c.codigo like :otros_criterios OR a.origen like :otros_criterios)";
			$where3[] = "(p.documento like :otros_criterios OR p.nombre like :otros_criterios OR p.apellido like :otros_criterios)";

			$sql_params[':otros_criterios']    = '%'.$params['filtros']['otros_criterios'].'%';
		}

		$condicion = !empty($where) ? ' WHERE ' . \implode(' AND ', $where) : '';
		$condicion2 = !empty($where) ? ' WHERE ' . \implode(' AND ', $where) : '';
		$condicion3 = !empty($where) ? ' WHERE ' . \implode(' AND ', $where) : '';

		if(!empty($condicion)){
			$condicion  .= !empty($where1) ? ' AND ' . \implode(' OR ', $where1) : '';
		}else{
			$condicion  = !empty($where1) ? ' AND ' . \implode(' OR ', $where1) : '';
		}
		if(!empty($condicion2)){
			$condicion2 .= !empty($where2) ? ' AND ' . \implode(' OR ', $where2) : '';
		}else{
			$condicion2 = !empty($where2) ? ' AND ' . \implode(' OR ', $where2) : '';
		}
		if(!empty($condicion3)){
			$condicion3 .= !empty($where3) ? ' AND ' . \implode(' OR ', $where3) : '';
		}else{
			$condicion3 = !empty($where3) ? ' AND ' . \implode(' OR ', $where3) : '';
		}

		if (!empty($params['filtros']['credencial'])) {
			$condicion .= " AND c.codigo = " . $params['filtros']['credencial'];
			$condicion2 .= " AND c.codigo = " . $params['filtros']['credencial'];
			$condicion3 .= " AND acc.id IS NULL";
		}

		$claseC = static::getClassIndex(new AccesoContratista());
		$tipoC 	= static::CONTRATISTA;
		$accesoC = static::tipoAccesoToString(static::CONTRATISTA);

		$consultaC = <<<SQL
        SELECT
			acc.id                                                              AS id,
			acc.ubicacion_id,
			u.nombre                                                            AS ubicacion,
			"$accesoC"                             		                        AS acceso,
			$tipoC                                                       		AS tipo,
			p.documento 														AS documento,
			p.nombre                                                            AS nombre,
			p.apellido                                                          AS apellido,
			acc.hora_ingreso							AS fecha_ingreso,
			acc.hora_egreso   							AS fecha_egreso,
			DATE_FORMAT(acc.hora_ingreso, '%H:%i:%s')                              AS hora_ingreso,
			DATE_FORMAT(acc.hora_egreso, '%H:%i:%s')                               AS hora_egreso,	
			acc.tipo_ingreso,
			acc.persona_id_ingreso,
			acc.tipo_egreso,
			acc.persona_id_egreso,
			CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, ''))   AS usuario_ingreso,
			pin.documento                                                       AS usuario_ingreso_documento,
			CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso,
			pout.documento                                                      AS usuario_egreso_documento,
			c.codigo                                                            AS credencial,
			acc.observaciones,
			con.nombre AS origen,
			u.nombre AS destino,
			CONCAT(COALESCE(pa.nombre, ''), ' ', COALESCE(pa.apellido, '')) AS autorizante
		FROM accesos AS acc
			JOIN accesos_contratistas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = $claseC
			JOIN credenciales AS c ON a.credencial_id = c.id
			JOIN ubicaciones AS u ON u.id = acc.ubicacion_id
			JOIN contratista_personal AS cp ON cp.id = a.empleado_id
			JOIN contratistas AS con ON con.id = cp.contratista_id
			JOIN personas AS p ON p.id = cp.persona_id
			JOIN personas AS pin ON acc.persona_id_ingreso = pin.id
			JOIN empleados AS emp ON cp.autorizante_id = emp.id
			JOIN personas AS pa ON emp.persona_id = pa.id
			LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
			$condicion   
SQL;

		$claseV = static::getClassIndex(new AccesoVisita());
		$tipoV 	= static::VISITANTE;
		$accesoV = static::tipoAccesoToString(static::VISITANTE);

		$consultaV = <<<SQL
        SELECT
			acc.id                                                              AS id,
			acc.ubicacion_id,
			u.nombre                                                            AS ubicacion,
			"$accesoV"                                                          AS acceso,
			$tipoV                                                              AS tipo,
			p.documento,
			p.nombre                                                            AS nombre,
			p.apellido                                                          AS apellido,
			acc.hora_ingreso							AS fecha_ingreso,
			acc.hora_egreso   							AS fecha_egreso,
			DATE_FORMAT(acc.hora_ingreso, '%H:%i:%s')                              AS hora_ingreso,
			DATE_FORMAT(acc.hora_egreso, '%H:%i:%s')                               AS hora_egreso,
			CASE
			WHEN c.acceso_id < 1  THEN acc.tipo_ingreso
			ELSE  6    
		END as tipo_ingreso	,
			acc.persona_id_ingreso,
			CASE
			WHEN c.acceso_id < 1 or acc.tipo_egreso is null THEN acc.tipo_egreso
			ELSE  6    
		END as tipo_egreso	,	
			acc.persona_id_egreso,
			CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, ''))   AS usuario_ingreso,
			pin.documento                                                       AS usuario_ingreso_documento,
			CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso,
			pout.documento                                                      AS usuario_egreso_documento,
			c.codigo                                                            AS credencial,
			acc.observaciones,
			a.origen AS origen,
			a.destino AS destino,
			CONCAT(COALESCE(pau.nombre, ''), ' ', COALESCE(pau.apellido, '')) AS autorizante
		FROM accesos AS acc
			JOIN accesos_visitas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = $claseV
			LEFT JOIN credenciales AS c ON a.credencial_id = c.id
			JOIN ubicaciones AS u ON u.id = acc.ubicacion_id
			JOIN personas AS p ON p.id = a.persona_id
			JOIN personas AS pin ON acc.persona_id_ingreso = pin.id 
			JOIN empleados AS eau ON eau.id = a.autorizante_id
			JOIN personas AS pau ON eau.persona_id = pau.id 
			LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
			$condicion2   
SQL;

		$claseVE = static::getClassIndex(new AccesoVisitaEnrolada());
		$tipoVE 	= static::VISITA_ENROLADA;
		$accesoVE = static::tipoAccesoToString(static::VISITA_ENROLADA);

		$consultaVE = <<<SQL
        SELECT
			acc.id                                                              AS id,
			acc.ubicacion_id,
			u.nombre                                                            AS ubicacion,
			"$accesoVE"		                                                    AS acceso,
			$tipoVE         		                                            AS tipo,
			p.documento,
			p.nombre                                                            AS nombre,
			p.apellido                                                          AS apellido,								
			acc.hora_ingreso							AS fecha_ingreso,
			acc.hora_egreso   							AS fecha_egreso,
			DATE_FORMAT(acc.hora_ingreso, '%H:%i:%s')                              AS hora_ingreso,
			DATE_FORMAT(acc.hora_egreso, '%H:%i:%s')                               AS hora_egreso,
			acc.tipo_ingreso,
			acc.persona_id_ingreso,
			acc.tipo_egreso,
			acc.persona_id_egreso,
			CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, ''))   AS usuario_ingreso,
			pin.documento                                                       AS usuario_ingreso_documento,
			CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso,
			pout.documento                                                      AS usuario_egreso_documento,
			NULL AS credencial,
			acc.observaciones,
			NULL AS origen,
			NULL AS destino,
			CONCAT(COALESCE(pau.nombre, ''), ' ', COALESCE(pau.apellido, '')) AS autorizante
		FROM accesos AS acc
			JOIN accesos_visitas_enroladas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = $claseVE
			JOIN ubicaciones AS u ON u.id = acc.ubicacion_id
			JOIN visitas as vis on vis.visita_id = a.visita_id
			JOIN personas AS p ON p.id = vis.persona_id
			JOIN personas AS pin ON acc.persona_id_ingreso = pin.id 
			JOIN empleados AS eau ON eau.id = vis.autorizante_id
			JOIN personas AS pau ON eau.persona_id = pau.id 
			LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
			$condicion3
SQL;


		$consultaCVVE = <<<SQL
         SELECT r.id,
				r.ubicacion_id,
				r.ubicacion,
				r.acceso,
				r.tipo,
				r.documento,
				r.nombre,
				r.apellido,
				r.fecha_ingreso,
				r.fecha_egreso,
				r.hora_ingreso,
				r.hora_egreso,
				r.tipo_ingreso,
				r.persona_id_ingreso,
				r.tipo_egreso,
				r.persona_id_egreso,
				r.usuario_ingreso,
				r.usuario_ingreso_documento,
				r.usuario_egreso,
				r.usuario_egreso_documento,
				r.credencial,
				r.observaciones,
				r.origen,
				r.destino,
				r.autorizante
			FROM ({$consultaC}
				UNION
				  {$consultaV}
				UNION
				  {$consultaVE}
			) AS r
SQL;

		$sql = '';

		switch ($params['filtros']['tipos_accesos']) {
			case $claseV:
				$sql = $consultaV;
				break;

			case $claseC:
				$sql = $consultaC;
				break;

			case $claseVE:
				$sql = $consultaVE;
				break;

			default:
				$sql = $consultaCVVE;
				break;
		}	

		$data = self::listadoAjaxMasivo($campos, $sql, $params, $sql_params);
		return $data;
	}



	public static function listar_hist_contratista_visitas_excel($params)
	{
		$cnx    = new Conexiones();
		$sql_params = [];

		$where = [];
		$where1 = [];
		$where2 = [];
		$where3 = [];

		$condicion = '';
		$condicion2 = '';
		$condicion3 = '';
		$condicion_filtros = '';
		$order = '';
		//$search = [];

		$default_params = [
			'order'     => [
				[
					'campo' => 'id',
					'dir'   => 'ASC',
				],
			],
			'start'     => 0,
			'lenght'    => 10,
			//'search'    => '',
			'filtros'   => [
				'ubicacion'       	=> null,
				'tipos_accesos'     => null,
				'fecha_desde'      	=> null,
				'fecha_hasta'       => null,
				'credencial'       	=> null,
				'sin_cierre'       	=> null,
				'otros_criterios'	=> null
			],

		];

		$params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
		$params = array_merge($default_params, $params);

		if (!empty($params['filtros']['ubicacion'])) {
			$where[] = "acc.ubicacion_id = :ubicacion";
			$sql_params[':ubicacion']    = $params['filtros']['ubicacion'];
		}

		if (!empty($params['filtros']['fecha_desde'])) {
			$where[] = "(acc.hora_ingreso >= :fecha_desde)";
			$fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_desde'])->format('Y-m-d');
			$sql_params[':fecha_desde']    = $fecha;
		}

		if (!empty($params['filtros']['fecha_hasta'])) {
			$where[] = "(acc.hora_ingreso <= :fecha_hasta)";
			$fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_hasta'])->format('Y-m-d 23:59:59');
			$sql_params[':fecha_hasta']    = $fecha;
		}

		if ($params['filtros']['sin_cierre'] == "0") {
			$where[] = "acc.hora_egreso IS NOT NULL";
		}

		if (!empty($params['filtros']['otros_criterios'])) {
			$where1[] = "p.documento like :otros_criterios";
			$where1[] = " p.nombre like :otros_criterios OR p.apellido like :otros_criterios OR c.codigo like :otros_criterios OR con.nombre like :otros_criterios ";
			$where2[] = "p.documento like :otros_criterios";
			$where2[] = " p.nombre like :otros_criterios OR p.apellido like :otros_criterios OR c.codigo like :otros_criterios OR a.origen like :otros_criterios";
			$where3[] = "p.documento like :otros_criterios";
			$where3[] = " p.nombre like :otros_criterios OR p.apellido like :otros_criterios";

			$sql_params[':otros_criterios']    = '%'.$params['filtros']['otros_criterios'].'%';
		}

		
		$condicion = !empty($where) ? ' WHERE ' . \implode(' AND ', $where) : '';
		$condicion2 = !empty($where) ? ' WHERE ' . \implode(' AND ', $where) : '';
		$condicion3 = !empty($where) ? ' WHERE ' . \implode(' AND ', $where) : '';

		if(!empty($condicion)){
			$condicion  .= !empty($where1) ? ' AND ' . \implode(' OR ', $where1) : '';
		}else{
			$condicion  = !empty($where1) ? ' AND ' . \implode(' OR ', $where1) : '';
		}
		if(!empty($condicion2)){
			$condicion2 .= !empty($where2) ? ' AND ' . \implode(' OR ', $where2) : '';
		}else{
			$condicion2 = !empty($where2) ? ' AND ' . \implode(' OR ', $where2) : '';
		}
		if(!empty($condicion3)){
			$condicion3 .= !empty($where3) ? ' AND ' . \implode(' OR ', $where3) : '';
		}else{
			$condicion3 = !empty($where3) ? ' AND ' . \implode(' OR ', $where3) : '';
		}

		if (!empty($params['filtros']['credencial'])) {
			$condicion .= " AND c.codigo = " . $params['filtros']['credencial'];
			$condicion2 .= " AND c.codigo = " . $params['filtros']['credencial'];
			$condicion3 .= " AND acc.id IS NULL";
		}

		//Esto se comenta porque se agrega el filtro otros criterios

		$claseC = static::getClassIndex(new AccesoContratista());
		$tipoC 	= static::CONTRATISTA;
		$accesoC = static::tipoAccesoToString(static::CONTRATISTA);

		$consultaC = <<<SQL
		SELECT
			acc.id                                                              AS id,
			"$accesoC"                             		                        AS acceso,					
			p.documento 														AS documento,
			p.nombre                                                            AS nombre,
			p.apellido                                                          AS apellido,
			u.nombre                                                            AS ubicacion,	
			DATE_FORMAT(acc.hora_ingreso, "%d/%m/%Y")							AS fecha_ingreso,
			DATE_FORMAT(acc.hora_ingreso, '%H:%i:%s')                              AS hora_ingreso,			
			acc.tipo_ingreso,
			c.codigo                                                            AS credencial,
			pin.documento                                                       AS usuario_ingreso_documento,
			CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, ''))   AS usuario_ingreso,
			DATE_FORMAT(acc.hora_egreso, "%d/%m/%Y")   							AS fecha_egreso,			
			DATE_FORMAT(acc.hora_egreso, '%H:%i:%s')                               AS hora_egreso,							
			acc.tipo_egreso,			
			pout.documento                                                      AS usuario_egreso_documento,
			CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso,						
			acc.observaciones,
			con.nombre AS origen,
			u.nombre AS destino,
			CONCAT(COALESCE(pa.nombre, ''), ' ', COALESCE(pa.apellido, '')) AS autorizante			
SQL;

		$fromC = <<<SQL
        FROM accesos AS acc
			JOIN accesos_contratistas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = $claseC
			JOIN credenciales AS c ON a.credencial_id = c.id
			JOIN ubicaciones AS u ON u.id = acc.ubicacion_id
			JOIN contratista_personal AS cp ON cp.id = a.empleado_id
			JOIN contratistas AS con ON con.id = cp.contratista_id
			JOIN personas AS p ON p.id = cp.persona_id
			JOIN personas AS pin ON acc.persona_id_ingreso = pin.id
			JOIN empleados AS emp ON cp.autorizante_id = emp.id
			JOIN personas AS pa ON emp.persona_id = pa.id
			LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
			$condicion
SQL;

		$claseV = static::getClassIndex(new AccesoVisita());
		$tipoV 	= static::VISITANTE;
		$accesoV = static::tipoAccesoToString(static::VISITANTE);

		$consultaV = <<<SQL
		SELECT
			acc.id                                                              AS id,
			"$accesoV"                                                          AS acceso,			
			p.documento,
			p.nombre                                                            AS nombre,
			p.apellido                                                          AS apellido,
			u.nombre                                                            AS ubicacion,			
			DATE_FORMAT(acc.hora_ingreso, "%d/%m/%Y")							AS fecha_ingreso,
			DATE_FORMAT(acc.hora_ingreso, '%H:%i:%s')                              AS hora_ingreso,
			CASE
			WHEN c.acceso_id < 1  THEN acc.tipo_ingreso
			ELSE  6    
			END as tipo_ingreso	,
			c.codigo                                                            AS credencial,
			pin.documento                                                       AS usuario_ingreso_documento,
			CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, ''))   AS usuario_ingreso,
			DATE_FORMAT(acc.hora_egreso, "%d/%m/%Y")   							AS fecha_egreso,			
			DATE_FORMAT(acc.hora_egreso, '%H:%i:%s')                               AS hora_egreso,						
			CASE
			WHEN c.acceso_id < 1 or acc.tipo_egreso is null THEN acc.tipo_egreso
			ELSE  6    
			END as tipo_egreso	,	
			pout.documento                                                      AS usuario_egreso_documento,
			CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso,
			acc.observaciones,
			a.origen AS origen,
			a.destino AS destino,
			CONCAT(COALESCE(pau.nombre, ''), ' ', COALESCE(pau.apellido, '')) AS autorizante	
SQL;

		$fromV = <<<SQL
		FROM accesos AS acc
					JOIN accesos_visitas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = $claseV
					LEFT JOIN credenciales AS c ON a.credencial_id = c.id
					JOIN ubicaciones AS u ON u.id = acc.ubicacion_id
					JOIN personas AS p ON p.id = a.persona_id
					JOIN personas AS pin ON acc.persona_id_ingreso = pin.id 
					JOIN empleados AS eau ON eau.id = a.autorizante_id
					JOIN personas AS pau ON eau.persona_id = pau.id 
					LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
					$condicion2
SQL;

		$claseVE = static::getClassIndex(new AccesoVisitaEnrolada());
		$tipoVE 	= static::VISITA_ENROLADA;
		$accesoVE = static::tipoAccesoToString(static::VISITA_ENROLADA);

		$consultaVE = <<<SQL
		SELECT
			acc.id                                                              AS id,
			"$accesoVE"		                                                    AS acceso,			
			p.documento,
			p.nombre                                                            AS nombre,
			p.apellido                                                          AS apellido,								
			u.nombre                                                            AS ubicacion,			
			DATE_FORMAT(acc.hora_ingreso, "%d/%m/%Y")							AS fecha_ingreso,
			DATE_FORMAT(acc.hora_ingreso, '%H:%i:%s')                              AS hora_ingreso,
			acc.tipo_ingreso,
			NULL AS credencial,
			pin.documento                                                       AS usuario_ingreso_documento,
			CONCAT(COALESCE(pin.nombre, ''), ' ', COALESCE(pin.apellido, ''))   AS usuario_ingreso,
			DATE_FORMAT(acc.hora_egreso, "%d/%m/%Y")   							AS fecha_egreso,			
			DATE_FORMAT(acc.hora_egreso, '%H:%i:%s')                               AS hora_egreso,					
			acc.tipo_egreso,
			pout.documento                                                      AS usuario_egreso_documento,			
			CONCAT(COALESCE(pout.nombre, ''), ' ', COALESCE(pout.apellido, '')) AS usuario_egreso,			
			acc.observaciones,
			NULL AS origen,
			NULL AS destino,
			CONCAT(COALESCE(pau.nombre, ''), ' ', COALESCE(pau.apellido, '')) AS autorizante
		SQL;

		$fromVE = <<<SQL
		FROM accesos AS acc
			JOIN accesos_visitas_enroladas AS a ON acc.tipo_id = a.id AND acc.tipo_modelo = $claseVE
			JOIN ubicaciones AS u ON u.id = acc.ubicacion_id
			JOIN visitas as vis on vis.visita_id = a.visita_id
			JOIN personas AS p ON p.id = vis.persona_id
			JOIN personas AS pin ON acc.persona_id_ingreso = pin.id 
			JOIN empleados AS eau ON eau.id = vis.autorizante_id
			JOIN personas AS pau ON eau.persona_id = pau.id 
			LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
			$condicion3
SQL;

		$consultaCVVE = <<<SQL
		SELECT acc.id,
			acc.acceso,					
			acc.documento,
			acc.nombre,
			acc.apellido,
			acc.ubicacion,	
			acc.fecha_ingreso,
			acc.hora_ingreso,
			acc.tipo_ingreso,
			acc.credencial,
			acc.usuario_ingreso_documento,
			acc.usuario_ingreso,
			acc.fecha_egreso,			
			acc.hora_egreso,	
			acc.tipo_egreso,
			acc.usuario_egreso_documento,
			acc.usuario_egreso,
			acc.observaciones,
			acc.origen,
			acc.destino,
			acc.autorizante			
SQL;
		$fromCVVE = <<<SQL
		FROM ({$consultaC} {$fromC}
					UNION
						{$consultaV} {$fromV}
					UNION
						{$consultaVE} {$fromVE}
				) AS acc
SQL;

		$sql = '';
		$from = '';

		switch ($params['filtros']['tipos_accesos']) {
			case $claseV:
				$sql = $consultaV;
				$from = $fromV;
				break;

			case $claseC:
				$sql = $consultaC;
				$from = $fromC;
				break;

			case $claseVE:
				$sql = $consultaVE;
				$from = $fromVE;
				break;

			default:
				$sql = $consultaCVVE;
				$from = $fromCVVE;
				break;
		}

		$group = <<<SQL
        GROUP BY acc.id 

SQL;

		$counter_query  = "SELECT COUNT(acc.id) AS total {$from}";

		/**Orden de las columnas */
		$orderna = [];
		foreach ($params['order'] as $i => $val) {
			$orderna[]  = "{$val['campo']} {$val['dir']}";
		}

		$order .= implode(',', $orderna);

		$limit = (isset($params['lenght']) && isset($params['start']) && $params['lenght'] != '')
			? " LIMIT  {$params['start']}, {$params['lenght']}" : ' ';


		$order .= (($order == '') ? '' : ', ') . 'acc.id desc';

		$order = ' ORDER BY ' . $order;

		ini_set('memory_limit', '1024M');
		$lista = $cnx->consulta(Conexiones::SELECT,  $sql . $from .  $group . $order . $limit, $sql_params);

		return ($lista) ? $lista : [];
	}

	static public function listar_horas_trabajadas($params)
	{
		$rol = Usuario::obtenerUsuarioLogueado();
		$campos    = 'id, ubicacion_id, ubicacion, codep, documento, cuit, nombre, apellido, fecha_entrada, fecha_egreso, 
					  hora_entrada, hora_egreso, horas_trabajadas, tipo_ingreso, tipo_egreso, observaciones, texto';
		$sql_params = [];

		$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo'])) ? 'tipo' : $params['order']['campo'];
		$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']))   ? 'asc' : $params['order']['dir'];
		$params['start']  = (!isset($params['start'])  || empty($params['start']))  ? 0 :
			$params['start'];
		$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght'])) ? 10 :
			$params['lenght'];

		if (!empty($params['filtros']['dependencia'])) {
			$where[] = "d.id = :dependencia";
			$sql_params[':dependencia']    = $params['filtros']['dependencia'];
		}else{
            if (!empty($rol->dependencias)) {
                $deps = implode("," ,$rol->dependencias);
                $where[] = "d.id IN ( " . $deps . ")";
            }            
        }

		if (!empty($params['filtros']['fecha_desde'])) {
			$where[] = "(acc.hora_ingreso >= :fecha_desde)";
			$fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_desde'])->format('Y-m-d');
			$sql_params[':fecha_desde']    = $fecha;
		}

		if (!empty($params['filtros']['fecha_hasta'])) {
			$where[] = "(acc.hora_ingreso <= :fecha_hasta)";
			$fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_hasta'])->format('Y-m-d 23:59:59');
			$sql_params[':fecha_hasta']    = $fecha;
		}

		if (!empty($params['filtros']['otro_criterio'])) {
			$where[] = "(p.documento like :otro_criterio OR p.nombre like :otro_criterio OR p.apellido like :otro_criterio OR cp.cuit like :otro_criterio)";
			$sql_params[':otro_criterio']    = '%'.$params['filtros']['otro_criterio'].'%';
		}

		$condicion = !empty($where) ? ' WHERE ' . \implode(' AND ', $where) : '';
        $condicion = empty($condicion) ? "WHERE acc.hora_ingreso >= '".date("Y-m-d", strtotime("-7 days"))."'" : $condicion;
		$condicion .= ' AND acc.hora_egreso IS NOT NULL';

		//SE INSTANCIA POR SI HICIERA FALTA DEFINIR LA PARTICIÓN DE LA TABLA DE ACCESO
		$anio_desde = (!empty($params['filtros']['fecha_desde'])) ? \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_desde'])->format('Y') : date('Y');
		$anio_hasta = (!empty($params['filtros']['fecha_hasta'])) ? \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_hasta'])->format('Y') : date('Y');
		$partition = [];
		for ($i=$anio_desde; $i <= $anio_hasta; $i++) { 
			array_push($partition,'p'.$i);
		}
		$partition = implode(",",$partition);

		$consulta = <<<SQL
         SELECT acc.id                                              AS id,
				acc.ubicacion_id,
				e.nombre                                            AS ubicacion,
				d.nombre                                            AS codep,
				p.documento,
				cp.cuit,
				p.nombre                                            AS nombre,
				p.apellido                                          AS apellido,
				acc.hora_ingreso           AS fecha_entrada,
				acc.hora_egreso           AS fecha_egreso,
				Date_format(acc.hora_ingreso, '%H:%i')              AS hora_entrada,
				Date_format(acc.hora_egreso, '%H:%i')               AS hora_egreso,
				Timediff(( acc.hora_egreso ), ( acc.hora_ingreso )) AS horas_trabajadas,
				acc.tipo_ingreso,
				acc.tipo_egreso,
				acc.observaciones,
				''                                                  AS texto
			FROM accesos AS acc
				JOIN accesos_empleados AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = 1
				JOIN ubicaciones AS e ON e.id = acc.ubicacion_id
				JOIN empleados AS cp ON cp.id = ac.empleado_id AND cp.borrado = 0
				JOIN personas AS p ON p.id = cp.persona_id
				LEFT JOIN empleado_dependencia_principal edp ON ( cp.id = edp.id_empleado AND Isnull(edp.fecha_hasta) AND edp.borrado = 0 )
				LEFT JOIN dependencias d ON ( d.id = edp.id_dependencia_principal AND Isnull(d.fecha_hasta) )
				JOIN personas AS pin ON pin.id = acc.persona_id_ingreso
				LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
				LEFT JOIN empleado_contrato ec ON ( cp.id = ec.id_empleado AND Isnull(ec.fecha_hasta) AND ec.borrado = 0 AND ec.id_tipo_contrato IN ( 1, 17, 15, 4, 3, 2, 5, 6, 7, 8, 9, 18, 16, 12, 11, 10, 19, 14, 13 ) )
			$condicion			 	
SQL;
		$data = self::listadoAjaxMasivo($campos, $consulta, $params, $sql_params);
		return $data;
	}

	public static function listar_horas_trabajadas_excel($params)
	{
		$cnx    = new Conexiones();
		$sql_params = [];
		$where = [];
		$condicion = '';
		$condicion_filtros = '';
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
				'dependencia'       => null,
				'fecha_desde'       => null,
				'fecha_hasta'       => null,
				'otro_criterio'		=> null
			],

		];

		$params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
		$params = array_merge($default_params, $params);

		if (!empty($params['filtros']['dependencia'])) {
			$where[] = "d.id = :dependencia";
			$sql_params[':dependencia']    = $params['filtros']['dependencia'];
		}else{
			if (!empty($rol->dependencias)) {
				$deps = implode("," ,$rol->dependencias);
				$where[] = "d.id IN ( " . $deps . ")";
			}        
		}

		if (!empty($params['filtros']['fecha_desde'])) {
			$where[] = "(DATE(acc.hora_ingreso) >= STR_TO_DATE(:fecha_desde, '%d/%m/%Y'))";
			$sql_params[':fecha_desde']    = $params['filtros']['fecha_desde'];
		}

		if (!empty($params['filtros']['fecha_hasta'])) {
			$where[] = "(DATE(acc.hora_ingreso) <= STR_TO_DATE(:fecha_hasta, '%d/%m/%Y'))";
			$sql_params[':fecha_hasta']    = $params['filtros']['fecha_hasta'];
		}

		if (!empty($params['filtros']['otro_criterio'])) {
			$where[] = "(p.documento like :otro_criterio OR p.nombre like :otro_criterio OR p.apellido like :otro_criterio OR cp.cuit like :otro_criterio)";
			$sql_params[':otro_criterio']    = '%'.$params['filtros']['otro_criterio'].'%';
		}

		$condicion = !empty($where) ? ' WHERE ' . \implode(' AND ', $where) : '';
		$condicion .= ' AND acc.hora_egreso IS NOT NULL';

		$anio_desde = (!empty($params['filtros']['fecha_desde'])) ? \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_desde'])->format('Y') : date('Y');
		$anio_hasta = (!empty($params['filtros']['fecha_hasta'])) ? \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_hasta'])->format('Y') : date('Y');
		$partition = [];
		for ($i=$anio_desde; $i <= $anio_hasta; $i++) { 
			array_push($partition,'p'.$i);
		}
		$partition = implode(",",$partition);

		$sql = <<<SQL
		SELECT  acc.id                                              AS id,
				p.documento,
				cp.cuit,
				p.nombre                                            AS nombre,
				p.apellido                                          AS apellido,
				d.nombre                                            AS codep,		
				Date_format(acc.hora_ingreso, '%d/%m/%Y')           AS fecha_entrada,
				Date_format(acc.hora_ingreso, '%H:%i')              AS hora_entrada,
				Date_format(acc.hora_egreso, '%H:%i')               AS hora_egreso,
				Timediff(( acc.hora_egreso ), ( acc.hora_ingreso )) AS horas_trabajadas						
SQL;

		$from = <<<SQL
		FROM   accesos PARTITION ($partition) AS acc
				JOIN accesos_empleados AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = 1
				JOIN ubicaciones AS e ON e.id = acc.ubicacion_id
				JOIN empleados AS cp ON cp.id = ac.empleado_id AND cp.borrado = 0
				JOIN personas AS p ON p.id = cp.persona_id
				LEFT JOIN empleado_dependencia_principal edp ON ( cp.id = edp.id_empleado AND Isnull(edp.fecha_hasta) AND edp.borrado = 0 )
				LEFT JOIN dependencias d ON ( d.id = edp.id_dependencia_principal AND Isnull(d.fecha_hasta) )
				JOIN personas AS pin ON pin.id = acc.persona_id_ingreso
				LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
				LEFT JOIN empleado_contrato ec ON ( cp.id = ec.id_empleado AND Isnull(ec.fecha_hasta) AND ec.borrado = 0 AND ec.id_tipo_contrato IN ( 1, 17, 15, 4, 3, 2, 5, 6, 7, 8, 9, 18, 16, 12, 11, 10, 19, 14, 13 ) )
			$condicion	
SQL;

		$group = <<<SQL
SQL;

		$counter_query  = "SELECT COUNT(acc.id) AS total {$from}";

		$limit = (isset($params['lenght']) && isset($params['start']) && $params['lenght'] != '')
			? " LIMIT  {$params['start']}, {$params['lenght']}" : ' ';

		$order .= (($order == '') ? '' : ', ') . 'codep, documento, hora_ingreso';

		$order = ' ORDER BY ' . $order;

		ini_set('memory_limit', '1024M');
		$lista = $cnx->consulta(Conexiones::SELECT,  $sql . $from .  $group . $order . $limit, $sql_params);

		return ($lista) ? $lista : [];
	}


	public static function listar_horas_trabajadas_agrup_excel($params)
	{
		$cnx    = new Conexiones();
		$sql_params = [];
		$where = [];
		$condicion = '';
		$condicion_filtros = '';
		$order = '';
		$search = [];
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
				'dependencia'       => null,
				'fecha_desde'       => null,
				'fecha_hasta'       => null,
				'otro_criterio'		=> null
			],

		];

		$params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
		$params = array_merge($default_params, $params);

		if (!empty($params['filtros']['dependencia'])) {
			$where[] = "d.id = :dependencia";
			$sql_params[':dependencia']    = $params['filtros']['dependencia'];
		}else{
			if (!empty($rol->dependencias)) {
				$deps = implode("," ,$rol->dependencias);
				$where[] = "d.id IN ( " . $deps . ")";
			}        
		}

		if (!empty($params['filtros']['fecha_desde'])) {
			$where[] = "(DATE(acc.hora_ingreso) >= STR_TO_DATE(:fecha_desde, '%d/%m/%Y'))";
			$sql_params[':fecha_desde']    = $params['filtros']['fecha_desde'];
		}

		if (!empty($params['filtros']['fecha_hasta'])) {
			$where[] = "(DATE(acc.hora_ingreso) <= STR_TO_DATE(:fecha_hasta, '%d/%m/%Y'))";
			$sql_params[':fecha_hasta']    = $params['filtros']['fecha_hasta'];
		}

		if (!empty($params['filtros']['otro_criterio'])) {
			$where[] = "(p.documento like :otro_criterio OR p.nombre like :otro_criterio OR p.apellido like :otro_criterio OR cp.cuit like :otro_criterio)";
			$sql_params[':otro_criterio']    = '%'.$params['filtros']['otro_criterio'].'%';
		}

		$condicion = !empty($where) ? ' WHERE ' . \implode(' AND ', $where) : '';
		$condicion .= ' AND acc.hora_egreso IS NOT NULL';

		$sql = <<<SQL
		SELECT  acc.id                                              AS id,
				p.documento,
				cp.cuit,
				p.nombre                                            AS nombre,
				p.apellido                                          AS apellido,
				d.nombre                                            AS codep,				
				Date_format(acc.hora_ingreso, '%d/%m/%Y')           AS fecha_entrada,
				SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF((acc.hora_egreso), (acc.hora_ingreso))))) 					AS horas_trabajadas				
SQL;

		$from = <<<SQL
		FROM   accesos AS acc
				JOIN accesos_empleados AS ac ON acc.tipo_id = ac.id AND acc.tipo_modelo = 1
				JOIN ubicaciones AS e ON e.id = acc.ubicacion_id
				JOIN empleados AS cp ON cp.id = ac.empleado_id AND cp.borrado = 0
				JOIN personas AS p ON p.id = cp.persona_id
				LEFT JOIN empleado_dependencia_principal edp ON ( cp.id = edp.id_empleado AND Isnull(edp.fecha_hasta) AND edp.borrado = 0 )
				LEFT JOIN dependencias d ON ( d.id = edp.id_dependencia_principal AND Isnull(d.fecha_hasta) )
				JOIN personas AS pin ON pin.id = acc.persona_id_ingreso
				LEFT JOIN personas AS pout ON acc.persona_id_egreso = pout.id
				LEFT JOIN empleado_contrato ec ON ( cp.id = ec.id_empleado AND Isnull(ec.fecha_hasta) AND ec.borrado = 0 AND ec.id_tipo_contrato IN ( 1, 17, 15, 4, 3, 2, 5, 6, 7, 8, 9, 18, 16, 12, 11, 10, 19, 14, 13 ) )
			$condicion	
SQL;

		$group = <<<SQL
        GROUP BY cp.cuit, DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') 

SQL;

		$counter_query  = "SELECT COUNT(acc.id) AS total {$from}";

		//$recordsTotal   =  $cnx->consulta(Conexiones::SELECT, $counter_query . $group, $sql_params)[0]['total'];

		$limit = (isset($params['lenght']) && isset($params['start']) && $params['lenght'] != '')
			? " LIMIT  {$params['start']}, {$params['lenght']}" : ' ';

		//$recordsFiltered = $cnx->consulta(Conexiones::SELECT, $counter_query .  $group, $sql_params)[0]['total'];

		$order .= (($order == '') ? '' : ', ') . 'codep, documento, hora_ingreso';

		$order = ' ORDER BY ' . $order;
		
		ini_set('memory_limit', '1024M');
		$lista = $cnx->consulta(Conexiones::SELECT,  $sql . $from .  $group . $order . $limit, $sql_params);
		return ($lista) ? $lista : [];
	}
}
