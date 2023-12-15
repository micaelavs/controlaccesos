$(document).ready(function () {
    var tabla = $('#historico_visitas_contratistas').DataTable({

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
            url: $base_url + '/index.php/accesos/ajax_historico_visitas_contratistas',
            contentType: "application/json",
            data: function (d) {
                filtros_dataTable = $.extend({}, d, {
                    ubicacion: $('#ubicacion').val(),
                    fecha_desde: ($('#fecha_desde_fecha').val() == '') ? moment().subtract(1, 'week').format("DD/MM/YYYY") : $('#fecha_desde_fecha').val(),
                    fecha_hasta: $('#fecha_hasta_fecha').val(),
                    sin_cierre: $("#sin_cierre").is(":checked") ? 1 : 0,
                    tipos_accesos: $('#tipos_accesos').val(),
                    credencial: $('#credencial').val(),
                    otros_criterios: $('#otros_criterios').val()
                });
                return filtros_dataTable;
            }
        },
        info: true,
        bFilter: false,
        columnDefs: [
            { targets: 0, width: '10%' },
            { targets: 1, width: '10%' },
            { targets: 2, width: '10%' },
            { targets: 3, width: '10%' },
            { targets: 4, width: '10%' },
            { targets: 5, width: '10%' },
            { targets: 6, width: '10%' },
            { targets: 7, width: '10%' },
            { targets: 8, width: '10%' },
            { targets: 9, width: '10%' },
            { targets: 10, width: '10%' },
            { targets: 11, width: '10%' },
            { targets: 12, width: '10%' },
            { targets: 13, width: '10%' },
            { targets: 14, width: '10%' },
            { targets: 15, width: '10%' },
            { targets: 16, width: '10%' },
            { targets: 17, width: '10%' },
            { targets: 18, width: '10%' },
            { targets: 19, width: '10%' },
        ],
        order: [[3, 'desc']],
        columns: [
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
                title: 'Fecha Ingreso',
                name: 'fecha_ingreso',
                data: 'fecha_ingreso',
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
                name: 'hora_ingreso',
                data: 'hora_ingreso',
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
                    if (data == $tipo_registros['online']) {
                        type = 'info';
                        text = 'On line';
                    } else if (data == $tipo_registros['ofline']) {
                        type = 'warning';
                        text = 'Off line';
                    } else if (data == $tipo_registros['registro_reloj']) {
                        type = 'success';
                        text = 'Reloj';
                    } else if (data == $tipo_registros['tarjeta_reloj']) {
                        type = 'tarjetaTM';
                        text = 'TM';
                    } else if (data == '') {
                        type = 'danger';
                        text = 'Sin registro';
                    }
                    return '<span class="label label-' + type + '">' + text + '</span>';
                }
            },
            {
                title: 'Fecha Egreso',
                name: 'fecha_egreso',
                data: 'fecha_egreso',
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
                    if (data == $tipo_registros['online']) {
                        type = 'info';
                        text = 'On line';
                    } else if (data == $tipo_registros['ofline']) {
                        type = 'warning';
                        text = 'Off line';
                    } else if (data == $tipo_registros['registro_reloj']) {
                        type = 'success';
                        text = 'Reloj';
                    } else if (data == $tipo_registros['tarjeta_reloj']) {
                        type = 'tarjetaTM';
                        text = 'TM';
                    } else if (data == null) {
                        type = 'danger';
                        text = 'Sin registro';
                    }

                    return '<span class="label label-' + type + '">' + text + '</span>';
                }
            },
            {
                title: 'Credencial',
                name: 'credencial',
                data: 'credencial',
                className: 'text-left',
                render: function (data) {
                    if (data == null) { data = '' }
                    return '<span class="label label-primary">' + data + '</span>';
                }
            },
            {
                title: 'Ubicacion',
                name: 'ubicacion',
                data: 'ubicacion',
                className: 'text-left'
            },
            {
                title: 'Acceso',
                name: 'acceso',
                data: 'acceso',
                className: 'text-left'
            },
            {
                title: 'Usuario Ingreso',
                name: 'usuario_ingreso',
                data: 'usuario_ingreso',
                className: 'text-left'
            },
            {
                title: 'Documento de usuario ingreso',
                name: 'usuario_ingreso_documento',
                data: 'usuario_ingreso_documento',
                className: 'text-left'
            },
            {
                title: 'Usuario Egreso',
                name: 'usuario_egreso',
                data: 'usuario_egreso',
                className: 'text-left'
            },
            {
                title: 'Documento de usuario egreso',
                name: 'usuario_egreso_documento',
                data: 'usuario_egreso_documento',
                className: 'text-left'
            },
            {
                title: 'Observaciones',
                name: 'observaciones',
                data: 'observaciones',
                className: 'text-left'
            },
            {
                title: 'Origen',
                name: 'origen',
                data: 'origen',
                className: 'text-left'
            },
            {
                title: 'Destino',
                name: 'destino',
                data: 'destino',
                className: 'text-left'
            },
            {
                title: 'Autorizante',
                name: 'autorizante',
                data: 'autorizante',
                className: 'text-left'
            },
        ]
    });


    var $fecha_desde = $('#fecha_desde_fecha');
    var $fecha_hasta = $('#fecha_hasta_fecha');
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

    $('#ubicacion').select2();

    $('#filtrar').click(function () {
        update();
    });

    $('#sin_cierre').on('change', function (e) {
        var $el = $(this);
        var $content = $('#incluir_sin_cierre');
        if ($el.is(':checked')) {
            $content.text('no filtrar');
            $content.prepend($("<span>", { 'class': 'pull-left fa fa-fw fa-check-square' }))
        } else {
            $content.text('filtrar');
            $content.prepend($("<span>", { 'class': 'pull-left fa fa-fw fa-square-o' }))
        }
    });

    $(".accion_exportador").click(function () {
        let fecha_desde_value = ($('#fecha_desde_fecha').val() == '') ? moment().subtract(1, 'week').format("DD/MM/YYYY") : $('#fecha_desde_fecha').val();
        let sin_cierre = ($("#sin_cierre").is(":checked") ? 1 : 0)

        var form = $('<form/>', { id: 'form_ln', action: $(this).val(), method: 'POST' });
        $(this).append(form);
        form.append($('<input/>', { name: 'search', type: 'hidden', value: $('div.dataTables_filter input').val() }))
            .append($('<input/>', { name: 'campo_sort', type: 'hidden', value: $('#historico_visitas_contratistas').dataTable().fnSettings().aoColumns[$('#historico_visitas_contratistas').dataTable().fnSettings().aaSorting[0][0]].name }))
            .append($('<input/>', { name: 'dir', type: 'hidden', value: $('#historico_visitas_contratistas').dataTable().fnSettings().aaSorting[0][1] }))
            .append($('<input/>', { name: 'rows', type: 'hidden', value: $('#historico_visitas_contratistas').dataTable().fnSettings().fnRecordsDisplay() }))
            .append($('<input/>', { name: 'ubicacion_csv', type: 'hidden', value: $('#ubicacion').val() }))
            .append($('<input/>', { name: 'fecha_desde_fecha_csv', type: 'hidden', value: fecha_desde_value }))
            .append($('<input/>', { name: 'fecha_hasta_fecha_csv', type: 'hidden', value: $('#fecha_hasta_fecha').val() }))
            .append($('<input/>', { name: 'tipos_accesos_csv', type: 'hidden', value: $('#tipos_accesos').val() }))
            .append($('<input/>', { name: 'credencial_csv', type: 'hidden', value: $('#credencial').val() }))
            .append($('<input/>', { name: 'otros_criterios_csv', type: 'hidden', value: $('#otros_criterios').val() }))
            .append($('<input/>', { name: 'sin_cierre_csv', type: 'hidden', value: sin_cierre  }))
        form.submit();
    });


});