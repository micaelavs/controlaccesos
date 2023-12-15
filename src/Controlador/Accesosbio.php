<?php

namespace App\Controlador;

use App\Helper\Msj;
use App\Helper\Vista;
use App\Modelo\AccesoBio;
use App\Modelo;


class Accesosbio extends Base
{

	public function accion_index()
	{
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista')))->pre_render();
	}

	public function accion_sincronizar()
	{		
		$respuesta = AccesoBio::sincronizar();
		if ($respuesta["estado"]) {
			$this->mensajeria->agregar(
				$respuesta["info"],
				\FMT\Mensajeria::TIPO_AVISO,
				$this->clase
			);
			$redirect = Vista::get_url("index.php/accesosbio/index");
			$this->redirect($redirect);
		} else {
			$this->mensajeria->agregar(
				$respuesta["info"],
				\FMT\Mensajeria::TIPO_ERROR,
				$this->clase
			);
			$redirect = Vista::get_url("index.php/accesosbio/index");
			$this->redirect($redirect);
		}
	}

	public function accion_ajax_accesosbio()
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

		$data =  Modelo\AccesoBio::listar_accesosbio($params);
		$datos['draw']    = (int) $this->request->query('draw');
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}
}
