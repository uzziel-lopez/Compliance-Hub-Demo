var countFolderNotifications = [];

$(document).ready(function() {
    // Función para obtener el número de notificaciones, número en la parte superior del icono de la campana
    function getFolderNotifications() {
        $.ajax({
            type: "GET",
            // linea de código para el desarrollo en entorno interno
            // url: "/app/webservice.php",
            
            // linea de código para el despliegue en Compliance Hub
            // url: "/app/webservice.php",
            
            // linea de código para el desarrollo en MAC entorno local /
            url: "/app/webservice.php",
            
            data: {
                action: "getFolderNotifications"
            }
        }).done(function(response) {
            var parsedResponse = JSON.parse(response);
            $("#numFolderNotifications").text(parsedResponse.total);
            toggleClearButtonFolderNotify(parsedResponse.total);
        });
    }
    
    // Función para controlar la visibilidad del botón LIMPIAR en el contenedor de los avisos de vencimiento
    function toggleClearButtonFolderNotify(notificationCount) {
        // Si el resultado es 0 quiere decir que no hay avisos pendientes y se oculta el botón de limpiar
        if (notificationCount == 0) {
            $("#clearFolderNotifications").hide();
        } else {
            $("#clearFolderNotifications").show();
        }
    }
    
    // Función para obtener los avisos de vencimiento asignados a un usuario y que no estan leidos
    function getNotWatchFolderNotifications() {
        $.ajax({
            type: "GET",
            // linea de código para el desarrollo en entorno interno
            // url: "/app/webservice.php",
            
            // linea de código para el despliegue en Compliance Hub
            // url: "/app/webservice.php",
            
            // linea de código para el desarrollo en MAC entorno local /
            url: "/app/webservice.php",
            
            data: {
                action: "getNotWatchFolderNotifications"
            }
        }).done(function(response) {
            countFolderNotifications = [];
            var parsedResponse = JSON.parse(response);
            var $folderNotificationsList = $("#notificationsFolderList");
            // Limpiar la lista antes de agregar nuevos elementos
            $folderNotificationsList.empty();
            // Si no hay registros de vencimientos pendientes mostramos una leyenda
            if (parsedResponse.length == 0) {
                $folderNotificationsList.append("<li>¡No hay avisos por revisar!</li>");
            } else {
                parsedResponse.forEach(function(item) {
                    countFolderNotifications.push(item.id_notify_folder);
                    
                    // Formatear las fechas usando la función formatDate
                    const formattedFirstDate = formatDate(item.first_fech_folder);
                    const formattedSecondDate = formatDate(item.second_fech_folder);
                    // Convertir la fecha de creación del aviso
                    var date = new Date(item.updated_at_notify_folder);
                    var options = {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: true,
                        timeZone: 'America/Mexico_City'
                    };
                    // Formatear la fecha y hora
                    var formattedDate = date.toLocaleString('es-MX', options);
                    
                    // LINEAS DE CÓDIGO PARA ENTORNO INTERNO //
                    // var folderUrl = "/backoffice/folders/subfolder.php?id=" + encodeURIComponent(item.id_folder) + "&key=" + encodeURIComponent(item.key_folder);
                    // var userUrl = "/backoffice/users/detail-user.php?id=" + encodeURIComponent(item.id_user_customer) + "&key=" + encodeURIComponent(item.key_user_customer);
                    
                    // ################## PARA DESPLIEGUE EN Compliance Hub ###############
                    // var folderUrl = "/backoffice/folders/subfolder.php?id=" + encodeURIComponent(item.id_folder) + "&key=" + encodeURIComponent(item.key_folder);
                    // var userUrl = "/backoffice/users/detail-user.php?id=" + encodeURIComponent(item.id_user_customer) + "&key=" + encodeURIComponent(item.key_user_customer);
                    
                    // ################## PARA DESARROLLO EN MAC entorno local / ###############
                    // Asignamos la URL correspondiente para ingresar a la carpeta del cliente
                    var folderUrl = "/backoffice/folders/subfolder.php?id=" + encodeURIComponent(item.id_folder) + "&key=" + encodeURIComponent(item.key_folder);
                    // Asignamos la URL para visualizar los detalles del usuario
                    var userUrl = "/backoffice/users/detail-user.php?id=" + encodeURIComponent(item.id_user_customer) + "&key=" + encodeURIComponent(item.key_user_customer);
                    
                    // Construir la parte condicional para 'asesorada por'
                    let asesoradaPor = '';
                    if (item.id_customer_folder != 0 && item.name_customer != null && item.usr_type_notify == 'Administrador' || item.id_customer_folder != 0 && item.name_customer != null && item.usr_type_notify == 'Empleado') {
                        asesoradaPor = `asesorado por <a href="${userUrl}" style="text-decoration: none; color: inherit; font-weight: bold;">${item.name_customer}</a>`;
                    }
                    
                    // Determinar el color según el mensaje_notify
                    let colorStyle = '';
                    if (item.message_notify === "Cerca de vencimiento") {
                        colorStyle = 'color: #FFA500; font-weight: bold;'; // Amarillo fuerte
                    } else if (item.message_notify === "Vencido") {
                        colorStyle = 'color: #DC3545; font-weight: bold;'; // Rojo
                    }
                    
                    // Crear el elemento <li> con el botón para eliminar de uno en uno los registros
                    var listItem = `
                        <li style="display: flex; justify-content: space-between; align-items: center; text-align: justify;">
                            <span style="flex: 1;">
                                &#8226; El cliente 
                                <a href="${folderUrl}" style="text-decoration: none; color: inherit; font-weight: bold;">
                                    ${item.name_folder}
                                </a> 
                                ${asesoradaPor} se encuentra <strong style="${colorStyle}">${item.message_notify}</strong> 
                                de su plazo (${formattedFirstDate} a ${formattedSecondDate}). El recordatorio fue enviado el ${formattedDate}
                            </span>
                            
                            <button 
                                hidden 
                                class="delete-notification-btn rounded-circle" 
                                style="width: 20px; height: 20px; display: flex; justify-content: center; align-items: center; padding: 0; border: none; position: relative; min-width: 20px; min-height: 20px; margin-left: 15px; background: none; outline: none;" 
                                data-id="${item.id_notify_folder}"
                            >
                                <i class="fas fa-check-circle" style="color: green; font-size: 20px;"></i>
                            </button>
                        </li>
                    `;
                    $folderNotificationsList.append(listItem);
                });
                
                // Agregar evento al botón de eliminar y ejecuta la función correspondiente
                $(".delete-notification-btn").click(function() {
                    var notifyId = $(this).data("id");
                    // Mostrar el overlay y deshabilitar el botón de eliminar
                    $(this).prop("disabled", true);
                    $("#loadingOverlayNotify").show();
                    deleteFolderNotification(notifyId, $(this));
                });
            }
        });
    }
    
    // Función para eliminar un registro individual de un aviso de vencimiento
    function deleteFolderNotification(notifyId, button) {
        $.ajax({
            type: "POST",
            // linea de código para el desarrollo en entorno interno
            // url: "/app/webservice.php",
            
            // linea de código para el despliegue en Compliance Hub
            // url: "/app/webservice.php",
            
            // linea de código para el desarrollo en MAC entorno local /
            url: "/app/webservice.php",
            
            data: {
                action: "deleteFolderNotification",
                id_notify_folder: notifyId
            }
        }).done(function(response) {
            try {
                var jsonResponse = JSON.parse(response);
                // Comprobar el status
                if (jsonResponse.status === "success") {
                    getNotWatchFolderNotifications();
                    getFolderNotifications();
                    // Ocultar el overlay y habilitar el botón de eliminar
                    $("#loadingOverlayNotify").hide();
                    button.prop("disabled", false);
                } else if (jsonResponse.status === "error") {
                    console.error("Error al eliminar el registro.");
                } else {
                    console.error("Respuesta inesperada del servidor:", jsonResponse);
                }
            } catch (error) {
                console.error("Error al analizar la respuesta JSON:", error, response);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
        });
    }
    
    function formatNumberWithZero(number) {
        return number < 10 ? "0" + number : number;
    }
    
    function formatDate(dateString) {
        var date = new Date(dateString);
        var day = formatNumberWithZero(date.getUTCDate());
        var month = formatNumberWithZero(date.getUTCMonth() + 1);
        var year = date.getUTCFullYear();
        return day + "/" + month + "/" + year;
    }
    
    // Función para limpiar los avisos de vencimiento - responde al botón azul LIMPIAR del contenedor
    function clearFolderNotifications(idNotify) {
        // Mostrar el overlay y deshabilitar el botón de LIMPIAR
        $("#loadingOverlayNotify").show();
        $("#clearFolderNotifications").prop("disabled", true);
        $.ajax({
            type: "GET",
            // linea de código para el desarrollo en entorno interno
            // url: "/app/webservice.php",
            
            // linea de código para el despliegue en Compliance Hub
            // url: "/app/webservice.php",
            
            // linea de código para el desarrollo en MAC entorno local /
            url: "/app/webservice.php",
            
            data: {
                action: "clearFolderNotifications",
                idNotify: JSON.stringify(idNotify) // Envía como JSON
            }
        }).done(function(response) {
            try {
                var parsedResponse = JSON.parse(response);
                if (parsedResponse.success) {
                    getFolderNotifications(); // Actualizar el número total de avisos
                    getNotWatchFolderNotifications(); // Actualizar la lista de avisos no marcados como leidos
                } else {
                    console.error(parsedResponse.message);
                }
            } catch (error) {
                console.error("Error parsing JSON response:", error, response);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX request failed:", textStatus, errorThrown);
        }).always(function() {
            // Ocultar el overlay y habilitar el botón
            $("#loadingOverlayNotify").hide();
            $("#clearFolderNotifications").prop("disabled", false);
        });
    }
    
    // Manejar el clic en el botón LIMPIAR
    $(".dropdown-header-folders button").click(function() {
        // Ejecuta la función para limpiar el registro de los avisos
        clearFolderNotifications(countFolderNotifications);
    });
    
    // Ejecutar las funciones inmediatamente
    getFolderNotifications(); // Actualizar el número total de avisos
    getNotWatchFolderNotifications(); // Actualizar la lista de avisos no marcados como leidos
    
    // Configurar el intervalo para ejecutar las funciones cada 4 segundos
    setInterval(function() {
        getFolderNotifications(); // Actualizar el número total de avisos
        getNotWatchFolderNotifications(); // Actualizar la lista de avisos no marcados como leidos
    }, 4000);
});