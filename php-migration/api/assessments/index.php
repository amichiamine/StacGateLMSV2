<?php
/**
 * API Assessments - Gestion évaluations
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/Utils.php';
require_once '../../core/Validator.php';
require_once '../../core/services/AssessmentService.php';

// Authentification requise
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

$method = $_SERVER['REQUEST_METHOD'];
$assessmentService = new AssessmentService();

try {
    switch ($method) {
        case 'GET':
            // Liste des évaluations avec pagination et filtres
            $page = intval($_GET['page'] ?? 1);
            $perPage = intval($_GET['per_page'] ?? 20);
            $courseId = $_GET['course_id'] ?? null;
            $type = $_GET['type'] ?? '';
            $search = $_GET['search'] ?? '';
            
            $filters = array_filter([
                'course_id' => $courseId,
                'type' => $type,
                'search' => $search
            ]);
            
            $result = $assessmentService->getAssessmentsByEstablishment($establishmentId, $page, $perPage, $filters);
            echo json_encode($result);
            break;
            
        case 'POST':
            // Créer une nouvelle évaluation
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Vérifier CSRF
            if (!verifyCSRFToken($input['_token'] ?? '')) {
                http_response_code(422);
                echo json_encode(['error' => 'Token CSRF invalide']);
                exit;
            }
            
            // Vérifier permissions
            if (!Auth::hasRole('formateur')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permissions insuffisantes']);
                exit;
            }
            
            // Ajouter l'établissement automatiquement
            $input['establishment_id'] = $establishmentId;
            
            $assessment = $assessmentService->createAssessment($input);
            http_response_code(201);
            echo json_encode($assessment);
            break;
            
        case 'PUT':
            // Modifier une évaluation
            $input = json_decode(file_get_contents('php://input'), true);
            $assessmentId = $input['id'] ?? null;
            
            if (!$assessmentId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID évaluation manquant']);
                exit;
            }
            
            // Vérifier CSRF
            if (!verifyCSRFToken($input['_token'] ?? '')) {
                http_response_code(422);
                echo json_encode(['error' => 'Token CSRF invalide']);
                exit;
            }
            
            // Vérifier permissions
            if (!Auth::hasRole('formateur')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permissions insuffisantes']);
                exit;
            }
            
            $assessment = $assessmentService->updateAssessment($assessmentId, $input);
            echo json_encode($assessment);
            break;
            
        case 'DELETE':
            // Supprimer une évaluation
            $input = json_decode(file_get_contents('php://input'), true);
            $assessmentId = $input['id'] ?? null;
            
            if (!$assessmentId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID évaluation manquant']);
                exit;
            }
            
            // Vérifier permissions
            if (!Auth::hasRole('formateur')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permissions insuffisantes']);
                exit;
            }
            
            $success = $assessmentService->deleteAssessment($assessmentId);
            echo json_encode(['success' => $success]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    Utils::log("Assessments API error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>