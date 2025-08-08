<?php
/**
 * Service d'exportation et d'archives
 */

class ExportService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Créer un job d'exportation
     */
    public function createExportJob($data) {
        try {
            $validator = Validator::make($data, [
                'type' => 'required|in:users,courses,analytics,assessments,study_groups,full_backup',
                'format' => 'required|in:csv,json,xml,pdf,zip',
                'establishment_id' => 'integer',
                'filters' => 'json'
            ]);
            
            if (!$validator->validate()) {
                throw new ValidationException($validator->getErrors());
            }
            
            $validatedData = $validator->getValidatedData();
            $validatedData['status'] = 'pending';
            $validatedData['created_by'] = Auth::id();
            $validatedData['filters'] = isset($data['filters']) ? json_encode($data['filters']) : '{}';
            
            $jobId = $this->db->insertWithTimestamps('export_jobs', $validatedData);
            
            // Traiter l'export en arrière-plan (simulation)
            $this->processExport($jobId);
            
            return $this->getExportJob($jobId);
            
        } catch (Exception $e) {
            Utils::log("Export job creation error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir un job d'exportation
     */
    public function getExportJob($id) {
        return $this->db->selectOne(
            "SELECT ej.*, u.first_name, u.last_name
             FROM export_jobs ej
             LEFT JOIN users u ON ej.created_by = u.id
             WHERE ej.id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Obtenir les jobs d'exportation
     */
    public function getExportJobs($establishmentId = null, $page = 1, $perPage = 20) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "ej.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        $sql = "SELECT ej.*, u.first_name, u.last_name
                FROM export_jobs ej
                LEFT JOIN users u ON ej.created_by = u.id
                WHERE {$whereClause}
                ORDER BY ej.created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Traiter un export (simulation du traitement en arrière-plan)
     */
    private function processExport($jobId) {
        try {
            $job = $this->getExportJob($jobId);
            if (!$job) {
                throw new Exception("Job introuvable");
            }
            
            // Marquer comme en cours
            $this->db->update('export_jobs', ['status' => 'processing'], 'id = :id', ['id' => $jobId]);
            
            $filters = json_decode($job['filters'], true);
            $data = [];
            
            // Générer les données selon le type
            switch ($job['type']) {
                case 'users':
                    $data = $this->exportUsers($job['establishment_id'], $filters);
                    break;
                case 'courses':
                    $data = $this->exportCourses($job['establishment_id'], $filters);
                    break;
                case 'analytics':
                    $data = $this->exportAnalytics($job['establishment_id'], $filters);
                    break;
                case 'assessments':
                    $data = $this->exportAssessments($job['establishment_id'], $filters);
                    break;
                case 'study_groups':
                    $data = $this->exportStudyGroups($job['establishment_id'], $filters);
                    break;
                case 'full_backup':
                    $data = $this->exportFullBackup($job['establishment_id']);
                    break;
            }
            
            // Générer le fichier
            $filename = $this->generateExportFile($data, $job['format'], $job['type']);
            
            // Marquer comme terminé
            $this->db->update('export_jobs', [
                'status' => 'completed',
                'file_path' => $filename,
                'completed_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $jobId]);
            
        } catch (Exception $e) {
            // Marquer comme échoué
            $this->db->update('export_jobs', [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ], 'id = :id', ['id' => $jobId]);
            
            Utils::log("Export processing error: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Exporter les utilisateurs
     */
    private function exportUsers($establishmentId, $filters) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "u.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        if (!empty($filters['role'])) {
            $whereClause .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }
        
        if (!empty($filters['active_only'])) {
            $whereClause .= " AND u.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        }
        
        return $this->db->select(
            "SELECT u.id, u.first_name, u.last_name, u.email, u.role, 
                    u.is_active, u.created_at, u.last_login_at,
                    e.name as establishment_name
             FROM users u
             LEFT JOIN establishments e ON u.establishment_id = e.id
             WHERE {$whereClause}
             ORDER BY u.created_at DESC",
            $params
        );
    }
    
    /**
     * Exporter les cours
     */
    private function exportCourses($establishmentId, $filters) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "c.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        if (!empty($filters['category'])) {
            $whereClause .= " AND c.category = :category";
            $params['category'] = $filters['category'];
        }
        
        if (!empty($filters['active_only'])) {
            $whereClause .= " AND c.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        }
        
        return $this->db->select(
            "SELECT c.id, c.title, c.category, c.type, c.price, c.is_free,
                    c.duration, c.level, c.enrollment_count, c.rating,
                    c.is_active, c.created_at,
                    u.first_name as instructor_first_name,
                    u.last_name as instructor_last_name,
                    e.name as establishment_name
             FROM courses c
             LEFT JOIN users u ON c.instructor_id = u.id
             LEFT JOIN establishments e ON c.establishment_id = e.id
             WHERE {$whereClause}
             ORDER BY c.created_at DESC",
            $params
        );
    }
    
    /**
     * Exporter les analytics
     */
    private function exportAnalytics($establishmentId, $filters) {
        $analyticsService = new AnalyticsService();
        
        return [
            'overview' => $analyticsService->getOverview($establishmentId),
            'popular_courses' => $analyticsService->getPopularCourses($establishmentId),
            'category_distribution' => $analyticsService->getCategoryDistribution($establishmentId),
            'progress_stats' => $analyticsService->getProgressStats($establishmentId),
            'enrollment_stats' => $analyticsService->getEnrollmentStats($establishmentId),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Exporter les évaluations
     */
    private function exportAssessments($establishmentId, $filters) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "a.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        if (!empty($filters['type'])) {
            $whereClause .= " AND a.type = :type";
            $params['type'] = $filters['type'];
        }
        
        return $this->db->select(
            "SELECT a.id, a.title, a.type, a.time_limit, a.max_attempts,
                    a.passing_score, a.is_active, a.created_at,
                    c.title as course_title,
                    COUNT(aa.id) as total_attempts
             FROM assessments a
             LEFT JOIN courses c ON a.course_id = c.id
             LEFT JOIN assessment_attempts aa ON a.id = aa.assessment_id
             WHERE {$whereClause}
             GROUP BY a.id, c.title
             ORDER BY a.created_at DESC",
            $params
        );
    }
    
    /**
     * Exporter les groupes d'étude
     */
    private function exportStudyGroups($establishmentId, $filters) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "sg.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        return $this->db->select(
            "SELECT sg.id, sg.name, sg.description, sg.max_members,
                    sg.is_public, sg.is_active, sg.created_at,
                    c.title as course_title,
                    u.first_name as creator_first_name,
                    u.last_name as creator_last_name,
                    COUNT(sgm.id) as member_count
             FROM study_groups sg
             LEFT JOIN courses c ON sg.course_id = c.id
             LEFT JOIN users u ON sg.creator_id = u.id
             LEFT JOIN study_group_members sgm ON sg.id = sgm.study_group_id
             WHERE {$whereClause}
             GROUP BY sg.id, c.title, u.first_name, u.last_name
             ORDER BY sg.created_at DESC",
            $params
        );
    }
    
    /**
     * Backup complet
     */
    private function exportFullBackup($establishmentId) {
        return [
            'establishment' => $this->db->selectOne("SELECT * FROM establishments WHERE id = :id", ['id' => $establishmentId]),
            'users' => $this->exportUsers($establishmentId, []),
            'courses' => $this->exportCourses($establishmentId, []),
            'assessments' => $this->exportAssessments($establishmentId, []),
            'study_groups' => $this->exportStudyGroups($establishmentId, []),
            'themes' => $this->db->select("SELECT * FROM themes WHERE establishment_id = :id", ['id' => $establishmentId]),
            'backup_date' => date('Y-m-d H:i:s'),
            'version' => APP_VERSION
        ];
    }
    
    /**
     * Générer le fichier d'export
     */
    private function generateExportFile($data, $format, $type) {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "{$type}_export_{$timestamp}";
        
        switch ($format) {
            case 'csv':
                return $this->generateCSV($data, $filename);
            case 'json':
                return $this->generateJSON($data, $filename);
            case 'xml':
                return $this->generateXML($data, $filename);
            case 'pdf':
                return $this->generatePDF($data, $filename);
            case 'zip':
                return $this->generateZIP($data, $filename);
            default:
                throw new Exception("Format non supporté: {$format}");
        }
    }
    
    /**
     * Générer un fichier CSV
     */
    private function generateCSV($data, $filename) {
        $filepath = UPLOADS_PATH . "/exports/{$filename}.csv";
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $file = fopen($filepath, 'w');
        
        if (is_array($data) && !empty($data)) {
            // Si c'est un tableau de tableaux associatifs
            if (is_array($data[0])) {
                fputcsv($file, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
            } else {
                // Données complexes - aplatir
                fputcsv($file, ['Key', 'Value']);
                foreach ($data as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value);
                    }
                    fputcsv($file, [$key, $value]);
                }
            }
        }
        
        fclose($file);
        
        return "exports/{$filename}.csv";
    }
    
    /**
     * Générer un fichier JSON
     */
    private function generateJSON($data, $filename) {
        $filepath = UPLOADS_PATH . "/exports/{$filename}.json";
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return "exports/{$filename}.json";
    }
    
    /**
     * Générer un fichier XML
     */
    private function generateXML($data, $filename) {
        $filepath = UPLOADS_PATH . "/exports/{$filename}.xml";
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><export/>');
        $this->arrayToXML($data, $xml);
        
        file_put_contents($filepath, $xml->asXML());
        
        return "exports/{$filename}.xml";
    }
    
    /**
     * Convertir un tableau en XML
     */
    private function arrayToXML($data, $xml) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $subnode = $xml->addChild($key);
                $this->arrayToXML($value, $subnode);
            } else {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
    
    /**
     * Générer un fichier PDF (basique)
     */
    private function generatePDF($data, $filename) {
        // Pour une vraie implémentation PDF, utiliser une bibliothèque comme TCPDF
        // Ici on génère un HTML simple
        $filepath = UPLOADS_PATH . "/exports/{$filename}.html";
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $html = "<!DOCTYPE html><html><head><title>Export {$filename}</title>";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}</style>";
        $html .= "</head><body>";
        $html .= "<h1>Export de données - " . date('d/m/Y H:i') . "</h1>";
        
        if (is_array($data) && !empty($data) && is_array($data[0])) {
            $html .= "<table><thead><tr>";
            foreach (array_keys($data[0]) as $header) {
                $html .= "<th>" . htmlspecialchars($header) . "</th>";
            }
            $html .= "</tr></thead><tbody>";
            
            foreach ($data as $row) {
                $html .= "<tr>";
                foreach ($row as $cell) {
                    if (is_array($cell) || is_object($cell)) {
                        $cell = json_encode($cell);
                    }
                    $html .= "<td>" . htmlspecialchars($cell) . "</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</tbody></table>";
        } else {
            $html .= "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        }
        
        $html .= "</body></html>";
        
        file_put_contents($filepath, $html);
        
        return "exports/{$filename}.html";
    }
    
    /**
     * Générer un fichier ZIP
     */
    private function generateZIP($data, $filename) {
        $filepath = UPLOADS_PATH . "/exports/{$filename}.zip";
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Impossible de créer le fichier ZIP");
        }
        
        // Ajouter les fichiers JSON
        $zip->addFromString("{$filename}.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Ajouter le CSV si possible
        if (is_array($data) && !empty($data) && is_array($data[0])) {
            $csv = Utils::arrayToCsv($data);
            $zip->addFromString("{$filename}.csv", $csv);
        }
        
        $zip->close();
        
        return "exports/{$filename}.zip";
    }
    
    /**
     * Télécharger un fichier d'export
     */
    public function downloadExport($jobId) {
        $job = $this->getExportJob($jobId);
        
        if (!$job || $job['status'] !== 'completed') {
            throw new Exception("Export non disponible");
        }
        
        $filepath = UPLOADS_PATH . '/' . $job['file_path'];
        
        if (!file_exists($filepath)) {
            throw new Exception("Fichier introuvable");
        }
        
        return [
            'filepath' => $filepath,
            'filename' => basename($filepath),
            'content_type' => $this->getContentType($filepath)
        ];
    }
    
    /**
     * Supprimer un job d'export
     */
    public function deleteExportJob($jobId) {
        $job = $this->getExportJob($jobId);
        
        if ($job && $job['file_path']) {
            $filepath = UPLOADS_PATH . '/' . $job['file_path'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        return $this->db->delete('export_jobs', 'id = :id', ['id' => $jobId]);
    }
    
    /**
     * Obtenir le type de contenu selon l'extension
     */
    private function getContentType($filepath) {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'csv':
                return 'text/csv';
            case 'json':
                return 'application/json';
            case 'xml':
                return 'application/xml';
            case 'html':
                return 'text/html';
            case 'zip':
                return 'application/zip';
            default:
                return 'application/octet-stream';
        }
    }
    
    /**
     * Nettoyer les anciens exports
     */
    public function cleanupOldExports($daysOld = 7) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        // Obtenir les anciens jobs
        $oldJobs = $this->db->select(
            "SELECT * FROM export_jobs WHERE created_at < :cutoff",
            ['cutoff' => $cutoffDate]
        );
        
        $deletedCount = 0;
        foreach ($oldJobs as $job) {
            if ($job['file_path']) {
                $filepath = UPLOADS_PATH . '/' . $job['file_path'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
            $deletedCount++;
        }
        
        // Supprimer les entrées de la base
        $this->db->delete('export_jobs', 'created_at < :cutoff', ['cutoff' => $cutoffDate]);
        
        return $deletedCount;
    }
}
?>