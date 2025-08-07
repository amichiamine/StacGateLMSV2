import { useState, useEffect } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { 
  BookOpen, 
  Plus, 
  Search, 
  Star, 
  Clock, 
  Users, 
  Play, 
  Calendar,
  Video,
  FileText,
  Award,
  TrendingUp,
  Euro
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { useAuth } from "@/hooks/useAuth";
import { apiRequest } from "@/lib/queryClient";

interface Course {
  id: string;
  establishmentId: string;
  title: string;
  description: string;
  shortDescription?: string;
  category: string;
  type: string;
  price: string;
  isFree: boolean;
  duration: number;
  level: string;
  language: string;
  tags?: string;
  imageUrl?: string;
  thumbnailUrl?: string;
  videoTrailerUrl?: string;
  instructorId?: string;
  isPublic: boolean;
  isActive: boolean;
  rating: string;
  enrollmentCount: number;
  createdAt: string;
  updatedAt: string;
}

export default function CoursesPage() {
  // Tous les hooks AVANT toute condition
  const { user, isLoading: authLoading, isAuthenticated } = useAuth();
  const { toast } = useToast();
  const queryClient = useQueryClient();
  
  const [searchTerm, setSearchTerm] = useState("");
  const [categoryFilter, setCategoryFilter] = useState("all");
  const [levelFilter, setLevelFilter] = useState("all");
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [newCourse, setNewCourse] = useState({
    title: "",
    description: "",
    shortDescription: "",
    category: "web",
    type: "cours",
    price: "0",
    isFree: true,
    duration: 60,
    level: "débutant",
    language: "français",
    tags: "",
    isPublic: true,
    isActive: true,
  });
  
  // Query pour récupérer les cours
  const { data: courses = [], isLoading: coursesLoading, error } = useQuery<Course[]>({
    queryKey: ['/api/courses'],
    enabled: isAuthenticated
  });

  // Mutation pour créer un cours
  const createCourseMutation = useMutation({
    mutationFn: async (courseData: any) => {
      const response = await fetch('/api/courses', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(courseData)
      });
      if (!response.ok) throw new Error('Failed to create course');
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Succès",
        description: "Le cours a été créé avec succès.",
      });
      queryClient.invalidateQueries({ queryKey: ['/api/courses'] });
      setShowCreateModal(false);
      setNewCourse({
        title: "",
        description: "",
        shortDescription: "",
        category: "web",
        type: "cours",
        price: "0",
        isFree: true,
        duration: 60,
        level: "débutant",
        language: "français",
        tags: "",
        isPublic: true,
        isActive: true,
      });
    },
    onError: (error: any) => {
      toast({
        title: "Erreur",
        description: "Impossible de créer le cours.",
        variant: "destructive",
      });
    },
  });

  const handleCreateCourse = () => {
    if (!newCourse.title || !newCourse.description || !newCourse.category) {
      toast({
        title: "Erreur",
        description: "Veuillez remplir tous les champs requis.",
        variant: "destructive",
      });
      return;
    }

    createCourseMutation.mutate({
      ...newCourse,
      establishmentId: user?.establishmentId || '',
      instructorId: user?.id || '',
      rating: "0.0",
      enrollmentCount: 0
    });
  };
  
  // Redirection si non authentifié
  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      toast({
        title: "Accès refusé",
        description: "Vous devez être connecté pour voir les cours.",
        variant: "destructive",
      });
      setTimeout(() => {
        window.location.href = "/login";
      }, 1500);
    }
  }, [isAuthenticated, authLoading, toast]);

  // Early return si non authentifié ou en cours de chargement
  if (authLoading || !isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (authLoading || coursesLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
        <div className="text-center">
          <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600 dark:text-gray-400">Chargement des cours...</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Card className="w-full max-w-md">
          <CardContent className="p-6">
            <p className="text-center text-gray-600">Redirection vers la connexion...</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
        <Card className="w-full max-w-md">
          <CardContent className="p-6">
            <div className="text-center">
              <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <FileText className="w-8 h-8 text-red-600" />
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">Erreur de chargement</h3>
              <p className="text-gray-600 mb-4">Impossible de récupérer les cours.</p>
              <Button onClick={() => window.location.reload()}>
                Actualiser
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  // Filtrer les cours
  const filteredCourses = courses.filter(course => {
    const matchesSearch = course.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         course.description.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesCategory = categoryFilter === "all" || course.category === categoryFilter;
    const matchesLevel = levelFilter === "all" || course.level === levelFilter;
    
    return matchesSearch && matchesCategory && matchesLevel;
  });

  const categories = Array.from(new Set(courses.map(course => course.category)));
  const isInstructor = user?.role === "formateur" || user?.role === "admin" || user?.role === "manager" || user?.role === "super_admin";

  const formatPrice = (price: string, isFree: boolean) => {
    if (isFree) return "Gratuit";
    return `${price}€`;
  };

  const getLevelBadgeColor = (level: string) => {
    switch (level) {
      case 'debutant': return 'bg-green-100 text-green-800';
      case 'intermediaire': return 'bg-yellow-100 text-yellow-800';
      case 'avance': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const formatLevel = (level: string) => {
    switch (level) {
      case 'debutant': return 'Débutant';
      case 'intermediaire': return 'Intermédiaire';
      case 'avance': return 'Avancé';
      default: return level;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
      {/* Header responsive avec navigation tactile */}
      <header className="sticky top-0 z-50 bg-white/90 dark:bg-gray-900/90 backdrop-blur-lg shadow-lg border-b border-gray-200 dark:border-gray-700">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3 sm:space-x-4">
              <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-r from-green-600 to-blue-600 rounded-2xl flex items-center justify-center">
                <BookOpen className="w-5 h-5 sm:w-6 sm:h-6 text-white" />
              </div>
              <div>
                <h1 className="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">
                  Formations & Cours
                </h1>
                <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
                  Découvrez et gérez vos formations ({courses.length} cours disponibles)
                </p>
              </div>
            </div>
            <div className="flex items-center space-x-2 sm:space-x-4">
              <Button 
                variant="outline" 
                size="sm"
                onClick={() => window.location.href = '/dashboard'}
                className="hidden sm:flex"
              >
                Tableau de bord
              </Button>
              <Button 
                variant="outline" 
                size="sm"
                onClick={() => window.location.href = '/dashboard'}
                className="sm:hidden"
              >
                Dashboard
              </Button>
            </div>
          </div>
        </div>
      </header>

      <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        {/* Statistiques rapides */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6 sm:mb-8">
          <Card className="bg-white/80 backdrop-blur-sm">
            <CardContent className="p-4 text-center">
              <BookOpen className="w-8 h-8 text-blue-600 mx-auto mb-2" />
              <p className="text-2xl font-bold text-gray-900">{courses.length}</p>
              <p className="text-sm text-gray-600">Cours total</p>
            </CardContent>
          </Card>
          <Card className="bg-white/80 backdrop-blur-sm">
            <CardContent className="p-4 text-center">
              <Award className="w-8 h-8 text-green-600 mx-auto mb-2" />
              <p className="text-2xl font-bold text-gray-900">{categories.length}</p>
              <p className="text-sm text-gray-600">Catégories</p>
            </CardContent>
          </Card>
          <Card className="bg-white/80 backdrop-blur-sm">
            <CardContent className="p-4 text-center">
              <Clock className="w-8 h-8 text-orange-600 mx-auto mb-2" />
              <p className="text-2xl font-bold text-gray-900">
                {courses.reduce((total, course) => total + course.duration, 0)}h
              </p>
              <p className="text-sm text-gray-600">Durée totale</p>
            </CardContent>
          </Card>
          <Card className="bg-white/80 backdrop-blur-sm">
            <CardContent className="p-4 text-center">
              <Star className="w-8 h-8 text-yellow-600 mx-auto mb-2" />
              <p className="text-2xl font-bold text-gray-900">
                {courses.length > 0 ? (courses.reduce((total, course) => total + parseFloat(course.rating), 0) / courses.length).toFixed(1) : '0'}
              </p>
              <p className="text-sm text-gray-600">Note moyenne</p>
            </CardContent>
          </Card>
        </div>

        {/* Filtres et recherche */}
        <div className="mb-6 sm:mb-8 space-y-4 lg:space-y-0 lg:flex lg:items-center lg:justify-between">
          <div className="flex flex-col sm:flex-row gap-4 flex-1">
            <div className="relative flex-1 max-w-md">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
              <Input
                placeholder="Rechercher un cours..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10 bg-white/80 backdrop-blur-sm border-gray-200/50"
              />
            </div>
            <Select value={categoryFilter} onValueChange={setCategoryFilter}>
              <SelectTrigger className="w-full sm:w-48 bg-white/80 backdrop-blur-sm border-gray-200/50">
                <SelectValue placeholder="Catégorie" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Toutes les catégories</SelectItem>
                {categories.map(category => (
                  <SelectItem key={category} value={category}>{category}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Select value={levelFilter} onValueChange={setLevelFilter}>
              <SelectTrigger className="w-full sm:w-48 bg-white/80 backdrop-blur-sm border-gray-200/50">
                <SelectValue placeholder="Niveau" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Tous les niveaux</SelectItem>
                <SelectItem value="debutant">Débutant</SelectItem>
                <SelectItem value="intermediaire">Intermédiaire</SelectItem>
                <SelectItem value="avance">Avancé</SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          {/* Bouton pour créer un cours (pour instructeurs) */}
          {isInstructor && (
            <div className="flex justify-end">
              <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
                <DialogTrigger asChild>
                  <Button className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white">
                    <Plus className="w-4 h-4 mr-2" />
                    Créer un cours
                  </Button>
                </DialogTrigger>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                  <DialogHeader>
                    <DialogTitle>Créer un nouveau cours</DialogTitle>
                    <DialogDescription>
                      Ajoutez un nouveau cours à votre établissement
                    </DialogDescription>
                  </DialogHeader>
                  
                  <div className="space-y-4 py-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <Label htmlFor="title">Titre du cours *</Label>
                        <Input
                          id="title"
                          value={newCourse.title}
                          onChange={(e) => setNewCourse({...newCourse, title: e.target.value})}
                          placeholder="Ex: Introduction au JavaScript"
                        />
                      </div>
                      <div>
                        <Label htmlFor="category">Catégorie *</Label>
                        <Input
                          id="category"
                          value={newCourse.category}
                          onChange={(e) => setNewCourse({...newCourse, category: e.target.value})}
                          placeholder="Ex: Développement Web"
                        />
                      </div>
                    </div>
                    
                    <div>
                      <Label htmlFor="description">Description *</Label>
                      <Textarea
                        id="description"
                        value={newCourse.description}
                        onChange={(e) => setNewCourse({...newCourse, description: e.target.value})}
                        placeholder="Décrivez le contenu et les objectifs du cours..."
                        rows={3}
                      />
                    </div>
                    
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                      <div>
                        <Label htmlFor="type">Type</Label>
                        <Select value={newCourse.type} onValueChange={(value) => setNewCourse({...newCourse, type: value})}>
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="asynchrone">Asynchrone</SelectItem>
                            <SelectItem value="synchrone">Synchrone</SelectItem>
                            <SelectItem value="mixte">Mixte</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div>
                        <Label htmlFor="level">Niveau</Label>
                        <Select value={newCourse.level} onValueChange={(value) => setNewCourse({...newCourse, level: value})}>
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="debutant">Débutant</SelectItem>
                            <SelectItem value="intermediaire">Intermédiaire</SelectItem>
                            <SelectItem value="avance">Avancé</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div>
                        <Label htmlFor="duration">Durée (heures)</Label>
                        <Input
                          id="duration"
                          type="number"
                          value={newCourse.duration}
                          onChange={(e) => setNewCourse({...newCourse, duration: parseInt(e.target.value) || 0})}
                          placeholder="0"
                        />
                      </div>
                    </div>
                    
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <Label htmlFor="price">Prix (€)</Label>
                        <Input
                          id="price"
                          value={newCourse.price}
                          onChange={(e) => setNewCourse({...newCourse, price: e.target.value})}
                          placeholder="0.00"
                          disabled={newCourse.isFree}
                        />
                      </div>
                      <div className="flex items-center space-x-2 pt-8">
                        <input
                          type="checkbox"
                          id="isFree"
                          checked={newCourse.isFree}
                          onChange={(e) => setNewCourse({...newCourse, isFree: e.target.checked, price: e.target.checked ? "0" : newCourse.price})}
                          className="rounded"
                        />
                        <Label htmlFor="isFree">Cours gratuit</Label>
                      </div>
                    </div>
                  </div>
                  
                  <div className="flex justify-end space-x-2">
                    <Button variant="outline" onClick={() => setShowCreateModal(false)}>
                      Annuler
                    </Button>
                    <Button 
                      onClick={handleCreateCourse}
                      disabled={createCourseMutation.isPending}
                      className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700"
                    >
                      {createCourseMutation.isPending ? "Création..." : "Créer le cours"}
                    </Button>
                  </div>
                </DialogContent>
              </Dialog>
            </div>
          )}
        </div>

        {/* Grille des cours avec design responsive et tactile */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
          {filteredCourses.map((course) => (
            <Card key={course.id} className="group hover:shadow-xl transition-all duration-300 bg-white/80 backdrop-blur-sm border border-gray-200/50 overflow-hidden">
              <div className="aspect-video bg-gradient-to-br from-blue-100 to-indigo-100 relative overflow-hidden">
                {course.thumbnailUrl || course.imageUrl ? (
                  <img 
                    src={course.thumbnailUrl || course.imageUrl} 
                    alt={course.title}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center">
                    <BookOpen className="w-12 h-12 text-blue-600/50" />
                  </div>
                )}
                <div className="absolute top-3 left-3">
                  <Badge className={`${getLevelBadgeColor(course.level)} border-0`}>
                    {formatLevel(course.level)}
                  </Badge>
                </div>
                <div className="absolute top-3 right-3">
                  <Badge variant="secondary" className="bg-white/90 text-gray-900 border-0">
                    {course.type}
                  </Badge>
                </div>
              </div>
              
              <CardContent className="p-4 sm:p-6">
                <div className="space-y-3">
                  <div>
                    <h3 className="font-bold text-lg text-gray-900 line-clamp-2 group-hover:text-blue-600 transition-colors">
                      {course.title}
                    </h3>
                    <p className="text-sm text-gray-600 line-clamp-2">
                      {course.description}
                    </p>
                  </div>
                  
                  <div className="flex items-center justify-between text-sm text-gray-500">
                    <div className="flex items-center space-x-1">
                      <Clock className="w-4 h-4" />
                      <span>{course.duration}h</span>
                    </div>
                    <div className="flex items-center space-x-1">
                      <Star className="w-4 h-4 text-yellow-500" />
                      <span>{course.rating}</span>
                    </div>
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <Badge variant="outline" className="text-xs">
                      {course.category}
                    </Badge>
                    <div className="flex items-center space-x-1 text-lg font-bold text-gray-900">
                      {!course.isFree && <Euro className="w-4 h-4" />}
                      <span>{formatPrice(course.price, course.isFree)}</span>
                    </div>
                  </div>
                  
                  <Button 
                    className="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white"
                    size="sm"
                  >
                    <Play className="w-4 h-4 mr-2" />
                    Voir le cours
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Message si aucun cours trouvé */}
        {filteredCourses.length === 0 && courses.length > 0 && (
          <div className="text-center py-12">
            <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <Search className="w-8 h-8 text-gray-400" />
            </div>
            <h3 className="text-lg font-semibold text-gray-900 mb-2">Aucun cours trouvé</h3>
            <p className="text-gray-600 mb-4">Essayez de modifier vos critères de recherche.</p>
            <Button 
              variant="outline" 
              onClick={() => {
                setSearchTerm("");
                setCategoryFilter("all");
                setLevelFilter("all");
              }}
            >
              Réinitialiser les filtres
            </Button>
          </div>
        )}

        {/* Message si aucun cours du tout */}
        {courses.length === 0 && (
          <div className="text-center py-12">
            <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <BookOpen className="w-8 h-8 text-blue-600" />
            </div>
            <h3 className="text-lg font-semibold text-gray-900 mb-2">Aucun cours disponible</h3>
            <p className="text-gray-600 mb-4">Les cours seront bientôt disponibles dans votre établissement.</p>
            {isInstructor && (
              <Button>
                <Plus className="w-4 h-4 mr-2" />
                Créer le premier cours
              </Button>
            )}
          </div>
        )}
      </div>
    </div>
  );
}