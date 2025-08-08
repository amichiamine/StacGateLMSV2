<?php
/**
 * API - Supprimer un utilisateur
 * DELETE /api/users/{id}
 */

header('Content-Type: application/json');

// Vérifier authentification et permissions
Auth::requireAuth();
if (!Auth::hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    
    // Vérifier que l'utilisateur peut supprimer cet utilisateur
    $authService = new AuthService();
    $targetUser = $authService->getUserById($userId);
    
    if (!$targetUser) {
        http_response_code(404);
        echo json_encode(['error' => 'Utilisateur non trouvé']);
        exit;
    }
    
    // Ne pas pouvoir se supprimer soi-même
    if ($targetUser['id'] == $currentUser['id']) {
        http_response_code(400);
        echo json_encode(['error' => 'Impossible de supprimer votre propre compte']);
        exit;
    }
    
    // Super admin peut supprimer tous les utilisateurs
    // Admin ne peut supprimer que les utilisateurs de son établissement
    if (!Auth::hasRole('super_admin') && $targetUser['establishment_id'] !== $currentUser['establishment_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Permissions insuffisantes']);
        exit;
    }
    
    // Supprimer l'utilisateur
    $authService->deleteUser($userId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Utilisateur supprimé avec succès'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Delete user error: " . $e->getMessage(), 'ERROR');
}