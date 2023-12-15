<?php
use App\Helper\Biometrica;

interface ApiInterface {
    public static function init($endpoint=null, $curl_setopt=null);
    static public function consulta($metodo=null, $recurso=null, $data=false);
   }
   
   class ApiInterna implements ApiInterface {
       public static $ENDPOINT = 'https://cap-testing.transporte.gob.ar';
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

           $query_params   = static::parseQuery($getData);
   
           curl_setopt($curl, CURLOPT_URL, $endpoint . $recurso . $query_params);
           curl_setopt($curl, CURLOPT_URL, $endpoint . $recurso . $query_params);
           
           switch ($metodo) {
               case static::METHOD_POST:
                   curl_setopt($curl, CURLOPT_POST, true);
                   curl_setopt($curl, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
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
               $data = (is_array($postData)) ? http_build_query($postData) : $postData;
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
    private static $NODO_SRC = 3; // Nodo enrolador Testing
    // 36724020 (sarca) 32436564 (mgiraldi) 11916349 (jcbartellini) 12890296 (fperezarrieu)
    private static $DNI_PRUEBAS = '36724020';
    private static $TARJETA_PRUEBAS = '50120189';
    
    private static function curlPost($accion=null, $data=array()){
        return ApiInterna::consulta('POST', $accion, [], $data);
    }
    private static function curlGet($accion=null, $data=array()){
        return ApiInterna::consulta('GET', $accion, $data, []);
    }
    private static function curlDelete($accion=null, $data=array()){
        return ApiInterna::consulta('GET', $accion, $data, []);
    }

    public function testPostAgregarReloj(){
        try {
            $resp = static::curlPost('/api/Relojes/agregar', json_encode([
                'ip'               => '127.0.0.1',
                'dns'              => 'acceso-desarrollo2',
                'puerto'           => 3001,
                'enrolador'        => false,
                'id'               => static::$NODO_SRC,
                'nodo'             => static::$NODO_SRC,
                'ultima_marcacion' => (new DateTime('now'))->format('Y-m-d H:i:s'),
                'habilitado'       => true,
            ], JSON_UNESCAPED_UNICODE));
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(is_object(json_decode($resp->data, false)), 'No es un objeto: '.$resp->data);
            $this->assertTrue(is_bool((json_decode($resp->data, false))->status), 'Estatus debe ser booleano: '.$resp->data);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    
    public function testGetEstadoNodo(){
        try {
            $resp   = static::curlGet("/api/Relojes/".static::$NODO_SRC."/estado");
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(is_array(json_decode($resp->data, false)), 'La respuesta no es un array');
            $data   = json_decode($resp->data, false);
            if(!empty($data)) {
                $this->assertTrue(is_bool($data[0]), 'La posicion 0 no es booleano, es: '. $data[0]);
                $this->assertTrue(is_string($data[1]), 'La posicion 1 no es string, es: '.$data[1]);
            }
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    public function testGetEstadoRelojes(){
        try {
            $resp   = static::curlGet("/api/Relojes/estado");
            $this->assertTrue($resp->status_code == '200', 'StatusCode no es 200, es :'.$resp->status_code);
            $this->assertTrue(is_object(json_decode($resp->data, false)), 'La respuesta no es un objeto');
            $data   = json_decode($resp->data, false);

            if(!empty($data)) {
                $data   = $data->{(string)static::$NODO_SRC};
                $this->assertTrue(is_array($data), 'La respuesta no es un array');
                $this->assertTrue(is_bool($data[0]), 'La posicion 0 no es booleano, es: '. $data[0]);
                $this->assertTrue(is_string($data[1]), 'La posicion 1 no es string, es: '.$data[1]);
            }
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }

    public function testConHelperPostAgregarReloj(){
        try {
            $resp   = Biometrica::alta_daemon([
                'ip'               => '127.0.0.1',
                'dns'              => 'acceso-desarrollo2',
                'puerto'           => 3001,
                'enrolador'        => false,
                'id'               => static::$NODO_SRC,
                'nodo'             => static::$NODO_SRC,
                'ultima_marcacion' => (new DateTime('now'))->format('Y-m-d H:i:s'),
                'habilitado'       => true,
            ]);
            $status_code = (Biometrica::getInstance())->getStatusCode();
            $this->assertTrue($status_code == '200', 'StatusCode no es 200, es :'.$status_code);
            $this->assertTrue(is_array($resp), 'No es un array: '.json_encode($resp));
            $this->assertTrue(is_bool($resp['status']), 'Estatus debe ser booleano: '.$resp['status']);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    
    public function testConHelperGetEstadoRelojes(){
        try {
            $resp   = Biometrica::obtenerEstadoRelojes();
            $status_code = (Biometrica::getInstance())->getStatusCode();
            // $resp   = static::curlGet("/api/Relojes/estado");
            $this->assertTrue($status_code == '200', 'StatusCode no es 200, es :'.$status_code);
            $this->assertTrue(is_array($resp), 'La respuesta no es un array');
            $data   = $resp;

            if(!empty($data)) {
                $data   = $data[(string)static::$NODO_SRC];
                $this->assertTrue(is_array($data), 'La respuesta no es un array');
                $this->assertTrue(is_bool($data[0]), 'La posicion 0 no es booleano, es: '. $data[0]);
                $this->assertTrue(is_string($data[1]), 'La posicion 1 no es string, es: '.$data[1]);
            }
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    
    public function testConHelperPostRecargarDaemon(){
        try {
            $resp   = Biometrica::recargar_daemon([
				'ip'               => '127.0.0.1',
                'dns'              => 'acceso-desarrollo2',
                'puerto'           => 3001,
                'enrolador'        => false,
                'id'               => static::$NODO_SRC,
                'nodo'             => static::$NODO_SRC,
                'ultima_marcacion' => (new DateTime('now'))->format('Y-m-d H:i:s'),
			]);
            $status_code = (Biometrica::getInstance())->getStatusCode();
            // $resp   = static::curlGet("/api/Relojes/estado");
            $this->assertTrue($status_code == '200', 'StatusCode no es 200, es :'.$status_code);
            $this->assertTrue(is_array($resp), 'La respuesta no es un array');
            $this->assertTrue(is_bool($resp['status']), 'El status no es booleano: '.$resp['status']);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    
    public function testConHelperGetObtenerTemplatePorDNI(){
        try {
            $resp   = Biometrica::accessId(static::$DNI_PRUEBAS);
            $status_code = (Biometrica::getInstance())->getStatusCode();
            // $resp   = static::curlGet("/api/Relojes/estado");
            $this->assertTrue($status_code == '200', 'StatusCode no es 200, es :'.$status_code);
            $this->assertTrue(is_array($resp), 'La respuesta no es un array', serialize($resp));
            $this->assertTrue(is_object($resp[0]), 'La respuesta no es un objeto', serialize($resp));
            $data   = $resp[0];
            $this->assertTrue(is_numeric($data->index), 'index no es numerico ');
            $this->assertTrue(is_string($data->data), 'data no es string ');
            $this->assertTrue(is_numeric($data->accessId), 'accessId no es numerico');
            $this->assertTrue(($data->accessId == static::$DNI_PRUEBAS), 'El accessId no coincide con el valor esperado. Esperado:'.static::$DNI_PRUEBAS.' Respuesta: '.$data->accessId);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    /**
     * Accion: Elimina el registro {nroTarjeta} de todos los nodos pasados, y lo vuelve a cargar.
     * Se asume que el proceso de eliminar es para evitar duplicidad o colision en caso de existir.
     *
     * @return void
     */
    public function testConHelperPostActualizarNroTarjeta(){
        try {
            $resp   = Biometrica::actualizarNroTarjeta([
                'nodes' => [(int)static::$NODO_SRC],
            ], (int)static::$TARJETA_PRUEBAS);
            $status_code = (Biometrica::getInstance())->getStatusCode();
            // $resp   = static::curlGet("/api/Relojes/estado");
            $this->assertTrue($status_code == '200', 'StatusCode no es 200, es :'.$status_code);
            $this->assertTrue(is_array($resp), 'La respuesta no es un array', json_encode($resp));
            $this->assertTrue(is_bool($resp['status']), 'status no es boolean '.$resp['status']);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    /**
     * Accion: Elimina el registro {nroTarjeta} de todos los nodos pasados
     *
     * @return void
     */
    public function testConHelperPostActualizarNroTarjetaDesenrolar(){
        try {
            $resp   = Biometrica::actualizarNroTarjetaDesenrolar([
                'nodes' => [(int)static::$NODO_SRC],
            ], (int)static::$TARJETA_PRUEBAS);
            $status_code = (Biometrica::getInstance())->getStatusCode();
            // $resp   = static::curlGet("/api/Relojes/estado");
            $this->assertTrue($status_code == '200', 'StatusCode no es 200, es :'.$status_code);
            $this->assertTrue(is_array($resp), 'La respuesta no es un array', json_encode($resp));
            $this->assertTrue(is_bool($resp['status']), 'status no es boolean '.$resp['status']);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    /**
     * Busca la instancia de Reloj por nodo, y envia la orden de limpiar registros internos (marcaciones offline)
     *
     * @return void
     */
    public function testConHelperGetSincronizarMarcacionesBorrar(){
        try {
            $resp   = Biometrica::sincronizar_marcaciones_borrar(static::$NODO_SRC);
            $status_code = (Biometrica::getInstance())->getStatusCode();
            // $resp   = static::curlGet("/api/Relojes/estado");
            $this->assertTrue($status_code == '200', 'StatusCode no es 200, es :'.$status_code);
            $this->assertTrue(is_array($resp), 'La respuesta no es un array', json_encode($resp));
            $this->assertTrue(is_bool($resp['status']), 'status no es boolean '.$resp['status']);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    public function testConHelperPostDistribuirDocumentos(){
        try {
            $resp   = Biometrica::distribuir_documentos([
                'templates' => \App\Modelo\Template::listarPorPersona(static::$DNI_PRUEBAS),
                'nodes'     => [(int)static::$NODO_SRC],
            ],static::$DNI_PRUEBAS);
            $status_code = (Biometrica::getInstance())->getStatusCode();
            // $resp   = static::curlGet("/api/Relojes/estado");
            $this->assertTrue($status_code == '200', 'StatusCode no es 200, es :'.$status_code);
            $this->assertTrue(is_array($resp), 'La respuesta no es un array', json_encode($resp));
            $this->assertTrue(is_bool($resp['status']), 'status no es boolean '.$resp['status']);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    public function testConHelperPostDistribuirTemplates(){
        try {
            $resp   = Biometrica::distribuir_templates(static::$DNI_PRUEBAS,
            [
                'templates' => \App\Modelo\Template::listarPorPersona(static::$DNI_PRUEBAS),
                'nodes'     => [(int)static::$NODO_SRC],
            ]);
            $status_code = (Biometrica::getInstance())->getStatusCode();
            // $resp   = static::curlGet("/api/Relojes/estado");
            $this->assertTrue($status_code == '200', 'StatusCode no es 200, es :'.$status_code);
            $this->assertTrue(is_array($resp), 'La respuesta no es un array', json_encode($resp));
            $this->assertTrue(is_bool($resp['status']), 'status no es boolean '.$resp['status']);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    public function testConHelperPostDeleteTemplates(){
        try {
            $resp   = Biometrica::delete_templates(
            [
                'accessId' => static::$DNI_PRUEBAS,
            ]);
            $status_code = (Biometrica::getInstance())->getStatusCode();
            // $resp   = static::curlGet("/api/Relojes/estado");
            $this->assertTrue($status_code == '200', 'StatusCode no es 200, es :'.$status_code);
            $this->assertTrue(is_bool($resp), '$resp no es boolean '.$resp);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    public function testConHelperPostBajaEnEnrolador(){
        try {
            $resp   = Biometrica::baja_enrolado(static::$DNI_PRUEBAS);
            $status_code = (Biometrica::getInstance())->getStatusCode();
            // $resp   = static::curlGet("/api/Relojes/estado");
            $this->assertTrue($status_code == '200', 'StatusCode no es 200, es :'.$status_code);
            $this->assertTrue(is_bool($resp), '$resp no es boolean '.$resp);
            $this->assertTrue($resp === true, '$resp no es true '.$resp);
        } catch (\Exception $e) {
            $this->assertTrue(false, $e);
        }
    }
    
}

// ./vendor/bin/phpunit --filter testConHelperPostDistribuirDocumentos pruebas_unitarias/ApiCapTest.php
// ./vendor/bin/phpunit --filter testConHelperPostBajaEnEnrolador pruebas_unitarias/ApiCapTest.php