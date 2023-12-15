
var mensajes_alerta = new Mensajes($("#mensajes"));

$(document).ready(function () {

    $fecha = ($fecha != '') ? moment($fecha,'DD/MM/YYYY') : moment();
    $ingreso = ($ingreso != '') ? moment($ingreso,'HH:mm') : moment();
    $egreso = ($egreso != '') ? moment($egreso,'HH:mm') : moment();
    //CAMPOS FECHA
    $('#fecha').datetimepicker({
        format: 'DD/MM/YYYY',
        maxDate:  moment().add(1, 'days'),
        defaultDate: $fecha
    });
    
    $('#hora_ingreso').datetimepicker({
        format: 'HH:mm',
        defaultDate: $ingreso
    });
    
    $('#hora_egreso').datetimepicker({
        format: 'HH:mm',
        defaultDate: $egreso
    });

    $("#ubicacion").select2();

    
});

function buscarEmpleado(){
    
    $.ajax({
        url: $base_url + '/index.php/Registros/buscar_empleado',
        type: 'POST',
        data: {
            documento: $("#documento").val()
        },
        success: function (data) {
            
            if (data.nombre && data.apellido) {
                $("#nombre_apellido").val(data.nombre + " " + data.apellido);
            }else {
                mensajes_alerta
                        .ocultarMensaje()
                        .setError('No se encontró documento')
                        .printError();
            }
        },
        error: function () {
            
        }
    });

}
