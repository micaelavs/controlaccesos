/**
 * Ejemplo de prueba:
 *
setTimeout(function(msj){
	msj.ocultarMensaje();
}, 3000,
	(new Mensajes()).setMensaje('Prueba de mensaje 1', 'success', 'check').printMensaje()
);
setTimeout(function(msj){
	msj.ocultarMensaje();
}, 3000,
	(new Mensajes()).setError('Prueba de error').printError()
	.setError('Prueba de error').printError()
);
*/

var Mensajes	= (function (cnt=null){
/**@attr {string} tipoMensaje*/
	var tipoMensaje	= 'success';
/**@attr {string} iconoMensaje*/
	var iconoMensaje= 'check';
/**
 * Texto de para mostrar como mensaje.
 * @attr {string} mensajes
*/
	var mensajes	= null;
/**
 * Texto/s de error a mostrar.
 * @attr {string|object} errores
*/
	var errores		= null;
/**
 * Contexto global donde agregar/quitar html.
 * @attr {object} contexto
*/
	var contexto	= null;
/**
 * Id que identifica a la propia instancia de mensajes.
 * @attr {string} owner_id
*/
	var owner_id	= null;

/**
 * Se ejecuta al instanciar la clase y define el contexto donde se deben insertar los mensajes.
 *
 * @param {JQuery}	cnt	- Objeto JQuery
 * @return {self}
 * @private
*/
	(function setContexto(cnt=null){
		owner_id	= '_' + Date.now();

		if(cnt === null)
			contexto	= $('body > div.container div.mensajeria');
		else
			contexto	= cnt;
		return this;
	})(cnt);
/**
 * @param {object} html	- Contenido html que se quiere insertar. Normalmente un objeto de JQuery
 * @return {void}
 * @private
*/
	var agregarAlContexto	= function(html=null){
		contexto.append(html);
	}
/**
 * Setea el texto, la clase bootstrap e icono del mensaje.
 *
 * @param {string} $texto	- Mensaje que se debe mostrar
 * @param {string} $tipo	- Estilo color de la caja segun Poncho. Default: success
 * @param {string} $icono	- icono de la caja. Default: check (cruz de cierre)
 * @return {self}
 * @public
*/
	this.setMensaje		= function($texto='', $tipo='success', $icono='check'){
		mensajes	= $texto;
		tipoMensaje	= $tipo;
		iconoMensaje= $icono;
		return this;
	}
/**
 * Setea el texto para mostrar errores.
 *
 * @param {string|array} $texto	- Mensaje que se debe mostrar
 * @return {self}
 * @public
*/
	this.setError			= function($texto=''){
		errores	= $texto;
		return this;
	}
/**
 * Agrega al contexto el html formado con el mensaje.
 *
 * @return {self|Error}
 * @public
*/
	this.printMensaje	= function(){
		if(mensajes === null)
			throw new Error('El contenido de "mensajes" no esta definido, use ".setMensaje()" previo a este metodo.');

		var button			= $('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>');
		var icono_mensaje	= $('<i class="fa fa-' + iconoMensaje + '"></i> <span>' + mensajes + '</span>');
		var div				= $('<div class="alert alert-' + tipoMensaje + ' alert-dismissible ' + owner_id + '" role="alert"></div>');
		div
		.append(button)
		.append(icono_mensaje);

		agregarAlContexto(div);
		return this;
	}
/**
 * Agrega al contexto el html formado con el mensaje de error.
 *
 * @return {self|Error}
 * @public
*/
	this.printError		= function(){
		if(errores === null)
			throw new Error('El contenido de "errores" no esta definido, use ".setErr()" previo a este metodo.');

		var button			= $('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>');
		var icono_mensaje	= '';

		if(typeof errores === 'object' && Array.isArray(errores))
			errores.forEach(function(texto, indice){
				icono_mensaje += $('<div><i class="fa fa-times-circle"></i>' + texto + '</div>');
			});
		else
			icono_mensaje	= $('<div><i class="fa fa-times-circle"></i> <span>' + errores + '</span> </div>');

		var div		= $('<div class="alert alert-danger alert-dismissible ' + owner_id + '" role="alert"></div>');
		div
		.append(button)
		.append(icono_mensaje);

		agregarAlContexto(div);
		return this;
	}
/**
 * Oculta con un fade el div propio que contiene el mensaje o error.
 *
 * @return {self}
 * @public
*/
	this.ocultarMensaje	= function(){
		$('.' + owner_id).fadeOut();
		return this;
	}
/**
 * Muestra con un fade el div propio que contiene el mensaje o error.
 *
 * @return {self}
 * @public
*/
	this.mostrarMensaje	= function(){
		$('.' + owner_id).fadeIn();
		return this;
	}
/**
 * Devuelve el objecto JQuery con el mensaje/error propio.
 *
 * @return {object} JQuery
 * @public
*/
	this.getMensajeHtml	= function(){
		return $('.' + owner_id);
	}
});