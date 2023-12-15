<?php

namespace App\Controlador;

use App\Helper\Vista;
use App\Modelo;
use App\Modelo\Pertenencia;
use App\Modelo\Ubicacion;
use App\Modelo\Persona;
use App\Modelo\Empleado;



class Pertenencias extends Base
{

	protected function accion_index()
	{
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista')))->pre_render();
	}

	public function accion_ajax_pertenencias()
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
			'filtros'   => [],
		];

		$data =  Modelo\Pertenencia::listar_pertenencias($params);
		$datos['draw']    = (int) $this->request->query('draw');
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	public function accion_alta()
	{
		$pertenencia = Pertenencia::obtener($this->request->query('id'));

		if ($this->request->post('operacion') == "alta") {
			$dni_solicitante = $this->request->post('solicitante_documento');
			$pertenencia->texto =  $this->request->post('texto');
			$persona = Persona::obtener();
			$persona->documento = $this->request->post('persona_documento');
			$persona->nombre    = $this->request->post('persona_nombre');
			$persona->apellido  = $this->request->post('persona_apellido');
			$pertenencia->persona = Persona::obtenerOAlta($persona);
			$pertenencia->ubicacion = Ubicacion::obtener($this->request->post('ubicacion_id'));
			$pertenencia->ubicacion_id = $pertenencia->ubicacion->id;
			$pertenencia->texto =  $this->request->post('texto');
			$empleado_solicitante = Empleado::obtenerPorDocumento($dni_solicitante);
			if ($empleado_solicitante->id_tipo_contrato!= Empleado::SIN_CONTRATO){
				$pertenencia->solicitante=$empleado_solicitante;
				if ($pertenencia->validar()) {
					if ($pertenencia->alta()) {
						$this->mensajeria->agregar('AVISO: Se dió de alta de forma exitosa una nueva pertenencia.',\FMT\Mensajeria::TIPO_AVISO,$this->clase,'index');
						$redirect = Vista::get_url('index.php/pertenencias/index');
						$this->redirect($redirect);
					} else {
						$this->mensajeria->agregar('ERROR: Hubo un error en el alta.',\FMT\Mensajeria::TIPO_ERROR,$this->clase,'index');
						$redirect = Vista::get_url('index.php/pertenencias/index');
						$this->redirect($redirect);
					}
				}else{
					$err	= $pertenencia->errores;
					foreach ($err as $text) {
						$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					}
				}	
			}else{
				$this->mensajeria->agregar(
					"ERROR: Se requiere solicitante con contrato activo.",
					\FMT\Mensajeria::TIPO_ERROR,
					$this->clase
				);
			}
		}
		$ubicaciones_select =  Modelo\Ubicacion::listar();
		$ubicaciones = [];
		foreach ($ubicaciones_select as $value) {
			$ubicaciones[$value['id']] = $value['nombre'];
		}
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'pertenencia','ubicaciones')))->pre_render();
	}

	public function accion_modificacion()
	{
		$pertenencia = Pertenencia::obtener($this->request->query('id'));
		
		if ($this->request->post('operacion') == "modificacion") {
			$dni_solicitante = $this->request->post('solicitante_documento');
			
			$empleado_solicitante = Empleado::obtenerPorDocumento($dni_solicitante);
			if ($empleado_solicitante->id_tipo_contrato!= Empleado::SIN_CONTRATO){
				$pertenencia->solicitante=$empleado_solicitante;
				$persona = Persona::obtener();
				$persona->documento = $this->request->post('persona_documento');
				$persona->nombre    = $this->request->post('persona_nombre');
				$persona->apellido  = $this->request->post('persona_apellido');
				$pertenencia->persona = Persona::obtenerOAlta($persona);
				$pertenencia->ubicacion = Ubicacion::obtener($this->request->post('ubicacion_id'));
				$pertenencia->ubicacion_id = $pertenencia->ubicacion->id;
				$pertenencia->texto =  $this->request->post('texto');
				if ($pertenencia->validar()) {
					if ($pertenencia->modificacion()) {
						$this->mensajeria->agregar('AVISO: Se modifico de forma exitosa una  pertenencia.',\FMT\Mensajeria::TIPO_AVISO,$this->clase,'index');
						$redirect = Vista::get_url('index.php/pertenencias/index');
						$this->redirect($redirect);
					} else {
						$this->mensajeria->agregar('ERROR: Hubo un error en la modificacion.',\FMT\Mensajeria::TIPO_ERROR,$this->clase,'index');
						$redirect = Vista::get_url('index.php/pertenencias/index');
						$this->redirect($redirect);
					}
				}else{
					$err	= $pertenencia->errores;
					foreach ($err as $text) {
						$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					}
				}	
			}else{
				$this->mensajeria->agregar(
					"ERROR: Se requiere solicitante con contrato activo.",
					\FMT\Mensajeria::TIPO_ERROR,
					$this->clase
				);
			}
		}
		$ubicaciones_select =  Modelo\Ubicacion::listar();
		$ubicaciones = [];
		foreach ($ubicaciones_select as $value) {
			$ubicaciones[$value['id']] = $value['nombre'];
		}
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'pertenencia','ubicaciones')))->pre_render();
	}

	public function accion_baja()
	{
		$pertenencia = Pertenencia::obtener($this->request->query('id'));

		if (!$pertenencia->id) {
			$this->mensajeria->agregar(
				"ERROR: La pertenencia no fue encontrada.",
				\FMT\Mensajeria::TIPO_ERROR,
				$this->clase
			);
			$redirect = Vista::get_url("index.php/Pertenencias/index");
			$this->redirect($redirect);
		}

		if ($this->request->post('confirmar') == 1) {
			$save = $pertenencia->baja();
			if ($save) {
				$this->mensajeria->agregar(
					"AVISO:La pertenencia ha sido dado de baja de forma exitosa.",
					\FMT\Mensajeria::TIPO_AVISO,
					$this->clase
				);
			} else {
				$this->mensajeria->agregar(
					"ERROR: La pertenencia no fue borrada.",
					\FMT\Mensajeria::TIPO_ERROR,
					$this->clase
				);
			}
			$redirect = Vista::get_url("index.php/Pertenencias/index");
			$this->redirect($redirect);
		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'pertenencia')))->pre_render();
	}

	public function accion_buscar_documento() {
		
		
		if (isset($_POST['documento'])) {
			$persona =  Modelo\Persona::obtenerPorDocumento($this->request->post('documento'));
			if ($this->request->post('documento') != '') {
				if (is_null($persona->id)) {
					$data = [
						'dato' => null,
						'msj' => 'No hay un registro de persona con Documento: ' . $this->request->post('documento')
					];
				} else {
					$data = [
						'dato' => $persona,
						'msj' => "Se encontró la persona " . "{$persona->nombre} {$persona->apellido} - " . "Documento: {$persona->documento}",
					];
				}
			} else {
				$data = [
					'dato' => null,
					'msj' => 'El Documento de la persona es necesario para realizar la búsqueda'
				];
			}
			(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
		} else {
			echo 'Error variable POST';
		}
	}

	public function accion_buscar_documento_solicitante() {
		
		
		if (isset($_POST['documento'])) {
			$empleado =  Modelo\Empleado::obtenerPorDocumento($this->request->post('documento'));
			if ($this->request->post('documento') != '') {
				if (is_null($empleado->id)) {
					$data = [
						'dato' => null,
						'msj' => 'No hay un registro de Empleado con Documento: ' . $this->request->post('documento')
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
					'msj' => 'El Documento del Empleado solicitante es necesario para realizar la búsqueda'
				];
			}
			(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
		} else {
			echo 'Error variable POST';
		}
	}

	//_metodo_vista_tabla_base_
}
