$(document).ready(function () {

    $(".hora").datetimepicker({
        format: "HH:mm",
    });

    

    $("#select_horario").on('change',function(event) {
        $.ajax({
            type: "GET",
            url: $base_url + '/index.php/empleados/plantilla_horaria/'+$(this).val(),
            data: {},
            success: function (data) {
                $horarios = data[0].horario;
                setearHorarios();
            }
        });
	});


    function setearHorarios(){
        if($horarios){
            $horarios =  (typeof $horarios === 'string' || $horarios instanceof String) ?  JSON.parse($horarios) : $horarios;
            for (let i = 0; i < $horarios.length; i++) {
                if($horarios[i].length == 0 || $horarios[i][0] == '' || $horarios[i][1] == ''){
                    $horarios[i][0] = '00:00';
                    $horarios[i][1] = '00:00';
                }
                let desde = ($horarios[i][0]) ? $horarios[i][0] : '00:00';
                $("#hora_desde"+i).data("DateTimePicker").date(moment(desde,'hh:mm'));
                let hasta = ($horarios[i][1]) ? $horarios[i][1] : '00:00';
                $("#hora_hasta"+i).data("DateTimePicker").date(moment(hasta,'hh:mm'));
               
            }
        }
    }

    setearHorarios();
});