<?php
/**
 * API - Détails d'un cours
 */

header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

try {
    $courseId = intval($_GET['id'] ?? 0);
    if (!$courseId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID cours requis']);
        exit;
    }
    
    $courseService = new CourseService();
    $course = $courseService->getCourseById($courseId);
    
    if (!$course) {
        http_response_code(404);
        echo json_encode(['error' => 'Cours non trouvé']);
        exit;
    }
    
    $user = Auth::user();
    
    // Vérifier les permissions
    if ($course['establishment_id'] != $user['establishment_id'] && !Auth::hasRole('super_admin')) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès non autorisé']);
        exit;
    }
    
    // Obtenir les inscriptions si c'est un apprenant
    $enrollment = null;
    if (Auth::hasRole('apprenant')) {
        $userCourses = $courseService->getUserCourses($user['id'], 1, 1000);
        foreach ($userCourses['data'] as $userCourse) {
            if ($userCourse['id'] == $courseId) {
                $enrollment = $userCourse;
                break;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'course' => $course,
        'enrollment' => $enrollment
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Course details error: " . $e->getMessage(), 'ERROR');
}