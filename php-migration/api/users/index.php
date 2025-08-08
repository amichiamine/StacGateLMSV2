<?php
/**
 * API Users - Gestion utilisateurs
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/Utils.php';
require_once '../../core/Validator.php';
require_once '../../core/services/AuthService.php';

// Authentification requise
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Vérifier permissions
if (!Auth::hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$authService = new AuthService();

try {
    switch ($method) {
        case 'GET':
            // Liste des utilisateurs avec pagination et filtres
            $page = intval($_GET['page'] ?? 1);
            $perPage = intval($_GET['per_page'] ?? 20);
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $filters = array_filter([
                'search' => $search,
                'role' => $role,
                'status' => $status
            ]);
            
            $result = $authService->getUsersByEstablishment($establishmentId, $page, $perPage, $filters);
            echo json_encode($result);
            break;
            
        case 'POST':
            // Créer un nouvel utilisateur
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Vérifier CSRF
            if (!verifyCSRFToken($input['_token'] ?? '')) {
                http_response_code(422);
                echo json_encode(['error' => 'Token CSRF invalide']);
                exit;
            }
            
            // Ajouter l'établissement automatiquement
            $input['establishment_id'] = $establishmentId;
            
            $user = $authService->createUser($input);
            http_response_code(201);
            echo json_encode($user);
            break;
            
        case 'PUT':
            // Modifier un utilisateur
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $input['id'] ?? null;
            
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID utilisateur manquant']);
                exit;
            }
            
            // Vérifier CSRF
            if (!verifyCSRFToken($input['_token'] ?? '')) {
                http_response_code(422);
                echo json_encode(['error' => 'Token CSRF invalide']);
                exit;
            }
            
            $user = $authService->updateUser($userId, $input);
            echo json_encode($user);
            break;
            
        case 'DELETE':
            // Supprimer un utilisateur
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $input['id'] ?? null;
            
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID utilisateur manquant']);
                exit;
            }
            
            // Empêcher la suppression de soi-même
            if ($userId == $currentUser['id']) {
                http_response_code(422);
                echo json_encode(['error' => 'Impossible de supprimer votre propre compte']);
                exit;
            }
            
            $success = $authService->deleteUser($userId);
            echo json_encode(['success' => $success]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    Utils::log("Users API error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>