<?php namespace App\Modelo;

use App\Helper\Biometrica;
use App\Helper\Conexiones;
use App\Helper\Validador;
use FMT\Logger;
use FMT\Modelo;

class Template extends Modelo {
	/** @var int */
	public $persona_id;
	/** @var int */
	public $index;
	/** @var string */
	public $data;
	/** @var Persona */
	public $persona;

	static public function listar() {
		$sql = <<<SQL
		SELECT
			persona_id,
			indice,
			data
		FROM templates;
		SQL;
		$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, []);
		if (is_array($res)) {
			$list = [];
			foreach ($res as $re) {
				$list[] = self::arrayToObject($re);
			}

			return $list;
		}

		return [];
	}

	public static function arrayToObject($res = []) {
		$obj = new self();
		$obj->persona_id = isset($res['persona_id']) ? (int)$res['persona_id'] : 0;
		$obj->index = isset($res['indice']) ? (int)$res['indice'] : 0;
		$obj->data = isset($res['data']) ? $res['data'] : null;
		$obj->persona = Persona::obtener($obj->persona_id);

		return $obj;
	}

	/**
	 * @param Persona $persona
	 * @param int     $indice
	 * @return Template
	 */
	static public function obtenerPorPersonaIndice(Persona $persona = null, $indice = null) {
		if (is_numeric($persona)) {
			if (!empty($persona->id) && $indice > 0) {
				$params = [
					':persona_id' => $persona->id,
					':indice'     => $indice,
				];
				$sql = <<<SQL
				SELECT
					persona_id,
					indice,
					data
				FROM templates
				WHERE persona_id = :persona_id AND indice = :indice;
				SQL;
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
	 * @param Persona $persona
	 * @return Template[]
	 */
	public static function obtenerDelEnrolador(Persona $persona) {
		$resp = [];
		if (!empty($persona->documento)) {
			$json = Biometrica::accessId($persona->documento);
			if ($json) {

				foreach ($json as $item) {
					$obj = new static();
					$obj->persona = Persona::obtenerPorDocumento($item->accessId);
					$obj->index = (int)$item->index;
					$obj->data = $item->data;
					$obj->persona_id = $obj->persona->id;
					$resp[] = $obj;
					$obj = null;
				}
			}
		}

		return $resp;
	}

	public static function listarPorPersona($persona) {
		$sql	= <<<SQL
			SELECT persona_id, indice, data FROM templates
			WHERE persona_id = :persona_id;
			SQL;
		if(!($persona instanceof Persona) && !isset($persona['id']))
			return [];

		$resp = (new Conexiones())->consulta(Conexiones::SELECT, $sql, [
			':persona_id'	=> ($persona instanceof Persona)
							 ? $persona->id
							 : $persona['id'],
		]);
		if (is_array($resp)) {
			$lista = [];
			foreach ($resp as $item) {
				$lista[] = self::arrayToObject($item);
			}

			return $lista;
		}

		return [];
	}

	public function alta() {

			$sql = <<<SQL
			INSERT INTO templates (persona_id, indice, data)
			VALUES (:persona_id, :index, :data) ON DUPLICATE KEY UPDATE data = :data;
			SQL;
			$params = [
				':persona_id' => $this->persona->id,
				':index'      => $this->index,
				':data'       => $this->data,
			];
			$resp = (new Conexiones())->consulta(Conexiones::INSERT, $sql, $params);
			if ($resp !== false) {
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'template';
				Logger::event('alta', $datos);
				return true;
			}

		return false;
	}

	public function validar() {
		$inputs = [
			'persona_id' => $this->persona->id,
			'index'      => $this->index,
			'template'   => $this->data,
		];
		$rules = [
			'persona_id' => ["required"],
			'index'      => ["numeric"],
			'template'   => ["required", 'valido'=>function($input){
				return substr($input, 0,10) !== 'AAAAAAAAAA';
			}],
		];
		$naming = [
			'persona_id' => "Persona",
			'index'      => "Posición del Template",
			'template'   => "Imagen de Template",
		];
		$validator = Validador::validate($inputs, $rules, $naming);
		$validator->customErrors([
			'valido' => 'El template no es valido',
		]);
		if ($validator->isSuccess()) {
			return true;
		}
		$this->errores = $validator->getErrors();

		return false;
	}

	public function baja() {
		$conn = new Conexiones();
		$sql = <<<SQL
		DELETE FROM templates WHERE persona_id = :persona_id AND indice = :indice
		SQL;
		$resultado = $conn->consulta(Conexiones::UPDATE, $sql, [
			':persona_id' => $this->persona->id,
			':indice'      => $this->index,
		]);
		if ($resultado !== false) {
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'template';
			Logger::event('baja', $datos);
		}

		return $resultado;
	}

	public function modificacion() {
		if ($this->validar()) {
			$sql = <<<SQL
			UPDATE templates
			SET data = :data
			WHERE persona_id = :persona_id AND indice = :indice;
			SQL;
			$params = [
				':persona_id' => $this->persona->id,
				':index'      => $this->index,
				':data'       => $this->data,
			];
			$resp = (new Conexiones())->consulta(Conexiones::UPDATE, $sql, $params);
			if ($resp >= 0) {
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'template';
				Logger::event('modificacion', $datos);
			}
		}

		return $resp;
	}
}