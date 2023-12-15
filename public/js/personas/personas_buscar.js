function buscarPersona(){
  if($("#documento").val() != ""){
       $.ajax({
            url: $("#linkAjaxBuscarPersona").val(),
            type: 'POST',
            
            data: {
                documento : $("#documento").val()
            },
            success:function(data){
              
               if(data.id != null) {
                $("#nombre").val(data.nombre);
                $("#apellido").val(data.apellido);
                $("#genero").val(data.genero);        
                
                $('#alerta_success').css("display","block");		
                $("#alerta_success").html('<i class="fa fa-list"></i> Se encontró persona con documento : ' + $("#documento").val());   
                $('#alerta_success').fadeOut(5000);
               $('.default-filtro').fadeOut(1000);
               }
               else{
                
                $("#nombre").val('');
                $("#apellido").val('');
                $("#genero").val('');

                $('#alerta').css("display","block");		
                $("#alerta").html('<i class="fa fa-list"></i> No se encontró persona con documento : ' + $("#documento").val());   
                $('#alerta').fadeOut(5000);
               $('.default-filtro').fadeOut(1000);
               }
            },
            error:function(){
              console.error("Error ajax");
            }
       });
  }
  else{
    alert("Debe ingresar un documento de usuario");
  }
}
