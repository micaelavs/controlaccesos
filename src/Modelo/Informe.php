<?php
namespace App\Modelo;

use FMT\Modelo;
use App\Helper\Validador;
use App\Helper\Conexiones;
use FMT\Logger;
use App\Modelo\Empleado;

class Informe extends Modelo {
/** @var int */
	public $id;
/** @var int */
	public $borrado;
/** @var int */
	public $empleado_id;
/** @var int */
	public $empleado_logueado_id;
/** @var DateTime */
	public $fecha_ultimo_envio;
/** @var Array */
	public $dependencias;
/** @var Array */
	public $contratos;
/** @var Empleado:: */
	public $empleado;

	static public function obtener($id=null){
		$obj	= new static;
		if($id===null){
			return static::arrayToObject();
		}
		$sql_params	= [
			':id'	=> $id,
		];
		$campos	= implode(',', [
			'id',
			'empleado_id',
			'empleado_logueado_id',
			'fecha_ultimo_envio',
			'dependencias',
			'contratos',
			'borrado',
		]);
		$sql	= <<<SQL
			SELECT {$campos}
			FROM informes_configuracion
			WHERE id = :id AND borrado = 0
SQL;
		$res	= (new Conexiones())->consulta(Conexiones::SELECT, $sql, $sql_params);
		if(!empty($res)){
			return static::arrayToObject($res[0]);
		}
		return static::arrayToObject();
	}

	static public function listar() {
		$campos	= implode(',', [
			'empleado_id',
			'empleado_logueado_id',
			'fecha_ultimo_envio',
			'dependencias',
			'contratos',
		]);
		$sql	= <<<SQL
			SELECT id, borrado, {$campos}
			FROM informes_configuracion
			WHERE borrado = 0
			ORDER BY id DESC
SQL;
		$resp	= (array)(new Conexiones())->consulta(Conexiones::SELECT, $sql);
		if(empty($resp)) { return [static::arrayToObject()]; }
		foreach ($resp as &$value) {
			$value	= static::arrayToObject($value);
		}
		return $resp;
	}

	public function alta(){
		if(!$this->validar()){
			return false;
		}
		$campos	= [
			'empleado_id',
			'empleado_logueado_id',
			'fecha_ultimo_envio',
			'dependencias',
			'contratos',
		];
		$sql_params	= [
		];
		foreach ($campos as $campo) {
			$sql_params[':'.$campo]	= $this->{$campo};
		}

		if($this->fecha_ultimo_envio instanceof \DateTime){
			$sql_params[':fecha_ultimo_envio']	= $this->fecha_ultimo_envio->format('Y-m-d H:i:s');
		}
		$this->contratos			= array_map(function($id){ return (int)$id; }, $this->contratos);
		$sql_params[':contratos']	= (sort($this->contratos))
									? json_encode($this->contratos)
									: null;
		$this->dependencias			= array_map(function($id){ return (int)$id; }, $this->dependencias);
		$sql_params[':dependencias']= (sort($this->dependencias))
									? json_encode($this->dependencias)
									: null;
//		$tt =  [7,'8',null,'"1=1 --']; $lll	= (sort($tt = array_map(function($id){ return (int)$id; }, $tt))) ? json_encode($tt) : json_encode([]); print_r($lll);

		$sql	= 'INSERT INTO informes_configuracion('.implode(',', $campos).') VALUES (:'.implode(',:', $campos).')';
		$res	= (new Conexiones())->consulta(Conexiones::INSERT, $sql, $sql_params);
		if($res !== false){
			$datos = (array) $this;
			$datos['modelo'] = 'Informe';
			Logger::event('alta', $datos);
		}
		return $res;
	}

	public function baja(){
		if(empty($this->id)) {
			return false;
		}
		$sql	= <<<SQL
			UPDATE informes_configuracion SET borrado = 1 WHERE id = :id
SQL;
		$res	= (new Conexiones())->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
		if (!empty($res) && $res > 0) {
			$datos				= (array)$this;
			$datos['modelo']	= 'Informe';
			if (is_numeric($res) && $res > 0) {
				$flag = true;
			} else {
				$datos['error_db'] = 'baja';
			}
			Logger::event('baja', $datos);
		}
		return $flag;
	}

	public function modificacion(){
		if(!$this->validar() || empty($this->id)){
			return false;
		}
		$campos	= [
			'empleado_id',
			'empleado_logueado_id',
			'fecha_ultimo_envio',
			'dependencias',
			'contratos',
		];
		$sql_params	= [
			':id'	=> $this->id,
		];
		foreach ($campos as $key => $campo) {
			$sql_params[':'.$campo]	= $this->{$campo};
			unset($campos[$key]);
			$campos[$campo]	= $campo .' = :'.$campo;
		}

		if($this->fecha_ultimo_envio instanceof \DateTime){
			$sql_params[':fecha_ultimo_envio']	= $this->fecha_ultimo_envio->format('Y-m-d H:i:s');
		}
		$this->contratos			= array_map(function($id){ return (int)$id; }, $this->contratos);
		$sql_params[':contratos']	= (sort($this->contratos))
									? json_encode($this->contratos)
									: null;
		$this->dependencias			= array_map(function($id){ return (int)$id; }, $this->dependencias);
		$sql_params[':dependencias']= (sort($this->dependencias))
									? json_encode($this->dependencias)
									: null;
//		$tt =  [7,'8',null,'"1=1 --']; $lll	= (sort($tt = array_map(function($id){ return (int)$id; }, $tt))) ? json_encode($tt) : json_encode([]); print_r($lll);

		$sql	= 'UPDATE informes_configuracion SET '.implode(',', $campos).' WHERE id = :id';
		$res	= (new Conexiones())->consulta(Conexiones::UPDATE, $sql, $sql_params);
		if($res !== false){
			$datos = (array) $this;
			$datos['modelo'] = 'Informe';
			Logger::event('modificacion', $datos);
		}
		return $res;
	}

	public function validar() {
		$reglas		= [
			'empleado_id'	=> ['required'],
			'dependencias'	=> ['required'],
			'contratos'		=> ['required'],
		];
		$nombres	= [
			'empleado_id'	=> 'Empleado',
			'dependencias'  => 'Dependencias',
			'contratos'		=> 'Contrato',
		];
		$validator	= Validador::validate((array)$this, $reglas, $nombres);
		if ($validator->isSuccess()) {
			return true;
		}
		$this->errores = $validator->getErrors();
		return false;
	}

	static public function arrayToObject($res = []) {
		$campos	= [
			'id'					=> 'int',
			'empleado_id'			=> 'int',
			'empleado_logueado_id'	=> 'int',
			'fecha_ultimo_envio'	=> 'datetime',
			'dependencias'			=> 'json',
			'contratos'				=> 'json',
			'borrado'				=> 'int',
		];
		$obj = new self();
		foreach ($campos as $campo => $type) {
			switch ($type) {
				case 'int':
					$obj->{$campo}	= isset($res[$campo]) ? (int)$res[$campo] : null;
					break;
				case 'json':
					$obj->{$campo}	= isset($res[$campo]) ? json_decode($res[$campo], true) : null;
					break;
				case 'datetime':
					$obj->{$campo}	= isset($res[$campo]) ? \DateTime::createFromFormat('Y-m-d H:i:s', $res[$campo]) : null;
					break;
				case 'date':
					$obj->{$campo}	= isset($res[$campo]) ? \DateTime::createFromFormat('Y-m-d', $res[$campo]) : null;
					break;
				default:
					$obj->{$campo}	= isset($res[$campo]) ? $res[$campo] : null;
					break;
			}
		}

		$obj->empleado	= empty($obj->empleado_id) ? null : Empleado::obtener($obj->empleado_id);
		return $obj;
	}
}