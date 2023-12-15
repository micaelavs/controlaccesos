$(document).ready(function () {
	dataColumns = [
		{
			title: 'Documento',
			name: 'documento',
			data: 'documento',
			className: 'text-left'
		},
		{
			title: 'Nombre',
			name: 'nombre',
			data: 'nombre',
			className: 'text-left'
		},
		{
			title: 'Apellido',
			name: 'apellido',
			data: 'apellido',
			className: 'text-left'
		},
		{
			title: 'Fecha creación',
			name: 'fecha_alta',
			data: 'fecha_alta',
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
                                <a href="${$base_url}/index.php/Relojes/accesos_restringidosBaja/${row.id}" title="Eliminar" data-toggle="tooltip"><i class="fa fa-trash text-primary"></i></a>
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
			infoEmpty: 'Ningún dato disponible en esta tabla...'
		},
		processing: true,
		serverSide: true,
		responsive: true,
		searchDelay: 1200,
		ajax: {
				url:$base_url + '/index.php/Relojes/ajax_accesos_restringidos/'+$id_reloj,
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