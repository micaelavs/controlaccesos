$(document).ready(function () {

  var $art_inicio = $('#art_inicio');
  var $art_fin = $('#art_fin');
  var $minDate = false;

  $art_inicio.datetimepicker({
      icons: {
          time: "fa fa-clock-o",
          date: "fa fa-calendar",
          up: "fa fa-arrow-up",
          down: "fa fa-arrow-down"
      },
      format: 'DD/MM/YYYY',
      defaultDate: $minDate
  }).on('dp.change',function(e){
    let fecha_select = moment(e.date,"DD/MM/YYYY");
    $('#art_fin').data("DateTimePicker").minDate(fecha_select);
  });

  $art_fin.datetimepicker({
      icons: {
          time: "fa fa-clock-o",
          date: "fa fa-calendar",
          up: "fa fa-arrow-up",
          down: "fa fa-arrow-down"
      },
      format: 'DD/MM/YYYY',
      defaultDate: $minDate
  });

});

function buscarPersonaPersonal() {
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
}

function buscarPersonaAutorizante() {
  if ($("#documento").val() != "") {
    $.ajax({
      url: $("#linkAjaxBuscarPersonaAut").val(),
      type: 'POST',

      data: {
        autorizante_documento: $("#autorizante_documento").val()
      },
      success: function (data) {
        $("#error_autorizante").hide();
        $("#success_autorizante").hide();
        if (data.dato == null) {
          $("#autorizante_nombre").val('');
          $("#autorizante_apellido").val('');
          $("#error_autorizante").text(data.msj);
          $("#error_autorizante").show();
        } else {
          $("#autorizante_nombre").val(data.dato.nombre);
          $("#autorizante_apellido").val(data.dato.apellido);
          $("#success_autorizante").text(data.msj);
          $("#success_autorizante").show();
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
