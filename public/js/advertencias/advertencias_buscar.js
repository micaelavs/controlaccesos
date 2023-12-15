$(document).ready(function () {

  $("#buscarPersonaPersonal").on('click',function (e) { 
    e.preventDefault();
    if ($("#documento").val() != "") {
      $.ajax({
        url: $("#linkAjaxBuscarPersonaPers").val(),
        type: 'POST',
  
        data: {
          persona_documento: $("#persona_documento").val()
        },
        success: function (data) {
          $("#error_persona").hide();
          $("#success_persona").hide();
          if (data.dato == null) {
            $("#persona_nombre").val('');
            $("#persona_apellido").val('');
            $("#error_persona").text(data.msj);
            $("#error_persona").show();
          } else {
            $("#persona_nombre").val(data.dato.nombre);
            $("#persona_apellido").val(data.dato.apellido);
            $("#success_persona").text(data.msj);
            $("#success_persona").show();
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
  });

});



function buscarPersonasolicitante() {
  if ($("#documento").val() != "") {
    $.ajax({
      url: $("#linkAjaxBuscarPersonaSol").val(),
      type: 'POST',

      data: {
        solicitante_documento: $("#solicitante_documento").val()
      },
      success: function (data) {
        $("#error_solicitante").hide();
        $("#success_solicitante").hide();
        if (data.dato == null) {
          $("#solicitante_nombre").val('');
          $("#solicitante_apellido").val('');
          $("#error_solicitante").text(data.msj);
          $("#error_solicitante").show();
        } else {
          $("#solicitante_nombre").val(data.dato.nombre);
          $("#solicitante_apellido").val(data.dato.apellido);
          $("#success_solicitante").text(data.msj);
          $("#success_solicitante").show();
        }
      },
      error: function () {
        console.error("Error ajax");
      }
    });
  }
  else {
    alert("Debe ingresar un documento");
  }
}
