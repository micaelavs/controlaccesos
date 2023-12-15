$(document).ready(function () {

    $("#btn_buscar_documento").click(function (e) { 
        e.preventDefault();
        documento = $("#documento_persona").val();
        $.ajax({
            type: "GET",
            url: $base_url + '/index.php/Relojes/ajax_buscarPersona/'+$id_reloj,
            data: {documento : documento},
            dataType: "json",
            success: function (response) {
                $("#error_persona").hide();
                if(!response.errores){
                    $("#nombre_persona").text(response.nombre);
                    $("#apellido_persona").text(response.apellido);
                }else{
                    $("#error_persona").text(response.errores);
                    $("#nombre_persona").text('');
                    $("#apellido_persona").text('');
                    $("#error_persona").show();
                }
            }
        });
    });

});