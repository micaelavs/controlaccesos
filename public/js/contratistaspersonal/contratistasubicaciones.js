$(document).ready(function () {
    var tabla = $('#contratistaubicaciones').DataTable({

        language: {
            url: $endpoint_cdn + '/datatables/1.10.12/Spanish_sym.json',
            decimal: ',',
            thousands: '.',
            infoEmpty: 'No hay datos de contratistas especificos...'
        },
        processing: true,
        serverSide: true,
        responsive: true,
        searchDelay: 1200,

        ajax: {
            url: $base_url + '/index.php/contratistaspersonal/ajax_contratistas_ubicaciones',
            contentType: "application/json",
            data: function (d) {
                filtros_dataTable = $.extend({}, d, {
                    idContratista: $('#idContratista').val(),
                });
                return filtros_dataTable;
            }
        },
        info: true,
        bFilter: true,
        columnDefs: [
        ],
        order: [[0, 'desc']],
        columns: [
            {
                title: 'Ubicacion',
                name: 'nombre_ubicacion',
                data: 'nombre_ubicacion',
                className: 'text-left'
            },
            {
                title: 'Desde',
                name: 'acceso_inicio_str',
                data: 'acceso_inicio_str',
                className: 'text-left'
            },
            {
                title: 'Hasta',
                name: 'acceso_fin_str',
                data: 'acceso_fin_str',
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
                    $html += ' <a href="#" style="margin-right:5px" data-user="" data-toggle="modal" data-target="#modal_editar" data-id="'+row.id+'" data-nombre="'+row.nombre_ubicacion+'" data-inicio="'+row.acceso_inicio_str+'" data-fin="'+row.acceso_fin_str+'"><i class="fa fa-pencil"></i></a>';
                    $html += ' <a href="' +$base_url+'/index.php/contratistaspersonal/ubicacion_baja/'+row.id+'" class="borrar" data-user="" data-toggle="tooltip" data-placement="top" title="Eliminar Permiso" target="_self"><i class="fa fa-trash"></i></a>';
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

    var $fechaAccesoInicio = $('#fecha_acceso_inicio');
    var $fechaAccesoFin = $('#fecha_acceso_fin');
    var $minDate = false;
    $fechaAccesoInicio.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: $minDate
    });
    $fechaAccesoFin.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: $minDate
    });

    $fechaAccesoInicio.on("dp.change", function (e) {
        $fechaAccesoFin.data("DateTimePicker").minDate(e.date);
    });

    $fechaAccesoFin.on("dp.change", function (e) {
        $fechaAccesoInicio.data("DateTimePicker").maxDate(e.date);
    });

    //INPUT DATETIME MODAL EDICION DE UBICACION
    var fechaAccesoInicioModal = $('#fecha_acceso_inicio_modal');
    var fechaAccesoFinModal = $('#fecha_acceso_fin_modal');
    var minDateModal = false;

    fechaAccesoInicioModal.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: minDateModal
    });
    fechaAccesoFinModal.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: minDateModal
    });

    fechaAccesoInicioModal.on("dp.change", function (e) {
        fechaAccesoFinModal.data("DateTimePicker").minDate(e.date);
    });

    fechaAccesoFinModal.on("dp.change", function (e) {
        fechaAccesoInicioModal.data("DateTimePicker").maxDate(e.date);
    });


    $('#modal_editar').on('show.bs.modal', function (event) {
        
        let button = $(event.relatedTarget) 
        let id = button.data('id')
        let nombre = button.data('nombre')
        let inicio = button.data('inicio')
        let fin = button.data('fin')

        let modal = $(this)
        // modal.find('.modal-title').text('New message to ' + recipient)
        
        modal.find('.modal-body #ubicacion_id_modal').val(id);
        modal.find('.modal-body #ubicacion_nombre_modal').text(nombre);
        fechaAccesoInicioModal.data("DateTimePicker").date(moment(inicio,'DD/MM/YYYY'));
        fechaAccesoFinModal.data("DateTimePicker").date(moment(fin,'DD/MM/YYYY'));

    })

});