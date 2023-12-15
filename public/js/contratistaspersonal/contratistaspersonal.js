$(document).ready(function () {
    var tabla = $('#contratistaspersonal').DataTable({
        
        language: {
            url: $endpoint_cdn+'/datatables/1.10.12/Spanish_sym.json',
            decimal: ',',
            thousands: '.',
            infoEmpty: 'No hay datos de contratistas especificos...'
        },
        processing: true,
        serverSide: true,
        responsive: true,
        searchDelay: 1200,

        ajax: {
            url:$base_url + '/index.php/contratistaspersonal/ajax_contratistas_personal',
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
                title: 'Apellido',
                name: 'persona_apellido',
                data: 'persona_apellido',
                className: 'text-left'
            },
            {
                title: 'ART Desde',
                name: 'art_inicio_str',
                data: 'art_inicio_str',
                className: 'text-left'
            },
            {
                title: 'ART Hasta',
                name: 'art_fin_str',
                data: 'art_fin_str',
                className: 'text-left'
            },
            {
                title: 'Permisos',
                data: 'permisos',
                name: 'permisos',
                className: 'text-center',
                orderable: false,
                render: function (data, type, row) {
                    var $html = '';
                        $html += '<div class="btn-group btn-group-sm">';
                        $html += ' <a href="' +$base_url+'/index.php/contratistaspersonal/ubicaciones/'+row.id+'" data-user="" data-toggle="tooltip" data-placement="top" title="Permiso de accesos a Ubicaciones" target="_self"><i class="fa fa-building-o"></i></a>&nbsp;';
                        $html += '</div>';
                    return $html;
                }
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
                        $html += ' <a href="' +$base_url+'/index.php/contratistaspersonal/modificacion/'+row.id+'" data-user="" data-toggle="tooltip" data-placement="top" title="Editar" target="_self"><i class="fa fa-pencil"></i></a>&nbsp;';
                        $html += ' <a href="' +$base_url+'/index.php/contratistaspersonal/baja/'+row.id+'" class="borrar" data-user="" data-toggle="tooltip" data-placement="top" title="Eliminar" target="_self"><i class="fa fa-trash"></i></a>';
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