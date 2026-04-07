<?php
    // Verificar si el usuario tiene permiso o si la sesión es válida
    session_start();
    //var_dump($_SESSION); // Esto te mostrará qué hay en la sesión
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['login']) || $_SESSION['user']['login'] !== true || empty($_SESSION['user'])) {
        die("Acceso denegado.");
    }
    $key_section = isset($_GET['section']) ? $_GET['section'] : '';
    $file_name_material = isset($_GET['file']) ? $_GET['file'] : '';
    // RUTA PARA ALMACENAR EN UNA UNIDAD EXTERNA
    // Sustituir la letra E
    //$ruta = 'E:/uploads/material/'.$key_section.'/'.$file_name_material;

    // RUTA PARA ALMACENAR LOS DOCUMENTOS EN LA CARPETA INTERNA DEL SERVIDOR
    $ruta = '../../../uploads/material/'.$key_section.'/'.$file_name_material;
    
    // Verifica si el archivo existe
    if (file_exists($ruta)) {
        // Configura las cabeceras para la descarga
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($ruta).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($ruta));
        // Leer el archivo y enviar al navegador para descargar
        readfile($ruta);
        exit;
    } else {
        echo "No se encontró el archivo.";
    }
?>