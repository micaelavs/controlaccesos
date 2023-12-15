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
			title: 'Ubicaciones Autorizadas',
			name: 'ubicaciones_autorizadas',
			data: 'ubicaciones_autorizadas',
			className: 'text-left'
		},
		{
			title: 'Enrolado',
			name: 'enrolado',
			data: 'enrolado',
			className: 'text-left',
			render: function (data, type, row){
				let html = '';
                
                if(row.enrolado == '1'){
                    html += '<span class="label label-success"><span class="fa fa-fw fa-thumbs-o-up"></span> Si</span>';
                }else{
                    html += '<span class="label label-danger"><span class="fa fa-fw fa-thumbs-o-down"></span> No</span>';
                }

				return html;
			}
		},
		{
			title: 'Acciones',
			data: 'acciones',
			name: 'acciones',
			className: 'text-center',
			orderable: false,
			render: function (data, type, row){
				let $html = `<div class="btn-group btn-group-sm">
                                <a href="${$base_url}/index.php/Visitas/enrolar/${row.id}"><i class="fa fa-user text-primary"></i></a>
                                <a href="${$base_url}/index.php/Visitas/modificacion/${row.id}"><i class="fa fa-edit text-primary"></i></a>
                                <a href="${$base_url}/index.php/Visitas/baja/${row.id}"><i class="fa fa-trash text-primary"></i></a>
                            </div>`;
				return $html;
			}
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
				url:$base_url + '/index.php/Visitas/ajax/'+$estado,
				contentType: "application/json",
				data: function (d) {
					filtros_dataTable = $.extend({}, d, {
						ubicaciones_autorizadas   :  $('#ubicaciones_autorizadas').val(),
					   	enrolado               :  $('#enrolado').val(),
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

	//SELECT DE UBIACIONES Y ENROLADO
	$("#ubicaciones_autorizadas").select2();
	$("#enrolado").select2();

	$("#ubicaciones_autorizadas").change(function (e) { 
		e.preventDefault();
		update()
	});

    $("#enrolado").change(function (e) { 
		e.preventDefault();
		update()
	});



});