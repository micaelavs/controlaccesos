$(document).ready(function () {
    var tabla = $('#tabla').DataTable({

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
            url: $base_url + '/index.php/AlertaRelojes/ajax',
            contentType: "application/json",
        },
        info: true,
        bFilter: true,
        columnDefs: [
            { targets: 0, width: '40%', responsivePriority: 1 },
            { targets: 1, width: '5%', responsivePriority: 1 },
        ],
        order: [[0, 'desc']],
        columns: [
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
                title: 'Email',
                name: 'email',
                data: 'email',
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
                    $html += ' <a href="' + $base_url + '/index.php/AlertaRelojes/baja/' + row.id + '" data-user="" data-toggle="tooltip" data-placement="top" title="Baja de Alerta" target="_self"><i class="fa fa-trash"></i></a>';
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