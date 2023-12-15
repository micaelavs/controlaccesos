<?php
/**
 * @var array $listado_planilla_reloj
 * @var string $nombres
 * @var array $adjuntar_novedades
 * @var string $fecha
 */

use Dompdf\Dompdf;
use Dompdf\Options;
use \FMT\Helper\Arr;

$css_pdf = \App\Helper\Vista::get_url() . '/css/accesos/estilo_pdf.css';
$logo = \App\Helper\Vista::get_url("img") . '/logo_ministerio_grande_blanco.png';

$content = '<!DOCTYPE html>';
$content .= '<html lang="en">';
$content .= '<head>';
$content .= '<link rel="stylesheet" href="' . $css_pdf . '">';
$content .= '<meta charset="utf-8">';
$content .= '</head><body>';

// $contratos =[1=>'Otras modalidades',2 =>'LM', 3=>'PP'];
$contratos= [1=>['nombre' => 'Otras modalidades'],2=>['nombre' => 'LM']];
$fecha = strtotime($fecha);
/** @var int - Cantidad de filas por pagina*/
$_CANT_FILAS	= 28;

foreach ($listado_planilla_reloj as $key => $lista_contrato) {
	
	$fila = 1;
	$hoja = 1;
	$tabla_abierta = false;
	foreach ($lista_contrato as $value) {
		if($fila ==1) {
			$tabla_abierta = true;
			$content .= '<div>';
			$content .= '<table> 
						    <tr>
						    	<td colspan="7" style="padding:0">
								<table>
									<tr><th style="text-align: left;">PLANILLA N° '.date('Y-z',$fecha).'/'.$hoja.'</th></tr>
									<tr><td class="cell"><strong>Año:</strong> '.date('Y',$fecha).'</td></tr>
									<tr><td class="cell"><strong>Mes:</strong> '.date('m',$fecha).'</td></tr>
									<tr><td class="cell"><strong>Día:</strong> '.date('d',$fecha).'</td></tr>
									<tr><td class="cell"><strong>Tipo de Contrato :</strong> '.Arr::path($contratos, "{$key}.nombre").'</td></tr>
								</table>
						    	</td>
								<td colspan="3" style="text-align:right;"><img alt="" src="'.$logo.'" height="60"></td>
						    </tr>
						</table>';

			$content .= '<h2 class="center title">PLANILLA ÚNICA RELOJ</h2>';
			$content .= '<table>';

			$content .='<tr>
							<th></th>
							<th>APELLIDO Y NOMBRE</th>
							<th>CUIL</th>
							<th>HORA INGRESO</th>
							<th>HORA EGRESO</th>
						</tr>';
		}

		$content .='<tr>
						<td class="cell">'.$fila.'</td>
						<td class="cell">'.$value['nombre_apellido'].'</td>
						<td class="cen">'.$value['cuit'].'</td>
						<td class="cen">'.$value['hora_ingreso'].'</td>
						<td class="cen">'.$value['hora_egreso'].'</td>
					</tr>';
		if($fila == $_CANT_FILAS) {
			$tabla_abierta = false;
			$content .= '</table>';
			$content .= '<div style="height:15px;"></div>';
			$content .= '<table>
						<tr>
							<th class="cell">Área</th>
							<th class="cell">Responsable</th>
							<th class="cell">Autorizante</th>
							<th class="cell">Firma - Cierre de Planilla</th>
						</tr>
						<tr>
							<td class="footer_size" >'.$nombres.'</td>
							<td style="padding: 12px;"></td>
							<td style="padding: 12px;"></td>
							<td style="padding: 12px;"></td>
						</tr>
					</table>';

			$content .= '</div>';
			$hoja++;
			$fila =1;
		} else {
			$fila++;			
		}	

	}
	if($tabla_abierta) {
		for ($i = $fila; $i <= $_CANT_FILAS; $i++) {
			$content .= '<tr>
						<td class="cen"> _ </td>
						<td class="cen"> _ </td>
						<td class="cen"> _ </td>
						<td class="cen"> _ </td>
						<td class="cen"> _ </td>
					</tr>';
		}
		$content .= '</table>';
		$content .= '<div style="height:15px;"></div>';
		$content .= '<table>
				<tr>
					<th class="cell">Área</th>
					<th class="cell">Responsable</th>
					<th class="cell">Autorizante</th>
					<th class="cell">Firma - Cierre de Planilla</th>
				</tr>
				<tr>
					<td class="footer_size">' . $nombres . '</td>
					<td style="padding: 12px;"></td>
					<td style="padding: 12px;"></td>
					<td style="padding: 12px;"></td>
				</tr>
			</table>';

		$content .= '</div>';
	}
}

	$fila = 1;
	$hoja++;

if (!empty($adjuntar_novedades)) {
	$tabla_abierta = false;
	foreach ($adjuntar_novedades as $value) {
		if($fila == 1) {
			$tabla_abierta = true;
			$content .= '<div>';
			$content .= '<table> 
						    <tr>
						    	<td colspan="7" style="padding: 0">
								<table>
									<tr><th style="text-align: left;">PLANILLA N° '.date('Y-z',$fecha).'/'.$hoja.'</th></tr>
									<tr><td class="cell"><strong>Año:</strong> '.date('Y',$fecha).'</td></tr>
									<tr><td class="cell"><strong>Mes:</strong> '.date('m',$fecha).'</td></tr>
									<tr><td class="cell"><strong>Día:</strong> '.date('d',$fecha).'</td></tr>
									<tr><td class="cell"><strong>Tipo de Contrato :</strong> </td></tr>
								</table>
						    	</td>
								<td colspan="3" style="text-align:right;"><img alt="" src="'.$logo.'" height="60"></td>
						    </tr>
						</table>';
// '.Arr::path($contratos, "{$key}.nombre").' PARA MOSTRAR EL TIPO DE CONTRATO
			$content .= '<h2 class="center title">PLANILLA DE NOVEDADES</h2>';
			$content .= '<table>';

			$content .='<tr>
							<th></th>
							<th>APELLIDO Y NOMBRE</th>
							<th>CUIL</th>
							<th>NOVEDAD</th>
						</tr>';
		}

		$content .='<tr>
						<td class="cell">'.$fila.'</td>
						<td class="cell">'.$value['nombre_apellido'].'</td>
						<td class="cen">'.$value['cuit'].'</td>
						<td class="cen">'.$value['novedad'].'</td>
					</tr>';
		if($fila == $_CANT_FILAS) {
			$tabla_abierta = false;
			$content .= '</table>';
			$content .= '<div style="height:15px;"></div>';
			$content .= '<table>
						<tr>
							<th class="cell">Área</th>
							<th class="cell">Responsable</th>
							<th class="cell">Autorizante</th>
							<th class="cell">Firma - Cierre de Planilla</th>
						</tr>
						<tr>
							<td class="footer_size" >'.$nombres.'</td>
							<td style="padding: 12px;"></td>
							<td style="padding: 12px;"></td>
							<td style="padding: 12px;"></td>
						</tr>
					</table>';

			$content .= '</div>';
			$hoja++;
			$fila =1;
		} else {
			$fila++;			
		}	

	}

	if($tabla_abierta) {
		for ($i = $fila; $i <= $_CANT_FILAS; $i++) {
			$content .= '<tr>
						<td class="cen"> _ </td>
						<td class="cen"> _ </td>
						<td class="cen"> _ </td>
						<td class="cen"> _ </td>
					</tr>';
		}

		$content .= '</table>';
		$content .= '<div style="height:15px;"></div>';
		$content .= '<table>
				<tr>
					<th class="cell">Área</th>
					<th class="cell">Responsable</th>
					<th class="cell">Autorizante</th>
					<th class="cell">Firma - Cierre de Planilla</th>
				</tr>
				<tr>
					<td class="footer_size">' . $nombres . '</td>
					<td style="padding: 12px;"></td>
					<td style="padding: 12px;"></td>
					<td style="padding: 12px;"></td>
				</tr>
			</table>';

		$content .= '</div>';
	}
}

$content .= '</body></html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($content);
$dompdf->setPaper('A4');
$dompdf->render();
$dompdf->stream($file_nombre);
exit;
