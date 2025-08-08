<?php
/**
 * API - Mettre à jour un utilisateur
 * PUT /api/users/{id}
 */

header('Content-Type: application/json');

// Vérifier authentification et permissions
Auth::requireAuth();
if (!Auth::hasRole('manager')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupérer l'ID utilisateur depuis l'URL ou les paramètres
    $userId = $_GET['id'] ?? $_POST['user_id'] ?? null;
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID utilisateur requis']);
        exit;
    }
    
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
    
    // Vérifier que l'utilisateur peut modifier cet utilisateur
    $authService = new AuthService();
    $targetUser = $authService->getUserById($userId);
    
    if (!$targetUser) {
        http_response_code(404);
        echo json_encode(['error' => 'Utilisateur non trouvé']);
        exit;
    }
    
    // Super admin peut modifier tous les utilisateurs
    // Admin/Manager ne peut modifier que les utilisateurs de son établissement
    if (!Auth::hasRole('super_admin') && $targetUser['establishment_id'] !== $currentUser['establishment_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Permissions insuffisantes']);
        exit;
    }
    
    // Préparer les données de mise à jour
    $updateData = [];
    
    if (isset($input['first_name'])) {
        $updateData['first_name'] = $input['first_name'];
    }
    
    if (isset($input['last_name'])) {
        $updateData['last_name'] = $input['last_name'];
    }
    
    if (isset($input['email'])) {
        $updateData['email'] = $input['email'];
    }
    
    if (isset($input['role'])) {
        $updateData['role'] = $input['role'];
    }
    
    if (isset($input['is_active'])) {
        $updateData['is_active'] = filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN);
    }
    
    if (!empty($input['password'])) {
        $updateData['password'] = Auth::hashPassword($input['password']);
    }
    
    // Mise à jour
    $updatedUser = $authService->updateUser($userId, $updateData);
    
    // Retourner l'utilisateur mis à jour sans le mot de passe
    unset($updatedUser['password']);
    
    echo json_encode([
        'success' => true,
        'data' => $updatedUser,
        'message' => 'Utilisateur mis à jour avec succès'
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
    Utils::log("API Update user error: " . $e->getMessage(), 'ERROR');
}