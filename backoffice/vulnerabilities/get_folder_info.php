<?php
// ===================================================================
// ENDPOINT PARA OBTENER INFORMACIÓN DE CARPETAS DE OPERACIONES
// Archivo: backoffice/vulnerabilities/get_folder_info.php
// ===================================================================

session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Verificar que se envíe la acción correcta
if (!isset($_POST['action']) || $_POST['action'] !== 'getFolderInfo') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    exit();
}

// Verificar que se envíen los parámetros requeridos
if (!isset($_POST['operation_id']) || !isset($_POST['campo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetros requeridos faltantes']);
    exit();
}

try {
    // Incluir los archivos necesarios
    require_once '../../app/config.php';
    require_once '../../app/Connector.php';
    
    // Obtener parámetros
    $operationId = intval($_POST['operation_id']);
    $campo = $_POST['campo']; // 'empresa' o 'cliente'
    
    // Instanciar conexión
    $connector = new Connector();
    $connection = $connector->connection();
    
    if ($campo === 'empresa') {
        // Obtener información de la carpeta de la empresa
        $sql = "SELECT 
            vo.id_company_operation,
            c.name_company,
            f_empresa.id_folder as empresa_folder_id,
            f_empresa.key_folder as empresa_key
        FROM vulnerable_operations vo
        LEFT JOIN companies c ON vo.id_company_operation = c.id_company
        LEFT JOIN folders f_empresa ON c.id_company = f_empresa.company_id AND f_empresa.fk_folder = 0
        WHERE vo.id_operation = ? AND vo.status_operation = 1";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$operationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'folder_info' => [
                    'empresa_folder_id' => $result['empresa_folder_id'],
                    'empresa_key' => $result['empresa_key'],
                    'empresa_name' => $result['name_company']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró información de la empresa']);
        }
        
    } else if ($campo === 'cliente') {
        // Obtener información de la carpeta del cliente
        $sql = "SELECT 
            vo.id_client_operation,
            f_cliente.id_folder as cliente_folder_id,
            f_cliente.key_folder as cliente_key,
            f_cliente.name_folder as cliente_name,
            f_cliente.fk_folder,
            f_empresa.id_folder as empresa_folder_id,
            f_empresa.key_folder as empresa_key
        FROM vulnerable_operations vo
        LEFT JOIN folders f_cliente ON vo.id_client_operation = f_cliente.id_folder
        LEFT JOIN folders f_empresa ON f_cliente.fk_folder = f_empresa.id_folder
        WHERE vo.id_operation = ? AND vo.status_operation = 1";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$operationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'folder_info' => [
                    'cliente_folder_id' => $result['cliente_folder_id'],
                    'cliente_key' => $result['cliente_key'],
                    'cliente_name' => $result['cliente_name'],
                    'empresa_folder_id' => $result['empresa_folder_id'],
                    'empresa_key' => $result['empresa_key']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró información del cliente']);
        }
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Campo no válido']);
    }
    
} catch (Exception $e) {
    error_log('Error en get_folder_info.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}

exit();
?>