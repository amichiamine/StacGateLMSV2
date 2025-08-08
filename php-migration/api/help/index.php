<?php
/**
 * API Help - Centre d'aide
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/Utils.php';
require_once '../../core/services/HelpService.php';

// Authentification requise
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

$method = $_SERVER['REQUEST_METHOD'];
$helpService = new HelpService();

try {
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'articles';
            
            switch ($action) {
                case 'articles':
                    // Articles d'aide avec pagination et filtres
                    $page = intval($_GET['page'] ?? 1);
                    $perPage = intval($_GET['per_page'] ?? 20);
                    $category = $_GET['category'] ?? '';
                    $search = $_GET['search'] ?? '';
                    $popular = $_GET['popular'] ?? false;
                    
                    $filters = array_filter([
                        'category' => $category,
                        'search' => $search,
                        'popular' => $popular
                    ]);
                    
                    $result = $helpService->getArticles($establishmentId, $page, $perPage, $filters);
                    echo json_encode($result);
                    break;
                    
                case 'categories':
                    // Liste des catégories disponibles
                    $categories = $helpService->getCategories($establishmentId);
                    echo json_encode($categories);
                    break;
                    
                case 'faq':
                    // FAQ par catégorie
                    $category = $_GET['category'] ?? 'general';
                    $faq = $helpService->getFAQ($establishmentId, $category);
                    echo json_encode($faq);
                    break;
                    
                case 'search':
                    // Recherche dans la base de connaissances
                    $query = $_GET['q'] ?? '';
                    $results = $helpService->searchKnowledgeBase($establishmentId, $query);
                    echo json_encode($results);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action non reconnue']);
                    break;
            }
            break;
            
        case 'POST':
            // Enregistrer une consultation d'article
            $input = json_decode(file_get_contents('php://input'), true);
            $articleId = $input['article_id'] ?? null;
            $helpful = $input['helpful'] ?? null;
            
            if ($articleId) {
                $helpService->trackArticleView($articleId, $currentUser['id'], $helpful);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID article manquant']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    Utils::log("Help API error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>