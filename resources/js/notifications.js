var countNotify = [];

$(document).ready(function() {
    // Función para obtener el número de notificaciones de la subida de documentos, número en la parte superior del icono del folder
    function getNotifications() {
        $.ajax({
            type: "GET",
            // linea de código para el desarrollo en entorno interno
            // url: "/app/webservice.php",
            
            // linea de código para el despliegue en Compliance Hub
            // url: "/app/webservice.php",
            
            // linea de código para el desarrollo en MAC entorno local /
            url: "/app/webservice.php",
            
            data: {
                action: "getNotifications"
            }
        }).done(function(response) {
            var parsedResponse = JSON.parse(response);
            $("#numNotifications").text(parsedResponse.total);
            toggleClearButton(parsedResponse.total);
        });
    }
    
    // Función para controlar la visibilidad del botón LIMPIAR en el contenedor de las notificaciones de los documentos
    function toggleClearButton(notificationCount) {
        // Si el resultado es 0 quiere decir que no hay notificaciones de documentos pendientes y se oculta el botón de limpiar
        if (notificationCount == 0) {
            $("#clearNotifications").hide();
        } else {
            $("#clearNotifications").show();
        }
    }
    
    // Función para obtener los documentos no vistos
    function getNotWachDocuments() {
        $.ajax({
            type: "GET",
            // linea de código para el desarrollo en entorno interno
            // url: "/app/webservice.php",
            
            // linea de código para el despliegue en Compliance Hub
            // url: "/app/webservice.php",
            
            // linea de código para el desarrollo en MAC entorno local /
            url: "/app/webservice.php",
            
            data: {
                action: "getNotWachDocuments"
            }
        }).done(function(response) {
            countNotify = [];
            var parsedResponse = JSON.parse(response);
            var $documentList = $("#documentList");
            // Limpiar la lista antes de agregar nuevos elementos
            $documentList.empty();
            // Si no hay registros de notificaciones pendientes mostramos una leyenda
            if (parsedResponse.length == 0) {
                $documentList.append("<li>¡No hay notificaciones por revisar!</li>");
            } else {
                parsedResponse.forEach(function(item) {
                    countNotify.push(item.id_document);
                    
                    // Convertir la fecha a un objeto Date
                    var date = new Date(item.created_at_document);
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
                    
                    // Crear URL para el documento y la carpeta (modifica estas URLs según tu estructura)
                    // ############## PARA DESPLIEGUE EN LOCAL O MODIFICAR EL ANTIGUO PARA QUE NO SE ABRA EN EXTERNO
                    
                    // NUEVO - - lineas de código para abrir el archivo en un almacenamiento nuevo con la dirección URL de entorno interno o Local - NUEVO
                    // var documentUrl = "/backoffice/folders/extensions/open_pdf.php?folder=" + encodeURIComponent(item.key_folder) + "&file=" + encodeURIComponent(item.file_name_document);
                    
                    // ANTIGUO - - lineas de código para el desarrollo en entorno interno - ANTIGUO
                    //var documentUrl = "/uploads/documents/" + encodeURIComponent(item.key_folder) + "/" + encodeURIComponent(item.file_name_document);
                    
                    // Asignamos una URL para ingresar a los detalles de la carpeta o folder donde esta el documento que fue cargado
                    // var folderUrl = "/backoffice/folders/subfolder.php?id=" + encodeURIComponent(item.id_folder) + "&key=" + encodeURIComponent(item.key_folder);
                    
                    // ################## PARA DESPLIEGUE EN Compliance Hub ###############
                    // lineas de código para el despliegue en Compliance Hub
                    
                    // NUEVO - - - Lineas de código para abrir el archivo en un almacenamiento nuevo con la dirección URL de Compliance Hub - CÓDIGO NUEVO
                    // var documentUrl = "/backoffice/folders/extensions/open_pdf.php?folder=" + encodeURIComponent(item.key_folder) + "&file=" + encodeURIComponent(item.file_name_document);
                    
                    // ANTIGUIO - - - CÓDIGO ANTIGUO
                    // var documentUrl = "/uploads/documents/" + encodeURIComponent(item.key_folder) + "/" + encodeURIComponent(item.file_name_document);
                    
                    // Asignamos una URL para ingresar a los detalles de la carpeta o folder donde esta el documento que fue cargado
                    // var folderUrl = "/backoffice/folders/subfolder.php?id=" + encodeURIComponent(item.id_folder) + "&key=" + encodeURIComponent(item.key_folder);
                    
                    // ################## PARA DESARROLLO EN MAC entorno local / ###############
                    // NUEVO - - - Lineas de código para abrir el archivo en un almacenamiento nuevo con la dirección URL de MAC entorno local / - CÓDIGO NUEVO
                    var documentUrl = "/backoffice/folders/extensions/open_pdf.php?folder=" + encodeURIComponent(item.key_folder) + "&file=" + encodeURIComponent(item.file_name_document);
                    
                    // Asignamos una URL para ingresar a los detalles de la carpeta o folder donde esta el documento que fue cargado
                    var folderUrl = "/backoffice/folders/subfolder.php?id=" + encodeURIComponent(item.id_folder) + "&key=" + encodeURIComponent(item.key_folder);
                    
                    // Crear elementos <li> para cada registro de documento y agregarlos a la lista desplegable
                    var listItem = `
                        <li style="display: flex; justify-content: space-between; align-items: center; text-align: justify;">
                            <span style="flex: 1;">
                            &#8226; El usuario <strong> ${item.name_user} </strong> ha agregado el documento 
                            <a href="${documentUrl}" target="_blank" style="text-decoration: none; color: inherit; font-weight: bold;">
                                ${item.file_name_document}
                            </a></strong> a la carpeta 
                            <a href="${folderUrl}" style="text-decoration: none; color: inherit; font-weight: bold;">
                                ${item.name_folder}
                            </a> el día ${formattedDate}
                            </span>
                            
                            <button 
                                class="delete-notification-documents-btn rounded-circle" 
                                style="width: 20px; height: 20px; display: flex; justify-content: center; align-items: center; padding: 0; border: none; position: relative; min-width: 20px; min-height: 20px; margin-left: 15px; background: none; outline: none;" 
                                data-id="${item.id_document}"
                            >
                                <i class="fas fa-check-circle" style="color: green; font-size: 20px;"></i>
                            </button>
                        </li>
                    `;
                    $documentList.append(listItem);
                });
                
                // Agregar evento al botón de eliminar o marcar como leido y ejecuta la función correspondiente
                $(".delete-notification-documents-btn").click(function() {
                    var notification_document = $(this).data("id");
                    // Mostrar el overlay y deshabilitar el botón de eliminar
                    $(this).prop("disabled", true);
                    $("#loadingOverlayNotify").show();
                    deleteDocumentNotify(notification_document, $(this));
                });
            }
        });
    }
    
    // Función para eliminar o marcar como leido un registro individual de una notificación de un documento
    function deleteDocumentNotify(notifyDocumentId, button) {
        $.ajax({
            type: "POST",
            // linea de código para el desarrollo en entorno interno
            // url: "/app/webservice.php",
            
            // linea de código para el despliegue en Compliance Hub
            // url: "/app/webservice.php",
            
            // linea de código para el desarrollo en MAC entorno local /
            url: "/app/webservice.php",
            
            data: {
                action: "deleteDocumentNotify",
                id_notify_document: notifyDocumentId
            }
        }).done(function(response) {
            try {
                var jsonResponse = JSON.parse(response);
                // Comprobar el status
                if (jsonResponse.status === "success") {
                    getNotifications(); // Actualizar el número total de notificaciones
                    getNotWachDocuments(); // Actualizar la lista de notificaciones no marcados como leidos
                    // Ocultar el overlay y habilitar el botón de eliminar
                    $("#loadingOverlayNotify").hide();
                    button.prop("disabled", false);
                } else if (jsonResponse.status === "error") {
                    console.error("Error al eliminar el registro de la notificación del documento.");
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
    
    // Función para limpiar las notificaciones de los documentos - responde al botón azul de LIMPIAR del contenedor
    function clearNotifications(documentIds) {
        // Mostrar el overlay y deshabilitar el botón de LIMPIAR para evitar multiples clics
        $("#loadingOverlayNotify").show();
        $("#clearNotifications").prop("disabled", true);
        $.ajax({
            type: "GET",
            // linea de código para el desarrollo en entorno interno
            // url: "/app/webservice.php",
            
            // linea de código para el despliegue en Compliance Hub
            // url: "/app/webservice.php",
            
            // linea de código para el desarrollo en MAC entorno local /
            url: "/app/webservice.php",
            
            data: {
                action: "clearNotifications",
                documentIds: documentIds.join(',')
            }
        }).done(function(response) {
            try {
                var parsedResponse = JSON.parse(response);
                if (parsedResponse.success) {
                    getNotifications(); // Actualizar el número total de notificaciones
                    getNotWachDocuments(); // Actualizar la lista de notificaciones no marcados como leidos
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
            $("#clearNotifications").prop("disabled", false);
        });
    }
    
    // Manejar el clic en el botón LIMPIAR
    $(".dropdown-header button").click(function() {
        // Ejecuta la función para limpiar el registro de las notificaciones
        clearNotifications(countNotify);
    });
    
    // Ejecutar las funciones inmediatamente
    getNotifications(); // Actualizar el número total de notificaciones
    getNotWachDocuments(); // Actualizar la lista de notificaciones no marcados como leidos
    
    // Configurar el intervalo para ejecutar las funciones cada 4 segundos
    setInterval(function() {
        getNotifications(); // Actualizar el número total de notificaciones
        getNotWachDocuments(); // Actualizar la lista de notificaciones no marcados como leidos
    }, 4000);
});