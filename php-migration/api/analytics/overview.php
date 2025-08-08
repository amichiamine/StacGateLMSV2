<?php
/**
 * API - Vue d'ensemble des analytics
 */

header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

try {
    $user = Auth::user();
    $establishmentId = null;
    
    // Super admin peut voir toutes les stats
    if (!Auth::hasRole('super_admin')) {
        $establishmentId = $user['establishment_id'];
    }
    
    // Manager+ peut voir les analytics
    if (!Auth::hasRole('manager')) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès non autorisé']);
        exit;
    }
    
    $analyticsService = new AnalyticsService();
    $overview = $analyticsService->getOverview($establishmentId);
    
    echo json_encode([
        'success' => true,
        'data' => $overview
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Analytics overview error: " . $e->getMessage(), 'ERROR');
}