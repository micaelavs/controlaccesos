<?php
namespace App\Modelo;

use App\Helper\Biometrica;
use App\Helper\Conexiones;
use App\Helper\Validador;
use FMT\Logger;
use FMT\Modelo;

class Tarjeta extends Modelo {
	/** @var integer */
	public $id = 0;
	public $access_id;
	public $borrado;

	 /**
			* Obtiene la tarjeta según el id.
     * @param $id
     * @return Tarjeta
     */
    static public function obtener($id)    
    {

		if($id == NULL){
        return static::arrayToObject();
      }
        $sql = <<<SQL
					SELECT
							id,access_id,borrado
					FROM tarjetas
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

	public function actualizarNroTarjeta($nodo_id=null) {
        $data	= [
			'nodes'		=> [$nodo_id],
		];
		$nroTarjeta = $this->access_id;
		$return	= Biometrica::actualizarNroTarjeta($data, $nroTarjeta);
		return  !empty($return) ? $return->status : false;
	}

	public function actualizarNroTarjetaDesenrolar($nodo_id=null) {
		$data	= [
			'nodes'		=> [$nodo_id],
		];
		$nroTarjeta = $this->access_id;
		$return	= Biometrica::actualizarNroTarjetaDesenrolar($data, $nroTarjeta);
		return  !empty($return) ? $return['status'] : false;
	}

	static public function listar() {
		$str = "SELECT * FROM tarjetas WHERE borrado = 0";
		$res = (new Conexiones)->consulta(Conexiones::SELECT, $str);
		$lista = [];
		if (!empty($res) && is_array($res)) {
			foreach ($res as $re) {
				$lista[] = (array)static::arrayToObject($re);
			}
		}

		return $lista;
	}

	static public function obtenerTarjetaPorNro($nrotarjeta) {

		$str = "SELECT * FROM tarjetas WHERE borrado = 0 AND access_id = :nrotarjeta";
		$res = (new Conexiones)->consulta(Conexiones::SELECT, $str,[':nrotarjeta' => $nrotarjeta]);

		if(!empty($res)){
            return static::arrayToObject($res[0]);
        }
        return static::arrayToObject();
	}

	 /**
			* Transforma el array en un objeto.
     * @param array $res
     * @return Tarjeta
     */
	public static function arrayToObject($res = []) {
		$obj = new self();
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->access_id = isset($res['access_id']) ? $res['access_id'] : null;
		$obj->borrado = isset($res['borrado']) ? (int)$res['borrado'] : 0;

		return $obj;
	}

	public function alta() {
		$sql = 'INSERT INTO tarjetas (access_id) VALUE (:access_id);';
		$conex = new Conexiones();
		$res = $conex->consulta(Conexiones::INSERT, $sql,[':access_id' => $this->access_id]);
		return $res;
	}

	public static function getAllTM($id_reloj){
		$conexion = new Conexiones();
		$resultado = $conexion->consulta(Conexiones::SELECT,
			'SELECT distinct t.* 
			FROM tarjetas as t
			INNER JOIN reloj_tarjetas as rt on rt.id_tarjeta = t.id
			WHERE rt.borrado = 0 and rt.id_reloj =  '.$id_reloj);
		if (is_array($resultado)) {
			$list = [];
			foreach ($resultado as $re) {
					$list[] = self::arrayToObject($re);
			}

			return $list;
		}

		return [];
	}

	public function validar() {
		$reglas = [
			'access_id'         => ['required','unico(tarjetas,access_id,'.$this->access_id.','.null.',\'0\')'],
			
		];
		$validator = Validador::validate((array)$this, $reglas, []); 
		$validator->customErrors([
			'unico' => 'El número de Tarjeta '. $this->access_id.' ya existe.'
		]);
		if ($validator->isSuccess()) {
    	   return true;
   		 } 
   		 else {
    		  $this->errores = $validator->getErrors();
     		 return false;
   		 }
	}
	public function modificacion() {}
	public function baja() {}

}
