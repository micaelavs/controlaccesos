$(document).ready(function () {
	
    $("#ubicaciones").select2();
    var opciones = $('#ubicaciones option:selected').sort().clone();
    $('#ubicacion_principal').empty();
    $("#ubicacion_principal").append(new Option("Seleccione una Ubicación Principal", ""));
    $('#ubicacion_principal').append(opciones);

    $("#ubicaciones").on('change', function () {
        var options = $('#ubicaciones option:selected').sort().clone();
        $('#ubicacion_principal').empty();
        $("#ubicacion_principal").append(new Option("Seleccione una Ubicación Principal", ""));
        $('#ubicacion_principal').append(options);

    });


});

$(function () {
	var mensajes_alerta	= new Mensajes();
	$('#ubicaciones').select2().on('select2:close', function () {
		$('#ubicaciones').trigger('blur')
	});
	
	/**
	 *
	 * @param access_id
	 * @param $alert
	 */
	function guardarTemplates(access_id, $alert) {
		$.ajax({
                url: $base_url + '/index.php/empleados/guardar_template_por_access_id',
				data: {
					access_id: access_id
				},
				method: "POST"
			})
			.done(function (data) {
				if (data.success === true) {
					$alert
						.removeClass("alert-warning")
						.addClass("alert-success");
						$('#enrolar').removeClass("alert-danger").addClass("alert-success");
						$('#enrolar strong').text('Usuario enrolado');
						$('#enrolar span').removeClass("fa-times-circle").addClass("fa-check-circle");
				}else{
					$('<div>'+data.templates[0].message+'</div>').insertAfter($alert);
				}
			});
	}
	
	$(document)
		.on('click', '#buscar-template', function (ev) {
			var $btn = $(ev.target);
			var access_id = $btn.data('access-id');
			$btn
				.data("loading-text", 'Buscando...')
				.button('loading');
			$.ajax({
                    url: $base_url + '/index.php/empleados/buscar_template_por_access_id',
					data: {
						access_id: access_id
					},
					method: "POST"
				})
				.done(function (data) {
					$btn.removeClass('btn-primary');
					var $container = $("#template-data");
					if (data && data.success) {
						$btn.addClass('btn-success');
						$container.empty();
						var dataLength = data.templates.length;
						if (dataLength === 2) {
							$btn.button('reset');
							var $alert = $("<div class='alert alert-warning' role='alert'>");
							$container
								.append(
									$alert
										.append(
											$("<button class='btn btn-sm btn-primary pull-right' role='button' type='button'>")
												.text("Guardar")
												.click(function (ev) {
													if (confirm("¿Desea almacenar los templates biométricos del empleado?")) {
														guardarTemplates(access_id, $alert);
													}
												})
										)
										.append(
											$("<span>")
												.html("Se encontraron <strong>" + dataLength +
													"</strong> templates en el enrolador.<br>Presione <strong>GUARDAR</strong> para continuar con el enrolamiento.")
										)
								);
						} else {
							$btn.button('reset');
							$container
								.append(
									$("<div class='alert alert-danger' role='alert'>")
										.append(
											$("<span>")
												.html("Se encontraron <strong>" + dataLength +
													"</strong> templates en el enrolador.<br>Se necesitan <strong>2</strong> para continuar con el registro.")
										)
								);
						}
					} else {
						$btn.addClass('btn-danger');
						$container.empty();
						$container
							.append(
								$("<div class='alert alert-danger' role='alert'>")
									.append(
										$("<span>")
											.html("Los templates son invalidos, enrolar las huellas nuevamente")
									)
							);
					}
					$container.fadeIn('fast');
				})
				.fail(function () {
					alert("algo salió mal");
				})
				.always(function () {
					$btn.button('reset');
				});
		})

		// .on('click', '#btn-enviar-persona', function (ev) {
        //     consaole.log('xxxxxx');
		// 	var $btn = $(ev.target);
		// 	$.ajax({					
        //             url: $base_url + '/index.php/empleados/enviar_empleado_a_enrolador',
		// 			data: {
		// 				eid: $btn.data('empleado-id')
		// 			},
		// 			method: "GET"
		// 		})
		// 		.done(function (data) {
		// 			$btn.removeClass('btn-primary btn-success btn-warning btn-danger btn-info');
		// 			if (data.resp !== null) {
		// 				if (data.resp.status === true) {
		// 					$btn.addClass('btn-success');
		// 					$btn.attr('title', "Información enviada con éxito")
		// 						.tooltip('fixTitle')
		// 						.tooltip('show');
		// 				} else {
		// 					$btn.addClass('btn-warning');
		// 					$btn.attr('title', "No se ha enviado la información. Intente más tarde.")
		// 						.tooltip('fixTitle')
		// 						.tooltip('show');
		// 				}
		// 			} else {
		// 				$btn.addClass('btn-danger');
		// 				$btn.attr('title', "No hay respuesta del enrolador.")
		// 					.tooltip('fixTitle')
		// 					.tooltip('show');
		// 			}
		// 		})
		// })

		.on('click', '#actualizar-template', function(ev){
			var $btn = $(ev.target);
			var access_id = $btn.data('access-id');

			mensajes_alerta.ocultarMensaje();
			mensajeCargando	= (new Mensajes()).setMensaje('Por favor aguarde, su pedido esta siendo procesado!', 'info', 'exclamation-circle').printMensaje();
			$.ajax({
                url: $base_url + '/index.php/empleados/actualizar_ubicacion',
				data: {
					access_id: access_id,
					ubicacion_id: $('#ubicacion_id').val(),
				},
				method: "POST"
			})
			.done(function (data) {
				mensajes_alerta
					.ocultarMensaje()
					.setMensaje('Ubicación actualizada correctamente.', 'success', 'check')
					.printMensaje();
			})
			.fail(function () {
				mensajes_alerta
					.ocultarMensaje()
					.setError('Error al actualizar ubicación.')
					.printError();
			})
			.always(function(){
				mensajeCargando.getMensajeHtml().hide();
			})
		});
});

