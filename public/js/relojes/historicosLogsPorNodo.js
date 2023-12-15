$(document).ready(function () {
	dataColumns = [
		{
			title: 'ID',
			name: 'id',
			data: 'id',
			className: 'text-left'
		},
		{
			title: 'Fecha',
			name: 'fecha',
			data: 'fecha',
			className: 'text-left'
		},
		{
			title: 'Código',
			name: 'cod_error',
			data: 'cod_error',
			className: 'text-left',
			render: function (data, type, row){
				let clase = 'danger';
				let icon = 'desktop';

				switch (row.cod_error) {
					case "1":
						clase = 'success';
						break;
					case "2":
						clase = 'success';
					 	icon = 'clock-o';
						break;
					case "P001":
						clase = 'fichadaIncorrectaSincro';
					break;
					case "P002":
						clase = 'fichadaIncorrectaAcceso';
                        icon = 'clock-o'
					break;
				}

				return '<span class="label label-' + clase +'"><span class="fa fa-fw fa-' + icon + '"></span> ' + row.cod_error + '</span>';
			}
		},
		{
			title: 'Descripción',
			name: 'mensaje',
			data: 'mensaje',
			orderable: false,
			className: 'text-left'
		}
	]
	var tabla = $('#tabla').DataTable({
	  language: {
			url: $endpoint_cdn+'/datatables/1.10.12/Spanish_sym.json',
			decimal: ',',
			thousands: '.',
			infoEmpty: 'No hay ningun Log cargado...'
		},
		processing: true,
		serverSide: true,
		responsive: true,
		searchDelay: 1200,
		ajax: {
				url:$base_url + '/index.php/Relojes/ajax_historicoLogsPorNodo/'+$nodo,
				contentType: "application/json",
				data: function (d) {
					filtros_dataTable = $.extend({}, d, {
						codigo_error   :  $('#codigo_error').val(),
					   	fecha_desde               :  $('#fecha_desde').val(),
					   	fecha_hasta               :  $('#fecha_hasta').val()
				   });
				   return filtros_dataTable;
   
			   }
		},
		info: true,
		bFilter: true,
		columnDefs: [
		],
		order: [[0,'desc']],
		columns: dataColumns
	});

	/**
    * Consulta al servidor los datos y redibuja la tabla
    * @return {Void}
    */
	function update() {
			tabla.draw();
	}

	//CAMPOS FECHA
	var formatoFecha = 'DD/MM/YYYY';
	$('#fecha_desde').datetimepicker({
		format: formatoFecha
	});

	$('#fecha_hasta').datetimepicker({
	format: formatoFecha
	});

	$("#fecha_desde").on("dp.change", function (e) {
        $('#fecha_hasta').data("DateTimePicker").minDate(e.date);
    });

    $("#fecha_hasta").on("dp.change", function (e) {
        $('#fecha_desde').data("DateTimePicker").maxDate(e.date);
    });

	//SELECT DE CODIGO DE ERROR
	$("#codigo_error").select2();

	$("#filtrar").click(function (e) { 
		e.preventDefault();
		update()
	});


});