<?php

namespace App\Controlador;

use App\Helper\Vista;
use App\Modelo;
use App\Modelo\Empleado;
use App\Modelo\Persona;
use App\Modelo\Template;
use App\Modelo\Ubicacion;
use App\Modelo\Visita;

class Visitas extends Base
{

	protected function accion_index()
	{

		$estado = ($this->request->query('id') == "0") ? 0 : 1;
		$ubicaciones_autorizadas = $this->lista_select_ubicaciones(Ubicacion::listar());
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','estado','ubicaciones_autorizadas')))->pre_render();
	}

	public function accion_ajax()
	{
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
					'dependencia_autorizada' => $this->request->query('ubicaciones_autorizadas'),
					'enrolado' => $this->request->query('enrolado')
				],
		];


		$estado = ($this->request->query('id'));
	
		$data = Visita::ajax($params,$estado);
		$datos['draw']    = (int) $this->request->query('draw');
		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}

	public function accion_alta()
	{
		$visita = Modelo\Visita::obtener(null);
		
		if ($this->request->post('operacion') == 'alta') {
			
			$visita->persona = Persona::obtenerPorDocumento($this->request->post('documento_persona'));
			if($visita->persona->id == null){
				$visita->persona->documento = $this->request->post('documento_persona');
				$visita->persona->nombre = $this->request->post('nombre');
				$visita->persona->apellido = $this->request->post('apellido');
				$visita->persona->genero = !empty($temp = $this->request->post('genero')) ? $temp : null;
				if($visita->persona->validar()){
					$visita->persona->id = $visita->persona->alta();
				}else{
					$err	= $visita->persona->errores;
					foreach ($err as $text) {
						$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					}
				}
			}
			$visita->ubicacion = !empty($temp = $this->request->post('ubicacion_autorizada')) ? Ubicacion::obtener($temp) : null;
			$visita->fecha_desde = !empty($temp = $this->request->post('fecha_desde')) ? $temp : null;
			$visita->fecha_hasta =  !empty($temp = $this->request->post('fecha_hasta')) ? $temp : null;
			$visita->aclaracion_autorizacion =  !empty($temp = $this->request->post('aclaracion_autorizacion')) ? $temp : null;
			$visita->autorizante =  !empty($temp = $this->request->post('id_empleado_autorizante')) ? Empleado::obtener($temp) :Empleado::obtener(null);
			$visita->autorizante->nombre =  !empty($temp = $this->request->post('autorizante_nombre')) ? $temp : null;

			if ($visita->validar()) {
				$visita->alta();
				$this->mensajeria->agregar("AVISO:La visita fué ingresada de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
				$redirect = Vista::get_url("index.php/Visitas/index");
				$this->redirect($redirect);
			} else {
				$err	= $visita->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}

		}
		$lista_generos	= \App\Modelo\Persona::$TIPO_GENEROS;
		$generos = [];
		foreach ($lista_generos as $value) {
			$generos[$value['id']] = $value['nombre'];
		}
		$ubicaciones_autorizadas = $this->lista_select_ubicaciones(Ubicacion::listar());

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'visita', 'ubicaciones_autorizadas','generos')))->pre_render();
	}

	
    public function accion_baja(){
		$visita = Visita::obtener($this->request->query('id'));

		if(!$visita->id){
			$this->mensajeria->agregar("ERROR: La visita no fue encontrada.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
            $redirect =Vista::get_url("index.php/Visitas/index");
            $this->redirect($redirect);	
		}

		if($this->request->post('confirmar') == 1) {
            $save = $visita->baja();
            if($save){
                $this->mensajeria->agregar("AVISO: Visita dada de baja de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
            }else{
                $this->mensajeria->agregar("ERROR: La visita no fue borrada.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
            }
            $redirect =Vista::get_url("index.php/Visitas/index");
            $this->redirect($redirect);	
        }


		$vista = $this->vista;
		(new Vista($this->vista_default,compact('vista', 'visita')))->pre_render();
	}

	public function accion_modificacion()
	{
		$visita = Modelo\Visita::obtener($this->request->query('id'));
		
		if(!$visita->id){
			$this->mensajeria->agregar("ERROR: La visita no fue encontrada.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
            $redirect =Vista::get_url("index.php/Visitas/index");
            $this->redirect($redirect);	
		}

		if ($this->request->post('operacion') == 'modificacion') {
			
			$visita->persona = !empty($temp = $this->request->post('documento_persona')) ?  Persona::obtenerPorDocumento($temp) : null;
			$visita->ubicacion = !empty($temp = $this->request->post('ubicacion_autorizada')) ? Ubicacion::obtener($temp) : null;
			$visita->fecha_desde = !empty($temp = $this->request->post('fecha_desde')) ? $temp : null;
			$visita->fecha_hasta =  !empty($temp = $this->request->post('fecha_hasta')) ? $temp : null;
			$visita->aclaracion_autorizacion =  !empty($temp = $this->request->post('aclaracion_autorizacion')) ? $temp : null;
			$visita->autorizante =  !empty($temp = $this->request->post('id_empleado_autorizante')) ? Empleado::obtener($temp) :Empleado::obtener(null);
			$visita->autorizante->nombre =  !empty($temp = $this->request->post('autorizante_nombre')) ? $temp : null;

			if ($visita->validar()) {
				$resp = $visita->modificacion();
				if($resp){
					$this->mensajeria->agregar("AVISO:La visita fué modificada de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
					$redirect = Vista::get_url("index.php/Visitas/index");
					$this->redirect($redirect);
				}else{
					$this->mensajeria->agregar("ERROR: La visita no se pudo modificar.".$resp,\FMT\Mensajeria::TIPO_ERROR,$this->clase);
					$redirect =Vista::get_url("index.php/Visitas/index");
					$this->redirect($redirect);	
				}
			} else {
				$err	= $visita->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}

		}

		$visita->fecha_desde = (!empty($visita->fecha_desde)) ? date("d/m/Y", strtotime($visita->fecha_desde)) : null;
		$visita->fecha_hasta = (!empty($visita->fecha_hasta)) ? date("d/m/Y", strtotime($visita->fecha_hasta)) : null;
		
		$ubicaciones_autorizadas = $this->lista_select_ubicaciones(Ubicacion::listar());

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'visita','ubicaciones_autorizadas')))->pre_render();
	}

	public function accion_enrolar(){
		$visita = Visita::obtener($this->request->query('id'));
		$estaEnrolado=Visita::estaEnrolado($visita->persona->id);

		if(!$visita->id){
			$this->mensajeria->agregar("ERROR: La visita no fue encontrada.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
            $redirect =Vista::get_url("index.php/Visitas/index");
            $this->redirect($redirect);	
		}

		if($this->request->post('confirmar') == 1) {
            $redirect =Vista::get_url("index.php/Visitas/index");
            $this->redirect($redirect);	
        }

		$vista = $this->vista;
		(new Vista($this->vista_default,compact('vista', 'visita', 'estaEnrolado')))->pre_render();
	}

	public function accion_actualizar_ubicacion(){

		if(empty($ubicacion_id = $this->request->post('ubicacion_id')) || empty($documento_persona = $this->request->post('access_id'))){
			return http_response_code(400);
		}

		$persona	= Persona::obtenerPorDocumento($documento_persona);
		$success	= $persona->actualizarTemplate($ubicacion_id);

		$ubicacion	= $ubicacion_id;
		
		$data["success"] = $success;
		$data["ubicacion"] = $ubicacion;

		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}

	public function accion_buscar_template_por_access_id(){
		
		$access_id = $this->request->post('access_id');
		$persona = Persona::obtenerPorDocumento($access_id);
		$success = false;
		if (!empty($persona->id)) {
			/** @var Template[] $lista */
			$templates = Template::obtenerDelEnrolador($persona);
			$success = true;
			foreach ($templates as $template) {
				$success &= $template->validar();
			}
		}

		$data["success"] = $success;
		$data["templates"] = $templates;

		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}

	public function accion_guardar_template_por_access_id(){
		$access_id =$this->request->post('access_id');
		$ubicacion_id = $this->request->post('ubicacion_id');
		$persona = Persona::obtenerPorDocumento($access_id);
		$success = false;
		$msg = "";
		if (!empty($persona->id)) {
			$lista = Template::obtenerDelEnrolador($persona);
			$templates = [];
			$res = false;
			foreach ($lista as $template) {
				$res = $template->alta();
				if(!$res){
					break;
				}
			}
			if($res) {
				$success = $persona->bajaEnEnrolador(); 
				$persona->distribuirTemplates($ubicacion_id); 
			}else{
				
				foreach ($lista as $template) {
					$template->baja();
				}
				$msg = 'Error en los templates';
			}
		}
		$templates[] = [
			'status'  => $success,
			'message' => $msg,
		];

		$data["success"] = $success;
		$data["templates"] = $templates;

		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}

	/**
	 * ===================================
	 *  FUNCIONES INTERNAS DEL CONTROLADOR
	 * ===================================
	 */
	private function lista_select_ubicaciones($lista = []){
		$listado = [];

		foreach ($lista as $item) {
			if(is_object($item)){
				$listado[$item->id] = ['id' => $item->id, 'nombre' =>$item->nombre,'borrado' => $item->borrado];
			}else{
				$listado[$item["id"]] = ['id' => $item["id"], 'nombre' =>$item["nombre"],'borrado' => $item["borrado"]];
			}
		}
		return $listado;
	}


}
