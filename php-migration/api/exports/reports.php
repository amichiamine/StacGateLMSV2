<?php
/**
 * API - Export de rapports avancés
 * POST /api/exports/reports
 */

header('Content-Type: application/json');

Auth::requireAuth();
if (!Auth::hasRole('manager')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    if (!verifyCSRFToken($input['_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF invalide']);
        exit;
    }
    
    $currentUser = Auth::user();
    $establishmentId = Auth::hasRole('super_admin') ? ($input['establishment_id'] ?? null) : $currentUser['establishment_id'];
    
    $reportType = $input['report_type'] ?? '';
    $format = $input['format'] ?? 'csv';
    $dateFrom = $input['date_from'] ?? null;
    $dateTo = $input['date_to'] ?? null;
    $filters = $input['filters'] ?? [];
    
    // Validation
    $allowedReports = ['users', 'courses', 'enrollments', 'assessments', 'analytics', 'activity'];
    $allowedFormats = ['csv', 'excel', 'pdf'];
    
    if (!in_array($reportType, $allowedReports)) {
        http_response_code(400);
        echo json_encode(['error' => 'Type de rapport non supporté']);
        exit;
    }
    
    if (!in_array($format, $allowedFormats)) {
        http_response_code(400);
        echo json_encode(['error' => 'Format non supporté']);
        exit;
    }
    
    $exportService = new ExportService();
    
    // Générer le rapport selon le type
    switch ($reportType) {
        case 'users':
            $export = $exportService->exportUsers($establishmentId, $format, $filters);
            break;
            
        case 'courses':
            $export = $exportService->exportCourses($establishmentId, $format, $filters);
            break;
            
        case 'enrollments':
            $export = $exportService->exportEnrollments($establishmentId, $format, $dateFrom, $dateTo);
            break;
            
        case 'assessments':
            $export = $exportService->exportAssessmentResults($establishmentId, $format, $filters);
            break;
            
        case 'analytics':
            $export = $exportService->exportAnalytics($establishmentId, $format, $dateFrom, $dateTo);
            break;
            
        case 'activity':
            $export = $exportService->exportUserActivity($establishmentId, $format, $dateFrom, $dateTo);
            break;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $export,
        'message' => 'Export généré avec succès'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API Export reports error: " . $e->getMessage(), 'ERROR');
}