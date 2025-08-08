<?php
/**
 * Service d'analytics et métriques
 */

class AnalyticsService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir les métriques générales
     */
    public function getOverview($establishmentId = null) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        // Utilisateurs
        $totalUsers = $this->db->count('users', $whereClause, $params);
        $activeUsers = $this->db->count(
            'users',
            $whereClause . " AND last_login_at > :date",
            array_merge($params, ['date' => date('Y-m-d H:i:s', strtotime('-30 days'))])
        );
        
        // Cours
        $totalCourses = $this->db->count('courses', $whereClause, $params);
        $activeCourses = $this->db->count(
            'courses',
            $whereClause . " AND is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1'),
            $params
        );
        
        // Inscriptions
        $enrollmentSql = $establishmentId 
            ? "SELECT COUNT(uc.id) as count FROM user_courses uc JOIN courses c ON uc.course_id = c.id WHERE c.establishment_id = :establishment_id"
            : "SELECT COUNT(*) as count FROM user_courses";
            
        $totalEnrollments = $this->db->selectOne($enrollmentSql, $params)['count'] ?? 0;
        
        $newEnrollments = $this->db->selectOne(
            $enrollmentSql . " AND uc.enrolled_at > :date",
            array_merge($params, ['date' => date('Y-m-d H:i:s', strtotime('-30 days'))])
        )['count'] ?? 0;
        
        // Évaluations
        $totalAssessments = $this->db->count('assessments', $whereClause, $params);
        
        return [
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'activity_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0
            ],
            'courses' => [
                'total' => $totalCourses,
                'active' => $activeCourses,
                'activation_rate' => $totalCourses > 0 ? round(($activeCourses / $totalCourses) * 100, 2) : 0
            ],
            'enrollments' => [
                'total' => $totalEnrollments,
                'new' => $newEnrollments,
                'average_per_course' => $totalCourses > 0 ? round($totalEnrollments / $totalCourses, 2) : 0
            ],
            'assessments' => [
                'total' => $totalAssessments
            ]
        ];
    }
    
    /**
     * Obtenir les statistiques des cours populaires
     */
    public function getPopularCourses($establishmentId = null, $limit = 10) {
        $whereClause = "c.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = [];
        
        if ($establishmentId) {
            $whereClause .= " AND c.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        return $this->db->select(
            "SELECT c.id, c.title, c.category, c.enrollment_count, c.rating,
                    u.first_name as instructor_first_name, u.last_name as instructor_last_name,
                    COUNT(uc.id) as current_enrollments,
                    AVG(uc.progress) as average_progress
             FROM courses c
             LEFT JOIN users u ON c.instructor_id = u.id
             LEFT JOIN user_courses uc ON c.id = uc.course_id
             WHERE {$whereClause}
             GROUP BY c.id, c.title, c.category, c.enrollment_count, c.rating, u.first_name, u.last_name
             ORDER BY c.enrollment_count DESC, c.rating DESC
             LIMIT {$limit}",
            $params
        );
    }
    
    /**
     * Obtenir les activités récentes des utilisateurs
     */
    public function getUserActivities($establishmentId = null, $limit = 20) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "u.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        // Connexions récentes
        $logins = $this->db->select(
            "SELECT u.id, u.first_name, u.last_name, u.last_login_at as activity_date,
                    'login' as activity_type, 'Connexion' as activity_description
             FROM users u
             WHERE {$whereClause} AND u.last_login_at IS NOT NULL
             ORDER BY u.last_login_at DESC
             LIMIT {$limit}",
            $params
        );
        
        // Inscriptions récentes aux cours
        $enrollments = $this->db->select(
            "SELECT u.id, u.first_name, u.last_name, uc.enrolled_at as activity_date,
                    'enrollment' as activity_type, 
                    CONCAT('Inscription au cours: ', c.title) as activity_description
             FROM user_courses uc
             JOIN users u ON uc.user_id = u.id
             JOIN courses c ON uc.course_id = c.id
             WHERE {$whereClause}
             ORDER BY uc.enrolled_at DESC
             LIMIT {$limit}",
            $params
        );
        
        // Cours complétés
        $completions = $this->db->select(
            "SELECT u.id, u.first_name, u.last_name, uc.completed_at as activity_date,
                    'completion' as activity_type,
                    CONCAT('Cours terminé: ', c.title) as activity_description
             FROM user_courses uc
             JOIN users u ON uc.user_id = u.id
             JOIN courses c ON uc.course_id = c.id
             WHERE {$whereClause} AND uc.completed_at IS NOT NULL
             ORDER BY uc.completed_at DESC
             LIMIT {$limit}",
            $params
        );
        
        // Fusionner et trier toutes les activités
        $activities = array_merge($logins, $enrollments, $completions);
        usort($activities, function($a, $b) {
            return strtotime($b['activity_date']) - strtotime($a['activity_date']);
        });
        
        return array_slice($activities, 0, $limit);
    }
    
    /**
     * Obtenir les statistiques d'inscriptions par période
     */
    public function getEnrollmentStats($establishmentId = null, $period = '30days') {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "c.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        // Déterminer la période
        switch ($period) {
            case '7days':
                $dateFormat = IS_POSTGRESQL ? "to_char(uc.enrolled_at, 'YYYY-MM-DD')" : "DATE_FORMAT(uc.enrolled_at, '%Y-%m-%d')";
                $dateFilter = "uc.enrolled_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30days':
                $dateFormat = IS_POSTGRESQL ? "to_char(uc.enrolled_at, 'YYYY-MM-DD')" : "DATE_FORMAT(uc.enrolled_at, '%Y-%m-%d')";
                $dateFilter = "uc.enrolled_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '12months':
                $dateFormat = IS_POSTGRESQL ? "to_char(uc.enrolled_at, 'YYYY-MM')" : "DATE_FORMAT(uc.enrolled_at, '%Y-%m')";
                $dateFilter = "uc.enrolled_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
                break;
            default:
                $dateFormat = IS_POSTGRESQL ? "to_char(uc.enrolled_at, 'YYYY-MM-DD')" : "DATE_FORMAT(uc.enrolled_at, '%Y-%m-%d')";
                $dateFilter = "uc.enrolled_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
        
        if (IS_POSTGRESQL) {
            $dateFilter = str_replace(['DATE_SUB', 'INTERVAL'], ['', ''], $dateFilter);
            $dateFilter = "uc.enrolled_at >= NOW() - INTERVAL '30 days'";
        }
        
        return $this->db->select(
            "SELECT {$dateFormat} as period,
                    COUNT(uc.id) as enrollments,
                    COUNT(DISTINCT uc.user_id) as unique_users,
                    COUNT(DISTINCT uc.course_id) as unique_courses
             FROM user_courses uc
             JOIN courses c ON uc.course_id = c.id
             WHERE {$whereClause} AND {$dateFilter}
             GROUP BY {$dateFormat}
             ORDER BY period DESC",
            $params
        );
    }
    
    /**
     * Obtenir la répartition par catégorie de cours
     */
    public function getCategoryDistribution($establishmentId = null) {
        $whereClause = "is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = [];
        
        if ($establishmentId) {
            $whereClause .= " AND establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        return $this->db->select(
            "SELECT category,
                    COUNT(*) as course_count,
                    SUM(enrollment_count) as total_enrollments,
                    AVG(rating) as average_rating
             FROM courses
             WHERE {$whereClause}
             GROUP BY category
             ORDER BY course_count DESC",
            $params
        );
    }
    
    /**
     * Obtenir les statistiques de progression
     */
    public function getProgressStats($establishmentId = null) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "c.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        $stats = $this->db->selectOne(
            "SELECT COUNT(*) as total_enrollments,
                    COUNT(CASE WHEN uc.progress = 0 THEN 1 END) as not_started,
                    COUNT(CASE WHEN uc.progress > 0 AND uc.progress < 100 THEN 1 END) as in_progress,
                    COUNT(CASE WHEN uc.progress = 100 THEN 1 END) as completed,
                    AVG(uc.progress) as average_progress
             FROM user_courses uc
             JOIN courses c ON uc.course_id = c.id
             WHERE {$whereClause}",
            $params
        );
        
        return [
            'total_enrollments' => (int) $stats['total_enrollments'],
            'not_started' => (int) $stats['not_started'],
            'in_progress' => (int) $stats['in_progress'],
            'completed' => (int) $stats['completed'],
            'average_progress' => round((float) $stats['average_progress'], 2),
            'completion_rate' => $stats['total_enrollments'] > 0 
                ? round(($stats['completed'] / $stats['total_enrollments']) * 100, 2) 
                : 0
        ];
    }
    
    /**
     * Obtenir les performances des instructeurs
     */
    public function getInstructorPerformance($establishmentId = null) {
        $whereClause = "c.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = [];
        
        if ($establishmentId) {
            $whereClause .= " AND c.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        return $this->db->select(
            "SELECT u.id, u.first_name, u.last_name,
                    COUNT(c.id) as course_count,
                    SUM(c.enrollment_count) as total_enrollments,
                    AVG(c.rating) as average_rating,
                    COUNT(CASE WHEN c.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1') . " THEN 1 END) as active_courses
             FROM users u
             JOIN courses c ON u.id = c.instructor_id
             WHERE {$whereClause} AND u.role IN ('formateur', 'admin', 'manager')
             GROUP BY u.id, u.first_name, u.last_name
             HAVING COUNT(c.id) > 0
             ORDER BY total_enrollments DESC, average_rating DESC",
            $params
        );
    }
    
    /**
     * Obtenir les métriques de temps réel
     */
    public function getRealTimeMetrics($establishmentId = null) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        // Utilisateurs connectés aujourd'hui
        $todayLogins = $this->db->count(
            'users',
            $whereClause . " AND DATE(last_login_at) = CURDATE()",
            $params
        );
        
        // Nouvelles inscriptions aujourd'hui
        $todayEnrollments = $this->db->selectOne(
            "SELECT COUNT(uc.id) as count 
             FROM user_courses uc 
             JOIN courses c ON uc.course_id = c.id 
             WHERE " . ($establishmentId ? "c.establishment_id = :establishment_id AND " : "") . "DATE(uc.enrolled_at) = CURDATE()",
            $params
        )['count'] ?? 0;
        
        // Cours complétés aujourd'hui
        $todayCompletions = $this->db->selectOne(
            "SELECT COUNT(uc.id) as count 
             FROM user_courses uc 
             JOIN courses c ON uc.course_id = c.id 
             WHERE " . ($establishmentId ? "c.establishment_id = :establishment_id AND " : "") . "DATE(uc.completed_at) = CURDATE()",
            $params
        )['count'] ?? 0;
        
        return [
            'today_logins' => $todayLogins,
            'today_enrollments' => $todayEnrollments,
            'today_completions' => $todayCompletions,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Exporter les données analytiques
     */
    public function exportAnalytics($establishmentId = null, $format = 'csv') {
        $data = [
            'overview' => $this->getOverview($establishmentId),
            'popular_courses' => $this->getPopularCourses($establishmentId),
            'category_distribution' => $this->getCategoryDistribution($establishmentId),
            'progress_stats' => $this->getProgressStats($establishmentId),
            'instructor_performance' => $this->getInstructorPerformance($establishmentId)
        ];
        
        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            case 'csv':
                // Convertir les données principales en CSV
                $csv = "Métrique,Valeur\n";
                $overview = $data['overview'];
                $csv .= "Utilisateurs totaux,{$overview['users']['total']}\n";
                $csv .= "Utilisateurs actifs,{$overview['users']['active']}\n";
                $csv .= "Cours totaux,{$overview['courses']['total']}\n";
                $csv .= "Cours actifs,{$overview['courses']['active']}\n";
                $csv .= "Inscriptions totales,{$overview['enrollments']['total']}\n";
                return $csv;
                
            default:
                return $data;
        }
    }
}
?>