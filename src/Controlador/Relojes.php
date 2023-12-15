<?php

namespace App\Controlador;

use App\Helper\Util;
use App\Helper\Vista;
use App\Modelo\Reloj;
use App\Modelo\AppRoles;
use App\Modelo\Persona;
use App\Modelo\TipoReloj;
use App\Modelo\Ubicacion;
use App\Modelo\Visita;

class Relojes extends Base
{
	/** @var  Reloj */
	public $reloj;

	public function accion_index()
	{
		$estados = Reloj::obtenerEstadoRelojes();			

		if (is_null($estados) || !$estados) {
			$this->mensajeria->agregar(' No se obtuvo respuesta del servidor, el estado de los relojes no se conoce.', \FMT\Mensajeria::TIPO_ERROR, $this->clase);
		}
		$permiso = AppRoles::obtener_rol();
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','permiso')))->pre_render();
	}

	public function accion_ajax_relojes(){

		$dataTable_columns	= $this->request->query('columns');
		$orders	= [];
		foreach($orden = (array)$this->request->query('order') as $i => $val){
				$orders[]	= [
						'campo'	=> (!empty($tmp = $orden[$i]) && !empty($dataTable_columns) && is_array($dataTable_columns[0]))
										? $dataTable_columns[ (int)$tmp['column'] ]['data']	:	'id',
						'dir'	=> !empty($tmp = $orden[$i]['dir'])
										? $tmp	:	'desc',
				];
		}
		$date  = [];
		if( preg_match('/^\d{2}\/\d{2}\/\d{4}/', $this->request->query('search')['value'],$date)){
				$el_resto = \preg_replace('/^\d{2}\/\d{2}\/\d{4}/','', $this->request->query('search')['value']);
				$search = \DateTime::createFromFormat('d/m/Y',$date[0])->format('Y-m-d').$el_resto;
		}else {
				$search = $this->request->query('search')['value'];
		}

		$params	= [
				'order'		=> $orders,
				'start'		=> !empty($tmp =$this->request->query('start'))
										? $tmp : 0,
				'lenght'	=> !empty($tmp = $this->request->query('length'))
										? $tmp : 10,
				'search'	=> !empty($search)
										? $search : '',
				'filtros'   => [

				],
		];

		$relojes = Reloj::listarAjax($params);
		$estados = Reloj::obtenerEstadoRelojes();

		$datos = [];
		foreach ($relojes["data"] as &$reloj) {
			$ultima_conexion = ($estados) ? \DateTime::createFromFormat('Y-m-d H:i:s', $estados[$reloj->nodo][1]) : null;
			$logRelojes = Reloj::listar_log_filtro($reloj->nodo, [], 5);
			$logRelojesLista = '';
			foreach ($logRelojes as $log) {
				$fecha =  \DateTime::createFromFormat('d/m/Y H:i', $log['fecha']);
				$logRelojesLista .= '<small><b>' . $fecha->format('d-m-Y H:i') . '</b> - ' . $log['mensaje'] . '</small><br>';
			}
			$estado = isset($estados[$reloj->nodo]) && $estados[$reloj->nodo][0] ? '<i class="fa fa-plug text-success" title="Conectado" aria-hidden="true"></i>' : '<i class="fa fa-plug text-danger" title="Desconectado" aria-hidden="true"></i>';
			$estado = "<div class='btn-group btn-group-sm' data-toggle='popover' data-placement='right' title='Últimos estados del reloj' data-content='{$logRelojesLista}'>{$estado}</div>";
			$reloj->estado = $estado;
			$reloj->ultima_conexion = ($ultima_conexion instanceof \DateTime) ? $ultima_conexion->format('d/m/Y H:i:s') : '--';
			$reloj->acceso_restringido = $reloj->acceso_restringido == 1 ? "Si" : "No";
			$reloj->acceso_tarjeta =  $reloj->acceso_tarjeta == 1 ? "Si" : "No";

		}
		$data = $relojes;
		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}

	public function accion_alta(){
		$reloj = Reloj::obtener(null);

		if($this->request->post('operacion') == "alta"){
			$reloj->ip = mb_strtolower(trim(Util::getPost('ip')), 'UTF-8');
			$reloj->puerto = (int)Util::getPost('puerto');
			$reloj->dns = !empty($this->request->post('dns')) ? $this->request->post('dns') : ''; 
			$reloj->numero_serie = mb_strtoupper(trim(Util::getPost('numero_serie')), 'UTF-8');
			$reloj->marca = mb_strtoupper(trim(Util::getPost('marca')), 'UTF-8');
			$reloj->modelo = mb_strtoupper(trim(Util::getPost('modelo')), 'UTF-8');
			$reloj->nodo = (int)Util::getPost('nodo');
			$reloj->tipo_id = (int)Util::getPost('tipo_id');
			$reloj->ubicacion_id = (int)Util::getPost('ubicacion_id');
			$reloj->notas = !empty($this->request->post('notas')) ? $this->request->post('notas') : ''; 
			$reloj->tipo_reloj = TipoReloj::obtener($reloj->tipo_id);
			$reloj->ubicacion = Ubicacion::obtener($reloj->ubicacion_id);			
			$reloj->acceso_restringido = ($this->request->post('acceso_restringido')) ? 1 : 0;
			$reloj->acceso_tarjeta = ($this->request->post('acceso_tarjeta')) ? 1 : 0;

			if($reloj->validar()){
				$res = $reloj->alta();
				$this->mensajeria->agregar(
					"AVISO:El reloj fué creado de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
					$redirect =Vista::get_url("index.php/Relojes/index");
					$this->redirect($redirect);	
			}else {
				$err	= $reloj->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}
		}
		$tipos_relojes = $this->lista_select_tipoReloj(TipoReloj::listar());
		$ubicaciones = $this->lista_select_ubicacion(Ubicacion::listar());

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','reloj','tipos_relojes','ubicaciones')))->pre_render();
	}

	public function accion_modificacion(){
		$reloj = Reloj::obtener($this->request->query('id'));

		if($this->request->post('operacion') == "modificacion"){

			$reloj->ip = mb_strtolower(trim(Util::getPost('ip')), 'UTF-8');
			$reloj->puerto = (int)Util::getPost('puerto');
			$reloj->dns = !empty($this->request->post('dns')) ? $this->request->post('dns') : ''; 
			$reloj->numero_serie = mb_strtoupper(trim(Util::getPost('numero_serie')), 'UTF-8');
			$reloj->marca = mb_strtoupper(trim(Util::getPost('marca')), 'UTF-8');
			$reloj->modelo = mb_strtoupper(trim(Util::getPost('modelo')), 'UTF-8');
			$reloj->nodo = (int)Util::getPost('nodo');
			$reloj->tipo_id = (int)Util::getPost('tipo_id');
			$reloj->ubicacion_id = (int)Util::getPost('ubicacion_id');
			$reloj->notas = !empty($this->request->post('notas')) ? $this->request->post('notas') : ''; 
			$reloj->tipo_reloj = TipoReloj::obtener($reloj->tipo_id);
			$reloj->ubicacion = Ubicacion::obtener($reloj->ubicacion_id);			
			$reloj->acceso_restringido = ($this->request->post('acceso_restringido')) ? 1 : 0;
			$reloj->acceso_tarjeta = ($this->request->post('acceso_tarjeta')) ? 1 : 0;
			
			if($reloj->validar()){
				$res = $reloj->modificacion();
				if($res){
					$this->mensajeria->agregar(
						"AVISO:El reloj fué modificaco de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
						$redirect =Vista::get_url("index.php/relojes/index");
						$this->redirect($redirect);	
				}else{
					$this->mensajeria->agregar('Ocurrió un error en la modificación.', \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}else {
				$err	= $reloj->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}
		}
		$tipos_relojes = $this->lista_select_tipoReloj(TipoReloj::listar());
		$ubicaciones = $this->lista_select_ubicacion(Ubicacion::listar());

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','reloj','tipos_relojes','ubicaciones')))->pre_render();
	}


	public function accion_baja(){
		$reloj = Reloj::obtener($this->request->query('id'));

		if(!$reloj->id){
			$this->mensajeria->agregar(
				"ERROR: El Reloj no fue encontrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				$redirect =Vista::get_url("index.php/Relojes/index");
				$this->redirect($redirect);	
		}

		if($this->request->post('confirmar') == 1) {
			$save = $reloj->baja();
			if($save){
				$this->mensajeria->agregar(
				"AVISO:El Reloj ha sido dado de baja de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
			}else{
				$this->mensajeria->agregar(
					"ERROR: El Reloj no fue borrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
			}
			$redirect =Vista::get_url("index.php/Relojes/index");
			$this->redirect($redirect);	
		}

		$vista = $this->vista;
		(new Vista($this->vista_default,compact('vista', 'reloj')))->pre_render();
	}
	

	public function accion_enrolador(){
		$reloj = Reloj::obtener($this->request->query('id'));

		if(!$reloj->id){
			$this->mensajeria->agregar(
				"ERROR: El Reloj no fue encontrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				$redirect =Vista::get_url("index.php/Relojes/index");
				$this->redirect($redirect);	
		}

		if($this->request->post('confirmar') == 1) {
				$save = $reloj->enrolar();
				if($save){
					$this->mensajeria->agregar(
					"AVISO:El Reloj <b>".$reloj->numero_serie."</b> configurado como <b>ENROLADOR</b>.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
				}else{
					$this->mensajeria->agregar(
						"ERROR: No se pudo configurar al Reloj <b>".$reloj->numero_serie."</b> como <b>ENROLADOR</b>.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				}
				$redirect =Vista::get_url("index.php/Relojes/index");
				$this->redirect($redirect);	
			}

		$vista = $this->vista;
		(new Vista($this->vista_default,compact('vista', 'reloj')))->pre_render();
	}


	public function accion_actualizar_templates(){
		$reloj = Reloj::obtener($this->request->query('id'));

		if(!$reloj->id){
			$this->mensajeria->agregar(
				"ERROR: El Reloj no fue encontrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				$redirect =Vista::get_url("index.php/Relojes/index");
				$this->redirect($redirect);	
		}

		if($this->request->post('confirmar') == 1) {
				$resp = $reloj->actualizarTemplates();
				if($resp){
					if($resp["exitos"] > 0){
						$this->mensajeria->agregar("AVISO: Se Actualizaron <b>".$resp["exitos"]."</b> huellas de un total de <b>".$resp["registros"]."</b> para el Reloj <b>".$reloj->dns."</b>",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
					}else{
						$this->mensajeria->agregar("AVISO: Se Actualizaron <b>".$resp["exitos"]."</b> huellas de un total de <b>".$resp["registros"]."</b> para el Reloj <b>".$reloj->dns."</b>",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
					}
				}else{
					$this->mensajeria->agregar(
						"ERROR: No se pudieron actualizar las huellas de empleados para el Reloj <b>".$reloj->dns."</b>",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				}
				$redirect =Vista::get_url("index.php/Relojes/index");
				$this->redirect($redirect);	
			}

		$vista = $this->vista;
		(new Vista($this->vista_default,compact('vista', 'reloj')))->pre_render();
	}

	public function accion_historicoLogsPorNodo(){
		$reloj = Reloj::obtener($this->request->query('id'));
		$codigos = $this->lista_select_codigoError(Reloj::$CODIGOS_LOGS);
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','reloj','codigos')))->pre_render();
	}

	public function accion_ajax_historicoLogsPorNodo(){
		$dataTable_columns	= $this->request->query('columns');
		$orders	= [];
		foreach($orden = (array)$this->request->query('order') as $i => $val){
				$orders[]	= [
						'campo'	=> (!empty($tmp = $orden[$i]) && !empty($dataTable_columns) && is_array($dataTable_columns[0]))
										? $dataTable_columns[ (int)$tmp['column'] ]['data']	:	'id',
						'dir'	=> !empty($tmp = $orden[$i]['dir'])
										? $tmp	:	'desc',
				];
		}
		$date  = [];
		if( preg_match('/^\d{2}\/\d{2}\/\d{4}/', $this->request->query('search')['value'],$date)){
				$el_resto = \preg_replace('/^\d{2}\/\d{2}\/\d{4}/','', $this->request->query('search')['value']);
				$search = \DateTime::createFromFormat('d/m/Y',$date[0])->format('Y-m-d').$el_resto;
		}else {
				$search = $this->request->query('search')['value'];
		}
		$params	= [
				'order'		=> $orders,
				'start'		=> !empty($tmp =$this->request->query('start'))
										? $tmp : 0,
				'lenght'	=> !empty($tmp = $this->request->query('length'))
										? $tmp : 10,
				'search'	=> !empty($search)
										? $search : '',
				'filtros'   => [
					'codigo' => $this->request->query('codigo_error'),
					'fecha_desde' => $this->request->query('fecha_desde'),
					'fecha_hasta' => $this->request->query('fecha_hasta'),
				],
		];

		$nodo = $this->request->query('id');
		$data = Reloj::listarAjaxHistoricosLogsPorNodo($params,$nodo);


		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}

	public function accion_sincronizacion(){
		$reloj = Reloj::obtener($this->request->query('id'));

		if(!$reloj->id){
			$this->mensajeria->agregar(
				"ERROR: El Reloj no fue encontrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				$redirect =Vista::get_url("index.php/relojes/index");
				$this->redirect($redirect);	
		}

		$vista = $this->vista;
		(new Vista($this->vista_default,compact('vista', 'reloj')))->pre_render();
	}


	public function accion_ajax_sincronizacion(){

		$dataTable_columns	= $this->request->query('columns');
		$orders	= [];
		foreach($orden = (array)$this->request->query('order') as $i => $val){
				$orders[]	= [
						'campo'	=> (!empty($tmp = $orden[$i]) && !empty($dataTable_columns) && is_array($dataTable_columns[0]))
										? $dataTable_columns[ (int)$tmp['column'] ]['data']	:	'id',
						'dir'	=> !empty($tmp = $orden[$i]['dir'])
										? $tmp	:	'desc',
				];
		}
		$date  = [];
		if( preg_match('/^\d{2}\/\d{2}\/\d{4}/', $this->request->query('search')['value'],$date)){
				$el_resto = \preg_replace('/^\d{2}\/\d{2}\/\d{4}/','', $this->request->query('search')['value']);
				$search = \DateTime::createFromFormat('d/m/Y',$date[0])->format('Y-m-d').$el_resto;
		}else {
				$search = $this->request->query('search')['value'];
		}
		$params	= [
				'order'		=> $orders,
				'start'		=> !empty($tmp =$this->request->query('start'))
										? $tmp : 0,
				'lenght'	=> !empty($tmp = $this->request->query('length'))
										? $tmp : 10,
				'search'	=> !empty($search)
										? $search : '',
				'filtros'   => [
					],
		];

		$nodo = $this->request->query('id');
		$data = Reloj::listarAjaxSincronizacion($params,$nodo);


		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}


	public function accion_sincronizacion_marcaciones(){
		$id_lote =$this->request->query('id');

		if(!$id_lote){
			$this->mensajeria->agregar(
				"ERROR: El Lote no fue encontrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				$redirect =Vista::get_url("index.php/Relojes/index");
				$this->redirect($redirect);	
		}

		$vista = $this->vista;
		(new Vista($this->vista_default,compact('vista', 'id_lote')))->pre_render();
	}


	public function accion_ajax_sincronizacion_marcacion(){

		$dataTable_columns	= $this->request->query('columns');
		$orders	= [];
		foreach($orden = (array)$this->request->query('order') as $i => $val){
				$orders[]	= [
						'campo'	=> (!empty($tmp = $orden[$i]) && !empty($dataTable_columns) && is_array($dataTable_columns[0]))
										? $dataTable_columns[ (int)$tmp['column'] ]['data']	:	'id',
						'dir'	=> !empty($tmp = $orden[$i]['dir'])
										? $tmp	:	'desc',
				];
		}
		$date  = [];
		if( preg_match('/^\d{2}\/\d{2}\/\d{4}/', $this->request->query('search')['value'],$date)){
				$el_resto = \preg_replace('/^\d{2}\/\d{2}\/\d{4}/','', $this->request->query('search')['value']);
				$search = \DateTime::createFromFormat('d/m/Y',$date[0])->format('Y-m-d').$el_resto;
		}else {
				$search = $this->request->query('search')['value'];
		}
		$params	= [
				'order'		=> $orders,
				'start'		=> !empty($tmp =$this->request->query('start'))
										? $tmp : 0,
				'lenght'	=> !empty($tmp = $this->request->query('length'))
										? $tmp : 10,
				'search'	=> !empty($search)
										? $search : '',
				'filtros'   => [
					],
		];

		$lote = $this->request->query('id');
		$data = Reloj::listarAjaxSincronizacionMarcacion($params,$lote);

		
		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}



	public function accion_alta_daemon(){
		$reloj = Reloj::obtener($this->request->query('id'));

		if(!$reloj->id){
			$this->mensajeria->agregar("ERROR: El Reloj no fue encontrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
			$redirect =Vista::get_url("index.php/Relojes/index");
			$this->redirect($redirect);	
		}

		$res = $reloj->alta_daemon();
		if($res){
			$this->mensajeria->agregar("Reloj agregado en Daemon de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
			$redirect =Vista::get_url("index.php/Relojes/index");
			$this->redirect($redirect);	
		}else{
			$this->mensajeria->agregar("ERROR: El Reloj no pudo ser agregado en daemon.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
			$redirect =Vista::get_url("index.php/Relojes/index");
			$this->redirect($redirect);	
		}
		
		return false;
	}


	public function accion_recargar_daemon(){
		$reloj = Reloj::obtener($this->request->query('id'));

		if(!$reloj->id){
			$this->mensajeria->agregar("ERROR: El Reloj no fue encontrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				$redirect =Vista::get_url("index.php/Relojes/index");
				$this->redirect($redirect);	
		}

		$res = $reloj->recargar_daemon();
		if($res){
			$this->mensajeria->agregar("Reloj recargado en Daemon de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
			$redirect =Vista::get_url("index.php/Relojes/index");
			$this->redirect($redirect);	
		}else{
			$this->mensajeria->agregar("ERROR: El Reloj no pudo ser recargado en daemon.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
			$redirect =Vista::get_url("index.php/Relojes/index");
			$this->redirect($redirect);	
		}
		
		return false;
	}

	public function accion_accesos_restringidos(){
		$reloj = Reloj::obtener($this->request->query('id'));

		if(!$reloj->id){
			$this->mensajeria->agregar(
				"ERROR: El Reloj no fue encontrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				$redirect =Vista::get_url("index.php/relojes/index");
				$this->redirect($redirect);	
		}

		$vista = $this->vista;
		(new Vista($this->vista_default,compact('vista', 'reloj')))->pre_render();
	}

	public function accion_ajax_accesos_restringidos(){

		$dataTable_columns	= $this->request->query('columns');
		$orders	= [];
		foreach($orden = (array)$this->request->query('order') as $i => $val){
				$orders[]	= [
						'campo'	=> (!empty($tmp = $orden[$i]) && !empty($dataTable_columns) && is_array($dataTable_columns[0]))
										? $dataTable_columns[ (int)$tmp['column'] ]['data']	:	'id',
						'dir'	=> !empty($tmp = $orden[$i]['dir'])
										? $tmp	:	'desc',
				];
		}
		$date  = [];
		if( preg_match('/^\d{2}\/\d{2}\/\d{4}/', $this->request->query('search')['value'],$date)){
				$el_resto = \preg_replace('/^\d{2}\/\d{2}\/\d{4}/','', $this->request->query('search')['value']);
				$search = \DateTime::createFromFormat('d/m/Y',$date[0])->format('Y-m-d').$el_resto;
		}else {
				$search = $this->request->query('search')['value'];
		}
		$params	= [
				'order'		=> $orders,
				'start'		=> !empty($tmp =$this->request->query('start'))
										? $tmp : 0,
				'lenght'	=> !empty($tmp = $this->request->query('length'))
										? $tmp : 10,
				'search'	=> !empty($search)
										? $search : '',
				'filtros'   => [
					],
		];

		$id_reloj = $this->request->query('id');
		$data = Reloj::listarAjaxAccesosRestringidos($params,$id_reloj);

		
		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}

	public function accion_accesos_restringidosAlta(){
		$reloj = Reloj::obtener($this->request->query('id'));
		$documento = !empty($temp = $this->request->post('documento_persona')) ? $temp : null;

		if(!$reloj->id){
			$this->mensajeria->agregar("ERROR: El Reloj no fue encontrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
			$redirect =Vista::get_url("index.php/Relojes/index");
			$this->redirect($redirect);	
		}

		if($this->request->post('operacion') == "alta"){
			$empleado = Persona::obtenerEmpleadoVisita($documento);
			if(!$empleado->id){
				$this->mensajeria->agregar("ERROR: La persona no se encuentra.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				$redirect =Vista::get_url("index.php/Relojes/accesos_restringidosAlta/".$reloj->id);
				$this->redirect($redirect);	
			}
			$persona = Persona::obtener($empleado->persona_id);
			$res = $reloj->alta_acceso_restringido($persona);
			if($res){
				$this->mensajeria->agregar("Acceso Restringido agregado de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
				$redirect =Vista::get_url("index.php/Relojes/accesos_restringidos/".$reloj->id);
				$this->redirect($redirect);	
			}else{
				$this->mensajeria->agregar("ERROR: No pudimos agregar el acceso restringido.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				$redirect =Vista::get_url("index.php/Relojes/accesos_restringidosAlta/".$reloj->id);
				$this->redirect($redirect);	
			}


		}

		$ubicacion = $reloj->ubicacion;
		$vista = $this->vista;
		(new Vista($this->vista_default,compact('vista', 'reloj','documento','ubicacion')))->pre_render();
	}

	public function accion_accesos_restringidosBaja(){
		// [0]=> object(App\Modelo\Reloj),[1]=> object(App\Modelo\Persona) 
		$acceso_restringido = Reloj::obtenerRelojAccesoRestringido($this->request->query('id'));
		
		if(empty($acceso_restringido)){
			$this->mensajeria->agregar(
				"ERROR: El Acceso restringido no fue encontrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				$redirect =Vista::get_url("index.php/Relojes/index");
				$this->redirect($redirect);	
		}

		if($this->request->post('confirmar') == 1) {
				$save = Reloj::bajaAccesoRestringido($this->request->query('id'));
				if($save){
					$this->mensajeria->agregar(
					"AVISO: El Acceso restringido ha sido dado de baja de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
				}else{
					$this->mensajeria->agregar(
						"ERROR: El Acceso restringido no fue borrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				}
				$redirect =Vista::get_url("index.php/Relojes/accesos_restringidos/".$acceso_restringido[0]->id);
				$this->redirect($redirect);	
		}

		$vista = $this->vista;
		(new Vista($this->vista_default,compact('vista', 'acceso_restringido')))->pre_render();
	}

	public function accion_ajax_buscarPersona(){

		$reloj = Reloj::obtener($this->request->query('id'));
		$documento = !empty($temp = $this->request->query('documento')) ? $temp : null;
		$empleado = Persona::obtenerEmpleadoVisita($documento);

		if(empty($empleado->id)){
			$empleado->errores = "Persona no encontrada.";
		}else if($empleado instanceof Visita && !$empleado->puedeAcceder($reloj->ubicacion_id)){
			$empleado->errores = "La visita no tiene autorización a la ubicación: ".$reloj->ubicacion->nombre;
		}
		
		$data = $empleado;
		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}
	/**
	 * ===================================
	 *  FUNCIONES INTERNAS DEL CONTROLADOR
	 * ===================================
	 */
	private function lista_select_tipoReloj($lista = []){
		$listado = [];

		foreach ($lista as $item) {
			if(is_object($item)){
				$listado[$item->id] = ['id' => $item->id, 'nombre' =>$item->descripcion,'borrado' => 0];
			}else{
				$listado[$item["id"]] = ['id' => $item["id"], 'nombre' =>$item["descripcion"],'borrado' => 0];
			}
		}
		return $listado;
	}

	private function lista_select_ubicacion($lista = []){
		$listado = [];

		foreach ($lista as $item) {
			if(is_object($item)){
				$listado[$item->id] = ['id' => $item->id, 'nombre' =>$item->nombre,'borrado' => 0];
			}else{
				$listado[$item["id"]] = ['id' => $item["id"], 'nombre' =>$item["nombre"],'borrado' => 0];
			}
		}
		return $listado;
	}

	private function lista_select_codigoError($lista = []){
		$listado = [];
		foreach ($lista as $key => $item) {
			$listado[$key] = ['id' => $key, 'nombre' =>$key." (".$item.") ",'borrado' => 0];
		}
		return $listado;
	}

}
