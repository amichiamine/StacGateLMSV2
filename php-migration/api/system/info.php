<?php
/**
 * API System - Informations système
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../core/Auth.php';
require_once '../../core/Utils.php';
require_once '../../core/services/SystemService.php';

// Authentification requise
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

// Vérifier permissions admin
if (!Auth::hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $systemService = new SystemService();
        $systemInfo = $systemService->getSystemInfo();
        echo json_encode($systemInfo);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
    }
    
} catch (Exception $e) {
    Utils::log("System info API error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>