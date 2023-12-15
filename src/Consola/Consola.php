<?php
namespace App\Consola;

use FMT\Consola AS ConsolaBase;
use FMT\Consola\Modelo\ColaTarea;

use App\Modelo;
use App\Helper\Conexiones;


/**
 * Acciones para procesar por cron del servidor.
*/
class Consola extends ConsolaBase {

	public function accion_help(){
		if (static::ClonadoProcessAlive('help')) {
			exit;
		}
		$consola_file	= constant('CONSOLA_FILE');
		$interprete		= constant('PHP_INTERPRETE');
		$ayuda	= <<<TXT
Este sistema posee los metodos :
 - {$interprete} {$consola_file} cola_tareas: Para usar en cron de sistema. Busca, administra y procesa las tareas pendientes almacenadas en base de datos.

 - {$interprete} {$consola_file} ejemplos (parametro)

 - {$interprete} {$consola_file} ejemplos_colateas
\n
TXT;
		echo $ayuda;		
		$this->matarProceso();
		exit;
	}

	public function accion_ejemplos(){
		if (static::ClonadoProcessAlive('ejemplos')) {
			exit;
		}
		$params		= $this->getParams();
        $params2    = $_SERVER['argv'][count($_SERVER['argv'])-1];
		
		try {
			print_r($params);
            print_r($params2);
		} catch (\Exception $e) {
			var_export($e);
		}

		$this->matarProceso();
		exit;
	}
	
    public function accion_ejemplos_colateas(){
		if (static::ClonadoProcessAlive('ejemplos_colateas')) {
			exit;
		}
		$params		= $this->getParams();
		$tarea		= ColaTarea::obtenerPorAccion('ejemplos_colateas', $params);
		if(!empty($tarea)){
			ColaTarea::tareaEjecutando($tarea);
		}

		try {
			echo print_r($params);
		} catch (\Exception $e) {
			var_export($e);
		}

		if(!empty($tarea)){
			ColaTarea::tareaFinalizar($tarea);
			$this->matarProceso();
			exit;
		}
	}
	
}