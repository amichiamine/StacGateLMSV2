<?php
/**
 * Classe Utils - Utilitaires généraux
 */

class Utils {
    
    /**
     * Nettoyer et sécuriser les données d'entrée
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Générer un slug à partir d'une chaîne
     */
    public static function generateSlug($text) {
        // Remplacer les caractères accentués
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        
        // Convertir en minuscules
        $text = strtolower($text);
        
        // Remplacer les caractères non alphanumériques par des tirets
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        
        // Supprimer les tirets en début et fin
        $text = trim($text, '-');
        
        return $text;
    }
    
    /**
     * Générer un identifiant unique
     */
    public static function generateId($length = 8) {
        return substr(bin2hex(random_bytes($length)), 0, $length);
    }
    
    /**
     * Formater une date
     */
    public static function formatDate($date, $format = 'd/m/Y H:i') {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }
    
    /**
     * Formater une date relative (il y a X temps)
     */
    public static function timeAgo($date) {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return "À l'instant";
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "Il y a {$minutes} minute" . ($minutes > 1 ? 's' : '');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "Il y a {$hours} heure" . ($hours > 1 ? 's' : '');
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return "Il y a {$days} jour" . ($days > 1 ? 's' : '');
        } else {
            return self::formatDate($date);
        }
    }
    
    /**
     * Formater un nombre
     */
    public static function formatNumber($number, $decimals = 0) {
        return number_format($number, $decimals, ',', ' ');
    }
    
    /**
     * Formater une taille de fichier
     */
    public static function formatFileSize($bytes) {
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Raccourcir un texte
     */
    public static function truncate($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Vérifier si une chaîne contient une autre
     */
    public static function contains($haystack, $needle, $caseSensitive = false) {
        if (!$caseSensitive) {
            $haystack = strtolower($haystack);
            $needle = strtolower($needle);
        }
        
        return strpos($haystack, $needle) !== false;
    }
    
    /**
     * Générer un mot de passe aléatoire
     */
    public static function generatePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
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
     * Obtenir l'adresse IP du client
     */
    public static function getClientIp() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                  'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    /**
     * Obtenir le user agent
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Vérifier si la requête est mobile
     */
    public static function isMobile() {
        $userAgent = self::getUserAgent();
        return preg_match('/Mobile|Android|iPhone|iPad/', $userAgent);
    }
    
    /**
     * Créer un cache simple de fichier
     */
    public static function cache($key, $data = null, $ttl = 3600) {
        $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
        
        // Lire le cache
        if ($data === null) {
            if (!file_exists($cacheFile)) {
                return null;
            }
            
            $cacheData = unserialize(file_get_contents($cacheFile));
            
            if ($cacheData['expires'] < time()) {
                unlink($cacheFile);
                return null;
            }
            
            return $cacheData['data'];
        }
        
        // Écrire le cache
        $cacheData = [
            'data' => $data,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($cacheFile, serialize($cacheData));
        return $data;
    }
    
    /**
     * Supprimer un élément du cache
     */
    public static function forgetCache($key) {
        $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
    
    /**
     * Vider tout le cache
     */
    public static function clearCache() {
        $files = glob(CACHE_PATH . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * Logger un message
     */
    public static function log($message, $level = 'INFO') {
        if (!LOG_ENABLED) {
            return;
        }
        
        $logFile = LOG_PATH . '/' . strtolower($level) . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$level}: {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Redirection avec message flash
     */
    public static function redirectWithMessage($url, $message, $type = 'success') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Obtenir et supprimer le message flash
     */
    public static function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = [
                'text' => $_SESSION['flash_message'],
                'type' => $_SESSION['flash_type'] ?? 'info'
            ];
            
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            
            return $message;
        }
        
        return null;
    }
    
    /**
     * Upload de fichier sécurisé
     */
    public static function uploadFile($file, $destination = 'uploads', $allowedTypes = null) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Aucun fichier uploadé');
        }
        
        $allowedTypes = $allowedTypes ?? ALLOWED_FILE_TYPES;
        $maxSize = UPLOAD_MAX_SIZE;
        
        // Vérifier la taille
        if ($file['size'] > $maxSize) {
            throw new Exception('Fichier trop volumineux');
        }
        
        // Vérifier le type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Type de fichier non autorisé');
        }
        
        // Générer un nom unique
        $filename = self::generateId() . '.' . $extension;
        $uploadPath = UPLOADS_PATH . '/' . $destination;
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $filePath = $uploadPath . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Erreur lors de l\'upload');
        }
        
        return [
            'filename' => $filename,
            'path' => $filePath,
            'url' => BASE_URL . '/uploads/' . $destination . '/' . $filename,
            'size' => $file['size'],
            'type' => $file['type']
        ];
    }
    
    /**
     * Convertir un tableau en CSV
     */
    public static function arrayToCsv($array, $delimiter = ';') {
        $output = fopen('php://temp', 'r+');
        
        if (!empty($array)) {
            // Headers
            fputcsv($output, array_keys($array[0]), $delimiter);
            
            // Données
            foreach ($array as $row) {
                fputcsv($output, $row, $delimiter);
            }
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Générer une couleur hexadécimale aléatoire
     */
    public static function generateRandomColor() {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Vérifier si une couleur est claire ou sombre
     */
    public static function isLightColor($color) {
        $hex = str_replace('#', '', $color);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        
        return $brightness > 155;
    }
}
?>