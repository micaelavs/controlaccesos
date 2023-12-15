<?php

namespace App\Modelo;

use App\Helper\Biometrica;
use App\Modelo\Modelo;
use App\Helper\Validador;
use App\Helper\Conexiones;
use FMT\Logger;

class Persona extends Modelo
{

	/** @var int */
	public $id;
	/** @var int */
	public $borrado;
	/**@var String**/
	public $documento;
	/**@var String**/
	public $nombre;
	/**@var String**/
	public $apellido;
	/**@var tinyint**/
	public $genero;
	/**Campo virtual autogenerado @var int**/
	public $tipo_persona = null;

	public $empleado;
	
	const NODEFINIDO	= 0;
	const FEMENINO		= 1;
	const MASCULINO		= 2;

	public static $TIPO_GENEROS = [
		['id' => self::NODEFINIDO,	'nombre'	=> 'No Definido'],
		['id' => self::FEMENINO,	'nombre'	=> 'Femenino'],
		['id' => self::MASCULINO,	'nombre'	=> 'Masculino'],
	];

	static $ANULAR_VALIDACION   = false;

	/**
	 * @param Persona $persona
	 * @return Persona
	 */
	public static function obtenerOAlta($persona) {
		if (!empty($persona->documento)) {
			$p = Persona::obtenerPorDocumento($persona->documento);
			if (!empty($p) && !empty($p->id) && $p->id > 0) {
				return $p;
			}
			if ($persona->alta()) {
				return $persona;
			}
		}

		return $persona;
	}


	public static function obtener($id = null)
	{
		$obj	= new static;
		if ($id === null) {
			return static::arrayToObject();
		}
		$sql_params	= [
			':id'	=> $id,
		];
		$campos	= implode(',', [
			'id', 'documento', 'nombre', 'apellido', 'genero', 'borrado'
		]);
		$sql	= <<<SQL
			SELECT {$campos}
			FROM personas 
			WHERE id = :id
SQL;
		$res	= (new Conexiones())->consulta(Conexiones::SELECT, $sql, $sql_params);
		if (!empty($res)) {
			return static::arrayToObject($res[0]);
		}
		return static::arrayToObject();
	}

	public static function listar()
	{
	}

	static public function listar_personas($params)
	{
		$campos    = 'id,documento, nombre, apellido, genero';
		$sql_params = [];

		$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo'])) ? 'tipo' : $params['order']['campo'];
		$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']))   ? 'asc' : $params['order']['dir'];
		$params['start']  = (!isset($params['start'])  || empty($params['start']))  ? 0 :
			$params['start'];
		$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght'])) ? 10 :
			$params['lenght'];
		$params['search'] = (!isset($params['search']) || empty($params['search'])) ? '' :
			$params['search'];

		$consulta = <<<SQL
        SELECT id,documento,nombre,apellido,genero FROM personas WHERE borrado=0
SQL;
		$data = self::listadoAjax($campos, $consulta, $params, $sql_params);
		return $data;
	}

	/**
	 * @param int $documento
	 * @return Persona
	 */
	static public function obtenerPorDocumento($documento)
	{
		$sql = "SELECT p.id, p.documento, p.nombre, p.apellido, p.genero,
                CASE 
                WHEN  emp.id >= 1 THEN :empleado 
                WHEN  v.visita_id >= 1 THEN :visita_enrolada
                END as tipo_persona 
                FROM personas AS p 
                LEFT JOIN empleados AS emp ON p.id = emp.persona_id AND emp.borrado = 0
                LEFT JOIN visitas AS v ON p.id = v.persona_id AND v.borrado = 0
                WHERE p.documento = :id AND p.borrado =0";
		$params = [
			':id' => $documento,
			':empleado' => 1, //Acceso::EMPLEADO, const EMPLEADO = 1;
			':visita_enrolada' => 4, //Acceso::VISITA_ENROLADA const VISITA_ENROLADA = 4;
		];
		if ($documento != 0) { //existe la pers
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
			if (!empty($res) && is_array($res) && isset($res[0])) {
				return static::arrayToObject($res[0]);
			}else{
				return static::arrayToObject();
			}
		} else {			
			return static::arrayToObject();
		}
	}

	public function alta()
	{
		$campos	= [
			'documento', 'nombre', 'apellido', 'genero'
		];
		$sql_params	= [];
		foreach ($campos as $campo) {
			$sql_params[':' . $campo]	= $this->{$campo};
		}

		$sql	= 'INSERT INTO personas(' . implode(',', $campos) . ') VALUES (:' . implode(',:', $campos) . ')';
		$res	= (new Conexiones())->consulta(Conexiones::INSERT, $sql, $sql_params);
		if ($res !== false) {
			$datos = (array) $this;
			$datos['modelo'] = 'personas';
			Logger::event('alta', $datos);
		}
		return $res;
	}

	public function modificacion()
	{
		$campos	= [
			'documento', 'nombre', 'apellido', 'genero'
		];
		$sql_params	= [
			':id'	=> $this->id,
		];
		foreach ($campos as $key => $campo) {
			$sql_params[':' . $campo]	= $this->{$campo};
			unset($campos[$key]);
			$campos[$campo]	= $campo . ' = :' . $campo;
		}
		
		$sql	= 'UPDATE personas SET ' . implode(',', $campos) . ' WHERE id = :id';
		$res	= (new Conexiones())->consulta(Conexiones::UPDATE, $sql, $sql_params);
		if ($res !== false) {
			$datos = (array) $this;
			// $datos['modelo'] = 'personas';
			// Logger::event('modificacion', $datos);
			return true;
		}
		return false;
	}

	public function baja()
	{
		$conexion = new Conexiones;
		$params = [':id' => $this->id];
		$sql = <<<SQL
		UPDATE personas SET  borrado = 1 WHERE id = :id
SQL;
		$res = $conexion->consulta(Conexiones::SELECT, $sql, $params);
		if ($res !== false) {
			$datos = (array) $this;
			$datos['modelo'] = 'personas';
			Logger::event('baja', $datos);
		} else {
			$datos['error_db'] = $conexion->errorInfo;
			Logger::event("error_baja", $datos);
		}
		return $res;
	}

	public function distribuirTemplates($ubicacion_id=null) {

		$resp = null;
		if (!empty($this->id) && !empty($this->documento)) {
			$data = [];
			$data['templates'] = $this->getTemplates();
			if($data['templates']) {

				$ubicacion = Ubicacion::obtener($ubicacion_id);
				$reloj = Reloj::obtenerPorUbicacion($ubicacion);
				foreach ($reloj as $cadaReloj){
				    $data['nodes'][] = $cadaReloj->nodo;
                }
				$resp = Biometrica::distribuir_templates($this->documento, $data);
			}else{
				$resp = true;
			}
		}

		return $resp;
	}

	public function actualizarTemplate($ubicacion_id=null) {
		if(!isset($ubicacion_id) || $ubicacion_id === false || $ubicacion_id === null)
			return false;
		if((empty($this->id) && $this->id !== 0) || empty($this->documento))
			return false;

		$return	= false;
		$data	= [
			'templates'	=> $this->getTemplates(),
			'nodes'		=> [],
		];
		$ubicacion_id=Ubicacion::obtener($ubicacion_id);
		foreach ((array)Reloj::obtenerPorUbicacion($ubicacion_id) as &$reloj){
			$data['nodes'][]	= $reloj->nodo;
		}

		$return	= Biometrica::distribuir_templates($this->documento, $data); // Devuelve un objeto, null o false.
		return  $return;
	}

	public function bajaEnEnrolador() {
		//esta funcion del proxy borra todos los templates y luego el usuario
		$resp = Biometrica::baja_enrolado($this->documento);
		return $resp;
	}

	public function validar()
	{
		if(static::$ANULAR_VALIDACION === true){
            static::$ANULAR_VALIDACION  = false;
            return true;
        }
		
		$rules = [
			'documento' =>  ['required','documento'],
			'nombre' =>  ['required','max_length(100)'],
			'apellido' =>  ['required','max_length(64)'],
		];
		$nombres	= [];

		$validator = Validador::validate((array)$this, $rules, $nombres);
		$validator->customErrors([
			'required'      => 'Campo <b>:attribute</b> es requerido',
			'requerido'     => 'Campo <b>:attribute</b> es requerido',
		]);
		if ($validator->isSuccess()) {
			return true;
		} else {
			$this->errores = $validator->getErrors();
			return false;
		}
	}


	static public function arrayToObject($res = [])
	{
		$campos	= [
			'id' =>  'int',
			'documento' =>  'string',
			'nombre' =>  'string',
			'apellido' =>  'string',
			'genero' =>  'int',
			'borrado' =>  'int',
			'tipo_persona' =>  'int',
		];

		$obj	= parent::arrayToObject($res, $campos);

		return $obj;
	}

	/**
	 * @return Template[]
	 */
	public function getTemplates() {
		return Template::listarPorPersona($this);
	}



	static public function obtenerPorTarjeta($tarjeta) {

		$sql = "SELECT p.id, p.documento, p.nombre, p.apellido, p.genero,
				CASE 
                WHEN  p.id >= 1 THEN :visita_tarjeta
                END as tipo_persona 
                FROM personas AS p
                INNER JOIN accesos_visitas AS av ON av.persona_id = p.id
				INNER JOIN credenciales AS c ON c.id = av.credencial_id
				WHERE c.codigo = :codigo AND c.tipo_acceso = :tipo_acceso_tm
				ORDER BY av.id DESC  ,c.id  DESC LIMIT 1";
		$params = [
		    ':codigo' => $tarjeta,
		    ':visita_tarjeta' => Acceso::VISITA_TARJETA_ENROLADA,
		    ':tipo_acceso_tm' => Acceso::TIPO_ACCESO_VISITA,
            ];
		if ($tarjeta != 0) { //existe la pers
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, $params);

			if (!empty($res) && is_array($res) && isset($res[0])) {
				return static::arrayToObject($res[0]);
			}

		} else {
			return static::arrayToObject();
		}

		return null;
	

	}

	static public function obtenerPorTarjetaContratista($tarjeta) {

		$sql = "SELECT p.id,p.documento,p.nombre,p.apellido,p.genero,
				       CASE
				         WHEN p.id >= 1 THEN :contratista_tarjeta
				       END AS tipo_persona
				FROM   credenciales c
				       INNER JOIN accesos_contratistas AS ac ON ac.credencial_id = c.id
				       INNER JOIN contratista_personal AS cp ON cp.id = ac.empleado_id
				       INNER JOIN personas AS p ON p.id = cp.persona_id
				WHERE  c.codigo = :codigo AND c.tipo_acceso = :tipo_acceso_tm
				ORDER  BY c.id DESC, ac.id DESC, cp.id DESC LIMIT 1";
		$params = [
		    ':codigo' => $tarjeta,
		    ':contratista_tarjeta' => Acceso::CONTRATISTA,
		    ':tipo_acceso_tm' => Acceso::TIPO_ACCESO_CONTRATISTA,
            ];
		if ($tarjeta != 0) { //existe la pers
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, $params);

			if (!empty($res) && is_array($res) && isset($res[0])) {
				return static::arrayToObject($res[0]);
			}
			
		} else {
			return static::arrayToObject();
		}

		return null;
	

	}

	public function getEmpleados() {
		if (!empty($this->empleado) && !empty($this->empleado->id)) {
			return $this->empleado;
		}
		if (!empty($this->id) && is_numeric($this->id) && $this->id > 0) {
			$sql = "SELECT id FROM empleados WHERE persona_id = :pid AND borrado= 0;";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, [':pid' => $this->id]);
			if (!empty($res) && isset($res[0])) {
				$this->empleado = Empleado::obtener($res[0]['id']);

				return $this->empleado;
			}
		}

		return Empleado::obtener(0);
	}

	static public function obtenerEmpleadoVisita($documento) {
		$empleado = Empleado::obtenerPorDocumento($documento);		
		if (empty($empleado->id)) {
			$visita = Visita::obtenerPorDocumento($documento);
			return $visita;
		}else{
			return $empleado;
		}
	}

	static public function anularValidacion(){
        static::$ANULAR_VALIDACION  = true;
    }
}