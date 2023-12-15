<?php
namespace App\Helper;
use FMT\Configuracion;
use PHPMailer\PHPMailer\PHPMailer;
use App\Helper\Vista;

/**
 * Clase para enviar emails. Las vistas se levantan en la carpeta "templates/nombre_de_vista.php"
 *
 * Ejemplo de uso para **un** email:
 *
 * (new \App\Helper\Email())
 *		->set_asunto('Asunto del Manesaje')
 *		->set_contenido('mensaje_aviso_mail', [
 *			'VARIABLE_1' => 'Testing'
 *		])
 *		->set_destinatario('rickz_anches@grr.la')
 *		->enviar()
 *		->tieneErrores();
 *
 * Ejemplo de uso para **multiples** emails:
 *
 * $muchos_emails	= ['rick_zanches@grr.la', 'morty@grr.la', 'veronica@grr.la'];
 * (new \App\Helper\Email())
 * 		->set_asunto('Asunto del Manesaje')
 * 		->set_contenido('mensaje_aviso_mail', [
 * 			'VARIABLE_1' => 'Testing'
 * 		])
 * 		->batch($muchos_emails);
 */
class Email  {
	protected $enviados_exitoso	= 0;
	protected $enviados_total	= 0;
	protected $enviados_fallidos= 0;
/**
 * True cuando tiene errores, false cuando no
 * @var        boolean
 */
	private	$error				= false;
/**
 * Almacena la instancia de PHPMailer.
 * @var        PHPMailer
 */
	protected $email 			= null;

	public function __construct(){
		$this->obtener(); 
	}
/**
 * Genera la instacia de PHPMailer en **$this->email** y lo configura.
 *
 * @return     PHPMailer
 */
	public function obtener($mantener_instancia=true){
		static $config = null;
		if($config === null) $config = Configuracion::instancia();
		if($mantener_instancia && $this->email !== null) return $this->email;

		$this->email				= new PHPMailer(true);
		$this->email->isHTML(true);
    	$this->email->isSMTP();
		$this->email->Host 			= $config['email']['host'];
		$this->email->Port 			= $config['email']['port'];
		$this->email->Username 		= $config['email']['user'];
		$this->email->Password 		= $config['email']['pass'];
		$this->email->SMTPAuth 		= $config['email']['SMTPAuth'];
		$this->email->SMTPAutoTLS 	= $config['email']['SMTPAutoTLS'];
		$this->email->CharSet 		= 'UTF-8';

		if(!empty($config['email']['debug']))
			$this->email->SMTPDebug 	= 3;

		if(!empty($config['email']['insecure']))
			$this->email->SMTPOptions 		= [
				'ssl'	=> [
					'verify_peer'		=> false,
					'verify_peer_name'	=> false,
					'allow_self_signed'	=> true,
				],
			];

		$this->set_remitente();
		return $this->email;
	}
/**
 * Setea el remitente. Si no se le pasan valores usa los especificados en la configuracion.
 *
 * @param      string  $email   email
 * @param      string  $nombre  nombre
 * @return     self
 */
	public function set_remitente($email='', $nombre=''){
		static $config = null;
		if($config === null)
			$config	= Configuracion::instancia();

		if(empty($email))
			$email	= $config['email']['from'];
		if(empty($nombre))
			$name	= $config['email']['name'];

		$this->email->setFrom($email, $name);
		return $this;
	}
/**
 * Configura el asunto del mensaje.
 *
 * @param      string  $titulo  El asunto del mensaje
 * @return     self
 */
	public function set_asunto($titulo=''){
		$this->email->Subject	= $titulo;
		return $this;
	}
/**
 * Setea el contenido. Si **$path" es true, **$template** es la vista de un archivo .
 *
 * @param      string   $template	Template Html o un archivo ubicado en "VISTAS_PATH . 'template/'"
 * @param      array    $vars		Opcional. Variables que se le pueden pasar al Template, si es null no se renderiza con Vista::
 * @param      boolean  $debug		Imprime el contenido del mensaje, tal como se mostraria en caso de ser enviado. (tambien se debe configurar en ./config/mail.php --> ['email']['debug'] )
 * @return     self
 */
	public function set_contenido($template='', $vars=null, $debug=false){
		static $config = null;
		if($config === null)
			$config	= Configuracion::instancia();

		if(is_array($vars)){
			$template	= (new Vista($template, $vars))->render();
		}
		if($debug && !empty($config['email']['debug'])) {
			throw new \Exception(var_export($template, true), 1);
			
		}
		$this->email->Body	= $template;
		return $this;
	}
/**
 * Setea el email de un destinatario y limpia las anteriores.
 *
 * @param      string  $email  Email destino
 * @return     self
 */
	public function set_destinatario($email='',$limpiar=true){
		if($limpiar){
			$this->limpiar_direcciones();
		}
		$this->email->addAddress($email);
		return $this;
	}
/**
 * Adjuntar archivos al email.
 *
 * @param      string  $url  	- Url del archivo
 * @param      string  $nombre  - Nombre del archivo al ser adjunto
 * @return     self
 */
	public function add_attachment($url='', $nombre = ''){
		$this->email->addAttachment($url, $nombre);
		return $this;
	}
/**
 * Envia el email.
 *
 * @return     self
 */
	public function enviar(){
		try {
			$this->email->send();
		} catch (\Exception $e) {
			$this->setErrores();
		}
		return $this;
	}
/**
 * Setter para **$this->error**
 * @return     boolean
 */
	public function tieneErrores() {
		return $this->error;
	}
/**
 * Getter para **$this->error**
 * @return     boolean
 */
	private function setErrores($mensaje = null) {
		$this->error	= true;
	}
/**
 * Limpia las direcciones de los destinatarios.
 *
 * @return     self
 */
	public function limpiar_direcciones(){
		$this->email->clearAddresses();
		return $this;
	}
/**
 * Enviar mensaje a multiples correos.
 * El array de acciones para **$ejecutar_despues** recibe una funcion del tipo Closure, al ejecutarse se le pasa como primer parametro **$this**, y como segundo la dirccion de email que se intenta usar.
 *
 * - $ejecutar_despues
 * - - Closure 'enviado'	- Se ejecuta despues de un envio exitoso
 * - - Closure 'error'		- Se ejecuta despues de un envio erroneo
 * - - Closure 'siempre'	- Se ejecuta despues cada ciclo independientemente que salga bien o mal.
 *
 * @param      Array  $emails             Lista de emails, indice numerico.
 * @param      Array  $ejecutar_despues  Funciones que se ejecutan despues de concluir cada etapa del envio por cada direccion.
 * @return     array   ( description_of_the_return_value )
 */
	public function batch(Array $emails=null, Array $ejecutar_despues=null){
		$ejecutar_despues	= array_merge([
			'enviado'	=> function($that, $email){ },
			'error'		=> function($that, $email){ },
			'siempre'	=> function($that, $email){ },
		], (array)$ejecutar_despues);

		foreach ($emails as $direccion) {
			$this->enviados_total++;
			$this->email->addAddress($direccion);
			if(!$this->enviar()->tieneErrores()){
				$this->enviados_exitoso++;
				if($ejecutar_despues['enviado'] !== null && is_object($ejecutar_despues['enviado']) && ($ejecutar_despues['enviado'] instanceof Closure)){
					$ejecutar_despues['enviado']($this, $direccion);
				}
			} else {
				$this->enviados_fallidos++;
				if($ejecutar_despues['error'] !== null && is_object($ejecutar_despues['error']) && ($ejecutar_despues['error'] instanceof Closure)){
					$ejecutar_despues['error']($this, $direccion);
				}
			}
			$this->email->clearAddresses();
			if($ejecutar_despues['siempre'] !== null && is_object($ejecutar_despues['siempre']) && ($ejecutar_despues['siempre'] instanceof Closure)){
				$ejecutar_despues['siempre']($this, $direccion);
			}
		}
		return [
			'enviados_total'	=> $this->enviados_total,
			'enviados_exitoso'	=> $this->enviados_exitoso,
			'enviados_fallidos'	=> $this->enviados_fallidos,
		];
	}
}
