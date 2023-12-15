<?php
/** @var string $archivo = ruta al archivo */
/** @var string $nombre = nombre con el que se descargara el archivo*/ 
   
           header("Content-Disposition:inline;filename=".$nombre.".pdf");
           header("Content-type: application/pdf;");
           header('Content-Length: ' . filesize($archivo));
           readfile($data);
        exit;
