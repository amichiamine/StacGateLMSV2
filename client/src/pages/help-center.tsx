import { useState } from "react";
import { useAuth } from "@/hooks/useAuth";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { 
  Search, 
  HelpCircle, 
  BookOpen, 
  MessageCircle,
  Plus,
  Edit,
  Trash2,
  Eye
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface HelpContent {
  id: string;
  title: string;
  content: string;
  category: string;
  role: string;
  createdAt: string;
  updatedAt: string;
}

export default function HelpCenter() {
  const { user, isAuthenticated } = useAuth();
  const { toast } = useToast();
  const queryClient = useQueryClient();
  
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedCategory, setSelectedCategory] = useState<string>("");
  const [selectedRole, setSelectedRole] = useState<string>("");

  // Get help contents
  const { data: helpContents = [], isLoading } = useQuery<HelpContent[]>({
    queryKey: ['/api/help', { establishmentId: user?.establishmentId, role: selectedRole, category: selectedCategory }],
    enabled: isAuthenticated && !!user?.establishmentId,
  });

  // Search help content
  const { data: searchResults = [], isLoading: searchLoading } = useQuery<HelpContent[]>({
    queryKey: ['/api/help/search', { establishmentId: user?.establishmentId, query: searchQuery, role: selectedRole }],
    enabled: isAuthenticated && !!user?.establishmentId && searchQuery.length > 2,
  });

  // Delete help content mutation
  const deleteHelpMutation = useMutation({
    mutationFn: async (id: string) => {
      const response = await fetch(`/api/help/${id}`, { method: 'DELETE' });
      if (!response.ok) throw new Error('Failed to delete help content');
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/help'] });
      toast({ title: "Succès", description: "Contenu d'aide supprimé avec succès" });
    },
    onError: () => {
      toast({ title: "Erreur", description: "Erreur lors de la suppression", variant: "destructive" });
    }
  });

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Card className="w-full max-w-md">
          <CardContent className="p-6">
            <p className="text-center text-gray-600">Veuillez vous connecter pour accéder au centre d'aide.</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  const displayedContents = searchQuery.length > 2 ? searchResults : helpContents;
  const categories = Array.from(new Set(helpContents.map(content => content.category)));
  const roles = Array.from(new Set(helpContents.map(content => content.role)));

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <div className="container mx-auto p-6">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Centre d'Aide</h1>
            <p className="text-gray-600 dark:text-gray-300 mt-2">Documentation et support pour votre plateforme</p>
          </div>
          {(user?.role === 'admin' || user?.role === 'super_admin' || user?.role === 'manager') && (
            <Button data-testid="button-create-help-content">
              <Plus className="h-4 w-4 mr-2" />
              Nouveau contenu
            </Button>
          )}
        </div>

        {/* Search Bar */}
        <Card className="mb-8" data-testid="card-search">
          <CardContent className="p-6">
            <div className="relative mb-4">
              <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
              <Input
                placeholder="Rechercher dans l'aide..."
                className="pl-10"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                data-testid="input-search-help"
              />
            </div>
            
            <div className="flex flex-wrap gap-4">
              <div className="flex flex-wrap gap-2">
                <Button
                  variant={selectedCategory === "" ? "default" : "outline"}
                  size="sm"
                  onClick={() => setSelectedCategory("")}
                  data-testid="button-category-all"
                >
                  Toutes les catégories
                </Button>
                {categories.map((category) => (
                  <Button
                    key={category}
                    variant={selectedCategory === category ? "default" : "outline"}
                    size="sm"
                    onClick={() => setSelectedCategory(category)}
                    data-testid={`button-category-${category}`}
                  >
                    {category}
                  </Button>
                ))}
              </div>
              
              <Separator orientation="vertical" className="h-8" />
              
              <div className="flex flex-wrap gap-2">
                <Button
                  variant={selectedRole === "" ? "default" : "outline"}
                  size="sm"
                  onClick={() => setSelectedRole("")}
                  data-testid="button-role-all"
                >
                  Tous les rôles
                </Button>
                {roles.map((role) => (
                  <Button
                    key={role}
                    variant={selectedRole === role ? "default" : "outline"}
                    size="sm"
                    onClick={() => setSelectedRole(role)}
                    data-testid={`button-role-${role}`}
                  >
                    {role}
                  </Button>
                ))}
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Content Tabs */}
        <Tabs defaultValue="all" className="w-full">
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="all" data-testid="tab-all">Tout</TabsTrigger>
            <TabsTrigger value="guides" data-testid="tab-guides">Guides</TabsTrigger>
            <TabsTrigger value="faq" data-testid="tab-faq">FAQ</TabsTrigger>
            <TabsTrigger value="tutorials" data-testid="tab-tutorials">Tutoriels</TabsTrigger>
          </TabsList>

          <TabsContent value="all" className="mt-6">
            {isLoading || searchLoading ? (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {[...Array(6)].map((_, i) => (
                  <Card key={i} className="animate-pulse">
                    <CardHeader>
                      <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                      <div className="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                    </CardHeader>
                    <CardContent>
                      <div className="space-y-2">
                        <div className="h-3 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div className="h-3 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            ) : displayedContents.length > 0 ? (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {displayedContents.map((content, index) => (
                  <Card key={content.id} className="hover:shadow-lg transition-shadow" data-testid={`card-help-${index}`}>
                    <CardHeader>
                      <div className="flex items-start justify-between">
                        <div>
                          <CardTitle className="text-lg">{content.title}</CardTitle>
                          <CardDescription className="mt-1">
                            <Badge variant="outline" className="text-xs mr-2" data-testid={`badge-category-${index}`}>
                              {content.category}
                            </Badge>
                            <Badge variant="secondary" className="text-xs" data-testid={`badge-role-${index}`}>
                              {content.role}
                            </Badge>
                          </CardDescription>
                        </div>
                        {(user?.role === 'admin' || user?.role === 'super_admin' || user?.role === 'manager') && (
                          <div className="flex gap-1">
                            <Button 
                              variant="ghost" 
                              size="sm"
                              data-testid={`button-edit-${index}`}
                            >
                              <Edit className="h-3 w-3" />
                            </Button>
                            <Button 
                              variant="ghost" 
                              size="sm"
                              onClick={() => deleteHelpMutation.mutate(content.id)}
                              data-testid={`button-delete-${index}`}
                            >
                              <Trash2 className="h-3 w-3" />
                            </Button>
                          </div>
                        )}
                      </div>
                    </CardHeader>
                    <CardContent>
                      <p className="text-sm text-gray-600 dark:text-gray-300 line-clamp-3" data-testid={`text-content-${index}`}>
                        {content.content.length > 150 ? `${content.content.substring(0, 150)}...` : content.content}
                      </p>
                      <div className="flex items-center justify-between mt-4">
                        <span className="text-xs text-gray-500" data-testid={`text-date-${index}`}>
                          {new Date(content.updatedAt).toLocaleDateString('fr-FR')}
                        </span>
                        <Button variant="ghost" size="sm" data-testid={`button-view-${index}`}>
                          <Eye className="h-3 w-3 mr-1" />
                          Voir
                        </Button>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            ) : (
              <Card data-testid="card-no-results">
                <CardContent className="p-8 text-center">
                  <HelpCircle className="h-12 w-12 mx-auto text-gray-400 mb-4" />
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    Aucun contenu trouvé
                  </h3>
                  <p className="text-gray-600 dark:text-gray-300 mb-4">
                    {searchQuery ? "Aucun résultat pour votre recherche." : "Aucun contenu d'aide disponible pour le moment."}
                  </p>
                  {(user?.role === 'admin' || user?.role === 'super_admin' || user?.role === 'manager') && (
                    <Button data-testid="button-create-first-content">
                      <Plus className="h-4 w-4 mr-2" />
                      Créer le premier contenu
                    </Button>
                  )}
                </CardContent>
              </Card>
            )}
          </TabsContent>

          {/* Other tab contents would be similar but filtered by category */}
          <TabsContent value="guides">
            <div className="text-center p-8">
              <BookOpen className="h-12 w-12 mx-auto text-gray-400 mb-4" />
              <p className="text-gray-600 dark:text-gray-300">Guides utilisateur à venir...</p>
            </div>
          </TabsContent>
          
          <TabsContent value="faq">
            <div className="text-center p-8">
              <MessageCircle className="h-12 w-12 mx-auto text-gray-400 mb-4" />
              <p className="text-gray-600 dark:text-gray-300">FAQ à venir...</p>
            </div>
          </TabsContent>
          
          <TabsContent value="tutorials">
            <div className="text-center p-8">
              <HelpCircle className="h-12 w-12 mx-auto text-gray-400 mb-4" />
              <p className="text-gray-600 dark:text-gray-300">Tutoriels à venir...</p>
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}