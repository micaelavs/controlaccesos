$("#btn_buscar_empleado").click(function (e) { 
    e.preventDefault();
    email = $("#email").val();
        $.ajax({
            type: "POST",
            url: $base_url + '/index.php/AlertaRelojes/buscar_user',
            data: {email : email},
            dataType: "json",
            success: function (response) {
                $("#error_persona").hide();
                if(!response.errores){
                    if(response.id == null){
                        $("#error_persona").text('Persona no encontrada.');
                        $("#error_persona").show();
                    }
                    $("#nombre_persona").text((response.nombre) ? response.nombre : '');
                    $("#apellido_persona").text((response.apellido) ? response.apellido : '');
                }else{
                    $("#error_persona").text(response.errores);
                    $("#nombre_persona").text('');
                    $("#apellido_persona").text('');
                    $("#error_persona").show();
                }
            }
        });
});