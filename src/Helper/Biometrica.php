<?php
namespace App\Helper;

class Biometrica extends \App\Helper\ApiCurlBiometrica {
	static private $ERRORES = false;

	static public function sincronizar_marcaciones_borrar($nodo) {
		$config = \FMT\Configuracion::instancia();
		Biometrica::init($config['biometrica']['server']);
		$api = static::getInstance();
		$api->setQuery([]);
		$return = $api->consulta('GET', "/api/Relojes/{$nodo}/borrar_marcaciones");
		if($api->getStatusCode() != '200'){
				static::setErrores($return['mensajes']);
				return false;
		}
		return $return;
	}

	static public function obtenerEstadoRelojes() {
		$config = \FMT\Configuracion::instancia();
		Biometrica::init($config['biometrica']['server']);
		$api = static::getInstance();
		$api->setQuery([]);
		$return = $api->consulta('GET', "/api/Relojes/estado");
		if($api->getStatusCode() != '200'){
				static::setErrores($return['mensajes']);
				return false;
		}
		return $return;
	}

	/**
	 * Da de alta un nuevo reloj
	 *
	 * @param [type] $data
	 * @return array
	 */
	static public function alta_daemon($data) {
		if(empty($data)){
			return false;
		}
		$config = \FMT\Configuracion::instancia();
 		Biometrica::init($config['biometrica']['server']);
		$api = static::getInstance();
		$return 		= $api->consulta('POST', "/api/Relojes/agregar", json_encode($data, JSON_UNESCAPED_UNICODE));
		if($api->getStatusCode() > 200){
			static::setErrores($return);
			return false;
		}
		return $return;
	}

	/**
	 * Recarga el reloj
	 *
	 * @param [type] $data
	 * @return array
	 */
	static public function recargar_daemon($data) {
		if(empty($data)){
			return false;
		}
		$config = \FMT\Configuracion::instancia();
		Biometrica::init($config['biometrica']['server']);
		$api = static::getInstance();
		$return 		= $api->consulta('POST', "/api/Relojes/recargar", json_encode($data, JSON_UNESCAPED_UNICODE));

		if($api->getStatusCode() > 200){
			static::setErrores($return);
			return false;
		}
		return $return;
	}

	/**
	 * Templates Distribuir documentos
	 *
	 * @param [type] $data
	 * @return array
	 */
	static public function distribuir_documentos($data,$documento) {
		$config = \FMT\Configuracion::instancia();
		Biometrica::init($config['biometrica']['server']);
		$api = static::getInstance();
		$api->setQuery([]);
		$return = $api->consulta('POST', "/api/Templates/distribuir/{$documento}", json_encode($data, JSON_UNESCAPED_UNICODE));
		if($api->getStatusCode() != '200'){
				static::setErrores($return['mensajes']);
				return false;
		}
		return $return;
	}

	/**
	 * Access ID
	 *
	 * @param [int] $accessID
	 * @return array
	 */
	static public function accessId($accessID) {
		$config = \FMT\Configuracion::instancia();
		Biometrica::init($config['biometrica']['server']);
		$api = static::getInstance();
		$api->setQuery([]);
		$return = $api->consulta('GET', "/api/templates/accessId/{$accessID}");
		if($api->getStatusCode() != '200'){
				static::setErrores($return['mensajes']);
				return false;
		}
		if(is_array($return)){
			$return = array_map(function($e){
				return json_decode(json_encode($e), false);
			}, $return);
		}
		return $return;
	}

	/**
	 * Baja de Enrolado
	 *
	 * @param [int] $accessID
	 * @return array
	 */
	static public function baja_enrolado($accessID) {
		$config = \FMT\Configuracion::instancia();
		Biometrica::init($config['biometrica']['server']);
		$api = static::getInstance();
		$api->setQuery([]);
		$return = $api->consulta('DELETE', "/api/Templates/accessId/{$accessID}");
		if($api->getStatusCode() != '200'){
				static::setErrores($return['mensajes']);
				return false;
		}
		try {
			return $return['status'];
		} catch (\Exception $e) {
			return false;
		}
	}


	/**
	 * Actualiza el Nro de Tarjeta
	 *
	 * @param [type] $data
	 * @return bolean
	 */
	static public function actualizarNroTarjeta($data,$nroTarjeta) {
		$config = \FMT\Configuracion::instancia();
		Biometrica::init($config['biometrica']['server']);
		$api = static::getInstance();
		$api->setQuery([]);
		$return = $api->consulta('POST', "/api/templates/distribuirTarjeta/{$nroTarjeta}", json_encode($data, JSON_UNESCAPED_UNICODE));
		if($api->getStatusCode() != '200'){
				static::setErrores($return['mensajes']);
				return false;
		}
		return $return;
	}

	/**
	 * Desenrola el Nro de Tarjeta
	 *
	 * @param [type] $data
	 * @return bolean
	 */
	static public function actualizarNroTarjetaDesenrolar($data,$nroTarjeta) {
		$config = \FMT\Configuracion::instancia();
		Biometrica::init($config['biometrica']['server']);
		$api = static::getInstance();
		$api->setQuery([]);
		$return = $api->consulta('POST', "/api/templates/distribuirTarjetaDesenrolar/{$nroTarjeta}", json_encode($data, JSON_UNESCAPED_UNICODE));
		if($api->getStatusCode() != '200'){
				static::setErrores($return['mensajes']);
				return false;
		}
		return $return;
	}

	static protected function setErrores($data=false){
			static::$ERRORES = $data;
	}
	
	static public function getErrores(){
			return static::$ERRORES;
	}

		/**
	 * Distribuye templates
	 *
	 * @param [type] $data
	 * @return array
	 */
	static public function distribuir_templates($documento,$data) {
		if(empty($data)){
			return false;
		}
		$config = \FMT\Configuracion::instancia();
		Biometrica::init($config['biometrica']['server']);
		$api = static::getInstance();
		$url	= "/api/templates/distribuir/{$documento}";
		$return 		= $api->consulta('POST', $url, json_encode($data, JSON_UNESCAPED_UNICODE));

		if($api->getStatusCode() > 200){
			static::setErrores($return);
			return false;
		}
		return $return;
	}
	
	/**
	 * Templates Delete 
	 *
	 * @param [type] $data
	 * @deprecated Identico a baja_enrolado
	 * @return array
	 */
	static public function delete_templates($data) {
		$config = \FMT\Configuracion::instancia();
		Biometrica::init($config['biometrica']['server']);
		$api = static::getInstance();
		$api->setQuery([]);
		$return = $api->consulta('DELETE', "/api/templates/accessId/{$data['accessId']}", json_encode($data, JSON_UNESCAPED_UNICODE));
		if($api->getStatusCode() != '200'){
				static::setErrores($return['mensajes']);
				return false;
		}
		try {
			return $return['status'];
		} catch (\Exception $e) {
			return false;
		}
	}
	
}