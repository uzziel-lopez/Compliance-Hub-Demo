<?php
  // Llamada a una función para mostrar los detalles de un usuario.
  // Se pasa el ID de usuario y la clave de usuario de la sesión actual como argumentos.
  $userNav = $controller->getDetailUser($_SESSION['user']['id_user'], $_SESSION['user']['key_user']);
  $current_page = basename($_SERVER['PHP_SELF']); // Obtiene el nombre del archivo actual
  
  $users_pages = ['create-user.php', 'detail-user.php', 'update-user.php', 'users.php'];
  $clientes_pages = ['folders.php', 'subfolder.php'];
  $tablero_pages = ['all_folders.php'];
  $material_pages = ['resources.php'];
  $empresas_pages = ['companies.php', 'create-company.php', 'update-company.php', 'detail-company.php']; // ← AGREGAR ESTA LÍNEA

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
        <a href="../../index.php" class="nav-link" style="font-weight:bold; color:white;">INICIO</a>
      </li>
    </ul>

    <span class="user-name">
      <a href="../../my-profile.php" style="text-decoration: none; color: inherit;">
        <?php if($userNav['photo_user'] != NULL){ ?>
          <img src="<?php echo "../../uploads/users/".$userNav['photo_user']; ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-top:-5px;">
        <?php } else { ?>
          <img width="5%" src="<?php echo "../../uploads/users/sin-foto.jpeg"; ?>" style="border-radius: 50%; margin-top:-5px;">
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
    <!--<a href="../../logout.php" class="nav-link"><i class="fas fa-sign-out-alt mr-1"></i> Cerrar Sesión</a>-->
    <a href="../../logout.php" class="nav-link" data-toggle="tooltip" title="Cerrar Sesión">
      <span><i class="fas fa-sign-out-alt mr-1 logout-icon"></i></span>
    </a>
  </div>

</nav>

<aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color: #37424A; color: #ffffff; position: fixed;"> 
  <a href="../../index.php" class="brand-link">
    <div style="height:100px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px;color:#ffffff;letter-spacing:.04em;">Compliance Hub</div>
  </a>

  <div class="sidebar">
    <nav class="mt-4 mb-4">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        
        <li class="nav-item">
          <a href="../../index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="fa fa-home nav-icon"></i>
            <p>Inicio</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="../../my-profile.php" class="nav-link <?php echo ($current_page == 'my-profile.php') ? 'active' : ''; ?>">
            <i class="fa fa-user nav-icon"></i>
            <p>Mi Perfil</p>
          </a>
        </li>

        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
        <?php if($_SESSION['user']['id_type_user'] == 1){ ?>
          <!--
          <li class="nav-item">
            <a href="../users/create-user.php" class="nav-link">
              <i class="fa fa-user-plus nav-icon"></i>
              <p>Crear usuario</p>
            </a>
          </li>
          -->
          <li class="nav-item">
            <a href="../users/users.php" class="nav-link <?php echo (in_array($current_page, $users_pages)) ? 'active' : ''; ?>">
              <i class="fa fa-users nav-icon"></i>
              <p>Usuarios</p>
            </a>
          </li>
        <?php } ?>

        <li class="nav-item">
          <a href="../folders/folders.php" class="nav-link <?php echo (in_array($current_page, $clientes_pages)) ? 'active' : ''; ?>">
            <i class="fa fa-folder-open nav-icon"></i>
            <p>Clientes</p>
          </a>
        </li>


                <!-- NUEVA OPCIÓN: EMPRESAS -->
        <li class="nav-item">
          <a href="../companies/companies.php" class="nav-link <?php echo (in_array($current_page, $empresas_pages)) ? 'active' : ''; ?>">
            <i class="fa fa-building nav-icon"></i>
            <p>Empresas</p>
          </a>
        </li>
        
        <!--
        <li class="nav-item">
          <a href="../documents/documents.php" class="nav-link">
            <i class="fa fa-file nav-icon"></i>
            <p>Consultar documentos</p>
          </a>
        </li>
        -->

        <li class="nav-item">
          <a href="../folders/all_folders.php" class="nav-link <?php echo (in_array($current_page, $tablero_pages)) ? 'active' : ''; ?>">
            <i class="fa fa-folder nav-icon"></i>
            <p>Tablero</p>
          </a>
        </li>


                <!-- NUEVA SECCIÓN: OP VULNERABLES (Solo para Administradores) -->
          <li class="nav-item">
            <a href="../vulnerabilities/vulnerabilities.php" class="nav-link <?php echo ($current_page == 'vulnerabilities.php') ? 'active' : ''; ?>">
              <i class="fas fa-shield-alt nav-icon"></i>
              <p>Op Vulnerables</p>
            </a>
          </li>

                
        <li class="nav-item">
          <a href="../support/resources.php" class="nav-link <?php echo (in_array($current_page, $material_pages)) ? 'active' : ''; ?>">
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
