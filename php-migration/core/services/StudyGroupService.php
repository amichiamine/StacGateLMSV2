<?php
/**
 * Service de gestion des groupes d'étude
 */

class StudyGroupService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir les groupes d'étude d'un établissement
     */
    public function getStudyGroupsByEstablishment($establishmentId, $page = 1, $perPage = 20, $filters = []) {
        $whereClause = "sg.establishment_id = :establishment_id AND sg.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = ['establishment_id' => $establishmentId];
        
        if (!empty($filters['course_id'])) {
            $whereClause .= " AND sg.course_id = :course_id";
            $params['course_id'] = $filters['course_id'];
        }
        
        if (!empty($filters['is_public'])) {
            $whereClause .= " AND sg.is_public = " . (IS_POSTGRESQL ? ':is_public' : ':is_public');
            $params['is_public'] = $filters['is_public'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (sg.name LIKE :search OR sg.description LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }
        
        $sql = "SELECT sg.*, 
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
                ORDER BY sg.created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Obtenir un groupe d'étude par ID
     */
    public function getStudyGroupById($id) {
        return $this->db->selectOne(
            "SELECT sg.*, 
                    c.title as course_title,
                    u.first_name as creator_first_name, 
                    u.last_name as creator_last_name,
                    COUNT(sgm.id) as member_count
             FROM study_groups sg
             LEFT JOIN courses c ON sg.course_id = c.id
             LEFT JOIN users u ON sg.creator_id = u.id
             LEFT JOIN study_group_members sgm ON sg.id = sgm.study_group_id
             WHERE sg.id = :id
             GROUP BY sg.id, c.title, u.first_name, u.last_name",
            ['id' => $id]
        );
    }
    
    /**
     * Créer un nouveau groupe d'étude
     */
    public function createStudyGroup($data) {
        try {
            $validator = Validator::make($data, [
                'establishment_id' => 'required|integer',
                'name' => 'required|max:255',
                'description' => 'max:1000',
                'course_id' => 'integer',
                'creator_id' => 'required|integer',
                'max_members' => 'integer'
            ]);
            
            if (!$validator->validate()) {
                throw new ValidationException($validator->getErrors());
            }
            
            $validatedData = $validator->getValidatedData();
            
            // Valeurs par défaut
            $validatedData['max_members'] = $validatedData['max_members'] ?? 20;
            $validatedData['is_public'] = $validatedData['is_public'] ?? true;
            $validatedData['is_active'] = $validatedData['is_active'] ?? true;
            
            $this->db->beginTransaction();
            
            // Créer le groupe
            $groupId = $this->db->insertWithTimestamps('study_groups', $validatedData);
            
            // Ajouter le créateur comme membre avec le rôle de modérateur
            $memberData = [
                'study_group_id' => $groupId,
                'user_id' => $validatedData['creator_id'],
                'role' => 'moderator',
                'joined_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('study_group_members', $memberData);
            
            $this->db->commit();
            
            return $this->getStudyGroupById($groupId);
            
        } catch (Exception $e) {
            $this->db->rollback();
            Utils::log("Study group creation error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Mettre à jour un groupe d'étude
     */
    public function updateStudyGroup($id, $data) {
        try {
            unset($data['id'], $data['created_at'], $data['creator_id']);
            
            $this->db->updateWithTimestamps('study_groups', $data, 'id = :id', ['id' => $id]);
            
            return $this->getStudyGroupById($id);
            
        } catch (Exception $e) {
            Utils::log("Study group update error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Supprimer un groupe d'étude
     */
    public function deleteStudyGroup($id) {
        try {
            $this->db->beginTransaction();
            
            // Supprimer les messages du groupe
            $this->db->delete('study_group_messages', 'study_group_id = :id', ['id' => $id]);
            
            // Supprimer les membres du groupe
            $this->db->delete('study_group_members', 'study_group_id = :id', ['id' => $id]);
            
            // Supprimer le groupe
            $result = $this->db->delete('study_groups', 'id = :id', ['id' => $id]);
            
            $this->db->commit();
            
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollback();
            Utils::log("Study group deletion error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Rejoindre un groupe d'étude
     */
    public function joinGroup($groupId, $userId) {
        try {
            $group = $this->getStudyGroupById($groupId);
            if (!$group || !$group['is_active']) {
                throw new Exception("Groupe introuvable ou inactif");
            }
            
            // Vérifier si l'utilisateur est déjà membre
            if ($this->db->exists('study_group_members', 'study_group_id = :group_id AND user_id = :user_id', 
                ['group_id' => $groupId, 'user_id' => $userId])) {
                throw new Exception("Utilisateur déjà membre du groupe");
            }
            
            // Vérifier la limite de membres
            if ($group['member_count'] >= $group['max_members']) {
                throw new Exception("Groupe complet");
            }
            
            // Ajouter le membre
            $memberData = [
                'study_group_id' => $groupId,
                'user_id' => $userId,
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s')
            ];
            
            $memberId = $this->db->insert('study_group_members', $memberData);
            
            return $this->db->selectOne(
                "SELECT sgm.*, u.first_name, u.last_name, u.avatar
                 FROM study_group_members sgm
                 JOIN users u ON sgm.user_id = u.id
                 WHERE sgm.id = :id",
                ['id' => $memberId]
            );
            
        } catch (Exception $e) {
            Utils::log("Study group join error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Quitter un groupe d'étude
     */
    public function leaveGroup($groupId, $userId) {
        try {
            $group = $this->getStudyGroupById($groupId);
            if (!$group) {
                throw new Exception("Groupe introuvable");
            }
            
            // Empêcher le créateur de quitter son propre groupe
            if ($group['creator_id'] == $userId) {
                throw new Exception("Le créateur du groupe ne peut pas le quitter");
            }
            
            return $this->db->delete(
                'study_group_members',
                'study_group_id = :group_id AND user_id = :user_id',
                ['group_id' => $groupId, 'user_id' => $userId]
            );
            
        } catch (Exception $e) {
            Utils::log("Study group leave error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les membres d'un groupe
     */
    public function getGroupMembers($groupId, $page = 1, $perPage = 20) {
        $sql = "SELECT sgm.*, u.first_name, u.last_name, u.email, u.avatar, u.role as user_role
                FROM study_group_members sgm
                JOIN users u ON sgm.user_id = u.id
                WHERE sgm.study_group_id = :group_id
                ORDER BY sgm.joined_at ASC";
        
        return $this->db->paginate($sql, ['group_id' => $groupId], $page, $perPage);
    }
    
    /**
     * Envoyer un message dans un groupe
     */
    public function sendMessage($groupId, $userId, $message) {
        try {
            // Vérifier que l'utilisateur est membre du groupe
            if (!$this->db->exists('study_group_members', 'study_group_id = :group_id AND user_id = :user_id', 
                ['group_id' => $groupId, 'user_id' => $userId])) {
                throw new Exception("Utilisateur non membre du groupe");
            }
            
            $validator = Validator::make(['message' => $message], [
                'message' => 'required|max:1000'
            ]);
            
            if (!$validator->validate()) {
                throw new ValidationException($validator->getErrors());
            }
            
            $messageData = [
                'study_group_id' => $groupId,
                'user_id' => $userId,
                'message' => $message,
                'sent_at' => date('Y-m-d H:i:s')
            ];
            
            $messageId = $this->db->insert('study_group_messages', $messageData);
            
            return $this->db->selectOne(
                "SELECT sgm.*, u.first_name, u.last_name, u.avatar
                 FROM study_group_messages sgm
                 JOIN users u ON sgm.user_id = u.id
                 WHERE sgm.id = :id",
                ['id' => $messageId]
            );
            
        } catch (Exception $e) {
            Utils::log("Study group message error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les messages d'un groupe
     */
    public function getGroupMessages($groupId, $page = 1, $perPage = 50) {
        $sql = "SELECT sgm.*, u.first_name, u.last_name, u.avatar
                FROM study_group_messages sgm
                JOIN users u ON sgm.user_id = u.id
                WHERE sgm.study_group_id = :group_id
                ORDER BY sgm.sent_at DESC";
        
        $result = $this->db->paginate($sql, ['group_id' => $groupId], $page, $perPage);
        
        // Inverser l'ordre pour avoir les messages les plus récents en bas
        $result['data'] = array_reverse($result['data']);
        
        return $result;
    }
    
    /**
     * Obtenir les groupes d'un utilisateur
     */
    public function getUserGroups($userId, $page = 1, $perPage = 20) {
        $sql = "SELECT sg.*, 
                       c.title as course_title,
                       sgm.role as user_role,
                       sgm.joined_at,
                       COUNT(sgm2.id) as member_count
                FROM study_group_members sgm
                JOIN study_groups sg ON sgm.study_group_id = sg.id
                LEFT JOIN courses c ON sg.course_id = c.id
                LEFT JOIN study_group_members sgm2 ON sg.id = sgm2.study_group_id
                WHERE sgm.user_id = :user_id AND sg.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1') . "
                GROUP BY sg.id, c.title, sgm.role, sgm.joined_at
                ORDER BY sgm.joined_at DESC";
        
        return $this->db->paginate($sql, ['user_id' => $userId], $page, $perPage);
    }
    
    /**
     * Promouvoir un membre en modérateur
     */
    public function promoteToModerator($groupId, $userId, $promoterId) {
        try {
            // Vérifier que le promoteur est modérateur ou créateur
            $promoter = $this->db->selectOne(
                "SELECT sgm.role, sg.creator_id
                 FROM study_group_members sgm
                 JOIN study_groups sg ON sgm.study_group_id = sg.id
                 WHERE sgm.study_group_id = :group_id AND sgm.user_id = :user_id",
                ['group_id' => $groupId, 'user_id' => $promoterId]
            );
            
            if (!$promoter || ($promoter['role'] !== 'moderator' && $promoter['creator_id'] != $promoterId)) {
                throw new Exception("Permissions insuffisantes");
            }
            
            return $this->db->update(
                'study_group_members',
                ['role' => 'moderator'],
                'study_group_id = :group_id AND user_id = :user_id',
                ['group_id' => $groupId, 'user_id' => $userId]
            );
            
        } catch (Exception $e) {
            Utils::log("Study group promotion error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les statistiques des groupes d'étude
     */
    public function getStudyGroupStats($establishmentId = null) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "sg.establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        $stats = $this->db->selectOne(
            "SELECT COUNT(DISTINCT sg.id) as total_groups,
                    COUNT(DISTINCT sgm.user_id) as total_members,
                    COUNT(DISTINCT sgmsg.id) as total_messages,
                    COUNT(CASE WHEN sg.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1') . " THEN 1 END) as active_groups
             FROM study_groups sg
             LEFT JOIN study_group_members sgm ON sg.id = sgm.study_group_id
             LEFT JOIN study_group_messages sgmsg ON sg.id = sgmsg.study_group_id
             WHERE {$whereClause}",
            $params
        );
        
        return [
            'total_groups' => (int) $stats['total_groups'],
            'active_groups' => (int) $stats['active_groups'],
            'total_members' => (int) $stats['total_members'],
            'total_messages' => (int) $stats['total_messages'],
            'average_members_per_group' => $stats['total_groups'] > 0 
                ? round($stats['total_members'] / $stats['total_groups'], 2) 
                : 0
        ];
    }
}
?>