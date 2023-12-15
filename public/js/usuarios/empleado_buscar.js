$(document).ready(function () {
  if ($("#empleado_documento").val() != "") {
    $("#guardar").prop('disabled', false);
  }
  else {
    $("#guardar").prop('disabled', true);    
  }
});


function buscarEmpleadoUsuario() {
    if ($("#empleado_documento").val() != "") {
      $.ajax({
        url: $("#linkAjaxBuscarEmpleado").val(),
        type: 'POST',
  
        data: {
            empleado_documento: $("#empleado_documento").val()
        },
        success: function (data) {
          $("#error_autorizante").hide();
          $("#success_autorizante").hide();
          if (data.dato == null) {
            $("#persona_nombre").val('');
            $("#empleado_ubicacion").val('');
            $("#error_autorizante").text(data.msj);
            $("#error_autorizante").show();
            $("#guardar").prop('disabled', true);    
          } else {
            $("#persona_nombre").val(data.dato.nombre + " " + data.dato.apellido);    
            $("#empleado_ubicacion").val(data.ubicacion);        
            $("#success_autorizante").text(data.msj);
            $("#success_autorizante").show();
            $("#guardar").prop('disabled', false);
          }
        },
        error: function () {
          console.error("Error ajax");
        }
      });
    }
    else {
      alert("Debe ingresar un documento");
      $("#persona_nombre").val('');
      $("#empleado_ubicacion").val('');
      $("#guardar").prop('disabled', true);
    }
  }