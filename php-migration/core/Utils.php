<?php
/**
 * Utilitaires généraux
 */

class Utils {
    
    /**
     * Nettoyer et sécuriser les données contre XSS
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Générer un ID unique
     */
    public static function generateId($length = 16) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Générer un mot de passe aléatoire
     */
    public static function generatePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle(str_repeat($chars, $length)), 0, $length);
    }
    
    /**
     * Créer un slug URL-friendly
     */
    public static function generateSlug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
    
    /**
     * Formater une date
     */
    public static function formatDate($date, $format = 'd/m/Y H:i') {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        return $date->format($format);
    }
    
    /**
     * Temps relatif (il y a X)
     */
    public static function timeAgo($date) {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        
        $now = new DateTime();
        $diff = $now->diff($date);
        
        if ($diff->days > 7) {
            return self::formatDate($date, 'd/m/Y');
        } elseif ($diff->days > 0) {
            return "Il y a " . $diff->days . " jour" . ($diff->days > 1 ? "s" : "");
        } elseif ($diff->h > 0) {
            return "Il y a " . $diff->h . " heure" . ($diff->h > 1 ? "s" : "");
        } elseif ($diff->i > 0) {
            return "Il y a " . $diff->i . " minute" . ($diff->i > 1 ? "s" : "");
        } else {
            return "À l'instant";
        }
    }
    
    /**
     * Formater un nombre
     */
    public static function formatNumber($number, $decimals = 0) {
        return number_format($number, $decimals, ',', ' ');
    }
    
    /**
     * Formater la taille d'un fichier
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 1) . ' ' . $units[$pow];
    }
    
    /**
     * Tronquer du texte
     */
    public static function truncate($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }
    
    /**
     * Valider un email
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valider une URL
     */
    public static function isValidUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Rechercher dans un texte
     */
    public static function contains($haystack, $needle, $caseSensitive = false) {
        if (!$caseSensitive) {
            $haystack = strtolower($haystack);
            $needle = strtolower($needle);
        }
        
        return strpos($haystack, $needle) !== false;
    }
    
    /**
     * Obtenir l'IP du client
     */
    public static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * Obtenir le User Agent
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Détecter si c'est un mobile
     */
    public static function isMobile() {
        return preg_match('/(Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone)/', self::getUserAgent());
    }
    
    /**
     * Cache simple
     */
    public static function cache($key, $data = null, $ttl = 3600) {
        $cacheDir = ROOT_PATH . '/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
        
        if ($data === null) {
            // Lecture du cache
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
                return unserialize(file_get_contents($cacheFile));
            }
            return false;
        } else {
            // Écriture du cache
            file_put_contents($cacheFile, serialize($data));
            return $data;
        }
    }
    
    /**
     * Supprimer une entrée du cache
     */
    public static function forgetCache($key) {
        $cacheFile = ROOT_PATH . '/cache/' . md5($key) . '.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
    
    /**
     * Vider tout le cache
     */
    public static function clearCache() {
        $cacheDir = ROOT_PATH . '/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Écrire dans les logs
     */
    public static function log($message, $level = 'INFO') {
        if (!LOG_ENABLED) return;
        
        $logDir = ROOT_PATH . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getClientIp();
        
        $logEntry = "[$timestamp] [$level] [$ip] $message" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Redirection avec message flash
     */
    public static function redirectWithMessage($url, $message, $type = 'info') {
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
        
        header("Location: $url");
        exit;
    }
    
    /**
     * Récupérer le message flash
     */
    public static function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            
            // Expirer après 5 minutes
            if (time() - $message['timestamp'] > 300) {
                return null;
            }
            
            return $message;
        }
        
        return null;
    }
    
    /**
     * Upload sécurisé d'un fichier
     */
    public static function uploadFile($file, $destination, $allowedTypes = null) {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Paramètres de fichier invalides');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erreur d\'upload: ' . $file['error']);
        }
        
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            throw new Exception('Fichier trop volumineux');
        }
        
        $allowedTypes = $allowedTypes ?: ALLOWED_FILE_TYPES;
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Type de fichier non autorisé');
        }
        
        $uploadDir = ROOT_PATH . '/uploads/' . $destination;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = self::generateId() . '.' . $extension;
        $filepath = $uploadDir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Échec de l\'upload');
        }
        
        return $destination . '/' . $filename;
    }
    
    /**
     * Convertir un array en CSV
     */
    public static function arrayToCsv($array, $delimiter = ';') {
        if (empty($array)) return '';
        
        $output = fopen('php://temp', 'r+');
        
        // En-têtes
        fputcsv($output, array_keys($array[0]), $delimiter);
        
        // Données
        foreach ($array as $row) {
            fputcsv($output, $row, $delimiter);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Générer une couleur aléatoire
     */
    public static function generateRandomColor() {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Détecter si une couleur est claire
     */
    public static function isLightColor($color) {
        $hex = ltrim($color, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        return $brightness > 155;
    }
    
    /**
     * Convertir une taille en bytes
     */
    public static function convertToBytes($value) {
        $units = ['B', 'K', 'M', 'G', 'T', 'P'];
        $value = trim($value);
        $last = strtoupper(substr($value, -1));
        $value = (int) substr($value, 0, -1);
        
        switch($last) {
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Formater une durée relative (il y a X temps)
     */
    public static function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'À l\'instant';
        if ($time < 3600) return floor($time/60) . ' min';
        if ($time < 86400) return floor($time/3600) . ' h';
        if ($time < 2592000) return floor($time/86400) . ' j';
        if ($time < 31104000) return floor($time/2592000) . ' mois';
        
        return floor($time/31104000) . ' ans';
    }
    
    /**
     * Vérifier et créer un répertoire s'il n'existe pas
     */
    public static function ensureDirectory($path) {
        if (!is_dir($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }
}

/**
 * Générer un token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valider un token CSRF
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}