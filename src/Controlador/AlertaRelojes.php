<?php

namespace App\Controlador;

use App\Helper\Vista;
use App\Modelo;
use App\Modelo\AlertaReloj;
use App\Modelo\Empleado;

class AlertaRelojes extends Base
{

	protected function accion_index()
	{
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista')))->pre_render();
	}

	public function accion_ajax()
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

		$data =  Modelo\AlertaReloj::listar_alertas($params);
		$datos['draw']    = (int) $this->request->query('draw');
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	public function accion_alta()
	{
		$alertaReloj = Modelo\AlertaReloj::obtener(null);
		
		if ($this->request->post('operacion') == 'alta') {
			$alertaReloj->empleado = Empleado::obtenerPorEmail($this->request->post('email'));
			if ($alertaReloj->validar()) {
				$alertaReloj->alta();
				$this->mensajeria->agregar("AVISO:El Registro fué ingresado de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
				$redirect = Vista::get_url("index.php/AlertaRelojes/index");
				$this->redirect($redirect);
			} else {
				$err	= $alertaReloj->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}

		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'alertaReloj')))->pre_render();
	}

	
    public function accion_baja(){
		$alertaReloj = AlertaReloj::obtener($this->request->query('id'));

		if(!$alertaReloj->id){
			$this->mensajeria->agregar("ERROR: El Alerta Reloj no fue encontrado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
            $redirect =Vista::get_url("index.php/AlertaRelojes/index");
            $this->redirect($redirect);	
		}
        
        if(!$alertaReloj->empleado->id){
            $save = $alertaReloj->baja();
			$this->mensajeria->agregar("ERROR: Se detectó que el Empleado no está activo dentro del sistema y lo dimos de baja.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
            $redirect =Vista::get_url("index.php/AlertaRelojes/index");
            $this->redirect($redirect);	
		}

		if($this->request->post('confirmar') == 1) {
            $save = $alertaReloj->baja();
            if($save){
                $this->mensajeria->agregar("AVISO: Alerta Reloj dada de baja de forma exitosa.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
            }else{
                $this->mensajeria->agregar("ERROR: La Alerta Reloj no fue borrada.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
            }
            $redirect =Vista::get_url("index.php/AlertaRelojes/index");
            $this->redirect($redirect);	
        }

		$vista = $this->vista;
		(new Vista($this->vista_default,compact('vista', 'alertaReloj')))->pre_render();
	}


    public function accion_buscar_user() {
			
		$data = Empleado::obtenerPorEmail($this->request->post('email'));
	    (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

}
