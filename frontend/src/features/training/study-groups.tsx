import { useState, useEffect } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { useToast } from "@/hooks/use-toast";
import { Loader2, Users, MessageCircle, Video, FileText, Calendar, Plus, Share, Settings, PaintBucket, UserPlus } from "lucide-react";
import { format } from "date-fns";

interface StudyGroup {
  id: string;
  name: string;
  description?: string;
  maxMembers: number;
  currentMembers: number;
  status: string;
  isPublic: boolean;
  tags?: string[];
  scheduledDate?: string;
  createdAt: string;
  members?: any[];
  isMember?: boolean;
}

interface StudyGroupMessage {
  id: string;
  content: string;
  type: string;
  createdAt: string;
  sender: {
    id: string;
    firstName: string;
    lastName: string;
    profileImageUrl?: string;
  };
}

export default function StudyGroupsPage() {
  const [selectedGroup, setSelectedGroup] = useState<StudyGroup | null>(null);
  const [showCreateDialog, setShowCreateDialog] = useState(false);
  const [messages, setMessages] = useState<StudyGroupMessage[]>([]);
  const [newMessage, setNewMessage] = useState("");
  const [websocket, setWebsocket] = useState<WebSocket | null>(null);
  const { toast } = useToast();
  const queryClient = useQueryClient();

  // Fetch study groups
  const { data: studyGroups, isLoading } = useQuery<StudyGroup[]>({
    queryKey: ["/api/study-groups"],
    staleTime: 30000,
  });

  // Fetch messages for selected group
  const { data: groupMessages } = useQuery<StudyGroupMessage[]>({
    queryKey: ["/api/study-groups", selectedGroup?.id, "messages"],
    enabled: !!selectedGroup,
    staleTime: 10000,
  });

  useEffect(() => {
    if (groupMessages) {
      setMessages(groupMessages);
    }
  }, [groupMessages]);

  // WebSocket setup for real-time features
  useEffect(() => {
    if (!selectedGroup) return;

    const protocol = window.location.protocol === "https:" ? "wss:" : "ws:";
    const wsUrl = `${protocol}//${window.location.host}/ws`;
    const ws = new WebSocket(wsUrl);

    ws.onopen = () => {
      console.log('WebSocket connected');
      // Join the study group room
      ws.send(JSON.stringify({
        type: 'join_group',
        studyGroupId: selectedGroup.id,
        userId: 'current_user_id' // This would come from auth context
      }));
    };

    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      
      switch (data.type) {
        case 'new_message':
          if (data.studyGroupId === selectedGroup.id) {
            setMessages(prev => [data.message, ...prev]);
          }
          break;
        case 'user_joined':
          if (data.studyGroupId === selectedGroup.id) {
            toast({
              title: "Member joined",
              description: "A new member joined the study group",
            });
          }
          break;
        case 'user_left':
          if (data.studyGroupId === selectedGroup.id) {
            toast({
              title: "Member left",
              description: "A member left the study group",
            });
          }
          break;
        case 'user_typing':
          // Handle typing indicators
          console.log('User typing:', data);
          break;
        case 'whiteboard_update':
          // Handle real-time whiteboard updates
          console.log('Whiteboard updated:', data);
          break;
      }
    };

    ws.onclose = () => {
      console.log('WebSocket disconnected');
    };

    setWebsocket(ws);

    return () => {
      ws.close();
    };
  }, [selectedGroup, toast]);

  // Create study group mutation
  const createGroupMutation = useMutation({
    mutationFn: async (data: any) => {
      const response = await fetch("/api/study-groups", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
      if (!response.ok) throw new Error(await response.text());
      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/study-groups"] });
      setShowCreateDialog(false);
      toast({
        title: "Study group created",
        description: "Your study group has been created successfully.",
      });
    },
    onError: () => {
      toast({
        title: "Error",
        description: "Failed to create study group. Please try again.",
        variant: "destructive",
      });
    },
  });

  // Join study group mutation
  const joinGroupMutation = useMutation({
    mutationFn: async (groupId: string) => {
      const response = await fetch(`/api/study-groups/${groupId}/join`, {
        method: "POST",
      });
      if (!response.ok) throw new Error(await response.text());
      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/study-groups"] });
      toast({
        title: "Joined group",
        description: "You have successfully joined the study group.",
      });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.message || "Failed to join study group.",
        variant: "destructive",
      });
    },
  });

  // Send message mutation
  const sendMessageMutation = useMutation({
    mutationFn: async (data: { content: string; type: string }) => {
      const response = await fetch(`/api/study-groups/${selectedGroup?.id}/messages`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
      if (!response.ok) throw new Error(await response.text());
      return response.json();
    },
    onSuccess: () => {
      setNewMessage("");
      queryClient.invalidateQueries({ queryKey: ["/api/study-groups", selectedGroup?.id, "messages"] });
    },
    onError: () => {
      toast({
        title: "Error",
        description: "Failed to send message. Please try again.",
        variant: "destructive",
      });
    },
  });

  const handleSendMessage = () => {
    if (!newMessage.trim() || !selectedGroup) return;
    
    sendMessageMutation.mutate({
      content: newMessage,
      type: "text"
    });
  };

  const handleCreateGroup = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    
    createGroupMutation.mutate({
      name: formData.get("name"),
      description: formData.get("description"),
      maxMembers: parseInt(formData.get("maxMembers") as string) || 10,
      isPublic: formData.get("isPublic") === "on",
      tags: (formData.get("tags") as string)?.split(",").map(tag => tag.trim()).filter(Boolean) || [],
    });
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-6">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Study Groups</h1>
          <p className="text-gray-600 dark:text-gray-400">Join collaborative study sessions with your peers</p>
        </div>
        
        <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
          <DialogTrigger asChild>
            <Button data-testid="button-create-group">
              <Plus className="h-4 w-4 mr-2" />
              Create Group
            </Button>
          </DialogTrigger>
          <DialogContent className="sm:max-w-[425px]">
            <form onSubmit={handleCreateGroup}>
              <DialogHeader>
                <DialogTitle>Create Study Group</DialogTitle>
                <DialogDescription>
                  Create a new study group to collaborate with your peers.
                </DialogDescription>
              </DialogHeader>
              
              <div className="grid gap-4 py-4">
                <div className="grid grid-cols-4 items-center gap-4">
                  <Label htmlFor="name" className="text-right">Name</Label>
                  <Input 
                    id="name" 
                    name="name"
                    placeholder="Group name"
                    className="col-span-3" 
                    required
                    data-testid="input-group-name"
                  />
                </div>
                
                <div className="grid grid-cols-4 items-center gap-4">
                  <Label htmlFor="description" className="text-right">Description</Label>
                  <Textarea 
                    id="description" 
                    name="description"
                    placeholder="What will you study together?"
                    className="col-span-3"
                    rows={3}
                    data-testid="input-group-description"
                  />
                </div>
                
                <div className="grid grid-cols-4 items-center gap-4">
                  <Label htmlFor="maxMembers" className="text-right">Max Members</Label>
                  <Input 
                    id="maxMembers" 
                    name="maxMembers"
                    type="number"
                    placeholder="10"
                    className="col-span-3"
                    min="2"
                    max="50"
                    defaultValue="10"
                    data-testid="input-max-members"
                  />
                </div>
                
                <div className="grid grid-cols-4 items-center gap-4">
                  <Label htmlFor="tags" className="text-right">Tags</Label>
                  <Input 
                    id="tags" 
                    name="tags"
                    placeholder="math, physics, chemistry"
                    className="col-span-3"
                    data-testid="input-group-tags"
                  />
                </div>
                
                <div className="grid grid-cols-4 items-center gap-4">
                  <Label htmlFor="isPublic" className="text-right">Public</Label>
                  <div className="col-span-3">
                    <Switch 
                      id="isPublic"
                      name="isPublic"
                      defaultChecked={true}
                      data-testid="switch-public"
                    />
                    <p className="text-xs text-gray-500 mt-1">Anyone can discover and join this group</p>
                  </div>
                </div>
              </div>
              
              <DialogFooter>
                <Button type="submit" disabled={createGroupMutation.isPending} data-testid="button-submit-group">
                  {createGroupMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                  Create Group
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Study Groups List */}
        <div className="lg:col-span-1">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Users className="h-5 w-5 mr-2" />
                Available Groups
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ScrollArea className="h-[600px]">
                <div className="space-y-3">
                  {studyGroups?.map((group) => (
                    <Card 
                      key={group.id} 
                      className={`cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 ${
                        selectedGroup?.id === group.id ? 'ring-2 ring-blue-500' : ''
                      }`}
                      onClick={() => setSelectedGroup(group)}
                      data-testid={`card-group-${group.id}`}
                    >
                      <CardContent className="p-4">
                        <div className="flex items-start justify-between mb-2">
                          <h3 className="font-medium text-sm">{group.name}</h3>
                          <Badge variant={group.status === 'active' ? 'default' : 'secondary'} className="text-xs">
                            {group.status}
                          </Badge>
                        </div>
                        
                        {group.description && (
                          <p className="text-xs text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">
                            {group.description}
                          </p>
                        )}
                        
                        <div className="flex items-center justify-between text-xs text-gray-500">
                          <span className="flex items-center">
                            <Users className="h-3 w-3 mr-1" />
                            {group.currentMembers}/{group.maxMembers}
                          </span>
                          <span>{format(new Date(group.createdAt), 'MMM d')}</span>
                        </div>
                        
                        {group.tags && group.tags.length > 0 && (
                          <div className="flex flex-wrap gap-1 mt-2">
                            {group.tags.slice(0, 3).map((tag) => (
                              <Badge key={tag} variant="outline" className="text-xs px-1 py-0">
                                {tag}
                              </Badge>
                            ))}
                          </div>
                        )}
                        
                        {!group.isMember && (
                          <Button 
                            size="sm" 
                            variant="outline" 
                            className="w-full mt-2 text-xs"
                            onClick={(e) => {
                              e.stopPropagation();
                              joinGroupMutation.mutate(group.id);
                            }}
                            disabled={joinGroupMutation.isPending || group.currentMembers >= group.maxMembers}
                            data-testid={`button-join-${group.id}`}
                          >
                            <UserPlus className="h-3 w-3 mr-1" />
                            {joinGroupMutation.isPending ? 'Joining...' : 'Join'}
                          </Button>
                        )}
                      </CardContent>
                    </Card>
                  ))}
                </div>
              </ScrollArea>
            </CardContent>
          </Card>
        </div>

        {/* Study Group Details and Chat */}
        <div className="lg:col-span-2">
          {selectedGroup ? (
            <div className="space-y-4">
              {/* Group Header */}
              <Card>
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div>
                      <CardTitle className="flex items-center">
                        {selectedGroup.name}
                        <Badge className="ml-2" variant={selectedGroup.status === 'active' ? 'default' : 'secondary'}>
                          {selectedGroup.status}
                        </Badge>
                      </CardTitle>
                      {selectedGroup.description && (
                        <CardDescription className="mt-1">
                          {selectedGroup.description}
                        </CardDescription>
                      )}
                    </div>
                    
                    <div className="flex items-center space-x-2">
                      <Button size="sm" variant="outline" data-testid="button-whiteboard">
                        <PaintBucket className="h-4 w-4 mr-1" />
                        Whiteboard
                      </Button>
                      <Button size="sm" variant="outline" data-testid="button-video-call">
                        <Video className="h-4 w-4 mr-1" />
                        Video Call
                      </Button>
                    </div>
                  </div>
                  
                  <div className="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                    <span className="flex items-center">
                      <Users className="h-4 w-4 mr-1" />
                      {selectedGroup.currentMembers}/{selectedGroup.maxMembers} members
                    </span>
                    <span className="flex items-center">
                      <Calendar className="h-4 w-4 mr-1" />
                      Created {format(new Date(selectedGroup.createdAt), 'MMMM d, yyyy')}
                    </span>
                  </div>
                  
                  {selectedGroup.tags && selectedGroup.tags.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                      {selectedGroup.tags.map((tag) => (
                        <Badge key={tag} variant="outline">
                          {tag}
                        </Badge>
                      ))}
                    </div>
                  )}
                </CardHeader>
              </Card>

              {/* Chat Interface */}
              {selectedGroup.isMember && (
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center">
                      <MessageCircle className="h-5 w-5 mr-2" />
                      Group Chat
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    {/* Messages */}
                    <ScrollArea className="h-[400px] mb-4 border rounded p-4">
                      <div className="space-y-4">
                        {messages.map((message) => (
                          <div key={message.id} className="flex items-start space-x-2" data-testid={`message-${message.id}`}>
                            <Avatar className="h-8 w-8">
                              <AvatarImage src={message.sender.profileImageUrl} />
                              <AvatarFallback className="text-xs">
                                {message.sender.firstName[0]}{message.sender.lastName[0]}
                              </AvatarFallback>
                            </Avatar>
                            <div className="flex-1 min-w-0">
                              <div className="flex items-center space-x-2 mb-1">
                                <span className="font-medium text-sm">
                                  {message.sender.firstName} {message.sender.lastName}
                                </span>
                                <span className="text-xs text-gray-500">
                                  {format(new Date(message.createdAt), 'HH:mm')}
                                </span>
                              </div>
                              <p className="text-sm text-gray-700 dark:text-gray-300">
                                {message.content}
                              </p>
                            </div>
                          </div>
                        ))}
                      </div>
                    </ScrollArea>

                    {/* Message Input */}
                    <div className="flex space-x-2">
                      <Input
                        placeholder="Type a message..."
                        value={newMessage}
                        onChange={(e) => setNewMessage(e.target.value)}
                        onKeyPress={(e) => {
                          if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            handleSendMessage();
                          }
                        }}
                        data-testid="input-message"
                      />
                      <Button 
                        onClick={handleSendMessage}
                        disabled={!newMessage.trim() || sendMessageMutation.isPending}
                        data-testid="button-send-message"
                      >
                        {sendMessageMutation.isPending ? (
                          <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                          <MessageCircle className="h-4 w-4" />
                        )}
                      </Button>
                    </div>
                  </CardContent>
                </Card>
              )}
            </div>
          ) : (
            <Card>
              <CardContent className="flex items-center justify-center h-[600px]">
                <div className="text-center">
                  <Users className="h-12 w-12 mx-auto text-gray-400 mb-4" />
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    Select a Study Group
                  </h3>
                  <p className="text-gray-600 dark:text-gray-400">
                    Choose a study group from the list to start collaborating
                  </p>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </div>
  );
}