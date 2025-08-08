<?php
/**
 * Service de gestion des cours
 */

class CourseService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir tous les cours d'un établissement
     */
    public function getCoursesByEstablishment($establishmentId, $page = 1, $perPage = 20, $filters = []) {
        $whereClause = "c.establishment_id = :establishment_id AND c.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = ['establishment_id' => $establishmentId];
        
        // Filtres
        if (!empty($filters['category'])) {
            $whereClause .= " AND c.category = :category";
            $params['category'] = $filters['category'];
        }
        
        if (!empty($filters['level'])) {
            $whereClause .= " AND c.level = :level";
            $params['level'] = $filters['level'];
        }
        
        if (!empty($filters['is_free'])) {
            $whereClause .= " AND c.is_free = " . (IS_POSTGRESQL ? ':is_free' : ':is_free');
            $params['is_free'] = $filters['is_free'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (c.title LIKE :search OR c.description LIKE :search OR c.tags LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }
        
        $sql = "SELECT c.*, 
                       u.first_name as instructor_first_name, 
                       u.last_name as instructor_last_name,
                       COUNT(uc.id) as enrollment_count
                FROM courses c 
                LEFT JOIN users u ON c.instructor_id = u.id 
                LEFT JOIN user_courses uc ON c.id = uc.course_id
                WHERE {$whereClause} 
                GROUP BY c.id, u.first_name, u.last_name
                ORDER BY c.created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Obtenir un cours par ID
     */
    public function getCourseById($id) {
        return $this->db->selectOne(
            "SELECT c.*, 
                    u.first_name as instructor_first_name, 
                    u.last_name as instructor_last_name,
                    u.avatar as instructor_avatar,
                    e.name as establishment_name
             FROM courses c 
             LEFT JOIN users u ON c.instructor_id = u.id 
             LEFT JOIN establishments e ON c.establishment_id = e.id
             WHERE c.id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Créer un nouveau cours
     */
    public function createCourse($data) {
        try {
            $validator = Validator::make($data, [
                'establishment_id' => 'required|integer',
                'title' => 'required|max:255',
                'description' => 'required',
                'short_description' => 'max:500',
                'category' => 'in:web,design,business,marketing,development,data,photography',
                'type' => 'in:cours,formation,webinaire,tutorial',
                'price' => 'numeric',
                'duration' => 'integer',
                'level' => 'in:debutant,intermediaire,avance',
                'language' => 'max:10',
                'instructor_id' => 'integer'
            ]);
            
            if (!$validator->validate()) {
                throw new ValidationException($validator->getErrors());
            }
            
            $validatedData = $validator->getValidatedData();
            
            // Valeurs par défaut
            $validatedData['category'] = $validatedData['category'] ?? 'web';
            $validatedData['type'] = $validatedData['type'] ?? 'cours';
            $validatedData['price'] = $validatedData['price'] ?? 0.00;
            $validatedData['is_free'] = ($validatedData['price'] == 0);
            $validatedData['duration'] = $validatedData['duration'] ?? 60;
            $validatedData['level'] = $validatedData['level'] ?? 'debutant';
            $validatedData['language'] = $validatedData['language'] ?? 'fr';
            $validatedData['is_public'] = $validatedData['is_public'] ?? true;
            $validatedData['is_active'] = $validatedData['is_active'] ?? true;
            $validatedData['rating'] = 0.00;
            $validatedData['enrollment_count'] = 0;
            
            $courseId = $this->db->insertWithTimestamps('courses', $validatedData);
            
            return $this->getCourseById($courseId);
            
        } catch (Exception $e) {
            Utils::log("Course creation error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Mettre à jour un cours
     */
    public function updateCourse($id, $data) {
        try {
            unset($data['id'], $data['created_at'], $data['enrollment_count']);
            
            // Recalculer is_free si le prix change
            if (isset($data['price'])) {
                $data['is_free'] = ($data['price'] == 0);
            }
            
            $this->db->updateWithTimestamps('courses', $data, 'id = :id', ['id' => $id]);
            
            return $this->getCourseById($id);
            
        } catch (Exception $e) {
            Utils::log("Course update error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Supprimer un cours
     */
    public function deleteCourse($id) {
        try {
            // Vérifier s'il y a des inscriptions
            $enrollmentCount = $this->db->count('user_courses', 'course_id = :id', ['id' => $id]);
            if ($enrollmentCount > 0) {
                throw new Exception("Impossible de supprimer un cours avec des inscriptions");
            }
            
            return $this->db->delete('courses', 'id = :id', ['id' => $id]);
            
        } catch (Exception $e) {
            Utils::log("Course deletion error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Inscrire un utilisateur à un cours
     */
    public function enrollUser($userId, $courseId) {
        try {
            // Vérifier que le cours existe et est actif
            $course = $this->getCourseById($courseId);
            if (!$course || !$course['is_active']) {
                throw new Exception("Cours introuvable ou inactif");
            }
            
            // Vérifier que l'utilisateur n'est pas déjà inscrit
            if ($this->db->exists('user_courses', 'user_id = :user_id AND course_id = :course_id', 
                ['user_id' => $userId, 'course_id' => $courseId])) {
                throw new Exception("Utilisateur déjà inscrit à ce cours");
            }
            
            $this->db->beginTransaction();
            
            // Créer l'inscription
            $enrollmentData = [
                'user_id' => $userId,
                'course_id' => $courseId,
                'progress' => 0.00,
                'enrolled_at' => date('Y-m-d H:i:s')
            ];
            
            $enrollmentId = $this->db->insert('user_courses', $enrollmentData);
            
            // Mettre à jour le compteur d'inscriptions du cours
            $this->db->execute(
                "UPDATE courses SET enrollment_count = enrollment_count + 1 WHERE id = :id",
                ['id' => $courseId]
            );
            
            $this->db->commit();
            
            return $this->db->selectOne(
                "SELECT uc.*, c.title as course_title 
                 FROM user_courses uc 
                 JOIN courses c ON uc.course_id = c.id 
                 WHERE uc.id = :id",
                ['id' => $enrollmentId]
            );
            
        } catch (Exception $e) {
            $this->db->rollback();
            Utils::log("Course enrollment error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Désinscrire un utilisateur d'un cours
     */
    public function unenrollUser($userId, $courseId) {
        try {
            $this->db->beginTransaction();
            
            // Supprimer l'inscription
            $deleted = $this->db->delete(
                'user_courses',
                'user_id = :user_id AND course_id = :course_id',
                ['user_id' => $userId, 'course_id' => $courseId]
            );
            
            if ($deleted > 0) {
                // Mettre à jour le compteur d'inscriptions du cours
                $this->db->execute(
                    "UPDATE courses SET enrollment_count = enrollment_count - 1 WHERE id = :id",
                    ['id' => $courseId]
                );
            }
            
            $this->db->commit();
            
            return $deleted > 0;
            
        } catch (Exception $e) {
            $this->db->rollback();
            Utils::log("Course unenrollment error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les cours d'un utilisateur
     */
    public function getUserCourses($userId, $page = 1, $perPage = 20) {
        $sql = "SELECT c.*, uc.enrolled_at, uc.progress, uc.last_accessed_at, uc.completed_at,
                       u.first_name as instructor_first_name, u.last_name as instructor_last_name
                FROM user_courses uc
                JOIN courses c ON uc.course_id = c.id
                LEFT JOIN users u ON c.instructor_id = u.id
                WHERE uc.user_id = :user_id
                ORDER BY uc.enrolled_at DESC";
        
        return $this->db->paginate($sql, ['user_id' => $userId], $page, $perPage);
    }
    
    /**
     * Obtenir les inscriptions d'un cours
     */
    public function getCourseEnrollments($courseId, $page = 1, $perPage = 20) {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.avatar,
                       uc.enrolled_at, uc.progress, uc.last_accessed_at, uc.completed_at
                FROM user_courses uc
                JOIN users u ON uc.user_id = u.id
                WHERE uc.course_id = :course_id
                ORDER BY uc.enrolled_at DESC";
        
        return $this->db->paginate($sql, ['course_id' => $courseId], $page, $perPage);
    }
    
    /**
     * Mettre à jour la progression d'un utilisateur
     */
    public function updateProgress($userId, $courseId, $progress) {
        try {
            $updateData = [
                'progress' => min(100, max(0, $progress)),
                'last_accessed_at' => date('Y-m-d H:i:s')
            ];
            
            // Si progression complète, marquer comme terminé
            if ($progress >= 100) {
                $updateData['completed_at'] = date('Y-m-d H:i:s');
            }
            
            return $this->db->update(
                'user_courses',
                $updateData,
                'user_id = :user_id AND course_id = :course_id',
                ['user_id' => $userId, 'course_id' => $courseId]
            );
            
        } catch (Exception $e) {
            Utils::log("Progress update error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les cours populaires
     */
    public function getPopularCourses($establishmentId = null, $limit = 10) {
        $whereClause = "c.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = [];
        
        if ($establishmentId) {
            $whereClause .= " AND c.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        return $this->db->select(
            "SELECT c.*, 
                    u.first_name as instructor_first_name, 
                    u.last_name as instructor_last_name
             FROM courses c 
             LEFT JOIN users u ON c.instructor_id = u.id 
             WHERE {$whereClause} 
             ORDER BY c.enrollment_count DESC, c.rating DESC 
             LIMIT {$limit}",
            $params
        );
    }
    
    /**
     * Rechercher des cours
     */
    public function searchCourses($searchTerm, $establishmentId = null, $page = 1, $perPage = 20) {
        $whereClause = "c.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1') . 
                      " AND (c.title LIKE :search OR c.description LIKE :search OR c.tags LIKE :search)";
        $params = ['search' => "%{$searchTerm}%"];
        
        if ($establishmentId) {
            $whereClause .= " AND c.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        $sql = "SELECT c.*, 
                       u.first_name as instructor_first_name, 
                       u.last_name as instructor_last_name
                FROM courses c 
                LEFT JOIN users u ON c.instructor_id = u.id 
                WHERE {$whereClause} 
                ORDER BY c.enrollment_count DESC";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Obtenir les statistiques des cours
     */
    public function getCourseStats($establishmentId = null) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        // Total cours
        $totalCourses = $this->db->count('courses', $whereClause, $params);
        
        // Cours actifs
        $activeCourses = $this->db->count(
            'courses',
            $whereClause . " AND is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1'),
            $params
        );
        
        // Total inscriptions
        $totalEnrollments = $this->db->selectOne(
            "SELECT COUNT(uc.id) as count 
             FROM user_courses uc 
             JOIN courses c ON uc.course_id = c.id 
             WHERE {$whereClause}",
            $params
        )['count'] ?? 0;
        
        // Cours complétés
        $completedCourses = $this->db->selectOne(
            "SELECT COUNT(uc.id) as count 
             FROM user_courses uc 
             JOIN courses c ON uc.course_id = c.id 
             WHERE {$whereClause} AND uc.completed_at IS NOT NULL",
            $params
        )['count'] ?? 0;
        
        return [
            'total_courses' => $totalCourses,
            'active_courses' => $activeCourses,
            'total_enrollments' => $totalEnrollments,
            'completed_courses' => $completedCourses,
            'completion_rate' => $totalEnrollments > 0 ? round(($completedCourses / $totalEnrollments) * 100, 2) : 0
        ];
    }
}
?>