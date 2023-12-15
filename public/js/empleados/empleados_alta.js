$(document).ready(function () {

    $('#dependencia').select2();
    var $fecha_desde_principal = $('#fecha_desde_principal');
    var $fecha_hasta_principal = $('#fecha_hasta_principal');

    var desde = ($fecha_desde != '') ? moment($fecha_desde,'DD/MM/YYYY') : false;
    var hasta = ($fecha_hasta != '') ? moment($fecha_hasta,'DD/MM/YYYY') : false;

    $fecha_desde_principal.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: desde
    });
    $fecha_hasta_principal.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: hasta
    });

    $fecha_desde_principal.on("dp.change", function (e) {
        $fecha_hasta_principal.data("DateTimePicker").minDate(e.date);
        $fecha_hasta_principal.data("DateTimePicker").date(null);
    });

    $fecha_hasta_principal.on("dp.change", function (e) {
        $fecha_desde_principal.data("DateTimePicker").maxDate(e.date);
    });

    //////////////////

    var $fecha_desde_contrato = $('#fecha_desde_contrato');
    var $fecha_hasta_contrato = $('#fecha_hasta_contrato');
    
    var desde_contrato = ($desde_contrato != '') ? moment($desde_contrato,'DD/MM/YYYY') : false;
    var hasta_contrato = ($hasta_contrato != '') ? moment($hasta_contrato,'DD/MM/YYYY') : false;

    $fecha_desde_contrato.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: desde_contrato
    });
    $fecha_hasta_contrato.datetimepicker({
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
        format: 'DD/MM/YYYY',
        defaultDate: hasta_contrato
    });

    $fecha_desde_contrato.on("dp.change", function (e) {
        $fecha_hasta_contrato.data("DateTimePicker").minDate(e.date);
        $fecha_hasta_contrato.data("DateTimePicker").date(null);

    });

    $fecha_hasta_contrato.on("dp.change", function (e) {
        $fecha_desde_contrato.data("DateTimePicker").maxDate(e.date);
    });


    /////////
    if($ubicaciones_autorizadas != null){
        $ubicaciones_autorizadas.forEach(ubicacion => {
            $('#ubicaciones option[value="'+ubicacion+'"]').attr('selected',true)
        });
    }

    $("#ubicaciones").select2();
    var opciones = $('#ubicaciones option:selected').sort().clone();
    if(opciones.length == 0){
        $('#ubicacion_principal').empty();
        $("#ubicacion_principal").append(new Option("Seleccione una ubicación Autorizada", ""));
    }else{
        $('#ubicacion_principal').append(opciones.removeAttr('selected'));
        $('#ubicacion_principal').removeAttr('disabled');
        if($ubicacion != 'null'){
            $('#ubicacion_principal').val($ubicacion)
        }else{
            $('#ubicacion_principal').val('')
        }
    }


    $("#ubicaciones").on('change', function () {
        var options = $('#ubicaciones option:selected').sort().clone();
        $('#ubicacion_principal').empty();
        $("#ubicacion_principal").append(new Option("Seleccione una Ubicación Principal", ""));
        $('#ubicacion_principal').append(options);
        $('#ubicacion_principal').removeAttr('disabled');

    });


});

function buscarDatosPersonaEmpleado() {
    buscarDatosPersona();
    buscarDatosEmpleados();
}

function buscarDatosPersona() {
    if ($("#documento").val() != "") {
        $.ajax({
            url: $("#buscarDatosPersonaAjax").val(),
            type: 'POST',

            data: {
                documento: $("#documento").val()
            },
            success: function (data) {
                if (data) {
                    $("#nombre").val(data.nombre);
                    $("#apellido").val(data.apellido);
                    $("#genero").val(data.genero);

                }
                else {

                    unsetDataForm();
                    alert("No se encontró documento");
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

function buscarDatosEmpleados() {
    if ($("#documento").val() != "") {
        $.ajax({
            url: $("#buscarDatosEmpleadoAjax").val(),
            type: 'POST',

            data: {
                documento: $("#documento").val()
            },
            success: function (data) {
                if (data) {
                    setDataEmpleado(data)
                    $("#boton_titulo").val('modificacion')
                }
                else {
                    //console.error("Error ajax");
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

function convertDateString(fecha) {
    var m = new Date(fecha);
    var dateString = ("0" + m.getUTCDate()).slice(-2) + "/" + ("0" + (m.getUTCMonth() + 1)).slice(-2) + "/" + m.getUTCFullYear();
    return dateString;
}

function unsetDataForm() {
    $("#nombre").val('');
    $("#apellido").val('');
    $("#genero").val('');
    $("#cuit").val('');
    $("#email").val('');
    $("#dependencia").val('');
    $("#fecha_desde_p").val('');
    $("#fecha_hasta_p").val('');
    $("#cargo").val('');
    $("#contrato").val('');
    $("#fecha_desde_c").val('');
    $("#fecha_hasta_c").val('');
    $("#ubicaciones").val('').change();
    $("#ubicacion_principal").val('');
    $("#observacion").val('');
    $("#planilla_reloj").prop("checked", false);

}
function setDataEmpleado(data) {
    $("#cuit").val(data.cuit);
    $("#email").val(data.email);
    $("#dependencia").val(data.dependencia_principal);
    if (data.desde_principal) {
        let desde_principal = convertDateString(data.desde_principal.date);
        $("#fecha_desde_p").val(desde_principal);
    };
    if (data.hasta_principal) {
        let hasta_principal = convertDateString(data.hasta_principal.date);
        $("#fecha_hasta_p").val(hasta_principal);
    };
    $("#cargo").val(data.cargo);
    $("#contrato").val(data.id_tipo_contrato);
    if (data.desde_contrato) {
        let desde_contrato = convertDateString(data.desde_contrato.date);
        $("#fecha_desde_c").val(desde_contrato)
    };
    if (data.hasta_contrato) {
        let hasta_contrato = convertDateString(data.hasta_contrato.date);
        $("#fecha_hasta_c").val(hasta_contrato);
    };
    let array_ub = data.ubicaciones_autorizadas.replace(/ /g, '').split(',');
    $("#ubicaciones").val(array_ub).change();
    $("#ubicacion_principal").val(data.ubicacion);
    $("#observacion").val(data.observacion);

    if (data.planilla_reloj == 1) {
        $("#planilla_reloj").prop("checked", true);
    } else {
        $("#planilla_reloj").prop("checked", false);
    };

}