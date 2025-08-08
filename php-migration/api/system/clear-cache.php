<?php
/**
 * API - Vider le cache système
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

if (!Auth::hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

try {
    // Vérifier CSRF
    $input = json_decode(file_get_contents('php://input'), true);
    if (!validateCSRFToken($input['_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF invalide']);
        exit;
    }
    
    // Vider le cache
    Utils::clearCache();
    
    // Logger l'action
    Utils::log("Cache cleared by admin: " . Auth::user()['email'], 'INFO');
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache vidé avec succès'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Clear cache error: " . $e->getMessage(), 'ERROR');
}