<?php
namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Validador;
use FMT\Logger;
use FMT\Modelo;

/**
 * Class Credencial Registra los cambios de estado de la tarjeta de entrada que valida al visitante
 */
class Credencial extends Modelo {
	/** @var int $id Identificador del Registro */
	public $id;
	/** @var string $codigo Código alfanumérico que registra la tarjeta física que valida al visitante */
	public $codigo;
	/** @var int $estatus Indica el estado en el que se encuentra el credencial en determinado momento
	 * [
	 *      0 => libre,
	 *      1 => asignado
	 * ] */
	public $estatus;
	/** @var Ubicacion */
	public $ubicacion;
	/** @var int Acceso_id */
	public $acceso_id;
	public $tipo_acceso;

	const LONG_TARJETA_CREDENCIAL = 8;

	/**
	 * Regresa una instancia de Credencial con los datos del registro localizado según el código
	 * pasado como argumento.
	 * @param string $codigo Código del Credencial a obtener.
	 * @param int    $ubicacion_id
	 * @return Credencial
	 */
	public static function obtenerPorCodigo($codigo, $ubicacion, $acceso_id = 0) {
		$codigo = trim($codigo); 
		$credencial = static::obtener(0);
		if (!empty($codigo)) {
			if (strlen($codigo) > self::LONG_TARJETA_CREDENCIAL) {
				$codigo = substr($codigo, 0, self::LONG_TARJETA_CREDENCIAL);
			}
			/** @var string $sql */
			$sql = "SELECT id FROM credenciales WHERE borrado = 0 AND codigo = :codigo AND ubicacion_id = :ubicacion_id";		
			$params = [
				':codigo'       => $codigo,
				':ubicacion_id' => $ubicacion->id,
			];
			/** @var Conexiones $con */
			$con = new Conexiones();
			/** @var array $res */
			$res = $con->consulta(Conexiones::SELECT, $sql, $params);
			if (isset($res[0])) {
				/** @var Credencial $credencial */
				$credencial = static::obtener(isset($res[0]['id']) ? (int)$res[0]['id'] : 0);
				if (!empty($credencial) && !empty($credencial->id)) {
					return $credencial;
				}
			}
			$credencial->codigo = $codigo;
			$credencial->ubicacion = Ubicacion::obtener($ubicacion->id);
			$credencial->acceso_id = $acceso_id;
			if ($credencial->alta()) {
				return $credencial;
			}
		}

		return $credencial;
	}

	/**
	 * Regresa una instancia de Credencial con los datos del registro localizado según el código
	 * pasado como argumento.
	 * @param string $codigo Código del Credencial a obtener.
	 * @param int    $ubicacion_id
	 * @return Credencial
	 */
	public static function obtenerPorCodigo_TM($codigo, $ubicacion) {
		$codigo = trim($codigo); 
		$credencial = static::obtener(0);
		if (!empty($codigo)) {
			/** @var string $sql */
			$sql = "SELECT id FROM credenciales WHERE borrado = 0 AND codigo = :codigo AND ubicacion_id = :ubicacion_id AND acceso_id = 1 order by id desc";		
			$params = [
				':codigo'       => $codigo,
				':ubicacion_id' => $ubicacion,
			];
			/** @var Conexiones $con */
			$con = new Conexiones();
			/** @var array $res */
			$res = $con->consulta(Conexiones::SELECT, $sql, $params);
			if (isset($res[0])) {
				/** @var Credencial $credencial */
				$credencial = static::obtener(isset($res[0]['id']) ? (int)$res[0]['id'] : 0);
				if (!empty($credencial) && !empty($credencial->id)) {
					return $credencial;
				}
			}
			$credencial->codigo = $codigo;
			$credencial->ubicacion = Ubicacion::obtener($ubicacion);
			/*if ($credencial->alta()) {
				return $credencial;
			}*/
		}

		return $credencial;
	}

	public static function obtenerPorCodigo_TM_Salida($codigo, $ubicacion,$acceso_id) {
		$codigo = trim($codigo); 
		$credencial = static::obtener(0);
		if (!empty($codigo)) {
			/** @var string $sql */
			$sql = "SELECT id FROM credenciales WHERE borrado = 0 AND codigo = :codigo AND ubicacion_id = :ubicacion_id AND acceso_id = :acceso_id order by id desc limit 1";		
			$params = [
				':codigo'       => $codigo,
				':ubicacion_id' => $ubicacion,
				':acceso_id' => $acceso_id,
			];
			/** @var Conexiones $con */
			$con = new Conexiones();
			/** @var array $res */
			$res = $con->consulta(Conexiones::SELECT, $sql, $params);
			if (isset($res[0])) {
				/** @var Credencial $credencial */
				$credencial = static::obtener(isset($res[0]['id']) ? (int)$res[0]['id'] : 0);
				if (!empty($credencial) && !empty($credencial->id)) {
					return $credencial;
				}
			}
			$credencial->codigo = $codigo;
			$credencial->ubicacion = Ubicacion::obtener($ubicacion);
			/*if ($credencial->alta()) {
				return $credencial;
			}*/
		}

		return $credencial;
	}

	static public function update_credencial_TM($id,$acceso_id,$estatus) {
			$sql = "UPDATE credenciales AS c
					 SET c.acceso_id  = :acceso_id,
					 c.estatus = :estatus
					 WHERE c.id = :id";
			$params = [	
						':id'           => $id,
						":acceso_id" => $acceso_id,
						':estatus'           => $estatus];
			$res = (new Conexiones())->consulta(Conexiones::UPDATE, $sql, $params);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				//Log
				// $datos = (array)$this;
				// $datos['modelo'] = 'Credencial';
				// Logger::event('update', $datos);

				return true;
			}
			return false;
	}

	/**
	 * Regresa un objeto del registro según el ID solicitado, en caso de pasar 0 como ID
	 * el método regresa una nueva instancia de Credencial.
	 * @param int|string $id Identificador del registro
	 * @return Credencial
	 */
	static public function obtener($id) {
		/** @var string $sql */
		$sql = "SELECT * FROM credenciales WHERE (id = :id) AND borrado = 0";
		/** @var array $params */
		$params = [':id' => $id];
		if (is_numeric($id)) {
			if ($id > 0) {
				$conex = new Conexiones();
				$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
				if (!empty($res) && is_array($res) && isset($res[0])) {
					return static::arrayToObject($res[0]);
				}
			} else {

				return static::arrayToObject();
			}
		}

		return static::arrayToObject();
	}

	/**
	 * @param array $res
	 * @return Credencial
	 */
	public static function arrayToObject($res = []) {
		$obj = new self();
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->codigo = isset($res['codigo']) ? $res['codigo'] : null;
		$obj->ubicacion = Ubicacion::obtener(isset($res['ubicacion_id']) ? (int)$res['ubicacion_id'] : 0);
		$obj->estatus = isset($res['estatus']) ? $res['estatus'] : null;
		$obj->acceso_id = isset($res['acceso_id']) ? $res['acceso_id'] : null;

		return $obj;
	}

	/**
	 * @return bool
	 */
	public function alta() {
		if ($this->validar()) {
			/** @var string $sql */
			$sql = "INSERT INTO credenciales (codigo, ubicacion_id, acceso_id) VALUE (:codigo, :ubicacion_id, :acceso_id)";
			/** @var Conexiones $con */
			$con = new Conexiones();
			/** @var int $res */
			$params = [
				':codigo'       => $this->codigo,
				':ubicacion_id' => $this->ubicacion->id,
				':acceso_id' => $this->acceso_id,
			];
			$res = $con->consulta(Conexiones::INSERT, $sql, $params);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				$this->id = (int)$res;
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'credencial';
				Logger::event('alta', $datos);

				return true;
			}
		}

		return false;
	}

	/**
	 * Verifica que los datos ingresados sean correctos
	 * @return bool
	 * @throws \SimpleValidator\SimpleValidatorException
	 */
	public function validar() {
		/** @var Validador $validator */
		$inputs = [
			'codigo'    => $this->codigo,
			'ubicacion' => $this->ubicacion->id
		];
		$rules = [
			'codigo'    => ['required', 'numeric', 'max_length('.self::LONG_TARJETA_CREDENCIAL.')', 'min_length(1)'],
			'ubicacion' => ['required', 'existe(ubicaciones,id)']
		];
		$naming = [
			'codigo'    => 'Código',
			'ubicacion' => 'Ubicación'
		];
		$validator = Validador::validate($inputs, $rules, $naming);
		if ($validator->isSuccess()) {
			$this->errores = [];

			return true;
		}
		$this->errores = $validator->getErrors();

		return false;
	}

	/**
	 * No se implementa
	 */
	public function baja() {
		// TODO: Implement baja() method.
	}

	/**
	 * Cambia el estatus del registro según se indica en el parámetro
	 * @return array|int|string
	 */
	public function modificacion() {
		if (!empty($this->id)) {
			/** @var string $sql */
			$sql = "UPDATE credenciales SET estatus = :estatus WHERE id = :id";
			/** @var Conexiones $con */
			$con = new Conexiones();
			/** @var int $res */
			$res = $con->consulta(Conexiones::UPDATE, $sql, [
				':estatus' => $this->estatus,
				':id'      => $this->id,
			]);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'credencial';
				Logger::event('modificacion', $datos);

				return true;
			}
		}

		return false;
	}

	public function activar() {
		if (empty($this->id) && empty($this->codigo) && $this->enUso()) {
			return false;
		}
		$sql = "UPDATE credenciales SET estatus = 1 WHERE id = :id";
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			$this->activar_tarjeta_magnetica();
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'credencial';
			Logger::event('activa_credencial', $datos);

			return true;
		}

		return false;
	}

	/**
	 * Guarda el tipo de acceso en el que se usó la credencial
	 * 1-> visita
	 * 2-> contratista
	 */
	public function update_tipo_acceso($tipo_acceso) { 
		// if (empty($this->id) && empty($this->codigo) && $this->enUso()) {
		// 	return false;
		// }
		$sql = 'UPDATE credenciales SET tipo_acceso = '.$tipo_acceso.' WHERE id = :id';
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			//$this->activar_tarjeta_magnetica();
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'credencial';
			Logger::event('tipo_acceso_credencial', $datos);

			return true;
		}

		return false;
	}

    public function liberar() {
        if (empty($this->id) && empty($this->codigo) && !$this->enUso()) {
            return false;
        }
        $sql = "UPDATE credenciales SET estatus = 0 WHERE id = :id";
        $conex = new Conexiones();
        $res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
        if (!empty($res) && is_numeric($res) && $res > 0) {
            //Log
            $this->estatus = 0;
            $datos = (array)$this;
            $datos['modelo'] = 'credencial';
            Logger::event('liberar_credencial', $datos);

            return true;
        }

        return false;
    }

	public function enUso() {
		$sql = "SELECT
			cred.id AS idCredencial
			FROM credenciales AS cred 
			INNER JOIN ubicaciones  u ON u.id = ubicacion_id
			INNER JOIN accesos_visitas acv ON cred.id = acv.credencial_id
			INNER JOIN accesos acc ON acv.id = acc.tipo_id			
			left join   (
			SELECT cred.id as id FROM accesos as acc 
			INNER JOIN accesos_visitas AS av ON acc.tipo_id = av.id AND acc.tipo_modelo = :modeloVis 
			INNER JOIN credenciales AS cred ON av.credencial_id = cred.id 
			WHERE acc.ubicacion_id = :ubicacion AND DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') = :fecha AND acc.hora_egreso IS NULL      
			UNION (SELECT 
			ac.credencial_id as id FROM accesos AS acc
			INNER JOIN accesos_contratistas AS ac on ac.id = acc.tipo_id  and acc.tipo_modelo = :modeloCont
			INNER JOIN credenciales AS cred on cred.id = ac.credencial_id 
			WHERE acc.ubicacion_id = :ubicacion and DATE_FORMAT(acc.hora_ingreso, '%d/%m/%Y') = :fecha AND acc.hora_egreso IS NULL))   as XX on   cred.id = XX.id  AND cred.codigo = :codigo
			WHERE cred.ubicacion_id = :ubicacion AND cred.estatus = 1 AND acc.tipo_modelo = :modeloVis AND cred.codigo = :codigo
			AND acc.hora_egreso IS NOT NULL AND XX.id IS NULL
			ORDER BY acc.hora_egreso DESC limit 1";

		$conex = new Conexiones();
		$credencialesMalBloqueadas = $conex->consulta(Conexiones::SELECT, $sql, [
			':modeloVis' => Acceso::VISITANTE,
			':modeloCont' => Acceso::CONTRATISTA,
			':fecha' => date("d/m/Y"),
			':ubicacion' => $this->ubicacion->id,
			':codigo' => $this->codigo
		]);

		if (!empty($credencialesMalBloqueadas[0]['idCredencial'])) {
			$sql = "UPDATE credenciales SET estatus = 0 WHERE id = :id";
			$res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
			if (!empty($res) && is_numeric($res) && $res > 0) {
				$this->estatus = 0;
			}
		}
		return $this->estatus != 0;
	}
	//verifica si es tarjeta magentica si es le asigna 1 al acceso para saber que esta entregada
	public function activar_tarjeta_magnetica(){
		if (strlen($this->codigo) == self::LONG_TARJETA_CREDENCIAL){
			$sql = "UPDATE credenciales SET acceso_id = 1 WHERE id = :id";
       		$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
			$this->acceso_id = 1;
		}
		
	}
}