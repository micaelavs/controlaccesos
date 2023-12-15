<?php
namespace App\Helper;

class Documento  {

/**
 * Verifica que el nombre del directorio exista en la direccion adecuada, caso contrario lo crea.
 * Devuelve la ruta completa en forma correcta.
 * Si el parametro es NULL, utiliza el directorio temporal dentro del sistema
 *
 * @param string $directorio - Nombre de la carpeta que se desea usar.
 * @param bool $limpiar_antiguos - Default: false - Si esta activo se limpian los archivos con mas de 14 dias de antiguedad.
 * @return string
 */
	static public function getDirectorio($directorio='', $limpiar_antiguos=false){
		if(!is_string($directorio)){
			return static::getDirectorioTMP();
		} else {
			$directorio	= BASE_PATH.'/uploads/'.$directorio;
		}
		if(!is_dir($directorio)){
			mkdir($directorio, 0777, true);
		}
		if($limpiar_antiguos === true){
			static::cleanTempDirectory($directorio);
		}
		return $directorio;
	}

	/**
 * Devuelve el directorio temporal para guardar archivos temporales.
 * @return string
 */
	static public function getDirectorioTMP(){
		$return	= BASE_PATH.'/uploads/temporal_consola';
		if(!is_dir($return)){
			mkdir($return, 0777, true);
		}
		static::cleanTempDirectory($return);
		return $return;
	}

/**
 * Elimina los archivos usados en un directorio.
 * @param string $dir
 * @return void
 */
	static private function cleanTempDirectory($dir=null){
		$archivos	= scandir($dir);
		
		$now_date	= new \DateTime('now');
		foreach ($archivos as $value) {
			if($value == '.' || $value == '..'){
				continue;
			}
			$file_date	= \DateTime::createFromFormat('U', filemtime($dir.'/'.$value));
			if(empty($file_date)) continue;
			$diff_date	= $now_date->diff($file_date)->format('%a');
			if((int)$diff_date > 14){
				unlink($dir.'/'.$value);
			}
		}
	}

	/**
	 * El proposito de este metodo es generear una ubicacion temporal para los archivos recividos en $_FILE.
	 * Cuando se debe usar una pantalla de confirmacion y mantener el dato de archivo es particularmente util.
	 *
	 * @param array $file_data - El contenido de $_FILE['nombre_campo']
	 * @return array - Con el mismo formato entrada pero con 'tmp_name' cambiado
	 */
	static public function crear_temporal($file_data=null){
		if(!(is_array($file_data) && isset($file_data['error']) && $file_data['error'] == UPLOAD_ERR_OK)){
			return false;
		}

		$directorio_temporal	= static::getDirectorio('tmp_web_files', true);
		$nombre_archivo			= md5($file_data['tmp_name']);
		if(move_uploaded_file($file_data['tmp_name'], $directorio_temporal.'/'.$nombre_archivo)){
			$file_data['tmp_name']		= $directorio_temporal.'/'.$nombre_archivo;
		}else{
			return false;
		}
		return $file_data;
	}
}