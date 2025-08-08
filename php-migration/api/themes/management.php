<?php
header('Content-Type: application/json');
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/services/ThemeService.php';

$database = new Database();
$auth = new Auth($database);

// Vérifier l'authentification
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$user = $auth->getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];
$themeService = new ThemeService($database);

// Vérifier les permissions admin
if (!in_array($user['role'], ['super_admin', 'admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes']);
    exit;
}

try {
    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? '';

            switch ($action) {
                case 'create':
                    $theme = $themeService->createCustomTheme(
                        $user['establishment_id'],
                        $data['theme_data'],
                        $user['id']
                    );
                    echo json_encode($theme);
                    break;

                case 'activate':
                    $result = $themeService->activateTheme(
                        $data['theme_id'],
                        $user['establishment_id']
                    );
                    echo json_encode(['success' => $result]);
                    break;

                case 'duplicate':
                    $theme = $themeService->duplicateTheme(
                        $data['theme_id'],
                        $data['new_name'],
                        $user['establishment_id'],
                        $user['id']
                    );
                    echo json_encode($theme);
                    break;

                case 'import':
                    $theme = $themeService->importTheme(
                        $data['theme_data'],
                        $user['establishment_id'],
                        $user['id']
                    );
                    echo json_encode($theme);
                    break;

                case 'preview':
                    $preview = $themeService->getThemePreview($data['theme_data']);
                    echo json_encode(['preview' => $preview]);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action non reconnue']);
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $theme = $themeService->updateTheme($data['theme_id'], $data['theme_data']);
            echo json_encode($theme);
            break;

        case 'GET':
            $action = $_GET['action'] ?? 'list';
            
            switch ($action) {
                case 'list':
                    $themes = $themeService->getAvailableThemes($user['establishment_id']);
                    echo json_encode($themes);
                    break;

                case 'active':
                    $theme = $themeService->getActiveTheme($user['establishment_id']);
                    echo json_encode($theme);
                    break;

                case 'export':
                    $export = $themeService->exportTheme($_GET['theme_id']);
                    echo json_encode($export);
                    break;

                case 'default':
                    $theme = $themeService->getDefaultTheme($_GET['theme_key'] ?? 'glassmorphism-blue');
                    echo json_encode($theme);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action non reconnue']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}