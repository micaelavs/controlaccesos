$(document).ready(function () {
	$(document).delegate('[data-toggle="tooltip"]', 'mouseover', function(){
		$(this).tooltip({ html : true });
	});
	$(document).delegate('[data-toggle="popover"]', 'mouseover', function(){
		$(this).popover({ html : true });
	});

	$("a[href='?c=base&a=manual']").attr('target', '_blank');

	$(".fecha.libre").datetimepicker({
	  format: 'DD/MM/YYYY'
	})
	$(".fecha").datetimepicker({
      maxDate: 'now',
      format: 'DD/MM/YYYY'
    })
	if($(".filestyle").lenght != 'undefined' && typeof $.fn.fileinput != 'undefined'){
		$(".filestyle").fileinput({
			language: 'es',
			browseLabel: '',
			showRemove: false,
			showUpload: false,
			previewFileIcon: '<i class="glyphicon glyphicon-eye"></i>',
			previewFileIconClass: 'file-icon-4x'
		});
	}
/**
 * Opciones por defecto para todas las implementaciones de DataTable()
*/
	if (typeof($.fn.dataTable) !== 'undefined') {
		$.fn.dataTable.ext.errMode      = 'none';
		$.extend( $.fn.dataTable.defaults, {
			language: {
				url: $endpoint_cdn+'/datatables/1.10.12/Spanish_sym.json',
				decimal: ',',
				thousands: '.',
				search: '_INPUT_',
				searchPlaceholder: 'Ingrese búsqueda'
			},
			info: true,
			buttons: [],
			order: [[0, 'desc']],
			ordering:  true,
			searching: true,
			columnDefs: [
				{targets: 3, searchable: false, orderable: false}
			]
		});
	}
	if(typeof $data_table_init !== 'undefined' && typeof($.fn.dataTable) !== 'undefined'){
		$('.'+$data_table_init).DataTable();
	}
	
	$('.container').delegate('select.activarSelect2', 'mouseover', function (e) {
		if($(this).hasClass('select2-hidden-accessible') || $(this).is(':disabled')){
			return;
		}
		$(this).select2();	
	});
	$('#id_ubicacion_actual').select2();
});

/**
 * Llena los elementos de una etiqueta <select> pasandole un array.
 * Se encarga de limpiar el contenido antes del llenado o mantener los ids previamente seleccionados.
 *
 * @param boolen	$not_clean	- Si esta en true, mantiene el "value" preseleecionado, ideal para articular con PHP.
 * @param string	$dom_select	- valor usado para seleccionar el elemento dom. E.j.: 'select#id_situacion_revista'
 * @param array		$options	- Opciones para el las etiquetas select con formato ['id' => '', nombre => '', borrado => '']
 * @return JQuery
*/
	function addOptions($options, $dom_select, $not_clean=false){
		$obj				= $($dom_select);
		if(typeof $obj[0] == 'undefined' || typeof $obj[0].nodeName == 'undefined') return $obj;
		if(! ($obj[0].nodeName	== 'SELECT'  || $obj[0].nodeName  == 'OPTGROUP')) return $obj;
		$value_pre_selected	= false;
		if($obj.val() != '' && $not_clean){
			$value_pre_selected = $obj.val();
		}
// Limpiar etiquetas <Select> antes de llenarlas
		$obj.html('');
		if($obj[0].nodeName  == 'SELECT'){
			$obj.append($('<option>', {
				value: '',
				text : 'Seleccione'
			}));
		}
// Llenar etiquetas <Select>
		$.each($options, function (i, item) {
			$_options	= {
				value: item.id,
				text : item.nombre,
			};
			if(item.borrado != '0'){
				$_options.disabled	= 'disabled';
			}
			if(Array.isArray($value_pre_selected)) {
				if($.inArray(item.id, $value_pre_selected) != -1){
					$_options.selected	= 'selected';
				}
			}else{
				if(item.id	== $value_pre_selected){
					$_options.selected	= 'selected';
				}
			}
			$obj.append($('<option>', $_options));
		});
		return $obj;
	}

function addOptionsMulti($options, $dom_select, $selected ){
	    $obj				= $($dom_select);
	    if($obj[0].nodeName	!= 'SELECT') return $obj;

	// Limpiar etiquetas <Select> antes de llenarlas
	    $obj.html('');
	    $obj.append($('<option>', {
	      value: '',
	      text : 'Seleccione'
	    }));
	// Llenar etiquetas <Select>
	    $.each($options, function (i, item) {
	      $_options	= {
	        value: i,
	        text : item.nombre,
	      };

	      if(item.borrado !=0){ 
	        $_options.disabled	= 'disabled';
	      }
	      
	      if($.inArray( i, $selected) != -1){
	        $_options.selected	= 'selected';
	      }
	      $obj.append($('<option>', $_options));
	    });
	    return $obj;
}
