<?php
/**
 * Fonctions utilitaires globales
 */

if (!function_exists('env')) {
    /**
     * Récupère une variable d'environnement avec valeur par défaut
     */
    function env(string $key, mixed $default = null): mixed {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        
        // Conversion des valeurs booléennes
        $lower = strtolower($value);
        if (in_array($lower, ['true', '(true)'])) {
            return true;
        }
        if (in_array($lower, ['false', '(false)'])) {
            return false;
        }
        
        // Conversion des valeurs null
        if (in_array($lower, ['null', '(null)'])) {
            return null;
        }
        
        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Récupère une valeur de configuration
     */
    function config(string $key, mixed $default = null): mixed {
        if (defined($key)) {
            return constant($key);
        }
        return env($key, $default);
    }
}

if (!function_exists('loadEnvFile')) {
    /**
     * Charge le fichier .env
     */
    function loadEnvFile(string $path = null): bool {
        $envFile = $path ?: dirname(__DIR__) . '/.env';
        
        if (!file_exists($envFile)) {
            return false;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Ignorer les commentaires
            if (strpos($line, '#') === 0) {
                continue;
            }
            
            // Parser la ligne KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Supprimer les guillemets
                $value = trim($value, '"\'');
                
                // Définir la variable d'environnement
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
        
        return true;
    }
}

// Charger automatiquement le fichier .env si disponible
loadEnvFile();
?>