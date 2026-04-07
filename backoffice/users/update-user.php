<?php
session_start();  



include "../../app/config.php";
include "../../app/FileController.php";
include "../../app/WebController.php";
$controller = new WebController();
$files = new FileController();

// FUNCIÓN DE DEBUG (PRIMERO DEFINIR LA FUNCIÓN)
function debugLog($step, $data = null) {
    error_log("DEBUG STEP $step: " . print_r($data, true));
    echo "<script>console.log('DEBUG STEP $step:', " . json_encode($data) . ");</script>";
}
// AHORA SÍ LLAMAR LA FUNCIÓN
debugLog('DEBUG-POST-RAW', [
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
    'POST_keys' => array_keys($_POST),
    'POST_action_isset' => isset($_POST['action']),
    'POST_action_value' => $_POST['action'] ?? 'NOT_SET',
    'POST_count' => count($_POST),
    'GET_params' => $_GET
]);

// Verificar si la sesión del usuario está activa
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    session_destroy();
    header("Location: ../../login.php");
    exit();
}

// COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMIN (1)
if($_SESSION['user']['id_type_user'] != 1) {
    header('location: users.php');
    exit();
}

// Obtener los detalles del usuario
$user = $controller->getDetailUser($_GET['id'], $_GET['key']);
debugLog('1-USER_LOADED', ['user_found' => !empty($user), 'user_id' => $user['id_user'] ?? 'NULL']);

// Si no se encuentra el usuario, regresar a la consulta principal
if(empty($user)){
    debugLog('ERROR-USER_NOT_FOUND');
    header("location: users.php");
    exit();
}

// FUNCIÓN PARA MOSTRAR EL TIPO DE USUARIOS Y EMPRESAS
$userTypes = $controller->getUserTypes();
$companies = $controller->getActiveCompanies();
debugLog('2-DATA_LOADED', [
    'userTypes_count' => count($userTypes), 
    'companies_count' => count($companies)
]);

function uploadFilePhoto($folio, $filename = null) {
    global $files;
    debugLog('FILE-UPLOAD-START', [
        'folio' => $folio,
        'file_exists' => isset($_FILES['file-imguser']),
        'file_error' => $_FILES['file-imguser']['error'] ?? 'NO_FILE'
    ]);
    
    // Verificar si hay archivo para subir
    if (isset($_FILES['file-imguser']) && $_FILES['file-imguser']['error'] === UPLOAD_ERR_OK) {
        $filename['imguser'] = $files->upload($folio, $_FILES['file-imguser'], "ord.imguser");
        debugLog('FILE-UPLOAD-SUCCESS', ['filename' => $filename['imguser']]);
    } else {
        $filename['imguser'] = null;
        debugLog('FILE-UPLOAD-SKIPPED', 'No file uploaded or upload error');
    }
    
    return $filename;
}

$permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$usr_rfc = substr(str_shuffle($permitted_chars), 0, 13);

// PROCESAR FORMULARIO
if(!empty($_POST['action']) && $_POST['action'] == 'update'){
    debugLog('3-FORM_SUBMITTED', [
        'POST_data' => $_POST['user'] ?? 'NULL',
        'FILES_data' => isset($_FILES['file-imguser']) ? 'FILE_EXISTS' : 'NO_FILE'
    ]);
    
    // Validar que los datos del usuario existan
    if (!isset($_POST['user']) || !is_array($_POST['user'])) {
        $mssg = "Error: Datos del formulario no válidos.";
        debugLog('ERROR-INVALID_FORM_DATA');
    } else {
        // NUEVA VALIDACIÓN: Si es cliente empresa, debe tener empresa asignada
        if($_POST['user']['id_type_user'] == 3 && empty($_POST['user']['id_company'])) {
            $mssg = "¡LOS USUARIOS DE TIPO CLIENTE EMPRESA DEBEN TENER UNA EMPRESA ASIGNADA!";
            debugLog('4-ERROR-NO_COMPANY');
        } else {
            debugLog('4-VALIDATION_START');
            
            // Validar duplicados de manera más eficiente
            $errors = [];
            
            // Solo validar si los campos cambiaron y existen
            if(isset($_POST['user']['email_user']) && $_POST['user']['email_user'] != $user['email_user']) {
                debugLog('4a-CHECKING_EMAIL', [
                    'old' => $user['email_user'],
                    'new' => $_POST['user']['email_user']
                ]);
                $emailUserUpdate = $controller->getEmailUser($_POST['user']['email_user']);
                if(!empty($emailUserUpdate) && $emailUserUpdate['id_user'] != $_GET['id']) {
                    $errors[] = "¡EL CORREO ELECTRÓNICO YA ESTÁ EN USO POR OTRO USUARIO ACTIVO!";
                    debugLog('4a-ERROR-EMAIL_EXISTS');
                }
            }
            
            if(isset($_POST['user']['phone_user']) && $_POST['user']['phone_user'] != $user['phone_user']) {
                debugLog('4b-CHECKING_PHONE', [
                    'old' => $user['phone_user'],
                    'new' => $_POST['user']['phone_user']
                ]);
                $phoneUserUpdate = $controller->getPhoneUser($_POST['user']['phone_user']);
                if(!empty($phoneUserUpdate) && $phoneUserUpdate['id_user'] != $_GET['id']) {
                    $errors[] = "¡EL NÚMERO DE TELÉFONO YA ESTÁ EN USO POR OTRO USUARIO ACTIVO!";
                    debugLog('4b-ERROR-PHONE_EXISTS');
                }
            }
            
            // RFC: usar el existente si no se proporciona uno nuevo
            if (!isset($_POST['user']['rfc_user']) || empty($_POST['user']['rfc_user'])) {
                $_POST['user']['rfc_user'] = !empty($user['rfc_user']) ? $user['rfc_user'] : $usr_rfc;
                debugLog('4c-RFC_SET', ['rfc_used' => $_POST['user']['rfc_user']]);
            } else if($_POST['user']['rfc_user'] != $user['rfc_user']) {
                debugLog('4c-CHECKING_RFC', [
                    'old' => $user['rfc_user'],
                    'new' => $_POST['user']['rfc_user']
                ]);
                $rfcUserUpdate = $controller->getRFCUser($_POST['user']['rfc_user']);
                if(!empty($rfcUserUpdate) && $rfcUserUpdate['id_user'] != $_GET['id']) {
                    $errors[] = "¡EL RFC YA SE ENCUENTRA REGISTRADO!";
                    debugLog('4c-ERROR-RFC_EXISTS');
                }
            }
            
            // Si hay errores, mostrar el primero
            if(!empty($errors)) {
                $mssg = $errors[0];
                debugLog('5-VALIDATION_ERRORS', $errors);
            } else {
                debugLog('5-VALIDATION_OK-CALLING_UPDATE', $_POST['user']);
                
                // Proceder con la actualización
                try {
                    $idUser = $controller->updateUser($_POST['user'], $_GET['id']);
                    debugLog('6-UPDATE_RESULT', [
                        'result' => $idUser, 
                        'type' => gettype($idUser),
                        'is_truthy' => !empty($idUser)
                    ]);
                    
                    if($idUser){
                        debugLog('7-UPDATE_SUCCESS-PROCESSING_PHOTO');
                        
                        // Manejar la foto
                        $files_result = uploadFilePhoto($user['key_user']);
                        
                        if(!empty($files_result['imguser'])){
                            $files_to_update = ['imguser' => $files_result['imguser']];
                        } else {
                            $files_to_update = ['imguser' => $user['photo_user']];
                        }
                        
                        debugLog('8-PHOTO_DATA', $files_to_update);
                        
                        $userId = $controller->updatePhotoUser($user['id_user'], $files_to_update);
                        debugLog('9-PHOTO_UPDATED', ['result' => $userId]);
                        
                        // Redirigir después de actualizar exitosamente
                        debugLog('10-REDIRECTING');
                        header('location: users.php');
                        exit();
                    } else {
                        $mssg = "Error al actualizar el usuario. Intente nuevamente.";
                        debugLog('ERROR-UPDATE_FAILED', ['updateUser_returned' => $idUser]);
                    }
                } catch (Exception $e) {
                    $mssg = "Error en la actualización: " . $e->getMessage();
                    debugLog('ERROR-EXCEPTION', ['message' => $e->getMessage()]);
                }
            }
        }
    }
} else {
    debugLog('0-NO_FORM_SUBMITTED', [
        'POST_action' => $_POST['action'] ?? 'NULL',
        'POST_empty' => empty($_POST)
    ]);
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
    <link rel="stylesheet" href="../../resources/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../../resources/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="../../resources/css/cropper.min.css" />
    
    <link rel="icon" href="../../resources/img/icono.png">
    <script src="../../resources/js/jquery-3.5.1.min.js"></script>
    <style>
      /* Estilos adicionales para el botón de "ver contraseña" */
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
      #password_user {
        padding-right: 30px;
      }
      

      #empresa-section {
          border-left: 4px solid #007bff;
          padding: 15px;
          background-color: #f8f9fa;
          border-radius: 5px;
          margin: 10px 0;
          display: none;
      }

      #empresa-section label {
          color: #007bff;
          font-weight: 600;
      }

      #empresa-actual {
        border-left: 4px solid #17a2b8;
        background-color: #e3f2fd;
        padding: 15px;
        border-radius: 5px;
        margin: 10px 0;
      }

      .badge {
        font-size: 0.8em;
      }
      
      .empresa-warning {
        border-left: 4px solid #ffc107;
        background-color: #fff3cd;
        padding: 15px;
        border-radius: 5px;
        margin: 10px 0;
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
                            <h1 class="m-0 text-dark">Actualizar usuario</h1>
                        </div>
                        <div class="col-sm-4 text-right">
                            <a href="users.php" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;" role="button" aria-pressed="true">Regresar</a>
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
                <form action="#" method="post" enctype="multipart/form-data">
                    <div class="container-fluid">
                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                        <?php if($_SESSION['user']['id_type_user'] == 1){ ?>
                            <div class="row">
                                
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="card" style="height: 100%">
                                        <div class="card-body">
                                            <div class="col-md-12">
                                                <!-- Campo Nombre -->
                                                <div class="form-group">
                                                    <label for="name_user">Nombre del usuario</label>
                                                    <input name="user[name_user]" type="text" class="form-control validate" id="name_user" required title="Utiliza solo letras como mínimo 3 y máximo 40" pattern="[a-zA-ZñÑáÁéÉíÍóÓúÚ ]{3,40}" maxlength="40" minlength="3" value="<?php echo htmlspecialchars($user['name_user']); ?>" autocomplete="off">
                                                    <small class="error-msg" style="color:red; display: none;">*Utiliza solo letras como mínimo 3 y máximo 40 caracteres</small>
                                                </div>
                                                
                                                <!--ID del tipo de Usuario / id_type_user -->
                                                <div class="form-group">
                                                    <label for="id_type_user">Tipo de usuario</label>
                                                    <select name="user[id_type_user]" id="id_type_user" class="form-control form-control-md selectTypeUser" required>
                                                        <?php foreach($userTypes as $key => $value): ?>
                                                            <?php if($user['id_type_user'] == $value['id_type']): ?>
                                                                <option value="<?php echo $user['id_type_user']; ?>" selected>
                                                                    <?php echo htmlspecialchars($user['name_type']); ?>
                                                                </option>
                                                            <?php else: ?>
                                                                <option value="<?php echo $value['id_type']; ?>">
                                                                    <?php echo htmlspecialchars($value['name_type']); ?>
                                                                </option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <!-- NUEVOS CAMPOS: Empresa (solo para clientes empresa) -->
                                                <div class="form-group" id="empresa-section">
                                                    <label for="id_company">Empresa: <span class="text-danger">*</span></label>
                                                    <select name="user[id_company]" class="form-control selectCompany" id="id_company">
                                                        <option value="">Seleccionar empresa...</option>
                                                        <?php foreach($companies as $company) { ?>
                                                            <option value="<?php echo $company['id_company']; ?>" 
                                                                    <?php echo (isset($_POST['user']['id_company']) ? 
                                                                              ($_POST['user']['id_company'] == $company['id_company'] ? 'selected' : '') : 
                                                                              ($user['id_company'] == $company['id_company'] ? 'selected' : '')); ?>>
                                                                <?php echo htmlspecialchars($company['name_company'] . ' (' . $company['rfc_company'] . ')'); ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                    <small class="form-text text-muted">Seleccione la empresa a la que pertenece este usuario</small>
                                                </div>

                                                

                                                <!-- Mostrar información actual de la empresa -->
                                                <?php if($user['id_company'] && $user['name_company']) { ?>
                                                <div class="card mb-3" id="empresa-actual">
                                                    <div class="card-header bg-info text-white">
                                                        <h6 class="mb-0">
                                                            <i class="fas fa-building"></i> Empresa Actual
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <strong>Empresa:</strong> <?php echo htmlspecialchars($user['name_company']); ?>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <strong>RFC:</strong> <?php echo htmlspecialchars($user['rfc_company']); ?>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-2">
                                                          <div class="col-lg-12">
                                                              <strong>Tipo:</strong> 
                                                              <span class="badge badge-info">Cliente Empresa</span>
                                                          </div>
                                                      </div>
                                                    </div>
                                                </div>
                                                <?php } else if($user['id_type_user'] == 3) { ?>
                                                <!-- Mostrar alerta si es cliente empresa pero no tiene empresa asignada -->
                                                <div class="empresa-warning">
                                                    <div class="alert alert-warning mb-0">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <strong>Atención:</strong> Este usuario es de tipo "Cliente Empresa" pero no tiene una empresa asignada.
                                                    </div>
                                                </div>
                                                <?php } ?>
                                            
                                                <!--Campo de RFC / rfc_user - CÓDIGO NUEVO-->
                                                <div class="form-group" style="display: none;" hidden>
                                                    <label for="rfc_user">RFC del usuario</label>
                                                    <?php if(empty($user['rfc_user']) || $user['rfc_user'] == NULL || $user['rfc_user'] == ''){ ?>
                                                        <input name="user[rfc_user]" type="text" class="form-control" id="rfc_user" required readonly value="<?php echo isset($_POST['user']['rfc_user']) ? htmlspecialchars($_POST['user']['rfc_user']) : $usr_rfc; ?>" autocomplete="off">
                                                    <?php } else { ?>
                                                        <input name="user[rfc_user]" type="text" class="form-control" id="rfc_user" required readonly value="<?php echo htmlspecialchars($user['rfc_user']); ?>" autocomplete="off">
                                                    <?php } ?>
                                                </div>
                                                
                                                <!--Campo de Teléfono / phone_user-->
                                                <div class="form-group">
                                                    <label for="phone_user">Número de teléfono</label>
                                                    <input name="user[phone_user]" type="text" class="form-control validate" id="phone_user" required title="Utiliza solo números. El número de teléfono debe tener 10 caracteres. Ejemplo: 8182597869" pattern="[0-9]{10}" maxlength="10" minlength="10" value="<?php echo htmlspecialchars($user['phone_user']); ?>" autocomplete="off">
                                                    <small class="error-msg" style="color:red; display: none;">*Utiliza solo números. El número de teléfono debe tener 10 caracteres</small>
                                                </div>
                                                
                                                <!--Campo de Correo Eletrónico / email_user-->
                                                <div class="form-group">
                                                    <label for="email_user">Correo electrónico</label>
                                                    <input name="user[email_user]" type="email" class="form-control validate" id="email_user" required maxlength="40" minlength="5" value="<?php echo htmlspecialchars($user['email_user']); ?>" autocomplete="off">
                                                    <small class="error-msg" style="color:red; display: none;">*Ingresa un correo electrónico válido</small>
                                                </div>
                                                
                                                <!--Campo de Password - Contraseña / password-->
                                                <div class="alert" role="alert" style="background-color: #F4EACD; color: #000000;">
                                                    ¡En caso de querer actualizar la contraseña, escriba la nueva contraseña!
                                                </div>

                                                <div class="form-group">
                                                    <label for="password_user">Password / Contraseña</label> (<small style="color:black;">*Para ver la contraseña dar click en el botón de la derecha</small>)
                                                    <div style="position: relative;">
                                                        <input name="user[password_user]" type="password" class="form-control validate" id="password_user" title="Introduce un password de mínimo 8 y máximo 15 caracteres. Ejemplo: $Contraseña123" maxlength="15" minlength="8" pattern="^(?=.*[!@#$%^&*])(?=.*[A-Z])(?=.*[0-9]).{8,}$" autocomplete="off">
                                                        <span class="password-toggle" onclick="togglePassword()">
                                                            <i class="fas fa-eye" id="eyeIconOpen" style="display: none;"></i>
                                                            <i class="fas fa-eye-slash" id="eyeIconClosed"></i>
                                                        </span>
                                                    </div>
                                                    <small class="error-msg" style="color:red; display: none;">*La contraseña debe contener al menos un símbolo especial, una letra mayúscula, un número y tener una longitud mínima de 8 caracteres. (Ejem. $Contraseña1234@)</small>
                                                </div>
                                                
                                                <!--Campo de ESTATUS / status_user -->
                                                <div class="form-group">
                                                    <label>Estatus</label>
                                                    <select name="user[status_user]" class="form-control form-control-md">
                                                        <?php if($user['status_user'] == 1){ ?>
                                                            <option value="1" selected="selected">Activo</option>
                                                            <option value="2">Inactivo</option>
                                                        <?php } else { ?>
                                                            <option value="1">Activo</option>
                                                            <option value="2" selected="selected">Inactivo</option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="card" style="height: 100%">
                                        <div class="card-body">
                                            <div class="col-md-12">
                                                
                                                <div class="form-group">
                                                    <label>Fecha de registro</label>
                                                    <input type="text" class="form-control" value="<?php echo date_format(date_create($user['created_at_user']), 'd/m/Y h:i a'); ?>" readonly disabled>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Última modificación</label>
                                                    <input type="text" class="form-control" value="<?php echo date_format(date_create($user['updated_at_user']), 'd/m/Y h:i a'); ?>" readonly disabled>
                                                </div>
                                                
                                                <!--Campo de FOTOGRAFÍA / photo_user-->
                                                <div class="form-group">
                                                    <label>Fotografía del usuario <small>(*Opcional)</small></label>
                                                    <input type="file" class="form-control" name="file-imguser" id="photo_user" accept="image/*">
                                                    
                                                    <!-- Modal para recorte de imagen -->
                                                    <div id="cropperModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Recortar Fotografía</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body text-center">
                                                                    <div>
                                                                        <img id="imagePreview" style="max-width: 100%; max-height: 400px;" />
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="modal-footer">
                                                                    <button type="button" id="cropImage" class="btn btn-primary">Recortar y Guardar</button>
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div style="text-align: center;"><br>
                                                        <?php if($user['photo_user'] != NULL){ ?>
                                                            <img width='280' height='280' style="border-radius: 50%; object-fit: cover;" src="<?php echo "../../uploads/users/".$user['photo_user']; ?>">
                                                        <?php } else { ?>
                                                            <img width='280' height='280' style="border-radius: 50%; object-fit: cover;" src="<?php echo "../../uploads/users/sin-foto.jpeg"; ?>">
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div><br>
                        <?php } ?>

                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                        <?php if($_SESSION['user']['id_type_user'] == 1){ ?>
                            <div class="alert" role="alert" style="text-align:center; font-size:20px; background-color: #37424A; color: #ffffff;">
                                ¡Favor de presionar <strong>una vez el botón de "actualizar usuario"</strong>, y esperar a que cargue la página!
                            </div>
                            <div class="form-group text-center">
                            <input type="hidden" name="action" value="update">
<button class="btn btn-lg" style="background-color: #37424A; color: #ffffff;" type="submit">Actualizar usuario</button>
                       </div>
                        <?php } ?>
                    </div>
                </form>
            </div><br>
        </div>
    </div>
    
    <script>
      $(document).ready(function(){
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
    <!-- Cropper.js JS -->
    <script src="../../resources/js/cropper.min.js"></script>
    <!-- Select2 -->
    <script src="../../resources/plugins/select2/js/select2.full.min.js"></script>
    
    <script>
      $(document).ready(function(){
        $('.selectTypeUser').select2({
          theme: 'bootstrap4'
        });
        
        $('.selectCompany').select2({
          theme: 'bootstrap4',
          placeholder: 'Seleccionar empresa...',
          allowClear: true
        });
      });
    </script>
    
    <!-- SCRIPT: Gestión de campos de empresa -->
    <script>
    $(document).ready(function() {
      // Función para mostrar/ocultar campos de empresa
        function toggleEmpresaFields() {
            var tipoUsuario = $('#id_type_user').val();
            
            if (tipoUsuario == '3') { // Cliente Empresa
              $('#empresa-section').show();
              $('#id_company').attr('required', true);
              
              // Mostrar información actual si existe
              <?php if($user['id_company']) { ?>
              $('#empresa-actual').show();
              <?php } ?>
            } else { // Admin o Empleado interno
              $('#empresa-section').hide();
              $('#empresa-actual').hide();
              $('#id_company').removeAttr('required').val('').trigger('change');
            }
        }
      
      // Ejecutar al cargar la página
      toggleEmpresaFields();
      
      // Ejecutar cuando cambie el tipo de usuario
      $('#id_type_user').change(function() {
        toggleEmpresaFields();
      });
      
      // Confirmación al cambiar empresa
      $('#id_company').change(function() {
        var empresaAnterior = '<?php echo $user['name_company'] ?? ''; ?>';
        var empresaNueva = $(this).find('option:selected').text();
        
        if (empresaAnterior && empresaNueva !== empresaAnterior && empresaNueva !== 'Seleccionar empresa...') {
          if (!confirm('¿Está seguro de cambiar la empresa de este usuario?\n\nEmpresa anterior: ' + empresaAnterior + '\nEmpresa nueva: ' + empresaNueva)) {
            $(this).val('<?php echo $user['id_company'] ?? ''; ?>').trigger('change');
          }
        }
      });
      
      // Validación antes de enviar el formulario
      $('form').on('submit', function(e) {
        var tipoUsuario = $('#id_type_user').val();
        var empresa = $('#id_company').val();
        
        // Validación específica para clientes empresa
        if (tipoUsuario == '3' && !empresa) {
          e.preventDefault();
          alert('Los usuarios de tipo Cliente Empresa deben tener una empresa asignada.');
          $('#id_company').focus();
          return false;
        }
        
        // Mostrar loading
        var submitBtn = $('button[name="action"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');
      });
    });
    </script>
    
    <script>
      // Función para convertir el valor del campo a mayúsculas
      function convertirAMayusculas(inputId) {
        var inputElement = document.getElementById(inputId);
        inputElement.addEventListener("input", function() {
          this.value = this.value.toUpperCase();
        });
      }
      
      // Función para permitir solo números en el campo
      function permitirSoloNumeros(inputId) {
        var inputElement = document.getElementById(inputId);
        inputElement.addEventListener("input", function() {
          this.value = this.value.replace(/[^0-9]/g, "");
        });
      }
      
      // Función para permitir solo texto (letras) en el campo
      function permitirSoloTexto(inputId) {
        var inputElement = document.getElementById(inputId);
        inputElement.addEventListener("input", function() {
          this.value = this.value.replace(/[^a-zA-ZñÑáÁéÉíÍóÓúÚ ]/g, "");
        });
      }

      // Llamar a las funciones para cada campo de entrada
      convertirAMayusculas("name_user");
      permitirSoloNumeros("phone_user");
      permitirSoloTexto("name_user");
    </script>
    
    <script>
      function togglePassword() {
        var passwordField = document.getElementById('password_user');
        var eyeIconOpen = document.getElementById('eyeIconOpen');
        var eyeIconClosed = document.getElementById('eyeIconClosed');
        passwordField.type = (passwordField.type === 'password') ? 'text' : 'password';
        
        eyeIconOpen.style.display = (passwordField.type === 'password') ? 'none' : 'inline-block';
        eyeIconClosed.style.display = (passwordField.type === 'password') ? 'inline-block' : 'none';
        
        eyeIconOpen.classList.add('clicked');
        eyeIconClosed.classList.add('clicked');
          
        setTimeout(function() {
          eyeIconOpen.classList.remove('clicked');
          eyeIconClosed.classList.remove('clicked');
        }, 200);
      }
    </script>
    
    <script>
      // Selecciona todos los campos de entrada y sus mensajes de error correspondientes
      const inputs = document.querySelectorAll('.validate');
      const errorMessages = document.querySelectorAll('.error-msg');
      
      inputs.forEach((input, index) => {
        input.addEventListener('input', () => {
          if (input.checkValidity()) {
            errorMessages[index].style.display = 'none';
          } else {
            errorMessages[index].style.display = 'block';
          }
        });
      });
    </script>
    
    <!--SCRIPT PARA RECORTAR LA FOTOGRAFÍA Y VER UNA VISTA PREVIA-->
    <script>
      let cropper;
      document.getElementById('photo_user').addEventListener('change', function(event) {
        const files = event.target.files;
        if (files && files.length > 0) {
          const file = files[0];
          
          // Validar tipo de archivo
          if (!file.type.startsWith('image/')) {
            alert('Por favor seleccione solo archivos de imagen.');
            this.value = '';
            return;
          }
          
          // Validar tamaño (máximo 5MB)
          if (file.size > 5 * 1024 * 1024) {
            alert('La imagen debe ser menor a 5MB.');
            this.value = '';
            return;
          }
          
          const reader = new FileReader();
          
          reader.onload = function(event) {
            const image = document.getElementById('imagePreview');
            image.src = event.target.result;
            $('#cropperModal').modal('show');
            
            if (cropper) {
              cropper.destroy();
            }
            cropper = new Cropper(image, {
              aspectRatio: 1,
              viewMode: 2,
              preview: '.preview',
            });
          };
          reader.readAsDataURL(file);
        }
      });
      
      document.getElementById('cropImage').addEventListener('click', function () {
        const fileInput = document.getElementById('photo_user');
        const originalFileName = fileInput.files[0]?.name || "cropped-image.png";
        
        cropper.getCroppedCanvas({
          width: 200,
          height: 200,
          imageSmoothingQuality: 'high',
        }).toBlob((blob) => {
          const file = new File([blob], originalFileName, { type: blob.type });
          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(file);
          fileInput.files = dataTransfer.files;
          
          $('#cropperModal').modal('hide');
        });
      });
    </script>
    
    <script>
      // Código para resetear el input de foto al cancelar o cerrar el modal
      document.addEventListener('DOMContentLoaded', function() {
        const cancelBtn = document.querySelector('#cropperModal .btn-secondary');
        if (cancelBtn) {
          cancelBtn.addEventListener('click', function() {
            document.getElementById('photo_user').value = '';
            if (cropper) {
              cropper.destroy();
            }
          });
        }
        
        const closeBtn = document.querySelector('#cropperModal .close');
        if (closeBtn) {
          closeBtn.addEventListener('click', function() {
            document.getElementById('photo_user').value = '';
            if (cropper) {
              cropper.destroy();
            }
          });
        }
        
        $('#cropperModal').on('hidden.bs.modal', function () {
          if (cropper) {
            cropper.destroy();
          }
        });
      });
    </script>



<script>
$(document).ready(function() {
    console.log('🔧 DEBUG: Página cargada, configurando eventos...');
    
    // Debug del botón específico
    $('button[name="action"]').on('click', function(e) {
        console.log('🔧 BUTTON CLICKED');
        console.log('🔧 Button name:', $(this).attr('name'));
        console.log('🔧 Button value:', $(this).attr('value'));
        console.log('🔧 Button type:', $(this).attr('type'));
        console.log('🔧 Form method:', $('form').attr('method'));
        console.log('🔧 Form action:', $('form').attr('action'));
    });
    
    // Debug del formulario
    $('form').on('submit', function(e) {
        console.log('🔧 FORM SUBMIT EVENT TRIGGERED');
        console.log('🔧 Event prevented?', e.isDefaultPrevented());
        
        // Verificar todos los datos del formulario
        var formData = new FormData(this);
        console.log('🔧 FORM DATA COMPLETE:');
        for (var pair of formData.entries()) {
            console.log('  ' + pair[0] + ': ' + pair[1]);
        }
        
        // Verificar específicamente el campo action
        var actionFromFormData = formData.get('action');
        console.log('🔧 ACTION FROM FORMDATA:', actionFromFormData);
        
        // Verificar el botón action
        var actionButton = $('button[name="action"]');
        console.log('🔧 ACTION BUTTON EXISTS:', actionButton.length > 0);
        console.log('🔧 ACTION BUTTON VALUE:', actionButton.val());
        
        // Verificar si hay algún preventDefault en otros scripts
        console.log('🔧 Form will submit in 2 seconds...');
        
        // NO prevenir el envío, solo debuggear
    });
    
    // Debug adicional para verificar conflictos
    console.log('🔧 jQuery version:', $.fn.jquery);
    console.log('🔧 Form exists:', $('form').length);
    console.log('🔧 Button exists:', $('button[name="action"]').length);
});
</script>

    
</body>
</html>