<?php
namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Validador;
use FMT\Logger;
use FMT\Modelo;
use App\Modelo\Empleado;

class Direccion extends Modelo {
	/* Atributos */
	/** @var int */
	public $id;
	/** @var string */
	public $codep;
	/** @var int */
	public $nombre;
	/** @var int */
	public $id_padre;
	/** @var string*/
	public $fecha_desde;
	/** @var string*/
	public $fecha_hasta;
	
	public $visible;

    static private $ANULAR_VALIDACION  = false;

	static public function listar() {
		$mbd = new Conexiones;
		$str = "SELECT
                    id,
                    codep,
                    nombre,
                    id_padre,
                    fecha_desde, 
                    fecha_hasta
                FROM dependencias
                WHERE  (ISNULL(fecha_hasta) OR fecha_hasta >= DATE(NOW())) AND visible = 1
                ORDER BY nombre ASC";
		$res = $mbd->consulta(Conexiones::SELECT, $str);

        $aux    = [];
        if(!empty($res[0])){
            foreach ($res as $data) {
                $aux[$data['id']]   = $data;
            }
        }
		return $aux;
	}

	/**
	 * @param array $res
	 * @return Direccion
	 */
	public static function arrayToObject($res = []) {
        if(empty($res)){
            return new static();
        }
		$obj = new static();
	
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->codep = isset($res['codep']) ? $res['codep'] : null;
		$obj->nombre = isset($res['nombre']) ? $res['nombre'] : null;
		$obj->id_padre = isset($res['id_padre']) ? $res['id_padre'] : null;
		$obj->fecha_desde =  isset($res['fecha_desde']) ? $res['fecha_desde'] : null;
		$obj->fecha_hasta = isset($res['fecha_hasta']) ? $res['fecha_hasta'] : null;

		return $obj;
		}

		public function alta() {
            $res= false;
		if ($this->validar()) {
			$mbd = new Conexiones;
			$sql = "INSERT INTO dependencias (
                        codep,
                        nombre,
                        id_padre,
                        fecha_desde,
                        fecha_hasta,
                        visible
                    ) VALUES (
                        :codep,
                        :nombre,
                        :id_padre,
                        :fecha_desde,
                        :fecha_hasta,
                        1
                    )";
			$params = [
				':codep'  => $this->codep,
				':nombre' => $this->nombre,
				':id_padre'  => $this->id_padre,
				':fecha_desde' => $this->fecha_desde,
				':fecha_hasta' => $this->fecha_hasta
			];
			$resultado = $mbd->consulta(Conexiones::INSERT, $sql, $params);
			if (!empty($resultado) && is_numeric($resultado) && $resultado > 0) {
				$res= true;
				$this->id = (int)$resultado;
				$datos = (array)$this;
				$datos['modelo'] = 'direccion';
				Logger::event('alta', $datos);
			}else{
				$res= false;
				$datos['error_db'] = $mbd->errorInfo;
     			Logger::event("error_alta",$datos);
			}
		}

		return $res;
	}


	public function validar() {
        // if(static::$ANULAR_VALIDACION === true){
        //     static::$ANULAR_VALIDACION  = false;
        //     return true;
        // }
		// $reglas = [
		// 	'nombre' => ['required', 'max_length(150)', 'min_length(5)', 'char'],
		// 	'id_padre' => ['required', 'numeric'],
		// 	'codep'  => ['required', 'alpha_numeric', 'min_length(2)', 'max_length(10)'],
		// 	'fecha_desde' => [ 'required','fecha'],
		// 	'fecha_hasta' =>[ 'fecha', 'despuesDe(:fecha_desde)' => function($input, $param1) {
		// 			if (is_null($input)) {
		// 				return true;
		// 			}
		// 			if (Validador::fecha($input)) {
		// 				$input = strtotime($input);
		// 				if (!$param1 instanceof \DateTime) {
		// 					$param1 = strtotime($param1);
		// 				}
		// 				return $input >= $param1;
		// 			}
		// 			return false;
		// 		}]
		// ];
		// $nombres = [
		// 	'nombre' => 'Dependencia',
		// 	'codep'  => 'CODEP',
		// 	'id_padre' => 'Dependencia Padre',
		// 	'fecha_desde' => 'Fecha desde',
		// 	'fecha_hasta' => 'Fecha Hasta'
		// ];
		// $validator = Validador::validate((array)$this, $reglas, $nombres);
		// if ($validator->isSuccess()) {
		// 	return true;
		// }
		// $this->errores = $validator->getErrors();

		// return false;
	}

		public function modificacion() {
		$resul = false;	
		if ($valido = $this->validar()) {	
			$mbd = new Conexiones;
			$sql = "UPDATE dependencias
					SET
	                    codep  = :codep,
						nombre = :nombre,
						id_padre = :id_padre,
						fecha_desde = :fecha_desde,
						fecha_hasta = :fecha_hasta
					WHERE 
						id = :id";
			if ($valido || !empty($this->codep)) {
				$params = [
					':id'     => $this->id,
					':codep'  => $this->codep,
					':nombre' => $this->nombre,
					':id_padre' => $this->id_padre,
					':fecha_desde' => $this->fecha_desde,
					':fecha_hasta' => $this->fecha_hasta
				];
				$res = $mbd->consulta(Conexiones::UPDATE, $sql, $params);
				if (!empty($res)) {
					$resul= true;
					$datos = (array)$this;
					$datos['modelo'] = 'direccion';
					Logger::event('modificacion', $datos);
					return $res > 0;
				}else{
					$resul= false;
					$datos['error_db'] = $mbd->errorInfo;
	      			Logger::event("error_modificacion",$datos);
				}
			}
		}	
		return $resul;
	}

	public function baja() {
		$mbd = new Conexiones;
		$sql = "UPDATE dependencias
                SET 
                    fecha_hasta = :fecha_hasta
                WHERE id = :id";
		$params = [':id' => $this->id, ':fecha_hasta' => $this->fecha_hasta];
		$res = $mbd->consulta(Conexiones::UPDATE, $sql, $params);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			$resul= true;
			$datos = (array)$this;
			$datos['modelo'] = 'direccion';
			Logger::event('baja', $datos);
		}else{
			$resul=false;
			$datos['error_db'] = $mbd->errorInfo;
     		Logger::event("error_baja",$datos);
		}

		return $resul;
	}

	public static function obtener($id = null) {
		if (is_numeric($id)) {
			if ($id > 0) {
				$sql = "SELECT
                            id,
                            codep,
                            nombre,
                            id_padre,
                            fecha_desde, 
                  			fecha_hasta,
                            visible
                        FROM dependencias
                        WHERE id = :id";
				$params = [':id' => $id];
				$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, $params);
				if (!empty($res) && is_array($res) && isset($res[0])) {
					return static::arrayToObject($res[0]);
				} else {
                    return static::arrayToObject();
                }
			}
		}

        if(static::$ANULAR_VALIDACION === true){
            static::$ANULAR_VALIDACION  = false;
            return static::arrayToObject();
        }
		return null;
	}

	static public function listar_dependencias() {
		$mbd = new Conexiones;
	    $resultado = $mbd->consulta(Conexiones::SELECT,
	    "SELECT id, nombre FROM dependencias WHERE ISNULL(fecha_hasta)");
	    return $resultado;

	}

	static public function listar_dependencias_organigrama() {
		$mbd = new Conexiones;
	    $resultado = $mbd->consulta(Conexiones::SELECT,
	    "SELECT id, nombre FROM dependencias WHERE ISNULL(fecha_hasta) AND visible = 1");
	    return $resultado;

	}

	public static function obtenerAusentesPorDependencia() {
		$fecha = date("Y-m-d H:i:s");
    $dia = date("w", strtotime($fecha));
    $hora = date("H:i:s", strtotime($fecha));
    $empleados_por_dependencia_por_contrato = static::empleados_por_dependencia_por_contrato($dia, $hora);
    $verificar_fichadas_ahora = static::verificar_ingresos_por_fecha($fecha);

    foreach ($empleados_por_dependencia_por_contrato as $key => $value) {
      if(array_key_exists($key, $verificar_fichadas_ahora)){
      	$empleados_por_dependencia_por_contrato[$key]['lm']['ausentes'] = $value['lm']['totalEmpleados'] - $verificar_fichadas_ahora[$key]['LMFichados']['totalEmpleados'];
        $empleados_por_dependencia_por_contrato[$key]['at']['ausentes'] = $value['at']['totalEmpleados'] - $verificar_fichadas_ahora[$key]['ATFichados']['totalEmpleados'];
      } else {
				$empleados_por_dependencia_por_contrato[$key]['lm']['ausentes'] = $value['lm']['totalEmpleados'];
        $empleados_por_dependencia_por_contrato[$key]['at']['ausentes'] = $value['at']['totalEmpleados'];
			}
    }
    return $empleados_por_dependencia_por_contrato;
  }

  public static function empleados_por_dependencia_por_contrato($dia, $hora){

		  $sql = "SELECT
        emp_dep.id_dependencia_principal as idDep,
        emp.id as idEmpleado,
        ec.id_tipo_contrato,
        eh.horarios
        FROM empleados AS emp
        INNER JOIN empleado_dependencia_principal AS emp_dep ON emp_dep.id_empleado = emp.id
        LEFT JOIN empleado_contrato as ec ON (ec.id_empleado = emp.id AND ISNULL(ec.fecha_hasta) AND ec.borrado = 0)
        left join empleado_horarios as eh on emp.id = eh.id_empleado";

      $con = new Conexiones();
      $res = $con->consulta(Conexiones::SELECT, $sql);

      foreach ($res as $key => $value) {
          $nuevoResultado[$value['idDep']][] = ['idEmpleado' => $value['idEmpleado'], 'idContrato' => $value['id_tipo_contrato'], 'horarios' => $value['horarios'], 'horariosDecode' => json_decode($value['horarios'], true)];
      }
      ksort($nuevoResultado);

      SituacionRevista::setModalidadVinculacion(Empleado::SINEP);
      $sinep = SituacionRevista::listarParaSelect();
      SituacionRevista::setModalidadVinculacion(Empleado::PRESTACION_SERVICIOS);
      $_1109 = SituacionRevista::listarParaSelect();

      foreach ($nuevoResultado as $key => $value) {
          $contadorLM = 0;
          $contadorAT = 0;
          $contadorLMDeberianEstar = 0;
          $contadorATDeberianEstar = 0;
          $dep_lista[$key]['cantEmpleados'] = count($value);

          $contadorConHorario = 0;
          $contadorSinHorario = 0;
          foreach ($value as $k => $val) {

              if(in_array($val['idContrato'], $sinep)){
            //   if($val['idContrato'] == Empleado::LEY_MARCO || $val['idContrato'] == Empleado::PLANTA_PERMANENTE){
                $contadorLM++;
              }
              if(in_array($val['idContrato'], $_1109)){
            //   if($val['idContrato'] == Empleado::AT  || $val['idContrato'] == Empleado::PLANTA_TRANSITORIA){
                  $contadorAT++;
              }
              if($val['horarios'] == null){
                  $contadorSinHorario++;
              } else {
                  $contadorConHorario++;
              }
              if($val['horariosDecode'] != null){

                  if (array_key_exists($dia, $val['horariosDecode']) && !empty($val['horariosDecode'][$dia][0]) && (strtotime($val['horariosDecode'][$dia][0]) <= strtotime($hora) && strtotime($hora) <= strtotime($val['horariosDecode'][$dia][1])) ) {
                      if(in_array($val['idContrato'], $sinep)){
                    //   if($val['idContrato'] == Empleado::LEY_MARCO || $val['idContrato'] == Empleado::PLANTA_PERMANENTE){
                          $contadorLMDeberianEstar++;
                      }
                      if(in_array($val['idContrato'], $_1109)){
                    //   if($val['idContrato'] == Empleado::AT || $val['idContrato'] == Empleado::PLANTA_TRANSITORIA){
                          $contadorATDeberianEstar++;
                      }
                  }
              }
          }

          $dep_lista[$key] = [
                                'cantEmpleados' => count($value),
                      					'lm' => ['totalEmpleadosLm' => $contadorLM, 'totalEmpleados' => $contadorLMDeberianEstar],
                    						'at' => ['totalEmpleadosAt' => $contadorAT, 'totalEmpleados' => $contadorATDeberianEstar],
                        				'conHorarios' => $contadorConHorario,
                            		'sinHorarios' => $contadorSinHorario
                            ];
        }
      ksort($dep_lista);
      return $dep_lista;
    }

    public static function verificar_ingresos_por_fecha($fecha){
        SituacionRevista::setModalidadVinculacion(Empleado::SINEP);
      $sinep = SituacionRevista::listarParaSelect();
      SituacionRevista::setModalidadVinculacion(Empleado::PRESTACION_SERVICIOS);
      $_1109 = SituacionRevista::listarParaSelect();

			$preResultado = [];
			$resultado = [];
      $sql = "SELECT emp.id,
      	emp_dep.id_dependencia_principal as idDep,
      	eh.horarios,
      	ec.id_tipo_contrato,
      	a.hora_ingreso
      	FROM empleados as emp
        INNER JOIN empleado_dependencia_principal as emp_dep     ON emp_dep.id_empleado = emp.id
        left join empleado_horarios as eh on emp.id = eh.id_empleado
        LEFT JOIN empleado_contrato as ec ON (ec.id_empleado = emp.id AND ISNULL(ec.fecha_hasta) AND ec.borrado = 0)
        LEFT JOIN accesos_empleados ae ON ae.empleado_id = emp.id
        LEFT JOIN accesos a ON ae.id = a.tipo_id
        WHERE date_format(a.hora_ingreso, '%Y-%m-%d') = :fecha and  time(a.hora_ingreso) <= time(:hora) AND a.hora_egreso IS NULL
        ORDER BY idDep ASC, hora_ingreso ASC";

      $con = new Conexiones();
      $res = $con->consulta(Conexiones::SELECT, $sql, [':fecha' => date("Y-m-d", strtotime($fecha)), ':hora' => date("H:i:s", strtotime($fecha))]);
        foreach ($res as $key => $value) {

          $preResultado[$value['idDep']][] = [
																								'horarios' => $value['horarios'],
                                        				'idContrato' => $value['id_tipo_contrato'],
                                                'fichada' => $value['hora_ingreso'],
                                                'idEmpleado' => $value['id']
                                                ];
        }

        foreach ($preResultado as $key => $value) {
          $contadorFueraDeMuestra = 0;
          $contadorLM = 0;
          $contadorAT = 0;
          foreach ($value as $k => $v) {
              if($v['horarios'] == null){
                    $contadorFueraDeMuestra++;
              } else {
      $sinep = SituacionRevista::listarParaSelect();
                      if(in_array($v['idContrato'], $sinep)){
                    //   if($v['idContrato'] == Empleado::LEY_MARCO || $v['idContrato'] == Empleado::PLANTA_PERMANENTE){
                            $contadorLM++;
                      }
                      if(in_array($v['idContrato'], $_1109)){
                    //   if($v['idContrato'] == Empleado::AT || $v['idContrato'] == Empleado::PLANTA_TRANSITORIA){
                            $contadorAT++;
                      }
              }
          }
          $resultado[$key]['LMFichados'] = ['totalEmpleados' => $contadorLM];
          $resultado[$key]['ATFichados'] = ['totalEmpleados' => $contadorAT];
          $resultado[$key]['fueraMuestra'] = $contadorFueraDeMuestra;
        }

      return $resultado;
    }

    static public function anularValidacion(){
        static::$ANULAR_VALIDACION  = true;
    }
}
