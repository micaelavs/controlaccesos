<?php
namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Validador;
use FMT\Logger;
use FMT\Modelo;

class Ubicacion extends Modelo {
	/* Atributos */
	/** @var int */
	public $id = 0;
	/** @var int */
	public $organismo_id = 0;
	/** @var string */
	public $nombre = null;
	/** @var string */
	public $calle = null;
	/** @var int */
	public $numero = 0;

	/**
	 * @param int $id
	 * @return Ubicacion
	 */
	static public function obtener($id = null) {
		if (is_numeric($id)) {
			if ($id > 0) {
				$sql = "SELECT * FROM ubicaciones WHERE borrado = 0 AND id = :id;";
				$res = (new Conexiones())->consulta(Conexiones::SELECT, $sql, [':id' => $id]);
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
	 * @return array
	 */
	static public function listar() {
		$str = "SELECT * FROM ubicaciones WHERE borrado = 0";
		$res = (new Conexiones)->consulta(Conexiones::SELECT, $str);
	
		return $res;
	}

	static public function listar2() {
		$str = "SELECT * FROM ubicaciones WHERE borrado = 0";
		$res = (new Conexiones)->consulta(Conexiones::SELECT, $str);
		$lista = [];
		if (!empty($res) && is_array($res)) {
			foreach ($res as $re) {
				$lista[] = $re;
			}
		}

		return $lista;
	}

	/**
	 * @param array $res
	 * @return Ubicacion
	 */
	public static function arrayToObject($res = []) {
		$obj = new self();
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->calle = isset($res['calle']) ? $res['calle'] : null;
		$obj->nombre = isset($res['nombre']) ? $res['nombre'] : null;
		$obj->numero = isset($res['numero']) ? $res['numero'] : null;
		$obj->organismo_id = isset($res['organismo_id']) ? (int)$res['organismo_id'] : 0;

		return $obj;
	}

	public function alta() {
		if ($this->validar()) {
			$mbd = new Conexiones;
			$sql = "INSERT INTO ubicaciones (nombre, calle, numero) VALUES (:nombre, :calle, :numero)";
			$params = [
				':nombre' => $this->nombre,
				':calle'  => $this->calle,
				':numero' => $this->numero,
			];
			$resultado = $mbd->consulta(Conexiones::INSERT, $sql, $params);
			if (is_numeric($resultado) && $resultado > 0) {
				$this->id = $resultado;
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'ubicacion';
				Logger::event('alta', $datos);

				return true;
			}
		}

		return false;
	}

	public function validar() {
	}

	/**
	 * @return bool
	 */
	public function modificacion() {
		$sql = "UPDATE
					ubicaciones
				SET
					nombre		= :nombre,
					calle		= :calle,
					numero		= :numero
				WHERE 
					id = :id";
		if (!empty($this->id)) {
			$params = [
				':id'     => $this->id,
				':nombre' => $this->nombre,
				':calle'  => $this->calle,
				':numero' => $this->numero,
			];
			$res = (new Conexiones)->consulta(Conexiones::UPDATE, $sql, $params);
			if (!empty($res) && $res > 0) {
				//Log
				$datos = (array)$this;
				$datos['modelo'] = 'ubicacion';
				Logger::event('modificacion', $datos);

				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function baja() {
		$params = [':id' => $this->id];
		$conex = new Conexiones;
		$sql_emp = "SELECT COUNT(empleado_id) AS count FROM empleados_x_ubicacion WHERE ubicacion_id = :id";
		$sql_con = "SELECT COUNT(personal_id) AS count FROM contratista_x_ubicacion WHERE ubicacion_id = :id";
		$res_emp = $conex->consulta(Conexiones::SELECT, $sql_emp, $params);
		$res_con = $conex->consulta(Conexiones::SELECT, $sql_con, $params);
		$emps = $res_emp[0]['count'];
		$cons = $res_con[0]['count'];
		$emps_c = $emps > 0;
		$cons_c = $cons > 0;
		if ($emps_c || $cons_c) {
			$msj = "La ubicación <strong>{$this->nombre}</strong> tiene: <ul>";
			if ($emps_c) {
				$msj .= "<li><strong>{$emps}</strong> <a href='?c=empleados&a=index'>Empleados</a> asociados.</li>";
			}
			if ($cons_c) {
				$msj .= "<li><strong>{$cons}</strong> <a href='?c=contratistas&a=index'>Contratistas</a> asociados.</li>";
			}
			$msj .= "</ul><br> Debe <strong>borrar/modificar</strong> antes estos elementos para poder retirar la ubicación";
			$this->errores = ['texto' => $msj];

			return false;
		}
		$sql = "UPDATE ubicaciones SET borrado = 1 WHERE id = :id";
		$res = $conex->consulta(Conexiones::UPDATE, $sql, $params);
		if (!empty($res) && is_numeric($res) && $res > 0) {
			//Log
			$datos = (array)$this;
			$datos['modelo'] = 'ubicacion';
			Logger::event('baja', $datos);

			return true;
		}

		return false;
	}

	static public function buscarLocacionApi($id_locacion_api=null, $id_edificio_api = null, $id_oficina_api = null){
    
        if($id_locacion_api && $id_edificio_api && $id_oficina_api){
	        $Conexiones = new Conexiones();
	        $resultado = $Conexiones->consulta(Conexiones::SELECT,
	<<<SQL

	            SELECT id
	            FROM ubicaciones
	            WHERE id_locacion_api= :id_locacion_api AND id_edificio_api = :id_edificio_api  AND id_oficina_api = :id_oficina_api
	            LIMIT 1
	SQL
	        ,[':id_locacion_api'=>$id_locacion_api, ':id_edificio_api'=>$id_edificio_api,':id_oficina_api'=>$id_oficina_api]);
	        return !empty($resultado) ? $resultado[0] : null;
        }

    }

   static public function actualizarLocacion($locacion = null, $id_ubicacion_ca = null){
        
        $cnx = new Conexiones();
        $sql_params = [
            ':id_ubicacion_ca' => $id_ubicacion_ca,
            ':nombre_completo' => $locacion['locacion'].' - '. $locacion['calle'].' - '.$locacion['numero'].' - '.$locacion['piso'].' - '.$locacion['oficina'],
            ':calle'	=> $locacion['calle'],
            ':numero'	=> $locacion['numero']
        ];

        $sql = 'UPDATE ubicaciones SET nombre = :nombre_completo, calle = :calle, numero = :numero, borrado = 0 
        		WHERE id = :id_ubicacion_ca';

        $res = $cnx->consulta(Conexiones::UPDATE, $sql, $sql_params);
        
        return $res;
    }

     static public function insertarLocacion($locacion = null){
        
        $cnx = new Conexiones();
        $sql_params = [
            ':nombre_completo' => $locacion['locacion'].' - '. $locacion['calle'].' - '.$locacion['numero'].' - '.$locacion['piso'].' - '.$locacion['oficina'],
            ':calle'	=> $locacion['calle'],
            ':numero'	=> $locacion['numero'],
            ':id_locacion_api' 	=> $locacion['id_locacion'],
            ':id_edificio_api' 	=> $locacion['id_edificio'],
            ':id_oficina_api'	=> $locacion['id_oficina']
        ];

        $sql = 'INSERT INTO ubicaciones (nombre, calle, numero, id_locacion_api, id_edificio_api, id_oficina_api) VALUES (:nombre_completo, :calle, :numero, :id_locacion_api, :id_edificio_api, :id_oficina_api)';
        $res = $cnx->consulta(Conexiones::INSERT, $sql, $sql_params);	

        return $res;
    }

    static public function borradoInicialDeLocaciones(){
      $cnx = new Conexiones();
      $sql = 'UPDATE ubicaciones SET borrado = 1';
      $res = $cnx->consulta(Conexiones::UPDATE, $sql, []);
      return $res;
    }

}