$(document).ready(function () {
    
    //CAMPOS FECHA
    var formatoFecha = 'DD/MM/YYYY';
    $('#fecha_desde').datetimepicker({
        format: formatoFecha
    }).on('dp.change',function(e){
        let fecha_select = moment($('#fecha_desde').val(),"DD/MM/YYYY");
        $('#fecha_hasta').data("DateTimePicker").minDate(fecha_select);
        $('#fecha_hasta').data("DateTimePicker").maxDate(fecha_select.add(180, 'days'));
    });
    
    $('#fecha_hasta').datetimepicker({
        maxDate:  moment().add(180, 'days'),
        format: formatoFecha
    }).val($fecha_hasta);

    $( function() {
    
        var url = $base_url+'/index.php/Empleados/json_buscar_empleado';
        $('#autorizante_nombre').typeahead({
            onSelect: function (item) {
                $("#id_empleado_autorizante").val(item.value);
                $("#autorizante_nombre").val(item.text);
            },
            ajax: {
                url: url,
                timeout: 500,
                displayField: 'nombre',
                valueField: 'idEmp',
                triggerLength: 3,
                method: "get",
                loadingClass: "loading-circle",
                preDispatch: function (query) {
                    return {
                        search: query,
                    }
                },
                preProcess: function (data) {
                    if (data.success === false) {
                        return false;
                    }
                    return data.lista.map(function ($empleado) {    
                        return {
                            "idEmp": $empleado.id,
                            "nombre": $empleado.nombre.concat(" ",$empleado.apellido),
                            "documento":$empleado.documento
                        }
                    });
                }
            }
        });
    
    });
    
    $("#btn_buscar_documento").on('click',function (e) { 
        e.preventDefault();
        documento = $("#documento_persona").val();
        $.ajax({
            type: "POST",
            url: $base_url + '/index.php/Personas/buscarPersonaAjax',
            data: {documento : documento},
            dataType: "json",
            success: function (response) {
                $("#error_persona").hide();
                console.log(response);
                if(response.id != null){
                    $("#nombre_persona").val(response.nombre);
                    $("#apellido_persona").val(response.apellido);
                    $("#genero_persona").removeAttr('disabled').val(response.genero);
                    $("#genero_persona").attr('disabled',true);
                }else{
                    $("#error_persona").text('Persona no encontrada, complete los demás campos.');
                    $("#nombre_persona").text('');
                    $("#apellido_persona").text('');
                    $("#error_persona").show();
                    $("#nombre_persona").removeAttr('disabled').val('');
                    $("#apellido_persona").removeAttr('disabled').val('');
                    $("#genero_persona").removeAttr('disabled').val('');

                }
            }
        });
    });

    $("#ubicacion_autorizada").select2();
});