<?php
/**
 * Gestionnaire d'authentification et autorisation
 * Support des rôles hiérarchiques et sessions sécurisées
 */

class Auth {
    private static $currentUser = null;
    
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }
    
    public static function user() {
        if (self::$currentUser === null && self::isAuthenticated()) {
            $db = Database::getInstance();
            self::$currentUser = $db->selectOne('users', '*', 'id = ? AND is_active = 1', [$_SESSION['user_id']]);
        }
        
        return self::$currentUser;
    }
    
    public static function login($email, $password, $establishmentId = null) {
        $db = Database::getInstance();
        
        // Préparer la condition WHERE
        $whereCondition = 'email = ? AND is_active = 1';
        $params = [$email];
        
        if ($establishmentId) {
            $whereCondition .= ' AND establishment_id = ?';
            $params[] = $establishmentId;
        }
        
        $user = $db->selectOne('users', '*', $whereCondition, $params);
        
        if ($user && password_verify($password, $user['password'])) {
            // Créer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['establishment_id'] = $user['establishment_id'];
            $_SESSION['last_activity'] = time();
            
            // Mettre à jour la dernière connexion
            $db->update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            
            self::$currentUser = $user;
            
            Utils::log("User logged in: {$user['email']} (ID: {$user['id']})", 'INFO');
            
            return $user;
        }
        
        Utils::log("Failed login attempt for email: $email", 'WARNING');
        return false;
    }
    
    public static function logout() {
        if (self::isAuthenticated()) {
            $user = self::user();
            if ($user) {
                Utils::log("User logged out: {$user['email']} (ID: {$user['id']})", 'INFO');
            }
        }
        
        // Détruire la session
        session_unset();
        session_destroy();
        self::$currentUser = null;
        
        // Démarrer une nouvelle session
        session_start();
    }
    
    public static function register($data) {
        $db = Database::getInstance();
        
        // Vérifier si l'email existe déjà pour cet établissement
        $existing = $db->selectOne('users', 'id', 'email = ? AND establishment_id = ?', 
            [$data['email'], $data['establishment_id']]);
        
        if ($existing) {
            throw new Exception('Un utilisateur avec cet email existe déjà dans cet établissement');
        }
        
        // Valider l'établissement
        $establishment = $db->selectOne('establishments', 'id', 'id = ? AND is_active = 1', 
            [$data['establishment_id']]);
        
        if (!$establishment) {
            throw new Exception('Établissement invalide ou inactif');
        }
        
        // Préparer les données utilisateur
        $userData = [
            'establishment_id' => $data['establishment_id'],
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
            'role' => $data['role'] ?? 'apprenant',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = $db->insert('users', $userData);
        
        Utils::log("New user registered: {$data['email']} (ID: $userId)", 'INFO');
        
        return $userId;
    }
    
    public static function hasRole($requiredRole) {
        $user = self::user();
        if (!$user) return false;
        
        $userRoleLevel = USER_ROLES[$user['role']] ?? 0;
        $requiredRoleLevel = USER_ROLES[$requiredRole] ?? 0;
        
        return $userRoleLevel >= $requiredRoleLevel;
    }
    
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            Utils::redirectWithMessage('/login', 'Vous devez être connecté pour accéder à cette page', 'error');
            exit;
        }
        
        // Vérifier l'expiration de session
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
            self::logout();
            Utils::redirectWithMessage('/login', 'Votre session a expiré', 'warning');
            exit;
        }
        
        $_SESSION['last_activity'] = time();
    }
    
    public static function requireRole($requiredRole) {
        self::requireAuth();
        
        if (!self::hasRole($requiredRole)) {
            Utils::redirectWithMessage('/dashboard', 'Vous n\'avez pas les permissions nécessaires', 'error');
            exit;
        }
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function generatePasswordResetToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 heure
        
        $db = Database::getInstance();
        $db->update('users', [
            'password_reset_token' => $token,
            'password_reset_expires' => $expiry
        ], 'id = ?', [$userId]);
        
        return $token;
    }
    
    public static function validatePasswordResetToken($token) {
        $db = Database::getInstance();
        return $db->selectOne('users', '*', 
            'password_reset_token = ? AND password_reset_expires > NOW() AND is_active = 1', 
            [$token]);
    }
    
    public static function resetPassword($token, $newPassword) {
        $user = self::validatePasswordResetToken($token);
        if (!$user) {
            throw new Exception('Token de réinitialisation invalide ou expiré');
        }
        
        $db = Database::getInstance();
        $db->update('users', [
            'password' => self::hashPassword($newPassword),
            'password_reset_token' => null,
            'password_reset_expires' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);
        
        Utils::log("Password reset for user: {$user['email']} (ID: {$user['id']})", 'INFO');
        
        return true;
    }
    
    public static function updateProfile($userId, $data) {
        $db = Database::getInstance();
        
        // Valider les données
        $allowedFields = ['first_name', 'last_name', 'email', 'bio', 'avatar', 'timezone', 'language', 'preferences'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            throw new Exception('Aucune donnée valide à mettre à jour');
        }
        
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        $affected = $db->update('users', $updateData, 'id = ?', [$userId]);
        
        if ($affected > 0) {
            // Recharger l'utilisateur actuel si c'est lui qui est modifié
            if (self::isAuthenticated() && $_SESSION['user_id'] == $userId) {
                self::$currentUser = null; // Force reload
            }
            
            Utils::log("User profile updated: ID $userId", 'INFO');
        }
        
        return $affected > 0;
    }
    
    public static function getEstablishment() {
        $user = self::user();
        if (!$user) return null;
        
        $db = Database::getInstance();
        return $db->selectOne('establishments', '*', 'id = ?', [$user['establishment_id']]);
    }
    
    public static function canAccessEstablishment($establishmentId) {
        $user = self::user();
        if (!$user) return false;
        
        // Super admin peut accéder à tous les établissements
        if ($user['role'] === 'super_admin') return true;
        
        // Autres rôles ne peuvent accéder qu'à leur établissement
        return $user['establishment_id'] == $establishmentId;
    }
    
    public static function anonymizeUser($userId) {
        $db = Database::getInstance();
        
        $anonymizedData = [
            'email' => 'anonymized_' . $userId . '@deleted.local',
            'first_name' => 'Utilisateur',
            'last_name' => 'Supprimé',
            'password' => '',
            'is_active' => 0,
            'bio' => null,
            'avatar' => null,
            'preferences' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $affected = $db->update('users', $anonymizedData, 'id = ?', [$userId]);
        
        if ($affected > 0) {
            Utils::log("User anonymized: ID $userId", 'INFO');
        }
        
        return $affected > 0;
    }
}
?>