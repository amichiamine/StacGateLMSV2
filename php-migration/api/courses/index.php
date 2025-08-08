<?php
/**
 * API - Liste des cours
 */

header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

try {
    $user = Auth::user();
    $page = intval($_GET['page'] ?? 1);
    $perPage = intval($_GET['per_page'] ?? 10);
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $filters = [];
    if ($search) $filters['search'] = $search;
    if ($category) $filters['category'] = $category;
    if ($status) $filters['status'] = $status;
    
    // Si c'est un formateur, montrer ses cours
    if (Auth::hasRole('formateur') && !Auth::hasRole('manager')) {
        $filters['instructor_id'] = $user['id'];
    }
    
    $courseService = new CourseService();
    $result = $courseService->getCoursesByEstablishment($user['establishment_id'], $page, $perPage, $filters);
    
    echo json_encode([
        'success' => true,
        'data' => $result['data'],
        'meta' => $result['meta']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Courses list error: " . $e->getMessage(), 'ERROR');
}