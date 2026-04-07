<?php
include "config.php";
include "debug.php";
include "WebController.php";
include "MailController.php";
$controller = new WebController();
$mailController = new MailController();

if (!empty($_GET['action'])) {
  switch ($_GET['action']) {

    case 'getUsers':
      $status = !empty($_GET['status']) ? $_GET['status'] : null;
      $response = ws_getUsers(
        $status
      );
      break;

    case 'getdocument':
      $idDocument = !empty($_GET['idDocument']) ? $_GET['idDocument'] : null;
      $response = ws_getdocument(
        $idDocument
      );
      break;

    case 'getAllDocuments':
      $fecha1 = !empty($_GET['fecha1']) ? $_GET['fecha1'] : null;
      $fecha2 = !empty($_GET['fecha2']) ? $_GET['fecha2'] : null;
      $response = ws_getAllDocuments(
        $fecha1,
        $fecha2
      );
      break;

    case 'getNotifications':
      global $controller;
      $data = $controller->ws_getIdNotifications();
      echo json_encode($data);
      return $data;
      break;

    case 'getNotWachDocuments':
      global $controller;
      $data = $controller->ws_getNotWachDocuments();
      echo json_encode($data);
      return $data;
      break;

    case 'getFolderDetail':
      $folderId = !empty($_GET['idFolder']) ? $_GET['idFolder'] : null;
      $response = ws_getFolderDetail(
        $folderId
      );
      break;

    // ESTA ES OTRA FORMA DE HACERLO - ES FUNCIONAL ESTE CÓDIGO
    /*
    case 'clearNotifications':
      global $controller;
      $newDocumentIds = !empty($_GET['documentIds']) ? $_GET['documentIds'] : '';
      $response = $controller->ws_clearNotifications($newDocumentIds);
      echo json_encode($response);
      return $response;
    break;
    */

    case 'clearNotifications':
      $newDocumentIds = !empty($_GET['documentIds']) ? $_GET['documentIds'] : '';
      $response = ws_clearNotifications(
        $newDocumentIds
      );
      break;

    case 'getFoldersAll':
      $fecha1 = !empty($_GET['fecha1']) ? $_GET['fecha1'] : null;
      $fecha2 = !empty($_GET['fecha2']) ? $_GET['fecha2'] : null;
      $status = !empty($_GET['status']) ? $_GET['status'] : null;
      $response = ws_getFoldersAll(
        $fecha1,
        $fecha2,
        $status
      );
      break;

    // Este caso maneja la solicitud 'idx_getFolders' que se solicita desde el INDEX
    case 'idx_getFolders':
      // Obtiene el parámetro 'fecha1' de la solicitud GET, si está presente; de lo contrario, asigna null
      $fecha1 = !empty($_GET['fecha1']) ? $_GET['fecha1'] : null;
      // Obtiene el parámetro 'fecha2' de la solicitud GET, si está presente; de lo contrario, asigna null
      $fecha2 = !empty($_GET['fecha2']) ? $_GET['fecha2'] : null;
      // Obtiene el parámetro 'status' de la solicitud GET, si está presente; de lo contrario, asigna null
      $status = !empty($_GET['status']) ? $_GET['status'] : null;
      // Obtiene el parámetro 'customer' de la solicitud GET, si está presente; de lo contrario, asigna null
      $customer = !empty($_GET['customer']) ? $_GET['customer'] : null;
      // Llama a la función ws_idxGetFolders con los parámetros obtenidos y guarda la respuesta
      $response = ws_idxGetFolders(
        $fecha1,
        $fecha2,
        $status,
        $customer
      );
      break;

    case 'getNoticationsTracings':
      global $controller;
      $data = $controller->ws_getNoticationsTracings();
      echo json_encode($data);
      return $data;
      break;

    case 'getNotWachTracings':
      global $controller;
      $data = $controller->ws_getNotWachTracings();
      echo json_encode($data);
      return $data;
      break;

    case 'clearTracingsNotify':
      $idNotifiesTracings = !empty($_GET['idNotify']) ? $_GET['idNotify'] : '';
      $response = ws_clearTracingsNotify(
        $idNotifiesTracings
      );
      break;

    case 'getTracingsFolderUser':
      $userId = !empty($_GET['userId']) ? $_GET['userId'] : null;
      $idFolder = !empty($_GET['idFolder']) ? $_GET['idFolder'] : null;
      $response = ws_getTracingsFolderUser(
        $userId,
        $idFolder
      );
      break;

    case 'loadDataTracingsFolderUser':
      $userId = !empty($_GET['userId']) ? $_GET['userId'] : null;
      $idFolder = !empty($_GET['idFolder']) ? $_GET['idFolder'] : null;
      $response = ws_loadDataTracingsFolderUser(
        $userId,
        $idFolder
      );
      break;

    case 'ws_clearTracingsNotifyFolder':
      $idNotifiesTracings = !empty($_GET['notifyIds']) ? $_GET['notifyIds'] : '';
      $response = ws_clearTracingsNotifyFolder(
        $idNotifiesTracings
      );
      break;

    case 'getTracingDetail':
      $idTracing = !empty($_GET['idTracing']) ? $_GET['idTracing'] : null;
      $keyTracing = !empty($_GET['keyTracing']) ? $_GET['keyTracing'] : null;
      $response = ws_getTracingDetail(
        $idTracing,
        $keyTracing
      );
      break;

    case 'getSectionDetail':
      $sectionId = !empty($_GET['idSection']) ? $_GET['idSection'] : null;
      $response = ws_getSectionDetail(
        $sectionId
      );
      break;

    case 'getFolderNotifications':
      global $controller;
      $data = $controller->ws_getFolderNotifications();
      echo json_encode($data);
      return $data;
      break;

    case 'getNotWatchFolderNotifications':
      global $controller;
      $data = $controller->ws_getNotWatchFolderNotifications();
      echo json_encode($data);
      return $data;
      break;

    case 'clearFolderNotifications':
      $idsNotifyFolderNotifications = !empty($_GET['idNotify']) ? $_GET['idNotify'] : '';
      $response = ws_clearFolderNotifications(
        $idsNotifyFolderNotifications
      );
      break;

    case 'getOperationDetail':
      $operationId = !empty($_GET['operationId']) ? $_GET['operationId'] : null;
      $response = ws_getOperationDetail($operationId);
      break;


  }
} else if (!empty($_POST['action'])) {
  switch ($_POST['action']) {

    case 'sendNoticeCustomers':
      if (!empty($_POST['seleccionados'])) {
        $userIds = $_POST['seleccionados'];
        $emails = [];
        foreach ($userIds as $id) {
          $user = $controller->getUser($id);
          if (!empty($user['email_user'])) {
            $emails[] = $user['email_user'];
          }
        }

        if (!empty($emails)) {
          $dataUser = $controller->getDetailUser($_POST['id_user_tracing'], $_POST['key_user']);
          $dataFolder = $controller->getDetailFolder($_POST['id_folder_tracing'], $_POST['key_folder_tracing'], 1);

          if (isset($dataUser, $dataFolder) && !empty($dataUser) && !empty($dataFolder)) {
            $sendNotification = filter_var($_POST['send_notification'], FILTER_VALIDATE_BOOLEAN);
            if ($sendNotification) {
              $mailSent = $mailController->sendNoticeCustomers($_POST, $emails, $dataUser, $dataFolder);
              if ($mailSent === true) {
                $createTracing = $controller->createTracing($_POST);
                if ($createTracing) {
                  foreach ($userIds as $id_user) {
                    $createTracingNotify = $controller->createTracingNotify($createTracing, $id_user, $_POST['id_folder_tracing']);
                  }

                  if ($createTracingNotify) {
                    echo json_encode(['status' => 'success', 'emails' => $emails]);
                  } else {
                    echo json_encode(['status' => 'error', 'message' => 'ERROR AL CREAR LAS NOTIFICACIONES.']);
                  }
                } else {
                  echo json_encode(['status' => 'error', 'message' => 'ERROR AL REGISTRAR EL SEGUIMIENTO.']);
                }
              } else {
                echo json_encode(['status' => 'error', 'message' => 'ERROR AL ENVIAR LA NOTIFICACIÓN.']);
              }
            } else {
              $tracingCreate = $controller->createTracing($_POST);
              if ($tracingCreate) {
                foreach ($userIds as $id_user) {
                  $createTracingNotify = $controller->createTracingNotify($tracingCreate, $id_user, $_POST['id_folder_tracing']);
                }
                if ($createTracingNotify) {
                  echo json_encode(['status' => 'success', 'emails' => $emails]);
                } else {
                  echo json_encode(['status' => 'error', 'message' => 'ERROR AL CREAR LAS NOTIFICACIONES.']);
                }
              } else {
                echo json_encode(['status' => 'error', 'message' => 'ERROR AL REGISTRAR EL SEGUIMIENTO.']);
              }
            }
          } else {
            echo json_encode(['status' => 'error', 'message' => 'ERROR: DATOS INSUFICIENTES.']);
          }
        } else {
          echo json_encode(['status' => 'error', 'message' => 'NO SE ENCONTRARON CORREOS VÁLIDOS.']);
        }
      } else {
        echo json_encode(['status' => 'error', 'message' => 'NO SE RECIBIERON USUARIOS SELECCIONADOS.']);
      }
      break;

    // CASE PARA CARGAR DE 5 EN 5 LOS SEGUIMIENTOS DE UN FOLDER O CARPETA - SE EJECUTA AL HACER SCROLL
    case 'loadMoreTracings':
      $folderId = $_POST['folder_id'];
      $offset = (int) $_POST['offset'];
      $limit = (int) $_POST['limit'];

      $tracings = $controller->getTracingsFolder(
        $folderId,
        $limit,
        $offset
      );
      // Verificar si hay más registros
      $totalRecords = $controller->countTracings($folderId); // Método que cuenta todos los registros
      $hasMore = ($offset + count($tracings)) < $totalRecords;
      echo json_encode(['tracings' => $tracings, 'hasMore' => $hasMore]);
      break;

    case 'updateDataTracing':
      if (!empty($_POST['dataTracing'])) {
        $updateSuccess = $controller->updateDataTracing($_POST['dataTracing']);
        if ($updateSuccess) {
          echo json_encode(['status' => 'success']);
        } else {
          echo json_encode(['status' => 'error']);
        }
      } else {
        echo json_encode(['status' => 'error', 'message' => 'DATOS NO PROPORCIONADOS']);
      }
      break;

    case "updateDocumentOrder":
      if (!empty($_POST['order']) && is_array($_POST['order'])) {
        echo $controller->updatePositionDocumentsSections([
          'order' => $_POST['order']
        ]);
      } else {
        echo json_encode(['success' => false, 'error' => 'Orden no válido']);
      }
      break;

    case 'deleteFolderNotification':
      $id_notify_folder = !empty($_POST['id_notify_folder']) ? $_POST['id_notify_folder'] : null;
      $data = $controller->ws_deleteFolderNotification(
        $id_notify_folder
      );

      if ($data) {
        echo json_encode(['status' => 'success']);
      } else {
        echo json_encode(['status' => 'error']);
      }
      break;

    case 'deleteTracingNotify':
      $notifyIdTracing = !empty($_POST['id_notify_tracing']) ? $_POST['id_notify_tracing'] : null;
      $data = $controller->ws_deleteTracingNotify(
        $notifyIdTracing
      );

      if ($data) {
        echo json_encode(['status' => 'success']);
      } else {
        echo json_encode(['status' => 'error']);
      }
      break;

    case 'deleteDocumentNotify':
      $notifyIdDocument = !empty($_POST['id_notify_document']) ? $_POST['id_notify_document'] : null;
      $data = $controller->ws_deleteDocumentNotify(
        $notifyIdDocument
      );

      if ($data) {
        echo json_encode(['status' => 'success']);
      } else {
        echo json_encode(['status' => 'error']);
      }
      break;


  }
}

function ws_getUsers($status)
{
  global $controller;
  $data = $controller->getUsers($status);
  echo json_encode($data);
  return $data;
}

function ws_getdocument($idDocument)
{
  global $controller;
  $data = $controller->getDetailDocument($idDocument);
  echo json_encode($data);
  return $data;
}

function ws_getAllDocuments($fecha1, $fecha2)
{
  global $controller;
  $data = $controller->ws_getAllDocuments($fecha1, $fecha2);
  echo json_encode($data);
  return $data;
}

function ws_getFolderDetail($folderId)
{
  global $controller;
  $data = $controller->getFolderDetail($folderId, 1);
  echo json_encode($data);
  return $data;
}

function ws_clearNotifications($newDocumentIds)
{
  global $controller;
  $data = $controller->ws_clearNotifications($newDocumentIds);
  echo json_encode($data);
  return $data;
}

function ws_getFoldersAll($fecha1, $fecha2, $status)
{
  global $controller;
  $data = $controller->ws_getFoldersAll($fecha1, $fecha2, $status);
  echo json_encode($data);
  return $data;
}

function ws_getOperationDetail($operationId)
{
  global $controller;
  try {
    // Usar el OperationController para obtener los datos
    require_once 'OperationController.php';
    $operationController = new OperationController();
    $data = $operationController->getOperationDetail($operationId);

    echo json_encode([
      'success' => true,
      'data' => $data
    ]);
    return $data;
  } catch (Exception $e) {
    echo json_encode([
      'success' => false,
      'error' => 'Error al obtener la operación: ' . $e->getMessage()
    ]);
    return false;
  }
}

/**
 * * Función para obtener carpetas en un rango de fechas y con un estado específico
 * *
 * * @param string $fecha1 - Fecha de inicio del rango (en formato YYYY-MM-DD)
 * * @param string $fecha2 - Fecha de fin del rango (en formato YYYY-MM-DD)
 * * @param string $status - Estado de las carpetas a filtrar
 * * @return array - Datos de las carpetas en formato JSON
 */
function ws_idxGetFolders($fecha1, $fecha2, $status, $customer)
{
  // Acceder al objeto controlador global
  global $controller;
  // Llamar al método ws_idxGetFolders del controlador, pasando las fechas, el estatus y el ejecutivo de ventas asociado
  // Este método realiza la consulta en la base de datos y devolver los resultados
  $data = $controller->ws_idxGetFolders($fecha1, $fecha2, $status, $customer);
  // Convertir los datos obtenidos en formato JSON para facilitar su uso en el frontend
  echo json_encode($data);
  // Devolver los datos obtenidos (esto es opcional si solo se necesita la salida en JSON)
  return $data;
}

function ws_clearTracingsNotify($id_notify)
{
  global $controller;

  if (!is_array($id_notify)) {
    $id_notify = json_decode($id_notify, true);
  }

  $data = $controller->ws_clearTracingsNotify($id_notify);
  echo json_encode($data);
  return $data;
}

function ws_getTracingsFolderUser($userId, $idFolder)
{
  global $controller;
  $data = $controller->ws_getTracingsFolderUser($userId, $idFolder);
  echo json_encode($data);
  return $data;
}

function ws_loadDataTracingsFolderUser($userId, $idFolder)
{
  global $controller;
  $data = $controller->ws_loadDataTracingsFolderUser($userId, $idFolder);
  echo json_encode($data);
  return $data;
}

function ws_clearTracingsNotifyFolder($id_notify)
{
  global $controller;

  if (!is_array($id_notify)) {
    $id_notify = json_decode($id_notify, true);
  }

  $data = $controller->ws_clearTracingsNotifyFolder($id_notify);
  echo json_encode($data);
  return $data;
}

function ws_getTracingDetail($idTracing, $keyTracing)
{
  global $controller;
  $data = $controller->ws_getTracingDetail($idTracing, $keyTracing, 1);
  echo json_encode($data);
  return $data;
}

function ws_getSectionDetail($sectionId)
{
  global $controller;
  $data = $controller->getSectionDetail($sectionId, 1);
  echo json_encode($data);
  return $data;
}

function ws_clearFolderNotifications($id_notify)
{
  global $controller;

  if (!is_array($id_notify)) {
    $id_notify = json_decode($id_notify, true);
  }

  $data = $controller->ws_clearFolderNotifications($id_notify);
  echo json_encode($data);
  return $data;
}
?>