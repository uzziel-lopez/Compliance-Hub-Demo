<?php
    include "config.php";
    include "debug.php";
    include "WebController.php";
    $controller = new WebController();
    $adminsList = $controller->getCustomersList(1, 1);
    $employeeList = $controller->getCustomersList(2, 1);
    
    try {
        // Declaración de fechas para el estatus "cerca de vencimiento"
        $fInicio = date('Y-m-d');
        $fFin = date('Y-m-d', strtotime('+60 days')); // Hace 60 días
        
        // Declaración de fechas par el estatus "vencido"
        $fechaInicio = date('Y-m-d', strtotime('-60 days')); // Hace 60 días
        $fechaFin = date('Y-m-d');
        
        // Ejecutar la consulta para obtener carpetas "vigentes"
        $carpetasVigentes = $controller->ws_idxGetFolders(null, null, '02', null);
        // Ejecutar la consulta para obtener carpetas "cerca de vencimiento"
        $carpetasCercaVencimiento = $controller->ws_idxGetFolders($fInicio, $fFin, '01', null);
        // Ejecutar la consulta para obtener carpetas "vencidas"
        $carpetasVencidas = $controller->ws_idxGetFolders($fechaInicio, $fechaFin, '03', null);
        
        // Combinar los resultados en un solo array
        $carpetas = array_merge($carpetasCercaVencimiento, $carpetasVencidas);
        // Combinamos los resultados de las consultas para obtener a los administradores y empleados
        $listUsers = array_merge($adminsList, $employeeList);
        
        // foreach para revisar si existen registros acerca de las carpetas vigentes
        foreach ($carpetasVigentes as $onlyVigente) {
            $existeCarpetaVigente = $controller->checkVigentes($onlyVigente['id_folder']);
            // Si existe el registro actualizamos su estatus a VIGENTE
            if($existeCarpetaVigente){
                foreach ($existeCarpetaVigente as $data) {
                    $status = 'Vigente';
                    $updated = $controller->updateNotifyVigente($data, $status);
                }
            }
        }
        
        // Procesar las carpetas Vencidas y Cerca de vencimiento para registrar las notificaciones
        // Registrar notificaciones para los vendeores
        foreach ($carpetas as $carpeta) {
            $idFolder = $carpeta['id_folder'];
            $status = ($carpeta['dias'] >= 1) ? 'Vencido' : 'Cerca de vencimiento';
            $type_usr = "Vendedor";
            // Verificar si ya existe una notificación activa para esta carpeta y usuario
            $existingNotification = $controller->checkNotification($idFolder, $carpeta['id_customer_folder']);
            if ($existingNotification) {
                if($existingNotification['message_notify'] != $status){
                    // Si existe, actualizar el estado de la notificación
                    $updated = $controller->updateNotificationStatus($idFolder, $carpeta['id_customer_folder'], $status);
                    if ($updated) {
                        // echo "Notificación actualizada para el vendedor";
                    } else {
                        // echo "Fallo al actualizar notificación para el vendedor";
                    }
                }
            } else {
                // Si no existe, registrar una nueva notificación
                $notifyId = $controller->createNotifyFolder($carpeta, $status, $type_usr);
                if ($notifyId) {
                    // echo "Notificación registrada para el vendedor";
                } else {
                    // echo "Fallo al registrar notificación para el vendedor";
                }
            }
            
            // Registrar notificaciones para administradores y empleados
            foreach ($listUsers as $admin) {
                if($admin['id_type_user'] == 1){
                    $usr_type = "Administrador";
                } else {
                    $usr_type = "Empleado";
                }
                $adminData = [
                    'id_folder' => $carpeta['id_folder'],
                    'id_customer_folder' => $admin['id_user'],
                ];
                $existingAdminNotification = $controller->checkNotification($carpeta['id_folder'], $admin['id_user']);
                if ($existingAdminNotification) {
                    if($existingAdminNotification['message_notify'] != $status){
                        // Actualizar la notificación existente
                        $updatedAdmin = $controller->updateNotificationStatus($carpeta['id_folder'], $admin['id_user'], $status);
                        if ($updatedAdmin) {
                            // echo "Notificación actualizada para el administrador";
                        } else {
                            // echo "Fallo al actualizar notificación para el administrador";
                        }
                    }
                } else {
                    // Registrar nueva notificación
                    $adminNotifyId = $controller->createNotifyFolder($adminData, $status, $usr_type);
                    if ($adminNotifyId) {
                        // echo "Notificación registrada para el administrador";
                    } else {
                        // echo "Fallo al registrar notificación para el administrador";
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Manejo de errores
        error_log("Error al registrar notificaciones: " . $e->getMessage());
    }
?>