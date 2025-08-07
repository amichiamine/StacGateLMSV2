import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Settings, Palette, FileText, Menu, Save, Plus, Trash2, Layout, Building2, Users, Shield, BookOpen } from "lucide-react";
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

export default function AdminPage() {
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const [selectedEstablishment, setSelectedEstablishment] = useState<string>("");
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

  const [newEstablishment, setNewEstablishment] = useState({
    name: "",
    slug: "",
    description: "",
    logo: "",
    domain: ""
  });

  const [showEstablishmentForm, setShowEstablishmentForm] = useState(false);
  
  const [newUser, setNewUser] = useState({
    firstName: "",
    lastName: "",
    email: "",
    password: "",
    establishmentId: "",
    role: "apprenant"
  });

  const [showUserForm, setShowUserForm] = useState(false);

  // Fetch themes
  const { data: themes = [], isLoading: themesLoading } = useQuery<Theme[]>({
    queryKey: ["/api/admin/themes"],
  });

  // Fetch customizable contents
  const { data: contents = [], isLoading: contentsLoading } = useQuery<CustomizableContent[]>({
    queryKey: ["/api/admin/customizable-contents"],
  });

  // Fetch menu items
  const { data: menuItems = [], isLoading: menuLoading } = useQuery<MenuItem[]>({
    queryKey: ["/api/admin/menu-items"],
  });

  // Fetch establishments
  const { data: establishments = [], isLoading: establishmentsLoading } = useQuery<any[]>({
    queryKey: ["/api/admin/establishments"],
  });

  // Fetch users
  const { data: users = [], isLoading: usersLoading } = useQuery<any[]>({
    queryKey: ["/api/admin/users"],
  });

  // Create theme mutation
  const createThemeMutation = useMutation({
    mutationFn: async (themeData: any) => {
      const response = await fetch("/api/admin/themes", {
        method: "POST",
        body: JSON.stringify(themeData),
        headers: {
          'Content-Type': 'application/json',
        },
      });
      if (!response.ok) throw new Error('Failed to create theme');
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Succès",
        description: "Thème créé avec succès",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/admin/themes"] });
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

  // Activate theme mutation
  const activateThemeMutation = useMutation({
    mutationFn: async (themeId: string) => {
      const response = await fetch(`/api/admin/themes/${themeId}/activate`, {
        method: "POST",
        headers: {
          'Content-Type': 'application/json',
        },
      });
      if (!response.ok) throw new Error('Failed to activate theme');
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Succès",
        description: "Thème activé avec succès",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/admin/themes"] });
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible d'activer le thème",
        variant: "destructive",
      });
    },
  });

  // Update content mutation
  const updateContentMutation = useMutation({
    mutationFn: async ({ id, content }: { id: string; content: string }) => {
      const response = await fetch(`/api/admin/customizable-contents/${id}`, {
        method: "PATCH",
        body: JSON.stringify({ content }),
        headers: {
          'Content-Type': 'application/json',
        },
      });
      if (!response.ok) throw new Error('Failed to update content');
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Succès",
        description: "Contenu mis à jour avec succès",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/admin/customizable-contents"] });
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

  const handleActivateTheme = (themeId: string) => {
    activateThemeMutation.mutate(themeId);
  };

  const handleUpdateContent = (id: string, content: string) => {
    updateContentMutation.mutate({ id, content });
  };

  // Fetch courses
  const { data: courses = [], isLoading: coursesLoading } = useQuery({
    queryKey: ['/api/courses'],
    enabled: selectedEstablishment !== ""
  });

  // Mutations pour gestion des cours
  const deleteCourseMutation = useMutation({
    mutationFn: async (courseId: string) => {
      const response = await fetch(`/api/courses/${courseId}`, {
        method: 'DELETE'
      });
      if (!response.ok) throw new Error('Failed to delete course');
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Succès",
        description: "Le cours a été supprimé avec succès.",
      });
      queryClient.invalidateQueries({ queryKey: ['/api/courses'] });
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible de supprimer le cours.",
        variant: "destructive",
      });
    },
  });

  // Create establishment mutation
  const createEstablishmentMutation = useMutation({
    mutationFn: async (establishmentData: any) => {
      const response = await fetch("/api/admin/establishments", {
        method: "POST",
        body: JSON.stringify(establishmentData),
        headers: {
          'Content-Type': 'application/json',
        },
      });
      if (!response.ok) throw new Error('Failed to create establishment');
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Succès",
        description: "Établissement créé avec succès",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/admin/establishments"] });
      setNewEstablishment({
        name: "",
        slug: "",
        description: "",
        logo: "",
        domain: ""
      });
      setShowEstablishmentForm(false);
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible de créer l'établissement",
        variant: "destructive",
      });
    },
  });

  const handleCreateEstablishment = () => {
    if (!newEstablishment.name.trim()) {
      toast({
        title: "Erreur",
        description: "Le nom de l'établissement est requis",
        variant: "destructive",
      });
      return;
    }
    
    // Generate slug from name if not provided
    const slug = newEstablishment.slug || newEstablishment.name.toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
    
    createEstablishmentMutation.mutate({
      ...newEstablishment,
      slug
    });
  };

  // Create user mutation
  const createUserMutation = useMutation({
    mutationFn: async (userData: any) => {
      const response = await fetch("/api/admin/users", {
        method: "POST",
        body: JSON.stringify(userData),
        headers: {
          'Content-Type': 'application/json',
        },
      });
      if (!response.ok) throw new Error('Failed to create user');
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Succès",
        description: "Utilisateur créé avec succès",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/admin/users"] });
      setNewUser({
        firstName: "",
        lastName: "",
        email: "",
        password: "",
        establishmentId: "",
        role: "apprenant"
      });
      setShowUserForm(false);
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible de créer l'utilisateur",
        variant: "destructive",
      });
    },
  });

  const handleCreateUser = () => {
    if (!newUser.firstName.trim() || !newUser.lastName.trim() || !newUser.email.trim() || !newUser.password.trim() || !newUser.establishmentId) {
      toast({
        title: "Erreur",
        description: "Tous les champs requis doivent être remplis",
        variant: "destructive",
      });
      return;
    }
    
    // Generate username from email
    const username = newUser.email.split('@')[0];
    
    createUserMutation.mutate({
      ...newUser,
      username
    });
  };

  if (themesLoading || contentsLoading || menuLoading || establishmentsLoading || usersLoading) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600 dark:text-gray-400">Chargement de l'administration...</p>
        </div>
      </div>
    );
  }

  // Filter data by selected establishment
  const filteredUsers = selectedEstablishment 
    ? (users || []).filter((user: any) => user.establishmentId === selectedEstablishment)
    : (users || []);

  const selectedEstablishmentData = (establishments || []).find((est: any) => est.id === selectedEstablishment);

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
      {/* Header unifié avec même style */}
      <header className="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm shadow-sm border-b rounded-b-3xl">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-2">
              <Settings className="w-8 h-8 text-blue-600" />
              <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Administration StacGate</h1>
            </div>
            <div className="flex items-center space-x-4">
              <Button 
                variant="outline" 
                size="sm"
                onClick={() => window.location.href = '/'}
                className="flex items-center gap-2 hover:bg-blue-50 hover:border-blue-300"
              >
                <BookOpen className="h-4 w-4" />
                Accueil
              </Button>
              <Button 
                variant="outline"  
                size="sm"
                onClick={() => window.location.href = '/dashboard'}
                className="flex items-center gap-2 hover:bg-blue-50 hover:border-blue-300"
              >
                <BookOpen className="h-4 w-4" />
                Dashboard
              </Button>
              <Button 
                variant="destructive" 
                size="sm"
                onClick={() => window.location.href = '/api/auth/logout'}
                className="bg-red-600 hover:bg-red-700"
              >
                Déconnexion
              </Button>
            </div>
          </div>
        </div>
      </header>

      <div className="container mx-auto py-6 px-4">
        <div className="mb-6">
          <p className="text-gray-600 dark:text-gray-300 text-lg">
            Gestion multi-établissement - Personnalisez chaque établissement
          </p>
        </div>

        {/* Establishment Selector avec style unifié */}
        <div className="mb-6 bg-white/80 backdrop-blur-sm dark:bg-gray-800/80 rounded-lg p-4 shadow-lg">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <Building2 className="w-4 h-4 text-blue-600" />
              </div>
              <span className="font-medium text-gray-900 dark:text-white">Établissement actuel :</span>
            </div>
            <Select value={selectedEstablishment} onValueChange={setSelectedEstablishment}>
              <SelectTrigger className="w-80">
                <SelectValue placeholder="Sélectionner un établissement" />
              </SelectTrigger>
              <SelectContent>
                {(establishments || []).map((establishment: any) => (
                  <SelectItem key={establishment.id} value={establishment.id}>
                    {establishment.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {selectedEstablishmentData && (
              <div className="text-sm text-gray-600 dark:text-gray-400">
                {selectedEstablishmentData.description}
              </div>
            )}
          </div>
        </div>

        {/* Warning when no establishment selected */}
        {!selectedEstablishment && (
          <div className="mb-6 bg-amber-50/80 backdrop-blur-sm dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 shadow-lg">
            <div className="flex items-center gap-2 text-amber-800 dark:text-amber-400">
              <div className="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                <Building2 className="w-4 h-4 text-amber-600" />
              </div>
              <span className="font-medium">Sélectionner un établissement</span>
            </div>
            <p className="text-amber-700 dark:text-amber-300 mt-1">
              Veuillez sélectionner un établissement pour accéder aux fonctions d'administration spécifiques.
            </p>
          </div>
        )}

        <Tabs defaultValue={selectedEstablishment ? "users" : "establishments"} className="space-y-6">
          <TabsList className={selectedEstablishment 
            ? "grid w-full grid-cols-7" 
            : "grid w-full grid-cols-1"
          }>
            {!selectedEstablishment && (
              <TabsTrigger value="establishments" className="flex items-center space-x-2">
                <Building2 className="w-4 h-4" />
                <span>Établissements</span>
              </TabsTrigger>
            )}
            {selectedEstablishment && (
              <>
                <TabsTrigger value="users" className="flex items-center space-x-2">
                  <Users className="w-4 h-4" />
                  <span>Utilisateurs</span>
                </TabsTrigger>
                <TabsTrigger value="courses" className="flex items-center space-x-2">
                  <BookOpen className="w-4 h-4" />
                  <span>Cours</span>
                </TabsTrigger>
                <TabsTrigger value="permissions" className="flex items-center space-x-2">
                  <Shield className="w-4 h-4" />
                  <span>Rôles</span>
                </TabsTrigger>
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
              </>
            )}
          </TabsList>

          {/* Establishments Tab */}
          <TabsContent value="establishments" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Building2 className="h-5 w-5" />
                  Gestion des Établissements
                </CardTitle>
                <CardDescription>
                  Créez et gérez les établissements de votre plateforme multi-académie
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="grid gap-4">
                  {(establishments || []).map((establishment: any) => (
                    <div key={establishment.id} className="flex items-center justify-between p-4 border rounded-lg">
                      <div>
                        <h3 className="font-medium">{establishment.name}</h3>
                        <p className="text-sm text-muted-foreground">{establishment.description}</p>
                        <span className={`inline-block px-2 py-1 text-xs rounded-full ${
                          establishment.isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                        }`}>
                          {establishment.isActive ? 'Actif' : 'Inactif'}
                        </span>
                      </div>
                      <div className="flex gap-2">
                        <Button variant="outline" size="sm">Modifier</Button>
                        <Button variant="destructive" size="sm">
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </div>
                  ))}
                  {!showEstablishmentForm ? (
                    <Button 
                      className="w-full"
                      onClick={() => setShowEstablishmentForm(true)}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      Ajouter un établissement
                    </Button>
                  ) : (
                    <div className="border rounded-lg p-4 space-y-4">
                      <h4 className="font-medium">Nouvel établissement</h4>
                      <div className="grid gap-4">
                        <div>
                          <Label htmlFor="establishment-name">Nom *</Label>
                          <Input
                            id="establishment-name"
                            value={newEstablishment.name}
                            onChange={(e) => setNewEstablishment({ ...newEstablishment, name: e.target.value })}
                            placeholder="École Supérieure de Commerce"
                          />
                        </div>
                        <div>
                          <Label htmlFor="establishment-slug">Identifiant URL</Label>
                          <Input
                            id="establishment-slug"
                            value={newEstablishment.slug}
                            onChange={(e) => setNewEstablishment({ ...newEstablishment, slug: e.target.value })}
                            placeholder="ecole-commerce (généré automatiquement)"
                          />
                        </div>
                        <div>
                          <Label htmlFor="establishment-description">Description</Label>
                          <Textarea
                            id="establishment-description"
                            value={newEstablishment.description}
                            onChange={(e) => setNewEstablishment({ ...newEstablishment, description: e.target.value })}
                            placeholder="Description de votre établissement"
                          />
                        </div>
                        <div>
                          <Label htmlFor="establishment-domain">Domaine personnalisé</Label>
                          <Input
                            id="establishment-domain"
                            value={newEstablishment.domain}
                            onChange={(e) => setNewEstablishment({ ...newEstablishment, domain: e.target.value })}
                            placeholder="mon-ecole.com (optionnel)"
                          />
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <Button 
                          onClick={handleCreateEstablishment}
                          disabled={createEstablishmentMutation.isPending}
                          className="flex-1"
                        >
                          <Save className="h-4 w-4 mr-2" />
                          Créer
                        </Button>
                        <Button 
                          variant="outline"
                          onClick={() => {
                            setShowEstablishmentForm(false);
                            setNewEstablishment({
                              name: "",
                              slug: "",
                              description: "",
                              logo: "",
                              domain: ""
                            });
                          }}
                        >
                          Annuler
                        </Button>
                      </div>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Courses Tab Content */}
          <TabsContent value="courses" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <BookOpen className="h-5 w-5" />
                  Gestion des Cours
                </CardTitle>
                <CardDescription>
                  Gérez les cours de l'établissement sélectionné
                </CardDescription>
              </CardHeader>
              <CardContent>
                {!selectedEstablishment ? (
                  <div className="text-center py-8 text-gray-500">
                    <BookOpen className="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p>Sélectionnez un établissement pour voir ses cours</p>
                  </div>
                ) : coursesLoading ? (
                  <div className="text-center py-8 text-gray-500">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                    <p>Chargement des cours...</p>
                  </div>
                ) : (
                  <div className="space-y-4">
                    <div className="flex justify-between items-center">
                      <div className="text-sm text-gray-600">
                        {Array.isArray(courses) ? courses.length : 0} cours trouvé(s)
                      </div>
                      <Button 
                        onClick={() => window.location.href = '/courses'}
                        className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700"
                      >
                        <Plus className="w-4 h-4 mr-2" />
                        Créer un cours
                      </Button>
                    </div>
                    
                    <div className="grid gap-4">
                      {Array.isArray(courses) ? courses.map((course: any) => (
                        <div key={course.id} className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                          <div className="flex-1">
                            <h4 className="font-semibold text-lg">{course.title}</h4>
                            <p className="text-gray-600 text-sm mt-1 line-clamp-2">{course.description}</p>
                            <div className="flex items-center space-x-4 mt-2">
                              <span className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                {course.category}
                              </span>
                              <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                {course.level === 'debutant' ? 'Débutant' : 
                                 course.level === 'intermediaire' ? 'Intermédiaire' : 
                                 course.level === 'avance' ? 'Avancé' : course.level}
                              </span>
                              <span className="text-xs text-gray-500">
                                {course.duration}h • ★ {course.rating}
                              </span>
                              <span className="text-xs font-medium text-gray-900">
                                {course.isFree ? 'Gratuit' : `${course.price}€`}
                              </span>
                            </div>
                          </div>
                          <div className="flex space-x-2">
                            <Button 
                              variant="outline" 
                              size="sm"
                              onClick={() => {
                                toast({
                                  title: "Info",
                                  description: "Fonction d'édition disponible prochainement",
                                });
                              }}
                            >
                              Modifier
                            </Button>
                            <Button 
                              variant="outline" 
                              size="sm"
                              onClick={() => deleteCourseMutation.mutate(course.id)}
                              disabled={deleteCourseMutation.isPending}
                              className="text-red-600 border-red-200 hover:bg-red-50"
                            >
                              <Trash2 className="w-4 h-4" />
                            </Button>
                          </div>
                        </div>
                      )) : []}
                    </div>
                    
                    {(!Array.isArray(courses) || courses.length === 0) && (
                      <div className="text-center py-12">
                        <BookOpen className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">Aucun cours trouvé</h3>
                        <p className="text-gray-500 mb-4">Cet établissement n'a pas encore de cours.</p>
                        <Button 
                          onClick={() => window.location.href = '/courses'}
                          className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700"
                        >
                          Créer le premier cours
                        </Button>
                      </div>
                    )}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Users Tab */}
          <TabsContent value="users" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Users className="h-5 w-5" />
                  Gestion des Utilisateurs
                </CardTitle>
                <CardDescription>
                  Gérez les utilisateurs et leurs rôles par établissement
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="grid gap-4">
                  {selectedEstablishment ? (
                    <>
                      <div className="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div className="text-sm text-blue-800 dark:text-blue-400">
                          <strong>Établissement :</strong> {selectedEstablishmentData?.name} 
                          <span className="ml-2 text-blue-600 dark:text-blue-300">
                            ({filteredUsers.length} utilisateur{filteredUsers.length > 1 ? 's' : ''})
                          </span>
                        </div>
                      </div>
                      {filteredUsers.map((user: any) => (
                        <div key={user.id} className="flex items-center justify-between p-4 border rounded-lg">
                          <div>
                            <h3 className="font-medium">{user.firstName} {user.lastName}</h3>
                            <p className="text-sm text-muted-foreground">{user.email}</p>
                            <div className="flex gap-2 mt-2">
                              <span className={`inline-block px-2 py-1 text-xs rounded-full ${
                                user.role === 'admin' ? 'bg-red-100 text-red-800' :
                                user.role === 'manager' ? 'bg-blue-100 text-blue-800' :
                                user.role === 'formateur' ? 'bg-purple-100 text-purple-800' :
                                'bg-green-100 text-green-800'
                              }`}>
                                {user.role}
                              </span>
                              <span className={`inline-block px-2 py-1 text-xs rounded-full ${
                                user.isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                              }`}>
                                {user.isActive ? 'Actif' : 'Inactif'}
                              </span>
                            </div>
                          </div>
                          <div className="flex gap-2">
                            <Button variant="outline" size="sm">Modifier</Button>
                            <Button variant="destructive" size="sm">
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </div>
                      ))}
                    </>
                  ) : (
                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                      <Users className="w-12 h-12 mx-auto mb-4 opacity-50" />
                      <p>Sélectionnez un établissement pour voir ses utilisateurs</p>
                    </div>
                  )}
                  
                  {selectedEstablishment && !showUserForm && (
                    <Button 
                      className="w-full"
                      onClick={() => setShowUserForm(true)}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      Ajouter un utilisateur
                    </Button>
                  )}
                  
                  {selectedEstablishment && showUserForm && (
                    <div className="border rounded-lg p-4 space-y-4">
                      <h4 className="font-medium">Nouvel utilisateur</h4>
                      <div className="grid gap-4">
                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <Label htmlFor="user-firstName">Prénom *</Label>
                            <Input
                              id="user-firstName"
                              value={newUser.firstName}
                              onChange={(e) => setNewUser({ ...newUser, firstName: e.target.value })}
                              placeholder="Jean"
                            />
                          </div>
                          <div>
                            <Label htmlFor="user-lastName">Nom *</Label>
                            <Input
                              id="user-lastName"
                              value={newUser.lastName}
                              onChange={(e) => setNewUser({ ...newUser, lastName: e.target.value })}
                              placeholder="Dupont"
                            />
                          </div>
                        </div>
                        <div>
                          <Label htmlFor="user-email">Email *</Label>
                          <Input
                            id="user-email"
                            type="email"
                            value={newUser.email}
                            onChange={(e) => setNewUser({ ...newUser, email: e.target.value })}
                            placeholder="jean.dupont@email.com"
                          />
                        </div>
                        <div>
                          <Label htmlFor="user-password">Mot de passe *</Label>
                          <Input
                            id="user-password"
                            type="password"
                            value={newUser.password}
                            onChange={(e) => setNewUser({ ...newUser, password: e.target.value })}
                            placeholder="Mot de passe sécurisé"
                          />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <Label htmlFor="user-establishment">Établissement *</Label>
                            <Select 
                              value={newUser.establishmentId} 
                              onValueChange={(value) => setNewUser({ ...newUser, establishmentId: value })}
                            >
                              <SelectTrigger>
                                <SelectValue placeholder="Choisir un établissement" />
                              </SelectTrigger>
                              <SelectContent>
                                {(establishments || []).map((establishment: any) => (
                                  <SelectItem key={establishment.id} value={establishment.id}>
                                    {establishment.name}
                                  </SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
                          </div>
                          <div>
                            <Label htmlFor="user-role">Rôle *</Label>
                            <Select 
                              value={newUser.role} 
                              onValueChange={(value) => setNewUser({ ...newUser, role: value })}
                            >
                              <SelectTrigger>
                                <SelectValue placeholder="Choisir un rôle" />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="super_admin">Super Admin</SelectItem>
                                <SelectItem value="admin">Admin</SelectItem>
                                <SelectItem value="manager">Manager</SelectItem>
                                <SelectItem value="formateur">Formateur</SelectItem>
                                <SelectItem value="apprenant">Apprenant</SelectItem>
                              </SelectContent>
                            </Select>
                          </div>
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <Button 
                          onClick={handleCreateUser}
                          disabled={createUserMutation.isPending}
                          className="flex-1"
                        >
                          <Save className="h-4 w-4 mr-2" />
                          Créer
                        </Button>
                        <Button 
                          variant="outline"
                          onClick={() => {
                            setShowUserForm(false);
                            setNewUser({
                              firstName: "",
                              lastName: "",
                              email: "",
                              password: "",
                              establishmentId: "",
                              role: "apprenant"
                            });
                          }}
                        >
                          Annuler
                        </Button>
                      </div>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Permissions Tab */}
          <TabsContent value="permissions" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Shield className="h-5 w-5" />
                  Gestion des Rôles et Permissions
                </CardTitle>
                <CardDescription>
                  Configurez les permissions pour chaque rôle utilisateur
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="grid gap-6">
                  {['admin', 'manager', 'formateur', 'apprenant'].map((role) => (
                    <div key={role} className="border rounded-lg p-4">
                      <h3 className="font-medium text-lg mb-3 capitalize">{role}</h3>
                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <h4 className="font-medium mb-2">Permissions système</h4>
                          <div className="space-y-2">
                            <label className="flex items-center space-x-2">
                              <input type="checkbox" className="rounded" />
                              <span className="text-sm">Gérer les établissements</span>
                            </label>
                            <label className="flex items-center space-x-2">
                              <input type="checkbox" className="rounded" />
                              <span className="text-sm">Gérer les utilisateurs</span>
                            </label>
                            <label className="flex items-center space-x-2">
                              <input type="checkbox" className="rounded" />
                              <span className="text-sm">Accès administration</span>
                            </label>
                          </div>
                        </div>
                        <div>
                          <h4 className="font-medium mb-2">Permissions contenu</h4>
                          <div className="space-y-2">
                            <label className="flex items-center space-x-2">
                              <input type="checkbox" className="rounded" />
                              <span className="text-sm">Créer des cours</span>
                            </label>
                            <label className="flex items-center space-x-2">
                              <input type="checkbox" className="rounded" />
                              <span className="text-sm">Modifier les thèmes</span>
                            </label>
                            <label className="flex items-center space-x-2">
                              <input type="checkbox" className="rounded" />
                              <span className="text-sm">Publier du contenu</span>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* WYSIWYG Tab */}
          <TabsContent value="wysiwyg" className="space-y-6">
            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div className="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                  Éditeur de pages WYSIWYG
                </h3>
                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  Personnalisez toutes les pages de votre plateforme avec l'éditeur visuel
                </p>
              </div>
              
              <div className="h-[800px]">
                <PageEditor 
                  pageName={currentPage} 
                  onPageChange={setCurrentPage}
                />
              </div>
            </div>
          </TabsContent>

          {/* Themes Tab */}
          <TabsContent value="themes" className="space-y-6">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Create New Theme */}
              <Card>
                <CardHeader>
                  <CardTitle>Créer un nouveau thème</CardTitle>
                  <CardDescription>
                    Personnalisez l'apparence de votre plateforme
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div>
                    <Label htmlFor="theme-name">Nom du thème</Label>
                    <Input
                      id="theme-name"
                      value={newTheme.name}
                      onChange={(e) => setNewTheme({ ...newTheme, name: e.target.value })}
                      placeholder="Mon thème personnalisé"
                    />
                  </div>
                  
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="primary-color">Couleur primaire</Label>
                      <div className="flex space-x-2">
                        <Input
                          type="color"
                          id="primary-color"
                          value={newTheme.primaryColor}
                          onChange={(e) => setNewTheme({ ...newTheme, primaryColor: e.target.value })}
                          className="w-16 h-10"
                        />
                        <Input
                          value={newTheme.primaryColor}
                          onChange={(e) => setNewTheme({ ...newTheme, primaryColor: e.target.value })}
                          className="flex-1"
                        />
                      </div>
                    </div>
                    
                    <div>
                      <Label htmlFor="secondary-color">Couleur secondaire</Label>
                      <div className="flex space-x-2">
                        <Input
                          type="color"
                          id="secondary-color"
                          value={newTheme.secondaryColor}
                          onChange={(e) => setNewTheme({ ...newTheme, secondaryColor: e.target.value })}
                          className="w-16 h-10"
                        />
                        <Input
                          value={newTheme.secondaryColor}
                          onChange={(e) => setNewTheme({ ...newTheme, secondaryColor: e.target.value })}
                          className="flex-1"
                        />
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="accent-color">Couleur d'accent</Label>
                      <div className="flex space-x-2">
                        <Input
                          type="color"
                          id="accent-color"
                          value={newTheme.accentColor}
                          onChange={(e) => setNewTheme({ ...newTheme, accentColor: e.target.value })}
                          className="w-16 h-10"
                        />
                        <Input
                          value={newTheme.accentColor}
                          onChange={(e) => setNewTheme({ ...newTheme, accentColor: e.target.value })}
                          className="flex-1"
                        />
                      </div>
                    </div>

                    <div>
                      <Label htmlFor="text-color">Couleur du texte</Label>
                      <div className="flex space-x-2">
                        <Input
                          type="color"
                          id="text-color"
                          value={newTheme.textColor}
                          onChange={(e) => setNewTheme({ ...newTheme, textColor: e.target.value })}
                          className="w-16 h-10"
                        />
                        <Input
                          value={newTheme.textColor}
                          onChange={(e) => setNewTheme({ ...newTheme, textColor: e.target.value })}
                          className="flex-1"
                        />
                      </div>
                    </div>
                  </div>

                  <div>
                    <Label htmlFor="font-family">Police de caractères</Label>
                    <Select value={newTheme.fontFamily} onValueChange={(value) => setNewTheme({ ...newTheme, fontFamily: value })}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="Inter">Inter</SelectItem>
                        <SelectItem value="Roboto">Roboto</SelectItem>
                        <SelectItem value="Open Sans">Open Sans</SelectItem>
                        <SelectItem value="Poppins">Poppins</SelectItem>
                        <SelectItem value="Lato">Lato</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <Button 
                    onClick={handleCreateTheme} 
                    disabled={createThemeMutation.isPending}
                    className="w-full"
                  >
                    <Plus className="w-4 h-4 mr-2" />
                    Créer le thème
                  </Button>
                </CardContent>
              </Card>

              {/* Existing Themes */}
              <Card>
                <CardHeader>
                  <CardTitle>Thèmes existants</CardTitle>
                  <CardDescription>
                    Gérez vos thèmes créés
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  {themes.length === 0 ? (
                    <p className="text-gray-500 text-center py-8">Aucun thème créé</p>
                  ) : (
                    themes.map((theme) => (
                      <div key={theme.id} className="border rounded-lg p-4 space-y-3">
                        <div className="flex justify-between items-center">
                          <h3 className="font-semibold">{theme.name}</h3>
                          {theme.isActive && (
                            <span className="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                              Actif
                            </span>
                          )}
                        </div>
                        
                        <div className="flex space-x-2">
                          <div 
                            className="w-6 h-6 rounded border"
                            style={{ backgroundColor: theme.primaryColor }}
                            title="Couleur primaire"
                          />
                          <div 
                            className="w-6 h-6 rounded border"
                            style={{ backgroundColor: theme.secondaryColor }}
                            title="Couleur secondaire"
                          />
                          <div 
                            className="w-6 h-6 rounded border"
                            style={{ backgroundColor: theme.accentColor }}
                            title="Couleur d'accent"
                          />
                        </div>

                        {!theme.isActive && (
                          <Button 
                            size="sm" 
                            onClick={() => handleActivateTheme(theme.id)}
                            disabled={activateThemeMutation.isPending}
                          >
                            Activer ce thème
                          </Button>
                        )}
                      </div>
                    ))
                  )}
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Content Tab */}
          <TabsContent value="content" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Contenus personnalisables</CardTitle>
                <CardDescription>
                  Modifiez les textes et contenus de votre plateforme
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                {contents.length === 0 ? (
                  <p className="text-gray-500 text-center py-8">Aucun contenu personnalisable</p>
                ) : (
                  contents.map((content) => (
                    <div key={content.id} className="border rounded-lg p-4">
                      <div className="mb-3">
                        <Label className="text-sm font-medium text-gray-700">
                          {content.blockKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                        </Label>
                        <p className="text-xs text-gray-500">Type: {content.blockType}</p>
                      </div>
                      
                      {content.blockType === 'html' ? (
                        <Textarea
                          defaultValue={content.content}
                          onBlur={(e) => handleUpdateContent(content.id, e.target.value)}
                          className="min-h-[100px]"
                          placeholder="Contenu HTML..."
                        />
                      ) : (
                        <Input
                          defaultValue={content.content}
                          onBlur={(e) => handleUpdateContent(content.id, e.target.value)}
                          placeholder="Contenu texte..."
                        />
                      )}
                      
                      <div className="mt-2 flex justify-end">
                        <Button 
                          size="sm" 
                          variant="outline"
                          onClick={() => handleUpdateContent(content.id, content.content)}
                        >
                          <Save className="w-4 h-4 mr-1" />
                          Sauvegarder
                        </Button>
                      </div>
                    </div>
                  ))
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Menu Tab */}
          <TabsContent value="menu" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Gestion des menus</CardTitle>
                <CardDescription>
                  Personnalisez la navigation de votre plateforme
                </CardDescription>
              </CardHeader>
              <CardContent>
                {menuItems.length === 0 ? (
                  <p className="text-gray-500 text-center py-8">Aucun élément de menu</p>
                ) : (
                  <div className="space-y-3">
                    {menuItems
                      .sort((a, b) => (a.sortOrder || 0) - (b.sortOrder || 0))
                      .map((item) => (
                        <div key={item.id} className="flex items-center justify-between border rounded-lg p-3">
                          <div className="flex items-center space-x-3">
                            {item.icon && <span className="text-gray-400">{item.icon}</span>}
                            <div>
                              <p className="font-medium">{item.label}</p>
                              <p className="text-sm text-gray-500">{item.url}</p>
                            </div>
                          </div>
                          
                          <div className="flex items-center space-x-2">
                            <span className="text-xs text-gray-400">#{item.sortOrder}</span>
                            <Button size="sm" variant="ghost">
                              <Trash2 className="w-4 h-4" />
                            </Button>
                          </div>
                        </div>
                      ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}