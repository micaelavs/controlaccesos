<?php
namespace App\Helper;
/**
 * Ejemplo para implementacion.
   ```php
   Test::init('http://localhost/algun_proyecto/api.php');
   Test::getEmpleado('20121231237');
   class Test extends \FMT\ApiCurlBiometrica {
 	static public function getEmpleado($cuit=null, $by_id=false){
  		$api = static::getInstance();
  		$api->setQuery([
			'contiene'	=> ['situacion_escalafonaria','persona'],
			'by_id'		=> $by_id
		]);
		$return = $api->consulta('GET', "/agentes/{$cuit}", $data=null);
 	}
   }```
*/
abstract class ApiCurlBiometrica {
/** @var ApiCurlBiometrica:: */
	protected static $instance= array();
/** @var string */
	private $endpoint		= null;
/** @var \Curl:: */
	private $curl			= null;
/** @var int */
	private $status_code	= null;
/** @var array */
	private $query_params	=[];
/** @var boolean */
	private $debug			= false;
/** @var boolean|array */
	private $putFile		= false;
/** @var string */
	private $api_token		= null;
/** @var string */
	private $api_client_id	= null;

	const METHOD_GET	= 'GET';
	const METHOD_POST	= 'POST';
	const METHOD_PUT	= 'PUT';
	const METHOD_DELETE	= 'DELETE';

	static protected $METODOS_PERMITIDOS	= [
		self::METHOD_GET	=> self::METHOD_GET,
		self::METHOD_POST	=> self::METHOD_POST,
		self::METHOD_PUT	=> self::METHOD_PUT,
		self::METHOD_DELETE	=> self::METHOD_DELETE,
	];

	final public function __construct(){}
	final public function __destruct(){
		if(is_resource($this->curl)){
			curl_close($this->curl);
		}
	}
/**
 * Sirve para trabajar siempre sobre la misma instancia inicializada.
 * @return ApiCurlBiometrica::
*/
	static public function getInstance(){
		$hije	= get_called_class();
		if(!(isset(static::$instance[$hije]) && static::$instance[$hije] !== null)){
			return static::$instance[$hije]	= new static();
		}
		return static::$instance[$hije];
	}

/**
 * Interfaz para inicializar el objeto.
 * @param string $endpoint - Endpoint donde realizar la consulta.
 * @param array  $curl_setopt - Parametros extra para `curl_setopt`.
 * @return ApiCurlBiometrica::
*/
	public static function init($endpoint=null, $curl_setopt=null){
		static::getInstance()->_init($endpoint, $curl_setopt);
		return static::getInstance();
	}

/**
 * Setea el endpoint de consulta y los seteos principales para inicializar CURL.
 *
 * @param string	$endpoint	- URL de consulta
 * @return void
*/
	final private function _init($endpoint = null, $curl_setopt=null){
		$this->endpoint	= $endpoint;
		$this->curl		= \curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
		curl_setopt($this->curl, CURLOPT_FORBID_REUSE, 1); /// Testear si rompe
		curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);

		if(!empty($curl_setopt)){
			foreach($curl_setopt as $name_option => $value) {
				if(constant($name_option) !== null){
					curl_setopt($this->curl, constant($name_option), $value);
				}
			}
		}
	}

/**
 * Checkea que el objeto este inicializado correctamente,
 *
 * @throws Exception - En caso de no estar iniciliazado correctamente.
*/
	private function checkInit(){
		if($this->endpoint === null){
			throw new \Exception("El api no fue inicializada correctamente", 1);
		}
	}

	final protected function debug(){
		$this->debug	= true;
	}

/**
 * Permite debugear la respuesta del API. 
 * Retorna lo que el servidor de destino genere en formato crudo y corta la ejecucion.
 * No afecta el comportamiento, simplemente no parsea la respuesta.
 *
 * @return void
 */
	final static public function activarDebug(){
		static::getInstance()->debug();
	}
/**
 * Realiza la consulta CURL segun tipo de metodo especificado. Combina el endpoint, con el recurso solicitado,  mas los parametros pasados por ->setQuery().
 * E.j-1:
   ``` php
   $api->setQuery([
   	'contiene'	=> ['situacion_escalafonaria','persona' => ['titulos', 'domicilio']],
   	'by_id'		=> $by_id
   ]);
   $api->consulta('GET', "/agentes/{$cuit}", $data=null);
   ```
* E.j-2:
   ``` php
   $api->consulta('POST',"/agentes/{$cuit}", $data=null);
   ```
 *
 * @param string	$metodo - Alguno de los metodos
 * @param string	$recurso - Son los parametros que se le agregan a la URL del endpoint.
 * @param array|object		$data - Si es array, cada indice se envia como un campo distinto, si es objeto se convierte a json y se agrega se envia como campo `data`
 *
 * @return bool|array
 * @throws Exception	- Si el metodo no es valido
*/
	final protected function consulta($metodo=null, $recurso=null, $data=false){
		$this->checkInit();

		curl_setopt($this->curl, CURLOPT_URL, $this->endpoint . $recurso . $this->getQuery());

		$autenticacion_oauth= '';

		switch ($metodo) {
			case static::METHOD_POST:
				curl_setopt($this->curl, CURLOPT_POST, true);
				curl_setopt($this->curl, CURLOPT_SAFE_UPLOAD, true);
				curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
					'Content-Type: application/json',
					$autenticacion_oauth,
				]);
				break;
			case static::METHOD_PUT:
				curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($this->curl, CURLOPT_SAFE_UPLOAD, true);
				curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
					'Content-Type: application/json',
					$autenticacion_oauth,
				]);
				break;
			case static::METHOD_DELETE:
				curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
				curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
					$autenticacion_oauth,
				]);
				break;
			case static::METHOD_GET:
				curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
					'Content-Type: application/json',
					$autenticacion_oauth,
				]);
				break;
			default:
				throw new \Exception("El metodo '{$metodo} no esta implementado. Pruebe con 'GET', 'PUT', 'POST' o 'DELETE'", 1);	
		}

		if(is_object($data)){
			$data	= json_encode($data);
		}
		if(!empty($data) && ($metodo == static::METHOD_POST || $metodo == static::METHOD_PUT || $metodo == static::METHOD_DELETE)){
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
		}

		$return	= curl_exec($this->curl);
		if($this->debug === true){
			$this->setStatusCode(curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
			echo "API Status Code: {$this->getStatusCode()} <br />";
			echo $return;
			$this->reset();
			exit;
		}
        try {
            $return	= json_decode($return, true);
        } catch (\Exception $e) {
            $return	= $return;
        }
		$this->setStatusCode(curl_getinfo($this->curl, CURLINFO_HTTP_CODE));

		$this->reset();
		return $return;
	}

/**
 * Setea el status code que devuelva la consulta realizada.
 *
 * @param int	$code - status code
 * @return void
*/
	protected function setStatusCode($code=null){
		$this->status_code	= $code;
	}

/**
 * Obtiene el Status Code de la consulta realizada.
 * @return int
*/
	public function getStatusCode(){
		return $this->status_code;
	}

/**
 * Se setean los parametros que seran pasados por url al hacer una consulta.
 *
 * @param array	$query_params	- Parametros con formato ['variable' => 'valor']
 * @return void
*/
	final protected function setQuery($query_params=null){
		if(!is_array($query_params)){
			throw new \Exception("Solo acepta un array como parametro", 1);
		}
		$this->query_params	= $query_params;
	}

/**
 * Devuelve los parametros seteados con "$this->setQuery()"  como string parcesado valido para pasarlo como query por URL.
 * E.j: "?variable1=valor1&variable2=valor2"
 *
 * Si $primer_parametro es false, el resultado seria similar a; "&variable1=valor1&variable2=valor2"
 *
 * @param boolean	$primer_parametro	- default: true- En false hace que el primer parametro no empieze con "?".
 * @return string
*/
	final private function getQuery($primer_parametro=true){
		if(empty($this->query_params)){
			return '';
		}
		$aux = '';
		foreach($this->query_params as $campo => $valor){
			if(is_array($valor)) {
				$valor	= json_encode($valor);
			}
			$valor = urlencode($valor);
			if($valor	!== ''){
				if (!empty($aux) || $primer_parametro === false) {
					$aux .= '&' . $campo . '=' . $valor;
				} else {
					$aux .= '?' . $campo . '=' . $valor;
				}
			}
		}
		return $aux;
	}

/**
 * Vuelve los paramentros a su estado inicial.
 * @return void
*/
	private function reset(){
		$this->query_params	=[];
		$this->debug		= false;
		$this->putFile		= false;
	}
}
