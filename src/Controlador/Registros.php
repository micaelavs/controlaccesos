<?php

namespace App\Controlador;

use App\Helper\Vista;
use App\Modelo\Acceso;
use App\Modelo\ContratistaEmpleado;
use App\Modelo\Credencial;
use App\Modelo\Empleado;
use App\Modelo\Persona;
use App\Modelo\Registro;
use App\Modelo\Ubicacion;
use App\Modelo\Usuario;
use App\Modelo\Visita;
use DateTime;

class Registros extends Base
{

	protected function accion_index()
	{
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista')))->pre_render();
	}

	protected function accion_carga_individual()
	{
        $registro = Registro::obtener(null);
        $ubicaciones = $this->lista_select_ubicacion(Ubicacion::listar());

		if ($this->request->post('operacion') == "alta") {
            $registro::$tipo_validacion = Registro::CARGA_INDIVIDUAL;
            $registro->ubicacion = Ubicacion::obtener($this->request->post('ubicacion_id'));
            $registro->empleado = Empleado::obtenerPorDocumento($this->request->post('documento'));
            $registro->fecha = !empty($temp = $this->request->post('fecha')) ?  DateTime::createFromFormat('d/m/Y', $temp) : null;
            $registro->hora_ingreso = !empty($temp = $this->request->post('hora_ingreso')) ? DateTime::createFromFormat('H:i', $temp) : null;
            $registro->hora_egreso = !empty($temp = $this->request->post('hora_egreso')) ? DateTime::createFromFormat('H:i', $temp) : null;

            $registro->observaciones = !empty($temp = $this->request->post('observaciones')) ? $temp : null;
            $registro->usuario = Usuario::obtenerUsuarioLogueado();

			if(!empty($registro->fecha)){
				$registro->hora_ingreso->setDate($registro->fecha->format('Y'), $registro->fecha->format('m'), $registro->fecha->format('d'));
				$registro->hora_egreso->setDate($registro->fecha->format('Y'), $registro->fecha->format('m'), $registro->fecha->format('d'));
				$registro->hora_ingreso = $registro->hora_ingreso->format('Y-m-d H:i:s');
				$registro->hora_egreso = $registro->hora_egreso->format('Y-m-d H:i:s');
			}

            if($registro->validar()){
				$res = $registro->carga_individual();
				if($res === true){
					$this->mensajeria->agregar(
						"AVISO: El Registro fué ingresado de forma exitosa.",
							\FMT\Mensajeria::TIPO_AVISO,
							$this->clase
						);
						$redirect = Vista::get_url("index.php/Registros/carga_individual");
						$this->redirect($redirect);
				}else{
					$err	= $registro->errores;
					foreach ($err as $text) {
						$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					}
				}
            } else {
				$err	= $registro->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}

		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','ubicaciones','registro')))->pre_render();
	}

	protected function accion_carga_individual_contratista()
	{

		$registro = Registro::obtener(null);
        $ubicaciones = $this->lista_select_ubicacion(Ubicacion::listar());

		if ($this->request->post('operacion') == "alta") {
            $registro::$tipo_validacion = Registro::CARGA_CONTRATISTA;
            $registro->ubicacion = Ubicacion::obtener($this->request->post('ubicacion_id'));
            $registro->empleado = ContratistaEmpleado::obtenerPorDocumento($this->request->post('documento'));
            $registro->fecha = !empty($temp = $this->request->post('fecha')) ?  DateTime::createFromFormat('d/m/Y', $temp) : null;
            $registro->hora_ingreso = !empty($temp = $this->request->post('hora_ingreso')) ? DateTime::createFromFormat('H:i', $temp) : null;
            $registro->hora_egreso = !empty($temp = $this->request->post('hora_egreso')) ? DateTime::createFromFormat('H:i', $temp) : null;
            $registro->credencial = Credencial::obtenerPorCodigo($this->request->post('credencial'),$registro->ubicacion);
            $registro->observaciones = !empty($temp = $this->request->post('observaciones')) ? $temp : null;
            $registro->usuario = Usuario::obtenerUsuarioLogueado();
			
			if(!empty($registro->fecha)){
				$registro->hora_ingreso->setDate($registro->fecha->format('Y'), $registro->fecha->format('m'), $registro->fecha->format('d'));
				$registro->hora_egreso->setDate($registro->fecha->format('Y'), $registro->fecha->format('m'), $registro->fecha->format('d'));
				$registro->hora_ingreso = $registro->hora_ingreso->format('Y-m-d H:i:s');
				$registro->hora_egreso = $registro->hora_egreso->format('Y-m-d H:i:s');
			}

            if($registro->validar()){
				$res = $registro->carga_individual_contratista();
				if($res === true){
					$this->mensajeria->agregar(
						"AVISO: El Registro fué ingresado de forma exitosa.",
							\FMT\Mensajeria::TIPO_AVISO,
							$this->clase
						);
						$redirect = Vista::get_url("index.php/Registros/carga_individual");
						$this->redirect($redirect);
				}else{
					$err	= $registro->errores;
					foreach ($err as $text) {
						$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					}
				}
            } else {
				$err	= $registro->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}

		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','ubicaciones','registro')))->pre_render();
	}

	protected function accion_carga_individual_visita()
	{

		$registro = Registro::obtener(null);
        $ubicaciones = $this->lista_select_ubicacion(Ubicacion::listar());

		if ($this->request->post('operacion') == "alta") {
            $registro::$tipo_validacion = Registro::CARGA_VISITA;
            $registro->ubicacion = Ubicacion::obtener($this->request->post('ubicacion_id'));
            $registro->autorizante = Empleado::obtenerPorDocumento($this->request->post('documento_autorizante'));
            $registro->visita = Visita::obtenerPorDocumento($this->request->post('documento_visita'));
            $registro->fecha = !empty($temp = $this->request->post('fecha')) ?  DateTime::createFromFormat('d/m/Y', $temp) : null;
            $registro->hora_ingreso = !empty($temp = $this->request->post('hora_ingreso')) ? DateTime::createFromFormat('H:i', $temp) : null;
            $registro->hora_egreso = !empty($temp = $this->request->post('hora_egreso')) ? DateTime::createFromFormat('H:i', $temp) : null;
            $registro->credencial = Credencial::obtenerPorCodigo($this->request->post('credencial'),$registro->ubicacion);
            $registro->observaciones = !empty($temp = $this->request->post('observaciones')) ? $temp : null;
            $registro->origen = !empty($temp = $this->request->post('origen')) ? $temp : null;
            $registro->destino = !empty($temp = $this->request->post('destino')) ? $temp : null;
            $registro->usuario = Usuario::obtenerUsuarioLogueado();

			if($registro->visita->id == 0){
				$persona  = Persona::obtenerPorDocumento($this->request->post('documento_visita'));
				if(!$persona->id){
					$persona->nombre = !empty($temp = $this->request->post('nombre')) ? $temp : null;
					$persona->apellido = !empty($temp = $this->request->post('apellido')) ? $temp : null;
					$persona->documento = !empty($temp = $this->request->post('documento_visita')) ? $temp : null;
					$persona->genero = !empty($temp = $this->request->post('genero')) ? $temp : 0;
					if($persona->validar()){
						$persona->id = $persona->alta();
					}
				}
				$registro->visita->persona =  $persona;
				$registro->visita->ubicacion =  $registro->ubicacion;
				$registro->visita->autorizante =  $registro->autorizante;
				$registro->visita->aclaracion_autorizacion =  $registro->destino;
				$registro->visita->fecha_desde =  $registro->fecha;
				$registro->visita->fecha_hasta =  $registro->fecha;
				if($registro->visita->validar()){
					$registro->visita->alta();
				}
			}

			if(!empty($registro->fecha)){
				$registro->hora_ingreso->setDate($registro->fecha->format('Y'), $registro->fecha->format('m'), $registro->fecha->format('d'));
				$registro->hora_egreso->setDate($registro->fecha->format('Y'), $registro->fecha->format('m'), $registro->fecha->format('d'));
				$registro->hora_ingreso = $registro->hora_ingreso->format('Y-m-d H:i:s');
				$registro->hora_egreso = $registro->hora_egreso->format('Y-m-d H:i:s');
			}
            if($registro->validar()){
				$res = $registro->carga_individual_visita();
				if($res === true){
					$this->mensajeria->agregar(
						"AVISO: El Registro fué ingresado de forma exitosa.",
							\FMT\Mensajeria::TIPO_AVISO,
							$this->clase
						);
						$redirect = Vista::get_url("index.php/Registros/carga_individual_visita");
						$this->redirect($redirect);
				}else{
					$err	= $registro->errores;
					foreach ($err as $text) {
						$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					}
				}
            } else {
				$err	= $registro->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}

		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','ubicaciones','registro')))->pre_render();
    }

    protected function accion_accesos_sin_cierre()
	{
        $ubicaciones = $this->lista_select_ubicacion(Ubicacion::listar());
		$tipos = $this->lista_select_tipos_accesos();

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','ubicaciones','tipos')))->pre_render();
    }


	protected function accion_ajax_sin_cierre(){
        $dataTable_columns    = $this->request->query('columns');
        $orders    = [];
        foreach ($orden = (array)$this->request->query('order') as $i => $val) {
            $orders[]    = [
                'campo'    => (!empty($tmp = $orden[$i]) && !empty($dataTable_columns) && is_array($dataTable_columns[0]))
                    ? $dataTable_columns[(int)$tmp['column']]['data']    :    'id',
                'dir'    => !empty($tmp = $orden[$i]['dir'])
                ? $tmp    :    'desc',
            ];
        }
        $date  = [];
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $this->request->query('search')['value'], $date)) {
            $el_resto = \preg_replace('/^\d{2}\/\d{2}\/\d{4}/', '', $this->request->query('search')['value']);
            $search = \DateTime::createFromFormat('d/m/Y', $date[0])->format('Y-m-d') . $el_resto;
        } else {
            $search = $this->request->query('search')['value'];
        }
        $params    = [
            'order'        => $orders,
            'start'        => !empty($tmp = $this->request->query('start'))
            ? $tmp : 0,
            'lenght'    => !empty($tmp = $this->request->query('length'))
            ? $tmp : 10,
            'search'    => !empty($search)
                ? $search : '',
            'filtros'   => [
                'ubicacion'      => $this->request->query('ubicacion_filtro'),
                'tipo_acceso'    => $this->request->query('tipo_filtro'),
                'fecha_desde'    => $this->request->query('fecha_desde_filtro'),
            ],
        ];

		if(!empty($this->_user->dependencias)){
			$params['filtros']['dependencias_autorizadas'] = implode(",",$this->_user->dependencias);
			$data =  Registro::listar_accesos($params,true);
		}else{
			$data =  Registro::listar_accesos($params);
		}
        $datos['draw']    = (int) $this->request->query('draw');
        (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();


    }

	protected function accion_solicitar_cierre(){
		$acceso = Acceso::obtener($this->request->query('id'));
		if(empty($acceso->id)){
			$this->mensajeria->agregar(
			"AVISO: El acceso no existe.",
				\FMT\Mensajeria::TIPO_ERROR,
				$this->clase
			);
			$redirect = Vista::get_url("index.php/Registros/accesos_sin_cierre");
			$this->redirect($redirect);
		}else if(!empty($acceso->id) && !empty($acceso->egreso)){
			$this->mensajeria->agregar(
				"AVISO: El acceso ya tiene registrado un egreso.",
					\FMT\Mensajeria::TIPO_ERROR,
					$this->clase
				);
			$redirect = Vista::get_url("index.php/Registros/accesos_sin_cierre");
			$this->redirect($redirect);
		}

		if($this->request->post('operacion') == "baja" && !empty($acceso->id)){
			
			$fecha = ($temp = $this->request->post('fecha_egreso')) ? $temp : null;
			$hora = ($temp = $this->request->post('hora_egreso')) ? $temp : null;
			$dateTime = \DateTime::createFromFormat('d/m/Y H:i', "{$fecha} {$hora}");
			$acceso->egreso = $dateTime;
			$acceso->tipo_egreso = Acceso::TIPO_REGISTRO_OFFLINE;
			$acceso->persona_egreso = Persona::obtener($this->_user->getEmpleado()->persona_id);
			$acceso->observaciones = ($temp = $this->request->post('observaciones_editable')) ? $temp."\n-CERRADO MANUAL." : "\n-CERRADO MANUAL.";
			if($acceso->validar()){
				$res = $acceso->modificacion();
				if($res){
					$this->mensajeria->agregar(
						"AVISO: El Registro fué ingresado de forma exitosa.",
							\FMT\Mensajeria::TIPO_AVISO,
							$this->clase
						);
						$redirect = Vista::get_url("index.php/Registros/carga_individual_visita");
						$this->redirect($redirect);
				}
			}else {
				$err	= $acceso->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}
		}
		$empleado = $this->_user->getEmpleado();
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','acceso','empleado')))->pre_render();
		
	}

    // ============ FUNCIONES ADICIONALES ==================
    public function accion_buscar_empleado() {
			
		$data = Empleado::obtenerPorDocumento($this->request->post('documento'));
	    (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	public function accion_buscar_contratista() {
			
		$data = ContratistaEmpleado::obtenerPorDocumento($this->request->post('documento'));
	    (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

    public function accion_buscar_persona() {
			
		$data = Persona::obtenerPorDocumento($this->request->post('documento'));
	    (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	public function accion_buscar_visita() {
			
		$data = Visita::obtenerPorDocumento($this->request->post('documento'));
	    (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
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

	private function lista_select_tipos_accesos(){
		$listado_tipo[Acceso::EMPLEADO] = ['id' => Acceso::EMPLEADO, 'nombre' => Acceso::tipoAccesoToString(Acceso::EMPLEADO), 'borrado' => 0];
		$listado_tipo[Acceso::VISITANTE] =  ['id' => Acceso::VISITANTE, 'nombre' => Acceso::tipoAccesoToString(Acceso::VISITANTE), 'borrado' => 0];
		$listado_tipo[Acceso::CONTRATISTA] = 	['id' => Acceso::CONTRATISTA, 'nombre' => Acceso::tipoAccesoToString(Acceso::CONTRATISTA), 'borrado' => 0];
		$listado_tipo[Acceso::VISITA_ENROLADA] = ['id' => Acceso::VISITA_ENROLADA, 'nombre' => Acceso::tipoAccesoToString(Acceso::VISITA_ENROLADA), 'borrado' => 0];
		return $listado_tipo;
	}
}
