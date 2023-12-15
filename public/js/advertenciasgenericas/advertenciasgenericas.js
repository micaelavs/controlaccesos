$(document).ready(function () {
    var tabla = $('#advertenciasgenericas').DataTable({

        language: {
            url: $endpoint_cdn + '/datatables/1.10.12/Spanish_sym.json',
            decimal: ',',
            thousands: '.',
            infoEmpty: 'No hay datos de personas especificos...'
        },
        processing: true,
        serverSide: true,
        responsive: true,
        searchDelay: 1200,

        ajax: {
            url: $base_url + '/index.php/advertenciasgenericas/ajax_advertenciasgenericas',
            contentType: "application/json",
        },
        info: true,
        bFilter: true,
        columnDefs: [
            // { targets: 0, width: '40%', responsivePriority: 1 },
            // { targets: 1, width: '5%', responsivePriority: 1 },
        ],
        order: [[0, 'desc']],
        columns: [
            {
                title: 'Texto',
                name: 'texto',
                data: 'texto',
                className: 'text-left'
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
                    $html += ' <a href="' + $base_url + '/index.php/advertenciasgenericas/modificacion/' + row.id + '" data-user="" data-toggle="tooltip" data-placement="top" title="Modificación de Advertencia Generica" target="_self"><i class="fa fa-pencil"></i></a>&nbsp;';
                    $html += ' <a href="' +$base_url+'/index.php/advertenciasgenericas/baja/'+row.id+'" class="borrar" data-user="" data-toggle="tooltip" data-placement="top" title="Eliminar Advertencia Generica" target="_self"><i class="fa fa-trash"></i></a>';
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