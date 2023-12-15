<?php
namespace App\Modelo;

class SigarhuApi extends \FMT\ApiCURL {
	use SigarhuTrait;

/**
 * Obtener informacion del agente (empleado), a partir del cuit o el id.
 * Si se combina con el metodo `SigarhuApi::contiene()` se puede filtrar la informacion vinculada, para economizar bits de datos.
 * @param string	$cuit	- Cuit del agente que se quiere consultar.
 * @param boolean	$by_id	- Si es true el parametro `$cuit` sera un `id`
 *
 * @return object
*/
	static public function getAgente($cuit=null,$by_id=false) {
		static $cache_cuit	= null;
		static $cache_by_id	= null;
		static $return		= null;
		static $cache_contiene	= null;
		$contiene				= static::getContiene();
		if($cache_cuit === $cuit && $cache_by_id === $by_id && $return !== null && $cache_contiene === $contiene){
			return $return['data'];
		} else {
			$cache_cuit		= $cuit;
			$cache_by_id	= $by_id;
			$cache_contiene	= $contiene;
		}
		$api = static::getInstance();
		$api->setQuery([
			'contiene'	=> $cache_contiene,
			'by_id'		=> $by_id,
		]);

		$return 		= $api->consulta('GET', "/agente/{$cuit}");

		if($api->getStatusCode() != '200'){
			static::setErrores($return['mensajes']);
            return false;
        }
		$return['data']	= static::arrayToObject($return['data']);
		return $return['data'];
	}

	static public function getParametricos($params=null){
		static $cache_params	= null;
		static $return			= null;
		if($cache_params === $params && $return !== null){
			return $return['data'];
		} else {
			$cache_params	= $params;
		}
		if(is_array($params)){
			static::contiene($params);
		}
		$api = static::getInstance();
		$api->setQuery([
			'contiene'	=> static::getContiene(),
		]);

		$return = $api->consulta('GET', "/parametricos");
		if($api->getStatusCode() != '200'){
			static::setErrores($return['mensajes']);
            return false;
        }
        $aux    = [];
        foreach ($return['data'] as $k => $v) {
            $aux[$k]    = static::arrayToObject($v, false);
        }
		return $aux;
	}

/**
 * Obtener informacion del agente (empleado), a partir del cuit o el id.
 * Si se combina con el metodo `SigarhuApi::contiene()` se puede filtrar la informacion vinculada, para economizar bits de datos.
 * @param string	$cuit	- Cuit del agente que se quiere consultar.
 * @param boolean	$by_id	- Si es true el parametro `$cuit` sera un `id`
 *
 * @return array
*/
	static public function searchAgentes($params=array()) {
		static $cache_params	= null;
		static $return			= null;
		if($cache_params === $params && $return !== null){
			return $return['data'];
		} else {
			$cache_params	= $params;
		}
		$paramsDefault	= [
			'cuit'				=> null,
			'nombre_apellido'	=> null,
			'estado' 			=> null,
			'agente_activo'		=> null,
			'limit_1'			=> null,
		];
		$params	= array_merge($paramsDefault, $params);
		if(empty($params['cuit']) && empty($params['nombre_apellido'])){
			return [];
		}

		$api = static::getInstance();
		$api->setQuery([
			'params'	=> $params,
		]);

		$return = $api->consulta('GET', "/search_agentes");
		if($api->getStatusCode() != '200'){
			static::setErrores($return['mensajes']);
            return false;
        }
		return $return['data'];
	}

	static public function getDependencia($id=null){
		static $cache_id	= null;
		static $return			= null;
		if($cache_id === $id && $return !== null){
			return $return['data'];
		} else {
			$cache_id	= $id;
		}
		if(empty($id)){
			return false;
		}
		$api		= static::getInstance();
		$return 	= $api->consulta('GET', "/dependencias/{$id}");
		if($api->getStatusCode() != '200'){
			static::setErrores($return['mensajes']);
            return false;
        }
		$return['data']	= static::arrayToObject($return['data']);
		return $return['data'];
	}

	static public function getConvenios($id_modalidad_vinculacion=null,$id_situacion_revista=null){
		static $cache_vinc	= null;
		static $cache_revi	= null;
		static $return		= null;
		if($cache_vinc === $id_modalidad_vinculacion && $cache_revi === $id_situacion_revista && $return !== null){
			return $return['data'];
		}else{
			$cache_vinc	= $id_modalidad_vinculacion;
			$cache_revi	= $id_situacion_revista;
		}
		if(!(is_numeric($id_modalidad_vinculacion) && is_numeric($id_situacion_revista))) {
			return [];
		}

		$api = static::getInstance();
		$api->setQuery([
			'id_modalidad_vinculacion'	=> $id_modalidad_vinculacion,
			'id_situacion_revista'		=> $id_situacion_revista,
		]);

		$return = $api->consulta('GET', "/convenios");
		if($api->getStatusCode() != '200'){
			static::setErrores($return['mensajes']);
            return false;
        }
		return $return['data'];
	}

/**
 * Interactua con el modulo Auditoria.
 * Posee 2 comportamientos:
 * - Si el `cuit` es ingresado, entonces va a devolver la ultima fecha que tubo modificaciones.
 * - Si se pasa un rango de fechas, se obtiene un array con los cuits modificados en ese rango. Si la `fecha_hasta` no se pasa, se asume que es el presente.
 *
 * -- Ejemplo 1: -------------------------------------------------
```
array (size=4)
  'tipo' => string 'agentes' (length=7)
  'fecha_desde' => string '2019-11-27 15:42:39' (length=19)
  'fecha_hasta' => null
  'cuits' =>
    array (size=1)
      0 => string '20121231237' (length=11)
```
 * ---------------------------------------------------------------
 * -- Ejemplo 2: -------------------------------------------------
```
 array (size=3)
  'tipo' => string 'modificacion' (length=12)
  'agente' => string '20121231237' (length=11)
  'fecha_ultima_modificacion' => string '2019-11-27 16:56:24' (length=19)
```
 * ---------------------------------------------------------------
 * @param int|DateTime      $cuit
 * @param false|DateTime    $fecha_hasta
 * @return array
 */
    static public function getAuditoria($cuit=null, $fecha_hasta=false){
        if(!($cuit instanceof \DateTime || is_numeric($cuit))){
            return false;
        }
        $fecha_desde    = '';
        if($cuit instanceof \DateTime){
            $fecha_desde    = $cuit;
            $cuit           = '';
        }
        if($fecha_desde instanceof \DateTime){
            $fecha_desde    = $fecha_desde->format('Y-m-d H:i:s');
        }
        if($fecha_hasta instanceof \DateTime){
            $fecha_hasta    = $fecha_hasta->format('Y-m-d H:i:s');
        }
        $params = [];
        if(!empty($fecha_desde)){
            $params['fecha_desde']  = $fecha_desde;
        }
        if(!empty($fecha_hasta)){
            $params['fecha_hasta']  = $fecha_hasta;
        }

        $api = static::getInstance();
        $api->setQuery($params);

        $return = $api->consulta('GET', "/auditoria/{$cuit}");

        if($api->getStatusCode() != '200'){
			static::setErrores($return['mensajes']);
            return false;
        }
		return $return['data'];
	}

/**
 * Obtiene aquellos CUITs de agentes que no poseen historial en el modulo Auditoria.
 * @return array
```
array (size=4)
  'tipo' => string 'agentes' (length=7)
  'fecha_desde' => null
  'fecha_hasta' => null
  'cuits' =>
    array (size=1)
      0 => string '20121231237' (length=11)
```
 */
    static public function getAuditoriaExtra(){
        $api = static::getInstance();
        $return = $api->consulta('GET', "/sincronizar_extra");

        if($api->getStatusCode() != '200'){
			static::setErrores($return['mensajes']);
            return false;
        }
		return $return['data'];
	}
}