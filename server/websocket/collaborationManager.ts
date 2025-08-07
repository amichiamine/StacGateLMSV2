import WebSocket from 'ws';

export interface User {
  id: string;
  name: string;
  role: string;
  establishmentId: string;
}

export interface Room {
  id: string;
  type: 'course' | 'studygroup' | 'whiteboard' | 'assessment';
  resourceId: string;
  establishmentId: string;
  participants: Map<string, { user: User; ws: WebSocket; joinedAt: Date }>;
  lastActivity: Date;
}

export class CollaborationManager {
  private rooms: Map<string, Room> = new Map();
  private userSessions: Map<string, { user: User; ws: WebSocket; rooms: Set<string> }> = new Map();

  /**
   * Add a user to the collaboration system
   */
  addUser(ws: WebSocket, user: User): void {
    const sessionId = this.generateSessionId();
    this.userSessions.set(sessionId, {
      user,
      ws,
      rooms: new Set()
    });

    // Store sessionId in WebSocket for cleanup
    (ws as any).sessionId = sessionId;
    (ws as any).user = user;

    console.log(`User ${user.name} (${user.role}) connected from establishment ${user.establishmentId}`);
    
    // Send welcome message with user info
    this.sendToUser(ws, {
      type: 'connected',
      data: {
        sessionId,
        user: user,
        message: 'Connected to collaboration server'
      }
    });
  }

  /**
   * Join a collaboration room
   */
  joinRoom(ws: WebSocket, roomId: string, roomType: string, resourceId: string): void {
    const sessionId = (ws as any).sessionId;
    const session = this.userSessions.get(sessionId);
    
    if (!session) {
      this.sendError(ws, 'User session not found');
      return;
    }

    // Create room if it doesn't exist
    if (!this.rooms.has(roomId)) {
      this.rooms.set(roomId, {
        id: roomId,
        type: roomType as any,
        resourceId,
        establishmentId: session.user.establishmentId,
        participants: new Map(),
        lastActivity: new Date()
      });
    }

    const room = this.rooms.get(roomId)!;
    
    // Add user to room
    room.participants.set(sessionId, {
      user: session.user,
      ws: session.ws,
      joinedAt: new Date()
    });
    
    session.rooms.add(roomId);
    room.lastActivity = new Date();

    console.log(`User ${session.user.name} joined room ${roomId} (${roomType}:${resourceId})`);

    // Notify user about successful join
    this.sendToUser(ws, {
      type: 'room_joined',
      data: {
        roomId,
        roomType,
        resourceId,
        participants: this.getRoomParticipants(roomId)
      }
    });

    // Notify other participants in the room
    this.broadcastToRoom(roomId, {
      type: 'user_joined',
      data: {
        user: session.user,
        joinedAt: new Date(),
        totalParticipants: room.participants.size
      }
    }, sessionId);
  }

  /**
   * Leave a collaboration room
   */
  leaveRoom(ws: WebSocket, roomId: string): void {
    const sessionId = (ws as any).sessionId;
    const session = this.userSessions.get(sessionId);
    
    if (!session) return;

    const room = this.rooms.get(roomId);
    if (!room) return;

    // Remove user from room
    room.participants.delete(sessionId);
    session.rooms.delete(roomId);
    room.lastActivity = new Date();

    console.log(`User ${session.user.name} left room ${roomId}`);

    // Notify other participants
    this.broadcastToRoom(roomId, {
      type: 'user_left',
      data: {
        user: session.user,
        leftAt: new Date(),
        totalParticipants: room.participants.size
      }
    });

    // Clean up empty rooms
    if (room.participants.size === 0) {
      this.rooms.delete(roomId);
      console.log(`Room ${roomId} cleaned up (empty)`);
    }
  }

  /**
   * Handle real-time collaboration messages
   */
  handleCollaborationMessage(ws: WebSocket, message: any): void {
    const sessionId = (ws as any).sessionId;
    const session = this.userSessions.get(sessionId);
    
    if (!session) {
      this.sendError(ws, 'User session not found');
      return;
    }

    const { type, roomId, data } = message;

    switch (type) {
      case 'join_room':
        this.joinRoom(ws, data.roomId, data.roomType, data.resourceId);
        break;
      
      case 'leave_room':
        this.leaveRoom(ws, roomId);
        break;
      
      case 'cursor_move':
        this.broadcastToRoom(roomId, {
          type: 'cursor_update',
          data: {
            userId: session.user.id,
            userName: session.user.name,
            position: data.position,
            timestamp: new Date()
          }
        }, sessionId);
        break;
      
      case 'text_change':
        this.broadcastToRoom(roomId, {
          type: 'content_update',
          data: {
            userId: session.user.id,
            userName: session.user.name,
            operation: data.operation,
            content: data.content,
            timestamp: new Date()
          }
        }, sessionId);
        break;
      
      case 'whiteboard_draw':
        this.broadcastToRoom(roomId, {
          type: 'whiteboard_update',
          data: {
            userId: session.user.id,
            userName: session.user.name,
            drawData: data.drawData,
            timestamp: new Date()
          }
        }, sessionId);
        break;
      
      case 'chat_message':
        this.broadcastToRoom(roomId, {
          type: 'chat_message',
          data: {
            userId: session.user.id,
            userName: session.user.name,
            message: data.message,
            timestamp: new Date()
          }
        }, sessionId);
        break;
      
      case 'typing_indicator':
        this.broadcastToRoom(roomId, {
          type: 'typing_update',
          data: {
            userId: session.user.id,
            userName: session.user.name,
            isTyping: data.isTyping,
            timestamp: new Date()
          }
        }, sessionId);
        break;
      
      default:
        console.warn(`Unknown collaboration message type: ${type}`);
    }
  }

  /**
   * Remove user from collaboration system
   */
  removeUser(ws: WebSocket): void {
    const sessionId = (ws as any).sessionId;
    const session = this.userSessions.get(sessionId);
    
    if (!session) return;

    console.log(`User ${session.user.name} disconnected`);

    // Leave all rooms
    for (const roomId of Array.from(session.rooms)) {
      this.leaveRoom(ws, roomId);
    }

    // Remove user session
    this.userSessions.delete(sessionId);
  }

  /**
   * Get active participants in a room
   */
  getRoomParticipants(roomId: string): any[] {
    const room = this.rooms.get(roomId);
    if (!room) return [];

    return Array.from(room.participants.values()).map(participant => ({
      user: participant.user,
      joinedAt: participant.joinedAt
    }));
  }

  /**
   * Get room statistics
   */
  getRoomStats(roomId: string): any {
    const room = this.rooms.get(roomId);
    if (!room) return null;

    return {
      id: room.id,
      type: room.type,
      resourceId: room.resourceId,
      participantCount: room.participants.size,
      lastActivity: room.lastActivity,
      participants: this.getRoomParticipants(roomId)
    };
  }

  /**
   * Get system-wide collaboration stats
   */
  getSystemStats(): any {
    return {
      activeUsers: this.userSessions.size,
      activeRooms: this.rooms.size,
      totalConnections: Array.from(this.userSessions.values()).length,
      roomsByType: this.getRoomsByType()
    };
  }

  private getRoomsByType(): any {
    const stats = { course: 0, studygroup: 0, whiteboard: 0, assessment: 0 };
    for (const room of Array.from(this.rooms.values())) {
      const roomType = room.type as keyof typeof stats;
      if (stats[roomType] !== undefined) {
        stats[roomType]++;
      }
    }
    return stats;
  }

  private sendToUser(ws: WebSocket, message: any): void {
    if (ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(message));
    }
  }

  private sendError(ws: WebSocket, error: string): void {
    this.sendToUser(ws, {
      type: 'error',
      data: { error, timestamp: new Date() }
    });
  }

  private broadcastToRoom(roomId: string, message: any, excludeSessionId?: string): void {
    const room = this.rooms.get(roomId);
    if (!room) return;

    for (const [sessionId, participant] of Array.from(room.participants.entries())) {
      if (sessionId !== excludeSessionId) {
        this.sendToUser(participant.ws, message);
      }
    }
  }

  private generateSessionId(): string {
    return `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }
}