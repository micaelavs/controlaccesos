function $alert(mensaje, tipo, icono) {
	var $icono = $("<span>").addClass('fa fa-fw fa-' + icono);
	var $close = $("<button>")
		.append($("<span>")
			.html("&times;")
			.prop('aria-hidden', true))
		.attr(
			{
				type: 'button',
				class: 'close',
				'data-dismiss': 'alert',
				'aria-label': 'Close'
			}
		);
	return $("<div>")
		.attr('role', 'alert')
		.addClass('alert alert-' + tipo + ' alert-dismissible')
		.append($close)
		.append($icono)
		.append(mensaje);
}

function initToolTips() {
	$('[data-toggle="tooltip"]').tooltip({html: true});
	$('[data-toggle="popover"]').popover({html: true});
}

/**
 * @typedef {Object} Acceso
 * @property {String} credencial
 * @property {String} nombre
 * @property {String} origen
 * @property {String} destino
 * @property {String} observaciones
 * @property {integer} acc_id
 * @property {integer} autorizante_id
 * @property {String} fecha_ingreso
 * @property {String} fecha_egreso
 * @property {String} hora_entrada
 * @property {String} hora_salida
 * @property {integer} persona_id
 * @property {integer} tipo_id
 * @property {String} tipo_modelo
 */
/**
 * @typedef {Object} AjaxResult
 * @property {Boolean} success
 * @property {Array.<Acceso>} lista
 */
  function AbrirModal(id, obs) {
  	$('#myModal').modal('show');
  	if(obs == 'null'){
  		obs = '';
  	}
  	$('#observaciones').val(obs);
   	$('#idAcceso').val(id);
  }

  $(document).on('click', function (e) {
    //did not click a popover toggle or popover
    if ($(e.target).data('toggle') !== 'popover'
        && $(e.target).parents('.popover.in').length === 0) { 
        $('.popover').popover('hide');
    }
});

$(document).ready(function () {
	
	"use strict";
	
	function geMoment() {
		var time = moment().format("H:mm");
		$("#hora_entrada").val(time);
	}
	
	geMoment();
	setInterval(function () {
		geMoment();
	}, 30000);// cada 30 segundos
	
	var $ubicacion = $('section#principal').data('ubicacion');
	
	var $table = $("#dataTable").DataTable({
		language: {
			url: $endpoint_cdn + '/datatables/1.10.12/Spanish_sym.json',
			decimal: ',',
			thousands: '.'
		},
		processing: false,
		serverSide: true,
		searchDelay: 1200,
		order: [ 6, 'desc' ],
		ajax: {
			url: $base_url + '/index.php/accesos/ajax_accesos',
			data: {
				ubicacion_id: $ubicacion,
				fecha: moment().format('DD/MM/YYYY')
			}
		},
		columns: [
			{
				data: 'credencial',
				title: 'N° de Credencial',
				name: 'credencial',
				targets: 0
			},
			{
				data: 'documento',
				title: 'Documento',
				name: 'documento',
				targets: 1
			},
			{
				data: 'nombre',
				title: 'Nombre',
				name: 'nombre',
				targets: 2,
				render: function (data, type, row) {
					var hidden = true;
					var observ = row.observaciones;
					var pert = row.pertenencias;
					var adver = row.advertencias;
					var origen = row.origen;
					var destin = row.destino;
					var style = '';
					if (row.es_visita === true){
						style = 'style="background: #9ad9ea; border-radius: 15px; text-align:center;"';
		
					}
					var orgTag = '';
					if (origen) {
						orgTag = '<strong>Origen:</strong> ' + origen;
					}
					var desTag = '';
					if (destin) {
						desTag = '<strong>Destino:</strong> ' + destin;
					}
					var obsTag = '';
					if (observ) {
						obsTag = '<p><em><u>Observaciones:</u></em> ' + observ + '</p>';
					}
					var perTag = '';
					if (pert) {
						perTag = '<p><em><u>Pertenencias:</u></em> ' + pert + '</p>';
					}
					var adverTag = '';
					if (adver) {
						adverTag = '<p><em><u>Advertencias:</u></em> ' + adver + '</p>';
					}
					if (orgTag || desTag || obsTag || perTag || adverTag) {
						hidden = false;
					}
					var orgDesTag = '<p>' + orgTag + (desTag ? '<br>' + desTag : '') + '</p>';
					var etiqueta = orgDesTag + obsTag + perTag + adverTag;
					var pop = '<a data-trigger="hover" tabindex="0" ' +
						'class="pull-right btn btn-sm btn-link" ' +
						'data-container="body" ' +
						'data-toggle="popover" ' +
						'data-placement="left" ' +
						'title="Detalles" ' +
						'data-content="' + etiqueta + '">' +
						'<i class="fa fa-clipboard"></i></a>';
					return '<div  class="text-left" '+style+'>' + data + (hidden ? '' : pop) + '</div>';
				}
			},
			{
				data: 'observaciones',
				title: 'Observaciones',
				name: 'observaciones',
				targets: 3,
				visible: false
			},

			{
				data: 'origen',
				title: 'Origen',
				name: 'origen',
				targets: 4,
				visible: false
			},
			{
				data: 'destino',
				title: 'Destino',
				name: 'destino',
				targets: 5,
				visible: false
			},
			{
				data: 'acc_id',
				title: 'Observaciones',
				name: 'acc_id',
				targets: 6,
				render: function (data, type, row) {
					var href = $base_url+"/index.php/accesos/editar_observaciones/" + row.acc_id;
					return '<a href="' + href + '" ' +
						'class="btn btn-link btn-xs btn-terminar" ' + 'id='+ row.acc_id + 'obs=' + row.observaciones +
						' title="Agregar/Editar observaciones ' + row.nombre + '"><i onclick="AbrirModal('+ row.acc_id + ', \'' + row.observaciones + '\')" class="fa fa-edit fa-fw fa-3x"></i></a>';
				}
			},
			{
				data: 'acc_id',
				title: 'Marcar Salida',
				name: 'acc_id',
				targets: 6,
				render: function (data, type, row) {
					var href = $base_url+"/index.php/accesos/baja/" + row.acc_id;
					return '<a href="' + href + '" ' +
						'class="btn btn-link btn-xs btn-terminar" ' +
						'data-toggle="tooltip" ' +
						'title="Marcar salida para ' + row.nombre + '"><i class="fa fa-sign-out fa-fw fa-3x"></i></a>';
				}
			},
			{
				data: 'destino',
				title: 'Destino',
				name: 'destino',
				targets: 5,
				visible: false
			}
		]
	}).on('draw', function () {
		initToolTips();
	});

	$('.popover-dismiss').popover({
		trigger: 'focus'
	})
	
	var predata = "";
	
	/**
	 * Verifica mediante un hash si la tabla ha sufrido cambios.
	 */
	function actualizarTabla() {
		$.ajax({
				url: "",
				data: {
					c: "acceso",
					a: "verificar_cambios_de_accesos",
					p: predata,
					ubicacion_id: $ubicacion,
					fecha: moment().format('DD/MM/YYYY')
				},
				method: "GET"
			})
			.done(function (data) {
				//if (data !== predata) {
					predata = data;
					console.error(data);
					$table.ajax.reload(null, false);
				//}
			});
	}
	
	/**
	 * Intervalo de tiempo en el que deberá hacerse una verificación de cambios en la tabla de accesos
	 * en la base de datos. En caso de que existan cambios en los registros, se procederá a actualizar
	 * la data de la interface de usuario de la página de registro de accesos.
	 * @type {number}
	 */
	
	var tiempoDeVerificacionDeCambiosEnTablaDeAccesos = 5000;
	//var tiempoDeVerificacionDeCambiosEnTablaDeAccesos = 115000;
	setInterval(function () {
		//actualizarTabla();
		//TICKET 5803. SE DETERMINA RECARGAR LA TABLA INDISTINTAMENTE  DE SI HUBIERON CAMBIOS.
		//ALGO MÁS INEFICIENTE, COMPENSANDO EVITANDO LLAMAR AL SERVIDOR CADA VEZ.
		$table.ajax.reload(null, false);
	}, tiempoDeVerificacionDeCambiosEnTablaDeAccesos);
	
	$('.tabulable').keypress(function (event) {
		if (event.which === 13) {
			// event.stopPropagation();
			event.preventDefault();
			if (event.keyCode === 13) {
				/* FOCUS ELEMENT */
				var inputs = $(this).parents("form").eq(0).find(":input");
				var idx = inputs.index(this);
				var type = $(inputs[idx + 1]).prop('tagName');
				if (idx === (inputs.length - 1)) {
					inputs[0].select()
				} else {
					inputs[idx + 1].focus(); //  handles submit buttons
					if (type === 'INPUT' || type === 'TEXTAREA') {
						inputs[idx + 1].select();
					}
				}
				return false;
			}
		}
	});
	var url = $base_url + '/index.php/empleados/json_buscar_empleado';
	$('#autorizante_nombre').typeahead({
		onSelect: function (item) {
			$("#autorizante_documento").val(item.value);
			$("#autorizante_nombre").val(item.text);
		},
		ajax: {
			url: url,
			timeout: 500,
			displayField: 'nombre',
			valueField: 'documento',
			triggerLength: 3,
			method: "get",
			loadingClass: "loading-circle",
			preDispatch: function (query) {
				return {
					search: query,
					ubicacion_id: $ubicacion
				}
			},
			preProcess: function (data) {
				if (data.success === false) {
					return false;
				}
				
				return data.lista.map(function ($empleado) {
					//return $empleado.persona;
					return {
						"idEmp": $empleado.id,
						"nombre": $empleado.nombre.concat(" ",$empleado.apellido),
						"documento":$empleado.documento,
						"oficina_contacto":$empleado.oficina_contacto,
						"oficina_interno":$empleado.oficina_interno
					}
				});
			}
		}
	});
	
	$(".validar_acceso").change(function (e) {
		var $el = $(e.target);
		var min = $el.attr('minlength');
		var habilitar = false;
		var $personaDocumento = $("#persona_documento");
		var $autorizanteDocumento = $("#autorizante_documento");
		var $credencial = $("#credencial");
		if ($el.val().length < min) {
			$el.parent().addClass('has-error');
			$el.parent().find('button').removeClass('btn-primary');
			$el.parent().find('button').addClass('btn-danger');
			habilitar = false;
		} else {
			$el.parent().removeClass('has-error');
			$el.parent().find('button').removeClass('btn-danger');
			$el.parent().find('button').addClass('btn-primary');
			habilitar = ($personaDocumento.val().length >= 6);
			if ($autorizanteDocumento.length > 0) {
				habilitar = ($autorizanteDocumento.val().length >= 6) && habilitar;
			}
			if ($credencial.length > 0) {
				habilitar = ($credencial.val().length > 0) && habilitar;
			}
			habilitar=true;
		}
		habilitar=true; //ver
		if (habilitar) {
			$("#btn_insertar").removeProp('disabled');
		} else {
			$("#btn_insertar").prop('disabled', true);
		}
	});
	
	$(document).on('click', ".btn-terminar", function (e) {
		e.preventDefault();
		var $this = $(this);
		var url = $this.attr('href');
		var $loader = $("#loader");
		$.ajax({
			url: url,
			contentType: "application/json; charset=utf-8",
			method: 'POST'
		}).done(function (data) {
			/** @var AjaxResult data */			
			if (data.status === true) {
				var tipo = 'success';
				var icono = 'sign-out';
				var mensaje = 'Salida marcada.';		
				$(".alert-dismissable").alert('close');
				$("div#mensajes").prepend($alert(mensaje, tipo, icono).fadeTo(5000, 500).slideUp(500, function () {
					$("#success-alert").slideUp(500);
				}));
			}
		}).always(function (data, textStatus, jqXHR) {
			$table.draw();
			initToolTips();
		})
	});
	initToolTips();

   $('#modal_nro_tarjeta').keypress(function(event){
       if(event.which != 8 && isNaN(String.fromCharCode(event.which))){
           event.preventDefault();
       }
   });

   $('#modal_nro_tarjeta').keypress(function(event){
       if(event.which != 8 && isNaN(String.fromCharCode(event.which))){
           event.preventDefault();
       }
   });

   $('#modal_nro_tarjeta').keyup(function(event){
   		//LONG_TARJETA_CREDENCIAL actual = 8
   	   if($('#modal_nro_tarjeta').val().length > 8){
   	   		$("#modal_nro_tarjeta").val("");
   	   }
       if($('#modal_nro_tarjeta').val().length == 8 && $("#textoTarjetaAsignar").text() == ""){
       		$("#textoTarjetaAsignar").append($('#modal_nro_tarjeta').val());
       		$("#hdNroTarjeta").val($('#modal_nro_tarjeta').val());
       		$("#modal_btn_guardar_nro_tarjeta").prop("disabled", false);
       		$("#modal_nro_tarjeta").val("");
       		$("#modal_nro_tarjeta").focus();
       }
       else{
       		if(event.which == 13){
       			return false;
       		}
       		else{
       			$("#textoTarjetaAsignar").text("");
       			$("#modal_btn_guardar_nro_tarjeta").prop("disabled", true);
       		}    		
       }
   });
   
   $('#modal_nro_tarjeta').on("cut copy paste",function(e) {
      e.preventDefault();
   }); 

	$('#modalNumeroTarjeta').on('hidden.bs.modal', function () {
	  $("#modal_nro_tarjeta").val("");
	  $("#textoTarjetaAsignar").text("");
	  $('#modal_nro_tarjeta').removeClass('form-control').addClass('oculto');
	  $('#chk_ocultar_caja_texto').prop('checked', false);
	});

	$('#modal_btn_guardar_nro_tarjeta').click(function(){
		//LONG_TARJETA_CREDENCIAL actual = 8
		//Siempre debería ser long = LONG_TARJETA_CREDENCIAL dado que el botón ASIGNAR estaba habilitado. Se valida igualmente.
		if($('#hdNroTarjeta').val().length == 8){
			$("#credencial").val($("#hdNroTarjeta").val());
			$("#modalNumeroTarjeta").modal('hide');
			$("#lbNroCodigo").text('Número Tarjeta ');
			$("#lbNroCodigo").append('<i class="fa fa-close" title="Corregir número de tarjeta" onClick="limpiarNroTarjeta()"></i>');
			$("#credencial").prop('readonly', true);
		}
	});

	$('#modalNumeroTarjeta').on('shown.bs.modal', function () {
      $('#modal_nro_tarjeta').focus();
      $("#credencial").val("");
   	});


	if($("#persona_documento").val()){
		$("#btn_buscar_documento").trigger("click");
	}

	if($("#autorizante_documento").val()){
		$("#btn_buscar_autorizante").trigger("click");
	}

});

function popUpTarjeta(){
	$("#modalNumeroTarjeta").modal('toggle');
}

function ocultarCajaTexto(){
	$("#modal_btn_guardar_nro_tarjeta").prop("disabled", true);
	$('#modal_nro_tarjeta').val("");
	$("#textoTarjetaAsignar").text("");

	if($('#modal_nro_tarjeta').hasClass('oculto')){
		$('#modal_nro_tarjeta').removeClass('oculto').addClass('form-control');
	}
	else{
		$('#modal_nro_tarjeta').removeClass('form-control').addClass('oculto');
	}

	$('#modal_nro_tarjeta').focus();
}

function limpiarNroTarjeta(){
	$("#lbNroCodigo").text('Número Credencial');
	$("#lbNroCodigo").append('<span class="required">*</span>');
	$("#credencial").prop('readonly', false);
	$("#hdNroTarjeta").val("");
	$("#credencial").val("");
}

$("#btn_buscar_documento").click(function (e) { 
    e.preventDefault();
    persona_documento = $("#persona_documento").val();
        $.ajax({
            type: "POST",
            url: $base_url + '/index.php/accesos/buscar_documento',
            data: {persona_documento : persona_documento},
            dataType: "json",
            success: function (response) {

				if (response.status === false) {
					
					var tipo = 'danger';
					var icono = 'times-circle';
					var mensaje = 'El campo Documento de Persona es obligatorio.';		
					$(".alert-dismissable").alert('close');
					$("div#mensajes").prepend($alert(mensaje, tipo, icono).fadeTo(5000, 500).slideUp(500, function () {
						$("#success-alert").slideUp(500);
					}));
				}else{
					$("#acceso").show();
				switch (response.data.tipo_acceso){
					
					case $tipo_acceso['empleado']:
						$("#empleado").show();
						$("#visita_enrolada").hide();
						$("#contratista").hide();
						$("#campos_persona").hide();
						$("#credencial").hide();
						$("#credencialTarjeta").hide();
						$("#nombre_empleado").text(response.data.empleado.nombre+' '+response.data.empleado.apellido);

						$("#pertenencias_empleado").hide();
						$("#pertenencias_visita").hide();
						$("#pertenencias_visita_enrolada").hide();
						$("#pertenencias_contratista").hide();	
						
						if ((response.pertenencias).length > 0) {
							$("#pertenencias_empleado").show();
							$("#lista_pertenencias_empleado").empty();					
							response.pertenencias.forEach( function(pertenencia) {
								$("#lista_pertenencias_empleado").append($("<li>").text(pertenencia['texto']));
							});
						}

						$("#advertencias_empleado").hide();
						$("#advertencias_visita").hide();						
						$("#advertencias_visita_enrolada").hide();
						$("#advertencias_contratista").hide();

						if ((response.advertencias).length > 0) {
							$("#advertencias_empleado").show();
							$("#lista_advertencias_empleado").empty();	
							response.advertencias.forEach( function(advertencia) {
								$("#lista_advertencias_empleado").append($("<li>").text(advertencia['texto']).append("<br>&emsp;&emsp;&emsp;<strong>"+advertencia['solicitante']['nombre']+" "+advertencia['solicitante']['apellido'] +"</strong>"))
							});
						}

					break;
					case $tipo_acceso['visita']:
						if(response.data.contratista_empleado.id == 0){
							$("#empleado").hide();
							$("#visita_enrolada").hide();
							$("#contratista").hide();
							$("#campos_persona").show();
							$("#persona_nombre").val(response.data.persona.nombre);
							$("#persona_apellido").val(response.data.persona.apellido);
						}else{
							$("#empleado").hide();
							$("#visita_enrolada").hide();
							$("#contratista").show();
							$("#campos_persona").show();
							$("#persona_nombre").val(response.data.persona.nombre);
							$("#persona_apellido").val(response.data.persona.apellido);
							$("#obs_contratista").hide();
							$("#nombre_personal_contratista").text(response.data.contratista_empleado.persona.nombre+' '+response.data.contratista_empleado.persona.apellido);
							if (response.data.contratista_empleado.contratista.id !== null){
								$nombre_contratista = response.data.contratista_empleado.contratista.nombre
							}else{
								$nombre_contratista = 'NO ASOCIADO';
							}
							$("#nombre_contratista").text($nombre_contratista);
							$("#autorizante_contratista").text(response.data.contratista_empleado.autorizante.nombre+' '+response.data.contratista_empleado.autorizante.apellido);
							$("#autorizante_documento").val(response.data.autorizante.documento);
							$("#btn_buscar_autorizante").trigger('click');

							$("#fechas_art").hide();
							$("#fechas_acc").hide();
							$("#vencido_contratista").show();
						}

						$("#pertenencias_empleado").hide();
						$("#pertenencias_visita").hide();
						$("#pertenencias_visita_enrolada").hide();
						$("#pertenencias_contratista").hide();

						if ((response.pertenencias).length > 0) {
							$("#pertenencias_visita").show();
							$("#lista_pertenencias_visita").empty();					
							response.pertenencias.forEach( function(pertenencia) {
								$("#lista_pertenencias_visita").append($("<li>").text(pertenencia['texto']));
							});
						}
						
						$("#advertencias_empleado").hide();
						$("#advertencias_visita").hide();						
						$("#advertencias_visita_enrolada").hide();
						$("#advertencias_contratista").hide();

						if ((response.advertencias).length > 0) {
							$("#advertencias_visita").show();	
							$("#lista_advertencias_visita").empty();	
							response.advertencias.forEach( function(advertencia) {
								$("#lista_advertencias_visita").append($("<li>").text(advertencia['texto']).append("<br>&emsp;&emsp;&emsp;<strong>"+advertencia['solicitante']['nombre']+" "+advertencia['solicitante']['apellido'] +"</strong>"))
							});
						}
						
					break;
					case $tipo_acceso['contratista']:
						$("#empleado").hide();
						$("#visita_enrolada").hide();
						$("#contratista").show();
						$("#campos_persona").hide();
						$("#nombre_personal_contratista").text(response.data.contratista_empleado.persona.nombre+' '+response.data.contratista_empleado.persona.apellido);
						if (response.data.contratista_empleado.contratista.id !== null){
							$nombre_contratista = response.data.contratista_empleado.contratista.nombre
						}else{
							$nombre_contratista = 'NO ASOCIADO';
						}
						$("#nombre_contratista").text($nombre_contratista);
						$("#autorizante_contratista").text(response.data.contratista_empleado.autorizante.nombre+' '+response.data.contratista_empleado.autorizante.apellido);
						$("#autorizante_documento").val(response.data.autorizante.documento);
						$("#btn_buscar_autorizante").trigger('click');
						
						var today = moment();

						//$start = response.data.contratista_empleado.art_inicio < today;//(new DateTime());
						$start = Date.parse(response.data.contratista_empleado.art_inicio.date) < Date.parse(today.format("YYYY-MM-DD HH:mm:ss"));//(new DateTime());
						//$end = response.data.contratista_empleado.art_fin > today;//(new DateTime());
						$end = Date.parse(response.data.contratista_empleado.art_fin.date) > Date.parse(today.format("YYYY-MM-DD HH:mm:ss"));//(new DateTime());
						$now = Date.parse(today.format("YYYY-MM-DD HH:mm:ss"));//new DateTime('now');
						
						if ($start && $end){
							$("#vigencia_art").addClass('label-info');
						}else{
							$("#vigencia_art").addClass('label-danger');
						}
						if (!$start){
							$("#art_inicio").addClass('text-danger');
						}
						if (!$end){
							$("#art_hasta").addClass('text-danger');
						}
						
						$("#art_inicio").text(moment(response.data.contratista_empleado.art_inicio.date).format('DD/MM/YYYY'));
						$("#art_hasta").text(moment(response.data.contratista_empleado.art_fin.date).format('DD/MM/YYYY'));
												
						if (response.ubicaciones_permisos.acceso_inicio == null){
							$iniOk = true
							$("#acceso_inicio").text('SIN RESTRICCION');
						}else{
							$ini = Date.parse(response.ubicaciones_permisos.acceso_inicio.date);
							$iniOk = $ini ? $ini <= $now : true;
							$("#acceso_inicio").text(moment(response.ubicaciones_permisos.acceso_inicio.date).format('DD/MM/YYYY'));
						}

						if (response.ubicaciones_permisos.acceso_fin == null){
							$finOk = true
							$("#acceso_fin").text('SIN RESTRICCION');
						}else{
							$fin = Date.parse(response.ubicaciones_permisos.acceso_fin.date);						
							$finOk = $fin ? $fin >= $now : true;
							$("#acceso_fin").text(moment(response.ubicaciones_permisos.acceso_fin.date).format('DD/MM/YYYY')); 	
						}																													

						if ($iniOk && $finOk){
							$("#acceso_span").addClass('label-success');
						}else{
							$("#acceso_span").addClass('label-danger');
						}

						$("#pertenencias_empleado").hide();
						$("#pertenencias_visita").hide();
						$("#pertenencias_visita_enrolada").hide();
						$("#pertenencias_contratista").hide();

						if ((response.pertenencias).length > 0) {
							$("#pertenencias_contratista").show();	
							$("#lista_pertenencias_contratista").empty();					
							response.pertenencias.forEach( function(pertenencia) {
								$("#lista_pertenencias_contratista").append($("<li>").text(pertenencia['texto']));
							});
						}

						$("#advertencias_empleado").hide();
						$("#advertencias_visita").hide();						
						$("#advertencias_visita_enrolada").hide();
						$("#advertencias_contratista").hide();

						if ((response.advertencias).length > 0) {
							$("#advertencias_contratista").show();
							$("#lista_advertencias_contratista").empty();	
							response.advertencias.forEach( function(advertencia) {
								$("#lista_advertencias_contratista").append($("<li>").text(advertencia['texto']).append("<br>&emsp;&emsp;&emsp;<strong>"+advertencia['solicitante']['nombre']+" "+advertencia['solicitante']['apellido'] +"</strong>"))
							});
						}

					break;
					case $tipo_acceso['visita_enrolada']: 
						$("#empleado").hide();
						$("#visita_enrolada").show();
						$("#contratista").hide();
						$("#campos_persona").hide();
						$("#credencialTarjeta").hide();
						$("#nombre_visita_enrolada").text(response.visita_enrolada.persona.nombre+' '+response.visita_enrolada.persona.apellido);					

						$("#pertenencias_empleado").hide();
						$("#pertenencias_visita").hide();
						$("#pertenencias_visita_enrolada").hide();
						$("#pertenencias_contratista").hide();	

						if ((response.pertenencias).length > 0) {
							$("#pertenencias_visita_enrolada").show();
							$("#lista_pertenencias_visita_enrolada").empty();					
							response.pertenencias.forEach( function(pertenencia) {
								$("#lista_pertenencias_visita_enrolada").append($("<li>").text(pertenencia['texto']));
							});
						}

						$("#advertencias_empleado").hide();
						$("#advertencias_visita").hide();						
						$("#advertencias_visita_enrolada").hide();
						$("#advertencias_contratista").hide();

						if ((response.advertencias).length > 0) {
							$("#advertencias_visita_enrolada").show();
							$("#lista_advertencias_visita_enrolada").empty();	
							response.advertencias.forEach( function(advertencia) {
								$("#lista_advertencias_visita_enrolada").append($("<li>").text(advertencia['texto']).append("<br>&emsp;&emsp;&emsp;<strong>"+advertencia['solicitante']['nombre']+" "+advertencia['solicitante']['apellido'] +"</strong>"))
							});
						}
					break;			
				}
				}

				
				
            },error: function (response) {
				
			}
        });
});

$("#btn_buscar_autorizante").click(function (e) { 
    e.preventDefault();
    autorizante_documento = $("#autorizante_documento").val();	
        $.ajax({
            type: "POST",
            url: $base_url + '/index.php/accesos/buscar_autorizante',
            data: {autorizante_documento : autorizante_documento},
            dataType: "json",
            success: function (response) {

				if (response.status === false) {
					
					var tipo = 'danger';
					var icono = 'times-circle';
					if (response.data === 'NO ENCONTRADO') {
						var mensaje = 'No se encontró empleado Autorizante.';		
					}else{
						var mensaje = 'El campo Documento de Autorizante es obligatorio.';
						$("#autorizante_nombre").val('')
					$("#oficina_contacto").val('')
					$("#oficina_interno").val('')
					}
					
					$(".alert-dismissable").alert('close');
					$("div#mensajes").prepend($alert(mensaje, tipo, icono).fadeTo(5000, 500).slideUp(500, function () {
						$("#success-alert").slideUp(500);
					}));
				}else{
					
					$("#autorizante_nombre").val(response.data.autorizante.nombre + ' '+response.data.autorizante.apellido)
					$("#oficina_contacto").val(response.data.autorizante.oficina_contacto)
					$("#oficina_interno").val(response.data.autorizante.oficina_interno)
					$("#acceso").show();
				}

				
				
            },error: function (response) {
				
			}
        });
});