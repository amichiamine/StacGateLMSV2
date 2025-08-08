<?php
header('Content-Type: application/json');
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/services/ProgressiveWebAppService.php';

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
$pwaService = new ProgressiveWebAppService($database, $user['establishment_id']);

try {
    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? '';

            switch ($action) {
                case 'subscribe':
                    $result = $pwaService->subscribePushNotifications(
                        $user['id'],
                        $data['subscription']
                    );
                    echo json_encode(['success' => $result]);
                    break;

                case 'send':
                    // Vérifier les permissions admin
                    if (!in_array($user['role'], ['super_admin', 'admin', 'manager'])) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Permissions insuffisantes']);
                        exit;
                    }

                    $results = $pwaService->sendPushNotification(
                        $data['user_id'],
                        $data['title'],
                        $data['body'],
                        $data['data'] ?? []
                    );
                    echo json_encode($results);
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