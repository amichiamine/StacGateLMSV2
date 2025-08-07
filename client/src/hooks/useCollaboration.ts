import { useEffect, useRef, useState, useCallback } from 'react';
import { useAuth } from './useAuth';

export interface CollaborationMessage {
  type: string;
  roomId?: string;
  data: any;
  timestamp?: string;
}

export interface CollaborationUser {
  id: string;
  name: string;
  role: string;
  establishmentId: string;
}

export interface RoomParticipant {
  user: CollaborationUser;
  joinedAt: string;
}

export interface UseCollaborationProps {
  autoConnect?: boolean;
  onMessage?: (message: CollaborationMessage) => void;
  onUserJoined?: (user: CollaborationUser) => void;
  onUserLeft?: (user: CollaborationUser) => void;
  onError?: (error: string) => void;
}

export function useCollaboration({
  autoConnect = true,
  onMessage,
  onUserJoined,
  onUserLeft,
  onError
}: UseCollaborationProps = {}) {
  const { user } = useAuth();
  const wsRef = useRef<WebSocket | null>(null);
  const reconnectTimeoutRef = useRef<NodeJS.Timeout>();
  const [isConnected, setIsConnected] = useState(false);
  const [isConnecting, setIsConnecting] = useState(false);
  const [currentRoom, setCurrentRoom] = useState<string | null>(null);
  const [participants, setParticipants] = useState<RoomParticipant[]>([]);
  const [error, setError] = useState<string | null>(null);

  const getWebSocketUrl = useCallback(() => {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const host = window.location.host;
    const params = new URLSearchParams({
      userId: user?.id || '',
      userName: `${user?.firstName || ''} ${user?.lastName || ''}`.trim() || user?.email || 'Unknown User',
      userRole: user?.role || 'apprenant',
      establishmentId: user?.establishmentId || 'default'
    });
    return `${protocol}//${host}/ws/collaboration?${params}`;
  }, [user]);

  const connect = useCallback(() => {
    if (!user?.id || isConnecting || isConnected) return;

    setIsConnecting(true);
    setError(null);

    try {
      const wsUrl = getWebSocketUrl();
      const ws = new WebSocket(wsUrl);
      
      ws.onopen = () => {
        console.log('WebSocket connected');
        setIsConnected(true);
        setIsConnecting(false);
        setError(null);
      };

      ws.onmessage = (event) => {
        try {
          const message: CollaborationMessage = JSON.parse(event.data);
          
          // Handle internal message types
          switch (message.type) {
            case 'connected':
              console.log('Collaboration session established:', message.data);
              break;
            
            case 'room_joined':
              setCurrentRoom(message.data.roomId);
              setParticipants(message.data.participants || []);
              break;
            
            case 'user_joined':
              setParticipants(prev => [...prev, { user: message.data.user, joinedAt: message.data.joinedAt }]);
              onUserJoined?.(message.data.user);
              break;
            
            case 'user_left':
              setParticipants(prev => prev.filter(p => p.user.id !== message.data.user.id));
              onUserLeft?.(message.data.user);
              break;
            
            case 'error':
              const errorMsg = message.data?.error || 'Unknown error';
              setError(errorMsg);
              onError?.(errorMsg);
              break;
            
            default:
              // Forward other messages to external handler
              onMessage?.(message);
          }
        } catch (error) {
          console.error('Error parsing WebSocket message:', error);
        }
      };

      ws.onclose = (event) => {
        console.log('WebSocket disconnected:', event.code, event.reason);
        setIsConnected(false);
        setIsConnecting(false);
        setCurrentRoom(null);
        setParticipants([]);

        // Attempt to reconnect after a delay (unless it was a clean close)
        if (event.code !== 1000 && autoConnect) {
          reconnectTimeoutRef.current = setTimeout(() => {
            connect();
          }, 3000);
        }
      };

      ws.onerror = (error) => {
        console.error('WebSocket error:', error);
        setIsConnecting(false);
        setError('Connection failed');
        onError?.('Connection failed');
      };

      wsRef.current = ws;
    } catch (error) {
      console.error('Failed to create WebSocket connection:', error);
      setIsConnecting(false);
      setError('Failed to establish connection');
      onError?.('Failed to establish connection');
    }
  }, [user, isConnecting, isConnected, autoConnect, getWebSocketUrl, onMessage, onUserJoined, onUserLeft, onError]);

  const disconnect = useCallback(() => {
    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
    }
    
    if (wsRef.current) {
      wsRef.current.close(1000, 'Manual disconnect');
      wsRef.current = null;
    }
    
    setIsConnected(false);
    setCurrentRoom(null);
    setParticipants([]);
  }, []);

  const sendMessage = useCallback((message: Omit<CollaborationMessage, 'timestamp'>) => {
    if (!wsRef.current || !isConnected) {
      console.warn('WebSocket not connected, cannot send message');
      return false;
    }

    try {
      wsRef.current.send(JSON.stringify({
        ...message,
        timestamp: new Date().toISOString()
      }));
      return true;
    } catch (error) {
      console.error('Failed to send WebSocket message:', error);
      return false;
    }
  }, [isConnected]);

  // Room management functions
  const joinRoom = useCallback((roomId: string, roomType: string, resourceId: string) => {
    return sendMessage({
      type: 'join_room',
      data: { roomId, roomType, resourceId }
    });
  }, [sendMessage]);

  const leaveRoom = useCallback((roomId: string) => {
    const success = sendMessage({
      type: 'leave_room',
      roomId,
      data: {}
    });
    
    if (success) {
      setCurrentRoom(null);
      setParticipants([]);
    }
    
    return success;
  }, [sendMessage]);

  // Collaboration-specific functions
  const sendCursorMove = useCallback((roomId: string, position: { x: number; y: number }) => {
    return sendMessage({
      type: 'cursor_move',
      roomId,
      data: { position }
    });
  }, [sendMessage]);

  const sendTextChange = useCallback((roomId: string, operation: string, content: any) => {
    return sendMessage({
      type: 'text_change',
      roomId,
      data: { operation, content }
    });
  }, [sendMessage]);

  const sendWhiteboardDraw = useCallback((roomId: string, drawData: any) => {
    return sendMessage({
      type: 'whiteboard_draw',
      roomId,
      data: { drawData }
    });
  }, [sendMessage]);

  const sendChatMessage = useCallback((roomId: string, message: string) => {
    return sendMessage({
      type: 'chat_message',
      roomId,
      data: { message }
    });
  }, [sendMessage]);

  const sendTypingIndicator = useCallback((roomId: string, isTyping: boolean) => {
    return sendMessage({
      type: 'typing_indicator',
      roomId,
      data: { isTyping }
    });
  }, [sendMessage]);

  // Auto-connect when user is available
  useEffect(() => {
    if (autoConnect && user?.id && !isConnected && !isConnecting) {
      connect();
    }

    return () => {
      if (reconnectTimeoutRef.current) {
        clearTimeout(reconnectTimeoutRef.current);
      }
    };
  }, [user?.id, autoConnect, connect, isConnected, isConnecting]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      disconnect();
    };
  }, [disconnect]);

  return {
    // Connection state
    isConnected,
    isConnecting,
    error,
    
    // Room state
    currentRoom,
    participants,
    
    // Connection management
    connect,
    disconnect,
    
    // Message sending
    sendMessage,
    
    // Room management
    joinRoom,
    leaveRoom,
    
    // Collaboration functions
    sendCursorMove,
    sendTextChange,
    sendWhiteboardDraw,
    sendChatMessage,
    sendTypingIndicator
  };
}