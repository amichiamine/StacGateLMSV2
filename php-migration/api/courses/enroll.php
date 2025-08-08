<?php
/**
 * API - Inscription à un cours
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
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
    
    $courseId = intval($input['course_id'] ?? 0);
    $action = $input['action'] ?? 'enroll'; // enroll ou unenroll
    
    if (!$courseId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID cours requis']);
        exit;
    }
    
    $user = Auth::user();
    $courseService = new CourseService();
    
    // Vérifier que le cours existe et appartient au même établissement
    $course = $courseService->getCourseById($courseId);
    if (!$course || ($course['establishment_id'] != $user['establishment_id'] && !Auth::hasRole('super_admin'))) {
        http_response_code(404);
        echo json_encode(['error' => 'Cours non trouvé']);
        exit;
    }
    
    if ($action === 'enroll') {
        $courseService->enrollUser($user['id'], $courseId);
        $message = 'Inscription réussie au cours';
    } else {
        $courseService->unenrollUser($user['id'], $courseId);
        $message = 'Désinscription réussie du cours';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Course enrollment error: " . $e->getMessage(), 'ERROR');
}