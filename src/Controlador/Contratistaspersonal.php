<?php

namespace App\Controlador;

use App\Helper\Vista;
use App\Modelo;
use App\Modelo\SituacionRevista;
use App\Helper;
use App\Modelo\ContratistaEmpleado;
use App\Modelo\ContratistaUbicacion;
use App\Modelo\Ubicacion;

class Contratistaspersonal extends Base
{

	protected function accion_index()
	{
		$idContratista = $this->request->query('id');
		$contratista =  Modelo\Contratista::obtener($idContratista);
		$nombreContratista = $contratista->nombre;
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'idContratista', 'nombreContratista')))->pre_render();
	}

	protected function accion_alta()
	{
		$idContratista = $this->request->query('id');
		$contratista =  Modelo\Contratista::obtener($idContratista);
		$nombreContratista = $contratista->nombre;
		$contratistasPersonal = Modelo\ContratistaEmpleado::obtener((int)$this->request->post('id'));
		if ($this->request->post('contratistaspersonal') == 'alta') {
			$persona = Modelo\Persona::obtenerPorDocumento((int)$this->request->post('persona_documento'));
			$autorizante_id = null;
			$autorizante = Modelo\Empleado::obtenerPorDocumento((int)$this->request->post('autorizante_documento'));
			if ($autorizante->id != null) {
				$autorizante_id = $autorizante->id;
			}

			$art_inicio = !empty($temp = $this->request->post('art_inicio')) ? \DateTime::createFromFormat('d/m/Y', $temp)->format('Y-m-d H:i:s') : null;
			$art_fin = !empty($temp = $this->request->post('art_fin')) ?  \DateTime::createFromFormat('d/m/Y', $temp)->format('Y-m-d H:i:s') : null;			
			$contratistasPersonal->contratista_id = $idContratista;
			$contratistasPersonal->autorizante_id = $autorizante_id;			
			$contratistasPersonal->persona_id = $persona->id;
			$contratistasPersonal->documento = !empty($temp = $this->request->post('persona_documento')) ?  $temp : null;
			$contratistasPersonal->nombre = !empty($temp = $this->request->post('persona_nombre')) ?  $temp : null;
			$contratistasPersonal->apellido = !empty($temp = $this->request->post('persona_apellido')) ?  $temp : null;			
			$contratistasPersonal->art_inicio = $art_inicio;
			$contratistasPersonal->art_fin = $art_fin;

			if ($contratistasPersonal->validar()) {
				$contratistasPersonal->alta();	
				$this->mensajeria->agregar("AVISO:El Registro fué ingresado de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
				$redirect = Vista::get_url("index.php/contratistaspersonal/index/".$idContratista);
				$this->redirect($redirect);				
			} else {
				$err	= $contratistasPersonal->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}			
		}
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'idContratista', 'nombreContratista')))->pre_render();
	}

	protected function accion_modificacion()
	{
		$contratistasPersonal = Modelo\ContratistaEmpleado::obtener($this->request->query('id'));

		$idContratista = $contratistasPersonal->contratista->id;
		$nombreContratista = $contratistasPersonal->contratista->nombre;

		if ($this->request->post('contratistaspersonal') == 'modificacion') {
			$persona = Modelo\Persona::obtenerPorDocumento((int)$this->request->post('persona_documento'));
			$autorizante_id = null;
			$autorizante = Modelo\Empleado::obtenerPorDocumento((int)$this->request->post('autorizante_documento'));
			if ($autorizante->id != null) {
				$autorizante_id = $autorizante->id;
			}
	
			$contratistasPersonal->contratista_id = $idContratista;
			$contratistasPersonal->autorizante_id = $autorizante_id;			
			$contratistasPersonal->persona_id = $persona->id;
			$contratistasPersonal->documento = !empty($temp = $this->request->post('persona_documento')) ?  $temp : null;
			$contratistasPersonal->nombre = !empty($temp = $this->request->post('persona_nombre')) ?  $temp : null;
			$contratistasPersonal->apellido = !empty($temp = $this->request->post('persona_apellido')) ?  $temp : null;			
			$contratistasPersonal->art_inicio = !empty($temp = $this->request->post('art_inicio')) ? \DateTime::createFromFormat('d/m/Y', $temp) : null;
			$contratistasPersonal->art_fin = !empty($temp = $this->request->post('art_fin')) ?  \DateTime::createFromFormat('d/m/Y', $temp) : null;	
			
			if ($contratistasPersonal->validar()) {
				$contratistasPersonal->modificacion();	
				$this->mensajeria->agregar("AVISO:El Registro fué modificado de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
				$redirect = Vista::get_url("index.php/contratistaspersonal/index/".$idContratista);
				$this->redirect($redirect);				
			} else {
				$err	= $contratistasPersonal->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}			
		}
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'idContratista', 'nombreContratista', 'contratistasPersonal')))->pre_render();
	}

	protected function accion_baja()
	{
		$contratistasPersonal = Modelo\ContratistaEmpleado::obtener($this->request->query('id'));
		if ($contratistasPersonal->id) {
			if ($this->request->post('confirmar')) {
				$subtitulo = 'Empleado';
				$texto = "Dará de baja al Contratista, </strong>";
				$res = $contratistasPersonal->baja();
				if ($res) {
					$this->mensajeria->agregar('AVISO: Se eliminó un contratistasPersonal de forma exitosa.', \FMT\Mensajeria::TIPO_AVISO, $this->clase, 'index');
					$redirect = Helper\Vista::get_url('index.php/contratistaspersonal/index/'.$contratistasPersonal->contratista->id);
					$this->redirect($redirect);
				}
			}
		} else {
			$redirect = Helper\Vista::get_url('index.php/contratistaspersonal/index/'.$contratistasPersonal->contratista->id);
			$this->redirect($redirect);
		}

		$vista = $this->vista;
		(new Helper\Vista($this->vista_default, compact('contratistasPersonal', 'vista')))->pre_render();

	}

	public function accion_buscarPersonalAjax()
	{

		if (isset($_POST['persona_documento'])) {
			$documento = $_POST['persona_documento'];
			if (!empty($documento) && is_numeric($documento) && mb_strlen($documento, 'UTF-8') >= 6) {
				$empleado =  Modelo\Empleado::obtenerPorDocumento($documento);
				if (empty($empleado) || empty($empleado->id)) {
					$contratista_empleado = Modelo\ContratistaEmpleado::obtenerPorDocumento($documento);

					if (empty($contratista_empleado) || empty($contratista_empleado->id)) {
						if (
							!empty($this->contratista_empleado->persona) &&
							!empty($this->contratista_empleado->persona->id)
						) {
							$data = [
								'dato' => $contratista_empleado,
								'msj' => "Se encontró la Persona " . "{$this->contratista_empleado->persona->full_name}- " . "Documento: <strong>{$documento}</strong>"
							];
							(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
						} else {
							$persona = Modelo\Persona::obtenerPorDocumento($documento);
							if (!empty($persona) && !empty($persona->id)) {
								$data = [
									'dato' => $persona,
									'msj' => "Se encontró la Persona " . "{$persona->nombre} {$persona->apellido} - " . "Documento: {$documento}"
								];
								(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
							} else {
								$data = [
									'dato' => null,
									'msj' => "No hay un registro de Persona con " . "Documento: {$documento}",
								];
								(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
							}
						}
					} else {
						$data = [
							'dato' => null,
							'msj' => "El documento que intenta registrar pertenece al empleado " . "{$contratista_empleado->persona->nombre} {$contratista_empleado->persona->apellido} de la contratista " . "{$contratista_empleado->contratista->nombre}"
						];
						(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
					}
				} else {
					$data = [
						'dato' => null,
						'msj' => 'El documento que intenta registrar pertenece al empleado ' . "{$empleado->nombre} {$empleado->apellido}, el mismo no puede ser cargado como EMPLEADO CONTRATISTA"
					];
					(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
				}
			} else {
				$data = [
					'dato' => null,
					'msj' => 'El Documento de la Persona es necesario para realizar la búsqueda'
				];
				(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
			}
		} else {
			echo 'Error variable POST';
		}
	}

	public function accion_buscarAutorizanteAjax()
	{

		if (isset($_POST['autorizante_documento'])) {
			$empleado =  Modelo\Empleado::obtenerPorDocumento($this->request->post('autorizante_documento'));
			if ($this->request->post('autorizante_documento') != '') {
				if (is_null($empleado->id)) {
					$data = [
						'dato' => null,
						'msj' => 'No hay un registro de Empleado con Documento: ' . $this->request->post('autorizante_documento')
					];
				} else {
					$data = [
						'dato' => $empleado,
						'msj' => "Se encontró el Empleado " . "{$empleado->nombre} {$empleado->apellido} - " . "Documento: {$empleado->documento}",
					];
				}
			} else {
				$data = [
					'dato' => null,
					'msj' => 'El Documento del Empleado Autorizante es necesario para realizar la búsqueda'
				];
			}
			(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
		} else {
			echo 'Error variable POST';
		}
	}

	public function accion_ajax_contratistas_personal()
	{
		$dataTable_columns	= $this->request->query('columns');
		$orders	= [];
		foreach ($orden = (array)$this->request->query('order') as $i => $val) {
			$orders[]	= [
				'campo'	=> (!empty($tmp = $orden[$i]) && !empty($dataTable_columns) && is_array($dataTable_columns[0]))
					? $dataTable_columns[(int)$tmp['column']]['data']	:	'id',
				'dir'	=> !empty($tmp = $orden[$i]['dir'])
					? $tmp	:	'desc',
			];
		}
		$date  = [];
		if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $this->request->query('search')['value'], $date)) {
			$el_resto = \preg_replace('/^\d{2}\/\d{2}\/\d{4}/', '', $this->request->query('search')['value']);
			$search = \DateTime::createFromFormat('d/m/Y', $date[0])->format('Y-m-d') . $el_resto;
		} else {
			$search = $this->request->query('search')['value'];
		}
		$params	= [
			'order'		=> $orders,
			'start'		=> !empty($tmp = $this->request->query('start'))
				? $tmp : 0,
			'lenght'	=> !empty($tmp = $this->request->query('length'))
				? $tmp : 10,
			'search'	=> !empty($search)
				? $search : '',
			'filtros'   => [
				'idContratista'      => $this->request->query('idContratista'),
			],
		];

		$data =  Modelo\ContratistaEmpleado::listar_contratistas_empleados($params);
		$datos['draw']    = (int) $this->request->query('draw');
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	protected function accion_ubicaciones()
	{
		$idPersonal = $this->request->query('id');
		$personal = Modelo\ContratistaEmpleado::obtener($idPersonal);
		$nombreCompletoPersonal = $personal->persona->nombre . " " . $personal->persona->apellido;

		$empresaId = $personal->contratista->id;

		$ubicaciones_select =  Modelo\Ubicacion::listar();
		$ubicaciones = [];
		foreach ($ubicaciones_select as $value) {
			$ubicaciones[$value['id']] = $value['nombre'];
		}

		$contratistaUbicacion =  Modelo\ContratistaUbicacion::obtener(0);

		if ($this->request->post('ubicaciones_agregar') == 'agregar') {

			$ubicacionPermiso = $this->request->post('ubicacion');

			

			$contratistaUbicacion->personal_id = !empty($idPersonal) ?  $idPersonal : null;
			$contratistaUbicacion->ubicacion_id = !empty($temp = $this->request->post('ubicacion')) ?  $temp : null;
			$contratistaUbicacion->acceso_inicio = !empty($temp = $this->request->post('fecha_acceso_inicio')) ? \DateTime::createFromFormat('d/m/Y', $temp)->format('Y-m-d H:i:s') : null;
			$contratistaUbicacion->acceso_fin = !empty($temp = $this->request->post('fecha_acceso_fin')) ? \DateTime::createFromFormat('d/m/Y', $temp)->format('Y-m-d H:i:s') : null;

			$cantPermisos = count(Modelo\ContratistaUbicacion::obtenerPermisos($personal, $ubicacionPermiso));

			if($cantPermisos == 0){
				if ($contratistaUbicacion->validar()) {
					$contratistaUbicacion->alta();
					$this->mensajeria->agregar("AVISO:El Registro fué ingresado de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
					$redirect = Vista::get_url("index.php/contratistaspersonal/ubicaciones/".$idPersonal);
					$this->redirect($redirect);				
				} else {
					$err	= $contratistaUbicacion->errores;
					foreach ($err as $text) {
						$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					}
				}
			}else{
				$this->mensajeria->agregar("AVISO:Ya existe permiso para esa ubicación.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
				$redirect = Vista::get_url("index.php/contratistaspersonal/ubicaciones/".$idPersonal);
				$this->redirect($redirect);								
			}
		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'idPersonal', 'nombreCompletoPersonal', 'ubicaciones','empresaId')))->pre_render();
	}

	protected function accion_ubicacion_baja()
	{
		$contratistaXUbicacion = ContratistaUbicacion::obtener($this->request->query('id'));		
		if ($contratistaXUbicacion->id) {
			$contratistasPersonal = ContratistaEmpleado::obtener($contratistaXUbicacion->personal_id);
			$ubicacion = Ubicacion::obtener($contratistaXUbicacion->ubicacion_id);
			if ($this->request->post('confirmar')) {
				$subtitulo = 'Empleado';
				$texto = "Dará de baja al permiso, </strong>";
				$res = $contratistaXUbicacion->baja();
				if ($res) {
					$this->mensajeria->agregar('AVISO: Se eliminó el permiso de forma exitosa.', \FMT\Mensajeria::TIPO_AVISO, $this->clase);
					$redirect = Helper\Vista::get_url('index.php/contratistaspersonal/ubicaciones/'.$contratistaXUbicacion->personal_id);
					$this->redirect($redirect);
				}
			}
		} else {
			$redirect = Helper\Vista::get_url('index.php/contratistaspersonal/ubicaciones/'.$contratistaXUbicacion->personal_id);
			$this->redirect($redirect);
		}

		$vista = $this->vista;
		(new Helper\Vista($this->vista_default, compact('contratistaXUbicacion','contratistasPersonal','ubicacion', 'vista')))->pre_render();
	}

	protected function accion_ubicacion_editar(){

		$idPersonal = $this->request->query('id');
		$personal = Modelo\ContratistaEmpleado::obtener($idPersonal);
		$ubicacion_id = $this->request->post('ubicacion_id');
		$contratistaUbicacion =  Modelo\ContratistaUbicacion::obtener($ubicacion_id);

		if (!empty($personal->id) && !empty($contratistaUbicacion->id)) {

			$contratistaUbicacion->acceso_inicio = !empty($temp = $this->request->post('fecha_acceso_inicio')) ? \DateTime::createFromFormat('d/m/Y', $temp)->format('Y-m-d H:i:s') : null;
			$contratistaUbicacion->acceso_fin = !empty($temp = $this->request->post('fecha_acceso_fin')) ? \DateTime::createFromFormat('d/m/Y', $temp)->format('Y-m-d H:i:s') : null;

			if ($contratistaUbicacion->modificacion()) {
				$this->mensajeria->agregar("AVISO: La ubicación fué modificado de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
				$redirect = Vista::get_url("index.php/contratistaspersonal/ubicaciones/".$idPersonal);
				$this->redirect($redirect);				
			} else {
				$err	= $contratistaUbicacion->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}

		}else{
			$this->mensajeria->agregar("ERROR: No existe esa ubicación.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
			$redirect = Vista::get_url("index.php/contratistaspersonal/ubicaciones/".$idPersonal);
			$this->redirect($redirect);
		}

	}

	public function accion_ajax_contratistas_ubicaciones()
	{
		$dataTable_columns	= $this->request->query('columns');
		$orders	= [];
		foreach ($orden = (array)$this->request->query('order') as $i => $val) {
			$orders[]	= [
				'campo'	=> (!empty($tmp = $orden[$i]) && !empty($dataTable_columns) && is_array($dataTable_columns[0]))
					? $dataTable_columns[(int)$tmp['column']]['data']	:	'id',
				'dir'	=> !empty($tmp = $orden[$i]['dir'])
					? $tmp	:	'desc',
			];
		}
		$date  = [];
		if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $this->request->query('search')['value'], $date)) {
			$el_resto = \preg_replace('/^\d{2}\/\d{2}\/\d{4}/', '', $this->request->query('search')['value']);
			$search = \DateTime::createFromFormat('d/m/Y', $date[0])->format('Y-m-d') . $el_resto;
		} else {
			$search = $this->request->query('search')['value'];
		}
		$params	= [
			'order'		=> $orders,
			'start'		=> !empty($tmp = $this->request->query('start'))
				? $tmp : 0,
			'lenght'	=> !empty($tmp = $this->request->query('length'))
				? $tmp : 10,
			'search'	=> !empty($search)
				? $search : '',
			'filtros'   => [
				'idContratista'      => $this->request->query('idContratista'),
			],
		];

		$data =  Modelo\ContratistaUbicacion::listarContratistaUbicacion($params);
		$datos['draw']    = (int) $this->request->query('draw');
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	//_metodo_vista_tabla_base_
}
