<?php

namespace App\Modelo;

use App\Modelo\Modelo;
use App\Helper\Validador;
use App\Helper\Conexiones;
use FMT\Logger;
use App\Modelo\Persona;
use App\Helper\Biometrica;
use DateTime;

class Empleado extends Modelo
{
    public $id;
    public $persona_id;
    public $usuario_id;
    public $ubicacion;
    public $ubicaciones_autorizadas;
    public $id_coped;
    public $cuit;
    public $email;
    public $planilla_reloj;
    public $oficina_contacto;
    public $oficina_interno;
    public $dependencia_principal;
    public $desde_principal;
    public $hasta_principal;
    public $cargo;
    public $id_tipo_contrato;
    public $desde_contrato;
    public $hasta_contrato;
    public $horarios;
    public $observacion;
    public $info_temporal = [];
    private $ubicacionesHash;
    //Datos de persona
    public $documento;
    public $nombre;
    public $apellido;
    public $genero;

    const AT = 1;
    const LEY_MARCO = 2;
    const PLANTA_PERMANENTE = 3;
    const AUTORIDADES_SUPERIORES = 4;
    const AD_HONOREM = 5;
    const PLANTA_TRANSITORIA = 6;
    const ENROLADO = 2;
    const SIN_CONTRATO = 0;
    const DECRETO_1109_17 = 7;
    const COMISION_SERVICIOS = 8;

    /** CONSTANTES NUEVAS MODALIDADES DE CONTRATACION
     * Se referencian con `id_modalidad_vinculacion` de la tabla `situaciones_revistas`
     */
    const SINEP                 = 1;
    const PRESTACION_SERVICIOS  = 2;
    const PERSONAL_EMBARCADO    = 3;
    const OTRA                  = 4;
    const EXTRAESCALAFONARIO    = 5;
    const AUTORIDAD_SUPERIOR    = 6;
    /**
     * utilizado para reportes de planilla reloj e informe mensual
     */
    const OTRAS_MODALIDADES = 1;
    public static $cargo_descripcion = [
        ['idCargo' => 1, 'descripcionCargo' => 'Agente'],
        ['idCargo' => 2, 'descripcionCargo' => 'Asesor'],
        ['idCargo' => 3, 'descripcionCargo' => 'Director'],
        ['idCargo' => 4, 'descripcionCargo' => 'Director General'],
        ['idCargo' => 5, 'descripcionCargo' => 'Secretario'],
        ['idCargo' => 6, 'descripcionCargo' => 'Subsecretario'],
        ['idCargo' => 7, 'descripcionCargo' => 'Jefe de Gabinete'],
        ['idCargo' => 8, 'descripcionCargo' => 'Ministro']
    ];

    static $ANULAR_VALIDACION   = false;

/*Constantes para uso generico*/
    const  ENROLADO_SI = 1;
    const  ENROLADO_NO = 0;

    //_CamposDeclaracionVista_
    public static function obtener($id = null)
    {
        if ($id === null) {
            return static::arrayToObject();
        }
        $sql_params    = [
            ':id'    => $id,
        ];
        $campos    = implode(',', [
            'em.id',
            'persona_id',
            'eu.usuario_id',
            'up.ubicacion',
            'ua.ubicaciones_autorizadas',
            'd.codep as id_codep',
            'em.cuit',
            'em.email',
            'em.planilla_reloj',
            'em.oficina_contacto',
            'em.oficina_interno',
            'edp.id_dependencia_principal AS dependencia_principal',
            'edp.fecha_desde AS desde_principal',
            'edp.fecha_hasta AS hasta_principal',
            'ec.cargo',
            'ec.id_tipo_contrato',
            'ec.fecha_desde AS desde_contrato',
            'ec.fecha_hasta AS hasta_contrato',
            'eh.horarios',
            'em.observacion',
            'p.documento',
            'p.nombre',
            'p.apellido',
            'p.genero'
        ]);
        $sql    = <<<SQL
                SELECT {$campos}
				FROM
					empleados AS em
					INNER JOIN personas p ON p.id = em.persona_id
					LEFT JOIN empleado_dependencia_principal edp ON (edp.id_empleado = em.id AND (edp.fecha_hasta >= NOW() OR edp.fecha_hasta IS NULL) AND edp.borrado = 0)
					LEFT JOIN empleado_contrato ec ON (ec.id_empleado = em.id AND ISNULL(ec.fecha_hasta) AND ec.borrado = 0)
					LEFT JOIN dependencias d ON d.id = edp.id_dependencia_principal
					LEFT JOIN empleado_x_usuario eu ON em.id=eu.empleado_id
					INNER JOIN (
									SELECT
										empleado_id, ubicacion_id AS ubicacion
									FROM empleados_x_ubicacion eu
									WHERE principal = 1
								) up ON up.empleado_id = em.id
					LEFT JOIN (
									SELECT
										empleado_id,
										GROUP_CONCAT(ubicacion_id SEPARATOR ', ') AS ubicaciones_autorizadas
									FROM empleados_x_ubicacion eu WHERE eu.borrado = 0
									GROUP BY empleado_id
								) ua ON ua.empleado_id = em.id
					LEFT JOIN empleado_horarios eh ON em.id = eh.id_empleado
				WHERE
					em.id = :id AND em.borrado = 0 AND p.borrado = 0;
SQL;
        $res    = (new Conexiones())->consulta(Conexiones::SELECT, $sql, $sql_params);
        if (!empty($res)) {
            return static::arrayToObject($res[0]);
        }
        return static::arrayToObject();
    }

    public static function listar()
    {
        $conexion = new Conexiones();
        $resultado = $conexion->consulta(
            Conexiones::SELECT,
            ''
        );

        if (empty($resultado)) {
            return [];
        }
        foreach ($resultado as &$value) {

            $value    = static::arrayToObject($value);
        }
        return $resultado;
    }

    static public function listar_empleados($params)
    {
       
        $rol = Usuario::obtenerUsuarioLogueado();
        $id_rol = $rol->rol_id;
        
        $campos    = 'id ,persona_id ,documento ,nombre ,apellido ,genero, ubicacion, ubicacion_id ,id_tipo_contrato ,ubicaciones_autorizadas ,d_principal ,id_d_principal ,enrolado, contrato_nombre, usuario';
        $sql_params = [];
        $where = [];
        $condicion = "AND e.borrado = 0 AND p.borrado = 0";

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
                'ubicacion'       => null,
                'dependencia'       => null,
                'contrato'       => null,
                'enrolado'       => null,
                'estado'       => null,
            ]
        ];

        $params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
        $params = array_merge($default_params, $params);


        /*Filtros */
        if (!empty($params['filtros']['ubicacion'])) {
            $where[] = "up.ubicacion_id = :ubicacion AND e.borrado = 0";
            $sql_params[':ubicacion']    = $params['filtros']['ubicacion'];
        }
        if (!empty($params['filtros']['dependencia'])) {
            $where[] = "dp.id_dependencia_principal = :dependencia AND e.borrado = 0";
            $sql_params[':dependencia']    = $params['filtros']['dependencia'];
        }else{
            if (!empty($rol->dependencias)) {
                $deps = implode("," ,$rol->dependencias);
                $where[] = "dp.id_dependencia_principal IN ( " . $deps . ")";
            }            
        }

        if (!empty($params['filtros']['contrato'])) {
            $where[] = "id_tipo_contrato = :contrato AND e.borrado = 0";
            $sql_params[':contrato']    = $params['filtros']['contrato'];
        }
        if (!empty($params['filtros']['enrolado'])) {
            $where[] = "IF (Isnull(t.data), 1, 2) = :enrolado AND e.borrado = 0";
            $sql_params[':enrolado']    = $params['filtros']['enrolado'];
        }
        if (!empty($params['filtros']['estado'])) {
            if (($params['filtros']['estado']) == 'Activos') {
                $where[] = "id_tipo_contrato IS NULL AND e.borrado = 0";
            } else {
                $where[] = "id_tipo_contrato IS NOT NULL AND e.borrado = 0";
            }
        }

        $condicion = !empty($where) ? ' WHERE ' . \implode(' AND ', $where) : '';

        if (!empty($params['search'])) {
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
        SELECT  e.id,
                e.persona_id,
                p.documento,
                p.nombre,
                p.apellido,
                p.genero,
                up.ubicacion,
                up.ubicacion_id,
                Ifnull(ec.id_tipo_contrato, 0)   AS id_tipo_contrato,
                CASE
                    WHEN id_tipo_contrato = 1 THEN 'SINEP - Planta Permanente'
                    WHEN id_tipo_contrato = 2 THEN 'SINEP - Ley Marco'
                    WHEN id_tipo_contrato = 3 THEN 'SINEP - Designacion Transitoria en Cargo de Planta Permanente con Funcion Ejecutiva'
                    WHEN id_tipo_contrato = 4 THEN 'SINEP - Planta Permanente MTR con Designacion Transitoria'
                    WHEN id_tipo_contrato = 5 THEN 'Prestacion de Servicios - 1109/17'
                    WHEN id_tipo_contrato = 6 THEN 'Prestacion de Servicios - 1109/17 con Financimiento Externo'
                    WHEN id_tipo_contrato = 7 THEN 'Prestacion de Servicios - Asistencia Tecnica'
                    WHEN id_tipo_contrato = 8 THEN 'Personal Embarcado - CLM'
                    WHEN id_tipo_contrato = 9 THEN 'Personal Embarcado - Planta Permanente'
                    WHEN id_tipo_contrato = 10 THEN 'Otra - Comision Servicios'
                    WHEN id_tipo_contrato = 11 THEN 'Otra - Planta Permanente con Designación Transitoria'
                    WHEN id_tipo_contrato = 12 THEN 'Otra - Gabinete de Asesores'
                    WHEN id_tipo_contrato = 13 THEN 'Autoridad Superior - Autoridad Superior'
                    WHEN id_tipo_contrato = 14 THEN 'Extraescalafonario - Extraescalafonario'
                    WHEN id_tipo_contrato = 15 THEN 'SINEP - Planta Permanente MTR con Designacion Transitoria con Funcion Ejecutiva'
                    WHEN id_tipo_contrato = 16 THEN 'Otra - Adscripción'
                    WHEN id_tipo_contrato = 17 THEN 'SINEP - Designación Transitoria sin Función Ejecutiva'
                    WHEN id_tipo_contrato = 18 THEN 'Otra - HORAS CÁTEDRA'
                    WHEN id_tipo_contrato = 19 THEN 'Otra - Otras Modalidades'
                    ELSE 'Sin contrato'
                END contrato_nombre,                
                ua.ubicaciones_autorizadas,
                dep.nombre                       AS d_principal,
                dp.id_dependencia_principal      AS id_d_principal,
                ec.fecha_hasta                   AS hasta_contrato,
                ec.fecha_desde                   AS desde_contrato,
                IF (Isnull(t.data), false, true) AS enrolado,
                $id_rol as usuario
            FROM   empleados e
                INNER JOIN personas p ON p.id = e.persona_id
                LEFT JOIN empleado_contrato ec ON ( e.id = ec.id_empleado AND (ec.fecha_hasta  IS NULL OR ec.fecha_hasta >= NOW()) AND ec.borrado = 0 )
                LEFT JOIN empleado_dependencia_principal dp ON ( e.id = dp.id_empleado AND (dp.fecha_hasta >= NOW() OR dp.fecha_hasta IS NULL) AND dp.borrado = 0 )
                LEFT JOIN dependencias dep ON dp.id_dependencia_principal = dep.id AND (dep.fecha_hasta >= NOW() OR dep.fecha_hasta IS NULL) AND dep.visible = 1
                LEFT JOIN (SELECT te.persona_id, te.data 
                            FROM   templates te
                            GROUP  BY te.persona_id) AS t ON t.persona_id = p.id
                INNER JOIN (SELECT eu.empleado_id, nombre AS ubicacion, u.id AS ubicacion_id
                            FROM   empleados_x_ubicacion eu
                            INNER JOIN ubicaciones u ON u.id = eu.ubicacion_id
                            WHERE  principal = 1 AND eu.borrado = 0) up ON up.empleado_id = e.id
                INNER JOIN (SELECT eu.empleado_id, Group_concat(nombre SEPARATOR ', ') AS ubicaciones_autorizadas, principal
                            FROM   empleados_x_ubicacion eu
                            INNER JOIN ubicaciones u ON u.id = eu.ubicacion_id
                            GROUP  BY empleado_id) ua ON ua.empleado_id = e.id
            $condicion           
SQL;
        $data = self::listadoAjax($campos, $consulta, $params, $sql_params);        
        return $data;
    }

    public static function listar_empleados_excel($params){
        $rol = Usuario::obtenerUsuarioLogueado();
        $id_rol = $rol->rol_id;

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
                'dependencia'   => null,
                'ubicacion'     => null,
                'contrato'      => null,
                'enrolado'      => null,
                'estado'        => null

            ],
            'count'     => false
        ];

        $params['filtros']  = array_merge($default_params['filtros'], $params['filtros']);
        $params = array_merge($default_params, $params);

        $sql= <<<SQL
            SELECT  e.id,
                    e.persona_id,
                    p.documento,
                    p.nombre,
                    p.apellido,
                    p.genero,
                    up.ubicacion,
                    up.ubicacion_id,
                    Ifnull(ec.id_tipo_contrato, 0)   AS id_tipo_contrato,
                    dep.nombre                       AS d_principal,
                CASE
                    WHEN id_tipo_contrato = 1 THEN 'SINEP - Planta Permanente'
                    WHEN id_tipo_contrato = 2 THEN 'SINEP - Ley Marco'
                    WHEN id_tipo_contrato = 3 THEN 'SINEP - Designacion Transitoria en Cargo de Planta Permanente con Funcion Ejecutiva'
                    WHEN id_tipo_contrato = 4 THEN 'SINEP - Planta Permanente MTR con Designacion Transitoria'
                    WHEN id_tipo_contrato = 5 THEN 'Prestacion de Servicios - 1109/17'
                    WHEN id_tipo_contrato = 6 THEN 'Prestacion de Servicios - 1109/17 con Financimiento Externo'
                    WHEN id_tipo_contrato = 7 THEN 'Prestacion de Servicios - Asistencia Tecnica'
                    WHEN id_tipo_contrato = 8 THEN 'Personal Embarcado - CLM'
                    WHEN id_tipo_contrato = 9 THEN 'Personal Embarcado - Planta Permanente'
                    WHEN id_tipo_contrato = 10 THEN 'Otra - Comision Servicios'
                    WHEN id_tipo_contrato = 11 THEN 'Otra - Planta Permanente con Designación Transitoria'
                    WHEN id_tipo_contrato = 12 THEN 'Otra - Gabinete de Asesores'
                    WHEN id_tipo_contrato = 13 THEN 'Autoridad Superior - Autoridad Superior'
                    WHEN id_tipo_contrato = 14 THEN 'Extraescalafonario - Extraescalafonario'
                    WHEN id_tipo_contrato = 15 THEN 'SINEP - Planta Permanente MTR con Designacion Transitoria con Funcion Ejecutiva'
                    WHEN id_tipo_contrato = 16 THEN 'Otra - Adscripción'
                    WHEN id_tipo_contrato = 17 THEN 'SINEP - Designación Transitoria sin Función Ejecutiva'
                    WHEN id_tipo_contrato = 18 THEN 'Otra - HORAS CÁTEDRA'
                    WHEN id_tipo_contrato = 19 THEN 'Otra - Otras Modalidades'
                    ELSE 'Sin contrato'
                END contrato_nombre,                
                ua.ubicaciones_autorizadas,
                dp.id_dependencia_principal      AS id_d_principal,
                ec.fecha_hasta                   AS hasta_contrato,
                ec.fecha_desde                   AS desde_contrato,
                IF (Isnull(t.data), false, true) AS enrolado,
                $id_rol as usuario
SQL;


    $from = <<<SQL
        FROM   empleados e
                INNER JOIN personas p ON p.id = e.persona_id
                LEFT JOIN empleado_contrato ec ON ( e.id = ec.id_empleado AND (ec.fecha_hasta  IS NULL OR ec.fecha_hasta >= NOW()) AND ec.borrado = 0 )
                LEFT JOIN empleado_dependencia_principal dp ON ( e.id = dp.id_empleado AND (dp.fecha_hasta >= NOW() OR dp.fecha_hasta IS NULL) AND dp.borrado = 0 )
                LEFT JOIN dependencias dep ON dp.id_dependencia_principal = dep.id AND (dep.fecha_hasta >= NOW() OR dep.fecha_hasta IS NULL) AND dep.visible = 1
                LEFT JOIN (SELECT te.persona_id, te.data 
                            FROM   templates te
                            GROUP  BY te.persona_id) AS t ON t.persona_id = p.id
                INNER JOIN (SELECT eu.empleado_id, nombre AS ubicacion, u.id AS ubicacion_id
                            FROM   empleados_x_ubicacion eu
                            INNER JOIN ubicaciones u ON u.id = eu.ubicacion_id
                            WHERE  principal = 1 AND eu.borrado = 0) up ON up.empleado_id = e.id
                INNER JOIN (SELECT eu.empleado_id, Group_concat(nombre SEPARATOR ', ') AS ubicaciones_autorizadas, principal
                            FROM   empleados_x_ubicacion eu
                            INNER JOIN ubicaciones u ON u.id = eu.ubicacion_id
                            GROUP  BY empleado_id) ua ON ua.empleado_id = e.id
SQL;


    $condicion = <<<SQL
        WHERE e.borrado = 0 AND p.borrado = 0
        
SQL;

    /**Filtros*/
    if(!empty($params['filtros']['dependencia'])){
        $condicion .= " AND dp.id_dependencia_principal = :dependencia AND e.borrado = 0";
        $sql_params[':dependencia']    = $params['filtros']['dependencia'];
    }else{
        if (!empty($rol->dependencias)) {
            $deps = implode("," ,$rol->dependencias);
            $condicion .= " AND dp.id_dependencia_principal IN ( " . $deps . ")";
        }        
    }

    if(!empty($params['filtros']['ubicacion'])){
        $condicion .= " AND up.ubicacion_id = :ubicacion AND e.borrado = 0";
        $sql_params[':ubicacion']    = $params['filtros']['ubicacion'];
    }
    if(!empty($params['filtros']['contrato'])){
        $condicion .= " AND id_tipo_contrato = :contrato AND e.borrado = 0";
        $sql_params[':contrato']    = $params['filtros']['contrato'];
    }
    
    if(!empty($params['filtros']['enrolado'])){
        $condicion .= " AND IF (Isnull(t.data), 1, 2) = :enrolado AND e.borrado = 0";
        $sql_params[':enrolado']    = $params['filtros']['enrolado'];
    }

     if(!empty($params['filtros']['estado'])){
         if (($params['filtros']['estado']) == 'Activos') {
                $condicion .= " AND id_tipo_contrato IS NULL AND e.borrado = 0";
            }else{
                $condicion .= " AND id_tipo_contrato IS NOT NULL AND e.borrado = 0";
            }
    }
    $counter_query  = "SELECT COUNT(e.id) AS total {$from}";

    $recordsTotal   =  $cnx->consulta(Conexiones::SELECT, $counter_query . $condicion, $sql_params )[0]['total'];

        //Los campos que admiten en el search (buscar) para concatenar al filtrado de la consulta
        if(!empty($params['search'])){
            $indice = 0;
            $search[]   = <<<SQL
            (p.documento LIKE :search{$indice} OR p.nombre LIKE :search{$indice} OR p.apellido LIKE :search{$indice} ) 
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

        $order .= (($order =='') ? '' : ', ').'p.apellido asc';

        $order = ' ORDER BY '.$order;

        $lista = $cnx->consulta(Conexiones::SELECT,  $sql .$from.$condicion.$order.$limit,$sql_params);

        return ($lista) ? $lista : [];
    }


    public function alta()
    {

            $per = Persona::obtenerPorDocumento($this->documento);
            if (empty($per) || empty($per->id)) {
                $per = new Persona();
                $per->documento = $this->documento;
                $per->nombre = $this->nombre;
                $per->apellido = $this->apellido;
                $per->genero = $this->genero;
                $flag = $per->alta();
                $this->persona_id = $flag;
            } else {
                $per->documento = $this->documento;
                $per->nombre = $this->nombre;
                $per->apellido = $this->apellido;
                $per->genero = $this->genero;
                $flag = $per->modificacion();
                $this->persona_id = $per->id;
            }
            if ($flag) {
                $conex = new Conexiones();
                $sql = "SELECT id FROM empleados WHERE persona_id = :persona_id AND borrado = 0";
                $buscar = $conex->consulta(
                    Conexiones::SELECT,
                    $sql,
                    [':persona_id' => $this->persona_id]
                );

                if (empty($buscar)) {
                    $sql = "INSERT INTO empleados(
								persona_id,
								cuit,
								email,
								planilla_reloj,
                                observacion
								)
								VALUE (
								:persona_id,
								:cuit,
								:email,
								:planilla_reloj,
							    :observacion
								)";
                    $params = [
                        ':persona_id' => $this->persona_id,
                        ':cuit' => $this->cuit,
                        ':email' => $this->email,
                        ':planilla_reloj' => $this->planilla_reloj,
                        ':observacion' => trim($this->observacion),
                    ];
                    $id_empleado = $conex->consulta(Conexiones::INSERT, $sql, $params);

                    if ($id_empleado !== false) {

                        $this->id = (int)$id_empleado;

                        $this->insert_dependencia_principal();

                        $this->insert_contrato();
                    } 
                    //LOG DE EXITO EN ALTA
                    if ($this->agregarUbicacion()) { //Agrega la ubicación principal
                        //Se agregan ubicaciones
                        if (!empty($this->ubicaciones_autorizadas)) {
                            if (!$this->agregarUbicacionesAutorizadas()) {
                                return false;
                            }

                            return true;
                        } else {
                            $this->errores = ['texto' => 'No se pudieron guardar las ubicaciones indicadas'];
                        }
                    }
                } else {
                    return false;
                }
            }
        

        return false;
    }

    public function modificacion()
    {

        $this->update();
        $per = Persona::obtenerPorDocumento($this->documento);
        $per->documento = $this->documento;
        $per->nombre = $this->nombre;
        $per->apellido = $this->apellido;
        $per->genero = $this->genero;            
        $per->modificacion();
        
        //Se agregan ubicaciones
        if (!empty($this->ubicaciones_autorizadas)) {
            if (!$this->agregarUbicacionesAutorizadas()) {
                return false;
            }
            return true;

        }   
        
    }

    /**
	 * @return bool
	 */
	public function update() {
        
		$sql = "UPDATE empleados SET cuit = :cuit, email = :email, planilla_reloj = :planilla_reloj, observacion = :observacion WHERE id=:id";
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::UPDATE, $sql, [	':id' => $this->id,
															':cuit' => $this->cuit,
															':email' => $this->email,
															':planilla_reloj' => $this->planilla_reloj,
															':observacion' => $this->observacion
															]);
		if ($res >= 0) {
            
			$this->modificacion_principal();            
			$this->modificacion_contrato();
		} else {
			// $datos['error_db'] = $conex->errorInfo;
			// Logger::event('error_modificacion', $datos);
		}

		return $res;
	}

    protected function modificacion_principal() {

        $desde_principal = ($this->desde_principal instanceof DateTime) ? $this->desde_principal->format('Y-m-d') : \DateTime::createFromFormat('d/m/Y', $this->desde_principal)->format('Y-m-d');
        if(isset($this->hasta_principal)){
            $hasta_principal = ($this->hasta_principal instanceof DateTime) ? $this->hasta_principal->format('Y-m-d') : \DateTime::createFromFormat('d/m/Y', $this->hasta_principal)->format('Y-m-d');
        }else{
            $hasta_principal = null;
        }
        
        

		if($this->tiene_dependencia_principal()) {            
			$sql = "UPDATE empleado_dependencia_principal SET fecha_desde = :desde, fecha_hasta = :hasta, id_dependencia_principal = :id_dependencia_principal WHERE id_empleado=:id AND (fecha_hasta >= NOW() OR fecha_hasta IS NULL) AND borrado = 0";
			$conex = new Conexiones();            
			$res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id, ':desde' => $desde_principal, ':hasta' => $hasta_principal, ':id_dependencia_principal' => $this->dependencia_principal]);            
		} else {
			$res = $this->insert_dependencia_principal();
		}
		return $res;
	}

    protected function tiene_dependencia_principal() {
		$conex = new Conexiones();
		$sql = "SELECT count(*) AS cant FROM empleado_dependencia_principal WHERE (fecha_hasta >= NOW() OR fecha_hasta IS NULL) AND id_empleado = :id AND borrado = 0";
		$res = $conex->consulta(Conexiones::SELECT, $sql, [':id' => $this->id]);
		return $res[0]['cant'];
	}

    /**
 * Modifica los datos de un contrato existente o inserta uno nuevo en caso de no tener.
 *
 * @return bool|int
 */
	protected function modificacion_contrato() {
        $desde_contrato = ($this->desde_contrato instanceof DateTime) ? $this->desde_contrato->format('Y-m-d') : \DateTime::createFromFormat('d/m/Y', $this->desde_contrato)->format('Y-m-d');
        
        if(isset($this->hasta_contrato)){
            $hasta_contrato = ($this->hasta_contrato instanceof DateTime) ? $this->hasta_contrato->format('Y-m-d') : \DateTime::createFromFormat('d/m/Y', $this->hasta_contrato)->format('Y-m-d');
        }else{
            $hasta_contrato = null;
        }
        
		if($this->tiene_contrato()) {
			$sql = "UPDATE empleado_contrato SET fecha_desde = :desde, fecha_hasta = :hasta, cargo = :cargo  WHERE id_empleado=:id AND ISNULL(fecha_hasta) AND borrado =0";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id, ':desde' => $desde_contrato, ':hasta' => $hasta_contrato, ':cargo' => $this->cargo ]);
		} else {
			$res = $this->insert_contrato();
		}
		return $res;
	}

    protected function insert_dependencia_principal()
    {
        $desde_principal = $this->desde_principal;
        $hasta_principal = $this->hasta_principal;
        $conex = new Conexiones();
        $sql = "INSERT INTO empleado_dependencia_principal (id_dependencia_principal, id_empleado, fecha_desde, fecha_hasta) VALUES (:id,:id_empleado,:desde,:hasta) ";
        $params = [
            ':id' => $this->dependencia_principal,
            ':id_empleado' => $this->id,
            ':desde' => ($desde_principal instanceof DateTime) ? $desde_principal->format('Y-m-d') : null,
            ':hasta' => ($hasta_principal instanceof DateTime) ? $hasta_principal->format('Y-m-d') : null,
        ];
        $res = $conex->consulta(Conexiones::INSERT,$sql,$params);
        return $res;
    }

    protected function insert_contrato()
    {
        $conex = new Conexiones();
        $res = $conex->consulta(
            Conexiones::INSERT,
            "INSERT INTO empleado_contrato (id_tipo_contrato, id_empleado, cargo, fecha_desde, fecha_hasta) VALUES (:id, :id_empleado, :cargo, :desde, :hasta) ",
            [
                ':id' => $this->id_tipo_contrato,
                ':id_empleado' => $this->id,
                ':cargo' => $this->cargo,
                ':desde' => ($this->desde_contrato instanceof DateTime) ? $this->desde_contrato->format('Y-m-d') : DateTime::createFromFormat('d/m/Y', $this->desde_contrato)->format('Y-m-d')  ,
                ':hasta' => null,
            ]
        );
        return $res;
    }

    public function cancelar_contrato(){

		if(empty($this->hasta_contrato) || empty($this->id)) {            
			$this->errores = (empty($this->fecha_hasta_contrato)) ? 'La <b>fecha hasta</b> del Contrato Actual es obligatoria para hacer la baja de contratación.': $this->errores;            
			return false;
		}
		$res = false;            
        if ($this->tiene_contrato()) {

			$sql = "UPDATE empleado_contrato SET fecha_hasta = :hasta, borrado = 1 WHERE id_empleado=:id AND ISNULL(fecha_hasta) AND borrado = 0";
			$conex = new Conexiones();
            $hasta_contrato =  ($this->hasta_contrato instanceof DateTime) ? $this->hasta_contrato->format('Y-m-d') : DateTime::createFromFormat('Y-m-d', $this->hasta_contrato)->format('Y-m-d');
			$res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id, ':hasta' => $hasta_contrato ]);

		}
		return $res;
	}

    public function cambiar_contrato() {        
		if($this->validar()) {
			$retorno = $this->insert_contrato();
		} else {
			$retorno = false;
		}
		return $retorno;
	}

    public function tiene_contrato() {
		$conex = new Conexiones();
		$sql = "SELECT count(*) AS cant FROM empleado_contrato WHERE ( ISNULL(fecha_hasta)  OR fecha_hasta > NOW() )AND id_empleado = :id AND borrado = 0";
		$res = $conex->consulta(Conexiones::SELECT, $sql, [':id' => $this->id]);
		return $res[0]['cant'];
	}

    public static function contrato_anterior($id) {
		$conex = new Conexiones();
		$sql = "SELECT * FROM empleado_contrato WHERE id_empleado = :id ORDER BY id DESC LIMIT 1";
		$res = $conex->consulta(Conexiones::SELECT, $sql, [':id' => $id]);

		return $res[0];
	}

    /**
     * Se actualiza la ubicación principal del empleado.
     *
     * @return bool
     */
    public function agregarUbicacion()
    {
        $conex = new Conexiones();
        //Se busca si ya hay ubicación principal almacenada
        $buscar = "SELECT
						id,
						ubicacion_id
					FROM empleados_x_ubicacion
					WHERE empleado_id = :id AND principal = 1;";
        $res = $conex->consulta(Conexiones::SELECT, $buscar, [':id' => $this->id]);
        if (!empty($res)) { //Si hay cargada se compara con la que se intenta cargar
            if ($res[0]['ubicacion_id'] == $this->ubicacion) {
                return true;
            } else { //Si es distinta a la que está cargada, se elimina y se carga la actual
                $eliminar = 'UPDATE empleados_x_ubicacion SET borrado = 1 WHERE empleado_id = :id';
                $resultado = $conex->consulta(
                    Conexiones::DELETE,
                    $eliminar,
                    [
                        ':id' => $res[0]['id'],
                    ]
                );
                if (!$resultado) {
                    $this->errores = ['texto' => 'No se pudo eliminar la ubicación previa'];

                    return false;
                } else {
                    //Log
                    // $datos = (array)$this;
                    // $datos['Modelo'] = 'empleado';
                    // Logger::event('baja_ubicacion_principal_empleado', $datos);
                }
            }
        }
        //Se inserta nueva ubicación
        $insertar = "INSERT INTO empleados_x_ubicacion (
						empleado_id,
						ubicacion_id,
						principal
					) VALUES (
						:empleado_id,
						:ubicacion_id,
						:principal
					);";
        $guardado = $conex->consulta(
            Conexiones::INSERT,
            $insertar,
            [
                ':empleado_id'  => $this->id,
                ':ubicacion_id' => $this->ubicacion,
                ':principal'    => 1,
            ]
        );
        if ($guardado !== false) {
            //Log
            $datos = (array)$this;
            $datos['Modelo'] = 'empleado';
            Logger::event('alta_ubicacion_principal_empleado', $datos);
        }

        return $guardado;
    }

    /**
     * Se actualizan las ubicaciones para las cuales tiene autorización el empleado.
     * Para ello se eliminan las que estuviesen cargadas.
     * Luego se procede a cargar las nuevas
     *
     * @return bool
     */
    public function agregarUbicacionesAutorizadas()
    {
        $conex = new Conexiones;
        $borrar = 'UPDATE empleados_x_ubicacion SET borrado = 1 WHERE empleado_id = :id';
        $resultado = $conex->consulta(Conexiones::UPDATE, $borrar, [':id' => $this->id]);
        
        $buscar = "SELECT empleado_id FROM empleados_x_ubicacion WHERE empleado_id = :id AND ubicacion_id = :ubi";
        $updatear ='UPDATE empleados_x_ubicacion SET borrado = 0, principal = :principal WHERE empleado_id = :id AND ubicacion_id = :ubi';
        $insertar = "INSERT INTO empleados_x_ubicacion (empleado_id,ubicacion_id,principal) VALUES (:id,:ubi,:principal)";

        $nuevas_ubicaciones = [];
        foreach ($this->ubicaciones_autorizadas as $ubicacion) {
            $nuevas_ubicaciones[] = $ubicacion;
            $existe =  $conex->consulta(Conexiones::SELECT, $buscar, [':id' => $this->id,':ubi' => $ubicacion]);
            if (!empty($existe) && is_array($existe) && isset($existe[0])) {
                $res = $conex->consulta(Conexiones::UPDATE, $updatear, [
                    ':id'        => $this->id,
                    ':ubi'       => $ubicacion,
                    ':principal' => (int)($this->ubicacion == $ubicacion),
                ]);
            }else{
                $res = $conex->consulta(Conexiones::INSERT, $insertar, [
                    ':id'        => $this->id,
                    ':ubi'       => $ubicacion,
                    ':principal' => (int)($this->ubicacion == $ubicacion),
                ]);
            }
            if (!$res) {
                $this->errores = 'Error al guardar ubicaciones autorizadas';

                return false;
            }
        }

        if (static::hashingUbicaciones($nuevas_ubicaciones) != $this->ubicacionesHash) {
        	$this->distribuirTemplates();
        }
        return true;
    }

    public function distribuirTemplates() {
		$resp = null;        
		if (!empty($this->id) && !empty($this->documento)) {
			$data = [];
			$data['templates'] = $this->getTemplates();
			if($data['templates']) {
                $ubicaciones = explode(',',$this->ubicaciones_autorizadas);
				foreach ($ubicaciones as $ubi_id) {
                    $ubicacion = Ubicacion::obtener($ubi_id);
					foreach (Reloj::obtenerPorUbicacion($ubicacion) as $reloj) {
						$data['nodes'][] = $reloj->nodo;
					}
				}                
				//$url = "templates/distribuir/{$this->documento}";
				$resp = Biometrica::distribuir_templates($this->documento, $data);
			}else{
				$resp = true;
			}
		}
		return $resp;
	}

    public function bajaEnEnrolador() {
		//esta funcion del proxy borra todos los templates y luego el usuario
		$uri = "templates/baja";
		$params = [
			'accessId' => $this->documento,
		];
		$resp = Biometrica::delete_templates($params);

		return $resp;
	}

    /**
	 * @return Template[]
	 */
	public function getTemplates() {
        $persona = Persona::obtener($this->persona_id);
		return Template::listarPorPersona($persona);
	}


    /**
	 *
	 */
	public function baja() {
		$conex = new Conexiones();
		$sql = "SELECT COUNT(usuario_id) AS count, usuario_id
				FROM empleado_x_usuario WHERE empleado_id = :id and estado = 1";
		$params = [':id' => $this->id];
		$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
		if ($res[0]['count'] > 0) {
			$user = Usuario::obtener($res[0]['usuario_id']);
			$this->errores = [
				'texto' => "EL empleado <strong>{$this->nombre} {$this->apellido}</strong> está asociado al usuario " .
					"<strong>{$user->username}</strong>.<br>" .
					"Para eliminar este empleado, deberá eliminar antes la asociación con el " .
					"<a href='?c=usuarios&amp;a=editar&amp;uid={$user->id}'>usuario<i class='fa fa-external-link fa-fw'></i></a>.",
			];
		} else {
			$sql = "UPDATE empleados SET borrado = 1 WHERE id = :id;";
			$res = $conex->consulta(Conexiones::UPDATE, $sql, $params);
			if (!empty($res) && is_numeric($res)) {

				return $res > 0;
			}
		}

		return false;
	}

    /**
 * Actualiza las huellas en una ubicacion.
 * Dado un empleado con ubicaciones autorizadas y un id de ubicacion, vuelve a enviar las huellas (templates) al conjunto de nodos de dicha ubicacion.
 *
 * Obtiene las huellas de una persona (Template) (almacenado en base de datos).
 * Obtiene los nodos (Relojes) de una ubicacion.
 * Formatea la informacion y la envia al API de relojes para redistribuir.
 *
 * @param integer	$ubicacion_id	- ID de la ubicacion
 * @return boolean
*/
	public function actualizarTemplate($ubicacion_id=null) {
		if(!isset($ubicacion_id) || $ubicacion_id === false || $ubicacion_id === null){
			return false;
        }
		if((empty($this->id) && $this->id !== 0) || empty($this->documento)){
			return false;
        }

		$return	= false;
		$url	= "templates/distribuir/{$this->documento}";
		$data	= [
			'templates'	=> $this->getTemplates(),
			'nodes'		=> [],
		];

        $ubicaciones_autorizadas = explode(",", str_replace(' ', '', $this->ubicaciones_autorizadas));
        
		foreach ($ubicaciones_autorizadas as $ubicacion) {            
			if($ubicacion == $ubicacion_id){
                $ubicacion_obj = Ubicacion::obtener($ubicacion);	
				foreach ((array)Reloj::obtenerPorUbicacion($ubicacion_obj) as &$reloj)
					$data['nodes'][]	= $reloj->nodo;
				//$return	= Biometrica::sendPost($url, $data); // Devuelve un objeto, null o false.
                $return = Biometrica::distribuir_templates($this->documento, $data);
			}
		}

		return  !empty($return)
				? $return['status']
				: false;
	}

    public static function buscar($busca) {

		$sql = "SELECT e.id, p.documento, p.nombre, p.apellido, p.genero, p.borrado
				FROM personas AS p INNER JOIN empleados AS e ON e.persona_id = p.id					
				WHERE (p.borrado = 0 AND e.borrado = 0) AND
				      (p.documento LIKE CONCAT('%', :busca, '%') OR
				       CONCAT_WS(' ', p.nombre, p.apellido) LIKE CONCAT('%', :busca, '%'));";
		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, [
			':busca'        => $busca,
		]);
		$lista = [];
		foreach ($res as $re) {
			$empleado = Empleado::arrayToObject($re);
			$lista[] = $empleado;
		}

		return $lista;
	}

    public static function lista_plantilla_horaria(){
        
		$sql = "SELECT id, nombre, horario FROM plantilla_horarios WHERE borrado = 0";
        $res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, []);
        return $res;
    }

    public static function horarios_plantilla($id){
        $sql = "SELECT horario FROM plantilla_horarios WHERE borrado = 0 AND id = :id";
        $res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, [':id'=>$id]);
        return $res;
    }

    public function alta_empleado_horario() {
			$conex = new Conexiones();
			$sql = "INSERT INTO empleado_horarios (id_empleado, horarios) VALUES (:id_empleado, :horarios) ON DUPLICATE KEY UPDATE horarios = :horarios, borrado = :borrado";
			$resultado = $conex->consulta(Conexiones::INSERT, $sql, [':horarios'  => json_encode($this->horarios, JSON_UNESCAPED_SLASHES),':id_empleado'  => $this->id, ':borrado'  => 0]);
			if (is_numeric($resultado) && $resultado > 0) {
				return true;
			}
		return false;
	}

    public function validar_horarios(){
        $rules = [
            'horarios' =>  ['required'],
            'id' =>  ['required','existe(empleados,id)'],
        ];
        $nombres    = [
            'existe' =>  'El empleado existe'
        ];

        $validator = Validador::validate((array)$this, $rules, $nombres);
        $validator->customErrors([
            'required'      => ' Campo <b> :attribute </b> es requerido',
        ]);
        if ($validator->isSuccess()) {
            return true;
        } else {
            $this->errores = $validator->getErrors();
            return false;
        }
        
    }
    public function validar()
    {
        if(static::$ANULAR_VALIDACION === true){
            static::$ANULAR_VALIDACION  = false;
            return true;
        }
        $documento = $this->documento;
        $rules = [
            'documento' =>  ['required','documento'],
            'nombre' =>  ['required','max_length(100)'],
            'apellido' =>  ['required','max_length(64)'],
            'cuit' =>  ['required','cuit','es_cuit' => function($cuit)use($documento){
                $cuit_to_documento = substr(substr($cuit, 0, -1), 2);
                return ($cuit_to_documento == $documento);
            }],
            'email' =>  ['required','max_length(60)'],
            'dependencia_principal' =>  ['required'],
            'desde_principal' =>  ['required','fecha'],
            'hasta_principal' =>  ['fecha'],            
            'id_tipo_contrato' =>  ['required'],
            'cargo' =>  ['required'],
            'hasta_contrato' =>  ['fecha'],
            'desde_contrato'	=> ['required','fecha'],
            'hasta_contrato'	=> ['fecha'],
            'ubicacion' =>  ['required'],
            'ubicaciones_autorizadas' =>  ['required'],
        ];
        $nombres    = [
            'desde_contrato' =>  'Fecha desde contrato',
            'hasta_contrato' =>  'Fecha hasta contrato',
            'ubicaciones_autorizadas' =>  'Ubicacaciones autorizadas',
            'id_tipo_contrato' =>  'Tipo de Contrato',
            'desde_principal' =>  'Fecha desde Ubicación Principal',
            'dependencia_principal' =>  'Dependencia',
        ];

        $validator = Validador::validate((array)$this, $rules, $nombres);
        $validator->customErrors([
            'es_cuit'       => 'El campo <b>:attribute</b> ingresado no coincide con el documento.',
        ]);
        if ($validator->isSuccess()) {
            return true;
        } else {
            $this->errores = $validator->getErrors();
            return false;
        }
    }

    public function validarContrato()
    {   
        
        $inputs = (array)$this;
        $inputs['desde_contrato_anterior'] = $this->info_temporal['fecha_contrato_anterior'];
		$self = $this;        

        $rules = [
            'cargo' =>  ['required'],
            'id_tipo_contrato' =>  ['required'],
            'hasta_contrato'	=> ['required','fecha_menor2(:desde_contrato)' => function($input,$param1){
                $rta = false;
                if (!is_null($input) && !is_null($param1)) {
                    $rta =  ($input <= $param1) ? true : false;
                }
                return $rta;
            },'fecha_mayor_anterior(:desde_contrato_anterior)' => function($input,$param1){
                $rta = false;
                if (!is_null($input) && !is_null($param1)) {
                    $rta =  ($input > $param1) ? true : false;
                }
                return $rta;
            }],
            'desde_contrato'	=> ['required', 'fecha', 'fecha_nuevo' => function($input) use ($self){
				$rta = true;
				$sql = "SELECT fecha_hasta  FROM (
						SELECT * FROM empleado_contrato WHERE id_empleado = :id_empleado ORDER BY id DESC) AS f
						GROUP BY f.id_empleado;";
				$conex = new Conexiones();
				$res = $conex->consulta(Conexiones::SELECT, $sql, [':id_empleado' =>  $self->id]);
				if (empty($res)) {
					$rta =  true;
				}else {
					if(is_null($res[0]['fecha_hasta'])){
						$rta =  true;
					} else {
						$rta = ($res[0]['fecha_hasta'] <= $input) ? true : false;
					}
				}
				return $rta;
			}],
        ];

        if( $this->info_temporal['finalizar_contrato']){
            $rules = [
                'hasta_contrato'	=> ['required','fecha_mayor_anterior(:desde_contrato_anterior)' => function($input,$param1){
                    $rta = false;
                    if (!is_null($input) && !is_null($param1)) {
                        $rta =  ($input >= $param1) ? true : false;
                    }
                    return $rta;
                }]
            ];
        }

        $nombres    = [
            'hasta_contrato' =>  'Fecha hasta Contrato', 
            'desde_contrato' =>  'Fecha desde Contrato Actual',
            'id_tipo_contrato' =>  'Contrato Actual',
        ];

        $validator = Validador::validate($inputs, $rules, $nombres);
        $validator->customErrors([
            'required'      => ' Campo <b> :attribute </b> es requerido',
            'requerido'     => ' Campo <b> :attribute </b> es requerido',
            "fecha_menor2" => " Campo <b> :attribute </b> no puede ser mayor al campo <b>Fecha desde Contrato Nuevo</b>",
            "fecha_mayor_anterior" => " Campo <b> :attribute </b> no puede ser menor al campo <b>Fecha desde Contrato</b>",
            "fecha_nuevo"  => " La fecha de inicio del contrato no puede ser menor a la fecha de cierre del contrato anterior."
        ]);
        if ($validator->isSuccess()) {
            return true;
        } else {
            $this->errores = $validator->getErrors();
            return false;
        }
    }

    
    //_metodo_vista_tabla_base_
    static public function arrayToObject($res = [])
    {
        $campos    = [
            'id' =>  'int',
            'persona_id' =>  'int',
            'usuario_id' =>  'int',
            'ubicacion' =>  'int',
            'ubicaciones_autorizadas' =>  'string',
            'id_coped' =>  'string',
            'cuit' =>  'string',
            'email' =>  'string',
            'planilla_reloj' =>  'int',
            'oficina_contacto' =>  'string',
            'oficina_interno' =>  'int',
            'dependencia_principal' =>  'int',
            'desde_principal' =>  'date',
            'hasta_principal' =>  'date',
            'cargo' =>  'int',
            'id_tipo_contrato' =>  'int',
            'desde_contrato' =>  'date',
            'hasta_contrato' =>  'date',
            'horarios' =>  'string',
            'observacion' =>  'string',
            'documento' =>  'string',
            'nombre' =>  'string',
            'apellido' =>  'string',
            'genero' =>  'string',

            //'codep' =>  'string','ubicacionesHash' =>  'int','total_registro' =>  'int',
            //_CamposTipoDatoVista_
        ];
        $obj = new self();
        foreach ($campos as $campo => $type) {
            switch ($type) {
                case 'int':
                    $obj->{$campo}    = isset($res[$campo]) ? (int)$res[$campo] : null;
                    break;
                case 'json':
                    $obj->{$campo}    = isset($res[$campo]) ? json_decode($res[$campo], true) : null;
                    break;
                case 'datetime':
                    $obj->{$campo}    = isset($res[$campo]) ? \DateTime::createFromFormat('Y-m-d H:i:s', $res[$campo]) : null;
                    break;
                case 'date':
                    $obj->{$campo}    = isset($res[$campo]) ? \DateTime::createFromFormat('Y-m-d H:i:s', $res[$campo] . ' 0:00:00') : null;
                    break;
                default:
                    $obj->{$campo}    = isset($res[$campo]) ? $res[$campo] : null;
                    break;
            }
        }

        return $obj;
    }

    private static function hashingUbicaciones($idAutorizados)
    {
        sort($idAutorizados);
        $hashing = hash("md5", implode('', $idAutorizados));

        return $hashing;
    }

    /**
     * Se utiliza en Api::accion_acceso() la consulta es ejecutada por el Nodo/Reloj.
     * Si el empleado tiene contrato vigente y esta autorizado en la ubicacion, devuelve true.
     *
     * @param      integer   $ubicacion_id  ID de la ubicacion del reloj que consulta.
     * @return     boolean
     */
    public function puedeAcceder($ubicacion_id, $empleado = NULL)
    {
        $empleado = !empty($empleado) ? $empleado : $this;
 
        if (empty($empleado->desde_contrato) && empty($empleado->hasta_contrato)) {
            return false;
        }
        
        if ($ubicacion_id == $empleado->ubicacion) {
            return true;
        } else if (is_array(explode(',',$empleado->ubicaciones_autorizadas))) {
            foreach (explode(',',$empleado->ubicaciones_autorizadas) as $autorizada) {
                if ($ubicacion_id == $autorizada) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $documento
     * @return Empleado
     */
    public static function obtenerPorDocumento($documento)
    {

        if (!empty($documento)) {
            $sql = "SELECT e.id
					FROM empleados AS e
						JOIN personas AS p ON e.persona_id = p.id
					WHERE e.borrado = 0
								AND p.borrado = 0
								AND p.documento = :documento";
            $conex = new Conexiones();
            $res = $conex->consulta(Conexiones::SELECT, $sql, [':documento' => $documento]);

            if (!empty($res) && is_array($res) && count($res) > 0) {
                $empleado = static::obtener($res[0]['id']);
                if (!empty($empleado)) {
                    return $empleado;
                }
            }
            $empleado = static::obtener(null);
            $empleado->documento = $documento;

            return $empleado;
        }

        return new static;
    }

    /**
     * @param string $email
     * @return Empleado
     */
    public static function obtenerPorEmail($email)
    {
        if (!empty($email)) {
            $sql = "SELECT id FROM empleados WHERE email = :email AND borrado = 0";
            $conex = new Conexiones();
            $res = $conex->consulta(Conexiones::SELECT, $sql, [':email' => $email]);
            if (!empty($res) && is_array($res) && count($res) > 0) {
                return static::obtener($res[0]['id']);
            }
        }

        return static::obtener(null);
    }

    public static function estaEnrolado($persona_id)
    {
        $conex = new Conexiones();
        $sql = "SELECT count(*) AS cant FROM templates WHERE persona_id = :persona_id";
        $res = $conex->consulta(Conexiones::SELECT, $sql, [':persona_id' => $persona_id]);
        return ($res[0]['cant'] > 0) ? true : false;
    }

    /**
	 * @param int $usuario_id
	 * @return Empleado
	 */
	public static function obtenerPorIdDeUsuario($usuario_id) {
		$empleado = null;
		if (!empty($usuario_id)) {
			$sql = "SELECT e.id
					FROM empleados AS e
						JOIN empleado_x_usuario AS eu ON e.id = eu.empleado_id
					WHERE eu.usuario_id = :usuario_id AND borrado = 0";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, [':usuario_id' => $usuario_id]);
			if (!empty($res) && count($res) > 0) {
				$empleado = static::obtener($res[0]['id']);
				$empleado->usuario_id = (int)$res[0]['id'];
			}
		}

		return $empleado;
	}

    public static function obtenerPorEmailContrato($email) {
		$lista = [];
		if (!empty($email)){
			$sql = "SELECT e.id FROM empleados AS e
						INNER JOIN empleado_contrato AS ec ON e.id = ec.id_empleado
					                  AND ( ec.fecha_desde IS NOT NULL AND ec.fecha_hasta IS NULL )
					WHERE  e.email = :email AND e.borrado = 0";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, [':email' => $email]);
            foreach ($res as $re) {
				$lista[] = Empleado::obtener($re['id']);
			}
        }
		return $lista;
	}

    static public function anularValidacion(){
        static::$ANULAR_VALIDACION  = true;
    }
}