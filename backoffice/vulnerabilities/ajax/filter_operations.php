<?php
// backoffice/vulnerabilities/ajax/filter_operations.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/Connector.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'error' => 'Método inválido']); exit;
}

$year   = isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');
$month  = $_POST['month'] ?? 'all';
$status = $_POST['status'] ?? 'all';            // verde | amarillo | rojo | all
$tab    = $_POST['tipo_cliente'] ?? 'personas-fisicas'; // personas-fisicas | personas-morales | fideicomisos

// mapear pestaña UI -> enum DB
$mapTipo = [
  'personas-fisicas'  => 'persona_fisica',
  'personas-morales'  => 'persona_moral',
  'fideicomisos'      => 'fideicomiso'
];
$tipoCliente = $mapTipo[$tab] ?? 'persona_fisica';

// Rango de fechas
if ($month === 'all') {
  $start = sprintf('%d-01-01', $year);
  $end   = sprintf('%d-12-31', $year);
} else {
  $lastDay = cal_days_in_month(CAL_GREGORIAN, (int)$month, $year);
  $start   = sprintf('%d-%02d-01', $year, (int)$month);
  $end     = sprintf('%d-%02d-%02d', $year, (int)$month, $lastDay);
}

try {
  $db = new Connector();

  // CASE para el semáforo (rojo > amarillo > verde)
  $caseSemaforo = "
    CASE
      WHEN vo.umbral_superado = 1 THEN 'rojo'
      WHEN vo.requiere_aviso_sat = 1 THEN 'amarillo'
      ELSE 'verde'
    END
  ";

  // Nota: usamos la vista v_clientes_completos si existe; si no, cae a folders.name_folder
  $sql = "
    SELECT
      vo.id_operation                             AS id,
      c.name_company                               AS empresa,
      COALESCE(vc.nombre_completo, f.name_folder)  AS cliente,
      vo.fecha_operacion,
      vo.tipo_propiedad,
      vo.uso_inmueble,
      vo.direccion_inmueble,
      vo.codigo_postal,
      vo.folio_escritura,
      vo.propietario_anterior,
      {$caseSemaforo}                              AS semaforo
    FROM vulnerable_operations vo
    LEFT JOIN companies c          ON c.id_company = vo.id_company_operation
    LEFT JOIN folders   f          ON f.id_folder  = vo.id_client_operation
    LEFT JOIN v_clientes_completos vc ON vc.id_folder = vo.id_client_operation
    WHERE vo.status_operation = 1
      AND vo.tipo_cliente = ?
      AND vo.fecha_operacion BETWEEN ? AND ?
  ";

  $params = [$tipoCliente, $start, $end];

  // Filtro por semáforo (si no es "all")
  if ($status !== 'all') {
    $sql .= " AND ({$caseSemaforo}) = ? ";
    $params[] = $status;
  }

  // Orden sugerido
  $sql .= " ORDER BY vo.fecha_operacion DESC, vo.id_operation DESC ";

  $rows = $db->consult($sql, $params);

  // Normaliza para el front (por si algún campo viene null)
  $data = array_map(function($r){
    return [
      'id'                  => (int)$r['id'],
      'empresa'             => $r['empresa'] ?? '-',
      'cliente'             => $r['cliente'] ?? '-',
      'fecha_operacion'     => $r['fecha_operacion'],
      'tipo_propiedad'      => $r['tipo_propiedad'] ?? '-',
      'uso_inmueble'        => $r['uso_inmueble'] ?? '-',
      'direccion_inmueble'  => $r['direccion_inmueble'] ?? '-',
      'codigo_postal'       => $r['codigo_postal'] ?? '-',
      'folio_escritura'     => $r['folio_escritura'] ?? '-',
      'propietario_anterior'=> $r['propietario_anterior'] ?? '-',
      'semaforo'            => $r['semaforo'] ?? 'verde',

      // Flags opcionales que ya usa tu UI (si no los usas, ignóralos):
      'empresa_missing_info'=> 0,
      'cliente_missing_info'=> 0,
    ];
  }, $rows ?? []);

  echo json_encode(['success' => true, 'data' => $data]);

} catch (Throwable $e) {
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
