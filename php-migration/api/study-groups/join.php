<?php
/**
 * API Study Groups - Rejoindre/Quitter groupe
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/Utils.php';
require_once '../../core/services/StudyGroupService.php';

// Authentification requise
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$currentUser = Auth::user();
$method = $_SERVER['REQUEST_METHOD'];
$studyGroupService = new StudyGroupService();

try {
    switch ($method) {
        case 'POST':
            // Rejoindre un groupe
            $input = json_decode(file_get_contents('php://input'), true);
            $groupId = $input['group_id'] ?? null;
            
            if (!$groupId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID groupe manquant']);
                exit;
            }
            
            // Vérifier CSRF
            if (!verifyCSRFToken($input['_token'] ?? '')) {
                http_response_code(422);
                echo json_encode(['error' => 'Token CSRF invalide']);
                exit;
            }
            
            $result = $studyGroupService->joinGroup($groupId, $currentUser['id']);
            echo json_encode($result);
            break;
            
        case 'DELETE':
            // Quitter un groupe
            $input = json_decode(file_get_contents('php://input'), true);
            $groupId = $input['group_id'] ?? null;
            
            if (!$groupId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID groupe manquant']);
                exit;
            }
            
            $result = $studyGroupService->leaveGroup($groupId, $currentUser['id']);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    Utils::log("Study Groups Join API error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>