<?php
session_start();
include "../../app/config.php";
//include "../../app/debug.php";
include "../../app/WebController.php";
$controller = new WebController();

// Verificar si la sesión del usuario está activa
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
  // Si no hay sesión activa, destruir la sesión
  session_destroy();
  // Redirigir a la página de inicio de sesión
  header("Location: ../../login.php");
  exit(); // Es importante salir después de redirigir para evitar que el código siguiente se ejecute innecesariamente
}

// Obtener la lista de carpetas mediante el método getFolders del controlador, pasando el status como parámetro (1-> ACTIVO, 2-> INACTIVO)
$folders = $controller->getFolders(1);
// Contar el número total de carpetas obtenidas en la variable $folders.
$totalFolders = count($folders);

// Obtener la lista de los usuarios del departamento de ventas y que esten activos (3 -> tipo de usuario ventas, 1 -> activos)
$customersList = $controller->getCustomersList(3, 1);
$companiesList = $controller->getCompanies(1);

//FUNCIÓN PARA CREAR UNA NUEVA CARPETA
// Verifica si se ha dado clic en algun boton a traves del action
if (!empty($_POST['action'])) {
  // Si la acción es 'add', se intenta crear un nuevo cliente
  if ($_POST['action'] == 'add') {
    // Llama al método para crear un cliente y obtiene el ID del cliente creado
    $folderId = $controller->createFolder($_POST['folder']);
    // Si se crea el cliente correctamente, redirecciona a la página de folders
    if ($folderId) {
      header('location: folders.php');
    }
  }
  // Si la acción es 'create', se intenta crear una carpeta nueva
  else if ($_POST['action'] == 'create') {
    // Llama al método para crear una carpeta y obtiene el ID de la carpeta creada
    $folderId = $controller->createFolder($_POST['folder']);
    // Si se crea la carpeta correctamente, redirecciona a la página de carpetas
    if ($folderId) {
      header('location: folders.php');
    }
  }
  // Si la acción es 'delete', se intenta eliminar una carpeta existente
  else if ($_POST['action'] == 'delete') {
    // Llama al método para eliminar la carpeta y obtiene el ID de la carpeta eliminada
    $idFolder = $controller->deleteFolder($_POST['delFolder']);
    // Si se elimina la carpeta correctamente, redirecciona a la página de carpetas
    if ($idFolder) {
      header('location: folders.php');
    }
  }
}



// PROCESAMIENTO PARA OBTENER DATOS DE EMPRESA (COPIAR DE companies.php)
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

// PROCESAMIENTO PARA ACTUALIZAR EMPRESA (COPIAR DE companies.php)
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'update_company') {
  header('Content-Type: application/json; charset=utf-8');
  ob_clean();
  try {
    $companyId = $_POST['company_id'];
    if (empty($companyId)) {
      echo json_encode(['success' => false, 'message' => 'ID de empresa requerido']);
      exit();
    }
    $requiredFields = ['name_company', 'rfc_company', 'razon_social', 'tipo_persona'];
    foreach ($requiredFields as $field) {
      if (empty($_POST['company'][$field])) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben ser completados']);
        exit();
      }
    }
    $updated = $controller->updateCompany($_POST['company'], $companyId);
    if ($updated) {
      echo json_encode(['success' => true, 'message' => 'Empresa actualizada exitosamente']);
    } else {
      echo json_encode(['success' => false, 'message' => 'Error al actualizar la empresa']);
    }
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
  }
  exit();
}

//FUNCIÓN PARA GENERAR UNA CLAVE PARA LA CARPETA
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
  <!--SCRIPT PARA MANEJAR EL MOSTRAR Y OCULTAR DE LA FECHA DE ORIGINAL RECIBIDO AL ACTUALIZAR-->
  <script>
    $(document).ready(function () {
      // Mostrar/ocultar el div y agregar/quitar el atributo required según el estado del checkbox
      $('#edit_chk_orig_recib_folder').change(function () {
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
    .title {
      font-size: 15px;
      font-weight: bold;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      padding: 5px 5px 5px 5px;
      margin-bottom: -10px;
    }

    .status-bar-checks {
      text-align: right;
      padding-top: 5px;
      margin-right: 10px;
    }

    .status-bar-checks .status-item {
      display: inline-block;
      margin-left: 10px;
    }

    .status-bar-checks i {
      font-size: 16px;
      /* Tamaño del icono */
      color: #000000;
    }

    .status-bar-checks .status-item span {
      font-weight: bold;
      font-size: 14px;
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

    .company-form-section h6 {
      color: #37424A;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.1rem;
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
    }

    .alert-info-custom {
      background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
      border: none;
      border-radius: 10px;
      color: #37424A;
      border-left: 4px solid #37424A;
      padding: 15px 20px;
    }

    .fideicomiso-section {
      border-left: 4px solid #17a2b8;
      background-color: #e3f2fd;
    }

    .fideicomiso-section h6 {
      color: #17a2b8;
    }

    .required {
      color: red;
    }

    .form-section {
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      padding: 20px;
      margin-bottom: 20px;
      background-color: #f8f9fa;
    }

    .form-section h6 {
      color: #495057;
      border-bottom: 2px solid #007bff;
      padding-bottom: 8px;
      margin-bottom: 15px;
    }

    .domicilio-extranjero {
      background-color: #e3f2fd;
      border: 1px solid #bbdefb;
      border-radius: 0.375rem;
      padding: 15px;
      margin-top: 15px;
    }

    .checkbox-section {
      background-color: #fff3cd;
      border: 1px solid #ffeaa7;
      border-radius: 0.375rem;
      padding: 10px;
      margin: 10px 0;
    }
  </style>
</head>

<body class="hold-transition sidebar-mini">
  <div class="wrapper" style="padding-top: 57px;">
    <?php include "../templates/navbar.php"; ?>
    <div class="content-wrapper">
      <div class="content-header" style="margin-bottom:-20px;">
        <div class="container-fluid">
          <div class="row justify-content-between mb-2">
            <div class="col-lg-6 col-sm-6">
              <h1 class="m-0 text-dark">Lista completa de clientes</h1>
            </div>

            <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1) O VENTAS (3)-->
            <?php if ($_SESSION['user']['id_type_user'] == 1 || $_SESSION['user']['id_type_user'] == 3) { ?>
              <div class="col-sm-4 text-right">
                <!-- Botón para abrir el modal -->
                <a href="#" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;" role="button"
                  aria-pressed="true" data-toggle="modal" data-target="#modalAgregarCarpeta">
                  <i class="fas fa-plus pr-2"></i>Agregar nuevo cliente
                </a>
              </div>
            <?php } ?>
          </div>
          <hr>
        </div>
      </div>

      <!--CONSULTA GENERAL DE TODAS LAS CARPETAS-->
      <div class="content">
        <div class="container-fluid">
          <strong>Total de clientes: <?php echo $totalFolders; ?></strong>
          <div class="row">
            <div class="col-lg-4 col-md-6 col-sm-12">
              <input type="text" class="form-control" style="margin-bottom:10px;" id="searchInputFolders"
                placeholder="Buscar cliente...">
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <?php if (empty($folders)) { ?>
                    <div class="alert alert-info" role="alert">
                      <i class="fas fa-exclamation-triangle"></i>&nbsp;¡No se hallaron registros de clientes!
                    </div>
                  <?php } else { ?>
                    <div class="row">
                      <?php foreach ($folders as $folder): ?>
                        <div class="col-lg-3 col-md-6 col-sm-12" id="myFolders">
                          <div class="folder">
                            <div class="title-bar"
                              style="background-color: #f5f5f5; color: #000000; border-radius: 10px; padding: 30px; display: flex; flex-direction: column; align-items: stretch;">
                              <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div class="title">
                                  <a href="subfolder.php?id=<?php echo $folder['id_folder']; ?>&key=<?php echo $folder['key_folder']; ?>"
                                    style="text-decoration: none; color: inherit;">
                                    <i class="fas fa-folder fa-lg"></i>
                                    &nbsp;&nbsp;
                                    <?php echo $folder['name_folder']; ?>
                                  </a>
                                </div>
                                <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->

                                <div class="dropdown" style="margin-top:5px;">
                                  <button class="btn btn-secondary" type="button"
                                    id="dropdownMenuButton_<?php echo $folder['id_folder']; ?>" data-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false"
                                    style="background-color: transparent; border: none;">
                                    <i class="fas fa-ellipsis-v" style="color: black; background-color: transparent;"></i>
                                  </button>
                                  <div class="dropdown-menu dropdown-menu-right"
                                    aria-labelledby="dropdownMenuButton_<?php echo $folder['id_folder']; ?>">
                                    <a class="dropdown-item" href="#" data-folder-id="<?php echo $folder['id_folder']; ?>">
                                      <i class="fas fa-pen"></i> Editar cliente
                                    </a>
                                    <?php if ($_SESSION['user']['id_type_user'] == 1) { ?>
                                      <hr>
                                      <form action="folders.php" method="POST">
                                        <input name="delFolder[idFolder]" type="text" class="form-control form-control-sm"
                                          id="id_folder" value="<?php echo $folder['id_folder']; ?>" readonly hidden
                                          style="display: none;">
                                        <button class="dropdown-item" type="submit" name="action" value="delete"
                                          onclick="return confirm('¿Estás seguro de eliminar el cliente?');">
                                          <i class="fas fa-trash"></i> Mover a la papelera
                                        </button>
                                      </form>
                                    <?php } ?>
                                  </div>
                                </div>

                              </div>
                            </div>
                          </div>&nbsp;
                        </div>
                      <?php endforeach; ?>

                    </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>



  <!-- Modal para agregar nuevo cliente -->
  <div class="modal fade" id="modalAgregarCarpeta" tabindex="-1" aria-labelledby="modalAgregarCarpetaLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalAgregarCarpetaLabel">Agregar nuevo cliente</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <!-- Formulario para agregar cliente -->
          <form id="formAgregarCliente" action="folders.php" method="POST">
            <input name="folder[id_user_folder]" type="hidden" value="<?php echo $_SESSION['user']['id_user']; ?>">
            <input name="folder[key_folder]" type="hidden" value="CLI-<?php echo $clave; ?>">

            <!-- Alerta si no hay empresas -->
            <?php if (empty($companiesList)): ?>
              <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>¡Atención!</strong> No hay empresas registradas.
                <a href="../companies/companies.php" class="alert-link">Haz clic aquí para registrar una empresa
                  primero.</a>
              </div>
            <?php endif; ?>

            <!-- Select para elegir empresa -->
            <div class="form-group">
              <label for="empresa_select">Empresa a la que pertenece el cliente: <span
                  style="color: red;">*</span></label>
              <select name="folder[fk_folder]" id="empresa_select" class="form-control" required <?php echo empty($companiesList) ? 'disabled' : ''; ?>>
                <option value="">-- Seleccionar empresa --</option>
                <?php foreach ($companiesList as $company): ?>
                  <option value="<?php echo $company['id_folder']; ?>">
                    <?php echo $company['name_folder']; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">
                Si no ves la empresa que necesitas,
                <a href="../companies/companies.php" target="_blank">ve al módulo de Empresas</a>
                para registrarla primero.
              </small>
            </div>

            <!-- Select para tipo de persona -->
            <div class="form-group">
              <label for="tipo_persona">Tipo de persona: <span style="color: red;">*</span></label>
              <select name="folder[tipo_persona]" id="tipo_persona" class="form-control" required>
                <option value="">-- Seleccionar tipo --</option>
                <option value="fisica">Persona Física</option>
                <option value="moral">Persona Moral</option>
                <option value="fideicomiso">Fideicomiso</option>
              </select>
            </div>

            <!-- SECCIÓN PERSONA FÍSICA -->
            <div id="seccion_fisica" style="display: none;">
              <div class="form-section">
                <h6><i class="fas fa-user"></i> Información de la Persona Física</h6>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="pf_nombre">Nombre: <span style="color: red;">*</span></label>
                      <input type="text" name="folder[pf_nombre]" class="form-control" id="pf_nombre"
                        placeholder="Ej. Juan">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="pf_apellido_paterno">Apellido Paterno: <span style="color: red;">*</span></label>
                      <input type="text" name="folder[pf_apellido_paterno]" class="form-control"
                        id="pf_apellido_paterno" placeholder="Ej. Pérez">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="pf_apellido_materno">Apellido Materno:</label>
                      <input type="text" name="folder[pf_apellido_materno]" class="form-control"
                        id="pf_apellido_materno" placeholder="Ej. López">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="pf_rfc">RFC: <span style="color: red;">*</span></label>
                      <input type="text" name="folder[pf_rfc]" class="form-control" id="pf_rfc" maxlength="13"
                        placeholder="PEPJ850525AB1">
                      <small class="text-muted">Formato: 4 letras + 6 números + 3 caracteres</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="pf_curp">CURP:</label>
                      <input type="text" name="folder[pf_curp]" class="form-control" id="pf_curp" maxlength="18"
                        placeholder="PEPJ850525HDFRNS05">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="pf_fecha_nacimiento">Fecha de Nacimiento:</label>
                      <input type="date" name="folder[pf_fecha_nacimiento]" class="form-control"
                        id="pf_fecha_nacimiento">
                    </div>
                  </div>
                </div>

                <h6><i class="fas fa-home"></i> Domicilio Nacional</h6>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pf_estado">Estado:</label>
                      <input type="text" name="folder[pf_estado]" class="form-control" id="pf_estado"
                        placeholder="Ej. Tabasco">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pf_ciudad">Ciudad o Población:</label>
                      <input type="text" name="folder[pf_ciudad]" class="form-control" id="pf_ciudad"
                        placeholder="Ej. Villahermosa">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pf_colonia">Colonia:</label>
                      <input type="text" name="folder[pf_colonia]" class="form-control" id="pf_colonia"
                        placeholder="Ej. Centro">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pf_codigo_postal">Código Postal:</label>
                      <input type="text" name="folder[pf_codigo_postal]" class="form-control" id="pf_codigo_postal"
                        maxlength="5" placeholder="86000">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="pf_calle">Calle:</label>
                      <input type="text" name="folder[pf_calle]" class="form-control" id="pf_calle"
                        placeholder="Ej. Av. Siempre Viva">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="pf_num_exterior">Núm. Exterior:</label>
                      <input type="text" name="folder[pf_num_exterior]" class="form-control" id="pf_num_exterior"
                        placeholder="123">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="pf_num_interior">Núm. Interior:</label>
                      <input type="text" name="folder[pf_num_interior]" class="form-control" id="pf_num_interior"
                        placeholder="A">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="pf_telefono">Teléfono:</label>
                      <input type="tel" name="folder[pf_telefono]" class="form-control" id="pf_telefono"
                        placeholder="9931234567">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="pf_email">Correo Electrónico:</label>
                      <input type="email" name="folder[pf_email]" class="form-control" id="pf_email"
                        placeholder="correo@ejemplo.com">
                    </div>
                  </div>
                </div>

                <div class="checkbox-section">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="pf_tiene_domicilio_extranjero"
                      name="folder[pf_tiene_domicilio_extranjero]" value="1">
                    <label class="form-check-label" for="pf_tiene_domicilio_extranjero">
                      ¿Tiene domicilio extranjero?
                    </label>
                  </div>
                </div>

                <!-- Domicilio Extranjero PF -->
                <div id="pf_domicilio_extranjero" class="domicilio-extranjero" style="display: none;">
                  <h6><i class="fas fa-globe"></i> Domicilio Extranjero</h6>
                  <div class="row">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="pf_pais_origen">País de Origen:</label>
                        <input type="text" name="folder[pf_pais_origen]" class="form-control" id="pf_pais_origen"
                          placeholder="Ej. Estados Unidos">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="pf_estado_extranjero">Estado o Provincia:</label>
                        <input type="text" name="folder[pf_estado_extranjero]" class="form-control"
                          id="pf_estado_extranjero" placeholder="Ej. Texas">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="pf_ciudad_extranjero">Ciudad o Población:</label>
                        <input type="text" name="folder[pf_ciudad_extranjero]" class="form-control"
                          id="pf_ciudad_extranjero" placeholder="Ej. Houston">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="pf_colonia_extranjero">Colonia del Extranjero:</label>
                        <input type="text" name="folder[pf_colonia_extranjero]" class="form-control"
                          id="pf_colonia_extranjero">
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="pf_calle_extranjero">Calle del Extranjero:</label>
                        <input type="text" name="folder[pf_calle_extranjero]" class="form-control"
                          id="pf_calle_extranjero">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="pf_num_exterior_ext">Núm. Exterior (Ext):</label>
                        <input type="text" name="folder[pf_num_exterior_ext]" class="form-control"
                          id="pf_num_exterior_ext">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="pf_num_interior_ext">Núm. Interior (Ext):</label>
                        <input type="text" name="folder[pf_num_interior_ext]" class="form-control"
                          id="pf_num_interior_ext">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="pf_codigo_postal_ext">Código Postal (Ext):</label>
                        <input type="text" name="folder[pf_codigo_postal_ext]" class="form-control"
                          id="pf_codigo_postal_ext">
                      </div>
                    </div>
                  </div>
                </div>


              </div>
            </div>

            <!-- SECCIÓN PERSONA MORAL -->
            <div id="seccion_moral" style="display: none;">
              <div class="form-section">
                <h6><i class="fas fa-building"></i> Información de la Persona Moral</h6>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="pm_razon_social">Razón Social: <span style="color: red;">*</span></label>
                      <input type="text" name="folder[pm_razon_social]" class="form-control" id="pm_razon_social"
                        placeholder="Ej. Empresa SA de CV">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pm_rfc">RFC Persona Moral: <span style="color: red;">*</span></label>
                      <input type="text" name="folder[pm_rfc]" class="form-control" id="pm_rfc" maxlength="12"
                        placeholder="EMP850525ABC">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pm_fecha_constitucion">Fecha de Constitución:</label>
                      <input type="date" name="folder[pm_fecha_constitucion]" class="form-control"
                        id="pm_fecha_constitucion">
                    </div>
                  </div>
                </div>

                <h6><i class="fas fa-user-tie"></i> Apoderado Legal</h6>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pm_apoderado_nombre">Nombre:</label>
                      <input type="text" name="folder[pm_apoderado_nombre]" class="form-control"
                        id="pm_apoderado_nombre">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pm_apoderado_paterno">Apellido Paterno:</label>
                      <input type="text" name="folder[pm_apoderado_paterno]" class="form-control"
                        id="pm_apoderado_paterno">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pm_apoderado_materno">Apellido Materno:</label>
                      <input type="text" name="folder[pm_apoderado_materno]" class="form-control"
                        id="pm_apoderado_materno">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pm_apoderado_fecha_nacimiento">Fecha de nacimiento de representante legal:</label>
                      <input type="date" name="folder[pm_apoderado_fecha_nacimiento]" class="form-control"
                        id="pm_apoderado_fecha_nacimiento">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="pm_apoderado_rfc">RFC Apoderado Legal:</label>
                      <input type="text" name="folder[pm_apoderado_rfc]" class="form-control" id="pm_apoderado_rfc"
                        maxlength="13">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="pm_apoderado_curp">CURP Apoderado Legal:</label>
                      <input type="text" name="folder[pm_apoderado_curp]" class="form-control" id="pm_apoderado_curp"
                        maxlength="18">
                    </div>
                  </div>
                </div>

                <h6><i class="fas fa-home"></i> Domicilio Nacional</h6>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pm_estado">Estado:</label>
                      <input type="text" name="folder[pm_estado]" class="form-control" id="pm_estado">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pm_ciudad">Ciudad o Población:</label>
                      <input type="text" name="folder[pm_ciudad]" class="form-control" id="pm_ciudad">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pm_colonia">Colonia:</label>
                      <input type="text" name="folder[pm_colonia]" class="form-control" id="pm_colonia">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="pm_codigo_postal">Código Postal:</label>
                      <input type="text" name="folder[pm_codigo_postal]" class="form-control" id="pm_codigo_postal"
                        maxlength="5">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="pm_calle">Calle:</label>
                      <input type="text" name="folder[pm_calle]" class="form-control" id="pm_calle">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="pm_num_exterior">Núm. Exterior:</label>
                      <input type="text" name="folder[pm_num_exterior]" class="form-control" id="pm_num_exterior">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="pm_num_interior">Núm. Interior:</label>
                      <input type="text" name="folder[pm_num_interior]" class="form-control" id="pm_num_interior">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="pm_telefono">Teléfono:</label>
                      <input type="tel" name="folder[pm_telefono]" class="form-control" id="pm_telefono">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="pm_email">Correo Electrónico:</label>
                      <input type="email" name="folder[pm_email]" class="form-control" id="pm_email">
                    </div>
                  </div>
                </div>

                <div class="checkbox-section">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="pm_tiene_domicilio_extranjero"
                      name="folder[pm_tiene_domicilio_extranjero]" value="1">
                    <label class="form-check-label" for="pm_tiene_domicilio_extranjero">
                      ¿Tiene domicilio extranjero?
                    </label>
                  </div>
                </div>

                <!-- Domicilio Extranjero PM -->
                <div id="pm_domicilio_extranjero" class="domicilio-extranjero" style="display: none;">
                  <h6><i class="fas fa-globe"></i> Domicilio Extranjero</h6>
                  <div class="row">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="pm_pais_origen">País de Origen:</label>
                        <input type="text" name="folder[pm_pais_origen]" class="form-control" id="pm_pais_origen">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="pm_estado_extranjero">Estado o Provincia:</label>
                        <input type="text" name="folder[pm_estado_extranjero]" class="form-control"
                          id="pm_estado_extranjero">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="pm_ciudad_extranjero">Ciudad o Población:</label>
                        <input type="text" name="folder[pm_ciudad_extranjero]" class="form-control"
                          id="pm_ciudad_extranjero">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="pm_colonia_extranjero">Colonia del Extranjero:</label>
                        <input type="text" name="folder[pm_colonia_extranjero]" class="form-control"
                          id="pm_colonia_extranjero">
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="pm_calle_extranjero">Calle del Extranjero:</label>
                        <input type="text" name="folder[pm_calle_extranjero]" class="form-control"
                          id="pm_calle_extranjero">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="pm_num_exterior_ext">Núm. Exterior (Ext):</label>
                        <input type="text" name="folder[pm_num_exterior_ext]" class="form-control"
                          id="pm_num_exterior_ext">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="pm_num_interior_ext">Núm. Interior (Ext):</label>
                        <input type="text" name="folder[pm_num_interior_ext]" class="form-control"
                          id="pm_num_interior_ext">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="pm_codigo_postal_ext">Código Postal Extranjero:</label>
                        <input type="text" name="folder[pm_codigo_postal_ext]" class="form-control"
                          id="pm_codigo_postal_ext">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- SECCIÓN FIDEICOMISO -->
            <div id="seccion_fideicomiso" style="display: none;">
              <div class="form-section">
                <h6><i class="fas fa-handshake"></i> Información del Fideicomiso</h6>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="fid_razon_social">Razón Social del Fiduciario: <span
                          style="color: red;">*</span></label>
                      <input type="text" name="folder[fid_razon_social]" class="form-control" id="fid_razon_social"
                        placeholder="Ej. Banco Fiduciario SA">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="fid_rfc">RFC del Fiduciario: <span style="color: red;">*</span></label>
                      <input type="text" name="folder[fid_rfc]" class="form-control" id="fid_rfc" maxlength="12"
                        placeholder="BFI850525ABC">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="fid_numero_referencia">Número / Referencia de Fideicomiso:</label>
                      <input type="text" name="folder[fid_numero_referencia]" class="form-control"
                        id="fid_numero_referencia" placeholder="FID-12345">
                    </div>
                  </div>
                </div>

                <h6><i class="fas fa-user-tie"></i> Apoderado Legal</h6>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="fid_apoderado_nombre">Nombre:</label>
                      <input type="text" name="folder[fid_apoderado_nombre]" class="form-control"
                        id="fid_apoderado_nombre">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="fid_apoderado_paterno">Apellido Paterno:</label>
                      <input type="text" name="folder[fid_apoderado_paterno]" class="form-control"
                        id="fid_apoderado_paterno">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="fid_apoderado_materno">Apellido Materno:</label>
                      <input type="text" name="folder[fid_apoderado_materno]" class="form-control"
                        id="fid_apoderado_materno">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="fid_apoderado_fecha_nacimiento">Fecha de nacimiento de representante legal:</label>
                      <input type="date" name="folder[fid_apoderado_fecha_nacimiento]" class="form-control"
                        id="fid_apoderado_fecha_nacimiento">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="fid_apoderado_rfc">RFC Apoderado Legal:</label>
                      <input type="text" name="folder[fid_apoderado_rfc]" class="form-control" id="fid_apoderado_rfc"
                        maxlength="13">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="fid_apoderado_curp">CURP Apoderado Legal:</label>
                      <input type="text" name="folder[fid_apoderado_curp]" class="form-control" id="fid_apoderado_curp"
                        maxlength="18">
                    </div>
                  </div>
                </div>

                <h6><i class="fas fa-home"></i> Domicilio Nacional</h6>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="fid_estado">Estado:</label>
                      <input type="text" name="folder[fid_estado]" class="form-control" id="fid_estado">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="fid_ciudad">Ciudad o Población:</label>
                      <input type="text" name="folder[fid_ciudad]" class="form-control" id="fid_ciudad">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="fid_colonia">Colonia:</label>
                      <input type="text" name="folder[fid_colonia]" class="form-control" id="fid_colonia">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="fid_codigo_postal">Código Postal:</label>
                      <input type="text" name="folder[fid_codigo_postal]" class="form-control" id="fid_codigo_postal"
                        maxlength="5">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="fid_calle">Calle:</label>
                      <input type="text" name="folder[fid_calle]" class="form-control" id="fid_calle">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="fid_num_exterior">Núm. Exterior:</label>
                      <input type="text" name="folder[fid_num_exterior]" class="form-control" id="fid_num_exterior">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="fid_num_interior">Núm. Interior:</label>
                      <input type="text" name="folder[fid_num_interior]" class="form-control" id="fid_num_interior">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="fid_telefono">Teléfono:</label>
                      <input type="tel" name="folder[fid_telefono]" class="form-control" id="fid_telefono">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="fid_email">Correo Electrónico:</label>
                      <input type="email" name="folder[fid_email]" class="form-control" id="fid_email">
                    </div>
                  </div>
                </div>

                <div class="checkbox-section">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="fid_tiene_domicilio_extranjero"
                      name="folder[fid_tiene_domicilio_extranjero]" value="1">
                    <label class="form-check-label" for="fid_tiene_domicilio_extranjero">
                      ¿Tiene domicilio extranjero?
                    </label>
                  </div>
                </div>

                <!-- Domicilio Extranjero Fideicomiso -->
                <div id="fid_domicilio_extranjero" class="domicilio-extranjero" style="display: none;">
                  <h6><i class="fas fa-globe"></i> Domicilio Extranjero</h6>
                  <div class="row">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="fid_pais_origen">País de Origen:</label>
                        <input type="text" name="folder[fid_pais_origen]" class="form-control" id="fid_pais_origen">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="fid_estado_extranjero">Estado o Provincia:</label>
                        <input type="text" name="folder[fid_estado_extranjero]" class="form-control"
                          id="fid_estado_extranjero">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="fid_ciudad_extranjero">Ciudad o Población:</label>
                        <input type="text" name="folder[fid_ciudad_extranjero]" class="form-control"
                          id="fid_ciudad_extranjero">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="fid_colonia_extranjero">Colonia del Extranjero:</label>
                        <input type="text" name="folder[fid_colonia_extranjero]" class="form-control"
                          id="fid_colonia_extranjero">
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="fid_calle_extranjero">Calle del Extranjero:</label>
                        <input type="text" name="folder[fid_calle_extranjero]" class="form-control"
                          id="fid_calle_extranjero">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="fid_num_exterior_ext">Núm. Exterior (Ext):</label>
                        <input type="text" name="folder[fid_num_exterior_ext]" class="form-control"
                          id="fid_num_exterior_ext">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="fid_num_interior_ext">Núm. Interior (Ext):</label>
                        <input type="text" name="folder[fid_num_interior_ext]" class="form-control"
                          id="fid_num_interior_ext">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="fid_codigo_postal_ext">Código Postal Extranjero:</label>
                        <input type="text" name="folder[fid_codigo_postal_ext]" class="form-control"
                          id="fid_codigo_postal_ext">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>



            <!-- Sección para Plazo de Vigencia y Checkboxes -->
            <div class="row">
              <div class="col-12">
                <label>Plazo de vigencia <small style="color:red;">(*Plazo opcional)</small></label>
              </div>
              <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="form-group">
                  <input type="date" class="form-control" name="folder[first_fech_folder]" id="edit_first_fech_folder">
                </div>
              </div>
              <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="form-group">
                  <input type="date" class="form-control" name="folder[second_fech_folder]"
                    id="edit_second_fech_folder">
                </div>
              </div>
            </div>

            <!-- Checkboxes organizados en dos filas -->
            <div class="row">
              <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="edit_chk_alta_fact_folder" value="Si"
                    name="folder[chk_alta_fact_folder]">
                  <label class="form-check-label" for="edit_chk_alta_fact_folder">Vo.Bo. Alta Facturación</label>
                </div>
              </div>
              <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="edit_chk_lib_folder" value="Si"
                    name="folder[chk_lib_folder]">
                  <label class="form-check-label" for="edit_chk_lib_folder">Vo.Bo. Liberación</label>
                </div>
              </div>
            </div>

            <div class="row mt-2">
              <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="edit_chk_orig_recib_folder" value="Si"
                    name="folder[chk_orig_recib_folder]">
                  <label class="form-check-label" for="edit_chk_orig_recib_folder">Original Recibido</label>
                </div>
              </div>
            </div>

            <div id="edit-fecha-original-recibido" style="display: none;" class="form-group" style="margin-top:15px;">
              <label for="edit_fech_orig_recib_folder">Fecha de original recibido:</label>
              <input type="date" class="form-control"  name="folder[fech_orig_recib_folder]"
                id="edit_fech_orig_recib_folder">
            </div>


            <!-- Botones del formulario -->
            <div class="form-group mt-4">
              <button type="submit" name="action" value="add" class="btn btn-primary" id="btnGuardarCliente" <?php echo empty($companiesList) ? 'disabled' : ''; ?>>
                <i class="fas fa-save"></i> Guardar Cliente
              </button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">
                <i class="fas fa-times"></i> Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>




  <!-- MODAL PARA AGREGAR/EDITAR EMPRESA (IGUAL QUE EN companies.php) -->
  <div class="modal fade company-modal" id="companyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-white" id="modalTitle">
            <i class="fas fa-building mr-2"></i><span id="modalTitleText">Editar Empresa</span>
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
              Complete la información de la empresa. Los campos con <span class="required-asterisk">*</span> son
              obligatorios.
            </div>

            <!-- Información Básica -->
            <div class="company-form-section">
              <h6><i class="fas fa-building"></i>Información General</h6>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Nombre Comercial <span class="required-asterisk">*</span></label>
                    <input type="text" class="form-control" name="company[name_company]" id="name_company" required
                      maxlength="100" placeholder="Inmobiliaria El Faro">
                    <small class="form-text text-muted">Nombre comercial de la empresa</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>RFC <span class="required-asterisk">*</span></label>
                    <input type="text" class="form-control" id="rfc_company" name="company[rfc_company]" required
                      maxlength="13" minlength="12" placeholder="ABC123456789" style="text-transform: uppercase;">
                    <small class="form-text text-muted">Registro Federal de Contribuyentes</small>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-8">
                  <div class="form-group">
                    <label>Razón Social <span class="required-asterisk">*</span></label>
                    <input type="text" class="form-control" name="company[razon_social]" id="razon_social" required
                      maxlength="150" placeholder="Inmobiliaria El Faro SA de CV">
                    <small class="form-text text-muted">Denominación legal completa</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Tipo de Persona <span class="required-asterisk">*</span></label>
                    <select class="form-control" name="company[tipo_persona]" id="tipo_persona" required>
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
                    <input type="date" class="form-control" name="company[fecha_constitucion]" id="fecha_constitucion"
                      max="<?php echo date('Y-m-d'); ?>">
                    <small class="form-text text-muted">Fecha de creación legal de la empresa</small>
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
                    <input type="tel" class="form-control" id="telefono" name="company[telefono]" maxlength="15"
                      placeholder="8123456789">
                    <small class="form-text text-muted">Número de contacto principal</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Email Corporativo</label>
                    <input type="email" class="form-control" name="company[email]" id="email" maxlength="100"
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
                    <input type="text" class="form-control" name="company[estado]" id="estado" maxlength="50"
                      placeholder="Nuevo León">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Ciudad/Municipio</label>
                    <input type="text" class="form-control" name="company[ciudad]" id="ciudad" maxlength="50"
                      placeholder="Monterrey">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-8">
                  <div class="form-group">
                    <label>Calle y Número</label>
                    <input type="text" class="form-control" name="company[calle]" id="calle" maxlength="150"
                      placeholder="Av. Constitución 123">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Colonia</label>
                    <input type="text" class="form-control" name="company[colonia]" id="colonia" maxlength="100"
                      placeholder="Centro">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Número Exterior</label>
                    <input type="text" class="form-control" name="company[num_exterior]" id="num_exterior"
                      maxlength="10" placeholder="123">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Número Interior</label>
                    <input type="text" class="form-control" name="company[num_interior]" id="num_interior"
                      maxlength="10" placeholder="A">
                    <small class="form-text text-muted">Opcional</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Código Postal</label>
                    <input type="text" class="form-control" id="codigo_postal" name="company[codigo_postal]"
                      maxlength="5" pattern="[0-9]{5}" placeholder="64000">
                  </div>
                </div>
              </div>
            </div>

            <!-- Representante Legal / Apoderado (Para Persona Moral y Persona Física) -->
            <div class="company-form-section" id="representante-section">
              <h6><i class="fas fa-user-tie"></i>Representante Legal / Apoderado</h6>
              <div class="alert alert-light border-left border-primary">
                <small><i class="fas fa-info-circle text-primary mr-1"></i>
                  Información de la persona autorizada para representar legalmente a la empresa</small>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Nombre(s)</label>
                    <input type="text" class="form-control" name="company[apoderado_nombre]" id="apoderado_nombre"
                      maxlength="50" placeholder="Juan Carlos" style="text-transform: capitalize;">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Apellido Paterno</label>
                    <input type="text" class="form-control" name="company[apoderado_apellido_paterno]"
                      id="apoderado_apellido_paterno" maxlength="50" placeholder="García"
                      style="text-transform: capitalize;">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Apellido Materno</label>
                    <input type="text" class="form-control" name="company[apoderado_apellido_materno]"
                      id="apoderado_apellido_materno" maxlength="50" placeholder="López"
                      style="text-transform: capitalize;">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>RFC del Representante</label>
                    <input type="text" class="form-control" id="apoderado_rfc" name="company[apoderado_rfc]"
                      maxlength="13" pattern="^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$" placeholder="GALO800101ABC"
                      style="text-transform: uppercase;">
                    <small class="form-text text-muted">RFC de la persona física (13 caracteres)</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>CURP del Representante</label>
                    <input type="text" class="form-control" id="apoderado_curp" name="company[apoderado_curp]"
                      maxlength="18" pattern="^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9A-Z][0-9]$"
                      placeholder="GALO800101HDFRPN09" style="text-transform: uppercase;">
                    <small class="form-text text-muted">Clave Única de Registro de Población (18 caracteres)</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Información del Fideicomiso (Solo para Fideicomisos) -->
            <div class="company-form-section fideicomiso-section" id="fideicomiso-section" style="display: none;">
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
                    <input type="text" class="form-control" name="company[fiduciario_nombre]" id="fiduciario_nombre"
                      maxlength="100" placeholder="Banco Nacional de México SA">
                    <small class="form-text text-muted">Institución fiduciaria</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>RFC del Fiduciario</label>
                    <input type="text" class="form-control" id="fiduciario_rfc" name="company[fiduciario_rfc]"
                      maxlength="13" placeholder="BNM840315PE6" style="text-transform: uppercase;">
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
                    <input type="text" class="form-control" name="company[fideicomitente_apellido_paterno]"
                      id="fideicomitente_apellido_paterno" maxlength="50" placeholder="Rodríguez">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Apellido Materno</label>
                    <input type="text" class="form-control" name="company[fideicomitente_apellido_materno]"
                      id="fideicomitente_apellido_materno" maxlength="50" placeholder="Flores">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>RFC del Fideicomitente</label>
                    <input type="text" class="form-control" id="fideicomitente_rfc" name="company[fideicomitente_rfc]"
                      maxlength="13" placeholder="ROFM800315AB2" style="text-transform: uppercase;">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>CURP del Fideicomitente</label>
                    <input type="text" class="form-control" id="fideicomitente_curp" name="company[fideicomitente_curp]"
                      maxlength="18" placeholder="ROFM800315MDFDRR04" style="text-transform: uppercase;">
                  </div>
                </div>
              </div>

              <!-- Fideicomisario -->
              <div class="row">
                <div class="col-md-12">
                  <h6 class="text-info"><i class="fas fa-user-friends mr-2"></i>Fideicomisario Principal</h6>
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
                    <input type="text" class="form-control" name="company[fideicomisario_apellido_paterno]"
                      id="fideicomisario_apellido_paterno" maxlength="50" placeholder="Méndez">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Apellido Materno</label>
                    <input type="text" class="form-control" name="company[fideicomisario_apellido_materno]"
                      id="fideicomisario_apellido_materno" maxlength="50" placeholder="Silva">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>RFC del Fideicomisario</label>
                    <input type="text" class="form-control" id="fideicomisario_rfc" name="company[fideicomisario_rfc]"
                      maxlength="13" placeholder="MESC750820CD1" style="text-transform: uppercase;">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>CURP del Fideicomisario</label>
                    <input type="text" class="form-control" id="fideicomisario_curp" name="company[fideicomisario_curp]"
                      maxlength="18" placeholder="MESC750820HDFLRL09" style="text-transform: uppercase;">
                  </div>
                </div>
              </div>

              <!-- Número de Fideicomiso -->
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Número de Fideicomiso</label>
                    <input type="text" class="form-control" name="company[numero_fideicomiso]" id="numero_fideicomiso"
                      maxlength="20" placeholder="F/12345">
                    <small class="form-text text-muted">Número de identificación del fideicomiso</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Fecha de Constitución del Fideicomiso</label>
                    <input type="date" class="form-control" name="company[fecha_fideicomiso]" id="fecha_fideicomiso"
                      max="<?php echo date('Y-m-d'); ?>">
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
              <i class="fas fa-save mr-1"></i><span id="btnText">Actualizar Empresa</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>



  <script src="../../resources/plugins/jquery/jquery.min.js"></script>
  <script src="../../resources/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../../resources/dist/js/adminlte.min.js"></script>
  <script src="../../resources/plugins/select2/js/select2.full.min.js"></script>
  <script src="../../resources/js/notifications.js"></script>
  <script src="../../resources/js/tracings.js"></script>
  <script src="../../resources/js/notify_folders.js"></script>

  <script>
    $(document).ready(function () {
      $('.selectAddCustomer, .selectEditCustomer').select2({
        theme: 'bootstrap4'
      });
    });
  </script>

  <script>
    $(document).ready(function () {
      $('[data-toggle="tooltip"]').tooltip({
        delay: { "show": 0, "hide": 0 } // Hacer que el tooltip aparezca y desaparezca inmediatamente
      });
    });
  </script>




  <script>
    $(document).ready(function () {
      // Limpiar el modal al cerrarlo
      $('#modalAgregarCarpeta').on('hidden.bs.modal', function () {
        // Limpiar formulario
        $('#formAgregarCliente')[0].reset();
      });

      // Validar que se seleccione una empresa al registrar cliente
      $('#formAgregarCliente').submit(function (e) {
        var empresaSelected = $('#empresa_select').val();
        if (!empresaSelected) {
          e.preventDefault();
          alert('Por favor selecciona una empresa para el cliente.');
          $('#empresa_select').focus();
          return false;
        }
      });

      // Formatear RFC en mayúsculas
      $('#rfc_cliente').on('input', function () {
        this.value = this.value.toUpperCase();
      });

      // Formatear CURP en mayúsculas
      $('#curp_cliente').on('input', function () {
        this.value = this.value.toUpperCase();
      });

      // Validación básica de RFC (opcional)
      $('#rfc_cliente').on('blur', function () {
        var rfc = this.value;
        if (rfc.length > 0 && rfc.length !== 13) {
          alert('El RFC debe tener exactamente 13 caracteres');
          this.focus();
        }
      });

      // Validación básica de CURP (opcional)
      $('#curp_cliente').on('blur', function () {
        var curp = this.value;
        if (curp.length > 0 && curp.length !== 18) {
          alert('La CURP debe tener exactamente 18 caracteres');
          this.focus();
        }
      });
    });
  </script>








  <script>
    $(document).ready(function () {
      let isEditMode = true; // Siempre en modo edición para folders

      // EDITAR EMPRESA - cuando se hace clic en editar folder
      $('.dropdown-item[data-folder-id]').click(function (e) {
        e.preventDefault();
        var folderId = $(this).data('folder-id');
        loadCompanyDataFromFolder(folderId);
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

      // VALIDACIONES EN TIEMPO REAL (igual que companies.php)
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

      // Validaciones para RFCs de personas físicas
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

      // Validación para RFC del fiduciario
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

      // Validaciones para CURPs
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
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');

        var formData = $(this).serialize() + '&ajax_action=update_company';

        $.ajax({
          url: window.location.href,
          type: 'POST',
          data: formData,
          dataType: 'json',
          success: function (response) {
            if (response && response.success) {
              alert('Empresa actualizada exitosamente');
              $('#companyModal').modal('hide');
              setTimeout(function () {
                window.location.reload();
              }, 1500);
            } else {
              alert('Error: ' + (response.message || 'Error al procesar la solicitud'));
            }
          },
          error: function (xhr, status, error) {
            alert('Error al comunicarse con el servidor');
          },
          complete: function () {
            saveBtn.prop('disabled', false).html(originalText);
          }
        });
      });

      // FUNCIÓN PARA CARGAR DATOS DE EMPRESA DESDE FOLDER
      function loadCompanyDataFromFolder(folderId) {
        // Primero obtener el folder para sacar el company_id
        $.ajax({
          type: "GET",
          url: "../../app/webservice.php",
          data: {
            action: "getFolderDetail",
            idFolder: folderId
          }
        }).done(function (response) {


          var parsedResponse = JSON.parse(response);
          var folderData = Array.isArray(parsedResponse) ? parsedResponse[0] : parsedResponse;


          // Obtener el ID de la empresa - USAR company_id en lugar de fk_folder
          var companyId = folderData.company_id || folderData.fk_folder;

          if (!companyId || companyId == 0) {
            alert('Error: No se encontró la empresa asociada al cliente');
            return;
          }

          // Ahora usar la misma función que companies.php
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
                // USAR EXACTAMENTE EL MISMO CÓDIGO QUE companies.php
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
                alert('Error al cargar los datos de la empresa: ' + (response.message || 'Error desconocido'));
              }
            },
            error: function (xhr, status, error) {

              alert('Error al comunicarse con el servidor');
            }
          });
        }).fail(function (xhr, status, error) {

          alert('Error al obtener la información del folder');
        });
      }
    });
  </script>




  <script>
    $(document).ready(function () {
      // Mostrar/ocultar secciones según tipo de persona
      $('#tipo_persona').change(function () {
        var tipo = $(this).val();

        // Ocultar todas las secciones
        $('#seccion_fisica, #seccion_moral, #seccion_fideicomiso').hide();

        // Limpiar campos requeridos anteriores
        $('.form-control').removeAttr('required');

        // Mostrar la sección correspondiente y establecer campos requeridos
        if (tipo === 'fisica') {
          $('#seccion_fisica').show();
          $('#pf_nombre, #pf_apellido_paterno, #pf_rfc').attr('required', true);
        } else if (tipo === 'moral') {
          $('#seccion_moral').show();
          $('#pm_razon_social, #pm_rfc').attr('required', true);
        } else if (tipo === 'fideicomiso') {
          $('#seccion_fideicomiso').show();
          $('#fid_razon_social, #fid_rfc').attr('required', true);
        }
      });

      // Mostrar/ocultar domicilio extranjero para Persona Física
      $('#pf_tiene_domicilio_extranjero').change(function () {
        if ($(this).is(':checked')) {
          $('#pf_domicilio_extranjero').show();
        } else {
          $('#pf_domicilio_extranjero').hide();
        }
      });

      // Mostrar/ocultar domicilio extranjero para Persona Moral
      $('#pm_tiene_domicilio_extranjero').change(function () {
        if ($(this).is(':checked')) {
          $('#pm_domicilio_extranjero').show();
        } else {
          $('#pm_domicilio_extranjero').hide();
        }
      });

      // Mostrar/ocultar domicilio extranjero para Fideicomiso
      $('#fid_tiene_domicilio_extranjero').change(function () {
        if ($(this).is(':checked')) {
          $('#fid_domicilio_extranjero').show();
        } else {
          $('#fid_domicilio_extranjero').hide();
        }
      });

      // Formatear RFC en mayúsculas
      $('#pf_rfc, #pm_rfc, #fid_rfc, #pm_apoderado_rfc, #fid_apoderado_rfc').on('input', function () {
        this.value = this.value.toUpperCase();
      });

      // Formatear CURP en mayúsculas
      $('#pf_curp, #pm_apoderado_curp, #fid_apoderado_curp').on('input', function () {
        this.value = this.value.toUpperCase();
      });

      // Limpiar el modal al cerrarlo
      $('#modalAgregarCarpeta').on('hidden.bs.modal', function () {
        $('#formAgregarCliente')[0].reset();
        $('#seccion_fisica, #seccion_moral, #seccion_fideicomiso').hide();
        $('#pf_domicilio_extranjero, #pm_domicilio_extranjero, #fid_domicilio_extranjero').hide();
        $('.form-control').removeAttr('required');
      });

      // Validación del formulario
      $('#formAgregarCliente').submit(function (e) {
        var empresaSelected = $('#empresa_select').val();
        var tipoPersona = $('#tipo_persona').val();

        if (!empresaSelected) {
          e.preventDefault();
          alert('Por favor selecciona una empresa para el cliente.');
          $('#empresa_select').focus();
          return false;
        }

        if (!tipoPersona) {
          e.preventDefault();
          alert('Por favor selecciona el tipo de persona.');
          $('#tipo_persona').focus();
          return false;
        }
      });
    });
  </script>



  <script>
    $(document).ready(function () {
      // Mostrar/ocultar secciones según tipo de persona
      $('#tipo_persona').change(function () {
        var tipo = $(this).val();

        // Ocultar todas las secciones
        $('#seccion_fisica, #seccion_moral, #seccion_fideicomiso').hide();

        // Limpiar campos requeridos anteriores
        $('.form-control').removeAttr('required');

        // Restablecer campos requeridos básicos
        $('#empresa_select, #tipo_persona').attr('required', true);

        // Mostrar la sección correspondiente y establecer campos requeridos
        if (tipo === 'fisica') {
          $('#seccion_fisica').show();
          $('#pf_nombre, #pf_apellido_paterno, #pf_rfc').attr('required', true);
        } else if (tipo === 'moral') {
          $('#seccion_moral').show();
          $('#pm_razon_social, #pm_rfc').attr('required', true);
        } else if (tipo === 'fideicomiso') {
          $('#seccion_fideicomiso').show();
          $('#fid_razon_social, #fid_rfc').attr('required', true);
        }
      });

      // Mostrar/ocultar domicilio extranjero para Persona Física
      $('#pf_tiene_domicilio_extranjero').change(function () {
        if ($(this).is(':checked')) {
          $('#pf_domicilio_extranjero').show();
        } else {
          $('#pf_domicilio_extranjero').hide();
        }
      });

      // Mostrar/ocultar domicilio extranjero para Persona Moral
      $('#pm_tiene_domicilio_extranjero').change(function () {
        if ($(this).is(':checked')) {
          $('#pm_domicilio_extranjero').show();
        } else {
          $('#pm_domicilio_extranjero').hide();
        }
      });

      // Mostrar/ocultar domicilio extranjero para Fideicomiso
      $('#fid_tiene_domicilio_extranjero').change(function () {
        if ($(this).is(':checked')) {
          $('#fid_domicilio_extranjero').show();
        } else {
          $('#fid_domicilio_extranjero').hide();
        }
      });

      // Formatear RFC en mayúsculas
      $('#pf_rfc, #pm_rfc, #fid_rfc, #pm_apoderado_rfc, #fid_apoderado_rfc').on('input', function () {
        this.value = this.value.toUpperCase();
      });

      // Formatear CURP en mayúsculas
      $('#pf_curp, #pm_apoderado_curp, #fid_apoderado_curp').on('input', function () {
        this.value = this.value.toUpperCase();
      });

      // Limpiar el modal al cerrarlo
      $('#modalAgregarCarpeta').on('hidden.bs.modal', function () {
        $('#formAgregarCliente')[0].reset();
        $('#seccion_fisica, #seccion_moral, #seccion_fideicomiso').hide();
        $('#pf_domicilio_extranjero, #pm_domicilio_extranjero, #fid_domicilio_extranjero').hide();
        $('.form-control').removeAttr('required');
        // Restablecer campos básicos como requeridos
        $('#empresa_select, #tipo_persona').attr('required', true);
      });

      // Validación del formulario
      $('#formAgregarCliente').submit(function (e) {
        var empresaSelected = $('#empresa_select').val();
        var tipoPersona = $('#tipo_persona').val();

        if (!empresaSelected) {
          e.preventDefault();
          alert('Por favor selecciona una empresa para el cliente.');
          $('#empresa_select').focus();
          return false;
        }

        if (!tipoPersona) {
          e.preventDefault();
          alert('Por favor selecciona el tipo de persona.');
          $('#tipo_persona').focus();
          return false;
        }
      });
    });
  </script>


</body>

</html>


<frame src="Portada.jsp?cveCita=null" name="Ceil" scrolling="no" noresize=""
  cd_frame_id_="583ad2de20a7d509e754b3d73c6e3e24"></frame>