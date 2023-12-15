<?php
namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Validador;
use FMT\Logger;
use FMT\Modelo;
use App\Helper\Email;
use FMT\Configuracion;


class AlertaReloj extends Modelo {
	/** @var int */
	public $id;
	/** @var Empleado $empleado*/
	public $empleado;
	/** @var int */
	public $borrado;
	
	
    static public function obtener($id=null){
		$obj	= new static;
		if($id===null){
			return static::arrayToObject();
		}
		$sql_params	= [
			':id'	=> $id,
		];
		
		$sql	= <<<SQL
			SELECT id, empleado_id
			FROM alerta_relojes
			WHERE id = :id AND borrado = 0
			SQL;
		$res	= (new Conexiones())->consulta(Conexiones::SELECT, $sql, $sql_params);
		if(!empty($res)){
			return static::arrayToObject($res[0]);
		}
		return static::arrayToObject();
	}
	static public function listar(){
		return [];
	}

	static public function listar_alertas($params) {
		
		$campos    = 'id,nombre, apellido, email';
		$sql_params = [];

		$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo'])) ? 'tipo' : $params['order']['campo'];
		$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']))   ? 'asc' : $params['order']['dir'];
		$params['start']  = (!isset($params['start'])  || empty($params['start']))  ? 0 : $params['start'];
		$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght'])) ? 10 : $params['lenght'];
		$params['search'] = (!isset($params['search']) || empty($params['search'])) ? '' : $params['search'];

		$consulta = <<<SQL
        SELECT ar.id, 
			p.nombre,
			p.apellido,
			emp.email				
			FROM alerta_relojes as ar 
			inner join empleados as emp on emp.id = ar.empleado_id
			inner join personas as p on p.id = emp.persona_id
			where ar.borrado = 0
SQL;
		$data = \App\Modelo\Modelo::listadoAjax($campos, $consulta, $params, $sql_params);
		return $data;
	}

	
	private static function arrayToObject($res = []) {
		$obj = new self();
		$obj->id = isset($res['id']) ? (int)$res['id'] : 0;
		$obj->borrado = isset($res['borrado']) ? (int)$res['borrado'] : 0;
		$obj->empleado = isset($res['empleado_id']) ? Empleado::obtener($res['empleado_id']) : null;
		return $obj;
	}


	public function alta() {

		$sql = "SELECT id FROM alerta_relojes WHERE empleado_id = :empleado_id AND borrado = 1";
		$conex = new Conexiones();
		$params = [
				"empleado_id"       => $this->empleado->id,
		];
		$res = $conex->consulta(Conexiones::SELECT, $sql, $params);
		if (!empty($res)) {
				$sql1 = "UPDATE alerta_relojes SET borrado = 0 WHERE id = :id";
				$params = [
						"id"  => (int) $res[0]['id']
				];
				$res = $conex->consulta(Conexiones::UPDATE, $sql1, $params);
				if (is_numeric($res) && $res > 0){
						$datos = (array)$this;
						$datos['modelo'] = 'AlertaReloj';
						Logger::event('rehabilitacion', $datos);
						return true;
				}
		} else {
				$sql = "INSERT INTO alerta_relojes (empleado_id) VALUE (:empleado_id)";
				$res = $conex->consulta(Conexiones::INSERT, $sql, $params);
				if (is_numeric($res) && $res > 0) {
						$datos = (array)$this;
						$datos['modelo'] = 'AlertaReloj';
						Logger::event('alta', $datos);
						return true;
				}
		}

		return false;
	}

	public static function obtenerPorIdEmpleado($id_empleado) {
	
		$sql_params	= [
			':id_empleado'	=> $id_empleado,
		];
		
		$sql	= <<<SQL
			SELECT id
			FROM alerta_relojes
			WHERE empleado_id = :id_empleado AND borrado = 0
			SQL;
		$res	= (new Conexiones())->consulta(Conexiones::SELECT, $sql, $sql_params);
		
		if(!empty($res)){
			return false;
		}
		return true;
	}

	public function validar() {
		
		$inputs = [
			'empleado_id' => $this->empleado->id,		
		];
		$self = $this;
		$reglas = [
			'empleado_id'        => ['required','existeEnListaAlerta' => function() use($self) {
				
				$emp_existente = $self::obtenerPorIdEmpleado($self->empleado->id);
				return $emp_existente;
			}],
		];

		
		$validator = Validador::validate($inputs, $reglas, ['empleado_id' => 'Empleado']); 
		$validator->customErrors([
			'required' => "Debe ingresar un Email válido.",
			'existeEnListaAlerta' => "Ya existe el empleado en la lista activa de alerta de relojes."
		]);

		if ($validator->isSuccess()) {
    	  return true;
   		 } 
   		 else {
    		  $this->errores = $validator->getErrors();   		
     		 return false;
   		 }
	}

	public function baja() {
		
		if (is_numeric($this->id) && $this->id > 0) {
			$sql = "UPDATE alerta_relojes SET borrado = 1 WHERE id = :id";
			$conex = new Conexiones();
			$res = $conex->consulta(Conexiones::UPDATE, $sql, [':id' => $this->id]);
			if (is_numeric($res) && $res > 0) {
				$datos = (array)$this;
				$datos['modelo'] = 'Alerta_reloj';
				Logger::event('baja', $datos);

				return true;
			}
		}

		return false;
	}

	public function modificacion() {
	}


	public function envioDeEmails($nodo, $err, $msj){
	    $config = Configuracion::instancia();
        \FMT\Mailer::init($config['email']['app_mailer']);

        if($nodo>0){
	        $reloj = Reloj::obtenerPorNodo($nodo);
	        if(!empty($reloj)){
		        $lista_de_emails = $this->listar();
		        $email = new Email();

		        switch ($err){
			        case Reloj::CONEXION_EXITOSA:
				        $titulo = 'Aviso de conexión de reloj';
				        break;
			        case Reloj::SIN_CONEXION || Reloj::TIMEOUT_CONNECTION:
				        $titulo = 'Aviso de desconexión de reloj';
			        	break;
			        default:
				        $titulo = 'Aviso de reloj';
			        	break;
		        }

		        $datos = [
			        'reloj' => $reloj,
			        'mensaje' => $msj,
			        'fecha' => new \DateTime(),
			        'titulo' => $titulo
		        ];

		        foreach ($lista_de_emails as $indice => $receptorAlerta) {
			        $email->set_asunto('Aviso de reloj');
			        $email->set_contenido("email_alerta_reloj",$datos,true);
			        $email->set_destinatario($receptorAlerta['email']);
			        $email->enviar();
		        }
	        }
        }
	}
}