
function buscarContratista (){
    
    let mensajes_alerta = new Mensajes($("#mensajes"));
    $.ajax({
        url: $base_url + '/index.php/Contratistas/buscar_contratista',
        type: 'POST',

        data: {
            cuit: $("#cuit").val()
        },
        success: function (data) {
            if(data.id == null) {
                $('#alerta').css("display","block");		
                $("#alerta").html('<i class="fa fa-list"></i> El <b>Cuit</b> no existe en los registros.');   
                $('#alerta').fadeOut(5000);
               $('.default-filtro').fadeOut(1000);
            }else{
                window.location.href = $base_url + '/index.php/Contratistas/modificacion/'+data.id;
            }
            
        },
        error: function () {
            console.error("Error ajax");
        }
    });

}

$("#provincia").change(function (e) { 
    e.preventDefault();
    provincia_id = $(this).val();
    $("#localidad").html(`<option value>Seleccione</option>`);
    if(provincia_id != ""){
        $.ajax({
            type: "GET",
            url: $base_url + '/index.php/Contratistas/ajax_localidades',
            data: {provincia_id : provincia_id},
            dataType: "json",
            success: function (response) {
                $.each(response, function (index, value) { 
                    $("#localidad").append(`<option value='${index}'>${value}</option>`);
                });
            }
        });
    }
});