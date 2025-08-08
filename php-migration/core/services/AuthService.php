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
     * Obtenir les utilisateurs d'un établissement avec pagination
     */
    public function getUsersByEstablishment($establishmentId, $page = 1, $perPage = 20, $filters = []) {
        $whereClause = "u.establishment_id = :establishment_id";
        $params = ['establishment_id' => $establishmentId];
        
        // Filtres
        if (!empty($filters['search'])) {
            $whereClause .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['role'])) {
            $whereClause .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }
        
        if (!empty($filters['status'])) {
            $isActive = $filters['status'] === 'active' ? (IS_POSTGRESQL ? 'TRUE' : '1') : (IS_POSTGRESQL ? 'FALSE' : '0');
            $whereClause .= " AND u.is_active = " . $isActive;
        }
        
        $sql = "SELECT u.*, e.name as establishment_name,
                       COUNT(uc.id) as course_count
                FROM users u 
                LEFT JOIN establishments e ON u.establishment_id = e.id 
                LEFT JOIN user_courses uc ON u.id = uc.user_id
                WHERE {$whereClause} 
                GROUP BY u.id, e.name
                ORDER BY u.created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser($userId, $data) {
        try {
            // Valider les données selon les champs autorisés
            $allowedFields = ['first_name', 'last_name', 'username', 'email', 'role', 'avatar', 'password', 'is_active'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));
            
            if (empty($updateData)) {
                throw new ValidationException(['general' => 'Aucune donnée valide à mettre à jour']);
            }
            
            // Validation spécifique pour email unique si modifié
            if (isset($updateData['email'])) {
                $existingUser = $this->db->selectOne(
                    "SELECT id FROM users WHERE email = :email AND id != :user_id",
                    ['email' => $updateData['email'], 'user_id' => $userId]
                );
                
                if ($existingUser) {
                    throw new ValidationException(['email' => 'Cette adresse email est déjà utilisée']);
                }
            }
            
            // Mettre à jour
            $this->db->update('users', $updateData, 'id = :id', ['id' => $userId]);
            
            // Retourner l'utilisateur mis à jour
            return $this->getUserById($userId);
            
        } catch (Exception $e) {
            Utils::log("User update error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function deleteUser($userId) {
        try {
            // Vérifier que l'utilisateur existe
            $user = $this->getUserById($userId);
            if (!$user) {
                throw new Exception('Utilisateur non trouvé');
            }
            
            // Supprimer (la base gère les cascades)
            $this->db->delete('users', 'id = :id', ['id' => $userId]);
            
            Utils::log("User deleted: {$userId}", 'INFO');
            return true;
            
        } catch (Exception $e) {
            Utils::log("User deletion error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Statistiques des utilisateurs par établissement
     */
    public function getUserStats($establishmentId) {
        try {
            $stats = [];
            
            // Total utilisateurs
            $stats['total'] = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM users WHERE establishment_id = :establishment_id",
                ['establishment_id' => $establishmentId]
            )['count'];
            
            // Par rôle
            $roleStats = $this->db->select(
                "SELECT role, COUNT(*) as count FROM users WHERE establishment_id = :establishment_id GROUP BY role",
                ['establishment_id' => $establishmentId]
            );
            
            $stats['by_role'] = [];
            foreach ($roleStats as $role) {
                $stats['by_role'][$role['role']] = $role['count'];
            }
            
            // Utilisateurs actifs
            $stats['active'] = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM users WHERE establishment_id = :establishment_id AND is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1'),
                ['establishment_id' => $establishmentId]
            )['count'];
            
            // Nouveaux ce mois
            $stats['this_month'] = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM users WHERE establishment_id = :establishment_id AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)",
                ['establishment_id' => $establishmentId]
            )['count'];
            
            return $stats;
            
        } catch (Exception $e) {
            Utils::log("User stats error: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Générer un nom d'utilisateur unique
     */
    private function generateUsername($firstName, $lastName) {
        $baseUsername = strtolower($firstName . '.' . $lastName);
        $baseUsername = preg_replace('/[^a-z0-9.]/', '', $baseUsername);
        
        $username = $baseUsername;
        $counter = 1;
        
        while ($this->db->selectOne("SELECT id FROM users WHERE username = :username", ['username' => $username])) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
}
?>
