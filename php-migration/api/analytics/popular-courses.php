<?php
/**
 * API - Cours populaires
 */

header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

try {
    $user = Auth::user();
    $limit = intval($_GET['limit'] ?? 5);
    $establishmentId = null;
    
    // Super admin peut voir tous les cours
    if (!Auth::hasRole('super_admin')) {
        $establishmentId = $user['establishment_id'];
    }
    
    $analyticsService = new AnalyticsService();
    $popularCourses = $analyticsService->getPopularCourses($establishmentId, $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $popularCourses
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Popular courses error: " . $e->getMessage(), 'ERROR');
}