<?php
namespace App\Controlador;

/**
 * Acciones para procesar por cron del servidor.
*/
use App\Modelo;
use App\Modelo\Informe;
use App\Modelo\Empleado;
use FMT\Logger;
use FMT\Helper;
use App\Helper\Vista;

class Cron extends \FMT\Consola {
	
	
	/**
	 * parseDateLog ---> aka parseDL para que sea mas corto
	 * Recibe un objeto DateTime, null o false y lo parsea a string. Usado para guardar en informacion en los logs.
	 * Este metodo es consecuencia de la migracion de Control de Accesos a la version 2.0.0 en la cual cambiaron varias formas de implementar fechas.
	 *
	 * @param DateTime|bool $date
	 * @return string|null
	 */
	private static function parseDL($date=null){
		$date	= ($date instanceof \DateTime) ? $date->format('Y-m-d') : $date;
		return empty($date) ? null : $date;
	}

	public function accion_sincronizar_fichadas_mecon(){

		if (static::ClonadoProcessAlive('sincronizar_fichadas_mecon')) {
			exit;
		}
		try{
			$respuesta = Modelo\AccesoBio::sincronizar();
			if(!$respuesta["estado"]){
				$this->debug($respuesta["info"], true, 'consola-sincronizar');
			}else{
				$this->debug($respuesta["info"], true, 'consola-sincronizar');
			}
		} catch (\Exception $e) {
			$this->debug($e, false, 'consola-sincronizar');
		}
	}

/**
 * Obtiene el listado de Informes, compila y ejecuta, accion_informes_enviar() y accion_informes_generar_pdf()
*/
	protected function accion_informes(){
		$cron_process		= true;
		$params				= $this->getParams();
		// $this->debug('accion_informes', true);

		if(empty($params['informe_id'])){
			$lista			= Informe::listar();
		} else {
			$lista			= [Informe::obtener($params['informe_id'])];
			$cron_process	= false;
		}
		$interval			= new \DateInterval('P1M');
		$interval->invert	= 1;
		$date				= (new \DateTime())->add($interval);
		/**
		 * @var array $configuracion - Almacena la configuracion usada en "informes_generar_pdf()"
		$configuracion		= [];
		/**
		 * @var array $cache_emails - Almacena los archivos solicitados por el usuario*/
		$cache_emails		= [];
		foreach ($lista as &$item) {
			if(!empty($item->fecha_ultimo_envio) && ($item->fecha_ultimo_envio->format('m-Y') == (new \DateTime())->format('m-Y'))){
				$datos		= [
					'modelo'		=> 'Cron',
					'ejecucion'		=> ($cron_process ? 'sistema' : 'manual'),
					'motivo'		=> 'Intento de reenvio. El informe correspondiente fue enviado anteriormente',
					'params'		=> ['informes_configuracion_id'	=> $item->id, ]
				];
				// $this->debug($datos);
				// $this->debug($item);
				Logger::event('cron_informes', $datos);
				continue;
			}

			$key					= json_encode($item->contratos);
			foreach ($item->dependencias as $dependencia_id) {
				$tmp_deps	= Helper\Arr::path($configuracion, "{$key}*{$dependencia_id}", [], '*');
				if(!in_array($item->empleado->email, $tmp_deps)){
					Helper\Arr::set_path($configuracion, "{$key}*{$dependencia_id}*" . count($tmp_deps), $item->empleado->email, '*');
				}

				$tmp_conf	= Helper\Arr::path($cache_emails, "{$item->empleado->email}", [], '*');
				$archivo_solicitado	= $date->format('Y').'-'.$date->format('m'). '-'. base64_encode(json_encode(['contratos'=>$item->contratos,'dependencia_id'=>$dependencia_id]));

				$informe_ids	= Helper\Arr::path($cache_emails, "{$item->empleado->email}*informe_ids", [], '*');
				if(!in_array($item->id, $informe_ids)){
					$informe_ids[]	= $item->id;
					Helper\Arr::set_path($cache_emails, "{$item->empleado->email}*informe_ids", $informe_ids, '*');
				}

				if(!in_array($archivo_solicitado, $tmp_conf)){
					Helper\Arr::set_path($cache_emails, "{$item->empleado->email}*{$archivo_solicitado}", [
						'filename'			=> $archivo_solicitado,
						'contratos'			=> $item->contratos,
						'dependencia_id'	=> $dependencia_id,
						'mes'				=> $date->format('m'),
						'anio'				=> $date->format('Y'),
						'empleado'			=> [
							'id'		=> $item->empleado->id,
							'full_name'	=> $item->empleado->persona->full_name,
						],
					], '*');
				}
			}
		}

		$this->ejecutar_accion('informes_enviar', $cache_emails);

		foreach ($configuracion as $contrato_ids => $dependencias) {
			foreach ($dependencias as $dependencia_id => $emails) {
				$datos				= [
					'contratos'			=> json_decode($contrato_ids, true),
					'dependencia_id'	=> $dependencia_id,
					'emails'			=> $emails,
					'mes'				=> $date->format('m'),
					'anio'				=> $date->format('Y'),
				];
				$datos['filename']	= $datos['anio'].'-'.$datos['mes']. '-'. base64_encode(json_encode(['contratos'=>$datos['contratos'],'dependencia_id'=>$datos['dependencia_id']]));

				$this->ejecutar_accion('informes_generar_pdf', $datos);
			}
		}
	}

/**
 * Se mantiene recoriendo los email y comprobando que existan todos los adjuntos que pidio el usuario.
 * Una vez completos los archivos los adjunta y envia el email.
 *
 * Si los archivos no son encontrados el proceso muere a las 12hs de su nacimiento.
*/
	protected function accion_informes_enviar($cache_emails=null){
		$cron_process	= false;
		if(!is_array($cache_emails)){
			$cache_emails	= $this->getParams();
			$cron_process	= true;
		}

		$process_start	= time();
		$cache_dependencias	= [];
		while(count($cache_emails) > 0) {
			foreach ($cache_emails as $email => $archivos) {
				$adjuntos_disponibles	= [];
				$cuerpo_mensaje			= [
					'dependencias'	=> [],
				];
				$informe_ids			= $archivos['informe_ids'];
				unset($archivos['informe_ids']);
				$dir_tmp	= \App\Helper\Documento::getDirectorio('tmp/', true);
				foreach ($archivos as $filename => $metadata) {
					if(!empty($file = glob($dir_tmp . $filename . '.pdf')) && is_readable($file[0])) {
						$adjuntos_disponibles[]	= $file[0];
					} else {
						clearstatcache();
						continue;
					}

					if(!array_key_exists($metadata['dependencia_id'], $cache_dependencias)) {
						$dependencia	= Modelo\Direccion::obtener($metadata['dependencia_id']);
						$cache_dependencias[$metadata['dependencia_id']]	= $dependencia->nombre;
					}
					$cuerpo_mensaje['dependencias'][]	= $cache_dependencias[$metadata['dependencia_id']];
					$cuerpo_mensaje['mes']				= $metadata['mes'];
					$cuerpo_mensaje['anio']				= $metadata['anio'];
					$cuerpo_mensaje['full_name']		= $metadata['empleado']['full_name'];
				}
				$dependenciasMail='';
				if(count($cuerpo_mensaje['dependencias'])>=1){
					for ($i=0; $i < count($cuerpo_mensaje['dependencias']) ; $i++) { 
						$dependenciasMail.=$cuerpo_mensaje['dependencias'][$i].", ";
					}
				}
				// else{
				// 	$dependenciasMail=$cuerpo_mensaje['dependencias'][0];
				// }

				if(count($archivos) > 0 && count($archivos) == count($adjuntos_disponibles)){
					unset($cache_emails[$email]);
					$Email				= new \App\Helper\Email();
					$Email
						->set_destinatario($email)
						->set_asunto("Informe de presentismo de empleados para la dependencia {$cuerpo_mensaje['dependencias'][0]} - Mes: {$cuerpo_mensaje['mes']} Año: {$cuerpo_mensaje['anio']}")
						->set_contenido("<i>Estimado {$cuerpo_mensaje['full_name']},<br><br>
							Se adjunta el informe de presentismo del mes {$cuerpo_mensaje['mes']} y año {$cuerpo_mensaje['anio']} de los agentes de la dependencia: {$dependenciasMail}. <br><br>							
							No responda este correo, fue generado automáticamente por el sistema de Presentismo.<br><br>
							Cualquier consulta comuníquese con la Dirección de Administración de Recursos Humanos. </i><br><br>
							<img src='https://www.transporte.gob.ar/_img/logo_ministerio_mediano_blanco.png'>");						
					foreach ($adjuntos_disponibles as $i => $file) {
						$num	= $i + 1;
						$Email->add_attachment($file, "Informe Presentismo {$cuerpo_mensaje['mes']} {$cuerpo_mensaje['anio']} -- {$num}.pdf");
					}
					$Email->enviar();

					$datos		= [
						'modelo'		=> 'Cron',
						'ejecucion'		=> ($cron_process ? 'sistema' : 'manual'),
						'parametros'	=> $archivos,
					];

					if(!$Email->tieneErrores()) {
						$fecha_ultimo_envio	= new \DateTime();
						foreach ($informe_ids as $informe_id) {
							$informe						= Informe::obtener($informe_id);
							$informe->fecha_ultimo_envio	= $fecha_ultimo_envio;
							$informe->modificacion();
						}
						$this->debug($datos);
						Logger::event('cron_informes_enviar_email', $datos);
					}
				}
			}
			sleep(2);
			/** Realizar Harakiri luego de 12hs  */
			if(abs(time() - $process_start) >= abs(12*60*60)){
				$datos		= [
					'modelo'			=> 'Cron',
					'ejecucion'			=> ($cron_process ? 'sistema' : 'manual'),
					'parametros'		=> $cache_emails,
					'segundos_activo'	=> abs(time() - $process_start),
				];
				Logger::event('cron_informes_enviar_tiempo_agotado', $datos);
				$this->matarProceso();
			}

		}
	}

	public function accion_informes_generar_pdf($params=null){
		if(!is_array($params)){
			$params	= $this->getParams();
		}
		$datos	= ['contratos', 'dependencia_id', 'mes', 'anio', 'filename'];
		foreach ($datos as $value) {
			if(empty($params[$value])){
				throw new \Exception("Error Processing Request, faltan algunos de los parametros: ".implode(',', $datos), 1);
			}
		}

		$system_set_filename	= $params['filename'];
		$nombre					= Modelo\Direccion::obtener($params['dependencia_id'])->nombre;
		$anio					= $params['anio'];
		$mes					= $params['mes'];

		$date_interval			= \DateTime::createFromFormat('Y-m-d H:i:s', "{$params['anio']}-{$params['mes']}-01 00:00:00");
		$fecha					= [
			'fecha_desde'	=> $date_interval->format('Y-m-d'),
			'fecha_hasta'	=> $date_interval->format('Y-m-t') // La "t" indica que se el ultimo dia valido del mes
		];

		$contratosArray	= Modelo\SituacionRevista::listarParaSelect();
		$contrato_tipo	= $params['contratos'];
		$listado_informe_mensual	= Modelo\AccesoEmpleado::listar_informe_mensual((int)$params['dependencia_id'], $fecha, $contrato_tipo);
		if(!empty($listado_informe_mensual)){
			$novedades	= Modelo\AccesoEmpleado::novedades_mensuales((int)$params['dependencia_id'], $fecha, $contrato_tipo);
		}

        $vista = $this->vista;
        (new Vista($this->vista_default,  compact(
			'listado_informe_mensual',
			'novedades',
			'nombre',
			'fecha',
			'anio',
			'mes',
			'contratosArray',
			'system_set_filename'
		)))->pre_render();
        
	}

/**
 * Recibe por parametro un CUIT para actualizar los datos de Control de Accesos desde Sigarhu.
 *
 * @return void
 */
    protected function accion_actualizar_agente(){
		if (static::ClonadoProcessAlive('actualizar_agente')) {
			exit;
		}
        $cuit = $_SERVER['argv'][count($_SERVER['argv'])-1];

		$sigarhu    = Modelo\SigarhuApi::getAgente($cuit);
		if( empty($sigarhu) ) {
			$this->debug(Modelo\SigarhuApi::getErrores());
            exit;
        } else {
			$this->actualizar_sistema($sigarhu);
		}
        exit;
    }

/**
 * Obtiene un listado de CUITs que tuvieron modificaciones en SIGARHU a partir del timestamp de ultima ejecucion de este script (si nunca fue ejecutado la fecha es 1970-01-01).
 * Luego consulta CUIT por CUIT en SIGARHU y actualiza Control de Accesos.
 * Si en algun momento se interrumpe la comunicacion, el proceso corta y guarda el timestamp.
 *
 * @return void
 */
    protected function accion_actualizar_desde_sigarhu(){
		if (static::ClonadoProcessAlive('actualizar_desde_sigarhu')) {
			exit;
		}
        $this->actualizarParametricos();
		$ultimo_log	= Modelo\IntegracionSigarhu::obtenerUltimoLog();
		$cuits = Modelo\SigarhuApi::getAuditoria($ultimo_log->fecha);

        if( !empty($cuits) ) {
        	foreach ($cuits['cuits'] as $cuit) {
				$sigarhu    = Modelo\SigarhuApi::getAgente($cuit);
				if( empty($sigarhu) ) {
					$this->debug(Modelo\SigarhuApi::getErrores(), false, 'sigarhu_errores');
					Modelo\IntegracionSigarhu::logActualizaciones((int)$cuit, null, null, json_encode(Modelo\SigarhuApi::getErrores()));
				} else {
					$this->actualizar_sistema($sigarhu);
				}
			}
		}
		if(empty($ultimo_log->cuit) && $extra	= Modelo\SigarhuApi::getAuditoriaExtra()){
			foreach($extra['cuits'] as $cuit){
				$sigarhu    = Modelo\SigarhuApi::getAgente($cuit);
				if( empty($sigarhu) ) {
					$this->debug(Modelo\SigarhuApi::getErrores(), false, 'sigarhu_errores');
					Modelo\IntegracionSigarhu::logActualizaciones((int)$cuit, null, null, json_encode(Modelo\SigarhuApi::getErrores()));
				} else {
					$this->actualizar_sistema($sigarhu);
				}
			}
		}
        exit;
    }

/**
 * Obtiene objeto Empleado de Control de Accesos y opera sobre los datos de Sigarhu para actualizar.
 *
 * @param object $sigarhu
 * @return bool
 */
    private function actualizar_sistema($sigarhu=null){
		if(empty($sigarhu->id)){
			return false;
		}
		$empleado					= Modelo\IntegracionSigarhu::obtenerEmpleadoPorDocumento($sigarhu->persona->documento);
		$empleado_control_cambios	= unserialize(serialize($empleado));
		$datos_modificados			= [];

		$modificacion	= false;
		if((string)$empleado->cuit != (string)$sigarhu->cuit && !is_null($sigarhu->cuit)){
			$modificacion	= true;
			$datos_modificados['cuit']			= ['ca'=>$empleado->cuit, 'sig'=>$sigarhu->cuit];
			$empleado->cuit						= (string)$sigarhu->cuit;
		}
		if($empleado->documento != $sigarhu->persona->documento && !is_null($sigarhu->persona->documento)){
			$modificacion	= true;
			$datos_modificados['documento']		= ['ca'=>$empleado->documento, 'sig'=>$sigarhu->persona->documento];
			$empleado->documento		= (string)$sigarhu->persona->documento;
		}
		if(mb_strtolower($empleado->nombre, 'UTF-8') != mb_strtolower($sigarhu->persona->nombre, 'UTF-8') && !is_null($sigarhu->persona->nombre)){
			$modificacion	= true;
			$datos_modificados['nombre']		= ['ca'=>$empleado->nombre, 'sig'=>$sigarhu->persona->nombre];
			$empleado->nombre			= $sigarhu->persona->nombre;
		}
		if(mb_strtolower($empleado->apellido, 'UTF-8') != mb_strtolower($sigarhu->persona->apellido, 'UTF-8') && !is_null($sigarhu->persona->apellido)){
			$modificacion	= true;
			$datos_modificados['apellido']		= ['ca'=>$empleado->apellido, 'sig'=>$sigarhu->persona->apellido];
			$empleado->apellido		= $sigarhu->persona->apellido;
		}
		if($empleado->genero != $sigarhu->persona->genero && !is_null($sigarhu->persona->genero)){
			$modificacion	= true;
			$datos_modificados['genero']		= ['ca'=>$empleado->genero, 'sig'=>$sigarhu->persona->genero];
			$empleado->genero			= $sigarhu->persona->genero;
		}
		if($empleado->email != $sigarhu->email && !empty($sigarhu->email)){
			$modificacion	= true;
			$datos_modificados['email']			= ['ca'=>$empleado->email, 'sig'=>$sigarhu->email];
			$empleado->email					= $sigarhu->email;
		}


        /**
		 * Modificaciones Generales
         */
		$alta_empleado	= false;
		if($empleado->id == null){
			Modelo\Persona::anularValidacion();
			Modelo\Empleado::anularValidacion();

			$empleado->ubicacion_principal		= Modelo\Ubicacion::obtener((int)'19');
			$empleado->ubicaciones_autorizadas	= [$empleado->ubicacion_principal];
			$empleado->email					= !empty($sigarhu->email) ? $sigarhu->email : 'sinemail@transporte.gob.ar';
			$empleado->alta();
			$alta_empleado	= true;
        }else if($modificacion === true){
			Modelo\Persona::anularValidacion();
			Modelo\Empleado::anularValidacion();
			$empleado->modificacion();
        }

        /**
		 * Modificaciones especificas como cambios de contrato
         */
		if(json_encode($empleado->horarios) != $sigarhu->horario->horarios && !is_null($sigarhu->horario->horarios)){
			$modificacion	= true;
			$datos_modificados['horarios']		= ['ca'=>json_encode($empleado->horarios), 'sig'=>$sigarhu->horario->horarios];
			$empleado->horarios					= json_decode($sigarhu->horario->horarios);
			Modelo\Empleado::anularValidacion();
			$empleado->alta_empleado_horario();
		}

        $sigarhu_fecha_desde_contrato = ($sigarhu->situacion_escalafonaria->fecha_inicio instanceof \DateTime)
            ? $sigarhu->situacion_escalafonaria->fecha_inicio
			: \DateTime::createFromFormat('Y-m-d', $sigarhu->situacion_escalafonaria->fecha_inicio);
		$sigarhu_fecha_desde_contrato = !empty($sigarhu_fecha_desde_contrato)
			? $sigarhu_fecha_desde_contrato : $empleado->desde_contrato;

        $sigarhu_fecha_hasta_contrato = ($sigarhu->situacion_escalafonaria->fecha_fin instanceof \DateTime)
            ? $sigarhu->situacion_escalafonaria->fecha_fin
			: \DateTime::createFromFormat('Y-m-d', $sigarhu->situacion_escalafonaria->fecha_fin);

		// $sigarhu->estado || ESTADO 2 es INACTIVO
		if($sigarhu->estado == 2 && !empty($sigarhu->fecha_baja)){
			$sigarhu_fecha_hasta_contrato = ($sigarhu->fecha_baja instanceof \DateTime)
				? $sigarhu->fecha_baja
				: \DateTime::createFromFormat('Y-m-d', $sigarhu->fecha_baja);
			$datos_modificados['fecha_baja']		= ['ca'=>static::parseDL($empleado->hasta_principal), 'sig'=>static::parseDL($sigarhu_fecha_hasta_contrato), 'con_motivo_de_baja'];
		}

        if(!empty($sigarhu_fecha_desde_contrato) && !is_null($sigarhu->situacion_escalafonaria->id_situacion_revista)){
			$modificacion_de_contrato	= false;
			$log_modificacion_de_contrato	= [
				// 'cargo'						=> ['ca'=>$empleado->cargo, 'sig'=>null],
				'contrato'					=> ['ca'=>$empleado->id_tipo_contrato, 'sig'=>$sigarhu->situacion_escalafonaria->id_situacion_revista],
				'fecha_desde_contrato'		=> ['ca'=>static::parseDL($empleado->desde_contrato), 'sig'=>static::parseDL($sigarhu_fecha_desde_contrato)],
				'fecha_hasta_contrato'		=> ['ca'=>static::parseDL($empleado->hasta_principal), 'sig'=>static::parseDL($sigarhu_fecha_hasta_contrato)],
			];
			if($alta_empleado === false){
				//Ejectuar si el empleado esta dado de baja
				if(empty($empleado->hasta_contrato) && ($sigarhu->estado == 2 && !empty($sigarhu->fecha_baja))){
					$empleado->hasta_contrato = !empty($sigarhu_fecha_hasta_contrato)
						? $sigarhu_fecha_hasta_contrato : $sigarhu_fecha_desde_contrato;
					Modelo\Empleado::anularValidacion();
					$empleado->cancelar_contrato(); // Si la `fecha_hasta_contrato` esta vacio, no se ejecuta.
					$modificacion_de_contrato	= true;
				}

				// Ejecutar cuando NO es una baja y se hicieron cambios en el contrato |--|  ESTADO 2 es INACTIVO
				if(!($sigarhu->estado == 2 && !empty($sigarhu->fecha_baja)) && ($empleado->id_tipo_contrato != $sigarhu->situacion_escalafonaria->id_situacion_revista || $empleado->desde_contrato != $sigarhu_fecha_desde_contrato)){
					if(empty($empleado->hasta_contrato)){
						$empleado->hasta_contrato = !empty($sigarhu_fecha_hasta_contrato)
							? $sigarhu_fecha_hasta_contrato : $sigarhu_fecha_desde_contrato;
						Modelo\Empleado::anularValidacion();
						$empleado->cancelar_contrato(); // Si la `fecha_hasta_contrato` esta vacio, no se ejecuta.
					}
					$empleado->cargo                = '1';
					$empleado->id_tipo_contrato			    = $sigarhu->situacion_escalafonaria->id_situacion_revista; // Modelo\SituacionRevista::obtener($sigarhu->situacion_escalafonaria->id_situacion_revista)->id;
					$empleado->desde_contrato = $sigarhu_fecha_desde_contrato;
					$empleado->hasta_contrato = !empty($sigarhu_fecha_hasta_contrato)
						? $sigarhu_fecha_hasta_contrato : $empleado->hasta_contrato;
					Modelo\Empleado::anularValidacion();
					$empleado->cambiar_contrato();
					$modificacion_de_contrato	= true;
				}
			} else {
				// $datos_modificados['cargo']						= ['ca'=>$empleado->cargo, 'sig'=>null];
				$datos_modificados['contrato']					= ['ca'=>$empleado->id_tipo_contrato, 'sig'=>$sigarhu->situacion_escalafonaria->id_situacion_revista];
				$datos_modificados['fecha_desde_contrato']		= ['ca'=>static::parseDL($empleado->desde_contrato), 'sig'=>static::parseDL($sigarhu_fecha_desde_contrato)];
				$datos_modificados['fecha_hasta_contrato']		= ['ca'=>static::parseDL($empleado->hasta_principal), 'sig'=>static::parseDL($sigarhu_fecha_hasta_contrato)];

				$empleado->cargo                = '1';
				$empleado->id_tipo_contrato	    = $sigarhu->situacion_escalafonaria->id_situacion_revista; // Modelo\SituacionRevista::obtener($sigarhu->situacion_escalafonaria->id_situacion_revista)->id;
				$empleado->desde_contrato = $sigarhu_fecha_desde_contrato;
				$empleado->hasta_contrato = !empty($sigarhu_fecha_hasta_contrato)
					? $sigarhu_fecha_hasta_contrato : $empleado->hasta_contrato;
				Modelo\Empleado::anularValidacion();
				$empleado->cambiar_contrato();
				if(!empty($sigarhu_fecha_hasta_contrato)){
					$empleado->hasta_contrato = !empty($sigarhu_fecha_hasta_contrato)
						? $sigarhu_fecha_hasta_contrato : $sigarhu_fecha_desde_contrato;
					Modelo\Empleado::anularValidacion();
					$empleado->cancelar_contrato();
				}
				$modificacion_de_contrato	= true;
			}
			if($modificacion_de_contrato){
				$datos_modificados	= array_merge($datos_modificados, $log_modificacion_de_contrato);
			}
        }

        $sigarhu_fecha_desde_principal = ($sigarhu->dependencia->fecha_desde instanceof \DateTime)
            ? $sigarhu->dependencia->fecha_desde
            : \DateTime::createFromFormat('Y-m-d', $sigarhu->dependencia->fecha_desde);
        $sigarhu_fecha_hasta_principal = ($sigarhu->dependencia->fecha_hasta instanceof \DateTime)
            ? $sigarhu->dependencia->fecha_hasta
			: \DateTime::createFromFormat('Y-m-d', $sigarhu->dependencia->fecha_hasta);

        if(!is_null($sigarhu->dependencia->id_dependencia) && ((int)$empleado->dependencia_principal != (int)$sigarhu->dependencia->id_dependencia || $empleado->desde_principal != $sigarhu_fecha_desde_principal)){
			$datos_modificados['dependencia_principal']		= ['ca'=>$empleado->dependencia_principal, 'sig'=>$sigarhu->dependencia->id_dependencia];
			$datos_modificados['fecha_desde_principal']		= ['ca'=>static::parseDL($empleado->desde_principal), 'sig'=>static::parseDL($sigarhu_fecha_desde_principal)];
			$datos_modificados['fecha_hasta_principal']		= ['ca'=>static::parseDL($empleado->hasta_principal), 'sig'=>static::parseDL($sigarhu_fecha_hasta_principal)];

            $empleado->dependencia_principal	= $sigarhu->dependencia->id_dependencia;
            $empleado->desde_principal    = $sigarhu_fecha_desde_principal;
            $empleado->hasta_principal	= $sigarhu_fecha_hasta_principal;

            Modelo\IntegracionSigarhu::modificarDependencia($empleado);
		}

		if($empleado != $empleado_control_cambios){
			$empleado_control_actualizacion			= Modelo\IntegracionSigarhu::obtenerEmpleadoPorDocumento($sigarhu->persona->documento);
			$aux									= [
				'cambios_guardados'	=> ($empleado_control_cambios != $empleado_control_actualizacion)
			];
			$datos_modificados						= $aux += $datos_modificados;
			Modelo\IntegracionSigarhu::logActualizaciones((int)$sigarhu->cuit, (int)$empleado->id, (int)$sigarhu->id, json_encode($datos_modificados));
		}
		return true;

	}

/**
 * Aqui se realiza una busqueda y actualizacion de datos adiciopnales requeridos para la creacion/actualizacion de un Empleado.
 * Ej.: Dependencias, y situaciones de revista.
 *
 * @return void
 */
    private function actualizarParametricos(){
		$param	= Modelo\SigarhuApi::getParametricos(['vinculacion_revista', 'dependencias']);
		if(empty($param)){
			$this->debug(Modelo\SigarhuApi::getErrores(), false, 'sigarhu_errores');
		}

        /* ----------------------------
         * -- Situaciones de Revista --
         * ------------------------- */

		$existente	= Modelo\SituacionRevista::listarParaSelect();
		$mod_vinc	= $param['vinculacion_revista']['modalidad_vinculacion']; // ['id' => int, 'nombre'=> string, 'borrado' => int]
		$sit_rev	= $param['vinculacion_revista']['situacion_revista']; // ['(int)modalidad_vinculacion_id' => ['id' => int, 'nombre'=> string, 'borrado' => int]]

		foreach ((array)$sit_rev as $id_modalidad_vinculacion	=> $_sit_rev) {
			foreach ($_sit_rev as $revista) {
				$_existente	= Helper\Arr::path($existente, $revista['id'], ['id'=>false,'nombre'=>'','borrado'=>'0']);

				$borrado	= (bool)$mod_vinc[$id_modalidad_vinculacion]['borrado'] || (bool)$revista['borrado'];
				$nombre		= $mod_vinc[$id_modalidad_vinculacion]['nombre'].' - '.$revista['nombre'];
				$modificado	= (
					!empty($_existente['id'])
					&& ($borrado != (bool)$_existente['borrado']
					|| $nombre != $_existente['nombre']
				));

				if(array_key_exists($revista['id'], $existente) && !$modificado){
					continue;
				}
				$actual_ca	= Modelo\SituacionRevista::obtener($revista['id']);
				$actual_ca->id_situacion_revista		= $revista['id'];
				$actual_ca->id_modalidad_vinculacion	= $id_modalidad_vinculacion;
				$actual_ca->nombre						= $nombre;
				$actual_ca->borrado						= $borrado;

				if(!empty($actual_ca->id)){
					$actual_ca->modificacion();
				} else {
					$actual_ca->alta();
				}
			}
        }

        /* ----------------------------
         * -- Dependencias --
         * ------------------------- */

        $dependencias   = $param['dependencias'];
        $existente      = Modelo\Direccion::listar();

        foreach ((array)$dependencias as $dep) {
            $fecha_desde     = ($dep['fecha_desde'] instanceof \DateTime) ? $dep['fecha_desde']->format('Y-m-d') : null;
            $fecha_hasta     = ($dep['fecha_hasta'] instanceof \DateTime) ? $dep['fecha_hasta']->format('Y-m-d') : null;
            if(in_array($dep['id'], $existente) && (
                $existente[$dep['id']]['codep'] == $dep['codep']
                && $existente[$dep['id']]['nombre'] == $dep['nombre']
                && $existente[$dep['id']]['id_padre'] == $dep['id_padre']
                && $existente[$dep['id']]['fecha_desde'] === $fecha_desde
                && $existente[$dep['id']]['fecha_hasta'] === $fecha_hasta
            )){
               continue;
            }
            Modelo\Direccion::anularValidacion();
            $actual_ca  = Modelo\Direccion::obtener($dep['id']);

            $actual_ca->codep           = $dep['codep'];
            $actual_ca->nombre          = $dep['nombre'];
            $actual_ca->id_padre        = $dep['id_padre'];
            $actual_ca->fecha_desde     = $fecha_desde;
            $actual_ca->fecha_hasta     = $fecha_hasta;
            $actual_ca->visible         = '1';

            Modelo\Direccion::anularValidacion();
            if(empty($actual_ca->id)){
                $actual_ca->alta();
            } else {
                $actual_ca->modificacion();
            }
        }
    }

 /**
 * Trae las locaciones de la api, por cada una de ellas, busca en la tabla ubicaciones de CA, si existe, si no existe la inserta con sus respectivos datos,
  si no, actualiza todos los datos de la locación.
 *
 * @return void
 */
    public function accion_actualizar_locacion(){
		
		$locaciones = Modelo\LocacionesApi::getListadoOficinas_reloj();

		if(!empty($locaciones)){
     		Modelo\Ubicacion::borradoInicialDeLocaciones(); 
    	}
	 	
	 	foreach ($locaciones as $key => $locacion) {
	        
	        $resp = Modelo\Ubicacion::buscarLocacionApi($locacion['id_locacion'],$locacion['id_edificio'], $locacion['id_oficina']);

	        if(!empty($resp)){ //viene una fila de resultado  id_ubicacion_api - id_edificio_api - id_oficina_api 
	        	if(!empty($resp['id'])){
	        		Modelo\Ubicacion::actualizarLocacion($locacion, $resp['id']);
	        	}else{
	        		Modelo\Ubicacion::insertarLocacion($locacion);
	        	}

	        }else{
	        	Modelo\Ubicacion::insertarLocacion($locacion);
	        }

		}
	}	
}