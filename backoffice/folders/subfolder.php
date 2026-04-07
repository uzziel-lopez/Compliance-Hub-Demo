<?php
session_start();
include "../../app/config.php";
//include "../../app/debug.php";
include "../../app/WebController.php";
$controller = new WebController();

// FUNCIÓN DE DEBUG
function debugLog($step, $data = null)
{
    error_log("DEBUG STEP $step: " . print_r($data, true));
    echo "<script>console.log('DEBUG STEP $step:', " . json_encode($data) . ");</script>";
}

// DEBUGGING INICIAL
if (!empty($_POST)) {
    debugLog('POST_RECEIVED', [
        'action' => $_POST['action'] ?? 'NO_ACTION',
        'updateFolder_isset' => isset($_POST['updateFolder']),
        'updateFolder_keys' => isset($_POST['updateFolder']) ? array_keys($_POST['updateFolder']) : [],
        'post_keys' => array_keys($_POST)
    ]);
}

// Verificar si la sesión del usuario está activa
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    // Si no hay sesión activa, destruir la sesión
    session_destroy();
    // Redirigir a la página de inicio de sesión
    header("Location: ../../login.php");
    exit(); // Es importante salir después de redirigir para evitar que el código siguiente se ejecute innecesariamente
}

//FUNCIÓN PARA MOSTRAR LOS DETALLES DE UNA CARPETA
$folder = $controller->getDetailFolder($_GET['id'], $_GET['key'], 1);
//FUNCIÓN PARA OBTENER LA KEY DE LA CARPETA PADRE EN CASO DE QUE TENGA LLAVE FORANEA (!= 0)
//ESTA FUNCIÓN NOS AYUDARA PARA LA NAVEGACIÓN EN EL BOTÓN DE REGRESAR
$keyFolder = $controller->getKeyFolder($folder['fk_folder']);
//Si no se encuentra EL ID DE LA CARPETA ENVIAMOS AL USUARIO AL INDEX
if (empty($folder)) {
    header("location: folders.php");
}
//MOSTRAR TODAS LAS SUBCARPETAS RELACIONADAS PARA UNA CARPETA
$folders = $controller->getSubFolders($_GET['id']);
$totalFolders = count($folders);
//FUNCIÓN PARA MOSTRAR LOS DOCUMENTOS DE LA CARPETA
$folderDocuments = $controller->getAllDocumentsFolder($folder['id_folder']);
$totalFolderDocuments = count($folderDocuments);

// Obtener la lista de los seguimientos mediante el método getTracingsFolder del controlador, pasando el id del folder o carpeta, el limit y el offset
$tracingsFolder = $controller->getTracingsFolder($folder['id_folder'], 5, 0);

//FUNCIÓN PARA MOSTRAR EN EL SELECT DE LOS SEGUIMIENTOS LA LISTA COMPLETA DE LOS USUARIOS DISPONIBLES
$allUsers = $controller->getUsers(1);

$permitted_chars_tracing = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$key_tracing = substr(str_shuffle($permitted_chars_tracing), 0, 6);

// FUNCIÓN PARA CREAR UNA NUEVA CARPETA
// Verifica si se ha dado clic en algun boton a traves del action





if (!empty($_POST['action'])) {
    // Si la acción es 'create', se intenta crear una carpeta nueva
    if ($_POST['action'] == 'createFolder') {
        // Llama al método para crear una carpeta y obtiene el ID de la carpeta creada
        $folderId = $controller->createFolder($_POST['newFolder']);
        // Si se crea la carpeta correctamente, redirecciona a la página de carpetas
        if ($folderId) {
            header("location: subfolder.php?id=$folder[id_folder]&key=$folder[key_folder]");
        }
    }

      // Si la acción es 'create', se intenta crear una carpeta nueva
  else if ($_POST['action'] == 'add') {
    // Llama al método para crear una carpeta y obtiene el ID de la carpeta creada
    $folderId = $controller->createFolder($_POST['newFolder']);
    // Si se crea la carpeta correctamente, redirecciona a la página de carpetas
    if ($folderId) {
        header("location: subfolder.php?id=$folder[id_folder]&key=$folder[key_folder]");
    }
  }


    // ================== CORREGIDO: ACTUALIZAR CARPETA ==================
    // Si la acción es 'updateFolder', se intenta actualizar la carpeta existente
    else if ($_POST['action'] == 'updateFolder') {
        debugLog('BEFORE_UPDATE', [
            'id_folder' => $_POST['updateFolder']['id_folder'] ?? 'MISSING',
            'method_exists' => method_exists($controller, 'updateNameFolder'),
            'data_received' => $_POST['updateFolder'] ?? 'NO_DATA',
            'tipo_persona' => $_POST['updateFolder']['tipo_persona'] ?? 'NOT_SET'
        ]);

        try {
            // Llama al método para actualizar la carpeta
            $idFolder = $controller->updateNameFolder($_POST['updateFolder']);

            debugLog('UPDATE_RESULT', [
                'result' => $idFolder,
                'redirect_will_happen' => (bool) $idFolder
            ]);

            // Si se actualiza correctamente, redirecciona
            if ($idFolder) {
                header("location: subfolder.php?id={$folder['id_folder']}&key={$folder['key_folder']}");
                exit();
            } else {
                debugLog('UPDATE_FAILED', 'updateNameFolder returned false/null');
                // Opcional: mostrar mensaje de error al usuario
                echo "<script>alert('Error al actualizar la información. Por favor intente nuevamente.');</script>";
            }
        } catch (Exception $e) {
            debugLog('UPDATE_ERROR', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            // Opcional: mostrar mensaje de error al usuario
            echo "<script>alert('Error técnico: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // Si la acción es 'delete', se intenta eliminar una carpeta existente
    else if ($_POST['action'] == 'deleteFolder') {
        // Llama al método para eliminar la carpeta y obtiene el ID de la carpeta eliminada
        $idFolder = $controller->deleteFolder($_POST['deleteFolder']);
        // Si se elimina la carpeta correctamente, redirecciona a la página de carpetas
        if ($idFolder) {
            header("location: subfolder.php?id=$folder[id_folder]&key=$folder[key_folder]");
        }
    }

    // MANTENER COMPATIBILIDAD: updateNameFolder (para otros formularios)
    else if ($_POST['action'] == 'updateNameFolder') {
        // Llama al método para actualizar el nombre de la carpeta y obtiene el ID de la carpeta actualizada
        $idFolder = $controller->updateNameFolder($_POST['updateNameFolder']);
        // Si se actualiza el nombre correctamente, redirecciona a la página de carpetas
        if ($idFolder) {
            header("location: subfolder.php?id=$folder[id_folder]&key=$folder[key_folder]");
        }
    }

    //ESTE CÓDIGO ES PARA SUBIR DOCUMENTOS CON LOS INPUTS DEL MODAL QUE ESTA OCULTO, EL CÓDIGO FUNCIONA PERO NO ESTA EN USO
    else if ($_POST['action'] == 'saveDocuments') {
        // Ruta de la carpeta de destino
        $carpeta = '../../uploads/documents/' . $folder['key_folder'];
        // Verifica si se han subido archivos
        if (!empty($_FILES['documents']['name'])) {
            // Recorre todos los archivos subidos
            foreach ($_FILES['documents']['name'] as $key => $filename) {
                // Nombre del archivo
                $archivonombre = $_FILES["documents"]["name"][$key];
                // Ruta del archivo temporal
                $fuente = $_FILES["documents"]["tmp_name"][$key];
                // Obtiene la extensión del archivo
                $info = new SplFileInfo($archivonombre);
                $file_extension = $info->getExtension();
                // Nombre del archivo destino (puedes renombrar el archivo si lo deseas)
                $nombre_archivo_destino = $archivonombre; // o puedes cambiarlo a algo diferente si quieres
                // Ruta completa del archivo de destino
                $target_path = $carpeta . '/' . $nombre_archivo_destino;
                // Verifica si el directorio de destino existe, si no, créalo
                if (!file_exists($carpeta)) {
                    mkdir($carpeta, 0777, true);
                }
                // Mueve el archivo a la carpeta de destino
                if (move_uploaded_file($fuente, $target_path)) {
                    //CLAVE ALEATORIA PARA LAS CARGAS DE DOCUMENTOS
                    $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $claveRandom = substr(str_shuffle($permitted_chars), 0, 7);
                    $keyDocument = "DOC-" . $claveRandom;
                    //Declaramos la data para almacenar la información de los Documentos
                    $dataDocument = array(
                        "id_folder_document" => $folder['id_folder'],
                        "id_user_document" => $_SESSION['user']['id_user'],
                        "key_document" => $keyDocument,
                        "file_name_document" => $archivonombre,
                        "file_extension_document" => $file_extension,
                        "first_fech_document" => null,
                        "second_fech_document" => null
                    );

                    //Obtenemos la respuesta del documento creado
                    $documentId = $controller->createDocument($dataDocument);
                } else {
                    echo "Ha ocurrido un error al subir el archivo $archivonombre.<br>";
                }
            }
            header("location: subfolder.php?id=$folder[id_folder]&key=$folder[key_folder]");
        }
    }

    //ESTE CÓDIGO ES PARA SUBIR LOS ARCHIVOS DE MANERA MASIVA
    else if ($_POST['action'] == 'saveFullDocuments') {
        // Ruta de la carpeta de destino (YA SEA AL INTERIOR DEL SERVER O EN UNA UNIDAD EXTERNA)

        // CÓDIGO PARA GUARDAR DOCUMENTOS DIRECTAMENTE EN EL SERVER
        $carpeta = '../../uploads/documents/' . $folder['key_folder'];

        // CÓDIGO GUARDAR DOCUMENTOS EN LA NUEVA UNIDAD
        // $carpeta = 'E:/uploads/documents/'.$folder['key_folder'];

        // Verifica si se han subido archivos
        if (!empty($_FILES['miarchivo']['name'])) {
            // Asegúrate de que la carpeta de destino exista, si no, crea la carpeta
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0777, true); // Crea la carpeta con permisos de escritura
            }

            // Recorre todos los archivos subidos
            foreach ($_FILES['miarchivo']['name'] as $key => $filename) {
                // Nombre del archivo
                $archivonombre = $_FILES["miarchivo"]["name"][$key];
                // Ruta del archivo temporal
                $fuente = $_FILES["miarchivo"]["tmp_name"][$key];
                // Obtiene la extensión del archivo
                $info = new SplFileInfo($archivonombre);
                $file_extension = $info->getExtension();
                // Nombre del archivo destino (puedes renombrar el archivo si lo deseas)
                $nombre_archivo_destino = $archivonombre; // o puedes cambiarlo a algo diferente si quieres
                // Ruta completa del archivo de destino
                $target_path = $carpeta . '/' . $nombre_archivo_destino;
                // Verifica si el directorio de destino existe, si no, créalo
                if (!file_exists($carpeta)) {
                    mkdir($carpeta, 0777, true);
                }
                // Mueve el archivo a la carpeta de destino
                if (move_uploaded_file($fuente, $target_path)) {
                    //CLAVE ALEATORIA PARA LAS CARGAS DE DOCUMENTOS
                    $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $claveRandom = substr(str_shuffle($permitted_chars), 0, 7);
                    $keyDocument = "DOC-" . $claveRandom;
                    //Declaramos la data para almacenar la información de los Documentos
                    $dataDocument = array(
                        "id_folder_document" => $folder['id_folder'],
                        "id_user_document" => $_SESSION['user']['id_user'],
                        "key_document" => $keyDocument,
                        "file_name_document" => $archivonombre,
                        "file_extension_document" => $file_extension,
                        "first_fech_document" => null,
                        "second_fech_document" => null
                    );
                    //Obtenemos la respuesta del documento creado
                    $documentId = $controller->createDocument($dataDocument);
                } else {
                    echo "Ha ocurrido un error al subir el archivo $archivonombre.<br>";
                }
            }
            header("location: subfolder.php?id=$folder[id_folder]&key=$folder[key_folder]");
        }
    }

    // Si la acción es 'deleteDocument', se intenta eliminar un documento existente
    else if ($_POST['action'] == 'deleteDocument') {
        // Llama al método para eliminar el documento y obtiene el ID del documento eliminado
        $idDocument = $controller->deleteDocument($_POST['deleteDocument']);
        // Si se elimina el documento correctamente, redirecciona a la página de carpetas
        if ($idDocument) {
            header("location: subfolder.php?id=$folder[id_folder]&key=$folder[key_folder]");
        }
    }

    // Si la acción es 'update', se intenta actualizar el nombre de una carpeta existente
    else if ($_POST['action'] == 'updateDocument') {
        // Llama al método para actualizar el nombre de la carpeta y obtiene el ID de la carpeta actualizada
        $idDocument = $controller->updateDocument($_POST['updateDocument']);
        // Si se actualiza el nombre correctamente, redirecciona a la página de carpetas
        if ($idDocument) {
            header("location: subfolder.php?id=$folder[id_folder]&key=$folder[key_folder]");
        }
    }

    // Si la acción es 'saveTracing', se intenta guardar el seguimiento tomando el texto del input del menú lateral (sin notificación)
    else if ($_POST['action'] == 'saveTracing') {
        // Llama al método para actualizar el nombre de la carpeta y obtiene el ID de la carpeta actualizada
        $saveTracing = $controller->createTracing($_POST['dataTracing']);
        // Si se actualiza el nombre correctamente, redirecciona a la página de carpetas
        if ($saveTracing) {
            header("location: subfolder.php?id=$folder[id_folder]&key=$folder[key_folder]");
        }
    }

    // Si la acción es 'deleteTracing', se intenta eliminar un seguimiento existente
    else if ($_POST['action'] == 'deleteTracing') {
        // Llama al método para eliminar el seguimiento y obtiene el ID del seguimiento eliminado
        $idTracingDelete = $controller->deleteTracing($_POST['deleteTracing']);
        // Si se elimina el documento correctamente, redirecciona a la página de detalles de la carpeta
        if ($idTracingDelete) {
            header("location: subfolder.php?id=$folder[id_folder]&key=$folder[key_folder]");
        }
    }
}

//FUNCIÓN PARA GENERAR UNA CLAVE PARA LA CARPETA
// Cadena de caracteres permitidos para generar la clave
$permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
// Genera una clave aleatoria tomando una subcadena de longitud 6 de la cadena de caracteres permitidos
$clave = substr(str_shuffle($permitted_chars), 0, 6);
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Compliance Hub</title>
    <link rel="stylesheet" href="../../resources/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../../resources/plugins/datatables-bs4/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="../../resources/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../../resources/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../../resources/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="icon" href="../../resources/img/icono.png">
    <script src="../../resources/js/jquery-3.5.1.min.js"></script>
    <!--SCRIPT PARA MANEJAR EL MOSTRAR Y OCULTAR DE LA FECHA DE ORIGINAL RECIBIDO AL REGISTRAR-->
    <script>
        $(document).ready(function () {
            // Inicialmente ocultar el div de la fecha y remover el atributo required
            $('#fecha-original-recibido').hide();
            $('input[name="newFolder[fech_orig_recib_folder]"]').removeAttr('required');
            // Mostrar/ocultar el div y agregar/quitar el atributo required según el estado del checkbox
            $('#opcion3').change(function () {
                if ($(this).is(':checked')) {
                    $('#fecha-original-recibido').show();
                    $('input[name="newFolder[fech_orig_recib_folder]"]').attr('required', 'required');
                } else {
                    $('#fecha-original-recibido').hide();
                    $('input[name="newFolder[fech_orig_recib_folder]"]').removeAttr('required');
                }
            });
        });
    </script>
    <!--SCRIPT PARA MANEJAR EL MSOTRAR Y OCULTAR DE LA FECHA DE ORIGINAL RECIBIDO AL ACTUALIZAR-->
    <script>
        $(document).ready(function () {
            // Mostrar/ocultar el div y agregar/quitar el atributo required según el estado del checkbox
            $('#edit_chk_orig_recib_folder').change(function () {
                if ($(this).is(':checked')) {
                    $('#edit-fecha-original-recibido').show();
                    $('input[name="updateFolder[fech_orig_recib_folder]"]').attr('required', 'required');
                } else {
                    $('#edit-fecha-original-recibido').hide();
                    $('input[name="updateFolder[fech_orig_recib_folder]"]').removeAttr('required');
                }
            });
        });
    </script>
    <style>
        .title {
            font-size: 15px;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 5px 5px 5px 5px;
            margin-bottom: -10px;
        }

        .status-bar-checks {
            text-align: right;
            padding-top: 5px;
            margin-right: 10px;
        }

        .status-bar-checks .status-item {
            display: inline-block;
            margin-left: 10px;
        }

        .status-bar-checks i {
            font-size: 16px;
            /* Tamaño del icono */
            color: #000000;
        }

        .status-bar-checks .status-item span {
            font-weight: bold;
            font-size: 14px;
        }

        /*DISEÑO DE LA CAJA DE SELECCIÓN DE MULTIPLES ARCHIVOS*/
        .files input {
            outline: 2px dashed #92b0b3;
            outline-offset: -10px;
            -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
            transition: outline-offset .15s ease-in-out, background-color .15s linear;
            padding: 120px 0px 85px 35%;
            text-align: center !important;
            margin: 0;
            width: 100% !important;
        }

        .files input:focus {
            outline: 2px dashed #92b0b3;
            outline-offset: -10px;
            -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
            transition: outline-offset .15s ease-in-out, background-color .15s linear;
            border: 1px solid #92b0b3;
        }

        .color input {
            background-color: #f1f1f1;
        }

        /* CSS PARA EL DISEÑO DEL OVERLAY */
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .spinner {
            border: 16px solid #f3f3f3;
            border-top: 16px solid #3498db;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Botón con CSS solo es respaldo */
        .menu-button-respaldo {
            position: fixed;
            top: 50%;
            right: 0px;
            transform: translateY(-50%);
            background-color: #5a9e46;
            color: #fff;
            border: none;
            border-radius: 50%;
            padding: 15px;
            cursor: pointer;
            /*z-index: 1000;*/
            z-index: 10000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            transition: right 0.3s ease;
            /* Agregamos la transición */
        }

        .menu-button {
            position: fixed;
            top: 50%;
            right: 0px;
            transform: translateY(-50%);
            height: 17%;
            /* Altura del botón basada en la imagen */
            border: none;
            background: transparent;
            /* Eliminar fondo */
            background-color: transparent;
            cursor: pointer;
            z-index: 10000;
            transition: right 0.3s ease;
            /* Agregamos la transición */
            padding: 0;
            /* Eliminar cualquier relleno */
            justify-content: center;
            align-items: center;
            outline: none;
            /* Eliminar el contorno alrededor del botón al hacer clic */
            display: flex;
        }

        /* Eliminar el contorno en el foco y el estado activo */
        .menu-button:focus,
        .menu-button:active {
            outline: none;
            /* Asegura que no haya contorno cuando se hace clic o se enfoca */
            box-shadow: none;
            /* También eliminar cualquier sombra que aparece con el foco */
        }

        /* Estilo para la imagen del icono */
        .menu-icon {
            width: 90%;
            /* Ajusta la imagen al tamaño del botón */
            height: 90%;
            /* Ajusta la imagen al tamaño del botón */
            object-fit: contain;
            /* Asegura que la imagen no se deforme */
        }

        .menu-button.open {
            right: 358px;
            /* Mueve el botón al ancho del menú */
        }

        /* Barra lateral */
        .sidebar-lateral {
            position: fixed;
            top: 0;
            right: -360px;
            /* Oculto fuera de pantalla inicialmente */
            width: 360px;
            height: 100%;
            background-color: #F4F6F9;
            color: #000000;
            /*padding: 20px;*/
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.2);
            transition: right 0.3s ease;
            /*z-index: 999;*/
            z-index: 9999;
            overflow-y: auto;
            /* Habilita el scroll vertical */

            display: flex;
            /* Utiliza flexbox para manejar el contenido */
            flex-direction: column;
            /* Asegura que los elementos estén en columna */
        }

        /* Nuevo contenedor para el contenido con padding */
        .sidebar-content {
            flex: 1;
            /* Permite que el contenido ocupe el espacio disponible */

            padding: 20px;
            box-sizing: border-box;
            /* Evita desbordamiento */
        }

        .sidebar-lateral.open {
            right: 0;
            /* Muestra la barra lateral */
        }

        /* Botón de cerrar */
        .close-button {
            background-color: #DC3545;
            color: white;
            border: none;
            border-radius: 100%;
            padding: 10px;
            cursor: pointer;
            position: absolute;
            top: 10px;
            right: 10px;
            width: 13%;
        }

        /* Contenido principal */
        .content {
            padding: 20px;
        }

        /* Timeline */
        .timeline {
            position: relative;
            margin: 20px 0;
            padding: 0;
            list-style: none;
        }

        /* Estilos de los ítems */
        .timeline-item {
            position: relative;
            margin: 10px 0;
            /* Reduce el margen entre ítems */
            width: 100%;
            /* Ajusta el ancho para pegarlo a las esquinas */
            padding: 15px;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow-wrap: break-word;
            line-height: 1.4;
            text-align: justify;
            z-index: 2;
        }

        /* Ajuste de texto */
        .timeline-item .date {
            font-size: 12px;
            color: #000000;
            font-weight: bold;
            margin-bottom: 5px;
            text-align: right;
        }

        .timeline-item .comment {
            font-size: 14px;
            color: #333;
            margin-bottom: 10px;
        }

        .timeline-item .user {
            font-size: 14px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 5px;
        }


        /* Contenedor del usuario y los botones */
        .user-row {
            display: flex;
            justify-content: space-between;
            /* Espaciado entre usuario y botones */
            align-items: center;
            /* Centra verticalmente */
            margin-bottom: 5px;
        }

        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 5px;
            /* Espacio entre los íconos */
        }

        /* Estilo del botón */
        .action-buttons button {
            background: none;
            border: none;
            cursor: pointer;
            color: #333;
            /* Color del ícono */
            font-size: 16px;
            padding: 5px;
            transition: color 0.2s ease;
        }

        /* Hover en los botones */
        .action-buttons button:hover {
            color: #007BFF;
            /* Cambia el color al pasar el mouse */
        }

        /* Alineación de los íconos */
        .action-buttons i {
            pointer-events: none;
            /* Desactiva los eventos en el ícono para que funcionen en el botón */
        }

        .edit-button-tracing {
            outline: none;
            /* Elimina el cuadro de enfoque */
            border: none;
            /* Elimina el borde (si es necesario) */
            background: none;
            /* Elimina el fondo (si es necesario) */
            padding: 0;
            /* Ajusta el padding para que se vea limpio */
            cursor: pointer;
            /* Cambia el cursor a pointer */
        }

        .edit-button-tracing:focus {
            outline: none;
            /* Asegúrate de que al recibir el foco no se muestre nada */
        }



        /* Estilo para el usuario que está en sesión (autor del seguimiento) */
        .timeline-item.author-highlight {
            border-left: 4px solid #3498db;
            /* Color azul */
            padding-left: 10px;
            /* Espaciado para separar del borde */
            background-color: #f0f8ff;
            /* Fondo ligero opcional */
        }

        /* Estilo para otros usuarios */
        .timeline-item.other-highlight {
            border-left: 4px solid #e67e22;
            /* Color naranja delgado */
            padding-left: 10px;
            background-color: #fffaf0;
            /* Fondo ligero opcional */
        }

        /*-----*/
        #loading {
            font-size: 16px;
            color: #666;
            margin-top: 20px;
        }

        /* Botón fijo al final de la barra lateral */
        .fixed-button {
            position: sticky;
            bottom: 0;
            background-color: #3E4850;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            text-align: center;
            cursor: pointer;
            z-index: 100;
            border-radius: 10px;
        }

        /*CSS PARA EL INPUT Y BOTON PARA EDITAR LA CARPETA PRINCIPAL*/
        /* Posicionar el botón dentro del input */
        .position-relative {
            position: relative;
        }

        /* Posicionar el botón dentro del input con separación */
        .edit-folder-btn {
            position: absolute;
            right: 20px;
            /* Aumenta este valor para más separación */
            top: 50%;
            transform: translateY(-50%);
            background: none;
            /* Sin fondo */
            border: none;
            /* Sin borde */
            color: #666;
            /* Color del ícono */
            cursor: pointer;
            padding: 0;
            /* Sin padding extra */
            font-size: 1rem;
            /* Ajusta el tamaño del ícono */
            outline: none;
            /* Elimina el borde de enfoque */
        }

        /* Evita el borde o cuadro negro al hacer clic */
        .edit-folder-btn:focus {
            outline: none;
            /* Sin efecto de enfoque */
            box-shadow: none;
            /* Evita sombras adicionales */
        }

        /* Cambio de color al pasar el mouse (opcional) */
        .edit-folder-btn:hover {
            color: #333;
            /* Color más oscuro en hover */
        }

        /* Estilo del contenedor */
        .input-container {
            position: sticky;
            /* Fija el contenedor dentro del menú lateral */
            bottom: 0;
            /* Coloca en la parte inferior */
            left: 0;
            width: 100%;
            /* Ocupa todo el ancho del menú */
            background-color: white;
            padding: 10px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.2);
            /* Sombra para resaltar */
            display: flex;
            align-items: center;
            box-sizing: border-box;
            z-index: 10;
            /* Asegura que esté por encima de los seguimientos */
        }

        /* Estilo del input */
        .new-tracing-input {
            flex-grow: 1;
            /* Ocupa el espacio restante */
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 16px;
            margin-right: 10px;
            /* Espacio para el icono */
            background-color: #E9EAEE;
        }

        /* Estilo del icono */
        .modal-icon {
            cursor: pointer;
            font-size: 24px;
            color: #666;
        }

        .notification-badge {
            position: fixed;
            top: 38%;
            right: 3px;
            transform: translateY(-50%);
            background-color: red;
            color: white;
            font-size: 15px;
            font-weight: bold;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini">
    <div id="loadingOverlay" style="display: none;">
        <div class="spinner"></div>
    </div>

    <!-- Botón con CSS para desplegar el Menú Lateral -->
    <!--<button id="menu-button" class="menu-button">
        <i class="fas fa-comments fa-lg"></i>
        </button>-->

    <!-- Imagen con el diseño del botón -->
    <!--<button id="menu-button" class="menu-button">
            <img src="../../resources/img/btnTracing.png" alt="btnSeguimiento" class="menu-icon">
        </button>-->

    <button id="menu-button" class="menu-button">
        <img src="../../resources/img/btnTracing.png" alt="btnSeguimiento" class="menu-icon">
        <div id="notification-badge" class="notification-badge">0</div>
    </button>

    <!-- Barra lateral -->
    <div id="sidebar-lateral" class="sidebar-lateral">
        <button id="close-sidebar" class="close-button" hidden>X</button>
        <!-- Nuevo contenedor para contenido con padding -->
        <div class="sidebar-content">
            <h3>Seguimiento</h3>
            <hr>

            <?php if (empty($tracingsFolder)) { ?>
                <div class="alert alert-info" role="alert" style="text-align:center;">
                    <i class="fas fa-exclamation-triangle"></i>&nbsp;¡No se hallaron registros de seguimientos!
                </div>
            <?php } else { ?>
                <ul id="timeline">
                    <?php foreach ($tracingsFolder as $tracing): ?>
                        <li
                            class="timeline-item <?php echo ($_SESSION['user']['id_user'] == $tracing['id_user_tracing']) ? 'author-highlight' : 'other-highlight'; ?>">
                            <div class="user-row">
                                <p class="user"><?php echo htmlspecialchars($tracing['name_user']); ?></p>
                                <?php if ($_SESSION['user']['id_user'] == $tracing['id_user_tracing']): ?>
                                    <div class="action-buttons">

                                        <form action="#" method="POST">
                                            <input name="deleteTracing[id_tracing]" type="text" class="form-control" id="id_tracing"
                                                value="<?php echo $tracing['id_tracing']; ?>" readonly hidden
                                                style="display: none;">
                                            <input name="deleteTracing[key_tracing]" type="text" class="form-control"
                                                id="key_tracing" value="<?php echo $tracing['key_tracing']; ?>" readonly hidden
                                                style="display: none;">

                                            <button class="edit-button-tracing" data-toggle="modal" id="btn-edit-tracing">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        </form>

                                        <form action="#" method="POST"
                                            onsubmit="return confirm('¿Estás seguro de eliminar el seguimiento?');">
                                            <input name="deleteTracing[id_tracing]" type="text" class="form-control" id="id_tracing"
                                                value="<?php echo $tracing['id_tracing']; ?>" readonly hidden
                                                style="display: none;">
                                            <input name="deleteTracing[key_tracing]" type="text" class="form-control"
                                                id="key_tracing" value="<?php echo $tracing['key_tracing']; ?>" readonly hidden
                                                style="display: none;">
                                            <button class="btn delete-button" name="action" value="deleteTracing"
                                                id="btn-delete-tracing">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>

                                    </div>
                                <?php endif; ?>
                            </div>
                            <p class="comment"><?php echo htmlspecialchars($tracing['comment_tracing']); ?></p>
                            <p class="date">
                                <?php echo date_format(date_create($tracing['created_at_tracing']), 'd/m/Y h:i a'); ?>
                            </p>
                        </li>
                        <!--<li class="timeline-item">
                                <p class="user"><?php echo $tracing['name_user'] ?></p>
                                <p class="comment"><?php echo $tracing['comment_tracing'] ?></p>
                                <p class="date"><?php echo date_format(date_create($tracing['created_at_tracing']), 'd/m/Y h:i a'); ?></p>
                            </li>-->
                        <hr>
                    <?php endforeach; ?>
                </ul>
                <!--<div id="loading" style="text-align: center;">Cargando...</div>-->
            <?php } ?>
        </div> <!-- Fin del contenedor con padding -->

        <!-- Nuevo contenedor para el input y el icono -->
        <form action="#" method="post" class="input-container">
            <input name="dataTracing[id_folder_tracing]" type="text" class="form-control" required
                value="<?php echo $folder['id_folder']; ?>" readonly style="display: none;" hidden>
            <input name="dataTracing[id_user_tracing]" type="text" class="form-control" required
                value="<?php echo $_SESSION['user']['id_user']; ?>" readonly style="display: none;" hidden>
            <input name="dataTracing[key_tracing]" type="text" class="form-control" required
                value="TRC-<?php echo $clave; ?>" readonly style="display: none;" hidden>
            <input name="dataTracing[comment_tracing]" type="text" class="new-tracing-input" required
                placeholder="Seguimiento" autocomplete="off">

            <i class="fas fa-ellipsis-v modal-icon" data-toggle="modal" data-target="#modalAddTracing"
                id="ellipsis-icon" style="pointer-events: none; opacity: 0.5;"></i>
            <button name="action" value="saveTracing" hidden>Guardar</button>
        </form>

        <!-- Botón fijo al final para añadir un nuevo seguimiento -->
        <!--
            <button class="fixed-button" role="button" aria-pressed="true" data-toggle="modal" data-target="#modalAddTracing">Agregar Nuevo Seguimiento</button>
            -->
    </div>

    <div class="wrapper" style="padding-top: 57px;">
        <?php include "../templates/navbar.php"; ?>
        <div class="content-wrapper">
            <div class="content-header">

                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-lg-8 col-md-6 col-sm-12">
                            <!--CÓDIGO ORIGINAL (QUITAR EL PHP)-->
                            <?php /*
<form action="#" method="post">
<div class="row">
<!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O VENTAS (3)-->
<?php if($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3){ ?>
    <div class="col-lg-6 col-md-12 col-sm-12">
        <input name="updateNameFolder[id_folder]" type="text" class="form-control form-control-md" id="id_folder" required value="<?php echo $folder['id_folder']; ?>" readonly style="display: none;" hidden>
        <input name="updateNameFolder[name_folder]" type="text" class="form-control form-control-md" id="name_folder" required placeholder="Ejem. entorno interno" value="<?php echo $folder['name_folder']; ?>">
    </div>
<?php } else { ?>
    <div class="col-12">
        <input type="text" class="form-control form-control-md" value="<?php echo $folder['name_folder']; ?>" readonly>
    </div>
<?php } ?>


<!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O VENTAS (3)-->
<?php if($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3){ ?>
    <div class="col-lg-6 col-md-12 col-sm-12" style="display: none;" hidden>
        <button class="btn btn-raised btn-info btn-md btn-block" name="action" value="updateNameFolder">Cambiar nombre</button>
    </div>
<?php } ?>
</div>
</form>
*/
                            ?>
                            <!--CÓDIGO NUEVO / con el input y el form por separados-->
                            <?php /*
<form action="#" method="post">
<div class="row">
<div class="col-lg-6 col-md-12 col-sm-12">
    <input type="text" class="form-control" id="id_folder" required value="<?php echo $folder['id_folder']; ?>" readonly style="display: none;" hidden>
    <input type="text" class="form-control" id="name_folder" required placeholder="Ejem. entorno interno" value="<?php echo $folder['name_folder']; ?>" readonly disabled>
</div>
<!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
<?php if($_SESSION['user']['id_type_user'] == 1){ ?>
    <div class="col-lg-2">
        <button class="btn btn-raised btn-info btn-md btn-block edit-folder-btn" name="action" value="updateNameFolder"><i class="fas fa-pen"></i></button>
    </div>
<?php } ?>
</div>
</form>
*/ ?>
                            <!--Nuevo código con el input y el botón de editar juntos-->
                            <form action="#" method="post">
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 col-sm-12 position-relative">
                                        <input type="text" class="form-control" id="id_folder" required
                                            value="<?php echo $folder['id_folder']; ?>" readonly style="display: none;"
                                            hidden>
                                        <input type="text" class="form-control" id="name_folder" required
                                            placeholder="Ejem. entorno interno" value="<?php echo $folder['name_folder']; ?>"
                                            readonly disabled>

                                        <!-- Botón del lápiz dentro del input -->
                                        <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                            <button type="button" class="edit-folder-btn" data-toggle="modal"
                                                data-target="#editFolderModal">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        <?php } ?>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="col-lg-4 col-md-6 col-sm-12 text-right">
                            <div class="row">
                                <div class="col-lg-12">
                                    <?php if ($folder['fk_folder'] == 0) { ?>
                                        <a href="folders.php" class="btn btn-block" style="background-color: #FF5800;
                                                color: #ffffff;
                                                text-decoration: none;
                                                display: inline-block;
                                                padding: 0.375rem 0.75rem;
                                                border-radius: 0.25rem;
                                                font-size: 1rem;
                                                line-height: 1.5;border: 1px solid transparent;
                                                cursor: pointer;">
                                            Regresar
                                        </a>
                                        <?php
                                    } else { ?>
                                        <a href="subfolder.php?id=<?php echo $folder['fk_folder']; ?>&key=<?php echo $keyFolder['key_folder']; ?>"
                                            class="btn btn-block" style="background-color: #FF5800;
                                                color: #ffffff;
                                                text-decoration: none;
                                                display: inline-block;
                                                padding: 0.375rem 0.75rem;
                                                border-radius: 0.25rem;
                                                font-size: 1rem;
                                                line-height: 1.5;border: 1px solid transparent;
                                                cursor: pointer;">
                                            Regresar
                                        </a>
                                        <?php
                                    }
                                    ?>
                                    <!--<button onclick="goBack()" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;" role="button" aria-pressed="true">Regresar</button>-->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="status-bar-checks" style="text-align: left !important; margin-top:-10px;">
                        <?php
                        if ($folder['chk_alta_fact_folder'] === "Si" || $folder['chk_lib_folder'] === 'Si' || $folder['chk_orig_recib_folder'] === 'Si') {
                            if ($folder['chk_alta_fact_folder'] === "Si") {
                                echo '<div class="status-item" data-toggle="tooltip" title="Vo. Bo. Alta Facturación"><span><i class="fas fa-file-alt"></i></span></div>';
                            }
                            if ($folder['chk_lib_folder'] === 'Si') {
                                echo '<div class="status-item" data-toggle="tooltip" title="Vo. Bo. Liberación"><span><i class="fas fa-truck"></i></span></div>';
                            }
                            if ($folder['chk_orig_recib_folder'] === 'Si') {
                                // Convertir la fecha al formato día, mes y año
                                $fechaOriginal = new DateTime($folder['fech_orig_recib_folder']);
                                $fechaFormateada = $fechaOriginal->format('d/m/Y');
                                echo '<div class="status-item" data-toggle="tooltip" title="Original Recibido - ' . htmlspecialchars($fechaFormateada, ENT_QUOTES, 'UTF-8') . '"><span><i class="fas fa-user-check"></i></span></div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <?php if (!empty($folders) || !empty($folderDocuments)) { ?>
                        <div class="row" style="margin-bottom:5px;">
                            <?php if (!empty($folders)) { ?>
                                <div class="col-12 col-md-auto d-flex justify-content-center">
                                    <strong>Total de expedientes: <?php echo $totalFolders; ?></strong>
                                </div>
                            <?php } ?>
                            <?php if (!empty($folderDocuments)) { ?>
                                <div class="col-12 col-md-auto d-flex justify-content-center">
                                    <strong>Total de documentos: <?php echo $totalFolderDocuments; ?></strong>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-lg-4">
                                            <?php if (empty($folders) and empty($folderDocuments)) { ?>
                                                <input type="text" class="form-control" style="margin-bottom:10px;"
                                                    id="searchInputFolders" placeholder="Buscar expediente...">
                                            <?php } else if (!empty($folders) and empty($folderDocuments)) { ?>
                                                    <input type="text" class="form-control" style="margin-bottom:10px;"
                                                        id="searchInputFolders" placeholder="Buscar expediente...">
                                            <?php } else if (empty($folders) and !empty($folderDocuments)) { ?>
                                                        <input type="text" class="form-control" style="margin-bottom:10px;"
                                                            id="searchInputDocs" placeholder="Buscar documentos...">
                                            <?php } else { ?>
                                                        <input type="text" class="form-control" style="margin-bottom:10px;"
                                                            id="searchInputFolders" placeholder="Buscar expediente...">
                                            <?php } ?>
                                        </div>
                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O VENTAS (3)-->
                                        <?php
                                        // Verificamos el tipo de usuario (administrador o ventas)
                                        if ($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3) {

                                            // Si estamos en una carpeta de cliente (fk_folder != 0), mostramos botones de documentos
                                            if ($folder['fk_folder'] != 0) {
                                                ?>
                                                <div class="col-lg-4">
                                                    <!-- Botón para abrir el modal de agregar documentos -->
                                                    <!--CÓDIGO ANTIGUO que mostraba un modal para seleccionar un documento de uno por uno-->
                                                    <!--<a href="#" class="btn btn-block" style="background-color: blue; color: #ffffff;" role="button" aria-pressed="true" data-toggle="modal" data-target="#modalAgregarDocuments">
                    <i class="fas fa-plus pr-2"></i>Agregar documentos
                </a>-->

                                                    <!--NUEVO CÓDÍGO QUE PERMITE SUBIR LOS DOCUMENTOS DE MANERA MASIVA-->
                                                    <a href="#" class="btn btn-block"
                                                        style="background-color: blue; color: #ffffff;" role="button"
                                                        aria-pressed="true" data-toggle="modal"
                                                        data-target="#modalAgregarFullDocuments">
                                                        <i class="fas fa-plus pr-2"></i>Agregar documentos
                                                    </a>
                                                </div>
                                                <div class="col-lg-4 text-right">
                                                    <!-- Botón para abrir el modal de agregar nuevas carpetas -->
                                                    <a href="#" class="btn btn-block"
                                                        style="background-color: blue; color: #ffffff;" role="button"
                                                        aria-pressed="true" data-toggle="modal"
                                                        data-target="#modalAgregarCarpeta">
                                                        <!--<a href="#" class="btn btn-block" style="background-color: #37424A; color: #ffffff;" role="button" aria-pressed="true" data-toggle="modal" data-target="#modalAgregarCarpeta">-->
                                                        <i class="fas fa-plus pr-2"></i>Agregar nuevo expediente
                                                    </a>
                                                </div>

                                                <?php /*
                        <div class="col-lg-3 text-right">
                            <!-- Botón para abrir el modal de agregar nuevas carpetas -->
                            <a href="tracing.php?id=<?php echo $folder['id_folder']; ?>&key=<?php echo $folder['key_folder']; ?>" class="btn btn-block" style="background-color: #5a9e46; color: #ffffff;" role="button" aria-pressed="true">
                                <i class="fas fa-plus pr-2"></i>Seguimiento
                            </a>
                        </div>
                        */ ?>

                                                <?php
                                                // Si estamos en una carpeta de empresa (fk_folder == 0), mostramos botón de agregar cliente
                                            } else if ($folder['fk_folder'] == 0) {
                                                ?>
                                                    <div class="col-lg-4 col-md-4 col-sm-4 text-right ml-auto">
                                                        <!-- Botón para abrir el modal de agregar nuevo cliente -->
                                                        <a href="#" class="btn btn-block"
                                                            style="background-color: #FF5800; color: #ffffff;" role="button"
                                                            aria-pressed="true" data-toggle="modal"
                                                            data-target="#modalAgregarCliente">
                                                            <i class="fas fa-plus pr-2"></i>Agregar nuevo cliente
                                                        </a>
                                                    </div>
                                                <?php
                                            }
                                        } ?>
                                    </div>

                                    <?php if (empty($folders) and empty($folderDocuments)) { ?>
                                        <div class="alert alert-info" role="alert">
                                            <i class="fas fa-exclamation-triangle"></i>&nbsp;¡No se hallaron registros de
                                            expedientes para este cliente!
                                        </div>
                                    <?php } else { ?>
                                        <div class="row">
                                            <?php foreach ($folders as $folder): ?>
                                                <div class="col-lg-3 col-md-6 col-sm-12" id="myFolders">
                                                    <div class="folder">
                                                        <div class="title-bar"
                                                            style="background-color: #f5f5f5; color: #000000; border-radius: 10px; padding: 5px; display: flex; flex-direction: column; align-items: stretch;">
                                                            <div
                                                                style="display: flex; justify-content: space-between; align-items: center;">
                                                                <div class="title">
                                                                    <a href="subfolder.php?id=<?php echo $folder['id_sub_folder']; ?>&key=<?php echo $folder['key_sub_folder']; ?>"
                                                                        style="text-decoration: none; color: inherit;">
                                                                        <i class="fas fa-folder fa-lg"></i>
                                                                        &nbsp;&nbsp;
                                                                        <?php echo $folder['name_sub_folder']; ?>
                                                                    </a>
                                                                </div>
                                                                <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->

                                                                <div class="dropdown" style="margin-top:5px;">
                                                                    <button class="btn btn-secondary" type="button"
                                                                        id="dropdownMenuButton_<?php echo $folder['id_sub_folder']; ?>"
                                                                        data-toggle="dropdown" aria-haspopup="true"
                                                                        aria-expanded="false"
                                                                        style="background-color: transparent; border: none;">
                                                                        <i class="fas fa-ellipsis-v"
                                                                            style="color: black; background-color: transparent;"></i>
                                                                    </button>
                                                                    <div class="dropdown-menu dropdown-menu-right"
                                                                        aria-labelledby="dropdownMenuButton_<?php echo $folder['id_sub_folder']; ?>">
                                                                        <a class="dropdown-item" href="#"
                                                                            data-folder-id="<?php echo $folder['id_sub_folder']; ?>">
                                                                            <i class="fas fa-pen"></i> Editar expediente
                                                                        </a>
                                                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->

                                                                        <hr>
                                                                        <form action="#" method="POST">
                                                                            <input name="deleteFolder[idFolder]" type="hidden"
                                                                                class="form-control form-control-sm"
                                                                                id="id_sub_folder"
                                                                                value="<?php echo $folder['id_sub_folder']; ?>"
                                                                                readonly hidden style="display: none;">
                                                                            <button class="dropdown-item" type="submit"
                                                                                name="action" value="deleteFolder"
                                                                                onclick="return confirm('¿Estás seguro de eliminar el expediente?');">
                                                                                <i class="fas fa-trash"></i> Mover a la papelera
                                                                            </button>
                                                                        </form>

                                                                    </div>
                                                                </div>

                                                            </div>

                                                            <div class="status-bar"
                                                                style="text-align: right; padding-top: 5px; margin-right:10px;">
                                                                <?php
                                                                if ($folder['dias'] === null) {
                                                                    echo '<span style="color: #0000FF; font-weight: bold; font-size:14px;">- - -</span>';
                                                                } else if ($folder['dias'] >= 1) {
                                                                    echo '<span style="color: #FF0000; font-weight: bold; font-size:14px;">Expediente vencido <i class="fas fa-times"></i></span>';
                                                                } else if ($folder['dias'] >= -60) {
                                                                    echo '<span style="color: #FFA500; font-weight: bold; font-size:14px;">Cerca de vencimiento <i class="fas fa-exclamation-triangle"></i></span>';
                                                                } else {
                                                                    echo '<span style="color: #008000; font-weight: bold; font-size:14px;">Expediente vigente <i class="fas fa-check"></i></span>';
                                                                }
                                                                ?>
                                                            </div>

                                                            <div class="status-bar-checks">
                                                                <?php
                                                                if ($folder['chk_alta_fact_sub_folder'] === "Si" || $folder['chk_lib_sub_folder'] === 'Si' || $folder['chk_orig_recib_sub_folder'] === 'Si') {
                                                                    if ($folder['chk_alta_fact_sub_folder'] === "Si") {
                                                                        echo '<div class="status-item" data-toggle="tooltip" title="Vo. Bo. Alta Facturación"><span><i class="fas fa-file-alt"></i></span></div>';
                                                                    }
                                                                    if ($folder['chk_lib_sub_folder'] === 'Si') {
                                                                        echo '<div class="status-item" data-toggle="tooltip" title="Vo. Bo. Liberación"><span><i class="fas fa-truck"></i></span></div>';
                                                                    }
                                                                    if ($folder['chk_orig_recib_sub_folder'] === 'Si') {
                                                                        // Convertir la fecha al formato día, mes y año
                                                                        $fechaOriginal = new DateTime($folder['fech_orig_recib_sub_folder']);
                                                                        $fechaFormateada = $fechaOriginal->format('d/m/Y');
                                                                        echo '<div class="status-item" data-toggle="tooltip" title="Original Recibido - ' . htmlspecialchars($fechaFormateada, ENT_QUOTES, 'UTF-8') . '"><span><i class="fas fa-user-check"></i></span></div>';
                                                                    }
                                                                } else {
                                                                    echo '<div class="status-item"><span>- - -</span></div>';
                                                                }
                                                                ?>
                                                            </div>

                                                        </div>
                                                    </div>&nbsp;
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php } ?>

                                    <!--CONSULTA GENERAL DE TODOS LOS DOCUMENTOS RELACIONADOS CON LA CARPETA -->
                                    <?php if (!empty($folderDocuments)) { ?>
                                        <?php if (!empty($folders)) { ?>
                                            <div class="row">
                                                <!--BOTÓN DE BUSQUEDA DE DOCUMENTOS-->
                                                <div class="col-lg-4 col-md-6 col-sm-12">
                                                    <input type="text" class="form-control" style="margin-bottom:10px;"
                                                        id="searchInputDocs" placeholder="Buscar documentos...">
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <!--MOSTRAR LOS DOCUMENTOS DE LA CARPETA-->
                                        <div class="row">
                                            <?php foreach ($folderDocuments as $folderDocument): ?>
                                                <div class="col-lg-3 col-md-4 col-sm-16" id="myDocuments">
                                                    <div class="card" style="width: 100%;">
                                                        <div class="card-body">

                                                            <!--ALERTA SUPERIOR QUE MOSTRABA EL ESTATUS DEL DOCUMENTO (VIGENCIA)-->

                                                            <!--<div style="text-align: right; margin-bottom: 10px;">
                                                                        <?php //if ($folderDocument['dias'] >= 1) { ?>
                                                                            <span class="badge bg-danger" style="font-size: 15px; width: auto; height: auto; padding: 5px 10px;">Vencido <i class="fas fa-times"></i></span>
                                                                        <?php //} else if ($folderDocument['dias'] >= -60){ ?>
                                                                            <span class="badge" style="font-size: 15px; color:white; background-color: orange; width: auto; height: auto; padding: 5px 10px;">Cerca de Vencimiento <i class="fas fa-exclamation-triangle"></i></span>
                                                                        <?php //} else { ?>
                                                                            <span class="badge bg-success" style="font-size: 15px; width: auto; height: auto; padding: 5px 10px;">Vigente <i class="fas fa-check"></i></span>
                                                                        <?php //} ?>
                                                                    </div>-->

                                                            <!--CÓDIGO NUEVO- PARA VISUALIZAR EL ARCHIVO EN OTRA UNIDAD-->
                                                            <embed
                                                                src="extensions/view_pdf.php?folder=<?php echo urlencode($folderDocument['key_folder']); ?>&file=<?php echo urlencode($folderDocument['file_name_document']); ?>"
                                                                width="100%" height="300px" type="application/pdf">
                                                            <!--// CÓDIGO ANTIGUO - DESARROLLO EN UN SERVER-->
                                                            <!--<embed src="<?php echo "../../uploads/documents/" . $folderDocument['key_folder'] . "/" . $folderDocument['file_name_document']; ?>" width="100%" height="300px" type="application/pdf">-->

                                                            <div class="title-bar"
                                                                style="display: flex; justify-content: space-between; align-items: center;">
                                                                <div class="title">
                                                                    <?php
                                                                    // CUANDO ES UN PDF
                                                                    if ($folderDocument['file_extension_document'] === 'pdf' || $folderDocument['file_extension_document'] === 'PDF') { ?>
                                                                        <!--CÓDIGO NUEVO-->
                                                                        <a href="extensions/open_pdf.php?folder=<?php echo urlencode($folderDocument['key_folder']); ?>&file=<?php echo urlencode($folderDocument['file_name_document']); ?>"
                                                                            style="width: 40px; height: 40px;" target='_blank'
                                                                            class="btn btn-danger"><i
                                                                                class="fas fa-file-pdf"></i></a>
                                                                        <!--// CÓDIGO ANTIGUO - DESARROLLO EN UN SERVER-->
                                                                        <!--<a href="<?php echo "../../uploads/documents/" . $folderDocument['key_folder'] . "/" . $folderDocument['file_name_document']; ?>" style="width: 40px; height: 40px;" target='_blank' class="btn btn-danger"><i class="fas fa-file-pdf"></i></a>-->
                                                                    <?php }
                                                                    //CUANDO ES UN WORD
                                                                    else { ?>
                                                                        <!--CÓDIGO NUEVO-->
                                                                        <a href="extensions/download_pdf.php?folder=<?php echo urlencode($folderDocument['key_folder']); ?>&file=<?php echo urlencode($folderDocument['file_name_document']); ?>"
                                                                            download style="width: 40px; height: 40px;"
                                                                            class="btn btn-primary"><i
                                                                                class="fas fa-file-word"></i></a>
                                                                        <!--// CÓDIGO ANTIGUO - DESARROLLO EN UN SERVER-->
                                                                        <!--<a href="<?php echo "../../uploads/documents/" . $folderDocument['key_folder'] . "/" . $folderDocument['file_name_document']; ?>" style="width: 40px; height: 40px;" target='_blank' class="btn btn-primary"><i class="fas fa-file-word"></i></a>-->
                                                                    <?php } ?>
                                                                    &nbsp;&nbsp;
                                                                    <?php echo $folderDocument['file_name_document']; ?>
                                                                </div>
                                                                <div class="dropdown">
                                                                    <button class="btn btn-secondary" type="button"
                                                                        id="dropdownMenuButton_<?php echo $folderDocument['id_document']; ?>"
                                                                        data-toggle="dropdown" aria-haspopup="true"
                                                                        aria-expanded="false"
                                                                        style="background-color: transparent; border: none;">
                                                                        <i class="fas fa-ellipsis-v"
                                                                            style="color: black; background-color: transparent;"></i>
                                                                    </button>
                                                                    <div class="dropdown-menu dropdown-menu-right"
                                                                        aria-labelledby="dropdownMenuButton_<?php echo $folderDocument['id_document']; ?>">
                                                                        <!--NUEVO CÓDIGO / MUESTRA EL DOCUMENTO EN OTRA VENTANA-->
                                                                        <?php
                                                                        // CUANDO ES UN PDF
                                                                        if ($folderDocument['file_extension_document'] === 'pdf' || $folderDocument['file_extension_document'] === 'PDF') { ?>
                                                                            <!--CÓDIGO NUEVO-->
                                                                            <a class="dropdown-item"
                                                                                href="extensions/open_pdf.php?folder=<?php echo urlencode($folderDocument['key_folder']); ?>&file=<?php echo urlencode($folderDocument['file_name_document']); ?>"
                                                                                target='_blank'>
                                                                                <!--// CÓDIGO ANTIGUO - DESARROLLO EN UN SERVER-->
                                                                                <!--<a class="dropdown-item" href="<?php echo "../../uploads/documents/" . $folderDocument['key_folder'] . "/" . $folderDocument['file_name_document']; ?>" target='_blank'>-->
                                                                                <i class="fas fa-eye"></i> Mostrar documento
                                                                            </a>

                                                                            <!--ORIGINAL (DESPLEGABA EL MODAL DE EDITAR DOCUMENTO)-->
                                                                            <!--
                                                                                        <a class="dropdown-item" href="#" data-document-id="<?php //echo $folderDocument['id_document']; ?>">
                                                                                            <i class="fas fa-eye"></i> Consultar detalles
                                                                                        </a>
                                                                                    -->
                                                                        <?php } ?>

                                                                        <!--CÓDIGO NUEVO-->
                                                                        <a class="dropdown-item"
                                                                            href="extensions/download_pdf.php?folder=<?php echo urlencode($folderDocument['key_folder']); ?>&file=<?php echo urlencode($folderDocument['file_name_document']); ?>"
                                                                            download>
                                                                            <!--// CÓDIGO ANTIGUO - DESARROLLO EN UN SERVER-->
                                                                            <!--<a class="dropdown-item" href="<?php echo "../../uploads/documents/" . $folderDocument['key_folder'] . "/" . $folderDocument['file_name_document']; ?>" download>-->
                                                                            <i class="fas fa-download"></i> Descargar
                                                                        </a>
                                                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->

                                                                        <hr>
                                                                        <form action="#" method="POST">
                                                                            <input name="deleteDocument[id_document]"
                                                                                type="text" class="form-control form-control-sm"
                                                                                id="id_document"
                                                                                value="<?php echo $folderDocument['id_document']; ?>"
                                                                                readonly hidden style="display: none;">
                                                                            <input name="deleteDocument[key_document]"
                                                                                type="text" class="form-control form-control-sm"
                                                                                id="key_document"
                                                                                value="<?php echo $folderDocument['key_document']; ?>"
                                                                                readonly hidden style="display: none;">
                                                                            <button class="dropdown-item" type="submit"
                                                                                name="action" value="deleteDocument"
                                                                                onclick="return confirm('¿Estás seguro de eliminar el documento?');">
                                                                                <i class="fas fa-trash"></i> Mover a la papelera
                                                                            </button>
                                                                        </form>

                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php } ?>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    </div>




    <!-- Modal para agregar nuevo cliente -->
    <div class="modal fade" id="modalAgregarCliente" tabindex="-1" aria-labelledby="modalAgregarClienteLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarCarpetaLabel">Agregar nuevo cliente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Formulario para agregar cliente -->
                    <form id="formAgregarCliente" action="subfolder.php" method="POST">
                        <input name="folder[id_user_folder]" type="hidden"
                            value="<?php echo $_SESSION['user']['id_user']; ?>">
                        <input name="folder[key_folder]" type="hidden" value="CLI-<?php echo $clave; ?>">


                        <!-- Select para tipo de persona -->
                        <div class="form-group">
                            <label for="tipo_persona">Tipo de persona: <span style="color: red;">*</span></label>
                            <select name="folder[tipo_persona]" id="tipo_persona" class="form-control" required>
                                <option value="">-- Seleccionar tipo --</option>
                                <option value="fisica">Persona Física</option>
                                <option value="moral">Persona Moral</option>
                                <option value="fideicomiso">Fideicomiso</option>
                            </select>
                        </div>

                        <!-- SECCIÓN PERSONA FÍSICA -->
                        <div id="seccion_fisica" style="display: none;">
                            <div class="form-section">
                                <h6><i class="fas fa-user"></i> Información de la Persona Física</h6>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="pf_nombre">Nombre: <span style="color: red;">*</span></label>
                                            <input type="text" name="folder[pf_nombre]" class="form-control"
                                                id="pf_nombre" placeholder="Ej. Juan">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="pf_apellido_paterno">Apellido Paterno: <span
                                                    style="color: red;">*</span></label>
                                            <input type="text" name="folder[pf_apellido_paterno]" class="form-control"
                                                id="pf_apellido_paterno" placeholder="Ej. Pérez">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="pf_apellido_materno">Apellido Materno:</label>
                                            <input type="text" name="folder[pf_apellido_materno]" class="form-control"
                                                id="pf_apellido_materno" placeholder="Ej. López">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="pf_rfc">RFC: <span style="color: red;">*</span></label>
                                            <input type="text" name="folder[pf_rfc]" class="form-control" id="pf_rfc"
                                                maxlength="13" placeholder="PEPJ850525AB1">
                                            <small class="text-muted">Formato: 4 letras + 6 números + 3
                                                caracteres</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="pf_curp">CURP:</label>
                                            <input type="text" name="folder[pf_curp]" class="form-control" id="pf_curp"
                                                maxlength="18" placeholder="PEPJ850525HDFRNS05">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="pf_fecha_nacimiento">Fecha de Nacimiento:</label>
                                            <input type="date" name="folder[pf_fecha_nacimiento]" class="form-control"
                                                id="pf_fecha_nacimiento">
                                        </div>
                                    </div>
                                </div>

                                <h6><i class="fas fa-home"></i> Domicilio Nacional</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pf_estado">Estado:</label>
                                            <input type="text" name="folder[pf_estado]" class="form-control"
                                                id="pf_estado" placeholder="Ej. Tabasco">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pf_ciudad">Ciudad o Población:</label>
                                            <input type="text" name="folder[pf_ciudad]" class="form-control"
                                                id="pf_ciudad" placeholder="Ej. Villahermosa">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pf_colonia">Colonia:</label>
                                            <input type="text" name="folder[pf_colonia]" class="form-control"
                                                id="pf_colonia" placeholder="Ej. Centro">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pf_codigo_postal">Código Postal:</label>
                                            <input type="text" name="folder[pf_codigo_postal]" class="form-control"
                                                id="pf_codigo_postal" maxlength="5" placeholder="86000">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="pf_calle">Calle:</label>
                                            <input type="text" name="folder[pf_calle]" class="form-control"
                                                id="pf_calle" placeholder="Ej. Av. Siempre Viva">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="pf_num_exterior">Núm. Exterior:</label>
                                            <input type="text" name="folder[pf_num_exterior]" class="form-control"
                                                id="pf_num_exterior" placeholder="123">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="pf_num_interior">Núm. Interior:</label>
                                            <input type="text" name="folder[pf_num_interior]" class="form-control"
                                                id="pf_num_interior" placeholder="A">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="pf_telefono">Teléfono:</label>
                                            <input type="tel" name="folder[pf_telefono]" class="form-control"
                                                id="pf_telefono" placeholder="9931234567">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="pf_email">Correo Electrónico:</label>
                                            <input type="email" name="folder[pf_email]" class="form-control"
                                                id="pf_email" placeholder="correo@ejemplo.com">
                                        </div>
                                    </div>
                                </div>

                                <div class="checkbox-section">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            id="pf_tiene_domicilio_extranjero"
                                            name="folder[pf_tiene_domicilio_extranjero]" value="1">
                                        <label class="form-check-label" for="pf_tiene_domicilio_extranjero">
                                            ¿Tiene domicilio extranjero?
                                        </label>
                                    </div>
                                </div>

                                <!-- Domicilio Extranjero PF -->
                                <div id="pf_domicilio_extranjero" class="domicilio-extranjero" style="display: none;">
                                    <h6><i class="fas fa-globe"></i> Domicilio Extranjero</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="pf_pais_origen">País de Origen:</label>
                                                <input type="text" name="folder[pf_pais_origen]" class="form-control"
                                                    id="pf_pais_origen" placeholder="Ej. Estados Unidos">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="pf_estado_extranjero">Estado o Provincia:</label>
                                                <input type="text" name="folder[pf_estado_extranjero]"
                                                    class="form-control" id="pf_estado_extranjero"
                                                    placeholder="Ej. Texas">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="pf_ciudad_extranjero">Ciudad o Población:</label>
                                                <input type="text" name="folder[pf_ciudad_extranjero]"
                                                    class="form-control" id="pf_ciudad_extranjero"
                                                    placeholder="Ej. Houston">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="pf_colonia_extranjero">Colonia del Extranjero:</label>
                                                <input type="text" name="folder[pf_colonia_extranjero]"
                                                    class="form-control" id="pf_colonia_extranjero">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="pf_calle_extranjero">Calle del Extranjero:</label>
                                                <input type="text" name="folder[pf_calle_extranjero]"
                                                    class="form-control" id="pf_calle_extranjero">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="pf_num_exterior_ext">Núm. Exterior (Ext):</label>
                                                <input type="text" name="folder[pf_num_exterior_ext]"
                                                    class="form-control" id="pf_num_exterior_ext">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="pf_num_interior_ext">Núm. Interior (Ext):</label>
                                                <input type="text" name="folder[pf_num_interior_ext]"
                                                    class="form-control" id="pf_num_interior_ext">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="pf_codigo_postal_ext">Código Postal (Ext):</label>
                                                <input type="text" name="folder[pf_codigo_postal_ext]"
                                                    class="form-control" id="pf_codigo_postal_ext">
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>

                        <!-- SECCIÓN PERSONA MORAL -->
                        <div id="seccion_moral" style="display: none;">
                            <div class="form-section">
                                <h6><i class="fas fa-building"></i> Información de la Persona Moral</h6>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="pm_razon_social">Razón Social: <span
                                                    style="color: red;">*</span></label>
                                            <input type="text" name="folder[pm_razon_social]" class="form-control"
                                                id="pm_razon_social" placeholder="Ej. Empresa SA de CV">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pm_rfc">RFC Persona Moral: <span
                                                    style="color: red;">*</span></label>
                                            <input type="text" name="folder[pm_rfc]" class="form-control" id="pm_rfc"
                                                maxlength="12" placeholder="EMP850525ABC">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pm_fecha_constitucion">Fecha de Constitución:</label>
                                            <input type="date" name="folder[pm_fecha_constitucion]" class="form-control"
                                                id="pm_fecha_constitucion">
                                        </div>
                                    </div>
                                </div>

                                <h6><i class="fas fa-user-tie"></i> Apoderado Legal</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pm_apoderado_nombre">Nombre:</label>
                                            <input type="text" name="folder[pm_apoderado_nombre]" class="form-control"
                                                id="pm_apoderado_nombre">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pm_apoderado_paterno">Apellido Paterno:</label>
                                            <input type="text" name="folder[pm_apoderado_paterno]" class="form-control"
                                                id="pm_apoderado_paterno">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pm_apoderado_materno">Apellido Materno:</label>
                                            <input type="text" name="folder[pm_apoderado_materno]" class="form-control"
                                                id="pm_apoderado_materno">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pm_apoderado_fecha_nacimiento">Fecha de nacimiento de
                                                representante legal:</label>
                                            <input type="date" name="folder[pm_apoderado_fecha_nacimiento]"
                                                class="form-control" id="pm_apoderado_fecha_nacimiento">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="pm_apoderado_rfc">RFC Apoderado Legal:</label>
                                            <input type="text" name="folder[pm_apoderado_rfc]" class="form-control"
                                                id="pm_apoderado_rfc" maxlength="13">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="pm_apoderado_curp">CURP Apoderado Legal:</label>
                                            <input type="text" name="folder[pm_apoderado_curp]" class="form-control"
                                                id="pm_apoderado_curp" maxlength="18">
                                        </div>
                                    </div>
                                </div>

                                <h6><i class="fas fa-home"></i> Domicilio Nacional</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pm_estado">Estado:</label>
                                            <input type="text" name="folder[pm_estado]" class="form-control"
                                                id="pm_estado">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pm_ciudad">Ciudad o Población:</label>
                                            <input type="text" name="folder[pm_ciudad]" class="form-control"
                                                id="pm_ciudad">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pm_colonia">Colonia:</label>
                                            <input type="text" name="folder[pm_colonia]" class="form-control"
                                                id="pm_colonia">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="pm_codigo_postal">Código Postal:</label>
                                            <input type="text" name="folder[pm_codigo_postal]" class="form-control"
                                                id="pm_codigo_postal" maxlength="5">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="pm_calle">Calle:</label>
                                            <input type="text" name="folder[pm_calle]" class="form-control"
                                                id="pm_calle">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="pm_num_exterior">Núm. Exterior:</label>
                                            <input type="text" name="folder[pm_num_exterior]" class="form-control"
                                                id="pm_num_exterior">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="pm_num_interior">Núm. Interior:</label>
                                            <input type="text" name="folder[pm_num_interior]" class="form-control"
                                                id="pm_num_interior">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="pm_telefono">Teléfono:</label>
                                            <input type="tel" name="folder[pm_telefono]" class="form-control"
                                                id="pm_telefono">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="pm_email">Correo Electrónico:</label>
                                            <input type="email" name="folder[pm_email]" class="form-control"
                                                id="pm_email">
                                        </div>
                                    </div>
                                </div>

                                <div class="checkbox-section">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            id="pm_tiene_domicilio_extranjero"
                                            name="folder[pm_tiene_domicilio_extranjero]" value="1">
                                        <label class="form-check-label" for="pm_tiene_domicilio_extranjero">
                                            ¿Tiene domicilio extranjero?
                                        </label>
                                    </div>
                                </div>

                                <!-- Domicilio Extranjero PM -->
                                <div id="pm_domicilio_extranjero" class="domicilio-extranjero" style="display: none;">
                                    <h6><i class="fas fa-globe"></i> Domicilio Extranjero</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="pm_pais_origen">País de Origen:</label>
                                                <input type="text" name="folder[pm_pais_origen]" class="form-control"
                                                    id="pm_pais_origen">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="pm_estado_extranjero">Estado o Provincia:</label>
                                                <input type="text" name="folder[pm_estado_extranjero]"
                                                    class="form-control" id="pm_estado_extranjero">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="pm_ciudad_extranjero">Ciudad o Población:</label>
                                                <input type="text" name="folder[pm_ciudad_extranjero]"
                                                    class="form-control" id="pm_ciudad_extranjero">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="pm_colonia_extranjero">Colonia del Extranjero:</label>
                                                <input type="text" name="folder[pm_colonia_extranjero]"
                                                    class="form-control" id="pm_colonia_extranjero">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="pm_calle_extranjero">Calle del Extranjero:</label>
                                                <input type="text" name="folder[pm_calle_extranjero]"
                                                    class="form-control" id="pm_calle_extranjero">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="pm_num_exterior_ext">Núm. Exterior (Ext):</label>
                                                <input type="text" name="folder[pm_num_exterior_ext]"
                                                    class="form-control" id="pm_num_exterior_ext">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="pm_num_interior_ext">Núm. Interior (Ext):</label>
                                                <input type="text" name="folder[pm_num_interior_ext]"
                                                    class="form-control" id="pm_num_interior_ext">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="pm_codigo_postal_ext">Código Postal Extranjero:</label>
                                                <input type="text" name="folder[pm_codigo_postal_ext]"
                                                    class="form-control" id="pm_codigo_postal_ext">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN FIDEICOMISO -->
                        <div id="seccion_fideicomiso" style="display: none;">
                            <div class="form-section">
                                <h6><i class="fas fa-handshake"></i> Información del Fideicomiso</h6>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fid_razon_social">Razón Social del Fiduciario: <span
                                                    style="color: red;">*</span></label>
                                            <input type="text" name="folder[fid_razon_social]" class="form-control"
                                                id="fid_razon_social" placeholder="Ej. Banco Fiduciario SA">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fid_rfc">RFC del Fiduciario: <span
                                                    style="color: red;">*</span></label>
                                            <input type="text" name="folder[fid_rfc]" class="form-control" id="fid_rfc"
                                                maxlength="12" placeholder="BFI850525ABC">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fid_numero_referencia">Número / Referencia de
                                                Fideicomiso:</label>
                                            <input type="text" name="folder[fid_numero_referencia]" class="form-control"
                                                id="fid_numero_referencia" placeholder="FID-12345">
                                        </div>
                                    </div>
                                </div>

                                <h6><i class="fas fa-user-tie"></i> Apoderado Legal</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fid_apoderado_nombre">Nombre:</label>
                                            <input type="text" name="folder[fid_apoderado_nombre]" class="form-control"
                                                id="fid_apoderado_nombre">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fid_apoderado_paterno">Apellido Paterno:</label>
                                            <input type="text" name="folder[fid_apoderado_paterno]" class="form-control"
                                                id="fid_apoderado_paterno">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fid_apoderado_materno">Apellido Materno:</label>
                                            <input type="text" name="folder[fid_apoderado_materno]" class="form-control"
                                                id="fid_apoderado_materno">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fid_apoderado_fecha_nacimiento">Fecha de nacimiento de
                                                representante legal:</label>
                                            <input type="date" name="folder[fid_apoderado_fecha_nacimiento]"
                                                class="form-control" id="fid_apoderado_fecha_nacimiento">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fid_apoderado_rfc">RFC Apoderado Legal:</label>
                                            <input type="text" name="folder[fid_apoderado_rfc]" class="form-control"
                                                id="fid_apoderado_rfc" maxlength="13">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fid_apoderado_curp">CURP Apoderado Legal:</label>
                                            <input type="text" name="folder[fid_apoderado_curp]" class="form-control"
                                                id="fid_apoderado_curp" maxlength="18">
                                        </div>
                                    </div>
                                </div>

                                <h6><i class="fas fa-home"></i> Domicilio Nacional</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fid_estado">Estado:</label>
                                            <input type="text" name="folder[fid_estado]" class="form-control"
                                                id="fid_estado">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fid_ciudad">Ciudad o Población:</label>
                                            <input type="text" name="folder[fid_ciudad]" class="form-control"
                                                id="fid_ciudad">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fid_colonia">Colonia:</label>
                                            <input type="text" name="folder[fid_colonia]" class="form-control"
                                                id="fid_colonia">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fid_codigo_postal">Código Postal:</label>
                                            <input type="text" name="folder[fid_codigo_postal]" class="form-control"
                                                id="fid_codigo_postal" maxlength="5">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fid_calle">Calle:</label>
                                            <input type="text" name="folder[fid_calle]" class="form-control"
                                                id="fid_calle">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="fid_num_exterior">Núm. Exterior:</label>
                                            <input type="text" name="folder[fid_num_exterior]" class="form-control"
                                                id="fid_num_exterior">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="fid_num_interior">Núm. Interior:</label>
                                            <input type="text" name="folder[fid_num_interior]" class="form-control"
                                                id="fid_num_interior">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="fid_telefono">Teléfono:</label>
                                            <input type="tel" name="folder[fid_telefono]" class="form-control"
                                                id="fid_telefono">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="fid_email">Correo Electrónico:</label>
                                            <input type="email" name="folder[fid_email]" class="form-control"
                                                id="fid_email">
                                        </div>
                                    </div>
                                </div>

                                <div class="checkbox-section">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            id="fid_tiene_domicilio_extranjero"
                                            name="folder[fid_tiene_domicilio_extranjero]" value="1">
                                        <label class="form-check-label" for="fid_tiene_domicilio_extranjero">
                                            ¿Tiene domicilio extranjero?
                                        </label>
                                    </div>
                                </div>

                                <!-- Domicilio Extranjero Fideicomiso -->
                                <div id="fid_domicilio_extranjero" class="domicilio-extranjero" style="display: none;">
                                    <h6><i class="fas fa-globe"></i> Domicilio Extranjero</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="fid_pais_origen">País de Origen:</label>
                                                <input type="text" name="folder[fid_pais_origen]" class="form-control"
                                                    id="fid_pais_origen">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="fid_estado_extranjero">Estado o Provincia:</label>
                                                <input type="text" name="folder[fid_estado_extranjero]"
                                                    class="form-control" id="fid_estado_extranjero">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="fid_ciudad_extranjero">Ciudad o Población:</label>
                                                <input type="text" name="folder[fid_ciudad_extranjero]"
                                                    class="form-control" id="fid_ciudad_extranjero">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="fid_colonia_extranjero">Colonia del Extranjero:</label>
                                                <input type="text" name="folder[fid_colonia_extranjero]"
                                                    class="form-control" id="fid_colonia_extranjero">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="fid_calle_extranjero">Calle del Extranjero:</label>
                                                <input type="text" name="folder[fid_calle_extranjero]"
                                                    class="form-control" id="fid_calle_extranjero">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="fid_num_exterior_ext">Núm. Exterior (Ext):</label>
                                                <input type="text" name="folder[fid_num_exterior_ext]"
                                                    class="form-control" id="fid_num_exterior_ext">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="fid_num_interior_ext">Núm. Interior (Ext):</label>
                                                <input type="text" name="folder[fid_num_interior_ext]"
                                                    class="form-control" id="fid_num_interior_ext">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="fid_codigo_postal_ext">Código Postal Extranjero:</label>
                                                <input type="text" name="folder[fid_codigo_postal_ext]"
                                                    class="form-control" id="fid_codigo_postal_ext">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- Sección para Plazo de Vigencia y Checkboxes -->
                        <div class="row">
                            <div class="col-12">
                                <label>Plazo de vigencia <small style="color:red;">(*Plazo opcional)</small></label>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <input type="date" class="form-control" name="folder[first_fech_folder]"
                                        id="edit_first_fech_folder">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <input type="date" class="form-control" name="folder[second_fech_folder]"
                                        id="edit_second_fech_folder">
                                </div>
                            </div>
                        </div>

                        <!-- Checkboxes organizados en dos filas -->
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_chk_alta_fact_folder"
                                        value="Si" name="folder[chk_alta_fact_folder]">
                                    <label class="form-check-label" for="edit_chk_alta_fact_folder">Vo.Bo. Alta
                                        Facturación</label>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_chk_lib_folder" value="Si"
                                        name="folder[chk_lib_folder]">
                                    <label class="form-check-label" for="edit_chk_lib_folder">Vo.Bo. Liberación</label>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_chk_orig_recib_folder"
                                        value="Si" name="folder[chk_orig_recib_folder]">
                                    <label class="form-check-label" for="edit_chk_orig_recib_folder">Original
                                        Recibido</label>
                                </div>
                            </div>
                        </div>

                        <div id="edit-fecha-original-recibido" style="display: none;" class="form-group"
                            style="margin-top:15px;">
                            <label for="edit_fech_orig_recib_folder">Fecha de original recibido:</label>
                            <input type="date" class="form-control" name="folder[fech_orig_recib_folder]"
                                id="edit_fech_orig_recib_folder">
                        </div>


                        <!-- Botones del formulario -->
                        <div class="form-group mt-4">
                            <button type="submit" name="action" value="add" class="btn btn-primary"
                                id="btnGuardarCliente">
                                <i class="fas fa-save"></i> Guardar Cliente
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal para agregar una nueva carpeta -->
    <div class="modal fade" id="modalAgregarCarpeta" tabindex="-1" aria-labelledby="modalAgregarCarpetaLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarCarpetaLabel">Agregar nuevo expediente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O VENTAS (3)-->
                    <?php if ($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3) { ?>
                        <!-- Formulario para agregar una nueva carpeta -->
                        <form id="formAgregarCarpeta" action="#" method="POST">
                            <input name="newFolder[id_user_folder]" type="hidden" class="form-control" id="id_user_folder"
                                required value="<?php echo $_SESSION['user']['id_user']; ?>" readonly style="display:none;"
                                hidden>
                            <input name="newFolder[fk_folder]" type="hidden" class="form-control" id="fk_folder" required
                                value="<?php echo $folder['id_folder']; ?>" readonly style="display:none;" hidden>
                            <input name="newFolder[key_folder]" type="hidden" class="form-control" id="key_folder" required
                                value="CARP-<?php echo $clave; ?>" readonly style="display:none;" hidden>

                            <div class="form-group">
                                <label for="name_folder">Nombre del expediente:</label>
                                <input type="text" name="newFolder[name_folder]" class="form-control" id="name_folder"
                                    required autocomplete="off">
                            </div>

                            <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                            <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>

                                <div class="row">
                                    <div class="col-12">
                                        <label>Plazo de vigencia <small style="color:red;">(*Plazo opcional)</small></label>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <input type="date" class="form-control" name="newFolder[first_fech_folder]">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <input type="date" class="form-control" name="newFolder[second_fech_folder]">
                                        </div>
                                    </div>
                                </div>

                                <!-- Checkboxes organizados en dos filas -->
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="opcion1" value="Si"
                                                name="newFolder[chk_alta_fact_folder]">
                                            <label class="form-check-label" for="opcion1">Vo.Bo. Alta Facturación</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="opcion2" value="Si"
                                                name="newFolder[chk_lib_folder]">
                                            <label class="form-check-label" for="opcion2">Vo.Bo. Liberación</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="opcion3" value="Si"
                                                name="newFolder[chk_orig_recib_folder]">
                                            <label class="form-check-label" for="opcion3">Original Recibido</label>
                                        </div>
                                    </div>
                                </div>

                                <div id="fecha-original-recibido" class="form-group" style="margin-top:15px;">
                                    <label>Fecha de original recibido:</label>
                                    <input type="date" class="form-control" name="newFolder[fech_orig_recib_folder]">
                                </div>

                            <?php } ?>

                            <button type="submit" class="btn btn-lg btn-block"
                                style="background-color: #37424A; color: #ffffff;" name="action"
                                value="createFolder">Guardar</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal para editar / actualizar cliente -->
    <div class="modal fade" id="modalEditarCarpeta" tabindex="-1" aria-labelledby="modalEditarCarpetaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarCarpetaLabel">Editar cliente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O VENTAS (3)-->
                    <?php if ($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3) { ?>

                        <!-- Formulario para editar cliente -->
                        <form id="formEditarCarpeta" action="subfolder.php" method="POST">
                            <input type="hidden" name="updateFolder[id_folder]" id="edit_folder_id">
                            <input type="hidden" name="updateFolder[id_user_folder]"
                                value="<?php echo $_SESSION['user']['id_user']; ?>">


                            <!-- Select para tipo de persona (deshabilitado en edición) -->
                            <div class="form-group">
                                <label for="edit_tipo_persona">Tipo de persona:</label>
                                <select name="updateFolder[tipo_persona]" id="edit_tipo_persona" class="form-control"
                                    disabled>
                                    <option value="fisica">Persona Física</option>
                                    <option value="moral">Persona Moral</option>
                                    <option value="fideicomiso">Fideicomiso</option>
                                </select>
                                <input type="hidden" name="updateFolder[tipo_persona]" id="edit_tipo_persona_hidden">
                                <small class="text-muted">El tipo de persona no se puede cambiar una vez creado el
                                    cliente.</small>
                            </div>

                            <!-- SECCIÓN PERSONA FÍSICA -->
                            <div id="edit_seccion_fisica" style="display: none;">
                                <div class="form-section">
                                    <h6><i class="fas fa-user"></i> Información de la Persona Física</h6>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_pf_nombre">Nombre: <span
                                                        style="color: red;">*</span></label>
                                                <input type="text" name="updateFolder[pf_nombre]" class="form-control"
                                                    id="edit_pf_nombre">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_pf_apellido_paterno">Apellido Paterno: <span
                                                        style="color: red;">*</span></label>
                                                <input type="text" name="updateFolder[pf_apellido_paterno]"
                                                    class="form-control" id="edit_pf_apellido_paterno">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_pf_apellido_materno">Apellido Materno:</label>
                                                <input type="text" name="updateFolder[pf_apellido_materno]"
                                                    class="form-control" id="edit_pf_apellido_materno">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_pf_rfc">RFC: <span style="color: red;">*</span></label>
                                                <input type="text" name="updateFolder[pf_rfc]" class="form-control"
                                                    id="edit_pf_rfc" maxlength="13">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_pf_curp">CURP:</label>
                                                <input type="text" name="updateFolder[curp_folder]" class="form-control"
                                                    id="edit_pf_curp" maxlength="18">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_pf_fecha_nacimiento">Fecha de Nacimiento:</label>
                                                <input type="date" name="updateFolder[pf_fecha_nacimiento]"
                                                    class="form-control" id="edit_pf_fecha_nacimiento">
                                            </div>
                                        </div>
                                    </div>

                                    <h6><i class="fas fa-home"></i> Domicilio Nacional</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pf_estado">Estado:</label>
                                                <input type="text" name="updateFolder[pf_estado]" class="form-control"
                                                    id="edit_pf_estado">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pf_ciudad">Ciudad o Población:</label>
                                                <input type="text" name="updateFolder[pf_ciudad]" class="form-control"
                                                    id="edit_pf_ciudad">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pf_colonia">Colonia:</label>
                                                <input type="text" name="updateFolder[pf_colonia]" class="form-control"
                                                    id="edit_pf_colonia">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pf_codigo_postal">Código Postal:</label>
                                                <input type="text" name="updateFolder[pf_codigo_postal]"
                                                    class="form-control" id="edit_pf_codigo_postal" maxlength="5">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_pf_calle">Calle:</label>
                                                <input type="text" name="updateFolder[pf_calle]" class="form-control"
                                                    id="edit_pf_calle">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_pf_num_exterior">Núm. Exterior:</label>
                                                <input type="text" name="updateFolder[pf_num_exterior]" class="form-control"
                                                    id="edit_pf_num_exterior">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_pf_num_interior">Núm. Interior:</label>
                                                <input type="text" name="updateFolder[pf_num_interior]" class="form-control"
                                                    id="edit_pf_num_interior">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_pf_telefono">Teléfono:</label>
                                                <input type="tel" name="updateFolder[pf_telefono]" class="form-control"
                                                    id="edit_pf_telefono">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_pf_email">Correo Electrónico:</label>
                                                <input type="email" name="updateFolder[pf_email]" class="form-control"
                                                    id="edit_pf_email">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="checkbox-section">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                id="edit_pf_tiene_domicilio_extranjero"
                                                name="updateFolder[pf_tiene_domicilio_extranjero]" value=1>
                                            <label class="form-check-label" for="edit_pf_tiene_domicilio_extranjero">
                                                ¿Tiene domicilio extranjero?
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Domicilio Extranjero PF -->
                                    <div id="edit_pf_domicilio_extranjero" class="domicilio-extranjero"
                                        style="display: none;">
                                        <h6><i class="fas fa-globe"></i> Domicilio Extranjero</h6>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_pf_pais_origen">País de Origen:</label>
                                                    <input type="text" name="updateFolder[pf_pais_origen]"
                                                        class="form-control" id="edit_pf_pais_origen">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_pf_estado_extranjero">Estado o Provincia:</label>
                                                    <input type="text" name="updateFolder[pf_estado_extranjero]"
                                                        class="form-control" id="edit_pf_estado_extranjero">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_pf_ciudad_extranjero">Ciudad o Población:</label>
                                                    <input type="text" name="updateFolder[pf_ciudad_extranjero]"
                                                        class="form-control" id="edit_pf_ciudad_extranjero">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_pf_colonia_extranjero">Colonia del Extranjero:</label>
                                                    <input type="text" name="updateFolder[pf_colonia_extranjero]"
                                                        class="form-control" id="edit_pf_colonia_extranjero">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_pf_calle_extranjero">Calle del Extranjero:</label>
                                                    <input type="text" name="updateFolder[pf_calle_extranjero]"
                                                        class="form-control" id="edit_pf_calle_extranjero">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="edit_pf_num_exterior_ext">Núm. Exterior (Ext):</label>
                                                    <input type="text" name="updateFolder[pf_num_exterior_ext]"
                                                        class="form-control" id="edit_pf_num_exterior_ext">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="edit_pf_num_interior_ext">Núm. Interior (Ext):</label>
                                                    <input type="text" name="updateFolder[pf_num_interior_ext]"
                                                        class="form-control" id="edit_pf_num_interior_ext">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="edit_pf_codigo_postal_ext">Código Postal Extranjero:</label>
                                                    <input type="text" name="updateFolder[pf_codigo_postal_ext]"
                                                        class="form-control" id="edit_pf_codigo_postal_ext">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN PERSONA MORAL -->
                            <div id="edit_seccion_moral" style="display: none;">
                                <div class="form-section">
                                    <h6><i class="fas fa-building"></i> Información de la Persona Moral</h6>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="edit_pm_razon_social">Razón Social: <span
                                                        style="color: red;">*</span></label>
                                                <input type="text" name="updateFolder[pm_razon_social]" class="form-control"
                                                    id="edit_pm_razon_social">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pm_rfc">RFC Persona Moral: <span
                                                        style="color: red;">*</span></label>
                                                <input type="text" name="updateFolder[pm_rfc]" class="form-control"
                                                    id="edit_pm_rfc" maxlength="12">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pm_fecha_constitucion">Fecha de Constitución:</label>
                                                <input type="date" name="updateFolder[pm_fecha_constitucion]"
                                                    class="form-control" id="edit_pm_fecha_constitucion">
                                            </div>
                                        </div>
                                    </div>

                                    <h6><i class="fas fa-user-tie"></i> Apoderado Legal</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pm_apoderado_nombre">Nombre:</label>
                                                <input type="text" name="updateFolder[pm_apoderado_nombre]"
                                                    class="form-control" id="edit_pm_apoderado_nombre">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pm_apoderado_paterno">Apellido Paterno:</label>
                                                <input type="text" name="updateFolder[pm_apoderado_paterno]"
                                                    class="form-control" id="edit_pm_apoderado_paterno">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pm_apoderado_materno">Apellido Materno:</label>
                                                <input type="text" name="updateFolder[pm_apoderado_materno]"
                                                    class="form-control" id="edit_pm_apoderado_materno">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pm_apoderado_fecha_nacimiento">Fecha de nacimiento:</label>
                                                <input type="date" name="updateFolder[pm_apoderado_fecha_nacimiento]"
                                                    class="form-control" id="edit_pm_apoderado_fecha_nacimiento">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="edit_pm_apoderado_rfc">RFC Apoderado Legal:</label>
                                                <input type="text" name="updateFolder[pm_apoderado_rfc]"
                                                    class="form-control" id="edit_pm_apoderado_rfc" maxlength="13">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="edit_pm_apoderado_curp">CURP Apoderado Legal:</label>
                                                <input type="text" name="updateFolder[pm_apoderado_curp]"
                                                    class="form-control" id="edit_pm_apoderado_curp" maxlength="18">
                                            </div>
                                        </div>
                                    </div>

                                    <h6><i class="fas fa-home"></i> Domicilio Nacional</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pm_estado">Estado:</label>
                                                <input type="text" name="updateFolder[pm_estado]" class="form-control"
                                                    id="edit_pm_estado">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pm_ciudad">Ciudad o Población:</label>
                                                <input type="text" name="updateFolder[pm_ciudad]" class="form-control"
                                                    id="edit_pm_ciudad">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pm_colonia">Colonia:</label>
                                                <input type="text" name="updateFolder[pm_colonia]" class="form-control"
                                                    id="edit_pm_colonia">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_pm_codigo_postal">Código Postal:</label>
                                                <input type="text" name="updateFolder[pm_codigo_postal]"
                                                    class="form-control" id="edit_pm_codigo_postal" maxlength="5">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_pm_calle">Calle:</label>
                                                <input type="text" name="updateFolder[pm_calle]" class="form-control"
                                                    id="edit_pm_calle">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_pm_num_exterior">Núm. Exterior:</label>
                                                <input type="text" name="updateFolder[pm_num_exterior]" class="form-control"
                                                    id="edit_pm_num_exterior">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_pm_num_interior">Núm. Interior:</label>
                                                <input type="text" name="updateFolder[pm_num_interior]" class="form-control"
                                                    id="edit_pm_num_interior">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_pm_telefono">Teléfono:</label>
                                                <input type="tel" name="updateFolder[pm_telefono]" class="form-control"
                                                    id="edit_pm_telefono">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_pm_email">Correo Electrónico:</label>
                                                <input type="email" name="updateFolder[pm_email]" class="form-control"
                                                    id="edit_pm_email">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="checkbox-section">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                id="edit_pm_tiene_domicilio_extranjero"
                                                name="updateFolder[pm_tiene_domicilio_extranjero]" value=1>
                                            <label class="form-check-label" for="edit_pm_tiene_domicilio_extranjero">
                                                ¿Tiene domicilio extranjero?
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Domicilio Extranjero PM -->
                                    <div id="edit_pm_domicilio_extranjero" class="domicilio-extranjero"
                                        style="display: none;">
                                        <h6><i class="fas fa-globe"></i> Domicilio Extranjero</h6>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_pm_pais_origen">País de Origen:</label>
                                                    <input type="text" name="updateFolder[pm_pais_origen]"
                                                        class="form-control" id="edit_pm_pais_origen">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_pm_estado_extranjero">Estado o Provincia:</label>
                                                    <input type="text" name="updateFolder[pm_estado_extranjero]"
                                                        class="form-control" id="edit_pm_estado_extranjero">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_pm_ciudad_extranjero">Ciudad o Población:</label>
                                                    <input type="text" name="updateFolder[pm_ciudad_extranjero]"
                                                        class="form-control" id="edit_pm_ciudad_extranjero">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_pm_colonia_extranjero">Colonia del Extranjero:</label>
                                                    <input type="text" name="updateFolder[pm_colonia_extranjero]"
                                                        class="form-control" id="edit_pm_colonia_extranjero">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_pm_calle_extranjero">Calle del Extranjero:</label>
                                                    <input type="text" name="updateFolder[pm_calle_extranjero]"
                                                        class="form-control" id="edit_pm_calle_extranjero">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="edit_pm_num_exterior_ext">Núm. Exterior (Ext):</label>
                                                    <input type="text" name="updateFolder[pm_num_exterior_ext]"
                                                        class="form-control" id="edit_pm_num_exterior_ext">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="edit_pm_num_interior_ext">Núm. Interior (Ext):</label>
                                                    <input type="text" name="updateFolder[pm_num_interior_ext]"
                                                        class="form-control" id="edit_pm_num_interior_ext">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="edit_pm_codigo_postal_ext">Código Postal Extranjero:</label>
                                                    <input type="text" name="updateFolder[pm_codigo_postal_ext]"
                                                        class="form-control" id="edit_pm_codigo_postal_ext">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN FIDEICOMISO -->
                            <div id="edit_seccion_fideicomiso" style="display: none;">
                                <div class="form-section">
                                    <h6><i class="fas fa-handshake"></i> Información del Fideicomiso</h6>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_fid_razon_social">Razón Social del Fiduciario: <span
                                                        style="color: red;">*</span></label>
                                                <input type="text" name="updateFolder[fid_razon_social]"
                                                    class="form-control" id="edit_fid_razon_social">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_fid_rfc">RFC del Fiduciario: <span
                                                        style="color: red;">*</span></label>
                                                <input type="text" name="updateFolder[fid_rfc]" class="form-control"
                                                    id="edit_fid_rfc" maxlength="12">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_fid_numero_referencia">Número / Referencia de
                                                    Fideicomiso:</label>
                                                <input type="text" name="updateFolder[fid_numero_referencia]"
                                                    class="form-control" id="edit_fid_numero_referencia">
                                            </div>
                                        </div>
                                    </div>

                                    <h6><i class="fas fa-user-tie"></i> Apoderado Legal</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_fid_apoderado_nombre">Nombre:</label>
                                                <input type="text" name="updateFolder[fid_apoderado_nombre]"
                                                    class="form-control" id="edit_fid_apoderado_nombre">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_fid_apoderado_paterno">Apellido Paterno:</label>
                                                <input type="text" name="updateFolder[fid_apoderado_paterno]"
                                                    class="form-control" id="edit_fid_apoderado_paterno">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_fid_apoderado_materno">Apellido Materno:</label>
                                                <input type="text" name="updateFolder[fid_apoderado_materno]"
                                                    class="form-control" id="edit_fid_apoderado_materno">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_fid_apoderado_fecha_nacimiento">Fecha de
                                                    nacimiento:</label>
                                                <input type="date" name="updateFolder[fid_apoderado_fecha_nacimiento]"
                                                    class="form-control" id="edit_fid_apoderado_fecha_nacimiento">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="edit_fid_apoderado_rfc">RFC Apoderado Legal:</label>
                                                <input type="text" name="updateFolder[fid_apoderado_rfc]"
                                                    class="form-control" id="edit_fid_apoderado_rfc" maxlength="13">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="edit_fid_apoderado_curp">CURP Apoderado Legal:</label>
                                                <input type="text" name="updateFolder[fid_apoderado_curp]"
                                                    class="form-control" id="edit_fid_apoderado_curp" maxlength="18">
                                            </div>
                                        </div>
                                    </div>

                                    <h6><i class="fas fa-home"></i> Domicilio Nacional</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_fid_estado">Estado:</label>
                                                <input type="text" name="updateFolder[fid_estado]" class="form-control"
                                                    id="edit_fid_estado">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_fid_ciudad">Ciudad o Población:</label>
                                                <input type="text" name="updateFolder[fid_ciudad]" class="form-control"
                                                    id="edit_fid_ciudad">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_fid_colonia">Colonia:</label>
                                                <input type="text" name="updateFolder[fid_colonia]" class="form-control"
                                                    id="edit_fid_colonia">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="edit_fid_codigo_postal">Código Postal:</label>
                                                <input type="text" name="updateFolder[fid_codigo_postal]"
                                                    class="form-control" id="edit_fid_codigo_postal" maxlength="5">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edit_fid_calle">Calle:</label>
                                                <input type="text" name="updateFolder[fid_calle]" class="form-control"
                                                    id="edit_fid_calle">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_fid_num_exterior">Núm. Exterior:</label>
                                                <input type="text" name="updateFolder[fid_num_exterior]"
                                                    class="form-control" id="edit_fid_num_exterior">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_fid_num_interior">Núm. Interior:</label>
                                                <input type="text" name="updateFolder[fid_num_interior]"
                                                    class="form-control" id="edit_fid_num_interior">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_fid_telefono">Teléfono:</label>
                                                <input type="tel" name="updateFolder[fid_telefono]" class="form-control"
                                                    id="edit_fid_telefono">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="edit_fid_email">Correo Electrónico:</label>
                                                <input type="email" name="updateFolder[fid_email]" class="form-control"
                                                    id="edit_fid_email">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="checkbox-section">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                id="edit_fid_tiene_domicilio_extranjero"
                                                name="updateFolder[fid_tiene_domicilio_extranjero]" value=1>
                                            <label class="form-check-label" for="edit_fid_tiene_domicilio_extranjero">
                                                ¿Tiene domicilio extranjero?
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Domicilio Extranjero Fideicomiso -->
                                    <div id="edit_fid_domicilio_extranjero" class="domicilio-extranjero"
                                        style="display: none;">
                                        <h6><i class="fas fa-globe"></i> Domicilio Extranjero</h6>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_fid_pais_origen">País de Origen:</label>
                                                    <input type="text" name="updateFolder[fid_pais_origen]"
                                                        class="form-control" id="edit_fid_pais_origen">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_fid_estado_extranjero">Estado o Provincia:</label>
                                                    <input type="text" name="updateFolder[fid_estado_extranjero]"
                                                        class="form-control" id="edit_fid_estado_extranjero">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_fid_ciudad_extranjero">Ciudad o Población:</label>
                                                    <input type="text" name="updateFolder[fid_ciudad_extranjero]"
                                                        class="form-control" id="edit_fid_ciudad_extranjero">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="edit_fid_colonia_extranjero">Colonia del Extranjero:</label>
                                                    <input type="text" name="updateFolder[fid_colonia_extranjero]"
                                                        class="form-control" id="edit_fid_colonia_extranjero">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_fid_calle_extranjero">Calle del Extranjero:</label>
                                                    <input type="text" name="updateFolder[fid_calle_extranjero]"
                                                        class="form-control" id="edit_fid_calle_extranjero">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="edit_fid_num_exterior_ext">Núm. Exterior (Ext):</label>
                                                    <input type="text" name="updateFolder[fid_num_exterior_ext]"
                                                        class="form-control" id="edit_fid_num_exterior_ext">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="edit_fid_num_interior_ext">Núm. Interior (Ext):</label>
                                                    <input type="text" name="updateFolder[fid_num_interior_ext]"
                                                        class="form-control" id="edit_fid_num_interior_ext">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="edit_fid_codigo_postal_ext">Código Postal
                                                        Extranjero:</label>
                                                    <input type="text" name="updateFolder[fid_codigo_postal_ext]"
                                                        class="form-control" id="edit_fid_codigo_postal_ext">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CAMPOS GENERALES PARA TODOS LOS TIPOS -->
                            <div class="form-section mt-3">
                                <h6><i class="fas fa-calendar-alt"></i> Información General</h6>

                                <div class="row">
                                    <div class="col-12">
                                        <label>Plazo de vigencia <small style="color:red;">(*Plazo opcional)</small></label>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <input type="date" class="form-control" name="updateFolder[first_fech_folder]"
                                                id="edit_first_fech_folder">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <input type="date" class="form-control" name="updateFolder[second_fech_folder]"
                                                id="edit_second_fech_folder">
                                        </div>
                                    </div>
                                </div>

                                <!-- Checkboxes organizados en dos filas -->
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="edit_chk_alta_fact_folder"
                                                value="Si" name="updateFolder[chk_alta_fact_folder]">
                                            <label class="form-check-label" for="edit_chk_alta_fact_folder">Vo.Bo. Alta
                                                Facturación</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="edit_chk_lib_folder"
                                                value="Si" name="updateFolder[chk_lib_folder]">
                                            <label class="form-check-label" for="edit_chk_lib_folder">Vo.Bo.
                                                Liberación</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="edit_chk_orig_recib_folder"
                                                value="Si" name="updateFolder[chk_orig_recib_folder]">
                                            <label class="form-check-label" for="edit_chk_orig_recib_folder">Original
                                                Recibido</label>
                                        </div>
                                    </div>
                                </div>

                                <div id="edit-fecha-original-recibido" class="form-group"
                                    style="margin-top:15px; display:none;">
                                    <label for="edit_fech_orig_recib_folder">Fecha de original recibido:</label>
                                    <input type="date" class="form-control" name="updateFolder[fech_orig_recib_folder]"
                                        id="edit_fech_orig_recib_folder">
                                </div>
                            </div>

                            <!-- Botones del formulario -->
                            <div class="form-group mt-4">
                                <button type="submit" name="action" value="updateFolder" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Actualizar Cliente
                                </button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            </div>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>






    <!-- ESTE MODAL SI FUNCIONA PERO AHORITA PARA EL SISTEMA NO SE ESTA USANDO, Modal para agregar nuevos documentos -->
    <div class="modal fade" id="modalAgregarDocuments" tabindex="-1" aria-labelledby="modalAgregarDocumentsLabel"
        aria-hidden="true">
        <div class="modal-dialog" style="max-width: 50%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarDocumentsLabel">Agregar documentos</h5>
                    &nbsp;&nbsp;&nbsp;
                    <button type="button" id="agregarDocumentoBtn" class="btn"
                        style="background-color: gray; color: #ffffff; border-radius:40px; margin-top:-5px;"><i
                            class="fas fa-plus"></i></button>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formAgregarDocuments" action="#" method="POST" enctype="multipart/form-data">
                        <div id="inputsContainer" style="display: flex; flex-wrap: wrap; margin-bottom: 5px;">
                            <!-- Filas por defecto -->
                            <div class="input-row col-12" style="display: flex; margin-bottom: 10px;">
                                <input type="file" class="form-control" name="documents[]" accept=".pdf" required>
                                <!--<input type="date" style="margin-right: 10px;" class="form-control" name="first_date[]" required>-->
                                <!--<input type="date" class="form-control" name="second_date[]" required>-->
                            </div>
                            <!-- original -->
                            <!--
                                <div class="input-row" style="display: flex; margin-bottom: 10px;">
                                    <input type="file" style="margin-right: 10px;" class="form-control" name="documents[]" accept=".pdf" required>
                                    <input type="date" style="margin-right: 10px;" class="form-control" name="first_date[]" required>
                                    <input type="date" class="form-control" name="second_date[]" required>
                                </div>
                                -->
                            <hr>
                        </div>
                        <button type="submit" class="btn btn-lg btn-block"
                            style="background-color: #37424A; color: #ffffff;" name="action"
                            value="saveDocuments">Guardar documentos</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para agregar nuevos documentos de manera masiva -->
    <div class="modal fade" id="modalAgregarFullDocuments" tabindex="-1"
        aria-labelledby="modalAgregarFullDocumentsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarFullDocumentsLabel">Agregar documentos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formAgregarFullDocuments" action="#" method="POST" enctype="multipart/form-data">
                        <!--CAMPO DE SELECCIÓN DE ARCHIVOS-->
                        <div class="form-group">
                            <small style="color:red;">*Selecciona uno o varios archivos.</small>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 files color">
                                        <input type="file" class="form-control" id="miarchivo" name="miarchivo[]"
                                            multiple="" required accept=".pdf">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-lg btn-block"
                            style="background-color: #37424A; color: #ffffff;" name="action"
                            value="saveFullDocuments">Guardar documentos</button>
                        <hr>
                        <!-- Contenedor de vista previa de archivos -->
                        <div id="preview-container"
                            style="max-height: 210px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; display: none;">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!--SIN USO (EN ESTE MODAL SE VISUALIZABAN LOS DETALLES DEL DOCUMENTO Y SE PODIA ACTUALIZAR SU FECHA DE VENCIMIENTO)-->
    <!-- Modal para editar / actualizar un documento -->
    <div class="modal fade" id="modalEditarDocument" tabindex="-1" aria-labelledby="modalEditarDocumentLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O VENTAS (3)-->
                    <?php if ($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3) { ?>
                        <h5 class="modal-title" id="modalEditarDocumentLabel">Editar documento</h5>
                    <?php } else { ?>
                        <h5 class="modal-title" id="modalEditarDocumentLabel">Detalles del documento</h5>
                    <?php } ?>


                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Formulario para editar una carpeta -->
                    <form id="formEditarCarpeta" action="#" method="POST">
                        <input type="hidden" class="form-control" name="updateDocument[id_document]"
                            id="edit_id_document" style="display:none;" hidden readonly>
                        <input type="hidden" class="form-control" name="updateDocument[key_document]"
                            id="edit_key_document" style="display:none;" hidden readonly>
                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O VENTAS (3)-->
                        <?php if ($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3) { ?>
                            <div class="form-group">
                                <label for="edit_first_fech_document">Plazo:</label>
                                <input type="date" name="updateDocument[first_fech_document]" class="form-control"
                                    id="edit_first_fech_document" required style="margin-bottom:10px;">
                                <input type="date" name="updateDocument[second_fech_document]" class="form-control"
                                    id="edit_second_fech_document" required>
                            </div>
                        <?php } else { ?>
                            <div class="form-group">
                                <label for="edit_first_fech_document">Plazo:</label>
                                <input type="date" class="form-control" id="edit_first_fech_document" required
                                    style="margin-bottom:10px;" readonly disabled>
                                <input type="date" class="form-control" id="edit_second_fech_document" required readonly
                                    disabled>
                            </div>
                        <?php } ?>


                        <div class="form-group">
                            <label for="edit_name_user">Autor:</label>
                            <input type="text" class="form-control" id="edit_name_user" required readonly disabled>
                        </div>
                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O VENTAS (3)-->
                        <?php if ($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3) { ?>
                            <button type="submit" class="btn btn-lg btn-block"
                                style="background-color: #37424A; color: #ffffff;" name="action"
                                value="updateDocument">Actualizar</button>
                        <?php } ?>
                    </form>
                </div>
            </div>
        </div>
    </div>




    <!-- Modal para agregar un nuevo seguimiento -->
    <!--Atributo para evitar cerrar el modal - data-backdrop="static" -->
    <div class="modal fade" id="modalAddTracing" tabindex="-1" aria-labelledby="modalAddTracingLabel"
        aria-hidden="true">
        <div class="modal-dialog" style="max-width: 50%;">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddTracingLabel">Agregar nuevo seguimiento</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <form id="addFormTracing" action="#" method="POST">
                        <input name="tracing[id_folder_tracing]" type="text" class="form-control" id="id_folder_tracing"
                            required value="<?php echo $folder['id_folder']; ?>" readonly style="display: none;" hidden>
                        <input name="tracing[key_folder_tracing]" type="text" class="form-control"
                            id="key_folder_tracing" required value="<?php echo $folder['key_folder']; ?>" readonly
                            style="display: none;" hidden>
                        <input name="tracing[id_user_tracing]" type="text" class="form-control" id="id_user_tracing"
                            required value="<?php echo $_SESSION['user']['id_user']; ?>" readonly style="display: none;"
                            hidden>
                        <input name="tracing[key_tracing]" type="text" class="form-control" id="key_tracing" required
                            readonly style="display: none;" hidden>
                        <input name="tracing[key_user]" type="text" class="form-control" id="key_user" required
                            value="<?php echo $_SESSION['user']['key_user']; ?>" readonly style="display: none;" hidden>

                        <div class="form-group">
                            <label>Usuarios a notificar:</label>
                            <select name="seleccionados[]" id="id_user" class="form-control select2" required multiple>
                                <?php foreach ($allUsers as $key => $value) { ?>
                                    <!--COMPROBAMOS QUE EL ID DEL USUARIO QUE TIENE LA SESIÓN SEA DIFERENTE PARA QUE NO SE MUESTRE EN EL SELECT-->
                                    <?php if ($value['id_user'] != $_SESSION['user']['id_user']) { ?>
                                        <option value="<?php echo $value['id_user']; ?>">
                                            <?php echo $value['name_user']; ?>
                                        </option>
                                    <?php } ?>
                                <?php } ?>
                            </select>

                            <!-- Botones para seleccionar y desmarcar -->
                            <button type="button" id="selectAllBtn" class="btn btn-primary btn-sm mt-2">Seleccionar
                                Todo</button>
                            <button type="button" id="deselectAllBtn" class="btn btn-secondary btn-sm mt-2">Desmarcar
                                Todo</button>
                        </div>

                        <div class="form-group">
                            <label>Comentario:</label>
                            <textarea name="tracing[comment_tracing]" class="form-control" id="comment_tracing" rows="4"
                                required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="send_notification" style="display: flex; align-items: center; font-size: 16px;">
                                ¿Enviar notificación por correo electrónico?
                                <input type="checkbox" id="send_notification" class="checkbox-custom"
                                    style="margin-left: 10px; transform: scale(1.5);">
                            </label>
                        </div>

                        <?php /*
<div class="form-group">
<label>
¿Enviar notificación por correo electrónico?
<!--
<small class="text-muted d-block mt-1" style="text-align:justify; color: 000000;">
Marca esta casilla si deseas enviar una notificación a los correos de los empleados seleccionados. Deja sin marcar si no quieres enviar la notificación.
</small>
-->
</label>
<input type="checkbox" id="send_notification" class="form-control">
</div>
*/ ?>

                        <button type="submit" class="btn btn-lg btn-block"
                            style="background-color: #37424A; color: #ffffff;" name="action" value="create"
                            id="createNoticeBtn">Guardar</button>
                    </form>

                </div>

            </div>
        </div>
    </div>





    <!-- Modal para editar un seguimiento -->
    <div class="modal fade" id="modalEditTracing" tabindex="-1" aria-labelledby="modalEditTracingLabel"
        aria-hidden="true">
        <div class="modal-dialog" style="max-width: 50%;">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditTracingLabel">Editar seguimiento</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <form id="editFormTracing" action="#" method="POST">
                        <input name="editTracing[id_tracing]" type="text" class="form-control" id="edit_id_tracing"
                            required readonly style="display: none;" hidden>
                        <input name="editTracing[key_tracing]" type="text" class="form-control" id="edit_key_tracing"
                            required readonly style="display: none;" hidden>

                        <div class="form-group">
                            <label>Comentario:</label>
                            <textarea name="editTracing[comment_tracing]" class="form-control" id="edit_comment_tracing"
                                rows="7" required></textarea>
                        </div>


                        <button type="submit" class="btn btn-lg btn-block"
                            style="background-color: #37424A; color: #ffffff;" name="action" value="updateTracing"
                            id="updateTracingBtn">Actualizar</button>
                    </form>

                </div>

            </div>
        </div>
    </div>

    <script src="../../resources/plugins/jquery/jquery.min.js"></script>
    <script src="../../resources/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../resources/dist/js/adminlte.min.js"></script>
    <!-- Select2 -->
    <script src="../../resources/plugins/select2/js/select2.full.min.js"></script>

    <!-- Scripts de notificaciones AL FINAL -->
    <script src="../../resources/js/notifications.js"></script>
    <script src="../../resources/js/tracings.js"></script>
    <script src="../../resources/js/notify_folders.js"></script>
    <!--ESTE ES EL SCRIPT QUE SE EJECUTA PARA MOSTRAR EL OVERLAY-->
    <script>
        document.getElementById('formAgregarFullDocuments').addEventListener('submit', function () {
            document.getElementById('loadingOverlay').style.display = 'flex';
        });
    </script>

    <script>
        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip({
                delay: { "show": 0, "hide": 0 } // Hacer que el tooltip aparezca y desaparezca inmediatamente
            });
        });
    </script>

    <!--ESTE ES EL CÓDIGO PARA MOSTRAR Y ELIMINAR LA VISTA PREVIA DE LOS ARCHIVOS QUE SE SUBEN AL INPUT MULTIPLE-->
    <script>
        document.getElementById('miarchivo').addEventListener('change', function (event) {
            const files = event.target.files;
            const previewContainer = document.getElementById('preview-container');
            const dt = new DataTransfer();

            if (files.length > 0) {
                previewContainer.style.display = 'block'; // Mostrar el contenedor si hay archivos
            } else {
                previewContainer.style.display = 'none'; // Ocultar el contenedor si no hay archivos
            }
            previewContainer.innerHTML = ''; // Limpiar el contenedor de vista previa

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                dt.items.add(file);
                const fileRow = document.createElement('div');
                fileRow.classList.add('file-row');
                fileRow.style.display = 'flex';
                fileRow.style.alignItems = 'center';
                fileRow.style.justifyContent = 'space-between';
                fileRow.style.marginBottom = '10px';

                const fileName = document.createElement('span');
                fileName.textContent = file.name;

                const removeButton = document.createElement('button');
                removeButton.textContent = 'Eliminar';
                removeButton.classList.add('btn', 'btn-danger', 'btn-sm');
                removeButton.style.marginLeft = '10px';
                removeButton.dataset.index = i;

                removeButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    const index = e.target.dataset.index;
                    const newFiles = Array.from(dt.files).filter((file, idx) => idx != index);
                    dt.items.clear();
                    newFiles.forEach(file => dt.items.add(file));
                    document.getElementById('miarchivo').files = dt.files;
                    updatePreview(dt.files);
                });
                fileRow.appendChild(fileName);
                fileRow.appendChild(removeButton);
                previewContainer.appendChild(fileRow);
            }
            document.getElementById('miarchivo').files = dt.files;
        });

        function updatePreview(files) {
            const previewContainer = document.getElementById('preview-container');
            previewContainer.innerHTML = ''; // Limpiar el contenedor de vista previa

            if (files.length > 0) {
                previewContainer.style.display = 'block'; // Mostrar el contenedor si hay archivos
            } else {
                previewContainer.style.display = 'none'; // Ocultar el contenedor si no hay archivos
            }

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileRow = document.createElement('div');
                fileRow.classList.add('file-row');
                fileRow.style.display = 'flex';
                fileRow.style.alignItems = 'center';
                fileRow.style.justifyContent = 'space-between';
                fileRow.style.marginBottom = '10px';
                const fileName = document.createElement('span');
                fileName.textContent = file.name;

                const removeButton = document.createElement('button');
                removeButton.textContent = 'Eliminar';
                removeButton.classList.add('btn', 'btn-danger', 'btn-sm');
                removeButton.style.marginLeft = '10px';
                removeButton.dataset.index = i;

                removeButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    const index = e.target.dataset.index;
                    const newFiles = Array.from(files).filter((file, idx) => idx != index);
                    const dt = new DataTransfer();
                    newFiles.forEach(file => dt.items.add(file));
                    document.getElementById('miarchivo').files = dt.files;
                    updatePreview(dt.files);
                });

                fileRow.appendChild(fileName);
                fileRow.appendChild(removeButton);
                previewContainer.appendChild(fileRow);
            }
        }
    </script>

    <!--ESTE SCRIPT FUNCIONABA PARA EL MODAL QUE IBA AÑADIENDO FILAS A TRAVES DE UN BOTON, EL MODAL SIGUE EN EL CÓDIGO PERO NO SE USA ACTUALMENTE-->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const agregarDocumentoBtn = document.getElementById("agregarDocumentoBtn");
            const inputsContainer = document.getElementById("inputsContainer");
            // Función para eliminar la fila al hacer clic en el botón "Eliminar"
            function eliminarFila() {
                const fila = this.parentNode; // Obtener el elemento padre del botón (la fila)
                inputsContainer.removeChild(fila); // Eliminar la fila del contenedor
            }
            // Agregar el evento clic a todos los botones "Eliminar"
            const deleteButtons = document.querySelectorAll(".delete-btn");
            deleteButtons.forEach(button => {
                button.addEventListener("click", eliminarFila);
            });
            // Evento clic para agregar una nueva fila
            agregarDocumentoBtn.addEventListener("click", function () {
                const inputRow = document.createElement("div");

                inputRow.className = "input-row col-12";
                /*original*/
                /*inputRow.className = "input-row";*/

                inputRow.style.display = "flex";

                inputRow.innerHTML = `
                        <input type="file" style="margin-right: 10px; margin-bottom: 10px;" class="form-control" name="documents[]" accept=".pdf">
                        <button type="button" style="margin-bottom: 10px;" class="btn btn-danger delete-btn">Eliminar</button>
                    `;
                /*original*/
                /*
                inputRow.innerHTML = `
                    <input type="file" style="margin-right: 10px; margin-bottom: 10px;" class="form-control" name="documents[]" accept=".pdf">
                    <input type="date" style="margin-right: 10px; margin-bottom: 10px;" class="form-control" name="first_date[]">
                    <input type="date" class="form-control" name="second_date[]" style="margin-bottom: 10px;">
                    <button type="button" style="margin-bottom: 10px;" class="btn btn-danger delete-btn">Eliminar</button>
                `;
                */
                inputsContainer.appendChild(inputRow);
                // Agregar evento clic al nuevo botón "Eliminar"
                const newDeleteButton = inputRow.querySelector(".delete-btn");
                newDeleteButton.addEventListener("click", eliminarFila);
            });
        });
    </script>
    <script>
        function goBack() {
            window.history.back();
        }
    </script>



    <script>
        $(document).ready(function () {
            // Acción de clic en editar carpeta
            $('.dropdown-item[data-folder-id]').click(function (e) {
                e.preventDefault();
                var folderId = $(this).data('folder-id');
                var folderName = $(this).closest('.folder').find('.title').text().trim();

                $.ajax({
                    type: "GET",
                    url: "../../app/webservice.php",
                    data: {
                        action: "getFolderDetail",
                        idFolder: folderId
                    }
                }).done(function (response) {
                    var parsedResponse = JSON.parse(response);

                    // Llenar el formulario de edición con los datos de la subcarpeta
                    $('#edit_folder_id').val(parsedResponse.id_folder);
                    $('#edit_tipo_persona_hidden').val(parsedResponse.tipo_persona);

                    // PRIMERO: Ocultar todas las secciones
                    $('#edit_seccion_fisica').hide();
                    $('#edit_seccion_moral').hide();
                    $('#edit_seccion_fideicomiso').hide();

                    // SEGUNDO: Llenar campos según el tipo de persona
                    if (parsedResponse.tipo_persona === 'fisica') {
                        $('#edit_seccion_fisica').show();

                        // Llenar campos básicos
                        $('#edit_pf_nombre').val(parsedResponse.pf_nombre);
                        $('#edit_pf_apellido_paterno').val(parsedResponse.pf_apellido_paterno);
                        $('#edit_pf_apellido_materno').val(parsedResponse.pf_apellido_materno);
                        $('#edit_pf_rfc').val(parsedResponse.rfc_folder);
                        $('#edit_pf_curp').val(parsedResponse.curp_folder);
                        $('#edit_pf_fecha_nacimiento').val(parsedResponse.pf_fecha_nacimiento);
                        $('#edit_pf_estado').val(parsedResponse.pf_estado);
                        $('#edit_pf_ciudad').val(parsedResponse.pf_ciudad);
                        $('#edit_pf_colonia').val(parsedResponse.pf_colonia);
                        $('#edit_pf_codigo_postal').val(parsedResponse.pf_codigo_postal);
                        $('#edit_pf_calle').val(parsedResponse.pf_calle);
                        $('#edit_pf_num_exterior').val(parsedResponse.pf_num_exterior);
                        $('#edit_pf_num_interior').val(parsedResponse.pf_num_interior);
                        $('#edit_pf_telefono').val(parsedResponse.pf_telefono);
                        $('#edit_pf_email').val(parsedResponse.pf_email);

                        // CORREGIR: Manejar domicilio extranjero
                        var tieneDomicilioExt = parsedResponse.pf_tiene_domicilio_extranjero == 1;
                        $('#edit_pf_tiene_domicilio_extranjero').prop('checked', tieneDomicilioExt);

                        if (tieneDomicilioExt) {
                            $('#edit_pf_domicilio_extranjero').show();
                            // Llenar datos de domicilio extranjero
                            $('#edit_pf_pais_origen').val(parsedResponse.pf_pais_origen);
                            $('#edit_pf_estado_extranjero').val(parsedResponse.pf_estado_extranjero);
                            $('#edit_pf_ciudad_extranjero').val(parsedResponse.pf_ciudad_extranjero);
                            $('#edit_pf_colonia_extranjero').val(parsedResponse.pf_colonia_extranjero);
                            $('#edit_pf_calle_extranjero').val(parsedResponse.pf_calle_extranjero);
                            $('#edit_pf_num_exterior_ext').val(parsedResponse.pf_num_exterior_ext);
                            $('#edit_pf_num_interior_ext').val(parsedResponse.pf_num_interior_ext);
                            $('#edit_pf_codigo_postal_ext').val(parsedResponse.pf_codigo_postal_ext);
                        } else {
                            $('#edit_pf_domicilio_extranjero').hide();
                        }

                    } else if (parsedResponse.tipo_persona === 'moral') {
                        $('#edit_seccion_moral').show();

                        // Llenar campos básicos
                        $('#edit_pm_razon_social').val(parsedResponse.pm_razon_social);
                        $('#edit_pm_rfc').val(parsedResponse.rfc_folder);
                        $('#edit_pm_fecha_constitucion').val(parsedResponse.pm_fecha_constitucion);
                        $('#edit_pm_apoderado_nombre').val(parsedResponse.pm_apoderado_nombre);
                        $('#edit_pm_apoderado_paterno').val(parsedResponse.pm_apoderado_paterno);
                        $('#edit_pm_apoderado_materno').val(parsedResponse.pm_apoderado_materno);
                        $('#edit_pm_apoderado_fecha_nacimiento').val(parsedResponse.pm_apoderado_fecha_nacimiento);
                        $('#edit_pm_apoderado_rfc').val(parsedResponse.pm_apoderado_rfc);
                        $('#edit_pm_apoderado_curp').val(parsedResponse.pm_apoderado_curp);
                        $('#edit_pm_estado').val(parsedResponse.pm_estado);
                        $('#edit_pm_ciudad').val(parsedResponse.pm_ciudad);
                        $('#edit_pm_colonia').val(parsedResponse.pm_colonia);
                        $('#edit_pm_codigo_postal').val(parsedResponse.pm_codigo_postal);
                        $('#edit_pm_calle').val(parsedResponse.pm_calle);
                        $('#edit_pm_num_exterior').val(parsedResponse.pm_num_exterior);
                        $('#edit_pm_num_interior').val(parsedResponse.pm_num_interior);
                        $('#edit_pm_telefono').val(parsedResponse.pm_telefono);
                        $('#edit_pm_email').val(parsedResponse.pm_email);

                        // CORREGIR: Manejar domicilio extranjero
                        var tieneDomicilioExt = parsedResponse.pm_tiene_domicilio_extranjero == 1;
                        $('#edit_pm_tiene_domicilio_extranjero').prop('checked', tieneDomicilioExt);

                        if (tieneDomicilioExt) {
                            $('#edit_pm_domicilio_extranjero').show();
                            // Llenar datos de domicilio extranjero
                            $('#edit_pm_pais_origen').val(parsedResponse.pm_pais_origen);
                            $('#edit_pm_estado_extranjero').val(parsedResponse.pm_estado_extranjero);
                            $('#edit_pm_ciudad_extranjero').val(parsedResponse.pm_ciudad_extranjero);
                            $('#edit_pm_colonia_extranjero').val(parsedResponse.pm_colonia_extranjero);
                            $('#edit_pm_calle_extranjero').val(parsedResponse.pm_calle_extranjero);
                            $('#edit_pm_num_exterior_ext').val(parsedResponse.pm_num_exterior_ext);
                            $('#edit_pm_num_interior_ext').val(parsedResponse.pm_num_interior_ext);
                            $('#edit_pm_codigo_postal_ext').val(parsedResponse.pm_codigo_postal_ext);
                        } else {
                            $('#edit_pm_domicilio_extranjero').hide();
                        }

                    } else if (parsedResponse.tipo_persona === 'fideicomiso') {
                        $('#edit_seccion_fideicomiso').show();

                        // Llenar campos básicos
                        $('#edit_fid_razon_social').val(parsedResponse.fid_razon_social);
                        $('#edit_fid_rfc').val(parsedResponse.rfc_folder);
                        $('#edit_fid_numero_referencia').val(parsedResponse.fid_numero_referencia);
                        $('#edit_fid_apoderado_nombre').val(parsedResponse.fid_apoderado_nombre);
                        $('#edit_fid_apoderado_paterno').val(parsedResponse.fid_apoderado_paterno);
                        $('#edit_fid_apoderado_materno').val(parsedResponse.fid_apoderado_materno);
                        $('#edit_fid_apoderado_fecha_nacimiento').val(parsedResponse.fid_apoderado_fecha_nacimiento);
                        $('#edit_fid_apoderado_rfc').val(parsedResponse.fid_apoderado_rfc);
                        $('#edit_fid_apoderado_curp').val(parsedResponse.fid_apoderado_curp);
                        $('#edit_fid_estado').val(parsedResponse.fid_estado);
                        $('#edit_fid_ciudad').val(parsedResponse.fid_ciudad);
                        $('#edit_fid_colonia').val(parsedResponse.fid_colonia);
                        $('#edit_fid_codigo_postal').val(parsedResponse.fid_codigo_postal);
                        $('#edit_fid_calle').val(parsedResponse.fid_calle);
                        $('#edit_fid_num_exterior').val(parsedResponse.fid_num_exterior);
                        $('#edit_fid_num_interior').val(parsedResponse.fid_num_interior);
                        $('#edit_fid_telefono').val(parsedResponse.fid_telefono);
                        $('#edit_fid_email').val(parsedResponse.fid_email);

                        // CORREGIR: Manejar domicilio extranjero
                        var tieneDomicilioExt = parsedResponse.fid_tiene_domicilio_extranjero == 1;
                        $('#edit_fid_tiene_domicilio_extranjero').prop('checked', tieneDomicilioExt);

                        if (tieneDomicilioExt) {
                            $('#edit_fid_domicilio_extranjero').show();
                            // Llenar datos de domicilio extranjero
                            $('#edit_fid_pais_origen').val(parsedResponse.fid_pais_origen);
                            $('#edit_fid_estado_extranjero').val(parsedResponse.fid_estado_extranjero);
                            $('#edit_fid_ciudad_extranjero').val(parsedResponse.fid_ciudad_extranjero);
                            $('#edit_fid_colonia_extranjero').val(parsedResponse.fid_colonia_extranjero);
                            $('#edit_fid_calle_extranjero').val(parsedResponse.fid_calle_extranjero);
                            $('#edit_fid_num_exterior_ext').val(parsedResponse.fid_num_exterior_ext);
                            $('#edit_fid_num_interior_ext').val(parsedResponse.fid_num_interior_ext);
                            $('#edit_fid_codigo_postal_ext').val(parsedResponse.fid_codigo_postal_ext);
                        } else {
                            $('#edit_fid_domicilio_extranjero').hide();
                        }
                    }

                    // Llenar campos generales
                    $('#edit_first_fech_folder').val(parsedResponse.first_fech_folder);
                    $('#edit_second_fech_folder').val(parsedResponse.second_fech_folder);

                    // Marcar los checkboxes si el valor es "Si" o diferente de null
                    $('#edit_chk_alta_fact_folder').prop('checked', parsedResponse.chk_alta_fact_folder === "Si");
                    $('#edit_chk_lib_folder').prop('checked', parsedResponse.chk_lib_folder === "Si");
                    $('#edit_chk_orig_recib_folder').prop('checked', parsedResponse.chk_orig_recib_folder === "Si");

                    // Mostrar/ocultar fecha de original recibido
                    if (parsedResponse.chk_orig_recib_folder === "Si") {
                        $('#edit-fecha-original-recibido').show();
                        $('input[name="updateFolder[fech_orig_recib_folder]"]').attr('required', 'required').val(parsedResponse.fech_orig_recib_folder);
                    } else {
                        $('#edit-fecha-original-recibido').hide();
                        $('input[name="updateFolder[fech_orig_recib_folder]"]').removeAttr('required').val('');
                    }

                    // Mostrar el modal de edición
                    $('#modalEditarCarpeta').modal('show');
                });
            });

            // AGREGAR: Event handlers para los checkboxes de domicilio extranjero
            $('#edit_pf_tiene_domicilio_extranjero').change(function () {
                if ($(this).is(':checked')) {
                    $('#edit_pf_domicilio_extranjero').show();
                } else {
                    $('#edit_pf_domicilio_extranjero').hide();
                    // Limpiar campos cuando se desmarca
                    $('#edit_pf_domicilio_extranjero input').val('');
                }
            });

            $('#edit_pm_tiene_domicilio_extranjero').change(function () {
                if ($(this).is(':checked')) {
                    $('#edit_pm_domicilio_extranjero').show();
                } else {
                    $('#edit_pm_domicilio_extranjero').hide();
                    // Limpiar campos cuando se desmarca
                    $('#edit_pm_domicilio_extranjero input').val('');
                }
            });

            $('#edit_fid_tiene_domicilio_extranjero').change(function () {
                if ($(this).is(':checked')) {
                    $('#edit_fid_domicilio_extranjero').show();
                } else {
                    $('#edit_fid_domicilio_extranjero').hide();
                    // Limpiar campos cuando se desmarca
                    $('#edit_fid_domicilio_extranjero input').val('');
                }
            });






            // Acción de búsqueda de carpetas
            $("#searchInputFolders").on("keyup", function () {
                var searchText = $(this).val().toLowerCase();
                $("#myFolders .title-bar").each(function () {
                    var titleText = $(this).find('.title').text().toLowerCase();
                    var documentContainer = $(this).closest(".col-lg-3");
                    if (titleText.indexOf(searchText) === -1) {
                        documentContainer.hide();
                    } else {
                        documentContainer.show();
                    }
                });
            });
            //Acción de búsqueda de documentos
            $(document).ready(function () {
                $("#searchInputDocs").on("keyup", function () {
                    var searchText = $(this).val().toLowerCase();
                    $("#myDocuments .title-bar .title").each(function () {
                        var documentName = $(this).text().toLowerCase(); // Selecciona el texto del título del documento
                        var documentContainer = $(this).closest(".col-lg-3");
                        if (documentName.indexOf(searchText) === -1) {
                            documentContainer.hide();
                        } else {
                            documentContainer.show();
                        }
                    });
                });
            });
            // Acción de clic en editar documento
            $('.dropdown-item[data-document-id]').click(function (e) {
                e.preventDefault();
                var documentId = $(this).data('document-id');
                $.ajax({
                    type: "GET",
                    url: "../../app/webservice.php",
                    data: {
                        action: "getdocument",
                        idDocument: documentId
                    }
                }).done(function (response) {
                    var parsedResponse = JSON.parse(response);
                    // Llenar el formulario de edición con los datos de la carpeta
                    $('#edit_id_document').val(parsedResponse.id_document);
                    $('#edit_key_document').val(parsedResponse.key_document);
                    $('#edit_first_fech_document').val(parsedResponse.first_fech_document);
                    $('#edit_second_fech_document').val(parsedResponse.second_fech_document);
                    $('#edit_name_user').val(parsedResponse.name_user);
                    // Mostrar el modal de edición de documentos
                    $('#modalEditarDocument').modal('show');
                });
            });
            //ACCIÓN DE CLIC PARA ACTUALIZAR LA INFORMACIÓN DE LA CARPETA ACTUAL
            $('.edit-folder-btn').click(function (e) {
                e.preventDefault();
                // Obtener el id de la carpeta del input oculto
                var folderId = $(this).closest('form').find('#id_folder').val();
                // Obtener el nombre de la carpeta del input de nombre
                var folderName = $(this).closest('form').find('#name_folder').val();
                // Realizar la llamada AJAX con el id de la carpeta
                $.ajax({
                    type: "GET",
                    url: "../../app/webservice.php",
                    data: {
                        action: "getFolderDetail",
                        idFolder: folderId
                    }
                }).done(function (response) {
                    var parsedResponse = JSON.parse(response);
                    // Llenar el formulario de edición con los datos de la carpeta
                    $('#edit_folder_id').val(parsedResponse.id_folder);
                    $('#edit_folder_name').val(parsedResponse.name_folder);
                    $('#edit_first_fech_folder').val(parsedResponse.first_fech_folder);
                    $('#edit_second_fech_folder').val(parsedResponse.second_fech_folder);
                    // Marcar los checkboxes si el valor es "Si" o diferente de null
                    $('#edit_chk_alta_fact_folder').prop('checked', parsedResponse.chk_alta_fact_folder === "Si");
                    $('#edit_chk_lib_folder').prop('checked', parsedResponse.chk_lib_folder === "Si");
                    $('#edit_chk_orig_recib_folder').prop('checked', parsedResponse.chk_orig_recib_folder === "Si");
                    // Mostrar/ocultar el div y agregar/quitar el atributo required según el estado del checkbox de "Original Recibido"
                    if (parsedResponse.chk_orig_recib_folder === "Si") {
                        $('#edit-fecha-original-recibido').show();
                        $('input[name="updateFolder[fech_orig_recib_folder]"]').attr('required', 'required').val(parsedResponse.fech_orig_recib_folder);
                    } else {
                        $('#edit-fecha-original-recibido').hide();
                        $('input[name="updateFolder[fech_orig_recib_folder]"]').removeAttr('required').val('');
                    }
                    // Mostrar el modal de edición de carpetas
                    $('#modalEditarCarpeta').modal('show');
                });
            });

            $(document).on('click', '.edit-button-tracing', function (e) {
                e.preventDefault(); // Detiene el envío del formulario

                // Obtén los valores de los inputs ocultos en el formulario asociado al botón
                var form = $(this).closest('form'); // Encuentra el formulario más cercano al botón
                var idTracing = form.find('#id_tracing').val(); // Obtiene el valor del input id_tracing
                var keyTracing = form.find('#key_tracing').val(); // Obtiene el valor del input key_tracing

                // Llama al webservice con AJAX
                $.ajax({
                    type: "GET",
                    url: "../../app/webservice.php",
                    data: {
                        action: "getTracingDetail",
                        idTracing: idTracing,
                        keyTracing: keyTracing
                    }
                }).done(function (response) {
                    var parsedResponse = JSON.parse(response); // Parsea la respuesta del servidor

                    // Llena los campos del modal con los datos recibidos
                    $('#edit_id_tracing').val(parsedResponse.id_tracing);
                    $('#edit_key_tracing').val(parsedResponse.key_tracing);
                    $('#edit_comment_tracing').val(parsedResponse.comment_tracing);

                    // Muestra el modal de edición
                    $('#modalEditTracing').modal('show');
                }).fail(function (error) {
                    console.error("Error al obtener los datos del seguimiento:", error);
                });
            });

        });
    </script>



    <!--SCRIPT QUE PERMITE MARCAR Y DESMARCAR TODAS LAS OPCIONES DEL SELECT-->
    <script>
        $(document).ready(function () {
            // Inicializa Select2
            $('#id_user').select2({
                theme: 'bootstrap4'
            });
            // Seleccionar todas las opciones
            $('#selectAllBtn').click(function () {
                const $select = $('#id_user');
                $select.find('option').prop('selected', true); // Marca todas las opciones
                $select.trigger('change'); // Sincroniza con Select2
            });
            // Desmarcar todas las opciones
            $('#deselectAllBtn').click(function () {
                const $select = $('#id_user');
                $select.find('option').prop('selected', false); // Desmarca todas las opciones
                $select.trigger('change'); // Sincroniza con Select2
            });
        });
    </script>

    <!--SCRIPT PARA GUARDAR UN NUEVO SEGUIMIENTO-->
    <script>
        // Asignar valores de la variable de sesión a variables JavaScript
        var idFolder = <?php echo json_encode($folder['id_folder']); ?>;
        var keyFolder = <?php echo json_encode($folder['key_folder']); ?>;

        $(document).ready(function () {
            $('#createNoticeBtn').click(function (e) {
                e.preventDefault(); // Evitar el comportamiento predeterminado del botón.
                // Validamos el formulario antes de enviar
                var form = document.getElementById('addFormTracing');
                if (!form.checkValidity()) {
                    form.reportValidity(); // Muestra los mensajes de validación nativos del navegador
                    return; // Detener si el formulario no es válido
                }
                // Desactivamos el botón mientras se procesa la solicitud.
                $(this).prop('disabled', true).text('Guardando...');
                // Recogemos los datos del formulario manualmente.
                var formData = {
                    // IDS DE LOS USUARIOS SELECCIONADOS
                    id_user: $('#id_user').val(),
                    // DATA PARA GUARDAR UN NUEVO SEGUIMIENTO
                    id_folder_tracing: $('#id_folder_tracing').val(),
                    key_folder_tracing: $('#key_folder_tracing').val(),
                    id_user_tracing: $('#id_user_tracing').val(),
                    key_tracing: $('#key_tracing').val(),
                    comment_tracing: $('#comment_tracing').val(),
                    // KEY PARA CONSULTAR LOS DATOS DE UN EMPLEADO Y MANDAR SU DATA EN EL CORREO
                    key_user: $('#key_user').val(),
                    send_notification: $('#send_notification').prop('checked') // Obtener true o false según el estado
                };

                // Llamada AJAX para enviar el formulario.
                $.ajax({
                    url: "../../app/webservice.php", // El archivo PHP donde se procesará el formulario.
                    type: 'POST',
                    data: {
                        action: 'sendNoticeCustomers',
                        // IDS DE LOS USUARIOS SELECCIONADOS
                        seleccionados: formData.id_user,
                        // DATA PARA GUARDAR UN NUEVO SEGUIMIENTO
                        id_folder_tracing: formData.id_folder_tracing,
                        key_folder_tracing: formData.key_folder_tracing,
                        id_user_tracing: formData.id_user_tracing,
                        key_tracing: formData.key_tracing,
                        comment_tracing: formData.comment_tracing,
                        // KEY PARA CONSULTAR LOS DATOS DE UN EMPLEADO Y MANDAR SU DATA EN EL CORREO
                        key_user: formData.key_user,
                        send_notification: formData.send_notification
                    },
                    success: function (response) {
                        try {
                            var result = JSON.parse(response); // Intenta parsear el JSON.
                            if (result.status === 'success') {
                                window.location.href = `subfolder.php?id=${idFolder}&key=${keyFolder}`;
                            } else {
                                alert("No se pudo completar la acción.");
                            }
                        } catch (e) {
                            alert("Ha ocurrido un error al procesar la respuesta del servidor.");
                            //console.error("Error al parsear JSON:", e);
                        }
                        $('#createNoticeBtn').prop('disabled', false).text('Guardar');
                    },
                    error: function (xhr, status, error) {
                        alert("Ha ocurrido un error, intenta de nuevo.");
                        //console.error("Error en la solicitud AJAX:", error);
                        $('#createNoticeBtn').prop('disabled', false).text('Guardar');
                    }
                });
            });
        });
    </script>






    <!--SCRIPT PARA ACTUALIZAR UN SEGUIMIENTO-->
    <script>
        // Asignar valores de la variable de sesión a variables JavaScript
        var idFolder = <?php echo json_encode($folder['id_folder']); ?>;
        var keyFolder = <?php echo json_encode($folder['key_folder']); ?>;

        $(document).ready(function () {
            $('#updateTracingBtn').click(function (e) {
                e.preventDefault(); // Evitar el comportamiento predeterminado del botón.
                // Validamos el formulario antes de enviar
                var form = document.getElementById('editFormTracing');
                if (!form.checkValidity()) {
                    form.reportValidity(); // Muestra los mensajes de validación nativos del navegador
                    return; // Detener si el formulario no es válido
                }
                // Desactivamos el botón mientras se procesa la solicitud.
                $(this).prop('disabled', true).text('Guardando...');
                // Recogemos los datos del formulario manualmente.
                var formData = {
                    edit_id_tracing: $('#edit_id_tracing').val(),
                    edit_key_tracing: $('#edit_key_tracing').val(),
                    edit_comment_tracing: $('#edit_comment_tracing').val(),
                };

                // Llamada AJAX para enviar el formulario.
                $.ajax({
                    url: "../../app/webservice.php", // El archivo PHP donde se procesará el formulario.
                    type: 'POST',
                    data: {
                        action: 'updateDataTracing',
                        dataTracing: formData
                    },
                    success: function (response) {
                        try {
                            var result = JSON.parse(response); // Intenta parsear el JSON.
                            if (result.status === 'success') {
                                window.location.href = `subfolder.php?id=${idFolder}&key=${keyFolder}`;
                            } else {
                                alert("No se pudo completar la acción.");
                            }
                        } catch (e) {
                            alert("Ha ocurrido un error al procesar la respuesta del servidor.");
                            //console.error("Error al parsear JSON:", e);
                        }
                        $('#updateTracingBtn').prop('disabled', false).text('Guardar');
                    },
                    error: function (xhr, status, error) {
                        alert("Ha ocurrido un error, intenta de nuevo.");
                        //console.error("Error en la solicitud AJAX:", error);
                        $('#updateTracingBtn').prop('disabled', false).text('Guardar');
                    }
                });
            });
        });
    </script>




    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuButton = document.getElementById('menu-button');
            const sidebar = document.getElementById('sidebar-lateral');
            const closeButton = document.getElementById('close-sidebar');
            //const addButton = document.querySelector('.fixed-button'); // Selección del botón "Agregar Nuevo Seguimiento"
            const ellipsisIcon = document.getElementById('ellipsis-icon'); // botón de los 3 puntos para cerrar el menu lateral

            // Función para cerrar el menú lateral
            function closeSidebar() {
                sidebar.style.display = 'none';  // Oculta el menú lateral
            }
            // Asociamos el evento de clic al ícono
            ellipsisIcon.addEventListener('click', function () {
                closeSidebar();  // Llama a la función que cierra el menú
            });


            // FUNCIÓN PARA COPIAR EL MENSAJE DEL INPUT TEXT DEL MENÚ AL DEL MODAL
            // Seleccionamos el icono (o el botón que abre el modal)
            const modalButton = document.querySelector('.modal-icon');  // Asegúrate de que el selector sea el correcto
            const keyTracingInput = document.querySelector('input[name="dataTracing[key_tracing]"]');
            const commentTracingInput = document.querySelector('input[name="dataTracing[comment_tracing]"]');

            // Seleccionamos los campos dentro del modal
            const keyTracingModal = document.getElementById('key_tracing');
            const commentTracingModal = document.getElementById('comment_tracing');

            // Función para copiar los valores al modal
            function copyToModal() {
                // Copiar el valor de 'key_tracing'
                keyTracingModal.value = keyTracingInput.value;

                // Copiar el valor de 'comment_tracing'
                commentTracingModal.value = commentTracingInput.value;
            }

            // Asociamos la función al evento de apertura del modal
            modalButton.addEventListener('click', function () {
                copyToModal();
            });

            // FUNCIONES PARA ABRIR Y CERRAR EL MENÚ
            // Función para abrir o cerrar el menú
            function toggleSidebar() {
                sidebar.classList.toggle('open');
                menuButton.classList.toggle('open');
            }

            // Función para cerrar el menú si se hace clic fuera
            function closeSidebarOnClickOutside(event) {
                if (!sidebar.contains(event.target) && !menuButton.contains(event.target)) {
                    sidebar.classList.remove('open');  // Cierra el menú
                    menuButton.classList.remove('open');  // Regresa el botón
                }
            }

            // Función para cerrar el menú desde el botón "Agregar Nuevo Seguimiento"
            function closeSidebar() {
                sidebar.classList.remove('open');  // Cierra el menú
                menuButton.classList.remove('open');  // Regresa el botón
            }

            // funciones para que se cierre el menú al dar cli en el botón de editar y de eliminar de la lista de seguimientos
            // Eventos para los botones de "Editar" y "Eliminar"
            document.querySelectorAll('.edit-button-tracing, .delete-button').forEach(button => {
                button.addEventListener('click', function () {
                    closeSidebar(); // Cierra el menú cuando se haga clic en estos botones
                });
            });

            // Delegación de eventos para los botones de editar y eliminar
            $('#timeline').on('click', '.edit-button-tracing, .delete-button', function () {
                closeSidebar();
            });

            // Eventos para abrir/cerrar el menú
            menuButton.addEventListener('click', toggleSidebar);
            closeButton.addEventListener('click', toggleSidebar);
            document.addEventListener('click', closeSidebarOnClickOutside);
            //addButton.addEventListener('click', closeSidebar);  // Cierra el menú al hacer clic en el botón "Agregar Nuevo Seguimiento"
        });
    </script>





    <!--SCRIPT PARA IR CARGANDO LOS DEMAS SEGUIMIENTOS DE 5 EN 5-->
    <script>

        //OBTENEMOS LA VARIABLE DE SESIÓN DEL USER ID PARA LA COMPROBACIÓN
        const currentUserId = <?php echo json_encode($_SESSION['user']['id_user']); ?>;


        let offset = 5;
        const limit = 5;
        const folderId = <?php echo json_encode($folder['id_folder']); ?>;
        let isLoading = false;
        let hasMoreRecords = true; // Bandera para determinar si hay más registros por cargar

        // Modificar el evento de scroll al menú lateral
        $('#sidebar-lateral').scroll(function () {
            // Comprobar si el scroll del contenedor ha llegado al final
            if ($('#sidebar-lateral').scrollTop() + $('#sidebar-lateral').innerHeight() >= $('#sidebar-lateral')[0].scrollHeight - 50) {
                if (!isLoading && hasMoreRecords) {
                    isLoading = true;
                    $('#loading').show();

                    $.ajax({
                        url: "../../app/webservice.php",
                        type: 'POST',
                        data: {
                            action: 'loadMoreTracings',
                            folder_id: folderId,
                            offset: offset,
                            limit: limit
                        },

                        success: function (response) {
                            const data = JSON.parse(response);
                            const tracings = data.tracings;

                            if (tracings.length > 0) {
                                tracings.forEach(function (tracing) {
                                    //COMPROBAMOS SI EL USUARIO QUE ESTA DENTRO DE LA SESIÓN ES EL AUTOR DEL SEGUIMIENTO
                                    const className = (currentUserId == tracing.id_user_tracing) ? 'author-highlight' : 'other-highlight';
                                    $('#timeline').append(`
                                    <li class="timeline-item ${className}">
                                        <div class="user-row">
                                            <p class="user">${tracing.name_user}</p>
                                            ${currentUserId == tracing.id_user_tracing ? `
                                            <div class="action-buttons">

                                                <form action="#" method="POST">
                                                    <input name="deleteTracing[id_tracing]" type="text" class="form-control" id="id_tracing" value='${tracing.id_tracing}' readonly hidden style="display: none;">
                                                    <input name="deleteTracing[key_tracing]" type="text" class="form-control" id="key_tracing" value='${tracing.key_tracing}' readonly hidden style="display: none;">
                                                    
                                                    <button class="edit-button-tracing" data-toggle="modal" id="btn-edit-tracing">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                </form>
                                        
                                                <form action="#" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar el seguimiento?');" style="display: inline;">
                                                    <input name='deleteTracing[id_tracing]' type='text' class='form-control' id='id_tracing' value='${tracing.id_tracing}' readonly hidden style="display: none;">
                                                    <input name='deleteTracing[key_tracing]' type='text' class='form-control' id='key_tracing' value='${tracing.key_tracing}' readonly hidden style="display: none;">
                                                    <button class="btn delete-button" name="action" value="deleteTracing" title="Eliminar">
                                                        <i class='fas fa-trash'></i>
                                                    </button>
                                                </form>
                                                
                                            </div>` : ''}
                                        </div>
                                        <p class="comment">${tracing.comment_tracing}</p>
                                        <p class="date">${formatDate(tracing.created_at_tracing)}</p>
                                    </li>
                                    <hr>
                                `);
                                });
                                offset += tracings.length;
                            }
                            // Verificar si hay más registros
                            if (!data.hasMore) {
                                hasMoreRecords = false;
                                $('#loading').text("¡No hay más registros!");
                            }
                            //$('#loading').hide();
                            isLoading = false;
                        },

                        error: function () {
                            alert("Ocurrió un error al cargar más seguimientos.");
                            $('#loading').hide();
                            isLoading = false;
                        }
                    });
                }
            }
        });

        // Función para formatear fechas
        function formatDate(dateString) {
            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0'); // Los meses en JS son 0-indexados
            const year = date.getFullYear();

            let hours = date.getHours();
            let minutes = String(date.getMinutes()).padStart(2, '0');
            let ampm = 'am';

            if (hours >= 12) {
                ampm = 'pm';
                if (hours > 12) hours -= 12; // Convertir a formato de 12 horas
            }
            if (hours === 0) {
                hours = 12;
            }

            return `${day}/${month}/${year} ${hours}:${minutes} ${ampm}`;
        }
    </script>

    <!--SCRIPT PARA VALIDAR SI EL CAMPO DE SEGUIMIENTO TIENE INFORMACIÓN Y SE HABILITAN LOS 3 PUNTITOS PARA ABRIR EL MODAL-->
    <script>
        // Obtener el input y el icono
        const commentInput = document.querySelector('input[name="dataTracing[comment_tracing]"]');
        const ellipsisIcon = document.getElementById('ellipsis-icon');

        // Función para verificar si el campo tiene texto
        function checkCommentText() {
            if (commentInput.value.trim() === '') {
                // Si el campo está vacío, deshabilitar el icono
                ellipsisIcon.style.pointerEvents = 'none';
                ellipsisIcon.style.opacity = '0.5'; // Icono más tenue para indicar que está deshabilitado
                ellipsisIcon.setAttribute('disabled', 'true');  // Deshabilitar el icono

            } else {
                // Si hay texto, habilitar el icono
                ellipsisIcon.removeAttribute('disabled'); // Habilitar el icono

                ellipsisIcon.style.pointerEvents = 'auto';
                ellipsisIcon.style.opacity = '1'; // Icono visible
            }
        }

        // Ejecutar la función al escribir en el campo
        commentInput.addEventListener('input', checkCommentText);

        // Ejecutar la función al cargar la página en caso de que el campo ya tenga texto
        document.addEventListener('DOMContentLoaded', checkCommentText);

    </script>

    <script>
        var userId = <?php echo json_encode($_SESSION['user']['id_user']); ?>;
        var idFolder = <?php echo json_encode($folder['id_folder']); ?>;

        $(function () {
            loadTracingsFolderUser();
        });

        function loadTracingsFolderUser() {
            $.ajax({
                type: "GET",
                url: "../../app/webservice.php",
                data: {
                    action: "getTracingsFolderUser",
                    userId: userId,
                    idFolder: idFolder
                }
            }).done(function (response) {
                var parsedResponse = JSON.parse(response);
                var notificationsCount = parsedResponse.total || 0;

                // Actualizamos el número de notificaciones en el círculo
                var badge = $('#notification-badge');
                badge.text(notificationsCount);
            });
        }


        function clearNotificationsAndReload() {
            countTracings = [];
            // Obtenemos los IDs de las notificaciones
            $.ajax({
                type: "GET",
                url: "../../app/webservice.php",
                data: {
                    action: "loadDataTracingsFolderUser",
                    userId: userId,
                    idFolder: idFolder
                }
            }).done(function (response) {
                countTracings = [];
                var parsedResponse = JSON.parse(response);

                if (parsedResponse.length == 0) {
                    //console.log("no hay data");
                } else {
                    parsedResponse.forEach(function (item) {
                        countTracings.push(item.id_notify);
                    });
                    $.ajax({
                        type: "GET",
                        url: "../../app/webservice.php",
                        data: {
                            action: "ws_clearTracingsNotifyFolder",
                            notifyIds: JSON.stringify(countTracings)  // Serializamos el array
                        }
                    }).done(function (response) {
                        try {
                            var dataresponse = JSON.parse(response);
                            if (dataresponse.success) {
                                loadTracingsFolderUser();
                            } else {
                                console.error(dataresponse.message);
                            }
                        } catch (error) {
                            console.error("Error parsing JSON response:", error, response);
                        }
                    }).fail(function (jqXHR, textStatus, errorThrown) {
                        console.error("AJAX request failed:", textStatus, errorThrown);
                    });
                }
            });
        }

        // Asociamos el evento al botón
        $(document).on('click', '#menu-button', function () {
            clearNotificationsAndReload();
        });
    </script>





    <script>
        $(document).ready(function () {
            // Mostrar/ocultar secciones según tipo de persona
            $('#tipo_persona').change(function () {
                var tipo = $(this).val();

                // Ocultar todas las secciones
                $('#seccion_fisica, #seccion_moral, #seccion_fideicomiso').hide();

                // Limpiar campos requeridos anteriores
                $('.form-control').removeAttr('required');

                // Restablecer campos requeridos básicos
                $('#empresa_select, #tipo_persona').attr('required', true);

                // Mostrar la sección correspondiente y establecer campos requeridos
                if (tipo === 'fisica') {
                    $('#seccion_fisica').show();
                    $('#pf_nombre, #pf_apellido_paterno, #pf_rfc').attr('required', true);
                } else if (tipo === 'moral') {
                    $('#seccion_moral').show();
                    $('#pm_razon_social, #pm_rfc').attr('required', true);
                } else if (tipo === 'fideicomiso') {
                    $('#seccion_fideicomiso').show();
                    $('#fid_razon_social, #fid_rfc').attr('required', true);
                }
            });

            // Mostrar/ocultar domicilio extranjero para Persona Física
            $('#pf_tiene_domicilio_extranjero').change(function () {
                if ($(this).is(':checked')) {
                    $('#pf_domicilio_extranjero').show();
                } else {
                    $('#pf_domicilio_extranjero').hide();
                }
            });

            // Mostrar/ocultar domicilio extranjero para Persona Moral
            $('#pm_tiene_domicilio_extranjero').change(function () {
                if ($(this).is(':checked')) {
                    $('#pm_domicilio_extranjero').show();
                } else {
                    $('#pm_domicilio_extranjero').hide();
                }
            });

            // Mostrar/ocultar domicilio extranjero para Fideicomiso
            $('#fid_tiene_domicilio_extranjero').change(function () {
                if ($(this).is(':checked')) {
                    $('#fid_domicilio_extranjero').show();
                } else {
                    $('#fid_domicilio_extranjero').hide();
                }
            });

            // Formatear RFC en mayúsculas
            $('#pf_rfc, #pm_rfc, #fid_rfc, #pm_apoderado_rfc, #fid_apoderado_rfc').on('input', function () {
                this.value = this.value.toUpperCase();
            });

            // Formatear CURP en mayúsculas
            $('#pf_curp, #pm_apoderado_curp, #fid_apoderado_curp').on('input', function () {
                this.value = this.value.toUpperCase();
            });

            // Limpiar el modal al cerrarlo
            $('#modalAgregarCarpeta').on('hidden.bs.modal', function () {
                $('#formAgregarCliente')[0].reset();
                $('#seccion_fisica, #seccion_moral, #seccion_fideicomiso').hide();
                $('#pf_domicilio_extranjero, #pm_domicilio_extranjero, #fid_domicilio_extranjero').hide();
                $('.form-control').removeAttr('required');
                // Restablecer campos básicos como requeridos
                $('#empresa_select, #tipo_persona').attr('required', true);
            });

            // Validación del formulario
            $('#formAgregarCliente').submit(function (e) {
                var tipoPersona = $('#tipo_persona').val();



                if (!tipoPersona) {
                    e.preventDefault();
                    alert('Por favor selecciona el tipo de persona.');
                    $('#tipo_persona').focus();
                    return false;
                }
            });
        });
    </script>
</body>

</html>