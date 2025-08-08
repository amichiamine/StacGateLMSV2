<?php
/**
 * API - Statistiques système
 * GET /api/system/stats
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
    $systemInfo = $systemService->getSystemInfo();
    $performanceMetrics = $systemService->getPerformanceMetrics();
    $recentActivity = $systemService->getRecentActivity(24); // 24 dernières heures
    
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'system_info' => $systemInfo,
        'performance' => $performanceMetrics,
        'activity' => [
            'recent_logins' => $recentActivity['logins'],
            'new_users' => $recentActivity['new_users'],
            'api_requests' => $recentActivity['api_requests'],
            'errors' => $recentActivity['errors']
        ],
        'resource_usage' => [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => Utils::parseFileSize(ini_get('memory_limit')),
            'memory_percentage' => round((memory_get_usage(true) / Utils::parseFileSize(ini_get('memory_limit'))) * 100, 2)
        ],
        'uptime' => [
            'server_uptime' => $systemService->getServerUptime(),
            'app_uptime' => $systemService->getAppUptime()
        ]
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    Utils::log("API System stats error: " . $e->getMessage(), 'ERROR');
}