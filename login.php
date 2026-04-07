<?php
declare(strict_types=1);

session_start();
require_once "app/config.php";
require_once "app/WebController.php";

/**
 * Clase para manejar la autenticación de usuarios
 */
class AuthenticationHandler
{
    private WebController $controller;
    private string $message;
    
    public function __construct()
    {
        $this->controller = new WebController();
        $this->message = '';
    }
    
    /**
     * Verifica si el usuario ya está autenticado
     */
    public function isUserAuthenticated(): bool
    {
        return !empty($_SESSION['user']['login']);
    }
    
    /**
     * Redirige al usuario autenticado al dashboard
     */
    public function redirectToDashboard(): void
    {
        if ($this->isUserAuthenticated()) {
            header("Location: index.php");
            exit();
        }
    }
    
    /**
     * Valida los datos de entrada del formulario
     */
    public function validateLoginData(array $postData): array
    {
        $email = trim($postData['email_user'] ?? '');
        $password = $postData['password_user'] ?? '';
        
        $errors = [];
        
        if (empty($email)) {
            $errors[] = "El correo electrónico es requerido";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El formato del correo electrónico no es válido";
        }
        
        if (empty($password)) {
            $errors[] = "La contraseña es requerida";
        }
        
        return [
            'email' => $email,
            'password' => $password,
            'errors' => $errors,
            'is_valid' => empty($errors)
        ];
    }
    
    /**
     * Intenta autenticar al usuario
     */
    public function attemptLogin(string $email, string $password): bool
    {
        try {
            $user = $this->controller->loginUser($email, $password);
            
            if ($user === null || $user === false) {
                $this->message = "El correo electrónico o contraseña es inválida. Verifícala antes de volver a intentarlo";
                return false;
            }
            
            $this->createUserSession($user);
            return true;
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $this->message = "Error en el sistema. Por favor, intenta más tarde";
            return false;
        }
    }
    
    /**
     * Crea la sesión del usuario autenticado
     */
    private function createUserSession(array $user): void
    {
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
        
        $_SESSION['user'] = [
            'login' => true,
            'id_user' => (int)$user['id_user'],
            'id_type_user' => (int)$user['id_type_user'],
            'key_user' => $user['key_user'],
            'name_user' => $user['name_user'],
            'status_user' => (int)$user['status_user'],
            'login_time' => time(),
            'last_activity' => time()
        ];
    }
    
    /**
     * Procesa el formulario de login
     */
    public function processLoginForm(array $postData): bool
    {
        $validation = $this->validateLoginData($postData);
        
        if (!$validation['is_valid']) {
            $this->message = implode('. ', $validation['errors']);
            return false;
        }
        
        return $this->attemptLogin($validation['email'], $validation['password']);
    }
    
    /**
     * Redirige al usuario autenticado exitosamente
     */
    public function redirectAfterSuccessfulLogin(): void
    {
        header('Location: index.php');
        exit();
    }
    
    /**
     * Obtiene el mensaje de error/estado
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    
    /**
     * Establece un mensaje personalizado
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
    
    /**
     * Limpia la sesión por seguridad
     */
    public function clearSession(): void
    {
        session_unset();
        session_destroy();
    }
    
    /**
     * Obtiene el valor de un campo del formulario de manera segura
     */
    public function getFormValue(array $postData, string $field): string
    {
        return htmlspecialchars($postData[$field] ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Inicialización del sistema de autenticación
try {
    $auth = new AuthenticationHandler();
    
    // Verificar si el usuario ya está autenticado
    $auth->redirectToDashboard();
    
    // Procesar formulario si se envió
    if (!empty($_POST)) {
        if ($auth->processLoginForm($_POST)) {
            $auth->redirectAfterSuccessfulLogin();
        }
    }
    
    // Obtener mensaje para mostrar en la vista
    $mssg = $auth->getMessage();
    
    // Mantener compatibilidad para obtener valores del formulario
    $email_value = $auth->getFormValue($_POST, 'email_user');
    
} catch (Exception $e) {
    error_log("Authentication system error: " . $e->getMessage());
    
    // En caso de error crítico, limpiar sesión y mostrar mensaje genérico
    session_unset();
    session_destroy();
    $mssg = "Error en el sistema de autenticación. Por favor, contacta al administrador";
    $email_value = '';
} catch (TypeError $e) {
    error_log("Type error in authentication: " . $e->getMessage());
    
    session_unset();
    session_destroy();
    $mssg = "Error de configuración del sistema. Contacta al administrador";
    $email_value = '';
}

/**
 * Función helper para mostrar mensajes en la vista
 */
function displayMessage(string $message, string $type = 'error'): string
{
    if (empty($message)) {
        return '';
    }
    
    $alertClass = match($type) {
        'success' => 'alert-success',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
        default => 'alert-danger'
    };
    
    return sprintf(
        '<div class="alert %s alert-dismissible fade show" role="alert">
            %s
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>',
        $alertClass,
        htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
    );
}

/**
 * Función helper para generar token CSRF
 */
function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Función helper para validar token CSRF
 */
function validateCSRFToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
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
    <link rel="stylesheet" type="text/css" href="resources/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="resources/css/micromodal.css">
    <link rel="stylesheet" type="text/css" href="resources/css/login.css">
    <link rel="icon" href="resources/img/icono.png">
    <style>
      p {
        font-size: 15px;
        font-family: 'Arial', sans-serif;
        color: #333;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        padding: 5px;
        margin-bottom: 0px;
      }
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
      
      /* Estilo para el botón flotante */
      .floating-button {
        position: fixed;
        bottom: 20px; /* Distancia desde la parte inferior */
        left: 20px; /* Distancia desde la parte izquierda */
        z-index: 1000; /* Asegura que esté por encima de todo */
        background-color: transparent;
        border: none;
        cursor: pointer;
        outline: none; /* Elimina el contorno al hacer clic */
      }
      .floating-button img {
        width: 50px; /* Tamaño del ícono */
        height: 50px;
        border-radius: 50%;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }
      .floating-button img:hover {
        transform: scale(1.1); /* Efecto al pasar el mouse */
        box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.3);
      }
      .floating-btn:focus {
        outline: none !important;
      }
      
      /*ESTILO PARA QUITAR EL CONTORNO DE LA IMAGEN AL DAR CLIC*/
      .floating-button:focus,
      .floating-button img:focus {
        outline: none;
        box-shadow: none; /* Elimina cualquier sombra añadida */
      }
      
      .logo-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
      }
      .brand-title {
        margin-top: 8px;
        font-size: 18px;
        font-weight: 700;
        color: #1e3859;
      }
    </style>
  </head>
  
  <body>
    <div class="container">
      <div class="row">
        <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
          <div class="card card-signin my-5">
            <div class="card-body">
              <h5 class="card-title text-center">
                Compliance Hub
              </h5>
              <div class="text-center logo-container">
                <div class="brand-title">Demo</div>
              </div>
              <form class="form-signin" action="#" method="post">
                <div class="form-label-group">
                  <input name="email_user" type="email" id="email_user" class="form-control" placeholder="Usuario" required value="<?php echo isset($_POST['email_user']) ? htmlspecialchars($_POST['email_user']) : ''; ?>">
                  <label for="email_user">Correo electrónico</label>
                </div>
                <div class="form-label-group">
                  <input name="password_user" type="password" id="password_user" class="form-control" placeholder="Contraseña" required value="<?php echo isset($_POST['password_user']) ? htmlspecialchars($_POST['password_user']) : ''; ?>">
                  <label for="password_user">Contraseña</label>
                  <span class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye" style="display: none;"></i>
                    <i class="fas fa-eye-slash"></i>
                  </span>
                </div>
                <hr class="my-4">
                <button class="btn btn-lg btn-block text-uppercase" type="submit">Entrar</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="modal micromodal-slide" id="modal-1" aria-hidden="true">
      <div class="modal__overlay" tabindex="-1" data-micromodal-close>
        <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
          <header class="modal__header">
            <h2 class="modal__title" id="modal-1-title">
              <i class="fas fa-info-circle"></i> Aviso
            </h2>
            <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
          </header>
          <main class="modal__content" id="modal-1-content">
            <p><?php echo $mssg; ?></p>
          </main>
          <footer class="modal__footer">
            <button class="modal__btn" data-micromodal-close aria-label="Close this dialog window">Cerrar</button>
          </footer>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="resources/js/micromodal.min.js"></script>
    
    <script>
      function togglePassword() {
        var passwordField = document.getElementById('password_user');
        var eyeIconOpen = document.querySelector('.fa-eye');
        var eyeIconClosed = document.querySelector('.fa-eye-slash');
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
      $(document).ready(function(){
        MicroModal.init({
          openTrigger: 'data-custom-open',
          closeTrigger: 'data-custom-close',
          disableScroll: true,
          disableFocus: false,
          awaitCloseAnimation: false,
          debugMode: true
        });
        <?php if ($mssg)  { echo "MicroModal.show('modal-1');"; }?>
      });
    </script>
  </body>
</html>
