<?php

namespace App\Controlador;

use App\Helper\Vista;
use App\Modelo;

class Personas extends Base
{

	protected function accion_index()
	{
		$personas = Modelo\Persona::listar();
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'personas')))->pre_render();
	}

	public function accion_ajax_personas()
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

		$data =  Modelo\Persona::listar_personas($params);
		$datos['draw']    = (int) $this->request->query('draw');
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	public function accion_alta()
	{
		$personas = Modelo\Persona::obtener($this->request->query('id'));
		$lista_generos	= \App\Modelo\Persona::$TIPO_GENEROS;
		$generos = [];
		foreach ($lista_generos as $value) {
			$generos[$value['id']] = $value['nombre'];
		}

		
		if ($this->request->post('personas') == 'alta') {
			$personas->documento = !empty($temp = $this->request->post('documento')) ?  $temp : null;
			$personas->nombre = !empty($temp = $this->request->post('nombre')) ?  $temp : null;
			$personas->apellido = !empty($temp = $this->request->post('apellido')) ?  $temp : null;
			$personas->genero = !empty($temp = $this->request->post('genero')) ?  $temp : 0;

			$personaDocumento = Modelo\Persona::obtenerPorDocumento($this->request->post('documento'));
			if (!empty($personaDocumento->documento)) {
				
				$personaDocumento->documento = !empty($temp = $this->request->post('documento')) ?  $temp : null;
				$personaDocumento->nombre = !empty($temp = $this->request->post('nombre')) ?  $temp : null;
				$personaDocumento->apellido = !empty($temp = $this->request->post('apellido')) ?  $temp : null;
				$personaDocumento->genero = !empty($temp = $this->request->post('genero')) ?  $temp : 0;
				if ($personas->validar()) {
					$res = $personaDocumento->modificacion();
					$this->mensajeria->agregar(
						"AVISO:El Registro fué modificado de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
						$redirect =Vista::get_url("index.php/personas/index");
						$this->redirect($redirect);	
				}else{
					$err	= $personas->errores;
					foreach ($err as $text) {
						$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					}
				}

			}else{

				if ($personas->validar()) {
					$personas->alta();
					$this->mensajeria->agregar(
						"AVISO:El Registro fué ingresado de forma exitosa.",
						\FMT\Mensajeria::TIPO_AVISO,
						$this->clase
					);
					$redirect = Vista::get_url("index.php/personas/index");
					$this->redirect($redirect);
				} else {
					$err	= $personas->errores;
					foreach ($err as $text) {
						$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					}
				}

			}
			
		}


		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'personas', 'generos','personaDocumento')))->pre_render();
	}

	public function accion_modificacion(){
		$personas = Modelo\Persona::obtener($this->request->query('id'));
		$lista_generos	= \App\Modelo\Persona::$TIPO_GENEROS;
		$generos = [];
		foreach ($lista_generos as $value) {
			$generos[$value['id']] = $value['nombre'];
		}

		
		if ($this->request->post('personas') == 'modificacion') {
			$personas->documento = !empty($temp = $this->request->post('documento')) ?  $temp : null;
			$personas->nombre = !empty($temp = $this->request->post('nombre')) ?  $temp : null;
			$personas->apellido = !empty($temp = $this->request->post('apellido')) ?  $temp : null;
			$personas->genero = !empty($temp = $this->request->post('genero')) ?  $temp : 0;

			$personaDocumento = Modelo\Persona::obtenerPorDocumento($this->request->post('documento'));
			if (!empty($personaDocumento->documento)) {
				
				$personaDocumento->documento = !empty($temp = $this->request->post('documento')) ?  $temp : null;
				$personaDocumento->nombre = !empty($temp = $this->request->post('nombre')) ?  $temp : null;
				$personaDocumento->apellido = !empty($temp = $this->request->post('apellido')) ?  $temp : null;
				$personaDocumento->genero = !empty($temp = $this->request->post('genero')) ?  $temp : 0;
				if ($personas->validar()) {
					$res = $personaDocumento->modificacion();
					$this->mensajeria->agregar(
						"AVISO:El Registro fué modificado de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
						$redirect =Vista::get_url("index.php/personas/index");
						$this->redirect($redirect);	
				}else{
					$err	= $personas->errores;
					foreach ($err as $text) {
						$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					}
				}

			}else{

				if ($personas->validar()) {
					$personas->alta();
					$this->mensajeria->agregar(
						"AVISO:El Registro fué ingresado de forma exitosa.",
						\FMT\Mensajeria::TIPO_AVISO,
						$this->clase
					);
					$redirect = Vista::get_url("index.php/personas/index");
					$this->redirect($redirect);
				} else {
					$err	= $personas->errores;
					foreach ($err as $text) {
						$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					}
				}

			}		
		}


		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'personas', 'generos','personaDocumento')))->pre_render();
   }
	   

	public function accion_buscarPersonaAjax()
	{
		if (isset($_POST['documento'])) {
			$persona = Modelo\Persona::obtenerPorDocumento($this->request->post('documento'));

			$data	= $persona;
			(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
		} else {
			echo 'Error variable POST';
		}
	}

	//_metodo_vista_tabla_base_
}
