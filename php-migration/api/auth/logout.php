<?php
/**
 * API - Déconnexion utilisateur
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
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
    
    Auth::logout();
    
    echo json_encode([
        'success' => true,
        'message' => 'Déconnexion réussie'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Logout error: " . $e->getMessage(), 'ERROR');
}