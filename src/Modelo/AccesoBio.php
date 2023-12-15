<?php
namespace App\Modelo;

use App\Helper\Conexiones;
use FMT\Configuracion;
use FMT\Logger;
use App\Modelo\Modelo;
use App\Helper\Msj;

class AccesoBio extends Modelo {

	public function alta(){}
	public function baja(){}
	public function modificacion(){}
	public function validar(){}

	static public function listar_accesosbio($params)
	{
		$campos    = 'id,dni, hora, puerta';
		$sql_params = [];

		$params['order']['campo'] = (!isset($params['order']['campo']) || empty($params['order']['campo'])) ? 'tipo' : $params['order']['campo'];
		$params['order']['dir']   = (!isset($params['order']['dir'])   || empty($params['order']['dir']))   ? 'asc' : $params['order']['dir'];
		$params['start']  = (!isset($params['start'])  || empty($params['start']))  ? 0 :
			$params['start'];
		$params['lenght'] = (!isset($params['lenght']) || empty($params['lenght'])) ? 10 :
			$params['lenght'];
		$params['search'] = (!isset($params['search']) || empty($params['search'])) ? '' :
			$params['search'];
			
		$consulta = <<<SQL
        
		SELECT id, dni, DATE_FORMAT(hora, '%d/%m/%Y %H:%i') as hora, puerta  FROM textmecon WHERE informado = 0 ORDER BY hora DESC LIMIT 2000
SQL;
		$data = self::listadoAjax($campos, $consulta, $params, $sql_params);
		return $data;
	}

	public static function listar(){

		$conexion = new Conexiones();
		$sql = "SELECT dni, DATE_FORMAT(hora, '%d/%m/%Y %H:%i') as hora, puerta  FROM textmecon WHERE informado = 0 ORDER BY hora DESC LIMIT 2000";
		$listadoNoInformados = $conexion->consulta(Conexiones::SELECT, $sql);

		return $listadoNoInformados;

	}

	/**
	 * Ejecuta la rutina de sincronizar fichadas.
	 * Conectar con el servidor remoto, validar, insertar datos en la DB.
	 *
	 * @return array - ["estado"=>(bool), "info"=>(string)]
	 */
	public static function sincronizar() {
		$config			=  Configuracion::instancia();
		$conexion		= new Conexiones();
		$dirProcesados	= $config['ssh']['mecon_carpeta_destino'];
		$carpeta_origen = $config['ssh']['mecon_carpeta_origen'];

		$connection		= \ssh2_connect($config['ssh']['host'], $config['ssh']['puerto']);
		if (!\ssh2_auth_password($connection, $config['ssh']['usuario'], $config['ssh']['password'])) {
			return static::response(0,"Error en las credenciales");
		}
		$sftp = \ssh2_sftp($connection);

		$archivosAProcesar		= scandir("ssh2.sftp://{$sftp}{$carpeta_origen}");
		$cantArchivosAProcesar	= count($archivosAProcesar);

		if (!empty($archivosAProcesar)) {
			$archivosProcesadosOk	= 0;
			foreach ($archivosAProcesar as $indice => $archivo) {

				$fecha_a_procesar	= substr($archivo,-14,10);
				error_log($fecha_a_procesar);

				if(filesize("ssh2.sftp://{$sftp}{$carpeta_origen}/{$archivo}") == 0){
					$nuevoNombre	= $dirProcesados.'/datos_'.$fecha_a_procesar.'.txt';
					if (file_exists($nuevoNombre)) {
						$i			= count(glob($nuevoNombre));
						$nuevoNombre= $dirProcesados . '/datos_' . $fecha_a_procesar . '_' . $i . '.txt';
					}
					$archivosProcesadosOk++;
					copy("ssh2.sftp://{$sftp}{$carpeta_origen}/{$archivo}", "ssh2.sftp://{$sftp}{$nuevoNombre}");
					\ssh2_sftp_unlink($sftp, $carpeta_origen.'/'.$archivo);
				} else {
					$cantRegistrosParaProcesar	= 
					$cantAccesosCompletos 		= 
					$procesados					= 
					$numeroLinea				= 0;
					$dnisDelArchivo				= [];

					$file_handle2 = fopen("ssh2.sftp://{$sftp}{$carpeta_origen}/{$archivo}", "rb");

					while (!feof($file_handle2)) {
						$numeroLinea++;
						$line_of_text = trim(fgets($file_handle2));

						if (!empty($line_of_text)) {
							$parts		= explode(';', $line_of_text);
							$validacion = static::validarDatos($parts[0], $parts[1], $parts[2]);

							if ($validacion['status'] != "ok") {
								return static::response(0,$validacion['detalle'] . " en el archivo:  datos_" . $fecha_a_procesar . ".txt" . " en la linea: " . $numeroLinea . ". Este registro no se procesa.");
							}
							$fechaC	= \DateTime::createFromFormat("d/m/Y H:i:s", $validacion['fechaCorrecta']);
							$fecha	= $fechaC->format("Y-m-d");
							$hora	= $fechaC->format("Y-m-d H:i:s");

							if (!static::verificarRegistroMeconExistente($parts[0], $fecha, $hora)) {
								$sqlInsert		= "INSERT INTO textmecon(dni,fecha,hora,puerta) VALUES (:dni, :fecha, :hora, :puerta)";
								$insertFichadas = $conexion->consulta(Conexiones::INSERT, $sqlInsert, [
									':dni'		=> $parts[0],
									':fecha'	=> $fecha,
									':hora'		=> $hora,
									':puerta'	=> $parts[3],
								]);

								$cantRegistrosParaProcesar++;
								$nuevo			= [
									'dni'	=> $parts[0],
									'fecha'	=> $fecha
								];
								if (!in_array($nuevo, $dnisDelArchivo)) {
									$dnisDelArchivo[] = [
										'dni'	=> $parts[0],
										'fecha'	=> $fecha
									];
								}
							}
						}
					}
					fclose($file_handle2);

					if (!empty($dnisDelArchivo)) {
						foreach ($dnisDelArchivo as $indice => $dniEmp) {
							$sqlEmpleado	= "SELECT e.id FROM empleados as e INNER JOIN personas as p on p.id = e.persona_id where p.documento = :dni";
							$esEmp			=  $conexion->consulta(Conexiones::SELECT, $sqlEmpleado, [
								':dni' => $dniEmp['dni']
							]);
							if (!empty($esEmp)) {
								$sqlUpdate	= "UPDATE textmecon SET informado = 1 WHERE dni = :dni AND fecha = :fecha";
								$actualizar = $conexion->consulta(Conexiones::UPDATE, $sqlUpdate, [
									':fecha'	=> $dniEmp['fecha'],
									':dni'	=> $dniEmp['dni']
								]);

								$sqlFichadas			= "SELECT dni,fecha,hora,puerta FROM textmecon WHERE dni = :dni AND fecha = :fecha ORDER BY hora ASC ";
								$fichadaDeEseEmpleado	= $conexion->consulta(Conexiones::SELECT, $sqlFichadas, [
									':fecha'=> $dniEmp['fecha'],
									':dni'	=> $dniEmp['dni']
								]);

								if (!empty($fichadaDeEseEmpleado)) {
									$result					= static::procesaFichadaEmpleado($fichadaDeEseEmpleado, $esEmp[0]['id']);
									$cantAccesosCompletos	= $cantAccesosCompletos + $result['cantidadAccesosCompletos'];
									$procesados				= $procesados + $result['procesados'];
								}
							} else {
								$sqlFichadas= "SELECT count(id) as cantidad FROM textmecon WHERE dni = :dni AND fecha = :fecha AND informado = 0";
								$fichadas	= $conexion->consulta(Conexiones::SELECT, $sqlFichadas, [
									':fecha'=> $dniEmp['fecha'],
									':dni'	=> $dniEmp['dni']
								]);
								$procesados	= $procesados + $fichadas[0]['cantidad'];
							}
						}
					}

					if ($cantRegistrosParaProcesar - $cantAccesosCompletos == $procesados) {
						$archivosProcesadosOk++;
						$nuevoNombre	= $dirProcesados . '/datos_' . $fecha_a_procesar . '.txt';

						if (file_exists($nuevoNombre)) {
							$i = count(glob($nuevoNombre));
							$nuevoNombre = $dirProcesados . '/datos_' . $fecha_a_procesar . '_' . $i . '.txt';
						}
						copy("ssh2.sftp://{$sftp}{$carpeta_origen}/{$archivo}", "ssh2.sftp://{$sftp}{$nuevoNombre}");
						\ssh2_sftp_unlink($sftp, $carpeta_origen.'/'.$archivo);
					}
				}
			}
			if ($archivosProcesadosOk == $cantArchivosAProcesar) {
				return static::response(1,"La sincronizacion termino con exito, se ha procesado " . $archivosProcesadosOk . " archivos");
				//Msj::setMensaje("La sincronizacion termino con exito, se ha procesado " . $archivosProcesadosOk . " archivos", 'success', 'check');
				//$this->redirigir(['c' => 'AccesosBio', 'a' => 'index']);
			} else {
				return static::response(1,"Ha ocurrido un error en la sincronización");
				//Msj::setMensaje("Ha ocurrido un error en la sincronización", 'warning', 'exclamation-triangle');
				//$this->redirigir(['c' => 'AccesosBio', 'a' => 'index']);
			}
		} else {
			return static::response(1,"No hay datos para sincroniza");
			//$this->redirigir(['c' => 'AccesosBio', 'a' => 'index']);
		}
	}

	/**
	 * Valida el formato del DNI, de la fecha y hora. Devuelve un array con status "ok" en caso de exito o "error" en caso de datos invalidos.
	 * En caso de exito, el array contiene un indice "fechaCorrecta" con el formato adecuado "d/m/Y H:i:s"
	 *
	 * @param string $dni
	 * @param string $fecha
	 * @param string $hora
	 * @return array ['status'=>'ok', 'fechaCorrecta'=>(string)] || ['status'=>'error', 'detalle'=>(string)]
	 */
	static private function validarDatos($dni=null, $fecha=null, $hora=null) {
		$fechaDeLaLinea = "";
		$lengthDni		= strlen(trim($dni));
		switch (false) {
			case ($lengthDni >= 7 && $lengthDni <= 8 && preg_match('/[0-9]{7,8}/', $dni)):
				return ['status' => "error", 'detalle' => "  Error en el formato del DNI"];
				break;
			case (strlen($hora) == 8 && preg_match("{[0-2][0-9][:][0-5][0-9][:][0-5][0-9]}", $hora) == 1):
				return ['status' => "error", 'detalle' => " Error en el formato de la hora"];
				break;
			case (strlen($fecha) == 10):
				return ['status' => "error", 'detalle' => " Error en el formato de la fecha"];
				break;
		}

		if (preg_match("{[0-3][0-9][/-][0-1][0-9][/-][2][0][1-9][0-9]}", $fecha) == 1) { // o es d/m/Y o d-m-Y
			$splitFecha = preg_split("{[/-]}", $fecha);
			$fechaDeLaLinea = $splitFecha[0] . "/" . $splitFecha[1] . "/" . $splitFecha[2];
			return ['status' => 'ok', 'fechaCorrecta' => $fechaDeLaLinea . " " . $hora];
		} elseif (preg_match("{[2][0][1-9][0-9][/-][0-1][0-9][/-][0-3][0-9]}", $fecha) == 1) { // Y/m/d o Y-m-d  cualquier otro formato esta mal
			$splitFecha = preg_split("{[/-]}", $fecha);
			$fechaDeLaLinea = $splitFecha[2] . "/" . $splitFecha[1] . "/" . $splitFecha[0]; // lo acomodo para que siempre quede con formato d/m/Y
			return ['status' => 'ok', 'fechaCorrecta' => $fechaDeLaLinea . " " . $hora];
		} else {
			return ['status' => "error", 'detalle' => " Error en el formato de la fecha"];
		}
	}

	/**
	 * Verifica si existe un registro anterior cargado con los mismos datos. Retorna true, en caso de existir.
	 *
	 * @param string $dni
	 * @param string $fecha - 'Y-m-d'
	 * @param string $hora - 'Y-m-d H:i:s'
	 * @return bool
	 */
	static private function verificarRegistroMeconExistente($dni, $fecha, $hora) {
		$conexion	= new Conexiones();
		$sql		= "SELECT id FROM textmecon WHERE dni = :dni AND fecha = :fecha AND hora = :hora";
		$id			= $conexion->consulta(Conexiones::SELECT, $sql, [
			':dni'		=> $dni,
			':fecha'	=> $fecha,
			':hora'		=> $hora
		]);

		if (!empty($id)) {
			return true;
		}
		return false;
	}

	/**
	 * Verifica el tipo de acceso (puerta fisica). entrada o salida
	 *
	 * @param string $puerta
	 * @param string $tipo - ENTRADA || SALIDA
	 * @return bool
	 */
	static private function verificarTipoAcceso($puerta, $tipo) {
		return strpos(strtolower($puerta), $tipo) !== false;
	}

	/**
	 * Verifica el tipo de acceso (puerta fisica). entrada o salida.
	 * Si el de acceso no es entrada, devuelve SS.
	 * Si el de acceso no es salida, devuelve EE.
	 * Si el tipo de acceso es correcto, devuelve OK
	 *
	 * @param string $puerta
	 * @param string $tipo - ENTRADA || SALIDA
	 * @return string - SS || EE || OK
	 */
	static private function verificar($elem1, $elem2) {
		switch (false) {
			case (static::verificarTipoAcceso($elem1['puerta'], 'entrada')):
				return 'SS';
				break;
			case (static::verificarTipoAcceso($elem2['puerta'], 'salida')):
				return 'EE';
				break;
		}
		return 'OK';
	}

	/**
	 * Crea el registro de acceso para el empleado.
	 *
	 * @param int $idEmpleado
	 * @return int - Id del registro creado
	 */
	static private function crearAccesoEmpleado($idEmpleado) {
		$conexion			= new Conexiones();
		$sql				= "INSERT INTO accesos_empleados(empleado_id) VALUES (:idEmp)";
		$idAccesoEmpleado	= $conexion->consulta(Conexiones::INSERT, $sql, [
			':idEmp'	=> $idEmpleado
		]);
		return $idAccesoEmpleado;
	}

	/**
	 * Verifica si el registro que se quiere cargar ya existe. Devuelve true en caso de existir.
	 *
	 * @param string $hora_ingreso
	 * @param string $hora_egreso
	 * @param int|string $idEmpleado
	 * @return bool
	 */
	static private function verificarAccesoExistente($hora_ingreso, $hora_egreso, $idEmpleado) {
		$conexion		= new Conexiones();
		$tipoIngreso	= Acceso::TIPO_REGISTRO_RELOJ_BIOHACIENDA;
		$ubicacion_id	= 1;
		$sql = <<<SQL
		SELECT acc.id FROM accesos as acc
		INNER JOIN accesos_empleados as ae on ae.id = acc.tipo_id
		WHERE ae.empleado_id = :idEmpleado
		AND hora_ingreso = :horaIngreso
		AND hora_egreso = :horaEgreso
		AND tipo_ingreso = :tipoIngreso
		AND tipo_egreso = :tipoEgreso
		AND tipo_modelo = :tipoModelo
		AND ubicacion_id = :ubicacion_id
SQL;

		$idAcceso = $conexion->consulta(Conexiones::SELECT, $sql, [
			':idEmpleado'	=> $idEmpleado,
		    ':horaIngreso'	=> $hora_ingreso,
			':horaEgreso'	=> $hora_egreso,
			':tipoIngreso'	=> $tipoIngreso,
			':tipoEgreso'	=> $tipoIngreso,
			':tipoModelo'	=> Acceso::EMPLEADO,
			':ubicacion_id'	=> $ubicacion_id,
		]);

		if (!empty($idAcceso)) {
			return true;
		}
		return false;
	}

	/**
	 * Registra el acceso del empleado.
	 *
	 * @param string $hora_ingreso
	 * @param string $hora_egreso
	 * @param string $puerta
	 * @param int|string $idEmpleado
	 * @return void
	 */
	static private function crearAcceso($hora_ingreso, $hora_egreso, $puerta, $idEmpleado) {

		$tipo_id		= static::crearAccesoEmpleado($idEmpleado);
		$ubicacion_id	= 1;
		$conexion		= new Conexiones();
		$insertAcceso	= <<<SQL
		INSERT INTO accesos (ubicacion_id,tipo_id,tipo_modelo,hora_ingreso,persona_id_ingreso,tipo_ingreso,hora_egreso,persona_id_egreso,tipo_egreso,observaciones) 
		VALUES (:ubicacion_id,:tipo_id,:tipo_modelo,:hora_ingreso,:persona_id_ingreso,:tipo_ingreso,:hora_egreso,:persona_id_egreso, :tipo_egreso, :observaciones)
SQL;

		$accesoCreado = $conexion->consulta(Conexiones::INSERT, $insertAcceso, [
			':ubicacion_id'			=> $ubicacion_id,
			':tipo_id'				=> $tipo_id,
			':tipo_modelo'			=> 1,
			':hora_ingreso'			=> $hora_ingreso,
			':persona_id_ingreso'	=> 8869, // En prod: tabla personas = Reloj Biometrico
			':tipo_ingreso'			=> Acceso::TIPO_REGISTRO_RELOJ_BIOHACIENDA,
			':hora_egreso'			=> $hora_egreso,
			':persona_id_egreso'	=> 8869, // En prod: tabla personas = Reloj Biometrico
			':tipo_egreso'			=> $hora_egreso ? Acceso::TIPO_REGISTRO_RELOJ_BIOHACIENDA : 0,
			':observaciones'		=> $puerta
		]);

	}

	/**
	 * Invoca procesos de validacion para a posterior crear el/los registro/s del empleado.
	 *
	 * @param array $fichadaEmpleado
	 * @param int $idEmpleado
	 * @return array ['cantidadAccesosCompletos' => (int), 'procesados' => (int)]
	 */
	static private function procesaFichadaEmpleado($fichadaEmpleado, $idEmpleado) {
		$resultado			= ['cantidadAccesosCompletos' => 0, 'procesados' => 0];
		$ind				= 0;
		$length				= count($fichadaEmpleado);
		$observacionSalida	= "(no hay registro de salida)";
		$observacionEntrada	= "(no hay registro de entrada)";

		while ($ind < $length) {
			if ($length - 1 == $ind) { //tiene una sola fichada
				if (
					static::verificarTipoAcceso($fichadaEmpleado[$ind]['puerta'], 'entrada')
					&& !static::verificarAccesoExistente($fichadaEmpleado[$ind]['hora'], null, $idEmpleado)
				){
						static::crearAcceso($fichadaEmpleado[$ind]['hora'], null, $fichadaEmpleado[$ind]['puerta'] . $observacionSalida, $idEmpleado);
						$resultado['procesados']++;
				} elseif (!static::verificarAccesoExistente($fichadaEmpleado[$ind]['hora'], $fichadaEmpleado[$ind]['hora'], $idEmpleado)) {
						static::crearAcceso($fichadaEmpleado[$ind]['hora'], $fichadaEmpleado[$ind]['hora'], $fichadaEmpleado[$ind]['puerta'] . $observacionEntrada, $idEmpleado);
						$resultado['procesados']++;
				}
				$ind = $ind + 1;
			} else {
				switch (static::verificar($fichadaEmpleado[$ind], $fichadaEmpleado[$ind + 1])) {
					case 'OK':
						if (!static::verificarAccesoExistente($fichadaEmpleado[$ind]['hora'], $fichadaEmpleado[$ind + 1]['hora'], $idEmpleado)) {
							static::crearAcceso($fichadaEmpleado[$ind]['hora'], $fichadaEmpleado[$ind + 1]['hora'], $fichadaEmpleado[$ind]['puerta']."-".$fichadaEmpleado[$ind + 1]["puerta"], $idEmpleado);
							$resultado['cantidadAccesosCompletos']++;
							$resultado['procesados']++;
						}
						$ind = $ind + 2;
						break;
					case 'EE':
						if (!static::verificarAccesoExistente($fichadaEmpleado[$ind]['hora'], null, $idEmpleado)) {
							static::crearAcceso($fichadaEmpleado[$ind]['hora'], null, $fichadaEmpleado[$ind]['puerta'].$observacionSalida, $idEmpleado);
							$resultado['procesados']++;
						}
						$ind++;
						break;
					case 'SS':
						if (!static::verificarAccesoExistente($fichadaEmpleado[$ind]['hora'], $fichadaEmpleado[$ind]['hora'], $idEmpleado)) {
							static::crearAcceso($fichadaEmpleado[$ind]['hora'], $fichadaEmpleado[$ind]['hora'], $fichadaEmpleado[$ind]['puerta'].$observacionEntrada, $idEmpleado);
							$resultado['procesados']++;
						}
						$ind++;
						break;
				}
			}
		}
		return $resultado;
	}

	/**
	 * Genera un mensaje con respues standar.
	 *
	 * @param int $estado
	 * @param string $info
	 * @return array - ["estado"=>(bool), "info"=>(string)]
	 */
	public static function response($estado=1,$info=""){
		if ($estado==0) { 
				http_response_code(400); 
		}
		return(["estado"=>$estado, "info"=>$info]);
	}
}