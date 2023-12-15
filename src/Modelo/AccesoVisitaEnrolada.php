<?php namespace App\Modelo;

use App\Helper\Conexiones;
use App\Helper\Util;
use App\Helper\Validador;
use FMT\Logger;
use FMT\Modelo;



/**
 * Gestiona las visitas enroladas
 * Class AccesoVisitaEnrolada
 */
class AccesoVisitaEnrolada extends Modelo
{

    /** @var int */
    public $id;
    /** @var int */
    public $visita_id;
    /** @var Visita */
    public $visita;
    /** @var int */
    public $tipo_acceso;
	/** @const Define el índice de la clase en la base de datos para la relación con la tabla correspondiente */
	const TIPO_MODEL = 4;


    /**
     * @param int $id
     * @return AccesoVisitaEnrolada
     */
    static public function obtener($id = null)
    {
        $sql = "SELECT id, visita_id, :tipo_acceso AS tipo_acceso
				FROM accesos_visitas_enroladas
				WHERE id = :id;";


        if (is_numeric($id)) {
            if ($id > 0) {
                $params = [
                    ':id' => $id,
                    ':tipo_acceso' => Acceso::VISITA_ENROLADA
                ];
                $conex = new Conexiones();
                $res = $conex->consulta(Conexiones::SELECT, $sql, $params);

                if (!empty($res) && is_array($res) && isset($res[0])) {
                    return static::arrayToObject($res[0]);
                }
            } else {

                return static::arrayToObject();
            }
        }

        return null;
    }

    public function alta() {

        if ($this->validar()){
            $conex = new Conexiones();
            $sql = "INSERT INTO accesos_visitas_enroladas (visita_id) VALUE (:visita_id)";
            $res = $conex->consulta(Conexiones::INSERT, $sql,
                [
                    ':visita_id' => $this->visita_id
                ]);
            if (!empty($res) && is_numeric($res) && $res > 0) {
                $this->id = (int)$res;
                //Log
                $datos = (array)$this;
                $datos['modelo'] = 'acceso_visita_enrolada';
                Logger::event('alta', $datos);

                return true;
            }
        }

        return false;
    }

    public static function enVisita($documento, $ubicacion_id, $fecha = null) {
        $class_str = Acceso::getClassIndex(new self());
        if (!empty($documento)) {
            $sql = "SELECT acc.id
					FROM accesos AS acc
					JOIN accesos_visitas_enroladas AS av ON acc.tipo_id = av.id AND acc.tipo_modelo = :clase
					JOIN visitas AS v ON av.visita_id = v.visita_id
					JOIN personas AS p ON v.persona_id = p.id";
            $params = [
                ':documento' => $documento,
                ':clase'     => $class_str,
            ];
            $where = [
                "p.borrado = 0",
                "acc.hora_egreso IS NULL",
                "p.documento = :documento",
            ];
            if ($ubicacion_id > 0) {
                array_push($where, 'acc.ubicacion_id = :ubicacion_id');
                $params[':ubicacion_id'] = $ubicacion_id;
            }
            if ($fecha) {
                array_push($where, "acc.hora_ingreso LIKE :fecha");
				$params[':fecha'] = '%'.$fecha->format('Y-m-d').'%';
            }
            if (count($where) > 0) {
                $sql .= "\nWHERE";
                for ($i = 0; $i < count($where); $i++) {
                    if ($i < count($where) - 1) {
                        $sql .= " {$where[$i]} AND";
                    } else {
                        $sql .= " {$where[$i]}";
                    }
                }
            }
            $conex = new Conexiones();
            $res = $conex->consulta(Conexiones::SELECT, $sql.' ORDER BY acc.id DESC LIMIT 1 ', $params);

            if (!empty($res) && is_array($res) && isset($res[0])) {
                return $res[0]['id'];
            }
        }

        return false;
    }

    public function validar() {
        $rules = [
            'visita_id' => ['required', 'numeric']
        ];
        $input_names = [
            'visita_id' => "Visita enrolada"
        ];
        $validator = Validador::validate((array)$this, $rules, $input_names);
        if ($validator->isSuccess() == true) {
            return true;
        } else {
            $this->errores = $validator->getErrors();

            return false;
        }
    }

    public function baja()
    {
    }

    public function modificacion()
    {
    }

    private static function arrayToObject($res = []) {
        /** @var AccesoVisitaEnrolada $obj */
        $obj = new static();
        $obj->id = isset($res['id']) ? (int)$res['id'] : 0;
        $obj->visita_id = isset($res['visita_id']) ? (int)$res['visita_id'] : 0;
        $obj->visita = Visita::obtener($obj->visita_id);
        $obj->tipo_acceso = isset($res['tipo_acceso']) ? (int)$res['tipo_acceso'] : 0;

        return $obj;
    }

    /**
     * @param int     $acceso_id
     * @param Persona $persona_egreso
     * @param int     $tipo_egreso
     * @param \DateTime|string $hora_egreso
     * @return bool
     */
    public function terminar($acceso_id, $persona_egreso, $tipo_egreso, $hora_egreso = 'now', $observaciones='') {
        $sql = "UPDATE accesos AS acc
				SET acc.hora_egreso     = :hora_egreso,
					acc.persona_id_egreso = :persona_id_egreso,
					acc.tipo_egreso       = :tipo_egreso,
					acc.observaciones     = :observaciones
				WHERE acc.id = :acc_id AND acc.tipo_id = :id AND acc.tipo_modelo = :clase";
        $conex = new Conexiones();

        if(!is_a($hora_egreso, 'DateTime')){
            $hora_egreso = new \DateTime($hora_egreso);
        }

        $params = [
            ':acc_id'            => $acceso_id,
            ':id'                => $this->id,
            ':clase'             => Acceso::getClassIndex(new self()),
            ':persona_id_egreso' => $persona_egreso->id,
            ':tipo_egreso'       => $tipo_egreso,
            ':hora_egreso'       => $hora_egreso->format('Y-m-d H:i:s'),
            ':observaciones'     => $observaciones,
        ];
        $res = $conex->consulta(Conexiones::UPDATE, $sql, $params);
        if (!empty($res) && is_numeric($res) && $res > 0) {
            //Log
            $datos = (array)$this;
            $datos['modelo'] = 'acceso_visitas_enroladas';
            Logger::event('fin_visita_enrolada', $datos);

            return true;
        }

        return false;
    }

}