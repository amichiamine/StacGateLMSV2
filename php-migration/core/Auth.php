<?php
/**
 * Classe Auth - Gestion de l'authentification et des sessions
 */

class Auth {
    
    /**
     * Vérifier si l'utilisateur est connecté
     */
    public static function check() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Obtenir l'utilisateur connecté
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        $db = Database::getInstance();
        return $db->selectOne(
            "SELECT u.*, e.name as establishment_name, e.slug as establishment_slug 
             FROM users u 
             LEFT JOIN establishments e ON u.establishment_id = e.id 
             WHERE u.id = :user_id",
            ['user_id' => $_SESSION['user_id']]
        );
    }
    
    /**
     * Obtenir l'ID de l'utilisateur connecté
     */
    public static function id() {
        return self::check() ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Connecter un utilisateur
     */
    public static function login($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['establishment_id'] = $user['establishment_id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        // Régénérer l'ID de session pour sécurité
        session_regenerate_id(true);
        
        // Mettre à jour la dernière connexion
        $db = Database::getInstance();
        $db->update(
            'users',
            ['last_login_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $user['id']]
        );
        
        return true;
    }
    
    /**
     * Déconnecter l'utilisateur
     */
    public static function logout() {
        // Nettoyer toutes les variables de session
        $_SESSION = [];
        
        // Détruire le cookie de session s'il existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Détruire la session
        session_destroy();
        
        return true;
    }
    
    /**
     * Hacher un mot de passe
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }
    
    /**
     * Vérifier un mot de passe
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Tentative de connexion
     */
    public static function attempt($email, $password, $establishmentId = null) {
        $db = Database::getInstance();
        
        // Si pas d'establishment spécifié, chercher dans tous les établissements
        if ($establishmentId) {
            $user = $db->selectOne(
                "SELECT * FROM users WHERE email = :email AND establishment_id = :establishment_id AND is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1'),
                ['email' => $email, 'establishment_id' => $establishmentId]
            );
        } else {
            // Chercher l'utilisateur dans tous les établissements
            $user = $db->selectOne(
                "SELECT * FROM users WHERE email = :email AND is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1'),
                ['email' => $email]
            );
        }
        
        if (!$user) {
            return false;
        }
        
        // Vérifier le mot de passe
        if (!self::verifyPassword($password, $user['password'])) {
            return false;
        }
        
        // Connecter l'utilisateur
        self::login($user);
        
        return $user;
    }
    
    /**
     * Vérifier le rôle de l'utilisateur
     */
    public static function hasRole($requiredRole) {
        $user = self::user();
        if (!$user) {
            return false;
        }
        
        $roleHierarchy = USER_ROLES;
        $userLevel = $roleHierarchy[$user['role']] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Vérifier si l'utilisateur a accès à un établissement
     */
    public static function hasEstablishmentAccess($establishmentId) {
        $user = self::user();
        if (!$user) {
            return false;
        }
        
        // Super admin a accès à tout
        if ($user['role'] === 'super_admin') {
            return true;
        }
        
        return $user['establishment_id'] == $establishmentId;
    }
    
    /**
     * Middleware d'authentification
     */
    public static function requireAuth() {
        if (!self::check()) {
            if (Router::isApi()) {
                Router::jsonError('Non authentifié', 401);
            } else {
                Router::redirect('/login');
            }
        }
    }
    
    /**
     * Middleware de vérification de rôle
     */
    public static function requireRole($role) {
        self::requireAuth();
        
        if (!self::hasRole($role)) {
            if (Router::isApi()) {
                Router::jsonError('Accès refusé', 403);
            } else {
                Router::redirect('/dashboard');
            }
        }
    }
    
    /**
     * Middleware de vérification d'établissement
     */
    public static function requireEstablishment($establishmentId) {
        self::requireAuth();
        
        if (!self::hasEstablishmentAccess($establishmentId)) {
            if (Router::isApi()) {
                Router::jsonError('Accès à cet établissement refusé', 403);
            } else {
                Router::redirect('/dashboard');
            }
        }
    }
    
    /**
     * Générer un token de réinitialisation de mot de passe
     */
    public static function generateResetToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 heure
        
        $db = Database::getInstance();
        
        // Supprimer les anciens tokens
        $db->delete('password_resets', 'user_id = :user_id', ['user_id' => $userId]);
        
        // Insérer le nouveau token
        $db->insert('password_resets', [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiry,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $token;
    }
    
    /**
     * Vérifier un token de réinitialisation
     */
    public static function verifyResetToken($token) {
        $db = Database::getInstance();
        
        return $db->selectOne(
            "SELECT * FROM password_resets 
             WHERE token = :token AND expires_at > :now",
            [
                'token' => $token,
                'now' => date('Y-m-d H:i:s')
            ]
        );
    }
    
    /**
     * Réinitialiser le mot de passe
     */
    public static function resetPassword($token, $newPassword) {
        $reset = self::verifyResetToken($token);
        if (!$reset) {
            return false;
        }
        
        $db = Database::getInstance();
        
        // Mettre à jour le mot de passe
        $hashedPassword = self::hashPassword($newPassword);
        $db->update(
            'users',
            ['password' => $hashedPassword],
            'id = :id',
            ['id' => $reset['user_id']]
        );
        
        // Supprimer le token utilisé
        $db->delete('password_resets', 'token = :token', ['token' => $token]);
        
        return true;
    }
    
    /**
     * Vérifier si la session est expirée
     */
    public static function isSessionExpired() {
        if (!isset($_SESSION['login_time'])) {
            return true;
        }
        
        return (time() - $_SESSION['login_time']) > SESSION_LIFETIME;
    }
    
    /**
     * Rafraîchir la session
     */
    public static function refreshSession() {
        if (self::check()) {
            $_SESSION['login_time'] = time();
            return true;
        }
        return false;
    }
}
?>