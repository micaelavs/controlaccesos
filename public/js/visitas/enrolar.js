$(function () {
    var mensajes_alerta	= new Mensajes();
	/**
	 *
	 * @param access_id
	 * @param $alert
	 */
	function guardarTemplates(access_id, $alert,ubicacion_id) {
		$.ajax({
				url:  $base_url+'/index.php/Visitas/guardar_template_por_access_id',
				data: {
					access_id: access_id,
					ubicacion_id: ubicacion_id
				},
				method: "POST"
			})
			.done(function (data) {
				let $container = $("#template-data");
				$container.empty();
				if (data.success === true) {
					$container
						.append(
							$("<div class='alert alert-success' role='alert'>")
								.append(
									$("<span>")
										.html("Templates guardados exitosamente.")
								)
						);
						$('#enrolar').removeClass("alert-danger").addClass("alert-success");
						$('#enrolar strong').text('Usuario enrolado');
						$('#enrolar span').removeClass("fa-times-circle").addClass("fa-check-circle");
				}else{
					$container
						.append(
							$("<div class='alert alert-danger' role='alert'>")
								.append(
									$("<span>")
										.html(+data.templates[0].message)
								)
						);
				}
			});
	}
	
	$(document).on('click', '#buscar-template', function (ev) {
			var $btn = $(ev.target);
			var access_id = $btn.data('access-id');
			var ubicacion_id = $btn.data('ubicacion-id');
			$btn
				.data("loading-text", 'Buscando...')
				.button('loading');
			$.ajax({
					url: $base_url+'/index.php/Visitas/buscar_template_por_access_id',
					data: {
						access_id: access_id
					},
					method: "POST"
				})
				.done(function (data) {
					$btn.removeClass('btn-primary');
					var $container = $("#template-data");
					if (data.success) {
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
													if (confirm("¿Desea almacenar los templates biométricos de la visita?")) {
														guardarTemplates(access_id, $alert,ubicacion_id);
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
		});

		$(document).on('click', '#actualizar-template', function(ev){

			var $btn = $(ev.target);
			var access_id = $btn.data('access-id');
			var ubicacion_id = $btn.data('ubicacion-id');
			
			mensajes_alerta.ocultarMensaje();
			mensajeCargando	= (new Mensajes()).setMensaje('Por favor aguarde, su pedido esta siendo procesado!', 'info', 'exclamation-circle').printMensaje();
			$.ajax({
				url: $base_url+'/index.php/Visitas/actualizar_ubicacion',
				data: {
					access_id: access_id,
					ubicacion_id: ubicacion_id,
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
