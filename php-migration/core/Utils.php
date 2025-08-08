<?php
/**
 * Utilitaires généraux
 * Fonctions helpers et outils communs
 */

class Utils {
    
    public static function log($message, $level = 'INFO') {
        if (!LOG_ENABLED) return;
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        $logFile = LOG_PATH . '/app.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function redirect($url) {
        header("Location: $url");
        exit;
    }
    
    public static function redirectWithMessage($url, $message, $type = 'info') {
        $_SESSION['flash_message'] = ['text' => $message, 'type' => $type];
        self::redirect($url);
    }
    
    public static function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePassword($password) {
        // Au moins 8 caractères, une majuscule, une minuscule, un chiffre
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
    }
    
    public static function generateSlug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
    
    public static function formatBytes($size, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    public static function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'à l\'instant';
        if ($time < 3600) return floor($time/60) . ' min';
        if ($time < 86400) return floor($time/3600) . ' h';
        if ($time < 2592000) return floor($time/86400) . ' j';
        if ($time < 31536000) return floor($time/2592000) . ' mois';
        
        return floor($time/31536000) . ' ans';
    }
    
    public static function truncateText($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
    
    public static function isValidUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    public static function uploadFile($file, $allowedTypes = null, $maxSize = null) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erreur lors de l\'upload du fichier');
        }
        
        $allowedTypes = $allowedTypes ?: ALLOWED_FILE_TYPES;
        $maxSize = $maxSize ?: UPLOAD_MAX_SIZE;
        
        // Vérifier la taille
        if ($file['size'] > $maxSize) {
            throw new Exception('Fichier trop volumineux (' . self::formatBytes($maxSize) . ' max)');
        }
        
        // Vérifier l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Type de fichier non autorisé');
        }
        
        // Générer un nom unique
        $filename = self::generateRandomString(16) . '.' . $extension;
        $uploadPath = UPLOADS_PATH . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Erreur lors de la sauvegarde du fichier');
        }
        
        return $filename;
    }
    
    public static function deleteFile($filename) {
        $filepath = UPLOADS_PATH . '/' . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
    
    public static function formatPrice($price, $currency = '€') {
        return number_format($price, 2, ',', ' ') . ' ' . $currency;
    }
    
    public static function formatDate($date, $format = 'd/m/Y à H:i') {
        return date($format, strtotime($date));
    }
    
    public static function getBrowserInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Détection simple du navigateur
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        
        return 'Inconnu';
    }
    
    public static function getClientIp() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Prendre la première IP si plusieurs (proxy)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public static function cache($key, $data = null, $expiry = null) {
        if (!CACHE_ENABLED) return $data;
        
        $expiry = $expiry ?: CACHE_LIFETIME;
        $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
        
        if ($data === null) {
            // Lecture du cache
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $expiry) {
                return unserialize(file_get_contents($cacheFile));
            }
            return null;
        } else {
            // Écriture du cache
            file_put_contents($cacheFile, serialize($data), LOCK_EX);
            return $data;
        }
    }
    
    public static function clearCache($pattern = '*') {
        if (!CACHE_ENABLED) return;
        
        $files = glob(CACHE_PATH . '/' . $pattern . '.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    public static function generateOTP($length = 6) {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
    
    public static function isBot() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $bots = ['bot', 'crawl', 'slurp', 'spider', 'facebook', 'twitter'];
        
        foreach ($bots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function rateLimitCheck($identifier, $maxRequests = 60, $windowMinutes = 1) {
        $cacheKey = "rate_limit_$identifier";
        $requests = self::cache($cacheKey) ?: [];
        $now = time();
        $windowStart = $now - ($windowMinutes * 60);
        
        // Nettoyer les anciennes requêtes
        $requests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        if (count($requests) >= $maxRequests) {
            return false; // Rate limit dépassé
        }
        
        // Ajouter la requête actuelle
        $requests[] = $now;
        self::cache($cacheKey, $requests, $windowMinutes * 60);
        
        return true;
    }
    
    public static function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    public static function errorResponse($message, $statusCode = 400) {
        self::jsonResponse(['error' => $message], $statusCode);
    }
    
    public static function successResponse($data = null, $message = 'Success') {
        $response = ['message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        self::jsonResponse($response);
    }
}

// Fonction CSRF token pour compatibility (alias)
function validateCSRFToken($token) {
    return verifyCSRFToken($token);
}
?>