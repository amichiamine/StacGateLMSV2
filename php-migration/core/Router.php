<?php
/**
 * Routeur simple pour l'application PHP
 * Gestion des routes GET/POST avec authentification
 */

class Router {
    private $routes = [];
    private $currentRoute = null;
    
    public function __construct() {
        $this->currentRoute = $this->getCurrentRoute();
    }
    
    private function getCurrentRoute() {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        return rtrim($path, '/') ?: '/';
    }
    
    public function get($pattern, $handler, $requireAuth = false) {
        $this->addRoute('GET', $pattern, $handler, $requireAuth);
    }
    
    public function post($pattern, $handler, $requireAuth = false) {
        $this->addRoute('POST', $pattern, $handler, $requireAuth);
    }
    
    public function put($pattern, $handler, $requireAuth = false) {
        $this->addRoute('PUT', $pattern, $handler, $requireAuth);
    }
    
    public function delete($pattern, $handler, $requireAuth = false) {
        $this->addRoute('DELETE', $pattern, $handler, $requireAuth);
    }
    
    private function addRoute($method, $pattern, $handler, $requireAuth) {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'requireAuth' => $requireAuth
        ];
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $found = false;
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchRoute($route['pattern'])) {
                $found = true;
                
                // Vérification de l'authentification si requise
                if ($route['requireAuth'] && !Auth::isAuthenticated()) {
                    Utils::redirectWithMessage('/login', 'Vous devez être connecté pour accéder à cette page', 'error');
                    return;
                }
                
                // Exécution du handler
                $this->executeHandler($route['handler']);
                break;
            }
        }
        
        if (!$found) {
            $this->handle404();
        }
    }
    
    private function matchRoute($pattern) {
        // Support des routes avec paramètres simples
        if ($pattern === $this->currentRoute) {
            return true;
        }
        
        // Support des patterns avec :param
        $pattern = preg_replace('/:[^\/]+/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $this->currentRoute);
    }
    
    private function executeHandler($handler) {
        if (is_string($handler)) {
            // Handler est un fichier
            $filepath = ROOT_PATH . '/' . $handler;
            
            if (file_exists($filepath)) {
                require $filepath;
            } else {
                Utils::log("Handler file not found: $filepath", 'ERROR');
                $this->handle404();
            }
        } elseif (is_callable($handler)) {
            // Handler est une fonction
            call_user_func($handler);
        } else {
            Utils::log("Invalid handler type: " . gettype($handler), 'ERROR');
            $this->handle404();
        }
    }
    
    private function handle404() {
        http_response_code(404);
        
        $pageTitle = "Page non trouvée - StacGateLMS";
        $pageDescription = "La page demandée n'existe pas.";
        
        require_once ROOT_PATH . '/includes/header.php';
        ?>
        
        <div style="padding: 4rem 0; text-align: center; margin-top: 80px;">
            <div class="container">
                <div class="glassmorphism p-8">
                    <div style="font-size: 8rem; margin-bottom: 2rem; opacity: 0.5;">🔍</div>
                    <h1 style="font-size: 3rem; margin-bottom: 1rem;">Page non trouvée</h1>
                    <p style="font-size: 1.2rem; opacity: 0.8; margin-bottom: 2rem;">
                        La page que vous recherchez n'existe pas ou a été déplacée.
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="/" class="glass-button">
                            🏠 Accueil
                        </a>
                        <a href="/portal" class="glass-button glass-button-secondary">
                            🏛️ Portail
                        </a>
                        <?php if (Auth::isAuthenticated()): ?>
                            <a href="/dashboard" class="glass-button glass-button-secondary">
                                📊 Dashboard
                            </a>
                        <?php else: ?>
                            <a href="/login" class="glass-button glass-button-secondary">
                                🔐 Connexion
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        require_once ROOT_PATH . '/includes/footer.php';
    }
    
    public function getRoute() {
        return $this->currentRoute;
    }
    
    public function getRoutes() {
        return $this->routes;
    }
    
    // Utilitaire pour redirection
    public function redirect($url, $permanent = false) {
        $statusCode = $permanent ? 301 : 302;
        http_response_code($statusCode);
        header("Location: $url");
        exit;
    }
    
    // Méthode pour générer des URLs
    public function url($path = '') {
        $baseUrl = rtrim(BASE_URL, '/');
        $path = ltrim($path, '/');
        return $baseUrl . '/' . $path;
    }
    
    // Support API JSON
    public function api($pattern, $handler, $method = 'GET') {
        $this->addRoute($method, '/api' . $pattern, function() use ($handler) {
            header('Content-Type: application/json');
            
            try {
                if (is_callable($handler)) {
                    $result = call_user_func($handler);
                } else {
                    $result = ['error' => 'Handler invalide'];
                }
                
                echo json_encode($result);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        }, false);
    }
    
    // Middleware pour CORS
    public function enableCORS() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
?>