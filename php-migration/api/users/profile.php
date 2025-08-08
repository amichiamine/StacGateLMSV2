<?php
/**
 * API Users - Gestion profil utilisateur
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/Utils.php';
require_once '../../core/services/AuthService.php';

// Authentification requise
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$currentUser = Auth::user();
$authService = new AuthService();

try {
    switch ($method) {
        case 'GET':
            // Obtenir le profil actuel
            echo json_encode($currentUser);
            break;
            
        case 'PUT':
            // Mettre à jour le profil
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Vérifier CSRF
            if (!verifyCSRFToken($input['_token'] ?? '')) {
                http_response_code(422);
                echo json_encode(['error' => 'Token CSRF invalide']);
                exit;
            }
            
            // Champs autorisés pour la modification du profil
            $allowedFields = ['first_name', 'last_name', 'username', 'avatar'];
            $updateData = array_intersect_key($input, array_flip($allowedFields));
            
            $user = $authService->updateUser($currentUser['id'], $updateData);
            echo json_encode($user);
            break;
            
        case 'POST':
            // Changer le mot de passe
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Vérifier CSRF
            if (!verifyCSRFToken($input['_token'] ?? '')) {
                http_response_code(422);
                echo json_encode(['error' => 'Token CSRF invalide']);
                exit;
            }
            
            $currentPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';
            $confirmPassword = $input['confirm_password'] ?? '';
            
            // Vérifications
            if (!Auth::verifyPassword($currentPassword, $currentUser['password'])) {
                http_response_code(422);
                echo json_encode(['error' => 'Mot de passe actuel incorrect']);
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                http_response_code(422);
                echo json_encode(['error' => 'Les mots de passe ne correspondent pas']);
                exit;
            }
            
            if (strlen($newPassword) < 8) {
                http_response_code(422);
                echo json_encode(['error' => 'Le mot de passe doit contenir au moins 8 caractères']);
                exit;
            }
            
            // Mettre à jour le mot de passe
            $hashedPassword = Auth::hashPassword($newPassword);
            $authService->updateUser($currentUser['id'], ['password' => $hashedPassword]);
            
            echo json_encode(['success' => true, 'message' => 'Mot de passe mis à jour']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    Utils::log("Profile API error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>