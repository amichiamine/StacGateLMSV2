<?php
/**
 * Classe Router - Gestionnaire de routage simple
 */

class Router {
    private $routes = [];
    private $params = [];
    
    /**
     * Ajouter une route GET
     */
    public function get($path, $handler, $requireAuth = false) {
        $this->addRoute('GET', $path, $handler, $requireAuth);
    }
    
    /**
     * Ajouter une route POST
     */
    public function post($path, $handler, $requireAuth = false) {
        $this->addRoute('POST', $path, $handler, $requireAuth);
    }
    
    /**
     * Ajouter une route PUT
     */
    public function put($path, $handler, $requireAuth = false) {
        $this->addRoute('PUT', $path, $handler, $requireAuth);
    }
    
    /**
     * Ajouter une route DELETE
     */
    public function delete($path, $handler, $requireAuth = false) {
        $this->addRoute('DELETE', $path, $handler, $requireAuth);
    }
    
    /**
     * Ajouter une route à la liste
     */
    private function addRoute($method, $path, $handler, $requireAuth) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'require_auth' => $requireAuth,
            'pattern' => $this->createPattern($path)
        ];
    }
    
    /**
     * Créer un pattern regex à partir du chemin
     */
    private function createPattern($path) {
        // Remplacer les paramètres {param} par des groupes de capture
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $path);
        $pattern = str_replace('/', '\/', $pattern);
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Traiter la requête actuelle
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Gérer les méthodes HTTP simulées via _method
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                // Extraire les paramètres de l'URL
                $this->extractParams($route['path'], $matches);
                
                // Vérifier l'authentification si requise
                if ($route['require_auth'] && !Auth::check()) {
                    if (strpos($path, '/api/') === 0) {
                        http_response_code(401);
                        header('Content-Type: application/json');
                        echo json_encode(['error' => 'Non authentifié']);
                        return;
                    } else {
                        header('Location: /login');
                        return;
                    }
                }
                
                // Inclure le fichier handler
                $this->executeHandler($route['handler']);
                return;
            }
        }
        
        // Aucune route trouvée - 404
        $this->handle404();
    }
    
    /**
     * Extraire les paramètres de l'URL
     */
    private function extractParams($routePath, $matches) {
        $pathParts = explode('/', $routePath);
        $this->params = [];
        
        $matchIndex = 1; // Skip the full match
        foreach ($pathParts as $part) {
            if (preg_match('/\{([a-zA-Z0-9_]+)\}/', $part, $paramMatch)) {
                $paramName = $paramMatch[1];
                if (isset($matches[$matchIndex])) {
                    $this->params[$paramName] = $matches[$matchIndex];
                    $_GET[$paramName] = $matches[$matchIndex]; // Pour compatibilité
                }
                $matchIndex++;
            }
        }
    }
    
    /**
     * Exécuter le handler de route
     */
    private function executeHandler($handler) {
        $filePath = ROOT_PATH . '/' . $handler;
        
        if (file_exists($filePath)) {
            // Variables disponibles dans les handlers
            $params = $this->params;
            
            include $filePath;
        } else {
            error_log("Handler file not found: " . $filePath);
            $this->handle404();
        }
    }
    
    /**
     * Gérer les erreurs 404
     */
    private function handle404() {
        http_response_code(404);
        
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, '/api/') === 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Endpoint non trouvé']);
        } else {
            include ROOT_PATH . '/pages/not-found.php';
        }
    }
    
    /**
     * Obtenir un paramètre de route
     */
    public function getParam($name, $default = null) {
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }
    
    /**
     * Obtenir tous les paramètres
     */
    public function getParams() {
        return $this->params;
    }
    
    /**
     * Redirection
     */
    public static function redirect($path, $code = 302) {
        http_response_code($code);
        header("Location: " . $path);
        exit;
    }
    
    /**
     * Générer une URL avec paramètres
     */
    public static function url($path, $params = []) {
        $url = BASE_URL . $path;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * Vérifier si la requête est AJAX/API
     */
    public static function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Vérifier si la requête est pour l'API
     */
    public static function isApi() {
        return strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
    }
    
    /**
     * Obtenir les données JSON du body de la requête
     */
    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }
    
    /**
     * Envoyer une réponse JSON
     */
    public static function jsonResponse($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Envoyer une réponse d'erreur JSON
     */
    public static function jsonError($message, $code = 400, $details = null) {
        $response = ['error' => $message];
        if ($details) {
            $response['details'] = $details;
        }
        
        self::jsonResponse($response, $code);
    }
    
    /**
     * Middleware pour CORS (si nécessaire)
     */
    public static function enableCors() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
?>