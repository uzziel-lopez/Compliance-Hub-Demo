<?php
declare(strict_types=1);

session_start();
require_once "app/config.php";
//require_once "app/debug.php";
require_once "app/WebController.php";
require_once "app/ExcelController.php";
require_once 'vendor/autoload.php';

$controller = new WebController();

// Verificar si la sesión del usuario está activa
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
  // Si no hay sesión activa, destruir la sesión
  session_destroy();
  // Redirigir a la página de inicio de sesión
  header("Location: login.php");
  exit(); // Es importante salir después de redirigir para evitar que el código siguiente se ejecute innecesariamente
}

// FUNCIÓN PARA MOSTRAR LOS DETALLES DE UN USUARIO
$user = $controller->getDetailUser($_SESSION['user']['id_user'], $_SESSION['user']['key_user']);

// FUNCIÓN PARA MOSTRAR EL TOTAL DE USUARIOS ACTIVOS
$usuarios = $controller->getUsers(1);
$totalUsuarios = count($usuarios);

// FUNCIÓN PARA MOSTRAR EL TOTAL DE CLIENTES ACTIVOS EN EL SISTEMA
$allFolders = $controller->getAllFolders(1);
$totalFolders = count($allFolders);

// FUNCIÓN PARA MOSTRAR EL TOTAL DE LOS DOCUMENTOS ACTIVOS EN EL SISTEMA
$allDocuments = $controller->showAllDocuments(1);
$totalDocuments = count($allDocuments);

// Crear un array de meses en español
$meses = ["ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO", "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE"];

// Obtener el año actual en formato de cuatro dígitos (YYYY)
$anio = date("Y");

// Obtener el mes actual en formato de dos dígitos (MM)
$mes = date("m");

// Crear una fecha de inicio para el primer día del mes actual a medianoche (YYYY-MM-01 00:00:00)
$fecha1 = $anio . "-" . $mes . "-01 00:00:00";

// Crear una fecha de fin para el último día del mes actual a las 23:59:59 (YYYY-MM-31 23:59:59)
$fecha2 = $anio . "-" . $mes . "-31 23:59:59";

// Llama a la función idx_getFoldersMonth del controlador para obtener las carpetas del mes dado entre las fechas $fecha1 y $fecha2
$get_folders_month = $controller->idx_getFoldersMonth($fecha1, $fecha2);

// Cuenta el número total de carpetas obtenidas en el mes
$totalFoldersMonth = count($get_folders_month);

// Llama a la función idx_getDocumentsMonth del controlador para obtener los documentos del mes dado entre las fechas $fecha1 y $fecha2
$get_documents_month = $controller->idx_getDocumentsMonth($fecha1, $fecha2);

// Cuenta el número total de documentos obtenidos en el mes
$totalDocumentsMonth = count($get_documents_month);

// Define la fecha de inicio del mes en el formato 'YYYY-MM-01'
$firstFetch = $anio . "-" . $mes . "-01";

// Define la fecha de fin del mes en el formato 'YYYY-MM-31'
$secondFetch = $anio . "-" . $mes . "-31";

// Llama a la función idx_getSelectFolders del controlador para obtener las carpetas vencidas (status '03') en el mes
$get_select_folder_vencidos_month = $controller->idx_getSelectFolders("03");
// CÓDIGO DE RESPALDO DONDE LA ESTADISTICA DE LOS CLIENTES VENCIDOS ERA POR EL MES ACTUAL
// $get_select_folder_vencidos_month = $controller->respaldo_idx_getSelectFolders($firstFetch, $secondFetch, "03");

// Cuenta el número total de carpetas vencidas obtenidas en el mes
$totalgetFoldersVencidos = count($get_select_folder_vencidos_month);

// Llama a la función idx_getSelectFolders del controlador para obtener las carpetas cerca de vencimiento (status '01') en el mes
$get_select_folder_cerca_vencimiento_month = $controller->idx_getSelectFolders("01");
// CÓDIGO DE RESPALDO DONDE LA ESTADISTICA DE LOS CLIENTES CERCA DE VENCIMIENTO ERA POR EL MES ACTUAL
// $get_select_folder_cerca_vencimiento_month = $controller->respaldo_idx_getSelectFolders($firstFetch, $secondFetch, "01");

// Cuenta el número total de carpetas cerca de vencimiento obtenidas en el mes
$totalgetFoldersCercaVencimiento = count($get_select_folder_cerca_vencimiento_month);

// Llama a la función idx_getFoldersAllSelect del controlador para obtener todas las carpetas vigentes (status '02')
$get_folder_all_vigentes = $controller->idx_getFoldersAllSelect("02");

// Cuenta el número total de carpetas vigentes obtenidas
$totalFoldersVigentes = count($get_folder_all_vigentes);

// Llama a la función idx_getFoldersAllSelect del controlador para obtener todas las carpetas sin plazo de vencimiento (status 'null')
$get_folder_all_sin_plazo = $controller->idx_getFoldersAllSelect("null");

// Cuenta el número total de carpetas sin plazo de vencimiento obtenidas
$totalFoldersSinPlazo = count($get_folder_all_sin_plazo);

// Obtener la lista de los usuarios del departamento de ventas y que esten activos (3 -> Tipo de Usuario Ventas, 1 -> Activos)
$customersList = $controller->getCustomersList(3, 1);

// Inicializar variable de mensaje
$mssg = null;

// Verificar si se ha enviado un formulario y se ha establecido una acción
if (!empty($_POST['action'])) {
  // Verificar si la acción es para generar un reporte de carpetas
  if ($_POST['action'] === 'reportFolders') {
    // Crear una instancia del controlador de Excel
    $excelController = new ExcelController();
    
    // Obtener el valor del estado de selección del formulario
    $statusSelect = $_POST['statusSelect'] ?? '';
    $customerSelect = $_POST['customerSelect'] ?? '';
    
    // Verificar si se ha seleccionado el filtro de 'Año y Mes'
    if (isset($_POST['check']) && $_POST['check'] == 1) {
      // Obtener los valores del año y mes seleccionados
      $year_select = $_POST['year_select'] ?? '';
      $monthSelect = $_POST['monthSelect'] ?? '';
      
      // Calcular las fechas de inicio y fin según el año y mes seleccionados
      if ($monthSelect === 'all') {
        $fecha1 = $year_select . "-01-01 00:00:00";
        $fecha2 = $year_select . "-12-31 23:59:59";
      } else {
        $fecha1 = $year_select . "-" . $monthSelect . "-01 00:00:00";
        $fecha2 = $year_select . "-" . $monthSelect . "-31 23:59:59";
      }
    }
    // Verificar si se han establecido las fechas de inicio y fin directamente
    elseif (isset($_POST['startFetch']) && isset($_POST['finishFetch'])) {
      // Obtener las fechas de inicio y fin directamente del formulario
      $startFetch = $_POST['startFetch'];
      $finishFetch = $_POST['finishFetch'];
      
      // Establecer las fechas de inicio y fin para la consulta
      $fecha1 = $startFetch . " 00:00:00";
      $fecha2 = $finishFetch . " 23:59:59";
    } else {
      // Establecer las fechas como null (es cuando se deshabilitan los selects tanto por intervalo como por año y mes)
      $fecha1 = null;
      $fecha2 = null;
    }
    
    // Obtener las carpetas filtradas según las fechas, el estatus y vendedor seleccionados
    $filterFolders = $controller->ws_idxGetFolders($fecha1, $fecha2, $statusSelect, $customerSelect);
    
    // Verificar si hay carpetas filtradas para generar el reporte
    if (!empty($filterFolders)) {
      $mssg = null;
      // Generar el reporte de carpetas en formato Excel
      $excelController->reportFolders($filterFolders);
    } else {
      // Establecer un mensaje si no hay información de carpetas para generar el reporte
      $mssg = "¡NO HAY INFORMACIÓN DE CLIENTES PARA GENERAR EL REPORTE!";
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
    <link rel="stylesheet" href="resources/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="resources/plugins/datatables-bs4/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="resources/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="resources/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="resources/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="icon" href="resources/img/icono.png">
    <script src="resources/js/jquery-3.5.1.min.js"></script>
    <!--<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>-->
    
    <style>
      .custom-card .card-body {
        padding: 20px;
      }
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
      #chartLegend div {
        display: flex;
        align-items: center; /* Alinea verticalmente los cuadros con el texto */
        margin-bottom: 10px; /* Espaciado entre las filas */
      }
    </style>
  </head>
  
  <body class="hold-transition sidebar-mini">
    <div class="wrapper" style="padding-top: 57px;">
      <?php include "navbar.php"; ?>
      <div class="content-wrapper">
        
        <div class="content-header">
          <div class="container-fluid">
            
            <div class="row justify-content-between mb-2">
              <div class="col-sm-8">
                <h1 class="m-0 text-dark">Bienvenid@: <?php echo $user['name_user']; ?></h1>
              </div>
              <div class="col-sm-4 text-right">
                <a href="#" class="btn btn-block" style="background-color: #FF5800; color: #ffffff; opacity: 0; pointer-events: none;" role="button" aria-pressed="true">Regresar</a>
              </div>
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
          </div>
        </div>
        
        <div class="content">
          <div class="container-fluid">
            
            <!--CARD CON LOS CONTENEDORES DE LA INFORMACIÓN-->
            <div class="card">
              <div class="card-body">
                <div class="row">
                  
                  <!-- CAJA DE TOTAL DE USUARIOS -->
                  <div class="col-lg-3 col-6">
                    <div class="small-box" style="background: linear-gradient(#1E3859, #1E3859);">
                      <div class="inner">
                        <h3 style="color:#fff"><?php echo $totalUsuarios; ?></h3>
                        <p style="color:#fff">Total de usuarios</p>
                      </div>
                      <div class="icon">
                        <i class="fa fa-users"></i>
                      </div>
                      <a href="backoffice/users/users.php" class="small-box-footer">
                        Más info <i class="fa fa-arrow-circle-right"></i>
                      </a>
                    </div>
                  </div>
                  
                  <!-- CAJA DEL TOTAL DE CLIENTES -->
                  <div class="col-lg-3 col-6">
                    <div class="small-box" style="background: linear-gradient(#4D688C, #4D688C);">
                      <div class="inner">
                        <h3 style="color:#fff"><?php echo $totalFolders; ?></h3>
                        <p style="color:#fff">Total general de clientes</p>
                      </div>
                      <div class="icon">
                        <i class="fa fa-folder-open"></i>
                      </div>
                      <a href="backoffice/folders/folders.php" class="small-box-footer">
                        Más info <i class="fa fa-arrow-circle-right"></i>
                      </a>
                    </div>
                  </div>
                  
                  <!-- CAJA DE TOTAL DE DOCUMENTOS -->
                  <div class="col-lg-3 col-6">
                    <div class="small-box" style="background: linear-gradient(#2C456F, #2C456F);">
                      <div class="inner">
                        <h3 style="color:#fff"><?php echo $totalDocuments; ?></h3>
                        <p style="color:#fff">Total de documentos</p>
                      </div>
                      <div class="icon">
                        <i class="fa fa-file-pdf"></i>
                      </div>
                      <a href="#" class="small-box-footer">
                        &nbsp;
                      </a>
                    </div>
                  </div>
                  
                  <!-- CAJA DEL TOTAL DE CLIENTES REGISTRADOS EN EL MES -->
                  <div class="col-lg-3 col-6">
                    <div class="small-box" style="background: linear-gradient(#455E84, #455E84);">
                      <div class="inner">
                        <h3 style="color:#fff"><?php echo $totalFoldersMonth; ?></h3>
                        <p style="color:#fff">Total de clientes del mes</p>
                      </div>
                      <div class="icon">
                        <i class="fa fa-folder"></i>
                      </div>
                      <a href="backoffice/folders/all_folders.php" class="small-box-footer">
                        Más info <i class="fa fa-arrow-circle-right"></i>
                      </a>
                    </div>
                  </div>
                
                </div>
              </div>
            </div>
            
            <div class="card" style="height: 100%; background-color: transparent; border: 0; box-shadow: none; margin-bottom:-0px;">
              <div class="row">
                <div class="col-md-8">
                  <div class="card" style="height: 100%; background-color: transparent; border: 0; box-shadow: none;">
                    
                    <!--CARD QUE MUESTRA LAS ESTADÍSTICAS GENERALES-->
                    <div class="card">
                      <div class="card-body">
                        <div class="row">
                          <div class="col-12">
                            <div class="card">
                              <div class="card-header">
                                <h3 class="card-title"><strong>ESTADÍSTICAS GENERALES</strong></h3>
                                <div class="card-tools"></div>
                              </div>
                              
                              <div class="card-body table-responsive p-0">
                                <table class="table">
                                  <thead>
                                    <tr>
                                      <th>CLIENTES VENCIDOS</th>  
                                      <th>PROXIMOS A VENCER</th>
                                      <th>CLIENTES VIGENTES</th>
                                      <th>SIN PLAZO VENCIMIENTO</th>
                                    </tr>
                                  </thead>
                                  
                                  <tbody>
                                    <tr>
                                      <td class="h5"><?php echo $totalgetFoldersVencidos; ?></td>  
                                      <td class="h5"><?php echo $totalgetFoldersCercaVencimiento; ?></td>
                                      <td class="h5"><?php echo $totalFoldersVigentes; ?></td>
                                      <td class="h5"><?php echo $totalFoldersSinPlazo; ?></td>                                  
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                            
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!--DIV PARA MOSTRAR LOS FILTROS POR FECHA, ESTATUS Y VENDEDOR-->
                    <div class="card" style="height:100%;">
                      <div class="card-body">
                        <form role="form" action="#" method="post">
                          
                          <div class="row">
                            <div class="col-sm-8">
                              <span class="pr-4">Filtrar resultados:</span>
                              <div class="form-check form-check-inline">
                                <input class="form-check-input filtrosDDL" type="radio" name="check" id="check-p1" value="1" checked>
                                <label class="form-check-label" for="check-p1">Año y Mes</label>
                              </div>
                              <div class="form-check form-check-inline">
                                <input class="form-check-input filtrosDDL" type="radio" name="check" id="check-p2" value="0">
                                <label class="form-check-label" for="check-p2">Intervalo de Fechas</label>
                              </div>
                            </div>
                            
                            <div class="col-sm-4 pb-2">
                              <button name="action" value="reportFolders" type="submit" class="btn btn-md btn-outline-success float-sm-right">
                                <i class="fas fa-file-alt mr-1"></i> Reporte de clientes
                              </button>
                            </div>
                            
                            <div class="col-sm-6">
                              <div class="form-group row">
                                <label class="col-sm-4 col-form-label text-sm-right pr-0">Año</label>
                                <div class="col-sm-8">
                                  <select id="yearSelect" class="form-control filtrosDDL selectFiltersYear" name="year_select"></select>
                                </div>
                              </div>
                            </div>
                            
                            <div class="col-sm-6">
                              <div class="form-group row">
                                <label class="col-sm-4 col-form-label text-sm-right pr-0">Fecha Inicio</label>
                                <div class="col-sm-8">
                                  <input type="date" class="form-control filtrosDDL" id="start_fetch" name="startFetch">
                                </div>
                              </div>
                            </div>
                          </div>
                          
                          <div class="row">
                            <div class="col-sm-6">
                              <div class="form-group row">
                                <label class="col-sm-4 col-form-label text-sm-right pr-0">Mes</label>
                                <div class="col-sm-8">
                                  <select class="form-control filtrosDDL selectFiltersMonth" id="month_select" name="monthSelect">
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
                              </div>
                            </div>
                            
                            <div class="col-sm-6">
                              <div class="form-group row">
                                <label class="col-sm-4 col-form-label text-sm-right pr-0">Fecha Fin</label>
                                <div class="col-sm-8">
                                  <input type="date" class="form-control filtrosDDL" id="finish_fetch" name="finishFetch">
                                </div>
                              </div>
                            </div>
                          </div>
                          
                          <div class="row">
                            <div class="col-sm-12">
                              <div class="form-group row">
                                <label class="col-sm-2 col-form-label text-sm-right pr-0">Estatus</label>
                                <div class="col-sm-10">
                                  <select class="form-control filtrosDDL selectFiltersStatus" id="status_select" name ="statusSelect">
                                    <option value="all">Todos los estatus</option>
                                    <option value="01">Cerca de vencimiento</option>
                                    <option value="02">Cliente vigente</option>
                                    <option value="03">Cliente vencido</option>
                                    <option value="null">Sin plazo de vencimiento</option>
                                  </select>
                                </div>
                              </div>
                            </div>
                          </div>
                          
                          <div class="row">
                            <div class="col-sm-12">
                              <div class="form-group row">
                                <label class="col-sm-2 col-form-label text-sm-right pr-0">Vendedor</label>
                                <div class="col-sm-10">
                                  <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O EMPLEADO (2)-->
                                  <?php if($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 2){ ?>
                                    <select class="form-control filtrosDDL selectFilterCutomers" id="customer_select" name="customerSelect">
                                      <option value="">Todos los vendedores</option>
                                      <?php foreach($customersList as $key => $value){ ?>
                                        <option value="<?php echo $value['id_user']; ?>">
                                          <?php echo $value['name_user']; ?>
                                        </option>
                                      <?php } ?>
                                    </select>
                                  <?php } else { ?>
                                    <select class="form-control filtrosDDL selectFilterCutomers" id="customer_select" name="customerSelect">
                                      <option value="">Todos los clientes</option>
                                      <option value="<?php echo $_SESSION['user']['id_user']; ?>">Clientes propios</option>
                                    </select>
                                  <?php } ?>
                                </div>
                              </div>
                            </div>
                          </div>
                        
                        </form>
                      </div>
                    </div>
                  
                  </div>
                </div>
                
                <!--COLUMNA PARA MOSTRAR EL ESTADÍSTICO GENERAL-->
                <div class="col-md-4">
                  <div class="card" style="height:97%;">
                    <div class="card-header">
                      <h3 class="card-title"><strong>DIAGRAMA ESTADÍSTICO GENERAL</strong></h3>
                      <div class="card-tools"></div>
                    </div>
                    <div class="card-body text-center">
                      <canvas id="pieChartStatusClients" style="height:65%; width:100%;"></canvas>
                      <div id="chartLegend" style="margin-top: 20px; display: flex; flex-direction: column; align-items: flex-start;"></div>
                    </div>
                  </div>
                </div>
              
              </div>
            </div>
            
            <!--CONTENEDOR DE LISTA DE CLIENTES - SECCIÓN FINAL -->
            <div class="card">
              <div class="card-body">
                
                <div class="row">
                  <div class="col-lg-10 col-md-12">
                    <div class="box-header with-border">
                      <h4 class="m-0 text-dark">LISTA DE CLIENTES DEL MES (<?php echo $meses[date('n')-1]; ?> <?php echo $anio; ?>)</h4>
                    </div>
                  </div>
                  <div class="col-lg-2 col-md-12">
                    <div class="box-header with-border">
                      <strong>Total de clientes: &nbsp;<b id="numTotalFolders"></b></strong>
                    </div>
                  </div>
                </div><br>
                
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
                    </thead>
                    <tbody id="dataFolders"></tbody>
                  </table>
                </div>
              
              </div>
            </div><br>
          
          </div>
        </div>
      
      </div>
    </div>
    
    <script src="resources/plugins/jquery/jquery.min.js"></script>
    <script src="resources/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="resources/plugins/datatables/jquery.dataTables.js"></script>
    <script src="resources/plugins/datatables-bs4/js/dataTables.bootstrap4.js"></script>
    <script src="resources/dist/js/adminlte.min.js"></script>
    <script src="resources/plugins/select2/js/select2.full.min.js"></script>
    <script src="resources/js/notifications.js"></script>
    <script src="resources/js/tracings.js"></script>
    <script src="resources/js/notify_folders.js"></script>
    <script src="resources/dist/js/chart.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>-->
    
    <script>
      $(document).ready(function(){
        $('.selectFiltersYear, .selectFiltersMonth, .selectFiltersStatus, .selectFilterCutomers').select2({
          theme: 'bootstrap4'
        });
      });
    </script>
    
    <script>
      // Asignar valores de la variable de sesión a variables JavaScript
      var userId = <?php echo json_encode($_SESSION['user']['id_user']); ?>;
      var userType = <?php echo json_encode($_SESSION['user']['id_type_user']); ?>;
    </script>
    
    <!--FUNCIÓN PARA COLOCAR EL MES ACTUAL EN LOS INPUTS DE INTERVALO DE FECHAS-->
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
    
    <!--FUNCIÓN PARA MARCAR EL AÑO ACTUAL EN EL SELECT DE AÑO Y UN FOR PARA IR AÑADIENDO EL AÑO SIGUIENTE-->
    <script>
      // Obtén el elemento select
      var yearSelect = document.getElementById("yearSelect");
      var currentYear = new Date().getFullYear(); // Obtiene el año actual
      var endYear = currentYear + 5; // Define el año final como 5 años más allá del año actual
      // Crea un bucle para generar opciones desde el año 2015 hasta el año final
      for (var year = 2020; year <= endYear; year++) {
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
    
    <!--FUNCIÓN PARA CAMBIAR LOS ESTADOS DE LOS RADIO BUTTON (AÑO Y MES O POR INTERVALO)-->
    <script>
      $(document).ready(function() {
        // Inicializar campos según el estado inicial del radio button y el select de estado
        updateFiltersByRadio();
        updateFiltersByStatus();
        
        // Manejar cambios en los radio buttons
        $('.filtrosDDL').on('change', function() {
          updateFiltersByRadio();
          updateFiltersByStatus();
        });
        
        // Manejar cambios en el select de estado
        $('#status_select').change(function() {
          updateFiltersByStatus();
        });
        
        function updateFiltersByRadio() {
          var isChecked = $('#check-p2').prop('checked');
          $('#yearSelect').prop('disabled', isChecked);
          $('#month_select').prop('disabled', isChecked);
          $('#start_fetch').prop('disabled', !isChecked);
          $('#finish_fetch').prop('disabled', !isChecked);
        }
        
        function updateFiltersByStatus() {
          var selectedValue = $('#status_select').val();
          if (selectedValue === '02' || selectedValue === 'null') {
            disableFilters();
          } else {
            enableFilters();
          }
        }
        
        function disableFilters() {
          $('#yearSelect').prop('disabled', true);
          $('#start_fetch').prop('disabled', true);
          $('#finish_fetch').prop('disabled', true);
          $('#month_select').prop('disabled', true);
          $('input[name="check"]').prop('disabled', true);
        }
        
        function enableFilters() {
          $('#yearSelect').prop('disabled', false);
          $('#start_fetch').prop('disabled', false);
          $('#finish_fetch').prop('disabled', false);
          $('#month_select').prop('disabled', false);
          $('input[name="check"]').prop('disabled', false);
          // Reaplicar la lógica de los radio buttons si es necesario
          updateFiltersByRadio();
        }
      });
    </script>
    
    <script>
      $(function () {
        var yearSelect = $("#yearSelect").val(),
        month_select = $("#month_select").val(),
        status = $("#status_select").val(),
        customer = $("#customer_select").val();
        
        var fecha1 = yearSelect + "-" + month_select + "-01 00:00:00";
        var fecha2 = yearSelect + "-" + month_select + "-31 23:59:59";
        loadFolders(fecha1, fecha2, status, customer);
      });
    </script>
    
    <script>
      $(document).ready(function(){
        $('.filtrosDDL').on('change', function() {
          // Las variables de yearSelect y month_select corresponden a los 2 select de mes y año
          // Las variables de start_fetch y finish_fetch corresponden al intervalo de fechas
          var yearSelect = $("#yearSelect").val(),
          month_select = $("#month_select").val(),
          fech1 = $("#start_fetch").val(),
          fech2 = $("#finish_fetch").val(),
          status = $("#status_select").val(),
          customer = $("#customer_select").val();
          
          // Verificar cuál radio button está seleccionado
          var isYearAndMonthSelected = $('#check-p1').prop('checked');
          var isDateRangeSelected = $('#check-p2').prop('checked');
          // Hacer algo basado en la selección
          if (isYearAndMonthSelected) {
            if (month_select === 'all') {
              var fecha1 = yearSelect + "-01-01 00:00:00";
              var fecha2 = yearSelect + "-12-31 23:59:59";
            } else {
              var fecha1 = yearSelect + "-" + month_select + "-01 00:00:00";
              var fecha2 = yearSelect + "-" + month_select + "-31 23:59:59";
            }
          } else if (isDateRangeSelected) {
            var fecha1 = fech1 + " 00:00:00";
            var fecha2 = fech2 + " 23:59:59";
          }
          loadFolders(fecha1, fecha2, status, customer);
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
          "columnDefs": [
            { "targets": 0, "orderable": false }, // Deshabilitar orden en la primera columna // detalles
            { "targets": 2, "orderable": false }, // Deshabilitar orden en la tercera columna // Estatus
            { "targets": 5, "orderable": false }  // Deshabilitar orden en la sexta columna // Seguimiento
          ],
          "destroy": true
        });
        // var table = $('#tblFolders').DataTable();
        table.clear().draw();
        $.ajax({
          type: "GET",
          url: "app/webservice.php",
          data: {
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
                "<a href='backoffice/folders/subfolder.php?id=" + item.id_folder + "&key=" + item.key_folder +"' class='btn btn-sm btn-success'><i class='fas fa-eye'></i></a>" + 
              "</td>" + 
              
              "<td style='text-align:left;' class='truncate-text'>" + 
                "<a href='backoffice/folders/subfolder.php?id=" + item.id_folder + "&key=" + item.key_folder +"'>" + item.name_folder + "</a>" + 
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
              
            "</tr>";
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
    
    <script>
      // Obtener los datos (totales) de las consultas PHP de la sección inicial
      const data = {
        // totalFoldersMonth: <?php echo $totalFoldersMonth; ?>,
        // totalDocumentsMonth: <?php echo $totalDocumentsMonth; ?>,
        totalgetFoldersVencidos: <?php echo $totalgetFoldersVencidos; ?>,
        totalgetFoldersCercaVencimiento: <?php echo $totalgetFoldersCercaVencimiento; ?>,
        totalFoldersVigentes: <?php echo $totalFoldersVigentes; ?>,
        totalFoldersSinPlazo: <?php echo $totalFoldersSinPlazo; ?>
      };
      // Convertir los datos en un arreglo para el gráfico
      const chartData = [
        // data.totalFoldersMonth,
        // data.totalDocumentsMonth,
        data.totalgetFoldersVencidos,
        data.totalgetFoldersCercaVencimiento,
        data.totalFoldersVigentes,
        data.totalFoldersSinPlazo
      ];
      
      const chartLabels = [
        // 'Clientes del mes',
        // 'Documentos del mes',
        'Clientes vencidos',
        'Clientes proximos a vencer',
        'Clientes vigentes',
        'Clientes sin plazo'
      ];
    </script>
    
    <script>
      const ctx = document.getElementById('pieChartStatusClients').getContext('2d');
      const chart = new Chart(ctx, {
        type: 'pie',
        data: {
          labels: chartLabels,
          datasets: [{
            data: chartData,
            backgroundColor: [
              // '#FF5800', // NARANJA - CLIENTES DEL MES
              // '#007BFF', // AZUL - DOCUMENTOS DEL MES
              '#DC3545', // ROJO - CARPETAS VENCIDAS
              '#FFA500', // AMARILLO - CERCA DE VENCIMIENTO
              '#28A745', // VERDE - VIGENTES
              '#17A2B8'  // TURQUESA - SIN FECHA DE VENCIMIENTO
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: false,
          plugins: {
            legend: {
              display: false // Oculta la leyenda predeterminada
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.raw || 0;
                  const total = context.chart.data.datasets[0].data.reduce((sum, val) => sum + val, 0);
                  const percentage = Math.round((value / total) * 100); // Calcula el porcentaje y redondea al entero más cercano
                  return `${label}: ${value} · (${percentage}%)`;
                }
              }
            }
          }
        }
      });
      
      // Generar una leyenda personalizada debajo del gráfico
      const legendContainer = document.getElementById('chartLegend');
      chartLabels.forEach((label, index) => {
        const color = chart.data.datasets[0].backgroundColor[index];
        const legendItem = document.createElement('div');
        legendItem.innerHTML = `
          <span style="display: inline-block; width: 15px; height: 15px; background-color: ${color}; margin-right: 10px; border-radius:100%;"></span>
          ${label}
        `;
        legendContainer.appendChild(legendItem);
      });
    </script>

  </body>
</html>