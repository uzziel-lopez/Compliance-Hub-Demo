<?php
declare(strict_types=1);

session_start();
require_once "app/config.php";
require_once "app/FileController.php";
require_once "app/WebController.php";

/**
 * Enumeración para los tipos de validación de usuario
 */
enum UserValidationType: string
{
    case NO_CHANGES = 'no_changes';
    case EMAIL_CHANGED = 'email_changed';
    case PHONE_CHANGED = 'phone_changed';
    case RFC_CHANGED = 'rfc_changed';
    case EMAIL_PHONE_CHANGED = 'email_phone_changed';
    case EMAIL_RFC_CHANGED = 'email_rfc_changed';
    case PHONE_RFC_CHANGED = 'phone_rfc_changed';
    case ALL_CHANGED = 'all_changed';
}

/**
 * Clase para manejar la actualización de perfil de usuario
 */
class UserProfileHandler
{
    private WebController $controller;
    private FileController $files;
    private string $message;
    private array $currentUser;
    
    public function __construct()
    {
        $this->controller = new WebController();
        $this->files = new FileController();
        $this->message = '';
    }
    
    /**
     * Verifica si la sesión del usuario está activa
     */
    public function validateSession(): void
    {
        if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
            session_destroy();
            header("Location: login.php");
            exit();
        }
    }
    
    /**
     * Obtiene los datos del usuario actual
     */
    public function getCurrentUser(): array
    {
        $this->currentUser = $this->controller->getDetailUser(
            $_SESSION['user']['id_user'], 
            $_SESSION['user']['key_user']
        );
        return $this->currentUser;
    }
    
    /**
     * Sube la foto del usuario
     */
    public function uploadUserPhoto(string $userKey): array
    {
        $filename = [];
        if (isset($_FILES['file-imguser']) && $_FILES['file-imguser']['error'] === UPLOAD_ERR_OK) {
            $filename['imguser'] = $this->files->upload($userKey, $_FILES['file-imguser'], "ord.imguser");
        }
        return $filename;
    }
    
    /**
     * Determina qué tipo de validación se necesita basado en los cambios
     */
    public function determineValidationType(array $newUserData): UserValidationType
    {
        $emailChanged = $newUserData['email_user'] !== $this->currentUser['email_user'];
        $phoneChanged = $newUserData['phone_user'] !== $this->currentUser['phone_user'];
        $rfcChanged = $newUserData['rfc_user'] !== $this->currentUser['rfc_user'];
        
        return match(true) {
            !$emailChanged && !$phoneChanged && !$rfcChanged => UserValidationType::NO_CHANGES,
            $emailChanged && !$phoneChanged && !$rfcChanged => UserValidationType::EMAIL_CHANGED,
            !$emailChanged && $phoneChanged && !$rfcChanged => UserValidationType::PHONE_CHANGED,
            !$emailChanged && !$phoneChanged && $rfcChanged => UserValidationType::RFC_CHANGED,
            $emailChanged && $phoneChanged && !$rfcChanged => UserValidationType::EMAIL_PHONE_CHANGED,
            $emailChanged && !$phoneChanged && $rfcChanged => UserValidationType::EMAIL_RFC_CHANGED,
            !$emailChanged && $phoneChanged && $rfcChanged => UserValidationType::PHONE_RFC_CHANGED,
            default => UserValidationType::ALL_CHANGED
        };
    }
    
    /**
     * Valida la disponibilidad de email, teléfono y RFC
     */
    public function validateUserDataAvailability(array $userData, UserValidationType $validationType): ?string
    {
        return match($validationType) {
            UserValidationType::NO_CHANGES => null,
            UserValidationType::EMAIL_CHANGED => $this->validateEmail($userData['email_user']),
            UserValidationType::PHONE_CHANGED => $this->validatePhone($userData['phone_user']),
            UserValidationType::RFC_CHANGED => $this->validateRFC($userData['rfc_user']),
            UserValidationType::EMAIL_PHONE_CHANGED => $this->validateEmailAndPhone($userData),
            UserValidationType::EMAIL_RFC_CHANGED => $this->validateEmailAndRFC($userData),
            UserValidationType::PHONE_RFC_CHANGED => $this->validatePhoneAndRFC($userData),
            UserValidationType::ALL_CHANGED => $this->validateAllFields($userData)
        };
    }
    
    /**
     * Valida solo el email
     */
    private function validateEmail(string $email): ?string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "El formato del correo electrónico no es válido";
        }
        
        $existingUser = $this->controller->getEmailUser($email);
        return !empty($existingUser) ? 
            "¡EL CORREO ELECTRÓNICO YA ESTÁ EN USO POR UN USUARIO ACTIVO. INTENTA CON OTRO!" : null;
    }
    
    /**
     * Valida solo el teléfono
     */
    private function validatePhone(string $phone): ?string
    {
        $existingUser = $this->controller->getPhoneUser($phone);
        return !empty($existingUser) ? 
            "¡EL NÚMERO DE TELÉFONO ESTÁ EN USO POR UN USUARIO ACTIVO, INTENTA CON OTRO!" : null;
    }
    
    /**
     * Valida solo el RFC
     */
    private function validateRFC(string $rfc): ?string
    {
        $existingUser = $this->controller->getRFCUser($rfc);
        return !empty($existingUser) ? 
            "¡EL RFC YA SE ENCUENTRA REGISTRADO, INTENTA CON OTRO!" : null;
    }
    
    /**
     * Valida email y teléfono
     */
    private function validateEmailAndPhone(array $userData): ?string
    {
        $emailError = $this->validateEmail($userData['email_user']);
        if ($emailError) return $emailError;
        
        return $this->validatePhone($userData['phone_user']);
    }
    
    /**
     * Valida email y RFC
     */
    private function validateEmailAndRFC(array $userData): ?string
    {
        $emailError = $this->validateEmail($userData['email_user']);
        if ($emailError) return $emailError;
        
        return $this->validateRFC($userData['rfc_user']);
    }
    
    /**
     * Valida teléfono y RFC
     */
    private function validatePhoneAndRFC(array $userData): ?string
    {
        $phoneError = $this->validatePhone($userData['phone_user']);
        if ($phoneError) return $phoneError;
        
        return $this->validateRFC($userData['rfc_user']);
    }
    
    /**
     * Valida todos los campos
     */
    private function validateAllFields(array $userData): ?string
    {
        $emailError = $this->validateEmail($userData['email_user']);
        if ($emailError) return $emailError;
        
        $phoneError = $this->validatePhone($userData['phone_user']);
        if ($phoneError) return $phoneError;
        
        return $this->validateRFC($userData['rfc_user']);
    }
    
    /**
     * Actualiza el usuario y su foto
     */
    public function updateUserProfile(array $userData): bool
    {
        try {
            $updateResult = $this->controller->updateUser($userData, $_SESSION['user']['id_user']);
            
            if (!$updateResult) {
                $this->message = "Error al actualizar el perfil del usuario";
                return false;
            }
            
            $this->updateUserPhoto();
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating user profile: " . $e->getMessage());
            $this->message = "Error interno del sistema";
            return false;
        }
    }
    
    /**
     * Actualiza la foto del usuario
     */
    private function updateUserPhoto(): void
    {
        $uploadedFiles = $this->uploadUserPhoto($this->currentUser['key_user']);
        
        $photoData = [
            'imguser' => $uploadedFiles['imguser'] ?? $this->currentUser['photo_user']
        ];
        
        $this->controller->updatePhotoUser($this->currentUser['id_user'], $photoData);
    }
    
    /**
     * Procesa la actualización del perfil
     */
    public function processProfileUpdate(array $postData): bool
    {
        if (empty($postData['action']) || $postData['action'] !== 'update') {
            return false;
        }
        
        if (empty($postData['user']) || !is_array($postData['user'])) {
            $this->message = "Datos de usuario inválidos";
            return false;
        }
        
        $userData = $this->sanitizeUserData($postData['user']);
        $validationType = $this->determineValidationType($userData);
        $validationError = $this->validateUserDataAvailability($userData, $validationType);
        
        if ($validationError) {
            $this->message = $validationError;
            return false;
        }
        
        if ($this->updateUserProfile($userData)) {
            header('Location: index.php');
            exit();
        }
        
        return false;
    }
    
    /**
     * Sanitiza los datos del usuario
     */
    private function sanitizeUserData(array $userData): array
    {
        return [
            'name_user' => trim($userData['name_user'] ?? ''),
            'email_user' => trim(strtolower($userData['email_user'] ?? '')),
            'phone_user' => trim($userData['phone_user'] ?? ''),
            'rfc_user' => trim(strtoupper($userData['rfc_user'] ?? '')),
            'id_type_user' => (int)($userData['id_type_user'] ?? 0),
            'status_user' => (int)($userData['status_user'] ?? 1),
            'password_user' => $userData['password_user'] ?? ''
        ];
    }
    
    /**
     * Valida los datos básicos del formulario
     */
    public function validateFormData(array $userData): array
    {
        $errors = [];
        
        if (empty($userData['name_user'])) {
            $errors[] = "El nombre es requerido";
        }
        
        if (empty($userData['email_user'])) {
            $errors[] = "El correo electrónico es requerido";
        } elseif (!filter_var($userData['email_user'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El formato del correo electrónico no es válido";
        }
        
        if (empty($userData['phone_user'])) {
            $errors[] = "El teléfono es requerido";
        }
        
        if (empty($userData['rfc_user'])) {
            $errors[] = "El RFC es requerido";
        }
        
        return $errors;
    }
    
    /**
     * Obtiene el mensaje de error
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    
    /**
     * Establece un mensaje
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
    
    /**
     * Genera un RFC aleatorio (manteniendo compatibilidad)
     */
    public function generateRandomRFC(): string
    {
        $permittedChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($permittedChars), 0, 13);
    }
}

// Inicialización del sistema
try {
    $profileHandler = new UserProfileHandler();
    
    // Validar sesión
    $profileHandler->validateSession();
    
    // Obtener datos del usuario actual
    $user = $profileHandler->getCurrentUser();
    
    // Generar RFC aleatorio para compatibilidad
    $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $usr_rfc = substr(str_shuffle($permitted_chars), 0, 13);
    
    // Inicializar mensaje
    $mssg = '';
    
    // Procesar formulario si se envió
    if (!empty($_POST)) {
        if (!$profileHandler->processProfileUpdate($_POST)) {
            $mssg = $profileHandler->getMessage();
        }
    }
    
    // Instanciar controladores para compatibilidad
    $controller = new WebController();
    $files = new FileController();
    
    // Función de compatibilidad para subir foto
    function uploadFilePhoto(string $folio, ?array $filename = null): array {
        global $files;
        $filename = $filename ?? [];
        if (isset($_FILES['file-imguser']) && $_FILES['file-imguser']['error'] === UPLOAD_ERR_OK) {
            $filename['imguser'] = $files->upload($folio, $_FILES['file-imguser'], "ord.imguser");
        }
        return $filename;
    }
    
} catch (Exception $e) {
    error_log("Profile system error: " . $e->getMessage());
    session_destroy();
    header("Location: login.php?error=system");
    exit();
} catch (TypeError $e) {
    error_log("Type error in profile: " . $e->getMessage());
    session_destroy();
    header("Location: login.php?error=type");
    exit();
}

/**
 * Función helper para mostrar errores de validación
 */
function displayValidationErrors(array $errors): string
{
    if (empty($errors)) {
        return '';
    }
    
    $errorList = implode('</li><li>', array_map(
        fn($error) => htmlspecialchars($error, ENT_QUOTES, 'UTF-8'),
        $errors
    ));
    
    return sprintf(
        '<div class="alert alert-danger">
            <ul class="mb-0">
                <li>%s</li>
            </ul>
        </div>',
        $errorList
    );
}

/**
 * Función helper para validar archivos de imagen
 */
function validateImageFile(array $file): array
{
    $errors = [];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return match($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => ['El archivo es demasiado grande'],
            UPLOAD_ERR_PARTIAL => ['El archivo se subió parcialmente'],
            UPLOAD_ERR_NO_FILE => [], // No hay archivo, es válido
            default => ['Error desconocido al subir el archivo']
        };
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        $errors[] = 'Solo se permiten archivos de imagen (JPEG, PNG, GIF)';
    }
    
    if ($file['size'] > $maxSize) {
        $errors[] = 'El archivo no puede ser mayor a 5MB';
    }
    
    return $errors;
}
?>

<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Compliance Hub</title>
    <link rel="stylesheet" href="resources/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="resources/plugins/datatables-bs4/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="resources/dist/css/adminlte.min.css">
    <!-- Cropper.js CSS -->
    <!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />-->
    <link rel="stylesheet" href="resources/css/cropper.min.css" />
    
    <link rel="icon" href="resources/img/icono.png">
    <script src="resources/js/jquery-3.5.1.min.js"></script>
    <style>
      /* Estilos adicionales para el botón de "ver contraseña" */
      .password-toggle {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        transition: transform 0.2s, font-size 0.2s; /* Agrega una transición a la transformación y al tamaño de la fuente */
      }
      .password-toggle:hover {
        color: #007bff; /* Cambia el color al hacer hover */
      }
      .password-toggle.clicked {
        transform: scale(1.2); /* Cambia la escala al hacer clic */
      }
      /* Ajustes para el campo de contraseña */
      #password {
        padding-right: 30px; /* Ajusta el espacio a la derecha para acomodar el ícono */
      }
    </style>
  </head>
  
  <body class="hold-transition sidebar-mini">
    <div class="wrapper" style="padding-top: 57px;">
      <?php include "navbar.php"; ?>
      
      <div class="content-wrapper">
        <div class="content-header">
          <div class="container-fluid">
            <div class="row mb-2">
              <div class="col-sm-8">
                <h1 class="m-0 text-dark">Actualizar información</h1>
              </div>
              <div class="col-sm-4 text-right">
                <!--<a href="<?=$_SERVER["HTTP_REFERER"]?>" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;" role="button" aria-pressed="true">Regresar</a>-->
                <a href="index.php" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;" role="button" aria-pressed="true">Regresar</a>
              </div>
            </div>
            <hr>
            <?php if (!empty($mssg)) { ?>
              <div class="row">
                <div class="col-12 pt-3">
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
          <form action="#" method="post" enctype="multipart/form-data">
            <div class="container-fluid">
              <div class="row">

                <div class="col-lg-6 col-md-6 col-sm-12">
                  <div class="card" style="height: 100%">
                    <div class="card-body">
                      <div class="col-md-12">
                        
                        <!--Campo de Nombre / name_user-->
                        <div class="form-group">
                          <label for="name_user">Nombre del empleado</label>
                          <input name="user[name_user]" type="text" class="form-control validate" id="name_user" required title="Utiliza solo letras como mínimo 3 y máximo 40" pattern="[a-zA-ZñÑáÁéÉíÍóÓúÚ ]{3,40}" maxlength="40" minlength="3" value="<?php echo $user['name_user']; ?>" autocomplete="off">
                          <small class="error-msg" style="color:red; display: none;">*Utiliza solo letras como mínimo 3 y máximo 40 caracteres</small>
                        </div>
                        <!--ID del tipo de Usuario / id_type_user -->
                        <input name="user[id_type_user]" value="<?php echo $user['id_type_user']; ?>" type="text" class="form-control" id="id_type_user" readonly hidden>
                        <div class="form-group">
                          <label>Tipo de empleado</label>
                          <input type="text" class="form-control" value="<?php echo $user['name_type']; ?>" readonly disabled>
                        </div>
                        
                        <!--Campo de RFC / rfc_user - CÓDIGO ANTIGUO-->
                        <!--<div class="form-group">
                          <label for="rfc_user">RFC del empleado</label>
                          <input name="user[rfc_user]" type="text" class="form-control" id="rfc_user" required title="Utiliza letras y números. El RFC debe tener 13 caracteres." pattern="[a-zA-ZñÑáÁéÉíÍóÓúÚ0-9 ]{13}" maxlength="13" minlength="13" value="<?php echo $user['rfc_user']; ?>" autocomplete="off">
                          <small class="error-msg" style="color:red; display: none;">*Utiliza letras y números. El RFC debe tener 13 caracteres</small>
                        </div>-->

                        <!--Campo de RFC / rfc_user - CÓDIGO NUEVO-->
                        <div class="form-group" style="display: none;" hidden>
                          <label for="rfc_user">RFC del usuario</label>
                          <?php if(empty($user['rfc_user']) || $user['rfc_user'] == NULL || $user['rfc_user'] == ''){ ?>
                            <input name="user[rfc_user]" type="text" class="form-control" id="rfc_user" required readonly value="<?php echo isset($_POST['user']['rfc_user']) ? htmlspecialchars($_POST['user']['rfc_user']) : $usr_rfc; ?>" autocomplete="off">
                          <?php } else { ?>
                            <input name="user[rfc_user]" type="text" class="form-control" id="rfc_user" required readonly value="<?php echo $user['rfc_user']; ?>" autocomplete="off">
                          <?php } ?>
                        </div>
                        
                        <!--Campo de Teléfono / phone_user-->
                        <div class="form-group">
                          <label for="phone_user">Número de teléfono</label>
                          <input name="user[phone_user]" type="text" class="form-control validate" id="phone_user" required title="Utiliza solo números. El número de teléfono debe tener 10 caracteres. Ejemplo: 8182597869" pattern="[0-9]{10}" maxlength="10" minlength="10" value="<?php echo $user['phone_user']; ?>" autocomplete="off">
                          <small class="error-msg" style="color:red; display: none;">*Utiliza solo números. El número de teléfono debe tener 10 caracteres</small>
                        </div>
                        
                        <!--Campo de Correo Eletrónico / email_user-->
                        <div class="form-group">
                          <label for="email_user">Correo electrónico</label>
                          <input name="user[email_user]" type="email" class="form-control validate" id="email_user" required maxlength="40" minlength="5" value="<?php echo $user['email_user']; ?>" autocomplete="off">
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
                          <input name="user[status_user]" value="<?php echo $user['status_user']; ?>" type="text" class="form-control" id="status_user" readonly hidden>
                          <label>Estatus</label>
                          <input type="text" class="form-control" value="Empleado activo" readonly disabled>
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
                          <label>Fotografía del empleado <small>(*Opcional)</small></label>
                          <input type="file" class="form-control" name="file-imguser" id="photo_user" accept="image/*">
                          <!--<small style="color:red;">*Campo opcional (solo si se desea actualizar la fotografía)</small>-->
                          
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
                              <img width='280' height='280' style="border-radius: 50%; object-fit: cover;" src="<?php echo "uploads/users/".$user['photo_user']; ?>">
                            <?php } else { ?>
                              <img width='280' height='280' style="border-radius: 50%; object-fit: cover;" src="<?php echo "uploads/users/sin-foto.jpeg"; ?>">
                              <!--<p style="color: red; font-weight:bold;">Sin foto de perfil</p>-->
                            <?php } ?>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
                </div>
              </div><br>
              
              <div class="alert" role="alert" style="text-align:center; font-size:20px; background-color: #37424A; color: #ffffff;">
                ¡Favor de presionar <strong>una vez el botón de "actualizar información"</strong>, y esperar a que cargue la página!
              </div>
              <div class="form-group  text-center">
                <button class="btn btn-lg" style="background-color: #37424A; color: #ffffff;" name="action" value="update">Actualizar información</button>
              </div>

            </div>
          </form>
        </div><br>
      </div>
    </div>
    <script>
      $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip({
          delay: { "show": 0, "hide": 0 } // Hacer que el tooltip aparezca y desaparezca inmediatamente
        });   
      });
    </script>
    <script src="resources/plugins/jquery/jquery.min.js"></script>
    <script src="resources/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="resources/dist/js/adminlte.min.js"></script>
    <script src="resources/js/notifications.js"></script>
    <script src="resources/js/tracings.js"></script>
    <script src="resources/js/notify_folders.js"></script>
    <!-- Cropper.js JS -->
    <!--<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>-->
    <script src="resources/js/cropper.min.js"></script>
    
    <script>
      // Función para convertir el valor del campo a mayúsculas
      function convertirAMayusculas(inputId) {
        // Obtener el campo de entrada por su id
        var inputElement = document.getElementById(inputId);
        // Agregar un evento que se dispare cuando el usuario escriba en el campo
        inputElement.addEventListener("input", function() {
          // Convertir el valor a mayúsculas y establecerlo nuevamente en el campo
          this.value = this.value.toUpperCase();
        });
      }
      // Función para permitir solo números en el campo
      function permitirSoloNumeros(inputId) {
        // Obtener el campo de entrada por su id
        var inputElement = document.getElementById(inputId);
        // Agregar un controlador de eventos para bloquear la entrada no numérica
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
      //convertirAMayusculas("rfc_user");
      permitirSoloNumeros("phone_user");
      permitirSoloTexto("name_user");
    </script>
    <script>
      function togglePassword() {
        var passwordField = document.getElementById('password_user');
        var eyeIconOpen = document.getElementById('eyeIconOpen');
        var eyeIconClosed = document.getElementById('eyeIconClosed');
        passwordField.type = (passwordField.type === 'password') ? 'text' : 'password';
        // Alterna la visibilidad de los iconos de ojo abierto y cerrado
        eyeIconOpen.style.display = (passwordField.type === 'password') ? 'none' : 'inline-block';
        eyeIconClosed.style.display = (passwordField.type === 'password') ? 'inline-block' : 'none';
        // Agrega y remueve la clase 'clicked' para la animación de cambio de tamaño
        eyeIconOpen.classList.add('clicked');
        eyeIconClosed.classList.add('clicked');
        
        setTimeout(function() {
          eyeIconOpen.classList.remove('clicked');
          eyeIconClosed.classList.remove('clicked');
        }, 200); // Ajusta el tiempo de la animación según sea necesario
      }
    </script>
    <script>
      // Selecciona todos los campos de entrada y sus mensajes de error correspondientes
      const inputs = document.querySelectorAll('.validate');
      const errorMessages = document.querySelectorAll('.error-msg');
      // Añade un evento para cada campo de entrada que se ejecute cuando cambie el valor
      inputs.forEach((input, index) => {
        input.addEventListener('input', () => {
          if (input.checkValidity()) {
            errorMessages[index].style.display = 'none';  // Oculta el mensaje de error si el valor es válido
          } else {
            errorMessages[index].style.display = 'block';  // Muestra el mensaje de error si el valor es inválido
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
          const reader = new FileReader();
          
          reader.onload = function(event) {
            // Mostrar la imagen en el modal
            const image = document.getElementById('imagePreview');
            image.src = event.target.result;
            // Mostrar el modal
            $('#cropperModal').modal('show');
            // Inicializar Cropper.js
            if (cropper) {
              cropper.destroy(); // Destruir cualquier instancia previa
            }
            cropper = new Cropper(image, {
              aspectRatio: 1, // Relación 1:1 para un recorte circular
              viewMode: 2,
              preview: '.preview', // Opcional: añade un contenedor para previsualización
            });
          };
          reader.readAsDataURL(file);
        }
      });
      
      document.getElementById('cropImage').addEventListener('click', function () {
        // Obtener el nombre original del archivo
        const fileInput = document.getElementById('photo_user');
        const originalFileName = fileInput.files[0]?.name || "cropped-image.png";
        
        cropper.getCroppedCanvas({
          width: 200,
          height: 200,
          imageSmoothingQuality: 'high',
        }).toBlob((blob) => {
          // Crear un archivo con el nombre original y el contenido recortado
          const file = new File([blob], originalFileName, { type: blob.type });
          // Asignar el archivo al input de tipo file usando DataTransfer
          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(file);
          fileInput.files = dataTransfer.files;
          
          // Ocultar el modal después de asignar el archivo
          $('#cropperModal').modal('hide');
        });
      });
    </script>
    
    <script>
      // Código para resetear el input de foto al cancelar o cerrar el modal
      document.querySelector('.btn-secondary').addEventListener('click', function() {
        document.getElementById('photo_user').value = '';
      });
      // Agregar evento al botón de cerrar (la 'x')
      document.querySelector('.close').addEventListener('click', function() {
        document.getElementById('photo_user').value = '';
      });
    </script>

  </body>
</html>