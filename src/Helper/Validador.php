<?php namespace App\Helper;

use SimpleValidator\Validator;

/**
 * Clase extendida de Validator para la definición de métodos de validación nuevo,
 * que se ajustan a los requerimientos particulares de la aplicación.
 * Class Validador
 */
class Validador extends Validator {
	const MAX_DIGITOS_DOCUMENTO = 8;
	const MIN_DIGITOS_DOCUMENTO = 6;
	const NUM_DIGITOS_CUIT = 11;

	protected static function required($input = null) {
		if (is_string($input)) {
			return strlen(trim($input)) > 0;
		} else if (is_numeric($input)) {
			return $input > 0;
		} else if (is_array($input)) {
			return count($input) > 0;
		}

		return !is_null($input);
	}

	/**
	 * Verifica que el dato a validar se de tipo texto.
	 * @param mixed $input
	 * @return bool
	 */
	public static function texto($input) {
		$tri = trim($input);
		$str = is_string($tri);

		return $str;
	}

	/**
	 * Verifica que el dato a validar tenga las características de un número de documento.
	 * @param mixed $input
	 * @return bool
	 */
	public static function documento($input) {
		$num = is_numeric($input);
		$str = trim((string)$input);
		$len = mb_strlen($str);
		$min = $len >= static::MIN_DIGITOS_DOCUMENTO;
		$max = $len <= static::MAX_DIGITOS_DOCUMENTO;

		return $num && ($min && $max);
	}

	/**
	 * Verifica que el dato a validar sea un número CUIT/CUIL válido, según la formula definida.
	 * @param mixed $input
	 * @return bool
	 */
	public static function cuit($input) {
		if (strlen($input) != 11) {
			return false;
		}
		$acumulado = 0;
		$digitos = str_split($input);
		$digito = array_pop($digitos);
		for ($i = 0; $i < count($digitos); $i++) {
			$acumulado += $digitos[9 - $i] * (2 + ($i % 6));
		}
		$verif = 11 - ($acumulado % 11);
		$verif = $verif == 11 ? 0 : $verif;

		return $digito == $verif;
	}

	/**
	 * Verifica que el dato a validar sea una fecha anterior a la referencia descrita en el
	 * segundo parámetro.
	 * @param \DateTime|string $input
	 * @param \DateTime|string $param1
	 * @return bool
	 */
	public static function antesDe($input, $param1) {
		if (is_null($input)) {
			return true;
		}
		if (static::fecha($input)) {
			if (!$input instanceof \DateTime) {
				$input = new \DateTime(date('Y-m-d H:i:s', strtotime($input)));
			}
			if (!$param1 instanceof \DateTime) {
				$param1 = new \DateTime(date('Y-m-d H:i:s', strtotime($param1)));
			}

			return $input <= $param1;
		}

		return false;
	}

	/**
	 * Verifica que el dato a validar sea una fecha válida, o un texto que se pueda interpretar
	 * como un dato de fecha.
	 * @param mixed $input
	 * @return bool
	 */
	public static function fecha($input) {
		if ($input instanceof \DateTime || strtotime($input) || empty($input)) {
			return true;
		}

		return false;
	}

	/**
	 * Verifica que el dato a validar sea una fecha posterior a la referencia descrita en el
	 * segundo parámetro.
	 * @param \DateTime        $input
	 * @param \DateTime|string $param1
	 * @return bool
	 */
	public static function despuesDe($input, $param1) {
		if (is_null($input)) {
			return true;
		} elseif (!$input instanceof \DateTime) {
			$input = new \DateTime(date('Y-m-d H:i:s', strtotime($input)));
		}
		if (static::fecha($input)) {
			if (!$param1 instanceof \DateTime) {
				$param1 = new \DateTime(date('Y-m-d H:i:s', strtotime($param1)));
			}

			return $input >= $param1;
		}

		return false;
	}

	/**
	 * Verifica que el dato a validar exista en la base de datos, haciendo referencia a la
	 * tabla y campo en que se desea verificar la existencia del mismo.
	 * @param mixed  $input Dato a verificar.
	 * @param string $tabla Tabla en la que se ejecutará la búsqueda.
	 * @param string $columna Columna en la que se ejecutará la búsqueda.
	 * @param string $database Base de Datos en la que se ejecutará la búsqueda
	 * (en caso de ser diferente a la DB definida en la configuración).
	 * @return bool true si existe, false si no.
	 */
	public static function existe($input, $tabla, $columna, $database = null) {
		$sql = "SELECT COUNT($columna) AS count FROM $tabla WHERE $columna = :input";
		$con = new Conexiones($database);
		$res = $con->consulta(Conexiones::SELECT, $sql, [':input' => $input]);
		if (!empty($res) && is_array($res) && count($res) > 0 && isset($res[0]) && isset($res[0]['count'])) {
			return $res[0]['count'] > 0;
		}

		return false;
	}

	/**
	 * Verifica que el dato a validar no exista en la base de datos, haciendo referencia a la
	 * tabla y campo en que se desea verificar la existencia del mismo.
	 * @param   mixed     $input
	 * @param     string  $tabla
	 * @param     string  $columna
	 * @param mixed  $id
	 * @param string $database
	 * @return bool
	 */
	public static function unico($input, $tabla, $columna, $id = null, $database = null, $estado = null) {
		if (!is_null($input)) {
			$sql = "SELECT COUNT($columna) AS count FROM $tabla WHERE $columna = :input";
			$params[':input'] = $input;
			if ($id) {
				$sql .= " AND id != :id";
				$params[':id'] = $id;
			}
			if ($estado) {
				$sql .= " AND borrado = :borrado";
				$params[':borrado'] = $estado;
			}			
			$con = new Conexiones($database);
			$res = $con->consulta(Conexiones::SELECT, $sql, $params);
			if (is_array($res) && isset($res[0]) && isset($res[0]['count'])) {
				return !($res[0]['count'] > 0);
			}

			return false;
		}

		return true;
	}

	/**
	 * Se le indica al validator la ruta al archivo de mensajes de error.
	 * @param $lang
	 * @return string
	 * @throws \Exception
	 */
	protected function getErrorFilePath($lang) {
		$path = __DIR__ . "/textos_validacion.php";
		if (is_file($path)) {
			return $path;
		}
		throw new \Exception('No existe el archivo de mensajes de de validación. Debe colocarse en ' . __DIR__ .
			', con el nombre "textos_validacion.php"');
	}

	protected static function numeric($input) {
    	if ($input != '') {
	        return is_numeric($input);   		
    	}	
 		return true;
    }

    protected static function alpha_numeric($input,$carecteres_extra='') {
    	if ($input != '') {
    		$patron = "#^[a-zA-ZÀ-ÿ0-9$carecteres_extra]+$#";
	        return (preg_match($patron, $input) == 1);
    	}
 		return true;
    }

    protected static function max_length($input, $length) {
    	if ($input != '') {
	        return (strlen($input) <= $length);
    	}
 		return true;
    }

    protected static function min_length($input, $length) {
    	if ($input != '') {
	        return (strlen($input) >= $length);
    	}
 		return true;
    }

    protected static function email($input) {
    	if ($input != '') {
        	return filter_var($input, FILTER_VALIDATE_EMAIL);
    	}
 		return true;
    }
 
     protected static function boolean($input) {
    	if ($input != '') {
        	return filter_var($input, FILTER_VALIDATE_BOOLEAN);
    	}
 		return true;
    }    

     protected static function char($input) {
    	if ($input != '') {
	        return (preg_match("#^[a-zA-ZÀ-ÿ ]+$#", $input) == 1);
    	}
 		return true;
    }

	/**
	 * Verifica que el dato a validar sea un entero mayor a la referencia descrita en el
	 * segundo parámetro.
	 * @param int        $input
	 * @param int $param1
	 * @return bool
	 */
     protected static function mayorA($input, $param1) {
		if ($input != '')
			if( static::numeric($input) && static::numeric($param1)) {
				return ($input > $param1);
		} else {
			return false;
		}
			return true;
		}

    protected static function integer($input) {
    	if ($input != '') {
        	return is_int($input) || ($input == (string) (int) $input);
    	}else{
    		return true;
    	}
    }

    protected static function isJson($input) {
    	if ($input != '') {
	        return is_string($input) && is_array(json_decode($input, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    	}
 		return true;
    }
	
	protected static function empleado_x_usuario($input = null) {
		if (is_string($input)) {
			return strlen(trim($input)) > 0;
		} else if (is_numeric($input)) {
			return $input > 0;
		} else if (is_array($input)) {
			return count($input) > 0;
		}

		return !is_null($input);
	}

}