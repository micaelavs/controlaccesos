<?php

namespace App\Controlador;

use App\Helper\Vista;
use App\Modelo;
use App\Modelo\SituacionRevista;
use App\Modelo\Contratista;
use App\Modelo\ContratistaEmpleado;

class Contratistas extends Base
{

	protected function accion_index()
	{
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista')))->pre_render();
	}

	protected function accion_alta()
	{
		$lista_provincias	= \App\Modelo\Provincia::listar_select();
		$provincias = [];
		foreach ($lista_provincias as $value) {
			$provincias[$value['id']] = $value['nombre'];
		}

		$lista_localidades	= \App\Modelo\Localidad::listar_select();
		$localidades = [];
		foreach ($lista_localidades as $value) {
			$localidades[$value['id']] = $value['nombre'];
		}

		$contratista = Modelo\Contratista::obtener((int)$this->request->post('id'));
		if ($this->request->post('contratista') == 'alta') {

			$contratista->nombre = !empty($temp = $this->request->post('nombre')) ?  $temp : null;
			$contratista->cuit = !empty($temp = $this->request->post('cuit')) ?  $temp : null;
			$contratista->direccion = !empty($temp = $this->request->post('direccion')) ?  $temp : null;
			$contratista->provincia_id = !empty($temp = $this->request->post('provincia')) ?  $temp : null;
			$contratista->localidad_id = !empty($temp = $this->request->post('localidad')) ?  $temp : 0;

			if ($contratista->validar()) {
				$contratista->alta();
				$this->mensajeria->agregar(
					"AVISO:El Registro fué ingresado de forma exitosa.",
					\FMT\Mensajeria::TIPO_AVISO,
					$this->clase
				);
				$redirect = Vista::get_url("index.php/contratistas/index");
				$this->redirect($redirect);
			}else {
				$err	= $contratista->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}
		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'provincias','localidades', 'contratista')))->pre_render();
	}

	protected function accion_modificacion()
	{
		$data_contratista = $this->recuperar_info();

		$contratista = Modelo\Contratista::obtener($this->request->query('id'));

		if ($this->request->post('contratista') == 'modificacion') {
			$contratista = Modelo\Contratista::obtener((int)$this->request->post('cuit'));
			$contratista->nombre = !empty($temp = $this->request->post('nombre')) ?  $temp : null;
			$contratista->cuit = !empty($temp = $this->request->post('cuit')) ?  $temp : null;
			$contratista->direccion = !empty($temp = $this->request->post('direccion')) ?  $temp : null;
			$contratista->provincia_id = !empty($temp = $this->request->post('provincia')) ?  $temp : null;
			$contratista->localidad_id = !empty($temp = $this->request->post('localidad')) ?  $temp : 0;

			if ($contratista->validar()) {
				$contratista->modificacion();
				$this->mensajeria->agregar(
					"AVISO:El Registro fué ingresado de forma exitosa.",
					\FMT\Mensajeria::TIPO_AVISO,
					$this->clase
				);
				$redirect = Vista::get_url("index.php/contratistas/index");
				$this->redirect($redirect);
			}else {
				$err	= $contratista->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}
		}

		$lista_provincias	= \App\Modelo\Provincia::listar_select();
		$provincias = [];
		foreach ($lista_provincias as $value) {
			$provincias[$value['id']] = $value['nombre'];
		}

		$lista_localidades	= \App\Modelo\Localidad::listar_select($contratista->provincia_id);
		$localidades = [];
		foreach ($lista_localidades as $value) {
			$localidades[$value['id']] = $value['nombre'];
		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'provincias', 'localidades','data_contratista','contratista')))->pre_render();
	}

	protected function accion_baja()
	{
		$contratista = Contratista::obtener($this->request->query('id'));		

		if ($contratista->id) {
			if ($this->request->post('confirmar')) {
				$subtitulo = 'Empleado';
				$texto = "Dará de baja al permiso, </strong>";				
				$res = $contratista->baja();
				if ($res) {

					ContratistaEmpleado::baja_contratista_personal($contratista->id);
					$this->mensajeria->agregar('AVISO: Se eliminó un contratista de forma exitosa.', \FMT\Mensajeria::TIPO_AVISO, $this->clase, 'index');
					$redirect = Vista::get_url('index.php/contratistas/index');
					$this->redirect($redirect);
				}
			}			
		} else {
			$redirect = Vista::get_url('index.php/contratistas/index');
			$this->redirect($redirect);
		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('contratista','vista')))->pre_render();
	}	

	public function accion_ajax_contratistas()
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

		$data =  Modelo\Contratista::listar_contratistas($params);
		$datos['draw']    = (int) $this->request->query('draw');
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	public function accion_buscar_contratista()
	{	
		$cuit = str_replace("-","",$this->request->post('cuit'));
		$data = Contratista::obtener($cuit);
	    (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}


	public function accion_ajax_localidades(){

		$lista_localidades	= \App\Modelo\Localidad::listar_select($this->request->query('provincia_id'));
		$localidades = [];
		foreach ($lista_localidades as $value) {
			$localidades[$value['id']] = $value['nombre'];
		}

		$data = $localidades;
	    (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();

	}
}
