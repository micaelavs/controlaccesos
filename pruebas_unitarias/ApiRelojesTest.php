<?php
/** ESCRIBIR ESTE SET COMPLETO DEMORO 1HS 30 MINUTOS, no juzguen */

interface ApiInterface {
 public static function init($endpoint=null, $curl_setopt=null);
 static public function consulta($metodo=null, $recurso=null, $data=false);
}

class ApiInterna implements ApiInterface {
    public static $ENDPOINT = 'https://controlaccesos-testing.transporte.gob.ar/api.php';
    static protected $METODOS_PERMITIDOS	= [
		self::METHOD_GET	=> self::METHOD_GET,
		self::METHOD_POST	=> self::METHOD_POST,
		self::METHOD_PUT	=> self::METHOD_PUT,
		self::METHOD_DELETE	=> self::METHOD_DELETE,
	];
    
    const METHOD_GET	= 'GET';
	const METHOD_POST	= 'POST';
	const METHOD_PUT	= 'PUT';
	const METHOD_DELETE	= 'DELETE';
    
    public static function init($endpoint = null, $curl_setopt=null){
		$endpoint	= static::$ENDPOINT;
		$curl		= \curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1); /// Testear si rompe
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);

		if(!empty($curl_setopt)){
			foreach($curl_setopt as $name_option => $value) {
				if(constant($name_option) !== null){
					curl_setopt($curl, constant($name_option), $value);
				}
			}
		}
        return $curl;
	}

    static function parseQuery($data=array(), $primer_parametro=true){
		if(empty($data)){
			return '';
		}
		$aux = '';
		foreach($data as $campo => $valor){
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
    
    static public function consulta($metodo=null, $recurso=null, $getData=array(), $postData=null){
        $curl       = static::init();
        $endpoint	= static::$ENDPOINT;
        $a = [
            'a' => $recurso,
        ];
        $a += $getData;
        $query_params   = static::parseQuery($a);

        curl_setopt($curl, CURLOPT_URL, $endpoint . $query_params);
        curl_setopt($curl, CURLOPT_URL, $endpoint . $query_params);
        
        switch ($metodo) {
			case static::METHOD_POST:
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER, [
					'Content-Type: multipart/form-data',
				]);
				break;
			case static::METHOD_GET:
				curl_setopt($curl, CURLOPT_HTTPHEADER, [
					'Content-Type: application/json',
				]);
				break;
			default:
				throw new \Exception("El metodo '{$metodo} no esta implementado. Pruebe con 'GET', 'PUT', 'POST' o 'DELETE'", 1);	
		}
        if($metodo == static::METHOD_POST ){
            $data = $postData;
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        $return = (object)[
            'data'          => curl_exec($curl),
            'status_code'   => (string)curl_getinfo($curl, CURLINFO_HTTP_CODE),
        ];
        curl_close($curl);
        return $return;
    }
}

/**
 * Las siguientes pruebas unitarias, comprueban el correcto funcionamiento del Api interno de Control de Accesos, simulando los mensajes que envia CAP (Control de Accesos Proxy) quien es el encargado de interactuar con los Relojes Biometricos
 */
class ApiRelojesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Numero de nodo
     * @var int
     */
    private static $NODO_SRC = 2;
    private static $DNI_PRUEBAS = '36724020';
    
    private static function curlPost($accion=null, $data=array()){
        return ApiInterna::consulta('POST', $accion, [], $data);
    }
    private static function curlGet($accion=null, $data=array()){
        return ApiInterna::consulta('GET', $accion, $data, []);
    }

    public function testSincronizarMarcaciones(){
        try {
            $fecha  = (new DateTime('now'))->format('Y-m-d H:i:s');
            $resp = static::curlPost('sincronizar_marcaciones', [
                'src_node'  => (int)static::$NODO_SRC,
                'total'     => (int)1,
                'mark['.$fecha.']'.(string)static::$DNI_PRUEBAS,
            ]);
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(empty($resp->data), 'Deveria devolver vacio, pero contiene: '.$resp->data);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    public function testAcceso(){
        try {
            $resp   = static::curlPost('acceso', [
                'access_id'  => (int)static::$DNI_PRUEBAS,
                'src_node'   => (int)static::$NODO_SRC,
                'source_id'  => (int)1,
                'date_time'  => '',
                'direction'  => '',
            ]);
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue($resp->data >= 2000);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    public function testGuardarLogReloj(){
        try {
            $resp   = static::curlPost('guardar_log_reloj', [
                'msj'  => (int)static::$DNI_PRUEBAS,
                'err'   => (int)2,
                'src_node'  => (int)static::$NODO_SRC,
            ]);
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(($resp->data == true  || $resp->data == false), 'La respuesta debe ser boleano, pero es: '.$resp->data);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    public function testIndex(){
        try {
            $resp   = static::curlGet('index');
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(is_string((json_decode($resp->data, false))->message), 'message no es string');
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    public function testProbarConexion(){
        try {
            $resp   = static::curlGet('probar_conexion');
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(is_string($resp->data), 'la respuesta no es string');
            $this->assertTrue(((\DateTime::createFromFormat('Y-m-d H:i:s', $resp->data)) instanceof \DateTime), 'la no tiene fecha valida con formato Y-m-d H:i:s');
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    public function testListarRelojes(){
        try {
            $resp   = static::curlGet('listar_relojes');
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(is_array(json_decode($resp->data, false)), 'la respuesta no es array');
            $data = json_decode($resp->data, false);
            if(!empty($data)){
                $data   = $data[0];
                $this->assertTrue(is_numeric($data->id), 'id no es numerico');
                $this->assertTrue(is_numeric($data->nodo), 'nodo no es numerico');
                $this->assertTrue(is_numeric($data->puerto), 'puerto no es numerico');
                $this->assertTrue(!empty($data->ip), 'ip no tiene contenido');
                $this->assertTrue(is_string($data->dns), 'dns no es string');
                $this->assertTrue(is_string($data->ultima_marcacion), 'ultima_marcacion no es string');
            }
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    public function testBuscarRelojEnrolador (){
        try {
            $resp   = static::curlGet('bucar_reloj_enrolador');
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue((json_decode($resp->data, false) instanceof \stdClass), 'la respuesta no es json');
            $data   = json_decode($resp->data, false);

            $this->assertTrue(is_numeric($data->id), 'id no es numerico');
            $this->assertTrue(is_numeric($data->nodo), 'nodo no es numerico');
            $this->assertTrue(is_numeric($data->puerto), 'puerto no es numerico');
            $this->assertTrue(!empty($data->ip), 'ip no tiene contenido');
            $this->assertTrue(is_string($data->dns), 'dns no es string');
            $this->assertTrue((is_bool($data->enrolador) || $data->enrolador == 'true'), 'enrolador no es booleano, es: '.$data->enrolador);
            $this->assertTrue(is_string($data->ultima_marcacion), 'ultima_marcacion no es string');
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
}