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
        header('location: users.php'); // Redirecciona a la página "users.php" si el usuario no es de tipo administrador
    }
    
    //FUNCIÓN PARA MOSTRAR LOS DETALLES DE UN USUARIO
    // Obtener los detalles de un usuario específico utilizando el controlador.
    // Se pasa el ID del usuario ($_GET['id']) y una clave adicional ($_GET['key'])
    // para autenticación o validación, y se guarda el resultado en la variable $user.
    $user = $controller->getDetailUser($_GET['id'], $_GET['key']);
    
    //Si no se encuentra EL ID DEL USUARIO LO REGRESAMOS A LA CONSULTA PRINCIPAL
    // Verifica si la variable $user está vacía
    if(empty($user)){
        // Si la variable $user está vacía, redirige al usuario a la página 'users.php'
        header("location: users.php");
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
    </head>
    
    <body class="hold-transition sidebar-mini">
        <div class="wrapper" style="padding-top: 57px;">
            <?php include "../templates/navbar.php"; ?>
            <div class="content-wrapper">
                
                <div class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-8">
                                <h1 class="m-0 text-dark">Detalles del usuario</h1>
                            </div>
                            <div class="col-sm-4 text-right">
                                <!--<a href="<?=$_SERVER["HTTP_REFERER"]?>" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;" role="button" aria-pressed="true">Regresar</a>-->
                                <a href="users.php" class="btn btn-block" style="background-color: #FF5800; color: #ffffff;" role="button" aria-pressed="true">Regresar</a>
                            </div>
                        </div>
                        <hr>
                    </div>
                </div>
                
                <div class="content">
                    <div class="container-fluid">
                        <!-- COMPROBAMOS QUE EL TIPO DE USUARIO SEA DE TIPO ADMINISTRADOR (1)-->
                        <?php if($_SESSION['user']['id_type_user'] == 1){ ?>
                            <div class="row">
                                
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="card" style="height: 100%">
                                        <div class="card-body">
                                            <div class="col-md-12">
                                                
                                                <!--Campo de Nombre / name_user-->
                                                <div class="form-group">
                                                    <label>Nombre del usuario</label>
                                                    <input type="text" class="form-control" value="<?php echo $user['name_user']; ?>" readonly disabled>
                                                </div>
                                                
                                                <!--ID del tipo de Usuario / id_type_user -->
                                                <div class="form-group">
                                                    <label>Tipo de usuario</label>
                                                    <input type="text" class="form-control" value="<?php echo $user['name_type']; ?>" readonly disabled>
                                                </div>
                                                
                                                <!--Campo de RFC / rfc_user-->
                                                <!--<div class="form-group">
                                                    <label>RFC del usuario</label>
                                                    <input type="text" class="form-control" value="<?php echo $user['rfc_user']; ?>" readonly>
                                                </div>-->
                                                
                                                <!--Campo de Teléfono / phone_user-->
                                                <div class="form-group">
                                                    <label>Número de teléfono</label>
                                                    <input type="text" class="form-control" value="<?php echo $user['phone_user']; ?>" readonly disabled>
                                                </div>
                                                
                                                <!--Campo de Correo Eletrónico / email_user-->
                                                <div class="form-group">
                                                    <label>Correo electrónico</label>
                                                    <input type="email" class="form-control" value="<?php echo $user['email_user']; ?>" readonly disabled>
                                                </div>
                                                
                                                <!--Campo de ESTATUS / status_user -->
                                                <div class="form-group">
                                                    <label>Estatus</label>
                                                    <?php if ($user['status_user'] == 1) { ?>
                                                        <input type="text" class="form-control" value="Usuario activo" readonly disabled>
                                                    <?php } else if($user['status_user'] == 2) { ?>
                                                        <input type="text" class="form-control" value="Usuario inactivo" readonly disabled>
                                                    <?php } else { ?>
                                                        <input type="text" class="form-control" value="Usuario eliminado" readonly disabled>
                                                    <?php } ?>
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
                                                    <div style="text-align: center;"><br>
                                                        <?php if($user['photo_user'] != NULL){ ?>
                                                            <img width='230' height='230' style="border-radius: 50%; object-fit: cover;" src="<?php echo "../../uploads/users/".$user['photo_user']; ?>">
                                                        <?php } else { ?>
                                                            <img width='230' height='230' style="border-radius: 50%; object-fit: cover;" src="<?php echo "../../uploads/users/sin-foto.jpeg"; ?>">
                                                            <!--<p style="color: red; font-weight:bold;">Sin foto de perfil</p>-->
                                                        <?php } ?>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        <?php } ?>
                    </div>
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
        <script src="../../resources/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../resources/dist/js/adminlte.min.js"></script>
        <script src="../../resources/js/notifications.js"></script>
        <script src="../../resources/js/tracings.js"></script>
        <script src="../../resources/js/notify_folders.js"></script>
    
    </body>
</html>