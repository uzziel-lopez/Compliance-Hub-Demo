<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/Connector.php';
require_once __DIR__ . '/../../../app/OperationController.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_operation'])) {
    throw new Exception('Parámetros insuficientes.');
  }

  $oc = new OperationController();
  $data = $oc->getOperationDetail((int)$_POST['id_operation']);

  echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
