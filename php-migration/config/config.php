<?php
/**
 * Configuration générale de l'application
 */

// Configuration de l'application
define('APP_NAME', 'StacGateLMS');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', APP_ENV === 'development');

// Configuration de sécurité
define('SESSION_LIFETIME', 24 * 60 * 60); // 24 heures
define('PASSWORD_SALT_ROUNDS', 12);
define('CSRF_TOKEN_NAME', '_token');

// Configuration des chemins
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost:8000');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip']);

// Configuration email (pour notifications)
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'localhost');
define('MAIL_PORT', getenv('MAIL_PORT') ?: 587);
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@stacgatelms.com');

// Configuration des rôles et permissions
define('USER_ROLES', [
    'super_admin' => 5,
    'admin' => 4,
    'manager' => 3,
    'formateur' => 2,
    'apprenant' => 1
]);

// Configuration des thèmes par défaut
define('DEFAULT_THEME_COLORS', [
    'primary' => '#8B5CF6',
    'secondary' => '#A78BFA', 
    'accent' => '#C4B5FD',
    'background' => '#FFFFFF',
    'text' => '#1F2937',
    'glass_bg' => 'rgba(255, 255, 255, 0.1)',
    'glass_border' => 'rgba(255, 255, 255, 0.2)'
]);

// Configuration des limites système
define('MAX_COURSES_PER_ESTABLISHMENT', 1000);
define('MAX_USERS_PER_ESTABLISHMENT', 10000);
define('MAX_FILE_UPLOADS_PER_DAY', 100);
define('API_RATE_LIMIT', 100); // requêtes par minute

// Configuration cache (simple file cache)
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 heure
define('CACHE_PATH', ROOT_PATH . '/cache');

// Configuration logs
define('LOG_ENABLED', true);
define('LOG_PATH', ROOT_PATH . '/logs');
define('LOG_LEVEL', APP_DEBUG ? 'DEBUG' : 'INFO');

// Configuration collaboration temps réel
define('COLLABORATION_ENABLED', true);
define('POLL_INTERVAL', 2); // secondes pour long polling
define('MAX_ROOM_PARTICIPANTS', 50);
define('MESSAGE_HISTORY_LIMIT', 100);

// Timezone
date_default_timezone_set('Europe/Paris');

// Configuration session PHP
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.name', 'stacgate_session');
}

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
if (isset($_SERVER['HTTPS'])) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Gestion des erreurs personnalisées
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    if (LOG_ENABLED) {
        $message = "[" . date('Y-m-d H:i:s') . "] Error: $errstr in $errfile on line $errline\n";
        error_log($message, 3, LOG_PATH . '/error.log');
    }
    
    if (APP_DEBUG) {
        echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:10px;margin:10px;border-radius:5px;'>";
        echo "<strong>Erreur PHP:</strong> $errstr<br>";
        echo "<strong>Fichier:</strong> $errfile<br>";
        echo "<strong>Ligne:</strong> $errline";
        echo "</div>";
    }
    
    return true;
}

set_error_handler('customErrorHandler');

// Fonction utilitaire pour les variables d'environnement
function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// Fonction pour générer un token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Fonction pour vérifier un token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Initialisation des dossiers nécessaires
$requiredDirs = [CACHE_PATH, LOG_PATH, UPLOADS_PATH];
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>