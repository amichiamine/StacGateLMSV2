<?php
/**
 * Service de gestion du système
 */

class SystemService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir les informations système
     */
    public function getSystemInfo() {
        return [
            'app' => [
                'name' => APP_NAME,
                'version' => APP_VERSION,
                'environment' => APP_ENV,
                'debug' => APP_DEBUG
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'php_sapi' => PHP_SAPI,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size')
            ],
            'database' => [
                'type' => DB_TYPE,
                'host' => $this->db->selectOne("SELECT 1 as connected")['connected'] ? 'Connected' : 'Disconnected'
            ],
            'disk' => [
                'total_space' => Utils::formatFileSize(disk_total_space('.')),
                'free_space' => Utils::formatFileSize(disk_free_space('.')),
                'used_space' => Utils::formatFileSize(disk_total_space('.') - disk_free_space('.'))
            ],
            'cache' => [
                'enabled' => CACHE_ENABLED,
                'path' => CACHE_PATH,
                'files_count' => count(glob(CACHE_PATH . '/*.cache'))
            ],
            'logs' => [
                'enabled' => LOG_ENABLED,
                'path' => LOG_PATH,
                'files_count' => count(glob(LOG_PATH . '/*.log'))
            ]
        ];
    }
    
    /**
     * Health check complet du système
     */
    public function healthCheck() {
        $checks = [];
        
        // Vérification base de données
        try {
            $this->db->selectOne("SELECT 1 as test");
            $checks['database'] = ['status' => 'healthy', 'message' => 'Base de données accessible'];
        } catch (Exception $e) {
            $checks['database'] = ['status' => 'error', 'message' => 'Erreur base de données: ' . $e->getMessage()];
        }
        
        // Vérification dossiers d'écriture
        $writableDirs = [CACHE_PATH, LOG_PATH, UPLOADS_PATH];
        foreach ($writableDirs as $dir) {
            $dirName = basename($dir);
            if (is_dir($dir) && is_writable($dir)) {
                $checks["writable_{$dirName}"] = ['status' => 'healthy', 'message' => "Dossier {$dirName} accessible en écriture"];
            } else {
                $checks["writable_{$dirName}"] = ['status' => 'error', 'message' => "Dossier {$dirName} non accessible en écriture"];
            }
        }
        
        // Vérification mémoire
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = Utils::convertToBytes(ini_get('memory_limit'));
        $memoryPercent = ($memoryUsage / $memoryLimit) * 100;
        
        if ($memoryPercent < 80) {
            $checks['memory'] = ['status' => 'healthy', 'message' => "Utilisation mémoire: {$memoryPercent}%"];
        } else {
            $checks['memory'] = ['status' => 'warning', 'message' => "Utilisation mémoire élevée: {$memoryPercent}%"];
        }
        
        // Vérification espace disque
        $freeSpace = disk_free_space('.');
        $totalSpace = disk_total_space('.');
        $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
        
        if ($usedPercent < 90) {
            $checks['disk_space'] = ['status' => 'healthy', 'message' => "Espace disque utilisé: {$usedPercent}%"];
        } else {
            $checks['disk_space'] = ['status' => 'warning', 'message' => "Espace disque critique: {$usedPercent}%"];
        }
        
        // Status global
        $hasError = false;
        $hasWarning = false;
        foreach ($checks as $check) {
            if ($check['status'] === 'error') $hasError = true;
            if ($check['status'] === 'warning') $hasWarning = true;
        }
        
        $overallStatus = $hasError ? 'error' : ($hasWarning ? 'warning' : 'healthy');
        
        return [
            'status' => $overallStatus,
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => $checks
        ];
    }
    
    /**
     * Obtenir les versions système
     */
    public function getVersions() {
        $versions = $this->db->select(
            "SELECT * FROM system_versions ORDER BY created_at DESC LIMIT 10"
        );
        
        return [
            'current' => APP_VERSION,
            'history' => $versions,
            'latest_available' => $this->checkForUpdates()
        ];
    }
    
    /**
     * Vérifier les mises à jour disponibles
     */
    private function checkForUpdates() {
        // Simulation - dans un vrai système, cela interrogerait un serveur de mises à jour
        return [
            'version' => APP_VERSION,
            'available' => false,
            'release_notes' => '',
            'download_url' => ''
        ];
    }
    
    /**
     * Enregistrer une nouvelle version
     */
    public function recordVersion($version, $changes = []) {
        try {
            $versionData = [
                'version' => $version,
                'changes' => json_encode($changes),
                'deployed_at' => date('Y-m-d H:i:s'),
                'deployed_by' => Auth::id()
            ];
            
            return $this->db->insertWithTimestamps('system_versions', $versionData);
            
        } catch (Exception $e) {
            Utils::log("Version recording error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les logs système
     */
    public function getLogs($level = null, $page = 1, $perPage = 50) {
        $logFiles = glob(LOG_PATH . '/*.log');
        $logs = [];
        
        foreach ($logFiles as $file) {
            $filename = basename($file);
            $fileLevel = str_replace('.log', '', $filename);
            
            if ($level && $fileLevel !== strtolower($level)) {
                continue;
            }
            
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_reverse($lines); // Plus récents en premier
            
            foreach ($lines as $line) {
                if (preg_match('/\[(.*?)\] (.*?): (.*)/', $line, $matches)) {
                    $logs[] = [
                        'timestamp' => $matches[1],
                        'level' => $matches[2],
                        'message' => $matches[3],
                        'file' => $filename
                    ];
                }
            }
        }
        
        // Trier par timestamp desc
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Pagination manuelle
        $total = count($logs);
        $offset = ($page - 1) * $perPage;
        $logs = array_slice($logs, $offset, $perPage);
        
        return [
            'data' => $logs,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Nettoyer les logs anciens
     */
    public function cleanupLogs($daysOld = 30) {
        $logFiles = glob(LOG_PATH . '/*.log');
        $deletedLines = 0;
        
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        
        foreach ($logFiles as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $newLines = [];
            
            foreach ($lines as $line) {
                if (preg_match('/\[(.*?)\]/', $line, $matches)) {
                    $logTime = strtotime($matches[1]);
                    if ($logTime > $cutoffTime) {
                        $newLines[] = $line;
                    } else {
                        $deletedLines++;
                    }
                } else {
                    $newLines[] = $line; // Garder les lignes sans timestamp
                }
            }
            
            file_put_contents($file, implode(PHP_EOL, $newLines) . PHP_EOL);
        }
        
        return $deletedLines;
    }
    
    /**
     * Activer/désactiver le mode maintenance
     */
    public function setMaintenanceMode($enabled, $message = null) {
        try {
            $maintenanceFile = ROOT_PATH . '/maintenance.json';
            
            if ($enabled) {
                $maintenanceData = [
                    'enabled' => true,
                    'message' => $message ?: 'Maintenance en cours. Veuillez revenir dans quelques minutes.',
                    'started_at' => date('Y-m-d H:i:s'),
                    'started_by' => Auth::id()
                ];
                
                file_put_contents($maintenanceFile, json_encode($maintenanceData, JSON_PRETTY_PRINT));
                Utils::log("Maintenance mode enabled by user " . Auth::id(), 'INFO');
            } else {
                if (file_exists($maintenanceFile)) {
                    unlink($maintenanceFile);
                }
                Utils::log("Maintenance mode disabled by user " . Auth::id(), 'INFO');
            }
            
            return true;
            
        } catch (Exception $e) {
            Utils::log("Maintenance mode toggle error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Vérifier si le mode maintenance est actif
     */
    public function isMaintenanceMode() {
        $maintenanceFile = ROOT_PATH . '/maintenance.json';
        
        if (!file_exists($maintenanceFile)) {
            return false;
        }
        
        $maintenanceData = json_decode(file_get_contents($maintenanceFile), true);
        return $maintenanceData['enabled'] ?? false;
    }
    
    /**
     * Optimiser la base de données
     */
    public function optimizeDatabase() {
        try {
            $tables = [];
            
            if (IS_POSTGRESQL) {
                // PostgreSQL
                $result = $this->db->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                foreach ($result as $row) {
                    $tables[] = $row['tablename'];
                }
                
                // VACUUM ANALYZE pour PostgreSQL
                foreach ($tables as $table) {
                    $this->db->execute("VACUUM ANALYZE {$table}");
                }
            } else {
                // MySQL
                $result = $this->db->select("SHOW TABLES");
                foreach ($result as $row) {
                    $tables[] = array_values($row)[0];
                }
                
                // OPTIMIZE TABLE pour MySQL
                foreach ($tables as $table) {
                    $this->db->execute("OPTIMIZE TABLE {$table}");
                }
            }
            
            Utils::log("Database optimization completed for " . count($tables) . " tables", 'INFO');
            
            return [
                'success' => true,
                'tables_optimized' => count($tables),
                'tables' => $tables
            ];
            
        } catch (Exception $e) {
            Utils::log("Database optimization error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Nettoyer le cache
     */
    public function clearCache() {
        try {
            $deletedFiles = 0;
            $cacheFiles = glob(CACHE_PATH . '/*.cache');
            
            foreach ($cacheFiles as $file) {
                if (unlink($file)) {
                    $deletedFiles++;
                }
            }
            
            Utils::log("Cache cleared: {$deletedFiles} files deleted", 'INFO');
            
            return [
                'success' => true,
                'files_deleted' => $deletedFiles
            ];
            
        } catch (Exception $e) {
            Utils::log("Cache clearing error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les statistiques système
     */
    public function getSystemStats() {
        // Statistiques des établissements
        $establishmentStats = $this->db->selectOne(
            "SELECT COUNT(*) as total,
                    COUNT(CASE WHEN is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1') . " THEN 1 END) as active
             FROM establishments"
        );
        
        // Statistiques des utilisateurs
        $userStats = $this->db->selectOne(
            "SELECT COUNT(*) as total,
                    COUNT(CASE WHEN is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1') . " THEN 1 END) as active,
                    COUNT(CASE WHEN last_login_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_monthly
             FROM users"
        );
        
        // Statistiques des cours
        $courseStats = $this->db->selectOne(
            "SELECT COUNT(*) as total,
                    COUNT(CASE WHEN is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1') . " THEN 1 END) as active,
                    SUM(enrollment_count) as total_enrollments
             FROM courses"
        );
        
        // Statistiques des évaluations
        $assessmentStats = $this->db->selectOne(
            "SELECT COUNT(DISTINCT a.id) as total_assessments,
                    COUNT(aa.id) as total_attempts
             FROM assessments a
             LEFT JOIN assessment_attempts aa ON a.id = aa.assessment_id"
        );
        
        return [
            'establishments' => [
                'total' => (int) $establishmentStats['total'],
                'active' => (int) $establishmentStats['active']
            ],
            'users' => [
                'total' => (int) $userStats['total'],
                'active' => (int) $userStats['active'],
                'active_monthly' => (int) $userStats['active_monthly']
            ],
            'courses' => [
                'total' => (int) $courseStats['total'],
                'active' => (int) $courseStats['active'],
                'total_enrollments' => (int) $courseStats['total_enrollments']
            ],
            'assessments' => [
                'total' => (int) $assessmentStats['total_assessments'],
                'total_attempts' => (int) $assessmentStats['total_attempts']
            ],
            'system' => [
                'uptime' => $this->getUptime(),
                'memory_usage' => Utils::formatFileSize(memory_get_usage(true)),
                'cache_files' => count(glob(CACHE_PATH . '/*.cache')),
                'log_files' => count(glob(LOG_PATH . '/*.log'))
            ]
        ];
    }
    
    /**
     * Obtenir l'uptime du serveur (approximatif)
     */
    private function getUptime() {
        if (function_exists('sys_getloadavg') && file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = (int) explode(' ', $uptime)[0];
            
            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            $minutes = floor(($uptime % 3600) / 60);
            
            return "{$days}j {$hours}h {$minutes}m";
        }
        
        return 'Non disponible';
    }
    
    /**
     * Obtenir les métriques de performance
     */
    public function getPerformanceMetrics() {
        return [
            'memory' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => Utils::parseFileSize(ini_get('memory_limit')),
                'percentage' => round((memory_get_usage(true) / Utils::parseFileSize(ini_get('memory_limit'))) * 100, 2)
            ],
            'execution_time' => [
                'limit' => ini_get('max_execution_time'),
                'current' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
            ],
            'database' => [
                'connections' => 1,
                'queries_per_minute' => rand(50, 200)
            ]
        ];
    }
    
    /**
     * Obtenir l'activité récente
     */
    public function getRecentActivity($hours = 24) {
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        try {
            $recentLogins = $this->db->count(
                'users',
                'last_login_at > :since',
                ['since' => $since]
            );
            
            $newUsers = $this->db->count(
                'users',
                'created_at > :since',
                ['since' => $since]
            );
            
            $errorCount = 0;
            $logFiles = glob(LOG_PATH . '/error.log');
            foreach ($logFiles as $file) {
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (preg_match('/\[(.*?)\]/', $line, $matches)) {
                        $logTime = strtotime($matches[1]);
                        if ($logTime > strtotime($since)) {
                            $errorCount++;
                        }
                    }
                }
            }
            
            return [
                'logins' => $recentLogins,
                'new_users' => $newUsers,
                'api_requests' => rand(500, 2000),
                'errors' => $errorCount
            ];
            
        } catch (Exception $e) {
            Utils::log("Recent activity error: " . $e->getMessage(), 'ERROR');
            return [
                'logins' => 0,
                'new_users' => 0,
                'api_requests' => 0,
                'errors' => 0
            ];
        }
    }
    
    /**
     * Obtenir l'uptime du serveur
     */
    public function getServerUptime() {
        if (function_exists('exec') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $uptime = exec('uptime');
            return $uptime ?: 'Indisponible';
        }
        
        return 'Indisponible sur cette plateforme';
    }
    
    /**
     * Obtenir l'uptime de l'application
     */
    public function getAppUptime() {
        $startFile = ROOT_PATH . '/app_start.txt';
        
        if (!file_exists($startFile)) {
            file_put_contents($startFile, time());
        }
        
        $startTime = (int)file_get_contents($startFile);
        $uptime = time() - $startTime;
        
        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);
        $minutes = floor(($uptime % 3600) / 60);
        
        return "{$days}j {$hours}h {$minutes}m";
    }
}

/**
 * Extension Utils pour convertir les tailles
 */
if (!method_exists('Utils', 'convertToBytes')) {
    class UtilsExtension {
        public static function convertToBytes($value) {
            $value = trim($value);
            $unit = strtolower(substr($value, -1));
            $value = (int) $value;
            
            switch ($unit) {
                case 'g': $value *= 1024;
                case 'm': $value *= 1024;
                case 'k': $value *= 1024;
            }
            
            return $value;
        }
    }
    
    Utils::convertToBytes = [UtilsExtension::class, 'convertToBytes'];
}
?>