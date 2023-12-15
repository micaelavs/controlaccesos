$(document).ready(function () {
    var filtros_dataTable = null;	
    $tipos_registros = JSON.parse($tipos_registros);

    var tabla = $('#tabla').DataTable({
        language: {
            url: $endpoint_cdn + '/datatables/1.10.12/Spanish_sym.json',
            decimal: ',',
            thousands: '.',
            infoEmpty: 'No hay datos de empleados...',
            sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros filtrados",
        },
        processing: true,
        serverSide: true,
        responsive: true,
        searchDelay: 2000,
        ajax: {
            url: $base_url + '/index.php/Registros/ajax_sin_cierre',
            contentType: "application/json",
            data: function (d) {
            filtros_dataTable = $.extend({}, d, {
                ubicacion_filtro  : $('#ubicacion_filtro').val(),
                tipo_filtro	: $('#tipo_filtro').val(),
                fecha_desde_filtro 	: $('#fecha_desde_filtro').val(),      
            });
            return filtros_dataTable; 
            } 	
        },
        info: true, 
        bFilter: true,
        order: [[0,'asc']],
        columns: [
            {
                title: 'Ubicación',
                name:  'ubicacion_nombre',
                data:  'ubicacion_nombre',
                className: 'text-left'
            },
            {
                title: 'Tipo',
                name:  'tipo_modelo',
                data:  'tipo_modelo',
                className: 'text-left'
            },
            {
                title: 'Credencial',
                name:  'credencial_codigo',
                data:  'credencial_codigo',
                className: 'text-left',
                render: function (data) {
                    if (data) {
                        return data;
                    }
                    return '0000';
                }
            },
            {
                title: 'Documento',
                name: 'persona_documento',
                data: 'persona_documento',
                className: 'text-left'
            },
            {
                title: 'Nombre',
                name: 'persona_nombre',
                data: 'persona_nombre',
                className: 'text-left'
            },
            {
                title: 'Tipo ingreso',
                name: 'tipo_ingreso',
                data: 'tipo_ingreso',
                className: 'none',
                render: function (data, type, row, obj) {
                    var tyregs;
                    switch (data) {
                        case '1':
                            tyregs = $tipos_registros[1];
                            break;
                        case '2':
                            tyregs = $tipos_registros[2];
                            break;
                        default :
                            tyregs = $tipos_registros[0]
                    }
                    return '<span class="label label-' + tyregs.type + '">' + tyregs.text + '</span>';
                }
            },
            {
                title: 'Fecha de ingreso',
                name:  'fecha_ingreso',
                data:  'fecha_ingreso',
                className: 'text-left',
                render: function (data, type, row) {
                    return moment(data,'DD/MM/YYYY').format('DD/MM/YYYY'); 
                }
            },
            {
                title: 'Hora ingreso',
                name:  'hora_ingreso',
                data:  'hora_ingreso',
                className: 'text-left'
            },
            {
                title: 'Detalles',
                name: 'detalles',
                data: null,
                className: 'none',
                render:function (data, type, row) {
                    var $body = $("<p>");
                    if (row.origen){
                        $body.append($("<div>", {text:row.origen}).prepend($('<strong>', {text:'Origen: '})))
                    }
                    if (row.destino){
                        $body.append($("<div>", {text:row.destino}).prepend($('<strong>', {text:'Destino: '})))
                    }
                    if (row.observaciones){
                        $body.append($("<div>", {text:row.observaciones}).prepend($('<strong>', {text:'Observaciones: '})))
                    }
                    return $body.prop('outerHTML');
                }
            },

            {
                title: 'Documento Empleado Autorizante',
                name:  'autorizante_persona_documento',
                data:  'autorizante_persona_documento',
                className: 'text-left'
            },
            {
                title: 'Nombre Empleado Autorizante',
                name:  'autorizante_persona_nombre',
                data:  'autorizante_persona_nombre',
                className: 'text-left'
            },

            {
                data: null,
                title: 'Acciones',
                responsivePriority: 0,
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return $("<a>", {
                        'class': 'btn btn-link btn-sm',
                        href: $base_url+'/index.php/Registros/solicitar_cierre/' + row.id
                    }).append($("<span>", {'class': 'fa fa-fw fa-2x fa-sign-out'})).prop('outerHTML');
                }
            }
        ]

    });

    $("#fecha_desde_filtro").datetimepicker({
        format: 'DD/MM/YYYY'
    });
    
    $('#filtrar_btn').click(function(){
        update();
        $('#alerta').css("display","block");		
         $("#alerta").html('Espere mientras se procesa la información...');   
         $('#alerta').fadeOut(5000);
        $('.default-filtro').fadeOut(1000);
    });
    
    /** Consulta al servidor los datos y redibuja la tabla
         * @return {Void}
        */
    function update() {
        tabla.draw();
    }

});
