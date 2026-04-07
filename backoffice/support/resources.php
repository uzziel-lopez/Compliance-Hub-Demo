<?php
    session_start();
    include "../../app/config.php";
    // include "../../app/debug.php";
    include "../../app/WebController.php";
    $controller = new WebController();
    
    // Verificar si la sesión del usuario está activa
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        // Si no hay sesión activa, destruir la sesión
        session_destroy();
        // Redirigir a la página de inicio de sesión
        header("Location: ../../login.php");
        exit(); // Es importante salir después de redirigir para evitar que el código siguiente se ejecute innecesariamente
    }
    
    // Obtener la lista de secciones mediante el método getSections del controlador, pasando el status como parámetro (1-> ACTIVO, 2-> INACTIVO)
    $sections = $controller->getSections(1);
    // Contar el número total de secciones obtenidas en la variable $sections.
    $totalSections = count($sections);
    
    if (!empty($_POST['action'])) {
        // Si la acción es 'createSection', se intenta crear una sección nueva
        if ($_POST['action'] == 'createSection') {
            // Llama al método para crear una sección y obtiene el ID de la sección creada
            $sectionId = $controller->createNewSection($_POST['newSection']);
            // Si se crea la sección correctamente, redirecciona a la página de recursos o material de apoyo
            if ($sectionId) {
                header('location: resources.php');
            }
        }
        
        // Si la acción es 'updateSection', se intenta actualizar la información de la sección existente
        else if($_POST['action'] == 'updateSection'){
            // Llama al método para actualizar la data de la sección y obtiene el ID de la sección actualizada
            $idSection = $controller->updateDataSection($_POST['editSection']);
            // Si se actualiza la data correctamente, redirecciona a la página de recursos o material de apoyo
            if($idSection){
                header('location: resources.php');
            }
        }

        // Si la acción es 'eliminatedSection', se intenta eliminar una sección existente
        else if($_POST['action'] == 'eliminatedSection'){
            // Llama al método para eliminar la sección y obtiene el ID de la sección eliminada
            $section_id = $controller->deleteSection($_POST['deleteSection']);
            // Si se elimina la sección correctamente, redirecciona a la página de recursos o material de apoyo
            if($section_id){
            header('location: resources.php');
            }
        }

        //ESTE CÓDIGO ES PARA SUBIR LOS ARCHIVOS DE MANERA MASIVA
        else if($_POST['action'] == 'saveFullDocuments'){
            // Ruta de la carpeta de destino (YA SEA AL INTERIOR DEL SERVER O EN UNA UNIDAD EXTERNA)
            
            // CÓDIGO PARA GUARDAR DOCUMENTOS DIRECTAMENTE EN EL SERVER
            $carpeta = '../../uploads/material/'.$_POST['saveDocuments']['keySection'];
            
            // CÓDIGO GUARDAR DOCUMENTOS EN LA NUEVA UNIDAD
            // $carpeta = 'E:/uploads/material/'.$_POST['saveDocuments']['keySection'];
            
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
                    $target_path = $carpeta.'/'.$nombre_archivo_destino;
                    // Verifica si el directorio de destino existe, si no, créalo
                    if (!file_exists($carpeta)) {
                        mkdir($carpeta, 0777, true);
                    }
                    // Mueve el archivo a la carpeta de destino
                    if(move_uploaded_file($fuente, $target_path)) {
                        //CLAVE ALEATORIA PARA LAS CARGAS DE DOCUMENTOS
                        $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        $claveRandom = substr(str_shuffle($permitted_chars), 0, 7);
                        $keyMaterial = "MAT-" . $claveRandom;
                        //Declaramos la data para almacenar la información de los Documentos
                        $dataDocument = array(
                            "id_section_material"=>$_POST['saveDocuments']['idSectionDocuments'],
                            "id_user_material"=>$_SESSION['user']['id_user'],
                            "key_material" =>$keyMaterial,
                            "file_name_material"=>$archivonombre,
                            "file_extension_material"=>$file_extension
                        );
                        //Obtenemos la respuesta del documento creado
                        $documentId = $controller->createMaterial($dataDocument);        
                    } else {
                        echo "Ha ocurrido un error al subir el archivo $archivonombre.<br>";
                    }
                }
                header("location: resources.php");
            }
        }

        // Si la acción es 'deleteMaterial', se intenta eliminar un documento de una sección existente
        else if($_POST['action'] == 'deleteMaterial'){
            // Llama al método para eliminar el documento de la sección y se obtiene el ID del documento eliminado
            $material_id = $controller->deleteMaterialSection($_POST['dataDeleteMaterial']);
            // Si se elimina el documento correctamente, redirecciona a la página de recursos o material de apoyo
            if($material_id){
            header('location: resources.php');
            }
        }

    }
    
    $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $keySection = substr(str_shuffle($permitted_chars), 0, 6);
?>

<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Compliance Hub</title>
        <link rel="stylesheet" href="../../resources/plugins/fontawesome-free/css/all.min.css">
        <link rel="stylesheet" href="../../resources/dist/css/adminlte.min.css">
        <link rel="icon" href="../../resources/img/icono.png">
        <script src="../../resources/js/jquery-3.5.1.min.js"></script>
        <style>
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
            .files input:focus{ outline: 2px dashed #92b0b3; outline-offset: -10px;
                -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
                transition: outline-offset .15s ease-in-out, background-color .15s linear; border:1px solid #92b0b3;
            }
            .color input{
                background-color:#f1f1f1;
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
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            /*Atributos para la lista de documentos dentro del contenedor*/
            .card-body {
                max-height: 180px; /* Altura máxima del contenedor */
                overflow-y: auto; /* Habilita el scroll vertical si el contenido excede la altura */
            }
            
            /* Estilo para la manita al agarrar */
            .handle {
                cursor: grab;
            }
            .handle:active {
                cursor: grabbing;
            }
            /* Estilo para el nombre del documento */
            .document-name {
                cursor: grab; /* Manita de agarre */
            }
            .document-name:active {
                cursor: grabbing; /* Manita cerrada al hacer clic */
            }
            .delete-button-material-section {
                background-color: transparent;
                border: none;
                color: red;
                cursor: pointer;
            }
        </style>
    </head>
    
    <body class="hold-transition sidebar-mini">
        <div id="loadingOverlay" style="display: none;">
            <div class="spinner"></div>
        </div>

        <div class="wrapper" style="padding-top: 57px;">
            <?php include "../templates/navbar.php"; ?>
            <div class="content-wrapper">
                
                <div class="content-header" style="margin-bottom:-20px;">
                    <div class="container-fluid">
                        <div class="row justify-content-between mb-2">
                            <div class="col-lg-6 col-sm-6">
                                <h1 class="m-0 text-dark">Material de Apoyo</h1>
                            </div>
                            
                            <div class="col-sm-4 text-right">
                                <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                <?php if($_SESSION['user']['id_type_user'] == 1){ ?>
                                    <!-- Botón para abrir el modal para añadir una nueva sección -->
                                    <a href="#" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;" role="button" aria-pressed="true" data-toggle="modal" data-target="#modalAddNewSection">
                                        <i class="fas fa-plus pr-2"></i>Agregar nueva sección
                                    </a>
                                <?php } else { ?>
                                    <a href="#" class="btn btn-block" style="background-color: #FF5800; color: #ffffff; opacity: 0; pointer-events: none;" role="button" aria-pressed="true">###</a>
                                <?php } ?>
                            </div>
                        </div>
                        <hr>
                    </div>
                </div>
                
                <div class="content">
                    <div class="container-fluid">
                        <?php if(empty($sections)) { ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-exclamation-triangle"></i>&nbsp;¡No se hallaron registros de material de apoyo!
                            </div>
                        <?php } else { ?>
                            <?php foreach ($sections as $dataSection) : ?>
                                <div class="row">
                                    <div class="col-12">
                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                        <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                            <div class="card">
                                                <div class="card-header">
                                                    <h3 class="card-title"><strong><?php echo $dataSection['title_section']; ?></strong></h3>
                                                    <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                    <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                        <div class="card-tools">
                                                            
                                                            <div class="dropdown" style="margin-top:-5px;">
                                                                <button class="btn btn-secondary" type="button" id="dropdownMenuButton_<?php echo $dataSection['id_section']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: transparent; border: none; font-size:15px;">
                                                                    <i class="fas fa-ellipsis-v" style="color: black; background-color: transparent;"></i>
                                                                </button>
                                                                
                                                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton_<?php echo $dataSection['id_section']; ?>">
                                                                    <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                                    <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                                        <a class="dropdown-item" href="#" data-section-savedocuments-id="<?php echo $dataSection['id_section']; ?>">
                                                                            <i class="fas fa-cloud-upload-alt" style="margin-right: 5px;"></i> Cargar documentos
                                                                        </a>
                                                                    <?php } ?>
                                                                    <hr>
                                                                    <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                                    <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                                        <a class="dropdown-item" href="#" data-section-changedata-id="<?php echo $dataSection['id_section']; ?>">
                                                                            <i class="fas fa-user-lock" style="margin-right: 5px;"></i> Modificar información
                                                                        </a>
                                                                    <?php } ?>

                                                                    <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                                    <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                                        <form action="resources.php" method="POST">
                                                                            <input name="deleteSection[idSection]" type="text" class="form-control" value="<?php echo $dataSection['id_section']; ?>" readonly style="display:none;" hidden>
                                                                            <input name="deleteSection[keySection]" type="text" class="form-control" value="<?php echo $dataSection['key_section']; ?>" readonly style="display:none;" hidden>
                                                                            <button class="dropdown-item" type="submit" name="action" value="eliminatedSection" onclick="return confirm('¿Estás seguro de eliminar el material de apoyo?');">
                                                                                <i class="fas fa-trash" style="margin-left:5px; margin-right: 5px;"></i> Mover a la papelera
                                                                            </button>
                                                                        </form>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                        
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                                
                                                <div class="card-body">
                                                    <ul style="list-style-type: none !important; padding-left: 0;" class="listaDocumentos" id="listaDocumentos_<?php echo $dataSection['id_section']; ?>" data-section-id="<?php echo $dataSection['id_section']; ?>">
                                                        <?php $documents = $controller->getDocumentsSection($dataSection['id_section']);
                                                        if(empty($documents)) { ?>
                                                            <div>Sin documentos</div>
                                                        <?php } else {
                                                            foreach ($documents AS $document) :  ?>
                                                                <li data-id="<?php echo $document['id_material']; ?>" class="document-item" style="margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                                                                    <div class="document-info" style="display: flex; align-items: center;">
                                                                        <!-- Ícono de arrastre -->
                                                                        <i class="fas fa-bars handle" style="cursor: grab; margin-right: 10px;"></i>
                                                                        
                                                                        <?php if ($document['file_extension_material'] == 'pdf' || $document['file_extension_material'] == 'PDF') { ?>
                                                                            <a href="extensions/open_file.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" target='_blank' class="btn btn-danger btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file-pdf"></i> <!-- Ícono de PDF en rojo -->
                                                                            </a>
                                                                            
                                                                            <a href="extensions/open_file.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" target='_blank' class="document-name" style="margin-left: 10px;"><?php echo $document['file_name_material']; ?></a>
                                                                            
                                                                        <?php } elseif ($document['file_extension_material'] == 'doc' || $document['file_extension_material'] == 'docx') { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" download class="btn btn-primary btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file-word"></i> <!-- Ícono de Word en azul -->
                                                                            </a>
                                                                            
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" download class="document-name" style="margin-left: 10px;"><?php echo $document['file_name_material']; ?></a>
                                                                            
                                                                        <?php } elseif ($document['file_extension_material'] == 'xls' || $document['file_extension_material'] == 'xlsx') { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="btn btn-success btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file-excel"></i> <!-- Ícono de Excel en verde -->
                                                                            </a>
                                                                            
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="document-name" style="margin-left: 10px;"><?php echo $document['file_name_material']; ?></a>
                                                                            
                                                                        <?php } elseif ($document['file_extension_material'] == 'ppt' || $document['file_extension_material'] == 'pptx') { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="btn btn-sm" style="background-color:#C84320; color:white; margin-left: 5px;">
                                                                                <i class="fas fa-file-powerpoint"></i> <!-- Ícono de PowerPoint en naranja -->
                                                                            </a>
                                                                        
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="document-name" style="margin-left: 10px;"><?php echo $document['file_name_material']; ?></a>
                                                                            
                                                                        <?php } else { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="btn btn-secondary btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file"></i> <!-- Ícono genérico de documento -->
                                                                            </a>
                                                                            
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="document-name" style="margin-left: 10px;"><?php echo $document['file_name_material']; ?></a>
                                                                        <?php } ?>
                                                                    </div>
                                                                    <!-- Ícono de eliminar -->
                                                                    <form action="resources.php" method="POST">
                                                                        <input name="dataDeleteMaterial[id_material]" type="text" class="form-control" value="<?php echo $document['id_material']; ?>" readonly hidden style="display: none;">
                                                                        <input name="dataDeleteMaterial[key_material]" type="text" class="form-control" value="<?php echo $document['key_material']; ?>" readonly hidden style="display: none;">
                                                                        <input name="dataDeleteMaterial[id_section_material]" type="text" class="form-control" value="<?php echo $document['id_section_material']; ?>" readonly hidden style="display: none;">
                                                                        
                                                                        <button class="btn btn-danger btn-sm delete-button-material-section" style="cursor: pointer;" name="action" value="deleteMaterial" onclick="return confirm('¿Estás seguro de eliminar el documento seleccionado?');">
                                                                            <i class="fas fa-trash-alt"></i>
                                                                        </button>
                                                                    </form>
                                                                </li>

                                                            <?php endforeach; 
                                                        } ?>
                                                    </ul>

                                                </div>
                                            </div>

                                        <?php 
                                        // COMPROBAMOS QUE EL TIPO DE USUARIO SEA EMPLEADO (2)
                                        } else if ($_SESSION['user']['id_type_user'] == 2) { 
                                            if ($dataSection['chk_view_empl'] == 'Si'){?>
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h3 class="card-title"><strong><?php echo $dataSection['title_section']; ?></strong></h3>
                                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                        <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                            <div class="card-tools">
                                                                
                                                                <div class="dropdown" style="margin-top:-5px;">
                                                                    <button class="btn btn-secondary" type="button" id="dropdownMenuButton_<?php echo $dataSection['id_section']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: transparent; border: none; font-size:15px;">
                                                                        <i class="fas fa-ellipsis-v" style="color: black; background-color: transparent;"></i>
                                                                    </button>
                                                                    
                                                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton_<?php echo $dataSection['id_section']; ?>">
                                                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                                        <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                                            <a class="dropdown-item" href="#" data-section-savedocuments-id="<?php echo $dataSection['id_section']; ?>">
                                                                                <i class="fas fa-cloud-upload-alt" style="margin-right: 5px;"></i> Cargar documentos
                                                                            </a>
                                                                        <?php } ?>
                                                                        <hr>
                                                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                                        <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                                            <a class="dropdown-item" href="#" data-section-changedata-id="<?php echo $dataSection['id_section']; ?>">
                                                                                <i class="fas fa-user-lock" style="margin-right: 5px;"></i> Modificar información
                                                                            </a>
                                                                        <?php } ?>

                                                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                                        <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                                            <form action="resources.php" method="POST">
                                                                                <input name="deleteSection[idSection]" type="text" class="form-control" value="<?php echo $dataSection['id_section']; ?>" readonly style="display:none;" hidden>
                                                                                <input name="deleteSection[keySection]" type="text" class="form-control" value="<?php echo $dataSection['key_section']; ?>" readonly style="display:none;" hidden>
                                                                                <button class="dropdown-item" type="submit" name="action" value="eliminatedSection" onclick="return confirm('¿Estás seguro de eliminar el material de apoyo?');">
                                                                                    <i class="fas fa-trash" style="margin-left:5px; margin-right: 5px;"></i> Mover a la papelera
                                                                                </button>
                                                                            </form>
                                                                        <?php } ?>
                                                                    </div>
                                                                </div>
                                                            
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                    
                                                    <div class="card-body">
                                                        <ul style="list-style-type: none; padding-left: 0;">
                                                            <?php $documents = $controller->getDocumentsSection($dataSection['id_section']);
                                                            if(empty($documents)) { ?>
                                                            <div>Sin documentos</div>
                                                            <?php } else {
                                                                foreach ($documents AS $document) :  ?>
                                                                    <li style="margin-bottom: 10px;">
                                                                        <?php if ($document['file_extension_material'] == 'pdf' || $document['file_extension_material'] == 'PDF') { ?>
                                                                            <a href="extensions/open_file.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" target='_blank' class="btn btn-danger btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file-pdf"></i> <!-- Ícono de PDF en rojo -->
                                                                            </a>
                                                                            <a href="extensions/open_file.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" target='_blank' style="margin-left: 5px;"><?php echo $document['file_name_material']; ?></a>
                                                                            
                                                                        <?php } elseif ($document['file_extension_material'] == 'doc' || $document['file_extension_material'] == 'docx') { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" download class="btn btn-primary btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file-word"></i> <!-- Ícono de Word en azul -->
                                                                            </a>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" download style="margin-left: 5px;"><?php echo $document['file_name_material']; ?></a>

                                                                        <?php } elseif ($document['file_extension_material'] == 'xls' || $document['file_extension_material'] == 'xlsx') { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="btn btn-success btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file-excel"></i> <!-- Ícono de Excel en verde -->
                                                                            </a>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" style="margin-left: 5px;"><?php echo $document['file_name_material']; ?></a>

                                                                        <?php } elseif ($document['file_extension_material'] == 'ppt' || $document['file_extension_material'] == 'pptx') { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="btn btn-sm" style="background-color:#C84320; color:white; margin-left: 5px;">
                                                                                <i class="fas fa-file-powerpoint"></i> <!-- Ícono de PowerPoint en naranja -->
                                                                            </a>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" style="margin-left: 5px;"><?php echo $document['file_name_material']; ?></a>

                                                                        <?php } else { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="btn btn-secondary btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file"></i> <!-- Ícono genérico de documento -->
                                                                            </a>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" style="margin-left: 5px;"><?php echo $document['file_name_material']; ?></a>
                                                                        <?php } ?>
                                                                    </li>

                                                                <?php endforeach; 
                                                            } ?>
                                                        </ul>

                                                    </div>
                                                </div>
                                            <?php } ?>
                                        <?php 
                                        // COMPROBAMOS QUE EL TIPO DE USUARIO SEA VENDEDOR (3)
                                        } else if ($_SESSION['user']['id_type_user'] == 3) {
                                            if ($dataSection['chk_view_sales'] == 'Si'){ ?>
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h3 class="card-title"><strong><?php echo $dataSection['title_section']; ?></strong></h3>
                                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                        <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                            <div class="card-tools">
                                                                
                                                                <div class="dropdown" style="margin-top:-5px;">
                                                                    <button class="btn btn-secondary" type="button" id="dropdownMenuButton_<?php echo $dataSection['id_section']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: transparent; border: none; font-size:15px;">
                                                                        <i class="fas fa-ellipsis-v" style="color: black; background-color: transparent;"></i>
                                                                    </button>
                                                                    
                                                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton_<?php echo $dataSection['id_section']; ?>">
                                                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                                        <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                                            <a class="dropdown-item" href="#" data-section-savedocuments-id="<?php echo $dataSection['id_section']; ?>">
                                                                                <i class="fas fa-cloud-upload-alt" style="margin-right: 5px;"></i> Cargar documentos
                                                                            </a>
                                                                        <?php } ?>
                                                                        <hr>
                                                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                                        <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                                            <a class="dropdown-item" href="#" data-section-changedata-id="<?php echo $dataSection['id_section']; ?>">
                                                                                <i class="fas fa-user-lock" style="margin-right: 5px;"></i> Modificar información
                                                                            </a>
                                                                        <?php } ?>

                                                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                                        <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                                            <form action="resources.php" method="POST">
                                                                                <input name="deleteSection[idSection]" type="text" class="form-control" value="<?php echo $dataSection['id_section']; ?>" readonly style="display:none;" hidden>
                                                                                <input name="deleteSection[keySection]" type="text" class="form-control" value="<?php echo $dataSection['key_section']; ?>" readonly style="display:none;" hidden>
                                                                                <button class="dropdown-item" type="submit" name="action" value="eliminatedSection" onclick="return confirm('¿Estás seguro de eliminar el material de apoyo?');">
                                                                                    <i class="fas fa-trash" style="margin-left:5px; margin-right: 5px;"></i> Mover a la papelera
                                                                                </button>
                                                                            </form>
                                                                        <?php } ?>
                                                                    </div>
                                                                </div>
                                                            
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                    
                                                    <div class="card-body">
                                                        <ul style="list-style-type: none; padding-left: 0;">
                                                            <?php $documents = $controller->getDocumentsSection($dataSection['id_section']);
                                                            if(empty($documents)) { ?>
                                                            <div>Sin documentos</div>
                                                            <?php } else {
                                                                foreach ($documents AS $document) :  ?>
                                                                    <li style="margin-bottom: 10px;">
                                                                        <?php if ($document['file_extension_material'] == 'pdf' || $document['file_extension_material'] == 'PDF') { ?>
                                                                            <a href="extensions/open_file.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" target='_blank' class="btn btn-danger btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file-pdf"></i> <!-- Ícono de PDF en rojo -->
                                                                            </a>
                                                                            <a href="extensions/open_file.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" target='_blank' style="margin-left: 5px;"><?php echo $document['file_name_material']; ?></a>
                                                                            
                                                                        <?php } elseif ($document['file_extension_material'] == 'doc' || $document['file_extension_material'] == 'docx') { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" download class="btn btn-primary btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file-word"></i> <!-- Ícono de Word en azul -->
                                                                            </a>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" download style="margin-left: 5px;"><?php echo $document['file_name_material']; ?></a>

                                                                        <?php } elseif ($document['file_extension_material'] == 'xls' || $document['file_extension_material'] == 'xlsx') { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="btn btn-success btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file-excel"></i> <!-- Ícono de Excel en verde -->
                                                                            </a>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" style="margin-left: 5px;"><?php echo $document['file_name_material']; ?></a>

                                                                        <?php } elseif ($document['file_extension_material'] == 'ppt' || $document['file_extension_material'] == 'pptx') { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="btn btn-sm" style="background-color:#C84320; color:white; margin-left: 5px;">
                                                                                <i class="fas fa-file-powerpoint"></i> <!-- Ícono de PowerPoint en naranja -->
                                                                            </a>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" style="margin-left: 5px;"><?php echo $document['file_name_material']; ?></a>

                                                                        <?php } else { ?>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" class="btn btn-secondary btn-sm" style="margin-left: 5px;">
                                                                                <i class="fas fa-file"></i> <!-- Ícono genérico de documento -->
                                                                            </a>
                                                                            <a href="extensions/download_archive.php?section=<?php echo urlencode($dataSection['key_section']); ?>&file=<?php echo urlencode($document['file_name_material']); ?>" style="margin-left: 5px;"><?php echo $document['file_name_material']; ?></a>
                                                                        <?php } ?>
                                                                    </li>

                                                                <?php endforeach; 
                                                            } ?>
                                                        </ul>

                                                    </div>
                                                </div>
                                            <?php } 
                                        } ?>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php } ?>
                    </div>
                </div>    
            
            </div>
        </div>
        
        <!-- Modal para agregar una nueva sección -->
        <div class="modal fade" id="modalAddNewSection" tabindex="-1" aria-labelledby="modalAddNewSectionLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAddNewSectionLabel">Agregar nueva sección</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Formulario para agregar una nueva carpeta -->
                        <form id="formAddSection" action="resources.php" method="POST">
                            <input name="newSection[id_user_section]" type="text" class="form-control" id="id_user_section" required value="<?php echo $_SESSION['user']['id_user']; ?>" readonly style="display:none;" hidden>
                            <input name="newSection[key_section]" type="text" class="form-control" id="key_section" required value="SEC-<?php echo $keySection; ?>" readonly style="display:none;" hidden>
                            
                            <div class="form-group">
                                <label>Nombre de la sección:</label>
                                <input type="text" name="newSection[title_section]" class="form-control" id="title_section" required autocomplete="off">
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <label>Acceso a la sección:</label>
                                </div>
                            </div>

                            <!-- Checkboxes organizados en dos filas -->
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="opcion1" value="Si" name="newSection[chk_view_empl]">
                                        <label class="form-check-label" for="opcion1">Dpto. Empleados</label>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="opcion2" value="Si" name="newSection[chk_view_sales]">
                                        <label class="form-check-label" for="opcion2">Dpto. Ventas</label>
                                    </div>
                                </div>
                            </div>
                            <br>
                            
                            <button type="submit" class="btn btn-lg btn-block" style="background-color: #37424A; color: #ffffff;" name="action" value="createSection">Guardar</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
        
        <!-- Modal para agregar nuevos documentos a una section-->
        <div class="modal fade" id="modalSaveDocumentsSection" tabindex="-1" aria-labelledby="modalSaveDocumentsSectionLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSaveDocumentsSectionLabel">Agregar documentos</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="formAddDocumentsSection" action="#" method="POST" enctype="multipart/form-data">
                            <input type="text" name="saveDocuments[idSectionDocuments]" id="idSectionDocuments" readonly style="display:none;" hidden>
                            <input type="text" name="saveDocuments[keySection]" id="keySection" readonly style="display:none;" hidden>

                            <!--CAMPO DE SELECCIÓN DE ARCHIVOS-->
                            <div class="form-group">
                                <small style="color:red;">*Selecciona uno o varios archivos.</small>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3 files color">
                                        <input type="file" class="form-control" id="miarchivo" name="miarchivo[]" multiple required accept=".pdf, .doc, .docx, .xls, .xlsx, .ppt, .pptx, .txt">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-lg btn-block" style="background-color: #37424A; color: #ffffff;" name="action" value="saveFullDocuments">Guardar documentos</button>
                            <hr>
                            <!-- Contenedor de vista previa de archivos -->
                            <div id="preview-container" style="max-height: 210px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; display: none;"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal para editar los datos de una sección -->
        <div class="modal fade" id="modalEditSection" tabindex="-1" aria-labelledby="modalEditSectionLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditSectionLabel">Editar sección</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Formulario para agregar una nueva carpeta -->
                        <form id="formEditSection" action="resources.php" method="POST">
                            <input name="editSection[id_section]" type="text" class="form-control" id="edit_id_section" required readonly style="display:none;" hidden>
                            <input name="editSection[key_section]" type="text" class="form-control" id="edit_key_section" required readonly style="display:none;" hidden>
                            
                            <div class="form-group">
                                <label>Nombre de la sección:</label>
                                <input type="text" name="editSection[title_section]" class="form-control" id="edit_title_section" required autocomplete="off">
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <label>Acceso a la sección:</label>
                                </div>
                            </div>

                            <!-- Checkboxes organizados en dos filas -->
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_chk_view_empl" value="Si" name="editSection[chk_view_empl]">
                                        <label class="form-check-label" for="edit_chk_view_empl">Dpto. Empleados</label>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_chk_view_sales" value="Si" name="editSection[chk_view_sales]">
                                        <label class="form-check-label" for="edit_chk_view_sales">Dpto. Ventas</label>
                                    </div>
                                </div>
                            </div>
                            <br>
                            
                            <button type="submit" class="btn btn-lg btn-block" style="background-color: #37424A; color: #ffffff;" name="action" value="updateSection">Actualizar</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
        
        <script src="../../resources/plugins/jquery/jquery.min.js"></script>
        <script src="../../resources/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../resources/dist/js/adminlte.min.js"></script>
        <!--<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>-->
        <script src="../../resources/js/Sortable.min.js"></script>
        <script src="../../resources/js/notifications.js"></script>
        <script src="../../resources/js/tracings.js"></script>
        <script src="../../resources/js/notify_folders.js"></script>
        <!--ESTE ES EL SCRIPT QUE SE EJECUTA PARA MOSTRAR EL OVERLAY-->
        <script>
            document.getElementById('modalSaveDocumentsSection').addEventListener('submit', function() {
                document.getElementById('loadingOverlay').style.display = 'flex';
            });
        </script>

        <!--SCRIPT PARA VALIDAR LA ENTRADA DE ARCHIVOS Y QUE SOLO SE PERMITAN CIERTOS DOCUMENTOS-->
        <script>
            document.getElementById('miarchivo').addEventListener('change', function() {
                const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
                const files = this.files;
                for (let i = 0; i < files.length; i++) {
                    const fileExtension = files[i].name.split('.').pop().toLowerCase();
                    if (!allowedExtensions.includes(fileExtension)) {
                        alert(`El archivo "${files[i].name}" no tiene un formato permitido.`);
                        this.value = ''; // Limpia el input
                        break;
                    }
                }
            });

        </script>

        <!--SCRIPT PARA ABRIR EL MODAL CON EL INPUT PARA EL REGISTRO Y CARGA DE DOCUMENTOS-->
        <script>
            $(document).ready(function () {
                // Acción de clic en "Cargar documentos"
                $('.dropdown-item[data-section-savedocuments-id]').click(function (e) {
                    e.preventDefault();
                    var sectionId = $(this).data('section-savedocuments-id');
                    $.ajax({
                        type: "GET",
                        url: "../../app/webservice.php",
                        data: {
                            action: "getSectionDetail",
                            idSection: sectionId
                        }
                    }).done(function (response) {
                        var parsedResponse = JSON.parse(response);
                        $('#idSectionDocuments').val(parsedResponse.id_section);
                        $('#keySection').val(parsedResponse.key_section);
                        // Mostrar el modal de carga de documentos para una section
                        $('#modalSaveDocumentsSection').modal('show');
                    });
                });
            });
        </script>

        <!--SCRIPT PARA LA EDICIÓN DE DATOS DE UNA SECCIÓN (TITULO Y PERMISOS)-->
        <script>
            $(document).ready(function () {
                // Acción de clic en "Modificar información"
                $('.dropdown-item[data-section-changedata-id]').click(function (e) {
                    e.preventDefault();
                    var sectionId = $(this).data('section-changedata-id');
                    $.ajax({
                        type: "GET",
                        url: "../../app/webservice.php",
                        data: {
                            action: "getSectionDetail",
                            idSection: sectionId
                        }
                    }).done(function (response) {
                        var parsedResponse = JSON.parse(response);
                        $('#edit_id_section').val(parsedResponse.id_section);
                        $('#edit_key_section').val(parsedResponse.key_section);
                        $('#edit_title_section').val(parsedResponse.title_section);
                        $('#edit_chk_view_empl').prop('checked', parsedResponse.chk_view_empl === "Si");
                        $('#edit_chk_view_sales').prop('checked', parsedResponse.chk_view_sales === "Si");
                        
                        // Mostrar el modal de edición de datos de una section
                        $('#modalEditSection').modal('show');
                    });
                });
            });
        </script>

        <!--ESTE ES EL CÓDIGO PARA MOSTRAR Y ELIMINAR LA VISTA PREVIA DE LOS ARCHIVOS QUE SE SUBEN AL INPUT MULTIPLE-->
        <script>
            document.getElementById('miarchivo').addEventListener('change', function(event) {
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
                    
                    removeButton.addEventListener('click', function(e) {
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
                    
                    removeButton.addEventListener('click', function(e) {
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
        
        <!--SCRIPT PARA ORDENAR LOS DOCUMENTOS ARRASTRANDO Y POSICIONARLOS EN UN ORDEN DEFINIDO-->
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Selecciona todas las listas dinámicas por su clase
                const lists = document.querySelectorAll('.listaDocumentos');
                
                lists.forEach(list => {
                    // Inicializa Sortable para cada lista
                    new Sortable(list, {
                        animation: 150, // Animación al arrastrar
                        onEnd: function (evt) {
                            // Obtener el nuevo orden de la lista actual
                            const items = list.querySelectorAll('li');
                            const order = Array.from(items).map(item => item.dataset.id);
                            
                            // Enviar el nuevo orden al webservice
                            $.ajax({
                                type: "POST",
                                url: "../../app/webservice.php",
                                data: {
                                    action: "updateDocumentOrder",
                                    order: order, // Enviar el nuevo orden como un array
                                    sectionId: list.dataset.sectionId // Enviar el ID de la sección si es necesario
                                }
                            }).done(function (response) {
                                const parsedResponse = JSON.parse(response);
                                if (parsedResponse.success) {
                                    //console.log('Orden actualizado correctamente');
                                } else {
                                    //console.error('Error al actualizar el orden:', parsedResponse.error);
                                }
                            }).fail(function (jqXHR, textStatus, errorThrown) {
                                //console.error('Error en la petición:', textStatus, errorThrown);
                            });
                        },
                    });
                });
            });
        </script>

    </body>
</html>