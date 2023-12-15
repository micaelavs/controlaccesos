<?php

namespace App\Modelo;

use FMT\Logger;
use App\Helper\Validator;
use App\Helper\Conexiones;
use App\Helper\Validador;

class Novedad extends Modelo {

const TIPO_COMISION_HORARIA = '43';	
	
public $id;

public $tipo_novedad;
/** @var DateTime */
public $fecha_desde;
/** @var DateTime */
public $fecha_hasta;

public $usuario;
/*** @var Empleado */
public $empleado;

public $hora_desde;

public $hora_hasta;
/*** @var Persona */
public $persona;
/*** @var string */
public $fecha_carga;

	public static function tipoAusencias($id=null) {
		static $TIPO_NOVEDADES	= null;
		if($TIPO_NOVEDADES	=== null) {
			$sql = "SELECT id, nombre FROM tipo_novedades";
			$TIPO_NOVEDADES	= (new Conexiones)->consulta(Conexiones::SELECT, $sql);
		}
		if(!empty($id)){
			foreach ($TIPO_NOVEDADES as $novedad) {
				if($novedad['id'] == $id)
					return $novedad['nombre'];
			}
		}
		return (array)$TIPO_NOVEDADES;
	}

	static public function obtener($id = null) {
		if (is_numeric($id) && $id > 0) {
				$sql = "SELECT nov.id, per.id id_persona, per.documento, per.nombre, id_empleado, per.apellido,id_tipo_novedad, fecha_desde,
						fecha_hasta, fecha_carga
						FROM novedades nov
						INNER JOIN empleados emp
						ON emp.id = nov.id_empleado
						INNER JOIN personas per
						ON per.id = emp.persona_id
						WHERE per.borrado = 0 AND emp.borrado = 0
						AND nov.id = :id;";
				$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, [':id' => $id]);
				if (!empty($res) && is_array($res) && isset($res[0])) {
					return static::arrayToObject($res[0]);
				}
		
			}else{
				return static::arrayToObject();

			}
	}

	/**
	 * @param array $res
	 * @return novedad
	 */
	public static function arrayToObject($res = []) {
		$obj = new static;
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->empleado = Empleado::obtener(isset($res['id_empleado']) ? $res['id_empleado'] : 0);
		$obj->tipo_novedad = isset($res['id_tipo_novedad']) ? $res['id_tipo_novedad'] : null;
		$obj->fecha_desde = isset($res['fecha_desde']) ? \DateTime::createFromFormat('Y-m-d H:i:s',$res['fecha_desde']) : false;
		$obj->fecha_hasta = isset($res['fecha_hasta']) ? \DateTime::createFromFormat('Y-m-d H:i:s',$res['fecha_hasta']) : false;
		$obj->fecha_carga = isset($res['fecha_carga']) ? \DateTime::createFromFormat('Y-m-d H:i:s',$res['fecha_carga']) : false;
		return $obj;
	}

	public static function listar_Novedades($params = array())
    {
        $campos    = 'id, documento, nombre, tipo_novedad, fecha_desde, fecha_hasta';
        $sql_params = [];
        $where = [];

        $condicion = "AND per.borrado = 0 AND emp.borrado = 0 AND nov.borrado = 0";

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
                'tipo_novedad'      => null,
                'fecha_desde'       => null,
                'fecha_hasta'       => null

            ]
        ];

        $params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
        $params = array_merge($default_params, $params);

        /*Filtros */
        if(!empty($params['filtros']['tipo_novedad'])){ 
            $where [] = "nov.id_tipo_novedad = :tipo_novedad";
            $sql_params[':tipo_novedad']  = $params['filtros']['tipo_novedad'];

        }
    
        if(!empty($params['filtros']['fecha_desde'])){
            $where [] = "nov.fecha_desde >= :fecha_desde";
            $fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_desde'])->format('Y-m-d');
            $sql_params[':fecha_desde']    = $fecha;

        }

        if(!empty($params['filtros']['fecha_hasta'])){
            $where [] = "nov.fecha_hasta <= :fecha_hasta";
            $fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_hasta'])->format('Y-m-d');
            $sql_params[':fecha_hasta']    = $fecha;

        }

		if(!empty($params['filtros']['dependencias'])){
            $where [] = "emp_dep.id_dependencia_principal IN (".implode(",",$params['filtros']['dependencias']).")";
		}

        $condicion .= !empty($where) ? ' WHERE ' . \implode(' AND ',$where) : '';

        if(!empty($params['search'])){
            $indice = 0;
            $search[]   = <<<SQL
            (per.documento LIKE :search{$indice} OR CONCAT(per.nombre, ' ' , per.apellido) LIKE :search{$indice} OR nov.fecha_desde like :search{$indice} OR nov.fecha_hasta like :search{$indice} OR tn.nombre like :search{$indice} ) 
SQL;
            $texto = $params['search'];
            $sql_params[":search{$indice}"] = "%{$texto}%";

            $buscar =  implode(' AND ', $search);
            $condicion .= empty($condicion) ? "{$buscar}" : " AND {$buscar} ";

        }


        $consulta = <<<SQL
     	
     	SELECT 	nov.id AS id, per.documento AS documento,
				CONCAT(per.nombre, ' ' , per.apellido) AS nombre, tn.nombre AS tipo_novedad, nov.fecha_desde, nov.fecha_hasta
        FROM novedades AS nov
		INNER JOIN empleados AS emp
		ON emp.id = nov.id_empleado
		LEFT JOIN empleado_contrato AS emp_cont
		ON (emp_cont.id_empleado = emp.id AND ISNULL(emp_cont.fecha_hasta) AND emp_cont.borrado = 0)
		LEFT JOIN empleado_dependencia_principal AS emp_dep
		ON (emp_dep.id_empleado = emp.id AND ISNULL(emp_dep.fecha_hasta) AND emp_dep.borrado = 0)
		INNER JOIN personas AS per
		ON per.id = emp.persona_id
		INNER JOIN tipo_novedades AS tn
		ON tn.id = nov.id_tipo_novedad
       	$condicion
      
SQL;

        $data = self::listadoAjax($campos, $consulta, $params, $sql_params);
        return $data;
    }

    public static function listar_novedades_excel($params){
        $cnx    = new Conexiones();
        $sql_params = [];
        $where = [];
        $condicion = '';
        $order = '';
        $search = [];

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
                'tipo_novedad'      => null,
                'fecha_desde'       => null,
                'fecha_hasta'       => null

            ],
            'count'     => false
        ];

        $params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
        $params = array_merge($default_params, $params);

        $sql= <<<SQL
      	SELECT nov.id AS id, date_format(nov.fecha_desde, '%d/%m/%Y') AS fecha_desde, date_format(nov.fecha_hasta, '%d/%m/%Y') AS fecha_hasta, per.documento AS documento, CONCAT(per.nombre, ' ' , per.apellido) AS nombre, tn.nombre AS tipo_novedad
SQL;


    $from = <<<SQL
        FROM novedades AS nov
		INNER JOIN empleados AS emp
		ON emp.id = nov.id_empleado
		LEFT JOIN empleado_contrato AS emp_cont
		ON (emp_cont.id_empleado = emp.id AND ISNULL(emp_cont.fecha_hasta) AND emp_cont.borrado = 0)
		LEFT JOIN empleado_dependencia_principal AS emp_dep
		ON (emp_dep.id_empleado = emp.id AND ISNULL(emp_dep.fecha_hasta) AND emp_dep.borrado = 0)
		INNER JOIN personas AS per
		ON per.id = emp.persona_id
		INNER JOIN tipo_novedades AS tn
		ON tn.id = nov.id_tipo_novedad
SQL;


    $condicion = <<<SQL
        WHERE per.borrado = 0 AND emp.borrado = 0 AND nov.borrado = 0
        
SQL;

    /**Filtros*/
    if(!empty($params['filtros']['tipo_novedad'])){
        $condicion .= " AND nov.id_tipo_novedad = :tipo_novedad";
        $sql_params[':tipo_novedad']    = $params['filtros']['tipo_novedad'];
    }

    if(!empty($params['filtros']['fecha_desde'])){
        $fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_desde'])->format('Y-m-d');
        $condicion .=  " AND nov.fecha_desde >= :fecha_desde";
        $sql_params[':fecha_desde']   = $fecha;
    }

     if(!empty($params['filtros']['fecha_hasta'])){
        $fecha = \DateTime::createFromFormat('d/m/Y', $params['filtros']['fecha_hasta'])->format('Y-m-d');
        $condicion .=  " AND nov.fecha_hasta <= :fecha_hasta";
        $sql_params[':fecha_hasta']   = $fecha;
    }


   
    $counter_query  = "SELECT COUNT(nov.id) AS total {$from}";

    $recordsTotal   =  $cnx->consulta(Conexiones::SELECT, $counter_query . $condicion, $sql_params )[0]['total'];

        //Los campos que admiten en el search (buscar) para concatenar al filtrado de la consulta
        if(!empty($params['search'])){
            $indice = 0;
            $search[]   = <<<SQL
            (per.documento LIKE :search{$indice} OR CONCAT(per.nombre, ' ' , per.apellido) LIKE :search{$indice} OR nov.fecha_desde like :search{$indice} OR nov.fecha_hasta like :search{$indice} OR tn.nombre like :search{$indice}) 
SQL;
            $texto = $params['search'];
            $sql_params[":search{$indice}"] = "%{$texto}%";

            $buscar =  implode(' AND ', $search);
            $condicion .= empty($condicion) ? "{$buscar}" : " AND {$buscar} ";
        
        }

        /**Orden de las columnas */
        $orderna = [];
        foreach ($params['order'] as $i => $val) {
            $orderna[]  = "{$val['campo']} {$val['dir']}";
        }

        $order .= implode(',', $orderna);

        $limit = (isset($params['lenght']) && isset($params['start']) && $params['lenght'] != '')
            ? " LIMIT  {$params['start']}, {$params['lenght']}" : ' ';

        $recordsFiltered= $cnx->consulta(Conexiones::SELECT, $counter_query.$condicion, $sql_params)[0]['total'];

       	$order .= (($order =='') ? '' : ', ').'nov.fecha_desde desc';

        $order = ' ORDER BY '.$order;

        $lista = $cnx->consulta(Conexiones::SELECT,  $sql .$from.$condicion.$order.$limit,$sql_params);


        return ($lista) ? $lista : [];
    }

	static public function listar() {
	}

	public static function listar_ultimas_siete($params = array())
    {
        $campos    = 'id, fecha_desde, fecha_hasta, tipo_novedad';
        $sql_params = [];
        $where = [];

        $condicion = "AND per.borrado = 0 AND emp.borrado = 0 AND nov.borrado = 0";

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
                'dni'      => null,
                'dni_hidden' => null
            ]
        ];

        $params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
        $params = array_merge($default_params, $params);

        /*Filtros */
        if(!empty($params['filtros']['dni'])){ 
            $where [] = "per.documento = :dni";
            $sql_params[':dni']  = $params['filtros']['dni'];

        }

         if(!empty($params['filtros']['dni_hidden'])){ 
            $where [] = "per.documento = :dni_hidden";
            $sql_params[':dni_hidden']  = $params['filtros']['dni_hidden'];

        }
    
        $condicion .= !empty($where) ? ' WHERE ' . \implode(' AND ',$where) : '';

        if(!empty($params['search'])){
            $indice = 0;
            $search[]   = <<<SQL
            (nov.fecha_desde like :search{$indice} OR nov.fecha_hasta like :search{$indice} OR tn.nombre like :search{$indice} ) 
SQL;
            $texto = $params['search'];
            $sql_params[":search{$indice}"] = "%{$texto}%";

            $buscar =  implode(' AND ', $search);
            $condicion .= empty($condicion) ? "{$buscar}" : " AND {$buscar} ";

        }

        $consulta = <<<SQL
     	
     	SELECT 	nov.id AS id, nov.fecha_desde, nov.fecha_hasta, tn.nombre AS tipo_novedad
        FROM novedades AS nov
		INNER JOIN empleados AS emp
		ON emp.id = nov.id_empleado
		LEFT JOIN empleado_contrato AS emp_cont
		ON (emp_cont.id_empleado = emp.id AND ISNULL(emp_cont.fecha_hasta) AND emp_cont.borrado = 0)
		LEFT JOIN empleado_dependencia_principal AS emp_dep
		ON (emp_dep.id_empleado = emp.id AND ISNULL(emp_dep.fecha_hasta) AND emp_dep.borrado = 0)
		INNER JOIN personas AS per
		ON per.id = emp.persona_id
		INNER JOIN tipo_novedades AS tn
		ON tn.id = nov.id_tipo_novedad AND (
					(CURDATE() - INTERVAL 7 DAY BETWEEN nov.fecha_desde and nov.fecha_hasta or CURDATE() BETWEEN nov.fecha_desde and nov.fecha_hasta) or 
					(nov.fecha_desde BETWEEN CURDATE() - INTERVAL 7 DAY and CURDATE() or nov.fecha_hasta BETWEEN CURDATE() - INTERVAL 7 DAY and CURDATE())
				)
       	$condicion
       	ORDER BY nov.fecha_desde DESC, tn.nombre
      
SQL;
	

        
        $data = self::listadoAjax($campos, $consulta, $params, $sql_params);
        return $data;
    }

    public static function listar_ultimas_siete_excel($params){
        $cnx    = new Conexiones();
        $sql_params = [];
        $where = [];
        $condicion = '';
        $order = '';
        $search = [];

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
                'dni'      => null,
                'dni_hidden' => null
               
            ],
            'count'     => false
        ];

        $params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
        $params = array_merge($default_params, $params);

        $sql= <<<SQL
      	SELECT nov.id AS id, date_format(nov.fecha_desde, '%d/%m/%Y') AS fecha_desde,  date_format(nov.fecha_hasta, '%d/%m/%Y') AS fecha_hasta, tn.nombre AS tipo_novedad
SQL;


    $from = <<<SQL
        FROM novedades AS nov
		INNER JOIN empleados AS emp
		ON emp.id = nov.id_empleado
		LEFT JOIN empleado_contrato AS emp_cont
		ON (emp_cont.id_empleado = emp.id AND ISNULL(emp_cont.fecha_hasta) AND emp_cont.borrado = 0)
		LEFT JOIN empleado_dependencia_principal AS emp_dep
		ON (emp_dep.id_empleado = emp.id AND ISNULL(emp_dep.fecha_hasta) AND emp_dep.borrado = 0)
		INNER JOIN personas AS per
		ON per.id = emp.persona_id
		INNER JOIN tipo_novedades AS tn
		ON tn.id = nov.id_tipo_novedad AND (
					(CURDATE() - INTERVAL 7 DAY BETWEEN nov.fecha_desde and nov.fecha_hasta or CURDATE() BETWEEN nov.fecha_desde and nov.fecha_hasta) or 
					(nov.fecha_desde BETWEEN CURDATE() - INTERVAL 7 DAY and CURDATE() or nov.fecha_hasta BETWEEN CURDATE() - INTERVAL 7 DAY and CURDATE())
					)
SQL;


    $condicion = <<<SQL
        WHERE per.borrado = 0 AND emp.borrado = 0 AND nov.borrado = 0
        
SQL;

    /**Filtros*/
    if(!empty($params['filtros']['dni'])){
        $condicion .= " AND per.documento = :dni";
        $sql_params[':dni']    = $params['filtros']['dni'];
    }

    if(!empty($params['filtros']['dni_hidden'])){
        $condicion .= " AND per.documento = :dni_hidden";
        $sql_params[':dni_hidden']    = $params['filtros']['dni_hidden'];
    }

    $counter_query  = "SELECT COUNT(nov.id) AS total {$from}";

    $recordsTotal   =  $cnx->consulta(Conexiones::SELECT, $counter_query . $condicion, $sql_params )[0]['total'];

        //Los campos que admiten en el search (buscar) para concatenar al filtrado de la consulta
        if(!empty($params['search'])){
            $indice = 0;
            $search[]   = <<<SQL
            (nov.fecha_desde like :search{$indice} OR nov.fecha_hasta like :search{$indice} OR tn.nombre like :search{$indice}) 
SQL;
            $texto = $params['search'];
            $sql_params[":search{$indice}"] = "%{$texto}%";

            $buscar =  implode(' AND ', $search);
            $condicion .= empty($condicion) ? "{$buscar}" : " AND {$buscar} ";
        
        }

        /**Orden de las columnas */
        $orderna = [];
        foreach ($params['order'] as $i => $val) {
            $orderna[]  = "{$val['campo']} {$val['dir']}";
        }

        $order .= implode(',', $orderna);

        $limit = (isset($params['lenght']) && isset($params['start']) && $params['lenght'] != '')
            ? " LIMIT  {$params['start']}, {$params['lenght']}" : ' ';

        $recordsFiltered= $cnx->consulta(Conexiones::SELECT, $counter_query.$condicion, $sql_params)[0]['total'];

       	$order .= (($order =='') ? '' : ', ').'nov.fecha_desde desc';

        $order = ' ORDER BY '.$order;

        $lista = $cnx->consulta(Conexiones::SELECT,  $sql .$from.$condicion.$order.$limit,$sql_params);


        return ($lista) ? $lista : [];
    }

	public function validar() {
		$tipo_contrato	= \App\Modelo\SituacionRevista::listarParaSelect();
		$usuario = Usuario::obtenerUsuarioLogueado();
		$reglas = [
			'fecha_desde' => ['required','fecha','iguales(:fecha_hasta,:tipo_novedad)' => function($input, $param1,$param2){
					$return = true;
					if (self::TIPO_COMISION_HORARIA == $param2 ){
						if(!is_null($input) && !is_null($param1)) {
							$return = ($input->format('Y-m-d') == $param1->format('Y-m-d')) ? true : false;
						}
						return $return;		
					}else{
						return true;
					}
			},],
			'fecha_hasta' => ['required','fecha','despuesDeDesde(:tipo_novedad,:fecha_desde)' =>function($input,$param1, $param2){
                //validación para distinto de comisión horaria válida las fechas solamente que una sea posterior a la otra
                if (self::TIPO_COMISION_HORARIA != $param1){ 
                    if(!($input instanceof \DateTime && !empty($input)) && ($param2 instanceof \DateTime && !empty($param2))){
                        return false;
                    }

                    if($input < $param2){
                        return false;
                    
                    }

                    return true;
                }

                return true;
            }],
			'hora_inicio' => [
					'required',
					'hora_valida(:tipo_novedad)' => function ($input,$param1) {
						if (self::TIPO_COMISION_HORARIA == $param1 ){
							return (strcmp($input,'00:00')!=0);
						}else{
							return true;
						}
						 
					}, 
			],	
			'hora_fin' => [
				'required',
				'hora_valida(:tipo_novedad)' => function ($input,$param1) {
					if (self::TIPO_COMISION_HORARIA == $param1 ){
						return (strcmp($input,'00:00')!=0);
					}else{
						return TRUE;
					}
						 
				},'despuesDeDesdeHora(:tipo_novedad,:fecha_desde,:fecha_hasta)' =>function($input,$param1, $param2, $param3){
                    //validación para la hora
                    if(strcmp($input,'00:00')!=0){
                        if (self::TIPO_COMISION_HORARIA == $param1){
                                if(!($param2 instanceof \DateTime && !empty($param2)) && ($param3 instanceof \DateTime && !empty($param3))){
                                    return false;
                                }
                               
                                if($param3 < $param2){
                                    return false;
                                }
                             
                        }

                        return true;
                    }  

                    return true;
                } 
			],	
				
			'empleado' => [
				'empleado_valido' => function($val){
				return ( $val instanceof Empleado && $val->id > 0) ? true : false;

			},'validacion_tipo_contrato(:empleado)' => function ($input,$param1) {
                \App\Modelo\SituacionRevista::setAutenticacion();
				$rta =(array_key_exists($param1->id_tipo_contrato, \App\Modelo\SituacionRevista::listarParaSelect())) ? true : false;
				return $rta;

			},'validacion_rca(:usuario)' => function ($input,$param1) {
				
				if(!empty($this->usuario->dependencias)){
					$resp = in_array($input->dependencia_principal, $param1->dependencias);
				} else {
					$resp = true;
				}
				return $resp;
			}],
			'tipo_novedad' => ["required",'integer','check_repeat(:empleado,:fecha_desde,:fecha_hasta,:id)' => function($input, $param1, $param2,$param3,$param4){
				if ($input !='' && $param1 != '' && $param2 != '') {
					$sql = "SELECT * FROM novedades WHERE id_tipo_novedad= :novedad AND borrado = 0 AND id_empleado = :empleado  
					AND DATE(fecha_desde) = :fecha_desde  AND DATE(fecha_hasta) = :fecha_hasta AND id != :id";
					$mbd = new Conexiones;
					$resultado = $mbd->consulta(Conexiones::SELECT, $sql, [':novedad' => $input, ':empleado' => $param1->id,
					':fecha_desde' => $param2->format('Y-m-d'),
					':fecha_hasta' => $param3->format('Y-m-d'),
					':id' => $param4]);
					return (count($resultado) >=1) ? false : true;
					}else{
					return true;
				}
			},'check_usuario_comision(:usuario)' => function($input,$param1){

				if($input == $this::TIPO_COMISION_HORARIA && empty($param1->getEmpleado()->id)){
					return false;
				}

				return true;
			}]
		];

		$nombres = [
			'nombre' => 'Nombre',
			'fecha_desde' => 'Fecha Desde',
			'fecha_hasta' => 'Fecha Hasta',
			'tipo_novedad' => "Tipo de Novedad",
			'id_empleado' => "Empleado",
			'hora_inicio' => "Hora Desde",
			'hora_fin' => "Hora Hasta"
		];

		$inputs = (array)$this;
		$inputs['empleado'] = ($this->empleado instanceof Empleado) ? $this->empleado : null;
		$inputs['hora_inicio'] = isset($this->fecha_desde)? $this->fecha_desde->format("H:i") : null ;
		$inputs['hora_fin'] = isset($this->fecha_hasta)? $this->fecha_hasta->format("H:i") : null ;
		
		$validator = Validador::validate($inputs, $reglas, $nombres);
		
		$validator->customErrors([
			"fecha_valida" => "Campo <strong> :attribute </strong> no válido.",
			"hora_valida" => "Campo <strong> :attribute </strong> no válido.",
			'empleado_valido' => "El empleado no se encuentra registrado.",
			"check_repeat"  => "Ya existe esa  novedad para la fecha seleccionada.",
			"validacion_tipo_contrato" => "No puede generar novedades para Empleados con tipo de contrato <strong>\"" .$tipo_contrato[$this->empleado->id_tipo_contrato]['nombre'].
					"\"</strong>",
			'validacion_rca' => "EL <strong>Empleado</strong> no pertenece a las dependencias autorizadas para el usuario.",
			'compare' => 'El campo :attribute no puede ser menor que Fecha Desde',
			'iguales' => 'El campo :attribute no puede ser distinto que Fecha Hasta',
            'despuesDeDesde' => 'La Fecha Hasta no puede ser menor que la Fecha Desde',
            'despuesDeDesdeHora'=> 'La Hora Fin debe ser posterior a la Hora Desde',
			'check_usuario_comision' => 'Su usuario no está asociado a un Empleado, no se permite crear Novedad <b>Comisión Horaria</b>'


		]);

		if ($validator->isSuccess()) {
			return true;
		}
		$this->errores = $validator->getErrors();

		return false;
	}

	public function modificacion() {
		$return = false;
			$sql = "UPDATE
					novedades
				SET
					id_empleado	= :id_empleado,
					id_tipo_novedad = :tipo_novedad,
					fecha_desde = :fecha_desde,
					fecha_hasta = :fecha_hasta,
					id_usuario_carga = :id_usuario					
				WHERE 
					id = :id;";
			if (!empty($this->id)) {
				$params = [
					':id_empleado' => $this->empleado->id,
					':tipo_novedad' => $this->tipo_novedad,
					':fecha_desde' => $this->fecha_desde->format("Y-m-d H:i"),
					':fecha_hasta' => $this->fecha_hasta->format("Y-m-d H:i"),
					':id_usuario' => $this->usuario->id,
					':id' => $this->id,
				];
				$mbd = new Conexiones;
				$res = $mbd->consulta(Conexiones::UPDATE, $sql, $params);
				if (!empty($res) && $res > 0) {
					$datos = (array)$this;
					$datos['modelo'] = 'novedad';
					if (is_numeric($res) && $res > 0) {
						$return = true;
					} else {
						$datos['error_db'] = $mbd->errorInfo;
					}

					Logger::event('modificacion', $datos);
				}
			}

		return $return;
	}

	public function baja() {
		$flag = false;
		$sql = "UPDATE
					novedades
				SET
					borrado = 1				
				WHERE 
					id = :id;";
		$mbd = new Conexiones;
		$res = $mbd->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
		if (!empty($res) && $res > 0) {
			$datos = (array)$this;
			$datos['modelo'] = 'novedad';
			if (is_numeric($res) && $res > 0) {
				$flag = true;
			} else {
				$datos['error_db'] = $mbd->errorInfo;
			}

			Logger::event('modificacion', $datos);
		}

		return $flag;

	}

	public function alta() {
		$return = false;
			$mbd = new Conexiones;
			$sql = "INSERT INTO novedades (id_empleado, id_tipo_novedad, fecha_desde,fecha_hasta, id_usuario_carga) 
					VALUES (:id_empleado, :id_tipo_novedad, :fecha_desde, :fecha_hasta, :id_usuario_carga)";
			$params = [
				':id_empleado' => $this->empleado->id,
				':id_tipo_novedad' => $this->tipo_novedad,
				':fecha_desde' => $this->fecha_desde->format("Y-m-d H:i"),
				':fecha_hasta' => $this->fecha_hasta->format("Y-m-d H:i"),
				':id_usuario_carga' => $this->usuario->id,
			];
			$resultado = $mbd->consulta(Conexiones::INSERT, $sql, $params);
			$this->id = $resultado;
			$datos = (array)$this;
			$datos['modelo'] = 'novedad';
			if (is_numeric($resultado) && $resultado > 0) {
				$return = true;
			} else {
				$datos['error_db'] = $mbd->errorInfo;
			}

			Logger::event('alta', $datos);

		return $return;
	}


}
