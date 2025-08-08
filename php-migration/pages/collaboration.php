<?php
require_once '../core/Auth.php';
require_once '../core/Database.php';

$database = new Database();
$auth = new Auth($database);

if (!$auth->isAuthenticated()) {
    header('Location: /login.php');
    exit;
}

$user = $auth->getCurrentUser();
$pageTitle = "Collaboration Temps Réel - StacGate LMS";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/collaboration.css">
</head>
<body class="collaboration-page">
    <?php include '../includes/header.php'; ?>

    <div class="main-container">
        <!-- Sidebar des salles actives -->
        <div class="collaboration-sidebar">
            <div class="sidebar-header">
                <h3>Salles Actives</h3>
                <button id="createRoomBtn" class="btn-create-room">
                    <i class="icon-plus"></i> Nouvelle Salle
                </button>
            </div>

            <div class="rooms-list" id="roomsList">
                <!-- Les salles seront chargées dynamiquement -->
            </div>

            <div class="collaboration-stats">
                <h4>Statistiques</h4>
                <div class="stat-item">
                    <span class="stat-label">Sessions actives:</span>
                    <span class="stat-value" id="activeSessions">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Participants:</span>
                    <span class="stat-value" id="totalParticipants">0</span>
                </div>
            </div>
        </div>

        <!-- Zone de collaboration principale -->
        <div class="collaboration-main">
            <div class="collaboration-header">
                <div class="room-info" id="currentRoomInfo">
                    <h2 id="roomTitle">Sélectionnez une salle</h2>
                    <div class="participants-indicator">
                        <div class="participants-avatars" id="participantsAvatars"></div>
                        <span class="participants-count" id="participantsCount">0 participants</span>
                    </div>
                </div>

                <div class="collaboration-tools">
                    <button class="tool-btn" id="chatToggle" title="Chat">
                        <i class="icon-message"></i>
                    </button>
                    <button class="tool-btn" id="whiteboardToggle" title="Tableau blanc">
                        <i class="icon-edit"></i>
                    </button>
                    <button class="tool-btn" id="screenShareToggle" title="Partage d'écran">
                        <i class="icon-screen"></i>
                    </button>
                </div>
            </div>

            <!-- Zone de contenu collaboratif -->
            <div class="collaboration-content" id="collaborationContent">
                <div class="welcome-message">
                    <div class="welcome-icon">🤝</div>
                    <h3>Bienvenue dans l'espace de collaboration</h3>
                    <p>Sélectionnez une salle existante ou créez-en une nouvelle pour commencer à collaborer en temps réel.</p>
                </div>
            </div>

            <!-- Chat en temps réel -->
            <div class="chat-panel" id="chatPanel" style="display: none;">
                <div class="chat-header">
                    <h4>Chat de la salle</h4>
                    <button class="close-btn" id="closeChatBtn">&times;</button>
                </div>
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-input-container">
                    <input type="text" id="chatInput" placeholder="Tapez votre message..." class="chat-input">
                    <button id="sendChatBtn" class="btn-send-chat">Envoyer</button>
                </div>
            </div>

            <!-- Tableau blanc -->
            <div class="whiteboard-panel" id="whiteboardPanel" style="display: none;">
                <div class="whiteboard-toolbar">
                    <button class="tool-btn active" data-tool="pen">✏️</button>
                    <button class="tool-btn" data-tool="eraser">🧽</button>
                    <button class="tool-btn" data-tool="line">📏</button>
                    <button class="tool-btn" data-tool="rectangle">⬜</button>
                    <button class="tool-btn" data-tool="circle">⭕</button>
                    <input type="color" id="colorPicker" value="#000000">
                    <input type="range" id="brushSize" min="1" max="50" value="5">
                    <button class="tool-btn" id="clearWhiteboard">🗑️</button>
                </div>
                <canvas id="whiteboardCanvas" width="800" height="600"></canvas>
            </div>
        </div>
    </div>

    <!-- Modal de création de salle -->
    <div class="modal" id="createRoomModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Créer une nouvelle salle</h3>
                <button class="close-btn" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createRoomForm">
                    <div class="form-group">
                        <label for="roomName">Nom de la salle</label>
                        <input type="text" id="roomName" name="room_name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="roomType">Type de salle</label>
                        <select id="roomType" name="room_type" class="form-control">
                            <option value="general">Générale</option>
                            <option value="course">Cours</option>
                            <option value="project">Projet</option>
                            <option value="study_group">Groupe d'étude</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="roomDescription">Description</label>
                        <textarea id="roomDescription" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="isPrivate" name="is_private">
                            Salle privée
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelCreateBtn">Annuler</button>
                <button type="submit" form="createRoomForm" class="btn-primary">Créer la salle</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/collaboration.js"></script>
    <script>
        // Initialiser la collaboration
        const collaboration = new CollaborationManager({
            userId: '<?php echo $user['id']; ?>',
            userRole: '<?php echo $user['role']; ?>',
            establishmentId: '<?php echo $user['establishment_id']; ?>'
        });

        collaboration.init();
    </script>
</body>
</html>