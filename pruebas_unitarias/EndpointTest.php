<?php
use \App\Modelo;

class EndpointTest extends \PHPUnit\Framework\TestCase
{

	public function testEndpointMailer(){
		$config	= \FMT\Configuracion::instancia();
		if(empty($config['email']['email_to_pruebaunitaria'])){
			$this->fail('No esta seteado un email de destino en "mail.php" con la variable "email_to_pruebaunitaria"');
			throw new \Exception('No esta seteado un email de destino en "mail.php" con la variable "email_to_pruebaunitaria"', 1);
		}

		$Email	= new \FMT\Mailer();
		$Email->servidor	= $config['email']['host'];
		$Email->puerto		= $config['email']['port'];
		$Email->usuario		= $config['email']['user'];
		$Email->clave		= $config['email']['pass'];
		$Email->SMTPAuth	= $config['email']['SMTPAuth'];
		$Email->SMTPAutoTLS	= $config['email']['SMTPAutoTLS'];
		$Email->CharSet		= 'utf8';
		$Email->isHTML();
		$Email->agregarDestinatario($config['email']['email_to_pruebaunitaria'], 'Test Unitario SIGARHU');
		$Email->setearRemitente($config['email']['from'], '');
		$Email->titulo		= 'Test Unitario SIGARHU testEndpointMailer';
		$Email->cuerpo		= "nada por aqui, solo un test unitario con \FMT\Mailer()";
		$Email->enviar();

		if($Email->ErrorInfo) {
			$this->fail('Error en el envio de emails. '.implode(',', (array)$Email->ErrorInfo));
		} else {
			$this->assertTrue(true);
		}
    }
	
	public function testEndpointPHPMailer_2(){
		$config	= \FMT\Configuracion::instancia();
		if(empty($config['email']['email_to_pruebaunitaria'])){
			$this->fail('No esta seteado un email de destino en "mail.php" con la variable "email_to_pruebaunitaria"');
			throw new \Exception('No esta seteado un email de destino en "mail.php" con la variable "email_to_pruebaunitaria"', 1);
		}

		
		$email	= new \App\Helper\Email();
		$email->set_asunto("Test Unitario SIGARHU testEndpointPHPMailer_2")
			->set_contenido("nada por aqui, solo un test unitario con \App\Helper\Email()", false)
	 		->set_destinatario($config['email']['email_to_pruebaunitaria'])
			 ->enviar();
		$error	= $email->tieneErrores();

		if($error) {
			$this->fail('Error en el envio de emails. '.implode(',', (array)$error));
		} else {
			$this->assertTrue(true);
		}
    }

    public function testEndpointUsuarios(){
		try {
			$test	= \FMT\Usuarios::getUsuarios();
			$this->assertGreaterThan(0, count($test), 'Fallo Usuarios::getUsuarios');
		} catch(Exception $e) {
			$this->fail($e->getMessage());
		}
    }

    public function testEndpointUbicaciones(){
		try {
			$test	= \FMT\Ubicaciones::get_regiones('AR');
			$this->assertIsObject($test, 'Fallo Ubicaciones::get_regiones');
		} catch( \Exception $e) {
			$this->fail($e->getMessage());
		}
    }

    public function testEndpointInformacionFecha(){
		try {
			$test	= \FMT\Informacion_fecha::dias_habiles_hasta_fecha(\DateTime::createFromFormat('Y-m-d', date('Y-m-d')), 10);
			$this->assertIsString($test, 'Fallo Informacion_fecha::dias_habiles_hasta_fecha');
		}catch( \Exception $e ){
			$this->fail($e->getMessage());
		}
	}
}