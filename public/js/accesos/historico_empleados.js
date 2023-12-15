$(document).ready(function () {
	var filtros_dataTable = null;	
	
	var tabla = $('#tabla').DataTable({
		language: {
			url: $endpoint_cdn + '/datatables/1.10.12/Spanish_sym.json',
			decimal: ',',
			thousands: '.',
			infoEmpty: 'No hay datos de empleados...',
			sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros filtrados",
		},
		processing: true,
		serverSide: true,
		responsive: true,
		searchDelay: 5000,
		ajax: {
			url: $base_url + '/index.php/Accesos/ajax_historico_empleados',
			contentType: "application/json",
			data: function (d) {
			filtros_dataTable = $.extend({}, d, {
				ubicacion_filtro  : $('#ubicacion_filtro').val(),
				dependencia_filtro	: $('#dependencia_filtro').val(),
				fecha_desde_filtro 	: $('#fecha_desde_filtro').val(),
				fecha_hasta_filtro 	: $('#fecha_hasta_filtro').val(),
				otro_criterio_filtro: $("#otro_criterio_filtro").val(),
				incluir_sin_cierre_filtro: $("#includeUnclosed").is(":checked") ? 1 : 0,
			});
			return filtros_dataTable; 
			} 	
		},
		info: true, 
		bFilter: false,
		columnDefs: [
			{ targets: 0, width: '5%', responsivePriority:1},
			{ targets: 1, width: '10%',responsivePriority:1}, 
			{ targets: 2, width: '10%',responsivePriority:1}, 
			{ targets: 3, width: '15%',responsivePriority:1}, 
			{ targets: 4, width: '10%',responsivePriority:1}, 
			{ targets: 5, width: '10%',responsivePriority:1}, 
			{ targets: 6, width: '15%',responsivePriority:1}, 
			{ targets: 7, width: '10%',responsivePriority:1}, 
			{ targets: 8, width: '10%',responsivePriority:1}, 
			{ targets: 9, width: '10%',responsivePriority:2}, 
			{ targets: 10, width: '10%',responsivePriority:2},
			{ targets: 11, width: '10%',responsivePriority:2}, 
			{ targets: 12, width: '10%',responsivePriority:2},
			{ targets: 13, width: '10%',responsivePriority:2}, 
		],
		order: [[3,'desc']],
		columns: [
			{
				title: 'Documento',
				name:  'documento',
				data:  'documento',
				className: 'text-left'
			},
			{
				title: 'Nombre',
				name:  'nombre',
				data:  'nombre',
				className: 'text-left'
			},
			{
				title: 'Apellido',
				name:  'apellido',
				data:  'apellido',
				className: 'text-left'
			},
			{
				title: 'Fecha ingreso',
				name:  'fecha_entrada',
				data:  'fecha_entrada',
				className: 'text-left',
				render: function (data, type, row) {
					if(data == null){
					}else{
						rta = moment(data,'DD/MM/YYYY H:i').format('DD/MM/YYYY'); 
					} 	
					return rta;
				}
			},
			{
				title: 'Hora ingreso',
				name:  'hora_entrada',
				data:  'hora_entrada',
				className: 'text-left'
			},
			{
				title: 'Tipo Ingreso',
				name:  'tipo_ingreso',
				data:  'tipo_ingreso',
				className: 'text-left',
				render: function (data, row) {
					let text = '';
					let type = '';
					if(data == null){
						type = 'danger';
						text = 'Sin registro';
						icon = 'desktop';
					}else if(data == $tipo_registros['online'] ){
						type = 'info';
						text = 'On line&emsp;&emsp;';
						icon = 'desktop';
					}else if(data == $tipo_registros['ofline'] ){
						type = 'warning';
						text = 'Off line&emsp;&emsp;';
						icon = 'desktop';
					}else if(data == $tipo_registros['registro_reloj']){
						type = 'success';
						text ='Reloj&emsp;&emsp;&emsp;';
						icon = 'clock-o';
					}else if(data == $tipo_registros['comision_horaria']){
						type ='comision';
						text = 'Comisión Horaria&emsp;&emsp;&emsp;';
						icon = 'desktop';
					}else if(data == $tipo_registros['biohacienda']){
						type = 'bioHacienda';
						text = 'BIO Hacienda';
						icon = 'clock-o';
					}

					return '<span class="label label-' + type +'"><span class="fa fa-fw fa-' + icon + '"></span> ' + text + '</span>';
				}
			},
			{
				title: 'Fecha egreso',
				name: 'fecha_egreso',
				data: 'fecha_egreso',
				className: 'text-left',
				render: function (data, type, row) {
					if(data == null){
					}else{
						rta = moment(data,'DD/MM/YYYY H:i').format('DD/MM/YYYY'); 
					} 	
					return rta;
				}
			},
			{
				title: 'Hora egreso',
				name: 'hora_egreso',
				data: 'hora_egreso',
				className: 'text-left'
			},
			{
				title: 'Tipo Egreso',
				name:  'tipo_egreso',
				data:  'tipo_egreso',
				className: 'text-left',
				render: function (data, row) {
					let text = '';
					let type = '';
					if(data == null){
						type = 'danger';
						text = 'Sin registro';
						icon = 'desktop';
					}else if(data == $tipo_registros['online'] ){
						type = 'info';
						text = 'On line&emsp;&emsp;';
						icon = 'desktop';
					}else if(data == $tipo_registros['ofline'] ){
						type = 'warning';
						text = 'Off line&emsp;&emsp;';
						icon = 'desktop';
					}else if(data == $tipo_registros['registro_reloj']){
						type = 'success';
						text ='Reloj&emsp;&emsp;&emsp;';
						icon = 'clock-o';
					}else if(data == $tipo_registros['comision_horaria']){
						type ='comision';
						text = 'Comisión Horaria&emsp;&emsp;&emsp;';
						icon = 'desktop';
					}else if(data == $tipo_registros['biohacienda']){
						type = 'bioHacienda';
						text = 'BIO Hacienda';
						icon = 'clock-o';
					}

					return '<span class="label label-' + type +'"><span class="fa fa-fw fa-' + icon + '"></span> ' + text + '</span>';
				}
			},
			{
				title: 'Dependencia',
				name: 'codep',
				data: 'codep',
				className: 'text-left'
			},
			{
				title: 'Ubicación',
				name: 'ubicacion',
				data: 'ubicacion',
				className: 'text-left'
			},
			{
				title: 'Usuario Ingreso',
				name: 'usuario_ingreso',
				data: 'usuario_ingreso',
				className: 'text-left'
			},
			{
				title: 'Usuario Egreso',
				name:  'usuario_egreso',
				data:  'usuario_egreso',
				className: 'text-left'
			},
			{
				title: 'Observaciones',
				data: 'observaciones',
				name: 'observaciones',
				className: 'text-left'
			},
		]

	});


	$('#filtrar_btn').click(function(){
		update();
		$('#alerta').css("display","block");		
 		$("#alerta").html('Presione el botón de filtrado y espere mientras procesa la información...');   
 		$('#alerta').fadeOut(5000);
		$('.default-filtro').fadeOut(1000);
	});

	$('#includeUnclosed').on('change', function (e) {
		var $el = $(this);
		var $content = $('#incluir_sin_cierre_content');
		if ($el.is(':checked')) {
			$content.text('no filtrar');
			$content.prepend($("<span>", {'class': 'pull-left fa fa-fw fa-check-square'}))
		} else {
			$content.text('filtrar');
			$content.prepend($("<span>", {'class': 'pull-left fa fa-fw fa-square-o'}))
		}
	});

	$(".hideable").fadeOut(1).removeProp('checked');
		$('#fecha_hasta_filtro').on('blur', function (e) {
			var $el = $(this);
			var val = $el.val();
			if (val) {
				$(".hideable").fadeIn(500);
			} else {
				$(".hideable").fadeOut(500);
			}
			
		});


	$("#fecha_desde_filtro").datetimepicker({
		format: 'DD/MM/YYYY',
		defaultDate: moment().subtract(7, 'days')
	});

	$("#fecha_hasta_filtro").datetimepicker({
		format: 'DD/MM/YYYY',
		defaultDate: moment()
	});

	$("#fecha_desde_filtro").on("dp.change", function (e) {
        $('#fecha_hasta_filtro').data("DateTimePicker").minDate(e.date);
    });

    $("#fecha_hasta_filtro").on("dp.change", function (e) {
        $('#fecha_desde_filtro').data("DateTimePicker").maxDate(e.date);
    });
	/** Consulta al servidor los datos y redibuja la tabla
	 * @return {Void}
	*/
	function update() {
	    tabla.draw();
	}

	    //filtros para el exportador
	$(".accion_exportador").click(function () {
	    var form = $('<form/>', {id:'form_ln' , action : $(this).val(), method : 'POST'});
	    $(this).append(form);
	    form.append($('<input/>', {name: 'search', type: 'hidden', value: $('div.dataTables_filter input').val() }))
	        .append($('<input/>', {name: 'campo_sort', type: 'hidden', value: $('#tabla').dataTable().fnSettings().aoColumns[$('#tabla').dataTable().fnSettings().aaSorting[0][0]].name }))
	        .append($('<input/>', {name: 'dir', type: 'hidden', value: $('#tabla').dataTable().fnSettings().aaSorting[0][1] }))
	        .append($('<input/>', {name: 'rows', type: 'hidden', value: $('#tabla').dataTable().fnSettings().fnRecordsDisplay() }))
	        .append($('<input/>', {name: 'ubicacion', type: 'hidden', value:$('#ubicacion_filtro').val() }))
	        .append($('<input/>', {name: 'dependencia', type: 'hidden', value:$('#dependencia_filtro').val() }))
	        .append($('<input/>', {name: 'fecha_desde', type: 'hidden', value:$('#fecha_desde_filtro').val() }))
	        .append($('<input/>', {name: 'fecha_hasta', type: 'hidden', value:$('#fecha_hasta_filtro').val() }))
	       	.append($('<input/>', {name: 'otro_criterio', type: 'hidden', value:$('#otro_criterio_filtro').val() }))
	        .append($('<input/>', {name: 'incluir_sin_cierre', type: 'hidden', value:$('#includeUnclosed').is(':checked') }));
	     form.submit();
	});

	//update();

	$('#ubicacion_filtro').select2();
	$('#dependencia_filtro').select2();
});
