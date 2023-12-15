$(document).ready(function () {
    var tabla = $('#contratistas').DataTable({
        
        language: {
            url: $endpoint_cdn+'/datatables/1.10.12/Spanish_sym.json',
            decimal: ',',
            thousands: '.',
            infoEmpty: 'No hay datos de contratistas especificos...'
        },
        processing: true,
        serverSide: true,
        responsive: true,
        searchDelay: 1200,

        ajax: {
            url:$base_url + '/index.php/contratistas/ajax_contratistas',
            contentType: "application/json",
        },
        info: true,
        bFilter: true,
        columnDefs: [
        ],
        order: [[0, 'desc']],
        columns: [
            {
                title: 'Cuit',
                name: 'cuit',
                data: 'cuit',
                className: 'text-left'
            },
            {
                title: 'Nombre',
                name: 'nombre',
                data: 'nombre',
                className: 'text-left'
            },
            {
                title: 'Direccion',
                name: 'direccion',
                data: 'direccion',
                className: 'text-left'
            },
            {
                title: 'Personal',
                data: 'personal',
                name: 'personal',
                className: 'text-center',
                orderable: false,
                render: function (data, type, row) {
                    var $html = '';
                        $html += '<div class="btn-group btn-group-sm">';
                        $html += ' <a href="' +$base_url+'/index.php/contratistaspersonal/index/'+row.id+'" data-user="" data-toggle="tooltip" data-placement="top" title="Ver lista de personal de '+row.nombre+'" target="_self"><i class="fa fa-user-plus"></i></a>&nbsp;';
                        $html += '</div>';
                    return $html;
                }
            },
            {
                title: 'Acciones',
                data: 'acciones',
                name: 'acciones',
                className: 'text-center',
                orderable: false,
                render: function (data, type, row) {
                    var $html = '';
                        $html += '<div class="btn-group btn-group-sm">';
                        $html += ' <a href="' + $base_url + '/index.php/contratistas/modificacion/' + row.id + '" data-user="" data-toggle="tooltip" data-placement="top" title="Editar" target="_self"><i class="fa fa-pencil"></i></a>&nbsp;';
                        $html += ' <a href="' +$base_url+'/index.php/contratistas/baja/'+row.id+'" class="borrar" data-user="" data-toggle="tooltip" data-placement="top" title="Eliminar" target="_self"><i class="fa fa-trash"></i></a>';
                        $html += '</div>';
                    return $html;
                }
            },
        ]
    });
    
    	/**
    * Consulta al servidor los datos y redibuja la tabla
    * @return {Void}
    */
	function update() {
        tabla.draw();
    }



});