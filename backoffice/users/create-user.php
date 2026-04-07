<?php
session_start();
include "../../app/config.php";
include "../../app/FileController.php";
include "../../app/WebController.php";
$controller = new WebController();
$files = new FileController();

// Verificar si la sesión del usuario está activa
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    session_destroy();
    header("Location: ../../login.php");
    exit();
}

// COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMIN (1)
if ($_SESSION['user']['id_type_user'] != 1) {
    header('location: users.php');
}

// Llama a la función getUserTypes() en el controlador para obtener los tipos de usuarios disponibles.
$userTypes = $controller->getUserTypes();
$companies = $controller->getSystemCompanies(1);

function uploadFilePhoto($folio, $filename = null)
{
    global $files;
    $filename['imguser'] = $files->upload($folio, $_FILES['file-imguser'], "ord.imguser");
    return $filename;
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
    $companyId = $controller->createCompany($_POST['company']);

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
            'keyCompany' => '' // Si no usas key para empresas, déjalo vacío
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

// PROCESAMIENTO PARA ACTUALIZAR EMPRESA - CORREGIDO
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

if (!empty($_POST['action'])) {
    if ($_POST['action'] == 'create') {
        // Validaciones existentes...
        $emailUser = $controller->getEmailUser($_POST['user']['email_user']);
        $phoneUser = $controller->getPhoneUser($_POST['user']['phone_user']);
        $rfcUser = $controller->getRFCUser($_POST['user']['rfc_user']);

        // NUEVA VALIDACIÓN: Si es cliente empresa, debe tener empresa asignada
        if ($_POST['user']['id_type_user'] == 3 && empty($_POST['user']['id_company'])) {
            $mssg = "¡LOS USUARIOS DE TIPO CLIENTE EMPRESA DEBEN TENER UNA EMPRESA ASIGNADA!";
        }
        // Validaciones existentes de email, teléfono, RFC...
        else if (!empty($emailUser)) {
            $mssg = "¡EL CORREO ELECTRÓNICO YA ESTÁ EN USO POR UN USUARIO ACTIVO. INTENTA CON OTRO!";
        } else if (!empty($phoneUser)) {
            $mssg = "¡EL NÚMERO DE TELÉFONO ESTÁ EN USO POR UN USUARIO ACTIVO, INTENTA CON OTRO!";
        } else if (!empty($rfcUser)) {
            $mssg = "¡EL RFC YA SE ENCUENTRA REGISTRADO, INTENTA CON OTRO!";
        } else {
            $userId = $controller->createUser($_POST['user']);
            if ($userId) {
                $files = uploadFilePhoto($_POST['user']['key_user']);
                $idUserNotification = $controller->createNotifications($userId, 0);
                $idUser = $controller->updatePhotoUser($userId, $files);
                if ($idUser) {
                    header('location: users.php');
                } else {
                    $mssg = "HA HABIDO UN PROBLEMA CON EL REGISTRO, INTENTA DE NUEVO";
                }
            }
        }
    }
}




$permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$clave = substr(str_shuffle($permitted_chars), 0, 5);
$usr_rfc = substr(str_shuffle($permitted_chars), 0, 13);
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
    <link rel="stylesheet" href="../../resources/css/cropper.min.css" />

    <link rel="icon" href="../../resources/img/icono.png">
    <script src="../../resources/js/jquery-3.5.1.min.js"></script>
    <style>
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            transition: transform 0.2s, font-size 0.2s;
        }

        .password-toggle:hover {
            color: #007bff;
        }

        .password-toggle.clicked {
            transform: scale(1.2);
        }

        #password {
            padding-right: 30px;
        }

        /* ESTILOS PARA EMPRESA */
        #empresa-section {
            border-left: 4px solid #007bff;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin: 10px 0;
        }

        .animate-in {
            animation: slideIn 0.3s ease-in-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .custom-notification {
            border-radius: 5px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .add-company-option {
            background-color: #e3f2fd;
            color: #1976d2;
            font-weight: bold;
            border-top: 2px solid #2196f3;
        }

        .add-company-option:hover {
            background-color: #bbdefb !important;
        }

        /* Estilos para el modal de empresa */
        .company-modal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .required-field {
            color: #dc3545;
        }

        .form-group.has-error .form-control {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }



        /* ESTILOS MEJORADOS PARA MODAL DE EMPRESA - TEMA #37424A */
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

        /* Padding específico solo para inputs de texto */
        .company-form-section input.form-control {
            padding: 12px 15px;
        }

        /* Padding específico para selects */
        .company-form-section select.form-control {
            padding: 8px 12px;
            height: auto;
            min-height: 42px;
        }

        /* Padding específico para textareas */
        .company-form-section textarea.form-control {
            padding: 12px 15px;
        }

        .company-form-section .form-control:focus {
            border-color: #37424A;
            box-shadow: 0 0 0 3px rgba(55, 66, 74, 0.1);
            transform: translateY(-1px);
        }

        .company-form-section .form-control.is-valid {
            border-color: #28a745;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.51.38a.74.74 0 0 0 1.04-.13l3.28-4.26a.75.75 0 1 0-1.18-.93L3.82 4.23 2.79 3.2a.75.75 0 1 0-1.06 1.06l.57.47z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .company-form-section .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6l.4.4M6.2 7.4l-.4-.4m.4-.4l-.4-.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
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

        .btn-add-company:disabled {
            background: #9ca3af;
            box-shadow: none;
            transform: none;
        }

        .modal-footer {
            border: none;
            padding: 20px 30px;
            background: #f8fafc;
            border-radius: 0 0 15px 15px;
        }

        .alert-info-custom {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border: none;
            border-radius: 10px;
            color: #37424A;
            border-left: 4px solid #37424A;
            padding: 15px 20px;
        }

        .form-text {
            font-size: 0.8rem;
            color: #6b7280;
        }

        /* Botón del input-group para abrir modal */
        .btn-outline-primary {
            border-color: #37424A;
            color: #37424A;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background-color: #37424A;
            border-color: #37424A;
            color: white;
        }

        .btn-outline-primary:focus {
            box-shadow: 0 0 0 0.2rem rgba(55, 66, 74, 0.25);
        }

        /* Animaciones */
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

        /* Preview de empresa */
        #company-preview {
            border-left: 4px solid #28a745 !important;
            animation: fadeIn 0.3s ease-in;
            background: linear-gradient(135deg, #f0fff4 0%, #f7fafc 100%);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* Badge en el preview */
        .badge-primary {
            background-color: #37424A !important;
        }

        /* Estilos para la sección de empresa en el formulario principal */

        #empresa-section {
            border-left: 4px solid #007bff;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin: 10px 0;
        }

        #empresa-section label {
            color: #37424A;
            font-weight: 600;
        }

        /* Notificaciones personalizadas */
        .custom-notification {
            border-radius: 5px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .custom-notification.alert-info {
            background-color: #37424A;
            color: white;
            border-left: 4px solid #2c353b;
        }

        .custom-notification.alert-success {
            background-color: #28a745;
            color: white;
            border-left: 4px solid #1e7e34;
        }

        .custom-notification.alert-danger {
            background-color: #dc3545;
            color: white;
            border-left: 4px solid #c82333;
        }

        .custom-notification i {
            margin-right: 8px;
        }

        /* Animación para campos de empresa */
        .animate-in {
            animation: slideIn 0.3s ease-in-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mejoras responsive */
        @media (max-width: 768px) {
            .company-modal .modal-dialog {
                margin: 10px;
                max-width: calc(100% - 20px);
            }

            .company-form-section {
                padding: 20px 15px;
            }

            .modal-body {
                padding: 20px 15px;
            }
        }

        /* Estilos para select2 en tema #37424A */
        .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
            background-color: #37424A !important;
            color: white !important;
        }

        .select2-container--bootstrap4 .select2-selection--single:focus {
            border-color: #37424A !important;
            box-shadow: 0 0 0 0.2rem rgba(55, 66, 74, 0.25) !important;
        }

        /* Consistencia con botones del sistema */
        .btn-light:hover {
            background-color: #f8f9fa;
            border-color: #37424A;
            color: #37424A;
        }

        /* Input group addon consistency */
        .input-group-append .btn {
            border-left: none;
        }

        /* Focus states consistency */
        .form-control:focus {
            border-color: #37424A;
            box-shadow: 0 0 0 0.2rem rgba(55, 66, 74, 0.25);
        }

        /* Alert borders for consistency */
        .alert-light {
            border-left: 4px solid #37424A;
        }



        /* Estilos para opciones de gestión en el select */
        .management-option {
            font-weight: bold;
            font-style: italic;
        }

        .add-option {
            background-color: #e8f5e8 !important;
            color: #28a745 !important;
        }

        .edit-option {
            background-color: #fff3cd !important;
            color: #856404 !important;
        }

        .delete-option {
            background-color: #f8d7da !important;
            color: #721c24 !important;
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
                            <h1 class="m-0 text-dark">Agregar usuario</h1>
                        </div>
                        <div class="col-sm-4 text-right">
                            <a href="users.php" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;"
                                role="button" aria-pressed="true">Regresar</a>
                        </div>
                    </div>
                    <hr>
                    <?php if (!empty($mssg)) { ?>
                        <div class="row">
                            <div class="col-12 pt-3">
                                <div class="alert alert-dismissible alert-danger p-4">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><?php echo htmlspecialchars($mssg); ?></h5>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="card" style="height: 100%">
                                <div class="card-body">
                                    <form action="#" method="post" enctype="multipart/form-data" id="userForm">
                                        <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                            <div class="col-md-12">

                                                <!--Campo de Clave / key_user-->
                                                <div class="form-group" style="display: none;" hidden>
                                                    <input name="user[key_user]" type="text" class="form-control"
                                                        id="key_user" required value="USR-<?php echo $clave; ?>" readonly>
                                                </div>

                                                <!--Campo de Nombre / name_user-->
                                                <div class="form-group">
                                                    <label for="name_user">Nombre del usuario</label>
                                                    <input name="user[name_user]" type="text" class="form-control validate"
                                                        id="name_user" required
                                                        title="Utiliza solo letras como mínimo 3 y máximo 40"
                                                        pattern="[a-zA-ZñÑáÁéÉíÍóÓúÚ ]{3,40}" maxlength="40" minlength="3"
                                                        value="<?php echo isset($_POST['user']['name_user']) ? htmlspecialchars($_POST['user']['name_user']) : ''; ?>"
                                                        autocomplete="off">
                                                    <small class="error-msg" style="color:red; display: none;">*Utiliza solo
                                                        letras como mínimo 3 y máximo 40 caracteres</small>
                                                </div>

                                                <!--Campo de RFC / rfc_user - CÓDIGO NUEVO PARA GENERAR DINAMICAMENTE EL RFC-->
                                                <div class="form-group" style="display: none;" hidden>
                                                    <input name="user[rfc_user]" type="text" class="form-control"
                                                        id="rfc_user" required readonly
                                                        value="<?php echo isset($_POST['user']['rfc_user']) ? htmlspecialchars($_POST['user']['rfc_user']) : $usr_rfc; ?>"
                                                        autocomplete="off">
                                                </div>

                                                <!--Campo de Tipo de USUARIO / id_type_user -->
                                                <div class="form-group">
                                                    <label for="id_type_user">Tipo de usuario</label>
                                                    <select name="user[id_type_user]" id="id_type_user"
                                                        class="form-control form-control-md selectTypeUser" required>
                                                        <option value="">--</option>
                                                        <?php foreach ($userTypes as $key => $value) { ?>
                                                            <option value="<?php echo $value['id_type']; ?>" <?php echo isset($_POST['user']['id_type_user']) && $_POST['user']['id_type_user'] == $value['id_type'] ? 'selected' : ''; ?>>
                                                                <?php echo $value['name_type']; ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>

                                                <!-- NUEVA SECCIÓN DE EMPRESA CON OPCIÓN DE AGREGAR -->
                                                <div class="form-group" id="empresa-section" style="display: none;">
                                                    <label for="id_company">Empresa: <span
                                                            class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <select name="user[id_company]" class="form-control"
                                                            id="id_company">
                                                            <option value="">Seleccionar empresa...</option>

                                                            <!-- EMPRESAS NORMALES -->
                                                            <?php foreach ($companies as $company) { ?>
                                                                <option value="<?php echo $company['id_company']; ?>">
                                                                    <?php echo htmlspecialchars($company['name_company'] . ' (' . $company['rfc_company'] . ')'); ?>
                                                                </option>
                                                            <?php } ?>

                                                            <!-- SEPARADOR -->
                                                            <option disabled>──────────────────</option>

                                                            <!-- OPCIONES DE GESTIÓN -->
                                                            <option value="add_new" class="management-option add-option">
                                                                Agregar Nueva Empresa
                                                            </option>
                                                            <option value="edit_company"
                                                                class="management-option edit-option">
                                                                Editar Empresa Existente
                                                            </option>
                                                            <option value="delete_company"
                                                                class="management-option delete-option">
                                                                Eliminar Empresa
                                                            </option>
                                                        </select>
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-primary"
                                                                id="btnAddCompany" title="Agregar nueva empresa">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">Seleccione la empresa a la que
                                                        pertenece el usuario</small>
                                                </div>



                                                <!-- Campo de Fotografía -->
                                                <div class="form-group">
                                                    <label for="photo_user">Fotografía <small>(*Opcional)</small></label>
                                                    <input type="file" class="form-control" name="file-imguser"
                                                        id="photo_user" accept="image/*">
                                                </div>

                                                <!-- Modal para recorte de imagen -->
                                                <div id="cropperModal" class="modal" tabindex="-1" role="dialog"
                                                    data-backdrop="static">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Recortar Fotografía</h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body text-center">
                                                                <div>
                                                                    <img id="imagePreview"
                                                                        style="max-width: 100%; max-height: 400px;" />
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" id="cropImage"
                                                                    class="btn btn-primary">Recortar y Guardar</button>
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-dismiss="modal">Cancelar</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!--Campo de Teléfono / phone_user-->
                                                <div class="form-group">
                                                    <label for="phone_user">Número de teléfono</label>
                                                    <input name="user[phone_user]" type="text" class="form-control validate"
                                                        id="phone_user" required
                                                        title="Utiliza solo números. El número de teléfono debe tener 10 caracteres. Ejemplo: 8182597869"
                                                        pattern="[0-9]{10}" maxlength="10" minlength="10"
                                                        value="<?php echo isset($_POST['user']['phone_user']) ? htmlspecialchars($_POST['user']['phone_user']) : ''; ?>"
                                                        autocomplete="off">
                                                    <small class="error-msg" style="color:red; display: none;">*Utiliza solo
                                                        números. El número de teléfono debe tener 10 caracteres</small>
                                                </div>

                                                <!--Campo de Correo Eletrónico / email_user-->
                                                <div class="form-group">
                                                    <label for="email_user">Correo electrónico</label>
                                                    <input name="user[email_user]" type="email"
                                                        class="form-control validate" id="email_user" required
                                                        maxlength="40" minlength="5"
                                                        value="<?php echo isset($_POST['user']['email_user']) ? htmlspecialchars($_POST['user']['email_user']) : ''; ?>"
                                                        autocomplete="off">
                                                    <small class="error-msg" style="color:red; display: none;">*Ingresa un
                                                        correo electrónico válido</small>
                                                </div>

                                                <!--Campo de Password - Contraseña / password-->
                                                <div class="form-group">
                                                    <label for="password_user">Password / Contraseña</label> (<small
                                                        style="color:black;">*Para ver la contraseña dar click en el botón
                                                        de la derecha</small>)
                                                    <div style="position: relative;">
                                                        <input name="user[password_user]" type="password"
                                                            class="form-control validate" id="password_user" required
                                                            title="Introduce un password de mínimo 8 y máximo 15 caracteres. Ejemplo: $Contraseña123"
                                                            maxlength="15" minlength="8"
                                                            pattern="^(?=.*[!@#$%^&*])(?=.*[A-Z])(?=.*[0-9]).{8,}$"
                                                            value="<?php echo isset($_POST['user']['password_user']) ? htmlspecialchars($_POST['user']['password_user']) : ''; ?>"
                                                            autocomplete="off">
                                                        <span class="password-toggle" onclick="togglePassword()">
                                                            <i class="fas fa-eye" id="eyeIconOpen"
                                                                style="display: none;"></i>
                                                            <i class="fas fa-eye-slash" id="eyeIconClosed"></i>
                                                        </span>
                                                    </div>
                                                    <small class="error-msg" style="color:red; display: none;">*La
                                                        contraseña debe contener al menos un símbolo especial, una letra
                                                        mayúscula, un número y tener una longitud mínima de 8 caracteres.
                                                        (Ejem. $Contraseña1234@)</small>
                                                </div>
                                            <?php } ?>

                                            <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                                            <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                                <div class="alert" role="alert"
                                                    style="text-align:center; font-size:20px; background-color: #37424A; color: #ffffff;">
                                                    ¡Favor de presionar <strong>una vez el botón de "guardar
                                                        usuario"</strong>, y esperar a que cargue la página!
                                                </div>
                                                <div class="form-group text-center">
                                                    <button class="btn btn-lg"
                                                        style="background-color: #37424A; color: #ffffff;" name="action"
                                                        value="create">Guardar usuario</button>
                                                </div>
                                            <?php } ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><br>
    </div>


    <!-- MODAL COMPLETO PARA AGREGAR NUEVA EMPRESA -->
    <div class="modal fade company-modal" id="addCompanyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-building mr-2"></i>Nueva Empresa
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="companyForm">
                    <div class="modal-body">
                        <div class="alert alert-info-custom">
                            <i class="fas fa-lightbulb mr-2"></i>
                            Complete la información de la empresa. Los campos con <span
                                class="required-asterisk">*</span> son obligatorios.
                        </div>

                        <!-- Información Básica -->
                        <div class="company-form-section">
                            <h6><i class="fas fa-building"></i>Información General</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nombre Comercial <span class="required-asterisk">*</span></label>
                                        <input type="text" class="form-control" name="company[name_company]" required
                                            maxlength="100" placeholder="Ej: Mi Empresa SA">
                                        <small class="form-text text-muted">Nombre con el que se conoce
                                            comercialmente</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>RFC <span class="required-asterisk">*</span></label>
                                        <input type="text" class="form-control" id="company_rfc"
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
                                        <input type="text" class="form-control" name="company[razon_social]" required
                                            maxlength="150"
                                            placeholder="Mi Empresa Sociedad Anónima de Capital Variable">
                                        <small class="form-text text-muted">Denominación legal completa</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tipo de Persona <span class="required-asterisk">*</span></label>
                                        <select class="form-control" name="company[tipo_persona]" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="moral">Persona Moral</option>
                                            <option value="fisica">Persona Física con Actividad Empresarial</option>
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
                                            max="<?php echo date('Y-m-d'); ?>">
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
                                        <input type="tel" class="form-control" id="company_telefono"
                                            name="company[telefono]" maxlength="15" placeholder="8123456789">
                                        <small class="form-text text-muted">Número de contacto principal</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email Corporativo</label>
                                        <input type="email" class="form-control" name="company[email]" maxlength="100"
                                            placeholder="contacto@empresa.com">
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
                                        <input type="text" class="form-control" name="company[estado]" maxlength="50"
                                            placeholder="Nuevo León">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Ciudad/Municipio</label>
                                        <input type="text" class="form-control" name="company[ciudad]" maxlength="50"
                                            placeholder="Monterrey">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Calle y Número</label>
                                        <input type="text" class="form-control" name="company[calle]" maxlength="150"
                                            placeholder="Av. Constitución 123">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Colonia</label>
                                        <input type="text" class="form-control" name="company[colonia]" maxlength="100"
                                            placeholder="Centro">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Número Exterior</label>
                                        <input type="text" class="form-control" name="company[num_exterior]"
                                            maxlength="10" placeholder="123">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Número Interior</label>
                                        <input type="text" class="form-control" name="company[num_interior]"
                                            maxlength="10" placeholder="A">
                                        <small class="form-text text-muted">Opcional</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Código Postal</label>
                                        <input type="text" class="form-control" id="company_cp"
                                            name="company[codigo_postal]" maxlength="5" pattern="[0-9]{5}"
                                            placeholder="64000">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Representante Legal -->
                        <div class="company-form-section">
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
                                            maxlength="50" placeholder="Juan Carlos"
                                            style="text-transform: capitalize;">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Apellido Paterno</label>
                                        <input type="text" class="form-control"
                                            name="company[apoderado_apellido_paterno]" maxlength="50"
                                            placeholder="García" style="text-transform: capitalize;">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Apellido Materno</label>
                                        <input type="text" class="form-control"
                                            name="company[apoderado_apellido_materno]" maxlength="50"
                                            placeholder="López" style="text-transform: capitalize;">
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

                        <!-- Resumen Visual -->
                        <div class="company-form-section bg-light">
                            <h6><i class="fas fa-clipboard-check"></i>Resumen</h6>
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="company-preview" class="p-3 border rounded bg-white"
                                        style="display: none;">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h6 class="mb-1" id="preview-name">-</h6>
                                                <small class="text-muted" id="preview-rfc">-</small>
                                            </div>
                                            <div class="col-md-4 text-right">
                                                <span class="badge badge-primary" id="preview-tipo">-</span>
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        <div id="preview-info" class="small">
                                            <em class="text-muted">Complete los campos para ver el resumen</em>
                                        </div>
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
                            <i class="fas fa-save mr-1"></i>Crear Empresa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <script>
        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip({
                delay: { "show": 0, "hide": 0 }
            });
        });
    </script>

    <script src="../../resources/plugins/jquery/jquery.min.js"></script>
    <script src="../../resources/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../resources/dist/js/adminlte.min.js"></script>
    <script src="../../resources/js/notifications.js"></script>
    <script src="../../resources/js/tracings.js"></script>
    <script src="../../resources/js/notify_folders.js"></script>
    <script src="../../resources/js/cropper.min.js"></script>
    <!-- Select2 -->
    <script src="../../resources/plugins/select2/js/select2.full.min.js"></script>

    <!-- SCRIPT PRINCIPAL ÚNICO -->
    <script>
        $(document).ready(function () {
            $('.selectTypeUser').select2({
                theme: 'bootstrap4'
            });

            // Select2 para empresas con template personalizado
            $('#id_company').select2({
                theme: 'bootstrap4',
                placeholder: 'Seleccionar empresa...',
                allowClear: true,
                templateResult: function (option) {
                    if (option.element && $(option.element).hasClass('management-option')) {
                        var $span = $('<span>');

                        if ($(option.element).hasClass('add-option')) {
                            $span.css({ 'color': '#28a745', 'font-weight': 'bold' });
                        } else if ($(option.element).hasClass('edit-option')) {
                            $span.css({ 'color': '#ffc107', 'font-weight': 'bold' });
                        } else if ($(option.element).hasClass('delete-option')) {
                            $span.css({ 'color': '#dc3545', 'font-weight': 'bold' });
                        }

                        $span.text(option.text);
                        return $span;
                    }
                    return option.text;
                }
            });

            // Función para mostrar/ocultar campos de empresa
            function toggleEmpresaFields() {
                var tipoUsuario = $('#id_type_user').val();

                if (tipoUsuario == '3') { // Cliente Empresa
                    $('#empresa-section').show().addClass('animate-in');
                    $('#id_company').attr('required', true);
                    showNotification('info', 'Usuario tipo Cliente Empresa: debe asignar una empresa');
                } else { // Admin o Empleado interno
                    $('#empresa-section').hide().removeClass('animate-in');
                    $('#id_company').removeAttr('required').val('').trigger('change');

                    if (tipoUsuario) {
                        var tipoNombre = $('#id_type_user option:selected').text();
                        showNotification('success', 'Usuario tipo ' + tipoNombre + ': empleado interno');
                    }
                }
            }
            // Ejecutar al cargar la página
            toggleEmpresaFields();

            // Ejecutar cuando cambie el tipo de usuario
            $('#id_type_user').change(function () {
                toggleEmpresaFields();
            });

            // Manejar selección de empresa y acciones de gestión
            $('#id_company').on('change', function () {
                var selectedValue = $(this).val();

                if (selectedValue === 'add_new') {
                    $(this).val('').trigger('change');
                    $('#addCompanyModal').modal('show');

                } else if (selectedValue === 'edit_company') {
                    $(this).val('').trigger('change');
                    openEditCompanySelector();

                } else if (selectedValue === 'delete_company') {
                    $(this).val('').trigger('change');
                    openDeleteCompanySelector();

                } else if (selectedValue) {
                    var companyName = $(this).find('option:selected').text();
                    showNotification('success', 'Empresa seleccionada: ' + companyName);
                }
            });

            // Función para abrir selector de empresa a editar
            function openEditCompanySelector() {
                var modalHtml = '<div class="modal fade" id="editSelectorModal" tabindex="-1">' +
                    '<div class="modal-dialog">' +
                    '<div class="modal-content">' +
                    '<div class="modal-header" style="background-color: #37424A;">' +
                    '<h5 class="modal-title text-white">' +
                    '<i class="fas fa-edit mr-2"></i>Seleccionar Empresa a Editar' +
                    '</h5>' +
                    '<button type="button" class="close text-white" data-dismiss="modal">' +
                    '<span>&times;</span>' +
                    '</button>' +
                    '</div>' +
                    '<div class="modal-body">' +
                    '<p>Seleccione la empresa que desea editar:</p>' +
                    '<div class="list-group" id="company-list-edit">' +
                    '</div>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';

                $('body').append(modalHtml);

                // Agregar empresas dinámicamente
                $('#id_company option').each(function () {
                    var value = $(this).val();
                    var text = $(this).text();

                    if (value && !$(this).hasClass('management-option') && !$(this).prop('disabled')) {
                        var button = $('<button>')
                            .addClass('list-group-item list-group-item-action')
                            .html('<i class="fas fa-building mr-2" style="color: #37424A;"></i>' + $('<div>').text(text).html())
                            .on('click', function () {
                                editCompany(value, text);
                            });

                        $('#company-list-edit').append(button);
                    }
                });

                $('#editSelectorModal').modal('show');

                $('#editSelectorModal').on('hidden.bs.modal', function () {
                    $(this).remove();
                });
            }

            // Función para abrir selector de empresa a eliminar - MEJORADA
            function openDeleteCompanySelector() {
                var modalHtml = '<div class="modal fade" id="deleteSelectorModal" tabindex="-1">' +
                    '<div class="modal-dialog modal-lg">' +
                    '<div class="modal-content">' +
                    '<div class="modal-header bg-danger">' +
                    '<h5 class="modal-title text-white">' +
                    '<i class="fas fa-exclamation-triangle mr-2"></i>Eliminar Empresa' +
                    '</h5>' +
                    '<button type="button" class="close text-white" data-dismiss="modal">' +
                    '<span>&times;</span>' +
                    '</button>' +
                    '</div>' +
                    '<div class="modal-body">' +
                    '<div class="alert alert-danger">' +
                    '<h5><i class="fas fa-exclamation-triangle mr-2"></i>¡ATENCIÓN!</h5>' +
                    '<p class="mb-2">Esta acción eliminará permanentemente la empresa del sistema.</p>' +
                    '<ul class="mb-0">' +
                    '<li>No se puede deshacer</li>' +
                    '<li>Se verificarán usuarios asociados</li>' +
                    '<li>Los datos se marcarán como eliminados</li>' +
                    '</ul>' +
                    '</div>' +
                    '<p><strong>Seleccione la empresa que desea eliminar:</strong></p>' +
                    '<div class="list-group" id="company-list-delete" style="max-height: 300px; overflow-y: auto;">' +
                    '</div>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-secondary" data-dismiss="modal">' +
                    '<i class="fas fa-times mr-1"></i>Cancelar' +
                    '</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';

                $('body').append(modalHtml);

                // Agregar empresas dinámicamente
                $('#id_company option').each(function () {
                    var value = $(this).val();
                    var text = $(this).text();

                    if (value && !$(this).hasClass('management-option') && !$(this).prop('disabled')) {
                        var button = $('<button>')
                            .addClass('list-group-item list-group-item-action list-group-item-danger')
                            .html(
                                '<div class="d-flex justify-content-between align-items-center">' +
                                '<div>' +
                                '<i class="fas fa-building mr-2"></i>' +
                                '<strong>' + $('<div>').text(text).html() + '</strong>' +
                                '</div>' +
                                '<div>' +
                                '<i class="fas fa-trash text-danger"></i>' +
                                '</div>' +
                                '</div>'
                            )
                            .on('click', function () {
                                confirmDeleteCompany(value, text);
                            });

                        $('#company-list-delete').append(button);
                    }
                });

                if ($('#company-list-delete').children().length === 0) {
                    $('#company-list-delete').html(
                        '<div class="alert alert-info text-center">' +
                        '<i class="fas fa-info-circle mr-2"></i>' +
                        'No hay empresas disponibles para eliminar' +
                        '</div>'
                    );
                }

                $('#deleteSelectorModal').modal('show');

                $('#deleteSelectorModal').on('hidden.bs.modal', function () {
                    $(this).remove();
                });
            }

            // Función para confirmar eliminación
            function confirmDeleteCompany(companyId, companyName) {
                $('#deleteSelectorModal').modal('hide');

                var confirmModalHtml = '<div class="modal fade" id="confirmDeleteModal" tabindex="-1">' +
                    '<div class="modal-dialog">' +
                    '<div class="modal-content">' +
                    '<div class="modal-header bg-danger">' +
                    '<h5 class="modal-title text-white">' +
                    '<i class="fas fa-exclamation-triangle mr-2"></i>Confirmar Eliminación' +
                    '</h5>' +
                    '</div>' +
                    '<div class="modal-body text-center">' +
                    '<div class="mb-3">' +
                    '<i class="fas fa-exclamation-triangle text-danger" style="font-size: 3rem;"></i>' +
                    '</div>' +
                    '<h5>¿Está completamente seguro?</h5>' +
                    '<p class="mb-3">Va a eliminar la empresa:</p>' +
                    '<div class="alert alert-light border">' +
                    '<strong>' + $('<div>').text(companyName).html() + '</strong>' +
                    '</div>' +
                    '<p class="text-muted small">Esta acción no se puede deshacer</p>' +
                    '</div>' +
                    '<div class="modal-footer justify-content-center">' +
                    '<button type="button" class="btn btn-secondary" data-dismiss="modal">' +
                    '<i class="fas fa-times mr-1"></i>Cancelar' +
                    '</button>' +
                    '<button type="button" class="btn btn-danger" id="confirmDeleteBtn">' +
                    '<i class="fas fa-trash mr-1"></i>Sí, Eliminar' +
                    '</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';

                $('body').append(confirmModalHtml);
                $('#confirmDeleteModal').modal('show');

                $('#confirmDeleteBtn').on('click', function () {
                    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Eliminando...');

                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        data: {
                            ajax_action: 'delete_company',
                            company_id: companyId
                        },
                        dataType: 'json',
                        success: function (response) {
                            $('#confirmDeleteModal').modal('hide');

                            if (response && response.success) {
                                $('#id_company option[value="' + companyId + '"]').remove();
                                $('#id_company').select2('destroy').select2({
                                    theme: 'bootstrap4',
                                    placeholder: 'Seleccionar empresa...',
                                    allowClear: true,
                                    templateResult: function (option) {
                                        if (option.element && $(option.element).hasClass('management-option')) {
                                            var $span = $('<span>');

                                            if ($(option.element).hasClass('add-option')) {
                                                $span.css({ 'color': '#28a745', 'font-weight': 'bold' });
                                            } else if ($(option.element).hasClass('edit-option')) {
                                                $span.css({ 'color': '#ffc107', 'font-weight': 'bold' });
                                            } else if ($(option.element).hasClass('delete-option')) {
                                                $span.css({ 'color': '#dc3545', 'font-weight': 'bold' });
                                            }

                                            $span.text(option.text);
                                            return $span;
                                        }
                                        return option.text;
                                    }
                                });

                                showNotification('success', '' + (response.message || 'Empresa eliminada exitosamente'));
                            } else {
                                showNotification('error', '' + (response.message || 'Error al eliminar la empresa'));
                            }
                        },
                        error: function (xhr, status, error) {
                            $('#confirmDeleteModal').modal('hide');
                            console.error('Error eliminando empresa:', error);
                            showNotification('error', 'Error al eliminar la empresa');
                        }
                    });
                });

                $('#confirmDeleteModal').on('hidden.bs.modal', function () {
                    $(this).remove();
                });
            }

            // Hacer funciones globales
            window.editCompany = function (companyId, companyName) {
                $('#editSelectorModal').modal('hide');
                loadCompanyDataAndEdit(companyId, companyName);
            };

            window.deleteCompany = function (companyId, companyName) {
                $('#deleteSelectorModal').modal('hide');
                confirmDeleteCompany(companyId, companyName);
            };

            // Función para cargar datos de empresa y editar
            function loadCompanyDataAndEdit(companyId, companyName) {
                var safeName = $('<div>').text(companyName).html();
                var editModalHtml = '<div class="modal fade company-modal" id="editCompanyModal" tabindex="-1">' +
                    '<div class="modal-dialog modal-xl">' +
                    '<div class="modal-content">' +
                    '<div class="modal-header" style="background: linear-gradient(135deg, #37424A 0%, #2c353b 100%);">' +
                    '<h5 class="modal-title text-white">' +
                    '<i class="fas fa-edit mr-2"></i>Editar Empresa: ' + safeName +
                    '</h5>' +
                    '<button type="button" class="close text-white" data-dismiss="modal">' +
                    '<span>&times;</span>' +
                    '</button>' +
                    '</div>' +
                    '<div class="modal-body">' +
                    '<div class="text-center">' +
                    '<i class="fas fa-spinner fa-spin fa-2x text-primary"></i>' +
                    '<p class="mt-2">Cargando datos de la empresa...</p>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';

                $('body').append(editModalHtml);
                $('#editCompanyModal').modal('show');

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
                            $('#editCompanyModal .modal-body').html(createEditForm(response.company));
                            setupEditFormValidations();
                        } else {
                            showNotification('error', response.message || 'Error al cargar los datos de la empresa');
                            $('#editCompanyModal').modal('hide');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error cargando empresa:', error);
                        showNotification('error', 'Error al comunicarse con el servidor');
                        $('#editCompanyModal').modal('hide');
                    }
                });

                $('#editCompanyModal').on('hidden.bs.modal', function () {
                    $(this).remove();
                });
            }

            // Función para crear formulario de edición COMPLETO
            function createEditForm(company) {
                // Escapar todos los valores
                var nameCompany = $('<div>').text(company.name_company || '').html();
                var rfcCompany = $('<div>').text(company.rfc_company || '').html();
                var razonSocial = $('<div>').text(company.razon_social || '').html();
                var fechaConstitucion = company.fecha_constitucion || '';
                var estado = $('<div>').text(company.estado || '').html();
                var ciudad = $('<div>').text(company.ciudad || '').html();
                var colonia = $('<div>').text(company.colonia || '').html();
                var calle = $('<div>').text(company.calle || '').html();
                var numExterior = $('<div>').text(company.num_exterior || '').html();
                var numInterior = $('<div>').text(company.num_interior || '').html();
                var codigoPostal = $('<div>').text(company.codigo_postal || '').html();
                var telefono = $('<div>').text(company.telefono || '').html();
                var email = $('<div>').text(company.email || '').html();
                var apoderadoNombre = $('<div>').text(company.apoderado_nombre || '').html();
                var apoderadoPaterno = $('<div>').text(company.apoderado_apellido_paterno || '').html();
                var apoderadoMaterno = $('<div>').text(company.apoderado_apellido_materno || '').html();
                var apoderadoRfc = $('<div>').text(company.apoderado_rfc || '').html();
                var apoderadoCurp = $('<div>').text(company.apoderado_curp || '').html();

                return '<div class="alert alert-info-custom">' +
                    '<i class="fas fa-lightbulb mr-2"></i>' +
                    'Edite la información de la empresa. Los campos con <span class="required-asterisk">*</span> son obligatorios.' +
                    '</div>' +

                    '<form id="editCompanyForm">' +
                    '<input type="hidden" name="company_id" value="' + company.id_company + '">' +

                    // Información Básica
                    '<div class="company-form-section">' +
                    '<h6><i class="fas fa-building"></i>Información General</h6>' +
                    '<div class="row">' +
                    '<div class="col-md-6">' +
                    '<div class="form-group">' +
                    '<label>Nombre Comercial <span class="required-asterisk">*</span></label>' +
                    '<input type="text" class="form-control" name="company[name_company]" required maxlength="100" value="' + nameCompany + '">' +
                    '<small class="form-text text-muted">Nombre con el que se conoce comercialmente</small>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-6">' +
                    '<div class="form-group">' +
                    '<label>RFC <span class="required-asterisk">*</span></label>' +
                    '<input type="text" class="form-control edit-rfc" name="company[rfc_company]" required maxlength="13" minlength="12" value="' + rfcCompany + '" style="text-transform: uppercase;">' +
                    '<small class="form-text text-muted">Registro Federal de Contribuyentes</small>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="row">' +
                    '<div class="col-md-8">' +
                    '<div class="form-group">' +
                    '<label>Razón Social <span class="required-asterisk">*</span></label>' +
                    '<input type="text" class="form-control" name="company[razon_social]" required maxlength="150" value="' + razonSocial + '">' +
                    '<small class="form-text text-muted">Denominación legal completa</small>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                    '<div class="form-group">' +
                    '<label>Tipo de Persona <span class="required-asterisk">*</span></label>' +
                    '<select class="form-control" name="company[tipo_persona]" required>' +
                    '<option value="">Seleccionar...</option>' +
                    '<option value="moral"' + (company.tipo_persona === 'moral' ? ' selected' : '') + '>Persona Moral</option>' +
                    '<option value="fisica"' + (company.tipo_persona === 'fisica' ? ' selected' : '') + '>Persona Física con Actividad Empresarial</option>' +
                    '</select>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +

                    // Información Legal
                    '<div class="company-form-section">' +
                    '<h6><i class="fas fa-calendar-alt"></i>Información Legal</h6>' +
                    '<div class="row">' +
                    '<div class="col-md-6">' +
                    '<div class="form-group">' +
                    '<label>Fecha de Constitución</label>' +
                    '<input type="date" class="form-control" name="company[fecha_constitucion]" value="' + fechaConstitucion + '" max="' + new Date().toISOString().split('T')[0] + '">' +
                    '<small class="form-text text-muted">Fecha de creación legal de la empresa</small>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +

                    // Información de Contacto
                    '<div class="company-form-section">' +
                    '<h6><i class="fas fa-phone"></i>Información de Contacto</h6>' +
                    '<div class="row">' +
                    '<div class="col-md-6">' +
                    '<div class="form-group">' +
                    '<label>Teléfono Principal</label>' +
                    '<input type="tel" class="form-control edit-telefono" name="company[telefono]" maxlength="15" value="' + telefono + '">' +
                    '<small class="form-text text-muted">Número de contacto principal</small>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-6">' +
                    '<div class="form-group">' +
                    '<label>Email Corporativo</label>' +
                    '<input type="email" class="form-control" name="company[email]" maxlength="100" value="' + email + '">' +
                    '<small class="form-text text-muted">Correo electrónico principal</small>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +

                    // Dirección Fiscal
                    '<div class="company-form-section">' +
                    '<h6><i class="fas fa-map-marker-alt"></i>Dirección Fiscal</h6>' +
                    '<div class="row">' +
                    '<div class="col-md-6">' +
                    '<div class="form-group">' +
                    '<label>Estado</label>' +
                    '<input type="text" class="form-control" name="company[estado]" maxlength="50" value="' + estado + '">' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-6">' +
                    '<div class="form-group">' +
                    '<label>Ciudad/Municipio</label>' +
                    '<input type="text" class="form-control" name="company[ciudad]" maxlength="50" value="' + ciudad + '">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="row">' +
                    '<div class="col-md-8">' +
                    '<div class="form-group">' +
                    '<label>Calle y Número</label>' +
                    '<input type="text" class="form-control" name="company[calle]" maxlength="150" value="' + calle + '">' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                    '<div class="form-group">' +
                    '<label>Colonia</label>' +
                    '<input type="text" class="form-control" name="company[colonia]" maxlength="100" value="' + colonia + '">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="row">' +
                    '<div class="col-md-4">' +
                    '<div class="form-group">' +
                    '<label>Número Exterior</label>' +
                    '<input type="text" class="form-control" name="company[num_exterior]" maxlength="10" value="' + numExterior + '">' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                    '<div class="form-group">' +
                    '<label>Número Interior</label>' +
                    '<input type="text" class="form-control" name="company[num_interior]" maxlength="10" value="' + numInterior + '">' +
                    '<small class="form-text text-muted">Opcional</small>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                    '<div class="form-group">' +
                    '<label>Código Postal</label>' +
                    '<input type="text" class="form-control edit-cp" name="company[codigo_postal]" maxlength="5" pattern="[0-9]{5}" value="' + codigoPostal + '">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +

                    // Representante Legal
                    '<div class="company-form-section">' +
                    '<h6><i class="fas fa-user-tie"></i>Representante Legal / Apoderado</h6>' +
                    '<div class="alert alert-light border-left border-primary">' +
                    '<small><i class="fas fa-info-circle text-primary mr-1"></i>' +
                    'Información de la persona autorizada para representar legalmente a la empresa</small>' +
                    '</div>' +
                    '<div class="row">' +
                    '<div class="col-md-4">' +
                    '<div class="form-group">' +
                    '<label>Nombre(s)</label>' +
                    '<input type="text" class="form-control" name="company[apoderado_nombre]" maxlength="50" value="' + apoderadoNombre + '">' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                    '<div class="form-group">' +
                    '<label>Apellido Paterno</label>' +
                    '<input type="text" class="form-control" name="company[apoderado_apellido_paterno]" maxlength="50" value="' + apoderadoPaterno + '">' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                    '<div class="form-group">' +
                    '<label>Apellido Materno</label>' +
                    '<input type="text" class="form-control" name="company[apoderado_apellido_materno]" maxlength="50" value="' + apoderadoMaterno + '">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="row">' +
                    '<div class="col-md-6">' +
                    '<div class="form-group">' +
                    '<label>RFC del Representante</label>' +
                    '<input type="text" class="form-control edit-apoderado-rfc" name="company[apoderado_rfc]" maxlength="13" value="' + apoderadoRfc + '" style="text-transform: uppercase;">' +
                    '<small class="form-text text-muted">RFC de la persona física (13 caracteres)</small>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-6">' +
                    '<div class="form-group">' +
                    '<label>CURP del Representante</label>' +
                    '<input type="text" class="form-control edit-apoderado-curp" name="company[apoderado_curp]" maxlength="18" value="' + apoderadoCurp + '" style="text-transform: uppercase;">' +
                    '<small class="form-text text-muted">Clave Única de Registro de Población (18 caracteres)</small>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +

                    '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-light" data-dismiss="modal">' +
                    '<i class="fas fa-times mr-1"></i>Cancelar' +
                    '</button>' +
                    '<button type="submit" class="btn btn-add-company" id="updateCompanyBtn">' +
                    '<i class="fas fa-save mr-1"></i>Actualizar Empresa' +
                    '</button>' +
                    '</div>' +
                    '</form>';
            }

            // Configurar validaciones para formulario de edición
            function setupEditFormValidations() {
                $('.edit-cp, .edit-telefono').on('input', function () {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });

                $('.edit-rfc').on('input', function () {
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

                $('.edit-apoderado-rfc').on('input', function () {
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

                $('.edit-apoderado-curp').on('input', function () {
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

                $('#editCompanyForm').on('submit', function (e) {
                    e.preventDefault();

                    var updateBtn = $('#updateCompanyBtn');
                    var originalText = updateBtn.html();
                    updateBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');

                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        data: $(this).serialize() + '&ajax_action=update_company',
                        dataType: 'json',
                        success: function (response) {
                            console.log('Respuesta del servidor:', response);

                            if (response && response.success) {
                                // CÓDIGO CORREGIDO PARA ACTUALIZAR EL SELECT
                                var companyText = response.company.name + ' (' + response.company.rfc + ')';
                                var option = $('#id_company option[value="' + response.company.id + '"]');
                                var wasSelected = option.is(':selected');

                                // Actualizar el texto de la opción
                                option.text(companyText);

                                // Forzar recarga completa del select2
                                $('#id_company').select2('destroy');
                                $('#id_company').select2({
                                    theme: 'bootstrap4',
                                    placeholder: 'Seleccionar empresa...',
                                    allowClear: true,
                                    templateResult: function (option) {
                                        if (option.element && $(option.element).hasClass('management-option')) {
                                            var $span = $('<span>');

                                            if ($(option.element).hasClass('add-option')) {
                                                $span.css({ 'color': '#28a745', 'font-weight': 'bold' });
                                            } else if ($(option.element).hasClass('edit-option')) {
                                                $span.css({ 'color': '#ffc107', 'font-weight': 'bold' });
                                            } else if ($(option.element).hasClass('delete-option')) {
                                                $span.css({ 'color': '#dc3545', 'font-weight': 'bold' });
                                            }

                                            $span.text(option.text);
                                            return $span;
                                        }
                                        return option.text;
                                    }
                                });

                                // Mantener la selección si estaba seleccionada
                                if (wasSelected) {
                                    $('#id_company').val(response.company.id).trigger('change');
                                }

                                $('#editCompanyModal').modal('hide');
                                showNotification('success', '' + (response.message || 'Empresa actualizada exitosamente'));

                                console.log('Select actualizado correctamente con:', companyText);
                            } else {
                                console.error('Error en respuesta:', response);
                                showNotification('error', '' + (response.message || 'Error al actualizar la empresa'));
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error AJAX:', {
                                status: status,
                                error: error,
                                responseText: xhr.responseText
                            });
                            showNotification('error', 'Error al actualizar la empresa');
                        },
                        complete: function () {
                            updateBtn.prop('disabled', false).html(originalText);
                        }
                    });
                });
            }

            // Botón para abrir modal de empresa
            $('#btnAddCompany').on('click', function () {
                $('#addCompanyModal').modal('show');
            });

            // Validaciones en tiempo real para formulario de creación
            $('#company_rfc').on('input', function () {
                var rfc = $(this).val().toUpperCase();
                $(this).val(rfc);
                updatePreview();

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

            $('#apoderado_rfc').on('input', function () {
                var rfc = $(this).val().toUpperCase();
                $(this).val(rfc);

                if (rfc.length >= 13) {
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

            $('#apoderado_curp').on('input', function () {
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

            $('#company_cp, #company_telefono').on('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Envío del formulario de empresa via AJAX
            $('#companyForm').on('submit', function (e) {
                e.preventDefault();

                var saveBtn = $('#saveCompanyBtn');
                var originalText = saveBtn.html();
                saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                var formData = $(this).serialize() + '&ajax_action=create_company';

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function (response) {
                        if (response && response.success) {
                            var companyText = response.company.name + ' (' + response.company.rfc + ')';

                            var $separator = $('#id_company option[disabled]').first();
                            var newOption = $('<option>', {
                                value: response.company.id,
                                text: companyText,
                                selected: true
                            });

                            if ($separator.length > 0) {
                                $separator.before(newOption);
                            } else {
                                $('#id_company').append(newOption);
                            }

                            $('#id_company').select2('destroy').select2({
                                theme: 'bootstrap4',
                                placeholder: 'Seleccionar empresa...',
                                allowClear: true,
                                templateResult: function (option) {
                                    if (option.element && $(option.element).hasClass('management-option')) {
                                        var $span = $('<span>');

                                        if ($(option.element).hasClass('add-option')) {
                                            $span.css({ 'color': '#28a745', 'font-weight': 'bold' });
                                        } else if ($(option.element).hasClass('edit-option')) {
                                            $span.css({ 'color': '#ffc107', 'font-weight': 'bold' });
                                        } else if ($(option.element).hasClass('delete-option')) {
                                            $span.css({ 'color': '#dc3545', 'font-weight': 'bold' });
                                        }

                                        $span.text(option.text);
                                        return $span;
                                    }
                                    return option.text;
                                }
                            });

                            $('#id_company').val(response.company.id).trigger('change');
                            $('#addCompanyModal').modal('hide');
                            $('#companyForm')[0].reset();
                            $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');

                            showNotification('success', '' + response.message + ': ' + response.company.name);
                        } else {
                            showNotification('error', '' + (response.message || 'Error al crear la empresa'));
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error AJAX:', error);

                        var responseText = xhr.responseText;
                        if (responseText && (responseText.includes('<!DOCTYPE') || responseText.includes('<html'))) {
                            console.error('El servidor está devolviendo HTML en lugar de JSON');
                            showNotification('error', 'Error: El servidor no está respondiendo correctamente');
                        } else {
                            showNotification('error', 'Error al comunicarse con el servidor');
                        }
                    },
                    complete: function () {
                        saveBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Limpiar formulario al cerrar modal
            $('#addCompanyModal').on('hidden.bs.modal', function () {
                $('#companyForm')[0].reset();
                $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
            });



            // Validación antes de enviar el formulario principal
            $('#userForm').on('submit', function (e) {
                var tipoUsuario = $('#id_type_user').val();
                var empresa = $('#id_company').val();

                if (tipoUsuario == '3' && !empresa) {
                    e.preventDefault();
                    showNotification('error', 'Los usuarios de tipo Cliente Empresa deben tener una empresa asignada.');
                    $('#id_company').focus();
                    return false;
                }

                var userName = $('#name_user').val();
                var userType = $('#id_type_user option:selected').text();
                var companyName = empresa ? $('#id_company option:selected').text() : 'Ninguna';

                var companyText = '';
                if (tipoUsuario == '3' && empresa) {
                    companyText = '\nEmpresa: ' + companyName;
                }

                var confirmMsg = '¿Confirmar creación del usuario?\n\n' +
                    'Nombre: ' + userName + '\n' +
                    'Tipo: ' + userType + companyText;

                if (!confirm(confirmMsg)) {
                    e.preventDefault();
                    return false;
                }

                showLoading();
            });

            // Preview completo en tiempo real
            function updatePreview() {
                var name = $('input[name="company[name_company]"]').val();
                var rfc = $('#company_rfc').val();
                var razon = $('input[name="company[razon_social]"]').val();
                var tipo = $('select[name="company[tipo_persona]"]').val();
                var estado = $('input[name="company[estado]"]').val();
                var ciudad = $('input[name="company[ciudad]"]').val();
                var telefono = $('input[name="company[telefono]"]').val();
                var email = $('input[name="company[email]"]').val();
                var apoderado_nombre = $('input[name="company[apoderado_nombre]"]').val();
                var apoderado_paterno = $('input[name="company[apoderado_apellido_paterno]"]').val();

                if (name || rfc || razon) {
                    $('#company-preview').show();

                    $('#preview-name').text(name || 'Nombre de la empresa');
                    $('#preview-rfc').text(rfc || 'RFC pendiente');

                    var tipoText = '';
                    var tipoClass = 'badge-secondary';
                    if (tipo === 'moral') {
                        tipoText = 'Persona Moral';
                        tipoClass = 'badge-primary';
                    } else if (tipo === 'fisica') {
                        tipoText = 'Persona Física';
                        tipoClass = 'badge-info';
                    } else {
                        tipoText = 'Tipo pendiente';
                    }

                    $('#preview-tipo').removeClass('badge-primary badge-info badge-secondary')
                        .addClass(tipoClass)
                        .text(tipoText);

                    var infoAdicional = [];

                    if (razon) {
                        infoAdicional.push('<strong>Razón Social:</strong> ' + $('<div>').text(razon).html());
                    }

                    if (estado || ciudad) {
                        var ubicacion = '';
                        if (ciudad && estado) {
                            ubicacion = $('<div>').text(ciudad + ', ' + estado).html();
                        } else if (ciudad) {
                            ubicacion = $('<div>').text(ciudad).html();
                        } else if (estado) {
                            ubicacion = $('<div>').text(estado).html();
                        }
                        infoAdicional.push('<strong>Ubicación:</strong> ' + ubicacion);
                    }

                    if (telefono) {
                        infoAdicional.push('<strong>Teléfono:</strong> ' + $('<div>').text(telefono).html());
                    }

                    if (email) {
                        infoAdicional.push('<strong>Email:</strong> ' + $('<div>').text(email).html());
                    }

                    if (apoderado_nombre || apoderado_paterno) {
                        var representante = '';
                        if (apoderado_nombre && apoderado_paterno) {
                            representante = $('<div>').text(apoderado_nombre + ' ' + apoderado_paterno).html();
                        } else if (apoderado_nombre) {
                            representante = $('<div>').text(apoderado_nombre).html();
                        } else if (apoderado_paterno) {
                            representante = $('<div>').text(apoderado_paterno).html();
                        }
                        infoAdicional.push('<strong>Representante:</strong> ' + representante);
                    }

                    if (infoAdicional.length > 0) {
                        $('#preview-info').html(infoAdicional.join('<br>'));
                    } else {
                        $('#preview-info').html('<em class="text-muted">Complete más campos para ver información adicional</em>');
                    }

                } else {
                    $('#company-preview').hide();
                }
            }

            // Actualizar preview cuando cambien los campos relevantes
            $('input[name="company[name_company]"], input[name="company[razon_social]"], ' +
                'select[name="company[tipo_persona]"], input[name="company[estado]"], ' +
                'input[name="company[ciudad]"], input[name="company[telefono]"], ' +
                'input[name="company[email]"], input[name="company[apoderado_nombre]"], ' +
                'input[name="company[apoderado_apellido_paterno]"], #company_rfc').on('input change', updatePreview);

            // Función para mostrar notificaciones
            function showNotification(type, message) {
                $('.custom-notification').remove();

                var bgClass = '';
                var icon = '';

                switch (type) {
                    case 'success':
                        bgClass = 'alert-success';
                        icon = 'fas fa-check-circle';
                        break;
                    case 'error':
                        bgClass = 'alert-danger';
                        icon = 'fas fa-exclamation-triangle';
                        break;
                    case 'info':
                        bgClass = 'alert-info';
                        icon = 'fas fa-info-circle';
                        break;
                }

                var notification = $('<div class="alert ' + bgClass + ' custom-notification mt-2">' +
                    '<i class="' + icon + '"></i> ' + $('<div>').text(message).html() +
                    '</div>');

                $('#id_type_user').closest('.form-group').after(notification);

                setTimeout(function () {
                    notification.fadeOut(500, function () {
                        $(this).remove();
                    });
                }, 3000);
            }

            // Función para mostrar loading
            function showLoading() {
                $('button[type="submit"]').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> Creando usuario...'
                );
            }
            
        // AGREGAR AQUÍ LA FUNCIÓN togglePassword()
            window.togglePassword = function() {
                var passwordField = document.getElementById('password_user');
                var eyeIconOpen = document.getElementById('eyeIconOpen');
                var eyeIconClosed = document.getElementById('eyeIconClosed');
                var passwordToggle = document.querySelector('.password-toggle');
                
                if (passwordField.type === 'password') {
                    // Mostrar contraseña
                    passwordField.type = 'text';
                    eyeIconOpen.style.display = 'inline';
                    eyeIconClosed.style.display = 'none';
                } else {
                    // Ocultar contraseña
                    passwordField.type = 'password';
                    eyeIconOpen.style.display = 'none';
                    eyeIconClosed.style.display = 'inline';
                }
                
                // Efecto visual de click
                passwordToggle.classList.add('clicked');
                setTimeout(function() {
                    passwordToggle.classList.remove('clicked');
                }, 200);
            };
        });
    </script>


</body>

</html>