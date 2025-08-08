<?php
/**
 * API - Inscription utilisateur
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
        'first_name' => 'required|max:100',
        'last_name' => 'required|max:100',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8',
        'password_confirmation' => 'required|confirmed:password',
        'establishment_id' => 'required|integer',
        'terms_accepted' => 'required|boolean'
    ]);
    
    if (!$validator->validate()) {
        http_response_code(400);
        echo json_encode(['error' => 'Données invalides', 'errors' => $validator->getErrors()]);
        exit;
    }
    
    $data = $validator->getValidatedData();
    
    if (!$data['terms_accepted']) {
        http_response_code(400);
        echo json_encode(['error' => 'Vous devez accepter les conditions d\'utilisation']);
        exit;
    }
    
    // Créer l'utilisateur
    $authService = new AuthService();
    $user = $authService->createUser($data);
    
    echo json_encode([
        'success' => true,
        'message' => 'Compte créé avec succès',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
            'establishment_id' => $user['establishment_id']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Register error: " . $e->getMessage(), 'ERROR');
}