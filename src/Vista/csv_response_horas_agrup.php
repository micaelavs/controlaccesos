<?php

/** @var array $data_agrup */
/** @var string $nombre */
/** @var array $titulos_agrup */
/** @var null | string $separador */

header('Content-Encoding: UTF-8');
header("Content-type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=" . $nombre . "_agrup.csv");
header("Pragma: no-cache");
header("Expires: 0");

function sumatoria_horas_trabajadas($total_sec, $total_min, $total_hora)
{

   $sumSec = $total_sec % 60;
   $extra_min = ($total_sec - $sumSec) / 60;
   $total_min = $total_min + $extra_min;
   $sumMin = $total_min % 60;
   $extra_hr = ($total_min - $sumMin) / 60;
   $total_hora = $total_hora + $extra_hr;
   $hora_sumatoria = sprintf('%02d', $total_hora) . ':' . sprintf('%02d', $sumMin) . ':' . sprintf('%02d', $sumSec);

   return $hora_sumatoria;
}

$doc_fila = '';
$dep_fila_agrup = '';

$contador_agrup = count($data_agrup);
$indice = 0;
$indice_divisor = 0;
$promedio = 0;
$sumatoria = 0;

$total_sec = 0;
$total_min = 0;
$total_hora = 0;

$separador = (isset($separador) && !is_null($separador)) ? $separador : ';';
$csv  = "\xEF\xBB\xBF"; //Byte Order Mark (BOM)

foreach ($data_agrup as $row) {
   if ($dep_fila_agrup != $row['codep']) {
      if ($dep_fila_agrup != '') {
         $hora_sumatoria = sumatoria_horas_trabajadas($total_sec, $total_min, $total_hora);
         $sumatoria_fila = [$doc_fila, $cuit_fila, $nombre_fila, $apellido_fila, '', '', '', $hora_sumatoria, gmdate("H:i:s", ($sumatoria / $indice_divisor))];
         $csv .= implode($separador, $sumatoria_fila);
         $csv .= "\r\n";
         $csv .= "\r\n";
      }
      $filtros = ['FECHA DESDE', 'FECHA HASTA', 'DEPENDENCIA'];
      $csv .= implode($separador, $filtros);
      $csv .= "\r\n";
      $filtros_valor = [$fecha_desde, $fecha_hasta, $row['codep']];
      $csv .= implode($separador, $filtros_valor);
      $csv .= "\r\n";
      $csv .= implode($separador, $titulos_agrup);
      $csv .= "\r\n";
   }

   if ($doc_fila == '') {
      $csv .= implode($separador, $row);
      $csv .= "\r\n";
      $doc_fila = $row['documento'];
      $cuit_fila = $row['cuit'];
      $nombre_fila = $row['nombre'];
      $apellido_fila = $row['apellido'];
   } else {
      if ($doc_fila != $row['documento']) {
         if ($dep_fila_agrup == $row['codep']) {
            $hora_sumatoria = sumatoria_horas_trabajadas($total_sec, $total_min, $total_hora);
            $sumatoria_fila = [$doc_fila, $cuit_fila, $nombre_fila, $apellido_fila, '', '', '', $hora_sumatoria, gmdate("H:i:s", ($sumatoria / $indice_divisor))];
            $csv .= implode($separador, $sumatoria_fila);
            $csv .= "\r\n";
         }

         $sumatoria = 0;
         $indice_divisor = 0;

         $total_sec = 0;
         $sumSec = 0;
         $extra_min = 0;
         $total_min = 0;
         $sumMin = 0;
         $extra_hr = 0;
         $total_hora = 0;

         $csv .= implode($separador, $row);
         $csv .= "\r\n";
         $doc_fila = $row['documento'];
         $cuit_fila = $row['cuit'];
         $nombre_fila = $row['nombre'];
         $apellido_fila = $row['apellido'];
      } else {
         $csv .= implode($separador, $row);
         $csv .= "\r\n";
      }
   }

   $dep_fila_agrup = $row['codep'];
   $indice++;
   $indice_divisor++;

   $parts = explode(":", $row['horas_trabajadas']);
   $sumatoria += $parts[2] + $parts[1] * 60 + $parts[0] * 3600;


   $total_sec += $parts[2];
   $total_min += $parts[1];
   $total_hora += $parts[0];

   if ($indice == $contador_agrup) {
      $hora_sumatoria = sumatoria_horas_trabajadas($total_sec, $total_min, $total_hora);
      $sumatoria_fila = [$doc_fila, $cuit_fila, $nombre_fila, $apellido_fila, '', '', '', $hora_sumatoria, gmdate("H:i:s", ($sumatoria / $indice_divisor))];
      $csv .= implode($separador, $sumatoria_fila);
      $csv .= "\r\n";
      $csv .= "\r\n";
   }
}

foreach ($data as $row) {
   if ($dep_fila_agrup != $row['codep']) {
      $csv .= "\r\n";
      $filtros = ['FECHA DESDE', 'FECHA HASTA', 'DEPENDENCIA'];
      $csv .= implode($separador, $filtros);
      $csv .= "\r\n";
      $filtros_valor = [$fecha_desde, $fecha_hasta, $row['codep']];
      $csv .= implode($separador, $filtros_valor);
      $csv .= "\r\n";
      $csv .= implode($separador, $titulos);
      $csv .= "\r\n";
   }

   if ($doc_fila == '') {
      $csv .= implode($separador, $row);
      $csv .= "\r\n";
      $doc_fila = $row['documento'];
      $cuit_fila = $row['cuit'];
      $nombre_fila = $row['nombre'];
      $apellido_fila = $row['apellido'];
   } else {
      if ($doc_fila != $row['documento']) {
         $csv .= implode($separador, $row);
         $csv .= "\r\n";
         $doc_fila = $row['documento'];
         $cuit_fila = $row['cuit'];
         $nombre_fila = $row['nombre'];
         $apellido_fila = $row['apellido'];
      } else {
         $csv .= implode($separador, $row);
         $csv .= "\r\n";
      }
   }

   $dep_fila_agrup = $row['codep'];
}

$csv .= "\r\n";
echo $csv;
exit;
