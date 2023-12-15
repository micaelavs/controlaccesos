<?php namespace App\Controlador;

use App\Helper\Msj;
use App\Helper\Util;
use App\Helper\Validador;
use App\Helper\Vista;
use App\Modelo\Acceso;
use App\Modelo\AppRoles;
use App\Modelo\Empleado;
use App\Modelo\Persona;
use App\Modelo\Tarjeta;
use App\Modelo\AccesoVisita;
use App\Modelo\Ubicacion;
use App\Modelo\Reloj;
use App\Modelo\Reloj_Tarjeta;
use App\Modelo\RelojTarjeta;

class Tarjetas extends Base {

	public function accion_index(){
		$listaRelojes = $this->lista_select_relojes(\App\Modelo\Reloj::listarRelojes_TM());
        $vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','listaRelojes')))->pre_render();
	}

	public function accion_alta() {
        $tarjeta = Tarjeta::obtener(null);

        if($this->request->post('operacion') == "alta"){
            
		}
        $vista = $this->vista;
		(new Vista($this->vista_default, compact('vista','tarjeta')))->pre_render();
	}

	/*ENROLAR LISTA DE TARJETAS SELECCIONADAS*/
	public function accion_actualizar_tarjeta() {

		$array_nro_tarjetas = !empty($temp = $this->request->post('access_id')) ? explode(",",$temp) : [];
		$nodo_id = !empty($temp = $this->request->post('nodo_id')) ? $temp : null;
		$tarjetas_nuevas = !empty($temp = $this->request->post('tarjetas_nuevas')) ? explode(",",$temp) : [];
        $tarjetas_existentes = Tarjeta::listar();		
		$nrosExistentes = array();
		$reloj = Reloj::obtenerPorNodo($nodo_id);		
		$success['altas'] = [];
		$success['actualizadas'] = [];
		$success['errores'] = [];

		/* ARMA UN ARRAY CON LOS NROS DE TARJETAS EXISTENTES EN DB */
		for ($i=0; $i < count($tarjetas_existentes) ; $i++) { 
			array_push($nrosExistentes, $tarjetas_existentes[$i]['access_id']);
		}

		/* RECORRO EL ARRAY DE TARJETAS INSERTADAS POR INPUT Y CHEQUEO SI EXISTE EN EL ARRAY DE NROS DE DB
		SI NO EXISTE SE DA DE ALTA*/
		for ($i=0; $i < count($tarjetas_nuevas) ; $i++) { 
			if (!in_array($tarjetas_nuevas[$i], $nrosExistentes)) {
                $tarjeta = Tarjeta::obtener(null);
                $tarjeta->access_id = $tarjetas_nuevas[$i];
				if($tarjeta->validar()){
					$resp = $tarjeta->alta();
					if($resp){
						$success['altas'][$i] = $tarjeta->access_id;
					}
				}else{
						$success['errores'][$i] = $tarjeta->errores;
				}
			}	
		}		


		/* RECORRO EL ARRAY DE LAS TARJETAS SELECCIONADAS (POR INPUT Y POR SELECT) Y ACTUALIZO EN RELOJ*/
		for ($i=0; $i < count($array_nro_tarjetas) ; $i++) {
			$tarjeta = Tarjeta::obtenerTarjetaPorNro($array_nro_tarjetas[$i]);
            $relojTarjeta = RelojTarjeta::obtener();
            $relojTarjeta->id_reloj = $reloj->id;
            $relojTarjeta->id_tarjeta = $tarjeta->id;
			if($relojTarjeta->validar()){
				$resp = $relojTarjeta->alta();
			}else{
				$success['errores'][$i] = $relojTarjeta->errores;
			}
			$respNroTarjeta = $tarjeta->actualizarNroTarjeta($nodo_id); //Envío el nro de tarjeta al reloj
			if($resp || $respNroTarjeta){
				$success['actualizadas'][$i] = $tarjeta->access_id;
			}			
		}
		

        $data = $success;
		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}

	/*DESENROLAR LISTA DE TARJETAS SELECCIONADAS*/
	public function accion_actualizar_tarjeta_desenrolar() {

		$array_nro_tarjetas = !empty($temp = $this->request->query('access_id')) ? explode(",",$temp) : [];
		$nodo_id =  !empty($temp = $this->request->query('nodo_id')) ? $temp : null;
		$reloj = Reloj::obtenerPorNodo($nodo_id);		
		$success['bajas'] = [];
		$success['actualizadas'] = [];

		/* RECORRO EL ARRAY DE LAS TARJETAS SELECCIONADAS (POR SELECT) PARA DESENROLAR Y ACTUALIZO EN RELOJ*/
		for ($i=0; $i < count($array_nro_tarjetas) ; $i++) {
			$tarjeta = Tarjeta::obtenerTarjetaPorNro($array_nro_tarjetas[$i]); 
            $relojTarjeta = RelojTarjeta::obtener();
            $relojTarjeta->id_reloj = $reloj->id;
            $relojTarjeta->id_tarjeta = $tarjeta->id;
			$relojTarjeta::$ACCION_TARJETA = "baja";
            if($relojTarjeta->validar()){
                $resp = $relojTarjeta->baja();
                $respNroTarjeta = $tarjeta->actualizarNroTarjetaDesenrolar($nodo_id);
				if($resp){
					$success['bajas'][$i] = $tarjeta->access_id;
				}
				if($respNroTarjeta){
					$success['actualizadas'][$i] = $tarjeta->access_id;
				}					
            }
		}

        $data = $success;
        (new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}	

	public function accion_actualizar_listaTM()
	{
		$nodo_id =  !empty($temp = $this->request->post('reloj_seleccionado')) ? $temp : null;
		$reloj = Reloj::obtenerPorNodo($nodo_id);	
		$data	= [
			'tm'	=> Tarjeta::getAllTM($reloj->id)
		];
		(new Vista(VISTAS_PATH.'/json_response.php',compact('data')))->pre_render();
	}

	/**
	 * ===================================
	 *  FUNCIONES INTERNAS DEL CONTROLADOR
	 * ===================================
	 */
	private function lista_select_relojes($lista = []){
		$listado = [];

		foreach ($lista as $item) {
			if(is_object($item)){
				$listado[$item->nodo] = ['id' => $item->nodo, 'nombre' =>$item->nodo,'borrado' => $item->borrado];
			}else{
				$listado[$item["nodo"]] = ['id' => $item["nodo"], 'nombre' =>$item["nodo"],'borrado' => $item["borrado"]];
			}
		}
		return $listado;
	}

}