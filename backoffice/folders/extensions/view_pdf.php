<?php
    // Verificar si el usuario tiene permiso o si la sesión es válida
    session_start();
    //var_dump($_SESSION); // Esto te mostrará qué hay en la sesión
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['login']) || $_SESSION['user']['login'] !== true || empty($_SESSION['user'])) {
        die("Acceso denegado.");
    }
    $key_folder = isset($_GET['folder']) ? $_GET['folder'] : '';
    $file_name_document = isset($_GET['file']) ? $_GET['file'] : '';
    
    // RUTA PARA ALMACENAR EN UNA UNIDAD EXTERNA
    // Sustituir la letra E
    // Construye la ruta del archivo en la unidad E:
    // $ruta = 'E:/uploads/documents/'.$key_folder.'/'.$file_name_document;

    // RUTA PARA ALMACENAR LOS DOCUMENTOS EN LA CARPETA INTERNA DEL SERVIDOR
    $ruta = '../../../uploads/documents/'.$key_folder.'/'.$file_name_document;
    
    // Verifica si el archivo existe
    if (file_exists($ruta)) {
        $file_extension = pathinfo($ruta, PATHINFO_EXTENSION);
        if ($file_extension === 'pdf' || $file_extension === 'PDF') {
            // Si es un PDF, mostrar directamente
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="'.basename($ruta).'"');
            header('Content-Length: ' . filesize($ruta));
            readfile($ruta);
            exit;
        } else {
            // Si no es un PDF, mostrar la leyenda
            echo "
                <div style='
                    color: #000000;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    font-family: Arial, sans-serif;
                    max-width: 600px;
                    margin: 20px auto;
                '>
                    <h4 style='margin-bottom: 10px; font-size: 14px;'>No es posible mostrar una vista previa de este archivo.</h4>
                    <p style='font-size: 13px; text-align: justify;'>
                        Se recomienda descargarlo para verlo correctamente.
                    </p>
                </div>
            ";
            exit;
        }
    } else {
        echo "No se encontró el archivo.";
    }
?>