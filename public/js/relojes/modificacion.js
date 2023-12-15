$(document).ready(function () {
	
	if($acceso_tarjeta == 1){
		$('#acceso_tarjeta').attr('checked',true);
	}
	if($acceso_restringido == 1){
		$('#acceso_restringido').attr('checked',true);
	}


	$('#acceso_tarjeta').click(function () {
		if($(this).val() == 1){
			$('#acceso_tarjeta').attr('value',0);
		}else{
			$('#acceso_tarjeta').attr('value',1);
		}
	});

	$('#acceso_restringido').click(function () {
		if($(this).val() == 1){
			$('#acceso_restringido').attr('value',0);
		}else{
			$('#acceso_restringido').attr('value',1);
		}
	});

});