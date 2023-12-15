$(document).ready(function () {

	//listado de novedades index
	if($('.novedades_listado').length){

		$('#fecha_desde_filtro,.fecha_desde_filtro').datetimepicker({
			        format: 'DD/MM/YYYY'
			    }).on("dp.change", function (e) {
			        update();
			        $('#fecha_desde_filtro').keyup(function() { 
			        	if(this.value == ''){
			        		update();
			       		}
			    	});
			    });


		$('#fecha_hasta_filtro,.fecha_hasta_filtro').datetimepicker({
			        format: 'DD/MM/YYYY'
			    }).on("dp.change", function (e) {
			        update();
			        $('#fecha_hasta_filtro').keyup(function() { 
			        	if(this.value == ''){
			        		update();
			       		}
			    	});
			    });


		$('select#tipo_novedad_filtro').on('change', function($e){
			update();
		});

		$('select#tipo_novedad_filtro').select2();
		/** Consulta al servidor los datos y redibuja la tabla
	     * @return {Void}
	    */
	    function update() {
	        tabla.draw();
	    }

	    /**
	     * Acciones para los filtros, actualizar vista
	    */
	    $('#tipo_novedad_filtro').on('change', update);

	    //para el listado ajax 
		var filtros_dataTable = null;
		var tabla = $('#tabla').DataTable({
	        language: {
	            url: $endpoint_cdn + '/datatables/1.10.12/Spanish_sym.json',
	            decimal: ',',
	            thousands: '.',
	            infoEmpty: 'No hay datos de Novedades...'
	        },
	        processing: true,
	        serverSide: true,
	        //responsive: true,
	        searchDelay: 1200,

	        ajax: {
	            url: $base_url + '/index.php/Novedades/ajax_novedades',
	            contentType: "application/json",
	            data: function (d) {
                filtros_dataTable = $.extend({}, d, {
             		tipo_novedad_filtro			: $('#tipo_novedad_filtro').val(),
                    fecha_desde_filtro  		: $('#fecha_desde_filtro').val(),
                   	fecha_hasta_filtro  		: $('#fecha_hasta_filtro').val()
                });
                return filtros_dataTable; 
                } 	
	        },
	        info: true, 
	        bFilter: true,
	        columnDefs: [
	        	{ targets: 0, width: '10%'}, 
		        { targets: 1, width: '10%'}, 
		        { targets: 2, width: '10%'}, 
		        { targets: 3, width: '15%'}, 
		        { targets: 4, width: '15%'}, 
		        { targets: 5, width: '5%'}, 
	
	        ],
	        order: [[0,'desc']],
	        columns: [
	      
	            {
	                title: 'Fecha Desde',
	                name:  'fecha_desde',
	                data:  'fecha_desde',
	                className: 'text-left',
	                  render: function (data, type, row) {
						if(data == null){
						}else{
							rta = moment(data,'DD/MM/YYYY').format('DD/MM/YYYY'); 
						} 	
						return rta;
					}
	            },
	            {
	                title: 'Fecha Hasta',
	                name:  'fecha_hasta',
	                data:  'fecha_hasta',
	                className: 'text-left',
	                  render: function (data, type, row) {
						if(data == null){
						}else{
							rta = moment(data,'DD/MM/YYYY').format('DD/MM/YYYY'); 
						} 	
						return rta;
					}
	            },
	            {
	                title: 'Documento',
	                name:  'documento',
	                data:  'documento',
	                className: 'text-left'
	            },
	            {
	                title: 'Nombre',
	                name:  'nombre',
	                data:  'nombre',
	                className: 'text-left'
	            },
	            {
	                title: 'Tipo Novedad',
	                name:  'tipo_novedad',
	                data:  'tipo_novedad',
	                className: 'text-left'
	            },
	            {
	                title: 'Acciones',
	                data: 'acciones',
	                name: 'acciones',
	                className: 'text-center',
	                orderable: false,
	                render: function (data, type, row) {
	                    var $html = '';
	                    $html += '<div class="btn-group btn-group-sm">';
	                    $html += ' <a href="' + $base_url + '/index.php/novedades/modificacion/' + row.id + '" data-user="" data-toggle="tooltip" data-placement="top" title="Modificar Novedad" target="_self"><i class="fa fa-pencil"></i></a>&nbsp;';
	                    $html += ' <a href="' + $base_url + '/index.php/novedades/baja/' + row.id + '" class="borrar" data-user="" data-toggle="tooltip" data-placement="top" title="Baja de novedad" target="_self"><i class="fa fa-trash"></i></a>';
	                    $html += '</div>';
	                    return $html;
	                }
	            },
	        ]
	    });


		//filtros para el exportador
	    $(".accion_exportador").click(function () {
	    var form = $('<form/>', {id:'form_ln' , action : $(this).val(), method : 'POST'});
	    $(this).append(form);
	    form.append($('<input/>', {name: 'search', type: 'hidden', value: $('div.dataTables_filter input').val() }))
	        .append($('<input/>', {name: 'campo_sort', type: 'hidden', value: $('#tabla').dataTable().fnSettings().aoColumns[$('#tabla').dataTable().fnSettings().aaSorting[0][0]].name }))
	        .append($('<input/>', {name: 'dir', type: 'hidden', value: $('#tabla').dataTable().fnSettings().aaSorting[0][1] }))
	        .append($('<input/>', {name: 'rows', type: 'hidden', value: $('#tabla').dataTable().fnSettings().fnRecordsDisplay() }))
	        .append($('<input/>', {name: 'tipo_novedad', type: 'hidden', value:$('#tipo_novedad_filtro').val() }))
	        .append($('<input/>', {name: 'fecha_desde', type: 'hidden', value:$('#fecha_desde_filtro').val() }))
	        .append($('<input/>', {name: 'fecha_hasta', type: 'hidden', value:$('#fecha_hasta_filtro').val() }));
	     form.submit();
		});



	}//fin listado index

	//listado ultimas siete novedades
	if($('.novedades_ultimas_siete').length){	

		var filtros_dataTable = null;
		var tabla = $('#tabla').DataTable({
	        language: {
	            url: $endpoint_cdn + '/datatables/1.10.12/Spanish_sym.json',
	            decimal: ',',
	            thousands: '.',
	            infoEmpty: 'No hay datos de Novedades...'
	        },
	        processing: true,
	        serverSide: true,
	        //responsive: true,
	        searchDelay: 1200,

	        ajax: {
	            url: $base_url + '/Novedades/ajax_ultimas_siete',
	            contentType: "application/json",
	            data: function (d) {
                filtros_dataTable = $.extend({}, d, {
             		dni			: $('#dni').val(),
             		dni_hidden 	: $('#dni_agente').val(),
                });
                return filtros_dataTable; 
                } 	
	        },
	        info: true, 
	        bFilter: true,
	        columnDefs: [
	        	{ targets: 0, width: '10%'}, 
		        { targets: 1, width: '10%'}, 
		        { targets: 2, width: '10%'}, 
	
	        ],
	        order: [[0,'desc']],
	        columns: [
	      
	            {
	                title: 'Fecha Desde',
	                name:  'fecha_desde',
	                data:  'fecha_desde',
	                className: 'text-left',
	                  render: function (data, type, row) {
						if(data == null){
						}else{
							rta = moment(data,'DD/MM/YYYY').format('DD/MM/YYYY'); 
						} 	
						return rta;
					}
	            },
	            {
	                title: 'Fecha Hasta',
	                name:  'fecha_hasta',
	                data:  'fecha_hasta',
	                className: 'text-left',
	                  render: function (data, type, row) {
						if(data == null){
						}else{
							rta = moment(data,'DD/MM/YYYY').format('DD/MM/YYYY'); 
						} 	
						return rta;
					}
	            }, 
	            {
	                title: 'Tipo Novedad',
	                name:  'tipo_novedad',
	                data:  'tipo_novedad',
	                className: 'text-left'
	            },
	        ]
	    });	
		

		//filtros para el exportador ultimas siete
	   $(".accion_exportador").click(function () {
	    var form = $('<form/>', {id:'form_ln' , action : $(this).val(), method : 'POST'});
	    $(this).append(form);
	    form.append($('<input/>', {name: 'search', type: 'hidden', value: $('div.dataTables_filter input').val() }))
	        .append($('<input/>', {name: 'campo_sort', type: 'hidden', value: $('#tabla').dataTable().fnSettings().aoColumns[$('#tabla').dataTable().fnSettings().aaSorting[0][0]].name }))
	        .append($('<input/>', {name: 'dir', type: 'hidden', value: $('#tabla').dataTable().fnSettings().aaSorting[0][1] }))
	        .append($('<input/>', {name: 'rows', type: 'hidden', value: $('#tabla').dataTable().fnSettings().fnRecordsDisplay() }))
	        .append($('<input/>', {name: 'dni', type: 'hidden', value:$('#dni').val() }))
	        .append($('<input/>', {name: 'dni_hidden', type: 'hidden', value:$('#dni_agente').val() }));
	     form.submit();
		});
	 
	    /** Consulta al servidor los datos y redibuja la tabla
	     * @return {Void}
	    */
	    function update() {
	        tabla.draw();
	    }

		$('#dni').keyup(function () { update(); });		

	}//fin listado ultimas siete novedades


	//para el alta/modificacion
	if($('#dni').length){

		if($('#dni').val().length){
			$('#dni').typeahead({
				onSelect: function (item) {
	
				},
				ajax: {
					url: $base_url+"/novedades/buscarEmpleado",
					displayField: 'full_name',
					valueField: 'dep',
					triggerLength: 8, //ej: 32318670
					method: "post",
					loadingClass: "loading-circle",
					preDispatch: function (query) {
						return {
							dni: query,
						}
					},
					preProcess: function (data) {
						if(data.data[0].error != undefined){
							$('#alerta').css("display","block");		
							$("#alerta").text(data.data[0].error); 
							$('#alerta').fadeOut(5000);
							$("#nombre").text("Datos no encontrados"); 
							$("#boton_novedades").attr('disabled','disabled');
						}
						if(data.data[0].nombre != undefined){
							$("#nombre").text(data.data[0].nombre); 
						}	
					}
				}
			});
			
			//lo del id mostrar se comenta por el momento y que fecha hasta será obligatorio
			if($("#tipo_novedad").val() == $comision_horaria){ //si es comisión horaria tiene el maxdate
				$("#fila_horario").css("visibility", "visible");	
				$('#fecha_desde').datetimepicker({
					format: 'DD/MM/YYYY',
					defaultDate: moment().subtract(1, 'days'),
					maxDate: moment().subtract(1, 'days')
				});
	
				$('#fecha_hasta').datetimepicker({
					format: 'DD/MM/YYYY',
					defaultDate: moment().subtract(1, 'days'),
					maxDate: moment().subtract(1, 'days'),
					minDate: moment($('#fecha_desde').val(),"DD/MM/YYYY").get()
				});
			}else{
				  $("#fila_horario").css("visibility", "hidden");
				$('#fecha_desde').datetimepicker({
					format: 'DD/MM/YYYY',
				});
	
				$('#fecha_hasta').datetimepicker({
					format: 'DD/MM/YYYY',
					defaultDate: moment().subtract(1, 'days'),
					minDate: moment($('#fecha_desde').val(),"DD/MM/YYYY").get()
				}); 
			}
	
			$("#fecha_desde").on("dp.change", function (e) {
				$('#fecha_hasta').data("DateTimePicker").minDate(e.date);
			});
		
			$("#fecha_hasta").on("dp.change", function (e) {
				$('#fecha_desde').data("DateTimePicker").maxDate(e.date);
			}); 
	
			//cuando borren el dni, se refrescan los campos
			$('#dni').keyup(function() { 
				if(this.value == ''){
					$("#boton_novedades").removeAttr('disabled');
	
					$.ajax({
						url: $base_url+"/novedades/actualizarTipoNovedad",
						data: {
						
						},
						method: "POST"
					})
					.done(function (data) {
	
						if(typeof data.data != 'undefined'){
							addOptionsMulti(data.data, 'select#tipo_novedad',data.data.nombre);
						}
	
					})
					.fail(function(data){
						addOptionsMulti([], 'select#tipo_novedad');
					});
	
						$("#fecha_desde").val('');
						$("#fecha_hasta").val('');
						$("#nombre").text(''); 
								
				}	
			});  
	
		}else{
	
			$('#fecha_desde').datetimepicker({
				format: 'DD/MM/YYYY',
				defaultDate: moment(),
			});
		
			$('#fecha_hasta').datetimepicker({
				format: 'DD/MM/YYYY',
				defaultDate: moment(),
			});
		
			$("#fecha_desde").on("dp.change", function (e) {
				$('#fecha_hasta').data("DateTimePicker").minDate(e.date);
			});
		
			$("#fecha_hasta").on("dp.change", function (e) {
				$('#fecha_desde').data("DateTimePicker").maxDate(e.date);
			}); 
	
		}
	
		$("#tipo_novedad").on('change',function(){
			if($(this).val() == $comision_horaria){
				  $("#fila_horario").css("visibility", "visible");
			
				$('#fecha_desde').datetimepicker({
					format: 'DD/MM/YYYY',
					defaultDate: moment().subtract(1, 'days'),
					maxDate: moment().subtract(1, 'days')
			
				});
	
				$('#fecha_hasta').datetimepicker({
					format: 'DD/MM/YYYY',
					defaultDate: moment().subtract(1, 'days'),
					maxDate: moment().subtract(1, 'days')
				});
			}else{
				$("#fila_horario").css("visibility", "hidden"); 
				$('#fecha_desde').datetimepicker({
					format: 'DD/MM/YYYY',
					defaultDate: moment()
					
				});
				$('#fecha_hasta').datetimepicker({
					format: 'DD/MM/YYYY',
					defaultDate: moment()
				});
			   }
		});

	}


});	    