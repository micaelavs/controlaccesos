<?php
namespace App\Modelo;

use App\Helper\Conexiones;
use FMT\Modelo;

/**
 * Modelos de contrato.
 * La propiedad `id` y `id_modalidad_vinculacion` son identicas y deben ser de ese modo.
 */
class SituacionRevista extends Modelo {
/** @var int */
	public $id;
/** @var int */
	public $id_modalidad_vinculacion;
/** @var int */
	public $id_situacion_revista;
/** @var string */
	public $nombre;
/** @var int */
	public $borrado;

/** @var bool */
	static private $REQUIERE_PERMISOS	= false; /** @todo Solo las constantes se deben escribir en mayuscula*/
/** @var bool */
    static private $PERMITE_VISIBLES	= false;
/** @var bool */
    static private $ANCLAR_MODALIDAD_VINCULACION	= false;

    const SIN_CONTRATO = 0;

    const SINEP_PLANTA_PERMANENTE = 1;
    const SINEP_LEY_MARCO = 2;
    const SINEP_DESIGNACION_TRANSITORIA_EN_CARGO_DE_PLANTA_PERMANENTE_CON_FUNCION_EJECUTIVA = 3;
    const SINEP_PLANTA_PERMANENTE_MTR_CON_DESIGNACION_TRANSITORIA = 4;
    const PRESTACION_DE_SERVICIOS_1109_17 = 5;
    const PRESTACION_DE_SERVICIOS_1109_17_CON_FINANCIMIENTO_EXTERNO = 6;
    const PRESTACION_DE_SERVICIOS_ASISTENCIA_TECNICA = 7;
    const PERSONAL_EMBARCADO_CLM = 8;
    const PERSONAL_EMBARCADO_PLANTA_PERMANENTE = 9;
    const OTRA_COMISION_SERVICIOS = 10;
    const OTRA_PLANTA_PERMANENTE_CON_DESIGNACION_TRANSITORIA = 11;
    const OTRA_GABINETE_DE_ASESORES = 12;
    const AUTORIDAD_SUPERIOR_AUTORIDAD_SUPERIOR = 13;
    const EXTRAESCALAFONARIO_EXTRAESCALAFONARIO = 14;
    const SINEP_PLANTA_PERMANENTE_MTR_CON_DESIGNACION_TRANSITORIA_CON_FUNCION_EJECUTIVA = 15;
    const OTRA_ADSCRIPCION = 16;
    const SINEP_DESIGNACION_TRANSITORIA_SIN_FUNCION_EJECUTIVA = 17;
    const OTRA_HORAS_CATEDRA = 18;
    const OTRA_OTRAS_MODALIDADES = 19;

    static $situaciones_resvistas = [
        self::SINEP_PLANTA_PERMANENTE => 'SINEP - Planta Permanente',
        self::SINEP_LEY_MARCO => 'SINEP - Ley Marco',
        self::SINEP_DESIGNACION_TRANSITORIA_EN_CARGO_DE_PLANTA_PERMANENTE_CON_FUNCION_EJECUTIVA => 'SINEP - Designacion Transitoria en Cargo de Planta Permanente con Funcion Ejecutiva',
        self::SINEP_PLANTA_PERMANENTE_MTR_CON_DESIGNACION_TRANSITORIA => 'SINEP - Planta Permanente MTR con Designacion Transitoria',
        self::PRESTACION_DE_SERVICIOS_1109_17 => 'Prestacion de Servicios - 1109/17',
        self::PRESTACION_DE_SERVICIOS_1109_17_CON_FINANCIMIENTO_EXTERNO => 'Prestacion de Servicios - 1109/17 con Financimiento Externo',
        self::PRESTACION_DE_SERVICIOS_ASISTENCIA_TECNICA => 'Prestacion de Servicios - Asistencia Tecnica',
        self::PERSONAL_EMBARCADO_CLM => 'Personal Embarcado - CLM',
        self::PERSONAL_EMBARCADO_PLANTA_PERMANENTE => 'Personal Embarcado - Planta Permanente',
        self::OTRA_COMISION_SERVICIOS => 'Otra - Comision Servicios',
        self::OTRA_PLANTA_PERMANENTE_CON_DESIGNACION_TRANSITORIA => 'Otra - Planta Permanente con Designación Transitoria',
        self::OTRA_GABINETE_DE_ASESORES => 'Otra - Gabinete de Asesores',
        self::AUTORIDAD_SUPERIOR_AUTORIDAD_SUPERIOR => 'Autoridad Superior - Autoridad Superior',
        self::EXTRAESCALAFONARIO_EXTRAESCALAFONARIO => 'Extraescalafonario - Extraescalafonario',
        self::SINEP_PLANTA_PERMANENTE_MTR_CON_DESIGNACION_TRANSITORIA_CON_FUNCION_EJECUTIVA => 'SINEP - Planta Permanente MTR con Designacion Transitoria con Funcion Ejecutiva',
        self::OTRA_ADSCRIPCION => 'Otra - Adscripción',
        self::SINEP_DESIGNACION_TRANSITORIA_SIN_FUNCION_EJECUTIVA => 'SINEP - Designación Transitoria sin Función Ejecutiva',
        self::OTRA_HORAS_CATEDRA => 'Otra - HORAS CÁTEDRA',
        self::OTRA_OTRAS_MODALIDADES => 'Otra - Otras Modalidades'
    ];

/**
 * Sirve para que los resultados sean filtrados segun los permisos en AppRoles::
 * Modalidades que sean visibles y editables.
 * @return void
 */
	static public function setAutenticacion(){
		static::$REQUIERE_PERMISOS	= true;
    }

/**
 * Sirve para que los resultados sean filtrados segun los permisos en AppRoles:: 
 * Modalidades que sean solo visibles.
 * @return void
 */
	static public function setVisibles(){
		static::$PERMITE_VISIBLES	= true;
	}

/**
 * Sirve para que los resultados sean filtrados por grupo de modalidad de vinculacion.
 * @param int $id_modalidad_vinculacion
 * @return void
 */
	static public function setModalidadVinculacion($id_modalidad_vinculacion=null){
		static::$ANCLAR_MODALIDAD_VINCULACION	= (int)$id_modalidad_vinculacion;
	}

	static public function obtener($id=null) {
		if(empty($id)){
			return static::arrayToObject();
		}
		$where		= ' id_situacion_revista = :id_situacion_revista AND id = :id';
		$sql_params	= [
			':id'					=> $id,
			':id_situacion_revista'	=> $id,
		];
		if(static::$REQUIERE_PERMISOS === true && !empty(AppRoles::obtener_atributos_select()) ){
			$sql_params[':id_modalidad_vinculacion']	= AppRoles::obtener_atributos_select();
            $where	.= ' AND id_modalidad_vinculacion IN (:id_modalidad_vinculacion)';
            static::$REQUIERE_PERMISOS	= false;
            static::$PERMITE_VISIBLES	= false;
            static::$ANCLAR_MODALIDAD_VINCULACION   = false;
		}
		if(static::$PERMITE_VISIBLES === true && !empty(AppRoles::obtener_atributos_visibles()) ){
			$sql_params[':id_modalidad_vinculacion']	= AppRoles::obtener_atributos_visibles();
            $where	.= ' AND id_modalidad_vinculacion IN (:id_modalidad_vinculacion)';
            static::$REQUIERE_PERMISOS	= false;
            static::$PERMITE_VISIBLES	= false;
            static::$ANCLAR_MODALIDAD_VINCULACION   = false;
		}
		if(static::$ANCLAR_MODALIDAD_VINCULACION !== false ){
			$sql_params[':id_modalidad_vinculacion']	= static::$ANCLAR_MODALIDAD_VINCULACION;
            $where	.= ' AND id_modalidad_vinculacion = :id_modalidad_vinculacion';
            static::$REQUIERE_PERMISOS	= false;
            static::$PERMITE_VISIBLES	= false;
            static::$ANCLAR_MODALIDAD_VINCULACION   = false;
		}
		$cnx	= new Conexiones();
		$sql	= 'SELECT id, id_modalidad_vinculacion, id_situacion_revista, nombre, borrado FROM situaciones_revistas WHERE '.$where.' ORDER BY id_modalidad_vinculacion DESC LIMIT 1';
		$res	= $cnx->consulta(Conexiones::SELECT, $sql, $sql_params);
		if(empty($res[0])){
			return static::arrayToObject();
		}
		return  static::arrayToObject($res[0]);
	}

	static public function listar(){
		$where		= '';
		$sql_params	= [];
		if(static::$REQUIERE_PERMISOS === true && !empty(AppRoles::obtener_atributos_select())){
			$sql_params[':id_modalidad_vinculacion']	= AppRoles::obtener_atributos_select();
            $where	.= ' AND id_modalidad_vinculacion IN (:id_modalidad_vinculacion)';
            static::$REQUIERE_PERMISOS	= false;
            static::$PERMITE_VISIBLES	= false;
            static::$ANCLAR_MODALIDAD_VINCULACION   = false;
		}
		if(static::$PERMITE_VISIBLES === true && !empty(AppRoles::obtener_atributos_visibles())){
			$sql_params[':id_modalidad_vinculacion']	= AppRoles::obtener_atributos_visibles();
            $where	.= ' AND id_modalidad_vinculacion IN (:id_modalidad_vinculacion)';
            static::$REQUIERE_PERMISOS	= false;
            static::$PERMITE_VISIBLES	= false;
            static::$ANCLAR_MODALIDAD_VINCULACION   = false;
		}
        if(static::$ANCLAR_MODALIDAD_VINCULACION !== false ){
			$sql_params[':id_modalidad_vinculacion']	= static::$ANCLAR_MODALIDAD_VINCULACION;
            $where	.= ' AND id_modalidad_vinculacion = :id_modalidad_vinculacion';
            static::$REQUIERE_PERMISOS	= false;
            static::$PERMITE_VISIBLES	= false;
            static::$ANCLAR_MODALIDAD_VINCULACION   = false;
        }

		$cnx	= new Conexiones();
		$sql	= 'SELECT id, id_modalidad_vinculacion, id_situacion_revista, nombre, borrado FROM situaciones_revistas  WHERE borrado = \'0\' '.$where.' ORDER BY id_modalidad_vinculacion ASC ';
		$res	= $cnx->consulta(Conexiones::SELECT, $sql, $sql_params);
		if(empty($res)){
			return [];
        }
        $aux    = [];
		foreach ($res as &$resp) {
            $aux[$resp['id']]   = static::arrayToObject($resp);
		}
		return $aux;
	}

/**
 * Realiza un SituacionRevista::listar() y le da formato de array cuyo indice es el id. Ideal para usar en mapeos o en campos SELECT.
 *
 * @return array
 */
	static public function listarParaSelect(){
		$res	= static::listar();
		$aux	= [];
		foreach ($res as $obj) {
			$aux[$obj->id]	= [
				'id'		=> $obj->id,
				'nombre'	=> $obj->nombre,
				'borrado'	=> $obj->borrado,
			];
		}
		return $aux;
	}

	public function baja(){
		if(empty($this->id)){
			return false;
		}
		$cnx	= new Conexiones();
		$sql	= 'UPDATE situaciones_revistas SET borrado = :borrado WHERE id = :id';
		$res	= $cnx->consulta(Conexiones::UPDATE, $sql, [
			'id'						=> $this->id,
			'borrado'					=> $this->borrado,
		]);
		if(empty($res)){
			return false;
		}
		return true;
	}
	public function modificacion(){
		if(empty($this->id)){
			return false;
		}
		$cnx	= new Conexiones();
		$sql	= 'UPDATE situaciones_revistas SET nombre = :nombre, borrado = :borrado WHERE id = :id';
		$res	= $cnx->consulta(Conexiones::UPDATE, $sql, [
			'id'						=> $this->id,
			'nombre'					=> $this->nombre,
			'borrado'					=> ((bool)$this->borrado) ? '1' : '0',
		]);
		if(empty($res)){
			return false;
		}
		return true;
	}
	public function alta(){
		if(!empty($this->id)){
			return false;
		}
		$cnx	= new Conexiones();
		$sql	= 'INSERT INTO situaciones_revistas (id, id_modalidad_vinculacion, id_situacion_revista, nombre, borrado) VALUES (:id, :id_modalidad_vinculacion, :id_situacion_revista, :nombre, :borrado)';
		$res	= $cnx->consulta(Conexiones::INSERT, $sql, [
			'id'						=> $this->id_situacion_revista,
			'id_modalidad_vinculacion'	=> $this->id_modalidad_vinculacion,
			'id_situacion_revista'		=> $this->id_situacion_revista,
			'nombre'					=> $this->nombre,
			'borrado'					=> $this->borrado,
		]);
		if($res !== false){
			$this->id	= $res;
			return true;
		}
		return false;
	}
	public function validar(){}

    /**
     * Devuelve el ID de la modalidad de vinculacion a partir del ID de contrato. 
     * Especialmente util para convinar con `AppRoles::puede_atributo()`
     *
     * @param int $id_situacion_revista
     * @return int
     */
    static public function obtenerModalidad($id_situacion_revista=null){
        $cnx	= new Conexiones();
        $res	= $cnx->consulta(Conexiones::SELECT, 'SELECT id_modalidad_vinculacion FROM situaciones_revistas WHERE id_situacion_revista = :id_situacion_revista LIMIT 1', [
            ':id_situacion_revista' => $id_situacion_revista,
        ]);
        return \FMT\Helper\Arr::path($res, '0.id_modalidad_vinculacion', false);
    }

	public static function arrayToObject($res = []) {
		static::$REQUIERE_PERMISOS	= false;

		$campos	= [
			'id'						=> 'int',
			'id_modalidad_vinculacion'	=> 'int',
			'id_situacion_revista'		=> 'int',
			'nombre'					=> 'string',
			'borrado'					=> 'int',
		];
		$obj = new static();
		foreach ($campos as $campo => $type) {
			switch ($type) {
				case 'int':
					$obj->{$campo}	= isset($res[$campo]) ? (int)$res[$campo] : null;
					break;
				case 'json':
					$obj->{$campo}	= isset($res[$campo]) ? json_decode($res[$campo], true) : null;
					break;
				case 'datetime':
					$obj->{$campo}	= isset($res[$campo]) ? \DateTime::createFromFormat('Y-m-d H:i:s', $res[$campo]) : null;
					break;
				case 'date':
					$obj->{$campo}	= isset($res[$campo]) ? \DateTime::createFromFormat('Y-m-d', $res[$campo]) : null;
					break;
				default:
					$obj->{$campo}	= isset($res[$campo]) ? $res[$campo] : null;
					break;
			}
		}
		return $obj;
	}

	/**
	 * Retorna las situaciones de revista incluidas en los 2 tipos de reportes planilla reloj e informe mensual TODO: VER DE HACERLO DINAMICO.
	 *
	 * @param int $tipo_reporte - OTRAS MODALIDADES (1) o LEY MARCO (2)
	 * @return array
	*/
	public static function idsReporte($tipo_reporte){
		if ($tipo_reporte == Empleado::OTRAS_MODALIDADES) {
			return  ['5','6','7','10','19'];  //MODALIDADES PRESTACIONES DE SERVICIOS y OTRA-COMISION SERVICIOS
		}else{
			return ['1','2','3','4','8','9','11','12','13','14','15','16','17','18']; //SINEP - PERSONAL EMBARCADO - AUTORIDAD SUPERIOR - OTRA EXCEPTO COMISION DE SERVICIOS- EXTRAESCALAFONARIA
		}

	}
}