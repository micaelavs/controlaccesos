<?php
namespace App\Modelo;

use App\Helper\Biometrica;
use App\Helper\Conexiones;
use App\Helper\Validador;
use FMT\Logger;
use FMT\Modelo;
use phpDocumentor\Reflection\DocBlock\Tags\Example;

class RelojTarjeta extends Modelo {
	/** @var integer */
	public $id;
	public $id_reloj;
	public $id_tarjeta;
	public $borrado;

	static $ACCION_TARJETA   = null;
     /**
			* Obtiene la tarjeta según el id.
     * @param $id
     * @return RelojTarjeta
     */
    static public function obtener($id = null)    
    {

		if($id == NULL){
        return static::arrayToObject();
      }
        $sql = <<<SQL
					SELECT
							id,id_reloj,id_tarjeta,borrado
					FROM reloj_tarjetas
					WHERE id = :id AND borrado = 0;
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
			* Transforma el array en un objeto.
     * @param array $res
     * @return RelojTarjeta
     */
	public static function arrayToObject($res = []) {
		$obj = new self();
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->id_reloj = isset($res['id_reloj']) ? $res['id_reloj'] : null;
		$obj->id_tarjeta = isset($res['id_tarjeta']) ? $res['id_tarjeta'] : null;
		$obj->borrado = isset($res['borrado']) ? (int)$res['borrado'] : 0;

		return $obj;
	}

	public function alta() {

		$conex = new Conexiones();
		$params = [':id_reloj' =>  $this->id_reloj,
		':id_tarjeta' =>  $this->id_tarjeta,];
		$sql = <<<SQL
		UPDATE reloj_tarjetas SET borrado = 0 WHERE id_reloj = :id_reloj AND id_tarjeta = :id_tarjeta
		SQL;
		$res = $conex->consulta(Conexiones::UPDATE, $sql, $params);

		if ($res == false) {
			$sql = 'INSERT INTO reloj_tarjetas (id_reloj,id_tarjeta) VALUE (:id_reloj, :id_tarjeta);';		
			$res = $conex->consulta(Conexiones::INSERT, $sql,
			[
				':id_reloj' => $this->id_reloj,
				':id_tarjeta' =>  $this->id_tarjeta,
			]);
		}

		return $res;
	}

	public function baja(){		
		$conexion = new Conexiones;
		$params = [':id_reloj' =>  $this->id_reloj,
				   ':id_tarjeta' =>  $this->id_tarjeta,];
		$sql = <<<SQL
		UPDATE reloj_tarjetas SET  borrado = 1 WHERE id_reloj = :id_reloj AND id_tarjeta = :id_tarjeta
SQL;
		$res = $conexion->consulta(Conexiones::UPDATE, $sql, $params);
		if ($res !== false) {
			$datos = (array) $this;
			$datos['modelo'] = 'categoriadocumento';
			Logger::event('baja', $datos);
		} else {
			$datos['error_db'] = $conexion->errorInfo;
			Logger::event("error_baja",$datos);
		}
		return $res;		
	}

    	  /**
     * @return bool
     */
    public function validar()
    {
        $inputs = [
            'id_reloj' => $this->id_reloj,
            'id_tarjeta' => $this->id_tarjeta,
        ];
		$tarjeta = Tarjeta::obtener($this->id_tarjeta);

		if( static::$ACCION_TARJETA == "baja"){
			$rules = [
				'id_reloj' => ['required','integer'],
				'id_tarjeta' => ['required','integer'],
			];
		}else{
			$rules = [
				'id_reloj' => ['required','integer'],
				'id_tarjeta' => ['required','integer','existente(:id_reloj)' => function($input,$param1){
					$rta = true;
					if (!is_null($input) && !is_null($param1)) {
						$sql = "SELECT * FROM reloj_tarjetas WHERE id_reloj = :id_reloj AND id_tarjeta = :id_tarjeta AND borrado = 0";
						$params = [':id_tarjeta' => $input,':id_reloj' => $param1];
						$res = (new Conexiones)->consulta(Conexiones::SELECT, $sql, $params);
						if (!empty($res) && is_array($res) && isset($res[0])) {
							$rta = false;
						}
					}
					return $rta;
				}],
			];
		}
        
        $naming = [
            'id_reloj' => "ID de Reloj",
            'id_tarjeta' => "ID de Tarjeta",
        ];
        $validator = Validador::validate($inputs, $rules, $naming);
		$validator->customErrors([
			'unico' => 'El número de Tarjeta '.$tarjeta->access_id.' ya existe.',
			'existente' => 'El número de Tarjeta '.$tarjeta->access_id.' ya está asociado al reloj.'
		]);
        if ($validator->isSuccess()) {
            return true;
        }
        $this->errores = $validator->getErrors();

        return false;
    }

	public function modificacion() {}


}
