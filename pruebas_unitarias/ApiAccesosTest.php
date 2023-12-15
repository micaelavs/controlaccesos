<?php
/** ESCRIBIR ESTE SET COMPLETO DEMORO 1HS 30 MINUTOS, no juzguen */

interface ApiInterface {
 public static function init($endpoint=null, $curl_setopt=null);
 static public function consulta($metodo=null, $recurso=null, $data=false);
}

class ApiInterna implements ApiInterface {
    public static $ENDPOINT = 'https://controlaccesos-testing.transporte.gob.ar/api.php';
    // public static $ENDPOINT = 'https://sarca-controlaccesos.dev.transporte.gob.ar/api.php';
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
class ApiAccesosTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Numero de nodo
     * @var int
     */
    private static $NODO_SRC = 2;
    private static $DNI_PRUEBAS     = '36724020';

    
    const TARJETA_VALIDA              = '50120154';
    // 36724020 (sarca) 32436564 (mgiraldi) 11916349 (jcbartellini) 12890296 (fperezarrieu)
    const DNI_ACTIVO_ENROLADO         = '36724020';
    const DNI_ACTIVO_CONTRATISTA      = '789987';
    // const DNI_ACTIVO_CONTRATISTA      = '125';
    
    const DNI_ACTIVO_NO_ENROLADO      = '';
    const DNI_INACTIVO_ENROLADO       = '37285479';
    const DNI_INACTIVO_NO_ENROLADO    = '20361793';
    
    /**
     * Los codigos que devuelve el API de CA, y que deveria recibir CAP
     */
    private static $RESP_API    = [
        \App\Api\Api::INGRESO_CORRECTO        => 'INGRESO_CORRECTO',
	    \App\Api\Api::EGRESO_CORRECTO         => 'EGRESO_CORRECTO',
	    \App\Api\Api::UBICACION_NO_AUTORIZADA => 'UBICACION_NO_AUTORIZADA',
	    \App\Api\Api::NODO_INVALIDO           => 'NODO_INVALIDO',
	    \App\Api\Api::DOCUMENTO_INVALIDO      => 'DOCUMENTO_INVALIDO',
	    \App\Api\Api::INGRESO_DUPLICADO       => 'INGRESO_DUPLICADO',
	    \App\Api\Api::EGRESO_DUPLICADO        => 'EGRESO_DUPLICADO',
	    \App\Api\Api::ERROR_INGRESO           => 'ERROR_INGRESO',
	    \App\Api\Api::ERROR_EGRESO            => 'ERROR_EGRESO',
    ];
    
    private static function curlPost($accion=null, $data=array()){
        return ApiInterna::consulta('POST', $accion, [], $data);
    }
    private static function curlGet($accion=null, $data=array()){
        return ApiInterna::consulta('GET', $accion, $data, []);
    }

    public function testAccesoIngresoBiometrico(){
        try {
            // Datos que enviaria CAP al momento de ingresar una persona
            $resp   = static::curlPost('acceso', [
                'access_id'  => (int)static::DNI_ACTIVO_ENROLADO, // DNI o Nro de Tarjeta
                'src_node'   => (int)static::$NODO_SRC,           // Nodo del reloj
                'source_id'  => \App\Api\Api::FUENTE_BIOMETRICO,  // Fuente desde donde se obtuvo el access_id
                'date_time'  => (new \DateTime('now'))->format('d/m/Y H:i'), // No se usa
                'direction'  => \App\Api\Api::DIRECCION_ENTRADA,  // Direccion/Sentido fisico (entrada o salida) (no se usa)
            ]);
            if(
                (is_numeric($resp->data) && $resp->data < \App\Api\Api::INGRESO_CORRECTO)
                || !is_numeric($resp->data)
            ) {
                if(is_numeric($resp->data)){ echo "Hay tirar var_dump del lado del Api para leer las respuesta... a debugear se a dicho ! ";}
                echo "\n";
                echo $resp->data;
                echo "\n";
                die;
            }
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(in_array($resp->data, array_keys(static::$RESP_API)), 'El codigo de respuesta no esta entre las opciones validas.'.json_encode($resp->data));
            $this->assertTrue($resp->data == \App\Api\Api::INGRESO_CORRECTO, 'Se esperaba INGRESO_CORRECTO y se obtuvo: '.static::$RESP_API[$resp->data]);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    /**
     * @depends testAccesoIngresoBiometrico
     */
    public function testAccesoEgresoBiometrico(){
        sleep(20);
        try {
            // Datos que enviaria CAP al momento de ingresar una persona
            $resp   = static::curlPost('acceso', [
                'access_id'  => (int)static::DNI_ACTIVO_ENROLADO, // DNI o Nro de Tarjeta
                'src_node'   => (int)static::$NODO_SRC,           // Nodo del reloj
                'source_id'  => \App\Api\Api::FUENTE_BIOMETRICO,  // Fuente desde donde se obtuvo el access_id
                'date_time'  => (new \DateTime('now'))->format('d/m/Y H:i'), // No se usa
                'direction'  => \App\Api\Api::DIRECCION_ENTRADA,  // Direccion/Sentido fisico (entrada o salida) (no se usa)
            ]);

            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(in_array($resp->data, array_keys(static::$RESP_API)), 'El codigo de respuesta no esta entre las opciones validas.'.json_encode($resp->data));
            $this->assertTrue($resp->data == \App\Api\Api::EGRESO_CORRECTO, 'Se esperaba EGRESO_CORRECTO y se obtuvo: '.static::$RESP_API[$resp->data]);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }

    public function testAccesoIngresoTarjeta(){
        try {
            // Datos que enviaria CAP al momento de ingresar una persona
            $resp   = static::curlPost('acceso', [
                'access_id'  => (int)static::TARJETA_VALIDA, // DNI o Nro de Tarjeta
                'src_node'   => (int)static::$NODO_SRC,           // Nodo del reloj
                'source_id'  => \App\Api\Api::FUENTE_TARJETA,  // Fuente desde donde se obtuvo el access_id
                'date_time'  => (new \DateTime('now'))->format('d/m/Y H:i'), // No se usa
                'direction'  => \App\Api\Api::DIRECCION_ENTRADA,  // Direccion/Sentido fisico (entrada o salida) (no se usa)
            ]);
            if(
                (is_numeric($resp->data) && $resp->data < \App\Api\Api::INGRESO_CORRECTO)
                || !is_numeric($resp->data)
            ) {
                if(is_numeric($resp->data)){ echo "Hay tirar var_dump del lado del Api para leer las respuesta... a debugear se a dicho ! ";}
                echo "\n";
                echo $resp->data;
                echo "\n";
                // die;
            }
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(in_array($resp->data, array_keys(static::$RESP_API)), 'El codigo de respuesta no esta entre las opciones validas.'.json_encode($resp->data));
            $this->assertTrue($resp->data == \App\Api\Api::INGRESO_CORRECTO, 'Se esperaba INGRESO_CORRECTO y se obtuvo: '.static::$RESP_API[$resp->data]);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }

    /**
     * @depends testAccesoIngresoTarjeta
     */
    public function testAccesoEgresoBuzonDeTarjeta(){
        sleep(20);
        try {
            // Datos que enviaria CAP al momento de ingresar una persona
            $resp   = static::curlPost('acceso', [
                'access_id'  => (int)static::TARJETA_VALIDA, // DNI o Nro de Tarjeta
                'src_node'   => (int)static::$NODO_SRC,           // Nodo del reloj
                'source_id'  => \App\Api\Api::FUENTE_BUZON_TARJETA,  // Fuente desde donde se obtuvo el access_id
                'date_time'  => (new \DateTime('now'))->format('d/m/Y H:i'), // No se usa
                'direction'  => \App\Api\Api::DIRECCION_SALIDA,  // Direccion/Sentido fisico (entrada o salida) (no se usa)
            ]);
            if(
                (is_numeric($resp->data) && $resp->data < \App\Api\Api::INGRESO_CORRECTO)
                || !is_numeric($resp->data)
            ) {
                if(is_numeric($resp->data)){ echo "Hay tirar var_dump del lado del Api para leer las respuesta... a debugear se a dicho ! ";}
                echo "\n";
                echo $resp->data;
                echo "\n";
                die;
            }
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(in_array($resp->data, array_keys(static::$RESP_API)), 'El codigo de respuesta no esta entre las opciones validas.'.json_encode($resp->data));
            $this->assertTrue($resp->data == \App\Api\Api::EGRESO_CORRECTO, 'Se esperaba EGRESO_CORRECTO y se obtuvo: '.static::$RESP_API[$resp->data]);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
}