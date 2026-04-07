var countTracings = [];

$(document).ready(function() {
    // Función para obtener el número de seguimientos asignados a un empleado, número en la parte superior del icono del mensaje
    function getNoticationsTracings() {
        $.ajax({
            type: "GET",
            url: "/app/webservice.php",
            data: {
                action: "getNoticationsTracings"
            }
        }).done(function(response) {
            var parsedResponse = JSON.parse(response);
            
            // Actualizar AMBOS contadores
            $("#numTracings").text(parsedResponse.total);
            $("#notification-badge").text(parsedResponse.total);
            
            toggleClearButton(parsedResponse.total);
        });
    }
    
    // Función para controlar la visibilidad del botón LIMPIAR en el contenedor de los seguimientos / tracings
    function toggleClearButton(notificationCount) {
        // Si el resultado es 0 quiere decir que no hay seguimientos pendientes y se oculta el botón de limpiar
        if (notificationCount == 0) {
            $("#clearTracings").hide();
        } else {
            $("#clearTracings").show();
        }
    }
    
    // Función para obtener los seguimientos asignados a un usuario y que no estan marcados como leidos
    function getNotWachTracings() {
        $.ajax({
            type: "GET",
            // linea de código para el desarrollo en entorno interno
            // url: "/app/webservice.php",
            
            // linea de código para el despliegue en Compliance Hub
            // url: "/app/webservice.php",
            
            // linea de código para el desarrollo en MAC entorno local /
            url: "/app/webservice.php",
            
            data: {
                action: "getNotWachTracings"
            }
        }).done(function(response) {
            countTracings = [];
            var parsedResponse = JSON.parse(response);
            var $tracingsList = $("#tracingsList");
            // Limpiar la lista antes de agregar nuevos elementos
            $tracingsList.empty();
            // Si no hay registros de seguimientos pendientes mostramos una leyenda
            if (parsedResponse.length == 0) {
                $tracingsList.append("<li>¡No hay seguimientos por revisar!</li>");
            } else {
                parsedResponse.forEach(function(item) {
                    countTracings.push(item.id_notify);
                    
                    // Convertir la fecha a un objeto Date
                    var date = new Date(item.created_at_notify);
                    // Opciones para formatear la fecha y hora en formato de México
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
                    
                    // ########## Despliegue en ENTORNO INTERNO ##########
                    // var folderUrl = "/backoffice/folders/subfolder.php?id=" + encodeURIComponent(item.id_folder) + "&key=" + encodeURIComponent(item.key_folder);
                    
                    // ########## Despliegue en Compliance Hub ##########
                    // var folderUrl = "/backoffice/folders/subfolder.php?id=" + encodeURIComponent(item.id_folder) + "&key=" + encodeURIComponent(item.key_folder);
                    
                    // ########## Despliegue en MAC entorno local / ##########
                    var folderUrl = "/backoffice/folders/subfolder.php?id=" + encodeURIComponent(item.id_folder) + "&key=" + encodeURIComponent(item.key_folder);
                    
                    // Función para truncar texto con puntos suspensivos
                    function truncateText(text, maxLength) {
                        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
                    }
                    var truncatedComment = truncateText(item.comment_tracing, 100); // Cambia 100 por el número de caracteres deseados para mostrar el comentario del seguimiento
                    
                    // Crear el elemento <li> con el botón para eliminar de uno en uno los registros de los seguimientos
                    var listItem = `
                        <li style="display: flex; justify-content: space-between; align-items: center; text-align: justify;">
                            <span style="flex: 1;">
                            &#8226; El usuario <strong>${item.name_user}</strong> ha añadido el seguimiento <strong>${truncatedComment}</strong> 
                            al expediente del cliente 
                            <a href="${folderUrl}" style="text-decoration: none; color: inherit; font-weight: bold;">
                                ${item.name_folder}
                            </a> 
                            el día ${formattedDate}
                            </span>
                            
                            <button 
                                class="delete-notification-tracing-btn rounded-circle" 
                                style="width: 20px; height: 20px; display: flex; justify-content: center; align-items: center; padding: 0; border: none; position: relative; min-width: 20px; min-height: 20px; margin-left: 15px; background: none; outline: none;" 
                                data-id="${item.id_notify}"
                            >
                                <i class="fas fa-check-circle" style="color: green; font-size: 20px;"></i>
                            </button>
                        </li>
                    `;
                    $tracingsList.append(listItem);
                });
                
                // Agregar evento al botón de eliminar o marcar como leido y ejecuta la función correspondiente
                $(".delete-notification-tracing-btn").click(function() {
                    var notification_tracing = $(this).data("id");
                    // Mostrar el overlay y deshabilitar el botón de eliminar
                    $(this).prop("disabled", true);
                    $("#loadingOverlayNotify").show();
                    deleteTracingNotify(notification_tracing, $(this));
                });
            }
        });
    }
    
    // Función para eliminar o marcar como leido un registro individual de un seguimiento
    function deleteTracingNotify(notifyTracingId, button) {
        $.ajax({
            type: "POST",
            // linea de código para el desarrollo en entorno interno
            // url: "/app/webservice.php",
            
            // linea de código para el despliegue en Compliance Hub
            // url: "/app/webservice.php",
            
            // linea de código para el desarrollo en MAC entorno local /
            url: "/app/webservice.php",
            
            data: {
                action: "deleteTracingNotify",
                id_notify_tracing: notifyTracingId
            }
        }).done(function(response) {
            try {
                var jsonResponse = JSON.parse(response);
                // Comprobar el status
                if (jsonResponse.status === "success") {
                    getNoticationsTracings(); // Actualizar el número total de seguimientos
                    getNotWachTracings(); // Actualizar la lista de seguimientos no marcados como leidos
                    // Ocultar el overlay y habilitar el botón de eliminar
                    $("#loadingOverlayNotify").hide();
                    button.prop("disabled", false);
                } else if (jsonResponse.status === "error") {
                    console.error("Error al eliminar el registro del seguimiento.");
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
    
    // Función para limpiar los seguimientos o tracings asignados a un usuario - responde al botón azul LIMPIAR del contenedor
    function clearTracingsNotify(idNotify) {
        // Mostrar el overlay y deshabilitar el botón de LIMPIAR
        $("#loadingOverlayNotify").show();
        $("#clearTracings").prop("disabled", true);
        $.ajax({
            type: "GET",
            // linea de código para el desarrollo en entorno interno
            // url: "/app/webservice.php",
            
            // linea de código para el despliegue en Compliance Hub
            // url: "/app/webservice.php",
            
            // linea de código para el desarrollo en MAC entorno local /
            url: "/app/webservice.php",
            
            data: {
                action: "clearTracingsNotify",
                idNotify: JSON.stringify(idNotify) // Envía como JSON
            }
        }).done(function(response) {
            try {
                var parsedResponse = JSON.parse(response);
                if (parsedResponse.success) {
                    getNoticationsTracings(); // Actualizar el número total de seguimientos
                    getNotWachTracings(); // Actualizar la lista de seguimientos no marcados como leidos
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
            $("#clearTracings").prop("disabled", false);
        });
    }
    
    // Manejar el clic en el botón LIMPIAR
    $(".dropdown-header-tracings button").click(function() {
        // Ejecuta la función para limpiar los registros de los seguimientos
        clearTracingsNotify(countTracings);
    });
    
    // Ejecutar las funciones inmediatamente
    getNoticationsTracings(); // Actualizar el número total de seguimientos
    getNotWachTracings(); // Actualizar la lista de seguimientos no marcados como leidos
    
    // Configurar el intervalo para ejecutar las funciones cada 4 segundos
    setInterval(function() {
        getNoticationsTracings(); // Actualizar el número total de seguimientos
        getNotWachTracings(); // Actualizar la lista de seguimientos no marcados como leidos
    }, 4000);
});