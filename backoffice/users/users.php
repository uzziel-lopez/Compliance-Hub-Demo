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
  
  // COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMIN (1)
  if($_SESSION['user']['id_type_user'] != 1) { // Comprueba si el tipo de usuario almacenado en la sesión no es igual a 1 (tipo de usuario "admin")
    header('location: ../../index.php'); // Redirecciona a la página "index.php" si el usuario no es de tipo administrador
  }
  
  //ACCIÓN PARA ELIMINAR USUARIOS
  // Comprueba si se ha enviado alguna acción a través del método POST
  if(!empty($_POST['action'])){
    // Comprueba si la acción es eliminar
    if($_POST['action'] == 'delete'){
      // Obtiene el ID del usuario a eliminar desde el formulario
      $userId = $controller->deleteUser($_POST['empl']);
      // Si se pudo eliminar correctamente el usuario
      if($userId){
        // Redirige al usuario a la página de usuarios después de eliminar
        header('location: users.php');
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
    <link rel="stylesheet" href="../../resources/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../../resources/plugins/datatables-bs4/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="../../resources/dist/css/adminlte.min.css">
    <link rel="icon" href="../../resources/img/icono.png">
    <script src="../../resources/js/jquery-3.5.1.min.js"></script>
    <style>
      /* Ocultar las flechas de ordenamiento en la primera columna */
      #tblUsers th:first-child {
        cursor: default;
      }
      /* Forzar la eliminación de las flechas que quedan */
      #tblUsers th:first-child::before,
      #tblUsers th:first-child::after {
        content: none !important;
      }
      
      /* NUEVOS ESTILOS PARA EMPRESA */
      .empresa-info {
        text-align: center !important;
        line-height: 1.3;
      }
      
      .empresa-info strong {
        font-size: 0.9em;
        color: #495057;
      }
      
      .empresa-info .rfc-text {
        font-size: 0.8em;
        color: #6c757d;
        font-style: italic;
      }
      
      .empresa-info .role-badge {
        margin-top: 3px;
        display: inline-block;
      }
      
      .badge-sm {
        font-size: 0.7em;
        padding: 0.25em 0.5em;
      }
      
      .no-empresa {
        color: #6c757d;
        font-style: italic;
        font-size: 0.85em;
      }
      
      /* Mejorar spacing de badges */
      .badge {
        font-weight: 500;
      }
      
      /* Filtros mejorados */
      .filtros-container {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 15px;
      }
      
      .filter-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
        display: block;
      }
    </style>
  </head>
  
  <body class="hold-transition sidebar-mini">
    <div class="wrapper" style="padding-top: 57px;">
      <?php include "../templates/navbar.php"; ?>
      
        <div class="content-wrapper">
          <div class="content-header">
            <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
            <?php if($_SESSION['user']['id_type_user'] == 1){ ?>
              <div class="container-fluid">
                <div class="row justify-content-between mb-2">
                  <div class="col-sm-8">
                    <h1 class="m-0 text-dark">Lista completa de usuarios</h1>
                  </div>
                  <div class="col-sm-4 text-right">
                    <a href="create-user.php" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;" role="button" aria-pressed="true">
                      <i class="fas fa-plus pr-2"></i>Agregar nuevo usuario
                    </a>
                  </div>
                </div>
                <hr>
                
                <!-- FILTROS MEJORADOS -->
                <div class="filtros-container">
                  <form class="row" action="#" method="get">
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <label class="filter-label">Estado del Usuario</label>
                      <select id="statusDDL" name="status" class="form-control filtrosDDL">
                        <option value="1">Usuarios Activos</option>
                        <option value="2">Usuarios Inactivos</option>
                      </select>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <label class="filter-label">Tipo de Usuario</label>
                      <select id="typeDDL" name="type" class="form-control filtrosDDL">
                        <option value="">Todos los tipos</option>
                        <option value="1">Administrador</option>
                        <option value="3">Cliente Empresa</option>
                      </select>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <label class="filter-label">Empresa</label>
                      <select id="companyDDL" name="company" class="form-control filtrosDDL">
                        <option value="">Todas las empresas</option>
                        <!-- Se llena dinámicamente con AJAX -->
                      </select>
                    </div>
                  </form>
                </div>
              
              </div>
            <?php } ?>
          </div>
          
          <div class="content">
            <div class="container-fluid">
              <!-- INFORMACIÓN DE TOTALES MEJORADA -->
              <div class="row mb-3">
                <div class="col-lg-3 col-md-6">
                  <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                      <span class="info-box-text">Total Usuarios</span>
                      <span class="info-box-number" id="numTotalsUsers">0</span>
                    </div>
                  </div>
                </div>
                <div class="col-lg-3 col-md-6">
                  <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-building"></i></span>
                    <div class="info-box-content">
                      <span class="info-box-text">Con Empresa</span>
                      <span class="info-box-number" id="numUsersWithCompany">0</span>
                    </div>
                  </div>
                </div>
                <div class="col-lg-3 col-md-6">
                  <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-user-tie"></i></span>
                    <div class="info-box-content">
                      <span class="info-box-text">Admins Empresa</span>
                      <span class="info-box-number" id="numAdminEmpresas">0</span>
                    </div>
                  </div>
                </div>
                <div class="col-lg-3 col-md-6">
                  <div class="info-box">
                    <span class="info-box-icon bg-primary"><i class="fas fa-cogs"></i></span>
                    <div class="info-box-content">
                      <span class="info-box-text">Operadores</span>
                      <span class="info-box-number" id="numOperadores">0</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-body">
                      <div class="table-responsive">
                        <table class="table table-sm table-striped table-bordered" id="tblUsers">
                          <thead>
                            <th></th>
                            <th>Fotografía</th>
                            <th>Nombre</th>
                            <th>Empresa / Rol</th>
                            <th>Correo electrónico</th>
                            <th>Tipo</th>
                            <th>Fecha de registro</th>
                            <th>Acciones</th>
                          </thead>
                          <tbody id="dataUsers"></tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
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
    <script src="../../resources/plugins/datatables/jquery.dataTables.js"></script>
    <script src="../../resources/plugins/datatables-bs4/js/dataTables.bootstrap4.js"></script>
    <script src="../../resources/dist/js/adminlte.min.js"></script>
    <script src="../../resources/js/notifications.js"></script>
    <script src="../../resources/js/tracings.js"></script>
    <script src="../../resources/js/notify_folders.js"></script>
    
    <script>
      // Asignar valores de la variable de sesión a variables JavaScript
      var userId = <?php echo json_encode($_SESSION['user']['id_user']); ?>;
    </script>

    <script>
      $(function () {
        loadUsers(1);
        loadCompaniesFilter();
      });
      
      $(document).ready(function(){
        $('.filtrosDDL').on('change', function() {
          var statusSelect = $("#statusDDL").val();
          var typeSelect = $("#typeDDL").val();
          var companySelect = $("#companyDDL").val();
          loadUsersFiltered(statusSelect, typeSelect, companySelect);
        });
      });
      
      // Cargar empresas para el filtro
      function loadCompaniesFilter() {
        $.ajax({
          url: '../../app/webservice.php',
          data: { action: 'getActiveCompanies' },
          success: function(response) {
            var companies = JSON.parse(response);
            var options = '<option value="">Todas las empresas</option>';
            companies.forEach(function(company) {
              options += '<option value="' + company.id_company + '">' + 
                         company.name_company + ' (' + company.rfc_company + ')</option>';
            });
            $('#companyDDL').html(options);
          }
        });
      }
      
      function loadUsers(statusSelect) {
        loadUsersFiltered(statusSelect, '', '');
      }
      
      function loadUsersFiltered(statusSelect, typeSelect = '', companySelect = '') {
        var table = $('#tblUsers').DataTable({
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
            { "targets": 0, "orderable": false }, // Ver detalles
            { "targets": 1, "orderable": false }, // Fotografía
            { "targets": 3, "orderable": false }, // Empresa
            { "targets": 5, "orderable": false }, // Tipo
            { "targets": 7, "orderable": false }  // Acciones
          ],
          "destroy": true,
          "pageLength": 25,
          "responsive": true
        });
        
        table.clear().draw();
        
        $.ajax({
          type: "GET",
          url: "../../app/webservice.php",
          data: { 
            action: "getUsers",
            status: statusSelect,
            type: typeSelect,
            company: companySelect
          }
        }).done(function(response) {
          var parsedResponse = JSON.parse(response);
          
          // Contadores para estadísticas
          var totalUsers = parsedResponse.length;
          var usersWithCompany = 0;
          var adminEmpresas = 0;
          var operadores = 0;
          
          $("#dataUsers").empty();
          
          parsedResponse.forEach(function(item) {
            // Aplicar filtros en el frontend
            if (typeSelect && item.id_type_user != typeSelect) return;
            if (companySelect && item.id_company != companySelect) return;
            
            // Contar estadísticas
            if (item.id_company) usersWithCompany++;
            if (item.company_role === 'admin_empresa') adminEmpresas++;
            if (item.company_role === 'operador') operadores++;
            
            var date = new Date(item.created_at_user);
            var day = ("0" + date.getDate()).slice(-2);
            var month = ("0" + (date.getMonth() + 1)).slice(-2);
            var year = date.getFullYear();
            var hours = ("0" + date.getHours()).slice(-2);
            var minutes = ("0" + date.getMinutes()).slice(-2);
            var seconds = ("0" + date.getSeconds()).slice(-2);
            var formattedDate = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
            
            var actionsLink = '';
            if (userId != item.id_user) {
              actionsLink = `
                <div class="btn-group" role="group">
                  <a href='update-user.php?id=${item.id_user}&key=${item.key_user}' 
                     class='btn btn-sm btn-primary' title='Editar usuario'>
                    <i class='fas fa-pen'></i>
                  </a>
                  <form action='users.php' method='POST' style='display: inline;'>
                    <input name='empl[idUser]' type='hidden' value='${item.id_user}'>
                    <input name='empl[keyUser]' type='hidden' value='${item.key_user}'>
                    <button class='btn btn-danger btn-sm' name='action' value='delete' 
                            title='Eliminar usuario'
                            onclick='return confirm("¿Está seguro de eliminar al usuario ${item.name_user}?")'>
                      <i class='fas fa-trash'></i>
                    </button>
                  </form>
                </div>`;
            } else {
              actionsLink = '<span class="badge badge-secondary">Usuario actual</span>';
            }
            
            // Formatear información de empresa mejorada
            var empresaInfo = '';
            if (item.name_company) {
              empresaInfo = `<div class="empresa-info">
                <strong>${item.name_company}</strong><br>
                <span class="rfc-text">${item.rfc_company}</span>`;
              
              if (item.id_type_user == 3 && item.company_role) {
                var roleLabel = '';
                var roleClass = '';
                
                switch(item.company_role) {
                  case 'admin_empresa': 
                    roleLabel = 'Admin Empresa';
                    roleClass = 'badge-primary';
                    break;
                  case 'operador': 
                    roleLabel = 'Operador';
                    roleClass = 'badge-success';
                    break;
                  case 'consultor': 
                    roleLabel = 'Consultor';
                    roleClass = 'badge-info';
                    break;
                }
                empresaInfo += `<br><span class="badge ${roleClass} badge-sm">${roleLabel}</span>`;
              }
              empresaInfo += '</div>';
            } else {
              if (item.id_type_user == 3) {
                empresaInfo = '<div class="no-empresa"><i class="fas fa-exclamation-triangle text-warning"></i> Sin empresa asignada</div>';
              } else {
                empresaInfo = '<div class="no-empresa">Empleado interno</div>';
              }
            }
            
            var newRow =
            "<tr>" +
              "<td style='text-align:center;'>" +
                "<a href='detail-user.php?id=" + item.id_user + "&key=" + item.key_user + "' " +
                "class='btn btn-sm btn-success' title='Ver detalles'>" +
                "<i class='fas fa-eye'></i></a>" +
              "</td>" +
              "<td style='text-align:center;'>" +
                (item.photo_user != null ?
                  "<img width='70' height='70' style='border-radius: 50%; object-fit: cover; border: 2px solid #dee2e6;' src='../../uploads/users/" + item.photo_user + "'>" :
                  "<img width='70' height='70' style='border-radius: 50%; object-fit: cover; border: 2px solid #dee2e6;' src='../../uploads/users/sin-foto.jpeg'>"
                ) +
              "</td>" +
              "<td style='text-align:center; vertical-align: middle;'>" + 
                "<strong>" + item.name_user + "</strong><br>" +
                "<small class='text-muted'>" + (item.rfc_user || 'Sin RFC') + "</small>" +
              "</td>" +
              "<td>" + empresaInfo + "</td>" +
              "<td style='text-align:center; vertical-align: middle;'>" + item.email_user + "</td>" +
              "<td style='text-align:center; vertical-align: middle;'>" + 
                "<span class='badge " + 
                (item.id_type_user == 1 ? 'badge-danger' : 
                 item.id_type_user == 2 ? 'badge-secondary' : 'badge-primary') + "'>" +
                item.name_type + "</span>" +
              "</td>" +
              "<td style='text-align:center; vertical-align: middle;'>" + 
                "<small>" + formattedDate + "</small>" +
              "</td>" +
              "<td style='text-align:center; vertical-align: middle;'>" + actionsLink + "</td>" + 
            "</tr>";
            
            table.row.add($(newRow)[0]);
          });
          
          // Actualizar estadísticas
          $("#numTotalsUsers").html(totalUsers);
          $("#numUsersWithCompany").html(usersWithCompany);
          $("#numAdminEmpresas").html(adminEmpresas);
          $("#numOperadores").html(operadores);
          
          table.draw();
        }).fail(function() {
          console.error('Error al cargar usuarios');
          $("#numTotalsUsers").html('Error');
        });
      }
    </script>
  </body>
</html>