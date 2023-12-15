var mensajes_alerta = new Mensajes($("#mensajes"));

$(document).ready(function () {
    $('#dependencia').select2();

    var $fecha_desde_principal = $('#fecha_desde_principal');
    var $fecha_hasta_principal = $('#fecha_hasta_principal');
    var $minDate = false;
    $fecha_desde_principal.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: $minDate
    });
    $fecha_hasta_principal.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: $minDate
    });

    $fecha_desde_principal.on("dp.change", function (e) {
        $fecha_hasta_principal.data("DateTimePicker").minDate(e.date);
    });

    $fecha_hasta_principal.on("dp.change", function (e) {
        $fecha_desde_principal.data("DateTimePicker").maxDate(e.date);
    });

    //////////////////

    var $fecha_desde_contrato = $('#fecha_desde_contrato');
    var $fecha_hasta_contrato = $('#fecha_hasta_contrato');
    var $fecha_desde_contrato_nuevo = $('#fecha_desde_contrato_nuevo');
    var $minDate = false;
    $fecha_desde_contrato.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: $minDate
    });
    $fecha_desde_contrato_nuevo.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: $minDate
    });
    $fecha_hasta_contrato.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: $minDate
    });


    /////////


    $("#ubicaciones").select2();
    var opciones = $('#ubicaciones option:selected').sort().clone();
    $('#ubicacion_principal').empty();
    $("#ubicacion_principal").append(new Option("Seleccione una Ubicación Principal", ""));
    $('#ubicacion_principal').append(opciones);

    $("#ubicaciones").on('change', function () {
        var options = $('#ubicaciones option:selected').sort().clone();
        $('#ubicacion_principal').empty();
        $("#ubicacion_principal").append(new Option("Seleccione una Ubicación Principal", ""));
        $('#ubicacion_principal').append(options);

    });


});



function buscarDatosPersonaEmpleado() {
    buscarDatosEmpleados();
}

function buscarDatosEmpleados() {
    if ($("#documento").val() != "") {
        $.ajax({
            url: $base_url + '/index.php/empleados/buscarDatosEmpleadoAjax',
            type: 'POST',

            data: {
                documento: $("#documento").val()
            },
            success: function (data) {
                if (data.id != null) {
                    window.location.href = $base_url + '/index.php/empleados/modificacion/'+data.id;
                }
                else {
                    mensajes_alerta
                        .ocultarMensaje()
                        .setError('No se encontró documento')
                        .printError();
                }
            },
            error: function () {
                console.error("Error ajax");
            }
        });
    }
    else {
        alert("Debe ingresar un nombre de usuario");
    }
}

