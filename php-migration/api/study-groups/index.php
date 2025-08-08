<?php
/**
 * API Study Groups - Gestion groupes d'étude
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/Utils.php';
require_once '../../core/Validator.php';
require_once '../../core/services/StudyGroupService.php';

// Authentification requise
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

$method = $_SERVER['REQUEST_METHOD'];
$studyGroupService = new StudyGroupService();

try {
    switch ($method) {
        case 'GET':
            // Liste des groupes d'étude avec pagination et filtres
            $page = intval($_GET['page'] ?? 1);
            $perPage = intval($_GET['per_page'] ?? 20);
            $courseId = $_GET['course_id'] ?? null;
            $isPublic = $_GET['is_public'] ?? null;
            $search = $_GET['search'] ?? '';
            $myGroups = $_GET['my_groups'] ?? false;
            
            $filters = array_filter([
                'course_id' => $courseId,
                'is_public' => $isPublic,
                'search' => $search,
                'my_groups' => $myGroups ? $currentUser['id'] : null
            ]);
            
            $result = $studyGroupService->getStudyGroupsByEstablishment($establishmentId, $page, $perPage, $filters);
            echo json_encode($result);
            break;
            
        case 'POST':
            // Créer un nouveau groupe d'étude
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Vérifier CSRF
            if (!verifyCSRFToken($input['_token'] ?? '')) {
                http_response_code(422);
                echo json_encode(['error' => 'Token CSRF invalide']);
                exit;
            }
            
            // Ajouter l'établissement et le créateur automatiquement
            $input['establishment_id'] = $establishmentId;
            $input['creator_id'] = $currentUser['id'];
            
            $studyGroup = $studyGroupService->createStudyGroup($input);
            http_response_code(201);
            echo json_encode($studyGroup);
            break;
            
        case 'PUT':
            // Modifier un groupe d'étude
            $input = json_decode(file_get_contents('php://input'), true);
            $groupId = $input['id'] ?? null;
            
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
            
            // Vérifier que l'utilisateur est le créateur ou admin
            $group = $studyGroupService->getStudyGroupById($groupId);
            if (!$group || ($group['creator_id'] != $currentUser['id'] && !Auth::hasRole('admin'))) {
                http_response_code(403);
                echo json_encode(['error' => 'Permissions insuffisantes']);
                exit;
            }
            
            $studyGroup = $studyGroupService->updateStudyGroup($groupId, $input);
            echo json_encode($studyGroup);
            break;
            
        case 'DELETE':
            // Supprimer un groupe d'étude
            $input = json_decode(file_get_contents('php://input'), true);
            $groupId = $input['id'] ?? null;
            
            if (!$groupId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID groupe manquant']);
                exit;
            }
            
            // Vérifier que l'utilisateur est le créateur ou admin
            $group = $studyGroupService->getStudyGroupById($groupId);
            if (!$group || ($group['creator_id'] != $currentUser['id'] && !Auth::hasRole('admin'))) {
                http_response_code(403);
                echo json_encode(['error' => 'Permissions insuffisantes']);
                exit;
            }
            
            $success = $studyGroupService->deleteStudyGroup($groupId);
            echo json_encode(['success' => $success]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    Utils::log("Study Groups API error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>