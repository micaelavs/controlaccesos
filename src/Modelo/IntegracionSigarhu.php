<?php
namespace App\Modelo;

use App\Helper\Conexiones;
use FMT\Modelo;

class IntegracionSigarhu extends Modelo {
    public function validar(){}
    public function alta(){}
    public function baja(){}
    public function modificacion(){}
/**
 * Devuelve el resultado de Empleado::arrayToObject(), tener en cuenta que si esta borrado no trae resultados.
 *
 * @param int $documento
 * @return Empleado::
 */
	public static function obtenerEmpleadoPorDocumento($documento=null) {
		if(empty($documento)){
			return false;
		}
		$res	= false;
		$cnx	= new Conexiones();
		$sql	= 'SELECT e.id FROM empleados AS e INNER JOIN personas AS p ON (p.id = e.persona_id) WHERE p.documento LIKE :documento AND e.borrado = 0 AND p.borrado = 0 ORDER BY e.id DESC LIMIT 1';
		$res = $cnx->consulta(Conexiones::SELECT, $sql, [':documento' => '%'.(int)$documento.'%']);

		if(isset($res[0]) && isset($res[0]['id']) ){
			return Empleado::obtener($res[0]['id']);
		}
		return Empleado::arrayToObject();
	}

/**
 * Modifica la dependencia de una persona empleada. Si existe una dependencia anterior realiza una baja logica y luego ingresa el nuevo registro.
 * Si nunca tuvo una dependencia, ingresa el nuevo registro.
 *
 * @param Modelo\Empleado $empleado - Con los nuevos datos actualizados.
 * @return bool
 */
    static public function modificarDependencia($empleado=null){
		if(empty($empleado->dependencia_principal) || empty($empleado->desde_principal)){
			return false;
		}
		$cnx    = new Conexiones();
		$dato_previo	= 'SELECT id, id_empleado, fecha_desde FROM empleado_dependencia_principal WHERE id_empleado = :id AND ISNULL(fecha_hasta) AND borrado = 0';
		$dato_previo	= $cnx->consulta(Conexiones::SELECT, $dato_previo, [':id' => $empleado->id]);

		if(!empty($dato_previo[0])){
			$fecha_desde_previa	= \DateTime::createFromFormat('Y-m-d', $dato_previo[0]['fecha_desde']);
			$fecha_desde_nueva	= static::parseDL($empleado->desde_principal);
			if($fecha_desde_nueva > $fecha_desde_previa){
				$sql    = 'UPDATE empleado_dependencia_principal SET fecha_hasta = :fecha_hasta WHERE id = :id';
				$res = $cnx->consulta(Conexiones::UPDATE, $sql, [
					':id'           => $dato_previo[0]['id'],
					':fecha_hasta'  => static::parseDL($empleado->desde_principal),
				]);
			} else {
				$sql    = 'UPDATE empleado_dependencia_principal SET borrado = 1 WHERE id = :id';
				$res = $cnx->consulta(Conexiones::UPDATE, $sql, [
					':id'           => $dato_previo[0]['id'],
				]);
			}
		}

        if(!empty($empleado->hasta_principal)){
			$sql    = 'INSERT INTO empleado_dependencia_principal (id_dependencia_principal, id_empleado, fecha_desde, fecha_hasta) VALUES (:id_dependencia_principal,:id_empleado, :fecha_desde, :fecha_hasta)';
			$res = $cnx->consulta(Conexiones::INSERT, $sql, [
				':id_dependencia_principal'	=> $empleado->dependencia_principal,
				':id_empleado'  			=> $empleado->id,
				':fecha_desde'				=> static::parseDL($empleado->desde_principal),
				':fecha_hasta'  			=> static::parseDL($empleado->hasta_principal),
			]);
			if(!empty($res)){
				return true;
			}
        } else {
            $sql    = 'INSERT INTO empleado_dependencia_principal (id_dependencia_principal, id_empleado, fecha_desde) VALUES (:id,:id_empleado, :desde)';
            $res = $cnx->consulta(Conexiones::INSERT, $sql, [
                ':id'           => $empleado->dependencia_principal,
                ':id_empleado'  => $empleado->id,
                ':desde'        => static::parseDL($empleado->desde_principal),
			]);
            if(!empty($res)){
                return true;
            }
        }
        return false;
	}

/**
 * Registra la fecha de actualizacion de los empleados
 *
 * @param int $cuit			- CUIT de empleado
 * @param int $id_emp_ca	- ID de empleado en Control de Accesos
 * @param int $id_emp_sig	- ID de empleado en Sigarhu
 * @return bool
 */
	static public function logActualizaciones($cuit=null, $id_emp_ca=null, $id_emp_sig=null, $descripcion=null){
		if(empty($cuit) || empty($id_emp_sig)){
			return false;
		}
		if($descripcion != null && !is_string($descripcion)){
			$descripcion	= json_encode($descripcion);
		}
		$cnx	= new Conexiones();
		$sql	= <<<SQL
		INSERT INTO log_actualizaciones_sigarhu (cuit, id_empleado_sigarhu, id_empleado_control_accesos, fecha, descripcion) VALUES (:cuit, :id_empleado_sigarhu, :id_empleado_control_accesos, :fecha, :descripcion)
SQL;
		$sql_params	= [
			':cuit'							=> $cuit,
			':id_empleado_control_accesos'	=> $id_emp_ca,
			':id_empleado_sigarhu'			=> $id_emp_sig,
			':fecha'						=> (new \DateTime())->format('Y-m-d H:i:s'),
			':descripcion'					=> $descripcion,
		];
		$res = $cnx->consulta(Conexiones::INSERT, $sql,$sql_params);
		if(!empty($res)){
			return true;
		}
		return false;
	}

/**
 * Devuelve el ultimo registro logueado, caso de no existir los datos quedan en `null` salvo la fecha que se asigna el '1970-01-01'.
 *
 * @return object
 */
	static public function obtenerUltimoLog(){
		$cnx	= new Conexiones();
		$sql	= 'SELECT id, cuit, id_empleado_sigarhu, id_empleado_control_accesos, fecha FROM log_actualizaciones_sigarhu ORDER BY id DESC LIMIT 1';
		$res = $cnx->consulta(Conexiones::SELECT, $sql,[]);
		$return	= [
			'id'							=> null,
			'cuit'							=> null,
			'id_empleado_sigarhu'			=> null,
			'id_empleado_control_accesos'	=> null,
			'fecha'							=> \DateTime::createFromFormat('Y-m-d H:i:s', '1970-01-01 0:00:00'),
		];
		if(!empty($res[0])){
			$return	= [
				'id'							=> $res[0]['id'],
				'cuit'							=> $res[0]['cuit'],
				'id_empleado_sigarhu'			=> $res[0]['id_empleado_sigarhu'],
				'id_empleado_control_accesos'	=> $res[0]['id_empleado_control_accesos'],
				'fecha'							=> \DateTime::createFromFormat('Y-m-d H:i:s', $res[0]['fecha']),
				// 'fecha'							=> \DateTime::createFromFormat('Y-m-d H:i:s', '1970-01-01 0:00:00'),
			];
		}
		return (object)$return;
	}

	/**
	 * parseDateLog ---> aka parseDL para que sea mas corto
	 * Recibe un objeto DateTime, null o false y lo parsea a string. Usado para guardar en informacion en los logs.
	 * Este metodo es consecuencia de la migracion de Control de Accesos a la version 2.0.0 en la cual cambiaron varias formas de implementar fechas.
	 *
	 * @param DateTime|bool $date
	 * @return string|null
	 */
	private static function parseDL($date=null){
		$date	= ($date instanceof \DateTime) ? $date->format('Y-m-d') : $date;
		return empty($date) ? null : $date;
	}
}