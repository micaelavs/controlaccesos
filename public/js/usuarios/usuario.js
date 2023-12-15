$('#rol').on('change', function (e) {
    $valor = $('#rol').val();
    if ($valor == 10) { //rol rca
        $('.dependencia').show();  
    } else {
        $('.dependencia').hide();  
    }

});

$(document).ready(function() {
    $("#dependencias").select2();
    $valor = $('#rol').val();
    if ($valor == 10) { //rol rca
        $('.dependencia').show();  
    } else {
        $('.dependencia').hide();  
    }

});