$(document).ready(function () {
    var tabla = $('#horas_trabajadas').DataTable({

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
            url: $base_url + '/index.php/accesos/ajax_horas_trabajadas',
            contentType: "application/json",
            data: function (d) {
                filtros_dataTable = $.extend({}, d, {
                    dependencia: $('#dependencia').val(),
                    fecha_desde: $('#fecha_desde_fecha').val(),
                    fecha_hasta: $('#fecha_hasta_fecha').val(),
                    otro_criterio: $('#otro_criterio').val(),
                });
                return filtros_dataTable;
            }
        },
        info: true,
        bFilter: false,
        columnDefs: [{ targets: 0, width: '10%' },
        { targets: 1, width: '10%' },
        { targets: 2, width: '10%' },
        { targets: 3, width: '10%' },
        { targets: 4, width: '10%' },
        { targets: 5, width: '10%' },
        { targets: 6, width: '10%' },
        { targets: 7, width: '10%' },
        { targets: 8, width: '10%' },
        { targets: 9, width: '10%' },
        { targets: 10, width: '10%' }],
        order: [[4, 'desc']],
        columns: [
            {
                title: 'Documento',
                name: 'documento',
                data: 'documento',
                className: 'text-left'
            },
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
                title: 'Apellido',
                name: 'apellido',
                data: 'apellido',
                className: 'text-left'
            },
            {
                title: 'Fecha',
                name: 'fecha_entrada',
                data: 'fecha_entrada',
                className: 'text-left',
                render: function (data, type, row) {
					if(data == null){
					}else{
						rta = moment(data,'DD/MM/YYYY H:i').format('DD/MM/YYYY'); 
					} 	
					return rta;
				}
            },
            {
                title: 'Hora Ingreso',
                name: 'hora_entrada',
                data: 'hora_entrada',
                className: 'text-left'
            },
            {
                title: 'Tipo Ingreso',
                name: 'tipo_ingreso',
                data: 'tipo_ingreso',
                className: 'text-left',
                render: function (data, row) {
                    let text = '';
                    let type = '';
                    if (data == null) {
                        type = 'danger';
                        text = 'Sin registro';
                        icon = 'desktop';
                    } else if (data == $tipo_registros['online']) {
                        type = 'info';
                        text = 'On line&emsp;&emsp;';
                        icon = 'desktop';
                    } else if (data == $tipo_registros['ofline']) {
                        type = 'warning';
                        text = 'Off line&emsp;&emsp;';
                        icon = 'desktop';
                    } else if (data == $tipo_registros['registro_reloj']) {
                        type = 'success';
                        text = 'Reloj&emsp;&emsp;&emsp;';
                        icon = 'clock-o';
                    } else if (data == $tipo_registros['comision_horaria']) {
                        type = 'comision';
                        text = 'Comisión Horaria&emsp;&emsp;&emsp;';
                    } else if (data == $tipo_registros['biohacienda']) {
                        type = 'bioHacienda';
                        text = 'BIO Hacienda';
                        icon = 'clock-o';
                    }
                    return '<span class="label label-' + type + '">' + text + '</span>';
                }
            },
            {
                title: 'Hora Egreso',
                name: 'hora_egreso',
                data: 'hora_egreso',
                className: 'text-left'
            },
            {
                title: 'Tipo Egreso',
                name: 'tipo_egreso',
                data: 'tipo_egreso',
                className: 'text-left',
                render: function (data, row) {
                    let text = '';
                    let type = '';
                    if (data == null) {
                        type = 'danger';
                        text = 'Sin registro';
                        icon = 'desktop';
                    } else if (data == $tipo_registros['online']) {
                        type = 'info';
                        text = 'On line&emsp;&emsp;';
                        icon = 'desktop';
                    } else if (data == $tipo_registros['ofline']) {
                        type = 'warning';
                        text = 'Off line&emsp;&emsp;';
                        icon = 'desktop';
                    } else if (data == $tipo_registros['registro_reloj']) {
                        type = 'success';
                        text = 'Reloj&emsp;&emsp;&emsp;';
                        icon = 'clock-o';
                    } else if (data == $tipo_registros['comision_horaria']) {
                        type = 'comision';
                        text = 'Comisión Horaria&emsp;&emsp;&emsp;';
                    } else if (data == $tipo_registros['biohacienda']) {
                        type = 'bioHacienda';
                        text = 'BIO Hacienda';
                        icon = 'clock-o';
                    }
                    return '<span class="label label-' + type + '">' + text + '</span>';
                }
            },
            {
                title: 'Horas Trabajadas',
                name: 'horas_trabajadas',
                data: 'horas_trabajadas',
                className: 'text-left'
            },
            {
                title: 'Dependencia',
                name: 'codep',
                data: 'codep',
                className: 'text-left'
            },
        ]
    });


    var $fecha_desde = $('#fecha_desde_fecha');
    var $fecha_hasta = $('#fecha_hasta');
    var $minDate = false;
    $fecha_desde.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: moment().subtract(7, 'days')
    })

    $fecha_hasta.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: moment()
    });

    /** Consulta al servidor los datos y redibuja la tabla
    * @return {Void}
    */
    function update() {
        tabla.draw();
    }

    $('#dependencia').select2();

    $('#filtrar').click(function () {
        update();
    });

    $('#fecha_hasta_fecha').datetimepicker({
        format: 'DD/MM/YYYY',
                    
    });

    $("#fecha_desde_fecha").on("dp.change", function (e) {
        $('#fecha_hasta_fecha').data("DateTimePicker").minDate(e.date);
    });

    $("#fecha_hasta_fecha").on("dp.change", function (e) {
        $('#fecha_desde_fecha').data("DateTimePicker").maxDate(e.date);
    });

    // $('#sin_cierre').on('change', function (e) {
    //     var $el = $(this);
    //     var $content = $('#incluir_sin_cierre');
    //     if ($el.is(':checked')) {
    //         $content.text('no filtrar');
    //         $content.prepend($("<span>", { 'class': 'pull-left fa fa-fw fa-check-square' }))
    //     } else {
    //         $content.text('filtrar');
    //         $content.prepend($("<span>", { 'class': 'pull-left fa fa-fw fa-square-o' }))
    //     }
    // });

    $(".accion_exportador").click(function () {
        let fecha_desde_value = ($('#fecha_desde_fecha').val() == '') ? moment().subtract(1, 'week').format("DD/MM/YYYY") : $('#fecha_desde_fecha').val();

        var form = $('<form/>', { id: 'form_ln', action: $(this).val(), method: 'POST' });
        $(this).append(form);
        form.append($('<input/>', { name: 'search', type: 'hidden', value: $('div.dataTables_filter input').val() }))
            .append($('<input/>', { name: 'campo_sort', type: 'hidden', value: $('#horas_trabajadas').dataTable().fnSettings().aoColumns[$('#horas_trabajadas').dataTable().fnSettings().aaSorting[0][0]].name }))
            .append($('<input/>', { name: 'dir', type: 'hidden', value: $('#horas_trabajadas').dataTable().fnSettings().aaSorting[0][1] }))
            .append($('<input/>', { name: 'rows', type: 'hidden', value: $('#horas_trabajadas').dataTable().fnSettings().fnRecordsDisplay() }))
            .append($('<input/>', { name: 'dependencia_csv', type: 'hidden', value: $('#dependencia').val() }))
            .append($('<input/>', { name: 'fecha_desde_fecha_csv', type: 'hidden', value: fecha_desde_value }))
            .append($('<input/>', { name: 'fecha_hasta_fecha_csv', type: 'hidden', value: $('#fecha_hasta_fecha').val() }))
            .append($('<input/>', { name: 'otro_criterio_csv', type: 'hidden', value: $('#otro_criterio').val() }))
        form.submit();
    });


});