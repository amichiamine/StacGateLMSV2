<?php
/**
 * Service d'authentification
 */

class AuthService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authentifier un utilisateur
     */
    public function authenticate($email, $password, $establishmentId = null) {
        try {
            // Rechercher l'utilisateur
            $whereClause = "email = :email AND is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
            $params = ['email' => $email];
            
            if ($establishmentId) {
                $whereClause .= " AND establishment_id = :establishment_id";
                $params['establishment_id'] = $establishmentId;
            }
            
            $user = $this->db->selectOne(
                "SELECT u.*, e.name as establishment_name, e.slug as establishment_slug 
                 FROM users u 
                 LEFT JOIN establishments e ON u.establishment_id = e.id 
                 WHERE {$whereClause}",
                $params
            );
            
            if (!$user) {
                return false;
            }
            
            // Vérifier le mot de passe
            if (!Auth::verifyPassword($password, $user['password'])) {
                return false;
            }
            
            // Connecter l'utilisateur
            Auth::login($user);
            
            return $user;
        } catch (Exception $e) {
            Utils::log("Authentication error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Créer un nouvel utilisateur
     */
    public function createUser($userData) {
        try {
            // Valider les données
            $validator = Validator::make($userData, [
                'first_name' => 'required|max:100',
                'last_name' => 'required|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
                'establishment_id' => 'required|integer',
                'role' => 'in:super_admin,admin,manager,formateur,apprenant'
            ]);
            
            if (!$validator->validate()) {
                throw new ValidationException($validator->getErrors());
            }
            
            $validatedData = $validator->getValidatedData();
            
            // Hacher le mot de passe
            $validatedData['password'] = Auth::hashPassword($validatedData['password']);
            $validatedData['role'] = $validatedData['role'] ?? 'apprenant';
            
            // Générer username si pas fourni
            if (empty($validatedData['username'])) {
                $validatedData['username'] = $this->generateUsername($validatedData['first_name'], $validatedData['last_name']);
            }
            
            // Insérer l'utilisateur
            $userId = $this->db->insertWithTimestamps('users', $validatedData);
            
            // Retourner l'utilisateur créé
            return $this->getUserById($userId);
            
        } catch (Exception $e) {
            Utils::log("User creation error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir un utilisateur par ID
     */
    public function getUserById($id) {
        return $this->db->selectOne(
            "SELECT u.*, e.name as establishment_name, e.slug as establishment_slug 
             FROM users u 
             LEFT JOIN establishments e ON u.establishment_id = e.id 
             WHERE u.id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Obtenir un utilisateur par email et établissement
     */
    public function getUserByEmail($email, $establishmentId) {
        return $this->db->selectOne(
            "SELECT u.*, e.name as establishment_name, e.slug as establishment_slug 
             FROM users u 
             LEFT JOIN establishments e ON u.establishment_id = e.id 
             WHERE u.email = :email AND u.establishment_id = :establishment_id",
            ['email' => $email, 'establishment_id' => $establishmentId]
        );
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser($id, $updateData) {
        try {
            // Enlever les champs qui ne peuvent pas être mis à jour
            unset($updateData['id'], $updateData['created_at']);
            
            // Hacher le mot de passe si fourni
            if (isset($updateData['password'])) {
                $updateData['password'] = Auth::hashPassword($updateData['password']);
            }
            
            $this->db->updateWithTimestamps('users', $updateData, 'id = :id', ['id' => $id]);
            
            return $this->getUserById($id);
            
        } catch (Exception $e) {
            Utils::log("User update error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function deleteUser($id) {
        try {
            // Vérifier que l'utilisateur existe
            $user = $this->getUserById($id);
            if (!$user) {
                throw new Exception("Utilisateur introuvable");
            }
            
            // Empêcher la suppression du dernier super admin
            if ($user['role'] === 'super_admin') {
                $superAdminCount = $this->db->count('users', 'role = :role', ['role' => 'super_admin']);
                if ($superAdminCount <= 1) {
                    throw new Exception("Impossible de supprimer le dernier super administrateur");
                }
            }
            
            return $this->db->delete('users', 'id = :id', ['id' => $id]);
            
        } catch (Exception $e) {
            Utils::log("User deletion error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les utilisateurs d'un établissement
     */
    public function getUsersByEstablishment($establishmentId, $page = 1, $perPage = 20, $search = null) {
        $whereClause = "u.establishment_id = :establishment_id";
        $params = ['establishment_id' => $establishmentId];
        
        if ($search) {
            $whereClause .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        $sql = "SELECT u.*, e.name as establishment_name, e.slug as establishment_slug 
                FROM users u 
                LEFT JOIN establishments e ON u.establishment_id = e.id 
                WHERE {$whereClause} 
                ORDER BY u.created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Obtenir tous les utilisateurs (super admin seulement)
     */
    public function getAllUsers($page = 1, $perPage = 20, $search = null, $role = null) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($search) {
            $whereClause .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if ($role) {
            $whereClause .= " AND u.role = :role";
            $params['role'] = $role;
        }
        
        $sql = "SELECT u.*, e.name as establishment_name, e.slug as establishment_slug 
                FROM users u 
                LEFT JOIN establishments e ON u.establishment_id = e.id 
                WHERE {$whereClause} 
                ORDER BY u.created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Vérifier les permissions d'un utilisateur
     */
    public function hasPermission($userId, $requiredRole) {
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }
        
        $roleHierarchy = USER_ROLES;
        $userLevel = $roleHierarchy[$user['role']] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Changer le mot de passe d'un utilisateur
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                throw new Exception("Utilisateur introuvable");
            }
            
            // Vérifier le mot de passe actuel
            if (!Auth::verifyPassword($currentPassword, $user['password'])) {
                throw new Exception("Mot de passe actuel incorrect");
            }
            
            // Valider le nouveau mot de passe
            $validator = Validator::make(['password' => $newPassword], [
                'password' => 'required|min:8'
            ]);
            
            if (!$validator->validate()) {
                throw new ValidationException($validator->getErrors());
            }
            
            // Mettre à jour le mot de passe
            $this->updateUser($userId, ['password' => $newPassword]);
            
            return true;
            
        } catch (Exception $e) {
            Utils::log("Password change error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Générer un nom d'utilisateur unique
     */
    private function generateUsername($firstName, $lastName) {
        $baseUsername = strtolower(Utils::generateSlug($firstName . '.' . $lastName));
        $username = $baseUsername;
        $counter = 1;
        
        while ($this->db->exists('users', 'username = :username', ['username' => $username])) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Obtenir les statistiques des utilisateurs
     */
    public function getUserStats($establishmentId = null) {
        $whereClause = "1 = 1";
        $params = [];
        
        if ($establishmentId) {
            $whereClause = "establishment_id = :establishment_id";
            $params['establishment_id'] = $establishmentId;
        }
        
        // Total utilisateurs
        $totalUsers = $this->db->count('users', $whereClause, $params);
        
        // Utilisateurs actifs (connectés dans les 30 derniers jours)
        $activeUsers = $this->db->count(
            'users',
            $whereClause . " AND last_login_at > :date",
            array_merge($params, ['date' => date('Y-m-d H:i:s', strtotime('-30 days'))])
        );
        
        // Nouveaux utilisateurs ce mois
        $newUsers = $this->db->count(
            'users',
            $whereClause . " AND created_at > :date",
            array_merge($params, ['date' => date('Y-m-d H:i:s', strtotime('-1 month'))])
        );
        
        // Répartition par rôle
        $roleStats = $this->db->select(
            "SELECT role, COUNT(*) as count 
             FROM users 
             WHERE {$whereClause} 
             GROUP BY role",
            $params
        );
        
        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'new_users' => $newUsers,
            'role_distribution' => $roleStats
        ];
    }
}
?>