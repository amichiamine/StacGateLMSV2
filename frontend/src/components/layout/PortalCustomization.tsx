import { useState, useEffect } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { 
  Palette, 
  Layout, 
  Save, 
  Plus,
  Trash2,
  FileText,
  Menu,
  Edit
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/queryClient";
import PageEditor from "@/components/wysiwyg/PageEditor";

interface Theme {
  id: string;
  name: string;
  isActive: boolean;
  primaryColor: string;
  secondaryColor: string;
  accentColor: string;
  backgroundColor: string;
  textColor: string;
  fontFamily: string;
  fontSize: string;
}

interface CustomizableContent {
  id: string;
  blockKey: string;
  blockType: string;
  content: string;
  isActive: boolean;
}

interface MenuItem {
  id: string;
  label: string;
  url: string;
  icon: string;
  parentId: string | null;
  sortOrder: number;
  isActive: boolean;
  permissions: any;
}

export default function PortalCustomization() {
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const [selectedTheme, setSelectedTheme] = useState<string>("");
  const [currentPage, setCurrentPage] = useState<string>("home");
  const [newTheme, setNewTheme] = useState({
    name: "",
    primaryColor: "#6366f1",
    secondaryColor: "#8b5cf6",
    accentColor: "#10b981",
    backgroundColor: "#ffffff",
    textColor: "#1f2937",
    fontFamily: "Inter",
    fontSize: "16px"
  });

  // Queries - en utilisant les endpoints de Super Admin pour le portail
  const { data: themes = [], isLoading: themesLoading } = useQuery<Theme[]>({
    queryKey: ['/api/super-admin/portal-themes'],
  });

  const { data: contents = [], isLoading: contentsLoading } = useQuery<CustomizableContent[]>({
    queryKey: ['/api/super-admin/portal-contents'],
    staleTime: 0,
    cacheTime: 0,
  });

  const { data: menuItems = [], isLoading: menuLoading } = useQuery<MenuItem[]>({
    queryKey: ['/api/super-admin/portal-menu-items'],
  });

  // Mutations pour les thèmes
  const createThemeMutation = useMutation({
    mutationFn: async (themeData: any) => {
      return await apiRequest('/api/super-admin/portal-themes', 'POST', themeData);
    },
    onSuccess: () => {
      toast({
        title: "Thème créé",
        description: "Le nouveau thème a été créé avec succès",
      });
      queryClient.invalidateQueries({ queryKey: ['/api/super-admin/portal-themes'] });
      setNewTheme({
        name: "",
        primaryColor: "#6366f1",
        secondaryColor: "#8b5cf6",
        accentColor: "#10b981",
        backgroundColor: "#ffffff",
        textColor: "#1f2937",
        fontFamily: "Inter",
        fontSize: "16px"
      });
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible de créer le thème",
        variant: "destructive",
      });
    },
  });

  const activateThemeMutation = useMutation({
    mutationFn: async (themeId: string) => {
      return await apiRequest(`/api/super-admin/portal-themes/${themeId}/activate`, 'POST', {});
    },
    onSuccess: () => {
      toast({
        title: "Thème activé",
        description: "Le thème a été activé avec succès",
      });
      queryClient.invalidateQueries({ queryKey: ['/api/super-admin/portal-themes'] });
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible d'activer le thème",
        variant: "destructive",
      });
    },
  });

  // États locaux pour les contenus
  const [contentValues, setContentValues] = useState<Record<string, string>>({});

  // Initialiser les valeurs des contenus
  useEffect(() => {
    if (contents.length > 0) {
      const initialValues = contents.reduce((acc, content) => {
        acc[content.id] = content.content || '';
        return acc;
      }, {} as Record<string, string>);
      setContentValues(initialValues);
    }
  }, [contents]);

  // Mutation pour mettre à jour le contenu
  const updateContentMutation = useMutation({
    mutationFn: async ({ contentId, content }: { contentId: string; content: string }) => {
      return await apiRequest(`/api/super-admin/portal-contents/${contentId}`, 'PATCH', { content });
    },
    onSuccess: () => {
      toast({
        title: "Contenu mis à jour",
        description: "Le contenu a été mis à jour avec succès",
      });
      queryClient.invalidateQueries({ queryKey: ['/api/super-admin/portal-contents'] });
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible de mettre à jour le contenu",
        variant: "destructive",
      });
    },
  });

  const handleCreateTheme = () => {
    if (!newTheme.name.trim()) {
      toast({
        title: "Erreur",
        description: "Le nom du thème est requis",
        variant: "destructive",
      });
      return;
    }
    createThemeMutation.mutate(newTheme);
  };

  const handleUpdateContent = (contentId: string, content: string) => {
    updateContentMutation.mutate({ contentId, content });
  };

  // États pour les menus
  const [newMenuItem, setNewMenuItem] = useState({
    label: "",
    url: "",
    target: "_self",
    order: 0,
    isActive: true
  });
  const [showAddMenu, setShowAddMenu] = useState(false);

  // Mutations pour les menus
  const createMenuMutation = useMutation({
    mutationFn: async (menuData: any) => {
      return await apiRequest('/api/super-admin/portal-menu-items', 'POST', menuData);
    },
    onSuccess: () => {
      toast({
        title: "Menu ajouté",
        description: "Le nouveau menu a été ajouté avec succès",
      });
      queryClient.invalidateQueries({ queryKey: ['/api/super-admin/portal-menu-items'] });
      setNewMenuItem({
        label: "",
        url: "",
        target: "_self",
        order: 0,
        isActive: true
      });
      setShowAddMenu(false);
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible d'ajouter le menu",
        variant: "destructive",
      });
    }
  });

  const deleteMenuMutation = useMutation({
    mutationFn: async (menuId: string) => {
      return await apiRequest(`/api/super-admin/portal-menu-items/${menuId}`, 'DELETE', {});
    },
    onSuccess: () => {
      toast({
        title: "Menu supprimé",
        description: "Le menu a été supprimé avec succès",
      });
      queryClient.invalidateQueries({ queryKey: ['/api/super-admin/portal-menu-items'] });
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible de supprimer le menu",
        variant: "destructive",
      });
    }
  });

  const handleCreateMenu = () => {
    if (!newMenuItem.label.trim() || !newMenuItem.url.trim()) {
      toast({
        title: "Erreur",
        description: "Le libellé et l'URL sont requis",
        variant: "destructive",
      });
      return;
    }
    createMenuMutation.mutate(newMenuItem);
  };

  if (themesLoading || contentsLoading || menuLoading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-center">
          <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600 dark:text-gray-400">Chargement de la personnalisation du portail...</p>  
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <Tabs defaultValue="wysiwyg" className="space-y-6">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="wysiwyg" className="flex items-center space-x-2">
            <Layout className="w-4 h-4" />
            <span>WYSIWYG</span>
          </TabsTrigger>
          <TabsTrigger value="themes" className="flex items-center space-x-2">
            <Palette className="w-4 h-4" />
            <span>Thèmes</span>
          </TabsTrigger>
          <TabsTrigger value="content" className="flex items-center space-x-2">
            <FileText className="w-4 h-4" />
            <span>Contenus</span>
          </TabsTrigger>
          <TabsTrigger value="menu" className="flex items-center space-x-2">
            <Menu className="w-4 h-4" />
            <span>Menus</span>
          </TabsTrigger>
        </TabsList>

        {/* WYSIWYG Tab */}
        <TabsContent value="wysiwyg" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Éditeur Visuel du Portail</CardTitle>
              <CardDescription>
                Personnalisez visuellement la page d'accueil du portail avec l'éditeur WYSIWYG
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="mb-4">
                <Label htmlFor="page-select">Sélectionner la page à modifier</Label>
                <Select value={currentPage} onValueChange={setCurrentPage}>
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Choisir une page" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="home">Page d'accueil du portail</SelectItem>
                    <SelectItem value="about">À propos</SelectItem>
                    <SelectItem value="contact">Contact</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <PageEditor 
                pageId={`portal-${currentPage}`}
                initialContent=""
                onSave={(content) => {
                  toast({
                    title: "Page sauvegardée",
                    description: "La page du portail a été mise à jour avec succès",
                  });
                }}
              />
            </CardContent>
          </Card>
        </TabsContent>

        {/* Themes Tab */}
        <TabsContent value="themes" className="space-y-6">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <div>
                <CardTitle>Gestion des Thèmes du Portail</CardTitle>
                <CardDescription>
                  Créez et gérez les thèmes visuels pour personnaliser l'apparence du portail
                </CardDescription>
              </div>
            </CardHeader>
            <CardContent>
              {/* Create new theme form */}
              <Card className="mb-6">
                <CardHeader>
                  <CardTitle className="text-lg">Créer un nouveau thème</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="theme-name">Nom du thème</Label>
                      <Input
                        id="theme-name"
                        value={newTheme.name}
                        onChange={(e) => setNewTheme({...newTheme, name: e.target.value})}
                        placeholder="Ex: Thème Bleu Corporate"
                      />
                    </div>
                    <div>
                      <Label htmlFor="font-family">Police</Label>
                      <Select value={newTheme.fontFamily} onValueChange={(value) => setNewTheme({...newTheme, fontFamily: value})}>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="Inter">Inter</SelectItem>
                          <SelectItem value="Roboto">Roboto</SelectItem>
                          <SelectItem value="Open Sans">Open Sans</SelectItem>
                          <SelectItem value="Lato">Lato</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  
                  <div className="grid grid-cols-3 gap-4">
                    <div>
                      <Label htmlFor="primary-color">Couleur Primaire</Label>
                      <div className="flex items-center space-x-2">
                        <Input
                          id="primary-color"
                          type="color"
                          value={newTheme.primaryColor}
                          onChange={(e) => setNewTheme({...newTheme, primaryColor: e.target.value})}
                          className="w-12 h-10 p-1 border rounded"
                        />
                        <Input
                          value={newTheme.primaryColor}
                          onChange={(e) => setNewTheme({...newTheme, primaryColor: e.target.value})}
                          placeholder="#6366f1"
                        />
                      </div>
                    </div>
                    
                    <div>
                      <Label htmlFor="secondary-color">Couleur Secondaire</Label>
                      <div className="flex items-center space-x-2">
                        <Input
                          id="secondary-color"
                          type="color"
                          value={newTheme.secondaryColor}
                          onChange={(e) => setNewTheme({...newTheme, secondaryColor: e.target.value})}
                          className="w-12 h-10 p-1 border rounded"
                        />
                        <Input
                          value={newTheme.secondaryColor}
                          onChange={(e) => setNewTheme({...newTheme, secondaryColor: e.target.value})}
                          placeholder="#8b5cf6"
                        />
                      </div>
                    </div>
                    
                    <div>
                      <Label htmlFor="accent-color">Couleur d'Accent</Label>
                      <div className="flex items-center space-x-2">
                        <Input
                          id="accent-color"
                          type="color"
                          value={newTheme.accentColor}
                          onChange={(e) => setNewTheme({...newTheme, accentColor: e.target.value})}
                          className="w-12 h-10 p-1 border rounded"
                        />
                        <Input
                          value={newTheme.accentColor}
                          onChange={(e) => setNewTheme({...newTheme, accentColor: e.target.value})}
                          placeholder="#10b981"
                        />
                      </div>
                    </div>
                    
                    <div>
                      <Label htmlFor="bg-color">Couleur de Fond</Label>
                      <div className="flex items-center space-x-2">
                        <Input
                          id="bg-color"
                          type="color"
                          value={newTheme.backgroundColor}
                          onChange={(e) => setNewTheme({...newTheme, backgroundColor: e.target.value})}
                          className="w-12 h-10 p-1 border rounded"
                        />
                        <Input
                          value={newTheme.backgroundColor}
                          onChange={(e) => setNewTheme({...newTheme, backgroundColor: e.target.value})}
                          placeholder="#ffffff"
                        />
                      </div>
                    </div>
                    
                    <div>
                      <Label htmlFor="text-color">Couleur du Texte</Label>
                      <div className="flex items-center space-x-2">
                        <Input
                          id="text-color"
                          type="color"
                          value={newTheme.textColor}
                          onChange={(e) => setNewTheme({...newTheme, textColor: e.target.value})}
                          className="w-12 h-10 p-1 border rounded"
                        />
                        <Input
                          value={newTheme.textColor}
                          onChange={(e) => setNewTheme({...newTheme, textColor: e.target.value})}
                          placeholder="#1f2937"
                        />
                      </div>
                    </div>
                    
                    <div>
                      <Label htmlFor="font-size">Taille de Police</Label>
                      <Select value={newTheme.fontSize} onValueChange={(value) => setNewTheme({...newTheme, fontSize: value})}>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="14px">14px</SelectItem>
                          <SelectItem value="16px">16px</SelectItem>
                          <SelectItem value="18px">18px</SelectItem>
                          <SelectItem value="20px">20px</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  
                  <Button 
                    onClick={handleCreateTheme}
                    disabled={createThemeMutation.isPending}
                    className="w-full"
                  >
                    <Plus className="h-4 w-4 mr-2" />
                    {createThemeMutation.isPending ? "Création..." : "Créer le thème"}
                  </Button>
                </CardContent>
              </Card>

              {/* Existing themes */}
              <div className="grid gap-4">
                {themes.map((theme) => (
                  <Card key={theme.id} className={theme.isActive ? "ring-2 ring-blue-500" : ""}>
                    <CardContent className="p-4">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                          <div className="flex space-x-1">
                            <div 
                              className="w-6 h-6 rounded-full border-2 border-white shadow-sm"
                              style={{ backgroundColor: theme.primaryColor }}
                            />
                            <div 
                              className="w-6 h-6 rounded-full border-2 border-white shadow-sm"
                              style={{ backgroundColor: theme.secondaryColor }}
                            />
                            <div 
                              className="w-6 h-6 rounded-full border-2 border-white shadow-sm"
                              style={{ backgroundColor: theme.accentColor }}
                            />
                          </div>
                          <div>
                            <h3 className="font-medium text-gray-900 dark:text-white">{theme.name}</h3>
                            <p className="text-sm text-gray-500">{theme.fontFamily} • {theme.fontSize}</p>
                          </div>
                        </div>
                        <div className="flex items-center space-x-2">
                          <Badge variant={theme.isActive ? "default" : "secondary"}>
                            {theme.isActive ? "Actif" : "Inactif"}
                          </Badge>
                          {!theme.isActive && (
                            <Button
                              size="sm"
                              onClick={() => activateThemeMutation.mutate(theme.id)}
                              disabled={activateThemeMutation.isPending}
                            >
                              Activer
                            </Button>
                          )}
                          <Button size="sm" variant="outline">
                            Modifier
                          </Button>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Content Tab */}
        <TabsContent value="content" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Contenus Personnalisables du Portail</CardTitle>
              <CardDescription>
                Modifiez les textes et contenus affichés sur la page d'accueil du portail
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4">
                {contents.map((content) => (
                  <Card key={content.id}>
                    <CardHeader>
                      <CardTitle className="text-lg capitalize">
                        {content.blockKey ? content.blockKey.replace(/_/g, ' ') : 'Contenu'}
                      </CardTitle>
                      <Badge variant="outline">{content.blockType}</Badge>
                    </CardHeader>
                    <CardContent>
                      {content.blockType === 'textarea' || content.blockType === 'html' ? (
                        <div className="space-y-2">
                          <Textarea
                            value={contentValues[content.id] || ''}
                            onChange={(e) => {
                              setContentValues(prev => ({
                                ...prev,
                                [content.id]: e.target.value
                              }));
                            }}
                            className="min-h-[120px]"
                            placeholder={`Modifier ${content.blockKey ? content.blockKey.replace(/_/g, ' ') : 'contenu'}`}
                          />
                          <Button
                            size="sm"
                            onClick={() => handleUpdateContent(content.id, contentValues[content.id] || '')}
                            disabled={updateContentMutation.isPending}
                          >
                            <Save className="h-4 w-4 mr-1" />
                            Sauvegarder
                          </Button>
                        </div>
                      ) : (
                        <div className="space-y-2">
                          <Input
                            value={contentValues[content.id] || ''}
                            onChange={(e) => {
                              setContentValues(prev => ({
                                ...prev,
                                [content.id]: e.target.value
                              }));
                            }}
                            placeholder={`Modifier ${content.blockKey ? content.blockKey.replace(/_/g, ' ') : 'contenu'}`}
                          />
                          <Button
                            size="sm"
                            onClick={() => handleUpdateContent(content.id, contentValues[content.id] || '')}
                            disabled={updateContentMutation.isPending}
                          >
                            <Save className="h-4 w-4 mr-1" />
                            Sauvegarder
                          </Button>
                        </div>
                      )}
                    </CardContent>
                  </Card>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Menu Tab */}
        <TabsContent value="menu" className="space-y-6">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <div>
                <CardTitle>Gestion des Menus du Portail</CardTitle>
                <CardDescription>
                  Configurez la navigation et les menus de la page d'accueil du portail
                </CardDescription>
              </div>
              <Button onClick={() => setShowAddMenu(true)}>
                <Plus className="h-4 w-4 mr-2" />
                Ajouter Menu
              </Button>
            </CardHeader>
            <CardContent>
              {showAddMenu && (
                <Card className="mb-6 border-2 border-dashed border-blue-300">
                  <CardHeader>
                    <CardTitle className="text-lg">Nouveau Menu</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <Label htmlFor="menuLabel">Libellé</Label>
                        <Input
                          id="menuLabel"
                          value={newMenuItem.label}
                          onChange={(e) => setNewMenuItem({...newMenuItem, label: e.target.value})}
                          placeholder="Accueil"
                        />
                      </div>
                      <div>
                        <Label htmlFor="menuUrl">URL</Label>
                        <Input
                          id="menuUrl"
                          value={newMenuItem.url}
                          onChange={(e) => setNewMenuItem({...newMenuItem, url: e.target.value})}
                          placeholder="/accueil"
                        />
                      </div>
                    </div>
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          id="menuActive"
                          checked={newMenuItem.isActive}
                          onChange={(e) => setNewMenuItem({...newMenuItem, isActive: e.target.checked})}
                          className="rounded border-gray-300"
                        />
                        <Label htmlFor="menuActive">Menu actif</Label>
                      </div>
                      <div className="flex items-center space-x-2">
                        <Button variant="outline" onClick={() => setShowAddMenu(false)}>
                          Annuler
                        </Button>
                        <Button onClick={handleCreateMenu} disabled={createMenuMutation.isPending}>
                          <Save className="h-4 w-4 mr-2" />
                          Créer Menu
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}

              <div className="grid gap-4">
                {menuItems.map((menuItem) => (
                  <Card key={menuItem.id}>
                    <CardContent className="p-4">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                          <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <Menu className="w-4 h-4 text-blue-600" />
                          </div>
                          <div>
                            <h3 className="font-medium text-gray-900 dark:text-white">{menuItem.label}</h3>
                            <p className="text-sm text-gray-500">{menuItem.url}</p>
                          </div>
                        </div>
                        <div className="flex items-center space-x-2">
                          <Badge variant={menuItem.isActive ? "default" : "secondary"}>
                            {menuItem.isActive ? "Actif" : "Inactif"}
                          </Badge>
                          <Button size="sm" variant="outline">
                            <Edit className="h-4 w-4" />
                          </Button>
                          <Button 
                            size="sm" 
                            variant="destructive"
                            onClick={() => deleteMenuMutation.mutate(menuItem.id)}
                            disabled={deleteMenuMutation.isPending}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}