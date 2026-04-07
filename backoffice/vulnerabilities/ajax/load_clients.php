<?php
// =====================================================================
// CREAR ARCHIVO: backoffice/vulnerabilities/ajax/load_clients.php
// =====================================================================

session_start();
require_once __DIR__ . '/../../../app/Connector.php';  // Usamos Connector directamente

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['company_id'])) {
    try {
        $connector = new Connector();
        $companyId = intval($_POST['company_id']);

        // Consulta para obtener clientes de la empresa seleccionada
        $sql = "
            SELECT 
                f.id_folder,
                f.name_folder,
                f.rfc_folder,
                f.curp_folder,
                c.tipo_persona
            FROM folders f
            LEFT JOIN companies c ON f.company_id = c.id_company
            WHERE f.company_id = :company_id
              AND f.status_folder = 1
              AND f.fk_folder = 0
            ORDER BY f.name_folder
        ";

        // Uso de consult() para ejecutar la consulta y traer el arreglo de resultados
        $clientes = $connector->consult($sql, [':company_id' => $companyId]);

        echo json_encode([
            'success'  => true,
            'clientes' => $clientes
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error'   => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'Datos insuficientes'
    ]);
}
?>