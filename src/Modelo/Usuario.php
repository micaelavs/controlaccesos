<?php
namespace App\Modelo;

use FMT\Logger;
use FMT\Usuarios;
use App\Helper\Validador;
use App\Modelo\AppRoles;
use App\Helper\Conexiones;

/**
 * Class Usuario
 */
class Usuario extends \FMT\Modelo {
	/** @var int */
	public $id = 0;
	/** @var string */
	public $username = null;
	/** @var string */
	public $nombre = null;
	/** @var string */
	public $apellido = null;
	/** @var string */
	public $email = null;
	/** @var integer */
	public $rol_id;
	public $rol_nombre;
	protected static $cache = [];
	/** @var Ubicacion */
	public $ubicacion = null;
	public $dependencias = [];

	/**
	 * Regresa lista de usuarios
	 * @return array
	 */
	public static function listar() {
		$config				= \FMT\Configuracion::instancia();
		$lista 				= Usuarios::getUsuarios();
		$roles_permitidos	= \FMT\Helper\Arr::get(AppRoles::$rol,'roles_permitidos',[]);
		foreach( $lista as &$usuario) {
			$usuario		= (object) $usuario;
			$usuario->rol_id= $usuario->permiso;
			if (empty($roles_permitidos) || in_array($usuario->rol_id , $roles_permitidos)) {
				$usuario->rol_nombre		= AppRoles::$permisos[$usuario->rol_id]['nombre'];
				$meta						= Usuarios::getMetadata($usuario->idUsuario);
				$usuario->metadata			= (!is_null($meta['metadata'])) ? json_decode($meta['metadata'],1) : [];
				$usuario->metadata_nombre	= $config['metadata_nombre'];
				if ($usuario->rol_id == AppRoles::RCA){
					$dependencias = (!empty($usuario->metadata)) ?array_column($usuario->metadata,'dependencia') : [];
					foreach($dependencias as $dependencia){
						$depen = Direccion::obtener($dependencia);
						$usuario->dependencias .= ''. $depen->nombre .' - '; 
						
					}
				}else{
					
					$usuario->dependencias = '-';
				}
			} else {
				unset($usuario);
			}
		}
		
		return $lista;
	}

	/**
	 * @param int|string $user_id
	 * @return Usuario
	 */
	public static function obtener($user_id = 0) {
		$usuario	= new static();
		if (!empty($user_id)) {
			if (isset(static::$cache[$user_id])) {
				$usuario	= static::$cache[$user_id];
			} else {
				$user	= Usuarios::getUsuario($user_id);
				if (isset($user['idUsuario'])) {
					$usuario		= new static();
					$usuario->id	= (int)$user['idUsuario'];
					$usuario->rol_id= Usuarios::getPermiso($usuario->id)['permiso'];
					if (empty($usuario->rol_id)) {
						$usuario->rol_id	= 0;
					}
					$usuario->rol_nombre	= AppRoles::$permisos[$usuario->rol_id]['nombre'];
					$usuario->username		= isset($user['user']) ? (string)$user['user'] : null;
					$usuario->nombre		= isset($user['nombre']) ? (string)$user['nombre'] : null;
					$usuario->apellido		= isset($user['apellido']) ? (string)$user['apellido'] : null;
					$usuario->email			= isset($user['email']) ? (string)$user['email'] : null;
					$usuario->ubicacion = Ubicacion::obtener(isset($_SESSION['id_ubicacion_actual']) ? $_SESSION['id_ubicacion_actual'] : 0);
					$dependencias = [];
					$meta = Usuarios::getMetadata($user_id);
					if(!is_null($meta['metadata'])) {
						$dependencias = json_decode($meta['metadata'],1);
						$dependencias = (!empty($dependencias)) ?array_column($dependencias,'dependencia') : [];
					}
					$usuario->dependencias = $dependencias;
					static::$cache[$user_id]= $usuario;
				}
				
			}
		}

		return $usuario;
	}

	/**
	 * @return Usuario
	 */
	
	public static function obtenerUsuarioLogueado() {
		if (isset($_SESSION['iu']) && is_numeric($_SESSION['iu'])) {
			return static::obtener($_SESSION['iu']);
		} else {
			return null;
		}
	}

	public function fullName() {
		return trim("{$this->nombre} {$this->apellido}");
	}

	public function modificacion() {
		$rta	= false;
		$operacion = 'M';
		if($this->id) {
			if (Usuarios::getPermiso($this->id) != $this->rol_id) {
				Usuarios::eliminarMetadata($this->id);
			}
			Usuarios::setPermiso($this->id, $this->rol_id);
			if (!empty($this->dependencias)) {
				foreach ($this->dependencias as $dependencia) {
				  $dependencias[] = ['dependencia' => $dependencia]; 
			    }
		  
				Usuarios::setMetadata($this->id, json_encode($dependencias));
			}
			
			$rta	= true;
		}
		if($rta){
			//static::log_usuarios($this, $operacion);
		}	
		return $rta;
	}

	public function validar(){  
		$rules	= [
			'username'   => ['required'],
			'rol_id'     => ['integer'],
			'dependencias' => ['validar_dependencia' => function ($input) {
				
				if($this->rol_id == AppRoles::RCA) {
				   if(is_null($this->dependencias)){
				      return false;
				   }else{
				   		return true;
				   }
			 	}else {
				   return true;
			}
		 }],
		];
//		$nombres	= ['metadata'	=> 'Area']; 	
		$validacion = Validador::validate((array)$this, $rules);
		$validacion->customErrors([
			'required'      => 'Campo :attribute es requerido',
			'requerido'     => 'Campo :attribute es requerido',
			'validar_dependencia' => "Campo dependencia es obligatorio"
		]);
		if ($validacion->isSuccess() == true) {
		  return true;
		} else {
			$this->errores	= $validacion->getErrors();
			return false;
		}  
	}

	/**
	 * Elimina el permiso del Usuario en la API y elimina la relación entre el usuario y el empleado.
	 * @return bool
	 */
	public function baja() {
		$rta	= false;
		if (!empty($this->id)) {
			Usuarios::eliminarPermiso($this->id);
			Usuarios::eliminarMetadata($this->id);
			$rta	= true;
			//static::log_usuarios($this,'B');
		}
		return $rta;
	}

	public function alta() {
		$rta	= false;
		$operacion = 'A';
		if($this->id) {
			Usuarios::getPermiso($this->id);
			Usuarios::setPermiso($this->id, $this->rol_id);
			
			$rta	= true;
			if (!empty($this->dependencias)) {
                                foreach ($this->dependencias as $dependencia) {
                                  $dependencias[] = ['dependencia' => $dependencia];
                            }

                                Usuarios::setMetadata($this->id, json_encode($dependencias));
                        }

		}
		
		if($rta){
		//	static::log_usuarios($this,$operacion);
		}	
		return $rta;
	}

	/**
	 * Regresa lista de usuarios administradores
	 * @return array
	 */
	public static function listar_administradores() {
		$config				= \FMT\Configuracion::instancia();
		$lista 				= Usuarios::getUsuarios();
		foreach( $lista as $key => $usuario) {
			$usuario		= (object) $usuario;
			$usuario->rol_id= $usuario->permiso;
			//if ($usuario->rol_id == AppRoles::ROL_ADMINISTRACION) {
			if ($usuario->rol_id == AppRoles::ADMINISTRADOR_CIET) {				
				$usuario->rol_nombre = AppRoles::$permisos[$usuario->rol_id]['nombre'];
				$lista[$key] = $usuario;
			} else {
				unset($lista[$key]);
			}
		}
		return $lista;
	}	
	
	
	public static function log_usuarios($data, $operacion){
		return false;
		//$metadata = $operacion != 'B' ? $data->metadata : $data->metadata[0]['area'];
		$campos	= [
			'id_usuario',
			'fecha_operacion',
			'tipo_operacion',
			'id_usuario_panel',
			'id_rol',
			'username',
			'metadata'
		];

		$sql_params	= [
			':id_usuario'		=> $data->id,
			':fecha_operacion'	=> date_format(date_create('now'), 'Y-m-d H:i:s'),
			':tipo_operacion'	=> $operacion,
			':id_usuario_panel'	=> static::obtenerUsuarioLogueado()->id,
			':id_rol'			=> $data->rol_id,
			':username'			=> $data->username,
			':metadata'			=> '',
		];

		$sql	= 'INSERT INTO usuarios('.implode(',', $campos).') VALUES (:'.implode(',:', $campos).')';
		$res	= (new Conexiones('db_log'))->consulta(Conexiones::INSERT, $sql, $sql_params);
		if($res !== false){
			$data_log	= [
				'id_usuario'		=> $data->id,
				'fecha_operacion'	=> gmdate('Y-m-d'),
				'tipo_operacion'	=> $operacion,
				'id_usuario_panel'	=> static::obtenerUsuarioLogueado()->id,
				'id_rol'			=> $data->rol_id,
				'username'			=> $data->username,
				'metadata'			=> '',
			];
			$datos = $data_log;
			$datos['modelo'] = 'Usuario';
			Logger::event('alta', $datos);
		}
		return $res;
	}


    public static function listar_usuarios($params=array()){
		$sql_params = [];
		$config	= \FMT\Configuracion::instancia();
        $campos	= 'id,nombre_usuario,usuario,rol';
     
        $params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo']) ) ? 'tipo' :$params['order']['campo']; 
        $params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']) )   ? 'asc' :$params['order']['dir'];
        $params['start']  = (!isset($params['start'])  || empty($params['start']) )  ? 0 :$params['start'];
        $params['lenght'] = (!isset($params['lenght']) || empty($params['lenght']) ) ? 10 :$params['lenght'];
        $params['search'] = (!isset($params['search']) || empty($params['search']) ) ? '' :$params['search'];
		$aux = '';
		foreach (AppRoles::obtener_lista_roles_permitidos()	 as $id => $nombre) {
			$aux .= ($aux) ? " UNION ALL SELECT {$id},'{$nombre}'" 
						  : " select {$id} id,'{$nombre}' rol";
		}
		$consulta =<<<SQL
			SELECT u.idUsuario id,CONCAT(u.nombre,' ',u.apellido) nombre_usuario,u.user usuario,lr.rol FROM {$config['database']['db_panel']['database']}.usuarios u  
			INNER JOIN {$config['database']['db_panel']['database']}.permisos p ON u.idUsuario = p.idUsuario

			LEFT JOIN ({$aux}) lr ON p.permiso = lr.id
			WHERE p.idModulo={$config['app']['modulo']} AND u.estado =1
SQL;
 
		 $data = \App\Modelo\Modelo::listadoAjax($campos, $consulta, $params, $sql_params,['{{{REPLACE}}}'=>'*']);
		 return $data;
	}	


	public function setEmpleado(Empleado $empleado) {
		$this->empleado = $empleado;
	}


	/**
	 * @param int|array $permisos
	 * @return bool
	 */
	public function tienePermiso($permisos) {
		if (is_array($permisos)) {
			foreach ($permisos as $permiso) {
				if ($this->permiso == $permiso) {
					return true;
				}
			}
		} else if (is_numeric($permisos)) {
			return $this->permiso == $permisos;
		}

		return false;
	}

	public function asociarEmpleado($empleado) {
		if (!empty($this->id) && !empty($empleado) && !empty($empleado->id)) {
			$conex = new Conexiones();
			$params = [':ide' => $empleado->id, ':idu' => $this->id,];
			$tipoSQL = Conexiones::SELECT;
			$sql = <<<SQL
				SELECT
					usuario_id,
					empleado_id,
					estado
				FROM empleado_x_usuario
				WHERE empleado_id = :ide OR usuario_id = :idu;
				SQL;
			$res = $conex->consulta($tipoSQL, $sql, $params);
			$existe = false;
			if (!empty($res)) {
				$sql1 = "UPDATE empleado_x_usuario SET estado = 0 WHERE usuario_id = :idu OR empleado_id = :ide;";
				$conex->consulta(Conexiones::UPDATE, $sql1, $params);
				foreach ($res as $re) {
					if ($re['usuario_id'] == $this->id && $re['empleado_id'] == $empleado->id) {
						$tipoSQL = Conexiones::UPDATE;
						$sql = "UPDATE empleado_x_usuario SET estado = 1 WHERE usuario_id = :idu AND empleado_id = :ide;";
						$existe = true;
						break;
					}
				}
			}
			if (!$existe) {
				$tipoSQL = Conexiones::INSERT;
				$sql = "INSERT INTO empleado_x_usuario (usuario_id, empleado_id, estado) VALUE (:idu, :ide, 1);";
			}
			$res = $conex->consulta($tipoSQL, $sql, $params);
			if (is_numeric($res) && $res >= 0) {
				//Log
				// $datos = (array)$this;
				// $datos['modelo'] = 'usuario';
				// Logger::event('asocia_usuario-empleado', $datos);

				return true;
			}
		}

		return false;
	}

	/**
	 * @return Empleado
	 */
	public function getEmpleado($usuario = null) {

		$usuario  = empty($usuario) ? $this : $usuario;
		if (!empty($usuario->id) && is_numeric($usuario->id) && $usuario->id > 0) {
			$sql = "SELECT empleado_id FROM empleado_x_usuario WHERE usuario_id = :uid AND estado = 1;";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::SELECT, $sql, [':uid' => $usuario->id]);
			if (!empty($res) && isset($res[0])) {
				$return = Empleado::obtener($res[0]['empleado_id']);
				return $return;
			}
		}

		return Empleado::obtener(0);
	}

}
