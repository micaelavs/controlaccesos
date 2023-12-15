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
            url: $base_url + '/index.php/empleados/ajax_empleados',
            contentType: "application/json",
            data: function (d) {
                filtros_dataTable = $.extend({}, d, {
                    ubicacion: $('#ubicacion').val(),
                    dependencia: $('#dependencia').val(),
                    contrato: $('#contrato').val(),
                    enrolado: $('#enrolado').val(),
                    estado: $('#estado').val(),

                });
                return filtros_dataTable;
            }
        },
        info: true,
        bFilter: true,
        columnDefs: [
            { targets: 0, width: '5%', responsivePriority: 1 },
            { targets: 1, width: '5%', responsivePriority: 1 },
        ],
        order: [[0, 'desc']],
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
                title: 'Ubicacion Principal',
                name: 'ubicacion',
                data: 'ubicacion',
                className: 'text-left'
            },
            // {
            //     title: 'Ubicaciones Autorizadas',
            //     name: 'ubicaciones_autorizadas',
            //     data: 'ubicaciones_autorizadas',
            //     className: 'text-left'
            // },
            {
                title: 'Dependencia Principal',
                name: 'd_principal',
                data: 'd_principal',
                className: 'text-left'
            },
            {
                title: 'Contrato',
                name: 'contrato_nombre',
                data: 'contrato_nombre',
                className: 'text-left'
            },
            {
                title: 'Enrolado',
                name: 'enrolado',
                data: 'enrolado',
                className: 'text-left',
                render: function (data, type, row) {
                    var $html = '';
                    (data == 1) ? $html += '<span class="label label-success"><span class="fa fa-fw fa-thumbs-o-up"></span> Si </span>' :
                        $html += '<span class="label label-danger"><span class="fa fa-fw fa-thumbs-o-down"></span>  No </span>';
                    return $html;

                }
            },
            {
                title: 'Acciones',
                data: 'usuario',
                name: 'usuario',
                className: 'text-center',
                orderable: false,
                render: function (data, type, row) {
                    var $html = '';
                    $html += '<div class="btn-group btn-group-sm">';
                   if(row!=null){
                           
                            if(data == $roles['enrolador_dis']  || data == $roles['admin_ciet']){
                                $html += ' <a href="' +$base_url+'/index.php/empleados/enrolar/'+row.id+'" class="enrolar" data-user="" data-toggle="tooltip" data-placement="top" title="Enrolar" target="_self"><i class="fa fa-user"></i></a>';                    
                                $html += ' <a href="' + $base_url + '/index.php/empleados/modificacion/' + row.id + '" data-user="" data-toggle="tooltip" data-placement="top" title="Editar" target="_self"><i class="fa fa-pencil"></i></a>&nbsp;';
                                $html += ' <a href="' +$base_url+'/index.php/empleados/modificacion_contrato/'+row.id+'" data-user="" data-toggle="tooltip" data-placement="top" title="Modificar contrato" target="_self"><i class="fa fa-pencil-square"></i></a>';
                                $html += ' <a href="' +$base_url+'/index.php/empleados/baja/'+row.id+'" class="borrar" data-user="" data-toggle="tooltip" data-placement="top" title="Borrar" target="_self"><i class="fa fa-trash"></i></a>'; 
                            }else if(data == $roles['rca'] || data == $roles['admin_rrhh']){
                                $html += ' <a href="' +$base_url+'/index.php/empleados/empleado_horarios/'+row.id+'" class="horario" data-user="" data-toggle="tooltip" data-placement="top" title="Ver horarios" target="_self"><i class="fa fa-clock-o"></i></a>';
                            }else if(data == $roles['reg_acceso'] || data == $roles['carga_datos'] || data == $roles['auditor'] || data == $roles['admin_convenios'] || data == $roles['rol_dis'] || data == $roles['empleado_ciet'] || data == $roles['rol_default'] || data == $roles['rol_administracion'] ){
                                $html += ' <a href="' + $base_url + '/index.php/empleados/modificacion/' + row.id + '" data-user="" data-toggle="tooltip" data-placement="top" title="Editar" target="_self"><i class="fa fa-pencil"></i></a>&nbsp;';
                                $html += ' <a href="' +$base_url+'/index.php/empleados/modificacion_contrato/'+row.id+'" data-user="" data-toggle="tooltip" data-placement="top" title="Modificar contrato" target="_self"><i class="fa fa-pencil-square"></i></a>';
                            }

                    }
                    $html += '</div>';
                    return $html;
                }
            },
        ]
    });

    /** Consulta al servidor los datos y redibuja la tabla
     * @return {Void}
    */
    function update() {
        tabla.draw();
    }

    /**
     * Acciones para los filtros, actualizar vista
    */
    $('#ubicacion').on('change', update);
    $('#dependencia').on('change', update);
    $('#contrato').on('change', update);
    $('#enrolado').on('change', update);

    $('#estado').click(function () {
         $(this).toggleClass('btn-info btn');
         $(this).toggleClass('btn btn-default');

        switch ($('#estado').attr('class')) {
            case 'btn btn-default':
                $('#estado').val("Inactivos");                
                $('#estado').prop('title', 'Mostrar el listado de Inactivos');
                break;
            case 'btn-info btn':                
                $('#estado').val("Activos");
                $('#estado').prop('title', 'Mostrar el listado de Activos');
                break;

        }
        update();
    });

    if($rol_actual == "RCA"){
        $("#alta_empleado").remove();
    }


    /****/

    $('#ubicacion').select2();
    $('#dependencia').select2();
    $('#contrato').select2();
    $('#enrolado').select2();

    $(".accion_exportador").click(function () {
        var form = $('<form/>', {id:'form_ln' , action : $(this).val(), method : 'POST'});
        $(this).append(form);
        form.append($('<input/>', {name: 'search', type: 'hidden', value: $('div.dataTables_filter input').val() }))
            .append($('<input/>', {name: 'campo_sort', type: 'hidden', value: $('#tabla').dataTable().fnSettings().aoColumns[$('#tabla').dataTable().fnSettings().aaSorting[0][0]].name }))
            .append($('<input/>', {name: 'dir', type: 'hidden', value: $('#tabla').dataTable().fnSettings().aaSorting[0][1] }))
            .append($('<input/>', {name: 'rows', type: 'hidden', value: $('#tabla').dataTable().fnSettings().fnRecordsDisplay() }))
            .append($('<input/>', {name: 'dependencia', type: 'hidden', value:$('#dependencia').val() }))
            .append($('<input/>', {name: 'ubicacion', type: 'hidden', value:$('#ubicacion').val() }))
            .append($('<input/>', {name: 'contrato', type: 'hidden', value:$('#contrato').val() }))
            .append($('<input/>', {name: 'enrolado', type: 'hidden', value:$('#enrolado').val() }))
            .append($('<input/>', {name: 'estado', type: 'hidden', value:$('#estado').val() }));
         form.submit();
    });
});