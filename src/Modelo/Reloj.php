<?php
	namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Biometrica;
use App\Helper\Validador;
use DateTime;
use FMT\Logger;
use FMT\Modelo;
use FMT\Usuarios;

class Reloj extends Modelo {


		/** @var int */
		public $id;
		/** @var string */
		public $ip;
		/** @var int */
		public $puerto;
		/** @var string */
		public $dns;
		/** @var string */
		public $numero_serie;
		/** @var string */
		public $marca;
		/** @var string */
		public $modelo;
		/** @var int */
		public $tipo_id;
		/** @var int */
		public $nodo;
		/** @var int */
		public $ubicacion_id;
		/** @var int */
		public $notas;
		/** @var TipoReloj */
		public $tipo_reloj;
		/** @var Ubicacion */
		public $ubicacion;
		/** @var bool */
		public $enrolador;
		/** @var \DateTime */
		public $ultima_marcacion;
		/** @var bool */
		public $acceso_restringido;
		/** @var bool */
    	public $acceso_tarjeta;


		const SINCRONIZACION_NI_EMPLEADO_NI_VISITA_ENROLADA = "P001";
		const ACCESO_NI_EMPLEADO_NI_VISITA_ENROLADA = "P002";
		const SIN_CONEXION = "1013";
		const TIMEOUT_CONNECTION = "1014";
		const CONEXION_EXITOSA = "1";
    	const OPERACION_EXITOSA = "2";
    	const RTA_CONSULTA = "2003";
    	const ERROR_SINCRONIZAR = "9001";
    	const ERROR_PROCESO = "9009";
		
		public static $CODIGOS_LOGS = [
			self::CONEXION_EXITOSA => 'Conexion exitosa',
			self::SIN_CONEXION =>'El estado de la conexion es incorrecto',
			self::TIMEOUT_CONNECTION => 'El tiempo para establecer la conexion expiro',
			self::SINCRONIZACION_NI_EMPLEADO_NI_VISITA_ENROLADA => 'Fichada de persona que no es empleado ni visita enrolada durante la sincronizacion',
			self::ACCESO_NI_EMPLEADO_NI_VISITA_ENROLADA => 'Fichada de persona que no es empleado ni visita enrolada en el acceso',
			self::OPERACION_EXITOSA => 'La operacion se pudo realizar con exito',
			self::RTA_CONSULTA => 'Respuesta de fichada',
			self::ERROR_SINCRONIZAR => 'Error al intentar sincronizar',
			self::ERROR_PROCESO => 'Error de proceso de registro',
		];

		/**
		 * Lista los relojes activos
		 *
		 * @return Reloj[]
		 */
		static public function listar() {
			$sql = <<<SQL
				SELECT
					id,
					ip,
					puerto,
					dns,
					numero_serie,
					marca,
					modelo,
					tipo_id,
					nodo,
					ubicacion_id,
					notas,
					enrolador,
					acceso_restringido,
					acceso_tarjeta
				FROM relojes
				WHERE borrado = 0;
				SQL;
			$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql);
			if (is_array($res)) {
				$list = [];
				foreach ($res as $re) {
						$list[] = self::arrayToObject($re);
				}

				return $list;
			}

			return [];
	}

	/**
		 * Lista los relojes activos
		 *
		 * @return Reloj[]
		 */
		static public function listarAjax($params=array()) {
			$sql_params = [];
			$campos	= 'id,ip,puerto,dns,numero_serie,marca,modelo,tipo_id,nodo,ubicacion,notas,enrolador,acceso_restringido,acceso_tarjeta';
			$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo']) ) ? 'tipo' :$params['order']['campo']; 
			$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']) )   ? 'asc' :$params['order']['dir'];
			$params['start']  = (!isset($params['start'])  || empty($params['start']) )  ? 0 :$params['start'];
			$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght']) ) ? 10 :$params['lenght'];
			$params['search'] = (!isset($params['search']) || empty($params['search']) ) ? '' :$params['search'];

			$consulta = <<<SQL
				SELECT
					relojes.id,
					ip,
					puerto,
					dns,
					numero_serie,
					marca,
					modelo,
					tipo_id,
					nodo,
					u.nombre as ubicacion,
					notas,
					enrolador,
					acceso_restringido,
					acceso_tarjeta
				FROM relojes
					LEFT JOIN ubicaciones AS u ON u.id = relojes.ubicacion_id
				WHERE relojes.borrado = 0
				SQL;
				$data = \App\Modelo\Modelo::listadoAjax($campos, $consulta, $params, $sql_params,['{{{REPLACE}}}'=>'*']);
				return $data;
	}

	/**
		 * Lista los sicronizaciones de Reloj activo
		 *
		 * @return Reloj[]
		 */
		static public function listarAjaxSincronizacion($params=array(),$nodo) {
			$sql_params = [
				':nodo' => $nodo
			];
			$campos	= 'id,nodo,totales,estado,fecha';
			$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo']) ) ? 'tipo' :$params['order']['campo']; 
			$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']) )   ? 'asc' :$params['order']['dir'];
			$params['start']  = (!isset($params['start'])  || empty($params['start']) )  ? 0 :$params['start'];
			$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght']) ) ? 10 :$params['lenght'];
			$params['search'] = (!isset($params['search']) || empty($params['search']) ) ? '' :$params['search'];

			$consulta = <<<SQL
				SELECT
				id, nodo, total as totales, estado, fecha
				FROM relojes_sincronizar_lotes
				WHERE nodo = :nodo
				SQL;
				$data = \App\Modelo\Modelo::listadoAjax($campos, $consulta, $params, $sql_params,['{{{REPLACE}}}'=>'*']);
				return $data;
	}

	/**
		 * Lista los historicos de Logs según Nodo
		 *
		 * @return Reloj[]
		 */
		static public function listarAjaxHistoricosLogsPorNodo($params=array(),$nodo) {
			$sql_params = [
				':nodo' => $nodo
			];
			$where = '';

			$campos	= 'id,cod_error,mensaje,fecha';
			$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo']) ) ? 'tipo' :$params['order']['campo']; 
			$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']) )   ? 'asc' :$params['order']['dir'];
			$params['start']  = (!isset($params['start'])  || empty($params['start']) )  ? 0 :$params['start'];
			$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght']) ) ? 10 :$params['lenght'];
			$params['search'] = (!isset($params['search']) || empty($params['search']) ) ? '' :$params['search'];
			$params['filtros'] = (!isset($params['filtros']) || empty($params['filtros']) ) ? null :$params['filtros'];

			if (!empty($params['filtros']['codigo'])) {
				$extra_where[] = "cod_error = :codigo";
				$sql_params[':codigo'] = $params['filtros']['codigo'];
			}
			if (!empty($params['filtros']['fecha_desde'])) {
				$extra_where[] = "(DATE(fecha) >= STR_TO_DATE(:fecha_desde, '%d/%m/%Y'))";
				$sql_params[':fecha_desde'] = $params['filtros']['fecha_desde'];
			}
			if (!empty($params['filtros']['fecha_hasta'])) {
				$extra_where[] = "(DATE(fecha) <= STR_TO_DATE(:fecha_hasta, '%d/%m/%Y'))";
				$sql_params[':fecha_hasta'] = $params['filtros']['fecha_hasta'];
			}

			if (!empty($extra_where)) {
				$where = ' AND ';
				$where .= implode(' AND ', $extra_where);
			}
			$consulta = <<<SQL
				SELECT
				id, cod_error, mensaje, DATE_FORMAT(fecha, '%d/%m/%Y %H:%i') as fecha
				FROM relojes_log as rl
				WHERE nodo = :nodo $where
				SQL;
			$data = \App\Modelo\Modelo::listadoAjax($campos, $consulta, $params, $sql_params,['{{{REPLACE}}}'=>'*']);
			return $data;
	}


	/**
		 * Lista los sicronizaciones de Reloj activo
		 *
		 * @return Reloj[]
		 */
		static public function listarAjaxSincronizacionMarcacion($params=array(),$lote) {
			$sql_params = [
				':id' => $lote
			];
			$campos	= 'id,nodo,fecha_marcacion,id_marcacion';
			$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo']) ) ? 'tipo' :$params['order']['campo']; 
			$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']) )   ? 'asc' :$params['order']['dir'];
			$params['start']  = (!isset($params['start'])  || empty($params['start']) )  ? 0 :$params['start'];
			$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght']) ) ? 10 :$params['lenght'];
			$params['search'] = (!isset($params['search']) || empty($params['search']) ) ? '' :$params['search'];

			$consulta = <<<SQL
				SELECT
				id_lote as id, nodo, fecha_marcacion , id_marcacion
				FROM relojes_sincronizar_marcaciones
				WHERE id_lote = :id
				SQL;

				$data = \App\Modelo\Modelo::listadoAjax($campos, $consulta, $params, $sql_params,['{{{REPLACE}}}'=>'*']);

				return $data;
	}


	/**
		 * Lista empleados con acceso restringido al nodo
		 *
		 * @return Reloj[]
		 */
		static public function listarAjaxAccesosRestringidos($params=array(),$id_reloj) {
			$sql_params = [
				':id' => $id_reloj
			];
			$campos	= 'id,nombre,documento,apellido,id_reloj,fecha_alta,fecha_ultima_modificacion';
			$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo']) ) ? 'tipo' :$params['order']['campo']; 
			$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']) )   ? 'asc' :$params['order']['dir'];
			$params['start']  = (!isset($params['start'])  || empty($params['start']) )  ? 0 :$params['start'];
			$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght']) ) ? 10 :$params['lenght'];
			$params['search'] = (!isset($params['search']) || empty($params['search']) ) ? '' :$params['search'];

			$consulta = <<<SQL
				SELECT
				ar.id, p.nombre, p.documento, p.apellido, ar.id_reloj, DATE_FORMAT(ar.fecha_alta, '%d/%m/%Y %H:%i') as fecha_alta, ar.fecha_ultima_modificacion
				FROM accesos_restringidos AS ar
				INNER JOIN personas AS p ON p.id = ar.id_persona AND p.borrado = 0
				WHERE ar.borrado = 0 AND ar.id_reloj = :id
				SQL;

				$data = \App\Modelo\Modelo::listadoAjax($campos, $consulta, $params, $sql_params);

				return $data;
	}

	public static function obtenerEstadoRelojes()
	{
			$json = (new Biometrica)->obtenerEstadoRelojes();
			return $json;
	}
	
	/**
     * @param array $res
     * @return Reloj
     */
    public static function arrayToObject($res = [])
    {
        $obj = new self();
        $obj->id = isset($res['id']) ? intval($res['id']) : 0;
        $obj->ip = isset($res['ip']) ? $res['ip'] : null;
        $obj->puerto = isset($res['puerto']) ? intval($res['puerto']) : 0;
        $obj->dns = isset($res['dns']) ? $res['dns'] : null;
        $obj->numero_serie = isset($res['numero_serie']) ? $res['numero_serie'] : null;
        $obj->marca = isset($res['marca']) ? $res['marca'] : null;
        $obj->modelo = isset($res['modelo']) ? $res['modelo'] : null;
        $obj->tipo_id = isset($res['tipo_id']) ? intval($res['tipo_id']) : 0;
        $obj->nodo = isset($res['nodo']) ? intval($res['nodo']) : 0;
        $obj->ubicacion_id = isset($res['ubicacion_id']) ? intval($res['ubicacion_id']) : 0;
        $obj->notas = isset($res['notas']) ? $res['notas'] : null;
        $obj->enrolador = isset($res['enrolador']) ? intval($res['enrolador']) : 0;
        $obj->tipo_reloj = TipoReloj::obtener($obj->tipo_id);
        $obj->ubicacion = Ubicacion::obtener($obj->ubicacion_id);
        $obj->ultima_marcacion = static::obtenerUltimaMarcacion($obj->nodo);
        $obj->acceso_restringido = isset($res['acceso_restringido']) ? intval($res['acceso_restringido']) : 0;
        $obj->acceso_tarjeta = isset($res['acceso_tarjeta']) ? intval($res['acceso_tarjeta']) : 0;
        return $obj;
    }


		 /**
			* Obtiene el reloj según el id.
     * @param $id
     * @return Reloj
     */
    static public function obtener($id)    
    {

			if($id == NULL){
        return static::arrayToObject();
      }
        $sql = <<<SQL
					SELECT
							r.id,
							r.ip,
							r.puerto,
							r.dns,
							r.numero_serie,
							r.marca,
							r.modelo,
							r.tipo_id,
							r.nodo,
							r.ubicacion_id,
							r.notas,
							r.enrolador,
							r.acceso_restringido,
							r.acceso_tarjeta
					FROM relojes AS r
					WHERE r.id = :id AND r.borrado = 0;
					SQL;
        $params = [':id' => $id];
				$conex = new Conexiones();
				$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
				if (!empty($res) && is_array($res) && isset($res[0])) {
						return static::arrayToObject($res[0]);
				}
				
      return static::arrayToObject();
    }

		/**
     * @param $src_node
     * @return Reloj
     */
    static public function obtenerPorNodo($src_node)
    {
			if($src_node == NULL){
        return static::arrayToObject();
      }
        $sql = <<<SQL
            SELECT
                r.id,
                r.ip,
                r.dns,
                r.puerto,
                r.numero_serie,
                r.marca,
                r.modelo,
                r.tipo_id,
                r.nodo,
                r.ubicacion_id,
                r.notas,
                r.enrolador,
                r.acceso_restringido,
                r.acceso_tarjeta
            FROM relojes AS r
            WHERE r.nodo = :src_node AND r.borrado = 0;
            SQL;
        $params = [':src_node' => $src_node];
				$conex = new Conexiones();
				$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
				if (!empty($res) && is_array($res) && isset($res[0])) {
						return static::arrayToObject($res[0]);
				}
        return static::arrayToObject();
    }


		/**
     * @param Ubicacion $ubicacion
     * @return Reloj[]
     */
    public static function obtenerPorUbicacion($ubicacion)
    {
			if(empty($ubicacion->id)){
        return [];
      }

			$sql = <<<SQL
					SELECT
							id,
							ip,
							dns,
							puerto,
							numero_serie,
							marca,
							modelo,
							tipo_id,
							nodo,
							ubicacion_id,
							notas,
							enrolador,
							acceso_restringido,
							acceso_tarjeta
					FROM relojes
					WHERE ubicacion_id = :ubicacion_id;
					SQL;
			// TODO: filtrar el reloj enrolador de la lista para producción dado que se supone que habrá mas de un reloj y el enrolador será solo con ese fin.
			$params[':ubicacion_id'] = $ubicacion->id;
			$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, $params);
			if (!empty($res) && is_array($res) && isset($res[0])) {
				$lista = [];
				foreach ($res as $re) {
						$lista[] = self::arrayToObject($re);
				}
				return $lista;
			}

			return [];
    }

		/**
     * @param int $nodo
     * @return DateTime|null
     */
    public static function obtenerUltimaMarcacion($nodo)
    {
        $conexiones = new Conexiones();
        $persona = Persona::obtenerPorDocumento($nodo);
        if (is_object($persona)) {
            $sql = 'SELECT MAX(fecha) fecha FROM
                        (SELECT max(hora_ingreso) as fecha FROM accesos WHERE persona_id_ingreso = :persona) as a UNION
                        (SELECT max(hora_egreso) as fecha FROM accesos WHERE persona_id_egreso = :persona) ';
            $max = $conexiones->consulta(Conexiones::SELECT, $sql, [':persona' => $persona->id]);
        }
        if (isset($max[0]['fecha'])) {
            return new DateTime($max[0]['fecha']);
        } else {
            return null;
        }
    }

	public static function obtenerRelojAccesoRestringido($id){
		$conexiones = new Conexiones();
        if (is_numeric($id)) {
            $sql = 'SELECT r.id, p.id as id_persona FROM accesos_restringidos as ar 
					INNER JOIN relojes as r ON r.id = ar.id_reloj 
					INNER JOIN personas as p ON p.id = ar.id_persona
					WHERE ar.id = :id AND ar.borrado = 0';
            $query = $conexiones->consulta(Conexiones::SELECT, $sql, [':id' => $id]);
        }
        if (!empty($query) && is_array($query) && isset($query[0])) {
			$res[] = self::obtener($query[0]["id"]);
			$res[] = Persona::obtener($query[0]["id_persona"]);
            return $res;
        } 
        return null;
	}

	public static function bajaAccesoRestringido($id){
		$conn = new Conexiones();
        $sql = "UPDATE accesos_restringidos SET borrado = 1 WHERE id = :id";
        $resultado = $conn->consulta(Conexiones::UPDATE, $sql, [':id' => $id]);
        if ($resultado !== false) {
            //Log
            $datos = (array)$id;
            $datos['modelo'] = 'accesos_restringidos';
            Logger::event('baja', $datos);
            return $resultado > 0;
        }

        return false;
	}


		/**
     * @return bool
     */
    public function alta()
    {

				$sql = "INSERT INTO relojes
									(ip, puerto, dns, numero_serie, marca, modelo, tipo_id, nodo, ubicacion_id, notas, acceso_restringido, acceso_tarjeta)
								VALUE
									(:ip, :puerto, :dns, :numero_serie, :marca, :modelo, :tipo_id, :nodo, :ubicacion_id, :notas, :acceso_restringido, :acceso_tarjeta);";
				$conex = new Conexiones();
				$res = $conex->consulta(Conexiones::INSERT, $sql,
						[
								':ip' => $this->ip,
								':puerto' => $this->puerto,
								':dns' => $this->dns,
								':numero_serie' => $this->numero_serie,
								':marca' => $this->marca,
								':modelo' => $this->modelo,
								':tipo_id' => $this->tipo_id,
								':nodo' => $this->nodo,
								':ubicacion_id' => $this->ubicacion_id,
								':notas' => $this->notas,
								':acceso_restringido' => $this->acceso_restringido,
								':acceso_tarjeta' => $this->acceso_tarjeta
						]);
				if (!empty($res) && is_numeric($res) && $res > 0) {
						$this->id = $res;
						//Log
						$datos = (array)$this;
						$datos['modelo'] = 'reloj';
						Logger::event('alta', $datos);
						$this->crearPersona();
				}
        return $res;
    }


		  /**
     * @return bool
     */
    public function validar()
    {
        $inputs = [
            'numero_serie' => $this->numero_serie,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'tipo_id' => $this->tipo_id,
            'ip' => $this->ip,
            'puerto' => $this->puerto,
            'nodo' => $this->nodo,
            'ubicacion_id' => $this->ubicacion_id,
            'dns' => $this->dns,
        ];
        $rules = [
            'numero_serie' => ['required'],
            'marca' => ['required'],
            'modelo' => ['required'],
            'tipo_id' => ['required'],
            'ip' => ['required', 'formatoValidoIP' => function ($input) {
                   	if(filter_var($input, FILTER_VALIDATE_IP)) {
  						return true;
					}else {
  						return false;
                   }
                }],
            'puerto' => ['required', 'numeric', 'max_length(5)'],
            'nodo' => ['required'],
            'dns' => ['required', 'max_length(20)'],
            'ubicacion_id' => ['required'],
        ];
        $naming = [
            'numero_serie' => "Número de Serie",
            'ip' => "Dirección de IP",
            'puerto' => "Puerto de Comunicación",
            'marca' => "Marca",
            'modelo' => "Modelo",
            'tipo_id' => "Tipo de Equipo",
            'nodo' => "Número de Nodo",
            'ubicacion_id' => "Ubicación",
            'dns' => 'DNS'
        ];
        $validator = Validador::validate($inputs, $rules, $naming);
        $validator->customErrors([
            'formatoValidoIP' => 'Dirección IP ingresada no válida.'
        ]);
        if ($validator->isSuccess()) {
            return true;
        }
        $this->errores = $validator->getErrors();

        return false;
    }

    /**
     * @return bool
     */
    public function baja()
    {
        $conn = new Conexiones();
        $sql = "UPDATE relojes SET borrado = 1 WHERE id = :id";
        $resultado = $conn->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
        if ($resultado !== false) {
            //Log
            $datos = (array)$this;
            $datos['modelo'] = 'reloj';
            Logger::event('baja', $datos);

            return $resultado > 0;
        }

        return false;
    }


		/**
     * @return bool
     */
    public function enrolar()
    {
        $conn = new Conexiones();
         $sql = <<<SQL
						UPDATE relojes
						SET enrolador = 0
						WHERE enrolador = 1;
						SQL;
        $conn->consulta(Conexiones::UPDATE, $sql);

         $sql = <<<SQL
            UPDATE relojes
            SET enrolador = 1
            WHERE id = :id;
            SQL;
        $resultado = $conn->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
        
				if ($resultado !== false) {
            //Log
            $datos = (array)$this;
            $datos['modelo'] = 'reloj';
            Logger::event('enrolado', $datos);
            return $resultado > 0;
        }

        return false;
    }

		/**
     * @return bool
     */
    public function modificacion()
    {
				$sql = <<<SQL
					UPDATE relojes
					SET
							ip           = :ip,
							puerto       = :puerto,
							dns          = :dns,
							numero_serie = :numero_serie,
							marca        = :marca,
							modelo       = :modelo,
							tipo_id      = :tipo_id,
							nodo         = :nodo,
							ubicacion_id = :ubicacion_id,
							notas        = :notas,
							enrolador    = :enrolador,
							acceso_restringido = :acceso_restringido,
							acceso_tarjeta = :acceso_tarjeta
					WHERE id = :id;
					SQL;
				$conex = new Conexiones();
				
				$res = $conex->consulta(Conexiones::UPDATE, $sql,
						[
								':id' => $this->id,
								':ip' => $this->ip,
								':puerto' => $this->puerto,
								':dns' => $this->dns,
								':numero_serie' => $this->numero_serie,
								':marca' => $this->marca,
								':modelo' => $this->modelo,
								':tipo_id' => $this->tipo_id,
								':nodo' => $this->nodo,
								':ubicacion_id' => $this->ubicacion_id,
								':notas' => $this->notas,
								':enrolador' => $this->enrolador,
								':acceso_restringido' => $this->acceso_restringido,
								':acceso_tarjeta' => $this->acceso_tarjeta
						]);
				if (is_numeric($res)) {
						//Log
						$datos = (array)$this;
						$datos['modelo'] = 'reloj';
						Logger::event('modificacion', $datos);
						$this->crearPersona();
					
						return true;
				}
				
        return false;
    }

		public function actualizarTemplates(){

			$update_reloj = $this->actualizarPersonas();
			return $update_reloj;
		}

    public static function obtenerEnrolador()
    {
        $sql = 'SELECT * FROM relojes WHERE enrolador=1 AND borrado = 0';
        $res = (new Conexiones())->consulta(Conexiones::SELECT, $sql);
        if ($res) {
            return static::arrayToObject($res[0]);
        }
        return static::arrayToObject();
    }



	protected function crearPersona()
    {
        $persona = Persona::obtenerPorDocumento($this->nodo);
        if (!$persona) {
            $persona = Persona::obtener(0);
            $persona->documento = $this->nodo;
            $persona->nombre = 'Reloj';
            $persona->apellido = 'Biométrico';
            $persona->genero = 0;
            $persona->alta();
        }
    }

	static public function listarRelojes_TM() {
        $str = "SELECT * FROM relojes WHERE borrado = 0 AND acceso_tarjeta = 1";
        $res = (new Conexiones)->consulta(Conexiones::SELECT, $str);
        $lista = [];
        if (!empty($res) && is_array($res)) {
            foreach ($res as $re) {
                $lista[] = (array)static::arrayToObject($re);
            }
        }

        return $lista;
    }

		/**
     * Actualiza en el nodo el listado de personas autorizados en la misma ubicacion.
     *
     * A partir de **ubicacion_id** trae la lista de personas que tienen templates cargados.
     * Luego construye la query para enviar al nodo
     *
     * @return boolean|array
     */
    public function actualizarPersonas()
    {
        if (empty($this->id)){
					return false;
				}
        $sql = <<<SQL
					(SELECT
								per.id  AS id,
								emp_ubic.ubicacion_id AS ubicacion_id,
								per.documento AS documento
							FROM empleados_x_ubicacion AS emp_ubic
							INNER JOIN empleados AS emp ON (emp.id = emp_ubic.empleado_id AND emp.borrado = 0)
							INNER JOIN personas AS per ON (
									per.id = emp.persona_id
									AND per.borrado = 0
									AND per.id IN (
											SELECT DISTINCT persona_id FROM templates WHERE ubicacion_id = :ubicacion_id
									))
							WHERE
									emp_ubic.ubicacion_id = :ubicacion_id)
									UNION ALL
									(   SELECT
									per.id  AS id,
									vis_ubic.ubicacion_id AS ubicacion_id,
									per.documento AS documento
							FROM visitas AS vis_ubic            
							INNER JOIN personas AS per ON (
									per.id = vis_ubic.persona_id
									AND per.borrado = 0
									AND per.id IN (
											SELECT DISTINCT persona_id FROM templates WHERE ubicacion_id = :ubicacion_id
									))
					WHERE ubicacion_id = :ubicacion_id)
					SQL;
        $personas   = (new Conexiones())->consulta(Conexiones::SELECT, $sql, [':ubicacion_id' => $this->ubicacion_id]);

        if(empty($personas)){
					return false;
				}

        $return = [];
        $logger_datos = [
            'modelo'    => 'reloj',
            'reloj_id'  => $this->id,
            'nodo_id'   => $this->nodo,
        ];

        $time_inicio    = time();

        foreach ((array)$personas as &$persona) {
			// usleep(200);
			/**
			 * Este proceso puede demorar mucho, lanza infinitos request contra CAP y tira timeout... sin embargo pareciera que en algun momento se completa la tarea, luego de 30 minutos o 1hs.
			 * Lo realmente malo es que durante ese tiempo el reloj queda en estado "Sincronizando"
			 */
					$data   = [
						'templates' => Template::listarPorPersona($persona),
						'nodes'     => [$this->nodo],
					];
            $resp   = Biometrica::distribuir_documentos($data,$persona['documento']); // Devuelve un objeto, null o false.;
						$return[] = isset($resp["status"]) && ($resp["status"]) ? 1 : 0;
        }

        $logger_datos['transcurrido']   = (time() - $time_inicio);
				$exitosos = array_filter( $return, function( $v ) { return ($v != 0); } );

        $logger_datos['registros']  = count($return);
        $logger_datos['exitos']     = count($exitosos);
        Logger::event('actualizar_personas', $logger_datos);

        return $logger_datos;
    }

		public static function listar_log($nodo) {
			$sql = "SELECT cod_error, mensaje, fecha FROM relojes_log WHERE nodo = :nodo ORDER BY id DESC LIMIT 5;";

			$conex = new Conexiones();

			$res = $conex->consulta(Conexiones::SELECT, $sql,
					[
							':nodo'      => $nodo
					]);

			return $res;

		}

		public static function listar_log_filtro($nodo, $filtros, $limit=false) {

			$where = '';
			$extra_where = [];
			$select = "SELECT id, cod_error, mensaje, DATE_FORMAT(fecha, '%d/%m/%Y %H:%i') as fecha FROM relojes_log as rl";

			if ($nodo){
					$where = " WHERE rl.nodo = :nodo ";
					$params['nodo'] = $nodo;
			}
			if (!empty($filtros['codigo'])) {
					$extra_where[] = "rl.cod_error = :codigo";
					$params[':codigo'] = $filtros['codigo'];
			}
			if (!empty($filtros['fecha_desde'])) {
					$extra_where[] = "(DATE(rl.fecha) >= STR_TO_DATE(:fecha_desde, '%d/%m/%Y'))";
					$params[':fecha_desde'] = $filtros['fecha_desde'];
			}
			if (!empty($filtros['fecha_hasta'])) {
					$extra_where[] = "(DATE(rl.fecha) <= STR_TO_DATE(:fecha_hasta, '%d/%m/%Y'))";
					$params[':fecha_hasta'] = $filtros['fecha_hasta'];
			}

			if (!empty($extra_where)) {
					if (empty($where)) {
							$where = " WHERE ";
					} else {
							$where .= ' AND ';
					}
					$where .= implode(' AND ', $extra_where);
			}
			$order = " ORDER BY rl.fecha desc";
			$sql = $select . $where. $order;
			if ($limit) {
					$limit = " LIMIT {$limit}";
					$sql .= $limit;
			}
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
			return $res;
		}

		public static function guardar_log($nodo, $cod_error, $mensaje) {
			$sql = "INSERT INTO relojes_log (nodo, cod_error, mensaje) VALUE (:nodo, :cod_error, :mensaje);";
					$conex = new Conexiones();
					$res = $conex->consulta(Conexiones::INSERT, $sql,
							[
									':nodo'      => $nodo,
									':cod_error' => $cod_error,
									':mensaje'   => $mensaje,

							]);
			return $res;
		}


		public static function ajax($order, $start, $length, $filtros, $extras = [], $nodo) {
			$con = new Conexiones();

			$mapeo_campos   = [
					'id'                => 'rl.id',
					'codigo' => 'rl.cod_error',
					'nodo'      => 'rl.nodo',
					'mensaje'       => 'rl.mensaje',
					'fecha'     => 'DATE_FORMAT(rl.fecha, "%d/%m/%Y %H:%i")'
			];

			$select = <<<SQL
				SELECT rl.cod_error as codigo, rl.mensaje as mensaje, DATE_FORMAT(rl.fecha, '%d/%m/%Y %H:%i') as fecha
				SQL;

			$from = <<<SQL
			FROM relojes_log AS rl
			SQL;

			$where = <<<SQL
			WHERE rl.nodo = :nodo
			SQL;

			$params[':nodo'] = $nodo;

			$counter_query = "SELECT COUNT(DISTINCT id) AS total {$from}";

			$recordsTotal = $recordsFiltered = $con->consulta(Conexiones::SELECT, $counter_query. $where, $params)[0]['total'];

			if ($filtros) {
					$filtro = [];
					foreach (explode(' ', $filtros) as $index => $value) {
							$filtro[] = <<<SQL
														(
															rl.cod_error LIKE :filtro{$index} OR
															DATE_FORMAT(rl.fecha, '%d/%m/%Y') LIKE :filtro{$index}
														)
														SQL;
							$params[":filtro{$index}"] = "%{$value}%";
					}
					$where = " WHERE " . implode(' AND ', $filtro);
			}
			$extra_where = [];

			if (!empty($extras['codigo'])) {
					$extra_where[] = "(rl.cod_error = :codigo)";
					$params[':codigo'] = $extras['codigo'];
			}


			if (!empty($extras['fecha_ini'])) {
					$extra_where[] = "(DATE(rl.fecha) >= STR_TO_DATE(:fecha_ini, '%d/%m/%Y'))";
					$params[':fecha_ini'] = $extras['fecha_ini'];
			}
			if (!empty($extras['fecha_fin'])) {
					$extra_where[] = "(DATE(rl.fecha) <= STR_TO_DATE(:fecha_fin, '%d/%m/%Y')";
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

			$sql = $select . $from . $where;
			if ($order) {
					$orders         = [];
					foreach ($order as $i => $val) {
							if(!empty($val['campo']) && !empty($val['dir']))
									$orders[]   = "{$mapeo_campos[$val['campo']]} {$val['dir']}";
					}
					$sql            .= $orders = " ORDER BY " . implode(',', $orders) . " ";
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
					'data'            => $con->consulta(Conexiones::SELECT, $sql, $params)
			];
		}

		public static function sincronizar_lotes_alta($nodo = false, $total = 0)
    {
			if ($nodo) {
					$sql = "INSERT INTO relojes_sincronizar_lotes (nodo, total, estado) VALUE (:nodo, :total, :estado);";
					$conex = new Conexiones();
					$res = $conex->consulta(Conexiones::INSERT, $sql, [
							':nodo' => $nodo,
							':total' => $total,
							':estado' => 'Iniciando guardado de marcaci'
					]);
					return $res;
			}
			return false;

    }


		public static function sincronizar_marcaciones_alta($nodo, $id_lote, $marcaciones = [])
    {
			$conex = new Conexiones();
			$conex->beginTransaction();
			foreach ($marcaciones as $fecha => $id_acceso) {
					$sql = "INSERT INTO relojes_sincronizar_marcaciones (nodo, id_lote, fecha_marcacion , id_marcacion) VALUE (:nodo, :id_lote, :fecha_marcacion, :id_marcacion);";
					$res = $conex->consulta(Conexiones::INSERT, $sql,
							[
									':nodo' => $nodo,
									':id_lote' => $id_lote,
									':fecha_marcacion' => $fecha,
									':id_marcacion' => $id_acceso
							]);
					if (!$res) {
							$conex->rollback();
							return false;
					}
			}
			$conex->commit();
			return true;
    }

		public static function sincronizar_marcaciones_borrar($nodo)
    {
			return Biometrica::sincronizar_marcaciones_borrar($nodo);
    }

		public function alta_daemon() {
			$data	= [
					'ip'	            => $this->ip,
				'dns'		        => $this->dns,
					'puerto'		    => $this->puerto,
					'enrolador'	        => $this->enrolador,
					'id'		        => $this->id,
					'nodo'	            => $this->nodo,
					'ultima_marcacion'  => date('Y-m-d H:i:s'),
					'operacion'  => 'alta',
			];
			$return = Biometrica::alta_daemon($data);
			return  $return;
		}

		public function recargar_daemon() {

			$data	= [
				'ip'	            => $this->ip,
				'dns'		        => $this->dns,
				'puerto'		    => $this->puerto,
				'enrolador'	        => $this->enrolador,
				'id'		        => $this->id,
				'nodo'	            => $this->nodo,
				'ultima_marcacion'  => date('Y-m-d H:i:s')
			];
			$return = Biometrica::recargar_daemon($data);
			return  $return;

		}

		public function alta_acceso_restringido($persona){

			if (!empty($persona)) {
				$usuario = Usuario::obtener($_SESSION['iu']);
				$operador = $usuario->getEmpleado();
				$sql = "INSERT INTO accesos_restringidos (id_persona, id_reloj, id_persona_operador) VALUE (:id_persona, :id_reloj, :id_persona_operador)";
					$conex = new Conexiones();
					$res = $conex->consulta(Conexiones::INSERT, $sql, [
							':id_persona' => $persona->id,
							':id_reloj' => $this->id,
							':id_persona_operador' => $operador->persona_id
					]);
					return $res;
			}
			return false;
		}
	
	/**
     * Registra un mensaje de estado para saber cuando inicia o finaliza la sincronizacion de fichadas biometricas
     *
     * @param int $id_lote
     * @param string $estado
     * @return int|bool
     */
    public static function sincronizar_lotes_modificar($id_lote, $estado)
    {
        $sql = "UPDATE relojes_sincronizar_lotes SET estado = :estado WHERE id= :id;";
        $conex = new Conexiones();
        $res = $conex->consulta(Conexiones::UPDATE, $sql, [
            ':id' => $id_lote,
            ':estado' => $estado
        ]);
        return $res;
    }

	/**
     * Obtiene lista de marcaciones para un "lote" (momento) determinado
     *
     * @param int $id_lote
     * @return array
     */
    public static function sincronizar_marcaciones_listar($id_lote)
    {
        $sql = "SELECT id_lote, nodo, fecha_marcacion , id_marcacion FROM relojes_sincronizar_marcaciones WHERE id_lote = :id_lote;";

        $conex = new Conexiones();

        $res = $conex->consulta(Conexiones::SELECT, $sql,
            [
                ':id_lote' => $id_lote
            ]);

        return $res;

    }
}