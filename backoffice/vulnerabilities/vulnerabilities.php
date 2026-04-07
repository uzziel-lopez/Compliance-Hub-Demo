<?php
// ===================================================================
// VERSIÓN DE DEBUG - Agrega esto TEMPORALMENTE al inicio del archivo
// ===================================================================

// ACTIVAR REPORTE DE ERRORES
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

echo "<!-- DEBUG: Iniciando carga del archivo -->\n";

// VERIFICAR ARCHIVOS UNO POR UNO
try {
    echo "<!-- DEBUG: Cargando config.php -->\n";
    include "../../app/config.php";
    echo "<!-- DEBUG: config.php cargado exitosamente -->\n";
} catch (Exception $e) {
    die("ERROR EN config.php: " . $e->getMessage());
}

try {
    echo "<!-- DEBUG: Cargando WebController.php -->\n";
    include "../../app/WebController.php";
    echo "<!-- DEBUG: WebController.php cargado exitosamente -->\n";
} catch (Exception $e) {
    die("ERROR EN WebController.php: " . $e->getMessage());
}

try {
    echo "<!-- DEBUG: Cargando ExcelController.php -->\n";
    include "../../app/ExcelController.php";
    echo "<!-- DEBUG: ExcelController.php cargado exitosamente -->\n";
} catch (Exception $e) {
    die("ERROR EN ExcelController.php: " . $e->getMessage());
}

try {
    echo "<!-- DEBUG: Verificando vendor/autoload.php -->\n";
    if (file_exists('../../vendor/autoload.php')) {
        require '../../vendor/autoload.php';
        echo "<!-- DEBUG: vendor/autoload.php cargado exitosamente -->\n";
    } else {
        echo "<!-- DEBUG: vendor/autoload.php NO EXISTE -->\n";
    }
} catch (Exception $e) {
    die("ERROR EN vendor/autoload.php: " . $e->getMessage());
}

try {
    echo "<!-- DEBUG: Cargando OperationController.php -->\n";
    require_once '../../app/OperationController.php';
    echo "<!-- DEBUG: OperationController.php cargado exitosamente -->\n";
} catch (Exception $e) {
    die("ERROR EN OperationController.php: " . $e->getMessage());
}

try {
    echo "<!-- DEBUG: Instanciando WebController -->\n";
    $controller = new WebController();
    echo "<!-- DEBUG: WebController instanciado exitosamente -->\n";
} catch (Exception $e) {
    die("ERROR AL INSTANCIAR WebController: " . $e->getMessage());
}

// Verificar si la sesión del usuario está activa
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    session_destroy();
    header("Location: ../../login.php");
    exit();
}

try {
    echo "<!-- DEBUG: Obteniendo lista de clientes -->\n";
    $customersList = $controller->getCustomersList(3, 1);
    echo "<!-- DEBUG: Lista de clientes obtenida exitosamente -->\n";
} catch (Exception $e) {
    echo "<!-- ERROR AL OBTENER CLIENTES: " . $e->getMessage() . " -->\n";
    $customersList = []; // Continuar con array vacío
}

// PROCESAR ACCIONES DEL FORMULARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        echo "<!-- DEBUG: Instanciando OperationController -->\n";
        $operationController = new OperationController();
        echo "<!-- DEBUG: OperationController instanciado exitosamente -->\n";
    } catch (Exception $e) {
        die("ERROR AL INSTANCIAR OperationController: " . $e->getMessage());
    }

    switch ($_POST['action']) {
        case 'createOperation':
            try {
                $result = $operationController->createOperation($_POST['operation']);

                if ($result['success']) {
                    $_SESSION['success_message'] = 'Operación vulnerable registrada exitosamente';
                    header('Location: vulnerabilities.php');
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Error al registrar la operación: ' . $result['error'];
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Error crítico: ' . $e->getMessage();
            }
            break;

        case 'deleteOperation':
            if (isset($_POST['delOperation']['idOperation'])) {
                try {
                    $result = $operationController->deleteOperation($_POST['delOperation']['idOperation']);

                    if ($result['success']) {
                        $_SESSION['success_message'] = 'Operación movida a la papelera';
                    } else {
                        $_SESSION['error_message'] = 'Error al eliminar la operación';
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'Error al eliminar: ' . $e->getMessage();
                }
            }
            header('Location: vulnerabilities.php');
            exit;


        case 'update':
            try {
                $operationController = new OperationController();
                $result = $operationController->updateOperation($_POST);

                if ($result['success']) {
                    // Redirigir con mensaje de éxito
                    header("Location: vulnerabilities.php?success=update&message=" . urlencode("Operación actualizada exitosamente"));
                    exit();
                } else {
                    // Mostrar error
                    $error_message = $result['error'] ?? 'Error al actualizar la operación';
                    header("Location: vulnerabilities.php?error=1&message=" . urlencode($error_message));
                    exit();
                }
            } catch (Exception $e) {
                error_log("Error al actualizar operación: " . $e->getMessage());
                header("Location: vulnerabilities.php?error=1&message=" . urlencode("Error interno del servidor"));
                exit();
            }

    }
}

// OBTENER DATOS REALES DE LA BASE DE DATOS
try {
    echo "<!-- DEBUG: Instanciando OperationController para obtener datos -->\n";
    $operationController = new OperationController();
    echo "<!-- DEBUG: OperationController instanciado para datos -->\n";

    // Obtener operaciones por tipo de cliente
    echo "<!-- DEBUG: Obteniendo operaciones de personas físicas -->\n";
    $operacionesData = [
        'personas-fisicas' => $operationController->getOperations(['tipo_cliente' => 'persona_fisica']),
        'personas-morales' => $operationController->getOperations(['tipo_cliente' => 'persona_moral']),
        'fideicomisos' => $operationController->getOperations(['tipo_cliente' => 'fideicomiso'])
    ];
    echo "<!-- DEBUG: Todas las operaciones obtenidas exitosamente -->\n";

} catch (Exception $e) {
    echo "<!-- ERROR AL OBTENER OPERACIONES: " . $e->getMessage() . " -->\n";
    // Datos de respaldo
    $operacionesData = [
        'personas-fisicas' => [],
        'personas-morales' => [],
        'fideicomisos' => []
    ];
}

// MOSTRAR MENSAJES DE ÉXITO/ERROR
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo $_SESSION['success_message'];
    echo '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>';
    echo '</div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo $_SESSION['error_message'];
    echo '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>';
    echo '</div>';
    unset($_SESSION['error_message']);
}

// FUNCIÓN PARA GENERAR UNA CLAVE PARA LA OPERACIÓN
$permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$clave = substr(str_shuffle($permitted_chars), 0, 6);

echo "<!-- DEBUG: Archivo cargado completamente sin errores fatales -->\n";
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
            $('input[name="folder[fech_orig_recib_folder]"]').removeAttr('required');
            // Mostrar/ocultar el div y agregar/quitar el atributo required según el estado del checkbox
            $('#opcion3').change(function () {
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
    <style>
        /* Estilos para los semáforos */
        .semaforo-verde {
            color: #28a745;
            font-size: 1.2rem;
        }

        .semaforo-amarillo {
            color: #ffc107;
            font-size: 1.2rem;
        }

        .semaforo-rojo {
            color: #dc3545;
            font-size: 1.2rem;
        }

        /* Estilo para pestañas tipo Google */
        .google-tabs {
            display: flex;
            border-bottom: 1px solid #dadce0;
            margin-bottom: 0;
            background-color: #ffffff;
            padding: 0;
        }

        .google-tab {
            background: none;
            border: none;
            padding: 12px 24px;
            color: #5f6368;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            position: relative;
            border-radius: 8px 8px 0 0;
            margin-right: 4px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .google-tab:hover {
            background-color: #f8f9fa;
            color: #202124;
        }

        .google-tab.active {
            color: #1a73e8;
            background-color: #ffffff;
            border-bottom: 2px solid #1a73e8;
        }

        .google-tab i {
            font-size: 16px;
        }

        /* Estilos para la tabla */
        .custom-table-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            margin: 0;
        }

        .custom-table thead th {
            background-color: #ffffff;
            color: #202124;
            font-weight: bold;
            text-align: center;
            padding: 16px 12px;
            border-bottom: 2px solid #e8eaed;
            font-size: 14px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .custom-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #e8eaed;
            font-size: 14px;
            vertical-align: middle;
        }

        .custom-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Estilos para campos clickeables */
        .clickeable-field {
            cursor: pointer;
            text-decoration: underline;
            color: #1a73e8;
        }

        .clickeable-field:hover {
            color: #1557b0;
        }

        /* Indicador de información faltante */
        .missing-info {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .missing-info::after {
            content: '';
            width: 8px;
            height: 8px;
            background-color: #ff9500;
            border-radius: 50%;
            display: inline-block;
        }

        /* Estilos para el buscador */
        .search-container {
            padding: 16px;
            background-color: #ffffff;
            border-bottom: 1px solid #e8eaed;
        }

        .search-input {
            width: 100%;
            max-width: 400px;
            padding: 10px 16px;
            border: 1px solid #dadce0;
            border-radius: 24px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .search-input:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        /* Contador de operaciones */
        .operations-counter {
            padding: 16px;
            background-color: #ffffff;
            font-weight: 500;
            color: #202124;
            border-bottom: 1px solid #e8eaed;
        }

        /* Ocultar contenedores de tabla */
        .tabla-container {
            display: none;
        }

        .tabla-container.active {
            display: block;
        }

        /* Estilos responsive */
        @media (max-width: 768px) {
            .google-tabs {
                flex-wrap: wrap;
                padding: 0 8px;
            }

            .google-tab {
                font-size: 12px;
                padding: 10px 16px;
            }

            .custom-table {
                font-size: 12px;
            }

            .custom-table thead th,
            .custom-table tbody td {
                padding: 8px;
            }
        }



        /* El contenedor no debe cortar los dropdowns */
        .custom-table-container {
            overflow: visible;
            /* antes estaba hidden */
        }

        /* Wrapper para hacer la tabla scrollable en móvil */
        .table-responsive-fix {
            overflow-x: auto;
            overflow-y: visible;
            /* importante para que los dropdowns no se corten */
            -webkit-overflow-scrolling: touch;
        }

        /* Asegura ancho mínimo de la tabla para forzar el scroll en móvil */
        .custom-table {
            min-width: 920px;
            /* ajusta según columnas que tengas */
        }

        /* Columna Acciones con ancho mínimo razonable */
        .custom-table th:last-child,
        .custom-table td:last-child {
            min-width: 150px;
            white-space: nowrap;
        }

        /* El dropdown dentro de tablas responsivas necesita esto en Bootstrap 4 */
        .table-responsive-fix .dropdown-menu {
            position: absolute;
            /* ayuda a que no quede “empujando” filas */
            will-change: transform;
            z-index: 1050;
            /* sobre la tabla */
        }

        /* Si usas sticky header, manténlo arriba aunque haya scroll horiz. */
        .custom-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            /* > que celdas */
            background: #fff;
        }


        /* Más columnas ⇒ más ancho mínimo para forzar scroll en móvil */
        .custom-table {
            min-width: 1400px;
            /* súbelo a 1600px si aún se aprieta */
        }

        /* Estilos para cards colapsables */
        .collapsible-card .card-header {
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            padding-right: 50px;
        }

        /* CORREGIDO: Hover que mantiene colores originales */
        .collapsible-card .card-header:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Hover específico para cada color de header */
        .collapsible-card .card-header.bg-primary:hover {
            background-color: #0056b3 !important;
        }

        .collapsible-card .card-header.bg-secondary:hover {
            background-color: #545b62 !important;
        }

        .collapsible-card .card-header.bg-info:hover {
            background-color: #138496 !important;
        }

        .collapsible-card .card-header.bg-success:hover {
            background-color: #1e7e34 !important;
        }

        .collapsible-card .card-header.bg-warning:hover {
            background-color: #d39e00 !important;
        }

        .collapsible-card .card-header.bg-danger:hover {
            background-color: #bd2130 !important;
        }

        /* Si usas colores personalizados como purple */
        .collapsible-card .card-header.bg-purple:hover {
            background-color: #5a4b81 !important;
        }

        .collapsible-card .card-header .collapse-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            transition: transform 0.3s ease;
            font-size: 16px;
            color: inherit;
            /* Mantiene el color del texto del header */
        }

        .collapsible-card .card-header[aria-expanded="false"] .collapse-icon {
            transform: translateY(-50%) rotate(-90deg);
        }

        .collapsible-card .card-header[aria-expanded="true"] .collapse-icon {
            transform: translateY(-50%) rotate(0deg);
        }

        /* Animación suave para el colapso */
        .collapsing {
            transition: height 0.35s ease;
        }

        /* Indicador visual para cards requeridas */
        .card-required .card-header::before {
            content: "*";
            color: #ffc107;
            font-weight: bold;
            margin-right: 5px;
            font-size: 18px;
        }

        /* Efecto visual cuando la card está colapsada */
        .collapsible-card .card-header[aria-expanded="false"] {
            border-bottom: none;
        }

        /* Estilo especial para la card activa */
        .card-active .card-header {
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        /* Efecto sutil en el icono al hacer hover */
        .collapsible-card .card-header:hover .collapse-icon {
            transform: translateY(-50%) scale(1.1) rotate(0deg);
        }

        .collapsible-card .card-header[aria-expanded="false"]:hover .collapse-icon {
            transform: translateY(-50%) scale(1.1) rotate(-90deg);
        }

        /* Mantener texto blanco en todos los estados */
        .collapsible-card .card-header,
        .collapsible-card .card-header:hover,
        .collapsible-card .card-header h6,
        .collapsible-card .card-header:hover h6 {
            color: white !important;
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
                        <div class="col-lg-6 col-sm-6">
                            <h1 class="m-0 text-dark">Operaciones Vulnerables</h1>
                        </div>

                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) -->
                        <?php if ($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3) { ?>
                            <div class="col-sm-4 text-right">
                                <!-- Botón para abrir el modal -->
                                <a href="#" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;"
                                    role="button" aria-pressed="true" onclick="abrirModalOperacion()">
                                    <i class="fas fa-plus pr-2"></i>Registrar nueva operación
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                    <hr>

                    <!-- FILTROS DE BUSQUEDA DE OPERACIONES POR INTERVALO DE FECHAS, POR ESTATUS Y POR ASESOR COMERCIAL -->
                    <form class="row mb-3" action="vulnerabilities.php" method="post">
                        <div class="col-lg-10 col-sm-12">
                            <strong>Filtro de fecha de operación</strong>

                            <div class="row">
                                <div class="input-group col-lg-2">
                                    <select class="form-control filtrosDDL" id="yearSelect" name="year_select">
                                        <!-- Se llenará dinámicamente con JavaScript -->
                                    </select>
                                </div>

                                <div class="input-group col-lg-3">
                                    <select class="form-control filtrosDDL" id="month_select" name="monthSelect">
                                        <option value="all">Todos los meses</option>
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

                                <div class="input-group col-lg-3">
                                    <select class="form-control filtrosDDL" id="status_select" name="statusSelect">
                                        <option value="all">Todos los riesgos</option>
                                        <option value="verde">Bajo Riesgo</option>
                                        <option value="amarillo">Riesgo Medio</option>
                                        <option value="rojo">Alto Riesgo</option>
                                    </select>
                                </div>

                                <div class="input-group col-lg-4">
                                    <select class="form-control filtrosDDL" id="customer_select" name="customerSelect">
                                        <option value="">Todas las empresas</option>
                                        <?php if (!empty($customersList)) {
                                            foreach ($customersList as $customer) { ?>
                                                <option value="<?php echo $customer['id_user']; ?>">
                                                    <?php echo $customer['name_user']; ?>
                                                </option>
                                            <?php }
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!--BOTÓN PARA GENERAR EL REPORTE DE OPERACIONES-->
                        <div class="col-lg-2 col-sm-12">
                            <button name="action" value="reportOperations" type="submit"
                                class="btn btn-md btn-outline-success float-sm-right">
                                <i class="fas fa-file-alt mr-1"></i> Generar reporte
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!--CONSULTA GENERAL DE TODAS LAS OPERACIONES-->
            <div class="content">
                <div class="container-fluid">

                    <!-- Pestañas estilo Google -->
                    <div class="google-tabs">
                        <button type="button" class="google-tab active" id="btn-personas-fisicas"
                            onclick="cambiarTab('personas-fisicas')">
                            <i class="fas fa-user"></i>Personas Físicas
                        </button>
                        <button type="button" class="google-tab" id="btn-personas-morales"
                            onclick="cambiarTab('personas-morales')">
                            <i class="fas fa-building"></i>Personas Morales
                        </button>
                        <button type="button" class="google-tab" id="btn-fideicomisos"
                            onclick="cambiarTab('fideicomisos')">
                            <i class="fas fa-handshake"></i>Fideicomisos
                        </button>
                    </div>

                    <div class="custom-table-container">
                        <!-- Contador de operaciones -->
                        <div class="operations-counter">
                            <strong>Total de operaciones: <span id="totalOperaciones">0</span></strong>
                        </div>

                        <!-- Buscador -->
                        <div class="search-container">
                            <input type="text" class="search-input" id="searchInputOperations"
                                placeholder="Buscar operación...">
                        </div>

                        <!-- Tabla Personas Físicas -->
                        <div id="tabla-personas-fisicas" class="tabla-container active table-responsive-fix">
                            <table class="custom-table" id="tblPersonasFisicas">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Empresa</th>
                                        <th>Cliente</th>
                                        <th>Fecha Operación</th>
                                        <th>Tipo de Propiedad</th>
                                        <th>Uso del Inmueble</th>
                                        <th>Dirección del Inmueble</th>
                                        <th>Código Postal</th>
                                        <th>Folio de Escritura</th>
                                        <th>Propietario Anterior</th>
                                        <th>Semáforo</th>
                                        <?php if ($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3) { ?>
                                            <th>Acciones</th>
                                        <?php } ?>
                                    </tr>
                                </thead>

                                <tbody id="dataPersonasFisicas"></tbody>
                            </table>
                        </div>

                        <!-- Tabla Personas Morales -->
                        <div id="tabla-personas-morales" class="tabla-container table-responsive-fix">
                            <table class="custom-table" id="tblPersonasMorales">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Empresa</th>
                                        <th>Cliente</th>
                                        <th>Fecha Operación</th>
                                        <th>Tipo de Propiedad</th>
                                        <th>Uso del Inmueble</th>
                                        <th>Dirección del Inmueble</th>
                                        <th>Código Postal</th>
                                        <th>Folio de Escritura</th>
                                        <th>Propietario Anterior</th>
                                        <th>Semáforo</th>
                                        <?php if ($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3) { ?>
                                            <th>Acciones</th>
                                        <?php } ?>
                                    </tr>
                                </thead>

                                <tbody id="dataPersonasMorales"></tbody>
                            </table>
                        </div>

                        <!-- Tabla Fideicomisos -->
                        <div id="tabla-fideicomisos" class="tabla-container table-responsive-fix">
                            <table class="custom-table" id="tblFideicomisos">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Empresa</th>
                                        <th>Cliente</th>
                                        <th>Fecha Operación</th>
                                        <th>Tipo de Propiedad</th>
                                        <th>Uso del Inmueble</th>
                                        <th>Dirección del Inmueble</th>
                                        <th>Código Postal</th>
                                        <th>Folio de Escritura</th>
                                        <th>Propietario Anterior</th>
                                        <th>Semáforo</th>
                                        <?php if ($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3) { ?>
                                            <th>Acciones</th>
                                        <?php } ?>
                                    </tr>
                                </thead>

                                <tbody id="dataFideicomisos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para agregar nueva operación -->

        <style>
            .modal-xl {
                max-width: 1200px;
            }

            .card-header h6 {
                margin: 0;
                font-weight: 600;
            }

            .text-danger {
                color: #dc3545 !important;
            }

            .alert {
                margin-bottom: 10px;
                padding: 10px 15px;
                border-radius: 5px;
            }

            .alert:last-child {
                margin-bottom: 0;
            }

            .input-group-text {
                background-color: #f8f9fa;
                border-color: #ced4da;
            }

            .form-check-label {
                font-size: 0.9rem;
            }

            .card {
                border: 1px solid #dee2e6;
                border-radius: 0.375rem;
            }

            .card-header {
                border-bottom: 1px solid rgba(0, 0, 0, .125);
            }

            .form-check-label strong {
                color: #495057;
            }

            #pf_domicilio_extranjero,
            #pm_domicilio_extranjero,
            #fid_domicilio_extranjero {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                border-left: 4px solid #007bff;
            }

            #pf_domicilio_extranjero h6,
            #pm_domicilio_extranjero h6,
            #fid_domicilio_extranjero h6 {
                color: #007bff;
                font-weight: 600;
            }

            .beneficiario-card {
                border: 1px solid #dee2e6;
            }

            .beneficiario-card .card-header {
                background-color: #f8f9fa;
                border-bottom: 1px solid #dee2e6;
            }

            .beneficiario-card h6.text-primary {
                color: #007bff !important;
                font-weight: 600;
                border-bottom: 2px solid #e9ecef;
                padding-bottom: 8px;
            }

            .nav-tabs .nav-link {
                color: #495057;
                border: 1px solid transparent;
            }

            .nav-tabs .nav-link.active {
                color: #007bff;
                background-color: #fff;
                border-color: #dee2e6 #dee2e6 #fff;
            }

            .nav-tabs .nav-link:hover {
                border-color: #e9ecef #e9ecef #dee2e6;
            }

            #moneda_otra {
                border-color: #28a745;
            }

            @media (max-width: 768px) {
                .modal-xl {
                    max-width: 95%;
                    margin: 1rem;
                }

                .card-body .row .col-lg-4,
                .card-body .row .col-lg-6,
                .card-body .row .col-lg-8 {
                    margin-bottom: 1rem;
                }

                .beneficiario-card .card-body .row .col-lg-2,
                .beneficiario-card .card-body .row .col-lg-3,
                .beneficiario-card .card-body .row .col-lg-4,
                .beneficiario-card .card-body .row .col-lg-6 {
                    margin-bottom: 1rem;
                }
            }

            #fid_beneficiarios_tabs .nav-link.active {
                background-color: #007bff !important;
                /* Azul más agradable */
                color: #ffffff !important;
                border-color: #007bff #007bff #fff !important;
            }
        </style><!-- Modal dinámico para agregar nueva operación vulnerable - LFPIORPI -->
        <div class="modal fade" id="modalAgregarOperacion" tabindex="-1" aria-labelledby="modalAgregarOperacionLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAgregarOperacionLabel">
                            <i class="fas fa-shield-alt"></i> Registrar nueva operación vulnerable
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <form id="formAgregarOperacion" action="vulnerabilities.php" method="POST">
                            <div id="accordion-formulario"></div>
                            <input name="operation[key_operation]" type="hidden" value="<?php echo $clave; ?>">
                            <input name="operation[tipo_cliente]" type="hidden" id="hidden_tipo_cliente">
                            <!-- NUEVOS CAMPOS OCULTOS PARA IDs CORRECTOS -->
                            <input name="operation[empresa_id_general]" type="hidden" id="hidden_empresa_id">
                            <input name="operation[id_cliente_existente_general]" type="hidden" id="hidden_cliente_id">


                            <!-- Sección 0: Información de la Empresa Seleccionada -->
                            <div class="card mb-3 collapsible-card">
                                <div class="card-header bg-info text-white" data-toggle="collapse"
                                    data-target="#collapse-info-empresa" aria-expanded="true"
                                    aria-controls="collapse-info-empresa">
                                    <h6 class="mb-0">
                                        <i class="fas fa-building"></i> Información de la Empresa
                                        <i class="fas fa-chevron-down collapse-icon"></i>
                                    </h6>
                                </div>
                                <div id="collapse-info-empresa" class="collapse show"
                                    data-parent="#accordion-formulario">
                                    <div class="card-body bg-light">
                                        <div class="alert alert-info">
                                            <small><i class="fas fa-info-circle mr-1"></i>
                                                Esta información se obtiene automáticamente de la empresa seleccionada y
                                                no se puede modificar</small>
                                        </div>

                                        <!-- Información General -->
                                        <div class="company-info-section mb-4">
                                            <h6 class="border-bottom pb-2 mb-3"><i
                                                    class="fas fa-info-circle text-info mr-2"></i>Información General
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Nombre:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_nombre_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">RFC:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_rfc_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Razón Social:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_razon_social_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Tipo:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_tipo_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Fecha Constitución:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_fecha_constitucion_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Contacto -->
                                        <div class="company-info-section mb-4">
                                            <h6 class="border-bottom pb-2 mb-3"><i
                                                    class="fas fa-phone text-success mr-2"></i>Contacto</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Teléfono:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_telefono_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Email:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_email_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Ubicación -->
                                        <div class="company-info-section mb-4">
                                            <h6 class="border-bottom pb-2 mb-3"><i
                                                    class="fas fa-map-marker-alt text-danger mr-2"></i>Ubicación</h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Estado:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_estado_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Ciudad:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_ciudad_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Colonia:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_colonia_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Dirección:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_direccion_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">C.P.:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_cp_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Representante Legal -->
                                        <div class="company-info-section">
                                            <h6 class="border-bottom pb-2 mb-3"><i
                                                    class="fas fa-user-tie text-warning mr-2"></i>Representante Legal
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Nombre:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_representante_nombre_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">RFC:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_representante_rfc_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">CURP:</label>
                                                        <input type="text"
                                                            class="form-control-plaintext bg-white border"
                                                            id="empresa_representante_curp_display" readonly
                                                            style="border-color: #dee2e6 !important;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>





                            <!-- Sección 1: Tipo de Cliente -->
                            <div class="card mb-3 collapsible-card">
                                <div class="card-header bg-primary text-white" data-toggle="collapse"
                                    data-target="#collapse-info-general" aria-expanded="true"
                                    aria-controls="collapse-info-general">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle"></i> Información General del Cliente
                                        <i class="fas fa-chevron-down collapse-icon"></i>
                                    </h6>
                                </div>
                                <div id="collapse-info-general" class="collapse show"
                                    data-parent="#accordion-formulario">
                                    <div class="card-body">
                                        <!-- Tipo de Cliente (readonly) -->
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>Tipo de Cliente:</label>
                                                    <input type="text" class="form-control" id="tipo_cliente_display"
                                                        readonly="">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Select de Empresa -->
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="select_empresa_general">
                                                        <i class="fas fa-building"></i> Seleccionar Empresa:
                                                    </label>
                                                    <select class="form-control" id="select_empresa_general"
                                                        name="empresa_id_general">
                                                        <option value="">-- Seleccionar empresa --</option>
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        Selecciona primero la empresa para filtrar los clientes
                                                        disponibles
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Select de Cliente Existente -->
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="select_cliente_general">
                                                        <i class="fas fa-user"></i> Seleccionar Cliente Existente
                                                        (Opcional):
                                                    </label>
                                                    <select class="form-control" id="select_cliente_general"
                                                        name="id_cliente_existente_general">
                                                        <option value="">-- Seleccionar cliente existente --</option>
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        Puedes seleccionar un cliente existente para autocompletar los
                                                        datos, o crear uno nuevo llenando el formulario
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Información adicional -->
                                        <div class="alert alert-info" role="alert">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Instrucciones:</strong>
                                            <ul class="mb-0 mt-2">
                                                <li>Si seleccionas una empresa, se mostrarán solo los clientes de esa
                                                    empresa</li>
                                                <li>Si no seleccionas empresa, se mostrarán todos los clientes
                                                    disponibles
                                                </li>
                                                <li>Al seleccionar un cliente existente, sus datos se cargarán
                                                    automáticamente</li>
                                                <li>Si no seleccionas un cliente, puedes crear uno nuevo llenando el
                                                    formulario</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>




                            <!-- Sección 2: Información de la Operación -->
                            <div class="card mb-3 collapsible-card card-required">
                                <div class="card-header bg-secondary text-white" data-toggle="collapse"
                                    data-target="#collapse-operacion" aria-expanded="true"
                                    aria-controls="collapse-operacion">
                                    <h6 class="mb-0">
                                        <i class="fas fa-handshake"></i> Información de la Operación
                                        <i class="fas fa-chevron-down collapse-icon"></i>
                                    </h6>
                                </div>
                                <div id="collapse-operacion" class="collapse show" data-parent="#accordion-formulario">

                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="fecha_operacion">Fecha de Operación: <span
                                                            class="text-danger">*</span></label>
                                                    <input name="operation[fecha_operacion]" type="date"
                                                        class="form-control" id="fecha_operacion" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="tipo_operacion">Tipo de Operación: <span
                                                            class="text-danger">*</span></label>
                                                    <select name="operation[tipo_operacion]" class="form-control"
                                                        id="tipo_operacion" required onchange="updateThresholdInfo()">
                                                        <option value="">Seleccione...</option>
                                                        <option value="intermediacion">Intermediación (Fracción I)
                                                        </option>
                                                        <option value="compra_directa">Compra directa</option>
                                                        <option value="preventa">Preventa (Fracción XX)</option>
                                                        <option value="construccion">Construcción (Fracción XX)</option>
                                                        <option value="arrendamiento">Arrendamiento (Fracción XX)
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="monto_operacion">Monto de la Operación: <span
                                                            class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">$</span>
                                                        </div>
                                                        <input name="operation[monto_operacion]" type="number"
                                                            class="form-control" id="monto_operacion" step="0.01"
                                                            required onchange="validateOperationAmount()">
                                                    </div>
                                                    <small id="threshold-info" class="form-text text-muted">Seleccione
                                                        el
                                                        tipo de operación para ver el umbral</small>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="moneda">Moneda:</label>
                                                    <select name="operation[moneda]" class="form-control" id="moneda"
                                                        onchange="toggleOtraMoneda()">
                                                        <option value="MXN">MXN - Peso Mexicano</option>
                                                        <option value="USD">USD - Dólar Americano</option>
                                                        <option value="EUR">EUR - Euro</option>
                                                        <option value="otra">Otra</option>
                                                    </select>
                                                    <input name="operation[moneda_otra]" type="text"
                                                        class="form-control mt-2" id="moneda_otra"
                                                        placeholder="Especifique la moneda" style="display: none;">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="forma_pago">Forma de Pago:</label>
                                                    <select name="operation[forma_pago]" class="form-control"
                                                        id="forma_pago" onchange="toggleCashAmount()">
                                                        <option value="transferencia">Transferencia bancaria</option>
                                                        <option value="cheque">Cheque</option>
                                                        <option value="efectivo">Efectivo</option>
                                                        <option value="credito">Crédito</option>
                                                        <option value="otro">Otro</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="cash-amount-section" class="row" style="display: none;">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="monto_efectivo">Monto en Efectivo:</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">$</span>
                                                        </div>
                                                        <input name="operation[monto_efectivo]" type="number"
                                                            class="form-control" id="monto_efectivo" step="0.01"
                                                            onchange="validateCashLimit()">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Alertas PLD -->
                                        <div id="pld-alerts-container" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección 3: Información del Inmueble -->
                            <div class="card mb-3 collapsible-card">
                                <div class="card-header bg-info text-white" data-toggle="collapse"
                                    data-target="#collapse-inmueble" aria-expanded="false"
                                    aria-controls="collapse-inmueble">
                                    <h6 class="mb-0">
                                        <i class="fas fa-building"></i> Información del Inmueble
                                        <i class="fas fa-chevron-down collapse-icon"></i>
                                    </h6>
                                </div>
                                <div id="collapse-inmueble" class="collapse" data-parent="#accordion-formulario">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="tipo_propiedad">Tipo de Propiedad: <span
                                                            class="text-danger">*</span></label>
                                                    <select name="operation[tipo_propiedad]" class="form-control"
                                                        id="tipo_propiedad" required>
                                                        <option value="">Seleccionar tipo...</option>
                                                        <option value="habitacional">Habitacional</option>
                                                        <option value="comercial">Comercial</option>
                                                        <option value="industrial">Industrial</option>
                                                        <option value="mixto">Mixto</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="uso_inmueble">Uso Específico del Inmueble:</label>
                                                    <select name="operation[uso_inmueble]" class="form-control"
                                                        id="uso_inmueble">
                                                        <option value="">Seleccionar uso...</option>
                                                        <option value="casa_residencial">Casa Residencial</option>
                                                        <option value="departamento">Departamento</option>
                                                        <option value="terreno">Terreno</option>
                                                        <option value="local_comercial">Local Comercial</option>
                                                        <option value="oficina">Oficina</option>
                                                        <option value="bodega">Bodega</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-8">
                                                <div class="form-group">
                                                    <label for="direccion_inmueble">Dirección del Inmueble:</label>
                                                    <textarea name="operation[direccion_inmueble]" class="form-control"
                                                        id="direccion_inmueble" rows="2"
                                                        placeholder="Calle, número, colonia, ciudad, estado"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="codigo_postal">Código Postal: <span
                                                            class="text-danger">*</span></label>
                                                    <input name="operation[codigo_postal]" type="text"
                                                        class="form-control" id="codigo_postal" maxlength="6"
                                                        placeholder="123456" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="folio_escritura">Folio de Escritura:</label>
                                                    <input name="operation[folio_escritura]" type="text"
                                                        class="form-control" id="folio_escritura"
                                                        placeholder="Folio de escritura">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="propietario_anterior">Propietario Anterior:</label>
                                                    <input name="operation[propietario_anterior]" type="text"
                                                        class="form-control" id="propietario_anterior"
                                                        placeholder="Nombre del propietario anterior">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección 4: Información del Cliente - PERSONA FÍSICA -->
                            <div class="card mb-3 collapsible-card" id="seccion-persona-fisica" style="display: none;">
                                <div class="card-header bg-success text-white" data-toggle="collapse"
                                    data-target="#collapse-persona-fisica" aria-expanded="false"
                                    aria-controls="collapse-persona-fisica">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user"></i> Información de la Persona Física
                                        <i class="fas fa-chevron-down collapse-icon"></i>
                                    </h6>
                                </div>
                                <div id="collapse-persona-fisica" class="collapse" data-parent="#accordion-formulario">

                                    <div class="card-body">
                                        <hr>
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pf_nombre">Nombre: <span
                                                            class="text-danger">*</span></label>
                                                    <input name="operation[pf_nombre]" type="text" class="form-control"
                                                        id="pf_nombre" placeholder="Nombre completo">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pf_apellido_paterno">Apellido Paterno: <span
                                                            class="text-danger">*</span></label>
                                                    <input name="operation[pf_apellido_paterno]" type="text"
                                                        class="form-control" id="pf_apellido_paterno"
                                                        placeholder="Apellido paterno">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pf_apellido_materno">Apellido Materno:</label>
                                                    <input name="operation[pf_apellido_materno]" type="text"
                                                        class="form-control" id="pf_apellido_materno"
                                                        placeholder="Apellido materno">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pf_rfc">RFC: <span class="text-danger">*</span></label>
                                                    <input name="operation[pf_rfc]" type="text" class="form-control"
                                                        id="pf_rfc" maxlength="13" placeholder="ABCD123456ABC">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pf_curp">CURP:</label>
                                                    <input name="operation[pf_curp]" type="text" class="form-control"
                                                        id="pf_curp" maxlength="18" placeholder="ABCD123456ABCDEFGH">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pf_fecha_nacimiento">Fecha de Nacimiento:</label>
                                                    <input name="operation[pf_fecha_nacimiento]" type="date"
                                                        class="form-control" id="pf_fecha_nacimiento">
                                                </div>
                                            </div>
                                        </div>

                                        <h6 class="mt-4 mb-3">Domicilio Nacional</h6>
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pf_estado">Estado:</label>
                                                    <input name="operation[pf_estado]" type="text" class="form-control"
                                                        id="pf_estado" placeholder="Estado">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pf_ciudad">Ciudad o Población:</label>
                                                    <input name="operation[pf_ciudad]" type="text" class="form-control"
                                                        id="pf_ciudad" placeholder="Ciudad">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pf_colonia">Colonia:</label>
                                                    <input name="operation[pf_colonia]" type="text" class="form-control"
                                                        id="pf_colonia" placeholder="Colonia">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="pf_calle">Calle:</label>
                                                    <input name="operation[pf_calle]" type="text" class="form-control"
                                                        id="pf_calle" placeholder="Calle">
                                                </div>
                                            </div>
                                            <div class="col-lg-2">
                                                <div class="form-group">
                                                    <label for="pf_num_exterior">Núm. Exterior:</label>
                                                    <input name="operation[pf_num_exterior]" type="text"
                                                        class="form-control" id="pf_num_exterior" placeholder="123">
                                                </div>
                                            </div>
                                            <div class="col-lg-2">
                                                <div class="form-group">
                                                    <label for="pf_num_interior">Núm. Interior:</label>
                                                    <input name="operation[pf_num_interior]" type="text"
                                                        class="form-control" id="pf_num_interior" placeholder="123">
                                                </div>
                                            </div>
                                            <div class="col-lg-2">
                                                <div class="form-group">
                                                    <label for="pf_codigo_postal">Código Postal:</label>
                                                    <input name="operation[pf_codigo_postal]" type="text"
                                                        class="form-control" id="pf_codigo_postal" maxlength="6"
                                                        placeholder="123456">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="pf_correo">Correo Electrónico:</label>
                                                    <input name="operation[pf_correo]" type="email" class="form-control"
                                                        id="pf_correo" placeholder="correo@ejemplo.com">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="pf_telefono">Teléfono:</label>
                                                    <input name="operation[pf_telefono]" type="tel" class="form-control"
                                                        id="pf_telefono" placeholder="Teléfono">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Domicilio Extranjero (Opcional) -->
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input"
                                                        id="pf_tiene_domicilio_extranjero"
                                                        onchange="toggleDomicilioExtranjero('pf')">
                                                    <label class="form-check-label" for="pf_tiene_domicilio_extranjero">
                                                        <strong>¿Tiene domicilio extranjero?</strong>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="pf_domicilio_extranjero" class="mt-3" style="display: none;">
                                            <h6 class="mb-3">Domicilio Extranjero (Opcional)</h6>
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pf_pais_origen">País de Origen:</label>
                                                        <input name="operation[pf_pais_origen]" type="text"
                                                            class="form-control" id="pf_pais_origen" placeholder="País">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pf_estado_provincia_ext">Estado o Provincia:</label>
                                                        <input name="operation[pf_estado_provincia_ext]" type="text"
                                                            class="form-control" id="pf_estado_provincia_ext"
                                                            placeholder="Estado">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pf_ciudad_poblacion_ext">Ciudad o Población:</label>
                                                        <input name="operation[pf_ciudad_poblacion_ext]" type="text"
                                                            class="form-control" id="pf_ciudad_poblacion_ext"
                                                            placeholder="Ciudad">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label for="pf_colonia_ext">Colonia del Extranjero:</label>
                                                        <input name="operation[pf_colonia_ext]" type="text"
                                                            class="form-control" id="pf_colonia_ext"
                                                            placeholder="Colonia">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label for="pf_calle_ext">Calle del Extranjero:</label>
                                                        <input name="operation[pf_calle_ext]" type="text"
                                                            class="form-control" id="pf_calle_ext" placeholder="Calle">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pf_num_exterior_ext">Núm. Exterior (Ext):</label>
                                                        <input name="operation[pf_num_exterior_ext]" type="text"
                                                            class="form-control" id="pf_num_exterior_ext"
                                                            placeholder="123">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pf_num_interior_ext">Núm. Interior (Ext):</label>
                                                        <input name="operation[pf_num_interior_ext]" type="text"
                                                            class="form-control" id="pf_num_interior_ext"
                                                            placeholder="123">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pf_codigo_postal_ext">Código Postal
                                                            Extranjero:</label>
                                                        <input name="operation[pf_codigo_postal_ext]" type="text"
                                                            class="form-control" id="pf_codigo_postal_ext"
                                                            placeholder="123456">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección 5: Información del Cliente - PERSONA MORAL -->
                            <div class="card mb-3 collapsible-card" id="seccion-persona-moral" style="display: none;">
                                <div class="card-header bg-warning text-white" data-toggle="collapse"
                                    data-target="#collapse-persona-moral" aria-expanded="false"
                                    aria-controls="collapse-persona-moral">
                                    <h6 class="mb-0">
                                        <i class="fas fa-building"></i> Información de la Persona Moral
                                        <i class="fas fa-chevron-down collapse-icon"></i>
                                    </h6>
                                </div>
                                <div id="collapse-persona-moral" class="collapse" data-parent="#accordion-formulario">

                                    <div class="card-body">


                                        <hr>
                                        <div class="row">
                                            <div class="col-lg-8">
                                                <div class="form-group">
                                                    <label for="pm_razon_social">Razón Social: <span
                                                            class="text-danger">*</span></label>
                                                    <input name="operation[pm_razon_social]" type="text"
                                                        class="form-control" id="pm_razon_social"
                                                        placeholder="Razón social">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pm_rfc">RFC Persona Moral: <span
                                                            class="text-danger">*</span></label>
                                                    <input name="operation[pm_rfc]" type="text" class="form-control"
                                                        id="pm_rfc" maxlength="12" placeholder="ABCD123456ABC">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="pm_fecha_constitucion">Fecha de Constitución:</label>
                                                    <input name="operation[pm_fecha_constitucion]" type="date"
                                                        class="form-control" id="pm_fecha_constitucion">
                                                </div>
                                            </div>
                                        </div>

                                        <h6 class="mt-4 mb-3">Apoderado Legal</h6>
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pm_apoderado_nombre">Nombre:</label>
                                                    <input name="operation[pm_apoderado_nombre]" type="text"
                                                        class="form-control" id="pm_apoderado_nombre"
                                                        placeholder="Nombre">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pm_apoderado_paterno">Apellido Paterno:</label>
                                                    <input name="operation[pm_apoderado_paterno]" type="text"
                                                        class="form-control" id="pm_apoderado_paterno"
                                                        placeholder="Apellido Paterno">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pm_apoderado_materno">Apellido Materno:</label>
                                                    <input name="operation[pm_apoderado_materno]" type="text"
                                                        class="form-control" id="pm_apoderado_materno"
                                                        placeholder="Apellido Materno">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pm_apoderado_rfc">RFC Apoderado Legal:</label>
                                                    <input name="operation[pm_apoderado_rfc]" type="text"
                                                        class="form-control" id="pm_apoderado_rfc" maxlength="13"
                                                        placeholder="ABCD123456ABC">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pm_apoderado_curp">CURP Apoderado Legal:</label>
                                                    <input name="operation[pm_apoderado_curp]" type="text"
                                                        class="form-control" id="pm_apoderado_curp" maxlength="18"
                                                        placeholder="ABCD123456ABCDEFGH">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pm_fecha_nacimiento_rep">Fecha de nacimiento de
                                                        representante legal:</label>
                                                    <input name="operation[pm_fecha_nacimiento_rep]" type="date"
                                                        class="form-control" id="pm_fecha_nacimiento_rep">
                                                </div>
                                            </div>
                                        </div>

                                        <h6 class="mt-4 mb-3">Domicilio Nacional</h6>
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pm_estado">Estado:</label>
                                                    <input name="operation[pm_estado]" type="text" class="form-control"
                                                        id="pm_estado" placeholder="Estado">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pm_ciudad">Ciudad o Población:</label>
                                                    <input name="operation[pm_ciudad]" type="text" class="form-control"
                                                        id="pm_ciudad" placeholder="Ciudad">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="pm_colonia">Colonia:</label>
                                                    <input name="operation[pm_colonia]" type="text" class="form-control"
                                                        id="pm_colonia" placeholder="Colonia">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="pm_calle">Calle:</label>
                                                    <input name="operation[pm_calle]" type="text" class="form-control"
                                                        id="pm_calle" placeholder="Calle">
                                                </div>
                                            </div>
                                            <div class="col-lg-2">
                                                <div class="form-group">
                                                    <label for="pm_num_exterior">Núm. Exterior:</label>
                                                    <input name="operation[pm_num_exterior]" type="text"
                                                        class="form-control" id="pm_num_exterior" placeholder="123">
                                                </div>
                                            </div>
                                            <div class="col-lg-2">
                                                <div class="form-group">
                                                    <label for="pm_num_interior">Núm. Interior:</label>
                                                    <input name="operation[pm_num_interior]" type="text"
                                                        class="form-control" id="pm_num_interior" placeholder="123">
                                                </div>
                                            </div>
                                            <div class="col-lg-2">
                                                <div class="form-group">
                                                    <label for="pm_codigo_postal">Código Postal:</label>
                                                    <input name="operation[pm_codigo_postal]" type="text"
                                                        class="form-control" id="pm_codigo_postal" maxlength="6"
                                                        placeholder="123456">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="pm_correo">Correo Electrónico:</label>
                                                    <input name="operation[pm_correo]" type="email" class="form-control"
                                                        id="pm_correo" placeholder="correo@ejemplo.com">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="pm_telefono">Teléfono:</label>
                                                    <input name="operation[pm_telefono]" type="tel" class="form-control"
                                                        id="pm_telefono" placeholder="Teléfono">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Domicilio Extranjero (Opcional) -->
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input"
                                                        id="pm_tiene_domicilio_extranjero"
                                                        onchange="toggleDomicilioExtranjero('pm')">
                                                    <label class="form-check-label" for="pm_tiene_domicilio_extranjero">
                                                        <strong>¿Tiene domicilio extranjero?</strong>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="pm_domicilio_extranjero" class="mt-3" style="display: none;">
                                            <h6 class="mb-3">Domicilio Extranjero (Opcional)</h6>
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pm_pais_origen">País de Origen:</label>
                                                        <input name="operation[pm_pais_origen]" type="text"
                                                            class="form-control" id="pm_pais_origen" placeholder="País">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pm_estado_provincia_ext">Estado o Provincia:</label>
                                                        <input name="operation[pm_estado_provincia_ext]" type="text"
                                                            class="form-control" id="pm_estado_provincia_ext"
                                                            placeholder="Estado">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pm_ciudad_poblacion_ext">Ciudad o Población:</label>
                                                        <input name="operation[pm_ciudad_poblacion_ext]" type="text"
                                                            class="form-control" id="pm_ciudad_poblacion_ext"
                                                            placeholder="Ciudad">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label for="pm_colonia_ext">Colonia del Extranjero:</label>
                                                        <input name="operation[pm_colonia_ext]" type="text"
                                                            class="form-control" id="pm_colonia_ext"
                                                            placeholder="Colonia">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label for="pm_calle_ext">Calle del Extranjero:</label>
                                                        <input name="operation[pm_calle_ext]" type="text"
                                                            class="form-control" id="pm_calle_ext" placeholder="Calle">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pm_num_exterior_ext">Núm. Exterior (Ext):</label>
                                                        <input name="operation[pm_num_exterior_ext]" type="text"
                                                            class="form-control" id="pm_num_exterior_ext"
                                                            placeholder="123">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pm_num_interior_ext">Núm. Interior (Ext):</label>
                                                        <input name="operation[pm_num_interior_ext]" type="text"
                                                            class="form-control" id="pm_num_interior_ext"
                                                            placeholder="123">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="pm_codigo_postal_ext">Código Postal
                                                            Extranjero:</label>
                                                        <input name="operation[pm_codigo_postal_ext]" type="text"
                                                            class="form-control" id="pm_codigo_postal_ext"
                                                            placeholder="123456">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Sección Beneficiarios Controladores -->
                                        <div class="mt-4">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">Beneficiarios Controladores</h6>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="agregarBeneficiarioControlador('pm')">
                                                    <i class="fas fa-plus"></i> Agregar Beneficiario
                                                </button>
                                            </div>
                                            <div id="pm_beneficiarios_container">
                                                <!-- Los beneficiarios se agregarán dinámicamente aquí -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sección 6: Información del Cliente - FIDEICOMISO -->
                                <div class="card mb-3 collapsible-card" id="seccion-fideicomiso" style="display: none;">
                                    <div class="card-header bg-purple text-white" data-toggle="collapse"
                                        data-target="#collapse-fideicomiso" aria-expanded="false"
                                        aria-controls="collapse-fideicomiso">
                                        <h6 class="mb-0">
                                            <i class="fas fa-handshake"></i> Información del Fideicomiso
                                            <i class="fas fa-chevron-down collapse-icon"></i>
                                        </h6>
                                    </div>
                                    <div id="collapse-fideicomiso" class="collapse" data-parent="#accordion-formulario">
                                        <div class="card-body">


                                            <hr>
                                            <div class="row">
                                                <div class="col-lg-8">
                                                    <div class="form-group">
                                                        <label for="fid_razon_social">Razón Social del Fiduciario: <span
                                                                class="text-danger">*</span></label>
                                                        <input name="operation[fid_razon_social]" type="text"
                                                            class="form-control" id="fid_razon_social"
                                                            placeholder="Razón social">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="fid_rfc">RFC del Fiduciario: <span
                                                                class="text-danger">*</span></label>
                                                        <input name="operation[fid_rfc]" type="text"
                                                            class="form-control" id="fid_rfc" maxlength="12"
                                                            placeholder="ABCD123456ABC">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label for="fid_numero_referencia">Número / Referencia de
                                                            Fideicomiso:</label>
                                                        <input name="operation[fid_numero_referencia]" type="text"
                                                            class="form-control" id="fid_numero_referencia"
                                                            placeholder="Referencia">
                                                    </div>
                                                </div>
                                            </div>

                                            <h6 class="mt-4 mb-3">Apoderado Legal</h6>
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="fid_apoderado_nombre">Nombre:</label>
                                                        <input name="operation[fid_apoderado_nombre]" type="text"
                                                            class="form-control" id="fid_apoderado_nombre"
                                                            placeholder="Nombre">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="fid_apoderado_paterno">Apellido Paterno:</label>
                                                        <input name="operation[fid_apoderado_paterno]" type="text"
                                                            class="form-control" id="fid_apoderado_paterno"
                                                            placeholder="Apellido Paterno">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="fid_apoderado_materno">Apellido Materno:</label>
                                                        <input name="operation[fid_apoderado_materno]" type="text"
                                                            class="form-control" id="fid_apoderado_materno"
                                                            placeholder="Apellido Materno">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="fid_apoderado_rfc">RFC Apoderado Legal:</label>
                                                        <input name="operation[fid_apoderado_rfc]" type="text"
                                                            class="form-control" id="fid_apoderado_rfc" maxlength="13"
                                                            placeholder="ABCD123456ABC">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="fid_apoderado_curp">CURP Apoderado Legal:</label>
                                                        <input name="operation[fid_apoderado_curp]" type="text"
                                                            class="form-control" id="fid_apoderado_curp" maxlength="18"
                                                            placeholder="ABCD123456ABCDEFGH">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="fid_fecha_nacimiento_rep">Fecha de nacimiento de
                                                            representante legal:</label>
                                                        <input name="operation[fid_fecha_nacimiento_rep]" type="date"
                                                            class="form-control" id="fid_fecha_nacimiento_rep">
                                                    </div>
                                                </div>
                                            </div>

                                            <h6 class="mt-4 mb-3">Domicilio Nacional</h6>
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="fid_estado">Estado:</label>
                                                        <input name="operation[fid_estado]" type="text"
                                                            class="form-control" id="fid_estado" placeholder="Estado">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="fid_ciudad">Ciudad o Población:</label>
                                                        <input name="operation[fid_ciudad]" type="text"
                                                            class="form-control" id="fid_ciudad" placeholder="Ciudad">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="fid_colonia">Colonia:</label>
                                                        <input name="operation[fid_colonia]" type="text"
                                                            class="form-control" id="fid_colonia" placeholder="Colonia">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label for="fid_calle">Calle:</label>
                                                        <input name="operation[fid_calle]" type="text"
                                                            class="form-control" id="fid_calle" placeholder="Calle">
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="form-group">
                                                        <label for="fid_num_exterior">Núm. Exterior:</label>
                                                        <input name="operation[fid_num_exterior]" type="text"
                                                            class="form-control" id="fid_num_exterior"
                                                            placeholder="123">
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="form-group">
                                                        <label for="fid_num_interior">Núm. Interior:</label>
                                                        <input name="operation[fid_num_interior]" type="text"
                                                            class="form-control" id="fid_num_interior"
                                                            placeholder="123">
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="form-group">
                                                        <label for="fid_codigo_postal">Código Postal:</label>
                                                        <input name="operation[fid_codigo_postal]" type="text"
                                                            class="form-control" id="fid_codigo_postal" maxlength="6"
                                                            placeholder="123456">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label for="fid_correo">Correo Electrónico:</label>
                                                        <input name="operation[fid_correo]" type="email"
                                                            class="form-control" id="fid_correo"
                                                            placeholder="correo@ejemplo.com">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label for="fid_telefono">Teléfono:</label>
                                                        <input name="operation[fid_telefono]" type="tel"
                                                            class="form-control" id="fid_telefono"
                                                            placeholder="Teléfono">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Domicilio Extranjero (Opcional) -->
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input"
                                                            id="fid_tiene_domicilio_extranjero"
                                                            onchange="toggleDomicilioExtranjero('fid')">
                                                        <label class="form-check-label"
                                                            for="fid_tiene_domicilio_extranjero">
                                                            <strong>¿Tiene domicilio extranjero?</strong>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="fid_domicilio_extranjero" class="mt-3" style="display: none;">
                                                <h6 class="mb-3">Domicilio Extranjero (Opcional)</h6>
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label for="fid_pais_origen">País de Origen:</label>
                                                            <input name="operation[fid_pais_origen]" type="text"
                                                                class="form-control" id="fid_pais_origen"
                                                                placeholder="País">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label for="fid_estado_provincia_ext">Estado o
                                                                Provincia:</label>
                                                            <input name="operation[fid_estado_provincia_ext]"
                                                                type="text" class="form-control"
                                                                id="fid_estado_provincia_ext" placeholder="Estado">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label for="fid_ciudad_poblacion_ext">Ciudad o
                                                                Población:</label>
                                                            <input name="operation[fid_ciudad_poblacion_ext]"
                                                                type="text" class="form-control"
                                                                id="fid_ciudad_poblacion_ext" placeholder="Ciudad">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label for="fid_colonia_ext">Colonia del Extranjero:</label>
                                                            <input name="operation[fid_colonia_ext]" type="text"
                                                                class="form-control" id="fid_colonia_ext"
                                                                placeholder="Colonia">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label for="fid_calle_ext">Calle del Extranjero:</label>
                                                            <input name="operation[fid_calle_ext]" type="text"
                                                                class="form-control" id="fid_calle_ext"
                                                                placeholder="Calle">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label for="fid_num_exterior_ext">Núm. Exterior
                                                                (Ext):</label>
                                                            <input name="operation[fid_num_exterior_ext]" type="text"
                                                                class="form-control" id="fid_num_exterior_ext"
                                                                placeholder="123">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label for="fid_num_interior_ext">Núm. Interior
                                                                (Ext):</label>
                                                            <input name="operation[fid_num_interior_ext]" type="text"
                                                                class="form-control" id="fid_num_interior_ext"
                                                                placeholder="123">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label for="fid_codigo_postal_ext">Código Postal
                                                                Extranjero:</label>
                                                            <input name="operation[fid_codigo_postal_ext]" type="text"
                                                                class="form-control" id="fid_codigo_postal_ext"
                                                                placeholder="123456">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Sección Beneficiarios Controladores para Fideicomiso -->
                                            <div class="mt-4">
                                                <h6 class="mb-3">Beneficiarios Controladores del Fideicomiso</h6>

                                                <!-- Pestañas para diferentes roles -->
                                                <ul class="nav nav-tabs" id="fid_beneficiarios_tabs" role="tablist">
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link active" id="fideicomitente-tab"
                                                            data-toggle="tab" href="#fideicomitente"
                                                            role="tab">Fideicomitente(s)</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="fiduciario-tab" data-toggle="tab"
                                                            href="#fiduciario" role="tab">Fiduciario(s)</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="fideicomisario-tab" data-toggle="tab"
                                                            href="#fideicomisario" role="tab">Fideicomisario(s)</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="control-efectivo-tab" data-toggle="tab"
                                                            href="#control-efectivo" role="tab">Control Efectivo</a>
                                                    </li>
                                                </ul>

                                                <div class="tab-content mt-3" id="fid_beneficiarios_content">
                                                    <div class="tab-pane fade show active" id="fideicomitente"
                                                        role="tabpanel">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-3">
                                                            <span>Fideicomitente(s)</span>
                                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                                onclick="agregarBeneficiarioControlador('fid', 'fideicomitente')">
                                                                <i class="fas fa-plus"></i> Agregar Fideicomitente
                                                            </button>
                                                        </div>
                                                        <div id="fid_fideicomitente_container"></div>
                                                    </div>

                                                    <div class="tab-pane fade" id="fiduciario" role="tabpanel">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-3">
                                                            <span>Fiduciario(s)</span>
                                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                                onclick="agregarBeneficiarioControlador('fid', 'fiduciario')">
                                                                <i class="fas fa-plus"></i> Agregar Fiduciario
                                                            </button>
                                                        </div>
                                                        <div id="fid_fiduciario_container"></div>
                                                    </div>

                                                    <div class="tab-pane fade" id="fideicomisario" role="tabpanel">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-3">
                                                            <span>Fideicomisario(s)</span>
                                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                                onclick="agregarBeneficiarioControlador('fid', 'fideicomisario')">
                                                                <i class="fas fa-plus"></i> Agregar Fideicomisario
                                                            </button>
                                                        </div>
                                                        <div id="fid_fideicomisario_container"></div>
                                                    </div>

                                                    <div class="tab-pane fade" id="control-efectivo" role="tabpanel">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-3">
                                                            <span>Personas con Control Efectivo</span>
                                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                                onclick="agregarBeneficiarioControlador('fid', 'control_efectivo')">
                                                                <i class="fas fa-plus"></i> Agregar Persona con Control
                                                            </button>
                                                        </div>
                                                        <div id="fid_control_efectivo_container"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>

                            <!-- Observaciones -->
                            <div class="form-group">
                                <label for="observaciones">Observaciones Internas:</label>
                                <textarea name="operation[observaciones]" class="form-control" id="observaciones"
                                    rows="3" placeholder="Observaciones adicionales sobre la operación"></textarea>
                            </div>

                            <div class="text-right">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success" name="action" value="createOperation">
                                    <i class="fas fa-save"></i> Guardar Operación
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Umbrales LFPIORPI 2025
            const UMBRALES_PLD = {
                'intermediacion': 1605000, // Fracción I - 8,025 UMAs
                'preventa': 907948,        // Fracción XX - 4,540 UMAs
                'construccion': 907948,    // Fracción XX - 4,540 UMAs
                'arrendamiento': 907948,   // Fracción XX - 4,540 UMAs
                'compra_directa': 1605000  // Aplicar umbral más alto por seguridad
            };

            const LIMITE_EFECTIVO_PLD = 719200; // Art. 32 LFPIORPI

            // Contadores para beneficiarios controladores
            let contadorBeneficiarios = {
                pm: 0,
                fid_fideicomitente: 0,
                fid_fiduciario: 0,
                fid_fideicomisario: 0,
                fid_control_efectivo: 0
            };

            // Función que se ejecuta cuando se abre el modal
            function abrirModalOperacion() {
                var tipoCliente = obtenerTipoClienteActivo();
                console.log('Tipo de cliente detectado:', tipoCliente);

                configurarModalSegunTipo(tipoCliente);

                var today = new Date().toISOString().split('T')[0];
                $('#fecha_operacion').val(today);

                limpiarFormulario();

                $('#modalAgregarOperacion').modal('show');
            }

            // Función para obtener el tipo de cliente activo según la pestaña
            function obtenerTipoClienteActivo() {
                if (typeof tabActiva !== 'undefined') {
                    return tabActiva;
                }

                if ($('#btn-personas-fisicas').hasClass('active')) {
                    return 'personas-fisicas';
                } else if ($('#btn-personas-morales').hasClass('active')) {
                    return 'personas-morales';
                } else if ($('#btn-fideicomisos').hasClass('active')) {
                    return 'fideicomisos';
                }
                return 'personas-fisicas';
            }

            // Función para configurar el modal según el tipo de cliente
            function configurarModalSegunTipo(tipoCliente) {
                console.log('Configurando modal para:', tipoCliente);

                $('#seccion-persona-fisica').hide();
                $('#seccion-persona-moral').hide();
                $('#seccion-fideicomiso').hide();

                switch (tipoCliente) {
                    case 'personas-fisicas':
                        $('#tipo_cliente_display').val('Persona Física');
                        $('#hidden_tipo_cliente').val('Persona Física');
                        $('#seccion-persona-fisica').show();
                        $('#pf_nombre, #pf_apellido_paterno, #pf_rfc').attr('required', true);
                        break;

                    case 'personas-morales':
                        $('#tipo_cliente_display').val('Persona Moral');
                        $('#hidden_tipo_cliente').val('Persona Moral');
                        $('#seccion-persona-moral').show();
                        $('#pm_razon_social, #pm_rfc').attr('required', true);
                        break;

                    case 'fideicomisos':
                        $('#tipo_cliente_display').val('Fideicomiso');
                        $('#hidden_tipo_cliente').val('Fideicomiso');
                        $('#seccion-fideicomiso').show();
                        $('#fid_razon_social, #fid_rfc').attr('required', true);
                        break;

                    default:
                        $('#tipo_cliente_display').val('Persona Física');
                        $('#hidden_tipo_cliente').val('Persona Física');
                        $('#seccion-persona-fisica').show();
                        $('#pf_nombre, #pf_apellido_paterno, #pf_rfc').attr('required', true);
                        break;
                }
            }



            // Función para mostrar/ocultar campo de otra moneda
            function toggleOtraMoneda() {
                const monedaSelect = $('#moneda');
                const otraMonedaInput = $('#moneda_otra');

                if (monedaSelect.val() === 'otra') {
                    otraMonedaInput.show().attr('required', true);
                } else {
                    otraMonedaInput.hide().removeAttr('required').val('');
                }
            }

            // Función para agregar beneficiario controlador
            function agregarBeneficiarioControlador(tipo, rol = '') {
                let containerId = '';
                let prefijo = '';
                let titulo = '';

                if (tipo === 'pm') {
                    containerId = 'pm_beneficiarios_container';
                    prefijo = 'pm_bc_' + contadorBeneficiarios.pm;
                    titulo = 'Beneficiario Controlador #' + (contadorBeneficiarios.pm + 1);
                    contadorBeneficiarios.pm++;
                } else if (tipo === 'fid') {
                    containerId = 'fid_' + rol + '_container';
                    prefijo = 'fid_' + rol + '_' + contadorBeneficiarios['fid_' + rol];
                    titulo = rol.charAt(0).toUpperCase() + rol.slice(1) + ' #' + (contadorBeneficiarios['fid_' + rol] + 1);
                    contadorBeneficiarios['fid_' + rol]++;
                }

                const beneficiarioHTML = `
        <div class="card mb-3 beneficiario-card" id="${prefijo}_card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0 flex-grow-1">${titulo}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="eliminarBeneficiario('${prefijo}_card')">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="card-body">
                <!-- Información Personal -->
                <h6 class="text-primary mb-3">Información Personal</h6>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="${prefijo}_nombre">Nombre(s): <span class="text-danger">*</span></label>
                            <input name="operation[${prefijo}_nombre]" type="text" class="form-control" id="${prefijo}_nombre" required>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="${prefijo}_apellido_paterno">Apellido Paterno: <span class="text-danger">*</span></label>
                            <input name="operation[${prefijo}_apellido_paterno]" type="text" class="form-control" id="${prefijo}_apellido_paterno" required>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="${prefijo}_apellido_materno">Apellido Materno:</label>
                            <input name="operation[${prefijo}_apellido_materno]" type="text" class="form-control" id="${prefijo}_apellido_materno">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="${prefijo}_curp">CURP:</label>
                            <input name="operation[${prefijo}_curp]" type="text" class="form-control" id="${prefijo}_curp" maxlength="18">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="${prefijo}_rfc">RFC:</label>
                            <input name="operation[${prefijo}_rfc]" type="text" class="form-control" id="${prefijo}_rfc" maxlength="13">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="${prefijo}_fecha_nacimiento">Fecha de Nacimiento:</label>
                            <input name="operation[${prefijo}_fecha_nacimiento]" type="date" class="form-control" id="${prefijo}_fecha_nacimiento">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="${prefijo}_nacionalidad">Nacionalidad:</label>
                            <select name="operation[${prefijo}_nacionalidad]" class="form-control" id="${prefijo}_nacionalidad">
                                <option value="mexicana">Mexicana</option>
                                <option value="extranjera">Extranjera</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="${prefijo}_pais_nacimiento">País de Nacimiento:</label>
                            <input name="operation[${prefijo}_pais_nacimiento]" type="text" class="form-control" id="${prefijo}_pais_nacimiento" value="México">
                        </div>
                    </div>
                </div>
                
                <!-- Información de Participación -->
                <h6 class="text-primary mb-3 mt-4">Información de Participación</h6>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="${prefijo}_tipo_participacion">Tipo de Participación:</label>
                            <select name="operation[${prefijo}_tipo_participacion]" class="form-control" id="${prefijo}_tipo_participacion">
                                <option value="">Seleccione...</option>
                                <option value="propiedad">Propiedad</option>
                                <option value="control">Control</option>
                                <option value="influencia_significativa">Influencia Significativa</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="${prefijo}_porcentaje">Porcentaje de Participación (%):</label>
                            <input name="operation[${prefijo}_porcentaje]" type="number" class="form-control" id="${prefijo}_porcentaje" min="0" max="100" step="0.01">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="${prefijo}_fecha_bc">Fecha en que adquirió la calidad de BC:</label>
                            <input name="operation[${prefijo}_fecha_bc]" type="date" class="form-control" id="${prefijo}_fecha_bc">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="${prefijo}_es_directo">¿Es directo?</label>
                            <select name="operation[${prefijo}_es_directo]" class="form-control" id="${prefijo}_es_directo">
                                <option value="">Seleccione...</option>
                                <option value="si">Sí</option>
                                <option value="no">No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="${prefijo}_cadena_titularidad">¿Existe cadena de titularidad?</label>
                            <select name="operation[${prefijo}_cadena_titularidad]" class="form-control" id="${prefijo}_cadena_titularidad" onchange="toggleCadenaTitularidad('${prefijo}')">
                                <option value="">Seleccione...</option>
                                <option value="si">Sí</option>
                                <option value="no">No</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row" id="${prefijo}_descripcion_cadena" style="display: none;">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label for="${prefijo}_descripcion_cadena_text">Descripción de la cadena de titularidad o control:</label>
                            <textarea name="operation[${prefijo}_descripcion_cadena_text]" class="form-control" id="${prefijo}_descripcion_cadena_text" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Domicilio -->
                <h6 class="text-primary mb-3 mt-4">Domicilio</h6>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="${prefijo}_estado">Estado:</label>
                            <input name="operation[${prefijo}_estado]" type="text" class="form-control" id="${prefijo}_estado">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="${prefijo}_ciudad">Ciudad:</label>
                            <input name="operation[${prefijo}_ciudad]" type="text" class="form-control" id="${prefijo}_ciudad">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="${prefijo}_colonia">Colonia:</label>
                            <input name="operation[${prefijo}_colonia]" type="text" class="form-control" id="${prefijo}_colonia">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="${prefijo}_calle">Calle:</label>
                            <input name="operation[${prefijo}_calle]" type="text" class="form-control" id="${prefijo}_calle">
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="form-group">
                            <label for="${prefijo}_num_exterior">Núm. Ext.:</label>
                            <input name="operation[${prefijo}_num_exterior]" type="text" class="form-control" id="${prefijo}_num_exterior">
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="form-group">
                            <label for="${prefijo}_num_interior">Núm. Int.:</label>
                            <input name="operation[${prefijo}_num_interior]" type="text" class="form-control" id="${prefijo}_num_interior">
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="form-group">
                            <label for="${prefijo}_cp">C.P.:</label>
                            <input name="operation[${prefijo}_cp]" type="text" class="form-control" id="${prefijo}_cp" maxlength="6">
                        </div>
                    </div>
                </div>
                
                <!-- Contacto -->
                <h6 class="text-primary mb-3 mt-4">Información de Contacto</h6>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="${prefijo}_correo">Correo Electrónico:</label>
                            <input name="operation[${prefijo}_correo]" type="email" class="form-control" id="${prefijo}_correo">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="${prefijo}_telefono">Teléfono:</label>
                            <input name="operation[${prefijo}_telefono]" type="tel" class="form-control" id="${prefijo}_telefono">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

                $('#' + containerId).append(beneficiarioHTML);
            }

            // Función para eliminar beneficiario
            function eliminarBeneficiario(cardId) {
                if (confirm('¿Está seguro de eliminar este beneficiario controlador?')) {
                    $('#' + cardId).remove();

                    // AGREGAR ESTAS LÍNEAS PARA REINDEXAR LOS TÍTULOS
                    reindexarBeneficiarios();
                }
            }


            // NUEVA FUNCIÓN PARA REINDEXAR (agregar después de eliminarBeneficiario)
            function reindexarBeneficiarios() {
                // Reindexar PM
                $('#pm_beneficiarios_container .beneficiario-card').each(function (index) {
                    $(this).find('.card-header h6').text('Beneficiario Controlador #' + (index + 1));
                });
                contadorBeneficiarios.pm = $('#pm_beneficiarios_container .beneficiario-card').length;

                // Reindexar Fideicomiso - Fideicomitente
                $('#fid_fideicomitente_container .beneficiario-card').each(function (index) {
                    $(this).find('.card-header h6').text('Fideicomitente #' + (index + 1));
                });
                contadorBeneficiarios.fid_fideicomitente = $('#fid_fideicomitente_container .beneficiario-card').length;

                // Reindexar Fideicomiso - Fiduciario
                $('#fid_fiduciario_container .beneficiario-card').each(function (index) {
                    $(this).find('.card-header h6').text('Fiduciario #' + (index + 1));
                });
                contadorBeneficiarios.fid_fiduciario = $('#fid_fiduciario_container .beneficiario-card').length;

                // Reindexar Fideicomiso - Fideicomisario
                $('#fid_fideicomisario_container .beneficiario-card').each(function (index) {
                    $(this).find('.card-header h6').text('Fideicomisario #' + (index + 1));
                });
                contadorBeneficiarios.fid_fideicomisario = $('#fid_fideicomisario_container .beneficiario-card').length;

                // Reindexar Fideicomiso - Control Efectivo
                $('#fid_control_efectivo_container .beneficiario-card').each(function (index) {
                    $(this).find('.card-header h6').text('Persona con Control Efectivo #' + (index + 1));
                });
                contadorBeneficiarios.fid_control_efectivo = $('#fid_control_efectivo_container .beneficiario-card').length;
            }

            // Función para mostrar/ocultar descripción de cadena de titularidad
            function toggleCadenaTitularidad(prefijo) {
                const select = $('#' + prefijo + '_cadena_titularidad');
                const descripcionDiv = $('#' + prefijo + '_descripcion_cadena');

                if (select.val() === 'si') {
                    descripcionDiv.show();
                    $('#' + prefijo + '_descripcion_cadena_text').attr('required', true);
                } else {
                    descripcionDiv.hide();
                    $('#' + prefijo + '_descripcion_cadena_text').removeAttr('required').val('');
                }
            }

            // Función para limpiar el formulario
            function limpiarFormulario() {
                var tipoClienteDisplay = $('#tipo_cliente_display').val();
                var hiddenTipoCliente = $('#hidden_tipo_cliente').val();

                $('#formAgregarOperacion')[0].reset();

                $('#tipo_cliente_display').val(tipoClienteDisplay);
                $('#hidden_tipo_cliente').val(hiddenTipoCliente);

                $('#pld-alerts-container').html('');
                $('#cash-amount-section').hide();
                $('#moneda_otra').hide();

                $('#pf_domicilio_extranjero, #pm_domicilio_extranjero, #fid_domicilio_extranjero').hide();
                $('#pf_tiene_domicilio_extranjero, #pm_tiene_domicilio_extranjero, #fid_tiene_domicilio_extranjero').prop('checked', false);

                $('#pm_beneficiarios_container').empty();
                $('#fid_fideicomitente_container, #fid_fiduciario_container, #fid_fideicomisario_container, #fid_control_efectivo_container').empty();

                contadorBeneficiarios = {
                    pm: 0,
                    fid_fideicomitente: 0,
                    fid_fiduciario: 0,
                    fid_fideicomisario: 0,
                    fid_control_efectivo: 0
                };

                $('#pf_nombre, #pf_apellido_paterno, #pf_rfc').removeAttr('required');
                $('#pm_razon_social, #pm_rfc').removeAttr('required');
                $('#fid_razon_social, #fid_rfc').removeAttr('required');
            }

            // Función para actualizar información de umbral
            function updateThresholdInfo() {
                const tipoOperacion = $('#tipo_operacion').val();
                const thresholdInfo = $('#threshold-info');

                if (tipoOperacion && UMBRALES_PLD[tipoOperacion]) {
                    const umbral = UMBRALES_PLD[tipoOperacion].toLocaleString('es-MX');
                    thresholdInfo.html(`<strong>Umbral de reportabilidad:</strong> ${umbral} MXN`);
                    thresholdInfo.removeClass('text-muted').addClass('text-info');
                } else {
                    thresholdInfo.html('Seleccione el tipo de operación para ver el umbral');
                    thresholdInfo.removeClass('text-info').addClass('text-muted');
                }
            }

            // Función para validar el monto de la operación
            function validateOperationAmount() {
                const tipoOperacion = $('#tipo_operacion').val();
                const monto = parseFloat($('#monto_operacion').val()) || 0;
                const alertContainer = $('#pld-alerts-container');

                if (!tipoOperacion || monto === 0) {
                    alertContainer.html('');
                    return;
                }

                const umbral = UMBRALES_PLD[tipoOperacion];
                let alertHTML = '';

                if (monto > umbral) {
                    alertHTML += `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Operación reportable al SAT:</strong> Esta operación supera el umbral de ${umbral.toLocaleString('es-MX')} MXN y debe reportarse mediante XML.
            </div>
        `;
                } else {
                    alertHTML += `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Operación no reportable:</strong> Esta operación está por debajo del umbral pero debe registrarse en bitácora interna.
            </div>
        `;
                }

                alertContainer.html(alertHTML);
            }

            // Función para mostrar/ocultar sección de efectivo
            function toggleCashAmount() {
                const formaPago = $('#forma_pago').val();
                const cashSection = $('#cash-amount-section');

                if (formaPago === 'efectivo') {
                    cashSection.show();
                    $('#monto_efectivo').attr('required', true);
                } else {
                    cashSection.hide();
                    $('#monto_efectivo').removeAttr('required').val('');
                    validateCashLimit();
                }
            }

            // Función para validar límite de efectivo
            function validateCashLimit() {
                const montoEfectivo = parseFloat($('#monto_efectivo').val()) || 0;
                const alertContainer = $('#pld-alerts-container');
                let alertsHTML = alertContainer.html();

                alertsHTML = alertsHTML.replace(/<div class="alert alert-danger">.*?<\/div>/gs, '');

                if (montoEfectivo > LIMITE_EFECTIVO_PLD) {
                    const cashAlert = `
            <div class="alert alert-danger">
                <i class="fas fa-ban"></i>
                <strong>¡OPERACIÓN PROHIBIDA!</strong> El monto en efectivo (${montoEfectivo.toLocaleString('es-MX')} MXN) excede el límite legal de ${LIMITE_EFECTIVO_PLD.toLocaleString('es-MX')} MXN establecido en el Art. 32 de la LFPIORPI.
            </div>
        `;
                    alertsHTML += cashAlert;
                }

                alertContainer.html(alertsHTML);
            }

            // Función para mostrar/ocultar domicilio extranjero
            function toggleDomicilioExtranjero(tipo) {
                const checkbox = $('#' + tipo + '_tiene_domicilio_extranjero');
                const domicilioSection = $('#' + tipo + '_domicilio_extranjero');

                if (checkbox.is(':checked')) {
                    domicilioSection.show();
                } else {
                    domicilioSection.hide();
                    domicilioSection.find('input').val('').removeAttr('required');
                }
            }

            // Validación de código postal (6 dígitos) y otras validaciones
            $(document).ready(function () {
                $('#codigo_postal, #pf_codigo_postal, #pm_codigo_postal, #fid_codigo_postal').on('input', function () {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });

                $(document).on('input', '[id$="_cp"]', function () {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });

                $('a[data-target="#modalAgregarOperacion"]').off('click').on('click', function (e) {
                    e.preventDefault();
                    abrirModalOperacion();
                });

                $('#formAgregarOperacion').on('submit', function (e) {
                    const montoEfectivo = parseFloat($('#monto_efectivo').val()) || 0;

                    if (montoEfectivo > LIMITE_EFECTIVO_PLD) {
                        e.preventDefault();
                        alert('No se puede registrar la operación. El monto en efectivo excede el límite legal permitido.');
                        return false;
                    }

                    const moneda = $('#moneda').val();
                    const otraMoneda = $('#moneda_otra').val();
                    if (moneda === 'otra' && !otraMoneda.trim()) {
                        e.preventDefault();
                        alert('Por favor especifique la moneda en el campo correspondiente.');
                        $('#moneda_otra').focus();
                        return false;
                    }

                    const tipoCliente = $('#hidden_tipo_cliente').val();
                    let camposRequeridos = ['fecha_operacion', 'tipo_operacion', 'monto_operacion', 'tipo_propiedad', 'codigo_postal'];

                    if (tipoCliente === 'Persona Física') {
                        camposRequeridos = camposRequeridos.concat(['pf_nombre', 'pf_apellido_paterno', 'pf_rfc']);
                    } else if (tipoCliente === 'Persona Moral') {
                        camposRequeridos = camposRequeridos.concat(['pm_razon_social', 'pm_rfc']);
                    } else if (tipoCliente === 'Fideicomiso') {
                        camposRequeridos = camposRequeridos.concat(['fid_razon_social', 'fid_rfc']);
                    }

                    for (let campo of camposRequeridos) {
                        const elemento = $('#' + campo);
                        if (elemento.length && !elemento.val().trim()) {
                            e.preventDefault();
                            alert('Por favor complete todos los campos obligatorios marcados con *');
                            elemento.focus();
                            return false;
                        }
                    }

                    if (tipoCliente === 'Persona Moral') {
                        const beneficiariosPM = $('#pm_beneficiarios_container .beneficiario-card').length;
                        if (beneficiariosPM === 0) {
                            e.preventDefault();
                            alert('Debe agregar al menos un Beneficiario Controlador para Persona Moral.');
                            return false;
                        }
                    }

                    if (tipoCliente === 'Fideicomiso') {
                        const totalBeneficiarios = $('#fid_fideicomitente_container .beneficiario-card').length +
                            $('#fid_fiduciario_container .beneficiario-card').length +
                            $('#fid_fideicomisario_container .beneficiario-card').length +
                            $('#fid_control_efectivo_container .beneficiario-card').length;

                        if (totalBeneficiarios === 0) {
                            e.preventDefault();
                            alert('Debe agregar al menos un Beneficiario Controlador en cualquiera de los roles del Fideicomiso.');
                            return false;
                        }
                    }
                });
            });
        </script>



        <!-- Modal IDÉNTICO para editar operación vulnerable - LFPIORPI -->
        <div class="modal fade" id="modalEditarOperacion" tabindex="-1" aria-labelledby="modalEditarOperacionLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarOperacionLabel">
                            <i class="fas fa-edit"></i> Editar operación vulnerable
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <form id="formEditarOperacion" action="vulnerabilities.php" method="POST">
                            <div id="accordion-formulario-edit"></div>
                            <input name="operation[key_operation]" type="hidden" value="<?php echo $clave; ?>">
                            <input name="operation[tipo_cliente]" type="hidden" id="hidden_tipo_cliente_edit">
                            <input name="operation[id_operation]" type="hidden" id="edit_operation_id">
                            <!-- NUEVOS CAMPOS OCULTOS PARA IDs CORRECTOS -->
                            <input name="operation[empresa_id_general]" type="hidden" id="hidden_empresa_id_edit">
                            <input name="operation[id_cliente_existente_general]" type="hidden"
                                id="hidden_cliente_id_edit">

                            <!-- Sección 0: Información de la Empresa Seleccionada -->
                            <div class="card mb-3 collapsible-card">
                                <div class="card-header bg-info text-white" data-toggle="collapse"
                                    data-target="#collapse-info-empresa-edit" aria-expanded="true"
                                    aria-controls="collapse-info-empresa-edit">
                                    <h6 class="mb-0">
                                        <i class="fas fa-building"></i> Información de la Empresa
                                        <i class="fas fa-chevron-down collapse-icon"></i>
                                    </h6>
                                </div>
                                <div id="collapse-info-empresa-edit" class="collapse show"
                                    data-parent="#accordion-formulario-edit">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="select_empresa_general_edit">
                                                <i class="fas fa-building"></i> Empresa:
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control" id="select_empresa_general_edit"
                                                name="select_empresa_general" required>
                                                <option value="">-- Seleccionar empresa --</option>
                                            </select>
                                        </div>

                                        <div id="info-empresa-seleccionada-edit" style="display: none;">
                                            <div class="alert alert-info">
                                                <h6><i class="fas fa-info-circle"></i> Información de la empresa:</h6>
                                                <div id="detalle-empresa-edit"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección 1: Información del Cliente -->
                            <div class="card mb-3 collapsible-card">
                                <div class="card-header bg-primary text-white" data-toggle="collapse"
                                    data-target="#collapse-info-cliente-edit" aria-expanded="true"
                                    aria-controls="collapse-info-cliente-edit">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user"></i> Información del Cliente
                                        <i class="fas fa-chevron-down collapse-icon"></i>
                                    </h6>
                                </div>
                                <div id="collapse-info-cliente-edit" class="collapse show"
                                    data-parent="#accordion-formulario-edit">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="tipo_cliente_display_edit">
                                                <i class="fas fa-user-tag"></i> Tipo de Cliente:
                                            </label>
                                            <input type="text" class="form-control" id="tipo_cliente_display_edit"
                                                readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="select_cliente_general_edit">
                                                <i class="fas fa-users"></i> Cliente Existente:
                                            </label>
                                            <select class="form-control" id="select_cliente_general_edit"
                                                name="select_cliente_general">
                                                <option value="">-- Seleccionar cliente existente --</option>
                                            </select>
                                        </div>

                                        <div id="info-cliente-seleccionado-edit" style="display: none;">
                                            <div class="alert alert-success">
                                                <h6><i class="fas fa-info-circle"></i> Información del cliente:</h6>
                                                <div id="detalle-cliente-edit"></div>
                                            </div>
                                        </div>

                                        <!-- Secciones dinámicas para cada tipo de cliente -->
                                        <!-- Persona Física -->
                                        <div id="seccion-persona-fisica-edit" style="display: none;">
                                            <h6><i class="fas fa-user"></i> Datos de Persona Física</h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="pf_nombre_edit">Nombre(s):</label>
                                                        <input type="text" class="form-control" id="pf_nombre_edit"
                                                            name="pf_nombre">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="pf_apellido_paterno_edit">Apellido Paterno:</label>
                                                        <input type="text" class="form-control"
                                                            id="pf_apellido_paterno_edit" name="pf_apellido_paterno">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="pf_apellido_materno_edit">Apellido Materno:</label>
                                                        <input type="text" class="form-control"
                                                            id="pf_apellido_materno_edit" name="pf_apellido_materno">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="pf_rfc_edit">RFC:</label>
                                                        <input type="text" class="form-control" id="pf_rfc_edit"
                                                            name="pf_rfc" maxlength="13">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="pf_curp_edit">CURP:</label>
                                                        <input type="text" class="form-control" id="pf_curp_edit"
                                                            name="pf_curp" maxlength="18">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Persona Moral -->
                                        <div id="seccion-persona-moral-edit" style="display: none;">
                                            <h6><i class="fas fa-building"></i> Datos de Persona Moral</h6>
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="form-group">
                                                        <label for="pm_razon_social_edit">Razón Social:</label>
                                                        <input type="text" class="form-control"
                                                            id="pm_razon_social_edit" name="pm_razon_social">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="pm_rfc_edit">RFC:</label>
                                                        <input type="text" class="form-control" id="pm_rfc_edit"
                                                            name="pm_rfc" maxlength="12">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Beneficiarios Controladores para Persona Moral -->
                                            <div class="card mt-3">
                                                <div class="card-header bg-secondary text-white">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-users-cog"></i> Beneficiarios Controladores
                                                        <button type="button" class="btn btn-sm btn-light float-right"
                                                            onclick="agregarBeneficiarioControlador('pm_edit')">
                                                            <i class="fas fa-plus"></i> Agregar
                                                        </button>
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div id="beneficiarios-pm-edit">
                                                        <!-- Los beneficiarios se agregarán dinámicamente aquí -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Fideicomiso -->
                                        <div id="seccion-fideicomiso-edit" style="display: none;">
                                            <h6><i class="fas fa-handshake"></i> Datos del Fideicomiso</h6>
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="form-group">
                                                        <label for="fid_razon_social_edit">Razón Social:</label>
                                                        <input type="text" class="form-control"
                                                            id="fid_razon_social_edit" name="fid_razon_social">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="fid_rfc_edit">RFC:</label>
                                                        <input type="text" class="form-control" id="fid_rfc_edit"
                                                            name="fid_rfc" maxlength="12">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Secciones de beneficiarios para fideicomiso -->
                                            <!-- Fideicomitente -->
                                            <div class="card mt-3">
                                                <div class="card-header bg-info text-white">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-user-tie"></i> Fideicomitente
                                                        <button type="button" class="btn btn-sm btn-light float-right"
                                                            onclick="agregarBeneficiarioControlador('fid_fideicomitente_edit')">
                                                            <i class="fas fa-plus"></i> Agregar
                                                        </button>
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div id="beneficiarios-fid-fideicomitente-edit">
                                                        <!-- Los fideicomitentes se agregarán dinámicamente aquí -->
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Fiduciario -->
                                            <div class="card mt-3">
                                                <div class="card-header bg-warning text-dark">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-user-shield"></i> Fiduciario
                                                        <button type="button" class="btn btn-sm btn-dark float-right"
                                                            onclick="agregarBeneficiarioControlador('fid_fiduciario_edit')">
                                                            <i class="fas fa-plus"></i> Agregar
                                                        </button>
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div id="beneficiarios-fid-fiduciario-edit">
                                                        <!-- Los fiduciarios se agregarán dinámicamente aquí -->
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Fideicomisario -->
                                            <div class="card mt-3">
                                                <div class="card-header bg-success text-white">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-user-check"></i> Fideicomisario
                                                        <button type="button" class="btn btn-sm btn-light float-right"
                                                            onclick="agregarBeneficiarioControlador('fid_fideicomisario_edit')">
                                                            <i class="fas fa-plus"></i> Agregar
                                                        </button>
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div id="beneficiarios-fid-fideicomisario-edit">
                                                        <!-- Los fideicomisarios se agregarán dinámicamente aquí -->
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Control Efectivo -->
                                            <div class="card mt-3">
                                                <div class="card-header bg-danger text-white">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-crown"></i> Control Efectivo
                                                        <button type="button" class="btn btn-sm btn-light float-right"
                                                            onclick="agregarBeneficiarioControlador('fid_control_efectivo_edit')">
                                                            <i class="fas fa-plus"></i> Agregar
                                                        </button>
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div id="beneficiarios-fid-control-efectivo-edit">
                                                        <!-- Los de control efectivo se agregarán dinámicamente aquí -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección 2: Información de la Operación -->
                            <div class="card mb-3 collapsible-card">
                                <div class="card-header bg-warning text-dark" data-toggle="collapse"
                                    data-target="#collapse-operacion-edit" aria-expanded="true"
                                    aria-controls="collapse-operacion-edit">
                                    <h6 class="mb-0">
                                        <i class="fas fa-exchange-alt"></i> Información de la Operación
                                        <i class="fas fa-chevron-down collapse-icon"></i>
                                    </h6>
                                </div>
                                <div id="collapse-operacion-edit" class="collapse show"
                                    data-parent="#accordion-formulario-edit">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="tipo_operacion_edit">
                                                        <i class="fas fa-tags"></i> Tipo de Operación:
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-control" id="tipo_operacion_edit"
                                                        name="operation[tipo_operacion]" required>
                                                        <option value="">-- Seleccionar --</option>
                                                        <option value="Compraventa">Compraventa</option>
                                                        <option value="Donación">Donación</option>
                                                        <option value="Adjudicación">Adjudicación</option>
                                                        <option value="Dación en Pago">Dación en Pago</option>
                                                        <option value="Permuta">Permuta</option>
                                                        <option value="Constitución de Usufructo">Constitución de
                                                            Usufructo</option>
                                                        <option value="Transmisión de Usufructo">Transmisión de
                                                            Usufructo</option>
                                                        <option value="Constitución de Fideicomiso">Constitución de
                                                            Fideicomiso</option>
                                                        <option value="Aportación a Sociedad">Aportación a Sociedad
                                                        </option>
                                                        <option value="Fusión">Fusión</option>
                                                        <option value="Escisión">Escisión</option>
                                                        <option value="Liquidación">Liquidación</option>
                                                        <option value="Otro">Otro</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="fecha_operacion_edit">
                                                        <i class="fas fa-calendar"></i> Fecha de la Operación:
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="date" class="form-control" id="fecha_operacion_edit"
                                                        name="operation[fecha_operacion]" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="monto_operacion_edit">
                                                        <i class="fas fa-dollar-sign"></i> Monto de la Operación:
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="number" class="form-control" id="monto_operacion_edit"
                                                        name="operation[monto_operacion]" step="0.01" min="0" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="moneda_edit">
                                                        <i class="fas fa-coins"></i> Moneda:
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-control" id="moneda_edit"
                                                        name="operation[moneda]" required>
                                                        <option value="">-- Seleccionar --</option>
                                                        <option value="MXN">Peso Mexicano (MXN)</option>
                                                        <option value="USD">Dólar Estadounidense (USD)</option>
                                                        <option value="EUR">Euro (EUR)</option>
                                                        <option value="Otra">Otra</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group" id="div_moneda_otra_edit"
                                                    style="display: none;">
                                                    <label for="moneda_otra_edit">Especificar Moneda:</label>
                                                    <input type="text" class="form-control" id="moneda_otra_edit"
                                                        name="operation[moneda_otra]">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="forma_pago_edit">
                                                        <i class="fas fa-credit-card"></i> Forma de Pago:
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-control" id="forma_pago_edit"
                                                        name="operation[forma_pago]" required>
                                                        <option value="">-- Seleccionar --</option>
                                                        <option value="Efectivo">Efectivo</option>
                                                        <option value="Transferencia">Transferencia Electrónica</option>
                                                        <option value="Cheque">Cheque</option>
                                                        <option value="Mixto">Mixto (Efectivo + Otro)</option>
                                                        <option value="Crédito">Crédito/Financiamiento</option>
                                                        <option value="Otro">Otro</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group" id="div_monto_efectivo_edit"
                                                    style="display: none;">
                                                    <label for="monto_efectivo_edit">
                                                        <i class="fas fa-money-bill-wave"></i> Monto en Efectivo:
                                                    </label>
                                                    <input type="number" class="form-control" id="monto_efectivo_edit"
                                                        name="operation[monto_efectivo]" step="0.01" min="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección 3: Información del Inmueble -->
                            <div class="card mb-3 collapsible-card">
                                <div class="card-header bg-success text-white" data-toggle="collapse"
                                    data-target="#collapse-inmueble-edit" aria-expanded="true"
                                    aria-controls="collapse-inmueble-edit">
                                    <h6 class="mb-0">
                                        <i class="fas fa-home"></i> Información del Inmueble
                                        <i class="fas fa-chevron-down collapse-icon"></i>
                                    </h6>
                                </div>
                                <div id="collapse-inmueble-edit" class="collapse show"
                                    data-parent="#accordion-formulario-edit">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="tipo_propiedad_edit">
                                                        <i class="fas fa-building"></i> Tipo de Propiedad:
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-control" id="tipo_propiedad_edit"
                                                        name="operation[tipo_propiedad]" required>
                                                        <option value="">-- Seleccionar --</option>
                                                        <option value="Casa">Casa</option>
                                                        <option value="Departamento">Departamento</option>
                                                        <option value="Terreno">Terreno</option>
                                                        <option value="Local Comercial">Local Comercial</option>
                                                        <option value="Oficina">Oficina</option>
                                                        <option value="Bodega">Bodega</option>
                                                        <option value="Nave Industrial">Nave Industrial</option>
                                                        <option value="Rancho">Rancho</option>
                                                        <option value="Otro">Otro</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="uso_inmueble_edit">
                                                        <i class="fas fa-home"></i> Uso del Inmueble:
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-control" id="uso_inmueble_edit"
                                                        name="operation[uso_inmueble]" required>
                                                        <option value="">-- Seleccionar --</option>
                                                        <option value="Habitacional">Habitacional</option>
                                                        <option value="Comercial">Comercial</option>
                                                        <option value="Industrial">Industrial</option>
                                                        <option value="Mixto">Mixto</option>
                                                        <option value="Recreativo">Recreativo</option>
                                                        <option value="Agrícola">Agrícola</option>
                                                        <option value="Otro">Otro</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="direccion_inmueble_edit">
                                                <i class="fas fa-map-marker-alt"></i> Dirección del Inmueble:
                                                <span class="text-danger">*</span>
                                            </label>
                                            <textarea class="form-control" id="direccion_inmueble_edit"
                                                name="operation[direccion_inmueble]" rows="3" required
                                                placeholder="Calle, número, colonia, ciudad, estado..."></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="codigo_postal_edit">
                                                        <i class="fas fa-mail-bulk"></i> Código Postal:
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" class="form-control" id="codigo_postal_edit"
                                                        name="operation[codigo_postal]" maxlength="5" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="folio_escritura_edit">
                                                        <i class="fas fa-file-contract"></i> Folio de Escritura:
                                                    </label>
                                                    <input type="text" class="form-control" id="folio_escritura_edit"
                                                        name="operation[folio_escritura]">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="propietario_anterior_edit">
                                                        <i class="fas fa-user-clock"></i> Propietario Anterior:
                                                    </label>
                                                    <input type="text" class="form-control"
                                                        id="propietario_anterior_edit"
                                                        name="operation[propietario_anterior]">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección 4: Información Adicional -->
                            <div class="card mb-3 collapsible-card">
                                <div class="card-header bg-secondary text-white" data-toggle="collapse"
                                    data-target="#collapse-adicional-edit" aria-expanded="true"
                                    aria-controls="collapse-adicional-edit">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle"></i> Información Adicional
                                        <i class="fas fa-chevron-down collapse-icon"></i>
                                    </h6>
                                </div>
                                <div id="collapse-adicional-edit" class="collapse show"
                                    data-parent="#accordion-formulario-edit">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="requiere_aviso_sat_edit"
                                                            name="operation[requiere_aviso_sat]" value="1">
                                                        <label class="form-check-label" for="requiere_aviso_sat_edit">
                                                            <i class="fas fa-bell"></i> Requiere Aviso al SAT
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="umbral_superado_edit" name="operation[umbral_superado]"
                                                            value="1">
                                                        <label class="form-check-label" for="umbral_superado_edit">
                                                            <i class="fas fa-exclamation-triangle"></i> Umbral Superado
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="observaciones_edit">
                                                <i class="fas fa-sticky-note"></i> Observaciones:
                                            </label>
                                            <textarea class="form-control" id="observaciones_edit"
                                                name="operation[observaciones]" rows="4"
                                                placeholder="Observaciones adicionales sobre la operación..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones del formulario -->
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary" name="action" value="update">
                                    <i class="fas fa-save"></i> Actualizar Operación
                                </button>
                            </div>
                        </form>
                    </div>
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
        // DATOS REALES DE LA BASE DE DATOS
        var operacionesData = <?php echo json_encode($operacionesData, JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        // Variable para saber qué pestaña está activa
        var tabActiva = "personas-fisicas";
        var userType = <?php echo $_SESSION['user']['id_type_user']; ?>;

        // =====================================================================
        // SECCIÓN 1: FUNCIONES EXISTENTES (SIN CAMBIOS)
        // =====================================================================

        $(document).ready(function () {
            // Cargar la primera tabla por defecto
            cargarTabla('personas-fisicas');

            // Funcionalidad del buscador
            $('#searchInputOperations').on('keyup', function () {
                var searchValue = this.value.toLowerCase();
                $('.tabla-container.active tbody tr').each(function () {
                    var rowText = $(this).text().toLowerCase();
                    if (rowText.indexOf(searchValue) === -1) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });
            });

            // Funcionalidad de los filtros
            $('.filtrosDDL').on('change', function () {
                aplicarFiltros();
            });

            // NUEVA FUNCIONALIDAD: Recargar datos después de insertar operación
            if (window.location.search.includes('success=1')) {
                setTimeout(function () {
                    location.reload();
                }, 2000);
            }
        });

        // Inicializar los selectores al cargar la página
        document.addEventListener('DOMContentLoaded', function () {
            generarAnios();
            establecerMesActual();
        });

        // Función para generar el select de años
        function generarAnios() {
            var yearSelect = document.getElementById("yearSelect");
            var currentYear = new Date().getFullYear();
            var endYear = currentYear + 5;

            for (var year = 2020; year <= endYear; year++) {
                var option = document.createElement("option");
                option.value = year;
                option.text = year;
                yearSelect.appendChild(option);

                if (year === currentYear) {
                    option.selected = true;
                }
            }
        }

        // Función para establecer el mes actual
        function establecerMesActual() {
            var monthSelect = document.getElementById('month_select');
            var currentMonth = new Date().getMonth() + 1;
            var formattedMonth = currentMonth < 10 ? '0' + currentMonth : currentMonth.toString();
            monthSelect.value = formattedMonth;
        }

        // Función para aplicar filtros con AJAX
        function aplicarFiltros() {
            var yearSelect = $("#yearSelect").val();
            var monthSelect = $("#month_select").val();
            var statusSelect = $("#status_select").val();

            // Hacer petición AJAX para obtener datos filtrados
            $.ajax({
                url: 'ajax/filter_operations.php',
                method: 'POST',
                data: {
                    year: yearSelect,
                    month: monthSelect,
                    status: statusSelect,
                    tipo_cliente: tabActiva
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // Actualizar datos y recargar tabla
                        operacionesData[tabActiva] = response.data;
                        cargarTablaConDatos(tabActiva, response.data);
                    }
                },
                error: function () {
                    console.error('Error al filtrar operaciones');
                    // Fallback: usar filtros locales si AJAX falla
                    aplicarFiltrosLocales();
                }
            });
        }

        // Función de respaldo para filtros locales (si AJAX falla)
        function aplicarFiltrosLocales() {
            var yearSelect = $("#yearSelect").val();
            var monthSelect = $("#month_select").val();
            var statusSelect = $("#status_select").val();

            // Crear rango de fechas basado en año y mes
            var fecha1, fecha2;
            if (monthSelect === 'all') {
                fecha1 = yearSelect + "-01-01";
                fecha2 = yearSelect + "-12-31";
            } else {
                fecha1 = yearSelect + "-" + monthSelect + "-01";
                fecha2 = yearSelect + "-" + monthSelect + "-31";
            }

            // Filtrar datos para la categoría actual
            var datosFiltrados = operacionesData[tabActiva].filter(function (item) {
                var fechaOperacion = item.fecha_operacion;
                var cumpleFecha = fechaOperacion >= fecha1 && fechaOperacion <= fecha2;
                var cumpleStatus = statusSelect === 'all' || item.semaforo === statusSelect;

                return cumpleFecha && cumpleStatus;
            });

            // Recargar la tabla con datos filtrados
            cargarTablaConDatos(tabActiva, datosFiltrados);
        }

        // Función para cargar tabla con datos específicos
        function cargarTablaConDatos(tipoCliente, data) {
            var tbody = '';
            var tableId = '';

            switch (tipoCliente) {
                case 'personas-fisicas': tableId = '#dataPersonasFisicas'; break;
                case 'personas-morales': tableId = '#dataPersonasMorales'; break;
                case 'fideicomisos': tableId = '#dataFideicomisos'; break;
            }

            var esAdmin = (userType === 1 || userType === 3);

            if (data && data.length > 0) {
                for (var i = 0; i < data.length; i++) {
                    var item = data[i] || {};
                    var semaforoClass = 'semaforo-' + (item.semaforo || 'verde');
                    var fechaFormateada = formatDate(item.fecha_operacion || new Date());
                    var empresaClass = item.empresa_missing_info ? 'missing-info clickeable-field' : 'clickeable-field';
                    var clienteClass = item.cliente_missing_info ? 'missing-info clickeable-field' : 'clickeable-field';

                    // Valores con fallback
                    var tipoPropiedad = item.tipo_propiedad || '-';
                    var usoInmueble = item.uso_inmueble || '-';
                    var direccion = item.direccion_inmueble || '-';
                    var cp = item.codigo_postal || '-';
                    var folio = item.folio_escritura || '-';
                    var propietario = item.propietario_anterior || '-';

                    tbody += "<tr>";
                    tbody += "<td style='text-align:center;'>" + (i + 1) + "</td>";
                    tbody += "<td style='text-align:left;'><span class='" + empresaClass + "' onclick='editarCampo(" + item.id + ', "empresa")' + "'>" + (item.empresa || '-') + "</span></td>";
                    tbody += "<td style='text-align:left;'><span class='" + clienteClass + "' onclick='editarCampo(" + item.id + ', "cliente")' + "'>" + (item.cliente || '-') + "</span></td>";
                    tbody += "<td style='text-align:center;'>" + fechaFormateada + "</td>";
                    tbody += "<td style='text-align:left;'>" + tipoPropiedad + "</td>";
                    tbody += "<td style='text-align:left;'>" + usoInmueble + "</td>";
                    tbody += "<td style='text-align:left;'>" + direccion + "</td>";
                    tbody += "<td style='text-align:center;'>" + cp + "</td>";
                    tbody += "<td style='text-align:center;'>" + folio + "</td>";
                    tbody += "<td style='text-align:left;'>" + propietario + "</td>";
                    tbody += "<td style='text-align:center;'><i class='fas fa-circle " + semaforoClass + "'></i></td>";

                    if (esAdmin) {
                        tbody += "<td style='text-align:center;'>"
                            + "<div class='dropdown position-static'>"
                            + "<button class='btn btn-primary dropdown-toggle btn-sm' type='button' "
                            + "id='dropdownMenuButton_" + item.id + "' data-toggle='dropdown' data-boundary='viewport' "
                            + "aria-haspopup='true' aria-expanded='false'>Acciones</button>"
                            + "<div class='dropdown-menu dropdown-menu-right' aria-labelledby='dropdownMenuButton_" + item.id + "'>"
                            + "<a class='dropdown-item' href='#' onclick='verDetalle(" + item.id + ")'><i class='fas fa-eye'></i> Ver detalle</a>"
                            + "<a class='dropdown-item' href='#' data-operation-id='" + item.id + "'><i class='fas fa-pen'></i> Editar operación</a>"
                            + "<hr>"
                            + "<form action='vulnerabilities.php' method='POST' class='m-0'>"
                            + "<input name='delOperation[idOperation]' type='hidden' value='" + item.id + "'>"
                            + "<button class='dropdown-item' type='submit' name='action' value='deleteOperation' "
                            + "onclick='return confirm(\"¿Estás seguro de eliminar la operación?\");'>"
                            + "<i class='fas fa-trash'></i> Mover a la papelera"
                            + "</button>"
                            + "</form>"
                            + "</div>"
                            + "</div>"
                            + "</td>";
                    }

                    tbody += "</tr>";
                }
            } else {
                // #, Empresa, Cliente, Fecha, Tipo, Uso, Dirección, CP, Folio, Propietario, Semáforo (+ Acciones si admin)
                var columnas = 11 + (esAdmin ? 1 : 0);
                tbody = "<tr><td colspan='" + columnas + "' class='text-center'>No hay operaciones que coincidan con los filtros seleccionados</td></tr>";
            }

            $(tableId).html(tbody);

            // Total
            $('#totalOperaciones').text(data ? data.length : 0);
        }

        // Función para cambiar entre pestañas
        function cambiarTab(tipoCliente) {
            // Actualizar botones
            $('.google-tab').removeClass('active');
            $('#btn-' + tipoCliente).addClass('active');

            // Ocultar todas las tablas
            $('.tabla-container').removeClass('active');

            // Mostrar la tabla seleccionada
            $('#tabla-' + tipoCliente).addClass('active');

            // Limpiar búsqueda
            $('#searchInputOperations').val('');

            tabActiva = tipoCliente;

            // Aplicar filtros a la nueva pestaña
            aplicarFiltros();
        }

        // Función para cargar datos en la tabla (versión original)
        function cargarTabla(tipoCliente) {
            var data = operacionesData[tipoCliente];
            cargarTablaConDatos(tipoCliente, data);
        }

        // Función para formatear fecha
        function formatDate(dateString) {
            var date = new Date(dateString);
            var day = String(date.getDate()).padStart(2, '0');
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var year = date.getFullYear();
            return day + '/' + month + '/' + year;
        }

        // Función para ver detalle (placeholder)
        function verDetalle(id) {
            alert('Ver detalle de operación ID: ' + id + ' (función a implementar)');
        }

        // Función para editar campo clickeable
        // Función para editar campo clickeable (VERSIÓN MEJORADA)
        function editarCampo(id, campo) {
            console.log('editarCampo called with:', id, campo);

            // Mostrar loading
            var loadingMsg = campo === 'empresa' ? 'Buscando carpeta de la empresa...' : 'Buscando carpeta del cliente...';

            // Crear un elemento de loading temporal
            var loadingElement = $('<div id="loading-redirect" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 10px; z-index: 9999;">' +
                '<i class="fas fa-spinner fa-spin"></i> ' + loadingMsg +
                '</div>');
            $('body').append(loadingElement);

            // Realizar petición AJAX para obtener la información de la carpeta
            $.ajax({
                url: 'get_folder_info.php',
                type: 'POST',
                data: {
                    action: 'getFolderInfo',
                    operation_id: id,
                    campo: campo
                },
                dataType: 'json',
                success: function (response) {
                    $('#loading-redirect').remove();

                    if (response.success && response.folder_info) {
                        var folderInfo = response.folder_info;
                        console.log('Folder info received:', folderInfo);

                        if (campo === 'empresa') {
                            // Para empresa, redirigir a la carpeta principal de la empresa
                            if (folderInfo.empresa_folder_id && folderInfo.empresa_key) {
                                var url = '../folders/subfolder.php?id=' + folderInfo.empresa_folder_id + '&key=' + folderInfo.empresa_key;
                                console.log('Redirecting to empresa folder:', url);
                                window.location.href = url;
                            } else {
                                alert('No se encontró la carpeta de la empresa.');
                            }
                        } else if (campo === 'cliente') {
                            // Para cliente, redirigir a la subcarpeta del cliente
                            if (folderInfo.cliente_folder_id && folderInfo.cliente_key) {
                                var url = '../folders/subfolder.php?id=' + folderInfo.cliente_folder_id + '&key=' + folderInfo.cliente_key;
                                console.log('Redirecting to cliente folder:', url);
                                window.location.href = url;
                            } else {
                                alert('No se encontró la carpeta del cliente.');
                            }
                        }
                    } else {
                        alert('Error: ' + (response.error || 'No se pudo obtener la información de la carpeta'));
                    }
                },
                error: function (xhr, status, error) {
                    $('#loading-redirect').remove();
                    console.error('Error AJAX:', error);
                    alert('Error al buscar la información de la carpeta. Intente nuevamente.');
                }
            });
        }
        // =====================================================================
        // SECCIÓN 2: NUEVAS FUNCIONES PARA EMPRESAS Y CLIENTES CENTRALIZADOS
        // =====================================================================

        // FUNCIÓN PARA CARGAR EMPRESAS CLIENTES
        function cargarEmpresasClientes() {
            const $select = $('#select_empresa_general');

            // Limpiar select
            $select.html('<option value="">Cargando empresas...</option>');

            // Hacer petición AJAX para obtener empresas
            $.ajax({
                url: 'get_clients_ajax.php',
                type: 'GET',
                data: {
                    action: 'get_client_companies'
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // Limpiar y agregar opción por defecto
                        $select.html('<option value="">-- Seleccionar empresa --</option>');

                        // Agregar opciones de empresas
                        response.companies.forEach(function (company) {
                            $select.append(
                                `<option value="${company.id_company}">
                                    ${company.name_company} - ${company.rfc_company || 'Sin RFC'}
                                </option>`
                            );
                        });

                        console.log('Empresas clientes cargadas:', response.companies.length);
                    } else {
                        $select.html('<option value="">Error al cargar empresas</option>');
                        console.error('Error:', response.error);
                    }
                },
                error: function (xhr, status, error) {
                    $select.html('<option value="">Error al cargar empresas</option>');
                    console.error('Error AJAX:', error);
                }
            });
        }

        // FUNCIÓN PARA CARGAR CLIENTES POR EMPRESA
        function cargarClientesPorEmpresa(empresaId) {
            const $select = $('#select_cliente_general');

            // Limpiar select
            $select.html('<option value="">Cargando clientes...</option>');

            // Si no hay empresa seleccionada, cargar TODOS los clientes
            if (!empresaId) {
                cargarTodosLosClientes();
                return;
            }

            // Hacer petición AJAX para clientes de la empresa específica
            $.ajax({
                url: 'get_clients_ajax.php',
                type: 'GET',
                data: {
                    action: 'get_clients_by_company',
                    empresa_id: empresaId
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // Limpiar y agregar opción por defecto
                        $select.html('<option value="">-- Seleccionar cliente existente --</option>');

                        // Agregar opciones de clientes
                        response.clients.forEach(function (client) {
                            // Agregar indicador del tipo de persona
                            let tipoIndicador = '';
                            switch (client.data.tipo_persona) {
                                case 'fisica':
                                    tipoIndicador = ' [Persona Física]';
                                    break;
                                case 'moral':
                                    tipoIndicador = ' [Persona Moral]';
                                    break;
                                case 'fideicomiso':
                                    tipoIndicador = ' [Fideicomiso]';
                                    break;
                            }

                            $select.append(
                                `<option value="${client.id}" data-client='${JSON.stringify(client.data)}'>
                                    ${client.text}${tipoIndicador}
                                </option>`
                            );
                        });

                        console.log(`Clientes cargados para empresa ${empresaId}:`, response.clients.length);
                    } else {
                        $select.html('<option value="">Error al cargar clientes</option>');
                        console.error('Error:', response.error);
                    }
                },
                error: function (xhr, status, error) {
                    $select.html('<option value="">Error al cargar clientes</option>');
                    console.error('Error AJAX:', error);
                }
            });
        }

        // FUNCIÓN PARA CARGAR TODOS LOS CLIENTES SIN FILTRO DE EMPRESA
        function cargarTodosLosClientes() {
            const $select = $('#select_cliente_general');

            // Limpiar select
            $select.html('<option value="">Cargando todos los clientes...</option>');

            // Hacer petición AJAX para obtener TODOS los clientes
            $.ajax({
                url: 'get_clients_ajax.php',
                type: 'GET',
                data: {
                    action: 'get_all_clients'
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // Limpiar y agregar opción por defecto
                        $select.html('<option value="">-- Seleccionar cliente existente --</option>');

                        // Agregar opciones de clientes con indicadores de tipo
                        response.clients.forEach(function (client) {
                            $select.append(
                                `<option value="${client.id}" data-client='${JSON.stringify(client.data)}'>
                                    ${client.text}
                                </option>`
                            );
                        });

                        console.log('Todos los clientes cargados:', response.clients.length);
                    } else {
                        $select.html('<option value="">Error al cargar clientes</option>');
                        console.error('Error:', response.error);
                    }
                },
                error: function (xhr, status, error) {
                    $select.html('<option value="">Error al cargar clientes</option>');
                    console.error('Error AJAX:', error);
                }
            });
        }

        // FUNCIÓN PARA LLENAR FORMULARIO CON DATOS DEL CLIENTE SELECCIONADO
        function llenarFormularioConClienteSeleccionado(clientData) {
            console.log('Llenando formulario con cliente:', clientData);

            // Determinar tipo de persona y llenar formulario correspondiente
            switch (clientData.tipo_persona) {
                case 'fisica':
                    llenarFormularioPersonaFisica(clientData);
                    break;
                case 'moral':
                    llenarFormularioPersonaMoral(clientData);
                    break;
                case 'fideicomiso':
                    llenarFormularioFideicomiso(clientData);
                    break;
            }
        }

        // FUNCIÓN PARA LLENAR FORMULARIO PERSONA FÍSICA
        function llenarFormularioPersonaFisica(data) {
            $('#pf_nombre').val(data.pf_nombre || '');
            $('#pf_apellido_paterno').val(data.pf_apellido_paterno || '');
            $('#pf_apellido_materno').val(data.pf_apellido_materno || '');
            $('#pf_rfc').val(data.rfc_folder || '');
            $('#pf_curp').val(data.curp_folder || '');
            $('#pf_fecha_nacimiento').val(data.pf_fecha_nacimiento || '');
            $('#pf_estado').val(data.pf_estado || '');
            $('#pf_ciudad').val(data.pf_ciudad || '');
            $('#pf_colonia').val(data.pf_colonia || '');
            $('#pf_calle').val(data.pf_calle || '');
            $('#pf_num_exterior').val(data.pf_num_exterior || '');
            $('#pf_num_interior').val(data.pf_num_interior || '');
            $('#pf_codigo_postal').val(data.pf_codigo_postal || '');
            $('#pf_telefono').val(data.pf_telefono || '');
            $('#pf_correo').val(data.pf_email || '');
        }

        // FUNCIÓN PARA LLENAR FORMULARIO PERSONA MORAL
        function llenarFormularioPersonaMoral(data) {
            $('#pm_razon_social').val(data.pm_razon_social || '');
            $('#pm_rfc').val(data.rfc_folder || '');
            $('#pm_fecha_constitucion').val(data.pm_fecha_constitucion || '');
            $('#pm_apoderado_nombre').val(data.pm_apoderado_nombre || '');
            $('#pm_apoderado_paterno').val(data.pm_apoderado_paterno || '');
            $('#pm_apoderado_materno').val(data.pm_apoderado_materno || '');
            $('#pm_apoderado_rfc').val(data.pm_apoderado_rfc || '');
            $('#pm_apoderado_curp').val(data.pm_apoderado_curp || '');
            $('#pm_estado').val(data.pm_estado || '');
            $('#pm_ciudad').val(data.pm_ciudad || '');
            $('#pm_colonia').val(data.pm_colonia || '');
            $('#pm_calle').val(data.pm_calle || '');
            $('#pm_num_exterior').val(data.pm_num_exterior || '');
            $('#pm_num_interior').val(data.pm_num_interior || '');
            $('#pm_codigo_postal').val(data.pm_codigo_postal || '');
            $('#pm_telefono').val(data.pm_telefono || '');
            $('#pm_correo').val(data.pm_email || '');
        }

        // FUNCIÓN PARA LLENAR FORMULARIO FIDEICOMISO
        function llenarFormularioFideicomiso(data) {
            $('#fid_razon_social').val(data.fid_razon_social || '');
            $('#fid_rfc').val(data.rfc_folder || '');
            $('#fid_numero_referencia').val(data.fid_numero_referencia || '');
            $('#fid_estado').val(data.fid_estado || '');
            $('#fid_ciudad').val(data.fid_ciudad || '');
            $('#fid_colonia').val(data.fid_colonia || '');
            $('#fid_calle').val(data.fid_calle || '');
            $('#fid_num_exterior').val(data.fid_num_exterior || '');
            $('#fid_num_interior').val(data.fid_num_interior || '');
            $('#fid_codigo_postal').val(data.fid_codigo_postal || '');
            $('#fid_telefono').val(data.fid_telefono || '');
            $('#fid_correo').val(data.fid_email || '');
        }

        // FUNCIÓN PARA LIMPIAR TODOS LOS FORMULARIOS
        function limpiarTodosLosFormularios() {
            // Persona Física
            $('#pf_nombre, #pf_apellido_paterno, #pf_apellido_materno, #pf_rfc, #pf_curp, #pf_fecha_nacimiento, #pf_estado, #pf_ciudad, #pf_colonia, #pf_calle, #pf_num_exterior, #pf_num_interior, #pf_codigo_postal, #pf_telefono, #pf_correo').val('');

            // Persona Moral
            $('#pm_razon_social, #pm_rfc, #pm_fecha_constitucion, #pm_apoderado_nombre, #pm_apoderado_paterno, #pm_apoderado_materno, #pm_apoderado_rfc, #pm_apoderado_curp, #pm_estado, #pm_ciudad, #pm_colonia, #pm_calle, #pm_num_exterior, #pm_num_interior, #pm_codigo_postal, #pm_telefono, #pm_correo').val('');

            // Fideicomiso
            $('#fid_razon_social, #fid_rfc, #fid_numero_referencia, #fid_estado, #fid_ciudad, #fid_colonia, #fid_calle, #fid_num_exterior, #fid_num_interior, #fid_codigo_postal, #fid_telefono, #fid_correo').val('');
        }

        // =====================================================================
        // SECCIÓN 3: FUNCIONES ACTUALIZADAS
        // =====================================================================

        function configurarModalSegunTipo(tipoCliente, manteneDatos = false) {
            console.log('Configurando modal para:', tipoCliente, 'mantener datos:', manteneDatos);

            // Ocultar todas las secciones
            $('#seccion-persona-fisica').hide();
            $('#seccion-persona-moral').hide();
            $('#seccion-fideicomiso').hide();

            // Solo limpiar selects si NO debemos mantener datos
            if (!manteneDatos) {
                // Limpiar selects centralizados
                $('#select_empresa_general').val('').trigger('change');
                $('#select_cliente_general').html('<option value="">-- Seleccionar cliente existente --</option>');

                // Limpiar el campo tipo_cliente_display
                $('#tipo_cliente_display').val('');

                // Cargar empresas y todos los clientes por defecto
                cargarEmpresasClientes();
                cargarTodosLosClientes();
            }

            switch (tipoCliente) {
                case 'personas-fisicas':
                    $('#hidden_tipo_cliente').val('persona_fisica');
                    $('#seccion-persona-fisica').show();
                    $('#pf_nombre, #pf_apellido_paterno, #pf_rfc').attr('required', true);
                    break;

                case 'personas-morales':
                    $('#hidden_tipo_cliente').val('persona_moral');
                    $('#seccion-persona-moral').show();
                    $('#pm_razon_social, #pm_rfc').attr('required', true);
                    break;

                case 'fideicomisos':
                    $('#hidden_tipo_cliente').val('fideicomiso');
                    $('#seccion-fideicomiso').show();
                    $('#fid_razon_social, #fid_rfc').attr('required', true);
                    break;

                default:
                    $('#hidden_tipo_cliente').val('persona_fisica');
                    $('#seccion-persona-fisica').show();
                    $('#pf_nombre, #pf_apellido_paterno, #pf_rfc').attr('required', true);
                    break;
            }
        }
        // PASO 2: NUEVA FUNCIÓN PARA DETECTAR Y CAMBIAR AUTOMÁTICAMENTE EL MODAL
        function cambiarModalSegunTipoClienteMejorado(tipoPersona, clientData) {
            let tipoClienteActual = $('#hidden_tipo_cliente').val();
            let tipoRequerido = '';
            let nombreTipo = '';

            // Determinar qué tipo de modal necesitamos
            switch (tipoPersona) {
                case 'fisica':
                    tipoRequerido = 'persona_fisica';
                    nombreTipo = 'Persona Física';
                    break;
                case 'moral':
                    tipoRequerido = 'persona_moral';
                    nombreTipo = 'Persona Moral';
                    break;
                case 'fideicomiso':
                    tipoRequerido = 'fideicomiso';
                    nombreTipo = 'Fideicomiso';
                    break;
            }

            // Si el modal actual no coincide con el tipo de cliente seleccionado
            if (tipoClienteActual !== tipoRequerido) {
                console.log(`Cambiando modal de ${tipoClienteActual} a ${tipoRequerido} (manteniendo empresa)`);

                // Mostrar mensaje al usuario (opcional)
                mostrarNotificacionCambioModal(nombreTipo);

                // Cambiar al modal correcto manteniendo las selecciones
                cambiarModalAlTipoCorrecto(tipoPersona, clientData);
            } else {
                // Si ya estamos en el modal correcto, solo llenar los datos
                llenarFormularioConClienteSeleccionado(clientData);
                $('#tipo_cliente_display').val(nombreTipo);
            }
        }

        // PASO 3: FUNCIÓN MEJORADA PARA CAMBIAR AL MODAL CORRECTO
        function cambiarModalAlTipoCorrecto(tipoPersona, clientData) {
            let tipoCliente = '';
            let nombreTipo = '';

            // NUEVO: Guardar la empresa actualmente seleccionada ANTES de cambiar modal
            const empresaSeleccionadaActual = $('#select_empresa_general').val();

            switch (tipoPersona) {
                case 'fisica':
                    tipoCliente = 'personas-fisicas';
                    nombreTipo = 'Persona Física';
                    break;
                case 'moral':
                    tipoCliente = 'personas-morales';
                    nombreTipo = 'Persona Moral';
                    break;
                case 'fideicomiso':
                    tipoCliente = 'fideicomisos';
                    nombreTipo = 'Fideicomiso';
                    break;
            }

            // MEJORADO: Reconfigurar el modal SIN limpiar los selects
            configurarModalSegunTipo(tipoCliente, true); // true = mantener datos

            // Establecer el tipo de cliente en el campo display
            $('#tipo_cliente_display').val(nombreTipo);

            // CORREGIDO: Restaurar empresa seleccionada inmediatamente
            if (empresaSeleccionadaActual) {
                console.log('Manteniendo empresa seleccionada:', empresaSeleccionadaActual);
                $('#select_empresa_general').val(empresaSeleccionadaActual);

                // Cargar clientes de esa empresa si el select está vacío
                if ($('#select_cliente_general option').length <= 1) {
                    cargarClientesPorEmpresa(empresaSeleccionadaActual);
                }

                // Después de un momento, seleccionar el cliente y llenar formulario
                setTimeout(function () {
                    // Reseleccionar el cliente en el select
                    $('#select_cliente_general').val(clientData.id_folder);

                    // Llenar el formulario con los datos del cliente
                    llenarFormularioConClienteSeleccionado(clientData);
                }, 300);

            } else {
                // Si no había empresa seleccionada, cargar todos los clientes si es necesario
                if ($('#select_cliente_general option').length <= 1) {
                    cargarTodosLosClientes();
                }

                setTimeout(function () {
                    // Reseleccionar el cliente en el select
                    $('#select_cliente_general').val(clientData.id_folder);

                    // Llenar el formulario con los datos del cliente
                    llenarFormularioConClienteSeleccionado(clientData);
                }, 300);
            }
        }
        // PASO 4: FUNCIÓN PARA MOSTRAR NOTIFICACIÓN (OPCIONAL)
        function mostrarNotificacionCambioModal(tipoCliente) {
            // Crear notificación temporal
            const notificacion = $(`
        <div class="alert alert-info alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; max-width: 400px;" 
             role="alert">
            <i class="fas fa-sync-alt"></i>
            <strong>Modal cambiado automáticamente</strong><br>
            Se ha cambiado al formulario de <strong>${tipoCliente}</strong> según el cliente seleccionado.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `);

            // Agregar al body
            $('body').append(notificacion);

            // Remover automáticamente después de 4 segundos
            setTimeout(function () {
                notificacion.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 4000);
        }


        function limpiarSoloFormularios() {
            // Persona Física
            $('#pf_nombre, #pf_apellido_paterno, #pf_apellido_materno, #pf_rfc, #pf_curp, #pf_fecha_nacimiento, #pf_estado, #pf_ciudad, #pf_colonia, #pf_calle, #pf_num_exterior, #pf_num_interior, #pf_codigo_postal, #pf_telefono, #pf_correo').val('');

            // Persona Moral
            $('#pm_razon_social, #pm_rfc, #pm_fecha_constitucion, #pm_apoderado_nombre, #pm_apoderado_paterno, #pm_apoderado_materno, #pm_apoderado_rfc, #pm_apoderado_curp, #pm_estado, #pm_ciudad, #pm_colonia, #pm_calle, #pm_num_exterior, #pm_num_interior, #pm_codigo_postal, #pm_telefono, #pm_correo').val('');

            // Fideicomiso
            $('#fid_razon_social, #fid_rfc, #fid_numero_referencia, #fid_estado, #fid_ciudad, #fid_colonia, #fid_calle, #fid_num_exterior, #fid_num_interior, #fid_codigo_postal, #fid_telefono, #fid_correo').val('');
        }


        // PASO 6: ACTUALIZAR LA FUNCIÓN limpiarFormulario
        function limpiarFormulario() {
            // Guardar tipo de cliente antes de limpiar
            var tipoClienteDisplay = $('#tipo_cliente_display').val();
            var hiddenTipoCliente = $('#hidden_tipo_cliente').val();

            // Limpiar formulario
            $('#formAgregarOperacion')[0].reset();

            // NO restaurar tipo de cliente - dejarlo en blanco
            $('#tipo_cliente_display').val('');
            // Mantener el tipo oculto para compatibilidad
            $('#hidden_tipo_cliente').val(hiddenTipoCliente);

            // Limpiar selects centralizados
            $('#select_empresa_general').val('').trigger('change');
            $('#select_cliente_general').html('<option value="">-- Seleccionar cliente existente --</option>');

            // Recargar todos los clientes
            cargarTodosLosClientes();

            // Limpiar otras secciones...
            $('#pld-alerts-container').html('');
            $('#cash-amount-section').hide();
            $('#moneda_otra').hide();

            $('#pf_domicilio_extranjero, #pm_domicilio_extranjero, #fid_domicilio_extranjero').hide();
            $('#pf_tiene_domicilio_extranjero, #pm_tiene_domicilio_extranjero, #fid_tiene_domicilio_extranjero').prop('checked', false);

            $('#pm_beneficiarios_container').empty();
            $('#fid_fideicomitente_container, #fid_fiduciario_container, #fid_fideicomisario_container, #fid_control_efectivo_container').empty();

            // Limpiar contadores si existen
            if (typeof contadorBeneficiarios !== 'undefined') {
                contadorBeneficiarios = {
                    pm: 0,
                    fid_fideicomitente: 0,
                    fid_fiduciario: 0,
                    fid_fideicomisario: 0,
                    fid_control_efectivo: 0
                };
            }

            // Remover atributos required
            $('#pf_nombre, #pf_apellido_paterno, #pf_rfc').removeAttr('required');
            $('#pm_razon_social, #pm_rfc').removeAttr('required');
            $('#fid_razon_social, #fid_rfc').removeAttr('required');
        }

        // =====================================================================
        // SECCIÓN 4: FUNCIONES AUXILIARES (COMPATIBILIDAD CON CÓDIGO ANTERIOR)
        // =====================================================================



        // Función para limpiar formulario específico por tipo (mantener compatibilidad)
        function limpiarFormularioCliente(tipoPersona) {
            if (tipoPersona === 'personas-fisicas') {
                $('#pf_nombre, #pf_apellido_paterno, #pf_apellido_materno, #pf_rfc, #pf_curp, #pf_fecha_nacimiento, #pf_estado, #pf_ciudad, #pf_colonia, #pf_calle, #pf_num_exterior, #pf_num_interior, #pf_codigo_postal, #pf_telefono, #pf_correo').val('');
            } else if (tipoPersona === 'personas-morales') {
                $('#pm_razon_social, #pm_rfc, #pm_fecha_constitucion, #pm_apoderado_nombre, #pm_apoderado_paterno, #pm_apoderado_materno, #pm_apoderado_rfc, #pm_apoderado_curp, #pm_estado, #pm_ciudad, #pm_colonia, #pm_calle, #pm_num_exterior, #pm_num_interior, #pm_codigo_postal, #pm_telefono, #pm_correo').val('');
            } else if (tipoPersona === 'fideicomisos') {
                $('#fid_razon_social, #fid_rfc, #fid_numero_referencia, #fid_estado, #fid_ciudad, #fid_colonia, #fid_calle, #fid_num_exterior, #fid_num_interior, #fid_codigo_postal, #fid_telefono, #fid_correo').val('');
            }
        }

        // Función para llenar formulario cliente (mantener compatibilidad con código anterior)
        function llenarFormularioCliente(clientData, tipoPersona) {
            // Redirigir a la nueva función centralizada
            llenarFormularioConClienteSeleccionado(clientData);
        }



        // =====================================================================
        // SECCIÓN 5: EVENT LISTENERS NUEVOS Y ACTUALIZADOS
        // =====================================================================

        // EXTENDER EL $(document).ready EXISTENTE CON NUEVOS EVENT LISTENERS
        $(document).ready(function () {

            // =========================================
            // NUEVOS Event listeners para selects centralizados
            // =========================================

            // Event listener para select de empresa
            $(document).on('change', '#select_empresa_general', function () {
                const empresaId = $(this).val();
                cargarClientesPorEmpresa(empresaId);
            });

            // Event listener para select de cliente general
            $(document).on('change', '#select_cliente_general', function () {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    const clientData = JSON.parse(selectedOption.attr('data-client'));

                    // NUEVA LÓGICA MEJORADA: Detectar y cambiar modal automáticamente
                    cambiarModalSegunTipoClienteMejorado(clientData.tipo_persona, clientData);

                } else {
                    // Si no hay cliente seleccionado, limpiar solo formularios, NO los selects
                    limpiarSoloFormularios();
                    $('#tipo_cliente_display').val('');
                }
            });


            // =========================================
            // MANTENER Event listeners existentes para compatibilidad
            // (Estos pueden ser eliminados eventualmente)
            // =========================================

            // Persona Física (MANTENER PARA COMPATIBILIDAD)
            $(document).on('change', '#select_cliente_pf', function () {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    const clientData = JSON.parse(selectedOption.attr('data-client'));
                    llenarFormularioCliente(clientData, 'personas-fisicas');
                } else {
                    limpiarFormularioCliente('personas-fisicas');
                }
            });

            // Persona Moral (MANTENER PARA COMPATIBILIDAD)
            $(document).on('change', '#select_cliente_pm', function () {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    const clientData = JSON.parse(selectedOption.attr('data-client'));
                    llenarFormularioCliente(clientData, 'personas-morales');
                } else {
                    limpiarFormularioCliente('personas-morales');
                }
            });

            // Fideicomiso (MANTENER PARA COMPATIBILIDAD)
            $(document).on('change', '#select_cliente_fid', function () {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    const clientData = JSON.parse(selectedOption.attr('data-client'));
                    llenarFormularioCliente(clientData, 'fideicomisos');
                } else {
                    limpiarFormularioCliente('fideicomisos');
                }
            });

        });

        // =====================================================================
        // SECCIÓN 6: FUNCIONES OBSOLETAS COMENTADAS (PARA REFERENCIA)
        // =====================================================================

        /*
        // FUNCIÓN OBSOLETA: cargarClientesPorTipo (COMENTADA - NO ELIMINAR TODAVÍA)
        // Esta función puede ser eliminada después de confirmar que todo funciona
        function cargarClientesPorTipo(tipoPersona, selectId) {
            const $select = $('#' + selectId);

            // Limpiar select
            $select.html('<option value="">Cargando...</option>');

            // Determinar el tipo de persona para la consulta
            let tipoConsulta = '';
            switch (tipoPersona) {
                case 'personas-fisicas':
                    tipoConsulta = 'fisica';
                    break;
                case 'personas-morales':
                    tipoConsulta = 'moral';
                    break;
                case 'fideicomisos':
                    tipoConsulta = 'fideicomiso';
                    break;
            }

            // Hacer petición AJAX
            $.ajax({
                url: 'get_clients_ajax.php',
                type: 'GET',
                data: {
                    action: 'get_clients_by_type',
                    tipo_persona: tipoConsulta
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // Limpiar y agregar opción por defecto
                        $select.html('<option value="">-- Seleccionar cliente existente --</option>');

                        // Agregar opciones de clientes
                        response.clients.forEach(function (client) {
                            $select.append(
                                `<option value="${client.id}" data-client='${JSON.stringify(client.data)}'>
                            ${client.text}
                        </option>`
                            );
                        });

                        console.log(`Clientes cargados para ${tipoPersona}:`, response.clients.length);
                    } else {
                        $select.html('<option value="">Error al cargar clientes</option>');
                        console.error('Error:', response.error);
                    }
                },
                error: function (xhr, status, error) {
                    $select.html('<option value="">Error al cargar clientes</option>');
                    console.error('Error AJAX:', error);
                }
            });
        }
        */

        // =====================================================================
        // SECCIÓN 7: FUNCIONES DE DEBUGGING (OPCIONAL)
        // =====================================================================

        // Función de debug para verificar que todo funciona
        function debugClientSelection() {
            console.log('=== DEBUG SELECCIÓN DE CLIENTES ===');
            console.log('Empresa seleccionada:', $('#select_empresa_general').val());
            console.log('Cliente seleccionado:', $('#select_cliente_general').val());
            console.log('Tipo de cliente activo:', $('#hidden_tipo_cliente').val());
            console.log('Sección visible:',
                $('#seccion-persona-fisica').is(':visible') ? 'Persona Física' :
                    $('#seccion-persona-moral').is(':visible') ? 'Persona Moral' :
                        $('#seccion-fideicomiso').is(':visible') ? 'Fideicomiso' : 'Ninguna'
            );
        }

        // Función para mostrar información del cliente seleccionado
        function mostrarInfoClienteSeleccionado() {
            const selectedOption = $('#select_cliente_general').find('option:selected');
            if (selectedOption.val()) {
                const clientData = JSON.parse(selectedOption.attr('data-client'));
                console.log('Datos del cliente seleccionado:', clientData);
                return clientData;
            } else {
                console.log('No hay cliente seleccionado');
                return null;
            }
        }



        // PASO 7: FUNCIÓN AUXILIAR PARA DEBUGGING
        function debugModalActual() {
            console.log('=== DEBUG MODAL ACTUAL ===');
            console.log('Campo display:', $('#tipo_cliente_display').val());
            console.log('Campo hidden:', $('#hidden_tipo_cliente').val());
            console.log('Secciones visibles:');
            console.log('- Persona Física:', $('#seccion-persona-fisica').is(':visible'));
            console.log('- Persona Moral:', $('#seccion-persona-moral').is(':visible'));
            console.log('- Fideicomiso:', $('#seccion-fideicomiso').is(':visible'));
        }

        // =====================================================================
        // FUNCIONALIDADES ADICIONALES OPCIONALES
        // =====================================================================

        // OPCIONAL: Función para cambiar modal manualmente
        function cambiarModalManualmente(tipoCliente) {
            // Confirmar con el usuario si hay datos en el formulario
            if (hayDatosEnFormulario()) {
                if (!confirm('¿Estás seguro de cambiar el tipo de formulario? Se perderán los datos no guardados.')) {
                    return false;
                }
            }

            // Limpiar y cambiar
            limpiarFormulario();
            configurarModalSegunTipo(tipoCliente);

            return true;
        }

        // OPCIONAL: Función para detectar si hay datos en el formulario
        function hayDatosEnFormulario() {
            let hayDatos = false;

            // Verificar campos de persona física
            $('#seccion-persona-fisica input').each(function () {
                if ($(this).val() && $(this).attr('id') !== 'tipo_cliente_display') {
                    hayDatos = true;
                    return false;
                }
            });

            // Verificar campos de persona moral
            if (!hayDatos) {
                $('#seccion-persona-moral input').each(function () {
                    if ($(this).val()) {
                        hayDatos = true;
                        return false;
                    }
                });
            }

            // Verificar campos de fideicomiso
            if (!hayDatos) {
                $('#seccion-fideicomiso input').each(function () {
                    if ($(this).val()) {
                        hayDatos = true;
                        return false;
                    }
                });
            }

            return hayDatos;
        }
        // =====================================================================
        // CIERRE DEL SCRIPT
        // =====================================================================





        // =====================================================================
        // FUNCIONES PARA MANEJAR LA INFORMACIÓN DE LA EMPRESA
        // =====================================================================

        /**
         * Función para poblar los campos de información de la empresa
         * Se llama cuando se selecciona una empresa en el select
         */
        function poblarInformacionEmpresa(empresaData) {
            console.log('Poblando información de empresa:', empresaData);

            if (!empresaData) {
                limpiarInformacionEmpresa();
                return;
            }

            // Información General
            $('#empresa_nombre_display').val(empresaData.name_company || '');
            $('#empresa_rfc_display').val(empresaData.rfc_company || '');
            $('#empresa_razon_social_display').val(empresaData.razon_social || empresaData.name_company || '');
            $('#empresa_tipo_display').val(formatearTipoPersona(empresaData.tipo_persona) || '');
            $('#empresa_fecha_constitucion_display').val(formatearFecha(empresaData.fecha_constitucion) || '');

            // Contacto
            $('#empresa_telefono_display').val(empresaData.telefono || '');
            $('#empresa_email_display').val(empresaData.email || '');

            // Ubicación
            $('#empresa_estado_display').val(empresaData.estado || '');
            $('#empresa_ciudad_display').val(empresaData.ciudad || '');
            $('#empresa_colonia_display').val(empresaData.colonia || '');

            // Construir dirección completa
            let direccion = construirDireccionCompleta(empresaData);
            $('#empresa_direccion_display').val(direccion);
            $('#empresa_cp_display').val(empresaData.codigo_postal || '');

            // Representante Legal
            let nombreRepresentante = construirNombreRepresentante(empresaData);
            $('#empresa_representante_nombre_display').val(nombreRepresentante);
            $('#empresa_representante_rfc_display').val(empresaData.apoderado_rfc || '');
            $('#empresa_representante_curp_display').val(empresaData.apoderado_curp || '');
        }

        /**
         * Función para limpiar todos los campos de información de empresa
         */
        function limpiarInformacionEmpresa() {
            console.log('Limpiando información de empresa');

            // Información General
            $('#empresa_nombre_display').val('');
            $('#empresa_rfc_display').val('');
            $('#empresa_razon_social_display').val('');
            $('#empresa_tipo_display').val('');
            $('#empresa_fecha_constitucion_display').val('');

            // Contacto
            $('#empresa_telefono_display').val('');
            $('#empresa_email_display').val('');

            // Ubicación
            $('#empresa_estado_display').val('');
            $('#empresa_ciudad_display').val('');
            $('#empresa_colonia_display').val('');
            $('#empresa_direccion_display').val('');
            $('#empresa_cp_display').val('');

            // Representante Legal
            $('#empresa_representante_nombre_display').val('');
            $('#empresa_representante_rfc_display').val('');
            $('#empresa_representante_curp_display').val('');
        }

        /**
         * Función auxiliar para formatear el tipo de persona
         */
        function formatearTipoPersona(tipo) {
            if (!tipo) return '';

            switch (tipo.toLowerCase()) {
                case 'fisica':
                    return 'Persona Física';
                case 'moral':
                    return 'Persona Moral';
                case 'fideicomiso':
                    return 'Fideicomiso';
                default:
                    return tipo;
            }
        }

        /**
         * Función auxiliar para formatear fechas
         */
        function formatearFecha(fecha) {
            if (!fecha) return '';

            try {
                // Si la fecha viene en formato YYYY-MM-DD, convertir a DD/MM/YYYY
                if (fecha.includes('-')) {
                    let partes = fecha.split('-');
                    if (partes.length === 3) {
                        return `${partes[2]}/${partes[1]}/${partes[0]}`;
                    }
                }
                return fecha;
            } catch (e) {
                console.error('Error al formatear fecha:', e);
                return fecha;
            }
        }

        /**
         * Función auxiliar para construir la dirección completa
         */
        function construirDireccionCompleta(empresaData) {
            let direccion = '';

            if (empresaData.calle) {
                direccion += empresaData.calle;

                if (empresaData.num_exterior) {
                    direccion += ' ' + empresaData.num_exterior;
                }

                if (empresaData.num_interior) {
                    direccion += ' Int. ' + empresaData.num_interior;
                }
            }

            return direccion;
        }

        /**
         * Función auxiliar para construir el nombre completo del representante
         */
        function construirNombreRepresentante(empresaData) {
            let nombre = '';

            if (empresaData.apoderado_nombre) {
                nombre += empresaData.apoderado_nombre;

                if (empresaData.apoderado_apellido_paterno) {
                    nombre += ' ' + empresaData.apoderado_apellido_paterno;
                }

                if (empresaData.apoderado_apellido_materno) {
                    nombre += ' ' + empresaData.apoderado_apellido_materno;
                }
            }

            return nombre;
        }

        /**
         * Función para obtener los datos de empresa por ID
         * Esta función debe llamarse cuando se selecciona una empresa
         */
        function obtenerDatosEmpresa(empresaId, callback) {
            if (!empresaId) {
                limpiarInformacionEmpresa();
                if (callback) callback(null);
                return;
            }

            console.log('Obteniendo datos de empresa ID:', empresaId);

            // Realizar petición AJAX para obtener los datos completos de la empresa
            $.ajax({
                url: 'get_company_data.php', // Necesitarás crear este archivo
                type: 'POST',
                data: {
                    action: 'getCompanyData',
                    company_id: empresaId
                },
                dataType: 'json',
                success: function (response) {
                    console.log('Respuesta del servidor:', response);

                    if (response.success && response.data) {
                        poblarInformacionEmpresa(response.data);
                        if (callback) callback(response.data);
                    } else {
                        console.error('Error al obtener datos de empresa:', response.error || 'Respuesta inválida');
                        limpiarInformacionEmpresa();
                        if (callback) callback(null);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error AJAX al obtener datos de empresa:', error);
                    limpiarInformacionEmpresa();
                    if (callback) callback(null);
                }
            });
        }

        /**
         * Función para integrar con el select de empresa existente
         * Modifica la función existente de cambio de empresa
         */
        function integrarConSelectEmpresa() {
            // Interceptar el evento change del select de empresa
            $(document).on('change', '#select_empresa_general', function () {
                let empresaId = $(this).val();
                console.log('Empresa seleccionada:', empresaId);

                if (empresaId) {
                    // Obtener datos de la empresa y poblar los campos
                    obtenerDatosEmpresa(empresaId, function (empresaData) {
                        if (empresaData) {
                            console.log('Información de empresa cargada exitosamente');
                        } else {
                            console.warn('No se pudieron cargar los datos de la empresa');
                        }
                    });
                } else {
                    limpiarInformacionEmpresa();
                }
            });
        }

        // =====================================================================
        // INICIALIZACIÓN
        // =====================================================================

        $(document).ready(function () {
            // Integrar las funciones con el select existente
            integrarConSelectEmpresa();

            console.log('Sistema de información de empresa inicializado');
        });








    </script>

    <script>
        $(document).ready(function () {
            // Sincronizar el ID de empresa cuando cambie el select
            $('#select_empresa_general').on('change', function () {
                var empresaId = $(this).val();
                $('#hidden_empresa_id').val(empresaId);
                console.log('ID de empresa sincronizado:', empresaId);
            });

            // Sincronizar el ID de cliente cuando cambie el select
            $('#select_cliente_general').on('change', function () {
                var clienteId = $(this).val();
                $('#hidden_cliente_id').val(clienteId);
                console.log('ID de cliente sincronizado:', clienteId);
            });

            // Debug: Ver qué datos se envían al hacer submit
            $('#formAgregarOperacion').on('submit', function () {
                console.log('=== DATOS DEL FORMULARIO ===');
                console.log('Empresa ID:', $('#hidden_empresa_id').val());
                console.log('Cliente ID:', $('#hidden_cliente_id').val());
                console.log('Tipo Cliente:', $('#hidden_tipo_cliente').val());

                // Validar que se haya seleccionado empresa
                if (!$('#hidden_empresa_id').val()) {
                    alert('Debe seleccionar una empresa antes de registrar la operación');
                    return false;
                }

                return true;
            });
        });
    </script>










    <!-- JavaScript para el modal de edición -->
    <script>
        // Variable global para almacenar los datos de la operación a editar
        let operacionAEditar = null;

        // Contadores para beneficiarios controladores en edición
        let contadorBeneficiariosEdit = {
            pm: 0,
            fid_fideicomitente: 0,
            fid_fiduciario: 0,
            fid_fideicomisario: 0,
            fid_control_efectivo: 0
        };

        /**
         * Función principal para abrir el modal de edición de operación
         * @param {number} operationId - ID de la operación a editar
         */


        // Event listener para los botones "Editar operación" en la tabla
        $(document).on('click', '.dropdown-item[data-operation-id]', function (e) {
            e.preventDefault();
            const operationId = $(this).data('operation-id');
            console.log('Iniciando edición de operación desde tabla:', operationId);
            editarOperacion(operationId);
        });

        /**
        * Configurar y abrir el modal de edición
        */
        function abrirModalEdicionOperacion() {
            console.log('Abriendo modal de edición para operación:', operacionAEditar.id_operation);

            // Limpiar formulario antes de llenar
            $('#formEditarOperacion')[0].reset();

            // Configurar el modal según el tipo de cliente
            configurarModalSegunTipoEdit(operacionAEditar.tipo_cliente);

            // Cargar empresas y clientes
            cargarEmpresasClientesEdit();

            // Llenar formulario con datos existentes (con un delay para que carguen los selects)
            setTimeout(function () {
                llenarFormularioEdicion();
            }, 800); // Aumentar el delay un poco

            // Mostrar el modal
            $('#modalEditarOperacion').modal('show');
        }

        function editarOperacion(operationId) {
            console.log('Iniciando edición de operación:', operationId);

            // Obtener los datos de la operación desde el servidor
            $.ajax({
                type: "GET",
                url: "../../app/webservice.php",
                data: {
                    action: "getOperationDetail",
                    operationId: operationId
                },
                dataType: 'json'
            }).done(function (response) {
                console.log('Respuesta completa del servidor:', response); // Para debugging

                if (response.success) {
                    // CORRECCIÓN: Los datos están en response.data.main
                    operacionAEditar = response.data.main; // ← AQUÍ ESTABA EL PROBLEMA

                    console.log('Datos de operación a editar:', operacionAEditar); // Para verificar

                    abrirModalEdicionOperacion();
                } else {
                    mostrarAlerta('Error al cargar los datos de la operación', 'error');
                }
            }).fail(function (xhr, status, error) {
                console.error('Error en la petición AJAX:', error);
                mostrarAlerta('Error de conexión al cargar los datos', 'error');
            });
        }

        /**
         * Configurar y abrir el modal de edición
         */


        /**
         * Cargar empresas y clientes para el modal de edición
         */
        function cargarEmpresasClientesEdit() {
            // Cargar empresas
            $.ajax({
                type: "GET",
                url: "../../app/webservice.php",
                data: { action: "getCompanies" },
                dataType: 'json'
            }).done(function (response) {
                let options = '<option value="">-- Seleccionar empresa --</option>';
                if (response.success && response.data) {
                    response.data.forEach(function (empresa) {
                        options += `<option value="${empresa.id_company}" data-company='${JSON.stringify(empresa)}'>${empresa.name_company}</option>`;
                    });
                }
                $('#select_empresa_general_edit').html(options);

                // Seleccionar la empresa de la operación
                if (operacionAEditar && operacionAEditar.id_company_operation) {
                    $('#select_empresa_general_edit').val(operacionAEditar.id_company_operation).trigger('change');
                }
            });

            // Cargar todos los clientes
            cargarTodosLosClientesEdit();
        }

        /**
         * Cargar todos los clientes para el modal de edición
         */
        function cargarTodosLosClientesEdit() {
            $.ajax({
                type: "GET",
                url: "../../app/webservice.php",
                data: { action: "getAllClients" },
                dataType: 'json'
            }).done(function (response) {
                let options = '<option value="">-- Seleccionar cliente existente --</option>';
                if (response.success && response.data) {
                    response.data.forEach(function (cliente) {
                        let nombreCompleto = '';
                        if (cliente.tipo_persona === 'persona_fisica') {
                            nombreCompleto = `${cliente.nombre || ''} ${cliente.apellido_paterno || ''} ${cliente.apellido_materno || ''}`.trim();
                        } else {
                            nombreCompleto = cliente.razon_social || '';
                        }
                        options += `<option value="${cliente.id_client}" data-client='${JSON.stringify(cliente)}'>${nombreCompleto} (${cliente.rfc || 'Sin RFC'})</option>`;
                    });
                }
                $('#select_cliente_general_edit').html(options);

                // Seleccionar el cliente de la operación
                if (operacionAEditar && operacionAEditar.id_client_operation) {
                    $('#select_cliente_general_edit').val(operacionAEditar.id_client_operation).trigger('change');
                }
            });
        }

        /**
         * Configurar el modal según el tipo de cliente para edición
         */
        function configurarModalSegunTipoEdit(tipoCliente) {
            console.log('Configurando modal de edición para:', tipoCliente);

            // Ocultar todas las secciones
            $('#seccion-persona-fisica-edit').hide();
            $('#seccion-persona-moral-edit').hide();
            $('#seccion-fideicomiso-edit').hide();

            switch (tipoCliente) {
                case 'persona_fisica':
                    $('#hidden_tipo_cliente_edit').val('persona_fisica');
                    $('#tipo_cliente_display_edit').val('Persona Física');
                    $('#seccion-persona-fisica-edit').show();
                    break;

                case 'persona_moral':
                    $('#hidden_tipo_cliente_edit').val('persona_moral');
                    $('#tipo_cliente_display_edit').val('Persona Moral');
                    $('#seccion-persona-moral-edit').show();
                    break;

                case 'fideicomiso':
                    $('#hidden_tipo_cliente_edit').val('fideicomiso');
                    $('#tipo_cliente_display_edit').val('Fideicomiso');
                    $('#seccion-fideicomiso-edit').show();
                    break;

                default:
                    $('#hidden_tipo_cliente_edit').val('persona_fisica');
                    $('#tipo_cliente_display_edit').val('Persona Física');
                    $('#seccion-persona-fisica-edit').show();
                    break;
            }
        }

        /**
         * Llenar el formulario con los datos de la operación a editar
         */

        function llenarFormularioEdicion() {
            if (!operacionAEditar) {
                console.error('No hay datos de operación para editar');
                return;
            }

            console.log('Llenando formulario con datos:', operacionAEditar);

            // Campos ocultos
            $('#edit_operation_id').val(operacionAEditar.id_operation);
            $('#hidden_empresa_id_edit').val(operacionAEditar.id_company_operation);
            $('#hidden_cliente_id_edit').val(operacionAEditar.id_client_operation);

            // Información de la operación
            $('#tipo_operacion_edit').val(operacionAEditar.tipo_operacion);
            $('#fecha_operacion_edit').val(operacionAEditar.fecha_operacion);
            $('#monto_operacion_edit').val(operacionAEditar.monto_operacion);
            $('#moneda_edit').val(operacionAEditar.moneda);
            $('#moneda_otra_edit').val(operacionAEditar.moneda_otra);
            $('#forma_pago_edit').val(operacionAEditar.forma_pago);
            $('#monto_efectivo_edit').val(operacionAEditar.monto_efectivo);

            // Información del inmueble
            $('#tipo_propiedad_edit').val(operacionAEditar.tipo_propiedad);
            $('#uso_inmueble_edit').val(operacionAEditar.uso_inmueble);
            $('#direccion_inmueble_edit').val(operacionAEditar.direccion_inmueble);
            $('#codigo_postal_edit').val(operacionAEditar.codigo_postal);
            $('#folio_escritura_edit').val(operacionAEditar.folio_escritura);
            $('#propietario_anterior_edit').val(operacionAEditar.propietario_anterior);

            // Checkboxes
            $('#requiere_aviso_sat_edit').prop('checked', operacionAEditar.requiere_aviso_sat == 1);
            $('#umbral_superado_edit').prop('checked', operacionAEditar.umbral_superado == 1);

            // Observaciones
            $('#observaciones_edit').val(operacionAEditar.observaciones);

            // Selects con valores
            $('#select_empresa_general_edit').val(operacionAEditar.id_company_operation);
            $('#select_cliente_general_edit').val(operacionAEditar.id_client_operation);

            // Mostrar/ocultar campos condicionales
            if (operacionAEditar.moneda === 'Otra') {
                $('#div_moneda_otra_edit').show();
            } else {
                $('#div_moneda_otra_edit').hide();
            }

            if (operacionAEditar.forma_pago === 'Efectivo' || operacionAEditar.forma_pago === 'Mixto') {
                $('#div_monto_efectivo_edit').show();
            } else {
                $('#div_monto_efectivo_edit').hide();
            }

            // Llenar datos específicos del cliente según el tipo
            llenarDatosClienteEdicionCorregido();

            console.log('Formulario llenado completamente');
        }

        /**
         * Llenar los datos específicos del cliente en el formulario de edición
         */
        function llenarDatosClienteEdicionCorregido() {
            console.log('Llenando datos del cliente, tipo:', operacionAEditar.tipo_cliente);

            switch (operacionAEditar.tipo_cliente) {
                case 'persona_fisica':
                    // Los datos de persona física vienen directamente en el objeto main con prefijo pf_
                    $('#pf_nombre_edit').val(operacionAEditar.pf_nombre || '');
                    $('#pf_apellido_paterno_edit').val(operacionAEditar.pf_apellido_paterno || '');
                    $('#pf_apellido_materno_edit').val(operacionAEditar.pf_apellido_materno || '');
                    $('#pf_rfc_edit').val(operacionAEditar.pf_rfc || '');
                    $('#pf_curp_edit').val(operacionAEditar.pf_curp || '');
                    $('#pf_fecha_nacimiento_edit').val(operacionAEditar.pf_fecha_nacimiento || '');
                    $('#pf_telefono_edit').val(operacionAEditar.pf_telefono || '');
                    $('#pf_email_edit').val(operacionAEditar.pf_email || '');

                    // Dirección nacional
                    $('#pf_estado_edit').val(operacionAEditar.pf_estado || '');
                    $('#pf_ciudad_edit').val(operacionAEditar.pf_ciudad || '');
                    $('#pf_colonia_edit').val(operacionAEditar.pf_colonia || '');
                    $('#pf_calle_edit').val(operacionAEditar.pf_calle || '');
                    $('#pf_num_exterior_edit').val(operacionAEditar.pf_num_exterior || '');
                    $('#pf_num_interior_edit').val(operacionAEditar.pf_num_interior || '');
                    $('#pf_codigo_postal_edit').val(operacionAEditar.pf_codigo_postal || '');

                    // Checkbox domicilio extranjero
                    $('#pf_tiene_domicilio_extranjero_edit').prop('checked', operacionAEditar.pf_tiene_domicilio_extranjero == 1);

                    // Dirección extranjera
                    $('#pf_pais_extranjero_edit').val(operacionAEditar.pf_pais_extranjero || '');
                    $('#pf_estado_extranjero_edit').val(operacionAEditar.pf_estado_extranjero || '');
                    $('#pf_ciudad_extranjero_edit').val(operacionAEditar.pf_ciudad_extranjero || '');
                    $('#pf_direccion_extranjero_edit').val(operacionAEditar.pf_direccion_extranjero || '');
                    $('#pf_codigo_postal_extranjero_edit').val(operacionAEditar.pf_codigo_postal_extranjero || '');
                    break;

                case 'persona_moral':
                    // Los datos de persona moral vienen directamente en el objeto main con prefijo pm_
                    $('#pm_razon_social_edit').val(operacionAEditar.pm_razon_social || '');
                    $('#pm_rfc_edit').val(operacionAEditar.pm_rfc || '');
                    $('#pm_fecha_constitucion_edit').val(operacionAEditar.pm_fecha_constitucion || '');
                    $('#pm_telefono_edit').val(operacionAEditar.pm_telefono || '');
                    $('#pm_email_edit').val(operacionAEditar.pm_email || '');

                    // Dirección nacional
                    $('#pm_estado_edit').val(operacionAEditar.pm_estado || '');
                    $('#pm_ciudad_edit').val(operacionAEditar.pm_ciudad || '');
                    $('#pm_colonia_edit').val(operacionAEditar.pm_colonia || '');
                    $('#pm_calle_edit').val(operacionAEditar.pm_calle || '');
                    $('#pm_num_exterior_edit').val(operacionAEditar.pm_num_exterior || '');
                    $('#pm_num_interior_edit').val(operacionAEditar.pm_num_interior || '');
                    $('#pm_codigo_postal_edit').val(operacionAEditar.pm_codigo_postal || '');

                    // Checkbox domicilio extranjero
                    $('#pm_tiene_domicilio_extranjero_edit').prop('checked', operacionAEditar.pm_tiene_domicilio_extranjero == 1);

                    // Dirección extranjera
                    $('#pm_pais_extranjero_edit').val(operacionAEditar.pm_pais_extranjero || '');
                    $('#pm_estado_extranjero_edit').val(operacionAEditar.pm_estado_extranjero || '');
                    $('#pm_ciudad_extranjero_edit').val(operacionAEditar.pm_ciudad_extranjero || '');
                    $('#pm_direccion_extranjero_edit').val(operacionAEditar.pm_direccion_extranjero || '');
                    $('#pm_codigo_postal_extranjero_edit').val(operacionAEditar.pm_codigo_postal_extranjero || '');

                    // Representante legal
                    $('#pm_representante_nombre_edit').val(operacionAEditar.pm_representante_nombre || '');
                    $('#pm_representante_paterno_edit').val(operacionAEditar.pm_representante_paterno || '');
                    $('#pm_representante_materno_edit').val(operacionAEditar.pm_representante_materno || '');
                    $('#pm_representante_rfc_edit').val(operacionAEditar.pm_representante_rfc || '');
                    $('#pm_representante_curp_edit').val(operacionAEditar.pm_representante_curp || '');
                    break;

                case 'fideicomiso':
                    // Los datos de fideicomiso vienen directamente en el objeto main con prefijo fid_
                    $('#fid_numero_contrato_edit').val(operacionAEditar.fid_numero_contrato || '');
                    $('#fid_fecha_contrato_edit').val(operacionAEditar.fid_fecha_contrato || '');
                    $('#fid_tipo_fideicomiso_edit').val(operacionAEditar.fid_tipo_fideicomiso || '');
                    $('#fid_proposito_edit').val(operacionAEditar.fid_proposito || '');
                    $('#fid_numero_referencia_edit').val(operacionAEditar.fid_numero_referencia || '');
                    $('#fid_telefono_edit').val(operacionAEditar.fid_telefono || '');
                    $('#fid_email_edit').val(operacionAEditar.fid_email || '');

                    // Dirección nacional
                    $('#fid_estado_edit').val(operacionAEditar.fid_estado || '');
                    $('#fid_ciudad_edit').val(operacionAEditar.fid_ciudad || '');
                    $('#fid_colonia_edit').val(operacionAEditar.fid_colonia || '');
                    $('#fid_calle_edit').val(operacionAEditar.fid_calle || '');
                    $('#fid_num_exterior_edit').val(operacionAEditar.fid_num_exterior || '');
                    $('#fid_num_interior_edit').val(operacionAEditar.fid_num_interior || '');
                    $('#fid_codigo_postal_edit').val(operacionAEditar.fid_codigo_postal || '');

                    // Checkbox domicilio extranjero
                    $('#fid_tiene_domicilio_extranjero_edit').prop('checked', operacionAEditar.fid_tiene_domicilio_extranjero == 1);

                    // Dirección extranjera
                    $('#fid_pais_extranjero_edit').val(operacionAEditar.fid_pais_extranjero || '');
                    $('#fid_estado_extranjero_edit').val(operacionAEditar.fid_estado_extranjero || '');
                    $('#fid_ciudad_extranjero_edit').val(operacionAEditar.fid_ciudad_extranjero || '');
                    $('#fid_direccion_extranjero_edit').val(operacionAEditar.fid_direccion_extranjero || '');
                    $('#fid_codigo_postal_extranjero_edit').val(operacionAEditar.fid_codigo_postal_extranjero || '');

                    // Apoderado
                    $('#fid_apoderado_nombre_edit').val(operacionAEditar.fid_apoderado_nombre || '');
                    $('#fid_apoderado_paterno_edit').val(operacionAEditar.fid_apoderado_paterno || '');
                    $('#fid_apoderado_materno_edit').val(operacionAEditar.fid_apoderado_materno || '');

                    // Fiduciaria
                    $('#fid_razon_social_edit').val(operacionAEditar.fid_razon_social || '');
                    $('#fid_rfc_edit').val(operacionAEditar.fid_rfc || '');
                    break;

                default:
                    console.warn('Tipo de cliente no reconocido:', operacionAEditar.tipo_cliente);
                    break;
            }

            console.log('Datos del cliente llenados para tipo:', operacionAEditar.tipo_cliente);
        }
        /**
         * Cargar beneficiarios controladores existentes para persona moral
         */
        function cargarBeneficiariosControladores(tipo, beneficiarios) {
            beneficiarios.forEach(function (beneficiario, index) {
                agregarBeneficiarioControlador(tipo);
                // Llenar los datos del beneficiario
                $(`#beneficiario_${tipo}_${contadorBeneficiariosEdit[tipo.replace('_edit', '')]}_nombre`).val(beneficiario.nombre);
                $(`#beneficiario_${tipo}_${contadorBeneficiariosEdit[tipo.replace('_edit', '')]}_apellido_paterno`).val(beneficiario.apellido_paterno);
                $(`#beneficiario_${tipo}_${contadorBeneficiariosEdit[tipo.replace('_edit', '')]}_apellido_materno`).val(beneficiario.apellido_materno);
                $(`#beneficiario_${tipo}_${contadorBeneficiariosEdit[tipo.replace('_edit', '')]}_rfc`).val(beneficiario.rfc);
                $(`#beneficiario_${tipo}_${contadorBeneficiariosEdit[tipo.replace('_edit', '')]}_porcentaje`).val(beneficiario.porcentaje);
            });
        }

        /**
         * Cargar beneficiarios de fideicomiso existentes
         */
        function cargarBeneficiariosFideicomiso(beneficiarios) {
            // Cargar fideicomitentes
            if (beneficiarios.fideicomitente) {
                beneficiarios.fideicomitente.forEach(function (beneficiario) {
                    agregarBeneficiarioControlador('fid_fideicomitente_edit');
                    // Llenar datos...
                });
            }

            // Cargar fiduciarios
            if (beneficiarios.fiduciario) {
                beneficiarios.fiduciario.forEach(function (beneficiario) {
                    agregarBeneficiarioControlador('fid_fiduciario_edit');
                    // Llenar datos...
                });
            }

            // Cargar fideicomisarios
            if (beneficiarios.fideicomisario) {
                beneficiarios.fideicomisario.forEach(function (beneficiario) {
                    agregarBeneficiarioControlador('fid_fideicomisario_edit');
                    // Llenar datos...
                });
            }

            // Cargar control efectivo
            if (beneficiarios.control_efectivo) {
                beneficiarios.control_efectivo.forEach(function (beneficiario) {
                    agregarBeneficiarioControlador('fid_control_efectivo_edit');
                    // Llenar datos...
                });
            }
        }

        // Event listeners para el modal de edición
        $(document).ready(function () {
            // Mostrar/ocultar moneda otra en edición
            $('#moneda_edit').change(function () {
                if ($(this).val() === 'Otra') {
                    $('#div_moneda_otra_edit').show();
                    $('#moneda_otra_edit').attr('required', true);
                } else {
                    $('#div_moneda_otra_edit').hide();
                    $('#moneda_otra_edit').attr('required', false);
                }
            });

            // Mostrar/ocultar monto efectivo en edición
            $('#forma_pago_edit').change(function () {
                if ($(this).val() === 'Efectivo' || $(this).val() === 'Mixto') {
                    $('#div_monto_efectivo_edit').show();
                    $('#monto_efectivo_edit').attr('required', true);
                } else {
                    $('#div_monto_efectivo_edit').hide();
                    $('#monto_efectivo_edit').attr('required', false);
                }
            });

            // Cambio de empresa en edición
            $('#select_empresa_general_edit').change(function () {
                const empresaId = $(this).val();
                $('#hidden_empresa_id_edit').val(empresaId);

                if (empresaId) {
                    const empresaData = JSON.parse($(this).find('option:selected').attr('data-company') || '{}');
                    mostrarInfoEmpresaSeleccionadaEdit(empresaData);
                    cargarClientesPorEmpresaEdit(empresaId);
                } else {
                    $('#info-empresa-seleccionada-edit').hide();
                    cargarTodosLosClientesEdit();
                }
            });

            // Cambio de cliente en edición
            $('#select_cliente_general_edit').change(function () {
                const clienteId = $(this).val();
                $('#hidden_cliente_id_edit').val(clienteId);

                if (clienteId) {
                    const clienteData = JSON.parse($(this).find('option:selected').attr('data-client') || '{}');
                    mostrarInfoClienteSeleccionadoEdit(clienteData);
                    cambiarModalSegunTipoClienteMejoradoEdit(clienteData.tipo_persona, clienteData);
                } else {
                    $('#info-cliente-seleccionado-edit').hide();
                    limpiarFormularioClienteEdit();
                }
            });
        });

        /**
         * Mostrar información de la empresa seleccionada en edición
         */
        function mostrarInfoEmpresaSeleccionadaEdit(empresaData) {
            if (empresaData && empresaData.name_company) {
                const info = `
            <strong>Nombre:</strong> ${empresaData.name_company}<br>
            <strong>RFC:</strong> ${empresaData.rfc_company || 'No especificado'}<br>
            <strong>Teléfono:</strong> ${empresaData.phone_company || 'No especificado'}<br>
            <strong>Email:</strong> ${empresaData.email_company || 'No especificado'}
        `;
                $('#detalle-empresa-edit').html(info);
                $('#info-empresa-seleccionada-edit').show();
            } else {
                $('#info-empresa-seleccionada-edit').hide();
            }
        }

        /**
         * Mostrar información del cliente seleccionado en edición
         */
        function mostrarInfoClienteSeleccionadoEdit(clienteData) {
            if (clienteData) {
                let info = '';
                if (clienteData.tipo_persona === 'persona_fisica') {
                    info = `
                <strong>Nombre:</strong> ${clienteData.nombre || ''} ${clienteData.apellido_paterno || ''} ${clienteData.apellido_materno || ''}<br>
                <strong>RFC:</strong> ${clienteData.rfc || 'No especificado'}<br>
                <strong>CURP:</strong> ${clienteData.curp || 'No especificado'}
            `;
                } else {
                    info = `
                <strong>Razón Social:</strong> ${clienteData.razon_social || ''}<br>
                <strong>RFC:</strong> ${clienteData.rfc || 'No especificado'}
            `;
                }
                $('#detalle-cliente-edit').html(info);
                $('#info-cliente-seleccionado-edit').show();
            } else {
                $('#info-cliente-seleccionado-edit').hide();
            }
        }

        /**
         * Cargar clientes por empresa en edición
         */
        function cargarClientesPorEmpresaEdit(empresaId) {
            $.ajax({
                type: "GET",
                url: "../../app/webservice.php",
                data: {
                    action: "getClientsByCompany",
                    companyId: empresaId
                },
                dataType: 'json'
            }).done(function (response) {
                let options = '<option value="">-- Seleccionar cliente existente --</option>';
                if (response.success && response.data) {
                    response.data.forEach(function (cliente) {
                        let nombreCompleto = '';
                        if (cliente.tipo_persona === 'persona_fisica') {
                            nombreCompleto = `${cliente.nombre || ''} ${cliente.apellido_paterno || ''} ${cliente.apellido_materno || ''}`.trim();
                        } else {
                            nombreCompleto = cliente.razon_social || '';
                        }
                        options += `<option value="${cliente.id_client}" data-client='${JSON.stringify(cliente)}'>${nombreCompleto} (${cliente.rfc || 'Sin RFC'})</option>`;
                    });
                }
                $('#select_cliente_general_edit').html(options);
            });
        }

        /**
         * Cambiar modal según tipo de cliente en edición (mejorado)
         */
        function cambiarModalSegunTipoClienteMejoradoEdit(tipoPersona, clientData) {
            let tipoClienteActual = $('#hidden_tipo_cliente_edit').val();
            let tipoRequerido = '';
            let nombreTipo = '';

            // Determinar qué tipo de modal necesitamos
            switch (tipoPersona) {
                case 'fisica':
                    tipoRequerido = 'persona_fisica';
                    nombreTipo = 'Persona Física';
                    break;
                case 'moral':
                    tipoRequerido = 'persona_moral';
                    nombreTipo = 'Persona Moral';
                    break;
                case 'fideicomiso':
                    tipoRequerido = 'fideicomiso';
                    nombreTipo = 'Fideicomiso';
                    break;
            }

            // Si el modal actual no coincide con el tipo de cliente seleccionado
            if (tipoClienteActual !== tipoRequerido) {
                console.log(`Cambiando modal de edición de ${tipoClienteActual} a ${tipoRequerido}`);
                configurarModalSegunTipoEdit(tipoRequerido);
            }

            // Llenar los datos del cliente
            llenarFormularioConClienteSeleccionadoEdit(clientData);
            $('#tipo_cliente_display_edit').val(nombreTipo);
        }

        /**
         * Llenar formulario con cliente seleccionado en edición
         */
        function llenarFormularioConClienteSeleccionadoEdit(clientData) {
            if (!clientData) return;

            switch (clientData.tipo_persona) {
                case 'persona_fisica':
                case 'fisica':
                    $('#pf_nombre_edit').val(clientData.nombre || '');
                    $('#pf_apellido_paterno_edit').val(clientData.apellido_paterno || '');
                    $('#pf_apellido_materno_edit').val(clientData.apellido_materno || '');
                    $('#pf_rfc_edit').val(clientData.rfc || '');
                    $('#pf_curp_edit').val(clientData.curp || '');
                    break;

                case 'persona_moral':
                case 'moral':
                    $('#pm_razon_social_edit').val(clientData.razon_social || '');
                    $('#pm_rfc_edit').val(clientData.rfc || '');
                    break;

                case 'fideicomiso':
                    $('#fid_razon_social_edit').val(clientData.razon_social || '');
                    $('#fid_rfc_edit').val(clientData.rfc || '');
                    break;
            }
        }

        /**
         * Limpiar formulario de cliente en edición
         */
        function limpiarFormularioClienteEdit() {
            // Limpiar campos de persona física
            $('#pf_nombre_edit, #pf_apellido_paterno_edit, #pf_apellido_materno_edit, #pf_rfc_edit, #pf_curp_edit').val('');

            // Limpiar campos de persona moral
            $('#pm_razon_social_edit, #pm_rfc_edit').val('');

            // Limpiar campos de fideicomiso
            $('#fid_razon_social_edit, #fid_rfc_edit').val('');

            // Limpiar beneficiarios
            $('#beneficiarios-pm-edit').empty();
            $('#beneficiarios-fid-fideicomitente-edit').empty();
            $('#beneficiarios-fid-fiduciario-edit').empty();
            $('#beneficiarios-fid-fideicomisario-edit').empty();
            $('#beneficiarios-fid-control-efectivo-edit').empty();

            // Resetear contadores
            contadorBeneficiariosEdit = {
                pm: 0,
                fid_fideicomitente: 0,
                fid_fiduciario: 0,
                fid_fideicomisario: 0,
                fid_control_efectivo: 0
            };
        }

        /**
         * Agregar beneficiario controlador en modal de edición
         */
        function agregarBeneficiarioControlador(tipo) {
            let contador, contenedor, titulo, prefijo;

            // Adaptar para el contexto de edición
            let tipoBase = tipo.replace('_edit', '');

            switch (tipoBase) {
                case 'pm':
                    contador = ++contadorBeneficiariosEdit.pm;
                    contenedor = '#beneficiarios-pm-edit';
                    titulo = 'Beneficiario Controlador';
                    prefijo = 'pm_edit';
                    break;
                case 'fid_fideicomitente':
                    contador = ++contadorBeneficiariosEdit.fid_fideicomitente;
                    contenedor = '#beneficiarios-fid-fideicomitente-edit';
                    titulo = 'Fideicomitente';
                    prefijo = 'fid_fideicomitente_edit';
                    break;
                case 'fid_fiduciario':
                    contador = ++contadorBeneficiariosEdit.fid_fiduciario;
                    contenedor = '#beneficiarios-fid-fiduciario-edit';
                    titulo = 'Fiduciario';
                    prefijo = 'fid_fiduciario_edit';
                    break;
                case 'fid_fideicomisario':
                    contador = ++contadorBeneficiariosEdit.fid_fideicomisario;
                    contenedor = '#beneficiarios-fid-fideicomisario-edit';
                    titulo = 'Fideicomisario';
                    prefijo = 'fid_fideicomisario_edit';
                    break;
                case 'fid_control_efectivo':
                    contador = ++contadorBeneficiariosEdit.fid_control_efectivo;
                    contenedor = '#beneficiarios-fid-control-efectivo-edit';
                    titulo = 'Control Efectivo';
                    prefijo = 'fid_control_efectivo_edit';
                    break;
                default:
                    console.error('Tipo de beneficiario no válido:', tipo);
                    return;
            }

            const beneficiarioHtml = `
        <div class="card mb-2 beneficiario-card-edit" id="beneficiario_card_${prefijo}_${contador}">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <small><strong>${titulo} #${contador}</strong></small>
                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarBeneficiarioEdit('${prefijo}', ${contador})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="card-body py-2">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="beneficiario_${prefijo}_${contador}_nombre">Nombre(s):</label>
                            <input type="text" class="form-control form-control-sm" 
                                   id="beneficiario_${prefijo}_${contador}_nombre" 
                                   name="beneficiarios[${tipoBase}][${contador}][nombre]" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="beneficiario_${prefijo}_${contador}_apellido_paterno">Apellido Paterno:</label>
                            <input type="text" class="form-control form-control-sm" 
                                   id="beneficiario_${prefijo}_${contador}_apellido_paterno" 
                                   name="beneficiarios[${tipoBase}][${contador}][apellido_paterno]" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="beneficiario_${prefijo}_${contador}_apellido_materno">Apellido Materno:</label>
                            <input type="text" class="form-control form-control-sm" 
                                   id="beneficiario_${prefijo}_${contador}_apellido_materno" 
                                   name="beneficiarios[${tipoBase}][${contador}][apellido_materno]">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="beneficiario_${prefijo}_${contador}_rfc">RFC:</label>
                            <input type="text" class="form-control form-control-sm" 
                                   id="beneficiario_${prefijo}_${contador}_rfc" 
                                   name="beneficiarios[${tipoBase}][${contador}][rfc]" maxlength="13">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="beneficiario_${prefijo}_${contador}_porcentaje">Porcentaje de participación:</label>
                            <div class="input-group">
                                <input type="number" class="form-control form-control-sm" 
                                       id="beneficiario_${prefijo}_${contador}_porcentaje" 
                                       name="beneficiarios[${tipoBase}][${contador}][porcentaje]" 
                                       min="0" max="100" step="0.01">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

            $(contenedor).append(beneficiarioHtml);
        }

        /**
         * Eliminar beneficiario controlador en modal de edición
         */
        function eliminarBeneficiarioEdit(prefijo, contador) {
            if (confirm('¿Está seguro de eliminar este beneficiario controlador?')) {
                $(`#beneficiario_card_${prefijo}_${contador}`).remove();
            }
        }

        /**
         * Función para mostrar alertas
         */
        function mostrarAlerta(mensaje, tipo = 'info') {
            // Implementar según el sistema de alertas del proyecto
            if (tipo === 'error') {
                alert('Error: ' + mensaje);
            } else {
                alert(mensaje);
            }
        }

        // Función para llamar desde los botones de editar en la tabla
        function abrirModalEdit(operationId) {
            editarOperacion(operationId);
        }
    </script>