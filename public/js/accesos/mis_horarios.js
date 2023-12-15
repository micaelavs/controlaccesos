$(document).ready(function () {
    $('#datetimeInicialFecha').datetimepicker({
        format: 'DD/MM/YYYY',
		defaultDate: moment().subtract(7, 'days')
    });
    $('#datetimeFinalFecha').datetimepicker({
        format: 'DD/MM/YYYY',
		defaultDate: moment()
    });
    var tabla = $('#dataTable').DataTable({
        language: {
            url: $endpoint_cdn + '/datatables/1.10.12/Spanish_sym.json',
            decimal: ',',
            thousands: '.'
        },
        processing: true,
        serverSide: true,
        responsive: true,
        searchDelay: 1200,
        ajax: {
            url: $base_url + '/index.php/accesos/ajax_mis_horarios',
            contentType: "application/json",
            data: function (d) {
                return $.extend({}, d, {
                    fecha_ini: ($('#fecha_desde').val()),
                    fecha_fin: ($('#fecha_hasta').val()),
                    ubicacion: $('#ubicacion').val(),
                });
            }
        },
        info: true,
        bFilter: false,
        order: [[2, 'desc']],
        columns: [
            
            {
                data: 'ubicacion',
                title: 'Ubicación',
                name: 'ubicacion',
                className: 'text-center',
                targets: 0,
            },
            {
                data: 'usuario_ingreso',
                title: 'Usuario ingreso',
                name: 'usuario_ingreso',
                className: 'none',
                targets: 1,
            },
            {
                data: 'fecha_entrada',
                title: 'Fecha ingreso',
                name: 'fecha_entrada',
                className: 'text-center',
                targets: 2,
            },
            {
                data: 'hora_entrada',
                title: 'Hora ingreso',
                name: 'hora_entrada',
                className: 'text-center',
                targets: 3,
            },
            {
                title: 'Tipo Ingreso',
                name:  'tipo_ingreso',
                data:  'tipo_ingreso',
                className: 'text-left',
                render: function (data, row) {
                    let icon = 'desktop';
                    let text = '';
                    let type = '';
                    if(data == null){
                        type = 'danger';
                        text = 'Sin registro';
                        icon = 'desktop';
                    }else if(data == $tipo_registros['online'] ){
                        type = 'info';
                        text = 'On line&emsp;&emsp;';
                        icon = 'desktop';
                    }else if(data == $tipo_registros['offline'] ){
                        type = 'warning';
                        text = 'Off line&emsp;&emsp;';
                        icon = 'desktop';
                    }else if(data == $tipo_registros['registro_reloj']){
                        type = 'success';
                        text ='Reloj&emsp;&emsp;&emsp;';
                        icon = 'clock-o';
                    }else if(data == $tipo_registros['comision_horaria']){
                        type ='comision';
                        text = 'Comisión Horaria&emsp;&emsp;&emsp;';
                        icon = 'desktop';
                    }else if(data == $tipo_registros['biohacienda']){
                        type = 'bioHacienda';
                        text = 'BIO Hacienda';
                        icon = 'clock-o';
                    }

                    return '<span class="label label-' + type +'"><span class="fa fa-fw fa-' + icon + '"></span> ' + text + '</span>';
                }
            },
            {
                data: 'usuario_egreso',
                title: 'Usuario egreso',
                name: 'usuario_egreso',
                className: 'none',
                targets: 5,
            },
            {
                data: 'fecha_egreso',
                title: 'Fecha egreso',
                name: 'fecha_egreso',
                className: 'text-center',
                targets: 6,
            },
            {
                data: 'hora_egreso',
                title: 'Hora egreso',
                name: 'hora_egreso',
                className: 'text-center',
                targets: 7,
            },
            {
                title: 'Tipo Egreso',
                name:  'tipo_egreso',
                data:  'tipo_egreso',
                className: 'text-left',
                render: function (data, row) {
                    let icon = 'desktop';
                    let text = '';
                    let type = '';
                    if(data == null){
                        type = 'danger';
                        text = 'Sin registro';
                        icon = 'desktop';
                    }else if(data == $tipo_registros['online'] ){
                        type = 'info';
                        text = 'On line&emsp;&emsp;';
                        icon = 'desktop';
                    }else if(data == $tipo_registros['offline'] ){
                        type = 'warning';
                        text = 'Off line&emsp;&emsp;';
                        icon = 'desktop';
                    }else if(data == $tipo_registros['registro_reloj']){
                        type = 'success';
                        text ='Reloj&emsp;&emsp;&emsp;';
                        icon = 'clock-o';
                    }else if(data == $tipo_registros['comision_horaria']){
                        type ='comision';
                        text = 'Comisión Horaria&emsp;&emsp;&emsp;';
                        icon = 'desktop';
                    }else if(data == $tipo_registros['biohacienda']){
                        type = 'bioHacienda';
                        text = 'BIO Hacienda';
                        icon = 'clock-o';
                    }

                    return '<span class="label label-' + type +'"><span class="fa fa-fw fa-' + icon + '"></span> ' + text + '</span>';
                }
            },
            {
                data: 'ubicacion_id',
                title: 'ID Ubicación',
                name: 'ubicacion_id',
                visible: false,
                targets: 9
            },
            {
                data: 'observaciones',
                title: 'Observaciones',
                name: 'observaciones',
                className:'none',
                targets: 10
            }
        ]
    });
    //$.fn.dataTable.moment('DD/MM/YYYY');
    function update() {
        tabla.draw();
    }
    $('#ubicacion').on('change', update);
    $('#fecha_desde').on('blur', update);
    $(".hideable").fadeOut(1).removeProp('checked');
    $('#fecha_hasta').on('blur', function (e) {
        var $el = $(this);
        var val = $el.val();
        if (val) {
            $(".hideable").fadeIn(500);
        } else {
            $(".hideable").fadeOut(500);
        }
        update();
    });
    var $collapseFiltros = $('#collapseFiltros');
    var $collapseFiltrosCaret = $("#collapseFiltros_caret");
    $collapseFiltros.on('hide.bs.collapse', function () {
        $collapseFiltrosCaret.removeClass('fa-caret-down').addClass('fa-caret-right')
    });
    $collapseFiltros.on('show.bs.collapse', function () {
        $collapseFiltrosCaret.removeClass('fa-caret-right').addClass('fa-caret-down')
    });

    if($no_relacionado){
        let mensajes_alerta = new Mensajes($("#mensajes"));
        mensajes_alerta.setMensaje('Aviso: El usuario no está asociado a un empleado, comunicarlo al RCA.', 'danger', 'times-circle')
        .printMensaje();
    }
});