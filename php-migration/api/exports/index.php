<?php
/**
 * API Exports - Gestion exports et sauvegardes
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/Utils.php';
require_once '../../core/services/ExportService.php';

// Authentification requise
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Vérifier permissions
if (!Auth::hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$exportService = new ExportService();

try {
    switch ($method) {
        case 'GET':
            // Liste des exports avec statuts
            $page = intval($_GET['page'] ?? 1);
            $perPage = intval($_GET['per_page'] ?? 20);
            $status = $_GET['status'] ?? '';
            
            $filters = array_filter(['status' => $status]);
            $result = $exportService->getExportsByEstablishment($establishmentId, $page, $perPage, $filters);
            echo json_encode($result);
            break;
            
        case 'POST':
            // Créer un nouvel export
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Vérifier CSRF
            if (!verifyCSRFToken($input['_token'] ?? '')) {
                http_response_code(422);
                echo json_encode(['error' => 'Token CSRF invalide']);
                exit;
            }
            
            $type = $input['type'] ?? 'custom';
            $format = $input['format'] ?? 'csv';
            $tables = $input['tables'] ?? [];
            $filters = $input['filters'] ?? [];
            
            $export = $exportService->createExport($establishmentId, $currentUser['id'], $type, $format, $tables, $filters);
            http_response_code(201);
            echo json_encode($export);
            break;
            
        case 'DELETE':
            // Supprimer un export
            $input = json_decode(file_get_contents('php://input'), true);
            $exportId = $input['id'] ?? null;
            
            if (!$exportId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID export manquant']);
                exit;
            }
            
            $success = $exportService->deleteExport($exportId);
            echo json_encode(['success' => $success]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    Utils::log("Exports API error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>