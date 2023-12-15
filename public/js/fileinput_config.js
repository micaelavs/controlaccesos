$(document).on('ready', function() {
    $(".filestyle").fileinput({
      language: 'es',
      browseLabel: '',
      showRemove: false,
      showUpload: false,
      previewFileIcon: '<i class="glyphicon glyphicon-eye"></i>',
      previewFileIconClass: 'file-icon-4x',
      maxFileSize: 10240,
      msgNoFilesSelected: 'El tamaño del archivo seleccionado no debe superar los 10Mb.'
    });
  });