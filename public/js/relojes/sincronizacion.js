$(document).ready(function () {
	dataColumns = [
		{
			title: 'N° Lote',
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
			title: 'Total Marcaciones',
			name: 'totales',
			data: 'totales',
			className: 'text-left'
		},
		{
			title: 'Estado',
			name: 'estado',
			data: 'estado',
			orderable: false,
			className: 'text-left'
		},
		{
			title: 'Fecha',
			name: 'fecha',
			data: 'fecha',
			orderable: false,
			className: 'text-left'
		},
		{
			title: 'Acciones',
			data: 'acciones',
			name: 'acciones',
			className: 'text-center',
			orderable: false,
			render: function (data, type, row){
				let $html = `<div class="btn-group btn-group-sm">
											<a href="${$base_url}/index.php/Relojes/sincronizacion_marcaciones/${row.id}"><i class="fa fa-eye text-primary"></i></a>
										</div>`;
				return $html;
			}
		},
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
				url:$base_url + '/index.php/Relojes/ajax_sincronizacion/'+$nodo,
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