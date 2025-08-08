<?php
/**
 * Service de gestion des notifications
 */

class NotificationService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Créer une notification
     */
    public function createNotification($data) {
        try {
            $validator = Validator::make($data, [
                'user_id' => 'required|integer',
                'title' => 'required|max:255',
                'message' => 'required',
                'type' => 'in:info,success,warning,error,course,assessment,system',
                'action_url' => 'url'
            ]);
            
            if (!$validator->validate()) {
                throw new ValidationException($validator->getErrors());
            }
            
            $validatedData = $validator->getValidatedData();
            $validatedData['type'] = $validatedData['type'] ?? 'info';
            $validatedData['is_read'] = false;
            
            $notificationId = $this->db->insertWithTimestamps('notifications', $validatedData);
            
            return $this->getNotificationById($notificationId);
            
        } catch (Exception $e) {
            Utils::log("Notification creation error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir une notification par ID
     */
    public function getNotificationById($id) {
        return $this->db->selectOne(
            "SELECT * FROM notifications WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Obtenir les notifications d'un utilisateur
     */
    public function getUserNotifications($userId, $page = 1, $perPage = 20, $unreadOnly = false) {
        $whereClause = "user_id = :user_id";
        $params = ['user_id' => $userId];
        
        if ($unreadOnly) {
            $whereClause .= " AND is_read = " . (IS_POSTGRESQL ? 'FALSE' : '0');
        }
        
        $sql = "SELECT * FROM notifications 
                WHERE {$whereClause} 
                ORDER BY created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($notificationId, $userId) {
        try {
            return $this->db->update(
                'notifications',
                ['is_read' => IS_POSTGRESQL ? true : 1, 'read_at' => date('Y-m-d H:i:s')],
                'id = :id AND user_id = :user_id',
                ['id' => $notificationId, 'user_id' => $userId]
            );
            
        } catch (Exception $e) {
            Utils::log("Notification mark read error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead($userId) {
        try {
            return $this->db->update(
                'notifications',
                ['is_read' => IS_POSTGRESQL ? true : 1, 'read_at' => date('Y-m-d H:i:s')],
                'user_id = :user_id AND is_read = ' . (IS_POSTGRESQL ? 'FALSE' : '0'),
                ['user_id' => $userId]
            );
            
        } catch (Exception $e) {
            Utils::log("Notifications mark all read error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Supprimer une notification
     */
    public function deleteNotification($notificationId, $userId) {
        try {
            return $this->db->delete(
                'notifications',
                'id = :id AND user_id = :user_id',
                ['id' => $notificationId, 'user_id' => $userId]
            );
            
        } catch (Exception $e) {
            Utils::log("Notification deletion error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir le nombre de notifications non lues
     */
    public function getUnreadCount($userId) {
        return $this->db->count(
            'notifications',
            'user_id = :user_id AND is_read = ' . (IS_POSTGRESQL ? 'FALSE' : '0'),
            ['user_id' => $userId]
        );
    }
    
    /**
     * Envoyer une notification à plusieurs utilisateurs
     */
    public function notifyUsers($userIds, $title, $message, $type = 'info', $actionUrl = null) {
        try {
            $this->db->beginTransaction();
            $createdNotifications = [];
            
            foreach ($userIds as $userId) {
                $notificationData = [
                    'user_id' => $userId,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'action_url' => $actionUrl,
                    'is_read' => false
                ];
                
                $notificationId = $this->db->insertWithTimestamps('notifications', $notificationData);
                $createdNotifications[] = $notificationId;
            }
            
            $this->db->commit();
            
            return $createdNotifications;
            
        } catch (Exception $e) {
            $this->db->rollback();
            Utils::log("Bulk notification error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Notifier tous les utilisateurs d'un établissement
     */
    public function notifyEstablishment($establishmentId, $title, $message, $type = 'info', $actionUrl = null, $roles = null) {
        try {
            $whereClause = "establishment_id = :establishment_id AND is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
            $params = ['establishment_id' => $establishmentId];
            
            if ($roles && is_array($roles)) {
                $placeholders = implode(',', array_fill(0, count($roles), '?'));
                $whereClause .= " AND role IN ({$placeholders})";
                $params = array_merge($params, $roles);
            }
            
            $users = $this->db->select(
                "SELECT id FROM users WHERE {$whereClause}",
                $params
            );
            
            $userIds = array_column($users, 'id');
            
            return $this->notifyUsers($userIds, $title, $message, $type, $actionUrl);
            
        } catch (Exception $e) {
            Utils::log("Establishment notification error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Notifications automatiques pour événements système
     */
    public function notifyNewCourseEnrollment($courseId, $userId) {
        $course = $this->db->selectOne("SELECT title FROM courses WHERE id = :id", ['id' => $courseId]);
        
        if ($course) {
            return $this->createNotification([
                'user_id' => $userId,
                'title' => 'Nouvelle inscription',
                'message' => "Vous êtes maintenant inscrit au cours : {$course['title']}",
                'type' => 'course',
                'action_url' => "/courses/{$courseId}"
            ]);
        }
    }
    
    /**
     * Notification de cours terminé
     */
    public function notifyCourseCompletion($courseId, $userId) {
        $course = $this->db->selectOne("SELECT title FROM courses WHERE id = :id", ['id' => $courseId]);
        
        if ($course) {
            return $this->createNotification([
                'user_id' => $userId,
                'title' => 'Cours terminé !',
                'message' => "Félicitations ! Vous avez terminé le cours : {$course['title']}",
                'type' => 'success',
                'action_url' => "/courses/{$courseId}"
            ]);
        }
    }
    
    /**
     * Notification de nouvelle évaluation
     */
    public function notifyNewAssessment($assessmentId, $userIds) {
        $assessment = $this->db->selectOne(
            "SELECT a.title, c.title as course_title 
             FROM assessments a 
             LEFT JOIN courses c ON a.course_id = c.id 
             WHERE a.id = :id",
            ['id' => $assessmentId]
        );
        
        if ($assessment) {
            $message = "Nouvelle évaluation disponible : {$assessment['title']}";
            if ($assessment['course_title']) {
                $message .= " dans le cours {$assessment['course_title']}";
            }
            
            return $this->notifyUsers($userIds, 'Nouvelle évaluation', $message, 'assessment', "/assessments/{$assessmentId}");
        }
    }
    
    /**
     * Notification de groupe d'étude
     */
    public function notifyStudyGroupMessage($groupId, $senderUserId, $message) {
        // Obtenir les membres du groupe sauf l'expéditeur
        $members = $this->db->select(
            "SELECT user_id FROM study_group_members 
             WHERE study_group_id = :group_id AND user_id != :sender_id",
            ['group_id' => $groupId, 'sender_id' => $senderUserId]
        );
        
        $group = $this->db->selectOne("SELECT name FROM study_groups WHERE id = :id", ['id' => $groupId]);
        $sender = $this->db->selectOne("SELECT first_name, last_name FROM users WHERE id = :id", ['id' => $senderUserId]);
        
        if ($group && $sender && !empty($members)) {
            $userIds = array_column($members, 'user_id');
            $senderName = $sender['first_name'] . ' ' . $sender['last_name'];
            
            return $this->notifyUsers(
                $userIds,
                "Message dans {$group['name']}",
                "{$senderName} : " . Utils::truncate($message, 100),
                'info',
                "/study-groups/{$groupId}"
            );
        }
    }
    
    /**
     * Notification de maintenance système
     */
    public function notifySystemMaintenance($message, $scheduledAt = null) {
        // Notifier tous les super admins et admins
        $adminUsers = $this->db->select(
            "SELECT id FROM users WHERE role IN ('super_admin', 'admin') AND is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1')
        );
        
        $userIds = array_column($adminUsers, 'id');
        
        $title = $scheduledAt ? 'Maintenance programmée' : 'Maintenance système';
        $fullMessage = $message;
        if ($scheduledAt) {
            $fullMessage .= " Programmée pour le " . Utils::formatDate($scheduledAt);
        }
        
        return $this->notifyUsers($userIds, $title, $fullMessage, 'warning');
    }
    
    /**
     * Nettoyer les anciennes notifications
     */
    public function cleanupOldNotifications($daysOld = 30) {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
            
            return $this->db->delete(
                'notifications',
                'created_at < :cutoff AND is_read = ' . (IS_POSTGRESQL ? 'TRUE' : '1'),
                ['cutoff' => $cutoffDate]
            );
            
        } catch (Exception $e) {
            Utils::log("Notification cleanup error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les statistiques des notifications
     */
    public function getNotificationStats($userId = null) {
        if ($userId) {
            // Stats pour un utilisateur spécifique
            $stats = $this->db->selectOne(
                "SELECT COUNT(*) as total,
                        COUNT(CASE WHEN is_read = " . (IS_POSTGRESQL ? 'FALSE' : '0') . " THEN 1 END) as unread,
                        COUNT(CASE WHEN is_read = " . (IS_POSTGRESQL ? 'TRUE' : '1') . " THEN 1 END) as read
                 FROM notifications
                 WHERE user_id = :user_id",
                ['user_id' => $userId]
            );
            
            // Types de notifications
            $typeStats = $this->db->select(
                "SELECT type, COUNT(*) as count
                 FROM notifications
                 WHERE user_id = :user_id
                 GROUP BY type
                 ORDER BY count DESC",
                ['user_id' => $userId]
            );
        } else {
            // Stats globales
            $stats = $this->db->selectOne(
                "SELECT COUNT(*) as total,
                        COUNT(CASE WHEN is_read = " . (IS_POSTGRESQL ? 'FALSE' : '0') . " THEN 1 END) as unread,
                        COUNT(CASE WHEN is_read = " . (IS_POSTGRESQL ? 'TRUE' : '1') . " THEN 1 END) as read,
                        COUNT(DISTINCT user_id) as unique_users
                 FROM notifications"
            );
            
            // Types de notifications
            $typeStats = $this->db->select(
                "SELECT type, COUNT(*) as count
                 FROM notifications
                 GROUP BY type
                 ORDER BY count DESC"
            );
        }
        
        return [
            'total' => (int) $stats['total'],
            'unread' => (int) $stats['unread'],
            'read' => (int) $stats['read'],
            'unique_users' => isset($stats['unique_users']) ? (int) $stats['unique_users'] : null,
            'type_distribution' => $typeStats,
            'read_rate' => $stats['total'] > 0 ? round(($stats['read'] / $stats['total']) * 100, 2) : 0
        ];
    }
    
    /**
     * Envoyer des notifications par email (si configuré)
     */
    public function sendEmailNotification($userId, $subject, $message, $actionUrl = null) {
        // Vérifier les préférences utilisateur
        $user = $this->db->selectOne("SELECT email, first_name, last_name FROM users WHERE id = :id", ['id' => $userId]);
        
        if (!$user || !$user['email']) {
            return false;
        }
        
        // Configuration email simple (à améliorer avec une vraie bibliothèque email)
        if (MAIL_HOST && MAIL_FROM) {
            $to = $user['email'];
            $headers = "From: " . MAIL_FROM . "\r\n";
            $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            $htmlMessage = "<html><body>";
            $htmlMessage .= "<h2>{$subject}</h2>";
            $htmlMessage .= "<p>Bonjour {$user['first_name']},</p>";
            $htmlMessage .= "<p>{$message}</p>";
            
            if ($actionUrl) {
                $htmlMessage .= "<p><a href='{$actionUrl}' style='background-color: #8B5CF6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Voir</a></p>";
            }
            
            $htmlMessage .= "<p>Cordialement,<br>L'équipe " . APP_NAME . "</p>";
            $htmlMessage .= "</body></html>";
            
            return mail($to, $subject, $htmlMessage, $headers);
        }
        
        return false;
    }
}
?>