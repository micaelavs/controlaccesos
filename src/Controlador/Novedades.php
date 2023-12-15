<?php

namespace App\Controlador;

use App\Modelo;
use App\Helper\Vista;
use App\Modelo\Novedad;
use App\Modelo\Usuario;

class Novedades extends Base {

	private $acceso;

	protected function accion_index() {

        /**/
        $datos = Modelo\LocacionesApi::getListadoEdificios();
        //$oficinas = Modelo\LocacionesApi::getListadoOficinas(1);
        //$datos = Modelo\LocacionesApi::getListadoOficinas(6);

        echo '<pre>';
        var_dump($datos); 
        echo '</pre>'; 
        
       




        /**/

       	$lista_tipo_novedades	=  Novedad::tipoAusencias();
       	$lista_novedades_aux = [];
       	foreach ($lista_tipo_novedades as $value) {
            $lista_novedades_aux[$value['id']] = ['id' => $value['id'], 'nombre' => $value['nombre'], 'borrado' => 0];
         
        }  
   
        $vista = $this->vista;
        (new Vista($this->vista_default, compact('vista', 'lista_novedades_aux')))->pre_render();
	}

	protected function accion_ajax_novedades(){

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
                'tipo_novedad'  => $this->request->query('tipo_novedad_filtro'),
                'fecha_desde'   => $this->request->query('fecha_desde_filtro'),
                'fecha_hasta'   => $this->request->query('fecha_hasta_filtro'),
                'dependencias' => $this->_user->dependencias
            ],
        ];

        $data =  Novedad::listar_Novedades($params);
        $datos['draw']    = (int) $this->request->query('draw');
        (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();


    }

    protected function accion_exportar_excel() {
        $nombre = 'Novedades'.date('Ymd_His');

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
                'tipo_novedad'          => $this->request->post('tipo_novedad'),
                'fecha_desde'           => $this->request->post('fecha_desde'), 
                'fecha_hasta'           => $this->request->post('fecha_hasta')
            ],
        ];
     
        $titulos = [
         
            'fecha_desde'	=>'Fecha Desde',
            'fecha_hasta'   =>'Fecha Hasta',
            'documento'		=> 'Documento',
            'nombre'		=> 'Nombre',
            'tipo_novedad'	=> 'Tipo Novedad',
        ];

        $data = Novedad::listar_novedades_excel($params);

        array_walk($data, function (&$value) {
            unset($value['id']);
        
        });

        (new Vista(VISTAS_PATH.'/csv_response.php',compact('nombre', 'titulos', 'data')))->render();
    }

    protected function accion_ajax_ultimas_siete(){
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
                'dni'  => $this->request->query('dni'),
                'dni_hidden' => $this->request->query('dni_hidden')
               
            ],
        ];

        $data =  Novedad::listar_ultimas_siete($params);
        $datos['draw']    = (int) $this->request->query('draw');
        (new Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
    }

     protected function accion_exportar_ultimas_siete_excel() {

        $nombre = 'Ultimas_Novedades'.date('Ymd_His');

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
                'dni'          => $this->request->post('dni')
            ],
        ];
     
        $titulos = [
         
            'fecha_desde'	=>'Fecha Desde',
            'fecha_hasta'   =>'Fecha Hasta',
            'tipo_novedad'	=> 'Tipo Novedad'
        ];

        $data = Novedad::listar_ultimas_siete_excel($params);

        array_walk($data, function (&$value) {
            unset($value['id']);
        
        });
       
        (new Vista(VISTAS_PATH.'/csv_response.php',compact('nombre', 'titulos', 'data')))->render();
    }	
  
    protected function accion_actualizarTipoNovedad(){
        if($this->request->is_ajax()){
            $data = $this->_get_tipo_novedad();
            $this->json->setData($data);
            $this->json->render();
            exit;
        }
    }

    private function _get_tipo_novedad(){
    	$lista_tipo_novedades	=  Novedad::tipoAusencias();
		$lista_novedades_aux = [];
       	foreach ($lista_tipo_novedades as $value) {
            $lista_novedades_aux[$value['id']] = ['id' => $value['id'], 'nombre' => $value['nombre'], 'borrado' => 0];
         
        }  
        
        return $lista_novedades_aux;
    }

	public function  accion_alta(){
        
		$lista_tipo_novedades	=  Novedad::tipoAusencias();
		$lista_novedades_aux = [];
       	foreach ($lista_tipo_novedades as $value) {
            $lista_novedades_aux[$value['id']] = ['id' => $value['id'], 'nombre' => $value['nombre'], 'borrado' => 0];
         
        }  
		
		$novedad =  Novedad::obtener($this->request->query('id'));

        if ($this->request->post('boton_novedades') == 'alta') {

	        $dni = !empty($this->request->post('dni')) ? $this->request->post('dni') : null;
			$empleado =  Modelo\Empleado::obtenerPorDocumento($dni);
			$novedad->empleado = $empleado;
			$novedad->tipo_novedad = !empty($temp = $this->request->post('tipo_novedad')) ?  $temp : null;
			
			$hora_desde = !empty($this->request->post('hora_inicio')) ? $this->request->post('hora_inicio') : '00:00';
			
			$fecha_desde = !empty($this->request->post('fecha_desde')) ? \DateTime::createFromFormat("d/m/Y H:i", $this->request->post('fecha_desde').''.$hora_desde) : null;

			$hora_hasta = !empty($this->request->post('hora_fin')) ? $this->request->post('hora_fin') : '00:00';
			
			$fecha_hasta = !empty($this->request->post('fecha_hasta')) ? \DateTime::createFromFormat("d/m/Y H:i", $this->request->post('fecha_hasta').''.$hora_hasta) : $fecha_desde;

			
			$novedad->fecha_desde = $fecha_desde;
           	$novedad->fecha_hasta = $fecha_hasta;
           	$novedad->usuario = $this->_user;
        	$nombre_empleado = $novedad->empleado->nombre. ' '. $novedad->empleado->apellido;
        	
            if ($novedad->validar()) {
                $resultado = $novedad->alta();
                if ($resultado > 0) {
                    $this->mensajeria->agregar(
                        "AVISO:La Novedad fué ingresada de forma exitosa.",
                        \FMT\Mensajeria::TIPO_AVISO,
                        $this->clase
                    );

                    if ($novedad->tipo_novedad == Novedad::TIPO_COMISION_HORARIA){
						$acceso = Modelo\Acceso::obtener(0);
						$persona_id = $novedad->empleado->persona_id;
						$persona = Modelo\Persona::obtener($persona_id);

						$acceso->persona =  $persona;
						$acceso->empleado = $novedad->empleado;
						$acceso->autorizante = $novedad->empleado;	
						$ubicacion_user = Modelo\Ubicacion::obtener($this->_user->getEmpleado()->ubicacion);
						$acceso->ubicacion = $ubicacion_user;
						//$autorizante_documento = $this->request->post('dni'); esto estaba en el original, pero no se usa en ningunlado
						$acceso->tipo_ingreso = Modelo\Acceso::TIPO_COMISION_HORARIA;
						$acceso->tipo_egreso = Modelo\Acceso::TIPO_COMISION_HORARIA;
						$acceso->tipo_acceso = Modelo\Acceso::EMPLEADO;
						$persona_user = Modelo\Persona::obtener($this->_user->getEmpleado()->persona_id);
						$acceso->persona_ingreso = $persona_user;
						$acceso->persona_egreso = $persona_user;	
						$acceso->ingreso = $novedad->fecha_desde;
						$acceso->egreso = $novedad->fecha_hasta;
						$acceso->hora_ingreso = $novedad->fecha_desde->format('H:i:s');
						$acceso->hora_egreso = $novedad->fecha_hasta->format('H:i:s');
						$acceso->observaciones = 'Comisión horaria';
					
						$set_hora = true;

						//agrego registro en accesoEmpleado
						$acceso_empleado = Modelo\AccesoEmpleado::obtener(0);
						$acceso_empleado->empleado_id = $acceso->empleado->id;
						
						$acceso_empleado->alta();
						
						$acceso->tipo_id = $acceso_empleado->id;
						$acceso->tipo_modelo = $acceso_empleado->tipo_acceso;
						
						$acceso->registrarAcceso($set_hora);

						$this->mensajeria->agregar(
                        "AVISO:Se dió de alta una novedad del Empleado <strong>{$nombre_empleado}</strong> y se actualizó el registro en el Acceso.",
                        \FMT\Mensajeria::TIPO_AVISO,
                        $this->clase
                    	);
						
					}

					$redirect = Vista::get_url("index.php/novedades/index");
                    $this->redirect($redirect);
                   
                } else {
                    $this->mensajeria->agregar(
                        "ERROR: Hubo un error en el alta de Novedad.",
                        \FMT\Mensajeria::TIPO_ERROR,
                        $this->clase
                    );
                    $redirect = Vista::get_url("index.php/novedades/index");
                    $this->redirect($redirect);
                }
            } else {
                $err    = $novedad->errores;
                foreach ($err as $text) {
                    $this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
                }
            }
        }

        $vista = $this->vista;
        (new Vista($this->vista_default, compact('vista', 'lista_novedades_aux', 'novedad')))->pre_render();
	}

	  protected function accion_buscarEmpleado(){
	  	$datos = [];
	  	$contratos = Modelo\SituacionRevista::listarParaSelect();
       	
       	if($this->request->is_ajax()){

        	Modelo\SituacionRevista::setAutenticacion();
			$tipo_contrato	= Modelo\SituacionRevista::listarParaSelect();
        	$empleado = Modelo\Empleado::obtenerPorDocumento($this->request->post('dni'));
        	if(empty($empleado->id)){
        			$datos[] = ['error'=> 'No se encontró ningún Empleado con el documento ingresado.'];
        	}else if(!array_key_exists($empleado->id_tipo_contrato, $tipo_contrato)) {
        			$contrato = $contratos[$empleado->id_tipo_contrato]['nombre'];
        			$datos[] = ['error'=> 'No puede generar novedades para Empleados con tipo de contrato: '. $contrato];
        	}else{
        		$nombre_apellido = $empleado->nombre.' '. $empleado->apellido;
        		$datos[] = ['nombre' => $nombre_apellido ];
        	}
    		
           $this->json->setData($datos);
           $this->json->render();

        }


    }

	public function  accion_modificacion(){
		$lista_tipo_novedades	=  Novedad::tipoAusencias();
		$lista_novedades_aux = [];
       	foreach ($lista_tipo_novedades as $value) {
            $lista_novedades_aux[$value['id']] = ['id' => $value['id'], 'nombre' => $value['nombre'], 'borrado' => 0];
         
        }

      	$novedad =  Novedad::obtener($this->request->query('id'));
   
        if ($this->request->post('boton_novedades') == 'modificacion') { 
          	$dni = !empty($this->request->post('dni_agente')) ? $this->request->post('dni_agente') : null;
			$empleado =  Modelo\Empleado::obtenerPorDocumento($dni);

			$novedad->empleado = $empleado;
			$novedad->tipo_novedad = !empty($temp = $this->request->post('tipo_novedad')) ?  $temp : null;
			
			$hora_desde = !empty($this->request->post('hora_inicio')) ? $this->request->post('hora_inicio') : '00:00';
			
			$fecha_desde = !empty($this->request->post('fecha_desde')) ? \DateTime::createFromFormat("d/m/Y H:i", $this->request->post('fecha_desde').''.$hora_desde) : null;

			$hora_hasta = !empty($this->request->post('hora_fin')) ? $this->request->post('hora_fin') : '00:00';
			
			$fecha_hasta = !empty($this->request->post('fecha_hasta')) ? \DateTime::createFromFormat("d/m/Y H:i", $this->request->post('fecha_hasta').''.$hora_hasta) : $fecha_desde;
			
			$novedad->fecha_desde = $fecha_desde;
           	$novedad->fecha_hasta = $fecha_hasta;
           	$novedad->usuario = $this->_user;
        	$nombre_empleado = $novedad->empleado->nombre. ' '. $novedad->empleado->apellido;
       	
            if ($novedad->validar()) {
                $resultado =$novedad->modificacion();
                if($resultado){
                	//cargo los campos que se modifican en acceso
					$acceso = Modelo\Acceso::obtener('0');
					$acceso->hora_ingreso = $novedad->fecha_desde;
		            $acceso->hora_egreso = $novedad->fecha_hasta;
		            $id_acceso = $acceso->obtener_id_acceso();

		            $acceso = Modelo\Acceso::obtener($id_acceso);
					$acceso->ingreso = $novedad->fecha_desde;
		            $acceso->egreso = $novedad->fecha_hasta;
					$acceso->modificacion();

					$this->mensajeria->agregar(
                        "AVISO:Se modificó una novedad para el Empleado <strong>{$nombre_empleado}</strong>.",
                        \FMT\Mensajeria::TIPO_AVISO,
                        $this->clase
                    );

                    $redirect = Vista::get_url("index.php/novedades/index");
                    $this->redirect($redirect);

                }else{
                	 $this->mensajeria->agregar(
                        "ERROR: Hubo un error en la modificación de Novedad.",
                        \FMT\Mensajeria::TIPO_ERROR,
                        $this->clase
                    );
                    $redirect = Vista::get_url("index.php/novedades/index");
                    $this->redirect($redirect);
                }
	          
            } else {
                $err    = $novedad->errores;
                foreach ($err as $text) {
                    $this->mensajeria->agregar($text, \FMT\Mensajeria::TIPO_ERROR, $this->clase);
                }
            }
        }

        $vista = $this->vista;
        (new Vista($this->vista_default, compact('vista', 'lista_novedades_aux', 'novedad')))->pre_render();
    }

	public function accion_baja(){

		$lista_tipo_novedades	=  Novedad::tipoAusencias();
		$lista_novedades_aux = [];
       	foreach ($lista_tipo_novedades as $value) {
            $lista_novedades_aux[$value['id']] = ['id' => $value['id'], 'nombre' => $value['nombre'], 'borrado' => 0];
         
        }  

	    $novedad = Novedad::obtener($this->request->query('id'));
	    $nombre_empleado = $novedad->empleado->nombre. ' '.$novedad->empleado->apellido;
		
		 //pasamos el acceso a la vista intermedia para luego recuperarlo en el post
		$acceso = Modelo\Acceso::obtener(0);
		$acceso->hora_ingreso = $novedad->fecha_desde;
		$acceso->hora_egreso = $novedad->fecha_hasta;
		$id_acceso = $acceso->obtener_id_acceso();
	
        if ($novedad->id) {
            if ($this->request->post('confirmar')) {
           		
           		$this->acceso = $this->request->post('acceso') ? $this->request->post('acceso') : $this->acceso;
      			$acceso = Modelo\Acceso::obtener($this->acceso);
                $result = $novedad->baja();
                if($result){
                	if(!empty($acceso)){
						if($acceso->alta_log_accesos()){
							$acceso->baja_acceso(); 
						}else{
						$this->mensajeria->agregar('AVISO:No se ha podido eliminar el acceso del Empleado', \FMT\Mensajeria::TIPO_ERROR, $this->clase, 'index');

					}
				}

                $this->mensajeria->agregar("AVISO: La Novedad del Empleado <strong> {$nombre_empleado}</strong>  se ha dado de baja exitosamente.", \FMT\Mensajeria::TIPO_AVISO, $this->clase, 'index');
                $redirect = Vista::get_url('index.php/novedades/index');
                $this->redirect($redirect);
                }else{
                    $this->mensajeria->agregar('AVISO:No se ha podido eliminar la Novedad', \FMT\Mensajeria::TIPO_ERROR, $this->clase, 'index');
                    $redirect = Vista::get_url('index.php/novedades/index');
                    $this->redirect($redirect);
                }
            }

         
        } else {
            $redirect = Vista::get_url('index.php/novedades/index');
            $this->redirect($redirect);
        }

    
        $vista = $this->vista;
        (new Vista($this->vista_default, compact('novedad','vista', 'id_acceso', 'lista_novedades_aux', 'nombre_empleado')))->pre_render();
    }

}