<?php

class WebSocketService {
    private $database;
    private $sessions = [];
    private $rooms = [];

    public function __construct($database) {
        $this->database = $database;
    }

    /**
     * Initialiser une connexion WebSocket simulée
     */
    public function initializeConnection($userId, $establishmentId) {
        $sessionId = uniqid('ws_');
        $this->sessions[$sessionId] = [
            'user_id' => $userId,
            'establishment_id' => $establishmentId,
            'connected_at' => date('Y-m-d H:i:s'),
            'last_activity' => time()
        ];
        
        // Log de la connexion
        $this->logActivity($userId, 'websocket_connect', [
            'session_id' => $sessionId
        ]);
        
        return $sessionId;
    }

    /**
     * Rejoindre une salle de collaboration
     */
    public function joinRoom($sessionId, $roomType, $roomId) {
        if (!isset($this->sessions[$sessionId])) {
            throw new Exception('Session WebSocket invalide');
        }

        $room = $roomType . '_' . $roomId;
        
        if (!isset($this->rooms[$room])) {
            $this->rooms[$room] = [
                'type' => $roomType,
                'id' => $roomId,
                'participants' => [],
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        $this->rooms[$room]['participants'][$sessionId] = $this->sessions[$sessionId];
        
        // Enregistrer l'activité
        $this->logActivity(
            $this->sessions[$sessionId]['user_id'], 
            'join_room', 
            ['room' => $room, 'type' => $roomType]
        );

        return [
            'room' => $room,
            'participants' => count($this->rooms[$room]['participants']),
            'status' => 'joined'
        ];
    }

    /**
     * Quitter une salle
     */
    public function leaveRoom($sessionId, $roomType, $roomId) {
        $room = $roomType . '_' . $roomId;
        
        if (isset($this->rooms[$room]['participants'][$sessionId])) {
            unset($this->rooms[$room]['participants'][$sessionId]);
            
            // Supprimer la salle si vide
            if (empty($this->rooms[$room]['participants'])) {
                unset($this->rooms[$room]);
            }
        }

        return ['status' => 'left', 'room' => $room];
    }

    /**
     * Envoyer un message temps réel
     */
    public function sendMessage($sessionId, $roomType, $roomId, $messageType, $data) {
        $room = $roomType . '_' . $roomId;
        
        if (!isset($this->rooms[$room])) {
            throw new Exception('Salle non trouvée');
        }

        $message = [
            'id' => uniqid('msg_'),
            'type' => $messageType,
            'data' => $data,
            'sender' => $this->sessions[$sessionId]['user_id'],
            'timestamp' => date('Y-m-d H:i:s'),
            'room' => $room
        ];

        // Sauvegarder le message pour les participants connectés ultérieurement
        $this->saveCollaborationMessage($message);

        // Simuler la diffusion aux participants
        $this->broadcastToRoom($room, $message);

        return $message;
    }

    /**
     * Obtenir les participants d'une salle
     */
    public function getRoomParticipants($roomType, $roomId) {
        $room = $roomType . '_' . $roomId;
        
        if (!isset($this->rooms[$room])) {
            return [];
        }

        $participants = [];
        foreach ($this->rooms[$room]['participants'] as $sessionId => $session) {
            $user = $this->database->findOne('users', ['id' => $session['user_id']]);
            if ($user) {
                $participants[] = [
                    'session_id' => $sessionId,
                    'user_id' => $user['id'],
                    'name' => $user['first_name'] . ' ' . $user['last_name'],
                    'role' => $user['role'],
                    'connected_at' => $session['connected_at']
                ];
            }
        }

        return $participants;
    }

    /**
     * Obtenir l'historique des messages d'une salle
     */
    public function getRoomHistory($roomType, $roomId, $limit = 50) {
        $room = $roomType . '_' . $roomId;
        
        return $this->database->findAll('collaboration_messages', [
            'room' => $room
        ], [
            'order_by' => 'timestamp DESC',
            'limit' => $limit
        ]);
    }

    /**
     * Sauvegarder un message de collaboration
     */
    private function saveCollaborationMessage($message) {
        $this->database->insert('collaboration_messages', [
            'id' => $message['id'],
            'room' => $message['room'],
            'type' => $message['type'],
            'data' => json_encode($message['data']),
            'sender_id' => $message['sender'],
            'timestamp' => $message['timestamp']
        ]);
    }

    /**
     * Diffuser un message aux participants d'une salle
     */
    private function broadcastToRoom($room, $message) {
        if (!isset($this->rooms[$room])) {
            return;
        }

        // Dans une vraie implémentation WebSocket, on diffuserait ici
        // Pour PHP, on simule en sauvegardant pour récupération via polling
        foreach ($this->rooms[$room]['participants'] as $sessionId => $session) {
            $this->addPendingMessage($session['user_id'], $message);
        }
    }

    /**
     * Ajouter un message en attente pour un utilisateur
     */
    private function addPendingMessage($userId, $message) {
        $cacheKey = "pending_messages_user_{$userId}";
        $messages = Utils::getCache($cacheKey, []);
        $messages[] = $message;
        
        // Garder seulement les 100 derniers messages
        if (count($messages) > 100) {
            $messages = array_slice($messages, -100);
        }
        
        Utils::setCache($cacheKey, $messages, 3600); // 1 heure
    }

    /**
     * Récupérer les messages en attente pour un utilisateur
     */
    public function getPendingMessages($userId) {
        $cacheKey = "pending_messages_user_{$userId}";
        $messages = Utils::getCache($cacheKey, []);
        
        // Vider le cache après récupération
        Utils::clearCache($cacheKey);
        
        return $messages;
    }

    /**
     * Enregistrer une activité de collaboration
     */
    private function logActivity($userId, $action, $data = []) {
        $this->database->insert('activity_logs', [
            'id' => uniqid('act_'),
            'user_id' => $userId,
            'action' => $action,
            'data' => json_encode($data),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    /**
     * Nettoyer les sessions inactives
     */
    public function cleanupInactiveSessions($timeoutMinutes = 30) {
        $timeout = time() - ($timeoutMinutes * 60);
        
        foreach ($this->sessions as $sessionId => $session) {
            if ($session['last_activity'] < $timeout) {
                // Retirer de toutes les salles
                foreach ($this->rooms as $roomName => $room) {
                    if (isset($room['participants'][$sessionId])) {
                        unset($this->rooms[$roomName]['participants'][$sessionId]);
                        
                        // Supprimer les salles vides
                        if (empty($this->rooms[$roomName]['participants'])) {
                            unset($this->rooms[$roomName]);
                        }
                    }
                }
                
                unset($this->sessions[$sessionId]);
            }
        }
    }

    /**
     * Obtenir les statistiques de collaboration
     */
    public function getCollaborationStats($establishmentId = null) {
        $stats = [
            'active_sessions' => count($this->sessions),
            'active_rooms' => count($this->rooms),
            'total_participants' => 0
        ];

        foreach ($this->rooms as $room) {
            $stats['total_participants'] += count($room['participants']);
        }

        // Statistiques depuis la base de données
        $conditions = [];
        if ($establishmentId) {
            $conditions['establishment_id'] = $establishmentId;
        }

        $todayMessages = $this->database->count('collaboration_messages', array_merge($conditions, [
            'timestamp >=' => date('Y-m-d 00:00:00')
        ]));

        $stats['messages_today'] = $todayMessages;

        return $stats;
    }
}