<?php
/**
 * API - Gestion membres groupes d'étude
 * GET/POST /api/study-groups/{id}/members
 */

header('Content-Type: application/json');

Auth::requireAuth();
$currentUser = Auth::user();

try {
    $groupId = $_GET['group_id'] ?? $_POST['group_id'] ?? null;
    
    if (!$groupId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID du groupe requis']);
        exit;
    }
    
    $studyGroupService = new StudyGroupService();
    
    // Vérifier que l'utilisateur peut accéder à ce groupe
    if (!$studyGroupService->canAccessGroup($groupId, $currentUser['id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès non autorisé à ce groupe']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtenir la liste des membres
        $members = $studyGroupService->getGroupMembers($groupId);
        
        echo json_encode([
            'success' => true,
            'data' => $members
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ajouter/retirer un membre
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        if (!verifyCSRFToken($input['_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide']);
            exit;
        }
        
        $action = $input['action'] ?? '';
        $userId = $input['user_id'] ?? null;
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID utilisateur requis']);
            exit;
        }
        
        switch ($action) {
            case 'add':
                $result = $studyGroupService->addMember($groupId, $userId);
                echo json_encode([
                    'success' => true,
                    'message' => 'Membre ajouté avec succès',
                    'data' => $result
                ]);
                break;
                
            case 'remove':
                // Vérifier permissions (propriétaire du groupe ou admin)
                if (!$studyGroupService->canManageGroup($groupId, $currentUser['id'])) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Permissions insuffisantes']);
                    exit;
                }
                
                $studyGroupService->removeMember($groupId, $userId);
                echo json_encode([
                    'success' => true,
                    'message' => 'Membre retiré avec succès'
                ]);
                break;
                
            case 'update_role':
                // Vérifier permissions
                if (!$studyGroupService->canManageGroup($groupId, $currentUser['id'])) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Permissions insuffisantes']);
                    exit;
                }
                
                $role = $input['role'] ?? 'member';
                $result = $studyGroupService->updateMemberRole($groupId, $userId, $role);
                echo json_encode([
                    'success' => true,
                    'message' => 'Rôle mis à jour avec succès',
                    'data' => $result
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Action non reconnue']);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Study group members error: " . $e->getMessage(), 'ERROR');
}