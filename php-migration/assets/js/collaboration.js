class CollaborationManager {
    constructor(config) {
        this.config = config;
        this.currentRoom = null;
        this.sessionId = null;
        this.participants = new Map();
        this.isConnected = false;
        this.retryCount = 0;
        this.maxRetries = 5;
        
        // Éléments DOM
        this.elements = {
            roomsList: document.getElementById('roomsList'),
            currentRoomInfo: document.getElementById('currentRoomInfo'),
            roomTitle: document.getElementById('roomTitle'),
            participantsAvatars: document.getElementById('participantsAvatars'),
            participantsCount: document.getElementById('participantsCount'),
            collaborationContent: document.getElementById('collaborationContent'),
            chatPanel: document.getElementById('chatPanel'),
            chatMessages: document.getElementById('chatMessages'),
            chatInput: document.getElementById('chatInput'),
            whiteboardPanel: document.getElementById('whiteboardPanel'),
            whiteboardCanvas: document.getElementById('whiteboardCanvas'),
            activeSessions: document.getElementById('activeSessions'),
            totalParticipants: document.getElementById('totalParticipants')
        };
        
        this.initializeEventListeners();
        this.initializeWhiteboard();
    }

    init() {
        this.loadRooms();
        this.startPolling();
        this.updateStats();
        
        // Polling pour les messages en attente
        setInterval(() => {
            this.checkPendingMessages();
        }, 2000);
    }

    initializeEventListeners() {
        // Boutons de création et navigation
        document.getElementById('createRoomBtn')?.addEventListener('click', () => {
            this.showCreateRoomModal();
        });

        // Chat
        document.getElementById('chatToggle')?.addEventListener('click', () => {
            this.toggleChat();
        });

        document.getElementById('closeChatBtn')?.addEventListener('click', () => {
            this.toggleChat();
        });

        document.getElementById('sendChatBtn')?.addEventListener('click', () => {
            this.sendChatMessage();
        });

        document.getElementById('chatInput')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendChatMessage();
            }
        });

        // Tableau blanc
        document.getElementById('whiteboardToggle')?.addEventListener('click', () => {
            this.toggleWhiteboard();
        });

        // Modal
        document.getElementById('closeModalBtn')?.addEventListener('click', () => {
            this.hideCreateRoomModal();
        });

        document.getElementById('cancelCreateBtn')?.addEventListener('click', () => {
            this.hideCreateRoomModal();
        });

        document.getElementById('createRoomForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.createRoom();
        });
    }

    async loadRooms() {
        try {
            const response = await fetch('/api/collaboration/rooms');
            const rooms = await response.json();
            
            this.renderRooms(rooms);
        } catch (error) {
            console.error('Erreur lors du chargement des salles:', error);
            this.showError('Impossible de charger les salles');
        }
    }

    renderRooms(rooms) {
        if (!this.elements.roomsList) return;

        this.elements.roomsList.innerHTML = rooms.map(room => `
            <div class="room-item ${room.id === this.currentRoom?.id ? 'active' : ''}" 
                 data-room-id="${room.id}" data-room-type="${room.type}">
                <div class="room-header">
                    <h4 class="room-name">${this.escapeHtml(room.name)}</h4>
                    <span class="room-participants">${room.participants_count || 0}</span>
                </div>
                <div class="room-meta">
                    <span class="room-type">${this.getRoomTypeLabel(room.type)}</span>
                    <span class="room-status ${room.is_active ? 'active' : 'inactive'}">
                        ${room.is_active ? '🟢 Actif' : '⚪ Inactif'}
                    </span>
                </div>
                <div class="room-actions">
                    <button class="btn-join" onclick="collaboration.joinRoom('${room.id}', '${room.type}')">
                        Rejoindre
                    </button>
                </div>
            </div>
        `).join('');
    }

    async joinRoom(roomId, roomType) {
        try {
            if (this.currentRoom) {
                await this.leaveCurrentRoom();
            }

            const response = await apiRequest('/api/websocket/collaboration', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'join_room',
                    room_type: roomType,
                    room_id: roomId
                })
            });

            const result = await response.json();
            
            this.currentRoom = { id: roomId, type: roomType };
            this.sessionId = result.session_id;
            this.isConnected = true;
            
            this.updateRoomDisplay(roomId, roomType);
            await this.loadParticipants();
            await this.loadChatHistory();
            
            this.showSuccess('Vous avez rejoint la salle');
            
        } catch (error) {
            console.error('Erreur lors de la connexion à la salle:', error);
            this.showError('Impossible de rejoindre la salle');
        }
    }

    async leaveCurrentRoom() {
        if (!this.currentRoom || !this.sessionId) return;

        try {
            await apiRequest('/api/websocket/collaboration', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'leave_room',
                    session_id: this.sessionId,
                    room_type: this.currentRoom.type,
                    room_id: this.currentRoom.id
                })
            });

            this.currentRoom = null;
            this.sessionId = null;
            this.isConnected = false;
            this.participants.clear();
            
            this.updateRoomDisplay(null);
            
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
        }
    }

    updateRoomDisplay(roomId, roomType = null) {
        if (!roomId) {
            this.elements.roomTitle.textContent = 'Sélectionnez une salle';
            this.elements.participantsCount.textContent = '0 participants';
            this.elements.participantsAvatars.innerHTML = '';
            this.elements.collaborationContent.innerHTML = `
                <div class="welcome-message">
                    <div class="welcome-icon">🤝</div>
                    <h3>Bienvenue dans l'espace de collaboration</h3>
                    <p>Sélectionnez une salle existante ou créez-en une nouvelle pour commencer à collaborer en temps réel.</p>
                </div>
            `;
            return;
        }

        this.elements.roomTitle.textContent = `Salle ${roomId}`;
        this.elements.collaborationContent.innerHTML = `
            <div class="collaboration-workspace">
                <div class="workspace-tools">
                    <button class="tool-btn" onclick="collaboration.shareScreen()">
                        <i class="icon-screen"></i> Partager l'écran
                    </button>
                    <button class="tool-btn" onclick="collaboration.startVideoCall()">
                        <i class="icon-video"></i> Appel vidéo
                    </button>
                    <button class="tool-btn" onclick="collaboration.shareDocument()">
                        <i class="icon-file"></i> Partager un document
                    </button>
                </div>
                <div class="workspace-content" id="workspaceContent">
                    <div class="workspace-placeholder">
                        <p>Espace de travail collaboratif</p>
                        <p>Utilisez les outils ci-dessus pour commencer à collaborer</p>
                    </div>
                </div>
            </div>
        `;
    }

    async loadParticipants() {
        if (!this.currentRoom) return;

        try {
            const response = await fetch(`/api/websocket/collaboration?action=participants&room_type=${this.currentRoom.type}&room_id=${this.currentRoom.id}`);
            const participants = await response.json();
            
            this.participants.clear();
            participants.forEach(p => this.participants.set(p.user_id, p));
            
            this.updateParticipantsDisplay();
            
        } catch (error) {
            console.error('Erreur lors du chargement des participants:', error);
        }
    }

    updateParticipantsDisplay() {
        const count = this.participants.size;
        this.elements.participantsCount.textContent = `${count} participant${count > 1 ? 's' : ''}`;
        
        this.elements.participantsAvatars.innerHTML = Array.from(this.participants.values())
            .slice(0, 5) // Afficher maximum 5 avatars
            .map(p => `
                <div class="participant-avatar" title="${this.escapeHtml(p.name)}">
                    ${p.name.charAt(0).toUpperCase()}
                </div>
            `).join('');
            
        if (this.participants.size > 5) {
            this.elements.participantsAvatars.innerHTML += `
                <div class="participant-avatar more">+${this.participants.size - 5}</div>
            `;
        }
    }

    async sendChatMessage() {
        const input = this.elements.chatInput;
        const message = input.value.trim();
        
        if (!message || !this.currentRoom || !this.sessionId) return;

        try {
            await apiRequest('/api/websocket/collaboration', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'send_message',
                    session_id: this.sessionId,
                    room_type: this.currentRoom.type,
                    room_id: this.currentRoom.id,
                    message_type: 'chat',
                    data: { message }
                })
            });

            input.value = '';
            
            // Ajouter le message immédiatement à l'interface
            this.addChatMessage({
                sender: this.config.userId,
                sender_name: 'Vous',
                message: message,
                timestamp: new Date().toISOString()
            });
            
        } catch (error) {
            console.error('Erreur lors de l\'envoi du message:', error);
            this.showError('Impossible d\'envoyer le message');
        }
    }

    addChatMessage(messageData) {
        if (!this.elements.chatMessages) return;

        const messageElement = document.createElement('div');
        messageElement.className = `chat-message ${messageData.sender === this.config.userId ? 'own' : 'other'}`;
        
        const time = new Date(messageData.timestamp).toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        messageElement.innerHTML = `
            <div class="message-header">
                <span class="sender-name">${this.escapeHtml(messageData.sender_name)}</span>
                <span class="message-time">${time}</span>
            </div>
            <div class="message-content">${this.escapeHtml(messageData.message)}</div>
        `;

        this.elements.chatMessages.appendChild(messageElement);
        this.elements.chatMessages.scrollTop = this.elements.chatMessages.scrollHeight;
    }

    async loadChatHistory() {
        if (!this.currentRoom) return;

        try {
            const response = await fetch(`/api/websocket/collaboration?action=history&room_type=${this.currentRoom.type}&room_id=${this.currentRoom.id}&limit=50`);
            const history = await response.json();
            
            this.elements.chatMessages.innerHTML = '';
            history.reverse().forEach(msg => {
                if (msg.type === 'chat') {
                    const data = JSON.parse(msg.data);
                    this.addChatMessage({
                        sender: msg.sender_id,
                        sender_name: msg.sender_name || 'Utilisateur',
                        message: data.message,
                        timestamp: msg.timestamp
                    });
                }
            });
            
        } catch (error) {
            console.error('Erreur lors du chargement de l\'historique:', error);
        }
    }

    async checkPendingMessages() {
        if (!this.isConnected) return;

        try {
            const response = await fetch(`/api/websocket/collaboration?action=pending_messages`);
            const messages = await response.json();
            
            messages.forEach(message => {
                this.handleIncomingMessage(message);
            });
            
        } catch (error) {
            console.error('Erreur lors de la vérification des messages:', error);
        }
    }

    handleIncomingMessage(message) {
        switch (message.type) {
            case 'chat':
                const data = JSON.parse(message.data);
                this.addChatMessage({
                    sender: message.sender,
                    sender_name: data.sender_name || 'Utilisateur',
                    message: data.message,
                    timestamp: message.timestamp
                });
                break;
                
            case 'participant_joined':
                this.loadParticipants();
                this.showNotification(`${message.data.name} a rejoint la salle`);
                break;
                
            case 'participant_left':
                this.loadParticipants();
                this.showNotification(`${message.data.name} a quitté la salle`);
                break;
        }
    }

    toggleChat() {
        const isVisible = this.elements.chatPanel.style.display !== 'none';
        this.elements.chatPanel.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible && this.currentRoom) {
            this.loadChatHistory();
        }
    }

    toggleWhiteboard() {
        const isVisible = this.elements.whiteboardPanel.style.display !== 'none';
        this.elements.whiteboardPanel.style.display = isVisible ? 'none' : 'block';
    }

    initializeWhiteboard() {
        if (!this.elements.whiteboardCanvas) return;

        const canvas = this.elements.whiteboardCanvas;
        const ctx = canvas.getContext('2d');
        
        let isDrawing = false;
        let currentTool = 'pen';
        let currentColor = '#000000';
        let currentSize = 5;

        // Event listeners pour les outils
        document.querySelectorAll('[data-tool]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('[data-tool]').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                currentTool = e.target.dataset.tool;
            });
        });

        document.getElementById('colorPicker')?.addEventListener('change', (e) => {
            currentColor = e.target.value;
        });

        document.getElementById('brushSize')?.addEventListener('input', (e) => {
            currentSize = e.target.value;
        });

        document.getElementById('clearWhiteboard')?.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        });

        // Dessin sur le canvas
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        function startDrawing(e) {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ctx.beginPath();
            ctx.moveTo(x, y);
        }

        function draw(e) {
            if (!isDrawing) return;
            
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ctx.lineWidth = currentSize;
            ctx.lineCap = 'round';
            ctx.strokeStyle = currentColor;
            
            ctx.lineTo(x, y);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(x, y);
        }

        function stopDrawing() {
            isDrawing = false;
            ctx.beginPath();
        }
    }

    async updateStats() {
        try {
            const response = await fetch('/api/websocket/collaboration?action=stats');
            const stats = await response.json();
            
            this.elements.activeSessions.textContent = stats.active_sessions || 0;
            this.elements.totalParticipants.textContent = stats.total_participants || 0;
            
        } catch (error) {
            console.error('Erreur lors de la mise à jour des statistiques:', error);
        }
    }

    startPolling() {
        // Polling pour les participants et statistiques
        setInterval(() => {
            if (this.currentRoom) {
                this.loadParticipants();
            }
            this.updateStats();
        }, 10000); // Toutes les 10 secondes
    }

    showCreateRoomModal() {
        document.getElementById('createRoomModal').style.display = 'block';
    }

    hideCreateRoomModal() {
        document.getElementById('createRoomModal').style.display = 'none';
        document.getElementById('createRoomForm').reset();
    }

    async createRoom() {
        const form = document.getElementById('createRoomForm');
        const formData = new FormData(form);
        
        try {
            const response = await apiRequest('/api/collaboration/rooms', {
                method: 'POST',
                body: JSON.stringify({
                    name: formData.get('room_name'),
                    type: formData.get('room_type'),
                    description: formData.get('description'),
                    is_private: formData.get('is_private') === 'on'
                })
            });

            this.hideCreateRoomModal();
            this.loadRooms();
            this.showSuccess('Salle créée avec succès');
            
        } catch (error) {
            console.error('Erreur lors de la création de la salle:', error);
            this.showError('Impossible de créer la salle');
        }
    }

    getRoomTypeLabel(type) {
        const labels = {
            'general': 'Générale',
            'course': 'Cours',
            'project': 'Projet',
            'study_group': 'Groupe d\'étude'
        };
        return labels[type] || type;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'error');
    }

    showNotification(message) {
        this.showToast(message, 'info');
    }

    showToast(message, type = 'info') {
        // Utiliser le système de toast existant
        if (window.showToast) {
            window.showToast(message, type);
        } else {
            alert(message);
        }
    }
}