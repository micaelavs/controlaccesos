function buscarPersona(){
	if($("#persona_documento").val() != ""){
		 $.ajax({
			  url: $("#linkAjaxBuscarPersonal").val(),
			  type: 'POST',
			  
			  data: {
				  documento : $("#persona_documento").val()
			  },
			  success:function(data){
				$("#error_personal").hide();
				$("#success_personal").hide();
				if (data.dato == null) {
					$("#persona_nombre").val('');
					$("#persona_apellido").val('');
					$("#error_personal").text(data.msj);
					$("#error_personal").show();
				} else {
					$("#persona_nombre").val(data.dato.nombre);
					$("#persona_apellido").val(data.dato.apellido);
					$("#success_personal").text(data.msj);
					$("#success_personal").show();
				}
			  },
			  error:function(jqXHR, textStatus, errorThrown){
				console.error(jqXHR);
				console.error(textStatus);
				console.error(errorThrown);
			  }
		 });
	}
	else{
	  alert("Debe ingresar un documento");
	}
  }
  function buscarSolicitante(){
	if($("#solicitante_documento").val() != ""){
		 $.ajax({
			  url: $("#linkAjaxBuscarSolicitante").val(),
			  type: 'POST',
			  
			  data: {
				  documento : $("#solicitante_documento").val()
			  },
			  success:function(data){
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
			  error:function(jqXHR, textStatus, errorThrown){
				console.error(jqXHR);
				console.error(textStatus);
				console.error(errorThrown);
			  }
		 });
	}
	else{
	  alert("Debe ingresar un documento");
	}
  }
  
  $(document).ready(function () {
	if($persona_documento != ""){
		$("#persona_documento").val($persona_documento);
		buscarPersona();
	}

	if($solicitante_documento != ""){
		$("#solicitante_documento").val($solicitante_documento);
		buscarSolicitante();
	}
  });