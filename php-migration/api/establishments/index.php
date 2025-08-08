<?php
/**
 * API - Liste des établissements
 */

header('Content-Type: application/json');

try {
    $activeOnly = isset($_GET['active_only']) ? filter_var($_GET['active_only'], FILTER_VALIDATE_BOOLEAN) : true;
    
    $establishmentService = new EstablishmentService();
    $establishments = $establishmentService->getAllEstablishments($activeOnly);
    
    echo json_encode([
        'success' => true,
        'data' => $establishments
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Establishments list error: " . $e->getMessage(), 'ERROR');
}