import { useEffect, useState } from 'react';
import { useCollaboration } from '@/hooks/useCollaboration';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { 
  Users, 
  Wifi, 
  WifiOff, 
  MessageCircle, 
  Eye,
  UserPlus,
  UserMinus
} from 'lucide-react';
import { useToast } from '@/hooks/use-toast';

interface CollaborationIndicatorProps {
  roomId?: string;
  roomType?: string;
  resourceId?: string;
  showParticipants?: boolean;
  className?: string;
}

export function CollaborationIndicator({
  roomId,
  roomType = 'general',
  resourceId = 'default',
  showParticipants = true,
  className = ''
}: CollaborationIndicatorProps) {
  const { toast } = useToast();
  const [isInRoom, setIsInRoom] = useState(false);

  const {
    isConnected,
    isConnecting,
    participants,
    currentRoom,
    joinRoom,
    leaveRoom,
    error
  } = useCollaboration({
    onUserJoined: (user) => {
      toast({
        title: "Utilisateur rejoint",
        description: `${user.name} a rejoint la session`,
      });
    },
    onUserLeft: (user) => {
      toast({
        title: "Utilisateur parti",
        description: `${user.name} a quitté la session`,
      });
    },
    onError: (error) => {
      toast({
        title: "Erreur de collaboration",
        description: error,
        variant: "destructive",
      });
    }
  });

  // Auto-join room when roomId is provided and connection is ready
  useEffect(() => {
    if (isConnected && roomId && !isInRoom && currentRoom !== roomId) {
      const success = joinRoom(roomId, roomType, resourceId);
      if (success) {
        setIsInRoom(true);
      }
    }
  }, [isConnected, roomId, roomType, resourceId, isInRoom, currentRoom, joinRoom]);

  // Update room status
  useEffect(() => {
    setIsInRoom(currentRoom === roomId);
  }, [currentRoom, roomId]);

  const handleToggleRoom = () => {
    if (!roomId) return;

    if (isInRoom) {
      const success = leaveRoom(roomId);
      if (success) {
        setIsInRoom(false);
      }
    } else {
      const success = joinRoom(roomId, roomType, resourceId);
      if (success) {
        setIsInRoom(true);
      }
    }
  };

  if (!isConnected && !isConnecting) {
    return (
      <div className={`flex items-center space-x-2 text-gray-500 ${className}`} data-testid="collaboration-indicator-disconnected">
        <WifiOff className="h-4 w-4" />
        <span className="text-sm">Non connecté</span>
      </div>
    );
  }

  return (
    <div className={`flex items-center space-x-3 ${className}`} data-testid="collaboration-indicator">
      {/* Connection Status */}
      <div className="flex items-center space-x-2">
        {isConnecting ? (
          <div className="flex items-center space-x-2 text-yellow-600" data-testid="status-connecting">
            <div className="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></div>
            <span className="text-sm">Connexion...</span>
          </div>
        ) : isConnected ? (
          <div className="flex items-center space-x-2 text-green-600" data-testid="status-connected">
            <Wifi className="h-4 w-4" />
            <span className="text-sm">Connecté</span>
          </div>
        ) : (
          <div className="flex items-center space-x-2 text-red-600" data-testid="status-error">
            <WifiOff className="h-4 w-4" />
            <span className="text-sm">Erreur</span>
          </div>
        )}
      </div>

      {/* Room Controls */}
      {roomId && isConnected && (
        <div className="flex items-center space-x-2">
          <Button
            variant={isInRoom ? "default" : "outline"}
            size="sm"
            onClick={handleToggleRoom}
            className="flex items-center space-x-1"
            data-testid="button-toggle-room"
          >
            {isInRoom ? (
              <>
                <UserMinus className="h-3 w-3" />
                <span>Quitter</span>
              </>
            ) : (
              <>
                <UserPlus className="h-3 w-3" />
                <span>Rejoindre</span>
              </>
            )}
          </Button>
          
          {isInRoom && (
            <Badge variant="secondary" data-testid="badge-room-status">
              En session
            </Badge>
          )}
        </div>
      )}

      {/* Participants List */}
      {showParticipants && participants.length > 0 && (
        <div className="flex items-center space-x-2" data-testid="participants-list">
          <div className="flex items-center space-x-1">
            <Users className="h-4 w-4 text-gray-500" />
            <span className="text-sm text-gray-600" data-testid="participants-count">
              {participants.length}
            </span>
          </div>
          
          <div className="flex -space-x-2">
            {participants.slice(0, 5).map((participant, index) => (
              <Avatar 
                key={participant.user.id} 
                className="h-6 w-6 border-2 border-white"
                data-testid={`avatar-participant-${index}`}
              >
                <AvatarFallback className="text-xs">
                  {participant.user.name.charAt(0).toUpperCase()}
                </AvatarFallback>
              </Avatar>
            ))}
            {participants.length > 5 && (
              <div 
                className="h-6 w-6 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center"
                data-testid="participants-overflow"
              >
                <span className="text-xs text-gray-600">+{participants.length - 5}</span>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Error Message */}
      {error && (
        <Badge variant="destructive" className="text-xs" data-testid="badge-error">
          {error}
        </Badge>
      )}
    </div>
  );
}