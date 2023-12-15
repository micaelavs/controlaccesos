<?php

namespace App\Controlador;

use App\Helper\Vista;
use App\Modelo;
use App\Modelo\SituacionRevista;
use App\Modelo\Empleado;
use App\Modelo\Template;
use App\Helper;
use PharIo\Manifest\Extension;

class Empleados extends Base
{

	protected function accion_index()
	{			
		$ubicaciones_select =  Modelo\Ubicacion::listar();
		$ubicaciones = [];
		foreach ($ubicaciones_select as $value) {
			$ubicaciones[$value['id']] = $value['nombre'];
		}

		$user_dependencias = ($this->_user->dependencias);
		$dependencias_select =  Modelo\Direccion::listar();
		$dependencias = [];
		if(!empty($this->_user->dependencias)) {
			foreach ($user_dependencias as $value) {
				$userDep = Modelo\Direccion::obtener($value);
				$dependencias[$userDep->id] = $userDep->nombre;
			}
		}else{
			foreach ($dependencias_select as $value) {
				$dependencias[$value['id']] = $value['nombre'];
			}
		}

		$lista_select	 = SituacionRevista::listarParaSelect();
		$contratos = [];
		foreach ($lista_select as $value) {
			$contratos[$value['id']] = $value['nombre'];
		}
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'ubicaciones', 'dependencias', 'contratos')))->pre_render();
	}

	public function accion_alta()
	{

		$dependencias_select =  Modelo\Direccion::listar();
		$dependencias = [];
		foreach ($dependencias_select as $value) {
			$dependencias[$value['id']] = $value['nombre'];
		}

		$lista_select	 = SituacionRevista::listarParaSelect();
		$contratos = [];
		foreach ($lista_select as $value) {
			$contratos[$value['id']] = $value['nombre'];
		}

		$lista_cargos = Empleado::$cargo_descripcion;
		$cargos = [];
		foreach ($lista_cargos as $value) {
			$cargos[$value['idCargo']] = $value['descripcionCargo'];
		}

		$ubicaciones_select =  Modelo\Ubicacion::listar();
		$ubicaciones = [];
		foreach ($ubicaciones_select as $value) {
			$ubicaciones[$value['id']] = $value['nombre'];
		}

		$lista_generos	= \App\Modelo\Persona::$TIPO_GENEROS;
		$generos = [];
		foreach ($lista_generos as $value) {
			$generos[$value['id']] = $value['nombre'];
		}

		$empleado = Modelo\Empleado::obtener($this->request->query('id'));

		if ($this->request->post('empleados') == 'alta') {
			$empleado->usuario_id = !empty($temp = $this->request->post('usuario_id')) ?  $temp : null;
			$empleado->persona_id = !empty($temp = $this->request->post('persona_id')) ?  $temp : null;
			$empleado->ubicacion = !empty($temp = $this->request->post('ubicacion_principal')) ?  $temp : null;
			$empleado->ubicaciones_autorizadas = !empty($temp = $this->request->post('ubicaciones')) ?  $temp : null;
			//$empleado->id_codep = !empty($temp = $this->request->post('id_codep')) ?  $temp : null;			
			$empleado->cuit = !empty($temp = $this->request->post('cuit')) ?  $temp : null;
			$empleado->email = !empty($temp = $this->request->post('email')) ?  $temp : null;
			$empleado->planilla_reloj = !empty($temp = $this->request->post('planilla_reloj')) ?  $temp : 0;
			$empleado->oficina_contacto = !empty($temp = $this->request->post('oficina_contacto')) ?  $temp : null;
			$empleado->oficina_interno = !empty($temp = $this->request->post('oficina_interno')) ?  $temp : null;
			$empleado->dependencia_principal = !empty($temp = $this->request->post('dependencia')) ?  $temp : null;
			$empleado->desde_principal = !empty($temp = $this->request->post('fecha_desde_p')) ? \DateTime::createFromFormat('d/m/Y', $temp)  : null;
			$empleado->hasta_principal = !empty($temp = $this->request->post('fecha_hasta_p')) ?  \DateTime::createFromFormat('d/m/Y', $temp)  : null;
			$empleado->cargo = !empty($temp = $this->request->post('cargo')) ?  $temp : null;
			$empleado->id_tipo_contrato = !empty($temp = $this->request->post('contrato')) ?  $temp : null;
			$empleado->desde_contrato = !empty($temp = $this->request->post('fecha_desde_c')) ? \DateTime::createFromFormat('d/m/Y', $temp) : null;
			$empleado->hasta_contrato = !empty($temp = $this->request->post('fecha_hasta_c')) ?  \DateTime::createFromFormat('d/m/Y', $temp) : null;
			$empleado->horarios = !empty($temp = $this->request->post('horarios')) ?  $temp : null;
			$empleado->observacion = !empty($temp = $this->request->post('observacion')) ?  $temp : null;
			$empleado->documento = !empty($temp = $this->request->post('documento')) ?  $temp : null;
			$empleado->nombre = !empty($temp = $this->request->post('nombre')) ?  $temp : null;
			$empleado->apellido = !empty($temp = $this->request->post('apellido')) ?  $temp : null;
			$empleado->genero = !empty($temp = $this->request->post('genero')) ?  $temp : 0;
			
			if ($empleado->validar()) {
				$empleado->alta();
				$this->mensajeria->agregar(
					"AVISO:El Registro fué ingresado de forma exitosa.",
					\FMT\Mensajeria::TIPO_AVISO,
					$this->clase
				);
				$redirect = Vista::get_url("index.php/empleados/index");
				$this->redirect($redirect);
			} else {
				$err	= $empleado->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}
		}

		$recuper_info = $this->recuperar_info();

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'empleado', 'dependencias', 'contratos', 'cargos', 'ubicaciones', 'generos','recuper_info')))->pre_render();
	}

	public function accion_modificacion()
	{

		$dependencias_select =  Modelo\Direccion::listar();
		$dependencias = [];
		foreach ($dependencias_select as $value) {
			$dependencias[$value['id']] = $value['nombre'];
		}

		$lista_select	 = SituacionRevista::listarParaSelect();
		$contratos = [];
		foreach ($lista_select as $value) {
			$contratos[$value['id']] = $value['nombre'];
		}

		$lista_cargos = Empleado::$cargo_descripcion;
		$cargos = [];
		foreach ($lista_cargos as $value) {
			$cargos[$value['idCargo']] = $value['descripcionCargo'];
		}

		$ubicaciones_select =  Modelo\Ubicacion::listar();
		$ubicaciones = [];
		foreach ($ubicaciones_select as $value) {
			$ubicaciones[$value['id']] = $value['nombre'];
		}

		$lista_generos	= \App\Modelo\Persona::$TIPO_GENEROS;
		$generos = [];
		foreach ($lista_generos as $value) {
			$generos[$value['id']] = $value['nombre'];
		}

		$empleado = Modelo\Empleado::obtener($this->request->query('id'));

		if ($this->request->post('empleados') == 'modificacion') {
			$empleado->usuario_id = !empty($temp = $this->request->post('usuario_id')) ?  $temp : null;
			$empleado->persona_id = !empty($temp = $this->request->post('persona_id')) ?  $temp : null;
			$empleado->ubicacion = !empty($temp = $this->request->post('ubicacion_principal')) ?  $temp : null;
			$empleado->ubicaciones_autorizadas = !empty($temp = $this->request->post('ubicaciones')) ?  $temp : null;
			//$empleado->id_codep = !empty($temp = $this->request->post('id_codep')) ?  $temp : null;			
			$empleado->cuit = !empty($temp = $this->request->post('cuit')) ?  $temp : null;
			$empleado->email = !empty($temp = $this->request->post('email')) ?  $temp : null;
			$empleado->planilla_reloj = !empty($temp = $this->request->post('planilla_reloj')) ?  $temp : 0;
			$empleado->oficina_contacto = !empty($temp = $this->request->post('oficina_contacto')) ?  $temp : null;
			$empleado->oficina_interno = !empty($temp = $this->request->post('oficina_interno')) ?  $temp : null;
			$empleado->dependencia_principal = !empty($temp = $this->request->post('dependencia')) ?  $temp : null;
			$empleado->desde_principal = !empty($temp = $this->request->post('fecha_desde_p')) ?   \DateTime::createFromFormat('d/m/Y', $temp)  : null;
			$empleado->hasta_principal = !empty($temp = $this->request->post('fecha_hasta_p')) ?   \DateTime::createFromFormat('d/m/Y', $temp)  : null;
			$empleado->cargo = !empty($temp = $this->request->post('cargo')) ?  $temp : null;
			$empleado->id_tipo_contrato = !empty($temp = $this->request->post('contrato')) ?  $temp : null;
			$empleado->desde_contrato = !empty($temp = $this->request->post('fecha_desde_c')) ?   \DateTime::createFromFormat('d/m/Y', $temp)  : null;
			$empleado->hasta_contrato = !empty($temp = $this->request->post('fecha_hasta_c')) ?   \DateTime::createFromFormat('d/m/Y', $temp)  : null;
			$empleado->horarios = !empty($temp = $this->request->post('horarios')) ?  $temp : null;
			$empleado->observacion = !empty($temp = $this->request->post('observacion')) ?  $temp : null;
			$empleado->documento = !empty($temp = $this->request->post('documento')) ?  $temp : null;
			$empleado->nombre = !empty($temp = $this->request->post('nombre')) ?  $temp : null;
			$empleado->apellido = !empty($temp = $this->request->post('apellido')) ?  $temp : null;
			$empleado->genero = !empty($temp = $this->request->post('genero')) ?  $temp : null;

			if ($empleado->validar()) {				
				$empleado->modificacion();
				$this->mensajeria->agregar(
					"AVISO:El Registro fué modificado de forma exitosa.",
					\FMT\Mensajeria::TIPO_AVISO,
					$this->clase
				);
				$redirect = Vista::get_url("index.php/empleados/index");
				$this->redirect($redirect);
			} else {
				$err	= $empleado->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
			}
		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'empleado', 'dependencias', 'contratos', 'cargos', 'ubicaciones', 'generos')))->pre_render();
	}

	public function accion_modificacion_contrato()
	{

		$dependencias_select =  Modelo\Direccion::listar();
		$dependencias = [];
		foreach ($dependencias_select as $value) {
			$dependencias[$value['id']] = $value['nombre'];
		}

		$lista_select	 = SituacionRevista::listarParaSelect();
		$contratos = [];
		foreach ($lista_select as $value) {
			$contratos[$value['id']] = $value['nombre'];
		}

		$lista_cargos = Empleado::$cargo_descripcion;
		$cargos = [];
		foreach ($lista_cargos as $value) {
			$cargos[$value['idCargo']] = $value['descripcionCargo'];
		}

		$ubicaciones_select =  Modelo\Ubicacion::listar();
		$ubicaciones = [];
		foreach ($ubicaciones_select as $value) {
			$ubicaciones[$value['id']] = $value['nombre'];
		}

		$lista_generos	= \App\Modelo\Persona::$TIPO_GENEROS;
		$generos = [];
		foreach ($lista_generos as $value) {
			$generos[$value['id']] = $value['nombre'];
		}

		$empleado = Modelo\Empleado::obtener($this->request->query('id'));
		$inactivo = false;

		if(is_null($empleado->id_tipo_contrato)){
			$inactivo = true;
			$contrato_anterior = Modelo\Empleado::contrato_anterior($empleado->id);
			$empleado->id_tipo_contrato = $contrato_anterior['id_tipo_contrato'];
			$empleado->cargo = $contrato_anterior['cargo'];
			$empleado->desde_contrato = \DateTime::createFromFormat('Y-m-d', $contrato_anterior['fecha_desde']);
			$empleado->hasta_contrato = \DateTime::createFromFormat('Y-m-d', $contrato_anterior['fecha_hasta']);
		}

		if ($this->request->post('empleados') == 'finalizar_contrato') {
			$empleado->info_temporal["fecha_contrato_anterior"] = $empleado->desde_contrato;
			$empleado->desde_contrato = !empty($temp = $this->request->post('fecha_desde_c_nuevo')) ?  \DateTime::createFromFormat('d/m/Y', $temp)  : null;
			$empleado->hasta_contrato = !empty($temp = $this->request->post('fecha_hasta_c')) ? \DateTime::createFromFormat('d/m/Y', $temp)  : null;
			$empleado->info_temporal["finalizar_contrato"] = true;

			if ($empleado->validarContrato()) {
				$data = $empleado;
				$this->mantener_info($data);
				$redirect = Helper\Vista::get_url('index.php/empleados/finalizar_contrato');
				$this->redirect($redirect);
			}else{
				$err	= $empleado->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
				$redirect = Helper\Vista::get_url('index.php/empleados/modificacion_contrato/' . $empleado->id);
				$this->redirect($redirect);
			}
		}
		if ($this->request->post('empleados') == 'renovar_contrato') {
			$empleado->info_temporal["fecha_contrato_anterior"] = $empleado->desde_contrato;
			$empleado->hasta_contrato = !empty($temp = $this->request->post('fecha_hasta_c')) ?  \DateTime::createFromFormat('d/m/Y', $temp)  : null;
			$empleado->desde_contrato = !empty($temp = $this->request->post('fecha_desde_c_nuevo')) ?  \DateTime::createFromFormat('d/m/Y', $temp)  : null;
			$empleado->cargo =  !empty($temp = $this->request->post('cargo_nuevo')) ?  $temp : null;
			$empleado->id_tipo_contrato =!empty($temp = $this->request->post('contrato_nuevo')) ?  $temp : null;

			if ($empleado->validarContrato()) {
				$empleado->cancelar_contrato();
				$empleado->hasta_contrato = null;
				if ($empleado->cambiar_contrato()) {
					$this->mensajeria->agregar('AVISO: Contrato del Empleado fue renovado.', \FMT\Mensajeria::TIPO_AVISO, $this->clase, 'index');
					$redirect = Helper\Vista::get_url('index.php/empleados/index');
					$this->redirect($redirect);
				}
			} else {
				$err	= $empleado->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
				$redirect = Helper\Vista::get_url('index.php/empleados/modificacion_contrato/' . $empleado->id);
				$this->redirect($redirect);
			}
		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'empleado', 'dependencias', 'contratos', 'cargos', 'ubicaciones', 'generos' ,'inactivo')))->pre_render();
	}

	public function accion_finalizar_contrato()
	{
		$data_empleado = $this->recuperar_info(); //RECUPERO INFO DESDE LA ACCION DE MODIFICAR CONTRATO
		$this->mantener_info($data_empleado);
		$empleado = null;
		
		if ($this->request->post('confirmar')) {
			$recuperar_data_empleado = $this->recuperar_info(); //RECUPERO INFO DESDE LA ACCION DE FINALIZAR CONTRATO

			$empleado = Modelo\Empleado::obtener($recuperar_data_empleado->id);
			if (isset($recuperar_data_empleado->hasta_contrato)) {
				$fecha_hasta_contrato_format = $recuperar_data_empleado->hasta_contrato->format('Y-m-d');
			} else {
				$fecha_hasta_contrato_format = \DateTime::createFromFormat("d/m/Y", date('d/m/Y'))->format('Y-m-d');
			}

			$empleado->hasta_contrato = $fecha_hasta_contrato_format;
			$empleado->desde_contrato = $empleado->desde_contrato->format('Y-m-d');

			if ($empleado->cancelar_contrato()) {
				$this->mensajeria->agregar('AVISO: Contrato del Empleado fue finalizado.', \FMT\Mensajeria::TIPO_AVISO, $this->clase, 'index');
				$redirect = Helper\Vista::get_url('index.php/empleados/index');
				$this->redirect($redirect);
			} else {
				$err= $empleado->errores;
				foreach ($err as $text) {
					$this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
				}
				$redirect = Helper\Vista::get_url('index.php/empleados/modificacion_contrato/' . $empleado->id);
				$this->redirect($redirect);
			}

		}

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'data_empleado', 'empleado')))->pre_render();
	}

	protected function accion_baja()
	{
		$empleado = Modelo\Empleado::obtener($this->request->query('id'));
		if ($empleado->id) {
			if ($this->request->post('confirmar')) {
				$subtitulo = 'Empleado';
				$texto = "Dará de baja al Empleado <strong>{$empleado->nombre}, </strong>";
				$res = $empleado->baja();
				if ($res) {
					$this->mensajeria->agregar('AVISO: Se eliminó un empleado de forma exitosa.', \FMT\Mensajeria::TIPO_AVISO, $this->clase, 'index');
					$redirect = Helper\Vista::get_url('index.php/empleados/index');
					$this->redirect($redirect);
				}
			}
		} else {
			$redirect = Helper\Vista::get_url('index.php/empleados/index');
			$this->redirect($redirect);
		}

		$vista = $this->vista;
		(new Helper\Vista($this->vista_default, compact('empleado', 'vista', 'empleado')))->pre_render();
	}

	public function accion_ajax_lista_ubicaciones()
	{
		$data	= [
			'ubicaciones'	=> Modelo\Ubicacion::listar2()
		];
		//Vista::crear('json_response', ['data' => compact('data')]);
		(new Vista(VISTAS_PATH . '/json_response.php', compact('ubicaciones')))->pre_render();
	}

	public function accion_ajax_empleados()
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
				'ubicacion'      => $this->request->query('ubicacion'),
				'dependencia'      => $this->request->query('dependencia'),
				'contrato'      => $this->request->query('contrato'),
				'enrolado'      => $this->request->query('enrolado'),
				'estado'      => $this->request->query('estado'),
			],
		];

		$data =  Modelo\Empleado::listar_empleados($params);
		$datos['draw']    = (int) $this->request->query('draw');
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	protected function accion_exportar_excel_empleados() {
        $nombre = 'Empleados'.date('Ymd_His');

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $this->request->post('search'), $date)) {
            $el_resto = \preg_replace('/^\d{2}\/\d{2}\/\d{4}/', '', $this->request->post('search'));
            $search = \DateTime::createFromFormat('d/m/Y', $date[0])->format('Y-m-d') . $el_resto;
        } else {
            $search = $this->request->post('search');
        }

        $params = [
            'order' => [!empty($this->request->post('campo_sort')) ? [
                'campo'=> $this->request->post('campo_sort'),
                'dir' => $this->request->post('dir')
            ] : ''],
            'search'    => !empty($search) ? $search : '',
            'start'     => '',
            'lenght'    => '',
            'filtros'   => [
                'dependencia' 	=> $this->request->post('dependencia'),
                'ubicacion'   	=> $this->request->post('ubicacion'), 
                'contrato'    	=> $this->request->post('contrato'),
                'enrolado'   	=> $this->request->post('enrolado'),
                'estado'    	=> $this->request->post('estado')


            ],
        ];
     
        $titulos = [
         
            'documento'		=>'Documento',
            'nombre'   		=>'Nombre',
            'apellido'   	=>'Apellido',
            'ubicacion'		=> 'Ubicación Principal',
            'd_principal'	=> 'Dependencia Principal',
            'contrato_nombre'	=>'Contrato',
            'enrolado'			=>'Enrolado'
        ];

        $data = Empleado::listar_empleados_excel($params);
       
        array_walk($data, function (&$value) {
            unset($value['id']);
            unset($value['persona_id']);
            unset($value['genero']);
            unset($value['ubicacion_id']);
            unset($value['id_tipo_contrato']);
            unset($value['ubicaciones_autorizadas']);
            unset($value['id_d_principal']);
            unset($value['hasta_contrato']);
            unset($value['desde_contrato']);
            unset($value['usuario']);
        
        });
     
      
        foreach ($data as &$value) {
            if($value['enrolado'] == Empleado::ENROLADO_SI){
            	$value["enrolado"] = "ENROLADO";
            }elseif($value['enrolado'] == Empleado::ENROLADO_NO){
            	$value['enrolado'] = "NO ENROLADO";
            }
           
       	}

        (new Vista(VISTAS_PATH.'/csv_response.php',compact('nombre', 'titulos', 'data')))->render();
    }

	public function accion_empleado_horarios(){
		$empleado = Empleado::obtener($this->request->query('id'));
		if(!$empleado->id){
			$this->mensajeria->agregar(
				"ERROR: El Empleado no existe.",
				\FMT\Mensajeria::TIPO_ERROR,
				$this->clase
			);
			$redirect = Vista::get_url("index.php/empleados/index");
			$this->redirect($redirect);
		}

		if ($this->request->post('operacion') == 'guardar') {
			$empleado->horarios =  ($temp = $this->request->post('horarios')) ? $temp : null;
			if ($empleado->validar_horarios()) {
				$empleado->alta_empleado_horario();
				$this->mensajeria->agregar("AVISO: Horarios guardados exitosamente.",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
				$redirect = Vista::get_url("index.php/empleados/index");
				$this->redirect($redirect);
			}else {
				$this->mensajeria->agregar("ERROR: No pudimos guardar los horarios del empleado.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
			}
		}
		
		$plantillas = [];
		foreach (Empleado::lista_plantilla_horaria() as $value) {
			$plantillas[$value['id']] = $value['nombre'];
		}
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'empleado','plantillas')))->pre_render();
	}

	public function accion_plantilla_horaria(){
		$id_plantilla = $this->request->query('id');
		$horarios = Empleado::horarios_plantilla($id_plantilla);
		$data	= $horarios;
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	public function accion_buscarDatosEmpleadoAjax()
	{
		if (isset($_POST['documento'])) {
			$emp = Empleado::obtenerPorDocumento($this->request->post('documento'));

			$data	= $emp;
			(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
		} else {
			echo 'Error variable POST';
		}
	}

	public function accion_buscar_user()
	{
		$persona = Modelo\Persona::obtenerPorDocumento($this->request->post('documento'));
		
		if ($persona->id > 0) {
			$emp = Empleado::obtenerPorDocumento($this->request->post('documento'));
			if ($emp->id > 0) {
				
				switch (true) {

					case ($emp->id_tipo_contrato === 0):

						$this->mensajeria->agregar(
							"El empleado {$emp->nombre} {$emp->apellido}, con DNI {$emp->documento} se encuentra inactivo. Para reactivarlo, busquelo en la lista de inactivos.",
							\FMT\Mensajeria::TIPO_AVISO,
							$this->clase
						);

						$redirect = Vista::get_url("index.php/empleados/index");
						$this->redirect($redirect);

						break;

					case ($emp->id > 0):
						$this->mensajeria->agregar(
							"El empleado {$emp->nombre} {$emp->apellido}, con DNI {$emp->documento} ENCONTRADO.",
							\FMT\Mensajeria::TIPO_AVISO,
							$this->clase
						);

						//encontre persona y empleado
						$data = $emp;
						$redirect = Vista::get_url("index.php/empleados/modificacion/".$emp->id);
						$this->redirect($redirect);

						break;
				}
			} else {
				//encontre persona pero no empleado
				$this->mensajeria->agregar(
					"La persona {$persona->nombre} {$persona->apellido}, con DNI {$persona->documento} existe pero NO ES EMPLEADO.",
					\FMT\Mensajeria::TIPO_AVISO,
					$this->clase
				);

				$data = $persona;
				$this->mantener_info($data);
				$redirect = Vista::get_url("index.php/empleados/alta");
				$this->redirect($redirect);
			}
		}
		//no encontre persona		
		$redirect = Vista::get_url("index.php/empleados/alta");

		$this->redirect($redirect);
	}

	public function accion_json_buscar_empleado() {
		$success = false;
		$lista = Empleado::buscar($this->request->query('search'));
		if (!empty($lista) && is_array($lista) && count($lista) > 0) {
			$success = true;
		}
		
		$data["success"]	= $success;
		$data["lista"]	= $lista;
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	protected function accion_enrolar()
	{
		$ubicaciones =  Modelo\Ubicacion::listar();

		$empleado = Modelo\Empleado::obtener($this->request->query('id'));
		$dependencia =  Modelo\Direccion::obtener($empleado->dependencia_principal);
		$estaEnrolado = Empleado::estaEnrolado($empleado->persona_id);
		$persona = Modelo\Persona::obtener($empleado->persona_id);

		$vista = $this->vista;
		(new Vista($this->vista_default, compact('vista', 'empleado','dependencia','ubicaciones','estaEnrolado','persona')))->pre_render();
	}

	public function accion_buscar_template_por_access_id() {
		$access_id = $this->request->post('access_id');
		$empleado = Empleado::obtenerPorDocumento($access_id);
		$persona = Modelo\Persona::obtener($empleado->persona_id);
		$success = false;
		if (!empty($empleado->id)) {
			/** @var Template[] $lista */
			$templates = Template::obtenerDelEnrolador($persona);
			$success = true;
			foreach ($templates as $template) {
				$success &= $template->validar();
			}
		}

		$data["success"] = $success;
		$data["templates"] = $templates;
		
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}

	public function accion_guardar_template_por_access_id() {
		$access_id = $this->request->post('access_id');
		$empleado = Empleado::obtenerPorDocumento($access_id);
		$persona = Modelo\Persona::obtener($empleado->persona_id);
		$success = false;
		$msg = "";
		if (!empty($empleado->id)) {
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
				$success = $empleado->bajaEnEnrolador();
				$empleado->distribuirTemplates();
			}else{
				
				foreach ($lista as $template) {
					$template->baja();
				}
				$msg = 'Error en los templates';
			}
		}
		
		$data["success"] = $success;
		$templates[] = [
			'status'  => $success,
			'message' => $msg,
		];
		(new Vista(VISTAS_PATH . '/json_response.php', compact('data','empleado', 'templates')))->pre_render();
	}

	/**
 * Recibe un documento de una persona, y un id de ubicacion (autorizada).
 * Consulta el template guardado de esa persona y lo envia a los relojes de la ubicacion.
 *
 * @return void
*/
public function accion_actualizar_ubicacion() {
	if(empty($ubicacion_id = $_POST['ubicacion_id']) || empty($documento_persona = $_POST['access_id'])){
		return http_response_code(400);
	}

	// $empleado	= Empleado::obtenerPorDocumento($documento_persona);
	// $success	= $empleado->actualizarTemplate($ubicacion_id);

	$empleado	= Empleado::obtenerPorDocumento($_POST['access_id']);
	$success	= $empleado->actualizarTemplate($_POST['ubicacion_id']);

	$ubicacion	= null;	
	foreach ((array)$empleado->ubicaciones_autorizadas as &$ubicacion_autorizada) {
		if($ubicacion_autorizada->id == $ubicacion_id)
			$ubicacion	= $ubicacion_autorizada;
	}
	$empleado	= $empleado->id;

	if(!$success)
		http_response_code(400);

	(new Vista(VISTAS_PATH . '/json_response.php', compact('data','empleado', 'ubicacion','success')))->pre_render();
}

	//_metodo_vista_tabla_base_
}
