<?php
// =====================================================================
// ARCHIVO ACTUALIZADO: backoffice/vulnerabilities/get_clients_ajax.php
// =====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// COPIAR EL MISMO PATRÓN DE INCLUSIÓN QUE USA vulnerabilities.php
try {
    include "../../app/config.php";
    include "../../app/WebController.php";
    include "../../app/ExcelController.php";
    
    if (file_exists('../../vendor/autoload.php')) {
        require '../../vendor/autoload.php';
    }
    
    if (file_exists('../../app/OperationController.php')) {
        require_once '../../app/OperationController.php';
    }
    
    $controller = new WebController();
    
} catch (Exception $e) {
    die(json_encode(['error' => 'Error al cargar archivos: ' . $e->getMessage()]));
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    
    // =====================================================================
    // NUEVA FUNCIÓN: Obtener empresas clientes
    // =====================================================================
    if ($_GET['action'] === 'get_client_companies') {
        try {
            // Verificar si el método existe
            if (!method_exists($controller, 'getClientCompaniesForSelect')) {
                throw new Exception('Método getClientCompaniesForSelect no existe. Necesitas agregarlo al WebController.php');
            }
            
            $companies = $controller->getClientCompaniesForSelect(1); // status = 1 (activas)
            
            echo json_encode([
                'success' => true,
                'companies' => $companies,
                'debug_info' => [
                    'total_companies' => count($companies)
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    // =====================================================================
    // NUEVA FUNCIÓN: Obtener clientes solo por empresa (todos los tipos)
    // =====================================================================
    elseif ($_GET['action'] === 'get_clients_by_company') {
        $empresaId = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : null;
        
        try {
            // Verificar si el método existe
            if (!method_exists($controller, 'getClientsByCompany')) {
                throw new Exception('Método getClientsByCompany no existe. Necesitas agregarlo al WebController.php');
            }
            
            $clients = $controller->getClientsByCompany($empresaId);
            
            // Formatear respuesta para el select
            $options = [];
            foreach ($clients as $client) {
                $nombre = $client['nombre_completo'] ?? 'Sin nombre';
                $rfc = $client['rfc_folder'] ?? 'Sin RFC';
                
                $options[] = [
                    'id' => $client['id_folder'],
                    'text' => $nombre . ' - ' . $rfc,
                    'data' => $client
                ];
            }
            
            echo json_encode([
                'success' => true,
                'clients' => $options,
                'debug_info' => [
                    'empresa_id' => $empresaId,
                    'total_clients' => count($clients)
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    // =====================================================================
    // NUEVA FUNCIÓN: Obtener clientes por empresa y tipo
    // =====================================================================
    elseif ($_GET['action'] === 'get_clients_by_company_and_type') {
        $empresaId = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : null;
        $tipoPersona = isset($_GET['tipo_persona']) ? $_GET['tipo_persona'] : null;
        
        try {
            // Verificar si el método existe
            if (!method_exists($controller, 'getClientsByCompanyAndType')) {
                throw new Exception('Método getClientsByCompanyAndType no existe. Necesitas agregarlo al WebController.php');
            }
            
            $clients = $controller->getClientsByCompanyAndType($empresaId, $tipoPersona);
            
            // Formatear respuesta para el select
            $options = [];
            foreach ($clients as $client) {
                $nombre = $client['nombre_completo'] ?? 'Sin nombre';
                $rfc = $client['rfc_folder'] ?? 'Sin RFC';
                
                $options[] = [
                    'id' => $client['id_folder'],
                    'text' => $nombre . ' - ' . $rfc,
                    'data' => $client
                ];
            }
            
            echo json_encode([
                'success' => true,
                'clients' => $options,
                'debug_info' => [
                    'empresa_id' => $empresaId,
                    'tipo_persona' => $tipoPersona,
                    'total_clients' => count($clients)
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    // =====================================================================
    // NUEVA FUNCIÓN: Obtener TODOS los clientes sin filtros
    // =====================================================================
    elseif ($_GET['action'] === 'get_all_clients') {
        try {
            // Verificar si el método existe
            if (!method_exists($controller, 'getAllClients')) {
                throw new Exception('Método getAllClients no existe. Necesitas agregarlo al WebController.php');
            }
            
            $clients = $controller->getAllClients();
            
            // Formatear respuesta para el select
            $options = [];
            foreach ($clients as $client) {
                $nombre = $client['nombre_completo'] ?? 'Sin nombre';
                $rfc = $client['rfc_folder'] ?? 'Sin RFC';
                $tipoPersona = '';
                
                // Agregar indicador del tipo de persona
                switch($client['tipo_persona']) {
                    case 'fisica':
                        $tipoPersona = ' [PF]';
                        break;
                    case 'moral':
                        $tipoPersona = ' [PM]';
                        break;
                    case 'fideicomiso':
                        $tipoPersona = ' [FID]';
                        break;
                }
                
                $options[] = [
                    'id' => $client['id_folder'],
                    'text' => $nombre . ' - ' . $rfc . $tipoPersona,
                    'data' => $client
                ];
            }
            
            echo json_encode([
                'success' => true,
                'clients' => $options,
                'debug_info' => [
                    'total_clients' => count($clients)
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    // =====================================================================
    // FUNCIÓN EXISTENTE: Obtener clientes por tipo (MANTENER PARA COMPATIBILIDAD)
    // =====================================================================
    elseif ($_GET['action'] === 'get_clients_by_type') {
        $tipoPersona = isset($_GET['tipo_persona']) ? $_GET['tipo_persona'] : null;
        $companyId = null;
        
        // Si el usuario es de empresa, solo puede ver clientes de su empresa
        if ($_SESSION['user']['id_type_user'] == 3 && isset($_SESSION['user']['id_company']) && $_SESSION['user']['id_company']) {
            $companyId = $_SESSION['user']['id_company'];
        }
        
        try {
            // Verificar si el método existe
            if (!method_exists($controller, 'getClientsByType')) {
                throw new Exception('Método getClientsByType no existe. Necesitas agregarlo al WebController.php');
            }
            
            $clients = $controller->getClientsByType($tipoPersona, $companyId);
            
            // Formatear respuesta para el select
            $options = [];
            foreach ($clients as $client) {
                $nombre = $client['nombre_completo'] ?? 'Sin nombre';
                $rfc = $client['rfc_folder'] ?? 'Sin RFC';
                
                $options[] = [
                    'id' => $client['id_folder'],
                    'text' => $nombre . ' - ' . $rfc,
                    'data' => $client
                ];
            }
            
            echo json_encode([
                'success' => true,
                'clients' => $options,
                'debug_info' => [
                    'tipo_persona' => $tipoPersona,
                    'company_id' => $companyId,
                    'user_type' => $_SESSION['user']['id_type_user'],
                    'total_clients' => count($clients)
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    // =====================================================================
    // FUNCIÓN EXISTENTE: Obtener detalles de cliente (MANTENER)
    // =====================================================================
    elseif ($_GET['action'] === 'get_client_details' && isset($_GET['id'])) {
        
        try {
            if (!method_exists($controller, 'getClientById')) {
                throw new Exception('Método getClientById no existe. Necesitas agregarlo al WebController.php');
            }
            
            $client = $controller->getClientById($_GET['id']);
            
            if ($client) {
                echo json_encode([
                    'success' => true,
                    'client' => $client
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Cliente no encontrado'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>