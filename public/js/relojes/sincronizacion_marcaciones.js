$(document).ready(function () {
	dataColumns = [
		{
			title: 'N°Lote',
			name: 'id',
			data: 'id',
			className: 'text-left'
		},
		{
			title: 'Nodo',
			name: 'nodo',
			data: 'nodo',
			className: 'text-left'
		},
		{
			title: 'Id Marcacion',
			name: 'id_marcacion',
			data: 'id_marcacion',
			className: 'text-left'
		},
		{
			title: 'Fecha',
			name: 'fecha_marcacion',
			data: 'fecha_marcacion',
			orderable: false,
			className: 'text-left'
		}
	]
	var tabla = $('#tabla').DataTable({
	  language: {
			url: $endpoint_cdn+'/datatables/1.10.12/Spanish_sym.json',
			decimal: ',',
			thousands: '.',
			infoEmpty: 'No hay ningun Lote cargado...'
		},
		processing: true,
		serverSide: true,
		responsive: true,
		searchDelay: 1200,
		ajax: {
				url:$base_url + '/index.php/Relojes/ajax_sincronizacion_marcacion/'+$lote,
				contentType: "application/json"
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


});