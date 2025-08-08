<?php
/**
 * API - Connexion utilisateur
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
    
    // Valider les données
    $validator = Validator::make($input, [
        'email' => 'required|email',
        'password' => 'required',
        'establishment_id' => 'integer'
    ]);
    
    if (!$validator->validate()) {
        http_response_code(400);
        echo json_encode(['error' => 'Données invalides', 'errors' => $validator->getErrors()]);
        exit;
    }
    
    $data = $validator->getValidatedData();
    
    // Authentifier
    $authService = new AuthService();
    $user = $authService->authenticate($data['email'], $data['password'], $data['establishment_id'] ?? null);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Identifiants incorrects']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
            'establishment_id' => $user['establishment_id'],
            'establishment_name' => $user['establishment_name']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Login error: " . $e->getMessage(), 'ERROR');
}