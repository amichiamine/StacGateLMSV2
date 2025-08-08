<?php
header('Content-Type: application/json');
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/services/WysiwygService.php';

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
$wysiwygService = new WysiwygService($database);

try {
    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? '';

            switch ($action) {
                case 'create_component':
                    $component = $wysiwygService->createComponent(
                        $data['name'],
                        $data['content'],
                        $data['properties'] ?? [],
                        $user['establishment_id'],
                        $user['id']
                    );
                    echo json_encode($component);
                    break;

                case 'update_component':
                    $result = $wysiwygService->updateComponent(
                        $data['component_id'],
                        $data['content'],
                        $data['properties'] ?? []
                    );
                    echo json_encode(['success' => $result]);
                    break;

                case 'upload_media':
                    if (!isset($_FILES['file'])) {
                        throw new Exception('Aucun fichier uploadé');
                    }
                    
                    $media = $wysiwygService->handleMediaUpload(
                        $_FILES['file'],
                        $user['establishment_id'],
                        $user['id']
                    );
                    echo json_encode($media);
                    break;

                case 'save_version':
                    $version = $wysiwygService->saveContentVersion(
                        $data['content_id'],
                        $data['content'],
                        $user['id'],
                        $data['version_note'] ?? ''
                    );
                    echo json_encode($version);
                    break;

                case 'restore_version':
                    $result = $wysiwygService->restoreVersion(
                        $data['version_id'],
                        $user['id']
                    );
                    echo json_encode(['success' => $result]);
                    break;

                case 'generate_preview':
                    $preview = $wysiwygService->generatePreview(
                        $data['content'],
                        $data['template'] ?? 'default'
                    );
                    echo json_encode(['preview' => $preview]);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action non reconnue']);
            }
            break;

        case 'GET':
            $action = $_GET['action'] ?? '';
            
            switch ($action) {
                case 'components':
                    $components = $wysiwygService->getComponents($user['establishment_id']);
                    echo json_encode($components);
                    break;

                case 'media_gallery':
                    $type = $_GET['type'] ?? null;
                    $media = $wysiwygService->getMediaGallery($user['establishment_id'], $type);
                    echo json_encode($media);
                    break;

                case 'versions':
                    $versions = $wysiwygService->getContentVersions($_GET['content_id']);
                    echo json_encode($versions);
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