<?php
header('Content-Type: application/json');
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/services/WebSocketService.php';

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
$websocketService = new WebSocketService($database);

try {
    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? '';

            switch ($action) {
                case 'join_room':
                    $sessionId = $websocketService->initializeConnection($user['id'], $user['establishment_id']);
                    $result = $websocketService->joinRoom(
                        $sessionId,
                        $data['room_type'],
                        $data['room_id']
                    );
                    $result['session_id'] = $sessionId;
                    echo json_encode($result);
                    break;

                case 'leave_room':
                    $result = $websocketService->leaveRoom(
                        $data['session_id'],
                        $data['room_type'],
                        $data['room_id']
                    );
                    echo json_encode($result);
                    break;

                case 'send_message':
                    $message = $websocketService->sendMessage(
                        $data['session_id'],
                        $data['room_type'],
                        $data['room_id'],
                        $data['message_type'],
                        $data['data']
                    );
                    echo json_encode($message);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action non reconnue']);
            }
            break;

        case 'GET':
            $action = $_GET['action'] ?? '';
            
            switch ($action) {
                case 'participants':
                    $participants = $websocketService->getRoomParticipants(
                        $_GET['room_type'],
                        $_GET['room_id']
                    );
                    echo json_encode($participants);
                    break;

                case 'history':
                    $history = $websocketService->getRoomHistory(
                        $_GET['room_type'],
                        $_GET['room_id'],
                        $_GET['limit'] ?? 50
                    );
                    echo json_encode($history);
                    break;

                case 'pending_messages':
                    $messages = $websocketService->getPendingMessages($user['id']);
                    echo json_encode($messages);
                    break;

                case 'stats':
                    $stats = $websocketService->getCollaborationStats($user['establishment_id']);
                    echo json_encode($stats);
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