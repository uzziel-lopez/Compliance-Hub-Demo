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
    // Construye la ruta del archivo en la unidad E:
    //$ruta = 'E:/uploads/material/'.$key_section.'/'.$file_name_material;

    // RUTA PARA ALMACENAR LOS DOCUMENTOS EN LA CARPETA INTERNA DEL SERVIDOR
    $ruta = '../../../uploads/material/'.$key_section.'/'.$file_name_material;
    
    // Verifica si el archivo existe
    if (file_exists($ruta)) {
        // Obtener la extensión del archivo
        $file_extension = pathinfo($ruta, PATHINFO_EXTENSION);
        // Configurar encabezados según el tipo de archivo
        switch (strtolower($file_extension)) {
            case 'pdf':
                header('Content-Type: application/pdf');
            break;
            case 'doc':
            case 'docx':
                header('Content-Type: application/msword');
            break;
            default:
                header('Content-Type: application/octet-stream');
            break;
        }
        header('Content-Disposition: inline; filename="'.basename($ruta).'"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    } else {
        echo "No se encontró el archivo.";
    }
?>