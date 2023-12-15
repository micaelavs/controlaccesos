var nrostarjetas = [];  

$(document).ready(function () {
    $("#tarjetas_lista").select2();
    $("#tarjetas_lista_des").select2();
    $("#reloj_lista").select2();

    var mensajes_alerta = new Mensajes($("#mensajes"));

    $("#reloj_lista").on('change', function () {
        ajaxRelojTM();
        $('#enrolar-tm').attr('nodo-id',$(this).val());
    });


    $("#tarjetas_lista_des").on('change', function () {
        armarArrayDesenrolar();
    });


    $("#tarjetas_lista").on('change', function () {
        nrostarjetas =  ($("#tarjetas_lista").val() || []);
        armarArrayElemSelect();
    });

    $(document).on('click', '#enrolar-tm', function (ev) {
        let access_id = $(this).attr('access-id');
        let nodo_id = $(this).attr('nodo-id');
        let tarjetas_nuevas = $(this).attr('tarjetasinput')
        
        if(!nodo_id){
            mensajes_alerta
            .ocultarMensaje()
            .setError('Debe elegir un reloj')
            .printError();
        }else{
            mensajes_alerta.ocultarMensaje();
            $.ajax({
                url: $base_url + '/index.php/Tarjetas/actualizar_tarjeta',
                data: {
                    access_id: access_id,
                    nodo_id: nodo_id, //Debo obtener el reloj y su ubicacion
                    tarjetas_nuevas: tarjetas_nuevas
                },
                method: "POST"
            })
                .done(function (data) {
                    if(data.altas.length > 0){
                        data.altas.forEach(element => {
                            mensajes_alerta
                            .setMensaje('Tarjeta '+element+' creada de forma correcta.', 'success', 'check')
                            .printMensaje()
                        });
                        nrostarjetas = [];
                        $('#tarjetas_lista').html('');
                        $("#tarjetas_lista option:selected").prop("selected", false);
                    }
                    if(data.actualizadas.length > 0){
                        data.actualizadas.forEach(element => {
                            mensajes_alerta
                            .setMensaje('Tarjeta '+element+' enrolada de forma correcta.', 'success', 'check')
                            .printMensaje()
                        });
                        nrostarjetas = [];
                        $('#tarjetas_lista').html('');
                        $("#tarjetas_lista option:selected").prop("selected", false);
                    }
                    if(data.errores.length > 0){
                        data.errores.forEach(mensaje => {    
                            mensajes_alerta
                                .setMensaje(mensaje[0], 'danger', 'times-circle')
                                .printMensaje();
                        });
                    }
                    ajaxRelojTM();
                })
                .fail(function () {
                    mensajes_alerta
                        .ocultarMensaje()
                        .setError('Error al enrolar Tarjetas.')
                        .printError();
                })
                .always(function () {
                    mensajeCargando.getMensajeHtml().hide();
                })
        }


    });


    $(document).on('click', '#desenrolar-tm', function (ev) {
        var $btn = $(ev.target);
        var access_id = $btn.attr('access-id');
        var nodo_id = $btn.attr('nodo-id');

        mensajes_alerta.ocultarMensaje();
        $.ajax({
            url: $base_url + '/index.php/Tarjetas/actualizar_tarjeta_desenrolar',
            data: {
                access_id: access_id,
                nodo_id: nodo_id, //Debo obtener el reloj y su ubicacion
            },
            method: "GET"
        })
            .done(function (data) {
                if(data.bajas.length > 0){
                    data.bajas.forEach(element => {
                        mensajes_alerta
                        .setMensaje('Tarjeta '+element+' desenrolada de forma correcta.', 'success', 'check')
                        .printMensaje()
                    });
                    $("#tarjetas_lista option:selected").prop("selected", false);
                    $btn.attr('nodo-id','');
                    $btn.attr('access-id','');
                }

                $('#enrolar-tm').attr('tarjetasinput','');
                $('#enrolar-tm').attr('access-id','');            
                
                ajaxRelojTM();
            })
            .fail(function () {
                mensajes_alerta
                    .ocultarMensaje()
                    .setError('Error al desenrolar Tarjetas.')
                    .printError();
            })
            .always(function () {
                mensajeCargando.getMensajeHtml().hide();
            })

    });


    $('#modal_nro_tarjeta').keypress(function (event) {
        if (event.which != 8 && isNaN(String.fromCharCode(event.which))) {
            event.preventDefault();
        }
    });

    $('#modal_nro_tarjeta').keyup(function (event) {
        //LONG_TARJETA_CREDENCIAL actual = 8
        if ($('#modal_nro_tarjeta').val().length > 8) {
            $("#modal_nro_tarjeta").val("");
        }
        if ($('#modal_nro_tarjeta').val().length == 8 && $("#textoTarjetaAsignar").text() == "") {
            $("#textoTarjetaAsignar").append($('#modal_nro_tarjeta').val());
            $("#hdNroTarjeta").val($('#modal_nro_tarjeta').val());
            $("#modal_btn_guardar_nro_tarjeta").prop("disabled", false);
            $("#modal_nro_tarjeta").val("");
            $("#modal_nro_tarjeta").focus();
        }
        else {
            if (event.which == 13) {
                return false;
            }
            else {
                $("#textoTarjetaAsignar").text("");
                $("#modal_btn_guardar_nro_tarjeta").prop("disabled", true);
            }
        }
    });

    $('#modal_nro_tarjeta').on("cut copy paste", function (e) {
        e.preventDefault();
    });

    $('#modalNumeroTarjeta').on('hidden.bs.modal', function () {
        $("#modal_nro_tarjeta").val("");
        $("#textoTarjetaAsignar").text("");
        $('#modal_nro_tarjeta').removeClass('form-control').addClass('oculto');
        $('#chk_ocultar_caja_texto').prop('checked', false);
    });

    $('#modal_btn_guardar_nro_tarjeta').click(function () {
        //LONG_TARJETA_CREDENCIAL actual = 8
        //Siempre debería ser long = LONG_TARJETA_CREDENCIAL dado que el botón ASIGNAR estaba habilitado. Se valida igualmente.
        var options = []
        $.each($("#tarjetas_lista").prop("options"), function(i, opt) {
            options.push(opt.textContent)
        })
    
        if ($('#hdNroTarjeta').val().length >= 8) {
            $("#modalNumeroTarjeta").modal('hide');
            $("#lbNroCodigo").text('Número Tarjeta ');
            $("#lbNroCodigo").append('<i class="fa fa-close" title="Corregir número de tarjeta" onClick="limpiarNroTarjeta()"></i>');
            if(!options.includes($('#hdNroTarjeta').val())){
                $('#tarjetas_lista').append($('<option>').val($('#hdNroTarjeta').val()).text($('#hdNroTarjeta').val()).attr("selected", true));
            }else{
                $("#span_error").html('La tarjeta ya existe en el listado');
                $('#span_error').fadeOut(4000);
            }
            armarArrayElemSelect();
        }
    });

    $('#modalNumeroTarjeta').on('shown.bs.modal', function () {
        $('#modal_nro_tarjeta').focus();
        $("#nroTarjeta").val("");
    });

    ajaxRelojTM();

});

function ajaxRelojTM() {
    $('#tarjetas_lista_des').html('');

    var reloj_seleccionado = $("#reloj_lista").val();
    if(reloj_seleccionado != ''){
        mensajeCargando = (new Mensajes($("#mensajes"))).setMensaje('Por favor aguarde, estamos cargando el listado de Tarjetas!', 'info', 'exclamation-circle').printMensaje();
        $.ajax({
            url: $base_url + '/index.php/Tarjetas/actualizar_listaTM',
            data: {
                reloj_seleccionado: reloj_seleccionado
            },
            method: "POST"
        })
            .done(function (data) {
                if (data.tm !== undefined) {
                    //addOptions(data.tm, 'select#tarjetas_lista', true);
                    addOptions(data.tm, 'select#tarjetas_lista_des', true);
                }
            })
            .always(function () {
                mensajeCargando.getMensajeHtml().hide();
            });
    }
    }

function addOptions($options, $dom_select, $not_clean = false) {
    $obj = $($dom_select);
    if (!($obj[0].nodeName == 'SELECT' || $obj[0].nodeName == 'OPTGROUP')) return $obj;
    $value_pre_selected = false;

    if ($obj.val() != '' && $not_clean) {
        $value_pre_selected = $obj.val();
    }
    // Limpiar etiquetas <Select> antes de llenarlas
    $obj.html('');
    /*if ($obj[0].nodeName == 'SELECT') {
        $obj.append($('<option>', {
            value: '',
            text: 'Seleccione'
        }));
    }*/
    // Llenar etiquetas <Select>
    $.each($options, function (i, item) {
        $_options = {
            value: item.access_id,
            text: item.access_id,
        };
        if (item.borrado != '0') {
            $_options.disabled = 'disabled';
        }
        if (Array.isArray($value_pre_selected)) {
            if ($.inArray(item.id, $value_pre_selected) != -1) {
                $_options.selected = 'selected';
            }
        } else {
            if (item.id == $value_pre_selected) {
                $_options.selected = 'selected';
            }
        }

        $obj.append($('<option>', $_options));
    });
    return $obj;
}



/*Agrega la option al select con el nro de tarjeta seteado */
function inserta_nroTarjeta() {

    var options = []
    $.each($("#tarjetas_lista").prop("options"), function(i, opt) {
        options.push(opt.textContent)
    })

    if ($("#nroTarjeta").val().length >= 8) {
        optionText = $("#nroTarjeta").val();
        optionValue = $("#nroTarjeta").val();
        if(!options.includes(optionText)){
            $('#tarjetas_lista').append($('<option>').val(optionValue).text(optionText).attr("selected", true));
        }else{
            $("#span_error").html('La tarjeta ya existe en el listado');
            $('#span_error').fadeOut(4000);
        }
        armarArrayElemSelect();
        $("#nroTarjeta").val('')
    }else{
        $("#span_error").html('El número de tarjeta debe ser de al menos 8 dígitos');
        $('#span_error').fadeOut(4000);
    }
}

/*Agrega al atributo data-access-id el array de los nros seleccionados
y al atributo data-ubicacion-id el value (ubicacion id) del reloj seleccionado*/
function armarArrayElemSelect() {
    if ($('#reloj_lista :selected').val() == "seleccione") {
        alert("Seleccione un reloj");
    } else {
        var selected = [];
        i = 0
        nrostarjetas = [];
        $('#tarjetas_lista :selected').each(function () {
            selected[i++] = $(this).text();
            nrostarjetas.push($(this).val());
        });
        var nodoReloj = $('#reloj_lista :selected').val();

        $('#enrolar-tm').attr('access-id', selected);
        $('#enrolar-tm').attr('nodo-id', nodoReloj);
        $('#enrolar-tm').attr('tarjetasinput', nrostarjetas);
    }
}

function armarArrayDesenrolar() {
    if ($('#reloj_lista :selected').val() == " ") {
        alert("Seleccione un reloj");
    } else {
        var selected = [];
        i = 0
        $('#tarjetas_lista_des :selected').each(function () {
            selected[i++] = $(this).text();
        });

        var nodoReloj = $('#reloj_lista :selected').val();

        $('#desenrolar-tm').attr('access-id', selected);
        $('#desenrolar-tm').attr('nodo-id', nodoReloj);
    }
}

function popUpTarjeta() {
    $("#modalNumeroTarjeta").modal('toggle');
}

function ocultarCajaTexto() {
    $("#modal_btn_guardar_nro_tarjeta").prop("disabled", true);
    $('#modal_nro_tarjeta').val("");
    $("#textoTarjetaAsignar").text("");

    if ($('#modal_nro_tarjeta').hasClass('oculto')) {
        $('#modal_nro_tarjeta').removeClass('oculto').addClass('form-control');
    }
    else {
        $('#modal_nro_tarjeta').removeClass('form-control').addClass('oculto');
    }

    $('#modal_nro_tarjeta').focus();
}