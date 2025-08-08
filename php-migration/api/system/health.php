<?php
/**
 * API System - État de santé du système
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/Utils.php';

// Authentification requise
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => []
        ];
        
        // Test base de données
        try {
            $db = Database::getInstance();
            $db->selectOne("SELECT 1 as test");
            $health['checks']['database'] = [
                'status' => 'ok',
                'message' => 'Base de données accessible'
            ];
        } catch (Exception $e) {
            $health['checks']['database'] = [
                'status' => 'error',
                'message' => 'Erreur de connexion à la base de données'
            ];
            $health['status'] = 'unhealthy';
        }
        
        // Test espace disque
        $diskFree = disk_free_space('.');
        $diskTotal = disk_total_space('.');
        $diskUsagePercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
        
        $health['checks']['disk_space'] = [
            'status' => $diskUsagePercent < 90 ? 'ok' : 'warning',
            'usage_percent' => $diskUsagePercent,
            'free_bytes' => $diskFree,
            'total_bytes' => $diskTotal
        ];
        
        // Test mémoire
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = Utils::convertToBytes(ini_get('memory_limit'));
        $memoryPercent = round(($memoryUsage / $memoryLimit) * 100, 2);
        
        $health['checks']['memory'] = [
            'status' => $memoryPercent < 80 ? 'ok' : 'warning',
            'usage_percent' => $memoryPercent,
            'usage_bytes' => $memoryUsage,
            'limit_bytes' => $memoryLimit
        ];
        
        // Test répertoires critiques
        $criticalDirs = ['logs', 'cache', 'uploads'];
        foreach ($criticalDirs as $dir) {
            $dirPath = ROOT_PATH . '/' . $dir;
            $health['checks']["dir_$dir"] = [
                'status' => (is_dir($dirPath) && is_writable($dirPath)) ? 'ok' : 'error',
                'writable' => is_writable($dirPath),
                'exists' => is_dir($dirPath)
            ];
            
            if (!is_dir($dirPath) || !is_writable($dirPath)) {
                $health['status'] = 'unhealthy';
            }
        }
        
        // Définir le code de statut HTTP
        http_response_code($health['status'] === 'healthy' ? 200 : 503);
        echo json_encode($health);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
    }
    
} catch (Exception $e) {
    Utils::log("System health API error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Erreur lors de la vérification de santé'
    ]);
}
?>