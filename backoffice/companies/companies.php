<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
include "../../app/config.php";
include "../../app/WebController.php";
$controller = new WebController();

// Verificar si la sesión del usuario está activa
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    session_destroy();
    header("Location: ../../login.php");
    exit();
}


// PROCESAMIENTO VIA AJAX PARA CREAR EMPRESA
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'create_company') {
    header('Content-Type: application/json');

    // Validar datos de empresa
    $requiredFields = ['name_company', 'rfc_company', 'razon_social', 'tipo_persona'];
    foreach ($requiredFields as $field) {
        if (empty($_POST['company'][$field])) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben ser completados']);
            exit();
        }
    }

    // Verificar si el RFC ya existe
    $existingRFC = $controller->getRFCCompany($_POST['company']['rfc_company']);
    if (!empty($existingRFC)) {
        echo json_encode(['success' => false, 'message' => 'El RFC de la empresa ya está registrado']);
        exit();
    }

    // Generar clave única para la empresa
    $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $_POST['company']['key_company'] = 'EMP-' . substr(str_shuffle($permitted_chars), 0, 8);

    // Crear empresa
    $companyId = $controller->createClientCompany($_POST['company']);


    if ($companyId) {
        // Obtener datos de la empresa recién creada
        $newCompany = $controller->getCompanyById($companyId);
        echo json_encode([
            'success' => true,
            'message' => 'Empresa creada exitosamente',
            'company' => [
                'id' => $newCompany['id_company'],
                'name' => $newCompany['name_company'],
                'rfc' => $newCompany['rfc_company']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear la empresa']);
    }
    exit();
}

// PROCESAMIENTO VIA AJAX PARA ACTUALIZAR EMPRESA
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'update_company') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();

    try {
        $companyId = $_POST['company_id'];

        if (empty($companyId)) {
            echo json_encode(['success' => false, 'message' => 'ID de empresa requerido']);
            exit();
        }

        // Validar datos requeridos
        $requiredFields = ['name_company', 'rfc_company', 'razon_social', 'tipo_persona'];
        foreach ($requiredFields as $field) {
            if (empty($_POST['company'][$field])) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben ser completados']);
                exit();
            }
        }

        // Verificar RFC duplicado (excluyendo la empresa actual)
        $existingRFC = $controller->getRFCCompanyExclude($_POST['company']['rfc_company'], $companyId);
        if (!empty($existingRFC)) {
            echo json_encode(['success' => false, 'message' => 'El RFC ya está registrado por otra empresa']);
            exit();
        }

        // Actualizar empresa
        $updated = $controller->updateCompany($_POST['company'], $companyId);

        if ($updated) {
            $updatedCompany = $controller->getCompanyById($companyId);
            echo json_encode([
                'success' => true,
                'message' => 'Empresa actualizada exitosamente',
                'company' => [
                    'id' => $updatedCompany['id_company'],
                    'name' => $updatedCompany['name_company'],
                    'rfc' => $updatedCompany['rfc_company']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la empresa']);
        }
    } catch (Exception $e) {
        error_log("Error actualizando empresa: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
    exit();
}

// PROCESAMIENTO VIA AJAX PARA ELIMINAR EMPRESA
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'delete_company') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();

    try {
        $companyId = $_POST['company_id'];

        if (empty($companyId)) {
            echo json_encode(['success' => false, 'message' => 'ID de empresa requerido']);
            exit();
        }

        // Verificar si la empresa tiene usuarios asociados
        $usersCount = $controller->getUsersByCompany($companyId);
        if (count($usersCount) > 0) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar la empresa porque tiene ' . count($usersCount) . ' usuarios asociados']);
            exit();
        }

        // Eliminar empresa (cambiar status a 3)
        $deleteData = array(
            'idCompany' => $companyId,
            'keyCompany' => ''
        );

        $deleted = $controller->deleteCompany($deleteData);

        if ($deleted) {
            echo json_encode(['success' => true, 'message' => 'Empresa eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la empresa']);
        }
    } catch (Exception $e) {
        error_log("Error eliminando empresa: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
    exit();
}

// PROCESAMIENTO PARA OBTENER DATOS DE EMPRESA
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'get_company_data') {
    header('Content-Type: application/json');

    $companyId = $_POST['company_id'];
    $company = $controller->getCompanyById($companyId);

    if ($company) {
        echo json_encode(['success' => true, 'company' => $company]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Empresa no encontrada']);
    }
    exit();
}

// Obtener todas las empresas activas
$companies = $controller->getClientCompanies(1);
$stats = $controller->getClientCompaniesStats();
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Compliance Hub - Empresas</title>
    <link rel="stylesheet" href="../../resources/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../../resources/plugins/datatables-bs4/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="../../resources/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../../resources/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../../resources/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="icon" href="../../resources/img/icono.png">
    <script src="../../resources/js/jquery-3.5.1.min.js"></script>

    <style>
        .main-header {
            background-color: #37424A !important;
        }

        .btn-accent {
            background-color: #37424A;
            border-color: #37424A;
            color: white;
        }

        .btn-accent:hover {
            background-color: #2c353b;
            border-color: #2c353b;
            color: white;
        }

        .card-header-accent {
            background: linear-gradient(135deg, #37424A 0%, #2c353b 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }

        .company-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(55, 66, 74, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .company-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(55, 66, 74, 0.15);
        }

        .company-card .card-body {
            padding: 25px;
        }

        .company-title {
            color: #37424A;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .company-rfc {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .company-info {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .company-actions {
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
        }

        .badge-tipo {
            font-size: 0.7rem;
            padding: 5px 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        .company-modal .modal-dialog {
            max-width: 1100px;
        }

        .company-modal .modal-header {
            background: linear-gradient(135deg, #37424A 0%, #2c353b 100%);
            border: none;
            border-radius: 15px 15px 0 0;
            padding: 20px 30px;
        }

        .company-modal .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(55, 66, 74, 0.15);
            overflow: hidden;
        }

        .company-modal .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .company-form-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid #37424A;
            transition: all 0.3s ease;
        }

        .company-form-section:hover {
            box-shadow: 0 5px 15px rgba(55, 66, 74, 0.08);
            transform: translateY(-2px);
        }

        .company-form-section h6 {
            color: #37424A;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }

        .company-form-section .form-control {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .company-form-section input.form-control {
            padding: 12px 15px;
        }

        .company-form-section select.form-control {
            padding: 8px 12px;
            height: auto;
            min-height: 42px;
        }

        .company-form-section .form-control:focus {
            border-color: #37424A;
            box-shadow: 0 0 0 3px rgba(55, 66, 74, 0.1);
            transform: translateY(-1px);
        }

        .required-asterisk {
            color: #dc3545;
            font-weight: bold;
        }

        .btn-add-company {
            background: linear-gradient(135deg, #37424A 0%, #2c353b 100%);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(55, 66, 74, 0.3);
        }

        .btn-add-company:hover {
            background: linear-gradient(135deg, #2c353b 0%, #37424A 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(55, 66, 74, 0.4);
            color: white;
        }

        .alert-info-custom {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border: none;
            border-radius: 10px;
            color: #37424A;
            border-left: 4px solid #37424A;
            padding: 15px 20px;
        }

        .company-form-section {
            animation: slideInUp 0.4s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .company-modal .modal-dialog {
                margin: 10px;
                max-width: calc(100% - 20px);
            }

            .company-form-section {
                padding: 20px 15px;
            }
        }

        .form-control.is-valid {
            border-color: #28a745;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.51.38a.74.74 0 0 0 1.04-.13l3.28-4.26a.75.75 0 1 0-1.18-.93L3.82 4.23 2.79 3.2a.75.75 0 1 0-1.06 1.06l.57.47z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6l.4.4M6.2 7.4l-.4-.4m.4-.4l-.4-.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(300px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* ESTILOS PARA CAMPOS ESPECÍFICOS DE FIDEICOMISO */
        .fideicomiso-section {
            border-left: 4px solid #17a2b8;
            background-color: #e3f2fd;
        }

        .fideicomiso-section h6 {
            color: #17a2b8;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper" style="padding-top: 57px;">
        <?php include "../templates/navbar.php"; ?>

        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                            <h1 class="m-0 text-dark">
                                <i class="fas fa-building text-primary mr-2"></i>
                                Gestión de Empresas
                            </h1>
                            <p class="text-muted mb-0">Administre las empresas registradas en el sistema</p>
                        </div>
                        <div class="col-sm-4 text-right">
                            <button type="button" class="btn btn-accent" id="btnAddCompany">
                                <i class="fas fa-plus mr-2"></i>Agregar Empresa
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <!-- ESTADÍSTICAS -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo $stats['total']; ?></h3>
                                    <p>Total Empresas</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-building"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo $stats['moral']; ?></h3>
                                    <p>Personas Morales</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-industry"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php echo $stats['fisica']; ?></h3>
                                    <p>Personas Físicas</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-secondary">
                                <div class="inner">
                                    <h3><?php echo $stats['fideicomiso']; ?></h3>
                                    <p>Fideicomisos</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-handshake"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FILTROS -->
                    <div class="filter-section">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Buscar empresa:</label>
                                <input type="text" id="searchCompany" class="form-control"
                                    placeholder="Nombre o RFC...">
                            </div>
                            <div class="col-md-3">
                                <label>Tipo de Persona:</label>
                                <select id="filterTipo" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="moral">Persona Moral</option>
                                    <option value="fisica">Persona Física</option>
                                    <option value="fideicomiso">Fideicomiso</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Ordenar por:</label>
                                <select id="sortBy" class="form-control">
                                    <option value="name">Nombre</option>
                                    <option value="rfc">RFC</option>
                                    <option value="date">Fecha Creación</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="button" id="clearFilters" class="btn btn-outline-secondary btn-block">
                                    <i class="fas fa-eraser"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- LISTADO DE EMPRESAS -->
                    <div class="row" id="companiesContainer">
                        <?php if (empty($companies)): ?>
                            <div class="col-12">
                                <div class="empty-state">
                                    <i class="fas fa-building"></i>
                                    <h4>No hay empresas registradas</h4>
                                    <p>Comience agregando la primera empresa al sistema</p>
                                    <button type="button" class="btn btn-accent btn-lg" id="btnAddFirstCompany">
                                        <i class="fas fa-plus mr-2"></i>Agregar Primera Empresa
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($companies as $company): ?>
                                <div class="col-lg-4 col-md-6 company-item"
                                    data-name="<?php echo strtolower($company['name_company']); ?>"
                                    data-rfc="<?php echo strtolower($company['rfc_company']); ?>"
                                    data-tipo="<?php echo $company['tipo_persona']; ?>"
                                    data-date="<?php echo $company['created_at_company']; ?>">
                                    <div class="company-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="company-title">
                                                    <?php echo htmlspecialchars($company['name_company']); ?></div>
                                                <span class="badge badge-tipo <?php
                                                echo $company['tipo_persona'] == 'moral' ? 'badge-primary' :
                                                    ($company['tipo_persona'] == 'fisica' ? 'badge-info' : 'badge-secondary');
                                                ?>">
                                                    <?php
                                                    echo $company['tipo_persona'] == 'moral' ? 'Moral' :
                                                        ($company['tipo_persona'] == 'fisica' ? 'Física' : 'Fideicomiso');
                                                    ?>
                                                </span>
                                            </div>

                                            <div class="company-rfc">
                                                <i class="fas fa-id-card mr-2"></i>
                                                <strong>RFC:</strong> <?php echo htmlspecialchars($company['rfc_company']); ?>
                                            </div>

                                            <div class="company-info">
                                                <div class="mb-1">
                                                    <i class="fas fa-file-alt mr-2"></i>
                                                    <strong>Razón Social:</strong>
                                                    <?php echo htmlspecialchars($company['razon_social']); ?>
                                                </div>

                                                <?php if ($company['ciudad'] || $company['estado']): ?>
                                                    <div class="mb-1">
                                                        <i class="fas fa-map-marker-alt mr-2"></i>
                                                        <strong>Ubicación:</strong>
                                                        <?php
                                                        echo htmlspecialchars(trim($company['ciudad'] . ', ' . $company['estado'], ', '));
                                                        ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($company['telefono']): ?>
                                                    <div class="mb-1">
                                                        <i class="fas fa-phone mr-2"></i>
                                                        <strong>Teléfono:</strong>
                                                        <?php echo htmlspecialchars($company['telefono']); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="mb-1">
                                                    <i class="fas fa-calendar mr-2"></i>
                                                    <strong>Registrada:</strong>
                                                    <?php echo date('d/m/Y', strtotime($company['created_at_company'])); ?>
                                                </div>
                                            </div>

                                            <div class="company-actions">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-info btn-view-company"
                                                        data-id="<?php echo $company['id_company']; ?>" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-warning btn-edit-company"
                                                        data-id="<?php echo $company['id_company']; ?>" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-delete-company"
                                                        data-id="<?php echo $company['id_company']; ?>"
                                                        data-name="<?php echo htmlspecialchars($company['name_company']); ?>"
                                                        title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA AGREGAR/EDITAR EMPRESA -->
    <div class="modal fade company-modal" id="companyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white" id="modalTitle">
                        <i class="fas fa-building mr-2"></i><span id="modalTitleText">Nueva Empresa</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="companyForm">
                    <input type="hidden" id="companyId" name="company_id">
                    <div class="modal-body">
                        <div class="alert alert-info-custom">
                            <i class="fas fa-lightbulb mr-2"></i>
                            Complete la información de la empresa. Los campos con <span
                                class="required-asterisk">*</span> son obligatorios.
                        </div>

                        <!-- Información Básica -->
                        <!-- Información Básica -->
                        <div class="company-form-section">
                            <h6><i class="fas fa-building"></i>Información General</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nombre Comercial <span class="required-asterisk">*</span></label>
                                        <input type="text" class="form-control" name="company[name_company]"
                                            id="name_company" required maxlength="100"
                                            placeholder="Inmobiliaria El Faro">
                                        <small class="form-text text-muted">Nombre comercial de la empresa</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>RFC <span class="required-asterisk">*</span></label>
                                        <input type="text" class="form-control" id="rfc_company"
                                            name="company[rfc_company]" required maxlength="13" minlength="12"
                                            placeholder="ABC123456789" style="text-transform: uppercase;">
                                        <small class="form-text text-muted">Registro Federal de Contribuyentes</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Razón Social <span class="required-asterisk">*</span></label>
                                        <input type="text" class="form-control" name="company[razon_social]"
                                            id="razon_social" required maxlength="150"
                                            placeholder="Inmobiliaria El Faro SA de CV">
                                        <small class="form-text text-muted">Denominación legal completa</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tipo de Persona <span class="required-asterisk">*</span></label>
                                        <select class="form-control" name="company[tipo_persona]" id="tipo_persona"
                                            required>
                                            <option value="">Seleccionar...</option>
                                            <option value="moral">Persona Moral</option>
                                            <option value="fisica">Persona Física con Actividad Empresarial</option>
                                            <option value="fideicomiso">Fideicomiso</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Información Legal -->
                        <div class="company-form-section">
                            <h6><i class="fas fa-calendar-alt"></i>Información Legal</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fecha de Constitución</label>
                                        <input type="date" class="form-control" name="company[fecha_constitucion]"
                                            id="fecha_constitucion" max="<?php echo date('Y-m-d'); ?>">
                                        <small class="form-text text-muted">Fecha de creación legal de la
                                            empresa</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Contacto -->
                        <div class="company-form-section">
                            <h6><i class="fas fa-phone"></i>Información de Contacto</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Teléfono Principal</label>
                                        <input type="tel" class="form-control" id="telefono" name="company[telefono]"
                                            maxlength="15" placeholder="8123456789">
                                        <small class="form-text text-muted">Número de contacto principal</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email Corporativo</label>
                                        <input type="email" class="form-control" name="company[email]" id="email"
                                            maxlength="100" placeholder="contacto@empresa.com">
                                        <small class="form-text text-muted">Correo electrónico principal</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dirección Fiscal -->
                        <div class="company-form-section">
                            <h6><i class="fas fa-map-marker-alt"></i>Dirección Fiscal</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Estado</label>
                                        <input type="text" class="form-control" name="company[estado]" id="estado"
                                            maxlength="50" placeholder="Nuevo León">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Ciudad/Municipio</label>
                                        <input type="text" class="form-control" name="company[ciudad]" id="ciudad"
                                            maxlength="50" placeholder="Monterrey">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Calle y Número</label>
                                        <input type="text" class="form-control" name="company[calle]" id="calle"
                                            maxlength="150" placeholder="Av. Constitución 123">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Colonia</label>
                                        <input type="text" class="form-control" name="company[colonia]" id="colonia"
                                            maxlength="100" placeholder="Centro">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Número Exterior</label>
                                        <input type="text" class="form-control" name="company[num_exterior]"
                                            id="num_exterior" maxlength="10" placeholder="123">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Número Interior</label>
                                        <input type="text" class="form-control" name="company[num_interior]"
                                            id="num_interior" maxlength="10" placeholder="A">
                                        <small class="form-text text-muted">Opcional</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Código Postal</label>
                                        <input type="text" class="form-control" id="codigo_postal"
                                            name="company[codigo_postal]" maxlength="5" pattern="[0-9]{5}"
                                            placeholder="64000">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Representante Legal / Apoderado (Para Persona Moral y Persona Física) -->
                        <div class="company-form-section" id="representante-section">
                            <h6><i class="fas fa-user-tie"></i>Representante Legal / Apoderado</h6>
                            <div class="alert alert-light border-left border-primary">
                                <small><i class="fas fa-info-circle text-primary mr-1"></i>
                                    Información de la persona autorizada para representar legalmente a la
                                    empresa</small>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nombre(s)</label>
                                        <input type="text" class="form-control" name="company[apoderado_nombre]"
                                            id="apoderado_nombre" maxlength="50" placeholder="Juan Carlos"
                                            style="text-transform: capitalize;">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Apellido Paterno</label>
                                        <input type="text" class="form-control"
                                            name="company[apoderado_apellido_paterno]" id="apoderado_apellido_paterno"
                                            maxlength="50" placeholder="García" style="text-transform: capitalize;">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Apellido Materno</label>
                                        <input type="text" class="form-control"
                                            name="company[apoderado_apellido_materno]" id="apoderado_apellido_materno"
                                            maxlength="50" placeholder="López" style="text-transform: capitalize;">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>RFC del Representante</label>
                                        <input type="text" class="form-control" id="apoderado_rfc"
                                            name="company[apoderado_rfc]" maxlength="13"
                                            pattern="^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$" placeholder="GALO800101ABC"
                                            style="text-transform: uppercase;">
                                        <small class="form-text text-muted">RFC de la persona física (13
                                            caracteres)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>CURP del Representante</label>
                                        <input type="text" class="form-control" id="apoderado_curp"
                                            name="company[apoderado_curp]" maxlength="18"
                                            pattern="^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9A-Z][0-9]$"
                                            placeholder="GALO800101HDFRPN09" style="text-transform: uppercase;">
                                        <small class="form-text text-muted">Clave Única de Registro de Población (18
                                            caracteres)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Fideicomiso (Solo para Fideicomisos) -->
                        <div class="company-form-section fideicomiso-section" id="fideicomiso-section"
                            style="display: none;">
                            <h6><i class="fas fa-handshake"></i>Información del Fideicomiso</h6>
                            <div class="alert alert-info">
                                <small><i class="fas fa-info-circle mr-1"></i>
                                    Complete la información específica del fideicomiso</small>
                            </div>

                            <!-- Fiduciario -->
                            <div class="row">
                                <div class="col-md-12">
                                    <h6 class="text-info"><i class="fas fa-university mr-2"></i>Fiduciario</h6>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nombre del Fiduciario</label>
                                        <input type="text" class="form-control" name="company[fiduciario_nombre]"
                                            id="fiduciario_nombre" maxlength="100"
                                            placeholder="Banco Nacional de México SA">
                                        <small class="form-text text-muted">Institución fiduciaria</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>RFC del Fiduciario</label>
                                        <input type="text" class="form-control" id="fiduciario_rfc"
                                            name="company[fiduciario_rfc]" maxlength="13" placeholder="BNM840315PE6"
                                            style="text-transform: uppercase;">
                                    </div>
                                </div>
                            </div>

                            <!-- Fideicomitente -->
                            <div class="row">
                                <div class="col-md-12">
                                    <h6 class="text-info"><i class="fas fa-user-check mr-2"></i>Fideicomitente</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nombre(s)</label>
                                        <input type="text" class="form-control" name="company[fideicomitente_nombre]"
                                            id="fideicomitente_nombre" maxlength="50" placeholder="María Elena">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Apellido Paterno</label>
                                        <input type="text" class="form-control"
                                            name="company[fideicomitente_apellido_paterno]"
                                            id="fideicomitente_apellido_paterno" maxlength="50" placeholder="Rodríguez">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Apellido Materno</label>
                                        <input type="text" class="form-control"
                                            name="company[fideicomitente_apellido_materno]"
                                            id="fideicomitente_apellido_materno" maxlength="50" placeholder="Flores">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>RFC del Fideicomitente</label>
                                        <input type="text" class="form-control" id="fideicomitente_rfc"
                                            name="company[fideicomitente_rfc]" maxlength="13"
                                            placeholder="ROFM800315AB2" style="text-transform: uppercase;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>CURP del Fideicomitente</label>
                                        <input type="text" class="form-control" id="fideicomitente_curp"
                                            name="company[fideicomitente_curp]" maxlength="18"
                                            placeholder="ROFM800315MDFDRR04" style="text-transform: uppercase;">
                                    </div>
                                </div>
                            </div>

                            <!-- Fideicomisario -->
                            <div class="row">
                                <div class="col-md-12">
                                    <h6 class="text-info"><i class="fas fa-user-friends mr-2"></i>Fideicomisario
                                        Principal</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nombre(s)</label>
                                        <input type="text" class="form-control" name="company[fideicomisario_nombre]"
                                            id="fideicomisario_nombre" maxlength="50" placeholder="Carlos Alberto">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Apellido Paterno</label>
                                        <input type="text" class="form-control"
                                            name="company[fideicomisario_apellido_paterno]"
                                            id="fideicomisario_apellido_paterno" maxlength="50" placeholder="Méndez">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Apellido Materno</label>
                                        <input type="text" class="form-control"
                                            name="company[fideicomisario_apellido_materno]"
                                            id="fideicomisario_apellido_materno" maxlength="50" placeholder="Silva">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>RFC del Fideicomisario</label>
                                        <input type="text" class="form-control" id="fideicomisario_rfc"
                                            name="company[fideicomisario_rfc]" maxlength="13"
                                            placeholder="MESC750820CD1" style="text-transform: uppercase;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>CURP del Fideicomisario</label>
                                        <input type="text" class="form-control" id="fideicomisario_curp"
                                            name="company[fideicomisario_curp]" maxlength="18"
                                            placeholder="MESC750820HDFLRL09" style="text-transform: uppercase;">
                                    </div>
                                </div>
                            </div>

                            <!-- Número de Fideicomiso -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Número de Fideicomiso</label>
                                        <input type="text" class="form-control" name="company[numero_fideicomiso]"
                                            id="numero_fideicomiso" maxlength="20" placeholder="F/12345">
                                        <small class="form-text text-muted">Número de identificación del
                                            fideicomiso</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fecha de Constitución del Fideicomiso</label>
                                        <input type="date" class="form-control" name="company[fecha_fideicomiso]"
                                            id="fecha_fideicomiso" max="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-add-company" id="saveCompanyBtn">
                            <i class="fas fa-save mr-1"></i><span id="btnText">Crear Empresa</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL PARA VER DETALLES DE EMPRESA -->
    <div class="modal fade" id="viewCompanyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header card-header-accent">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-eye mr-2"></i>Detalles de la Empresa
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="companyDetailsContent">
                    <!-- El contenido se carga dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="../../resources/plugins/jquery/jquery.min.js"></script>
    <script src="../../resources/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../resources/dist/js/adminlte.min.js"></script>
    <script src="../../resources/js/notifications.js"></script>

    <script>
        $(document).ready(function () {
            let isEditMode = false;

            // ABRIR MODAL PARA AGREGAR EMPRESA
            $('#btnAddCompany, #btnAddFirstCompany').on('click', function () {
                resetForm();
                isEditMode = false;
                $('#modalTitleText').text('Nueva Empresa');
                $('#btnText').text('Crear Empresa');
                $('#companyModal').modal('show');
            });

            // MOSTRAR/OCULTAR CAMPOS SEGÚN TIPO DE PERSONA
            $('#tipo_persona').on('change', function () {
                var tipo = $(this).val();

                if (tipo === 'fideicomiso') {
                    $('#representante-section').hide();
                    $('#fideicomiso-section').show();
                } else {
                    $('#fideicomiso-section').hide();
                    $('#representante-section').show();
                }
            });

            // VALIDACIONES EN TIEMPO REAL
            $('#rfc_company').on('input', function () {
                var rfc = $(this).val().toUpperCase();
                $(this).val(rfc);

                if (rfc.length >= 12) {
                    var rfcPattern = /^[A-Z]{3,4}[0-9]{6}[A-Z0-9]{3}$/;
                    if (rfcPattern.test(rfc)) {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                    } else {
                        $(this).removeClass('is-valid').addClass('is-invalid');
                    }
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });

            // Validación para RFCs de personas físicas (representantes y fideicomiso)
            $('#apoderado_rfc, #fideicomitente_rfc, #fideicomisario_rfc').on('input', function () {
                var rfc = $(this).val().toUpperCase();
                $(this).val(rfc);

                if (rfc.length === 13) {
                    var rfcPattern = /^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$/;
                    if (rfcPattern.test(rfc)) {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                    } else {
                        $(this).removeClass('is-valid').addClass('is-invalid');
                    }
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });

            // Validación para RFC del fiduciario (persona moral)
            $('#fiduciario_rfc').on('input', function () {
                var rfc = $(this).val().toUpperCase();
                $(this).val(rfc);

                if (rfc.length >= 12) {
                    var rfcPattern = /^[A-Z]{3,4}[0-9]{6}[A-Z0-9]{3}$/;
                    if (rfcPattern.test(rfc)) {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                    } else {
                        $(this).removeClass('is-valid').addClass('is-invalid');
                    }
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });

            // Validación para CURPs
            $('#apoderado_curp, #fideicomitente_curp, #fideicomisario_curp').on('input', function () {
                var curp = $(this).val().toUpperCase();
                $(this).val(curp);

                if (curp.length === 18) {
                    var curpPattern = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9A-Z][0-9]$/;
                    if (curpPattern.test(curp)) {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                    } else {
                        $(this).removeClass('is-valid').addClass('is-invalid');
                    }
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });

            $('#codigo_postal, #telefono').on('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // ENVÍO DEL FORMULARIO
            $('#companyForm').on('submit', function (e) {
                e.preventDefault();

                var saveBtn = $('#saveCompanyBtn');
                var originalText = saveBtn.html();
                saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                var action = isEditMode ? 'update_company' : 'create_company';
                var formData = $(this).serialize() + '&ajax_action=' + action;

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function (response) {
                        if (response && response.success) {
                            showNotification('success', response.message);
                            $('#companyModal').modal('hide');

                            // Recargar la página para mostrar los cambios
                            setTimeout(function () {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showNotification('error', response.message || 'Error al procesar la solicitud');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error AJAX:', error);
                        showNotification('error', 'Error al comunicarse con el servidor');
                    },
                    complete: function () {
                        saveBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // EDITAR EMPRESA
            $('.btn-edit-company').on('click', function () {
                var companyId = $(this).data('id');
                loadCompanyDataAndEdit(companyId);
            });

            // VER DETALLES DE EMPRESA
            $('.btn-view-company').on('click', function () {
                var companyId = $(this).data('id');
                loadCompanyDetails(companyId);
            });

            // ELIMINAR EMPRESA
            $('.btn-delete-company').on('click', function () {
                var companyId = $(this).data('id');
                var companyName = $(this).data('name');
                confirmDeleteCompany(companyId, companyName);
            });

            // FILTROS
            $('#searchCompany').on('input', function () {
                filterCompanies();
            });

            $('#filterTipo').on('change', function () {
                filterCompanies();
            });

            $('#sortBy').on('change', function () {
                sortCompanies();
            });

            $('#clearFilters').on('click', function () {
                $('#searchCompany').val('');
                $('#filterTipo').val('');
                $('#sortBy').val('name');
                filterCompanies();
            });

            // FUNCIONES AUXILIARES
            function resetForm() {
                $('#companyForm')[0].reset();
                $('#companyId').val('');
                $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                $('#representante-section').show();
                $('#fideicomiso-section').hide();
            }

            function loadCompanyDataAndEdit(companyId) {
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        ajax_action: 'get_company_data',
                        company_id: companyId
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response && response.success) {
                            isEditMode = true;
                            $('#modalTitleText').text('Editar Empresa');
                            $('#btnText').text('Actualizar Empresa');

                            // Llenar el formulario con los datos
                            var company = response.company;
                            $('#companyId').val(company.id_company);
                            $('#name_company').val(company.name_company);
                            $('#rfc_company').val(company.rfc_company);
                            $('#razon_social').val(company.razon_social);
                            $('#tipo_persona').val(company.tipo_persona);
                            $('#fecha_constitucion').val(company.fecha_constitucion);
                            $('#telefono').val(company.telefono);
                            $('#email').val(company.email);
                            $('#estado').val(company.estado);
                            $('#ciudad').val(company.ciudad);
                            $('#calle').val(company.calle);
                            $('#colonia').val(company.colonia);
                            $('#num_exterior').val(company.num_exterior);
                            $('#num_interior').val(company.num_interior);
                            $('#codigo_postal').val(company.codigo_postal);

                            // Campos de representante
                            $('#apoderado_nombre').val(company.apoderado_nombre);
                            $('#apoderado_apellido_paterno').val(company.apoderado_apellido_paterno);
                            $('#apoderado_apellido_materno').val(company.apoderado_apellido_materno);
                            $('#apoderado_rfc').val(company.apoderado_rfc);
                            $('#apoderado_curp').val(company.apoderado_curp);

                            // Campos de fideicomiso
                            $('#fiduciario_nombre').val(company.fiduciario_nombre);
                            $('#fiduciario_rfc').val(company.fiduciario_rfc);
                            $('#fideicomitente_nombre').val(company.fideicomitente_nombre);
                            $('#fideicomitente_apellido_paterno').val(company.fideicomitente_apellido_paterno);
                            $('#fideicomitente_apellido_materno').val(company.fideicomitente_apellido_materno);
                            $('#fideicomitente_rfc').val(company.fideicomitente_rfc);
                            $('#fideicomitente_curp').val(company.fideicomitente_curp);
                            $('#fideicomisario_nombre').val(company.fideicomisario_nombre);
                            $('#fideicomisario_apellido_paterno').val(company.fideicomisario_apellido_paterno);
                            $('#fideicomisario_apellido_materno').val(company.fideicomisario_apellido_materno);
                            $('#fideicomisario_rfc').val(company.fideicomisario_rfc);
                            $('#fideicomisario_curp').val(company.fideicomisario_curp);
                            $('#numero_fideicomiso').val(company.numero_fideicomiso);
                            $('#fecha_fideicomiso').val(company.fecha_fideicomiso);

                            // Mostrar/ocultar secciones según tipo
                            if (company.tipo_persona === 'fideicomiso') {
                                $('#representante-section').hide();
                                $('#fideicomiso-section').show();
                            } else {
                                $('#fideicomiso-section').hide();
                                $('#representante-section').show();
                            }

                            $('#companyModal').modal('show');
                        } else {
                            showNotification('error', response.message || 'Error al cargar los datos de la empresa');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error cargando empresa:', error);
                        showNotification('error', 'Error al comunicarse con el servidor');
                    }
                });
            }

            function loadCompanyDetails(companyId) {
                $('#companyDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando...</p></div>');
                $('#viewCompanyModal').modal('show');

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        ajax_action: 'get_company_data',
                        company_id: companyId
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response && response.success) {
                            var company = response.company;
                            var html = createCompanyDetailsHTML(company);
                            $('#companyDetailsContent').html(html);
                        } else {
                            $('#companyDetailsContent').html('<div class="alert alert-danger">Error al cargar los datos</div>');
                        }
                    },
                    error: function () {
                        $('#companyDetailsContent').html('<div class="alert alert-danger">Error al comunicarse con el servidor</div>');
                    }
                });
            }

            function createCompanyDetailsHTML(company) {
                var tipoPersonaText = '';
                if (company.tipo_persona === 'moral') {
                    tipoPersonaText = 'Persona Moral';
                } else if (company.tipo_persona === 'fisica') {
                    tipoPersonaText = 'Persona Física con Actividad Empresarial';
                } else if (company.tipo_persona === 'fideicomiso') {
                    tipoPersonaText = 'Fideicomiso';
                }

                var html = `
                <div class="company-details">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-building text-primary mr-2"></i>Información General</h6>
                            <table class="table table-borderless">
                                <tr><td><strong>Nombre:</strong></td><td>${company.name_company || '-'}</td></tr>
                                <tr><td><strong>RFC:</strong></td><td>${company.rfc_company || '-'}</td></tr>
                                <tr><td><strong>Razón Social:</strong></td><td>${company.razon_social || '-'}</td></tr>
                                <tr><td><strong>Tipo:</strong></td><td>${tipoPersonaText}</td></tr>
                                <tr><td><strong>Fecha Constitución:</strong></td><td>${company.fecha_constitucion ? new Date(company.fecha_constitucion).toLocaleDateString('es-MX') : '-'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-phone text-primary mr-2"></i>Contacto</h6>
                            <table class="table table-borderless">
                                <tr><td><strong>Teléfono:</strong></td><td>${company.telefono || '-'}</td></tr>
                                <tr><td><strong>Email:</strong></td><td>${company.email || '-'}</td></tr>
                            </table>
                            
                            <h6><i class="fas fa-map-marker-alt text-primary mr-2"></i>Ubicación</h6>
                            <table class="table table-borderless">
                                <tr><td><strong>Estado:</strong></td><td>${company.estado || '-'}</td></tr>
                                <tr><td><strong>Ciudad:</strong></td><td>${company.ciudad || '-'}</td></tr>
                                <tr><td><strong>Dirección:</strong></td><td>${[company.calle, company.num_exterior, company.num_interior].filter(Boolean).join(' ') || '-'}</td></tr>
                                <tr><td><strong>Colonia:</strong></td><td>${company.colonia || '-'}</td></tr>
                                <tr><td><strong>C.P.:</strong></td><td>${company.codigo_postal || '-'}</td></tr>
                            </table>
                        </div>
                    </div>
            `;

                // Agregar información específica según el tipo
                if (company.tipo_persona === 'fideicomiso') {
                    html += `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6><i class="fas fa-handshake text-primary mr-2"></i>Información del Fideicomiso</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr><td><strong>Número:</strong></td><td>${company.numero_fideicomiso || '-'}</td></tr>
                                        <tr><td><strong>Fecha Fideicomiso:</strong></td><td>${company.fecha_fideicomiso ? new Date(company.fecha_fideicomiso).toLocaleDateString('es-MX') : '-'}</td></tr>
                                        <tr><td><strong>Fiduciario:</strong></td><td>${company.fiduciario_nombre || '-'}</td></tr>
                                        <tr><td><strong>RFC Fiduciario:</strong></td><td>${company.fiduciario_rfc || '-'}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr><td><strong>Fideicomitente:</strong></td><td>${[company.fideicomitente_nombre, company.fideicomitente_apellido_paterno, company.fideicomitente_apellido_materno].filter(Boolean).join(' ') || '-'}</td></tr>
                                        <tr><td><strong>RFC Fideicomitente:</strong></td><td>${company.fideicomitente_rfc || '-'}</td></tr>
                                        <tr><td><strong>Fideicomisario:</strong></td><td>${[company.fideicomisario_nombre, company.fideicomisario_apellido_paterno, company.fideicomisario_apellido_materno].filter(Boolean).join(' ') || '-'}</td></tr>
                                        <tr><td><strong>RFC Fideicomisario:</strong></td><td>${company.fideicomisario_rfc || '-'}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                } else if (company.apoderado_nombre || company.apoderado_apellido_paterno) {
                    html += `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6><i class="fas fa-user-tie text-primary mr-2"></i>Representante Legal</h6>
                            <table class="table table-borderless">
                                <tr><td><strong>Nombre:</strong></td><td>${[company.apoderado_nombre, company.apoderado_apellido_paterno, company.apoderado_apellido_materno].filter(Boolean).join(' ') || '-'}</td></tr>
                                <tr><td><strong>RFC:</strong></td><td>${company.apoderado_rfc || '-'}</td></tr>
                                <tr><td><strong>CURP:</strong></td><td>${company.apoderado_curp || '-'}</td></tr>
                            </table>
                        </div>
                    </div>
                `;
                }

                html += '</div>';
                return html;
            }

            function confirmDeleteCompany(companyId, companyName) {
                if (confirm(`¿Está seguro de que desea eliminar la empresa "${companyName}"?\n\nEsta acción no se puede deshacer.`)) {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        data: {
                            ajax_action: 'delete_company',
                            company_id: companyId
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response && response.success) {
                                showNotification('success', response.message);
                                setTimeout(function () {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                showNotification('error', response.message || 'Error al eliminar la empresa');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error eliminando empresa:', error);
                            showNotification('error', 'Error al comunicarse con el servidor');
                        }
                    });
                }
            }

            function filterCompanies() {
                var searchTerm = $('#searchCompany').val().toLowerCase();
                var filterTipo = $('#filterTipo').val();

                $('.company-item').each(function () {
                    var $item = $(this);
                    var name = $item.data('name');
                    var rfc = $item.data('rfc');
                    var tipo = $item.data('tipo');

                    var matchesSearch = name.includes(searchTerm) || rfc.includes(searchTerm);
                    var matchesTipo = !filterTipo || tipo === filterTipo;

                    if (matchesSearch && matchesTipo) {
                        $item.show();
                    } else {
                        $item.hide();
                    }
                });
            }

            function sortCompanies() {
                var sortBy = $('#sortBy').val();
                var $container = $('#companiesContainer');
                var $items = $('.company-item').get();

                $items.sort(function (a, b) {
                    var aVal, bVal;

                    switch (sortBy) {
                        case 'name':
                            aVal = $(a).data('name');
                            bVal = $(b).data('name');
                            break;
                        case 'rfc':
                            aVal = $(a).data('rfc');
                            bVal = $(b).data('rfc');
                            break;
                        case 'date':
                            aVal = $(a).data('date');
                            bVal = $(b).data('date');
                            break;
                        default:
                            return 0;
                    }

                    return aVal.localeCompare(bVal);
                });

                $.each($items, function (index, item) {
                    $container.append(item);
                });
            }

            function showNotification(type, message) {
                var bgClass = type === 'success' ? 'alert-success' : 'alert-danger';
                var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';

                var notification = $(`
                <div class="alert ${bgClass} notification" role="alert">
                    <i class="fas ${icon} mr-2"></i>
                    ${message}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            `);

                $('body').append(notification);

                setTimeout(function () {
                    notification.fadeOut(500, function () {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    </script>
</body>

</html>
