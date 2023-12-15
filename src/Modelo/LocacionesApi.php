<?php
namespace App\Modelo;

class LocacionesApi extends \FMT\ApiCURL{
    static private $ERRORES = false;

    static public function getListadoLocaciones() {
        $api = static::getInstance();
        $return = $api->consulta('GET', "/get_locaciones");
        if($api->getStatusCode() != '200'){
            static::setErrores($return['mensajes']);
            return false;
        }
        return $return['data'];
    }

    static public function getListadoEdificios() {
        $api = static::getInstance();
        $return = $api->consulta('GET', "/get_edificios");
        /*if($api->getStatusCode() != '200'){
            static::setErrores($return['mensajes']);
            return false;
        }*/
        return $return['data'];
    }

    static public function getListadoOficinas_reloj() {
        $api = static::getInstance();
        $return = $api->consulta('GET', "/get_oficinas_reloj");
        if($api->getStatusCode() != '200'){
            static::setErrores($return['mensajes']);
            return false;
        }

        return $return['data'];

    }
     
    static protected function setErrores($data=false){
        static::$ERRORES = $data;
    }
    
    static public function getErrores(){
        return static::$ERRORES;
    }
}