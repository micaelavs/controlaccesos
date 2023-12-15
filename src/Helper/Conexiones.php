<?php

namespace App\Helper;

use FMT\Configuracion;
use \PDO;

/**
 * Class Conexiones
 *
 * Esta clase permite la conexion a multiples bases de datos, agregando en config/database.php['database']
 * una key con un nombre, y usando ese nombre en el construct.
 * El construct sin nombre usa los valores por defecto, una base con nombre no necesita redefinir todos los parametros
 *
 * @package App
 */
class Conexiones {

	const SELECT = 0;
	const INSERT = 1;
	const DELETE = 2;
	const UPDATE = 3;

/** @var \PDO[] */
	protected static $conexiones;

	static private $DEBUG = false;
/** @var string Codigo de error de MySQL si consulta devuelve false*/
	public $errorCode;
/** @var string Mensaje de error de MySQL*/
	public $errorInfo;

/** @var string */
	protected $database;




	public function __construct($database = '') {
		$this->database = $database;
	}



/**
 * @return PDO
 */
	public function conectaDB() {
		if (!isset(static::$conexiones[$this->database])) {
			$config		= Configuracion::instancia();
			$host		= $config['database']['host'];
			$database	= $config['database']['database'];
			$username	= $config['database']['user'];
			$passwd		= $config['database']['pass'];
			if ($this->database) {
				if(isset($config['database'][$this->database]) && is_array($config['database'][$this->database])) {
					if(isset($config['database'][$this->database]['host'])){
						$host		= $config['database'][$this->database]['host'];
					}
					if(isset($config['database'][$this->database]['database'])){
						$database	= $config['database'][$this->database]['database'];
					}
					if(isset($config['database'][$this->database]['user'])){
						$username	= $config['database'][$this->database]['user'];
					}
					if(isset($config['database'][$this->database]['pass'])){
						$passwd		= $config['database'][$this->database]['pass'];
					}
				}else{
					throw new \UnexpectedValueException('Base de datos seleccionada no está configurada');
				}
			}
			static::$conexiones[$this->database]	= new \PDO('mysql:host='.$host.';dbname='.$database, $username, $passwd, [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
			static::$_stmts[$this->database]		= [];
		}

		return static::$conexiones[$this->database];
	}


	protected static $_stmts = [];

/**
 * @param \PDO $c
 * @param string $sql
 * @return \PDOStatement
 */
	protected function preparar($c, $sql) {
		if (!isset(static::$_stmts[$this->database][$sql])) {
			static::$_stmts[$this->database][$sql]	= $c->prepare($sql);
		}
		return static::$_stmts[$this->database][$sql];
	}

/**
 * Consulta a la db usando parametros
 * Si hay un error, se devuelve false y se cargan las propiedades errorCode/errorInfo (revisar con ===, ya que update puede devolver 0)
 *
 * @param int $type constantes de clase
 * @param string $sql
 * @param array $params
 * @return array|int|string|bool
 */	public function consulta($type, $sql, $params = []) {
		$this->debug($sql, $params,debug_backtrace());
		$c = $this->conectaDB();
        foreach ($params as $key => $value) {
          if(is_array($value)) { 
            $remp = ''; 
            foreach ($value as $i => $content) { 
                $remp .= ($remp) ? ','.$key.$i : $key.$i; 
                $params[$key.$i] = $content; 
                } 
                $sql = preg_replace('/\\'.$key.'/', $remp, $sql); 
                    unset($params[$key]);
          }
        }
		$stmt = $this->preparar($c, $sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value);
		}

		$stmt->execute();
		if ($stmt->errorCode() != '00000') {
			$this->errorCode = $stmt->errorCode();
			$this->errorInfo = $stmt->errorInfo();
               		$config = Configuracion::instancia();
                	if(!empty($config['app']['dev'])) {
				print_r($this->errorCode);
				print_r($this->errorInfo);
				Conexiones::activarDebug();
				$this->debug($sql, $params,debug_backtrace());
			}
			return false;
		}
		switch ($type) {
			case self::SELECT:
			$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$stmt->closeCursor();
				return $resultado;
			case self::INSERT:
				return $c->lastInsertId();
			case self::DELETE:
				return $stmt->rowCount();
			case self::UPDATE:
				return $stmt->rowCount();
			default:
				throw new \InvalidArgumentException('Tipo no reconocido');
		}

	}


	public function beginTransaction(){
		$c	= $this->conectaDB();
		$c->beginTransaction();
	}

	public function commit(){
		$c	= $this->conectaDB();
		$c->commit();
	}

	public function rollback(){
		$c	= $this->conectaDB();
		$c->rollBack();
	}

/**
 * Metodo para la activacion del debugueo de consultas.
 * 
 * @param void
 * @return void 
 */
	static public function activarDebug(){
		static::$DEBUG	= true;
	}

/**
 * Metodo para debug de consultas ejecutadas
 *
 * @param string	$sql_debug		- consulta que se debuguea
 * @param array		$params_debug	- array con los valores de reemplazo
 * @param array		$trace 			- Resultado de "debug_backtrace()"
 * @return void
 */
	protected function debug($sql_debug,$params_debug,$trace) {
		$config	= Configuracion::instancia();
		if(!empty($config['app']['dev']) && static::$DEBUG === true) {
			if(is_array($params_debug)) {
				array_walk($params_debug, function(&$value,$key) use (&$sql_debug,&$params_debug) {
				  	if(is_array($value)) {
				  		$remp		= '';
				  		foreach ($value as $i => $content) { 
				  			$remp 					.= ($remp) ? ','.$key.$i : $key.$i;
				  			$params_debug[$key.$i]	= $content; 
				  		}
			  			$sql_debug	= preg_replace('/\\'.$key.'/', $remp, $sql_debug);
			  			unset($params_debug[$key]);
			  		}else{
			  			$value	= "'".$value."'";
			  		}			  	
				});
				$sql_debug	= str_replace(array_keys($params_debug), array_values($params_debug), $sql_debug);
			}

			$html	= <<<HTML
			<pre class="xdebug-var-dump" dir="ltr">
				<p style="text-align: center;">Archivo: {$trace[0]['file']} <b>Linea: {$trace[0]['line']}</b></p>
				<font color="#cc0000">
					{$sql_debug}
				</font>
			</pre>
HTML;
			print_r($html);
		}
	}
}
