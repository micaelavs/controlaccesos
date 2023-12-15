<?php

namespace App\Controlador;

use App\Modelo\Ubicacion;
use App\Modelo\Direccion;
use App\Helper\Vista;
use App\Modelo\Acceso;
use App\Modelo\AccesoEmpleado;
use App\Modelo\SituacionRevista;
use App\Modelo\Usuario;
use App\Modelo\Empleado;
use App\Modelo\Persona;
use App\Modelo\ContratistaEmpleado;
use App\Modelo\AppRoles;
use App\Modelo\Visita;
use App\Modelo\AccesoVisitaEnrolada;
use App\Modelo\Credencial;
use App\Modelo\Advertencia;
use App\Modelo\Pertenencia;
use App\Helper\Validador;

class Accesos extends Base
{
    public $acceso;
	public $documento;

    public function accion_cambiar_ubicacion(){
		unset($_SESSION['id_ubicacion_actual']);
        $ubicaciones_select = Ubicacion::listar();
        $ubicaciones = [];
        foreach ($ubicaciones_select as $value) {
            $ubicaciones[$value['id']] = $value['nombre'];
        }
        $vista = $this->vista;
        (new Vista(VISTAS_PATH.'/accesos/definir_ubicacion.php', compact('vista','ubicaciones')))->pre_render();
	}

    protected function accion_mis_horarios() {
		$notificacion = false;
		$duplicado    = false;
        $no_relacionado = false;
		$horarios = null;
		$ubicacion = Ubicacion::listar();
        $empleado = $this->_user->getEmpleado();

        foreach ($ubicacion as $value) {
            $ubicaciones[$value['id']] = ['id' => $value['id'], 'nombre' => $value['nombre'], 'borrado' => 0];
        }
		if(empty(Empleado::obtenerPorEmail($this->_user->email))){
			$notificacion = true;
		}

		if (count(Empleado::obtenerPorEmailContrato($this->_user->email)) > 1) {
			$duplicado = true;
		}else{
			if(!empty($empleado->id)) {
				$horarios =$empleado->horarios;
			}
		}
        
        if(empty($empleado->id)) {
			$no_relacionado = true;
		}
		$vista = $this->vista;
		(new Vista($this->vista_default, compact('ubicaciones', 'horarios','notificacion','duplicado','no_relacionado','vista')))->pre_render();					
	}

    public function accion_ajax_mis_horarios() {
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

		$empleado = $this->_user->getEmpleado();
        if(empty($empleado->id)) {
            $resp = Empleado::obtenerPorEmailContrato($this->_user->email);
            if(count($resp) == 1){
                $empleado = $resp[0];
            }
		}

        $extras = [];
		
        $draw = $_GET['draw'];
		$start = $_GET['start'];
		$length = $_GET['length'];
		$extras['ubicacion_id'] = $_GET['ubicacion'];
		$extras['fecha_ini'] = (strlen($_GET['fecha_ini'] == 0))? date('d/m/Y', strtotime('-7 day')):$_GET['fecha_ini'];
		$extras['fecha_fin'] = (strlen($_GET['fecha_fin'] == 0))? date('d/m/Y'):$_GET['fecha_fin'];
		$extras['incluir_sin_cierre'] = true;
        
		$data = AccesoEmpleado::ajax($orders, $params['start'], $params['lenght'], $params['filtros'],$extras,  false ,  $empleado->id);
		$datos['draw'] = (int) $this->request->query('draw');

        (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}


    protected function accion_historico_empleados()
    {
        $listado_ubicaciones =  Ubicacion::listar();
        $listado_ubicaciones_aux = [];
        $listado_direcciones =  Direccion::listar();
        $lista_dependecias_filtradas = [];
        $listado_direcciones_aux = [];

        if (!empty($this->_user->dependencias)) {
            foreach ($listado_direcciones as $direccion) {
                if (in_array($direccion['id'], $this->_user->dependencias)) {
                    $lista_dependecias_filtradas[] = $direccion;
                }
            }
        }

        $listado_direcciones = !empty($lista_dependecias_filtradas) ? $lista_dependecias_filtradas : $listado_direcciones;

        foreach ($listado_direcciones as $value) {
            $listado_direcciones_aux[$value['id']] = ['id' => $value['id'], 'nombre' => $value['nombre'], 'borrado' => 0];
        }

        foreach ($listado_ubicaciones as $value) {
            $listado_ubicaciones_aux[$value['id']] = ['id' => $value['id'], 'nombre' => $value['nombre'], 'borrado' => 0];
        }

        $vista = $this->vista;
        (new Vista($this->vista_default, compact('vista', 'listado_direcciones_aux', 'listado_ubicaciones_aux')))->pre_render();
    }

    protected function accion_ajax_historico_empleados()
    {

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
        //se quita el search porque agregamos el campo otros criterios
        $params    = [
            'order'        => $orders,
            'start'        => !empty($tmp = $this->request->query('start'))
                ? $tmp : 0,
            'lenght'    => !empty($tmp = $this->request->query('length'))
                ? $tmp : 10,
            'search'    => !empty($this->request->query('otro_criterio_filtro')) ? $this->request->query('otro_criterio_filtro') : '',
            'filtros'   => [
                'ubicacion'      => $this->request->query('ubicacion_filtro'),
                'dependencia'    => $this->request->query('dependencia_filtro'),
                'fecha_desde'    => $this->request->query('fecha_desde_filtro'),
                'fecha_hasta'    => $this->request->query('fecha_hasta_filtro'),
                'incluir_sin_cierre'   => $this->request->query('incluir_sin_cierre_filtro'),
            ],
        ];

        $data =  AccesoEmpleado::listar_reporte($params);
        $datos['draw']    = (int) $this->request->query('draw');
        (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
    }

    protected function accion_exportar_excel_historico_empleados()
    {
        $user = Usuario::obtenerUsuarioLogueado();
        $nombre = 'historico' . date('Ymd_His');
        //se comenta porque lo sacamos

        $anio_filtro = !empty($this->request->post('anio')) ? $this->request->post('anio') : '';
        $params = [
            'order' => [!empty($this->request->post('campo_sort')) ? [
                'campo' => $this->request->post('campo_sort'),
                'dir' => $this->request->post('dir')
            ] : ''],
            'start'     => '',
            'lenght'    => '',
            'search'    => !empty($this->request->post('otro_criterio')) ? $this->request->post('otro_criterio') : '',
            'filtros'   => [
                'ubicacion'             => $this->request->post('ubicacion'),
                'dependencia'            => $this->request->post('dependencia'),
                'fecha_desde'           => $this->request->post('fecha_desde'),
                'fecha_hasta'           => $this->request->post('fecha_hasta'),
                'incluir_sin_cierre'    =>  $this->request->post('incluir_sin_cierre'),
            ],
        ];

        $titulos = [
            'documento'   => 'Documento',
            'nombre'      => 'Nombre',
            'apellido'    => 'Apellido',
            'fecha_entrada'   => 'Fecha Ingreso',
            'hora_entrada'    => 'Hora Ingreso',
            'tipo_ingreso'    => 'Tipo Ingreso',
            'fecha_egreso'    => 'Fecha Egreso',
            'hora_egreso'     => 'Hora Egreso',
            'tipo_egreso'    => 'Tipo Egreso',
            'codep'       => 'Dependencia',
            'ubicacion'       => 'Ubicación',
            'usuario_ingreso' => 'Usuario Ingreso',
            'usuario_egreso'  => 'Usuario Egreso',
            'observaciones'  => 'Observaciones'

        ];

        $data = AccesoEmpleado::listar_historico_empleados_excel($params);
        $rol = AppRoles::obtener_rol();

        (new Vista(VISTAS_PATH . '/csv_response.php', compact('nombre', 'titulos', 'data')))->render();
    }

    protected function accion_historico_visitas_contratistas()
    {
        $ubicaciones_select =  Ubicacion::listar();
        $ubicaciones = [];
        foreach ($ubicaciones_select as $value) {
            $ubicaciones[$value['id']] = $value['nombre'];
        }

        $tipos_accesos = [
            Acceso::VISITANTE   => Acceso::tipoAccesoToString(Acceso::VISITANTE),
            Acceso::CONTRATISTA => Acceso::tipoAccesoToString(Acceso::CONTRATISTA),
            Acceso::VISITA_ENROLADA => Acceso::tipoAccesoToString(Acceso::VISITA_ENROLADA)
        ];

        $vista = $this->vista;
        (new Vista($this->vista_default, compact('vista', 'ubicaciones', 'tipos_accesos')))->pre_render();
    }

    protected function accion_ajax_historico_visitas_contratistas()
    {
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
        //se comenta el campo search porque se agrega el filtro otros criterios

        $params    = [
            'order'        => $orders,
            'start'        => !empty($tmp = $this->request->query('start'))
                ? $tmp : 0,
            'lenght'    => !empty($tmp = $this->request->query('length'))
                ? $tmp : 10,
            'filtros'   => [
                'ubicacion'        => $this->request->query('ubicacion'),
                'fecha_desde'        => $this->request->query('fecha_desde'),
                'fecha_hasta'        => $this->request->query('fecha_hasta'),
                'sin_cierre'        => $this->request->query('sin_cierre'),
                'tipos_accesos'        => $this->request->query('tipos_accesos'),
                'credencial'        => $this->request->query('credencial'),
                'otros_criterios'   => $this->request->query('otros_criterios'),
            ],
        ];

        $data =  Acceso::listar_reporte($params);
        $datos['draw']    = (int) $this->request->query('draw');
        (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
    }


    public function accion_exportar_historico_visitas_contratistas_csv()
    {
        $nombre = 'historico_visitas_contratistas_' . date('Ymd_His');
        $user = Usuario::obtenerUsuarioLogueado();
        //se comenta porque se incluye el campo otros criterios

        $params = [
            'order' => [!empty($this->request->post('campo_sort')) ? [
                'campo' => $this->request->post('campo_sort'),
                'dir' => $this->request->post('dir')
            ] : ''],
            'start'     => '',
            'lenght'    => '',
            'filtros'   => [
                'ubicacion'       => $this->request->post('ubicacion_csv'),
                'tipos_accesos'       => $this->request->post('tipos_accesos_csv'),
                'fecha_desde'       => $this->request->post('fecha_desde_fecha_csv'),
                'fecha_hasta'       => $this->request->post('fecha_hasta_fecha_csv'),
                'credencial'        => $this->request->post('credencial_csv'),
                'sin_cierre'        => $this->request->post('sin_cierre_csv'),
                'otros_criterios'   => $this->request->post('otros_criterios_csv')
            ],

        ];
        $titulos = [
            'acceso' => ' Tipo de Acceso',
            'documento' => 'Documento',
            'nombre' => 'Nombre',
            'apellido' => 'Apellido',
            'ubicacion' => 'Ubicacion',
            'fecha_ingreso' => 'Fecha Ingreso',
            'hora_ingreso' => 'Hora ingreso',
            'tipo_ingreso' => 'Tipo ingreso',
            'credencial' => 'Credencial',
            'usuario_ingreso_documento' => 'Documento de usuario ingreso',
            'usuario_ingreso' => 'Usuario ingreso',
            'fecha_egreso' => 'Fecha egreso',
            'hora_egreso' => 'Hora egreso',
            'tipo_egreso' => 'Tipo egreso',
            'usuario_egreso_documento' => 'Documento de usuario egreso',
            'usuario_egreso' => 'Usuario egreso',
            'observaciones' => 'Observaciones',
            'origen'  => 'Origen',
            'destino' => 'Destino',
            'autorizante' => 'Autorizante'
        ];

        $data = Acceso::listar_hist_contratista_visitas_excel($params);

        array_walk($data, function (&$value) {
            unset($value['id']);
        });

        $usuario = $user->nombre . ' ' . $user->apellido;

        (new Vista(VISTAS_PATH . '/csv_response.php', compact('nombre', 'titulos', 'data', 'usuario')))->pre_render();
    }

    protected function accion_index() {
        if (!isset($_SESSION['id_ubicacion_actual']) OR 
            $_SESSION['id_ubicacion_actual'] <= 0){
            if ($this->request->post('id_ubicacion_actual')>0) {
                $_SESSION['id_ubicacion_actual'] = $this->request->post('id_ubicacion_actual'); 
                if (!$this->acceso) {
                    $this->acceso = Acceso::obtener(0);
                }
                $acceso = $this->acceso;
                $ubicacion = Ubicacion::obtener($_SESSION['id_ubicacion_actual']);
                $vista = $this->vista;
                (new Vista($this->vista_default, compact('vista','acceso','ubicacion')))->pre_render();
            }else{
                $ubicaciones_select = Ubicacion::listar();
		        $ubicaciones = [];
		        foreach ($ubicaciones_select as $value) {
			        $ubicaciones[$value['id']] = $value['nombre'];
		        }
                $vista = $this->vista;
                (new Vista(VISTAS_PATH.'/accesos/definir_ubicacion.php', compact('vista','ubicaciones')))->pre_render();
            }
        }else{
            $temp = $this->recuperar_info();
            if($temp){
                $this->acceso = $temp;
            }
            else if (!$this->acceso) {
                $this->acceso = Acceso::obtener(0);
            }
            $acceso = $this->acceso;
            $ubicacion = Ubicacion::obtener($_SESSION['id_ubicacion_actual']);
            $vista = $this->vista;
            (new Vista($this->vista_default, compact('vista','acceso','ubicacion')))->pre_render();
        }
	}

    protected function accion_definir_ubicacion() {
        if (!isset($_SESSION['id_ubicacion_actual']) OR 
            $_SESSION['id_ubicacion_actual'] <= 0){
                (new Vista(VISTAS_PATH.'/accesos/definir_ubicacion.php', compact('vista','acceso')))->pre_render();
        }else{
		if (!$this->acceso) {
			$this->acceso = Acceso::obtener(0);
		}
		$acceso = $this->acceso;
        $vista = $this->vista;
        (new Vista($this->vista_default, compact('vista','acceso')))->pre_render();
        }
	}

    public function accion_ajax_accesos() {
		if (
			!isset($_GET['ubicacion_id']) ||
			!isset($_GET['fecha']) ||
			!isset($_GET['draw']) ||
			!isset($_GET['order']) ||
			!is_array($_GET['order']) ||
			!isset($_GET['start']) ||
			!isset($_GET['length']) ||
			!isset($_GET['search']['value']) ||
			!isset($_GET['_'])
		) {
			return null;
		}
		$draw = $_GET['draw'];
		$order = $_GET['order'];
		$start = $_GET['start'];
		$length = $_GET['length'];
		$filtros = $_GET['search']['value'];
		$ubicacion = Ubicacion::obtener($this->request->query('ubicacion_id') ?: 0);
		$fecha = \DateTime::createFromFormat('d/m/Y', $this->request->query('fecha'));
		$datos = Acceso::ajax($ubicacion, $fecha, $order, $start, $length, $filtros);
    
		$ids_persona = Visita::obtener_id($ubicacion->id ?: 0); 

		foreach ($datos['data'] as $key => &$value) {
			$value['es_visita'] = false;
			if(($value['tipo_modelo'] == AccesoVisitaEnrolada::TIPO_MODEL) AND
			 (in_array($value['persona_id'], $ids_persona))){ 			
				 	$value['es_visita'] = true;			
			}
			//nueva modalidad de ingreso - tarjeta magnetica
			if ($value['tipo_credencial'] == 1){
				$value['nombre'] = $value['nombre'] . ' (TM Entregada)';
			}else {
				if ($value['tipo_credencial'] > 1){
					$value['nombre'] = $value['nombre'] . ' (TM Ingresada)';
				}
			}
		}

        $data = $datos;
        $datos['draw']    = (int) $this->request->query('draw');
        (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
	}


    public function accion_buscar_documento() {
		
		$this->datos();
              
        if ($this->request->post('persona_documento') != '') {
            $data = $this->acceso;
            $ubicaciones_permisos = $data->contratista_empleado->obtenerUbicacion($this->acceso->ubicacion->id);
            $pertenencias = Pertenencia::listarPorDocumento($this->request->post('persona_documento'));
            $advertencias = Advertencia::listarPorDocumento($this->request->post('persona_documento'));

            $pertenencia_ubicacion = [];
			foreach ($pertenencias as $pertenencia) {
				if (($pertenencia->ubicacion->id === $this->_user->ubicacion->id) || !$pertenencia->ubicacion->id) {
					$pertenencia_ubicacion[] = $pertenencia;

				}
			}

            $advertencia_ubicacion = [];
			foreach ($advertencias as $advertencia) {
				if (($advertencia->ubicacion->id === $this->_user->ubicacion->id) || !$advertencia->ubicacion->id) {
					$advertencia_ubicacion[] = $advertencia;
				}
			}

            $data = [
                'data' => $this->acceso,
                'ubicaciones_permisos' => $ubicaciones_permisos,
                'pertenencias' => $pertenencia_ubicacion,
                'advertencias' => $advertencia_ubicacion,
                'status' => true
            ];
            (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
        }else{
            $data = [
                'data' => null,
                'ubicaciones_permisos' => null,
                'pertenencias' => null,
                'advertencias' => null,
                'status' => false
            ];
            (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
        }        
	}

    public function accion_buscar_autorizante() {

        $this->datos();
        if ($this->request->post('autorizante_documento') != '') {
            $data = $this->acceso;
            
            if (is_null($this->acceso->autorizante->id)){
                $data = [
                    'data' => 'NO ENCONTRADO',
                    'ubicaciones_permisos' => null,
                    'status' => false
                ];
                (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
                
            }
            $ubicaciones_permisos = $data->contratista_empleado->obtenerUbicacion($this->acceso->ubicacion->id);

            $data = [
                'data' => $this->acceso,
                'ubicaciones_permisos' => $ubicaciones_permisos,
                'status' => true
            ];
            (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
        }else{
            $data = [
                'data' => null,
                'ubicaciones_permisos' => null,
                'status' => false
            ];
            (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
        }   
	}

    public function validarDocumento($documento, $modelo, $autorizante = false) {
		$inputs = [];
		$inputs['documento'] = $documento;
		$rules = ['documento' => ['required', 'documento']];
		$naming['documento'] = 'Documento de ' . $modelo;
		if ($autorizante) {
			$ubicacion = $this->acceso->ubicacion;
			$inputs['autorizante'] = $this->acceso->autorizante;
			$rules['autorizante'] = [
				'empleado_valido'    => function ($empleado) {
					/** @var Empleado $empleado */
					return !empty($empleado) &&
						!empty($empleado->id) &&
						!empty($empleado->documento) &&
						!empty($empleado->nombre);
				},
				'autorizante_valido' => function ($empleado) {
				return ($empleado->id_tipo_contrato > 0 && (is_null($empleado->hasta_contrato) || $empleado->hasta_contrato >= new \DateTime()));
			}
			];
			$naming['autorizante'] = 'Autorizante';
		}
		$validator = Validador::validate($inputs, $rules, $naming);
		if ($autorizante) {
			$validator->customErrors([
				'empleado_valido'    => 'El <strong>:attribute</strong> no es un empleado válido.',
				'autorizante_valido' => 'El <strong>:attribute</strong> no es un empleado con contrato activo.',
			]);
		}
		if ($validator->isSuccess()) {
			return true;
		} else {
			return false;
		}
	}
    
    private function datos() {
		$acceso = Acceso::obtener(0);
		$acceso->persona = Persona::obtener(0);
		$acceso->autorizante = Empleado::obtener(0);
		$acceso->contratista_empleado = ContratistaEmpleado::obtener(0);
		$acceso->credencial = Credencial::obtener(0);
		//$acceso->credencial->ubicacion = $this->_user->ubicacion;
		//$acceso->credencial->acceso_id = Util::getPost('hdNroTarjeta') != null ? 1 : 0;
        $acceso->credencial->acceso_id = $this->request->post('hdNroTarjeta') != null ? 1 : 0;
		$acceso->ubicacion = Ubicacion::obtener($_SESSION['id_ubicacion_actual']);

        if($this->request->post('persona_observaciones_empleado') != ''){            
            $acceso->observaciones = $this->request->post('persona_observaciones_empleado');
        }
        elseif($this->request->post('persona_observaciones_visita_enrolada') != ''){
            $acceso->observaciones = $this->request->post('persona_observaciones_visita_enrolada');
        }
        elseif($this->request->post('persona_observaciones_contratista') != ''){
            $acceso->observaciones = $this->request->post('persona_observaciones_contratista');
        }
        elseif($this->request->post('persona_observaciones_campos_persona') != ''){
            $acceso->observaciones = $this->request->post('persona_observaciones_campos_persona');
        }

		$acceso->origen = ucfirst(mb_strtoupper($this->request->post('origen') ?: '', 'UTF-8'));
		$acceso->destino = ucfirst(mb_strtoupper($this->request->post('destino') ?: '', 'UTF-8'));
		$autorizante_documento = $this->request->post('autorizante_documento');

		$acceso->tipo_ingreso = Acceso::TIPO_REGISTRO_ONLINE;
		$acceso->persona_ingreso = Persona::obtener($this->_user->getEmpleado($this->_user)->persona_id);
		if (!empty($autorizante_documento)) {
			$autorizante = Empleado::obtenerPorDocumento($autorizante_documento);
			if (!empty($autorizante) &&
				!empty($autorizante->id)
			)
				$acceso->autorizante = $autorizante;
		}else{
			$autorizante_documento_precargado = ContratistaEmpleado::obtener($this->request->post('persona_documento'));           
			if(!is_null($autorizante_documento_precargado)){
                
                $personaDoc = Persona::obtener($autorizante_documento_precargado->autorizante->persona_id);                
				$autorizante_precargado = Empleado::obtenerPorDocumento($personaDoc->documento);
				$acceso->autorizante = $autorizante_precargado;
			}
		}
		$acceso->tipo_acceso = null;
		
		if ($this->request->post('persona_documento')) {
			$documento = $this->request->post('persona_documento');
			if (!empty($documento)) {
				//$this->advertencia($documento);
				//$this->pertenencia($documento);
				/** @var Empleado $empleado */
				$empleado = Empleado::obtenerPorDocumento($documento);
				if (!empty($empleado) &&
					!empty($empleado->id) &&
					$empleado->puedeAcceder($_SESSION['id_ubicacion_actual'])
				) {
					$acceso->tipo_acceso = Acceso::EMPLEADO;
					$acceso->empleado = $empleado;
                    $persona = Persona::obtenerPorDocumento($documento);
					$acceso->persona = $persona;
				} else {
					$contratista = ContratistaEmpleado::obtener($documento);
					if (!empty($contratista) &&
						!empty($contratista->id)
					) {
                        if ($contratista->puedeAcceder($_SESSION['id_ubicacion_actual'])){
                            $acceso->tipo_acceso = Acceso::CONTRATISTA;
						    $acceso->contratista_empleado = $contratista;
						    $acceso->persona = $contratista->persona;
                        }else{
                            $acceso->tipo_acceso = Acceso::VISITANTE;
						    $acceso->contratista_empleado = $contratista;
						    $acceso->persona = $contratista->persona;
                        }						
					} else {
						$visitaEnrolada = Visita::obtenerPorDocumento($documento);
						if (!empty($visitaEnrolada) && !empty($visitaEnrolada->id) && $visitaEnrolada->puedeAcceder($_SESSION['id_ubicacion_actual'])){
							$acceso->tipo_acceso = Acceso::VISITA_ENROLADA;
							$acceso->persona = $visitaEnrolada->persona;
							$acceso->visita_enrolada = $visitaEnrolada;
							$acceso->autorizante = $visitaEnrolada->autorizante;
						} else {
							$acceso->tipo_acceso = Acceso::VISITANTE;
							$persona = Persona::obtenerPorDocumento($documento);
							if (!empty($persona) && !empty($persona->id)) {
								$acceso->persona = $persona;
							} else {
								$acceso->persona->documento = $this->request->post('persona_documento');
								$acceso->persona->nombre = mb_strtoupper($this->request->post('persona_nombre') ?: '', 'UTF-8');
								$acceso->persona->apellido = mb_strtoupper($this->request->post('persona_apellido') ?: '', 'UTF-8');
							}
						}
					}
					$credencial_code = $this->request->post('credencial');
					$acceso_id = $this->request->post('hdNroTarjeta') != null ? 1 : 0;
					if (!empty($credencial_code)) {
							$credencial = Credencial::obtenerPorCodigo($credencial_code, $this->_user->ubicacion, $acceso_id);
							if (empty($credencial->errores)) {
								$acceso->credencial = $credencial;
							} else {
								//Msj::addErrores($credencial->errores);
							}
					}
				}
			}

		} else if ($this->old_data instanceof Acceso) {
			$acceso = $this->old_data;
		}
        
		$this->acceso = $acceso;
	
	}

   
    protected function accion_horas_trabajadas()
    {
        $user_dependencias = ($this->_user->dependencias);
		$dependencias_select =  Direccion::listar();
		$dependencias = [];
		if(!empty($this->_user->dependencias)) {
			foreach ($user_dependencias as $value) {
				$userDep = Direccion::obtener($value);
				$dependencias[$userDep->id] = $userDep->nombre;
			}
		}else{
			foreach ($dependencias_select as $value) {
				$dependencias[$value['id']] = $value['nombre'];
			}
		}

        $tipos_accesos = [
            Acceso::VISITANTE   => Acceso::tipoAccesoToString(Acceso::VISITANTE),
            Acceso::CONTRATISTA => Acceso::tipoAccesoToString(Acceso::CONTRATISTA),
            Acceso::VISITA_ENROLADA => Acceso::tipoAccesoToString(Acceso::VISITA_ENROLADA)
        ];

        $vista = $this->vista;
        (new Vista($this->vista_default, compact('vista', 'dependencias', 'tipos_accesos')))->pre_render();
    }

    protected function accion_ajax_horas_trabajadas()
    {
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
        //se comenta, porque se quita el campo search, se agrega el filtro otro criterios

        $params    = [
            'order'        => $orders,
            'start'        => !empty($tmp = $this->request->query('start'))
                ? $tmp : 0,
            'lenght'    => !empty($tmp = $this->request->query('length'))
                ? $tmp : 10,
            'filtros'   => [
                'dependencia'        => $this->request->query('dependencia'),
                'fecha_desde'        => $this->request->query('fecha_desde'),
                'fecha_hasta'        => $this->request->query('fecha_hasta'),
                'otro_criterio'      => $this->request->query('otro_criterio')

            ],
        ];

        $data =  Acceso::listar_horas_trabajadas($params);
        $datos['draw']    = (int) $this->request->query('draw');
        (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
    }

    protected function accion_horas_trabajadas_excel()
    {

        $nombre = 'horas_trabajadas_' . date('Ymd_His');
        $user = Usuario::obtenerUsuarioLogueado();
        //se comenta todo lo respectivo al campo search porque se agrega un filtro otro criterio que cumple la misma función

        $params = [
            'order' => [!empty($this->request->post('campo_sort')) ? [
                'campo' => $this->request->post('campo_sort'),
                'dir' => $this->request->post('dir')
            ] : ''],
            'start'     => '',
            'lenght'    => '',
            'filtros'   => [
                'dependencia'       => $this->request->post('dependencia_csv'),
                'fecha_desde'       => $this->request->post('fecha_desde_fecha_csv'),
                'fecha_hasta'       => $this->request->post('fecha_hasta_fecha_csv'),
                'otro_criterio'    => $this->request->post('otro_criterio_csv')
            ],

        ];
        
        $titulos = [
            'documento' => 'Documento',
            'cuit' => 'Cuit',
            'nombre' => 'Nombre',
            'apellido' => 'Apellido',
            'codep'  => 'Dependencia',
            'fecha_entrada' => 'Fecha',
            'hora_entrada' => 'Ingreso',
            'hora_egreso' => 'Egreso',
            'horas_trabajadas' => 'Horas Trabajadas',
        ];

        $titulos_agrup = [
            'documento' => 'Documento',
            'cuit' => 'Cuit',
            'nombre' => 'Nombre',
            'apellido' => 'Apellido',
            'codep'  => 'Dependencia',
            'fecha_entrada' => 'Fecha',
            'horas_trabajadas' => 'Horas Trabajadas',
            'Sumatoria Horas Trabajadas',
            'Promedio Horas Trabajadas',
        ];
        
        $data = Acceso::listar_horas_trabajadas_excel($params);
        $data_agrup = Acceso::listar_horas_trabajadas_agrup_excel($params);
        array_walk($data, function (&$value) {
            unset($value['id']);
        });

        array_walk($data_agrup, function (&$value) {
            unset($value['id']);
        });

        $usuario = $user->nombre . ' ' . $user->apellido;

        (new Vista(VISTAS_PATH . '/csv_response_horas_agrup.php', compact('nombre', 'titulos', 'titulos_agrup', 'data', 'data_agrup', 'usuario', 'fecha_desde', 'fecha_hasta')))->pre_render();
    }

    protected function accion_alta() {
       
		$this->datos();
		if ($this->acceso->credencial->enUso()) {
            $this->mensajeria->agregar("La <strong>Credencial</strong> <strong>{$this->acceso->credencial->codigo}</strong> " .
				"está asignada actualmente.",\FMT\Mensajeria::TIPO_ERROR,$this->clase,'index');
		} else {
			//if ($this->validarDocumento($this->acceso->persona->documento, 'Persona')) {                
				if ($this->acceso->enVisita()[0]) {
                    $this->mensajeria->agregar("La persona <strong>{$this->acceso->persona->nombre} {$this->acceso->persona->apellido}</strong> ya se encuentra en la" .
						" ubicación.",\FMT\Mensajeria::TIPO_ERROR,$this->clase,'index');    
				} else {
					if (($this->acceso->tipo_acceso !==  Acceso::EMPLEADO) && ($this->acceso->tipo_acceso ==  Acceso::CONTRATISTA) && is_null($this->acceso->autorizante->desde_contrato)) {
                            $this->mensajeria->agregar("El Autorizante <strong>{$this->acceso->autorizante->persona->full_name}</strong> no es un empleado con contrato activo.",\FMT\Mensajeria::TIPO_ERROR,$this->clase,'index');    
						}else{                            
							$es_tarjeta_magnetica = $this->request->post('hdNroTarjeta') != null ? 1 : 0;                         
                             if ($this->acceso->alta($es_tarjeta_magnetica)) {
                                $this->mensajeria->agregar('Acceso registrado para <strong>' . Acceso::tipoAccesoToString($this->acceso->tipo_acceso) . ' ' .
                                     "{$this->acceso->persona->nombre} {$this->acceso->persona->apellido}</strong> a las " .
                                     $this->acceso->ingreso->format('H:i:s'),\FMT\Mensajeria::TIPO_AVISO,$this->clase,'index');
                                 $this->acceso = Acceso::obtener(0);
                             } else {
                                 $err	= $this->acceso->errores;
					            foreach ($err as $text) {
						            $this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
					            }
                             }
                         }

				}
			//}
		}
		$this->mantener_info($this->acceso);
		$redirect = Vista::get_url("index.php/accesos/index");
		$this->redirect($redirect);
	}

    protected function accion_editar_observaciones() {

        $id_acceso = (int)$this->request->post('idAcceso');
		$acceso = Acceso::obtener($id_acceso);		
        $acceso->observaciones = $this->request->post('observaciones');
	
		$success = false;
		$error = null;
		$message = null;
       
		if (!empty($id_acceso) && is_numeric($id_acceso) && $id_acceso> 0) {
            
			$acceso->agregarObservaciones();
            
			if ($acceso) {
                
				$success = true;
				$message = 'Se modificaron correctamente las observaciones';
                $this->mensajeria->agregar($message,\FMT\Mensajeria::TIPO_AVISO,$this->clase,'index');
                // $redirect = Vista::get_url("index.php/accesos/index");
                // $this->redirect($redirect);
			} else {
				$error = $acceso['msj'];
                $this->mensajeria->agregar($error,\FMT\Mensajeria::TIPO_ERROR,$this->clase,'index');
			}
		} else {
			$error = 'Error para agregar observaciones';
            $this->mensajeria->agregar($error,\FMT\Mensajeria::TIPO_ERROR,$this->clase,'index');
		}

        $redirect = Vista::get_url("index.php/accesos/index");
                $this->redirect($redirect);

	}

    protected function accion_baja() {
		$success = false;
		$acceso_id = (int)$this->request->query('id');
		$error = null;
		$message = null;
        $persona_egreso = Persona::obtener($this->_user->getEmpleado($this->_user)->persona_id);
		if (!empty($acceso_id) && is_numeric($acceso_id) && $acceso_id > 0) {
			$acceso = Acceso::terminar($acceso_id, $persona_egreso, Acceso::TIPO_REGISTRO_ONLINE);
			if ($acceso['terminado']) {			
                $success = true;	
				$message = $acceso['msj'];
                $this->mensajeria->agregar($message,\FMT\Mensajeria::TIPO_AVISO,$this->clase,'index');
			} else {
				$error = $acceso['msj'];
                $this->mensajeria->agregar($error,\FMT\Mensajeria::TIPO_ERROR,$this->clase,'index');
			}
		} else {
			$error = 'No se puede terminar la Visita sin un identificador de persona';
			$this->mensajeria->agregar($error,\FMT\Mensajeria::TIPO_ERROR,$this->clase,'index');
		}
        $data = [
            'status' => $success,
            'error' => $error,
            'message' => $message,
        ];
   
        (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();

	}
    public function accion_informe_mensual()
    {
        $dependencias_select =  Direccion::listar();
        $dependencias = [];
        foreach ($dependencias_select as $value) {
            $dependencias[$value['id']] = $value['nombre'];
        }

        $contratosArray     = SituacionRevista::listarParaSelect();
        $tipo_contrato = '';

        if ($this->request->method() == 'POST') {

            if ($this->request->post('otras') == 'pdf_otras') {
                $tipo_contrato = '1'; //otras modalidades
            }
            if ($this->request->post('empleados') == 'pdf_empleados') {
                $tipo_contrato = '2'; //empleados
            }
            if ($this->request->post('ambos') == 'pdf_ambos') {
                $tipo_contrato = 'ambos';
            }

            $dependencia = !empty($temp = $this->request->post('dependencia')) ?  $temp : null;
            $mes = !empty($temp = $this->request->post('mes_fecha')) ?  $temp : null;
            $anio = !empty($temp = $this->request->post('anio_fecha')) ?  $temp : null;

            $this->accion_validar_errores($dependencia, $mes, $anio);

            $nombre_dependencia = Direccion::obtener($dependencia);
            $nombre = (!is_null($nombre_dependencia)) ? $nombre_dependencia->nombre : '';
            $fecha = $this->accion_fecha_mes($mes, $anio);

            $listado_informe_mensual = AccesoEmpleado::listar_informe_mensual($dependencia, $fecha, $tipo_contrato);

            if (!empty($listado_informe_mensual)) {
                $novedades = AccesoEmpleado::novedades_mensuales($dependencia, $fecha, $tipo_contrato);
                $vista = $this->vista;
                $file_nombre = 'informe_mensual_otras_modalidades';
                (new Vista(VISTAS_PATH . '/accesos/informe_mensual_pdf.php', compact('vista', 'file_nombre', 'listado_informe_mensual', 'novedades', 'nombre', 'fecha', 'anio', 'contratosArray', 'mes')))->pre_render();
            } else {
                $this->mensajeria->agregar("AVISO: No se encontraron datos.", \FMT\Mensajeria::TIPO_ERROR, $this->clase);
                $redirect = Vista::get_url("index.php/Accesos/informe_mensual");
                $this->redirect($redirect);
            }
        }

        $vista = $this->vista;
        (new Vista($this->vista_default, compact('vista', 'dependencias')))->pre_render();
    }

    public function accion_validar_errores($dependencia, $mes, $anio)
    {
        $error = '';

        if (is_null($dependencia)) {
            $error .= 'Debe seleccionar una Dependencia <br>';
        }
        if (is_null($mes)) {
            $error .= 'El campo de Mes no debe estar vacio <br>';
        }
        if (is_null($anio)) {
            $error .= 'El campo de Año no debe estar vacio <br>';
        }

        if (is_null($dependencia) or is_null($anio) or is_null($mes)) {
            $this->mensajeria->agregar("AVISO: " . $error, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
            $redirect = Vista::get_url("index.php/Accesos/informe_mensual");
            $this->redirect($redirect);
        }
    }

    public function accion_fecha_mes($mes, $anio)
    {

        $fecha = [];
        $fecha_d = "";
        $fecha_h = "";

        switch ($mes) {
                //Formato m-d
            case 'enero':
                $fecha_d = "01-01";
                $fecha_h = "01-31";
                break;
            case 'marzo':
                $fecha_d = "03-01";
                $fecha_h = "03-31";
                break;
            case 'mayo':
                $fecha_d = "05-01";
                $fecha_h = "05-31";
                break;
            case 'julio':
                $fecha_d = "07-01";
                $fecha_h = "07-31";
                break;
            case 'agosto':
                $fecha_d = "08-01";
                $fecha_h = "08-31";
                break;
            case 'octubre':
                $fecha_d = "10-01";
                $fecha_h = "10-31";
                break;
            case 'diciembre':
                $fecha_d = "12-01";
                $fecha_h = "12-31";
                break;
            case 'abril':
                $fecha_d = "04-01";
                $fecha_h = "04-30";
                break;
            case 'junio':
                $fecha_d = "06-01";
                $fecha_h = "06-30";
                break;
            case 'septiembre':
                $fecha_d = "09-01";
                $fecha_h = "09-30";
                break;
            case 'noviembre':
                $fecha_d = "11-01";
                $fecha_h = "11-30";
                break;
            default:
                $fecha_d = "02-01";
                $fecha_h = "02-28";
                break;
        }

        $fecha_desde = $anio . "-" . $fecha_d;
        $fecha_hasta = $anio . "-" . $fecha_h;
        $fecha = ["fecha_desde" => $fecha_desde, "fecha_hasta" => $fecha_hasta];

        return $fecha;
    }

    public function accion_planilla_reloj()
    {
        $dependencias_select =  Direccion::listar();
        $dependencias = [];
        foreach ($dependencias_select as $value) {
            $dependencias[$value['id']] = $value['nombre'];
        }

        $contratosArray     = SituacionRevista::listarParaSelect();
        $tipo_contrato = '';

        if ($this->request->method() == 'POST') {

            if ($this->request->post('otras') == 'pdf_otras') {
                $contrato_tipo = Empleado::AT;
            }
            if ($this->request->post('ley_marco') == 'pdf_ley_marco') {
                $contrato_tipo = Empleado::LEY_MARCO;
            }

            $dependencias_post = !empty($temp = $this->request->post('dependencia')) ?  $temp : null;
            $dependencias_select = (in_array(999999, $dependencias_post)) ? array_column($dependencias, 'id') : $dependencias_post;

            $fecha = !empty($temp = $this->request->post('fecha_fecha')) ? \DateTime::createFromFormat('d/m/Y', $temp)->format('Y-m-d') : null;

            if (!$this->accion_validar_errores_planilla($dependencias_post, $fecha)) {

                $nombres = '';
                foreach ($dependencias_select as $dependencia) {
                    $nombre_dependencia = Direccion::obtener($dependencia);
                    $nombres .= (!is_null($nombre_dependencia)) ? $nombre_dependencia->nombre . ' ' : '';
                }

                $listado_planilla_reloj = AccesoEmpleado::listar_unico_reloj($dependencias_select, $fecha, $contrato_tipo);

                if (!empty($listado_planilla_reloj)) {
                    // novedades
                    $adjuntar_novedades = AccesoEmpleado::adjuntar_novedades($dependencias_select, $fecha, $contrato_tipo);
                    $vista = $this->vista;
                    $file_nombre = 'planilla_reloj';
                    (new Vista(VISTAS_PATH . '/accesos/planilla_reloj_pdf.php', compact('vista', 'file_nombre', 'listado_planilla_reloj', 'adjuntar_novedades', 'nombres', 'fecha')))->pre_render();
                } else {
                    $this->mensajeria->agregar("AVISO: No se encontraron datos.", \FMT\Mensajeria::TIPO_ERROR, $this->clase);
                    $redirect = Vista::get_url("index.php/Accesos/planilla_reloj");
                    $this->redirect($redirect);
                }
            } else {
                $redirect = Vista::get_url("index.php/Accesos/planilla_reloj");
                $this->redirect($redirect);
            }
        }
        $vista = $this->vista;
        (new Vista($this->vista_default, compact('vista', 'dependencias')))->pre_render();
    }

    public function accion_validar_errores_planilla($dependencia, $fecha)
    {
        $error = '';

        if (is_null($dependencia)) {
            $error .= 'Debe seleccionar al menos una Dependencia <br>';
        }
        if (is_null($fecha)) {
            $error .= 'El campo de Fecha no debe estar vacio <br>';
        }

        if (is_null($dependencia) or is_null($fecha)) {
            $this->mensajeria->agregar("AVISO: " . $error, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
            return true;
        }

        return false;
    }
}
