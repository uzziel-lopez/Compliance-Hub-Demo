<?php
    session_start();
    include "../../app/config.php";
    //include "../../app/debug.php";
    include "../../app/WebController.php";
    $controller = new WebController();
    //FORZAR A QUE ESTA VISTA NO SE MUESTRE, ES FUNCIONAL PERO EL CLIENTE NO LO REQUIERE
    header("Location: ../../index.php");
    
    // Verificar si la sesión del usuario está activa
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        // Si no hay sesión activa, destruir la sesión
        session_destroy();
        // Redirigir a la página de inicio de sesión
        header("Location: ../../login.php");
        exit(); // Es importante salir después de redirigir para evitar que el código siguiente se ejecute innecesariamente
    }
    //FUNCIÓN PARA CREAR UNA NUEVA CARPETA
    // Verifica si se ha dado clic en algun boton a traves del action
    if(!empty($_POST['action'])){
        if($_POST['action'] == 'deleteDocument'){
            // Llama al método para eliminar el documento y obtiene el ID del documento eliminado
            $idDocument = $controller->deleteDocument($_POST['deleteDocument']);
            // Si se elimina el documento correctamente, redirecciona a la página de documentos
            if($idDocument){
                header("location: documents.php");
            }
        }
        // Si la acción es 'updateDocument', se intenta actualizar la data del documento
        else if($_POST['action'] == 'updateDocument'){
            // Llama al método para actualizar la data del documento y obtiene el ID del documento actualizado
            $idDocument = $controller->updateDocument($_POST['updateDocument']);
            // Si se actualiza la data correctamente, redirecciona a la página de documentos
            if($idDocument){
                header("location: documents.php");
            }
        }
    }
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
        <link rel="icon" href="../../resources/img/icono.png">
        <script src="../../resources/js/jquery-3.5.1.min.js"></script>
        <style>
            .truncate-text {
                max-width: 100px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        </style>
    </head>
    
    <body class="hold-transition sidebar-mini">
        <div class="wrapper" style="padding-top: 57px;">
            <?php include "../templates/navbar.php"; ?>
            <div class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">
                        
                        <div class="row justify-content-between mb-2">
                            <div class="col-sm-8">
                                <h1 class="m-0 text-dark">Lista completa de documentos</h1>
                            </div>
                            <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O VENTAS (3)-->
                            <?php if($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3){ ?>
                                <div class="col-sm-4 text-right">
                                    <a href="../folders/folders.php" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;" role="button" aria-pressed="true"><i class="fas fa-plus pr-2"></i>Agregar nuevo documento</a>
                                </div>
                            <?php } ?>
                        </div>
                        
                        <!--FILTRO DE BUSQUEDA DE DOCUMENTOS POR MES-->
                        <form class="row mb-2" action="#" method="get">
                            <div class="col-sm-12">
                                <div class="row">
                                    <div class="input-group col-4 col-sm-3">
                                        <select class="form-control filtrosDDL" id="month_select" name ="monthSelect">
                                            <option value="all">Todos</option>
                                            <option value="01">Enero</option>
                                            <option value="02">Febrero</option>
                                            <option value="03">Marzo</option>
                                            <option value="04">Abril</option>
                                            <option value="05">Mayo</option>
                                            <option value="06">Junio</option>
                                            <option value="07">Julio</option>
                                            <option value="08">Agosto</option>
                                            <option value="09">Septiembre</option>
                                            <option value="10">Octubre</option>
                                            <option value="11">Noviembre</option>
                                            <option value="12">Diciembre</option>
                                        </select>
                                    </div>
                                    <div class="input-group col-8 col-sm-3">
                                        <select id="yearSelect" class="form-control filtrosDDL" name="year_select"></select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="content">
                        <div class="container-fluid">
                            <strong>Total de documentos: &nbsp;<b id="numTotalsDocuments"></b></strong>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped table-bordered" id="tblDocuments">
                                                    <thead>
                                                        <th></th>
                                                        <th>Carpeta</th>
                                                        <th>Documento</th>
                                                        <th>Ext</th>
                                                        <!--<th>Estatus</th>-->
                                                        <!--<th>Plazo</th>-->
                                                        <th>Autor</th>
                                                        <th>Fecha de alta</th>
                                                        <th>Acciones</th>
                                                    </thead>
                                                    <tbody id="dataDocs"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                </div>
            </div>
        </div>
        
        <!--SIN USO (EN ESTE MODAL SE VISUALIZABAN LOS DETALLES DEL DOCUMENTO Y SE PODIA ACTUALIZAR SU PLAZO DE VENCIMIENTO)-->
        <!-- Modal para editar un documento -->
        <div class="modal fade" id="modalEditarDocument" tabindex="-1" aria-labelledby="modalEditarDocumentLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O EMPLEADO (2 )-->
                        <?php if($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 2){ ?>
                            <h5 class="modal-title" id="modalEditarDocumentLabel">Editar documento</h5>
                        <?php } else { ?>
                            <h5 class="modal-title" id="modalEditarDocumentLabel">Detalles del documento</h5>
                        <?php } ?>
                        
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Formulario para editar un documento -->
                        <form id="formEditarCarpeta" action="#" method="POST">
                            <input type="hidden" class="form-control" name="updateDocument[id_document]" id="edit_id_document" style="display:none;" hidden readonly>
                            <input type="hidden" class="form-control" name="updateDocument[key_document]" id="edit_key_document" style="display:none;" hidden readonly>
                            <div class="form-group">
                                <label for="edit_file_name_document">Nombre del documento:</label>
                                <input type="text" class="form-control" id="edit_file_name_document" required readonly disabled>
                            </div>
                            <div class="form-group">
                                <label for="edit_name_folder">Carpeta:</label>
                                <input type="text" class="form-control" id="edit_name_folder" required readonly disabled>
                            </div>
                            <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O EMPLEADO (2 )-->
                            <?php if($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 2){ ?>
                                <div class="form-group">
                                    <label for="edit_first_fech_document">Plazo:</label>
                                    <input type="date" name="updateDocument[first_fech_document]" class="form-control" id="edit_first_fech_document" required style="margin-bottom:10px;">
                                    <input type="date" name="updateDocument[second_fech_document]" class="form-control" id="edit_second_fech_document" required>
                                </div>
                            <?php } else { ?>
                                <div class="form-group">
                                    <label for="edit_first_fech_document">Plazo:</label>
                                    <input type="date" class="form-control" id="edit_first_fech_document" required style="margin-bottom:10px;" readonly disabled>
                                    <input type="date" class="form-control" id="edit_second_fech_document" required readonly disabled>
                                </div>
                            <?php } ?>

                            <div class="form-group">
                                <label for="edit_name_user">Autor:</label>
                                <input type="text" class="form-control" id="edit_name_user" required readonly disabled>
                            </div>

                            <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O EMPLEADO (2 )-->
                            <?php if($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 2){ ?>
                                <button type="submit" class="btn btn-lg btn-block" style="background-color: #37424A; color: #ffffff;" name="action" value="updateDocument">Actualizar</button>
                            <?php } ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="../../resources/plugins/jquery/jquery.min.js"></script>
        <script src="../../resources/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../resources/plugins/datatables/jquery.dataTables.js"></script>
        <script src="../../resources/plugins/datatables-bs4/js/dataTables.bootstrap4.js"></script>
        <script src="../../resources/dist/js/adminlte.min.js"></script>
        <script src="../../resources/js/notifications.js"></script>
        <script src="../../resources/js/tracings.js"></script>
        <script src="../../resources/js/notify_folders.js"></script>
        <script>
        // Asignar valores de la variable de sesión a variables JavaScript
            var userId = <?php echo json_encode($_SESSION['user']['id_user']); ?>;
            var userType = <?php echo json_encode($_SESSION['user']['id_type_user']); ?>;
        </script>

        <script>
            // Obtén el elemento select
            var yearSelect = document.getElementById("yearSelect");
            var currentYear = new Date().getFullYear(); // Obtiene el año actual
            // Crea un bucle para generar opciones desde un año específico hasta el año actual
            for (var year = 2022; year <= currentYear; year++) {
                var option = document.createElement("option"); // Crea un elemento de opción
                option.value = year; // Establece el valor de la opción al año
                option.text = year; // Establece el texto de la opción al año
                yearSelect.appendChild(option); // Agrega la opción al select
                // Si el año actual coincide con el año en el bucle, selecciona la opción
                if (year === currentYear) {
                    option.selected = true;
                }
            }
        </script>
        <!--FUNCIÓN PARA MARCAR EL SELECT CON EL MES ACTUAL-->
        <script>
            // Obtener el elemento select por su id
            var monthSelect = document.getElementById('month_select');
            // Obtener el mes actual (1-12)
            var currentMonth = new Date().getMonth() + 1;
            // Convertir el mes actual a dos dígitos
            var formattedMonth = currentMonth < 10 ? '0' + currentMonth : currentMonth.toString();
            // Configurar la opción seleccionada para el mes actual
            monthSelect.value = formattedMonth;
        </script>
        
        <!--ESTE SCRIPT SOLO ES PARA CUANDO LOS FILTROS OBTIENEN UN CAMBIO DE FECHA-->
        <script>
            $(document).ready(function(){
                $('.filtrosDDL').on('change', function() {
                    //Las variables de yearSelect y month_select corresponden a los 2 select de mes y año
                    var yearSelect = $("#yearSelect").val(),
                    month_select = $("#month_select").val();
                    
                    if(month_select === 'all'){
                        var fecha1 = yearSelect + "-01-01 00:00:00";
                        var fecha2 = yearSelect + "-12-31 23:59:59";
                    } else {
                        var fecha1 = yearSelect + "-" + month_select + "-01 00:00:00";
                        var fecha2 = yearSelect + "-" + month_select + "-31 23:59:59";
                    }
                    loadDocuments(fecha1, fecha2);
                });
            });
        </script>
        <!--ESTE SCRIPT ME MUESTRA LAS FECHAS QUE ESTAN POR DEFECTO EN LOS SELECTS-->
        <script>
            $(function () {
                var yearSelect = $("#yearSelect").val(),
                month_select = $("#month_select").val();
                var fecha1 = yearSelect + "-" + month_select + "-01 00:00:00";
                var fecha2 = yearSelect + "-" + month_select + "-31 23:59:59";
                loadDocuments(fecha1, fecha2);
            });
        </script>
        
        <!--ESTA ES LA FUNCIÓN QUE VA A CARGAR LOS DOCUMENTOS EN LA VISTA-->
        <script>
            function loadDocuments(fecha1, fecha2) {
                var table = $('#tblDocuments').DataTable({
                    language: {
                        "decimal": "",
                        "emptyTable": "No hay información",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                        "infoFiltered": "(Filtrado de _MAX_ total entradas)",
                        "infoPostFix": "",
                        "thousands": ",",
                        "lengthMenu": "Mostrar _MENU_ registros",
                        "loadingRecords": "Cargando...",
                        "processing": "Procesando...",
                        "search": "Buscar:",
                        "zeroRecords": "Sin resultados encontrados",
                        "paginate": {
                            "first": "Primero",
                            "last": "Ultimo",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        }
                    },
                    "destroy": true
                });
                //var table = $('#tblDocuments').DataTable();
                table.clear().draw();
                
                $.ajax({
                    type: "GET",
                    url: "../../app/webservice.php",
                    data: { 
                        action: "getAllDocuments",
                        fecha1: fecha1,
                        fecha2: fecha2
                    }
                }).done(function(response) {
                    var parsedResponse = JSON.parse(response);
                    $("#numTotalsDocuments").html(parsedResponse.length);
                    $("#dataDocs").empty();
                    parsedResponse.forEach(function(item) {
                        // Función para formatear un número de día o mes con un 0 inicial si es necesario
                        function formatNumberWithZero(number) {
                            return number < 10 ? "0" + number : number;
                        }
                        // Función para formatear la fecha en formato "dd/mm/yyyy"
                        function formatDate(dateString) {
                            var date = new Date(dateString);
                            // Ajustar la fecha para considerar la zona horaria
                            var day = formatNumberWithZero(date.getUTCDate()); // getDate() para día local, getUTCDate() para día UTC
                            var month = formatNumberWithZero(date.getUTCMonth() + 1); // getMonth() para mes local, getUTCMonth() para mes UTC
                            var year = date.getUTCFullYear(); // getFullYear() para año local, getUTCFullYear() para año UTC
                            return day + "/" + month + "/" + year;
                        }
                        
                        var newRow =
                        "<tr>" + 
                            "<td style='text-align:center;'>" + 
                                "<a href='../../uploads/documents/" + item.key_folder + "/" + item.file_name_document + "' target='_blank' class='btn btn-danger'><i class='fas fa-file-pdf'></i></a>" +
                            "</td>" + 
                            
                            "<td style='text-align:left;' class='truncate-text'>" + 
                                "<a href='../folders/subfolder.php?id=" + item.id_folder + "&key=" + item.key_folder +"'>" + item.name_folder + "</a>" + 
                            "</td>" + 
                            
                            "<td style='text-align:left;' class='truncate-text'>" + item.file_name_document + "</td>" +
                            "<td style='text-align:center;' class='truncate-text'>" + "." + item.file_extension_document + "</td>";
                            
                            //ESTA CONDICIÓN MOSTRABA UNAS ALERTAS CON EL ESTATUS DEL DOCUMENTO CON BASE AL PLAZO DE VENCIMIENTO
                            /*
                            if (item.dias >= 1) {
                                //newRow += "<td style='text-align:center;'>" + item.dias + "</td>";
                                newRow += "<td style='text-align:center;'><div class='alert alert-danger' role='alert' style='text-align:center;'>Vencido</div></td>";
                            } else if (item.dias >= -60) {
                                //newRow += "<td style='text-align:center;'>" + item.dias + "</td>";
                                newRow += "<td style='text-align:center;'><div class='alert alert-warning' role='alert' style='text-align:center; color:white; background-color: orange;'>Cerca de Vencimiento</div></td>";
                            } else {
                                //newRow += "<td style='text-align:center;'>" + item.dias + "</td>";
                                newRow += "<td style='text-align:center;'><div class='alert alert-success' role='alert' style='text-align:center;'>Vigente</div></td>";
                            }
                            */
                            
                            //ESTA COLUMNA MOSTRABA EL PLAZO DE VENCIMIENTO DEL DOCUMENTO
                            //newRow += "<td style='text-align:center; width:15%;'>" + formatDate(item.first_fech_document) + " - " + formatDate(item.second_fech_document) + "</td>" + 
                            
                            newRow +=
                            "<td style='text-align:center;' class='truncate-text'>" + item.name_user + "</td>" + 
                            "<td style='text-align:center;'>" + formatDate(item.created_at_document) + "</td>" + 
                            
                            "<td style='text-align:center;'>" + 
                                "<div class='dropdown'>" + 
                                    "<button class='btn btn-primary dropdown-toggle' type='button' id='dropdownMenuButton_" + item.id_document + "' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>" + 
                                        "Acciones" + 
                                    "</button>" + 
                                    "<div class='dropdown-menu dropdown-menu-right' aria-labelledby='dropdownMenuButton_" + item.id_document + "'>" + 
                                        //NUEVO CÓDIGO / MUESTRA EL DOCUMENTO EN OTRA VENTANA
                                        "<a class='dropdown-item' href='../../uploads/documents/" + item.key_folder + "/" + item.file_name_document + "' target='_blank'>" +
                                            "<i class='fas fa-eye'></i> Mostrar documento" +
                                        "</a>" +

                                        //ORIGINAL (DESPLEGABA EL MODAL DE EDITAR O VER DETALLES DEL DOCUMENTO)-->
                                        /*
                                        "<a class='dropdown-item' href='#' data-document-id='" + item.id_document + "'>" +
                                            "<i class='fas fa-eye'></i> Consultar detalles" +
                                        "</a>" + 
                                        */

                                        "<a class='dropdown-item' href='../../uploads/documents/" + item.key_folder + "/" + item.file_name_document + "' download>" +
                                            "<i class='fas fa-download'></i> Descargar" +
                                        "</a>";
                                        // COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) PARA MOSTRAR EL BOTON DE ELIMINAR / MOVER A LA PAPELERA
                                        if (userType == 1) {
                                            newRow +=
                                            "<hr>" + 
                                            "<form action='#' method='POST'>" +
                                                "<input name='deleteDocument[id_document]' type='text' class='form-control form-control-sm' id='id_document' value='" + item.id_document + "' readonly hidden style='display: none;'>" +
                                                "<input name='deleteDocument[key_document]' type='text' class='form-control form-control-sm' id='key_document' value='" + item.key_document + "' readonly hidden style='display: none;'>" +
                                                "<button class='dropdown-item' type='submit' name='action' value='deleteDocument' onclick='return confirm(\"¿Estás seguro de eliminar el documento?\");'>" +
                                                    "<i class='fas fa-trash'></i> Mover a la papelera" + 
                                                "</button>" +
                                            "</form>";
                                        }
                                    newRow +=
                                    "</div>" +
                                "</div>" +
                            "</td>" +
                        "</tr>";
                        
                        // Agregar la nueva fila al cuerpo de la tabla
                        table.row.add($(newRow)[0]);
                    });
                    
                    // Redibujar la tabla después de agregar nuevas filas
                    table.draw();
                });
            }
        </script>
        
        <script>
            $(document).ready(function () {
                // Acción de clic en consultar detalles
                $(document).on('click', '.dropdown-item[data-document-id]', function (e) {
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
                        // Llenar el formulario del modal con los datos del documento
                        $('#edit_id_document').val(parsedResponse.id_document);
                        $('#edit_key_document').val(parsedResponse.key_document);
                        $('#edit_first_fech_document').val(parsedResponse.first_fech_document);
                        $('#edit_second_fech_document').val(parsedResponse.second_fech_document);
                        $('#edit_name_user').val(parsedResponse.name_user);  
                        $('#edit_file_name_document').val(parsedResponse.file_name_document);  
                        $('#edit_name_folder').val(parsedResponse.name_folder);  
                        // Mostrar el modal de edición
                        $('#modalEditarDocument').modal('show');
                    });
                });
            });
        </script>
    </body>
</html>