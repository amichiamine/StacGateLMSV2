<?php
/**
 * Service de gestion des évaluations et examens
 */

class AssessmentService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir les évaluations d'un établissement
     */
    public function getAssessmentsByEstablishment($establishmentId, $page = 1, $perPage = 20, $filters = []) {
        $whereClause = "a.establishment_id = :establishment_id AND a.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = ['establishment_id' => $establishmentId];
        
        if (!empty($filters['type'])) {
            $whereClause .= " AND a.type = :type";
            $params['type'] = $filters['type'];
        }
        
        if (!empty($filters['course_id'])) {
            $whereClause .= " AND a.course_id = :course_id";
            $params['course_id'] = $filters['course_id'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (a.title LIKE :search OR a.description LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }
        
        $sql = "SELECT a.*, 
                       c.title as course_title,
                       COUNT(aa.id) as attempt_count
                FROM assessments a
                LEFT JOIN courses c ON a.course_id = c.id
                LEFT JOIN assessment_attempts aa ON a.id = aa.assessment_id
                WHERE {$whereClause}
                GROUP BY a.id, c.title
                ORDER BY a.created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Obtenir une évaluation par ID
     */
    public function getAssessmentById($id) {
        $assessment = $this->db->selectOne(
            "SELECT a.*, c.title as course_title, c.instructor_id
             FROM assessments a
             LEFT JOIN courses c ON a.course_id = c.id
             WHERE a.id = :id",
            ['id' => $id]
        );
        
        if ($assessment && $assessment['questions']) {
            $assessment['questions'] = json_decode($assessment['questions'], true);
        }
        
        return $assessment;
    }
    
    /**
     * Créer une nouvelle évaluation
     */
    public function createAssessment($data) {
        try {
            $validator = Validator::make($data, [
                'establishment_id' => 'required|integer',
                'title' => 'required|max:255',
                'description' => 'max:1000',
                'type' => 'in:quiz,exam,assignment',
                'course_id' => 'integer',
                'time_limit' => 'integer',
                'max_attempts' => 'integer',
                'passing_score' => 'numeric'
            ]);
            
            if (!$validator->validate()) {
                throw new ValidationException($validator->getErrors());
            }
            
            $validatedData = $validator->getValidatedData();
            
            // Valeurs par défaut
            $validatedData['type'] = $validatedData['type'] ?? 'quiz';
            $validatedData['time_limit'] = $validatedData['time_limit'] ?? 60;
            $validatedData['max_attempts'] = $validatedData['max_attempts'] ?? 3;
            $validatedData['passing_score'] = $validatedData['passing_score'] ?? 60.00;
            $validatedData['is_active'] = $validatedData['is_active'] ?? true;
            
            // Traiter les questions
            if (isset($data['questions']) && is_array($data['questions'])) {
                $validatedData['questions'] = json_encode($data['questions']);
            } else {
                $validatedData['questions'] = json_encode([]);
            }
            
            $assessmentId = $this->db->insertWithTimestamps('assessments', $validatedData);
            
            return $this->getAssessmentById($assessmentId);
            
        } catch (Exception $e) {
            Utils::log("Assessment creation error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Mettre à jour une évaluation
     */
    public function updateAssessment($id, $data) {
        try {
            unset($data['id'], $data['created_at']);
            
            // Traiter les questions si fournies
            if (isset($data['questions']) && is_array($data['questions'])) {
                $data['questions'] = json_encode($data['questions']);
            }
            
            $this->db->updateWithTimestamps('assessments', $data, 'id = :id', ['id' => $id]);
            
            return $this->getAssessmentById($id);
            
        } catch (Exception $e) {
            Utils::log("Assessment update error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Supprimer une évaluation
     */
    public function deleteAssessment($id) {
        try {
            // Supprimer les tentatives associées
            $this->db->delete('assessment_attempts', 'assessment_id = :id', ['id' => $id]);
            
            return $this->db->delete('assessments', 'id = :id', ['id' => $id]);
            
        } catch (Exception $e) {
            Utils::log("Assessment deletion error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Démarrer une tentative d'évaluation
     */
    public function startAttempt($assessmentId, $userId) {
        try {
            $assessment = $this->getAssessmentById($assessmentId);
            if (!$assessment || !$assessment['is_active']) {
                throw new Exception("Évaluation introuvable ou inactive");
            }
            
            // Vérifier le nombre de tentatives
            $attemptCount = $this->db->count(
                'assessment_attempts',
                'assessment_id = :assessment_id AND user_id = :user_id',
                ['assessment_id' => $assessmentId, 'user_id' => $userId]
            );
            
            if ($attemptCount >= $assessment['max_attempts']) {
                throw new Exception("Nombre maximum de tentatives atteint");
            }
            
            // Vérifier s'il y a une tentative en cours
            $ongoingAttempt = $this->db->selectOne(
                "SELECT * FROM assessment_attempts 
                 WHERE assessment_id = :assessment_id AND user_id = :user_id 
                 AND completed_at IS NULL 
                 AND started_at > :time_limit",
                [
                    'assessment_id' => $assessmentId,
                    'user_id' => $userId,
                    'time_limit' => date('Y-m-d H:i:s', time() - ($assessment['time_limit'] * 60))
                ]
            );
            
            if ($ongoingAttempt) {
                return $ongoingAttempt;
            }
            
            // Créer une nouvelle tentative
            $attemptData = [
                'assessment_id' => $assessmentId,
                'user_id' => $userId,
                'started_at' => date('Y-m-d H:i:s'),
                'answers' => json_encode([]),
                'score' => 0
            ];
            
            $attemptId = $this->db->insertWithTimestamps('assessment_attempts', $attemptData);
            
            return $this->db->selectOne(
                "SELECT * FROM assessment_attempts WHERE id = :id",
                ['id' => $attemptId]
            );
            
        } catch (Exception $e) {
            Utils::log("Assessment attempt start error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Soumettre les réponses d'une évaluation
     */
    public function submitAnswers($attemptId, $answers) {
        try {
            $attempt = $this->db->selectOne(
                "SELECT aa.*, a.questions, a.time_limit, a.passing_score
                 FROM assessment_attempts aa
                 JOIN assessments a ON aa.assessment_id = a.id
                 WHERE aa.id = :id",
                ['id' => $attemptId]
            );
            
            if (!$attempt) {
                throw new Exception("Tentative introuvable");
            }
            
            if ($attempt['completed_at']) {
                throw new Exception("Cette tentative est déjà terminée");
            }
            
            // Vérifier le temps limite
            $timeElapsed = time() - strtotime($attempt['started_at']);
            if ($timeElapsed > ($attempt['time_limit'] * 60)) {
                throw new Exception("Temps limite dépassé");
            }
            
            // Calculer le score
            $questions = json_decode($attempt['questions'], true);
            $score = $this->calculateScore($questions, $answers);
            
            // Mettre à jour la tentative
            $updateData = [
                'answers' => json_encode($answers),
                'score' => $score,
                'completed_at' => date('Y-m-d H:i:s'),
                'passed' => $score >= $attempt['passing_score']
            ];
            
            $this->db->updateWithTimestamps('assessment_attempts', $updateData, 'id = :id', ['id' => $attemptId]);
            
            return $this->db->selectOne(
                "SELECT * FROM assessment_attempts WHERE id = :id",
                ['id' => $attemptId]
            );
            
        } catch (Exception $e) {
            Utils::log("Assessment submission error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les tentatives d'un utilisateur pour une évaluation
     */
    public function getUserAttempts($assessmentId, $userId) {
        return $this->db->select(
            "SELECT * FROM assessment_attempts 
             WHERE assessment_id = :assessment_id AND user_id = :user_id 
             ORDER BY started_at DESC",
            ['assessment_id' => $assessmentId, 'user_id' => $userId]
        );
    }
    
    /**
     * Obtenir toutes les tentatives d'une évaluation
     */
    public function getAssessmentAttempts($assessmentId, $page = 1, $perPage = 20) {
        $sql = "SELECT aa.*, u.first_name, u.last_name, u.email
                FROM assessment_attempts aa
                JOIN users u ON aa.user_id = u.id
                WHERE aa.assessment_id = :assessment_id
                ORDER BY aa.started_at DESC";
        
        return $this->db->paginate($sql, ['assessment_id' => $assessmentId], $page, $perPage);
    }
    
    /**
     * Calculer le score d'une évaluation
     */
    private function calculateScore($questions, $answers) {
        if (empty($questions)) {
            return 0;
        }
        
        $totalQuestions = count($questions);
        $correctAnswers = 0;
        
        foreach ($questions as $index => $question) {
            $questionId = $question['id'] ?? $index;
            $userAnswer = $answers[$questionId] ?? null;
            
            switch ($question['type']) {
                case 'multiple_choice':
                    if ($userAnswer === $question['correct_answer']) {
                        $correctAnswers++;
                    }
                    break;
                    
                case 'true_false':
                    if ($userAnswer === $question['correct_answer']) {
                        $correctAnswers++;
                    }
                    break;
                    
                case 'text':
                    // Comparaison simple pour les réponses texte
                    $correctText = strtolower(trim($question['correct_answer']));
                    $userText = strtolower(trim($userAnswer));
                    if ($correctText === $userText) {
                        $correctAnswers++;
                    }
                    break;
                    
                case 'multiple_select':
                    $correctOptions = $question['correct_answers'] ?? [];
                    $userOptions = $userAnswer ?? [];
                    if (sort($correctOptions) === sort($userOptions)) {
                        $correctAnswers++;
                    }
                    break;
            }
        }
        
        return round(($correctAnswers / $totalQuestions) * 100, 2);
    }
    
    /**
     * Obtenir les statistiques d'une évaluation
     */
    public function getAssessmentStats($assessmentId) {
        $stats = $this->db->selectOne(
            "SELECT COUNT(*) as total_attempts,
                    COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) as completed_attempts,
                    COUNT(CASE WHEN passed = " . (IS_POSTGRESQL ? 'TRUE' : '1') . " THEN 1 END) as passed_attempts,
                    AVG(score) as average_score,
                    MAX(score) as highest_score,
                    MIN(score) as lowest_score
             FROM assessment_attempts
             WHERE assessment_id = :assessment_id",
            ['assessment_id' => $assessmentId]
        );
        
        return [
            'total_attempts' => (int) $stats['total_attempts'],
            'completed_attempts' => (int) $stats['completed_attempts'],
            'passed_attempts' => (int) $stats['passed_attempts'],
            'average_score' => round((float) $stats['average_score'], 2),
            'highest_score' => (float) $stats['highest_score'],
            'lowest_score' => (float) $stats['lowest_score'],
            'pass_rate' => $stats['completed_attempts'] > 0 
                ? round(($stats['passed_attempts'] / $stats['completed_attempts']) * 100, 2) 
                : 0
        ];
    }
    
    /**
     * Obtenir les statistiques générales des évaluations
     */
    public function getGeneralStats($establishmentId = null) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "a.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        $stats = $this->db->selectOne(
            "SELECT COUNT(DISTINCT a.id) as total_assessments,
                    COUNT(DISTINCT aa.id) as total_attempts,
                    COUNT(CASE WHEN aa.passed = " . (IS_POSTGRESQL ? 'TRUE' : '1') . " THEN 1 END) as passed_attempts,
                    AVG(aa.score) as average_score
             FROM assessments a
             LEFT JOIN assessment_attempts aa ON a.id = aa.assessment_id
             WHERE {$whereClause}",
            $params
        );
        
        return [
            'total_assessments' => (int) $stats['total_assessments'],
            'total_attempts' => (int) $stats['total_attempts'],
            'passed_attempts' => (int) $stats['passed_attempts'],
            'average_score' => round((float) $stats['average_score'], 2),
            'overall_pass_rate' => $stats['total_attempts'] > 0 
                ? round(($stats['passed_attempts'] / $stats['total_attempts']) * 100, 2) 
                : 0
        ];
    }
}
?>