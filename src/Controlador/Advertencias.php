<?php

namespace App\Controlador;

use App\Helper\Vista;
use App\Modelo;
use App\Modelo\Persona;
use App\Modelo\Empleado;

class Advertencias extends Base
{

	protected function accion_index()
	{
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista')))->pre_render();
	}

	public function accion_ajax_advertencias()
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

		$data =  Modelo\Advertencia::listar_advertencias($params);
		$datos['draw']    = (int) $this->request->query('draw');
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	public function accion_alta()
	{
		$ubicaciones_select =  Modelo\Ubicacion::listar();
		$ubicaciones = [];
		foreach ($ubicaciones_select as $value) {
			$ubicaciones[$value['id']] = $value['nombre'];
		}

		$advertencias_select = Modelo\AdvertenciaGenerica::listar();
		$advertencias = [];
		foreach ($advertencias_select as $value) {
			$advertencias[$value['id']] = $value['texto'];
		}

		$advertencia = Modelo\Advertencia::obtener((int)$this->request->post('id'));
		if ($this->request->post('advertencia') == 'alta') {

			//$persona = Modelo\Persona::obtenerPorDocumento((int)$this->request->post('persona_documento'));
			$persona = Persona::obtener();
			$persona->documento = $this->request->post('persona_documento');
			$persona->nombre    = $this->request->post('persona_nombre');
			$persona->apellido  = $this->request->post('persona_apellido');
			$ubicacion = Modelo\Ubicacion::obtener((int)$this->request->post('ubicacion'));
			$dni_solicitante = $this->request->post('solicitante_documento');
			$solicitante = Modelo\Empleado::obtenerPorDocumento((int)$this->request->post('solicitante_documento'));
			$generico = Modelo\AdvertenciaGenerica::obtener((int)$this->request->post('advertencia_generica'));
			$msj = !empty($temp = $this->request->post('msj')) ?  $temp : null;
			$solicitante = Empleado::obtenerPorDocumento($dni_solicitante);
			$advertencia->persona = Persona::obtenerOAlta($persona);
			$advertencia->ubicacion = !empty($temp = $ubicacion) ?  $temp : null;
			$advertencia->texto = !empty($temp = $generico->texto) ?  $temp . ' ' . $msj : $msj;
			if ($solicitante->id_tipo_contrato!= Empleado::SIN_CONTRATO){
				$advertencia->solicitante = $solicitante;
				if ($advertencia->validar()) {
					$advertencia->alta();
					$this->mensajeria->agregar("AVISO:El Registro fué ingresado de forma exitosa.", \FMT\Mensajeria::TIPO_AVISO, $this->clase);
					$redirect = Vista::get_url("index.php/advertencias/index");
					$this->redirect($redirect);
				} else {
					$err	= $advertencia->errores;
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

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'ubicaciones', 'advertencias')))->pre_render();
	}

	public function accion_modificacion()
	{
		$ubicaciones_select =  Modelo\Ubicacion::listar();
		$ubicaciones = [];
		foreach ($ubicaciones_select as $value) {
			$ubicaciones[$value['id']] = $value['nombre'];
		}

		$advertencias_select = Modelo\AdvertenciaGenerica::listar();
		$advertencias = [];
		foreach ($advertencias_select as $value) {
			$advertencias[$value['id']] = $value['texto'];
		}

		$advertencia = Modelo\Advertencia::obtener($this->request->query('id'));
		$persona = Modelo\Persona::obtener($advertencia->persona->id);		
		$ubicacion = Modelo\Ubicacion::obtener($advertencia->ubicacion->id);		
		$solicitante = Modelo\Persona::obtener($advertencia->solicitante->persona_id);	
		$msj = $advertencia->texto;
		
		if ($this->request->post('advertencia') == 'modificacion') {
			$dni_solicitante = $this->request->post('solicitante_documento');
			$solicitante = Modelo\Empleado::obtenerPorDocumento((int)$this->request->post('solicitante_documento'));
			$persona = Modelo\Persona::obtenerPorDocumento((int)$this->request->post('persona_documento'));
			$ubicacion = Modelo\Ubicacion::obtener((int)$this->request->post('ubicacion'));
			//$solicitante = Modelo\Empleado::obtenerPorDocumento((int)$this->request->post('solicitante_documento'));
			$generico = Modelo\AdvertenciaGenerica::obtener((int)$this->request->post('advertencia_generica'));
			$msj = !empty($temp = $this->request->post('msj')) ?  $temp : null;

			$advertencia->persona = !empty($temp = $persona) ?  $temp : null;
			$advertencia->ubicacion = !empty($temp = $ubicacion) ?  $temp : null;
			//$advertencia->solicitante = !empty($temp = $solicitante) ?  $temp : null;
			$advertencia->texto = !empty($temp = $generico->texto) ?  $temp : $msj;
			if ($solicitante->id_tipo_contrato!= Empleado::SIN_CONTRATO){
				$advertencia->solicitante = $solicitante;
				if ($advertencia->validar()) {
					$advertencia->modificacion();
					$this->mensajeria->agregar("AVISO:El Registro fué modifficado de forma exitosa.", \FMT\Mensajeria::TIPO_AVISO, $this->clase);
					$redirect = Vista::get_url("index.php/advertencias/index");
					$this->redirect($redirect);
				} else {
					$err	= $advertencia->errores;
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

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'ubicaciones', 'advertencias', 'advertencia', 'persona', 'ubicacion', 'solicitante', 'msj')))->pre_render();
	}

	public function accion_baja()
	{
		$advertencia = Modelo\Advertencia::obtener($this->request->query('id'));
		if ($advertencia->id) {
			if ($this->request->post('confirmar')) {
				$subtitulo = 'Advertencia';
				$texto = "Dará de baja la advertencia, </strong>";
				$res = $advertencia->baja();
				if ($res) {
					$this->mensajeria->agregar('AVISO: Se eliminó un advertencias de forma exitosa.', \FMT\Mensajeria::TIPO_AVISO, $this->clase, 'index');
					$redirect = Vista::get_url('index.php/advertencias/index/');
					$this->redirect($redirect);
				}
			}
		} else {
			$redirect = Vista::get_url('index.php/advertencias/index/');
			$this->redirect($redirect);
		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('advertencia', 'vista')))->pre_render();

	}

	public function accion_buscarPersonalAjax()
	{
		if (isset($_POST['persona_documento'])) {
			$documento = $_POST['persona_documento'];
			if (!empty($documento) && is_numeric($documento) && mb_strlen($documento, 'UTF-8') >= 6) {
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

	public function accion_buscarSolicitanteAjax()
	{

		if (isset($_POST['solicitante_documento'])) {
			$empleado =  Modelo\Empleado::obtenerPorDocumento($this->request->post('solicitante_documento'));
			if ($this->request->post('solicitante_documento') != '') {
				if (is_null($empleado->id)) {
					$data = [
						'dato' => null,
						'msj' => 'No hay un registro de Empleado con Documento: ' . $this->request->post('solicitante_documento')
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
