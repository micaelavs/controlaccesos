$(document).ready(function () {
	if($is_admin == 1){
		dataColumns = [
			{
				title: '',
				name: 'id',
				data: 'id',
				className: 'text-left'
			},
			{
				title: 'Número de Serie',
				name: 'numero_serie',
				data: 'numero_serie',
				className: 'text-left'
			},
			{
				title: 'Ubicación',
				name: 'ubicacion',
				data: 'ubicacion',
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
				title: 'Acciones',
				data: 'acciones',
				name: 'acciones',
				className: 'text-center',
				orderable: false,
				render: function (data, type, row){
					let $html = `<div class="btn-group btn-group-sm">
												<a href="${$base_url}/index.php/Relojes/alta_daemon/${row.id}" data-toggle="tooltip" data-placement="top" title="Alta Reloj en Daemon" ><i class="fa fa-plus text-primary btn-alta-reloj-daemon"></i></a>
											 	<a href="${$base_url}/index.php/Relojes/recargar_daemon/${row.id}" data-toggle="tooltip" data-placement="top" title="Recargar Reloj en Daemon" ><i class="fa fa-retweet text-success btn-recargar-reloj-daemon"></i></a>
											 	<a href="${$base_url}/index.php/Relojes/enrolador/${row.id}" data-toggle="tooltip" data-placement="top" title="${(row.enrolador == 1) ? 'Enrolador Actual' : 'Enrolador'}" ><i class="fa fa-gear btn-enrolador ${(row.enrolador == 1) ? 'text-danger' : ''}"></i></a>
											 	<a href="${$base_url}/index.php/Relojes/accesos_restringidos/${row.id}" data-toggle="tooltip" data-placement="top" title="Ver accesos restringidos habilitados" ><i class="fa fa fa-expeditedssl"></i></a>
											</div>`;
					return $html;
				}
			},
		]
	}else{
		dataColumns = [
			{
				title: 'DNS',
				name: 'dns',
				data: 'dns',
				className: 'text-left',
				render: function (data, type, row){
					return `${row.dns}`
				}
			},
			{
				title: 'IP',
				name: 'ip',
				data: 'ip',
				className: 'text-left'
			},
			{
				title: 'Nodo',
				name: 'nodo',
				data: 'nodo',
				className: 'text-left'
			},
			{
				title: 'Ubicación',
				name: 'ubicacion',
				data: 'ubicacion',
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
				title: 'Última Conexión',
				name: 'ultima_conexion',
				data: 'ultima_conexion',
				className: 'text-left'
			},
			{
				title: 'Acceso c/ Tarjeta',
				name: 'acceso_tarjeta',
				data: 'acceso_tarjeta',
				className: 'text-left'
			},
			{
				title: 'Marca',
				name: 'marca',
				data: 'marca',
				className: 'text-left'
			},
			{
				title: 'Modelo',
				name: 'modelo',
				data: 'modelo',
				className: 'text-left'
			},
			{
				title: 'Acciones',
				data: 'acciones',
				name: 'acciones',
				className: 'text-center',
				orderable: false,
				render: function (data, type, row){
					enrolador = (row.enrolador == 1) ? 'text-danger' : '';
					let $html = `<div class="btn-group btn-group-sm">
												<a href="${$base_url}/index.php/Relojes/modificacion/${row.id}" data-toggle="tooltip" data-placement="top" title="Editar Reloj" ><i class="fa fa-edit text-primary btn-editar-reloj ml-2"></i></a>
											 	<a href="${$base_url}/index.php/Relojes/sincronizacion/${row.id}" data-toggle="tooltip" data-placement="top" title="Sincronizaciones" ><i class="fa fa-arrows-alt text-primary btn-sincronizar-reloj ml-2"></i></a>
											 	<a href="${$base_url}/index.php/Relojes/alta_daemon/${row.id}" data-toggle="tooltip" data-placement="top" title="Alta Reloj en Daemon" ><i class="fa fa-plus text-primary btn-alta-reloj-daemon ml-2"></i></a>
											 	<a href="${$base_url}/index.php/Relojes/recargar_daemon/${row.id}" data-toggle="tooltip" data-placement="top" title="Recargar Reloj en Daemon" ><i class="fa fa-retweet text-primary btn-recargar-reloj-daemon ml-2"></i></a>
											 	<a href="${$base_url}/index.php/Relojes/baja/${row.id}" data-toggle="tooltip" data-placement="top" title="Eliminar Reloj" ><i class="fa fa-trash text-primary btn-baja ml-2"></i></a>
											 	<a href="${$base_url}/index.php/Relojes/enrolador/${row.id}" data-toggle="tooltip" data-placement="top" title="${(row.enrolador == 1) ? 'Enrolador Actual' : 'Enrolador'}" ><i class="fa fa-gear btn-enrolador ml-2 ${enrolador} ${(row.enrolador == 1) ? 'text-danger' : ''}"></i></a>
											 	<a href="${$base_url}/index.php/Relojes/actualizar_templates/${row.id}" data-toggle="tooltip" data-placement="top" title="Actualizar huellas de personas" ><i class="fa fa-upload actualizar-templates ml-2"></i></a>
											 	<a href="${$base_url}/index.php/Relojes/historicoLogsPorNodo/${row.id}" data-toggle="tooltip" data-placement="top" title="Ver logs del reloj" ><i class="fa fa-eye ver-logs"></i></a>
											 	<a href="${$base_url}/index.php/Relojes/accesos_restringidos/${row.id}" data-toggle="tooltip" data-placement="top" title="Ver accesos restringidos habilitados" ><i class="fa fa fa-expeditedssl"></i></a>
											</div>`;
					return $html;
				}
			},
		]
	}

	var tabla = $('#tabla').DataTable({
	  language: {
			url: $endpoint_cdn+'/datatables/1.10.12/Spanish_sym.json',
			decimal: ',',
			thousands: '.',
			infoEmpty: 'No hay ningun Reloj cargado...'
		},
		processing: true,
		serverSide: true,
		responsive: true,
		searchDelay: 1200,
		ajax: {
				url:$base_url + '/index.php/Relojes/ajax_relojes',
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

	if($permiso != '9'){
		$("#alta_reloj").remove();
	}
});