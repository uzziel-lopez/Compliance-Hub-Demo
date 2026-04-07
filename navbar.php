<?php
declare(strict_types=1);

/**
 * Enumeración para las secciones de navegación
 */
enum NavigationSection: string
{
    case USERS = 'users';
    case CLIENTS = 'clients';
    case COMPANIES = 'companies';  // ← AGREGAR ESTA LÍNEA

    case DASHBOARD = 'dashboard';
    case MATERIALS = 'materials';
    case VULNERABILITIES = 'vulnerabilities';  // Nueva sección añadida
    case OTHER = 'other';
}

/**
 * Clase para manejar la navegación y el estado de la barra de navegación
 */
class NavigationHandler
{
    private WebController $controller;
    private array $userNav;
    private string $currentPage;
    private array $navigationPages;
    
    public function __construct(WebController $controller)
    {
        $this->controller = $controller;
        $this->currentPage = basename($_SERVER['PHP_SELF']);
        $this->initializeNavigationPages();
        $this->loadUserNavigation();
    }
    
    /**
     * Inicializa la configuración de páginas por sección
     */
    private function initializeNavigationPages(): void
    {
        $this->navigationPages = [
            NavigationSection::USERS->value => [
                'create-user.php',
                'detail-user.php', 
                'update-user.php',
                'users.php'
            ],
            NavigationSection::CLIENTS->value => [
                'folders.php',
                'subfolder.php'
            ],
                    NavigationSection::COMPANIES->value => [  // ← AGREGAR ESTA SECCIÓN
            'companies.php',
            'create-company.php',
            'update-company.php',
            'detail-company.php'
        ],
            NavigationSection::DASHBOARD->value => [
                'all_folders.php'
            ],
            NavigationSection::MATERIALS->value => [
                'resources.php'
            ],
            NavigationSection::VULNERABILITIES->value => [  // Nueva sección
                'vulnerabilities.php',
                'vulnerability-scan.php',
                'security-audit.php'
            ]
        ];
    }
    
    /**
     * Carga los datos de navegación del usuario
     */
    private function loadUserNavigation(): void
    {
        if (!isset($_SESSION['user']['id_user'], $_SESSION['user']['key_user'])) {
            throw new InvalidArgumentException('Session user data is invalid');
        }
        
        $this->userNav = $this->controller->getDetailUser(
            $_SESSION['user']['id_user'], 
            $_SESSION['user']['key_user']
        );
        
        if (empty($this->userNav)) {
            throw new RuntimeException('User navigation data could not be loaded');
        }
    }
    
    /**
     * Obtiene los datos del usuario para la navegación
     */
    public function getUserNavigation(): array
    {
        return $this->userNav;
    }
    
    /**
     * Obtiene la página actual
     */
    public function getCurrentPage(): string
    {
        return $this->currentPage;
    }
    
    /**
     * Determina la sección activa basada en la página actual
     */
    public function getActiveSection(): NavigationSection
    {
        foreach ($this->navigationPages as $section => $pages) {
            if (in_array($this->currentPage, $pages, true)) {
                return NavigationSection::from($section);
            }
        }
        
        return NavigationSection::OTHER;
    }
    
    /**
     * Verifica si una página específica está activa
     */
    public function isPageActive(string $page): bool
    {
        return $this->currentPage === $page;
    }
    
    /**
     * Verifica si una sección específica está activa
     */
    public function isSectionActive(NavigationSection $section): bool
    {
        return $this->getActiveSection() === $section;
    }
    
    /**
     * Obtiene las páginas de una sección específica
     */
    public function getSectionPages(NavigationSection $section): array
    {
        return $this->navigationPages[$section->value] ?? [];
    }
    
    /**
     * Obtiene todas las páginas de navegación
     */
    public function getAllNavigationPages(): array
    {
        return $this->navigationPages;
    }
    
    /**
     * Genera las clases CSS para un elemento de navegación
     */
    public function getNavItemClasses(string $page, string $baseClass = 'nav-link'): string
    {
        $classes = [$baseClass];
        
        if ($this->isPageActive($page)) {
            $classes[] = 'active';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Genera las clases CSS para una sección de navegación
     */
    public function getNavSectionClasses(NavigationSection $section, string $baseClass = 'nav-section'): string
    {
        $classes = [$baseClass];
        
        if ($this->isSectionActive($section)) {
            $classes[] = 'active';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Verifica si el usuario tiene permisos para acceder a una sección
     */
    public function hasAccessToSection(NavigationSection $section): bool
    {
        $userType = (int)($this->userNav['id_type_user'] ?? 0);
        
        return match($section) {
            NavigationSection::USERS => in_array($userType, [1, 2]), // Admin y Supervisor
            NavigationSection::CLIENTS => in_array($userType, [1, 2, 3]), // Admin, Supervisor y Ventas
            NavigationSection::COMPANIES => in_array($userType, [1,3]), // Solo Administradores
            NavigationSection::DASHBOARD => in_array($userType, [1, 2]), // Admin y Supervisor
            NavigationSection::MATERIALS => in_array($userType, [1, 2, 3]), // Todos los tipos
            NavigationSection::VULNERABILITIES => in_array($userType, [1,2,3]), // Solo Administradores
            NavigationSection::OTHER => true
        };
    }
    
    /**
     * Obtiene el título de una sección
     */
    public function getSectionTitle(NavigationSection $section): string
    {
        return match($section) {
            NavigationSection::USERS => 'Gestión de Usuarios',
            NavigationSection::CLIENTS => 'Gestión de Clientes',
            NavigationSection::COMPANIES => 'Gestión de Empresas',
            NavigationSection::DASHBOARD => 'Tablero de Control',
            NavigationSection::MATERIALS => 'Material de Apoyo',
            NavigationSection::VULNERABILITIES => 'Operaciones Vulnerables',  // Nuevo título
            NavigationSection::OTHER => 'Otras Páginas'
        };
    }
    
    /**
     * Obtiene el ícono de una sección
     */
    public function getSectionIcon(NavigationSection $section): string
    {
        return match($section) {
            NavigationSection::USERS => 'fas fa-users',
            NavigationSection::CLIENTS => 'fas fa-folder-open',
            NavigationSection::COMPANIES => 'fas fa-building',
            NavigationSection::DASHBOARD => 'fas fa-chart-bar',
            NavigationSection::MATERIALS => 'fas fa-book',
            NavigationSection::VULNERABILITIES => 'fas fa-shield-alt',  // Nuevo ícono de seguridad
            NavigationSection::OTHER => 'fas fa-ellipsis-h'
        };
    }
    
    /**
     * Genera breadcrumbs basados en la página actual
     */
    public function generateBreadcrumbs(): array
    {
        $section = $this->getActiveSection();
        $breadcrumbs = [
            ['title' => 'Inicio', 'url' => 'index.php']
        ];
        
        if ($section !== NavigationSection::OTHER) {
            $breadcrumbs[] = [
                'title' => $this->getSectionTitle($section),
                'url' => $this->getSectionPages($section)[0] ?? '#'
            ];
        }
        
        // Agregar página actual si no es la primera de la sección
        $sectionPages = $this->getSectionPages($section);
        if (!empty($sectionPages) && $sectionPages[0] !== $this->currentPage) {
            $breadcrumbs[] = [
                'title' => $this->getPageTitle($this->currentPage),
                'url' => null // Página actual, sin enlace
            ];
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Obtiene el título de una página específica
     */
    private function getPageTitle(string $page): string
    {
        $titles = [
            'users.php' => 'Lista de Usuarios',
            'create-user.php' => 'Crear Usuario',
            'detail-user.php' => 'Detalle de Usuario',
            'update-user.php' => 'Actualizar Usuario',
            'folders.php' => 'Carpetas de Clientes',
            'subfolder.php' => 'Subcarpetas',
            'all_folders.php' => 'Todas las Carpetas',
            'resources.php' => 'Material de Apoyo',
            'vulnerabilities.php' => 'Operaciones Vulnerables',  // Nuevo título
            'vulnerability-scan.php' => 'Escaneo de Vulnerabilidades',
            'security-audit.php' => 'Auditoría de Seguridad'
        ];
        
        return $titles[$page] ?? ucfirst(str_replace(['.php', '-', '_'], ['', ' ', ' '], $page));
    }
}

// Verificar que existe el controlador y la sesión
if (!isset($controller) || !($controller instanceof WebController)) {
    throw new RuntimeException('WebController instance is required for navigation');
}

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    throw new RuntimeException('User session is required for navigation');
}

try {
    // Inicializar el manejador de navegación
    $navigationHandler = new NavigationHandler($controller);
    
    // Obtener datos para compatibilidad con el código existente
    $userNav = $navigationHandler->getUserNavigation();
    $current_page = $navigationHandler->getCurrentPage();
    
    // Mantener arrays originales para compatibilidad
    $users_pages = $navigationHandler->getSectionPages(NavigationSection::USERS);
    $clientes_pages = $navigationHandler->getSectionPages(NavigationSection::CLIENTS);
    $empresas_pages = $navigationHandler->getSectionPages(NavigationSection::COMPANIES);  // ← AGREGAR ESTA LÍNEA
    $tablero_pages = $navigationHandler->getSectionPages(NavigationSection::DASHBOARD);
    $material_pages = $navigationHandler->getSectionPages(NavigationSection::MATERIALS);
    $vulnerabilities_pages = $navigationHandler->getSectionPages(NavigationSection::VULNERABILITIES);  // Nueva variable


    
} catch (Exception $e) {
    error_log("Navigation error: " . $e->getMessage());
    
    // Fallback para mantener funcionalidad básica
    $userNav = [];
    $current_page = basename($_SERVER['PHP_SELF']);
    $users_pages = ['create-user.php', 'detail-user.php', 'update-user.php', 'users.php'];
    $clientes_pages = ['folders.php', 'subfolder.php'];
    $tablero_pages = ['all_folders.php'];
    $material_pages = ['resources.php'];
    $vulnerabilities_pages = ['vulnerabilities.php', 'vulnerability-scan.php', 'security-audit.php'];  // Fallback
    $empresas_pages = ['companies.php', 'create-company.php', 'update-company.php', 'detail-company.php'];

}

/**
 * Funciones helper para usar en las vistas
 */

/**
 * Verifica si una página está activa
 */
function isPageActive(string $page): bool
{
    global $navigationHandler;
    return $navigationHandler?->isPageActive($page) ?? (basename($_SERVER['PHP_SELF']) === $page);
}

/**
 * Verifica si una sección está activa
 */
function isSectionActive(array $pages): bool
{
    global $current_page;
    return in_array($current_page, $pages, true);
}

/**
 * Genera clases CSS para elementos de navegación
 */
function getNavClasses(string $page, string $baseClass = 'nav-link'): string
{
    $classes = [$baseClass];
    if (isPageActive($page)) {
        $classes[] = 'active';
    }
    return implode(' ', $classes);
}

/**
 * Obtiene el nombre de usuario para mostrar en la navegación
 */
function getUserDisplayName(): string
{
    global $userNav;
    return htmlspecialchars($userNav['name_user'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
}

/**
 * Obtiene el tipo de usuario
 */
function getUserType(): string
{
    global $userNav;
    $typeNames = [
        1 => 'Administrador',
        2 => 'Supervisor', 
        3 => 'Ventas',
        4 => 'Usuario'
    ];
    
    $typeId = (int)($userNav['id_type_user'] ?? 0);
    return $typeNames[$typeId] ?? 'Usuario';
}

/**
 * Verifica si el usuario tiene permisos de administrador
 */
function isAdmin(): bool
{
    global $userNav;
    return ((int)($userNav['id_type_user'] ?? 0)) === 1;
}

/**
 * Verifica si el usuario tiene permisos de supervisor o superior
 */
function isSupervisorOrAbove(): bool
{
    global $userNav;
    return in_array((int)($userNav['id_type_user'] ?? 0), [1, 2], true);
}
?>

<style>
  .navbar-custom-menu {
    display: flex;
    align-items: center;
  }
  .navbar-custom-menu .nav-link {
    color: white;
  }
  .navbar-left {
    display: flex;
    align-items: center;
  }
  .user-name {
    color: white;
    font-weight: bold;
    margin-left: 4px;
  }
  /*DISEÑO DEL BOTÓN PARA CERRAR SESIÓN*/
  .logout-icon {
    margin-top:1px;
    font-size: 1.3rem; /* Ajusta este valor para hacer el ícono más grande */
    color: #ffffff; /* Color del ícono */
    transition: color 0.3s; /* Efecto suave al cambiar de color */
  }
  .logout-icon:hover {
    color: #ffffff; /* Cambia el color al pasar el mouse */
  }
  
  /*DISEÑO PARA MANTENER SELECCIONADA UNA OPCIÓN DEL MENÚ DEPENDIENDO LA PAGINA*/
  /* Asegura que la clase active tenga un estilo resaltado */
  .nav-link.active {
    background-color: #4B555C !important; /* Color de fondo para la opción activa */
    color: #000000; /* Color del texto */
  }
  /* Cambia el color del ícono en la opción activa */
  .nav-link.active .nav-icon {
    color: #ffffff; /* Color del ícono en la opción activa */
  }
  

  
  /* Estilos para el contenedor de las notificaciones de los documentos */
  .dropNotifications {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: #ffffff;
    border: 1px solid #ccc;
    display: none;
    z-index: 999;
    width: 450px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    max-height: 300px;
    overflow-y: auto;
    border-radius: 10px;
  }
  .dropNotifications ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .dropNotifications ul li {
    padding: 15px;
    color: #000000;
    border-bottom: 1px solid #ccc;
  }
  .dropNotifications ul li:hover {
    background-color: #f0f0f0;
  }
  .notifications-archive-container {
    position: relative;
  }
  .notifications-archive-container .fa-bell {
    cursor: pointer;
    margin-left: 5px;
  }
  .dropdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #ccc;
  }
  .dropdown-header h3 {
    margin: 0;
    font-size: 16px;
    color: #000;
  }
  .dropdown-header button {
    padding: 5px 10px;
    font-size: 14px;
    color: #fff;
    background-color: #007bff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  .dropdown-header button:hover {
    background-color: #0056b3;
  }
  
  /* Estilos para el contenedor de las notificaciones de los seguimientos o tracings */
  .dropTracings {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: #ffffff;
    border: 1px solid #ccc;
    display: none;
    z-index: 999;
    width: 450px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    max-height: 300px;
    overflow-y: auto;
    border-radius: 10px;
  }
  .dropTracings ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .dropTracings ul li {
    padding: 15px;
    color: #000000;
    border-bottom: 1px solid #ccc;
  }
  .dropTracings ul li:hover {
    background-color: #f0f0f0;
  }
  .notify-container-tracings {
    position: relative;
    margin-right:5px;
  }
  .notify-container-tracings .fa-comment-dots {
    cursor: pointer;
    margin-left: 10px;
    font-size:23px;
  }
  .dropdown-header-tracings {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #ccc;
  }
  .dropdown-header-tracings h3 {
    margin: 0;
    font-size: 16px;
    color: #000;
  }
  .dropdown-header-tracings button {
    padding: 5px 10px;
    font-size: 14px;
    color: #fff;
    background-color: #007bff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  .dropdown-header-tracings button:hover {
    background-color: #0056b3;
  }

  /* Estilos para el contenedor de las notificaciones de los vencimientos de los clientes */
  .dropFolderNotifications {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: #ffffff;
    border: 1px solid #ccc;
    display: none;
    z-index: 999;
    width: 450px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    max-height: 300px;
    overflow-y: auto;
    border-radius: 10px;
  }
  .dropFolderNotifications ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .dropFolderNotifications ul li {
    padding: 15px;
    color: #000000;
    border-bottom: 1px solid #ccc;
  }
  .dropFolderNotifications ul li:hover {
    background-color: #f0f0f0;
  }
  .notification-folder-container {
    position: relative;
  }
  .notification-folder-container .fa-exclamation-triangle {
    cursor: pointer;
    margin-left: 5px;
    font-size: 18px; /* Cambia el tamaño del icono */
  }
  .dropdown-header-folders {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #ccc;
  }
  .dropdown-header-folders h3 {
    margin: 0;
    font-size: 16px;
    color: #000;
  }
  .dropdown-header-folders button {
    padding: 5px 10px;
    font-size: 14px;
    color: #fff;
    background-color: #007bff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  .dropdown-header-folders button:hover {
    background-color: #0056b3;
  }
  
  /* CSS PARA EL DISEÑO DEL OVERLAY DE CARGA AL DAR CLIC EN LOS BOTONES DE LIMPIAR */
  #loadingOverlayNotify {
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
</style>

<nav class="main-header navbar navbar-expand navbar-dark" style="background-color: #37424A; color: #ffffff; position: fixed; top: 0; right:0; left:0; z-index: 1000; height: auto;">
  
  <div class="navbar-left">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#"><i style="margin-top:5px;" class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="index.php" class="nav-link" style="font-weight:bold; color:white;">INICIO</a>
      </li>
    </ul>
    
    <span class="user-name">
      <a href="my-profile.php" style="text-decoration: none; color: inherit;">
        <?php if($userNav['photo_user'] != NULL){ ?>
          <img src="<?php echo "uploads/users/".$userNav['photo_user']; ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-top:-5px;">
        <?php } else { ?>
          <img width="5%" src="<?php echo "uploads/users/sin-foto.jpeg"; ?>" style="border-radius: 50%; margin-top:-5px;">
        <?php } ?>
        &nbsp;<?php echo $userNav['name_user']; ?>
      </a>
    </span>
  </div>
  
  <div class="navbar-custom-menu ml-auto">
    <!--CONTENEDOR DE SEGUIMIENTOS O TRACINGS-->
    <div class="notify-container-tracings">
      <i class="fa fa-comment-dots fa-lg"></i>
      <sup id="numTracings"></sup>
      <div class="dropTracings">
        <div class="dropdown-header-tracings">
          <h3><strong>SEGUIMIENTOS</strong></h3>
          <button id="clearTracings">LIMPIAR</button>
        </div>
        <ul id="tracingsList">
          <!-- Aquí se mostrarán los registros de los seguimientos -->
        </ul>
      </div>
    </div>
    
    <!--CONTENEDOR DE SEGUIMIENTOS DE LA CARGA DE DOCUMENTOS-->
    <div class="notifications-archive-container">
      <i class="fa fa-bell fa-lg"></i>
      <sup id="numNotifications"></sup>
      <div class="dropNotifications">
        <div class="dropdown-header">
          <h3><strong>NOTIFICACIONES</strong></h3>
          <button id="clearNotifications">LIMPIAR</button>
        </div>
        <ul id="documentList">
          <!-- Aquí se mostrarán los registros de documentos -->
        </ul>
      </div>
    </div>
    
    <!--CONTENEDOR DE NOTIFICACIONES DE LOS FOLDERS O AVISOS DE VENCIMIENTO-->
    <div class="notification-folder-container">
      <i class="fa fa-exclamation-triangle fa-lg"></i>
      <sup id="numFolderNotifications"></sup>
      <div class="dropFolderNotifications">
        <div class="dropdown-header-folders">
          <h3><strong>AVISOS DE VENCIMIENTO</strong></h3>
          <!--<button id="clearFolderNotifications">LIMPIAR</button>-->
        </div>
        <ul id="notificationsFolderList">
          <!-- Aquí se mostrarán los registros de los vencimientos -->
        </ul>
      </div>
    </div>
    
    <!-- Overlay para carga al dar clic en los botones de limpiar -->
    <div id="loadingOverlayNotify" style="display: none;">
      <div class="spinner"></div>
    </div>
    
    <!--Botón de cerrar sesión antiguo-->
    <!--<a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt mr-1"></i> Cerrar Sesión</a>-->
    <a href="logout.php" class="nav-link" data-toggle="tooltip" title="Cerrar Sesión">
      <span><i class="fas fa-sign-out-alt mr-1 logout-icon"></i></span>
    </a>
  </div>

</nav>

<aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color: #37424A; color: #ffffff; position: fixed;"> 
  <a href="index.php" class="brand-link">
    <div style="height:100px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px;color:#ffffff;letter-spacing:.04em;">Compliance Hub</div>
  </a>
  
  <div class="sidebar">
    <nav class="mt-4 mb-4">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        
        <li class="nav-item">
          <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="fa fa-home nav-icon"></i>
            <p>Inicio</p>
          </a>
        </li>
        
        <li class="nav-item">
          <a href="my-profile.php" class="nav-link <?php echo ($current_page == 'my-profile.php') ? 'active' : ''; ?>">
            <i class="fa fa-user nav-icon"></i>
            <p>Mi Perfil</p>
          </a>
        </li>
        
        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
        <?php if($_SESSION['user']['id_type_user'] == 1){ ?>
          <li class="nav-item">
            <a href="backoffice/users/users.php" class="nav-link <?php echo (in_array($current_page, $users_pages)) ? 'active' : ''; ?>">
              <i class="fa fa-users nav-icon"></i>
              <p>Usuarios</p>
            </a>
          </li>
        <?php } ?>
        

      
        <li class="nav-item">
          <a href="backoffice/folders/folders.php" class="nav-link <?php echo (in_array($current_page, $clientes_pages)) ? 'active' : ''; ?>">
            <i class="fa fa-folder-open nav-icon"></i>
            <p>Clientes</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="backoffice/companies/companies.php" class="nav-link <?php echo (in_array($current_page, $empresas_pages)) ? 'active' : ''; ?>">
            <i class="fa fa-building nav-icon"></i>
            <p>Empresas</p>
          </a>
        </li>
        

        
        
        <li class="nav-item">
          <a href="backoffice/folders/all_folders.php" class="nav-link <?php echo (in_array($current_page, $tablero_pages)) ? 'active' : ''; ?>">
            <i class="fa fa-folder nav-icon"></i>
            <p>Tablero</p>
          </a>
        </li>

        

         <!-- NUEVA SECCIÓN: OPERACIONES VULNERABLES (Solo para Administradores) -->
          <li class="nav-item">
            <a href="backoffice/vulnerabilities/vulnerabilities.php" class="nav-link <?php echo ($current_page == 'vulnerabilities.php') ? 'active' : ''; ?>">
              <i class="fas fa-shield-alt nav-icon"></i>
              <p>Op Vulnerables</p>
            </a>
          </li>
        
        <li class="nav-item">
          <a href="backoffice/support/resources.php" class="nav-link <?php echo (in_array($current_page, $material_pages)) ? 'active' : ''; ?>">
            <i class="fa fa-copy nav-icon"></i>
            <p>Material de Apoyo</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>

</aside>

<script>
  $(document).ready(function () {
    // Verifica el estado guardado en el localStorage
    if(localStorage.getItem('sidebarState') === 'collapsed') {
      $('body').addClass('sidebar-collapse'); // Si el estado es comprimido, agrega la clase para comprimir el menú
    } else {
      $('body').removeClass('sidebar-collapse'); // Si el estado es expandido, remueve la clase
    }
    // Acción del botón para alternar el estado
    $('a[data-widget="pushmenu"]').on('click', function() {
      if ($('body').hasClass('sidebar-collapse')) {
        // Si el menú está comprimido, lo expandimos y guardamos el estado
        localStorage.setItem('sidebarState', 'expanded');
      } else {
        // Si el menú está expandido, lo comprimimos y guardamos el estado
        localStorage.setItem('sidebarState', 'collapsed');
      }
    });
    // Desactiva la expansión automática cuando se pasa el mouse
    $('body').removeClass('sidebar-mini'); // Desactiva el modo mini si está habilitado
  });

  /*CÓDIGO PARA CONTROLAR LOS DROPDOWN DE LAS NOTIFICACIONES*/
  // Función para manejar la visibilidad de los dropdowns
  function toggleDropdown(triggerSelector, dropdownSelector, closeSelectors) {
    // Añadir evento click al elemento disparador
    document.querySelector(triggerSelector).addEventListener('click', function (event) {
      event.stopPropagation(); // Evita la propagación del evento hacia otros elementos
      // Seleccionar el dropdown correspondiente
      var dropdown = document.querySelector(dropdownSelector);
      
      // Alternar la visibilidad del dropdown (mostrar/ocultar)
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
      
      // Cerrar otros dropdowns pasados en el arreglo closeSelectors
      closeSelectors.forEach(function (selector) {
        var element = document.querySelector(selector);
        if (element) {
          element.style.display = 'none';
        }
      });
    });
    
    // Ocultar el dropdown cuando se hace clic fuera de él
    document.addEventListener('click', function (event) {
      var dropdown = document.querySelector(dropdownSelector);
      if (!dropdown.contains(event.target) && !document.querySelector(triggerSelector).contains(event.target)) {
        dropdown.style.display = 'none';
      }
    });
  }
  
  // Implementación de los dropdowns específicos
  toggleDropdown(
    '.notifications-archive-container .fa-bell', // Trigger para documentos cargados al sistema
    '.notifications-archive-container .dropNotifications', // Dropdown de documentos
    ['.notify-container-tracings .dropTracings', '.notification-folder-container .dropFolderNotifications'] // Dropdowns a cerrar
  );
  
  toggleDropdown(
    '.notify-container-tracings .fa-comment-dots', // Trigger para seguimientos o tracings
    '.notify-container-tracings .dropTracings', // Dropdown de seguimientos
    ['.notifications-archive-container .dropNotifications', '.notification-folder-container .dropFolderNotifications'] // Dropdowns a cerrar
  );
  
  toggleDropdown(
    '.notification-folder-container .fa-exclamation-triangle', // Trigger para notificaciones de los folders
    '.notification-folder-container .dropFolderNotifications', // Dropdown de notificaciones
    ['.notify-container-tracings .dropTracings', '.notifications-archive-container .dropNotifications'] // Dropdowns a cerrar
  );
</script>
