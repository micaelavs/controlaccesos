<?php
namespace App\Controlador;
use App\Modelo;
use App\Helper;

class Usuarios extends Base {


	protected function accion_index() {
		$usuarios = Modelo\Usuario::listar();
		(new Helper\Vista($this->vista_default,['usuarios' => $usuarios,'vista' => $this->vista ]))
			->pre_render();
	}

	protected function accion_alta() {
		$usuario = Modelo\Usuario::obtener($this->request->post('username'));
		if( $this->request->post('buscar')) {
			if(!$usuario->id) {
				$this->mensajeria->agregar("El nombre de usuario <strong>{$this->request->post('username')}</strong> no existe",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
			}

			if( $usuario->rol_id != 0) {
				$this->mensajeria->agregar("El nombre de usuario <strong>{$usuario->username}</strong> ya tiene el rol  \"<strong>{$usuario->rol_nombre}</strong>\"",\FMT\Mensajeria::TIPO_AVISO,$this->clase);
				$redirect = Helper\Vista::get_url('index.php/usuarios/index');
				$this->redirect($redirect);
			}
		}

		if($this->request->post('guardar')) {
			$usuario->rol_id	= $this->request->post('rol');
			$usuario->dependencias	= $this->request->post('dependencias');
			if ($usuario->validar()) {
				if ($usuario->alta()) {		
					$empleado = Modelo\Empleado::obtenerPorDocumento($this->request->post('empleado_documento'));
					$usuario->asociarEmpleado($empleado);			
					$this->mensajeria->agregar('AVISO: Se dió de alta de forma exitosa un nuevo usuario.',\FMT\Mensajeria::TIPO_AVISO,$this->clase,'index');
					$redirect = Helper\Vista::get_url('index.php/usuarios/index');
					$this->redirect($redirect);
				} else {
					$this->mensajeria->agregar('ERROR: Hubo un error en el alta.',\FMT\Mensajeria::TIPO_ERROR,$this->clase,'index');
					$redirect = Helper\Vista::get_url('index.php/usuarios/index');
					$this->redirect($redirect);
				}
			} else {
				foreach ($usuario->errores as $value) {
					$this->mensajeria->agregar($value ,\FMT\Mensajeria::TIPO_ERROR,$this->clase,$this->accion);
				}
			}
		}
		$usuario->rol_id = $this->request->post('rol');
		$roles = \App\Modelo\AppRoles::obtener_listado();
		$vista = $this->vista;
		$operacion = 'Alta';
		$dependencias_select =  Modelo\Direccion::listar();
		$dependencias = [];
		foreach ($dependencias_select as $value) {
			$dependencias[$value['id']] = $value['nombre'];
		}
		(new Helper\Vista($this->vista_default,compact('usuario', 'vista', 'roles', 'operacion','dependencias')))->pre_render();
	}

	protected function accion_modificar(){
		$usuario = Modelo\Usuario::obtener($this->request->query('id'));
		$empleado_usuario = $usuario->getEmpleado();
		$ubicacion = Modelo\Ubicacion::obtener($empleado_usuario->ubicacion);

		$empleado_usuario_fullname = $empleado_usuario->nombre . ' ' . $empleado_usuario->apellido;
		$empleado_usuario_dni = $empleado_usuario->documento;
		$ubicacion_nombre = $ubicacion->nombre;

		if($usuario->id) {
			$usuario->rol_id = !empty($temp = $this->request->post('rol')) ? $temp : $usuario->rol_id;
			
			if($this->request->post('guardar')) {
				$usuario->dependencias	= $this->request->post('dependencias');
				if ($usuario->validar()) {
					if ($usuario->modificacion()) {
						$empleado = Modelo\Empleado::obtenerPorDocumento($this->request->post('empleado_documento'));
						$usuario->asociarEmpleado($empleado);
						$this->mensajeria->agregar('AVISO: Se modificó de forma exitosa el usuario.',\FMT\Mensajeria::TIPO_AVISO,$this->clase,'index');
						$redirect = Helper\Vista::get_url('index.php/usuarios/index');
						$this->redirect($redirect);
					} else {
						$this->mensajeria->agregar('ERROR: Hubo un error en la modificación.',\FMT\Mensajeria::TIPO_ERROR,$this->clase,'index');
						$redirect = Helper\Vista::get_url('index.php/usuarios/index');
						$this->redirect($redirect);
					}
				} else {

					foreach ($usuario->errores as $value) {
						$this->mensajeria->agregar($value ,\FMT\Mensajeria::TIPO_ERROR,$this->clase,$this->accion);
					}
				}
			}
		} else {
			$this->mensajeria->agregar("El usuario que intenta modificar no existe.",\FMT\Mensajeria::TIPO_ERROR,$this->clase);
			$redirect = Helper\Vista::get_url('index.php/usuarios/index');
			$this->redirect($redirect);
		}

		$roles = \App\Modelo\AppRoles::obtener_listado();
		$vista = $this->vista;
		$operacion = 'Modificación';
		$dependencias_select =  Modelo\Direccion::listar();
		$dependencias = [];
		foreach ($dependencias_select as $value) {
			$dependencias[$value['id']] = $value['nombre'];
		}
		(new Helper\Vista(VISTAS_PATH.'/usuarios/alta.php',compact('usuario', 'vista', 'roles', 'operacion', 'empleado_usuario_fullname', 'ubicacion_nombre' , 'empleado_usuario_dni','dependencias')))->pre_render();
	}

	protected function accion_baja() {
		$usuario = Modelo\Usuario::obtener($this->request->query('id'));
		if($usuario->id) {
			if ($this->request->post('confirmar')) {
				$res = $usuario->baja();
				if ($res) {
					$this->mensajeria->agregar('AVISO: Se eliminó un usuario de forma exitosa.',\FMT\Mensajeria::TIPO_AVISO,$this->clase,'index');
					$redirect = Helper\Vista::get_url('index.php/usuarios/index');
					$this->redirect($redirect);
				}
			}
		} else {
			$redirect = Helper\Vista::get_url('index.php/usuarios/index');
			$this->redirect($redirect);
		}

		$vista = $this->vista;
		(new Helper\Vista($this->vista_default,compact('usuario', 'vista')))->pre_render();
	}

    protected function accion_ajax_usuarios() {
        $dataTable_columns	= $this->request->query('columns');
        $orders	= [];
        foreach($orden = (array)$this->request->query('order') as $i => $val){
            $orders[]	= [
                'campo'	=> (!empty($tmp = $orden[$i]) && !empty($dataTable_columns) && is_array($dataTable_columns[0]))
                        ? $dataTable_columns[ (int)$tmp['column'] ]['data']	:	'id',
                'dir'	=> !empty($tmp = $orden[$i]['dir'])
                        ? $tmp	:	'desc',
            ];
        }
        $date  = [];
        if( preg_match('/^\d{2}\/\d{2}\/\d{4}/', $this->request->query('search')['value'],$date)){
            $el_resto = \preg_replace('/^\d{2}\/\d{2}\/\d{4}/','', $this->request->query('search')['value']);
            $search = \DateTime::createFromFormat('d/m/Y',$date[0])->format('Y-m-d').$el_resto;
        }else {
            $search = $this->request->query('search')['value'];
        }
        $params	= [
            'order'		=> $orders,
            'start'		=> !empty($tmp =$this->request->query('start'))
                        ? $tmp : 0,
            'lenght'	=> !empty($tmp = $this->request->query('length'))
                        ? $tmp : 10,
            'search'	=> !empty($search)
                        ? $search : '',
            'filtros'   => [

            ],
        ];

        $data =  \App\Modelo\Usuario::listar_usuarios($params);
        $datos['draw']	= (int) $this->request->query('draw');
        (new Helper\Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
    }
	
	public function accion_buscarAutorizanteAjax()
	{
		if (isset($_POST['empleado_documento'])) {
			$empleado =  Modelo\Empleado::obtenerPorDocumento($this->request->post('empleado_documento'));
			if ($this->request->post('empleado_documento') != '') {
				if (is_null($empleado->id)) {
					$data = [
						'dato' => null,
						'ubicacion' => null,
						'msj' => 'No hay un registro de Empleado con Documento: ' . $this->request->post('empleado_documento')
					];
				} else {
					$ubicacion = Modelo\Ubicacion::obtener($empleado->ubicacion);
					$data = [
						'dato' => $empleado,
						'ubicacion' => $ubicacion->nombre,
						'msj' => "Se encontró el Empleado " . "{$empleado->nombre} {$empleado->apellido} - " . "Documento: {$empleado->documento}",
					];
				}
			} else {
				$data = [
					'dato' => null,
					'ubicacion' => null,
					'msj' => 'El Documento del Empleado es necesario para realizar la búsqueda'
				];
			}
			(new Helper\Vista(VISTAS_PATH . '/json_response.php', compact('data')))->pre_render();
		} else {
			echo 'Error variable POST';
		}
	}
}