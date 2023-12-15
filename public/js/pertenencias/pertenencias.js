$(document).ready(function () {
    var tabla = $('#pertenencias').DataTable({

        language: {
            url: $endpoint_cdn + '/datatables/1.10.12/Spanish_sym.json',
            decimal: ',',
            thousands: '.',
            infoEmpty: 'No hay datos de pertenencias especificos...'
        },
        processing: true,
        serverSide: true,
        responsive: true,
        searchDelay: 1200,

        ajax: {
            url: $base_url + '/index.php/pertenencias/ajax_pertenencias',
            contentType: "application/json",
        },
        info: true,
        bFilter: true,
        columnDefs: [
            { targets: 0, width: '10%', responsivePriority: 1 },
            { targets: 1, width: '25%', responsivePriority: 1 },
        ],
        order: [[0, 'desc']],
        columns: [
            {
                title: 'Documento',
                name: 'persona_documento',
                data: 'persona_documento',
                className: 'text-left'
            },
            {
                title: 'Nombre',
                name: 'persona_nombre',
                data: "persona_nombre",
                    render: function ( data, type, row ) {
                        return row.persona_nombre + ' ' + row.persona_apellido;},
                className: 'text-left'
            },
            {
                title: 'Pertenencias',
                name: 'texto',
                data: 'texto',
                className: 'text-left'
            },
            {
                title: 'Ubicacion',
                name: 'ubicacion_nombre',
                data: "ubicacion_nombre",
                    render: function ( data, type, row ) {
                        return row.ubicacion_nombre + row.ubicacion_direccion;},
                className: 'text-left'
            },
            {
                title: 'Solicitante',
                name: 'solicitante_persona_nombre',
                data: "solicitante_persona_nombre",
                    render: function ( data, type, row ) {
                        return row.solicitante_persona_nombre + ' ' + row.solicitante_persona_apellido;},
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
                    $html += ' <a href="' + $base_url + '/index.php/pertenencias/modificacion/' + row.id + '" data-toggle="tooltip" data-placement="top" title="Modificación de Pertenencia" target="_self"><i class="fa fa-pencil"></i></a>&nbsp;';
                    $html += ' <a href="' + $base_url + '/index.php/pertenencias/baja/' + row.id + '" data-toggle="tooltip" data-placement="top" title="Baja de Pertenencia" target="_self"><i class="fa fa-trash"></i></a>&nbsp;';
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