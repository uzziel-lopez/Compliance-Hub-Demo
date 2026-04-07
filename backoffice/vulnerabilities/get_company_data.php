<?php
// ===================================================================
// ENDPOINT PARA OBTENER DATOS COMPLETOS DE EMPRESA
// Archivo: backoffice/vulnerabilities/get_company_data.php
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
if (!isset($_POST['action']) || $_POST['action'] !== 'getCompanyData') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    exit();
}

// Verificar que se envíe el ID de la empresa
if (!isset($_POST['company_id']) || empty($_POST['company_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de empresa requerido']);
    exit();
}

try {
    // Incluir los archivos necesarios
    require_once '../../app/config.php';
    require_once '../../app/WebController.php';
    
    // Instanciar el controlador
    $controller = new WebController();
    
    // Obtener el ID de la empresa
    $companyId = intval($_POST['company_id']);
    
    // Consultar los datos de la empresa
    $empresaData = $controller->getCompanyById($companyId);
    
    if ($empresaData) {
        // Formatear los datos para enviar al frontend
        $response = [
            'success' => true,
            'data' => [
                // Información General
                'id_company' => $empresaData['id_company'],
                'name_company' => $empresaData['name_company'],
                'rfc_company' => $empresaData['rfc_company'],
                'razon_social' => $empresaData['razon_social'],
                'tipo_persona' => $empresaData['tipo_persona'],
                'fecha_constitucion' => $empresaData['fecha_constitucion'],
                
                // Contacto
                'telefono' => $empresaData['telefono'],
                'email' => $empresaData['email'],
                
                // Ubicación
                'estado' => $empresaData['estado'],
                'ciudad' => $empresaData['ciudad'],
                'colonia' => $empresaData['colonia'],
                'calle' => $empresaData['calle'],
                'num_exterior' => $empresaData['num_exterior'],
                'num_interior' => $empresaData['num_interior'],
                'codigo_postal' => $empresaData['codigo_postal'],
                
                // Representante Legal
                'apoderado_nombre' => $empresaData['apoderado_nombre'],
                'apoderado_apellido_paterno' => $empresaData['apoderado_apellido_paterno'],
                'apoderado_apellido_materno' => $empresaData['apoderado_apellido_materno'],
                'apoderado_rfc' => $empresaData['apoderado_rfc'],
                'apoderado_curp' => $empresaData['apoderado_curp']
            ]
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Empresa no encontrada']);
    }
    
} catch (Exception $e) {
    error_log('Error en get_company_data.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}

exit();
?>