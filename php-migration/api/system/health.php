<?php
/**
 * API - Health check système
 * GET /api/system/health
 */

header('Content-Type: application/json');

// Vérifier authentification et permissions admin
Auth::requireAuth();
if (!Auth::hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes']);
    exit;
}

try {
    $systemService = new SystemService();
    $healthCheck = $systemService->healthCheck();
    
    // Calculer le statut global
    $globalStatus = 'healthy';
    $errors = [];
    
    foreach ($healthCheck as $checkName => $check) {
        if ($check['status'] === 'error') {
            $globalStatus = 'error';
            $errors[] = $checkName . ': ' . $check['message'];
        } elseif ($check['status'] === 'warning' && $globalStatus === 'healthy') {
            $globalStatus = 'warning';
        }
    }
    
    $response = [
        'success' => true,
        'status' => $globalStatus,
        'timestamp' => date('Y-m-d H:i:s'),
        'checks' => $healthCheck,
        'summary' => [
            'total_checks' => count($healthCheck),
            'healthy' => count(array_filter($healthCheck, fn($c) => $c['status'] === 'healthy')),
            'warnings' => count(array_filter($healthCheck, fn($c) => $c['status'] === 'warning')),
            'errors' => count(array_filter($healthCheck, fn($c) => $c['status'] === 'error'))
        ]
    ];
    
    if ($globalStatus === 'error') {
        $response['errors'] = $errors;
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    Utils::log("API System health error: " . $e->getMessage(), 'ERROR');
}