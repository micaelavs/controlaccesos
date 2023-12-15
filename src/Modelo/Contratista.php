<?php
namespace App\Modelo;

use App\Modelo\Modelo;
use App\Helper\Validador;
use App\Helper\Conexiones;
use FMT\Logger;
use App\Helper\Util;
/**
 * Class Contratista
 */
class Contratista extends Modelo {
	/** @var int */
	public $id = 0;
	/** @var string */
	public $nombre = null;
	/** @var string */
	public $cuit = null;
	/** @var string */
	public $direccion = null;
	/** @var Localidad */
	//public $localidad = null;
	/** @var int */
	public $localidad_id = 0;
	/** @var Provincia */
	//public $provincia = null;
	/** @var int */
	public $provincia_id = 0;

	public $edit = false;


	public static function listar() {
		$conex = new Conexiones();

		return $conex->consulta(Conexiones::SELECT, static::sql());
	}

    static public function listar_contratistas($params)
	{
		$campos    = 'id,nombre,cuit,direccion,provincia_id,provincia,localidad_id';
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
        		SELECT
					c.id     AS id,
					c.nombre AS nombre,
					cuit,
					direccion,
					p.id     AS provincia_id,
					p.nombre AS provincia,
					l.id     AS localidad_id,
					(case c.localidad_id when 0 then 'Otra' else l.nombre end) AS localidad
				FROM contratistas AS c
					LEFT JOIN provincias AS p ON c.provincia_id = p.id
					LEFT JOIN localidades AS l ON c.localidad_id = l.id
				WHERE borrado = 0
SQL;

		$data = self::listadoAjax($campos, $consulta, $params, $sql_params);
		return $data;
	}

	/**
	 * @param $extra
	 * @return string
	 */
	protected static function sql($extra = '') {
		return "SELECT
					c.id     AS id,
					c.nombre AS nombre,
					cuit,
					direccion,
					p.id     AS provincia_id,
					p.nombre AS provincia,
					l.id     AS localidad_id,
					(case c.localidad_id when 0 then 'Otra' else l.nombre end) AS localidad
				FROM contratistas AS c
					LEFT JOIN provincias AS p ON c.provincia_id = p.id
					LEFT JOIN localidades AS l ON c.localidad_id = l.id
				WHERE borrado = 0" . $extra;
	}

	/**
	 * @param int|string $id ID o CUIT
	 * @return Contratista
	 */
	public static function obtener($id) {
		$sql = static::sql(" AND (c.id = :id OR cuit = :id)");
		if (is_numeric($id)) {
			if ($id > 0) {
				$params = [':id' => $id];
				$conex = new Conexiones();
				$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
				if (!empty($res) && is_array($res) && isset($res[0])) {
					
					return static::arrayToObject($res[0]);
				}
			} 
		}
		return static::arrayToObject();
			
	}

	/**
	 * @param $res
	 * @return Contratista
	 */
	// private static function arrayToObject($res = []) {
	// 	$obj = new self();
	// 	$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
	// 	$obj->nombre = isset($res['nombre']) ? $res['nombre'] : null;
	// 	$obj->cuit = isset($res['cuit']) ? $res['cuit'] : null;
	// 	$obj->direccion = isset($res['direccion']) ? $res['direccion'] : null;
	// 	$obj->provincia_id = isset($res['provincia_id']) ? (int)$res['provincia_id'] : 0;
	// 	$obj->provincia = Provincia::obtener($obj->provincia_id);
	// 	$obj->localidad_id = isset($res['localidad_id']) ? (int)$res['localidad_id'] : 0;
	// 	$obj->localidad = Localidad::obtener($obj->localidad_id);

	// 	return $obj;
	// }

    static public function arrayToObject($res = [])
    {
        $campos    = [
            'id' =>  'int',
            'nombre' =>  'string',
            'cuit' =>  'string',
            'direccion' =>  'string',
            'provincia_id' =>  'string',
            //'provincia' =>  'string',
            'localidad_id' =>  'string',
            //'localidad' =>  'string',
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

	public function alta() {
		if ($this->validar()) {
			$sql = "INSERT INTO contratistas (nombre, cuit, direccion, provincia_id, localidad_id) VALUE (:nombre, :cuit, :direccion, :provincia_id, :localidad_id)";
			$conex = new Conexiones();
			$params = [
				//"nombre"       => mb_strtoupper($this->nombre, 'UTF-8'),
				"nombre"       => $this->nombre,
				"cuit"         => (int)$this->cuit,
				"direccion"    => $this->direccion,
				"provincia_id" => $this->provincia_id,
				"localidad_id" => $this->localidad_id,
			];
			$res = $conex->consulta(Conexiones::INSERT, $sql, $params);
			if (is_numeric($res) && $res > 0) {
				//Log
				// $datos = (array)$this;
				// $datos['modelo'] = 'contratista';
				// Logger::event('alta', $datos);

				return true;
			}
		}

		return false;
	}

	public function validar() {
		$reglas = [
			'cuit'         => ['required','cuit','unico(contratistas,cuit,'.$this->id.','.null.',\'0\')'],
			'nombre'       => ['required', 'texto', 'min_length(2)', 'max_length(64)'],			
			'direccion'    => ['required', 'texto', 'max_length(64)'],
			'provincia_id' => ['required', 'numeric'],
			'localidad_id' => ['localidad'=>function($input){
				return is_numeric($input);
			}],
			
		];
		$validator = Validador::validate((array)$this, $reglas, ['localidad_id' => 'Localidad','provincia_id' => 'Provincia']); 
		$validator->customErrors([
			'localidad'=>'La localidad no existe',
			'cuit' => 'El número de CUIT ingresado no es válido.',
			'unico' => 'El número de CUIT ingresado ya existe.'
		]);
		if ($validator->isSuccess()) {
    	   return true;
   		 } 
   		 else {
    		  $this->errores = $validator->getErrors();
     		 return false;
   		 }
	}

	public function baja() {
		if (is_numeric($this->id) && $this->id > 0) {
			$sql = "UPDATE contratistas SET borrado = 1 WHERE id = :id";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
			if (is_numeric($res) && $res > 0) {
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'contratista';
				Logger::event('baja', $datos);

				return true;
			}
			$this->errores = ['error' => "No se pudo eliminar la contratista " . $this->nombre];
		}

		return true;
	}

	public function modificacion() {
	$this->edit = true;
		if ($this->validar()) {
			$sql = "UPDATE contratistas SET 
						nombre = :nombre, 
						cuit = :cuit, 
						direccion = :direccion, 
						provincia_id = :provincia_id, 
						localidad_id = :localidad_id
					WHERE id = :id";
			$conex = new Conexiones();
			$params = [
				"id"           => $this->id,
//				"nombre"       => mb_strtoupper($this->nombre, 'UTF-8'),
				"nombre"       => $this->nombre,
				"cuit"         => $this->cuit,
				"direccion"    => $this->direccion,
				"provincia_id" => $this->provincia_id,
				"localidad_id" => $this->localidad_id,
			];
			$res = $conex->consulta(Conexiones::UPDATE, $sql, $params);
			if (is_numeric($res) && $res > 0) {
				//Log
				// $datos = (array)$this;
				// $datos['modelo'] = 'contratista';
				// Logger::event('modificacion', $datos);

				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $sql
	 * @param array  $extra
	 * @return bool
	 */
	protected function guardar($sql, $extra = []) {
		$conex = new Conexiones();
		$params = [
				"nombre"       => $this->nombre,
				"cuit"         => $this->cuit,
				"direccion"    => $this->direccion,
				"provincia_id" => $this->provincia_id,
				"localidad_id" => $this->localidad_id,
			] + $extra;
		$res = $conex->consulta(Conexiones::INSERT, $sql, $params);

		return !empty($res) && $res > 0;
	}
}