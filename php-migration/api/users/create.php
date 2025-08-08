<?php
/**
 * API - Créer un utilisateur
 * POST /api/users
 */

header('Content-Type: application/json');

// Vérifier authentification et permissions
Auth::requireAuth();
if (!Auth::hasRole('manager')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

try {
    // Validation CSRF
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    if (!verifyCSRFToken($input['_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF invalide']);
        exit;
    }
    
    $currentUser = Auth::user();
    $establishmentId = Auth::hasRole('super_admin') ? ($input['establishment_id'] ?? null) : $currentUser['establishment_id'];
    
    // Données utilisateur
    $userData = [
        'first_name' => $input['first_name'] ?? '',
        'last_name' => $input['last_name'] ?? '',
        'email' => $input['email'] ?? '',
        'password' => $input['password'] ?? '',
        'role' => $input['role'] ?? 'apprenant',
        'establishment_id' => $establishmentId,
        'is_active' => true
    ];
    
    $authService = new AuthService();
    $newUser = $authService->createUser($userData);
    
    // Retourner l'utilisateur créé sans le mot de passe
    unset($newUser['password']);
    
    echo json_encode([
        'success' => true,
        'data' => $newUser,
        'message' => 'Utilisateur créé avec succès'
    ]);

} catch (ValidationException $e) {
    http_response_code(422);
    echo json_encode([
        'error' => 'Données invalides',
        'validation_errors' => $e->getErrors()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Create user error: " . $e->getMessage(), 'ERROR');
}