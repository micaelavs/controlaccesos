$(document).ready(function () {
    var tabla = $('#accesosbio').DataTable({

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
            url: $base_url + '/index.php/accesosbio/ajax_accesosbio',
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
                title: 'DNI (No es empleado)',
                name: 'dni',
                data: 'dni',
                className: 'text-left'
            },
            {
                title: 'Fecha',
                name: 'hora',
                data: 'hora',
                className: 'text-left'
            },
            {
                title: 'Puerta',
                name: 'puerta',
                data: 'puerta',
                className: 'text-left'
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