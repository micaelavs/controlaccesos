<?php

namespace App\Controlador;

use App\Helper\Vista;
use App\Modelo;

class Advertenciasgenericas extends Base
{

	public function accion_index()
	{
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista')))->pre_render();
	}

	public function accion_ajax_advertenciasgenericas()
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
			'search'	=> !empty($search) ? $search : '',
			'filtros'   => [],
		];

		$data =  Modelo\AdvertenciaGenerica::listar_advertencias_genericas($params);
		$datos['draw']    = (int) $this->request->query('draw');
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	public function accion_alta()
	{
		$advertencia = Modelo\AdvertenciaGenerica::obtener((int)$this->request->post('id'));
		if ($this->request->post('advertencia') == 'alta') {

			$advertencia->texto = !empty($temp = $this->request->post('texto')) ?  $temp : null;

			if ($advertencia->validar()) {
				$advertencia->alta();
				$this->mensajeria->agregar("AVISO:El Registro fué ingresado de forma exitosa.", \FMT\Mensajeria::TIPO_AVISO, $this->clase);
				$redirect = Vista::get_url("index.php/advertenciasgenericas/index");
				$this->redirect($redirect);
			} else {
				$err	= $advertencia->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}
		}
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista')))->pre_render();
	}

	public function accion_modificacion()
	{
		$advertencia = Modelo\AdvertenciaGenerica::obtener((int)$this->request->query('id'));
		if ($this->request->post('advertencia') == 'modificacion') {

			$advertencia->texto = !empty($temp = $this->request->post('texto')) ?  $temp : null;

			if ($advertencia->validar()) {
				$advertencia->modificacion();
				$this->mensajeria->agregar("AVISO:El Registro fué modificado de forma exitosa.", \FMT\Mensajeria::TIPO_AVISO, $this->clase);
				$redirect = Vista::get_url("index.php/advertenciasgenericas/index");
				$this->redirect($redirect);
			} else {
				$err	= $advertencia->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}
		}
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','advertencia')))->pre_render();
	}

	public function accion_baja()
	{
		$advertencia = Modelo\AdvertenciaGenerica::obtener($this->request->query('id'));
		if ($advertencia->id) {
			if ($this->request->post('confirmar')) {
				$subtitulo = 'Advertencia generica';
				$texto = "Dará de baja la advertencia generica, </strong>";
				$res = $advertencia->baja();
				if ($res) {
					$this->mensajeria->agregar('AVISO: Se eliminó una advertencia generica de forma exitosa.', \FMT\Mensajeria::TIPO_AVISO, $this->clase, 'index');
					$redirect = Vista::get_url('index.php/advertenciasgenericas/index/');
					$this->redirect($redirect);
				}
			}
		} else {
			$redirect = Vista::get_url('index.php/advertenciasgenericas/index/');
			$this->redirect($redirect);
		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('advertencia', 'vista')))->pre_render();

	}

	//_metodo_vista_tabla_base_
}
