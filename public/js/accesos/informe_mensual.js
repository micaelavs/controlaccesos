$(document).ready(function () {
    $('#mes').datetimepicker({
        format: 'MMMM',
    });
    $('#anio').datetimepicker({
        format: 'YYYY'
    });

    $('#dependencia').select2();

});