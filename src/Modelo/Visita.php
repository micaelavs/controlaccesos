<?php
namespace App\Modelo;

use App\Helper\Biometrica;
use App\Helper\Conexion;
use App\Helper\Conexiones;
use App\Helper\Validador;
use DateTime;
use FMT\Logger;
use FMT\Modelo;

class Visita extends Modelo {
	/** @var integer */
	public $id = 0;
	/** @var integer */
	public $visita_id = 0;
    /** @var Persona */
	public $persona = null;
    /** @var Ubicacion*/
	public $ubicacion = null;
	public $id_autorizados = 0;
    /** @var Empleado*/
	public $autorizante = null;
	public $aclaracion_autorizacion = '';
	
	public $fecha_desde;
	public $fecha_hasta;

	const ENROLADO = 2;

	static public function obtener($id = null) {
		$sql = "SELECT
					p.nombre,
					p.apellido,
					p.documento,
					p.id as persona_id,
					v.ubicacion_id,
					v.autorizante_id,
					v.visita_id as id,
                    v.fecha_desde,
                    v.fecha_hasta,
                    v.aclaracion_autorizacion
          	FROM visitas AS v
          	INNER JOIN personas AS p ON v.persona_id= p.id
  
         	WHERE v.visita_id = :id AND p.borrado = 0";

		if (is_numeric($id)) {
			if ($id > 0) {
				$con = new Conexiones();
				$res = $con->consulta(Conexiones::SELECT, $sql, [':id' => $id]);
				
				if (is_array($res) && isset($res[0])) {
					return static::arrayToObject($res[0]);
				}
			}
		}

		return static::arrayToObject();
	}

	static public function obtener_id($id = null) {
		$resultado = [];
		$sql = "SELECT
					v.persona_id
					
          	FROM visitas AS v
          	
         	WHERE v.ubicacion_id = :id";

		
		$con = new Conexiones();
		$res = $con->consulta(Conexiones::SELECT, $sql, [':id' => $id]);	
        foreach ($res as $key) {
        	$resultado[] = $key['persona_id'];
        }
		return $resultado;
		
	}

	public function alta() {
            if (!empty($this->persona) && !empty($this->persona->id)) {// si lo dio de alta o lo modifico ok
                $conex = new Conexiones(); //preg si lo borro antes

                $sql = "SELECT visita_id FROM visitas WHERE persona_id = :id_persona AND ubicacion_id = :id_ubicacion AND fecha_desde = :fecha_desde AND fecha_hasta = :fecha_hasta AND borrado = 0";

                $existeComoVisita = $conex->consulta(Conexiones::SELECT, $sql, [
                    ':id_persona' => $this->persona->id,
                    ':id_ubicacion' => $this->ubicacion->id,
                    ':fecha_desde' => (!$this->fecha_desde instanceof DateTime)? DateTime::createFromFormat('d/m/Y', $this->fecha_desde)->format('Y-m-d') : $this->fecha_desde->format('Y-m-d'),
                    ':fecha_hasta' => (!$this->fecha_desde instanceof DateTime)? DateTime::createFromFormat('d/m/Y', $this->fecha_hasta)->format('Y-m-d') : $this->fecha_hasta->format('Y-m-d'),
                ]);

                if ($existeComoVisita != false) {
                    $this->errores = "La persona ingresada ya ha sido dada de alta como visita para la ubicacion elegida en ese período.";
                    return false;
                } else {
                    $sql = "INSERT INTO visitas(	
								ubicacion_id,
								autorizante_id,
								aclaracion_autorizacion,
								fecha_desde,
								fecha_hasta,
								persona_id
								) 
								VALUE (
								:ubicacion_id,
								:autorizante_id,
								:aclaracion_autorizacion,
								:fecha_desde,
								:fecha_hasta,
								:persona_id
								);";

                    $params = [
                        ':ubicacion_id' => $this->ubicacion->id,
                        ':autorizante_id' => $this->autorizante->id,
                        ':aclaracion_autorizacion' => $this->aclaracion_autorizacion,
                        ':fecha_desde' => (!$this->fecha_desde instanceof DateTime)? DateTime::createFromFormat('d/m/Y', $this->fecha_desde)->format('Y-m-d') : $this->fecha_desde->format('Y-m-d'),
                        ':fecha_hasta' => (!$this->fecha_desde instanceof DateTime)? DateTime::createFromFormat('d/m/Y', $this->fecha_hasta)->format('Y-m-d') : $this->fecha_hasta->format('Y-m-d'),
                        ':persona_id' => $this->persona->id

					];

					$id_visita = $conex->consulta(Conexiones::INSERT, $sql, $params);

					if ($id_visita !== false) {
						$this->id = (int)$id_visita;
						$datos['modelo'] = 'visita';
						Logger::event('alta', $datos);
						return true;

                    } else {
                        $datos = (array)$this;
                        $datos['modelo'] = 'visita';
                        $datos['error_db'] = $conex->errorInfo;
                        Logger::event('error_alta', $datos);
                        $this->errores = $conex->errorInfo;
                        return false;
                    }

                }
            }

		return false;
	}

	public function baja() {
		$conex = new Conexiones();
		$params = [':id' => $this->id];
		$sql = "UPDATE visitas SET borrado = 1 WHERE visita_id = :id;";
		$res = $conex->consulta(Conexiones::UPDATE, $sql, $params);
		if (!empty($res) && is_numeric($res)) {
			//Log
			$datos = (array)$this;
			$datos['Modelo'] = 'visita';
			Logger::event('baja', $datos);

			return $res > 0;
		}

		return false;
		
	}

	public function modificacion() {//

				if (!empty($this->persona) && !empty($this->persona->id)) {
					$sql = "UPDATE visitas SET	
								ubicacion_id = :ubicacion_id,
								autorizante_id = :autorizante_id,
								aclaracion_autorizacion = :aclaracion_autorizacion,
								fecha_desde = 	:fecha_desde,
								fecha_hasta = :fecha_hasta,
								persona_id = :persona_id
							WHERE visita_id = :visita_id
								";

					$params = [
						':ubicacion_id' => $this->ubicacion->id,
						':autorizante_id' => $this->autorizante->id,
						':aclaracion_autorizacion' => $this->aclaracion_autorizacion,
						':fecha_desde' => \DateTime::createFromFormat('d/m/Y', $this->fecha_desde)->format('Y-m-d'),
						':fecha_hasta' => \DateTime::createFromFormat('d/m/Y', $this->fecha_hasta)->format('Y-m-d'),
						':persona_id'  => $this->persona->id,
						':visita_id'  => $this->id

					];
					$conex = new Conexiones();
					$res = $conex->consulta(Conexiones::UPDATE, $sql,$params);

					$datos = (array)$this;
					$datos['modelo'] = 'visita';

					if (is_numeric($res) && $res >= 0) {
						//Log
						Logger::event('modificacion', $datos);

						return true;
					} else {
						$datos['error_db'] = $conex->errorInfo; 
						Logger::event('modificacion', $datos);
					}
			}
		return false;
    }

	public static function esAutorizante($empleado, $ubicacion_id) {
		if ($empleado->ubicacion_principal->id === $ubicacion_id) {
			return true;
		}

		return false;
	}

	public static function ajax($params=array(),$estado) {
		$sql_params = [];
		$where = '';
		
		$campos	= 'persona_id,documento,nombre,apellido,ubicaciones_autorizadas,fecha_desde,fecha_hasta,aut_id,aut_nombre_apellido,id,descripcion,enrolado';
		$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo']) ) ? 'tipo' :$params['order']['campo']; 
		$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']) )   ? 'asc' :$params['order']['dir'];
		$params['start']  = (!isset($params['start'])  || empty($params['start']) )  ? 0 :$params['start'];
		$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght']) ) ? 10 :$params['lenght'];
		$params['search'] = (!isset($params['search']) || empty($params['search']) ) ? '' :$params['search'];
		$params['filtros'] = (!isset($params['filtros']) || empty($params['filtros']) ) ? null :$params['filtros'];

		if (!empty($params['filtros']['dependencia_autorizada'])) {
			$extra_where[] = "ub.id IN(:dependencia_autorizada)";
			$sql_params[':dependencia_autorizada'] = $params['filtros']['dependencia_autorizada'];
		}
		if (!empty($params['filtros']['enrolado']) && $params['filtros']['enrolado'] == self::ENROLADO) {
			$extra_where[] = "p.id IN (SELECT persona_id FROM templates)";
		}

		if (!empty($params['filtros']['enrolado']) && $params['filtros']['enrolado'] != self::ENROLADO) {
			$extra_where[] = "p.id NOT IN (SELECT persona_id FROM templates)";
		}

		if ($estado == 0) {
			$extra_where[] =  ' (v.borrado = 1 OR fecha_hasta < "'.date('Y-m-d').'") ';
		}else{
			$extra_where[] =  ' v.borrado = 0 AND (fecha_hasta >= "'.date('Y-m-d').'"  OR fecha_hasta IS NULL) ';
		}
		
		if (!empty($extra_where)) {
			$where = ' AND ';
			$where .= implode(' AND ', $extra_where);
		}

		$consulta = <<<SQL
			SELECT
			p.id as persona_id, 
			p.documento, 
			p.nombre, 
			p.apellido, 
			ub.nombre as ubicaciones_autorizadas,
			DATE_FORMAT(v.fecha_desde, '%d/%m/%Y %H:%i') as fecha_desde, 
			DATE_FORMAT(v.fecha_hasta, '%d/%m/%Y %H:%i') as fecha_hasta, 
			E.persona_id as aut_id, 
			CONCAT( ppp.nombre,' ', ppp.apellido) as aut_nombre_apellido,
			v.visita_id as id,
			v.aclaracion_autorizacion as descripcion, 
			if (isnull(t.data),false,true) AS enrolado
			FROM visitas AS v
			INNER JOIN personas AS p ON v.persona_id= p.id
			inner join empleados E on E.id = v.autorizante_id
			INNER JOIN personas AS ppp ON E.persona_id = ppp.id
			INNER JOIN ubicaciones AS ub ON ub.id = v.ubicacion_id
			LEFT JOIN (select te.persona_id,te.data from templates te  group by te.persona_id) as t ON t.persona_id = p.id
			WHERE p.borrado = 0 AND ub.borrado = 0 $where
SQL;
		$data = \App\Modelo\Modelo::listadoAjax($campos, $consulta, $params, $sql_params,['{{{REPLACE}}}'=>'*']);
		return $data;

	}

	public static function estaEnrolado($persona_id) {
		$conex = new Conexiones();
		$sql = "SELECT count(*) AS cant FROM templates WHERE persona_id = :persona_id";
		$res = $conex->consulta(Conexiones::SELECT, $sql, [':persona_id' => $persona_id]);
		return ($res[0]['cant'] > 0) ? true : false;
	}


	public static function arrayToObject($res = []) {
		$obj = new self();
		
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->persona = Persona::obtener(isset($res['persona_id']) ? (int)$res['persona_id'] : 0);
		$obj->autorizante =  Empleado::obtener(isset($res['autorizante_id']) ? (int)$res['autorizante_id'] : 0);
		$obj->ubicacion =  Ubicacion::obtener(isset($res['ubicacion_id']) ? (int)$res['ubicacion_id'] : 0);

		$obj->aclaracion_autorizacion = isset($res['aclaracion_autorizacion']) ? $res['aclaracion_autorizacion'] : null; 
		$obj->fecha_desde = isset($res['fecha_desde']) ? $res['fecha_desde'] : null;
		$obj->fecha_hasta = isset($res['fecha_hasta']) ? $res['fecha_hasta'] : null;
		

		return $obj;
	}
	
	public function validar() {
		$inputs = [
			'documento'   => $this->persona->documento,
			'ubicacion' => isset($this->ubicacion)? $this->ubicacion->id : $this->ubicacion,
			'fecha_desde' => (!$this->fecha_desde instanceof DateTime)? DateTime::createFromFormat('d/m/Y', $this->fecha_desde) : $this->fecha_desde,
            'fecha_hasta' => (!$this->fecha_desde instanceof DateTime) ? DateTime::createFromFormat('d/m/Y', $this->fecha_hasta) : $this->fecha_hasta,
            'autorizante' => $this->autorizante,
		];

		$self = $this;

		$rules = [
			'documento'    => ['required','documento','no_es_empleado_activo' => function() use ($self) {
				$empleado = Empleado::obtenerPorDocumento($self->persona->documento);
		        if ( $empleado->id_tipo_contrato != SituacionRevista::SIN_CONTRATO){
                    return false;
                }
                return true;
            },'existe_persona' => function() use ($self) {
				$persona = Persona::obtenerPorDocumento($self->persona->documento);
		        if ( $persona->id == null){
                    return false;
                }
                return true;
            }],
			'autorizante'    => ['required', 'es_autorizante' => function() use ($self) {
		        if (!empty($self->autorizante) && !empty($self->autorizante->id)){
                    return true;
                }
                return false;
            }],
			'ubicacion' => ['required'],
			'fecha_desde'	=> ['required', 'fecha', 'fecha_posterior_al_registro_previo' => function($input) use($self) {

				$conex = new Conexiones();
				$sql = "SELECT visita_id, fecha_desde, fecha_hasta FROM visitas WHERE persona_id = :id_persona AND ubicacion_id = :id_ubicacion AND borrado = 0 AND visita_id != :id ORDER BY fecha_hasta DESC LIMIT 1";
				$existeComoVisita = $conex->consulta(Conexiones::SELECT, $sql, [
					':id_persona' => $self->persona->id,
					':id_ubicacion' => $self->ubicacion->id,
					':id' => $self->id
				]);
				if(!empty($existeComoVisita) ){
					$fechaHasta = \DateTime::createFromFormat("Y-m-d", $existeComoVisita[0]['fecha_hasta']);
					return $input > $fechaHasta;
				}
				return true;
			}],
			'fecha_hasta'	=> ['required', 'fecha_mayor(:fecha_desde)' => function($input,$param1){

				$rta = true;
				if (!is_null($input)) {
					$rta =  ($input >= $param1) ? true : false; //input es fecha hasta //param fecha desde
				}
					return $rta;
			  }],
		];

		$naming = [
			'documento'    => 'Documento',
			'ubicacion'  => "Ubicacion Autorizada",
			'fecha_desde'  => "Fecha Desde Ingreso",
			'fecha_hasta'  => "Fecha Hasta Salida"
		];

		$validator = Validador::validate($inputs, $rules, $naming);
		$validator->customErrors([
			"fecha_menor"  => "Campo <b> :attribute </b> no puede ser menor a la Fecha Desde.",
            "no_es_empleado_activo" => "La persona ingresada es un empleado activo y no puede darse de alta como visita enrolada.",
			'fecha_posterior_al_registro_previo' => "La persona ingresada es una visita enrolada <strong> activa </strong> para la ubicación elegida y la  <b> :attribute </b> seleccionada.",
            "es_autorizante" => "La persona Autorizante no existe.",
            "fecha_mayor" => "La fecha hasta debe ser mayor a la fecha desde.",
            "existe_persona" => "No pudimos dar de alta a la Persona, complete los campos obligatorios.",
		]);

		if ($validator->isSuccess() == true) {
			return true;
		}
		$this->errores = $validator->getErrors();

		return false;
	}

	/** ========================
	 *	COLOCAR EN EL CONTROLADOR
	 * ========================= 
	 */
	/*protected function accion_enrolar() {
		$this->datos();

		if (!empty($this->empleado) && !empty($this->empleado->id)) {
			$empleado = $this->empleado;
			$estaEnrolado=Empleado::estaEnrolado($this->empleado->persona->id);
			Vista::crear('abm.empleados.enrolar', compact('empleado','estaEnrolado'));
		} else {
			Msj::setErr('Se requieren <strong>datos válidos</strong> de un empleado ' .
				'para ejecutar la acción solicitada.');
		}
		$this->mantener($this->empleado);
		$this->redirigir(['c' => 'empleados', 'a' => 'index']);
	}*/

		public static function empleados_por_ubicacion($id_ubicacion){

	    $conexion = new Conexiones();

	    $sql = "
		    SELECT emp.id, CONCAT(p.nombre,', ',p.apellido) AS nombre
		    FROM empleados emp
		    INNER JOIN personas p ON emp.persona_id = p.id
		    INNER JOIN empleados_x_ubicacion eu ON eu.empleado_id = emp.id
		    INNER JOIN empleado_contrato ec on ec.id_empleado = emp.id 
		    WHERE eu.ubicacion_id  = :ubicacion AND eu.principal = 1 AND emp.borrado = 0 AND (ISNULL (ec.fecha_hasta) OR ec.fecha_hasta > NOW())
		    ORDER BY p.nombre ASC";
	    $param = [':ubicacion' => $id_ubicacion];

	    return $conexion->consulta(Conexiones::SELECT,$sql,$param);
	}

    public static function obtenerPorDocumento($documento , $id_ubicacion = 0) {
        if (!empty($documento)) {
        	$sqlUbicacion =" AND v.ubicacion_id = " . $id_ubicacion . " ORDER BY visita_id DESC";
            $sql = "SELECT v.visita_id
					FROM visitas AS v
					JOIN personas AS p ON v.persona_id = p.id
					WHERE v.borrado = 0  AND p.borrado = 0  AND p.documento = :documento";
			if($id_ubicacion != 0){
                 $sql = $sql . $sqlUbicacion ;
			} 		
            $conex = new Conexiones();
            $res = $conex->consulta(Conexiones::SELECT, $sql, [':documento' => $documento]);
            if (!empty($res) && is_array($res) && count($res) > 0) {
                $visita = static::obtener($res[0]['visita_id']);
                if (!empty($visita)) {
                    return $visita;
                }
            }
            $visita = static::obtener(0);
            $visita->persona->documento = $documento;

            return $visita;
        }

        return new static;
    }

    public function puedeAcceder($ubicacion_id) {
        if(empty($this->fecha_hasta)) {
            return false;
        }
         elseif (new \DateTime($this->fecha_hasta) >= new \DateTime()) {
            if ($ubicacion_id == $this->ubicacion->id) {
                return true;
            }
        }
        return false;
    }


}