<?php
namespace App\Modelo;

/**
 * Caja de herramientas para implementar en SigarhuApi::
 */
trait SigarhuTrait {
/** @var bool|array */
	static private $ASOCIACIONES	= false;
/** @var bool|array */
	static private $ERRORES			= false;
/**
 * Convierte un array a objecto en forma recursiva. Si algun indice es de tipo string y contiene la palabra *fecha* lo combierte a objecto DateTime::
 *
 * @param array		$data	- Informacion a convertir
 * @param bool		$como_objeto - Default: true. Devuelve un objeto o un array si es es `false`
 * @return array|object
*/
	static private function arrayToObject(&$data=null, $como_objeto=true){
		foreach ($data as $attr => &$val) {
			if(is_array($val) && count($val) == 0){
				$data[$attr]	= array();
				continue;
			}
			if(is_string($attr) && !is_array($val)){
				$data[$attr]	= $val;
			} else if(is_string($attr) && preg_match('/fecha/i', $attr) && is_array($val) && !empty($val)){
                $tmp    = \DateTime::createFromFormat('Y-m-d H:i:s', $val['date']);
				$data[$attr]	= !empty($tmp) ? $tmp : \DateTime::createFromFormat('Y-m-d H:i:s.u', $val['date']); // arreglo para mantener compatibilidad entre versiones 5.5 y 5.6 o mayor de PHP
			} else if(is_array($val)){
				if(is_array($val)){
					$aux	= array_keys($val);
					$indice_numerico	= is_numeric(array_pop($aux));
				} else{ $indice_numerico	= false; }

				$data[$attr]	= static::arrayToObject($val, (!$indice_numerico && $como_objeto));
			}
		}
		return ($como_objeto) ? (object)$data : $data;
	}

/**
 * Obtiene el contenido seteado con `SigarhuApi::contiene()`. Se usa para establecer el *contiene* en las querys de consulta.
 * @return bool|array
*/
	static private	function getContiene(){
		$tmp	= static::$ASOCIACIONES;
		static::$ASOCIACIONES	= false;
		return $tmp;
	}

/**
 * Este metodo sirve para filtrar la informacion vinculada al agente (empleado), y de esa forma acotar la cantidad de bits y comportamientos asociados a dichas vinculaciones.
 * Consulta de datos mas pequeñas generan respuestas mas rapidas.
 *
 * Ej.:
 * `['situacion_escalafonaria','persona' => ['titulos', 'domicilio']`
 * Hara que tanto los obtener como las modificaciones tengan la estructura completa del indice `situacion_escalafonaria`, y `persona` del mismo modo, este ultimo indice, ademas de la informacion basica de la persona (ejemplo DNI, nombre, aperllido, etc) tambien tenga la informacion  complementaria de los `titulos` y `domicilio`.
 *
 * @param array	$asociaciones
 * @return void
*/
	static public function contiene($asociaciones=true){
		if($asociaciones === true || (is_array($asociaciones) && count($asociaciones) == 0)){
			static::$ASOCIACIONES	= true;
		}
		if(is_array($asociaciones) && count($asociaciones) > 0){
			static::$ASOCIACIONES	= $asociaciones;
		}
	}

/**
 * Al momento de hacer actualizaciones de datos via API es necesario manejar los mensajes de error pueda devolver.
 * Uso interno de SigarhuApi para almacenar los mensajes de error.
 *
 * @param array|bool $data
 * @return void
 */
	static protected function setErrores($data=false){
		static::$ERRORES	= $data;
	}

/**
 * Obtener mensajes de error almacenados por la respuesta del servidor al intentar hacer consultas.
 *
 * @return array|bool
 */
	static public function getErrores(){
		return static::$ERRORES;
	}
}