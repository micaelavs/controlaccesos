<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use FMT\Informacion_fecha;
use App\Modelo\AccesoEmpleado;
use \FMT\Helper\Arr;
ini_set('max_execution_time', 1800);
ini_set('memory_limit','4096M');      

$content = '<!DOCTYPE html>
			<html lang="en">
			<head>
			<link rel="stylesheet" href="././css/estilo_pdf.css">
			<meta charset="utf-8">';

$content .= '<style>
				table {font-family: "Roboto","Helvetica Neue","Helvetica","Arial",sans-serif;border-collapse: collapse;width: 100%;font-size:14px; }
				td, th {border: 2px solid #dddddd;padding: 5px;}
				tr:nth-child(even){box-sizing: border-box;}
				.box {float: left; width: 50%;padding: 50px;height: 150px;}
				.img-container {float: left;width: 33.33%;padding: 5px;}
				.clearfix::after {content: "";clear: both;display: table;}
				.center{text-align:center}
				.title{font-family: "Roboto","Helvetica Neue","Helvetica","Arial",sans-serif;}
				.cell{padding: 2px;}
				.cen{padding: 2px;text-align:center;}
				.footer_size{font-size: 12px;   }
				.page-break{page-break-after: always;}
				.footer-position {position: fixed; bottom: 2cm; left: 0cm; right: 0cm;}
				table {font-size:10px;}
			</style>';
$content .= '</head><body>';


$filas_restantes = '<tr>
						<td class="cen"> _ </td>
						<td class="cen"> _ </td>
						<td class="cen"> _ </td>
						<td class="cen"> _ </td>
						<td class="cen"> _ </td>	
					</tr>';

$tabla_responsable ='<table class="footer-position"> 
						<tr>
							<th class="cell">Área</th>
							<th class="cell">Responsable</th>
							<th class="cell">Autorizante</th>
							<th class="cell">Firma - Cierre de Planilla</th>
						</tr>
						<tr>
							<td class="footer_size" >'.$nombre.'</td>
							<td style="padding: 12px;"></td>
							<td style="padding: 12px;"></td>
							<td style="padding: 12px;"></td>
						</tr>
					</table>';
 if(!empty($listado_informe_mensual)){
foreach ($listado_informe_mensual as $key => $lista_contrato) {
	
	$fila = 1;
	$coincide = "";
	$horas = "";
	$fecha_reg = "";

	$novedad = "";
	$novedad_id = "";
	$novedad_2 = "";

	foreach ($lista_contrato as $value) 
	{
		if(!empty($coincide) && $coincide != $value['empleado_id']){
			$content .= '<tr>
							<td colspan="3" style="padding: 2px;text-align:right;"> Total horas trabajadas:</td>
							<td class="cen">'.$total_horas .'</td>
							<td></td>
						</tr>
						</table>
						<div style="height:10px;"></div>';
			$content .= $tabla_responsable;
			$content .= '</div>';
			$content .= '<div class="page-break"></div>';
			$fila =1;
		}

		if($fila ==1) {
			$coincide = $value['empleado_id'];
			$fecha_reg = date_format(date_create($value['fecha']), "Y-m-d");
			$horas = "";

			$content .= '<div>
						<table> 
						    <tr>
						    	<td colspan="7" style="padding: 0px;">
								<table>
									<tr><td class="cell"><strong>Año:</strong> '.$anio.'</td></tr>
									<tr><td class="cell"><strong>Mes:</strong> '.strtoupper($mes).'</td></tr>
									<tr><td class="cell"><strong>Empleado:</strong> '.$value['nombre_apellido'].'</td></tr>
									<tr><td class="cell"><strong>Tipo de Contrato :</strong> '.Arr::path($contratosArray, "{$key}.nombre").'</td></tr>
								</table>
						    	</td>
								<td colspan="3" style="text-align:right;"><img src="'.BASE_PATH.'/img/logo_ministerio_grande_blanco.png"/ height="60"></td>
						    </tr>
						</table>

						<h5 class="center title">INFORME MENSUAL</h5>
						<table>';

			$content .='<tr>
							<th width="15%">FECHA</th>
							<th width="15%">HORA INGRESO</th>
							<th width="15%">HORA EGRESO</th>
							<th width="15%">HORAS TRABAJADAS</th>
							<th>NOVEDAD</th>
						</tr>';
		}

		$horas_trabajadas = ( !is_null($value['cant_horas'])  ? date_format(date_create($value['cant_horas']), "H:i") : "0");
		$horas_trab = (!empty($horas_trabajadas) ? $horas_trabajadas : '0');
		$hora_egreso = (!is_null($value['hora_egreso']) ? date_format(date_create($value['hora_egreso']), "H:i") : "__");

		//Se evalúa cada fecha para obtener la novedad (se toma en cuenta, si tiene una segunda novedad)
		foreach ($novedades as $data) {
			if($value['empleado_id'] == $data['empleado_id']){
				if($novedad_2 == ""){
					if ( empty($novedad_id) && $novedad_id != $data['novedad_id'] ) {
						
						if( (date_format(date_create($data['fecha_desde']), "Y-m-d") <= $value['fecha']) && (date_format(date_create($data['fecha_hasta']), "Y-m-d") >= $value['fecha'])  ){
							$novedad_2 = $data['novedad'];
						}
						
					}
				}else{
					if( date_format(date_create($data['fecha_desde']), "Y-m-d") <= $value['fecha'] && date_format(date_create($data['fecha_hasta']), "Y-m-d") >= $value['fecha']){
						$novedad = $data['novedad'];
						$novedad_id = $data['novedad_id'];
					}
					
				}
			}	
		}
		//Diferencia de dias entre la fecha del registro de la actual iteración y la fecha del registro de la anterior iteración
		$date1 = date_create($fecha_reg);
		$date2 = date_create(date_format(date_create($value['fecha']), "Y-m-d"));
		$diff  = date_diff($date1, $date2);
		//Con la diferencia, se indica en el informe los dias que faltó el empleado (tambien se incluyen los fines de semana)
		if($diff->days > 1){
			for ($i=1; $i < $diff->days; $i++) { 
				$fecha_suma = new DateTime($fecha_reg);
				$fecha_suma->add(new DateInterval('P'.$i.'D'));

				$habil = Informacion_fecha::es_habil( new DateTime($fecha_suma->format('Y-m-d')) );
				//Se evalúa cada fecha para obtener la novedad (se toma en cuenta, si tiene una segunda novedad)
				$data_novedad = "";
				$novedad_id = "";
				$data_novedad_2 = "";
				foreach ($novedades as $data) {
					if($value['empleado_id'] == $data['empleado_id']){
						if($data_novedad_2 == ""){
							if ( empty($novedad_id) && $novedad_id != $data['novedad_id'] ) {
								
								if(  date_format(date_create($data['fecha_desde']), "Y-m-d") <= $fecha_suma->format('Y-m-d') && $data['fecha_hasta'] >= $fecha_suma->format('Y-m-d')){
									$data_novedad_2 = $data['novedad'];}
							}
						}else{
							if(date_format(date_create($data['fecha_desde']), "Y-m-d") <= $fecha_suma->format('Y-m-d') && $data['fecha_hasta'] >= $fecha_suma->format('Y-m-d')){
								$data_novedad = $data['novedad'];
								$novedad_id = $data['novedad_id'];
							}
						}
					}
				}

				if($habil){
					$content .='<tr>
									<td class="cen">'.$fecha_suma->format('d/m/Y').'</td>
									<td class="cen">__</td>
									<td class="cen">__</td>
									<td class="cen">0</td>
									<td class="cen">'.$data_novedad.'<br>'.$data_novedad_2.'</td>
								</tr>';
				}
			}
		}



		$content .='<tr>
						<td class="cen">'.date_format(date_create($value['fecha']), "d/m/Y").'</td>
						<td class="cen">'.date_format(date_create($value['hora_ingreso']), "H:i").'</td>
						<td class="cen">'.$hora_egreso.'</td>
						<td class="cen">'.$horas_trab.'</td>
						<td class="cen">'.$novedad.'<br>'.$novedad_2.'</td>
					</tr>';

		//Si faltó el ultimo día, se agrega en el informe dicho día y se busca la novedad
		$ultimo_dia = date_create($fecha['fecha_hasta']);		
		$diff_ultimo_dia = date_diff($date2, $ultimo_dia);

		if($diff_ultimo_dia->days == 1 && ($fecha['fecha_hasta'] != date_format(date_create($value['fecha']), "Y-m-d"))){

			$asistencia = AccesoEmpleado::verificar_asistencia($fecha['fecha_hasta'], $value['empleado_id']);
			if(!$asistencia){

				foreach ($novedades as $data) {
					if($value['empleado_id'] == $data['empleado_id']){
						if($novedad_2 == ""){
							if ( empty($novedad_id) && $novedad_id != $data['novedad_id'] ) {
								
								if( $fecha['fecha_hasta'] == $data['fecha_desde'] ){
									$novedad_2 = $data['novedad'];
								}
								
							}
						}else{
							if( $fecha['fecha_hasta'] == $data['fecha_desde'] ){
								$novedad = $data['novedad'];
								$novedad_id = $data['novedad_id'];
							}
							
						}
					}	
				}
			
				$content .='<tr>
								<td class="cen">'.date_format(date_create($fecha['fecha_hasta']), "d/m/Y").'</td>
								<td class="cen">__</td>
								<td class="cen">__</td>
								<td class="cen">0</td>
								<td class="cen">'.$novedad.'<br>'.$novedad_2.'</td>
							</tr>';
			}
		}
		//Fin funcionalidad ultimo día

		$horas[] = (!empty($horas_trabajadas) ?  $horas_trabajadas : "00:00");
		$total_horas = sum_horas($horas);
		

		$fila++;
		$fecha_reg = date_format(date_create($value['fecha']), "Y-m-d");

		$novedad = "";
		$novedad_id = "";
		$novedad_2 = "";

	}

	$total_horas = sum_horas($horas);

	$content .= '<tr>
					<td colspan="3" style="padding: 2px;text-align:right;"> Total horas trabajadas:</td>
					<td class="cen">'.$total_horas .'</td>
					<td></td>
				</tr>
				</table>
				<div style="height:10px;"></div>';
	$content .= $tabla_responsable;
	$content .= '</div>';
	$content .= '<div class="page-break"></div>';
}
}else{
	$content .= '<div>
							<table> 
							    <tr>
							    	<td colspan="7" style="padding: 0px;">
									<table>
										<tr><td class="cell"><strong>Año:</strong> &nbsp; </td></tr>
										<tr><td class="cell"><strong>Mes:</strong> &nbsp; </td></tr>
										<tr><td class="cell"><strong>Empleado:</strong> &nbsp; </td></tr>
										<tr><td class="cell"><strong>Tipo de Contrato :</strong> &nbsp; </td></tr>
									</table>
							    	</td>
									<td colspan="3" style="text-align:right;"><img src="'.BASE_PATH.'/img/logo_ministerio_grande_blanco.png"/ height="60"></td>
							    </tr>
							</table>

							<h5 class="center title">INFORME MENSUAL</h5>';
	$content .= '</div><h5 class="center title">No se encuentran datos en esta dependencia para el mes solicitado</h5></div>';
	$content .= '<div>';	
	$content .= $tabla_responsable;
	$content .= '</div>';
}
$content .= '</body></html>';

function sum_horas($horas) {
    $i = 0;
    foreach ($horas as $time) {
        sscanf($time, '%d:%d', $hour, $min);
        $i += $hour * 60 + $min;
    }
    if ($h = floor($i / 60)) {
        $i %= 60;
    }
    return sprintf('%02d:%02d', $h, $i);
}

//print_r($content);die();
$dir_tmp	= \App\Helper\Documento::getDirectorio('tmp/', true);
$options = (new Options())
	->setTempDir($dir_tmp)
	->setIsRemoteEnabled(true);
	//->setChroot(getcwd()); // Segun la documentacion este metodo es altamente peligroso, por no decir inecesario.
$dompdf = new Dompdf( $options );
$dompdf->setBasePath(BASE_PATH);
$dompdf->loadHtml($content);
$dompdf->setPaper('A4'); // (Opcional) Configurar papel y orientación
$dompdf->render(); // Generar el PDF desde contenido HTML
$file_name = "Informe-mensual-".date('d-m-Y')."";

if(!empty($system_set_filename)) {
	file_put_contents($dir_tmp.$system_set_filename.'.pdf', $dompdf->output(), LOCK_EX);
	return;
}

$dompdf->output(); // Obtener el PDF generado
$dompdf->stream($file_name, array("Attachment"=>0));