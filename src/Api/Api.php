<?php
namespace App\Api;

use App\Helper\Vista;
use App\Modelo\Acceso;
use App\Modelo\AccesoContratista;
use App\Modelo\AccesoEmpleado;
use App\Modelo\AccesoVisita;
use App\Modelo\AccesoVisitaEnrolada;
use App\Modelo\AlertaReloj;
use App\Modelo\ContratistaEmpleado;
use App\Modelo\Credencial;
use App\Modelo\Empleado;
use App\Modelo\Persona;
use App\Modelo\Reloj;
use App\Modelo\Visita;
use App\Modelo\Usuario;
use App\Modelo\AppRoles;
use DateTime;
use DateInterval;


/**
 * Si modifica metodos de este API pruebar despues con este comando:
 * vendor/bin/phpunit pruebas_unitarias/ApiRelojesTest.php
 */
class Api extends \FMT\Controlador {

    // Datos que se envian a CAP
	const INGRESO_CORRECTO = 2000;
	const EGRESO_CORRECTO = 2001;
	const UBICACION_NO_AUTORIZADA = 2002;
	const NODO_INVALIDO = 2003;
	const DOCUMENTO_INVALIDO = 2004;
	const INGRESO_DUPLICADO = 2005;
	const EGRESO_DUPLICADO = 2006;
	const ERROR_INGRESO = 2100;
	const ERROR_EGRESO = 2101;
    
    // Datos que vienen de CAP, Fuente de la marcacion
    const FUENTE_BUZON_TARJETA = 0; // En realidad es el lector externo, que es donde se suele conectar el
    const FUENTE_TARJETA       = 1; // Es el lector interno de tarjetas RFID
    const FUENTE_BIOMETRICO    = 4;

    // Datos que vienen de CAP.
    // La direccion se configura en el equipo (puede ser erroneo) y depende de la hubicacion fisica
    const DIRECCION_DEFAULT             = 0;
    const DIRECCION_ENTRADA             = 200;
    const DIRECCION_SALIDA              = 201;
    const DIRECCION_ENTRADA_INTERMEDIA  = 202;
    const DIRECCION_SALIDA_INTERMEDIA   = 203;
    const DIRECCION_INDEFINIDO          = 204;

	protected function accion_index(){
        $data   = [];
        $data["message"] = "API de comunicación";
        (new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}

	protected function accion_guardar_log_reloj()
	{
			$msj = $this->request->post('msj');
			$err = $this->request->post('err');
			$src_node = (int)$this->request->post('src_node');
			$res = false;

			if (Reloj::guardar_log($src_node, $err, $msj)) {
                // $alerta = AlertaReloj::obtener();
                // $alerta->envioDeEmails($src_node, $err, $msj);
                $res = true;
			}
            $data = $res;
			(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}

	public function accion_acceso()
    {
        $data = 0;
        try {
            $documento = (int)$this->request->post('access_id');
            $src_node = (int)$this->request->post('src_node');

            $fuente = (int)$this->request->post('source_id');
            $esEntrada=false;

            if ($fuente == static::FUENTE_TARJETA || $fuente == static::FUENTE_BUZON_TARJETA) {
                $persona = Persona::obtenerPorTarjeta($documento); //busca la persona en los accesos de visitas
                if (empty($persona->id)) {
                    $persona = Persona::obtenerPorTarjetaContratista($documento); //busca la persona en los accesos de contratistas
                }                
            } else{
                $persona = Persona::obtenerPorDocumento($documento);
            }            

            if (!empty($persona->id)) {
                $nodo = Reloj::obtenerPorNodo($src_node);
                if (!empty($nodo->id)) {

                    $personaUsuario = Persona::obtenerPorDocumento($src_node);
                    $ahora = new DateTime();

                    $ingresoTarjetaEnrolada=false;
                    switch ($persona->tipo_persona) {
                        case Acceso::EMPLEADO:
                            $ingresante = Empleado::obtenerPorDocumento($documento);
                            $existeAcceso = AccesoEmpleado::enVisita($ingresante->documento, $nodo->ubicacion_id, $ahora);
                            $visita_enrolada = null;
                            break;
                        case Acceso::VISITA_ENROLADA:
                            $ingresante = Visita::obtenerPorDocumento($documento ,$nodo->ubicacion_id);
                            if(!is_null($ingresante)){
                                $existeAcceso = AccesoVisitaEnrolada::enVisita($ingresante->persona->documento, $nodo->ubicacion_id, $ahora);
                            }else{
                                $existeAcceso = null;
                            }
                            $visita_enrolada = $ingresante;
                            break;
                        case Acceso::VISITA_TARJETA_ENROLADA:
                            $ingresante = $persona;                            
                            if(!is_null($ingresante)){
                                $accesoTarjeta = AccesoVisita::enVisitaTarjeta($ingresante->documento, $nodo->ubicacion_id, $ahora);
                                $existeAcceso = $accesoTarjeta["id"];
                                $ingresoTarjetaEnrolada = $accesoTarjeta["hora_egreso"];
                                $esEntrada = $accesoTarjeta["entradaVisita"]; // true or false
                            }else{
                                $existeAcceso = null;
                            }
                            break;
                        case Acceso::CONTRATISTA:
                            $ingresante = $persona;                                                        
                            if(!is_null($ingresante)){
                                $accesoTarjeta = AccesoContratista::enVisitaTarjeta($ingresante->documento, $nodo->ubicacion_id, $ahora);
                                $existeAcceso = $accesoTarjeta["id"];
                                $ingresoTarjetaEnrolada = $accesoTarjeta["hora_egreso"];
                                $esEntrada = $accesoTarjeta["entradaVisita"]; // true or false
                                $contratista = $ingresante;
                            }else{
                                $existeAcceso = null;
                            }
                            break;
                        default:
                            $data = static::ERROR_EGRESO;
                            (new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
                    }

                    $accesoExclusivoaReloj  = Acceso::obtenerDatosaccesoExclusivo($src_node,$persona->id);
                    Reloj::guardar_log($src_node, 2003,"Rta de consulta: ".$accesoExclusivoaReloj." --- Nodo: ".$src_node." --- PersonaID: ".$persona->id);

                    if ($accesoExclusivoaReloj<1000 && $accesoExclusivoaReloj>0) {
                         //error por restriccion en reloj
                         $data = static::NODO_INVALIDO;
                         (new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
                    }

                    if (empty($existeAcceso)) {
                        if ($fuente == static::FUENTE_TARJETA) {
                            Reloj::guardar_log(0, 9009,"NO SE ENCONTRO REGISTRO DE ACCESO X TARJETA: ".$documento." SOURCE: ".$fuente."  NODO:".$src_node." --- PersonaID: ".$persona->id."--- Fecha/Hora: ".date_format($ahora, 'Y-m-d H:i:s'));
                            $data = static::ERROR_INGRESO;
                        }
                        elseif ($fuente == static::FUENTE_BIOMETRICO AND $ingresante->puedeAcceder($nodo->ubicacion->id)) {                         
                            $accesoUltSalida = Acceso::obtenerUltimaSalidaEmpleadoPorDocumento($documento,$nodo->ubicacion_id);
                            $diferencia = $ahora->sub(new DateInterval('PT40S'));
                            if ($accesoUltSalida === null || $accesoUltSalida->egreso < $diferencia) { 
                                $acceso = Acceso::obtener(0);
                                $acceso->tipo_ingreso       = Acceso::TIPO_REGISTRO_RELOJ;
                                $acceso->tipo_acceso        = $persona->tipo_persona;
                                $acceso->persona            = $persona;
                                $acceso->empleado           = $ingresante;
                                $acceso->ubicacion          = $nodo->ubicacion;
                                $acceso->persona_ingreso    = $personaUsuario;
                                if ($persona->tipo_persona == Acceso::VISITA_ENROLADA) {
                                    $acceso->visita_enrolada    = $visita_enrolada;
                                }
                                if ($persona->tipo_persona == Acceso::VISITA_TARJETA_ENROLADA) {
                                    $acceso->credencial         = $documento;
                                }
                                if ($acceso->alta()) {
                                    $data = static::INGRESO_CORRECTO;
                                } else {
                                    error_log(json_encode($_POST) . json_encode($acceso->errores));
                                    $data = static::ERROR_INGRESO;
                                }
                            }else {
                                $data = static::EGRESO_DUPLICADO;
                            } 
                        }                        
                        else {
                            $data = static::UBICACION_NO_AUTORIZADA;
                        }
                    } else {                        

                        $acceso = Acceso::obtener($existeAcceso);  
                        if ($persona->tipo_persona == Acceso::CONTRATISTA) {
                            $acceso->credencial = Credencial::obtenerPorCodigo_TM($documento,$nodo->ubicacion_id);
                            $acceso->contratista_empleado = ContratistaEmpleado::obtenerPorDocumento($contratista->documento);
                        }

                        if ($esEntrada) {    // SIEMPRE es false para Biometrico .. ignorar el nombre de la variable
                            if ($nodo->acceso_tarjeta) {                                
                                $credencial = Credencial::obtenerPorCodigo_TM($documento,$nodo->ubicacion_id);
                                if ($credencial->acceso_id == 1) {
                                    $update_acceso_TM = (Acceso::obtener(0))->update_acceso_TM($acceso->id,$ahora);                                
                                    $update_credencial_TM = Credencial::update_credencial_TM($credencial->id,$acceso->id,1);
                                    if ($update_acceso_TM && $update_credencial_TM) {
                                        Reloj::guardar_log(0, 9009,"INGRESO CORRECTO X TARJETA: ".$documento." SOURCE: ".$fuente."  NODO:".$src_node." --- PersonaID: ".$persona->id."--- Fecha/Hora: ".date_format($ahora, 'Y-m-d H:i:s'));
                                        $data = static::INGRESO_CORRECTO;
                                    }else{
                                        Reloj::guardar_log(0, 9009,"INGRESO INCORRECTO X TARJETA: ".$documento." SOURCE: ".$fuente."  NODO:".$src_node." --- PersonaID: ".$persona->id."--- Fecha/Hora: ".date_format($ahora, 'Y-m-d H:i:s'));
                                        $data = static::ERROR_INGRESO;
                                    }            
                                }else{
                                    Reloj::guardar_log(0, 9009,"CREDENCIAL: ".$documento."  YA ASIGNADA EN ACCESO NODO:".$src_node." SOURCE: ".$fuente." --- PersonaID: ".$persona->id."--- Fecha/Hora: ".date_format($ahora, 'Y-m-d H:i:s'));
                                    $data = static::ERROR_INGRESO;
                                }                                
                            }else{
                                Reloj::guardar_log(0, 9009,"RELOJ NO HABILITADO PARA INGRESO X TARJETA: ".$documento." SOURCE: ".$fuente."  NODO:".$src_node." --- PersonaID: ".$persona->id."--- Fecha/Hora: ".date_format($ahora, 'Y-m-d H:i:s'));
                                $data = static::NODO_INVALIDO;
                            }                                            
                        }else{

                            if ($fuente == static::FUENTE_BIOMETRICO) { //salida por huella
                                $diferencia = $ahora->sub(new DateInterval('PT20S'));
                                if ($acceso->ingreso < $diferencia) {
                                    $terminado = Acceso::terminar($existeAcceso, $personaUsuario, Acceso::TIPO_REGISTRO_RELOJ);
                                    if ($terminado['terminado']) {
                                        $data = static::EGRESO_CORRECTO;
                                    } else {
                                        $data = static::ERROR_EGRESO;
                                    }
                                } else {
                                    $data = static::INGRESO_DUPLICADO;
                                }
                            }

                            if ($fuente == static::FUENTE_TARJETA OR $fuente == static::FUENTE_BUZON_TARJETA ) { //salida por tarjeta
                                if ($nodo->acceso_tarjeta) { //validar reloj habilitado para tarjeta
                                    $diferencia = $ahora->sub(new DateInterval('PT20S'));
                                    if ($acceso->ingreso < $diferencia) {
                                        $terminado = Acceso::terminar($existeAcceso, $personaUsuario, Acceso::TIPO_REGISTRO_TARJETA_RELOJ);
                                        if ($terminado['terminado']) {
                                            $credencial = Credencial::obtenerPorCodigo_TM_Salida($documento,$nodo->ubicacion_id,$acceso->id);
                                            $update_credencial_TM = Credencial::update_credencial_TM($credencial->id,$acceso->id,0);
                                            Reloj::guardar_log(0, 9009,"EGRESO CORRECTO X TARJETA: ".$documento." SOURCE: ".$fuente."  NODO:".$src_node." --- PersonaID: ".$persona->id."--- Fecha/Hora: ".date_format($ahora, 'Y-m-d H:i:s'));
                                            $data = static::EGRESO_CORRECTO;
                                        } else {
                                            Reloj::guardar_log(0, 9009,"EGRESO INCORRECTO X TARJETA: ".$documento." SOURCE: ".$fuente."  NODO:".$src_node." --- PersonaID: ".$persona->id."--- Fecha/Hora: ".date_format($ahora, 'Y-m-d H:i:s'));
                                            $data = static::ERROR_EGRESO;
                                        }
                                    } else {
                                        $data = static::INGRESO_DUPLICADO;
                                    }
                                }else{
                                    Reloj::guardar_log(0, 9009,"RELOJ NO HABILITADO PARA EGRESO X TARJETA: ".$documento." SOURCE: ".$fuente."  NODO:".$src_node." --- PersonaID: ".$persona->id);
                                    $data = static::NODO_INVALIDO;
                                }
                            }                            
                        }                        
                    }
                } else {
                    $data = static::NODO_INVALIDO;
                }
            } else {
                $data = static::DOCUMENTO_INVALIDO;
            }
        } catch (\Exception $ex) {
            error_log('ex api' . $ex->getMessage());
        }
        (new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
    }

    /**
     * Devuelve la fecha en formato "YYYY-mm-dd HH:ii:ss" sin comillas, si tiene comillas rompe el API que consulta.
     *
     * @return string
     */
    public function accion_probar_conexion()
    {
        $data = (new DateTime())->format("Y-m-d H:i:s");
        echo $data;
        exit;
    }

    public function accion_listar_relojes()
    {
        $data = Reloj::listar();
        $data = array_map(function($reloj){
            $obj = (object)[
                'id'              => $reloj->id,
                'nodo'            => $reloj->nodo,
                'puerto'          => $reloj->puerto,
                'ip'              => $reloj->ip,
                'dns'             => $reloj->dns,
                'enrolador'       => $reloj->enrolador,
                'ultima_marcacion'=> $reloj->ultima_marcacion,
            ];
            $obj->dns   = $obj->dns == null ? 'sindns' : $obj->dns;
            if($obj->ultima_marcacion instanceof \DateTime){
                $obj->ultima_marcacion = $obj->ultima_marcacion->format('Y-m-d H:i:s');
            }
            return $obj;
        }, $data);
        (new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
    }

    public function accion_bucar_reloj_enrolador()
    {
        $reloj  = Reloj::obtenerEnrolador();
        $data = (object)[
            'id'              => $reloj->id,
            'nodo'            => $reloj->nodo,
            'puerto'          => $reloj->puerto,
            'ip'              => $reloj->ip,
            'dns'             => $reloj->dns,
            'enrolador'       => (bool)$reloj->enrolador,
            'ultima_marcacion'=> $reloj->ultima_marcacion,
        ];
        $data->dns   = $data->dns == null ? 'sindns' : $data->dns;
        if($data->ultima_marcacion instanceof \DateTime){
            $data->ultima_marcacion = $data->ultima_marcacion->format('Y-m-d H:i:s');
        }

        (new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
    }

    public function accion_sincronizar_marcaciones()
    {
        $src_node   = (int)$this->request->post('src_node');
        $total      = (int)$this->request->post('total');
        $marcaciones= $this->request->post('mark');

        $id_lote    = Reloj::sincronizar_lotes_alta($src_node, $total);
        if ($id_lote) {
            $resultado  = Reloj::sincronizar_marcaciones_alta($src_node, $id_lote, $marcaciones);
            if ($resultado) {
                $borrando_marcaciones       = Reloj::sincronizar_lotes_modificar($id_lote, 'Borrando marcaciones del reloj');
                if ($borrando_marcaciones) {
                    $borrar_marcaciones     = Reloj::sincronizar_marcaciones_borrar($src_node);
                    while(!$borrar_marcaciones['status']) {
                        $borrar_marcaciones = Reloj::sincronizar_marcaciones_borrar($src_node);
                        usleep(5000); // Para reducir la carga del servicio que recibe el request // 0.005 segundos
                    }
                    if ($borrar_marcaciones['status']) {
                        $this->sincronizar($id_lote, $src_node);
                    }
                }
            }
        }
    }

    private function sincronizar($id_lote, $src_node)
    {
        Reloj::sincronizar_lotes_modificar($id_lote, 'Sicronizando con accesos');
        $marcaciones    = Reloj::sincronizar_marcaciones_listar((int)$id_lote);
        $marks          = [];

        foreach ($marcaciones as $key => $value) {
            $marks[$value['fecha_marcacion']] = $value['id_marcacion'];
        }

        if ($marks) {
            $nodo           = Reloj::obtenerPorNodo($src_node);
            $personaUsuario = Persona::obtenerPorDocumento($src_node);
            $esEntrada      =false;

            foreach ($marks as $timestamp => $documento) {
                try {
                    $fecha      = new DateTime($timestamp);
                    $persona    = Persona::obtenerPorDocumento($documento);
                    if (is_null($persona)) {
                        $persona    = Persona::obtenerPorTarjeta($documento); //busca la persona en los accesos de visitas
                    }                    
                    if (is_null($persona)) {
                        $persona    = Persona::obtenerPorTarjetaContratista($documento); //busca la persona en los accesos de contratistas
                    }

                    if (!empty($persona->id)) {
                        if (!empty($nodo->id)) {
                            $ingresoTarjetaEnrolada=false;

                            switch ($persona->tipo_persona) {
                                case Acceso::EMPLEADO:
                                    $ingresante     = Empleado::obtenerPorDocumento($documento);
                                    $existeAcceso   = AccesoEmpleado::enVisita($ingresante->documento, $nodo->ubicacion_id, $fecha);
                                    $visita_enrolada= null;
                                    $valido         = true;
                                    break;
                                case Acceso::VISITA_ENROLADA:
                                    $ingresante     = Visita::obtenerPorDocumento($documento);
                                    $existeAcceso   = AccesoVisitaEnrolada::enVisita($ingresante->documento, $nodo->ubicacion_id, $fecha);
                                    $visita_enrolada= $ingresante;
                                    $valido         = true;
                                    break;
                                case Acceso::VISITA_TARJETA_ENROLADA:
                                    $ingresante = $persona;
                                    if(!is_null($ingresante)){
                                        $accesoTarjeta          = AccesoVisita::enVisitaTarjeta($ingresante->documento, $nodo->ubicacion_id, $fecha);
                                        $existeAcceso           = $accesoTarjeta["id"];
                                        $ingresoTarjetaEnrolada = $accesoTarjeta["hora_egreso"];
                                        $esEntrada              = $accesoTarjeta["entradaVisita"];
                                        $valido                 = true;
                                    }else{
                                        $existeAcceso           = null;
                                    }
                                    break;
                                case Acceso::CONTRATISTA:
                                    $ingresante = $persona;                                                        
                                    if(!is_null($ingresante)){
                                        $accesoTarjeta          = AccesoContratista::enVisitaTarjeta($ingresante->documento, $nodo->ubicacion_id, $fecha);
                                        $existeAcceso           = $accesoTarjeta["id"];
                                        $ingresoTarjetaEnrolada = $accesoTarjeta["hora_egreso"];
                                        $esEntrada              = $accesoTarjeta["entradaVisita"]; // true or false
                                        $contratista            = $ingresante;
                                        $valido                 = true;
                                    }else{
                                        $existeAcceso   = null;
                                    }
                                    break;
                                default:
                                    $valido = false;
                                    break;
                            }
                            if ($valido) {
                                if ($existeAcceso) { //si existe el acceso
                                    $acceso = Acceso::obtener($existeAcceso);
                                    if ($acceso->ingreso > $fecha) {
                                        $fecha_ingreso_anterior     = $acceso->ingreso;
                                        $acceso1                    = Acceso::obtener(0);
                                        $acceso1->tipo_acceso       = $persona->tipo_persona;

                                        $acceso1->persona           = $persona;
                                        $acceso1->empleado          = $ingresante;
                                        $acceso1->ubicacion         = $nodo->ubicacion;
                                        $acceso1->tipo_ingreso      = Acceso::TIPO_REGISTRO_RELOJ;
                                        $acceso1->ingreso           = $fecha;
                                        $acceso1->persona_ingreso   = $personaUsuario;

                                        $acceso1->persona_egreso    = $personaUsuario;
                                        $acceso1->tipo_egreso       = Acceso::TIPO_REGISTRO_RELOJ;
                                        $acceso1->egreso            = $fecha_ingreso_anterior->modify('-1 minute');
                                        $acceso1->observaciones     = 'Sincronizacion del reloj - ingreso. Egreso calculado por sistema';
                                        $alta                       = $acceso1->altaSync();
                                        $cierre                     = $acceso1->cierreSync();

                                        if ($acceso1->tipo_acceso == Acceso::VISITA_TARJETA_ENROLADA OR $acceso1->tipo_acceso == Acceso::CONTRATISTA) {
                                            $credencial             = Credencial::obtenerPorCodigo_TM_Salida($documento,$nodo->ubicacion_id,$acceso->id);
                                            $update_credencial_TM   = Credencial::update_credencial_TM($credencial->id,$acceso->id,0);
                                        }                                        
                                    } else {
                                        $acceso->tipo_egreso    = Acceso::TIPO_REGISTRO_RELOJ;
                                        $acceso->persona_egreso = $personaUsuario;
                                        $acceso->egreso         = $fecha;
                                        $acceso->observaciones  = 'Sincronizacion del reloj - egreso.';
                                        $acceso->cierreSync();

                                        if ($acceso->tipo_acceso == Acceso::VISITA_TARJETA_ENROLADA OR $acceso->tipo_acceso == Acceso::CONTRATISTA) {
                                            $credencial             = Credencial::obtenerPorCodigo_TM_Salida($documento,$nodo->ubicacion_id,$acceso->id);
                                            $update_credencial_TM   = Credencial::update_credencial_TM($credencial->id,$acceso->id,0);
                                        }
                                    }
                                } else {
	                                if ($ingresante->puedeAcceder($nodo->ubicacion->id)) {
		                                $acceso                 = Acceso::obtener(0);
		                                $acceso->tipo_acceso    = $persona->tipo_persona;
		                                $acceso->persona        = $persona;
		                                $acceso->empleado       = $ingresante;
		                                $acceso->ubicacion      = $nodo->ubicacion;
		                                $acceso->tipo_ingreso   = Acceso::TIPO_REGISTRO_RELOJ;
		                                $acceso->persona_ingreso= $personaUsuario;
		                                $acceso->ingreso        = $fecha;
                                        $acceso->observaciones  = 'Sincronizacion del reloj - ingreso.';
		                                if ($persona->tipo_persona == Acceso::VISITA_ENROLADA) {
			                                $acceso->visita_enrolada        = $visita_enrolada;
		                                }
                                        if ($persona->tipo_persona == Acceso::VISITA_TARJETA_ENROLADA) {
                                            $acceso->credencial             = $documento;
                                        }
                                        if ($persona->tipo_persona == Acceso::CONTRATISTA) {
                                            $acceso->credencial             = Credencial::obtenerPorCodigo_TM($documento,$nodo->ubicacion_id);
                                            $acceso->contratista_empleado   = ContratistaEmpleado::obtenerPorDocumento($contratista->documento);
                                        }
		                                $acceso->altaSync();
	                                }
                                }
                            }
                        }

                    }

                } catch (\Exception $e) {
                    Reloj::guardar_log($src_node, 9001, $e->getMessage());
                }
            }
        }
        Reloj::sincronizar_lotes_modificar($id_lote, 'Finalizado');
    }

/**
 * Metodo para ejemplificar la escritura de un API
 * - Permite consultar por CUIT o por ID.
 * - El comportamiento cambia segun el metodo de consulta, *GET* para obtener informacion. *POST* y *PUT* para crear o actualizar datos.
 *
 * Ejemplo de endpoint: url/api.php/ejemplos/1234
 *
 * @return void
*/
	protected function accion_ejemplos(){
		$param_get_1= $this->request->query('id');
		$data		= (object)['id' => null];
		switch ($this->request->method()) {
			case 'GET':
				$data	= (object)['id' => $param_get_1];
				break;
			case 'POST':
				$data	= (object)['id' => $param_get_1];
				break;
			case 'PUT':
				$data	= (object)['id' => $param_get_1];
				break;
			case 'DELETE':
				break;
		}
		if(empty($data->id)){
			$this->json->setError();
			$this->json->setMensajes(['Data no encontrada, pase un parametro.']);
		}
		$data	= json_decode(json_encode($data), true);
		$this->json->setData($data);
		$this->json->render();
	}

/**
 * Convierte un array a objecto en forma recursiva. Si algun indice es de tipo string y contiene la palabra *fecha* lo combierte a objecto DateTime::
 *
 * @param array		$data	- Informacion a convertir
 * @param bool		$como_objeto - Default: true. Devuelve un objeto o un array si es es `false`
 * @return array|object
 */
	static private function arrayToObject(&$data = null, $como_objeto = true)
	{
		foreach ($data as $attr => &$val) {
			if (is_array($val) && count($val) == 0) {
				$data[$attr]	= array();
				continue;
			}
			if (is_string($attr) && !is_array($val)) {
				$data[$attr]	= $val;
			} else if (is_string($attr) && preg_match('/fecha/i', $attr) && is_array($val) && !empty($val)) {
                $tmp    = \DateTime::createFromFormat('Y-m-d H:i:s', $val['date']);
                $tmp	= !empty($tmp) ? $tmp : \DateTime::createFromFormat('Y-m-d H:i:s.u', $val['date']); // arreglo para mantener compatibilidad entre versiones 5.5 y 5.6 o mayor de PHP

				$data[$attr]	= !empty($tmp) ? $tmp : \DateTime::createFromFormat('Y-m-d H:i:s.u', $val['date'].' 0:00:00.000000');
			} else if (is_array($val)) {
				if (is_array($val)) {
					$aux	= array_keys($val);
					$indice_numerico	= is_numeric(array_pop($aux));
				} else {
					$indice_numerico	= false;
				}

				$data[$attr]	= static::arrayToObject($val, !$indice_numerico);
			}
		}
		return ($como_objeto) ? (object) $data : $data;
	}
/**
 * Metodo para traer los mails de los RCA que pertenezcan a una dependencia en particular
 * - Consulta por id_dependencia
 * Ejemplo de endpoint: url/api.php/get_rca/19
 /*Retorna los mails si los encuentra, si no un mensaje de información que no encontró los correos
 **/
    public function accion_get_email_rca(){
        $id = $this->request->query('id'); //id de dependencia
        $usuarios = Usuario::listar();
        $usuarios_bis = [];
        $emails = [];
        $respuesta = [];
        if (is_numeric($id)) {
            foreach ($usuarios as $key => $usuario) {
            $usuarios_bis[$usuario->idUsuario] = Usuario::obtener($usuario->idUsuario);
            }
           
            foreach ($usuarios_bis as $key => $usuario_obj) {
               if($usuario_obj->rol_id != AppRoles::RCA ){
                unset($usuarios_bis[$key]);
               }
            }

            foreach ($usuarios_bis as $key => $usuario_obj) {
                if(!in_array($id,$usuario_obj->dependencias)){  
                    unset($usuarios_bis[$key]);
                }

            }

            foreach ($usuarios_bis as $key => $usuario_obj) {
                $emails[] = $usuario_obj->email;
            }

            if(!empty($emails)){
                $respuesta = ['emails' => $emails];
            }else{
                $respuesta = ['info' => "Aviso: No se pudo encontrar el correo, entregue una copia al RCA."];
            }
            
            $this->json->setData($respuesta);
        }else { 
            $info   = [
                'Los parametros suministrados son incorrectos.'
            ];
            $this->json->setMensajes($info);
            $this->json->setError();
        }
        $this->json->render();
    }
}
