<?php
    session_start();
    include "../../app/config.php";
    //include "../../app/debug.php";
    include "../../app/WebController.php";
    include "../../app/ExcelController.php";
    require '../../vendor/autoload.php';
    $controller = new WebController();
    // Verificar si la sesión del usuario está activa
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        // Si no hay sesión activa, destruir la sesión
        session_destroy();
        // Redirigir a la página de inicio de sesión
        header("Location: ../../login.php");
        exit(); // Es importante salir después de redirigir para evitar que el código siguiente se ejecute innecesariamente
    }
    // Obtener la lista de los usuarios del departamento de ventas y que esten activos (3 -> Tipo de Usuario Ventas, 1 -> Activos)
    $customersList = $controller->getCustomersList(3, 1);
    
    // FUNCIÓN PARA CREAR UNA NUEVA CARPETA
    // Verifica si se ha dado clic en algun boton a traves del action
    if(!empty($_POST['action'])){
        // Si la acción es 'createFolder', se intenta crear una carpeta nueva
        if($_POST['action'] == 'createFolder'){
            // Llama al método para crear una carpeta y obtiene el ID de la carpeta creada
            $folderId = $controller->createFolder($_POST['folder']);
            // Si se crea la carpeta correctamente, redirecciona a la página de carpetas
            if($folderId){
                header('location: all_folders.php');
            }
        }
        // Si la acción es 'deleteFolder', se intenta eliminar una carpeta existente
        else if($_POST['action'] == 'deleteFolder'){
            // Llama al método para eliminar la carpeta y obtiene el ID de la carpeta eliminada
            $idFolder = $controller->deleteFolder($_POST['delFolder']);
            // Si se elimina la carpeta correctamente, redirecciona a la página de carpetas
            if($idFolder){
                header('location: all_folders.php');
            }
        }
        // Si la acción es 'update', se intenta actualizar el nombre de una carpeta existente
        else if($_POST['action'] == 'updateFolder'){
            // Llama al método para actualizar el nombre de la carpeta y obtiene el ID de la carpeta actualizada
            $idFolder = $controller->updateNameFolder($_POST['updateFolder']);
            // Si se actualiza el nombre correctamente, redirecciona a la página de carpetas
            if($idFolder){
                header('location: all_folders.php');
            }
        }
        // Si la acción es "reportFolders" se genera un reporte en formato Excel con los datos de la consulta
        else if($_POST['action'] == 'reportFolders'){
            $excelController = new ExcelController();
            $statusSelect = $_POST['statusSelect'];
            $customerSelect = $_POST['customerSelect'];
            if (isset($_POST['startFetch']) && isset($_POST['finishFetch'])) {
                $startFetch = $_POST['startFetch'];
                $finishFetch = $_POST['finishFetch'];
                $fecha1 = $startFetch." 00:00:00";
                $fecha2 = $finishFetch." 23:59:59";
            } else {
                $fecha1 = null;
                $fecha2 = null;
            }
            $filterFolders = $controller->ws_idxGetFolders($fecha1, $fecha2, $statusSelect, $customerSelect);
            if(!empty($filterFolders)) {
                $mssg = null;
                $excelController->reportFolders($filterFolders);
            } else {
                $mssg = "¡NO HAY INFORMACIÓN DE CLIENTES PARA GENERAR EL REPORTE!";
            }
        }
    }
    
    // FUNCIÓN PARA GENERAR UNA CLAVE PARA LA CARPETA
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
            $(document).ready(function(){
                // Inicialmente ocultar el div de la fecha y remover el atributo required
                $('#fecha-original-recibido').hide();
                $('input[name="folder[fech_orig_recib_folder]"]').removeAttr('required');
                // Mostrar/ocultar el div y agregar/quitar el atributo required según el estado del checkbox
                $('#opcion3').change(function(){
                    if ($(this).is(':checked')) {
                        $('#fecha-original-recibido').show();
                        $('input[name="folder[fech_orig_recib_folder]"]').attr('required', 'required');
                    } else {
                        $('#fecha-original-recibido').hide();
                        $('input[name="folder[fech_orig_recib_folder]"]').removeAttr('required');
                    }
                });
            });
        </script>
        
        <!--SCRIPT PARA MANEJAR EL MOSTRAR Y OCULTAR DE LA FECHA DE ORIGINAL RECIBIDO AL ACTUALIZAR EL REGISTRO-->
        <script>
            $(document).ready(function(){
                // Mostrar/ocultar el div y agregar/quitar el atributo required según el estado del checkbox
                $('#edit_chk_orig_recib_folder').change(function(){
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
            .truncate-text {
                max-width: 100px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            /* Ocultar las flechas de ordenamiento en la primera columna */
            #tblFolders th:first-child {
                cursor: default;
            }
            /* Forzar la eliminación de las flechas que quedan */
            #tblFolders th:first-child::before,
            #tblFolders th:first-child::after {
                content: none !important;
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
                                <h1 class="m-0 text-dark">Tablero de clientes</h1>
                            </div>
                            <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O VENTAS (3)-->
                            <?php if($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3){ ?>
                                <div class="col-sm-4 text-right">
                                    <!-- Botón para abrir el modal de registro de un nuevo cliente -->
                                    <a href="#" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;" role="button" aria-pressed="true" data-toggle="modal" data-target="#modalAgregarCarpeta">
                                        <i class="fas fa-plus pr-2"></i>Agregar nuevo cliente
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                        
                        <hr>
                        
                        <?php if (!empty($mssg)) { ?>
                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-dismissible alert-danger p-4">
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <h5><?php echo $mssg; ?></h5>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        
                        <!-- FILTROS DE BUSQUEDA DE CLIENTES POR INTERVALO DE FECHAS, POR ESTATUS Y POR ASESOR COMERCIAL -->
                        <form class="row mb-2" action="#" method="post">
                            <div class="col-lg-10 col-sm-12">
                                <strong>Plazo de vigencia</strong>
                                
                                <div class="row">
                                    <div class="input-group col-lg-3">
                                        <input type="date" class="form-control filtrosDDL" id="start_fetch" name="startFetch">
                                    </div>
                                    
                                    <div class="input-group col-lg-3">
                                        <input type="date" class="form-control filtrosDDL" id="finish_fetch" name="finishFetch">
                                    </div>
                                    
                                    <div class="input-group col-lg-3">
                                        <select class="form-control filtrosDDL selectFiltersStatus" id="status_select" name ="statusSelect">
                                            <option value="all">Todos los estatus</option>
                                            <option value="01">Cerca de vencimiento</option>
                                            <option value="02">Cliente vigente</option>
                                            <option value="03">Cliente vencido</option>
                                            <option value="null">Sin plazo de vencimiento</option>
                                        </select>
                                    </div>
                                    
                                    <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O EMPLEADO (2)-->
                                    <?php if($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 2){ ?>
                                        <div class="input-group col-lg-3">
                                            <select class="form-control filtrosDDL selectFilterCutomers" id="customer_select" name="customerSelect">
                                                <option value="">Todos los vendedores</option>
                                                <?php foreach($customersList as $key => $value){ ?>
                                                    <option value="<?php echo $value['id_user']; ?>">
                                                        <?php echo $value['name_user']; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    <?php } else { ?>
                                        <div class="input-group col-lg-3">
                                            <select class="form-control filtrosDDL selectFilterCutomers" id="customer_select" name="customerSelect">
                                                <option value="">Todos los clientes</option>
                                                <option value="<?php echo $_SESSION['user']['id_user']; ?>">Clientes propios</option>
                                            </select>
                                        </div>
                                    <?php } ?>
                                
                                </div>
                            </div>
                            
                            <!--BOTÓN PARA GENERAR EL REPORTE DE CLIENTES-->
                            <div class="col-lg-2 col-sm-12">
                                <button name="action" value="reportFolders" type="submit" class="btn btn-md btn-outline-success float-sm-right"> <i class="fas fa-file-alt mr-1"></i> Generar reporte</button>
                            </div>
                        
                        </form>
                    </div>
                    
                    <div class="content">
                        <div class="container-fluid">
                            <strong>Total de clientes: &nbsp;<b id="numTotalFolders"></b></strong>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped table-bordered" id="tblFolders">
                                                    <thead>
                                                        <th></th>
                                                        <th>Cliente</th>
                                                        <th>Estatus</th>
                                                        <th>Inicio del plazo</th>
                                                        <th>Fin del plazo</th>
                                                        <th>Seguimiento</th>
                                                        <th>Fecha de registro</th>
                                                        <th>Asesor</th>
                                                        <th>Autor</th>
                                                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                                        <?php if($_SESSION['user']['id_type_user'] == 1){ ?>
                                                            <th>Acciones</th>
                                                        <?php } ?>
                                                    </thead>
                                                    <tbody id="dataFolders"></tbody>
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
        
        <!-- Modal para agregar una nueva carpeta -->
        <div class="modal fade" id="modalAgregarCarpeta" tabindex="-1" aria-labelledby="modalAgregarCarpetaLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAgregarCarpetaLabel">Agregar nuevo cliente</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Formulario para agregar un nuevO cliente -->
                        <form id="formAgregarCarpeta" action="all_folders.php" method="POST">
                            <input name="folder[id_user_folder]" type="text" class="form-control" id="id_user_folder" required value="<?php echo $_SESSION['user']['id_user']; ?>" readonly style="display:none;" hidden>
                            <input name="folder[fk_folder]" type="text" class="form-control" id="fk_folder" required value="0" readonly style="display:none;" hidden>
                            <input name="folder[key_folder]" type="text" class="form-control" id="key_folder" required value="CARP-<?php echo $clave; ?>" readonly style="display:none;" hidden>
                            
                            <div class="form-group">
                                <label for="name_folder">Nombre del cliente:</label>
                                <input type="text" name="folder[name_folder]" class="form-control" id="name_folder" required autocomplete="off">
                            </div>
                            
                            <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                            <?php if($_SESSION['user']['id_type_user'] == 1){ ?>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <label>Plazo de vigencia <small style="color:red;">(*Plazo opcional)</small></label>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <input type="date" class="form-control" name="folder[first_fech_folder]">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <input type="date" class="form-control" name="folder[second_fech_folder]">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Checkboxes organizados en dos filas -->
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="opcion1" value="Si" name="folder[chk_alta_fact_folder]">
                                            <label class="form-check-label" for="opcion1">Vo.Bo. Alta Facturación</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="opcion2" value="Si" name="folder[chk_lib_folder]">
                                            <label class="form-check-label" for="opcion2">Vo.Bo. Liberación</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-2">
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="opcion3" value="Si" name="folder[chk_orig_recib_folder]">
                                            <label class="form-check-label" for="opcion3">Original Recibido</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="fecha-original-recibido" class="form-group" style="margin-top:15px;">
                                    <label>Fecha de original recibido:</label>
                                    <input type="date" class="form-control" name="folder[fech_orig_recib_folder]">
                                </div>
                                
                            <?php } ?>
                            
                            <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                            <?php if($_SESSION['user']['id_type_user'] == 1){ ?>
                                <div class="form-group" style="margin-top:10px;">
                                    <label for="id_customer_folder">Asesor comercial <small style="color:red;">(*Opcional)</small></label>
                                    <select name="folder[id_customer_folder]" id="id_customer_folder" class="form-control selectAddCustomer">
                                        <option value="">--</option>
                                        <?php foreach($customersList as $key => $value){ ?>
                                            <option value="<?php echo $value['id_user']; ?>">
                                                <?php echo $value['name_user']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            <?php } else { ?>
                                <div class="form-group" style="margin-top:10px;">
                                    <input name="folder[id_customer_folder]" type="text" class="form-control" required value="<?php echo $_SESSION['user']['id_user']; ?>" readonly style="display:none;" hidden>
                                </div>
                            <?php } ?>
                            
                            <button type="submit" class="btn btn-lg btn-block" style="background-color: #37424A; color: #ffffff;" name="action" value="createFolder">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal para editar un registro de un cliente existente -->
        <div class="modal fade" id="modalEditarCarpeta" tabindex="-1" aria-labelledby="modalEditarCarpetaLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarCarpetaLabel">Editar cliente</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Formulario para editar un cliente -->
                        <form id="formEditarCarpeta" action="all_folders.php" method="POST">
                            <input type="hidden" name="updateFolder[id_folder]" id="edit_folder_id">
                            
                            <div class="form-group">
                                <label for="edit_folder_name">Nombre del cliente:</label>
                                <input type="text" name="updateFolder[name_folder]" class="form-control" id="edit_folder_name" required autocomplete="off">
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <label>Plazo de vigencia <small style="color:red;">(*Plazo opcional)</small></label>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <input type="date" class="form-control" name="updateFolder[first_fech_folder]" id="edit_first_fech_folder">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <input type="date" class="form-control" name="updateFolder[second_fech_folder]" id="edit_second_fech_folder">                  
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Checkboxes organizados en dos filas -->
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_chk_alta_fact_folder" value="Si" name="updateFolder[chk_alta_fact_folder]">
                                        <label class="form-check-label" for="edit_chk_alta_fact_folder">Vo.Bo. Alta Facturación</label>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_chk_lib_folder" value="Si" name="updateFolder[chk_lib_folder]">
                                        <label class="form-check-label" for="edit_chk_lib_folder">Vo.Bo. Liberación</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-2">
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_chk_orig_recib_folder" value="Si" name="updateFolder[chk_orig_recib_folder]">
                                        <label class="form-check-label" for="edit_chk_orig_recib_folder">Original Recibido</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="edit-fecha-original-recibido" class="form-group" style="margin-top:15px;">
                                <label for="edit_fech_orig_recib_folder">Fecha de original recibido:</label>
                                <input type="date" class="form-control" name="updateFolder[fech_orig_recib_folder]" id="edit_fech_orig_recib_folder">
                            </div>
                            
                            <div class="form-group" style="margin-top:10px;">
                                <label for="edit_id_customer_folder">Asesor comercial <small style="color:red;">(*Opcional)</small></label>
                                <select name="updateFolder[id_customer_folder]" id="edit_id_customer_folder" class="form-control selectEditCustomer">
                                    <option value="">--</option>
                                    <?php foreach($customersList as $key => $value){ ?>
                                        <option value="<?php echo $value['id_user']; ?>">
                                            <?php echo $value['name_user']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-lg btn-block" style="background-color: #37424A; color: #ffffff;" name="action" value="updateFolder">Actualizar</button>
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
        <script src="../../resources/plugins/select2/js/select2.full.min.js"></script>
        <script src="../../resources/js/notifications.js"></script>
        <script src="../../resources/js/tracings.js"></script>
        <script src="../../resources/js/notify_folders.js"></script>
        
        <script>
            $(document).ready(function(){
                $('.selectAddCustomer, .selectEditCustomer, .selectFiltersStatus, .selectFilterCutomers').select2({
                    theme: 'bootstrap4'
                });
            });
        </script>
        
        <script>
            // Asignar valores de la variable de sesión a variables JavaScript
            var userId = <?php echo json_encode($_SESSION['user']['id_user']); ?>;
            var userType = <?php echo json_encode($_SESSION['user']['id_type_user']); ?>;
        </script>
        
        <script>
            $(document).ready(function() {
                // Obtener la fecha actual y colocarla en los inputs del intervalo
                var currentDate = new Date();
                // Obtener el primer día del mes actual
                var firstDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                // Obtener el último día del mes actual
                var lastDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
                // Formatear la fecha en el formato YYYY-MM-DD para establecer el valor del input
                var formattedDateFirst = firstDayOfMonth.toISOString().split('T')[0];
                // Formatear la fecha en el formato YYYY-MM-DD para establecer el valor del input
                var formattedDate = lastDayOfMonth.toISOString().split('T')[0];
                // Establecer el valor del input
                $('#start_fetch').val(formattedDateFirst);
                // Establecer el valor del input
                $('#finish_fetch').val(formattedDate);
            });
        </script>
        
        <script>
            $(document).ready(function() {
                updateFiltersByStatus();
                
                // Manejar cambios en el select de estado
                $('#status_select').change(function() {
                    updateFiltersByStatus();
                });
                
                function updateFiltersByStatus() {
                    var selectedValue = $('#status_select').val();
                    if (selectedValue === '02' || selectedValue === 'null') {
                        disableFilters();
                    } else {
                        enableFilters();
                    }
                }
                
                function disableFilters() {
                    $('#start_fetch').prop('disabled', true);
                    $('#finish_fetch').prop('disabled', true);
                }
                
                function enableFilters() {
                    $('#start_fetch').prop('disabled', false);
                    $('#finish_fetch').prop('disabled', false);
                }
            });
        </script>
        
        <script>
            $(function () {
                var startFetch = $("#start_fetch").val(),
                finishFetch = $("#finish_fetch").val(),
                status = $("#status_select").val(),
                customer = $("#customer_select").val();
                
                var fecha1 = startFetch + " 00:00:00";
                var fecha2 = finishFetch + " 23:59:59";
                loadFolders(fecha1, fecha2, status, customer);
            });
        </script>
        
        <script>
            $(document).ready(function(){
                $('.filtrosDDL').on('change', function() {
                    var startFetch = $("#start_fetch").val(),
                    finishFetch = $("#finish_fetch").val(),
                    status = $("#status_select").val(),
                    customer = $("#customer_select").val();
                    
                    var fecha1 = startFetch + " 00:00:00";
                    var fecha2 = finishFetch + " 23:59:59";
                    loadFolders(fecha1, fecha2, status, customer);
                });
                
                // Acción de clic en editar carpeta
                $(document).on('click', '.dropdown-item[data-folder-id]', function (e) {
                    e.preventDefault();
                    var folderId = $(this).data('folder-id');
                    
                    $.ajax({
                        type: "GET",
                        url: "../../app/webservice.php",
                        data: {
                            action: "getFolderDetail",
                            idFolder: folderId
                        }
                    }).done(function (response) {
                        var parsedResponse = JSON.parse(response);
                        // Llenar el formulario de edición con los datos del cliente
                        $('#edit_folder_id').val(parsedResponse.id_folder );
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
                        
                        // Configurar el select del asesor comercial usando Select2
                        var idCustomerFolder = parsedResponse.id_customer_folder;
                        if (idCustomerFolder && idCustomerFolder !== "0") {
                            $('#edit_id_customer_folder').val(idCustomerFolder).trigger('change'); // Actualiza Select2
                        } else {
                            $('#edit_id_customer_folder').val("").trigger('change'); // Limpia la selección
                        }
                        
                        // SI ES UN SELECT NORMAL QUEDA DE LA SIGUIENTE FORMA SIN EL TRIGGER, SI ES CON Select2 se usa la forma de arriba
                        /*
                        if (idCustomerFolder && idCustomerFolder !== "0") {
                            $('#edit_id_customer_folder').val(idCustomerFolder); // Seleccionar la opción correspondiente
                        } else {
                            $('#edit_id_customer_folder').val(""); // Dejar sin selección
                        }
                        */
                        
                        // Mostrar el modal de edición de clientes
                        $('#modalEditarCarpeta').modal('show');
                    });
                });
            });
        </script>
        
        <!--ESTE CÓDIGO ES PARA QUE AL MOMENTO DE ESCRIBIR EN LOS CAMPOS DE FECHA SE FORMATE LA FECHA-->
        <script>
            // Función para convertir dd/mm/yyyy a yyyy-mm-dd
            function convertToISODate(fecha) {
                const [day, month, year] = fecha.split('/');
                return `${year}-${month}-${day}`;
            }
            // Evento para asegurarse que las fechas estén en formato ISO tras escribirlas
            $("#start_fetch, #finish_fetch").on('blur change', function() {
                let fechaIngresada = this.value;
                var status = $("#status_select").val();
                var customer = $("#customer_select").val();
                // Si la fecha está en formato dd/mm/yyyy (por ejemplo: 01/09/2024), convertimos a ISO
                if (fechaIngresada.includes("/")) {
                    const fechaISO = convertToISODate(fechaIngresada);
                    this.value = fechaISO; // Forzamos a que el valor sea en formato ISO
                }
                // Recargamos la tabla con las nuevas fechas
                const startDate = $("#start_fetch").val();
                const endDate = $("#finish_fetch").val();
                loadFolders(startDate, endDate, status, customer);  // Pasar los valores para recargar los datos
            });
        </script>
        
        <script>
            function loadFolders(fecha1, fecha2, status, customer) {
                let columnDefs = [
                    { "targets": 0, "orderable": false }, // Deshabilitar orden en la primera columna
                    { "targets": 2, "orderable": false }, // Deshabilitar orden en la tercera columna
                    { "targets": 5, "orderable": false }  // Deshabilitar orden en la séptima columna
                ];
                // Condicional para agregar el último columnDefs solo si userType es 1 (administrador) - Columna de acciones
                if (userType == 1) {
                    columnDefs.push({ "targets": 9, "orderable": false });  // Deshabilitar orden en la novena columna
                }
                
                var table = $('#tblFolders').DataTable({
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
                    "columnDefs": columnDefs,
                    "destroy": true,
                });
                // var table = $('#tblFolders').DataTable();
                table.clear().draw();
                $.ajax({
                    type: "GET",
                    url: "../../app/webservice.php",
                    data: { 
                        // action: "getFoldersAll",
                        action: "idx_getFolders",
                        fecha1: fecha1,
                        fecha2: fecha2,
                        status: status,
                        customer: customer
                    }
                }).done(function(response) {
                    var parsedResponse = JSON.parse(response);
                    $("#numTotalFolders").html(parsedResponse.length);
                    $("#dataFolders").empty();
                    parsedResponse.forEach(function(item) {
                        var newRow = "<tr>" + 
                            "<td style='text-align:center;'>" + 
                                "<a href='../folders/subfolder.php?id=" + item.id_folder + "&key=" + item.key_folder +"' class='btn btn-sm btn-success'><i class='fas fa-eye'></i></a>" + 
                            "</td>" + 
                            
                            "<td style='text-align:left;' class='truncate-text'>" + 
                                "<a href='../folders/subfolder.php?id=" + item.id_folder + "&key=" + item.key_folder +"'>" + item.name_folder + "</a>" + 
                            "</td>";
                            
                            if (item.dias == null) {
                                newRow += "<td style='text-align:center; width:20%;'><div class='alert alert-info' role='alert' style='text-align:center;'>Sin plazo de vencimiento</div></td>";
                            } else if (item.dias >= 1) {
                                newRow += "<td style='text-align:center; width:20%;'><div class='alert alert-danger' role='alert' style='text-align:center;'>Cliente vencido</div></td>";
                            } else if (item.dias >= -60) {
                                newRow += "<td style='text-align:center; width:20%;'><div class='alert alert-warning' role='alert' style='text-align:center; color:white; background-color: orange;'>Cerca de vencimiento</div></td>";
                            } else {
                                newRow += "<td style='text-align:center; width:20%;'><div class='alert alert-success' role='alert' style='text-align:center;'>Cliente vigente</div></td>";
                            }
                            
                            if(item.dias == null){
                                newRow += "<td style='text-align:center;'>- - -</td>";
                                newRow += "<td style='text-align:center;'>- - -</td>";
                            } else {
                                newRow += "<td style='text-align:center;' data-order='" + item.first_fech_folder + "'>" + formatDate(item.first_fech_folder) + "</td>";
                                newRow += "<td style='text-align:center;' data-order='" + item.second_fech_folder + "'>" + formatDate(item.second_fech_folder) + "</td>";
                            }
                            
                            newRow += "<td style='text-align:center;'>" + 
                            "<div style='display: flex; justify-content: center; gap: 10px;'>";
                                if (item.chk_alta_fact_folder === "Si" || item.chk_lib_folder === "Si" || item.chk_orig_recib_folder === "Si") {
                                    if (item.chk_alta_fact_folder === "Si") {
                                        newRow += "<div class='status-item' data-toggle='tooltip' title='Vo. Bo. Alta Facturación'><span><i class='fas fa-file-alt'></i></span></div>";
                                    }
                                    if (item.chk_lib_folder === "Si") {
                                        newRow += "<div class='status-item' data-toggle='tooltip' title='Vo. Bo. Liberación'><span><i class='fas fa-truck'></i></span></div>";
                                    }
                                    if (item.chk_orig_recib_folder === "Si") {
                                        // Convertir la fecha al formato día, mes y año
                                        var fechaOriginal = new Date(item.fech_orig_recib_folder);
                                        // Ajustar la fecha sumando un día
                                        fechaOriginal.setDate(fechaOriginal.getDate() + 1);
                                        var fechaFormateada = ("0" + fechaOriginal.getDate()).slice(-2) + "/" + ("0" + (fechaOriginal.getMonth() + 1)).slice(-2) + "/" + fechaOriginal.getFullYear();
                                        newRow += "<div class='status-item' data-toggle='tooltip' title='Original Recibido - " + fechaFormateada + "'><span><i class='fas fa-user-check'></i></span></div>";
                                    }
                                } else {
                                    newRow += "- - -";
                                }
                            newRow += "</div></td>";
                            
                            newRow += "<td style='text-align:center;' data-order='" + item.created_at_folder + "'>" + formatDate(item.created_at_folder) + "</td>";
                            
                            newRow += "<td style='text-align:left;' class='truncate-text'>" + item.name_customer + "</td>";
                            
                            newRow += "<td style='text-align:left;' class='truncate-text'>" + item.name_user + "</td>";
                            
                            // COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)
                            if (userType == 1) {
                                newRow += "<td style='text-align:center;'>" + 
                                    "<div class='dropdown'>" + 
                                        "<button class='btn btn-primary dropdown-toggle' type='button' id='dropdownMenuButton_" + item.id_folder + "' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>" + 
                                            "Acciones" + 
                                        "</button>" + 
                                        "<div class='dropdown-menu dropdown-menu-right' aria-labelledby='dropdownMenuButton_" + item.id_folder + "'>" + 
                                            "<a class='dropdown-item' href='#' data-folder-id='" + item.id_folder + "'>" + 
                                                "<i class='fas fa-pen'></i> Editar cliente" + 
                                            "</a>";
                                            // Mostrar el botón de eliminar si el usuario es administrador
                                            if (userType == 1) {
                                                newRow += "<hr>" + 
                                                "<form action='#' method='POST'>" + 
                                                    "<input name='delFolder[idFolder]' type='text' class='form-control form-control-sm' id='id_folder' value='" + item.id_folder + "' readonly hidden style='display: none;'>" + 
                                                    "<button class='dropdown-item' type='submit' name='action' value='deleteFolder' onclick='return confirm(\"¿Estás seguro de eliminar el cliente?\");'>" + 
                                                        "<i class='fas fa-trash'></i> Mover a la papelera" + 
                                                    "</button>" + 
                                                "</form>";
                                            }
                                        newRow += "</div>" + 
                                    "</div>" + 
                                "</td>";
                            }
                        newRow += "</tr>";
                        table.row.add($(newRow)[0]);
                    });
                    table.draw();
                    // Inicializar tooltips después de llenar la tabla
                    $('[data-toggle="tooltip"]').tooltip({
                        delay: { "show": 0, "hide": 0 } // Hacer que el tooltip aparezca y desaparezca inmediatamente
                    });
                });
            }
            
            function formatNumberWithZero(number) {
                return number < 10 ? "0" + number : number;
            }
            
            function formatDate(dateString) {
                var date = new Date(dateString);
                var day = formatNumberWithZero(date.getUTCDate());
                var month = formatNumberWithZero(date.getUTCMonth() + 1);
                var year = date.getUTCFullYear();
                return day + "/" + month + "/" + year;
            }
        </script>
        
    </body>
</html>